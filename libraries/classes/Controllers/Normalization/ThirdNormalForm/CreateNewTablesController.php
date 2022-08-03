<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization\ThirdNormalForm;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function json_decode;

final class CreateNewTablesController extends AbstractController
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
        $newtables = json_decode($_POST['newTables'], true);
        $res = $this->normalization->createNewTablesFor3NF($newtables, $GLOBALS['db']);
        $this->response->addJSON($res);
    }
}
