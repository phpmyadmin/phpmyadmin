<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Bookmarks\Bookmark;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\File;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Plugins\Import\ImportFormat;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;
use Throwable;

use function __;
use function _ngettext;
use function in_array;
use function ini_get;
use function ini_parse_quantity;
use function ini_set;
use function is_array;
use function is_link;
use function is_numeric;
use function is_string;
use function is_uploaded_file;
use function mb_strlen;
use function min;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function time;

#[Route('/import', ['GET', 'POST'])]
final readonly class ImportController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Import $import,
        private Sql $sql,
        private DatabaseInterface $dbi,
        private BookmarkRepository $bookmarkRepository,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        ImportSettings::$charsetOfFile = $request->getParsedBodyParamAsString('charset_of_file', '');
        $format = $request->getParsedBodyParamAsString('format', '');
        ImportSettings::$importType = $request->getParsedBodyParamAsString('import_type', '');
        Current::$messageToShow = $request->getParsedBodyParamAsString('message_to_show', '');
        ImportSettings::$skipQueries = (int) $request->getParsedBodyParamAsStringOrNull('skip_queries');
        ImportSettings::$localImportFile = $request->getParsedBodyParamAsString('local_import_file', '');
        if ($request->hasBodyParam('show_as_php')) {
            Sql::$showAsPhp = (bool) $request->getParsedBodyParam('show_as_php');
        }

        // reset import messages for ajax request
        $_SESSION['Import_message']['message'] = null;
        $_SESSION['Import_message']['go_back_url'] = null;
        // default values
        ResponseRenderer::$reload = false;

        $ajaxReload = [];
        Import::$importText = '';
        // Are we just executing plain query or sql file?
        // (eg. non import, but query box/window run)
        if (Current::$sqlQuery !== '') {
            // apply values for parameters
            /** @var array<string, string>|null $parameters */
            $parameters = $request->getParsedBodyParam('parameters');
            if ($request->hasBodyParam('parameterized') && is_array($parameters)) {
                foreach ($parameters as $parameter => $replacementValue) {
                    if (! is_numeric($replacementValue)) {
                        $replacementValue = $this->dbi->quoteString($replacementValue);
                    }

                    $quoted = preg_quote($parameter, '/');
                    // making sure that :param does not apply values to :param1
                    Current::$sqlQuery = preg_replace(
                        '/' . $quoted . '([^a-zA-Z0-9_])/',
                        $replacementValue . '${1}',
                        Current::$sqlQuery,
                    ) ?? '';
                    // for parameters that appear at the end of the string
                    Current::$sqlQuery = preg_replace(
                        '/' . $quoted . '$/',
                        $replacementValue,
                        Current::$sqlQuery,
                    ) ?? '';
                }
            }

            // run SQL query
            Import::$importText = Current::$sqlQuery;
            ImportSettings::$importType = 'query';
            $format = 'sql';
            $_SESSION['sql_from_query_box'] = true;

            // If there is a request to ROLLBACK when finished.
            if ($request->hasBodyParam('rollback_query')) {
                $this->import->handleRollbackRequest(Import::$importText);
            }

            // refresh navigation and main panels
            if (preg_match('/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', Current::$sqlQuery) === 1) {
                ResponseRenderer::$reload = true;
                $ajaxReload['reload'] = true;
            }

            // refresh navigation panel only
            if (preg_match('/^(CREATE|ALTER)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', Current::$sqlQuery) === 1) {
                $ajaxReload['reload'] = true;
            }

            // do a dynamic reload if table is RENAMED
            // (by sending the instruction to the AJAX response handler)
            if (
                preg_match(
                    '/^RENAME\s+TABLE\s+(.*?)\s+TO\s+(.*?)($|;|\s)/i',
                    Current::$sqlQuery,
                    $renameTableNames,
                ) === 1
            ) {
                $ajaxReload['reload'] = true;
                $ajaxReload['table_name'] = Util::unQuote($renameTableNames[2]);
            }

            Current::$sqlQuery = '';
        } elseif ($request->hasBodyParam('id_bookmark')) {
            // run bookmark
            ImportSettings::$importType = 'query';
            $format = 'sql';
        }

        // If we didn't get any parameters, either user called this directly, or
        // upload limit has been reached, let's assume the second possibility.
        $getParams = $request->getQueryParams();
        $postParams = $request->getParsedBody();
        if ($postParams === [] && $getParams === []) {
            Current::$message = Message::error(
                __(
                    'You probably tried to upload a file that is too large. Please refer ' .
                    'to %sdocumentation%s for a workaround for this limit.',
                ),
            );
            Current::$message->addParam('[doc@faq1-16]');
            Current::$message->addParam('[/doc]');

            // so we can obtain the message
            $_SESSION['Import_message']['message'] = Current::$message->getDisplay();
            $_SESSION['Import_message']['go_back_url'] = UrlParams::$goto;

            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Current::$message);

            return $this->response->response();
        }

        // Add console message id to response output
        $consoleMessageId = $request->getParsedBodyParam('console_message_id');
        if ($consoleMessageId !== null) {
            $this->response->addJSON('console_message_id', $consoleMessageId);
        }

        $importFormat = ImportFormat::tryFrom($format);
        if ($importFormat === null) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error(__('Incorrect format parameter'))->getDisplay());

            return $this->response->response();
        }

        if (Current::$table !== '' && Current::$database !== '') {
            UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];
        } elseif (Current::$database !== '') {
            UrlParams::$params = ['db' => Current::$database];
        } else {
            UrlParams::$params = [];
        }

        // Create error and goto url
        if (ImportSettings::$importType === 'table') {
            UrlParams::$goto = Url::getFromRoute('/table/import');
        } elseif (ImportSettings::$importType === 'database') {
            UrlParams::$goto = Url::getFromRoute('/database/import');
        } elseif (ImportSettings::$importType === 'server') {
            UrlParams::$goto = Url::getFromRoute('/server/import');
        } elseif (UrlParams::$goto === '' || preg_match('@^index\.php$@i', UrlParams::$goto) !== 1) {
            if (Current::$table !== '' && Current::$database !== '') {
                UrlParams::$goto = Url::getFromRoute('/table/structure');
            } elseif (Current::$database !== '') {
                UrlParams::$goto = Url::getFromRoute('/database/structure');
            } else {
                UrlParams::$goto = Url::getFromRoute('/server/sql');
            }
        }

        Import::$errorUrl = UrlParams::$goto . Url::getCommon(UrlParams::$params, '&');
        $_SESSION['Import_message']['go_back_url'] = Import::$errorUrl;

        if (Current::$database !== '') {
            $this->dbi->selectDb(Current::$database);
        }

        Util::setTimeLimit();
        if ($this->config->config->MemoryLimit !== '' && $this->config->config->MemoryLimit !== '0') {
            ini_set('memory_limit', $this->config->config->MemoryLimit);
        }

        ImportSettings::$timestamp = time();
        ImportSettings::$maximumTime = 0;
        $maxExecutionTime = (int) ini_get('max_execution_time');
        if ($request->hasBodyParam('allow_interrupt') && $maxExecutionTime >= 1) {
            ImportSettings::$maximumTime = $maxExecutionTime - 1; // Give 1 second for phpMyAdmin to exit nicely
        }

        // set default values
        ImportSettings::$timeoutPassed = false;
        Import::$hasError = false;
        ImportSettings::$readMultiply = 1;
        ImportSettings::$finished = false;
        ImportSettings::$offset = 0;
        ImportSettings::$maxSqlLength = 0;
        Current::$sqlQuery = '';
        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$goSql = false;
        ImportSettings::$executedQueries = 0;
        ImportSettings::$runQuery = true;
        ImportSettings::$charsetConversion = false;
        $resetCharset = false;
        ImportSettings::$message = 'Sorry an unexpected error happened!';

        Import::$result = false;

        // Bookmark Support: get a query back from bookmark if required
        $idBookmark = (int) $request->getParsedBodyParamAsStringOrNull('id_bookmark');
        $actionBookmark = (int) $request->getParsedBodyParamAsStringOrNull('action_bookmark');
        if ($idBookmark !== 0) {
            switch ($actionBookmark) {
                case 0: // bookmarked query that have to be run
                    $bookmark = $this->bookmarkRepository->get(
                        $request->hasBodyParam('action_bookmark_all') ? null : $this->config->selectedServer['user'],
                        $idBookmark,
                    );
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $bookmarkVariables = $request->getParsedBodyParam('bookmark_variable');
                    if (is_array($bookmarkVariables)) {
                        Import::$importText = $bookmark->applyVariables($bookmarkVariables);
                    } else {
                        Import::$importText = $bookmark->getQuery();
                    }

                    // refresh navigation and main panels
                    if (preg_match('/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', Import::$importText) === 1) {
                        ResponseRenderer::$reload = true;
                        $ajaxReload['reload'] = true;
                    }

                    // refresh navigation panel only
                    if (
                        preg_match('/^(CREATE|ALTER)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', Import::$importText) === 1
                    ) {
                        $ajaxReload['reload'] = true;
                    }

                    break;
                case 1: // bookmarked query that have to be displayed
                    $bookmark = $this->bookmarkRepository->get($this->config->selectedServer['user'], $idBookmark);
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    Import::$importText = $bookmark->getQuery();
                    if ($request->isAjax()) {
                        Current::$message = Message::success(__('Showing bookmark'));
                        $this->response->setRequestStatus(Current::$message->isSuccess());
                        $this->response->addJSON('message', Current::$message);
                        $this->response->addJSON('sql_query', Import::$importText);
                        $this->response->addJSON('action_bookmark', $actionBookmark);

                        return $this->response->response();
                    }

                    ImportSettings::$runQuery = false;
                    break;
                case 2: // bookmarked query that have to be deleted
                    $bookmark = $this->bookmarkRepository->get($this->config->selectedServer['user'], $idBookmark);
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $bookmark->delete();
                    if ($request->isAjax()) {
                        Current::$message = Message::success(
                            __('The bookmark has been deleted.'),
                        );
                        $this->response->setRequestStatus(Current::$message->isSuccess());
                        $this->response->addJSON('message', Current::$message);
                        $this->response->addJSON('action_bookmark', $actionBookmark);
                        $this->response->addJSON('id_bookmark', $idBookmark);

                        return $this->response->response();
                    }

                    ImportSettings::$runQuery = false;
                    Import::$hasError = true; // this is kind of hack to skip processing the query
                    break;
            }
        }

        // Do no run query if we show PHP code
        if (Sql::$showAsPhp !== null) {
            ImportSettings::$runQuery = false;
            ImportSettings::$goSql = true;
        }

        // We can not read all at once, otherwise we can run out of memory
        // Calculate value of the limit
        $memoryLimit = (string) ini_get('memory_limit');
        $memoryLimit = ini_parse_quantity($memoryLimit);
        // 2 MB as default
        if ($memoryLimit === 0) {
            $memoryLimit = 2 * 1024 * 1024;
        }

        // In case no memory limit we work on 10MB chunks
        if ($memoryLimit === -1) {
            $memoryLimit = 10 * 1024 * 1024;
        }

        // Just to be sure, there might be lot of memory needed for uncompression
        ImportSettings::$readLimit = $memoryLimit / 8;

        // handle filenames
        if (
            isset($_FILES['import_file'])
            && is_array($_FILES['import_file'])
            && isset($_FILES['import_file']['name'], $_FILES['import_file']['tmp_name'])
            && is_string($_FILES['import_file']['name'])
            && is_string($_FILES['import_file']['tmp_name'])
        ) {
            ImportSettings::$importFile = $_FILES['import_file']['tmp_name'];
            ImportSettings::$importFileName = $_FILES['import_file']['name'];
        }

        if (ImportSettings::$localImportFile !== '' && $this->config->config->UploadDir !== '') {
            // sanitize $local_import_file as it comes from a POST
            ImportSettings::$localImportFile = Core::securePath(ImportSettings::$localImportFile);

            ImportSettings::$importFile = Util::userDir($this->config->config->UploadDir)
                . ImportSettings::$localImportFile;

            /**
             * Do not allow symlinks to avoid security issues
             * (user can create symlink to file they can not access,
             * but phpMyAdmin can).
             */
            if (@is_link(ImportSettings::$importFile)) {
                ImportSettings::$importFile = 'none';
            }
        } elseif (ImportSettings::$importFile === '' || ! is_uploaded_file(ImportSettings::$importFile)) {
            ImportSettings::$importFile = 'none';
        }

        // Do we have file to import?

        if (ImportSettings::$importFile !== 'none' && ! Import::$hasError) {
            /**
             *  Handle file compression
             */
            $importHandle = new File(ImportSettings::$importFile);
            $importHandle->checkUploadedFile();
            if ($importHandle->isError()) {
                /** @var Message $errorMessage */
                $errorMessage = $importHandle->getError();

                $importHandle->close();

                $_SESSION['Import_message']['message'] = $errorMessage->getDisplay();

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $errorMessage->getDisplay());
                $this->response->addHTML($errorMessage->getDisplay());

                return $this->response->response();
            }

            $importHandle->setDecompressContent(true);
            $importHandle->open();
            if ($importHandle->isError()) {
                /** @var Message $errorMessage */
                $errorMessage = $importHandle->getError();

                $importHandle->close();

                $_SESSION['Import_message']['message'] = $errorMessage->getDisplay();

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $errorMessage->getDisplay());
                $this->response->addHTML($errorMessage->getDisplay());

                return $this->response->response();
            }
        } elseif (! Import::$hasError && Import::$importText === '') {
            Current::$message = Message::error(
                __(
                    'No data was received to import. Either no file name was ' .
                    'submitted, or the file size exceeded the maximum size permitted ' .
                    'by your PHP configuration. See [doc@faq1-16]FAQ 1.16[/doc].',
                ),
            );

            $_SESSION['Import_message']['message'] = Current::$message->getDisplay();

            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Current::$message->getDisplay());
            $this->response->addHTML(Current::$message->getDisplay());

            return $this->response->response();
        }

        // Convert the file's charset if necessary
        if (
            Encoding::isSupported()
            && ImportSettings::$charsetOfFile !== '' && ImportSettings::$charsetOfFile !== 'utf-8'
            && in_array(ImportSettings::$charsetOfFile, Encoding::listEncodings(), true)
        ) {
            ImportSettings::$charsetConversion = true;
        } elseif (ImportSettings::$charsetOfFile !== '' && ImportSettings::$charsetOfFile !== 'utf-8') {
            $this->dbi->query('SET NAMES \'' . ImportSettings::$charsetOfFile . '\'');
            // We can not show query in this case, it is in different charset
            ImportSettings::$sqlQueryDisabled = true;
            $resetCharset = true;
        }

        // Something to skip? (because timeout has passed)
        if (! Import::$hasError && $request->hasBodyParam('skip')) {
            $originalSkip = $skip = (int) $request->getParsedBodyParamAsStringOrNull('skip');
            while ($skip > 0 && ! ImportSettings::$finished) {
                $this->import->getNextChunk(
                    $importHandle ?? null,
                    min($skip, ImportSettings::$readLimit),
                );
                // Disable read progressivity, otherwise we eat all memory!
                ImportSettings::$readMultiply = 1;
                $skip -= ImportSettings::$readLimit;
            }

            unset($skip);
        }

        // This array contain the data like number of valid sql queries in the statement
        // and complete valid sql statement (which affected for rows)
        $queriesToBeExecuted = [];

        if (! Import::$hasError) {
            $importPlugin = new ($importFormat->getClassName());

            $importPlugin->setImportOptions($request);

            // Do the real import
            $defaultFkCheck = ForeignKey::handleDisableCheckInit();
            try {
                $queriesToBeExecuted = $importPlugin->doImport($importHandle ?? null);
                ForeignKey::handleDisableCheckCleanup($defaultFkCheck);
            } catch (Throwable $e) {
                ForeignKey::handleDisableCheckCleanup($defaultFkCheck);

                throw $e;
            }
        }

        if (isset($importHandle)) {
            $importHandle->close();
        }

        // Reset charset back, if we did some changes
        if ($resetCharset) {
            $this->dbi->query('SET CHARACTER SET ' . $this->dbi->getDefaultCharset());
            $this->dbi->setCollation($this->dbi->getDefaultCollation());
        }

        // Show correct message
        if ($idBookmark !== 0 && $actionBookmark === 2) {
            Current::$message = Message::success(__('The bookmark has been deleted.'));
            Current::$displayQuery = Import::$importText;
            Import::$hasError = false; // unset error marker, it was used just to skip processing
        } elseif ($idBookmark !== 0 && $actionBookmark === 1) {
            Current::$message = Message::notice(__('Showing bookmark'));
        } elseif (ImportSettings::$finished && ! Import::$hasError) {
            // Do not display the query with message, we do it separately
            Current::$displayQuery = ';';
            if (ImportSettings::$importType !== 'query') {
                Current::$message = Message::success(
                    '<em>'
                    . _ngettext(
                        'Import has been successfully finished, %d query executed.',
                        'Import has been successfully finished, %d queries executed.',
                        ImportSettings::$executedQueries,
                    )
                    . '</em>',
                );
                Current::$message->addParam(ImportSettings::$executedQueries);

                if (ImportSettings::$importNotice !== '') {
                    Current::$message->addHtml(ImportSettings::$importNotice);
                }

                if (ImportSettings::$localImportFile !== '') {
                    Current::$message->addText('(' . ImportSettings::$localImportFile . ')');
                } elseif (
                    isset($_FILES['import_file'])
                    && is_array($_FILES['import_file'])
                    && isset($_FILES['import_file']['name'])
                    && is_string($_FILES['import_file']['name'])
                ) {
                    Current::$message->addText('(' . $_FILES['import_file']['name'] . ')');
                }
            }
        }

        // Did we hit timeout? Tell it user.
        if (ImportSettings::$timeoutPassed) {
            UrlParams::$params['timeout_passed'] = '1';
            UrlParams::$params['offset'] = ImportSettings::$offset;
            if (ImportSettings::$localImportFile !== '') {
                UrlParams::$params['local_import_file'] = ImportSettings::$localImportFile;
            }

            $importUrl = Import::$errorUrl = UrlParams::$goto . Url::getCommon(UrlParams::$params, '&');

            Current::$message = Message::error(
                __(
                    'Script timeout passed, if you want to finish import,'
                    . ' please %sresubmit the same file%s and import will resume.',
                ),
            );
            Current::$message->addParamHtml('<a href="' . $importUrl . '">');
            Current::$message->addParamHtml('</a>');

            if (ImportSettings::$offset === 0 || (isset($originalSkip) && $originalSkip === ImportSettings::$offset)) {
                Current::$message->addText(
                    __(
                        'However on last run no data has been parsed,'
                        . ' this usually means phpMyAdmin won\'t be able to'
                        . ' finish this import unless you increase php time limits.',
                    ),
                );
            }
        }

        // if there is any message, copy it into $_SESSION as well,
        // so we can obtain it by AJAX call
        if (Current::$message instanceof Message) {
            $_SESSION['Import_message']['message'] = Current::$message->getDisplay();
        }

        // Parse and analyze the query, for correct db and table name
        // in case of a query typed in the query window
        // (but if the query is too large, in case of an imported file, the parser
        //  can choke on it so avoid parsing)
        $sqlLength = mb_strlen(Current::$sqlQuery);
        if ($sqlLength <= $this->config->settings['MaxCharactersInDisplayedSQL']) {
            [$statementInfo, Current::$database, $tableFromSql, $reloadNeeded] = ParseAnalyze::sqlQuery(
                Current::$sqlQuery,
                Current::$database,
            );

            ResponseRenderer::$reload = $reloadNeeded;

            if (Current::$table != $tableFromSql && $tableFromSql !== '') {
                Current::$table = $tableFromSql;
            }
        }

        foreach (ImportSettings::$failedQueries as $die) {
            Generator::mysqlDie($die['error'], $die['sql'], false, Import::$errorUrl, Import::$hasError);
        }

        if (ImportSettings::$goSql) {
            if ($queriesToBeExecuted === []) {
                $queriesToBeExecuted = [Current::$sqlQuery];
            }

            $htmlOutput = '';

            foreach ($queriesToBeExecuted as Current::$sqlQuery) {
                // parse sql query
                [$statementInfo, Current::$database, $tableFromSql, $reloadNeeded] = ParseAnalyze::sqlQuery(
                    Current::$sqlQuery,
                    Current::$database,
                );

                ResponseRenderer::$reload = $reloadNeeded;

                // Check if User is allowed to issue a 'DROP DATABASE' Statement
                if ($this->sql->hasNoRightsToDropDatabase($statementInfo)) {
                    Generator::mysqlDie(
                        __('"DROP DATABASE" statements are disabled.'),
                        '',
                        false,
                        $_SESSION['Import_message']['go_back_url'],
                    );
                }

                if (Current::$table != $tableFromSql && $tableFromSql !== '') {
                    Current::$table = $tableFromSql;
                }

                $htmlOutput .= $this->sql->executeQueryAndGetQueryResponse(
                    $request,
                    $statementInfo,
                    false, // is_gotofile
                    Current::$database, // db
                    Current::$table, // table
                    '', // sql_query_for_bookmark - see below
                    '', // message_to_show
                    UrlParams::$goto, // goto
                    null, // disp_query
                    '', // disp_message
                    Current::$sqlQuery,
                    Current::$sqlQuery, // complete_query
                );
            }

            // sql_query_for_bookmark is not included in Sql::executeQueryAndGetQueryResponse
            // since only one bookmark has to be added for all the queries submitted through
            // the SQL tab
            if (! empty($request->getParsedBodyParam('bkm_label')) && Import::$importText !== '') {
                $this->sql->storeTheQueryAsBookmark(
                    Current::$database,
                    $this->config->selectedServer['user'],
                    $request->getParsedBodyParamAsString('sql_query'),
                    $request->getParsedBodyParamAsString('bkm_label'),
                    $request->hasBodyParam('bkm_replace'),
                    $request->hasBodyParam('bkm_all_users'),
                );
            }

            $this->response->addJSON('ajax_reload', $ajaxReload);
            $this->response->addHTML($htmlOutput);

            return $this->response->response();
        }

        if ($request->hasBodyParam('rollback_query')) {
            // We rollback because there might be other queries that need to be executed after this,
            // e.g. creation of a bookmark.
            $this->dbi->query('ROLLBACK');
            ImportSettings::$message .= __('[ROLLBACK occurred.]');
        }

        if (Import::$result) {
            // Save a Bookmark with more than one queries (if Bookmark label given).
            if (! empty($request->getParsedBodyParam('bkm_label')) && Import::$importText !== '') {
                $relation = new Relation($this->dbi);

                $this->sql->storeTheQueryAsBookmark(
                    Current::$database,
                    $this->config->selectedServer['user'],
                    $request->getParsedBodyParamAsString('sql_query'),
                    $request->getParsedBodyParamAsString('bkm_label'),
                    $request->hasBodyParam('bkm_replace'),
                    $request->hasBodyParam('bkm_all_users'),
                );
            }

            $this->response->setRequestStatus(true);
            $this->response->addJSON('message', Message::success(ImportSettings::$message));
            $this->response->addJSON(
                'sql_query',
                Generator::getMessage(ImportSettings::$message, Current::$sqlQuery, MessageType::Success),
            );
        } elseif (Import::$result === false) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(ImportSettings::$message));
        } else {
            /** @psalm-suppress UnresolvableInclude */
            include ROOT_PATH . UrlParams::$goto;
        }

        return $this->response->response();
    }
}
