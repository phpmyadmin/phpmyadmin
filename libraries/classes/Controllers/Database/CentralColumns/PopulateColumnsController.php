<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\CentralColumns;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class PopulateColumnsController extends AbstractController
{
    /** @var CentralColumns */
    private $centralColumns;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        CentralColumns $centralColumns
    ) {
        parent::__construct($response, $template);
        $this->centralColumns = $centralColumns;
    }

    public function __invoke(): void
    {
        $columns = $this->centralColumns->getColumnsNotInCentralList($GLOBALS['db'], $_POST['selectedTable']);
        $this->render('database/central_columns/populate_columns', ['columns' => $columns]);
    }
}
