<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require_once('./libraries/read_dump.lib.php');
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');

if (!isset($db)) {
    $db = '';
}

/**
 * Increases the max. allowed time to run a script
 */
@set_time_limit($cfg['ExecTimeLimit']);


/**
 * Defines the url to return to in case of error in a sql statement
 */
if (!isset($goto) || !preg_match('@^(db_details|tbl_properties)(_[a-z]*)?\.php$@i', $goto)) {
    $goto = 'db_details.php';
}
$err_url  = $goto
          . '?' . PMA_generate_common_url($db)
          . (preg_match('@^tbl_properties(_[a-z]*)?\.php$@', $goto) ? '&amp;table=' . urlencode($table) : '');


/**
 * Set up default values for some variables
 */
$view_bookmark = 0;
$sql_bookmark  = isset($sql_bookmark) ? $sql_bookmark : '';
$sql_query     = isset($sql_query)    ? $sql_query    : '';
if (!empty($sql_localfile) && !empty($cfg['UploadDir'])) {
    if (substr($cfg['UploadDir'], -1) != '/') {
        $cfg['UploadDir'] .= '/';
    }
    $sql_file  = $cfg['UploadDir'] . $sql_localfile;
} else if (empty($sql_file)) {
    $sql_file  = 'none';
}

/**
 * Bookmark Support: get a query back from bookmark if required
 */
if (!empty($id_bookmark)) {
    require_once('./libraries/bookmark.lib.php');
    switch ($action_bookmark) {
        case 0: // bookmarked query that have to be run
            $sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], $id_bookmark);
            if (isset($bookmark_variable) && !empty($bookmark_variable)) {
                $sql_query = preg_replace('|/\*(.*)\[VARIABLE\](.*)\*/|imsU', '${1}' . PMA_sqlAddslashes($bookmark_variable) . '${2}', $sql_query);
            }
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
        $open_basedir = @ini_get('open_basedir');

        if (!isset($sql_file_compression)) $sql_file_compression = '';

        // If we are on a server with open_basedir, we must move the file
        // before opening it. The doc explains how to create the "./tmp"
        // directory

        if (!empty($open_basedir)) {

            $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : './tmp/');

            // function is_writeable() is valid on PHP3 and 4
            if (!is_writeable($tmp_subdir)) {
                $sql_query = PMA_readFile($sql_file, $sql_file_compression);
                if ($sql_query == FALSE) {
                    $message = $strFileCouldNotBeRead;
                }
            }
            else {
                $sql_file_new = $tmp_subdir . basename($sql_file);
                move_uploaded_file($sql_file, $sql_file_new);
                $sql_query = PMA_readFile($sql_file_new, $sql_file_compression);
                if ($sql_query == FALSE) {
                    $message = $strFileCouldNotBeRead;
                }
                unlink($sql_file_new);
            }
        }
        else {
            // read from the normal upload dir
            $sql_query = PMA_readFile($sql_file, $sql_file_compression);
            if ($sql_query == FALSE) {
                $message = $strFileCouldNotBeRead;
            }
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
    && preg_match('@DROP[[:space:]]+(IF EXISTS[[:space:]]+)?DATABASE @i', $sql_query)) {
    // Checks if the user is a Superuser
    // TODO: set a global variable with this information
    // loic1: optimized query
    $result = @PMA_mysql_query('USE mysql');
    if (PMA_mysql_error()) {
        require_once('./header.inc.php');
        PMA_mysqlDie($strNoDropDatabases, '', '', $err_url);
    }
}
define('PMA_CHK_DROP', 1);

/**
 * Store a query as a bookmark before executing it?
 */
if (isset($SQLbookmark) && $sql_query != '') {
    require_once('./libraries/bookmark.lib.php');
    $bfields = array(
                 'dbase' => $db,
                 'user'  => $cfg['Bookmark']['user'],
                 'query' => urlencode($sql_query),
                 'label' => $bkm_label
    );

    PMA_addBookmarks($bfields, $cfg['Bookmark'], (isset($bkm_all_users) && $bkm_all_users == 'true' ? true : false));
}

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
    // beginning of "db_details.php" & "tbl_properties.php")

    // You can either
    // * specify the amount of maximum pieces per query (having max_*_length set to 0!) or
    // * specify the amount of maximum chars  per query (having max_*_pieces set to 0!)
    // - max_nofile_* is used for any queries submitted via copy&paste in the textarea
    // - max_file_*   is used for any file-submitted query
    if (!$cfg['VerboseMultiSubmit']) {
        // Here be the values if the Verbose-Mode (see config.inc.php) is NOT activated
        $max_nofile_length = 500;
        $max_nofile_pieces = 0;
        // Nijel: Here must be some limit, as extended inserts can be really
        //        huge and parsing them eats megabytes of memory
        $max_file_length   = 10000;
        $max_file_pieces   = 10;
    } else {
        // Values for verbose-mode
        $max_nofile_length = 0;
        $max_nofile_pieces = 50;
        // Nijel: Here must be some limit, as extended inserts can be really
        //        huge and parsing them eats megabytes of memory
        $max_file_length   = 50000;
        $max_file_pieces   = 50;
    }

    if ($sql_file != 'none' &&
          (($max_file_pieces != 0 && ($pieces_count > $max_file_pieces))
            ||
          ($max_file_length != 0 && (strlen($sql_query) > $max_file_length)))) {
          // Be nice with bandwidth...
        $sql_query_cpy = $sql_query = '';
        $save_bandwidth = TRUE;
        $save_bandwidth_length = $max_file_length;
        $save_bandwidth_pieces = $max_file_pieces;
    } else {

        $sql_query_cpy = implode(";\n", $pieces) . ';';
         // Be nice with bandwidth... for now, an arbitrary limit of 500,
         // could be made configurable but probably not necessary
        if (($max_nofile_length != 0 && (strlen($sql_query_cpy) > $max_nofile_length))
              || ($max_nofile_pieces != 0 && $pieces_count > $max_nofile_pieces)) {
            $sql_query_cpy = $sql_query = '';
            $save_bandwidth = TRUE;
            $save_bandwidth_length = $max_nofile_length;
            $save_bandwidth_pieces = $max_nofile_pieces;
        }
    }

    // really run the query?
    if ($view_bookmark == 0) {
        // Only one query to run
        if ($pieces_count == 1 && !empty($pieces[0])) {
            $sql_query = $pieces[0];
            if (preg_match('@^(DROP|CREATE)[[:space:]]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)@i', $sql_query)) {
                $reload = 1;
            }
            require('./sql.php');
        }

        // Runs multiple queries
        else if (PMA_mysql_select_db($db)) {
            $mult = TRUE;
            $info_msg = '';
            $info_count = 0;

            for ($i = 0; $i < $pieces_count; $i++) {
                $a_sql_query = $pieces[$i];
                if ($i == $pieces_count - 1 && preg_match('@^(SELECT|SHOW)@i', $a_sql_query)) {
                    $complete_query = $sql_query;
                    $display_query = $sql_query;
                    $sql_query = $a_sql_query;
                    require('./sql.php');
                }

                $result = PMA_mysql_query($a_sql_query);
                if ($result == FALSE) { // readdump failed
                    if (isset($my_die) && $cfg['IgnoreMultiSubmitErrors']) {
                        $my_die[] = "\n\n" . $a_sql_query;
                    } elseif ($cfg['IgnoreMultiSubmitErrors']) {
                        $my_die = array();
                        $my_die[] = $a_sql_query;
                    } else {
                        $my_die = $a_sql_query;
                    }

                    if ($cfg['VerboseMultiSubmit']) {
                        $info_msg .= $a_sql_query . '; # ' . $strError . "\n";
                        $info_count++;
                    }

                    if (!$cfg['IgnoreMultiSubmitErrors']) {
                        break;
                    }
                } else if ($cfg['VerboseMultiSubmit']) {
                    $a_num_rows = (int)@mysql_num_rows($result);
                    $a_aff_rows = (int)@mysql_affected_rows();
                    if ($a_num_rows > 0) {
                        $a_rows = $a_num_rows;
                        $a_switch = $strRows . ': ';
                    } elseif ($a_aff_rows > 0) {
                        $a_rows = $a_aff_rows;
                        $a_switch = $strAffectedRows;;
                    } else {
                        $a_rows = '';
                        $a_switch = $strEmptyResultSet;
                    }

                    $info_msg .= $a_sql_query . "; # " . $a_switch . $a_rows . "\n";
                    $info_count++;
                }

                if (!isset($reload) && preg_match('@^(DROP|CREATE)[[:space:]]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)@i', $a_sql_query)) {
                    $reload = 1;
                }
            } // end for

            if ($cfg['VerboseMultiSubmit'] && strlen($info_msg) > 0 &&
                  ((!isset($save_bandwidth) || $save_bandwidth == FALSE) ||
                  ($save_bandwidth_pieces == 0 && strlen($info_msg) < $save_bandwidth_length) ||
                  ($save_bandwidth_length == 0 && $info_count < $save_bandwidth_pieces))) {
                $sql_query = $info_msg;
            }

        } // end else if
    } // end if (really run the query)
    unset($pieces);
} // end if



/**
 * MySQL error
 */
if (isset($my_die)) {
    $js_to_run = 'functions.js';
    require_once('./header.inc.php');
    if (is_array($my_die)) {
        foreach($my_die AS $key => $die_string) {
            PMA_mysqlDie('', $die_string, '', $err_url, FALSE);
            echo '<hr />';
        }
    } else {
        PMA_mysqlDie('', $my_die, '', $err_url, TRUE);
    }
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
if ($goto == 'tbl_properties.php') {
    if (!isset($table)) {
        $goto     = 'db_details.php';
    } else {
        PMA_mysql_select_db($db);
        $is_table = @PMA_mysql_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'');
        if (!($is_table && @mysql_numrows($is_table))) {
            $goto = 'db_details.php';
            unset($table);
        }
    } // end if... else...
}
if ($goto == 'db_details.php') {
    if (isset($table)) {
        unset($table);
    }
    if (!isset($db)) {
        $goto     = 'main.php';
    } else {
        $is_db    = @PMA_mysql_select_db($db);
        if (!$is_db) {
            $goto = 'main.php';
            unset($db);
        }
    } // end if... else...
}
// Defines the message to be displayed
if (!empty($id_bookmark) && $action_bookmark == 2) {
    $message   = $strBookmarkDeleted;
} else if (!isset($sql_query_cpy)) {
    if (empty($message)) {
        $message   = $strNoQuery;
    }
} else if ($sql_query_cpy == '') {
    $message   = "$strSuccess&nbsp;:<br />$strTheContent ($pieces_count $strInstructions)&nbsp;";
} else {
    $message   = $strSuccess;
}
// Loads to target script
if ($goto == 'db_details.php' || $goto == 'tbl_properties.php') {
    $js_to_run = 'functions.js';
}
if ($goto != 'main.php') {
    require_once('./header.inc.php');
}
$active_page = $goto;
require('./' . $goto);
?>
