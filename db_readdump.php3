<?php
/* $Id$ */


/**
 * Increases the max. allowed time to run a script
 */
@set_time_limit(10000);


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./lib.inc.php3');


/**
 * Set up default values for some variables and 
 */
$view_bookmark = 0;
$sql_bookmark  = isset($sql_bookmark) ? $sql_bookmark : '';
$sql_query     = isset($sql_query)    ? $sql_query    : '';
$sql_file      = isset($sql_file)     ? $sql_file     : 'none';


/**
 * Bookmark Support: get a query back from bookmark if required
 */
if (!empty($id_bookmark)) {
    switch($action_bookmark) {
        case 0: // bookmarked query that have to be run
            $sql_query = query_bookmarks($db, $cfgBookmark, $id_bookmark);
            break;
        case 1: // bookmarked query that have to be displayed
            $sql_query = query_bookmarks($db, $cfgBookmark, $id_bookmark);
            $view_bookmark = 1;
            break;
        case 2: // bookmarked query that have to be deleted
            $sql_query = delete_bookmarks($db, $cfgBookmark, $id_bookmark);
            break;
    }
} // end if


/**
 * Prepares the sql query
 */
// Gets the query from a file if required 
if ($sql_file != 'none') {
// loic1: php < 4.05 for windows seems not to list the regexp test
//    if (ereg('^php[0-9A-Za-z_.-]+$', basename($sql_file))) {
    if (file_exists($sql_file)) {
        $sql_query = fread(fopen($sql_file, 'r'), filesize($sql_file));
        if (get_magic_quotes_runtime() == 1) {
            $sql_query = stripslashes($sql_query);
        }
    }
}
else if (empty($id_bookmark) && get_magic_quotes_gpc() == 1) {
	$sql_query = stripslashes($sql_query);
}
$sql_query = trim($sql_query);
// $sql_query come from the query textarea, if it's a reposted query gets its
// 'true' value
if (!empty($prev_sql_query)) {
    $prev_sql_query = urldecode($prev_sql_query);
    if ($sql_query == trim(htmlspecialchars($prev_sql_query))) {
        $sql_query  = $prev_sql_query;
    }
}

// Drop database is not allowed -> ensure the query can be run
if (!$cfgAllowUserDropDatabase
    && eregi('DROP[[:space:]]+(IF EXISTS[[:space:]]+)?DATABASE ', $sql_query)) {
    // Checks if the user is a Superuser
    // TODO: set a global variable with this information
    $result = mysql_query('SELECT * FROM mysql.user');
    $rows   = @mysql_num_rows($result);
    // empty <> 0 for certain php3 releases
    if (empty($rows) || $rows == 0) {
        include('./header.inc.php3');
        mysql_die($strNoDropDatabases);
    }
}
define('PMA_CHK_DROP', 1);

// Copy the query, used for display purposes only
$sql_query_cpy = $sql_query;


/**
 * Executes the query
 */
if ($sql_query != '') {
    $sql_query    = remove_remarks($sql_query);
    $pieces       = split_sql_file($sql_query, ';');
    $pieces_count = count($pieces);

    // Only one query to run
    if ($pieces_count == 1 && !empty($pieces[0]) && $view_bookmark == 0) {
        $sql_query = trim($pieces[0]);
        // sql.php3 will stripslash the query if get_magic_quotes_gpc
        if (get_magic_quotes_gpc() == 1) {
            $sql_query = addslashes($sql_query);
        }
        if (eregi('^(DROP|CREATE)[[:space:]]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)', $sql_query)) {
            $reload = 'true';
        }
        include('./sql.php3');
        exit();
    }

    // Runs multiple queries
    else if (mysql_select_db($db)) {
        for ($i = 0; $i < $pieces_count; $i++) {
            $a_sql_query = trim($pieces[$i]);
            if (!empty($a_sql_query) && $a_sql_query[0] != '#') {
                $result = mysql_query($a_sql_query);
                if ($result == FALSE) { // readdump failed
                    $my_die = $a_sql_query;
                    break;
                }
            }
            if (!isset($reload) && eregi('^(DROP|CREATE)[[:space:]]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)', $a_sql_query)) {
                $reload = 'true';
            }
        } // end for
    } // end else if
} // end if


/**
 * Go back to the calling script
 */
require('./header.inc.php3');
if (isset($my_die)) {
    mysql_die('', $my_die);
}
// Be nice with bandwidth...
if ($sql_file != 'none' && $pieces_count > 10) {
    $sql_query = '';
    unset($sql_query_cpy);
    $message   = "$strSuccess&nbsp:<br />$strTheContent ($pieces_count $strInstructions)&nbsp;";
} else {
    $message   = $strSuccess;
}
if (!isset($goto)
    || ($goto != 'db_details.php3' && $goto != 'tbl_properties.php3')) {
    $goto = 'db_details.php3';
}
require('./' . $goto);
?>
