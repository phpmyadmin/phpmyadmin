<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays status of phpMyAdmin configuration storage
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
PMA_Header::getInstance()->display();


/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam(true);


/**
 * Displays the footer
 */
require 'libraries/footer.inc.php';
?>
