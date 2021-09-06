<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\CentralColumns;

use PhpMyAdmin\Controllers\Database\AbstractController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class PopulateColumnsController extends AbstractController
{
    /** @var CentralColumns */
    private $centralColumns;

    /**
     * @param ResponseRenderer $response
     * @param string           $db             Database name
     * @param CentralColumns   $centralColumns
     */
    public function __construct($response, Template $template, $db, $centralColumns)
    {
        parent::__construct($response, $template, $db);
        $this->centralColumns = $centralColumns;
    }

    public function __invoke(): void
    {
        $columns = $this->centralColumns->getColumnsNotInCentralList($this->db, $_POST['selectedTable']);
        $this->render('database/central_columns/populate_columns', ['columns' => $columns]);
    }
}
