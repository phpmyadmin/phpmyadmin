<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require('./libraries/read_dump.lib.php3');
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');


/**
 * Increases the max. allowed time to run a script
 */
@set_time_limit($cfg['ExecTimeLimit']);


/**
 * Defines the url to return to in case of error in a sql statement
 */
if (!isset($goto)
    || ($goto != 'db_details.php3' && $goto != 'tbl_properties.php3')) {
    $goto = 'db_details.php3';
}
$err_url  = $goto
          . '?' . PMA_generate_common_url($db)
          . (($goto == 'tbl_properties.php3') ? '&amp;table=' . urlencode($table) : '');


/**
 * Set up default values for some variables
 */
$view_bookmark = 0;
$sql_bookmark  = isset($sql_bookmark) ? $sql_bookmark : '';
$sql_query     = isset($sql_query)    ? $sql_query    : '';
if (!empty($sql_localfile) && $cfg['UploadDir'] != '') {
    $sql_file  = $cfg['UploadDir'] . $sql_localfile;
} else if (empty($sql_file)) {
    $sql_file  = 'none';
}


/**
 * Bookmark Support: get a query back from bookmark if required
 */
if (!empty($id_bookmark)) {
    include('./libraries/bookmark.lib.php3');
    switch ($action_bookmark) {
        case 0: // bookmarked query that have to be run
            $sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], $id_bookmark);
            break;
        case 1: // bookmarked query that have to be displayed
            $sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], $id_bookmark);
            $view_bookmark = 1;
            break;
        case 2: // bookmarked query that have to be deleted
            $sql_query = PMA_deleteBookmarks($db, $cfg['Bookmark'], $id_bookmark);
            break;
    }
} // end if


/**
 * Prepares the sql query
 */
// Gets the query from a file if required
if ($sql_file != 'none') {
// loic1 : fixed a security issue
//    if ((file_exists($sql_file) && is_uploaded_file($sql_file))
//        || file_exists($cfg['UploadDir'] . $sql_localfile)) {
    if (file_exists($sql_file)
        && ((isset($sql_localfile) && $sql_file == $cfg['UploadDir'] . $sql_localfile) || is_uploaded_file($sql_file))) {
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
                $sql_query = PMA_readFile($sql_file, $sql_file_compression);
            }
            else {
                $sql_file_new = $tmp_subdir . basename($sql_file);
                if (PMA_PHP_INT_VERSION < 40003) {
                    copy($sql_file, $sql_file_new);
                } else {
                    move_uploaded_file($sql_file, $sql_file_new);
                }
                $sql_query = PMA_readFile($sql_file_new, $sql_file_compression);
                unlink($sql_file_new);
            }
        }
        else {
            // read from the normal upload dir
            $sql_query = PMA_readFile($sql_file, $sql_file_compression);
        }

        // Convert the file's charset if necessary
        if ($cfg['AllowAnywhereRecoding'] && $allow_recoding
            && isset($charset_of_file) && $charset_of_file != $charset) {
            $sql_query = PMA_convert_string($charset_of_file, $charset, $sql_query);
        }
    } // end uploaded file stuff
}

// Kanji convert SQL textfile 2002/1/4 by Y.Kawada
if (@function_exists('PMA_kanji_str_conv')) {
    $sql_tmp   = trim($sql_query);
    PMA_change_enc_order();
    $sql_query = PMA_kanji_str_conv($sql_tmp, $knjenc, isset($xkana) ? $xkana : '');
    PMA_change_enc_order();
} else {
    $sql_query = trim($sql_query);
}

// $sql_query come from the query textarea, if it's a reposted query gets its
// 'true' value
if (!empty($prev_sql_query)) {
    $prev_sql_query = urldecode($prev_sql_query);
    if ($sql_query == trim(htmlspecialchars($prev_sql_query))) {
        $sql_query  = $prev_sql_query;
    }
}

// Drop database is not allowed -> ensure the query can be run
if (!$cfg['AllowUserDropDatabase']
    && eregi('DROP[[:space:]]+(IF EXISTS[[:space:]]+)?DATABASE ', $sql_query)) {
    // Checks if the user is a Superuser
    // TODO: set a global variable with this information
    // loic1: optimized query
    $result = @PMA_mysql_query('USE mysql');
    if (PMA_mysql_error()) {
        include('./header.inc.php3');
        PMA_mysqlDie($strNoDropDatabases, '', '', $err_url);
    }
}
define('PMA_CHK_DROP', 1);

/**
 * Executes the query
 */
if ($sql_query != '') {
    $pieces       = array();
    PMA_splitSqlFile($pieces, $sql_query, PMA_MYSQL_INT_VERSION);
    $pieces_count = count($pieces);
    if ($pieces_count > 1) {
        $is_multiple = TRUE;
    }

    // Copy of the cleaned sql statement for display purpose only (see near the
    // beginning of "db_details.php3" & "tbl_properties.php3")
    if ($sql_file != 'none' && $pieces_count > 10) {
         // Be nice with bandwidth...
        $sql_query_cpy = $sql_query = '';
    } else {
        $sql_query_cpy = implode(";\n", $pieces) . ';';
         // Be nice with bandwidth... for now, an arbitrary limit of 500,
         // could be made configurable but probably not necessary
        if (strlen($sql_query_cpy) > 500) {
            $sql_query_cpy = $sql_query = '';
        }
    }

    // really run the query?
    if ($view_bookmark == 0) {
        // Only one query to run
        if ($pieces_count == 1 && !empty($pieces[0])) {
            $sql_query = $pieces[0];
            if (eregi('^(DROP|CREATE)[[:space:]]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)', $sql_query)) {
                $reload = 1;
            }
            include('./sql.php3');
            exit();
        }

        // Runs multiple queries
        else if (PMA_mysql_select_db($db)) {
            $mult = TRUE;
            for ($i = 0; $i < $pieces_count; $i++) {
                $a_sql_query = $pieces[$i];
                $result = PMA_mysql_query($a_sql_query);
                if ($result == FALSE) { // readdump failed
                    $my_die = $a_sql_query;
                    break;
                }
                if (!isset($reload) && eregi('^(DROP|CREATE)[[:space:]]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)', $a_sql_query)) {
                    $reload = 1;
                }
            } // end for
        } // end else if
    } // end if (really run the query)
    unset($pieces);
} // end if



/**
 * MySQL error
 */
if (isset($my_die)) {
    $js_to_run = 'functions.js';
    include('./header.inc.php3');
    PMA_mysqlDie('', $my_die, '', $err_url);
}


/**
 * Go back to the calling script
 */
// Checks for a valid target script
if (isset($table) && $table == '') {
    unset($table);
}
if (isset($db) && $db == '') {
    unset($db);
}
$is_db = $is_table = FALSE;
if ($goto == 'tbl_properties.php3') {
    if (!isset($table)) {
        $goto     = 'db_details.php3';
    } else {
        $is_table = @PMA_mysql_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'');
        if (!($is_table && @mysql_numrows($is_table))) {
            $goto = 'db_details.php3';
            unset($table);
        }
    } // end if... else...
}
if ($goto == 'db_details.php3') {
    if (isset($table)) {
        unset($table);
    }
    if (!isset($db)) {
        $goto     = 'main.php3';
    } else {
        $is_db    = @PMA_mysql_select_db($db);
        if (!$is_db) {
            $goto = 'main.php3';
            unset($db);
        }
    } // end if... else...
}
// Defines the message to be displayed
if (!empty($id_bookmark) && $action_bookmark == 2) {
    $message   = $strBookmarkDeleted;
} else if (!isset($sql_query_cpy)) {
    $message   = $strNoQuery;
} else if ($sql_query_cpy == '') {
    $message   = "$strSuccess&nbsp;:<br />$strTheContent ($pieces_count $strInstructions)&nbsp;";
} else {
    $message   = $strSuccess;
}
// Loads to target script
if ($goto == 'db_details.php3' || $goto == 'tbl_properties.php3') {
    $js_to_run = 'functions.js';
}
if ($goto != 'main.php3') {
    include('./header.inc.php3');
}
require('./' . $goto);
?>
