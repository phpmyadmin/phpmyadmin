<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class AddKeyController extends AbstractController
{
    /** @var SqlController */
    private $sqlController;

    /** @var StructureController */
    private $structureController;

    /**
     * @param ResponseRenderer $response
     * @param string           $db       Database name
     * @param string           $table    Table name
     */
    public function __construct(
        $response,
        Template $template,
        $db,
        $table,
        SqlController $sqlController,
        StructureController $structureController
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->sqlController = $sqlController;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        global $reload;

        $this->sqlController->index();

        $reload = true;

        ($this->structureController)();
    }
}
