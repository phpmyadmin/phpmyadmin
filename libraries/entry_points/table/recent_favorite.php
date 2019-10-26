<?php
/**
 * Browse recent and favourite tables chosen from navigation
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\RecentFavoriteTable;

if (! defined('PHPMYADMIN')) {
    exit;
}

RecentFavoriteTable::getInstance('recent')
    ->removeIfInvalid($_REQUEST['db'], $_REQUEST['table']);

RecentFavoriteTable::getInstance('favorite')
    ->removeIfInvalid($_REQUEST['db'], $_REQUEST['table']);

require ROOT_PATH . 'libraries/entry_points/sql.php';
