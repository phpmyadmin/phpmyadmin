<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Core script for import, this is just the glue around all other stuff
 *
 * @package PhpMyAdmin
 */

/**
 * Get the variables sent or posted to this script and a core script
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/sql.lib.php';
require_once 'libraries/bookmark.lib.php';
//require_once 'libraries/display_import_functions.lib.php';

if (isset($_REQUEST['show_as_php'])) {
    $GLOBALS['show_as_php'] = $_REQUEST['show_as_php'];
}

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'action_bookmark',
    'allow_interrupt',
    'bkm_label',
    'bookmark_variable',
    'charset_of_file',
    'format',
    'id_bookmark',
    'import_type',
    'is_js_confirmed',
    'MAX_FILE_SIZE',
    'message_to_show',
    'noplugin',
    'skip_queries',
    'local_import_file'
);

// TODO: adapt full list of allowed parameters, as in export.php
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

// Use to identify curren cycle is executing
// a multiquery statement or stored routine
if (!isset($_SESSION['is_multi_query'])) {
    $_SESSION['is_multi_query'] = false;
}

// Are we just executing plain query or sql file?
// (eg. non import, but query box/window run)
if (! empty($sql_query)) {
    // run SQL query
    $import_text = $sql_query;
    $import_type = 'query';
    $format = 'sql';

    // refresh navigation and main panels
    if (preg_match('/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i', $sql_query)) {
        $GLOBALS['reload'] = true;
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
        $ajax_reload['table_name'] = PMA_Util::unQuote($rename_table_names[2]);
        $ajax_reload['reload'] = true;
    }

    $sql_query = '';
} elseif (! empty($sql_localfile)) {
    // run SQL file on server
    $local_import_file = $sql_localfile;
    $import_type = 'queryfile';
    $format = 'sql';
    unset($sql_localfile);
} elseif (! empty($sql_file)) {
    // run uploaded SQL file
    $import_file = $sql_file;
    $import_type = 'queryfile';
    $format = 'sql';
    unset($sql_file);
} elseif (! empty($id_bookmark)) {
    // run bookmark
    $import_type = 'query';
    $format = 'sql';
}

// If we didn't get any parameters, either user called this directly, or
// upload limit has been reached, let's assume the second possibility.
;
if ($_POST == array() && $_GET == array()) {
    $message = PMA_Message::error(
        __('You probably tried to upload a file that is too large. Please refer to %sdocumentation%s for a workaround for this limit.')
    );
    $message->addParam('[doc@faq1-16]');
    $message->addParam('[/doc]');

    // so we can obtain the message
    $_SESSION['Import_message']['message'] = $message->getDisplay();
    $_SESSION['Import_message']['go_back_url'] = $goto;

    $message->display();
    exit; // the footer is displayed automatically
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
    '/^'. $format . '_/'
);
foreach (array_keys($_POST) as $post_key) {
    foreach ($post_patterns as $one_post_pattern) {
        if (preg_match($one_post_pattern, $post_key)) {
            $GLOBALS[$post_key] = $_POST[$post_key];
        }
    }
}

// Check needed parameters
PMA_Util::checkParameters(array('import_type', 'format'));

// We don't want anything special in format
$format = PMA_securePath($format);

// Import functions
require_once 'libraries/import.lib.php';

// Create error and goto url
if ($import_type == 'table') {
    $err_url = 'tbl_import.php?' . PMA_URL_getCommon($db, $table);
    $_SESSION['Import_message']['go_back_url'] = $err_url;
    $goto = 'tbl_import.php';
} elseif ($import_type == 'database') {
    $err_url = 'db_import.php?' . PMA_URL_getCommon($db);
    $_SESSION['Import_message']['go_back_url'] = $err_url;
    $goto = 'db_import.php';
} elseif ($import_type == 'server') {
    $err_url = 'server_import.php?' . PMA_URL_getCommon();
    $_SESSION['Import_message']['go_back_url'] = $err_url;
    $goto = 'server_import.php';
} else {
    if (empty($goto) || !preg_match('@^(server|db|tbl)(_[a-z]*)*\.php$@i', $goto)) {
        if (strlen($table) && strlen($db)) {
            $goto = 'tbl_structure.php';
        } elseif (strlen($db)) {
            $goto = 'db_structure.php';
        } else {
            $goto = 'server_sql.php';
        }
    }
    if (strlen($table) && strlen($db)) {
        $common = PMA_URL_getCommon($db, $table);
    } elseif (strlen($db)) {
        $common = PMA_URL_getCommon($db);
    } else {
        $common = PMA_URL_getCommon();
    }
    $err_url  = $goto . '?' . $common
        . (preg_match('@^tbl_[a-z]*\.php$@', $goto)
            ? '&amp;table=' . htmlspecialchars($table)
            : '');
    $_SESSION['Import_message']['go_back_url'] = $err_url;
}


if (strlen($db)) {
    $GLOBALS['dbi']->selectDb($db);
}

@set_time_limit($cfg['ExecTimeLimit']);
if (! empty($cfg['MemoryLimit'])) {
    @ini_set('memory_limit', $cfg['MemoryLimit']);
}

$timestamp = time();
if (isset($allow_interrupt)) {
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

// Bookmark Support: get a query back from bookmark if required
if (! empty($id_bookmark)) {
    $id_bookmark = (int)$id_bookmark;
    include_once 'libraries/bookmark.lib.php';
    switch ($action_bookmark) {
    case 0: // bookmarked query that have to be run
        $import_text = PMA_Bookmark_get(
            $db,
            $id_bookmark,
            'id',
            isset($action_bookmark_all)
        );
        if (isset($bookmark_variable) && ! empty($bookmark_variable)) {
            $import_text = preg_replace(
                '|/\*(.*)\[VARIABLE\](.*)\*/|imsU',
                '${1}' . PMA_Util::sqlAddSlashes($bookmark_variable) . '${2}',
                $import_text
            );
        }

        // refresh navigation and main panels
        if (preg_match(
            '/^(DROP)\s+(VIEW|TABLE|DATABASE|SCHEMA)\s+/i',
            $import_text
        )) {
            $GLOBALS['reload'] = true;
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
        $import_text = PMA_Bookmark_get($db, $id_bookmark);
        if ($GLOBALS['is_ajax_request'] == true) {
            $message = PMA_Message::success(__('Showing bookmark'));
            $response = PMA_Response::getInstance();
            $response->isSuccess($message->isSuccess());
            $response->addJSON('message', $message);
            $response->addJSON('sql_query', $import_text);
            $response->addJSON('action_bookmark', $action_bookmark);
            exit;
        } else {
            $run_query = false;
        }
        break;
    case 2: // bookmarked query that have to be deleted
        $import_text = PMA_Bookmark_get($db, $id_bookmark);
        PMA_Bookmark_delete($db, $id_bookmark);
        if ($GLOBALS['is_ajax_request'] == true) {
            $message = PMA_Message::success(__('The bookmark has been deleted.'));
            $response = PMA_Response::getInstance();
            $response->isSuccess($message->isSuccess());
            $response->addJSON('message', $message);
            $response->addJSON('action_bookmark', $action_bookmark);
            $response->addJSON('id_bookmark', $id_bookmark);
            exit;
        } else {
            $run_query = false;
            $error = true; // this is kind of hack to skip processing the query
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
if (strtolower(substr($memory_limit, -1)) == 'm') {
    $memory_limit = (int)substr($memory_limit, 0, -1) * 1024 * 1024;
} elseif (strtolower(substr($memory_limit, -1)) == 'k') {
    $memory_limit = (int)substr($memory_limit, 0, -1) * 1024;
} elseif (strtolower(substr($memory_limit, -1)) == 'g') {
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

    $import_file = PMA_Util::userDir($cfg['UploadDir'])
        . $local_import_file;

} elseif (empty($import_file) || ! is_uploaded_file($import_file)) {
    $import_file  = 'none';
}

// Do we have file to import?

if ($import_file != 'none' && ! $error) {
    // work around open_basedir and other limitations
    $open_basedir = @ini_get('open_basedir');

    // If we are on a server with open_basedir, we must move the file
    // before opening it. The doc explains how to create the "./tmp"
    // directory

    if (! empty($open_basedir)) {

        $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : 'tmp/');

        if (is_writable($tmp_subdir)) {


            $import_file_new = $tmp_subdir . basename($import_file) . uniqid();
            if (move_uploaded_file($import_file, $import_file_new)) {
                $import_file = $import_file_new;
                $file_to_unlink = $import_file_new;
            }

            $size = filesize($import_file);
        }
    }

    /**
     *  Handle file compression
     * @todo duplicate code exists in File.class.php
     */
    $compression = PMA_detectCompression($import_file);
    if ($compression === false) {
        $message = PMA_Message::error(__('File could not be read'));
        $error = true;
    } else {
        switch ($compression) {
        case 'application/bzip2':
            if ($cfg['BZipDump'] && @function_exists('bzopen')) {
                $import_handle = @bzopen($import_file, 'r');
            } else {
                $message = PMA_Message::error(
                    __('You attempted to load file with unsupported compression (%s). Either support for it is not implemented or disabled by your configuration.')
                );
                $message->addParam($compression);
                $error = true;
            }
            break;
        case 'application/gzip':
            if ($cfg['GZipDump'] && @function_exists('gzopen')) {
                $import_handle = @gzopen($import_file, 'r');
            } else {
                $message = PMA_Message::error(
                    __('You attempted to load file with unsupported compression (%s). Either support for it is not implemented or disabled by your configuration.')
                );
                $message->addParam($compression);
                $error = true;
            }
            break;
        case 'application/zip':
            if ($cfg['ZipDump'] && @function_exists('zip_open')) {
                /**
                 * Load interface for zip extension.
                 */
                include_once 'libraries/zip_extension.lib.php';
                $result = PMA_getZipContents($import_file);
                if (! empty($result['error'])) {
                    $message = PMA_Message::rawError($result['error']);
                    $error = true;
                } else {
                    $import_text = $result['data'];
                }
            } else {
                $message = PMA_Message::error(
                    __('You attempted to load file with unsupported compression (%s). Either support for it is not implemented or disabled by your configuration.')
                );
                $message->addParam($compression);
                $error = true;
            }
            break;
        case 'none':
            $import_handle = @fopen($import_file, 'r');
            break;
        default:
            $message = PMA_Message::error(
                __('You attempted to load file with unsupported compression (%s). Either support for it is not implemented or disabled by your configuration.')
            );
            $message->addParam($compression);
            $error = true;
            break;
        }
    }
    // use isset() because zip compression type does not use a handle
    if (! $error && isset($import_handle) && $import_handle === false) {
        $message = PMA_Message::error(__('File could not be read'));
        $error = true;
    }
} elseif (! $error) {
    if (! isset($import_text) || empty($import_text)) {
        $message = PMA_Message::error(
            __('No data was received to import. Either no file name was submitted, or the file size exceeded the maximum size permitted by your PHP configuration. See [doc@faq1-16]FAQ 1.16[/doc].')
        );
        $error = true;
    }
}

// so we can obtain the message
//$_SESSION['Import_message'] = $message->getDisplay();

// Convert the file's charset if necessary
if ($GLOBALS['PMA_recoding_engine'] != PMA_CHARSET_NONE && isset($charset_of_file)) {
    if ($charset_of_file != 'utf-8') {
        $charset_conversion = true;
    }
} elseif (isset($charset_of_file) && $charset_of_file != 'utf8') {
    if (PMA_DRIZZLE) {
        // Drizzle doesn't support other character sets,
        // so we can't fallback to SET NAMES - throw an error
        $error = true;
        $message = PMA_Message::error(
            __('Cannot convert file\'s character set without character set conversion library')
        );
    } else {
        $GLOBALS['dbi']->query('SET NAMES \'' . $charset_of_file . '\'');
        // We can not show query in this case, it is in different charset
        $sql_query_disabled = true;
        $reset_charset = true;
    }
}

// Something to skip?
if (! $error && isset($skip)) {
    $original_skip = $skip;
    while ($skip > 0) {
        PMA_importGetNextChunk($skip < $read_limit ? $skip : $read_limit);
        // Disable read progresivity, otherwise we eat all memory!
        $read_multiply = 1;
        $skip -= $read_limit;
    }
    unset($skip);
}

// This array contain the data like numberof valid sql queries in the statement
// and complete valid sql statement (which affected for rows)
$sql_data = array('valid_sql' => array(), 'valid_queries' => 0);

if (! $error) {
    // Check for file existance
    include_once "libraries/plugin_interface.lib.php";
    $import_plugin = PMA_getPlugin(
        "import",
        $format,
        'libraries/plugins/import/',
        $import_type
    );
    if ($import_plugin == null) {
        $error = true;
        $message = PMA_Message::error(
            __('Could not load import plugins, please check your installation!')
        );
    } else {
        // Do the real import
        $import_plugin->doImport($sql_data);
    }
}

if (! $error && false !== $import_handle && null !== $import_handle) {
    fclose($import_handle);
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
if (! empty($id_bookmark) && $action_bookmark == 2) {
    $message = PMA_Message::success(__('The bookmark has been deleted.'));
    $display_query = $import_text;
    $error = false; // unset error marker, it was used just to skip processing
} elseif (! empty($id_bookmark) && $action_bookmark == 1) {
    $message = PMA_Message::notice(__('Showing bookmark'));
} elseif ($bookmark_created) {
    $special_message = '[br]'  . sprintf(
        __('Bookmark %s created'),
        htmlspecialchars($bkm_label)
    );
} elseif ($finished && ! $error) {
    if ($import_type == 'query') {
        $message = PMA_Message::success();
    } else {
        if ($import_notice) {
            $message = PMA_Message::success(
                '<em>'
                . __('Import has been successfully finished, %d queries executed.')
                . '</em>'
            );
            $message->addParam($executed_queries);

            $message->addString($import_notice);
            if (isset($local_import_file)) {
                $message->addString('(' . $local_import_file . ')');
            } else {
                $message->addString('(' . $_FILES['import_file']['name'] . ')');
            }
        } else {
            $message = PMA_Message::success(
                __('Import has been successfully finished, %d queries executed.')
            );
            $message->addParam($executed_queries);
            if (isset($local_import_file)) {
                $message->addString('(' . $local_import_file . ')');
            } else {
                $message->addString('(' . $_FILES['import_file']['name'] . ')');
            }
        }
    }
}

// Did we hit timeout? Tell it user.
if ($timeout_passed) {
    $message = PMA_Message::error(
        __('Script timeout passed, if you want to finish import, please resubmit same file and import will resume.')
    );
    if ($offset == 0 || (isset($original_skip) && $original_skip == $offset)) {
        $message->addString(
            __('However on last run no data has been parsed, this usually means phpMyAdmin won\'t be able to finish this import unless you increase php time limits.')
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
if (strlen($sql_query) <= $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
    include_once 'libraries/parse_analyze.inc.php';
}

// There was an error?
if (isset($my_die)) {
    foreach ($my_die as $key => $die) {
        PMA_Util::mysqlDie(
            $die['error'], $die['sql'], '', $err_url, $error
        );
    }
}

if ($go_sql) {
    // parse sql query
    include_once 'libraries/parse_analyze.inc.php';

    PMA_executeQueryAndSendQueryResponse(
        $analyzed_sql_results, false, $db, $table, null, $import_text, null,
        $analyzed_sql_results['is_affected'], null,
        null, null, null, $goto, $pmaThemeImage, null, null, null, $sql_query,
        null, null
    );
} else if ($result) {
    $response = PMA_Response::getInstance();
    $response->isSuccess(true);
    $response->addJSON('message', PMA_Message::success($msg));
    $response->addJSON(
        'sql_query',
        PMA_Util::getMessage($msg, $sql_query, 'success')
    );
} else if ($result == false) {
    $response = PMA_Response::getInstance();
    $response->isSuccess(false);
    $response->addJSON('message', PMA_Message::error($msg));
} else {
    $active_page = $goto;
    include '' . $goto;
}
?>
