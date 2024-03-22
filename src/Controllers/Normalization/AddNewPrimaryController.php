<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;

final class AddNewPrimaryController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Normalization $normalization,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $numFields = 1;

        $db = DatabaseName::tryFrom(Current::$database);
        $table = TableName::tryFrom(Current::$table);
        $dbName = isset($db) ? $db->getName() : '';
        $tableName = isset($table) ? $table->getName() : '';

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
    }
}
