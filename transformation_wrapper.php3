<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Get the variables sent or posted to this script and displays the header
 */
require('./libraries/grab_globals.lib.php3');

/**
 * Gets a core script and starts output buffering work
 */
if (!defined('PMA_COMMON_LIB_INCLUDED')) {
    include('./libraries/common.lib.php3');
}
if (!defined('PMA_OB_LIB_INCLUDED')) {
    include('./libraries/ob.lib.php3');
}

require('./libraries/relation.lib.php3'); // foreign keys
require('./libraries/transformations.lib.php3'); // Transformations
$cfgRelation = PMA_getRelationsParam();


/**
 * Displays the query submitted and its result
 */
if (!empty($message)) {
    if (isset($goto)) {
        $goto_cpy      = $goto;
        $goto          = 'tbl_properties.php3?' 
                       . PMA_generate_common_url($db, $table)
                       . '&amp;$show_query=1'
                       . '&amp;sql_query=' . urlencode($disp_query);
    } else {
        $show_query = '1';
    }
    if (isset($sql_query)) {
        $sql_query_cpy = $sql_query;
        unset($sql_query);
    }
    if (isset($disp_query)) {
        $sql_query     = (get_magic_quotes_gpc() ? stripslashes($disp_query) : $disp_query);
    }
    PMA_showMessage($message);
    if (isset($goto_cpy)) {
        $goto          = $goto_cpy;
        unset($goto_cpy);
    }
    if (isset($sql_query_cpy)) {
        $sql_query     = $sql_query_cpy;
        unset($sql_query_cpy);
    }
}
if (get_magic_quotes_gpc()) {
    if (!empty($sql_query)) {
        $sql_query   = stripslashes($sql_query);
    }
    if (!empty($primary_key)) {
        $primary_key = stripslashes($primary_key);
    }
} // end if


/**
 * Defines the url to return to in case of error in a sql statement
 */
if (!isset($goto)) {
    $goto    = 'db_details.php3';
}
if (!ereg('^(db_details|tbl_properties|tbl_select)', $goto)) {
    $err_url = $goto . "?" . PMA_generate_common_url($db) . "&amp;sql_query=" . urlencode($sql_query);
} else {
    $err_url = $goto . '?'
             . PMA_generate_common_url($db)
             . ((ereg('^(tbl_properties|tbl_select)', $goto)) ? '&amp;table=' . urlencode($table) : '');
}


/**
 * Ensures db and table are valid, else moves to the "parent" script
 */
require('./libraries/db_table_exists.lib.php3');


/**
 * Sets parameters for links and displays top menu
 */
$url_query = PMA_generate_common_url($db, $table)
           . '&amp;goto=tbl_properties.php3';

/**
 * Get the list of the fields of the current table
 */
PMA_mysql_select_db($db);
$table_def = PMA_mysql_query('SHOW FIELDS FROM ' . PMA_backquote($table));
if (isset($primary_key)) {
    $local_query = 'SELECT * FROM ' . PMA_backquote($table) . ' WHERE ' . $primary_key;
    $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    $row         = PMA_mysql_fetch_array($result);
    // No row returned
    if (!$row) {
        unset($row);
        unset($primary_key);
        $goto_cpy          = $goto;
        $goto              = 'tbl_properties.php3?'
                           . PMA_generate_common_url($db, $table)
                           . '&amp;$show_query=1'
                           . '&amp;sql_query=' . urlencode($local_query);
        if (isset($sql_query)) {
            $sql_query_cpy = $sql_query;
            unset($sql_query);
        }
        $sql_query         = $local_query;
        PMA_showMessage($strEmptyResultSet);
        $goto              = $goto_cpy;
        unset($goto_cpy);
        if (isset($sql_query_cpy)) {
            $sql_query    = $sql_query_cpy;
            unset($sql_query_cpy);
        }
    } // end if (no record returned)
}
else
{
    $local_query = 'SELECT * FROM ' . PMA_backquote($table) . ' LIMIT 1';
    $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    unset($row);
}

$default_ct = 'application/octet-stream';

if ($cfgRelation['commwork']) {
    $mime_map = PMA_getMime($db, $table);
    $mime_options = PMA_transformation_getOptions((isset($mime_map[urldecode($transform_key)]['transformation_options']) ? $mime_map[urldecode($transform_key)]['transformation_options'] : ''));

    @reset($mime_options);
    while(list($key, $option) = each($mime_options)) {
        if (eregi('^; charset=.*$', $option)) {
            $mime_options['charset'] = $option;
        }
    }
}

/**
 * Sends http headers
 */
// Don't use cache (required for Opera)
$GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';
header('Expires: ' . $GLOBALS['now']); // rfc2616 - Section 14.21
header('Last-Modified: ' . $GLOBALS['now']);
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache'); // HTTP/1.0
// [MIME]
$content_type = 'Content-Type: ' . (isset($mime_map[urldecode($transform_key)]['mimetype']) ? str_replace("_", "/", $mime_map[urldecode($transform_key)]['mimetype']) : $default_ct) . (isset($mime_options['charset']) ? $mime_options['charset'] : '');
header($content_type);

echo $row[urldecode($transform_key)];

/**
 * Close MySql non-persistent connections
 */
if (isset($GLOBALS['dbh']) && $GLOBALS['dbh']) {
    @mysql_close($GLOBALS['dbh']);
}
if (isset($GLOBALS['userlink']) && $GLOBALS['userlink']) {
    @mysql_close($GLOBALS['userlink']);
}
?>
