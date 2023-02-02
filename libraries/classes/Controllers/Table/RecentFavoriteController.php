<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\RecentFavoriteTable;

use function is_string;

/**
 * Browse recent and favorite tables chosen from navigation.
 */
class RecentFavoriteController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $db = isset($_REQUEST['db']) && is_string($_REQUEST['db']) ? $_REQUEST['db'] : '';
        $table = isset($_REQUEST['table']) && is_string($_REQUEST['table']) ? $_REQUEST['table'] : '';

        RecentFavoriteTable::getInstance('recent')->removeIfInvalid($db, $table);
        RecentFavoriteTable::getInstance('favorite')->removeIfInvalid($db, $table);

        /** @var SqlController $controller */
        $controller = Core::getContainerBuilder()->get(SqlController::class);
        $controller($request);
    }
}
