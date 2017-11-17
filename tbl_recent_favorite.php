<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Browse recent and favourite tables chosen from navigation
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\RecentFavoriteTable;

require_once 'libraries/common.inc.php';

RecentFavoriteTable::getInstance('recent')
    ->removeIfInvalid($_REQUEST['db'], $_REQUEST['table']);

RecentFavoriteTable::getInstance('favorite')
    ->removeIfInvalid($_REQUEST['db'], $_REQUEST['table']);

require 'sql.php';
