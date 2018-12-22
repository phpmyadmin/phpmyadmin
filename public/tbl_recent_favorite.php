<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Browse recent and favourite tables chosen from navigation
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\RecentFavoriteTable;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

RecentFavoriteTable::getInstance('recent')
    ->removeIfInvalid($_REQUEST['db'], $_REQUEST['table']);

RecentFavoriteTable::getInstance('favorite')
    ->removeIfInvalid($_REQUEST['db'], $_REQUEST['table']);

require ROOT_PATH . 'sql.php';
