<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\RecentFavoriteTable;

use function __;

/**
 * Browse recent and favorite tables chosen from navigation.
 */
final class RecentFavoriteController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        try {
            $db = DatabaseName::from($request->getParam('db'));
            $table = TableName::from($request->getParam('table'));
        } catch (InvalidIdentifier) {
            $this->redirect('/', ['message' => __('Invalid database or table name.')]);

            return;
        }

        RecentFavoriteTable::getInstance('recent')->removeIfInvalid($db->getName(), $table->getName());
        RecentFavoriteTable::getInstance('favorite')->removeIfInvalid($db->getName(), $table->getName());

        $this->redirect('/sql', ['db' => $db->getName(), 'table' => $table->getName()]);
    }
}
