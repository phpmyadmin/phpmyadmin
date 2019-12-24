<?php
/**
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\RecentFavoriteTable;

/**
 * Browse recent and favorite tables chosen from navigation.
 *
 * @package PhpMyAdmin\Controllers\Table
 */
class RecentFavoriteController extends AbstractController
{
    /**
     * @return void
     */
    public function index(): void
    {
        RecentFavoriteTable::getInstance('recent')->removeIfInvalid(
            $_REQUEST['db'],
            $_REQUEST['table']
        );

        RecentFavoriteTable::getInstance('favorite')->removeIfInvalid(
            $_REQUEST['db'],
            $_REQUEST['table']
        );

        require ROOT_PATH . 'libraries/entry_points/sql.php';
    }
}
