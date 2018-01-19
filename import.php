<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Core script for import, this is just the glue around all other stuff
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Response;
use PMA\libraries\Encoding;
use PMA\libraries\plugins\ImportPlugin;
use PMA\libraries\File;
use PMA\libraries\URL;
use PMA\libraries\Util;
use PMA\libraries\Bookmark;

/* Enable LOAD DATA LOCAL INFILE for LDI plugin */
if (isset($_POST['format']) && $_POST['format'] == 'ldi') {
    define('PMA_ENABLE_LDI', 1);
}

/**
 * Get the variables sent or posted to this script and a core script
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/sql.lib.php';
//require_once 'libraries/display_import_functions.lib.php';

if (isset($_REQUEST['show_as_php'])) {
    $GLOBALS['show_as_php'] = $_REQUEST['show_as_php'];
}

// Import functions.
require_once 'libraries/import.lib.php';

// If there is a request to 'Simulate DML'.
if (isset($_REQUEST['simulate_dml'])) {
    PMA_handleSimulateDMLRequest();
    exit;
}

$response = Response::getInstance();

// If it's a refresh console bookmarks request
if (isset($_REQUEST['console_bookmark_refresh'])) {
    $response->addJSON(
        'console_message_bookmark', PMA\libraries\Console::getBookmarkContent()
    );
    exit;
}
// If it's a console bookmark add request
if (isset($_REQUEST['console_bookmark_add'])) {
    if (isset($_REQUEST['label']) && isset($_REQUEST['db'])
        && isset($_REQUEST['bookmark_query']) && isset($_REQUEST['shared'])
    ) {
        $cfgBookmark = Bookmark::getParams();
        $bookmarkFields = array(
            'bkm_database' => $_REQUEST['db'],
            'bkm_user'  => $cfgBookmark['user'],
            'bkm_sql_query' => $_REQUEST['bookmark_query'],
            'bkm_label' => $_REQUEST['label']
        );
        $isShared = ($_REQUEST['shared'] == 'true' ? true : false);
        $bookmark = Bookmark::createBookmark($bookmarkFields, $isShared);
        if ($bookmark !== false && $bookmark->save()) {
            $response->addJSON('message', __('Succeeded'));
            $response->addJSON('data', $bookmarkFields);
            $response->addJSON('isShared', $isShared);
        } else {
            $response->addJSON('message', __('Failed'));
        }
        die();
    } else {
        $response->addJSON('message', __('Incomplete params'));
        die();
    }
}

$format = '';

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'charset_of_file',
    'format',
    'import_type',
    'is_js_confirmed',
    'MAX_FILE_SIZE',
    'message_to_show',
    'noplugin',
    'skip_queries',
    'local_import_file'
);

foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

// reset import messages for ajax request
$_SESSION['Import_message']['message'] = null;
$_SESSION['Import_message']['go_back_url'] = null;
// default values
$GLOBALS['reload'] = false;

// Use to identify current cycle is executing
// a multiquery statement or stored routine
if (!isset($_SESSION['is_multi_query'])) {
    $_SESSION['is_multi_query'] = false;
}

$ajax_reload = array();
// Are we just executing plain query or sql file?
// (eg. non import, but query box/window run)
if (! empty($sql_query)) {

    // apply values for parameters
    if (! empty($_REQUEST['parameterized'])
        && ! empty($_REQUEST['parameters'])
        && is_array($_REQUEST['parameters'])
    ) {
        $parameters = $_REQUEST['parameters'];
        foreach ($parameters as $parameter => $replacement) {
            $quoted = preg_quote($parameter, '/');
            // making sure that :param does not apply values to :param1
            $sql_query = preg_replace(
                '/' . $quoted . '([^a-zA-Z0-9_])/',
                $GLOBALS['dbi']->escapeString($replacement) . '${1}',
                $sql_query
            );
            // for parameters the appear at the end of the string
            $sql_query = preg_replace(
                '/' . $quoted . '$/',
                $GLOBALS['dbi']->escapeString($replacement),
                $sql_query
            );
        }
    }

    // run SQL query
    $import_text = $sql_query;
    $import_type = 'query';
    $format = 'sql';
    $_SESSION['sql_from_query_box'] = true;

    // If there is a request to ROLLBACK when finished.
    if (isset($_REQUEST['rollback_query'])) {
        PMA_handleRollbackRequest($import_text);
    }

    // refresh navigation and main panels
    if (preg_match('/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $sql_query)) {
        $GLOBALS['reload'] = true;
        $ajax_reload['reload'] = true;
    }

    // refresh navigation panel only
    if (preg_match(
        '/^(CREATE|ALTER)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i',
        $sql_query
    )) {
        $ajax_reload['reload'] = true;
    }

    // do a dynamic reload if table is RENAMED
    // (by sending the instruction to the AJAX response handler)
    if (preg_match(
        '/^RENAME\s+TABLE\s+(.*?)\s+TO\s+(.*?)($|;|\s)/i',
        $sql_query,
        $rename_table_names
    )) {
        $ajax_reload['reload'] = true;
        $ajax_reload['table_name'] = PMA\libraries\Util::unQuote(
            $rename_table_names[2]
        );
    }

    $sql_query = '';
} elseif (! empty($sql_file)) {
    // run uploaded SQL file
    $import_file = $sql_file;
    $import_type = 'queryfile';
    $format = 'sql';
    unset($sql_file);
} elseif (! empty($_REQUEST['id_bookmark'])) {
    // run bookmark
    $import_type = 'query';
    $format = 'sql';
}

// If we didn't get any parameters, either user called this directly, or
// upload limit has been reached, let's assume the second possibility.
if ($_POST == array() && $_GET == array()) {
    $message = PMA\libraries\Message::error(
        __(
            'You probably tried to upload a file that is too large. Please refer ' .
            'to %sdocumentation%s for a workaround for this limit.'
        )
    );
    $message->addParam('[doc@faq1-16]');
    $message->addParam('[/doc]');

    // so we can obtain the message
    $_SESSION['Import_message']['message'] = $message->getDisplay();
    $_SESSION['Import_message']['go_back_url'] = $GLOBALS['goto'];

    $response->setRequestStatus(false);
    $response->addJSON('message', $message);

    exit; // the footer is displayed automatically
}

// Add console message id to response output
if (isset($_POST['console_message_id'])) {
    $response->addJSON('console_message_id', $_POST['console_message_id']);
}

/**
 * Sets globals from $_POST patterns, for import plugins
 * We only need to load the selected plugin
 */

if (! in_array(
    $format,
    array(
        'csv',
        'ldi',
        'mediawiki',
        'ods',
        'shp',
        'sql',
        'xml'
    )
)
) {
    // this should not happen for a normal user
    // but only during an attack
    PMA_fatalError('Incorrect format parameter');
}

$post_patterns = array(
    '/^force_file_/',
    '/^' . $format . '_/'
);

PMA_setPostAsGlobal($post_patterns);

// Check needed parameters
PMA\libraries\Util::checkParameters(array('import_type', 'format'));

// We don't want anything special in format
$format = PMA_securePath($format);

if (strlen($table) > 0 && strlen($db) > 0) {
    $urlparams = array('db' => $db, 'table' => $table);
} elseif (strlen($db) > 0) {
    $urlparams = array('db' => $db);
} else {
    $urlparams = array();
}

// Create error and goto url
if ($import_type == 'table') {
    $goto = 'tbl_import.php';
} elseif ($import_type == 'database') {
    $goto = 'db_import.php';
} elseif ($import_type == 'server') {
    $goto = 'server_import.php';
} else {
    if (empty($goto) || !preg_match('@^(server|db|tbl)(_[a-z]*)*\.php$@i', $goto)) {
        if (strlen($table) > 0 && strlen($db) > 0) {
            $goto = 'tbl_structure.php';
        } elseif (strlen($db) > 0) {
            $goto = 'db_structure.php';
        } else {
            $goto = 'server_sql.php';
        }
    }
}
$err_url = $goto . URL::getCommon($urlparams);
$_SESSION['Import_message']['go_back_url'] = $err_url;
// Avoid setting selflink to 'import.php'
// problem similar to bug 4276
if (basename($_SERVER['SCRIPT_NAME']) === 'import.php') {
    $_SERVER['SCRIPT_NAME'] = $goto;
}


if (strlen($db) > 0) {
    $GLOBALS['dbi']->selectDb($db);
}

Util::setTimeLimit();
if (! empty($cfg['MemoryLimit'])) {
    @ini_set('memory_limit', $cfg['MemoryLimit']);
}

$timestamp = time();
if (isset($_REQUEST['allow_interrupt'])) {
    $maximum_time = ini_get('max_execution_time');
} else {
    $maximum_time = 0;
}

// set default values
$timeout_passed = false;
$error = false;
$read_multiply = 1;
$finished = false;
$offset = 0;
$max_sql_len = 0;
$file_to_unlink = '';
$sql_query = '';
$sql_query_disabled = false;
$go_sql = false;
$executed_queries = 0;
$run_query = true;
$charset_conversion = false;
$reset_charset = false;
$bookmark_created = false;
$result = false;
$msg = 'Sorry an unexpected error happened!';

// Bookmark Support: get a query back from bookmark if required
if (! empty($_REQUEST['id_bookmark'])) {
    $id_bookmark = (int)$_REQUEST['id_bookmark'];
    switch ($_REQUEST['action_bookmark']) {
    case 0: // bookmarked query that have to be run
        $bookmark = Bookmark::get(
            $db,
            $id_bookmark,
            'id',
            isset($_REQUEST['action_bookmark_all'])
        );

        if (! empty($_REQUEST['bookmark_variable'])) {
            $import_text = $bookmark->applyVariables(
                $_REQUEST['bookmark_variable']
            );
        } else {
            $import_text = $bookmark->getQuery();
        }

        // refresh navigation and main panels
        if (preg_match(
            '/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i',
            $import_text
        )) {
            $GLOBALS['reload'] = true;
            $ajax_reload['reload'] = true;
        }

        // refresh navigation panel only
        if (preg_match(
            '/^(CREATE|ALTER)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i',
            $import_text
        )
        ) {
            $ajax_reload['reload'] = true;
        }
        break;
    case 1: // bookmarked query that have to be displayed
        $bookmark = Bookmark::get($db, $id_bookmark);
        $import_text = $bookmark->getQuery();
        if ($response->isAjax()) {
            $message = PMA\libraries\Message::success(__('Showing bookmark'));
            $response->setRequestStatus($message->isSuccess());
            $response->addJSON('message', $message);
            $response->addJSON('sql_query', $import_text);
            $response->addJSON('action_bookmark', $_REQUEST['action_bookmark']);
            exit;
        } else {
            $run_query = false;
        }
        break;
    case 2: // bookmarked query that have to be deleted
        $bookmark = Bookmark::get($db, $id_bookmark);
        if (! empty($bookmark)) {
            $bookmark->delete();
            if ($response->isAjax()) {
                $message = PMA\libraries\Message::success(
                    __('The bookmark has been deleted.')
                );
                $response->setRequestStatus($message->isSuccess());
                $response->addJSON('message', $message);
                $response->addJSON('action_bookmark', $_REQUEST['action_bookmark']);
                $response->addJSON('id_bookmark', $id_bookmark);
                exit;
            } else {
                $run_query = false;
                $error = true; // this is kind of hack to skip processing the query
            }
        }

        break;
    }
} // end bookmarks reading

// Do no run query if we show PHP code
if (isset($GLOBALS['show_as_php'])) {
    $run_query = false;
    $go_sql = true;
}

// We can not read all at once, otherwise we can run out of memory
$memory_limit = trim(@ini_get('memory_limit'));
// 2 MB as default
if (empty($memory_limit)) {
    $memory_limit = 2 * 1024 * 1024;
}
// In case no memory limit we work on 10MB chunks
if ($memory_limit == -1) {
    $memory_limit = 10 * 1024 * 1024;
}

// Calculate value of the limit
$memoryUnit = mb_strtolower(substr($memory_limit, -1));
if ('m' == $memoryUnit) {
    $memory_limit = (int)substr($memory_limit, 0, -1) * 1024 * 1024;
} elseif ('k' == $memoryUnit) {
    $memory_limit = (int)substr($memory_limit, 0, -1) * 1024;
} elseif ('g' == $memoryUnit) {
    $memory_limit = (int)substr($memory_limit, 0, -1) * 1024 * 1024 * 1024;
} else {
    $memory_limit = (int)$memory_limit;
}

// Just to be sure, there might be lot of memory needed for uncompression
$read_limit = $memory_limit / 8;

// handle filenames
if (isset($_FILES['import_file'])) {
    $import_file = $_FILES['import_file']['tmp_name'];
}
if (! empty($local_import_file) && ! empty($cfg['UploadDir'])) {

    // sanitize $local_import_file as it comes from a POST
    $local_import_file = PMA_securePath($local_import_file);

    $import_file = PMA\libraries\Util::userDir($cfg['UploadDir'])
        . $local_import_file;

    /*
     * Do not allow symlinks to avoid security issues
     * (user can create symlink to file he can not access,
     * but phpMyAdmin can).
     */
    if (@is_link($import_file)) {
        $import_file  = 'none';
    }

} elseif (empty($import_file) || ! is_uploaded_file($import_file)) {
    $import_file  = 'none';
}

// Do we have file to import?

if ($import_file != 'none' && ! $error) {
    /**
     *  Handle file compression
     */
    $import_handle = new File($import_file);
    $import_handle->checkUploadedFile();
    if ($import_handle->isError()) {
        PMA_stopImport($import_handle->getError());
    }
    $import_handle->setDecompressContent(true);
    $import_handle->open();
    if ($import_handle->isError()) {
        PMA_stopImport($import_handle->getError());
    }
} elseif (! $error) {
    if (! isset($import_text) || empty($import_text)) {
        $message = PMA\libraries\Message::error(
            __(
                'No data was received to import. Either no file name was ' .
                'submitted, or the file size exceeded the maximum size permitted ' .
                'by your PHP configuration. See [doc@faq1-16]FAQ 1.16[/doc].'
            )
        );
        PMA_stopImport($message);
    }
}

// so we can obtain the message
//$_SESSION['Import_message'] = $message->getDisplay();

// Convert the file's charset if necessary
if (Encoding::isSupported() && isset($charset_of_file)) {
    if ($charset_of_file != 'utf-8') {
        $charset_conversion = true;
    }
} elseif (isset($charset_of_file) && $charset_of_file != 'utf-8') {
    $GLOBALS['dbi']->query('SET NAMES \'' . $charset_of_file . '\'');
    // We can not show query in this case, it is in different charset
    $sql_query_disabled = true;
    $reset_charset = true;
}

// Something to skip? (because timeout has passed)
if (! $error && isset($_POST['skip'])) {
    $original_skip = $skip = intval($_POST['skip']);
    while ($skip > 0 && ! $finished) {
        PMA_importGetNextChunk($skip < $read_limit ? $skip : $read_limit);
        // Disable read progressivity, otherwise we eat all memory!
        $read_multiply = 1;
        $skip -= $read_limit;
    }
    unset($skip);
}

// This array contain the data like numberof valid sql queries in the statement
// and complete valid sql statement (which affected for rows)
$sql_data = array('valid_sql' => array(), 'valid_queries' => 0);

if (! $error) {
    // Check for file existence
    include_once "libraries/plugin_interface.lib.php";
    /* @var $import_plugin ImportPlugin */
    $import_plugin = PMA_getPlugin(
        "import",
        $format,
        'libraries/plugins/import/',
        $import_type
    );
    if ($import_plugin == null) {
        $message = PMA\libraries\Message::error(
            __('Could not load import plugins, please check your installation!')
        );
        PMA_stopImport($message);
    } else {
        // Do the real import
        try {
            $default_fk_check = PMA\libraries\Util::handleDisableFKCheckInit();
            $import_plugin->doImport($sql_data);
            PMA\libraries\Util::handleDisableFKCheckCleanup($default_fk_check);
        } catch (Exception $e) {
            PMA\libraries\Util::handleDisableFKCheckCleanup($default_fk_check);
            throw $e;
        }
    }
}

if (isset($import_handle)) {
    $import_handle->close();
}

// Cleanup temporary file
if ($file_to_unlink != '') {
    unlink($file_to_unlink);
}

// Reset charset back, if we did some changes
if ($reset_charset) {
    $GLOBALS['dbi']->query('SET CHARACTER SET utf8');
    $GLOBALS['dbi']->query(
        'SET SESSION collation_connection =\'' . $collation_connection . '\''
    );
}

// Show correct message
if (! empty($id_bookmark) && $_REQUEST['action_bookmark'] == 2) {
    $message = PMA\libraries\Message::success(__('The bookmark has been deleted.'));
    $display_query = $import_text;
    $error = false; // unset error marker, it was used just to skip processing
} elseif (! empty($id_bookmark) && $_REQUEST['action_bookmark'] == 1) {
    $message = PMA\libraries\Message::notice(__('Showing bookmark'));
} elseif ($bookmark_created) {
    $special_message = '[br]'  . sprintf(
        __('Bookmark %s has been created.'),
        htmlspecialchars($_POST['bkm_label'])
    );
} elseif ($finished && ! $error) {
    // Do not display the query with message, we do it separately
    $display_query = ';';
    if ($import_type != 'query') {
        $message = PMA\libraries\Message::success(
            '<em>'
            . _ngettext(
                'Import has been successfully finished, %d query executed.',
                'Import has been successfully finished, %d queries executed.',
                $executed_queries
            )
            . '</em>'
        );
        $message->addParam($executed_queries);

        if ($import_notice) {
            $message->addHtml($import_notice);
        }
        if (! empty($local_import_file)) {
            $message->addText('(' . $local_import_file . ')');
        } else {
            $message->addText('(' . $_FILES['import_file']['name'] . ')');
        }
    }
}

// Did we hit timeout? Tell it user.
if ($timeout_passed) {
    $urlparams['timeout_passed'] = '1';
    $urlparams['offset'] = $GLOBALS['offset'];
    if (isset($local_import_file)) {
        $urlparams['local_import_file'] = $local_import_file;
    }

    $importUrl = $err_url = $goto . URL::getCommon($urlparams);

    $message = PMA\libraries\Message::error(
        __(
            'Script timeout passed, if you want to finish import,'
            . ' please %sresubmit the same file%s and import will resume.'
        )
    );
    $message->addParamHtml('<a href="' . $importUrl . '">');
    $message->addParamHtml('</a>');

    if ($offset == 0 || (isset($original_skip) && $original_skip == $offset)) {
        $message->addText(
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
if (isset($message)) {
    $_SESSION['Import_message']['message'] = $message->getDisplay();
}
// Parse and analyze the query, for correct db and table name
// in case of a query typed in the query window
// (but if the query is too large, in case of an imported file, the parser
//  can choke on it so avoid parsing)
$sqlLength = mb_strlen($sql_query);
if ($sqlLength <= $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
    include_once 'libraries/parse_analyze.lib.php';

    list(
        $analyzed_sql_results,
        $db,
        $table_from_sql
    ) = PMA_parseAnalyze($sql_query, $db);
    // @todo: possibly refactor
    extract($analyzed_sql_results);

    if ($table != $table_from_sql && !empty($table_from_sql)) {
        $table = $table_from_sql;
    }
}

// There was an error?
if (isset($my_die)) {
    foreach ($my_die as $key => $die) {
        PMA\libraries\Util::mysqlDie(
            $die['error'], $die['sql'], false, $err_url, $error
        );
    }
}

if ($go_sql) {

    if (! empty($sql_data) && ($sql_data['valid_queries'] > 1)) {
        $_SESSION['is_multi_query'] = true;
        $sql_queries = $sql_data['valid_sql'];
    } else {
        $sql_queries = array($sql_query);
    }

    $html_output = '';

    foreach ($sql_queries as $sql_query) {

        // parse sql query
        include_once 'libraries/parse_analyze.lib.php';
        list(
            $analyzed_sql_results,
            $db,
            $table_from_sql
        ) = PMA_parseAnalyze($sql_query, $db);
        // @todo: possibly refactor
        extract($analyzed_sql_results);

        // Check if User is allowed to issue a 'DROP DATABASE' Statement
        if (PMA_hasNoRightsToDropDatabase(
            $analyzed_sql_results, $cfg['AllowUserDropDatabase'], $GLOBALS['is_superuser']
        )) {
            PMA\libraries\Util::mysqlDie(
                __('"DROP DATABASE" statements are disabled.'),
                '',
                false,
                $_SESSION['Import_message']['go_back_url']
            );
            return;
        } // end if

        if ($table != $table_from_sql && !empty($table_from_sql)) {
            $table = $table_from_sql;
        }

        $html_output .= PMA_executeQueryAndGetQueryResponse(
            $analyzed_sql_results, // analyzed_sql_results
            false, // is_gotofile
            $db, // db
            $table, // table
            null, // find_real_end
            null, // sql_query_for_bookmark - see below
            null, // extra_data
            null, // message_to_show
            null, // message
            null, // sql_data
            $goto, // goto
            $pmaThemeImage, // pmaThemeImage
            null, // disp_query
            null, // disp_message
            null, // query_type
            $sql_query, // sql_query
            null, // selectedTables
            null // complete_query
        );
    }

    // sql_query_for_bookmark is not included in PMA_executeQueryAndGetQueryResponse
    // since only one bookmark has to be added for all the queries submitted through
    // the SQL tab
    if (! empty($_POST['bkm_label']) && ! empty($import_text)) {
        $cfgBookmark = Bookmark::getParams();
        PMA_storeTheQueryAsBookmark(
            $db, $cfgBookmark['user'],
            $_REQUEST['sql_query'], $_POST['bkm_label'],
            isset($_POST['bkm_replace']) ? $_POST['bkm_replace'] : null
        );
    }

    $response->addJSON('ajax_reload', $ajax_reload);
    $response->addHTML($html_output);
    exit();

} else if ($result) {
    // Save a Bookmark with more than one queries (if Bookmark label given).
    if (! empty($_POST['bkm_label']) && ! empty($import_text)) {
        $cfgBookmark = Bookmark::getParams();
        PMA_storeTheQueryAsBookmark(
            $db, $cfgBookmark['user'],
            $_REQUEST['sql_query'], $_POST['bkm_label'],
            isset($_POST['bkm_replace']) ? $_POST['bkm_replace'] : null
        );
    }

    $response->setRequestStatus(true);
    $response->addJSON('message', PMA\libraries\Message::success($msg));
    $response->addJSON(
        'sql_query',
        PMA\libraries\Util::getMessage($msg, $sql_query, 'success')
    );
} else if ($result == false) {
    $response->setRequestStatus(false);
    $response->addJSON('message', PMA\libraries\Message::error($msg));
} else {
    $active_page = $goto;
    include '' . $goto;
}

// If there is request for ROLLBACK in the end.
if (isset($_REQUEST['rollback_query'])) {
    $GLOBALS['dbi']->query('ROLLBACK');
}
