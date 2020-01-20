<?php
/**
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\RecentFavoriteTable;

/**
 * Browse recent and favorite tables chosen from navigation.
 *
 * @package PhpMyAdmin\Controllers\Table
 */
class RecentFavoriteController extends AbstractController
{
    public function index(): void
    {
        global $containerBuilder;

        RecentFavoriteTable::getInstance('recent')->removeIfInvalid(
            $_REQUEST['db'],
            $_REQUEST['table']
        );

        RecentFavoriteTable::getInstance('favorite')->removeIfInvalid(
            $_REQUEST['db'],
            $_REQUEST['table']
        );

        /** @var SqlController $controller */
        $controller = $containerBuilder->get(SqlController::class);
        $controller->index();
    }
}
