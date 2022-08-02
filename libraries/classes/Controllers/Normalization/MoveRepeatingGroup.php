<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class MoveRepeatingGroup extends AbstractController
{
    /** @var Normalization */
    private $normalization;

    public function __construct(ResponseRenderer $response, Template $template, Normalization $normalization)
    {
        parent::__construct($response, $template);
        $this->normalization = $normalization;
    }

    public function __invoke(ServerRequest $request): void
    {
        $repeatingColumns = $_POST['repeatingColumns'];
        $newTable = $_POST['newTable'];
        $newColumn = $_POST['newColumn'];
        $primary_columns = $_POST['primary_columns'];
        $res = $this->normalization->moveRepeatingGroup(
            $repeatingColumns,
            $primary_columns,
            $newTable,
            $newColumn,
            $GLOBALS['table'],
            $GLOBALS['db']
        );
        $this->response->addJSON($res);
    }
}
