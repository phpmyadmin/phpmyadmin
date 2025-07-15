<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;

use function __;
use function count;
use function is_array;

#[Route('/table/structure/primary', ['POST'])]
final class PrimaryController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly StructureController $structureController,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[]|null $selected */
        $selected = $request->getParsedBodyParam('selected_fld', $request->getParsedBodyParam('selected'));

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return $this->response->response();
        }

        $this->dbi->selectDb(Current::$database);
        $hasPrimary = $this->hasPrimaryKey();

        $deletionConfirmed = $request->getParsedBodyParamAsStringOrNull('mult_btn');

        if ($hasPrimary && $deletionConfirmed === null) {
            if (Current::$database === '') {
                return $this->response->missingParameterError('db');
            }

            if (Current::$table === '') {
                return $this->response->missingParameterError('table');
            }

            UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No databases selected.')));

                    return $this->response->response();
                }

                return $this->response->redirectToRoute(
                    '/',
                    ['reload' => true, 'message' => __('No databases selected.')],
                );
            }

            $tableName = TableName::tryFrom($request->getParam('table'));
            if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No table selected.')));

                    return $this->response->response();
                }

                return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);
            }

            $this->response->render('table/structure/primary', [
                'db' => Current::$database,
                'table' => Current::$table,
                'selected' => $selected,
            ]);

            return $this->response->response();
        }

        if ($deletionConfirmed === __('Yes') || ! $hasPrimary) {
            Current::$sqlQuery = 'ALTER TABLE ' . Util::backquote(Current::$table);
            if ($hasPrimary) {
                Current::$sqlQuery .= ' DROP PRIMARY KEY,';
            }

            Current::$sqlQuery .= ' ADD PRIMARY KEY(';

            $i = 1;
            $selectedCount = count($selected);
            foreach ($selected as $field) {
                Current::$sqlQuery .= Util::backquote($field);
                Current::$sqlQuery .= $i++ === $selectedCount ? ');' : ', ';
            }

            $this->dbi->selectDb(Current::$database);
            $result = $this->dbi->tryQuery(Current::$sqlQuery);

            if (! $result) {
                Current::$message = Message::error($this->dbi->getError());
            }
        }

        if (Current::$message === null) {
            Current::$message = Message::success();
        }

        return ($this->structureController)($request);
    }

    private function hasPrimaryKey(): bool
    {
        $result = $this->dbi->query('SHOW KEYS FROM ' . Util::backquote(Current::$table));

        foreach ($result as $row) {
            if ($row['Key_name'] === 'PRIMARY') {
                return true;
            }
        }

        return false;
    }
}
