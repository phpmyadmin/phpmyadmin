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
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');

// Check parameters

PMA_checkParameters(array('db', 'table'));

/**
 * If a file from UploadDir was submitted, use this file
 */
$unlink_local_textfile = false;
if (isset($btnLDI) && isset($local_textfile) && $local_textfile != '') {
    if (empty($DOCUMENT_ROOT)) {
        if (!empty($_SERVER) && isset($_SERVER['DOCUMENT_ROOT'])) {
            $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
        }
        else if (!empty($_ENV) && isset($_ENV['DOCUMENT_ROOT'])) {
            $DOCUMENT_ROOT = $_ENV['DOCUMENT_ROOT'];
        }
        else if (@getenv('DOCUMENT_ROOT')) {
            $DOCUMENT_ROOT = getenv('DOCUMENT_ROOT');
        }
        else {
            $DOCUMENT_ROOT = '.';
        }
    } // end if

    if (substr($cfg['UploadDir'], -1) != '/') {
        $cfg['UploadDir'] .= '/';
    }
    $textfile = $DOCUMENT_ROOT . dirname($PHP_SELF) . '/' . preg_replace('@^./@s', '', $cfg['UploadDir']) . preg_replace('@\.\.*@', '.', $local_textfile);
    if (file_exists($textfile)) {
        $open_basedir = @ini_get('open_basedir');

        // If we are on a server with open_basedir, we must move the file
        // before opening it. The doc explains how to create the "./tmp"
        // directory

        if (!empty($open_basedir)) {

            $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : './tmp/');

            // function is_writeable() is valid on PHP3 and 4
            if (!is_writeable($tmp_subdir)) {
                echo $strWebServerUploadDirectoryError . ': ' . $tmp_subdir
                 . '<br />';
                exit();
            } else {
                $textfile_new = $tmp_subdir . basename($textfile);
                move_uploaded_file($textfile, $textfile_new);
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
    require_once('./header.inc.php');
    $message = $strMustSelectFile;
    require('./ldi_table.php');
} elseif (isset($btnLDI) && ($textfile != 'none')) {
    if (!isset($replace)) {
        $replace = '';
    }

    // the error message does not correspond exactly to the error...
    if (!@chmod($textfile, 0644)) {
       echo $strFileCouldNotBeRead . ' ' . $textfile . '<br />';
       require_once('./footer.inc.php');
    }

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

    if ($local_option == "1") {
        $sql_query     .= ' LOCAL';
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
        $sql_query .= ' (';
        $tmp   = split(',( ?)', $column_name);
        $cnt_tmp = count($tmp);
        for ($i = 0; $i < $cnt_tmp; $i++) {
            if ($i > 0) {
                $sql_query .= ', ';
            }
            $sql_query     .= PMA_backquote(trim($tmp[$i]));
        } // end for
        $sql_query .= ')';
    }

    // We could rename the ldi* scripts to tbl_properties_ldi* to improve
    // consistency with the other sub-pages.
    //
    // The $goto in ldi_table.php is set to tbl_properties.php but maybe
    // if would be better to Browse the latest inserted data.
    require('./sql.php');
    if ($unlink_local_textfile) {
        unlink($textfile);
    }
}


/**
 * The form used to define the query hasn't been yet submitted -> loads it
 */
else {
    require('./ldi_table.php');
}
?>
