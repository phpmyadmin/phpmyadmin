<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

/**
 * Handles database multi-table querying
 */
class MultiTableQueryController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->addScriptFiles(['database/multi_table_query.js', 'database/query_generator.js']);

        $queryInstance = new MultiTableQuery($this->dbi, $this->template, $GLOBALS['db']);

        $this->response->addHTML($queryInstance->getFormHtml());
    }
}
