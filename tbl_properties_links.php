<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Sets error reporting level
 */
// (removed to avoid path disclosure, not sure about why this was here)
// error_reporting(E_ALL);


// Check parameters

require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db'));

/**
 * Count amount of navigation tabs
 */
$db_details_links_count_tabs = 0;


/**
 * Prepares links
 */
require_once('./libraries/bookmark.lib.php');
$book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');

if ($table_info_num_rows > 0) {
    $lnk2    = 'sql.php';
    $arg2    = $url_query
             . '&amp;sql_query=' . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table)))
             . '&amp;pos=0';
    $lnk4    = 'tbl_select.php';
    $arg4    = $url_query;
    $ln6_stt = (PMA_MYSQL_INT_VERSION >= 40000)
             ? 'TRUNCATE TABLE '
             : 'DELETE FROM ';
    $lnk6    = 'sql.php';
    $arg6    = $url_query . '&amp;sql_query='
             . urlencode($ln6_stt . PMA_backquote($table))
             .  '&amp;zero_rows='
             .  urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)));
    $att6    = 'class="drop" onclick="return confirmLink(this, \'' . $ln6_stt . PMA_jsFormat($table) . '\')"';
} else {
    $lnk2    = '';
    $arg2    = '';
    $lnk4    = '';
    $arg4    = '';
    $lnk6    = '';
    $arg6    = '';
    $att6    = '';
}

$arg7 = $url_query . '&amp;reload=1&amp;purge=1&amp;sql_query=' . urlencode('DROP TABLE ' . PMA_backquote($table) ) . '&amp;zero_rows=' . urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table)));
$att7 = 'class="drop" onclick="return confirmLink(this, \'DROP TABLE ' . PMA_jsFormat($table) . '\')"';


/**
 * Displays links
 */

if ($cfg['LightTabs']) {
    echo '&nbsp;';
} else {
    echo '<table border="0" cellspacing="0" cellpadding="3" width="100%" class="tabs">' . "\n"
       . '    <tr>' . "\n"
       . '        <td width="8">&nbsp;</td>';
}

echo PMA_printTab($strStructure, 'tbl_properties_structure.php', $url_query)
   . PMA_printTab($strBrowse, $lnk2, $arg2)
   . PMA_printTab($strSQL, 'tbl_properties.php', $url_query)
   . PMA_printTab($strSearch, $lnk4, $arg4)
   . PMA_printTab($strInsert, 'tbl_change.php', $url_query)
   . PMA_printTab($strExport, 'tbl_properties_export.php', $url_query . '&amp;single_table=true')
   . PMA_printTab($strOperations, 'tbl_properties_operations.php', $url_query)
   . PMA_printTab($strEmpty, $lnk6, $arg6, $att6)
   . PMA_printTab($strDrop, 'sql.php', $arg7, $att7)
   . "\n";

if (!$cfg['LightTabs']) {
    echo '</tr></table>';
} else {
    echo '<br />';
}

?><br />
