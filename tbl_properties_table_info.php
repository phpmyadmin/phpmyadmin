<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// this should be recoded as functions, to avoid messing with global
// variables

// Check parameters

require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db', 'table'));

/**
 * Gets table informations
 */
// The 'show table' statement works correct since 3.23.03
$local_query         = 'SHOW TABLE STATUS LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'';
$table_info_result   = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
$showtable           = PMA_mysql_fetch_array($table_info_result);
$tbl_type            = strtoupper($showtable['Type']);
$tbl_charset         = empty($showtable['Charset']) ? '' : $showtable['Charset'];
$table_info_num_rows = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
$show_comment        = (isset($showtable['Comment']) ? $showtable['Comment'] : '');
$auto_increment      = (isset($showtable['Auto_increment']) ? $showtable['Auto_increment'] : '');

$tmp                 = explode(' ', $showtable['Create_options']);
$tmp_cnt             = count($tmp);
for ($i = 0; $i < $tmp_cnt; $i++) {
    $tmp1            = explode('=', $tmp[$i]);
    if (isset($tmp1[1])) {
        $$tmp1[0]    = $tmp1[1];
    }
} // end for
unset($tmp1, $tmp);
mysql_free_result($table_info_result);


/**
 * Displays top menu links
 */
echo '<!-- top menu -->' . "\n";
require('./tbl_properties_links.php');


/**
 * Displays table comment
 */
if (!empty($show_comment) && !isset($avoid_show_comment)) {
    ?>
<!-- Table comment -->
<p><i>
    <?php echo htmlspecialchars($show_comment) . "\n"; ?>
</i></p>
    <?php
} // end if

echo "\n\n";
?>
