<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class AddKeyController extends AbstractController
{
    /** @var SqlController */
    private $sqlController;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        SqlController $sqlController,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->sqlController = $sqlController;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        global $reload;

        ($this->sqlController)();

        $reload = true;

        ($this->structureController)();
    }
}
