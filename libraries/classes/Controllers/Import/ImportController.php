<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\File;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Import;
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
use function ini_set;
use function intval;
use function is_array;
use function is_link;
use function is_numeric;
use function is_uploaded_file;
use function mb_strlen;
use function mb_strtolower;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function strlen;
use function substr;
use function time;
use function trim;

final class ImportController extends AbstractController
{
    /** @var Import */
    private $import;

    /** @var Sql */
    private $sql;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Import $import,
        Sql $sql,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->import = $import;
        $this->sql = $sql;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['collation_connection'] = $GLOBALS['collation_connection'] ?? null;
        $GLOBALS['goto'] = $GLOBALS['goto'] ?? null;
        $GLOBALS['display_query'] = $GLOBALS['display_query'] ?? null;
        $GLOBALS['ajax_reload'] = $GLOBALS['ajax_reload'] ?? null;
        $GLOBALS['import_text'] = $GLOBALS['import_text'] ?? null;
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['memory_limit'] = $GLOBALS['memory_limit'] ?? null;
        $GLOBALS['read_limit'] = $GLOBALS['read_limit'] ?? null;
        $GLOBALS['finished'] = $GLOBALS['finished'] ?? null;
        $GLOBALS['offset'] = $GLOBALS['offset'] ?? null;
        $GLOBALS['charset_conversion'] = $GLOBALS['charset_conversion'] ?? null;
        $GLOBALS['timestamp'] = $GLOBALS['timestamp'] ?? null;
        $GLOBALS['maximum_time'] = $GLOBALS['maximum_time'] ?? null;
        $GLOBALS['timeout_passed'] = $GLOBALS['timeout_passed'] ?? null;
        $GLOBALS['import_file'] = $GLOBALS['import_file'] ?? null;
        $GLOBALS['go_sql'] = $GLOBALS['go_sql'] ?? null;
        $GLOBALS['sql_file'] = $GLOBALS['sql_file'] ?? null;
        $GLOBALS['error'] = $GLOBALS['error'] ?? null;
        $GLOBALS['max_sql_len'] = $GLOBALS['max_sql_len'] ?? null;
        $GLOBALS['msg'] = $GLOBALS['msg'] ?? null;
        $GLOBALS['sql_query_disabled'] = $GLOBALS['sql_query_disabled'] ?? null;
        $GLOBALS['executed_queries'] = $GLOBALS['executed_queries'] ?? null;
        $GLOBALS['run_query'] = $GLOBALS['run_query'] ?? null;
        $GLOBALS['reset_charset'] = $GLOBALS['reset_charset'] ?? null;
        $GLOBALS['result'] = $GLOBALS['result'] ?? null;
        $GLOBALS['import_file_name'] = $GLOBALS['import_file_name'] ?? null;
        $GLOBALS['import_notice'] = $GLOBALS['import_notice'] ?? null;
        $GLOBALS['read_multiply'] = $GLOBALS['read_multiply'] ?? null;
        $GLOBALS['my_die'] = $GLOBALS['my_die'] ?? null;
        $GLOBALS['active_page'] = $GLOBALS['active_page'] ?? null;
        $GLOBALS['reload'] = $GLOBALS['reload'] ?? null;
        $GLOBALS['charset_connection'] = $GLOBALS['charset_connection'] ?? null;

        $GLOBALS['charset_of_file'] = $_POST['charset_of_file'] ?? null;
        $GLOBALS['format'] = $_POST['format'] ?? '';
        $GLOBALS['import_type'] = $_POST['import_type'] ?? null;
        $GLOBALS['is_js_confirmed'] = $_POST['is_js_confirmed'] ?? null;
        $GLOBALS['MAX_FILE_SIZE'] = $_POST['MAX_FILE_SIZE'] ?? null;
        $GLOBALS['message_to_show'] = $_POST['message_to_show'] ?? null;
        $GLOBALS['noplugin'] = $_POST['noplugin'] ?? null;
        $GLOBALS['skip_queries'] = $_POST['skip_queries'] ?? null;
        $GLOBALS['local_import_file'] = $_POST['local_import_file'] ?? null;
        $GLOBALS['show_as_php'] = $_POST['show_as_php'] ?? null;

        // If it's a refresh console bookmarks request
        if (isset($_GET['console_bookmark_refresh'])) {
            $this->response->addJSON(
                'console_message_bookmark',
                Console::getBookmarkContent()
            );

            return;
        }

        // If it's a console bookmark add request
        if (isset($_POST['console_bookmark_add'])) {
            if (! isset($_POST['label'], $_POST['db'], $_POST['bookmark_query'], $_POST['shared'])) {
                $this->response->addJSON('message', __('Incomplete params'));

                return;
            }

            $bookmarkFields = [
                'bkm_database' => $_POST['db'],
                'bkm_user' => $GLOBALS['cfg']['Server']['user'],
                'bkm_sql_query' => $_POST['bookmark_query'],
                'bkm_label' => $_POST['label'],
            ];
            $isShared = ($_POST['shared'] === 'true');
            $bookmark = Bookmark::createBookmark($this->dbi, $bookmarkFields, $isShared);
            if ($bookmark !== false && $bookmark->save()) {
                $this->response->addJSON('message', __('Succeeded'));
                $this->response->addJSON('data', $bookmarkFields);
                $this->response->addJSON('isShared', $isShared);
            } else {
                $this->response->addJSON('message', __('Failed'));
            }

            return;
        }

        // reset import messages for ajax request
        $_SESSION['Import_message']['message'] = null;
        $_SESSION['Import_message']['go_back_url'] = null;
        // default values
        $GLOBALS['reload'] = false;

        // Use to identify current cycle is executing
        // a multiquery statement or stored routine
        if (! isset($_SESSION['is_multi_query'])) {
            $_SESSION['is_multi_query'] = false;
        }

        $GLOBALS['ajax_reload'] = [];
        $GLOBALS['import_text'] = '';
        // Are we just executing plain query or sql file?
        // (eg. non import, but query box/window run)
        if (! empty($GLOBALS['sql_query'])) {
            // apply values for parameters
            if (! empty($_POST['parameterized']) && ! empty($_POST['parameters']) && is_array($_POST['parameters'])) {
                $parameters = $_POST['parameters'];
                foreach ($parameters as $parameter => $replacement) {
                    $replacementValue = $this->dbi->escapeString($replacement);
                    if (! is_numeric($replacementValue)) {
                        $replacementValue = '\'' . $replacementValue . '\'';
                    }

                    $quoted = preg_quote($parameter, '/');
                    // making sure that :param does not apply values to :param1
                    $GLOBALS['sql_query'] = preg_replace(
                        '/' . $quoted . '([^a-zA-Z0-9_])/',
                        $replacementValue . '${1}',
                        $GLOBALS['sql_query']
                    );
                    // for parameters the appear at the end of the string
                    $GLOBALS['sql_query'] = preg_replace(
                        '/' . $quoted . '$/',
                        $replacementValue,
                        $GLOBALS['sql_query']
                    );
                }
            }

            // run SQL query
            $GLOBALS['import_text'] = $GLOBALS['sql_query'];
            $GLOBALS['import_type'] = 'query';
            $GLOBALS['format'] = 'sql';
            $_SESSION['sql_from_query_box'] = true;

            // If there is a request to ROLLBACK when finished.
            if (isset($_POST['rollback_query'])) {
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
                preg_match(
                    '/^RENAME\s+TABLE\s+(.*?)\s+TO\s+(.*?)($|;|\s)/i',
                    $GLOBALS['sql_query'],
                    $rename_table_names
                )
            ) {
                $GLOBALS['ajax_reload']['reload'] = true;
                $GLOBALS['ajax_reload']['table_name'] = Util::unQuote($rename_table_names[2]);
            }

            $GLOBALS['sql_query'] = '';
        } elseif (! empty($GLOBALS['sql_file'])) {
            // run uploaded SQL file
            $GLOBALS['import_file'] = $GLOBALS['sql_file'];
            $GLOBALS['import_type'] = 'queryfile';
            $GLOBALS['format'] = 'sql';
            unset($GLOBALS['sql_file']);
        } elseif (! empty($_POST['id_bookmark'])) {
            // run bookmark
            $GLOBALS['import_type'] = 'query';
            $GLOBALS['format'] = 'sql';
        }

        // If we didn't get any parameters, either user called this directly, or
        // upload limit has been reached, let's assume the second possibility.
        if ($_POST == [] && $_GET == []) {
            $GLOBALS['message'] = Message::error(
                __(
                    'You probably tried to upload a file that is too large. Please refer ' .
                    'to %sdocumentation%s for a workaround for this limit.'
                )
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
        if (isset($_POST['console_message_id'])) {
            $this->response->addJSON('console_message_id', $_POST['console_message_id']);
        }

        /**
         * Sets globals from $_POST patterns, for import plugins
         * We only need to load the selected plugin
         */

        if (! in_array($GLOBALS['format'], ['csv', 'ldi', 'mediawiki', 'ods', 'shp', 'sql', 'xml'])) {
            // this should not happen for a normal user
            // but only during an attack
            Core::fatalError('Incorrect format parameter');
        }

        $post_patterns = [
            '/^force_file_/',
            '/^' . $GLOBALS['format'] . '_/',
        ];

        Core::setPostAsGlobal($post_patterns);

        $this->checkParameters(['import_type', 'format']);

        // We don't want anything special in format
        $GLOBALS['format'] = Core::securePath($GLOBALS['format']);

        if (strlen($GLOBALS['table']) > 0 && strlen($GLOBALS['db']) > 0) {
            $GLOBALS['urlParams'] = [
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table'],
            ];
        } elseif (strlen($GLOBALS['db']) > 0) {
            $GLOBALS['urlParams'] = ['db' => $GLOBALS['db']];
        } else {
            $GLOBALS['urlParams'] = [];
        }

        // Create error and goto url
        if ($GLOBALS['import_type'] === 'table') {
            $GLOBALS['goto'] = Url::getFromRoute('/table/import');
        } elseif ($GLOBALS['import_type'] === 'database') {
            $GLOBALS['goto'] = Url::getFromRoute('/database/import');
        } elseif ($GLOBALS['import_type'] === 'server') {
            $GLOBALS['goto'] = Url::getFromRoute('/server/import');
        } elseif (empty($GLOBALS['goto']) || ! preg_match('@^index\.php$@i', $GLOBALS['goto'])) {
            if (strlen($GLOBALS['table']) > 0 && strlen($GLOBALS['db']) > 0) {
                $GLOBALS['goto'] = Url::getFromRoute('/table/structure');
            } elseif (strlen($GLOBALS['db']) > 0) {
                $GLOBALS['goto'] = Url::getFromRoute('/database/structure');
            } else {
                $GLOBALS['goto'] = Url::getFromRoute('/server/sql');
            }
        }

        $GLOBALS['errorUrl'] = $GLOBALS['goto'] . Url::getCommon($GLOBALS['urlParams'], '&');
        $_SESSION['Import_message']['go_back_url'] = $GLOBALS['errorUrl'];

        if (strlen($GLOBALS['db']) > 0) {
            $this->dbi->selectDb($GLOBALS['db']);
        }

        Util::setTimeLimit();
        if (! empty($GLOBALS['cfg']['MemoryLimit'])) {
            ini_set('memory_limit', $GLOBALS['cfg']['MemoryLimit']);
        }

        $GLOBALS['timestamp'] = time();
        if (isset($_POST['allow_interrupt'])) {
            $GLOBALS['maximum_time'] = ini_get('max_execution_time');
        } else {
            $GLOBALS['maximum_time'] = 0;
        }

        // set default values
        $GLOBALS['timeout_passed'] = false;
        $GLOBALS['error'] = false;
        $GLOBALS['read_multiply'] = 1;
        $GLOBALS['finished'] = false;
        $GLOBALS['offset'] = 0;
        $GLOBALS['max_sql_len'] = 0;
        $GLOBALS['sql_query'] = '';
        $GLOBALS['sql_query_disabled'] = false;
        $GLOBALS['go_sql'] = false;
        $GLOBALS['executed_queries'] = 0;
        $GLOBALS['run_query'] = true;
        $GLOBALS['charset_conversion'] = false;
        $GLOBALS['reset_charset'] = false;
        $GLOBALS['msg'] = 'Sorry an unexpected error happened!';

        $GLOBALS['result'] = false;

        // Bookmark Support: get a query back from bookmark if required
        if (! empty($_POST['id_bookmark'])) {
            $id_bookmark = (int) $_POST['id_bookmark'];
            switch ($_POST['action_bookmark']) {
                case 0: // bookmarked query that have to be run
                    $bookmark = Bookmark::get(
                        $this->dbi,
                        $GLOBALS['cfg']['Server']['user'],
                        $GLOBALS['db'],
                        $id_bookmark,
                        'id',
                        isset($_POST['action_bookmark_all'])
                    );
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    if (! empty($_POST['bookmark_variable'])) {
                        $GLOBALS['import_text'] = $bookmark->applyVariables($_POST['bookmark_variable']);
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
                    $bookmark = Bookmark::get(
                        $this->dbi,
                        $GLOBALS['cfg']['Server']['user'],
                        $GLOBALS['db'],
                        $id_bookmark
                    );
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $GLOBALS['import_text'] = $bookmark->getQuery();
                    if ($this->response->isAjax()) {
                        $GLOBALS['message'] = Message::success(__('Showing bookmark'));
                        $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
                        $this->response->addJSON('message', $GLOBALS['message']);
                        $this->response->addJSON('sql_query', $GLOBALS['import_text']);
                        $this->response->addJSON('action_bookmark', $_POST['action_bookmark']);

                        return;
                    } else {
                        $GLOBALS['run_query'] = false;
                    }

                    break;
                case 2: // bookmarked query that have to be deleted
                    $bookmark = Bookmark::get(
                        $this->dbi,
                        $GLOBALS['cfg']['Server']['user'],
                        $GLOBALS['db'],
                        $id_bookmark
                    );
                    if (! $bookmark instanceof Bookmark) {
                        break;
                    }

                    $bookmark->delete();
                    if ($this->response->isAjax()) {
                        $GLOBALS['message'] = Message::success(
                            __('The bookmark has been deleted.')
                        );
                        $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
                        $this->response->addJSON('message', $GLOBALS['message']);
                        $this->response->addJSON('action_bookmark', $_POST['action_bookmark']);
                        $this->response->addJSON('id_bookmark', $id_bookmark);

                        return;
                    } else {
                        $GLOBALS['run_query'] = false;
                        $GLOBALS['error'] = true; // this is kind of hack to skip processing the query
                    }

                    break;
            }
        }

        // Do no run query if we show PHP code
        if (isset($GLOBALS['show_as_php'])) {
            $GLOBALS['run_query'] = false;
            $GLOBALS['go_sql'] = true;
        }

        // We can not read all at once, otherwise we can run out of memory
        $GLOBALS['memory_limit'] = trim((string) ini_get('memory_limit'));
        // 2 MB as default
        if (empty($GLOBALS['memory_limit'])) {
            $GLOBALS['memory_limit'] = 2 * 1024 * 1024;
        }

        // In case no memory limit we work on 10MB chunks
        if ($GLOBALS['memory_limit'] === '-1') {
            $GLOBALS['memory_limit'] = 10 * 1024 * 1024;
        }

        // Calculate value of the limit
        $memoryUnit = mb_strtolower(substr((string) $GLOBALS['memory_limit'], -1));
        if ($memoryUnit === 'm') {
            $GLOBALS['memory_limit'] = (int) substr((string) $GLOBALS['memory_limit'], 0, -1) * 1024 * 1024;
        } elseif ($memoryUnit === 'k') {
            $GLOBALS['memory_limit'] = (int) substr((string) $GLOBALS['memory_limit'], 0, -1) * 1024;
        } elseif ($memoryUnit === 'g') {
            $GLOBALS['memory_limit'] = (int) substr((string) $GLOBALS['memory_limit'], 0, -1) * 1024 * 1024 * 1024;
        } else {
            $GLOBALS['memory_limit'] = (int) $GLOBALS['memory_limit'];
        }

        // Just to be sure, there might be lot of memory needed for uncompression
        $GLOBALS['read_limit'] = $GLOBALS['memory_limit'] / 8;

        // handle filenames
        if (isset($_FILES['import_file'])) {
            $GLOBALS['import_file'] = $_FILES['import_file']['tmp_name'];
            $GLOBALS['import_file_name'] = $_FILES['import_file']['name'];
        }

        if (! empty($GLOBALS['local_import_file']) && ! empty($GLOBALS['cfg']['UploadDir'])) {
            // sanitize $local_import_file as it comes from a POST
            $GLOBALS['local_import_file'] = Core::securePath($GLOBALS['local_import_file']);

            $GLOBALS['import_file'] = Util::userDir((string) $GLOBALS['cfg']['UploadDir'])
                . $GLOBALS['local_import_file'];

            /*
             * Do not allow symlinks to avoid security issues
             * (user can create symlink to file they can not access,
             * but phpMyAdmin can).
             */
            if (@is_link($GLOBALS['import_file'])) {
                $GLOBALS['import_file'] = 'none';
            }
        } elseif (empty($GLOBALS['import_file']) || ! is_uploaded_file($GLOBALS['import_file'])) {
            $GLOBALS['import_file'] = 'none';
        }

        // Do we have file to import?

        if ($GLOBALS['import_file'] !== 'none' && ! $GLOBALS['error']) {
            /**
             *  Handle file compression
             */
            $importHandle = new File($GLOBALS['import_file']);
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
        } elseif (! $GLOBALS['error'] && (! isset($GLOBALS['import_text']) || empty($GLOBALS['import_text']))) {
            $GLOBALS['message'] = Message::error(
                __(
                    'No data was received to import. Either no file name was ' .
                    'submitted, or the file size exceeded the maximum size permitted ' .
                    'by your PHP configuration. See [doc@faq1-16]FAQ 1.16[/doc].'
                )
            );

            $_SESSION['Import_message']['message'] = $GLOBALS['message']->getDisplay();

            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $GLOBALS['message']->getDisplay());
            $this->response->addHTML($GLOBALS['message']->getDisplay());

            return;
        }

        // Convert the file's charset if necessary
        if (Encoding::isSupported() && isset($GLOBALS['charset_of_file'])) {
            if ($GLOBALS['charset_of_file'] !== 'utf-8') {
                $GLOBALS['charset_conversion'] = true;
            }
        } elseif (isset($GLOBALS['charset_of_file']) && $GLOBALS['charset_of_file'] !== 'utf-8') {
            $this->dbi->query('SET NAMES \'' . $GLOBALS['charset_of_file'] . '\'');
            // We can not show query in this case, it is in different charset
            $GLOBALS['sql_query_disabled'] = true;
            $GLOBALS['reset_charset'] = true;
        }

        // Something to skip? (because timeout has passed)
        if (! $GLOBALS['error'] && isset($_POST['skip'])) {
            $original_skip = $skip = intval($_POST['skip']);
            while ($skip > 0 && ! $GLOBALS['finished']) {
                $this->import->getNextChunk(
                    $importHandle ?? null,
                    $skip < $GLOBALS['read_limit'] ? $skip : $GLOBALS['read_limit']
                );
                // Disable read progressivity, otherwise we eat all memory!
                $GLOBALS['read_multiply'] = 1;
                $skip -= $GLOBALS['read_limit'];
            }

            unset($skip);
        }

        // This array contain the data like number of valid sql queries in the statement
        // and complete valid sql statement (which affected for rows)
        $queriesToBeExecuted = [];

        if (! $GLOBALS['error']) {
            /**
             * @var ImportPlugin $import_plugin
             */
            $import_plugin = Plugins::getPlugin('import', $GLOBALS['format'], $GLOBALS['import_type']);
            if ($import_plugin == null) {
                $GLOBALS['message'] = Message::error(
                    __('Could not load import plugins, please check your installation!')
                );

                $_SESSION['Import_message']['message'] = $GLOBALS['message']->getDisplay();

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $GLOBALS['message']->getDisplay());
                $this->response->addHTML($GLOBALS['message']->getDisplay());

                return;
            }

            // Do the real import
            $default_fk_check = ForeignKey::handleDisableCheckInit();
            try {
                $queriesToBeExecuted = $import_plugin->doImport($importHandle ?? null);
                ForeignKey::handleDisableCheckCleanup($default_fk_check);
            } catch (Throwable $e) {
                ForeignKey::handleDisableCheckCleanup($default_fk_check);

                throw $e;
            }
        }

        if (isset($importHandle)) {
            $importHandle->close();
        }

        // Reset charset back, if we did some changes
        if ($GLOBALS['reset_charset']) {
            $this->dbi->query('SET CHARACTER SET ' . $GLOBALS['charset_connection']);
            $this->dbi->setCollation($GLOBALS['collation_connection']);
        }

        // Show correct message
        if (! empty($id_bookmark) && $_POST['action_bookmark'] == 2) {
            $GLOBALS['message'] = Message::success(__('The bookmark has been deleted.'));
            $GLOBALS['display_query'] = $GLOBALS['import_text'];
            $GLOBALS['error'] = false; // unset error marker, it was used just to skip processing
        } elseif (! empty($id_bookmark) && $_POST['action_bookmark'] == 1) {
            $GLOBALS['message'] = Message::notice(__('Showing bookmark'));
        } elseif ($GLOBALS['finished'] && ! $GLOBALS['error']) {
            // Do not display the query with message, we do it separately
            $GLOBALS['display_query'] = ';';
            if ($GLOBALS['import_type'] !== 'query') {
                $GLOBALS['message'] = Message::success(
                    '<em>'
                    . _ngettext(
                        'Import has been successfully finished, %d query executed.',
                        'Import has been successfully finished, %d queries executed.',
                        $GLOBALS['executed_queries']
                    )
                    . '</em>'
                );
                $GLOBALS['message']->addParam($GLOBALS['executed_queries']);

                if (! empty($GLOBALS['import_notice'])) {
                    $GLOBALS['message']->addHtml($GLOBALS['import_notice']);
                }

                if (! empty($GLOBALS['local_import_file'])) {
                    $GLOBALS['message']->addText('(' . $GLOBALS['local_import_file'] . ')');
                } else {
                    $GLOBALS['message']->addText('(' . $_FILES['import_file']['name'] . ')');
                }
            }
        }

        // Did we hit timeout? Tell it user.
        if ($GLOBALS['timeout_passed']) {
            $GLOBALS['urlParams']['timeout_passed'] = '1';
            $GLOBALS['urlParams']['offset'] = $GLOBALS['offset'];
            if (isset($GLOBALS['local_import_file'])) {
                $GLOBALS['urlParams']['local_import_file'] = $GLOBALS['local_import_file'];
            }

            $importUrl = $GLOBALS['errorUrl'] = $GLOBALS['goto'] . Url::getCommon($GLOBALS['urlParams'], '&');

            $GLOBALS['message'] = Message::error(
                __(
                    'Script timeout passed, if you want to finish import,'
                    . ' please %sresubmit the same file%s and import will resume.'
                )
            );
            $GLOBALS['message']->addParamHtml('<a href="' . $importUrl . '">');
            $GLOBALS['message']->addParamHtml('</a>');

            if ($GLOBALS['offset'] == 0 || (isset($original_skip) && $original_skip == $GLOBALS['offset'])) {
                $GLOBALS['message']->addText(
                    __(
                        'However on last run no data has been parsed,'
                        . ' this usually means phpMyAdmin won\'t be able to'
                        . ' finish this import unless you increase php time limits.'
                    )
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
        if ($sqlLength <= $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
            [$statementInfo, $GLOBALS['db'], $table_from_sql] = ParseAnalyze::sqlQuery(
                $GLOBALS['sql_query'],
                $GLOBALS['db']
            );

            $GLOBALS['reload'] = $statementInfo->reload;
            $GLOBALS['offset'] = $statementInfo->offset;

            if ($GLOBALS['table'] != $table_from_sql && ! empty($table_from_sql)) {
                $GLOBALS['table'] = $table_from_sql;
            }
        }

        // There was an error?
        if (isset($GLOBALS['my_die'])) {
            foreach ($GLOBALS['my_die'] as $die) {
                Generator::mysqlDie($die['error'], $die['sql'], false, $GLOBALS['errorUrl'], $GLOBALS['error']);
            }
        }

        if ($GLOBALS['go_sql']) {
            if ($queriesToBeExecuted !== []) {
                $_SESSION['is_multi_query'] = true;
            } else {
                $queriesToBeExecuted = [$GLOBALS['sql_query']];
            }

            $html_output = '';

            foreach ($queriesToBeExecuted as $GLOBALS['sql_query']) {
                // parse sql query
                [$statementInfo, $GLOBALS['db'], $table_from_sql] = ParseAnalyze::sqlQuery(
                    $GLOBALS['sql_query'],
                    $GLOBALS['db']
                );

                $GLOBALS['offset'] = $statementInfo->offset;
                $GLOBALS['reload'] = $statementInfo->reload;

                // Check if User is allowed to issue a 'DROP DATABASE' Statement
                if (
                    $this->sql->hasNoRightsToDropDatabase(
                        $statementInfo,
                        $GLOBALS['cfg']['AllowUserDropDatabase'],
                        $this->dbi->isSuperUser()
                    )
                ) {
                    Generator::mysqlDie(
                        __('"DROP DATABASE" statements are disabled.'),
                        '',
                        false,
                        $_SESSION['Import_message']['go_back_url']
                    );

                    return;
                }

                if ($GLOBALS['table'] != $table_from_sql && ! empty($table_from_sql)) {
                    $GLOBALS['table'] = $table_from_sql;
                }

                $html_output .= $this->sql->executeQueryAndGetQueryResponse(
                    $statementInfo,
                    false, // is_gotofile
                    $GLOBALS['db'], // db
                    $GLOBALS['table'], // table
                    null, // find_real_end
                    null, // sql_query_for_bookmark - see below
                    null, // extra_data
                    null, // message_to_show
                    null, // sql_data
                    $GLOBALS['goto'], // goto
                    null, // disp_query
                    null, // disp_message
                    $GLOBALS['sql_query'], // sql_query
                    null // complete_query
                );
            }

            // sql_query_for_bookmark is not included in Sql::executeQueryAndGetQueryResponse
            // since only one bookmark has to be added for all the queries submitted through
            // the SQL tab
            if (! empty($_POST['bkm_label']) && ! empty($GLOBALS['import_text'])) {
                $relation = new Relation($this->dbi);

                $this->sql->storeTheQueryAsBookmark(
                    $relation->getRelationParameters()->bookmarkFeature,
                    $GLOBALS['db'],
                    $GLOBALS['cfg']['Server']['user'],
                    $_POST['sql_query'],
                    $_POST['bkm_label'],
                    isset($_POST['bkm_replace'])
                );
            }

            $this->response->addJSON('ajax_reload', $GLOBALS['ajax_reload']);
            $this->response->addHTML($html_output);

            return;
        }

        if ($GLOBALS['result']) {
            // Save a Bookmark with more than one queries (if Bookmark label given).
            if (! empty($_POST['bkm_label']) && ! empty($GLOBALS['import_text'])) {
                $relation = new Relation($this->dbi);

                $this->sql->storeTheQueryAsBookmark(
                    $relation->getRelationParameters()->bookmarkFeature,
                    $GLOBALS['db'],
                    $GLOBALS['cfg']['Server']['user'],
                    $_POST['sql_query'],
                    $_POST['bkm_label'],
                    isset($_POST['bkm_replace'])
                );
            }

            $this->response->setRequestStatus(true);
            $this->response->addJSON('message', Message::success($GLOBALS['msg']));
            $this->response->addJSON(
                'sql_query',
                Generator::getMessage($GLOBALS['msg'], $GLOBALS['sql_query'], 'success')
            );
        } elseif ($GLOBALS['result'] === false) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error($GLOBALS['msg']));
        } else {
            $GLOBALS['active_page'] = $GLOBALS['goto'];
            /** @psalm-suppress UnresolvableInclude */
            include ROOT_PATH . $GLOBALS['goto'];
        }

        // If there is request for ROLLBACK in the end.
        if (! isset($_POST['rollback_query'])) {
            return;
        }

        $this->dbi->query('ROLLBACK');
    }
}
