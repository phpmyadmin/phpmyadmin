<?php
/* $Id$ */


/**
 * Runs common work
 */
require('./tbl_properties_common.php3');
$err_url   = 'tbl_properties.php3' . $err_url;
$url_query .= '&amp;back=tbl_properties.php3';

/**
 * Top menu
 */
require('./tbl_properties_table_info.php3');

?>
<ul>

<!-- TABLE WORK -->
<?php
/**
 * Query box, bookmark, insert data from textfile
 */
$goto = 'tbl_properties.php3';
require('./tbl_query_box.php3');

?>
</ul>

<?php

/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
