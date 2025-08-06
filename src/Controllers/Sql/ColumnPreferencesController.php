<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\UiProperty;

use function array_map;
use function explode;
use function intval;
use function is_string;

#[Route('/sql/set-column-preferences', ['POST'])]
final class ColumnPreferencesController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly DatabaseInterface $dbi)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $tableObject = $this->dbi->getTable(Current::$database, Current::$table);
        $status = false;

        $tableCreateTime = $request->getParsedBodyParamAsStringOrNull('table_create_time');

        // set column order
        $colorder = $request->getParsedBodyParam('col_order');
        if (is_string($colorder)) {
            $propertyValue = array_map(intval(...), explode(',', $colorder));
            $status = $tableObject->setUiProp(UiProperty::ColumnOrder, $propertyValue, $tableCreateTime);
        }

        // set column visibility
        $colvisib = $request->getParsedBodyParam('col_visib');
        if ($status === true && is_string($colvisib)) {
            $propertyValue = array_map(intval(...), explode(',', $colvisib));
            $status = $tableObject->setUiProp(UiProperty::ColumnVisibility, $propertyValue, $tableCreateTime);
        }

        if ($status instanceof Message) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $status->getString());

            return $this->response->response();
        }

        $this->response->setRequestStatus($status);

        return $this->response->response();
    }
}
