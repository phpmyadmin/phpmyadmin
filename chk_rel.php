<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/header.inc.php';


/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam(true);


/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
