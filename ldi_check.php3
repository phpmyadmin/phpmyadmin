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
 * The form used to define the query has been submitted -> do the work
 */
if (isset($btnLDI) && ($textfile != 'none')) {
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
    if (get_magic_quotes_gpc()) {
        $field_terminater = stripslashes($field_terminater);
        $enclosed         = PMA_sqlAddslashes(stripslashes($enclosed));
        $escaped          = PMA_sqlAddslashes(stripslashes($escaped));
        $line_terminator  = stripslashes($line_terminator);
        $column_name      = PMA_sqlAddslashes(stripslashes($column_name));
    } else {
        $enclosed         = PMA_sqlAddslashes($enclosed);
        $escaped          = PMA_sqlAddslashes($escaped);
        $column_name      = PMA_sqlAddslashes($column_name);
    }

    // (try to) make sure the file is readable:
    chmod($textfile, 0777);

    // Builds the query
    $query     =  'LOAD DATA';

    // for versions before 3.23.49, we use the LOCAL keyword, because
    // there was a version (cannot find which one, and it does not work
    // with 3.23.38) where the user can LOAD, even if the user does not 
    // have FILE priv, and even if the file is on the server 
    // (which is the present case)
    //
    // if we find how to check the server about --local-infile 
    // and --enable-local-infile, we could modify the code
    // to use LOCAL for version >= 32349 if the server accepts it
    //
    // we could also code our own loader, but LOAD DATA INFILE is optimized
    // for speed

    if (PMA_MYSQL_INT_VERSION < 32349) {
        $query     .= ' LOCAL';
    }
    $query     .= ' INFILE \'' . $textfile . '\'';
    if (!empty($replace)) {
        $query .= ' ' . $replace;
    }
    $query     .= ' INTO TABLE ' . PMA_backquote($into_table);
    if (isset($field_terminater)) {
        $query .= ' FIELDS TERMINATED BY \'' . $field_terminater . '\'';
    }
    if (isset($enclose_option) && strlen($enclose_option) > 0) {
        $query .= ' OPTIONALLY';
    }
    if (strlen($enclosed) > 0) {
        $query .= ' ENCLOSED BY \'' . $enclosed . '\'';
    }
    if (strlen($escaped) > 0) {
        $query .= ' ESCAPED BY \'' . $escaped . '\'';
    }
    if (strlen($line_terminator) > 0){
        $query .= ' LINES TERMINATED BY \'' . $line_terminator . '\'';
    }
    if (strlen($column_name) > 0) {
        if (PMA_MYSQL_INT_VERSION >= 32306) {
            $query .= ' (';
            $tmp   = split(',( ?)', $column_name);
            for ($i = 0; $i < count($tmp); $i++) {
                if ($i > 0) {
                    $query .= ', ';
                }
                $query     .= PMA_backquote(trim($tmp[$i]));
            } // end for
            $query .= ')';
        } else {
            $query .= ' (' . $column_name . ')';
        }
    }

    // Executes the query
    // sql.php3 will stripslash the query if 'magic_quotes_gpc' is set to on
    if (get_magic_quotes_gpc()) {
        $sql_query = addslashes($query);
    } else {
        $sql_query = $query;
    }

    // We could rename the ldi* scripts to tbl_properties_ldi* to improve
    // consistency with the other sub-pages.
    //
    // The $goto in ldi_table.php3 is set to tbl_properties.php3 but maybe
    // if would be better to Browse the latest inserted data.
    include('./sql.php3');
}


/**
 * The form used to define the query hasn't been yet submitted -> loads it
 */
else {
    include('./ldi_table.php3');
}
?>
