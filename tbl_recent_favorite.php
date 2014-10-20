<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Browse recent and favourite tables chosen from navigation
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/RecentFavoriteTable.class.php';

PMA_RecentFavoriteTable::getInstance('recent')
    ->removeIfInvalid($_REQUEST['db'], $_REQUEST['table']);

PMA_RecentFavoriteTable::getInstance('favorite')
    ->removeIfInvalid($_REQUEST['db'], $_REQUEST['table']);

require 'sql.php';
?>