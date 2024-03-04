<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Bookmarks\Bookmark;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\File;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
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

final class ImportController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Import $import,
        private Sql $sql,
        private DatabaseInterface $dbi,
        private readonly BookmarkRepository $bookmarkRepository,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['goto'] ??= null;
        $GLOBALS['display_query'] ??= null;
        $GLOBALS['ajax_reload'] ??= null;
        $GLOBALS['import_text'] ??= null;
        $GLOBALS['message'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['error'] ??= null;
        $GLOBALS['result'] ??= null;

        ImportSettings::$charsetOfFile = (string) $request->getParsedBodyParam('charset_of_file');
        $format = $request->getParsedBodyParam('format', '');
        ImportSettings::$importType = (string) $request->getParsedBodyParam('import_type');
        $GLOBALS['is_js_confirmed'] = $request->getParsedBodyParam('is_js_confirmed');
        $GLOBALS['message_to_show'] = $request->getParsedBodyParam('message_to_show');
        $GLOBALS['noplugin'] = $request->getParsedBodyParam('noplugin');
        ImportSettings::$skipQueries = (int) $request->getParsedBodyParam('skip_queries');
        ImportSettings::$localImportFile = (string) $request->getParsedBodyParam('local_import_file');
        $GLOBALS['show_as_php'] = $request->getParsedBodyParam('show_as_php');

        // reset import messages for ajax request
        $_SESSION['Import_message']['message'] = null;
        $_SESSION['Import_message']['go_back_url'] = null;
        // default values
        $GLOBALS['reload'] = false;

        $GLOBALS['ajax_reload'] = [];
        $GLOBALS['import_text'] = '';
        // Are we just executing plain query or sql file?
        // (eg. non import, but query box/window run)
        if (! empty($GLOBALS['sql_query'])) {
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
                    $GLOBALS['sql_query'] = preg_replace(
                        '/' . $quoted . '([^a-zA-Z0-9_])/',
                        $replacementValue . '${1}',
                        $GLOBALS['sql_query'],
                    );
                    // for parameters the appear at the end of the string
                    $GLOBALS['sql_query'] = preg_replace(
                        '/' . $quoted . '$/',
                        $replacementValue,
                        $GLOBALS['sql_query'],
                    );
                }
            }

            // run SQL query
            $GLOBALS['import_text'] = $GLOBALS['sql_query'];
            ImportSettings::$importType = 'query';
            $format = 'sql';
            $_SESSION['sql_from_query_box'] = true;

            // If there is a request to ROLLBACK when finished.
            if ($request->hasBodyParam('rollback_query')) {
                $this->import->handleRollbackRequest($GLOBALS['import_text']);
            }

            // refresh navigation and main panels
            if (preg_match('/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $GLOBALS['sql_query'])) {
                $GLOBALS['reload'] = true;
                $GLOBALS['ajax_reload']['reload'] = true;
            }

            // refresh navigation panel only
            if (preg_match('/^(CREATE|ALTER)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $GLOBALS['sql_query'])) {
                $GLOBALS['ajax_reload']['reload'] = true;
            }

            // do a dynamic reload if table is RENAMED
            // (by sending the instruction to the AJAX response handler)
            if (
                preg_match('/^RENAME\s+TABLE\s+(.*?)\s+TO\s+(.*?)($|;|\s)/i', $GLOBALS['sql_query'], $renameTableNames)
            ) {
                $GLOBALS['ajax_reload']['reload'] = true;
                $GLOBALS['ajax_reload']['table_name'] = Util::unQuote($renameTableNames[2]);
            }

            $GLOBALS['sql_query'] = '';
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
            $GLOBALS['message'] = Message::error(
                __(
                    'You probably tried to upload a file that is too large. Please refer ' .
                    'to %sdocumentation%s for a workaround for this limit.',
                ),
            );
            $GLOBALS['message']->addParam('[doc@faq1-16]');
            $GLOBALS['message']->addParam('[/doc]');

            // so we can obtain the message
            $_SESSION['Import_message']['message'] = $GLOBALS['message']->getDisplay();
            $_SESSION['Import_message']['go_back_url'] = $GLOBALS['goto'];

            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $GLOBALS['message']);

            return; // the footer is displayed automatically
        }

        // Add console message id to response output
        $consoleMessageId = $request->getParsedBodyParam('console_message_id');
        if ($consoleMessageId !== null) {
            $this->response->addJSON('console_message_id', $consoleMessageId);
        }

        /**
         * Sets globals from $_POST patterns, for import plugins
         * We only need to load the selected plugin
         */

        if (! in_array($format, ['csv', 'ldi', 'mediawiki', 'ods', 'shp', 'sql', 'xml'], true)) {
            // this should not happen for a normal user
            // but only during an attack
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error(__('Incorrect format parameter'))->getDisplay());

            return;
        }

        $postPatterns = ['/^' . $format . '_/'];

        Core::setPostAsGlobal($postPatterns);

        // We don't want anything special in format
        $format = Core::securePath($format);

        if (Current::$table !== '' && Current::$database !== '') {
            $GLOBALS['urlParams'] = ['db' => Current::$database, 'table' => Current::$table];
        } elseif (Current::$database !== '') {
            $GLOBALS['urlParams'] = ['db' => Current::$database];
        } else {
            $GLOBALS['urlParams'] = [];
        }

        // Create error and goto url
        if (ImportSettings::$importType === 'table') {
            $GLOBALS['goto'] = Url::getFromRoute('/table/import');
        } elseif (ImportSettings::$importType === 'database') {
            $GLOBALS['goto'] = Url::getFromRoute('/database/import');
        } elseif (ImportSettings::$importType === 'server') {
            $GLOBALS['goto'] = Url::getFromRoute('/server/import');
        } elseif (empty($GLOBALS['goto']) || ! preg_match('@^index\.php$@i', $GLOBALS['goto'])) {
            if (Current::$table !== '' && Current::$database !== '') {
                $GLOBALS['goto'] = Url::getFromRoute('/table/structure');
            } elseif (Current::$database !== '') {
                $GLOBALS['goto'] = Url::getFromRoute('/database/structure');
            } else {
                $GLOBALS['goto'] = Url::getFromRoute('/server/sql');
            }
        }

        $GLOBALS['errorUrl'] = $GLOBALS['goto'] . Url::getCommon($GLOBALS['urlParams'], '&');
        $_SESSION['Import_message']['go_back_url'] = $GLOBALS['errorUrl'];

        if (Current::$database !== '') {
            $this->dbi->selectDb(Current::$database);
        }

        Util::setTimeLimit();
        $config = Config::getInstance();
        if (! empty($config->settings['MemoryLimit'])) {
            ini_set('memory_limit', $config->settings['MemoryLimit']);
        }

        ImportSettings::$timestamp = time();
        ImportSettings::$maximumTime = 0;
        $maxExecutionTime = (int) ini_get('max_execution_time');
        if ($request->hasBodyParam('allow_interrupt') && $maxExecutionTime >= 1) {
            ImportSettings::$maximumTime = $maxExecutionTime - 1; // Give 1 second for phpMyAdmin to exit nicely
        }

        // set default values
        ImportSettings::$timeoutPassed = false;
        $GLOBALS['error'] = false;
        ImportSettings::$readMultiply = 1;
        ImportSettings::$finished = false;
        ImportSettings::$offset = 0;
        ImportSettings::$maxSqlLength = 0;
        $GLOBALS['sql_query'] = '';
        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$goSql = false;
        ImportSettings::$executedQueries = 0;
        ImportSettings::$runQuery = true;
        ImportSettings::$charsetConversion = false;
        $resetCharset = false;
        ImportSettings::$message = 'Sorry an unexpected error happened!';

        $GLOBALS['result'] = false;

        // Bookmark Support: get a query back from bookmark if required
        $idBookmark = (int) $request->getParsedBodyParam('id_bookmark');
        $actionBookmark = (int) $request->getParsedBodyParam('action_bookmark');
        if ($idBookmark !== 0) {
            switch ($actionBookmark) {
                case 0: // bookmarked query that have to be run
                    $bookmark = $this->bookmarkRepository->get(
                        $request->hasBodyParam('action_bookmark_all') ? null : $config->selectedServer['user'],
                        $idBookmark,
                    );
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $bookmarkVariables = $request->getParsedBodyParam('bookmark_variable');
                    if (is_array($bookmarkVariables)) {
                        $GLOBALS['import_text'] = $bookmark->applyVariables($bookmarkVariables);
                    } else {
                        $GLOBALS['import_text'] = $bookmark->getQuery();
                    }

                    // refresh navigation and main panels
                    if (preg_match('/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $GLOBALS['import_text'])) {
                        $GLOBALS['reload'] = true;
                        $GLOBALS['ajax_reload']['reload'] = true;
                    }

                    // refresh navigation panel only
                    if (preg_match('/^(CREATE|ALTER)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $GLOBALS['import_text'])) {
                        $GLOBALS['ajax_reload']['reload'] = true;
                    }

                    break;
                case 1: // bookmarked query that have to be displayed
                    $bookmark = $this->bookmarkRepository->get($config->selectedServer['user'], $idBookmark);
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $GLOBALS['import_text'] = $bookmark->getQuery();
                    if ($request->isAjax()) {
                        $GLOBALS['message'] = Message::success(__('Showing bookmark'));
                        $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
                        $this->response->addJSON('message', $GLOBALS['message']);
                        $this->response->addJSON('sql_query', $GLOBALS['import_text']);
                        $this->response->addJSON('action_bookmark', $actionBookmark);

                        return;
                    }

                    ImportSettings::$runQuery = false;
                    break;
                case 2: // bookmarked query that have to be deleted
                    $bookmark = $this->bookmarkRepository->get($config->selectedServer['user'], $idBookmark);
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $bookmark->delete();
                    if ($request->isAjax()) {
                        $GLOBALS['message'] = Message::success(
                            __('The bookmark has been deleted.'),
                        );
                        $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
                        $this->response->addJSON('message', $GLOBALS['message']);
                        $this->response->addJSON('action_bookmark', $actionBookmark);
                        $this->response->addJSON('id_bookmark', $idBookmark);

                        return;
                    }

                    ImportSettings::$runQuery = false;
                    $GLOBALS['error'] = true; // this is kind of hack to skip processing the query
                    break;
            }
        }

        // Do no run query if we show PHP code
        if (isset($GLOBALS['show_as_php'])) {
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

        if (ImportSettings::$localImportFile !== '' && $config->settings['UploadDir'] !== '') {
            // sanitize $local_import_file as it comes from a POST
            ImportSettings::$localImportFile = Core::securePath(ImportSettings::$localImportFile);

            ImportSettings::$importFile = Util::userDir($config->settings['UploadDir'])
                . ImportSettings::$localImportFile;

            /**
             * Do not allow symlinks to avoid security issues
             * (user can create symlink to file they can not access,
             * but phpMyAdmin can).
             */
            if (@is_link(ImportSettings::$importFile)) {
                ImportSettings::$importFile = 'none';
            }
        } elseif (empty(ImportSettings::$importFile) || ! is_uploaded_file(ImportSettings::$importFile)) {
            ImportSettings::$importFile = 'none';
        }

        // Do we have file to import?

        if (ImportSettings::$importFile !== 'none' && ! $GLOBALS['error']) {
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

                return;
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

                return;
            }
        } elseif (! $GLOBALS['error'] && empty($GLOBALS['import_text'])) {
            $GLOBALS['message'] = Message::error(
                __(
                    'No data was received to import. Either no file name was ' .
                    'submitted, or the file size exceeded the maximum size permitted ' .
                    'by your PHP configuration. See [doc@faq1-16]FAQ 1.16[/doc].',
                ),
            );

            $_SESSION['Import_message']['message'] = $GLOBALS['message']->getDisplay();

            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $GLOBALS['message']->getDisplay());
            $this->response->addHTML($GLOBALS['message']->getDisplay());

            return;
        }

        // Convert the file's charset if necessary
        if (
            Encoding::isSupported()
            && ImportSettings::$charsetOfFile !== '' && ImportSettings::$charsetOfFile !== 'utf-8'
        ) {
            ImportSettings::$charsetConversion = true;
        } elseif (ImportSettings::$charsetOfFile !== '' && ImportSettings::$charsetOfFile !== 'utf-8') {
            $this->dbi->query('SET NAMES \'' . ImportSettings::$charsetOfFile . '\'');
            // We can not show query in this case, it is in different charset
            ImportSettings::$sqlQueryDisabled = true;
            $resetCharset = true;
        }

        // Something to skip? (because timeout has passed)
        if (! $GLOBALS['error'] && $request->hasBodyParam('skip')) {
            $originalSkip = $skip = (int) $request->getParsedBodyParam('skip');
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

        if (! $GLOBALS['error']) {
            /** @var ImportPlugin $importPlugin */
            $importPlugin = Plugins::getPlugin('import', $format, ImportSettings::$importType);
            if ($importPlugin == null) {
                $GLOBALS['message'] = Message::error(
                    __('Could not load import plugins, please check your installation!'),
                );

                $_SESSION['Import_message']['message'] = $GLOBALS['message']->getDisplay();

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $GLOBALS['message']->getDisplay());
                $this->response->addHTML($GLOBALS['message']->getDisplay());

                return;
            }

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
            $GLOBALS['message'] = Message::success(__('The bookmark has been deleted.'));
            $GLOBALS['display_query'] = $GLOBALS['import_text'];
            $GLOBALS['error'] = false; // unset error marker, it was used just to skip processing
        } elseif ($idBookmark !== 0 && $actionBookmark === 1) {
            $GLOBALS['message'] = Message::notice(__('Showing bookmark'));
        } elseif (ImportSettings::$finished && ! $GLOBALS['error']) {
            // Do not display the query with message, we do it separately
            $GLOBALS['display_query'] = ';';
            if (ImportSettings::$importType !== 'query') {
                $GLOBALS['message'] = Message::success(
                    '<em>'
                    . _ngettext(
                        'Import has been successfully finished, %d query executed.',
                        'Import has been successfully finished, %d queries executed.',
                        ImportSettings::$executedQueries,
                    )
                    . '</em>',
                );
                $GLOBALS['message']->addParam(ImportSettings::$executedQueries);

                if (ImportSettings::$importNotice !== '') {
                    $GLOBALS['message']->addHtml(ImportSettings::$importNotice);
                }

                if (ImportSettings::$localImportFile !== '') {
                    $GLOBALS['message']->addText('(' . ImportSettings::$localImportFile . ')');
                } elseif (
                    isset($_FILES['import_file'])
                    && is_array($_FILES['import_file'])
                    && isset($_FILES['import_file']['name'])
                    && is_string($_FILES['import_file']['name'])
                ) {
                    $GLOBALS['message']->addText('(' . $_FILES['import_file']['name'] . ')');
                }
            }
        }

        // Did we hit timeout? Tell it user.
        if (ImportSettings::$timeoutPassed) {
            $GLOBALS['urlParams']['timeout_passed'] = '1';
            $GLOBALS['urlParams']['offset'] = ImportSettings::$offset;
            if (ImportSettings::$localImportFile !== '') {
                $GLOBALS['urlParams']['local_import_file'] = ImportSettings::$localImportFile;
            }

            $importUrl = $GLOBALS['errorUrl'] = $GLOBALS['goto'] . Url::getCommon($GLOBALS['urlParams'], '&');

            $GLOBALS['message'] = Message::error(
                __(
                    'Script timeout passed, if you want to finish import,'
                    . ' please %sresubmit the same file%s and import will resume.',
                ),
            );
            $GLOBALS['message']->addParamHtml('<a href="' . $importUrl . '">');
            $GLOBALS['message']->addParamHtml('</a>');

            if (ImportSettings::$offset === 0 || (isset($originalSkip) && $originalSkip == ImportSettings::$offset)) {
                $GLOBALS['message']->addText(
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
        if (isset($GLOBALS['message'])) {
            $_SESSION['Import_message']['message'] = $GLOBALS['message']->getDisplay();
        }

        // Parse and analyze the query, for correct db and table name
        // in case of a query typed in the query window
        // (but if the query is too large, in case of an imported file, the parser
        //  can choke on it so avoid parsing)
        $sqlLength = mb_strlen($GLOBALS['sql_query']);
        if ($sqlLength <= $config->settings['MaxCharactersInDisplayedSQL']) {
            [$statementInfo, Current::$database, $tableFromSql] = ParseAnalyze::sqlQuery(
                $GLOBALS['sql_query'],
                Current::$database,
            );

            $GLOBALS['reload'] = $statementInfo->flags->reload;
            ImportSettings::$offset = (int) $statementInfo->flags->offset;

            if (Current::$table != $tableFromSql && $tableFromSql !== '') {
                Current::$table = $tableFromSql;
            }
        }

        foreach (ImportSettings::$failedQueries as $die) {
            Generator::mysqlDie($die['error'], $die['sql'], false, $GLOBALS['errorUrl'], $GLOBALS['error']);
        }

        if (ImportSettings::$goSql) {
            if ($queriesToBeExecuted === []) {
                $queriesToBeExecuted = [$GLOBALS['sql_query']];
            }

            $htmlOutput = '';

            foreach ($queriesToBeExecuted as $GLOBALS['sql_query']) {
                // parse sql query
                [$statementInfo, Current::$database, $tableFromSql] = ParseAnalyze::sqlQuery(
                    $GLOBALS['sql_query'],
                    Current::$database,
                );

                ImportSettings::$offset = (int) $statementInfo->flags->offset;
                $GLOBALS['reload'] = $statementInfo->flags->reload;

                // Check if User is allowed to issue a 'DROP DATABASE' Statement
                if (
                    $this->sql->hasNoRightsToDropDatabase(
                        $statementInfo,
                        $config->settings['AllowUserDropDatabase'],
                        $this->dbi->isSuperUser(),
                    )
                ) {
                    Generator::mysqlDie(
                        __('"DROP DATABASE" statements are disabled.'),
                        '',
                        false,
                        $_SESSION['Import_message']['go_back_url'],
                    );

                    return;
                }

                if (Current::$table != $tableFromSql && $tableFromSql !== '') {
                    Current::$table = $tableFromSql;
                }

                $htmlOutput .= $this->sql->executeQueryAndGetQueryResponse(
                    $statementInfo,
                    false, // is_gotofile
                    Current::$database, // db
                    Current::$table, // table
                    null, // sql_query_for_bookmark - see below
                    null, // message_to_show
                    null, // sql_data
                    $GLOBALS['goto'], // goto
                    null, // disp_query
                    null, // disp_message
                    $GLOBALS['sql_query'], // sql_query
                    null, // complete_query
                );
            }

            // sql_query_for_bookmark is not included in Sql::executeQueryAndGetQueryResponse
            // since only one bookmark has to be added for all the queries submitted through
            // the SQL tab
            if (! empty($request->getParsedBodyParam('bkm_label')) && ! empty($GLOBALS['import_text'])) {
                $relation = new Relation($this->dbi);

                $this->sql->storeTheQueryAsBookmark(
                    $relation->getRelationParameters()->bookmarkFeature,
                    Current::$database,
                    $config->selectedServer['user'],
                    $request->getParsedBodyParam('sql_query'),
                    $request->getParsedBodyParam('bkm_label'),
                    $request->hasBodyParam('bkm_replace'),
                );
            }

            $this->response->addJSON('ajax_reload', $GLOBALS['ajax_reload']);
            $this->response->addHTML($htmlOutput);

            return;
        }

        if ($GLOBALS['result']) {
            // Save a Bookmark with more than one queries (if Bookmark label given).
            if (! empty($request->getParsedBodyParam('bkm_label')) && ! empty($GLOBALS['import_text'])) {
                $relation = new Relation($this->dbi);

                $this->sql->storeTheQueryAsBookmark(
                    $relation->getRelationParameters()->bookmarkFeature,
                    Current::$database,
                    $config->selectedServer['user'],
                    $request->getParsedBodyParam('sql_query'),
                    $request->getParsedBodyParam('bkm_label'),
                    $request->hasBodyParam('bkm_replace'),
                );
            }

            $this->response->setRequestStatus(true);
            $this->response->addJSON('message', Message::success(ImportSettings::$message));
            $this->response->addJSON(
                'sql_query',
                Generator::getMessage(ImportSettings::$message, $GLOBALS['sql_query'], 'success'),
            );
        } elseif ($GLOBALS['result'] === false) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(ImportSettings::$message));
        } else {
            /** @psalm-suppress UnresolvableInclude */
            include ROOT_PATH . $GLOBALS['goto'];
        }

        // If there is request for ROLLBACK in the end.
        if (! $request->hasBodyParam('rollback_query')) {
            return;
        }

        $this->dbi->query('ROLLBACK');
    }
}
