<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class DatabaseController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private readonly DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->response->addJSON(['databases' => $this->dbi->getDatabaseList()]);
    }
}
