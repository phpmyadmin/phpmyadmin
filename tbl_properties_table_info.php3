<?php
/* $Id$ */


/**
 * Gets table informations
 */
// The 'show table' statement works correct since 3.23.03
if (PMA_MYSQL_INT_VERSION >= 32303) {
    $local_query  = 'SHOW TABLE STATUS LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'';
    $result       = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
    $showtable    = mysql_fetch_array($result);
    $tbl_type     = strtoupper($showtable['Type']);
    $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
    $show_comment = (isset($showtable['Comment']) ? $showtable['Comment'] : '');

// better logic, thanks to Loic:
    $tmp              = explode(' ', $showtable['Create_options']);
    $tmp_cnt          = count($tmp);
    for ($i = 0; $i < $tmp_cnt; $i++) {
        $tmp1         = explode('=', $tmp[$i]);
        if (isset($tmp1[1])) {
            $$tmp1[0] = $tmp1[1];
        }
    } // end for
    unset($tmp1);
    unset($tmp);
} else {
    $local_query  = 'SELECT COUNT(*) AS count FROM ' . PMA_backquote($table);
    $result       = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
    $showtable    = array();
    $num_rows     = mysql_result($result, 0, 'count');
    $show_comment = '';
}
mysql_free_result($result);


/**
 * Displays top menu links
 */
echo '<!-- first browse links -->' . "\n";
require('./tbl_properties_links.php3');


/**
 * Displays table comment
 */
if (!empty($show_comment)) {
    ?>
<!-- Table comment -->
<p><i>
    <?php echo $show_comment . "\n"; ?>
</i></p>
    <?php
} // end if

?>
