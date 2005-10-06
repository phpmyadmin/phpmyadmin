<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/* Core script for import, this is just the glue around all other stuff */

/**
 * Get the variables sent or posted to this script and a core script
 */
require_once('./libraries/grab_globals.lib.php');
$js_to_run = 'functions.js';
require_once('./libraries/common.lib.php');

PMA_checkParameters(array('import_type', 'what'));

// Import functions
require_once('./libraries/import.lib.php');

if ($import_type == 'table') {
    $err_url = 'tbl_import.php?' . PMA_generate_common_url($db, $table);
} elseif ($import_type == 'database') {
    $err_url = 'db_import.php?' . PMA_generate_common_url($db);
} else {
    $err_url = 'server_import.php?' . PMA_generate_common_url();
}


if ($import_type != 'server') {
    PMA_DBI_select_db($db);
}

@set_time_limit($cfg['ExecTimeLimit']);
$timestamp = time();
if (isset($allow_interrupt)) {
    $maximum_time = ini_get('max_execution_time');
} else {
    $maximum_time = 0;
}

// set default values
$timeout_passed = FALSE;
$error = FALSE;
$read_multiply = 1;
$finished = FALSE;
$offset = 0;
$max_sql_len = 0;
$file_to_unlink = '';
$sql_query = '';
$sql_query_disabled = FALSE;
$go_sql = FALSE;
$reload = FALSE;
$executed_queries = 0;

// We can not read all at once, otherwise we can run out of memory
$memory_limit = trim(@ini_get('memory_limit'));
// 2 MB as default
if (empty($memory_limit)) $memory_limit = 2 * 1024 * 1024;

// Calculate value of the limit
if (strtolower(substr($memory_limit, -1)) == 'm') $memory_limit = (int)substr($memory_limit, 0, -1) * 1024 * 1024;
elseif (strtolower(substr($memory_limit, -1)) == 'k') $memory_limit = (int)substr($memory_limit, 0, -1) * 1024;
elseif (strtolower(substr($memory_limit, -1)) == 'g') $memory_limit = (int)substr($memory_limit, 0, -1) * 1024 * 1024 * 1024;
else $memory_limit = (int)$memory_limit;

$read_limit = $memory_limit / 4; // Just to be sure, there might be lot of memory needed for uncompression

// handle filenames
if (!empty($local_import_file) && !empty($cfg['UploadDir'])) {

    // sanitize $local_import_file as it comes from a POST
    $local_import_file = PMA_securePath($local_import_file);

    if (substr($cfg['UploadDir'], -1) != '/') {
        $cfg['UploadDir'] .= '/';
    }
    
    $import_file  = $cfg['UploadDir'] . $local_import_file;
} else if (empty($import_file) || !is_uploaded_file($import_file))  {
    $import_file  = 'none';
}

// work around open_basedir and other limitations
if ($import_file != 'none') {
    $open_basedir = @ini_get('open_basedir');

    // If we are on a server with open_basedir, we must move the file
    // before opening it. The doc explains how to create the "./tmp"
    // directory

    if (!empty($open_basedir)) {

        $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : './tmp/');

        // function is_writeable() is valid on PHP3 and 4
        if (is_writeable($tmp_subdir)) {
            $import_file_new = $tmp_subdir . basename($import_file);
            if (move_uploaded_file($import_file, $import_file_new)) {
                $import_file = $import_file_new;
                $file_to_unlink = $import_file_new;
            }
        }
    }
    
    $compression = PMA_detectCompression($import_file);
    if ($compression === FALSE) {
        $message = $strFileCouldNotBeRead;
        $show_error_header = TRUE;
        $error = TRUE;
    } else {
        switch ($compression) {
            case 'application/bzip2':
                if ($cfg['BZipDump'] && @function_exists('bzopen')) {
                    $import_handle = @bzopen($import_file, 'r');
                } else {
                    $message = sprintf($strUnsupportedCompressionDetected, $compression);
                    $show_error_header = TRUE;
                    $error = TRUE;
                }
                break;
            case 'application/gzip':
                if ($cfg['GZipDump'] && @function_exists('gzopen')) {
                    $import_handle = @gzopen($import_file, 'r');
                } else {
                    $message = sprintf($strUnsupportedCompressionDetected, $compression);
                    $show_error_header = TRUE;
                    $error = TRUE;
                }
                break;
            case 'application/zip':
                if ($cfg['GZipDump'] && @function_exists('gzinflate')) {
                    include_once('./libraries/unzip.lib.php');
                    $import_handle = new SimpleUnzip();
                    $import_handle->ReadFile($import_file);
                    if ($import_handle->Count() == 0) {
                        $message = $strNoFilesFoundInZip;
                        $show_error_header = TRUE;
                        $error = TRUE;
                    } elseif ($import_handle->GetError(0) != 0) {
                        $message = $strErrorInZipFile . ' ' . $import_handle->GetErrorMsg(0);
                        $show_error_header = TRUE;
                        $error = TRUE;
                    } else {
                        $import_text = $import_handle->GetData(0);
                    }
                    // We don't need to store it further
                    $import_handle = '';
                } else {
                    $message = sprintf($strUnsupportedCompressionDetected, $compression);
                    $show_error_header = TRUE;
                    $error = TRUE;
                }
                break;
            case 'none':
                $import_handle = @fopen($import_file, 'r');
                break;
            default:
                $message = sprintf($strUnsupportedCompressionDetected, $compression);
                $show_error_header = TRUE;
                $error = TRUE;
                break;
        }
    }
    if (!$error && $import_handle === FALSE) {
        $message = $strFileCouldNotBeRead;
        $show_error_header = TRUE;
        $error = TRUE;
    }
} else {
    if (!isset($import_text) || empty($import_text)) {
        $message = $strNothingToImport;
        $show_error_header = TRUE;
        $error = TRUE;
    }
}

// Convert the file's charset if necessary
$charset_conversion = FALSE;
$reset_charset = FALSE;
if (PMA_MYSQL_INT_VERSION < 40100
    && $cfg['AllowAnywhereRecoding'] && $allow_recoding
    && isset($charset_of_file) && $charset_of_file != $charset) {
    $charset_conversion = TRUE;
} else if (PMA_MYSQL_INT_VERSION >= 40100
    && isset($charset_of_file) && $charset_of_file != 'utf8') {
    PMA_DBI_query('SET NAMES \'' . $charset_of_file . '\'');
    $reset_charset = TRUE;
}

// Something to skip?
if (isset($skip)) {
    $original_skip = $skip;
    while ($skip > 0) {
        PMA_importGetNextChunk($skip < $read_limit ? $skip : $read_limit);
        $read_multiply = 1; // Disable read progresivity, otherwise we eat all memory!
        $skip -= $read_limit;
    }
    unset($skip);
}

if (!$error) {
    // Do the real import
    require('./libraries/import/' . PMA_securePath($what) . '.php');
}

// Cleanup temporary file
if ($file_to_unlink != '') {
    unlink($file_to_unlink);
}
// Reset charset back, if we did some changes
if ($reset_charset) {
    PMA_DBI_query('SET CHARACTER SET utf8');
    PMA_DBI_query('SET SESSION collation_connection =\'' . $collation_connection . '\'');
}

if ($finished && !$error) {
    $message = $strImportFinished;
}

if ($timeout_passed) {
    $message = $strTimeoutPassed;
    if ($offset == 0 || (isset($original_skip) && $original_skip == $offset)) {
        $message .= ' ' . $strTimeoutNothingParsed;
    }
}

// Display back import page
require_once('./header.inc.php');

// There was an error?
if (isset($my_die)) {
    foreach ($my_die AS $key => $die) {
        PMA_mysqlDie($die['error'], $die['sql'], '', $err_url, $error);
        echo '<hr />';
    }
}

if ($go_sql) {
    require_once('./sql.php');
} elseif ($import_type == 'server') {
    $active_page = 'server_import.php';
    require_once('./server_import.php');
} elseif ($import_type == 'database') {
    $active_page = 'db_import.php';
    require_once('./db_import.php');
} else {
    $active_page = 'tbl_import.php';
    require_once('./tbl_import.php');
}
exit();
?>
