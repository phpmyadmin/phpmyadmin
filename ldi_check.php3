<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * This file checks and builds the sql-string for
 * LOAD DATA INFILE 'file_name.txt' [REPLACE | IGNORE] INTO TABLE table_name
 *    [FIELDS
 *        [TERMINATED BY '\t']
 *        [OPTIONALLY] ENCLOSED BY "]
 *        [ESCAPED BY '\\' ]]
 *    [LINES TERMINATED BY '\n']
 *    [(column_name,...)]
 */


/**
 * Gets some core scripts
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');


/**
 * If a file from UploadDir was submitted, use this file
 */
$unlink_local_textfile = false;
if (isset($btnLDI) && isset($local_textfile) && $local_textfile != '') {
    if (empty($DOCUMENT_ROOT)) {
        if (!empty($_SERVER) && isset($_SERVER['DOCUMENT_ROOT'])) {
            $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
        }
        else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['DOCUMENT_ROOT'])) {
            $DOCUMENT_ROOT = $HTTP_SERVER_VARS['DOCUMENT_ROOT'];
        }
        else if (!empty($_ENV) && isset($_ENV['DOCUMENT_ROOT'])) {
            $DOCUMENT_ROOT = $_ENV['DOCUMENT_ROOT'];
        }
        else if (!empty($HTTP_ENV_VARS) && isset($HTTP_ENV_VARS['DOCUMENT_ROOT'])) {
            $DOCUMENT_ROOT = $HTTP_ENV_VARS['DOCUMENT_ROOT'];
        }
        else if (@getenv('DOCUMENT_ROOT')) {
            $DOCUMENT_ROOT = getenv('DOCUMENT_ROOT');
        }
        else {
            $DOCUMENT_ROOT = '.';
        }
    } // end if

    $textfile = $DOCUMENT_ROOT . dirname($PHP_SELF) . '/' . eregi_replace('^./', '', $cfg['UploadDir']) . eregi_replace('\.\.*', '.', $local_textfile);
    if (file_exists($textfile)) {
        $open_basedir     = '';
        if (PMA_PHP_INT_VERSION >= 40000) {
            $open_basedir = @ini_get('open_basedir');
        }
        if (empty($open_basedir)) {
            $open_basedir = @get_cfg_var('open_basedir');
        }

        // If we are on a server with open_basedir, we must move the file
        // before opening it. The doc explains how to create the "./tmp"
        // directory

        if (!empty($open_basedir)) {

            $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : './tmp/');

            // function is_writeable() is valid on PHP3 and 4
            if (!is_writeable($tmp_subdir)) {
                // if we cannot move the file, let PHP report the error
                error_reporting(E_ALL);
            } else {
                $textfile_new = $tmp_subdir . basename($textfile);
                if (PMA_PHP_INT_VERSION < 40003) {
                    copy($textfile, $textfile_new);
                } else {
                    move_uploaded_file($textfile, $textfile_new);
                }
                $textfile = $textfile_new;
                $unlink_local_textfile = true;
            }
        }
    }
}

/**
 * The form used to define the query has been submitted -> do the work
 */
if (isset($btnLDI) && empty($textfile)) {
    $js_to_run = 'functions.js';
    include('./header.inc.php3');
    $message = $strMustSelectFile;
    include('./ldi_table.php3');
} elseif (isset($btnLDI) && ($textfile != 'none')) {
    if (!isset($replace)) {
        $replace = '';
    }

    error_reporting(E_ALL);
    chmod($textfile, 0644);

    // Kanji encoding convert appended by Y.Kawada
    if (function_exists('PMA_kanji_file_conv')) {
        $textfile         = PMA_kanji_file_conv($textfile, $knjenc, isset($xkana) ? $xkana : '');
    }

    // Convert the file's charset if necessary
    if ($cfg['AllowAnywhereRecoding'] && $allow_recoding
        && isset($charset_of_file) && $charset_of_file != $charset) {
        $textfile         = PMA_convert_file($charset_of_file, $convcharset, $textfile);
    }

    // Formats the data posted to this script
    $textfile             = PMA_sqlAddslashes($textfile);
    $enclosed             = PMA_sqlAddslashes($enclosed);
    $escaped              = PMA_sqlAddslashes($escaped);
    $column_name          = PMA_sqlAddslashes($column_name);

    // (try to) make sure the file is readable:
    chmod($textfile, 0777);

    // Builds the query
    $sql_query     =  'LOAD DATA';

    // for versions before 3.23.49, we use the LOCAL keyword, because
    // there was a version (cannot find which one, and it does not work
    // with 3.23.38) where the user can LOAD, even if the user does not
    // have FILE priv, and even if the file is on the server
    // (which is the present case)
    //
    // we could also code our own loader, but LOAD DATA INFILE is optimized
    // for speed

    if (PMA_MYSQL_INT_VERSION < 32349) {
        $sql_query     .= ' LOCAL';
    }

    if (PMA_MYSQL_INT_VERSION > 40003) {
        $tmp_query  = "SHOW VARIABLES LIKE 'local\\_infile'";
        $result = PMA_mysql_query($tmp_query);
        if ($result != FALSE && mysql_num_rows($result) > 0) {
            $tmp = PMA_mysql_fetch_row($result);
            if ($tmp[1] == 'ON') {
                $sql_query     .= ' LOCAL';
            }
        }
        mysql_free_result($result);
    }

    $sql_query     .= ' INFILE \'' . $textfile . '\'';
    if (!empty($replace)) {
        $sql_query .= ' ' . $replace;
    }
    $sql_query     .= ' INTO TABLE ' . PMA_backquote($into_table);
    if (isset($field_terminater)) {
        $sql_query .= ' FIELDS TERMINATED BY \'' . $field_terminater . '\'';
    }
    if (isset($enclose_option) && strlen($enclose_option) > 0) {
        $sql_query .= ' OPTIONALLY';
    }
    if (strlen($enclosed) > 0) {
        $sql_query .= ' ENCLOSED BY \'' . $enclosed . '\'';
    }
    if (strlen($escaped) > 0) {
        $sql_query .= ' ESCAPED BY \'' . $escaped . '\'';
    }
    if (strlen($line_terminator) > 0){
        $sql_query .= ' LINES TERMINATED BY \'' . $line_terminator . '\'';
    }
    if (strlen($column_name) > 0) {
        if (PMA_MYSQL_INT_VERSION >= 32306) {
            $sql_query .= ' (';
            $tmp   = split(',( ?)', $column_name);
            for ($i = 0; $i < count($tmp); $i++) {
                if ($i > 0) {
                    $sql_query .= ', ';
                }
                $sql_query     .= PMA_backquote(trim($tmp[$i]));
            } // end for
            $sql_query .= ')';
        } else {
            $sql_query .= ' (' . $column_name . ')';
        }
    }

    // We could rename the ldi* scripts to tbl_properties_ldi* to improve
    // consistency with the other sub-pages.
    //
    // The $goto in ldi_table.php3 is set to tbl_properties.php3 but maybe
    // if would be better to Browse the latest inserted data.
    include('./sql.php3');
    if ($unlink_local_textfile) {
        unlink($textfile);
    }
}


/**
 * The form used to define the query hasn't been yet submitted -> loads it
 */
else {
    include('./ldi_table.php3');
}
?>
