<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./db_details_common.php3');
require('./libraries/relation.lib.php3');


/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam(TRUE);


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
