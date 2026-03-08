<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\SqlQueryForm;

/**
 * Server SQL executor
 */
#[Route('/server/sql', ['GET', 'POST'])]
final readonly class SqlController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private SqlQueryForm $sqlQueryForm,
        private DatabaseInterface $dbi,
        private PageSettings $pageSettings,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addScriptFiles(['makegrid.js', 'vendor/jquery/jquery.uitablefilter.js', 'sql.js']);

        $this->pageSettings->init('Sql');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->response->addHTML($this->sqlQueryForm->getHtml('', ''));

        return $this->response->response();
    }
}
