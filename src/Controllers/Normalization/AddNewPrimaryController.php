<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;

#[Route('/normalization/add-new-primary', ['POST'])]
final class AddNewPrimaryController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Normalization $normalization,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $numFields = 1;

        $db = DatabaseName::tryFrom(Current::$database);
        $table = TableName::tryFrom(Current::$table);
        $dbName = $db?->getName() ?? '';
        $tableName = $table?->getName() ?? '';

        $columnMeta = ['Field' => $tableName . '_id', 'Extra' => 'auto_increment'];
        $html = $this->normalization->getHtmlForCreateNewColumn(
            $userPrivileges,
            $numFields,
            $dbName,
            $tableName,
            $columnMeta,
        );
        $html .= Url::getHiddenInputs($dbName, $tableName);
        $this->response->addHTML($html);

        return $this->response->response();
    }
}
