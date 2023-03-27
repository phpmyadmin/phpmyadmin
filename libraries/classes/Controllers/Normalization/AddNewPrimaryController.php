<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

final class AddNewPrimaryController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Normalization $normalization)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $numFields = 1;

        $db = DatabaseName::tryFromValue($GLOBALS['db']);
        $table = TableName::tryFromValue($GLOBALS['table']);
        $dbName = isset($db) ? $db->getName() : '';
        $tableName = isset($table) ? $table->getName() : '';

        $columnMeta = ['Field' => $tableName . '_id', 'Extra' => 'auto_increment'];
        $html = $this->normalization->getHtmlForCreateNewColumn($numFields, $dbName, $tableName, $columnMeta);
        $html .= Url::getHiddenInputs($dbName, $tableName);
        $this->response->addHTML($html);
    }
}
