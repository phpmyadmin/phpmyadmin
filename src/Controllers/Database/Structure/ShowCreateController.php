<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;

use function __;

#[Route('/database/structure/show-create', ['POST'])]
final class ShowCreateController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if ($selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return $this->response->response();
        }

        $tables = $this->getShowCreateTables($selected);

        $showCreate = $this->template->render('database/structure/show_create', ['tables' => $tables]);

        $this->response->addJSON('message', $showCreate);

        return $this->response->response();
    }

    /**
     * @param string[] $selected Selected tables.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function getShowCreateTables(array $selected): array
    {
        $tables = ['tables' => [], 'views' => []];

        foreach ($selected as $table) {
            $object = $this->dbi->getTable(Current::$database, $table);

            $tables[$object->isView() ? 'views' : 'tables'][] = [
                'name' => Core::mimeDefaultFunction($table),
                'show_create' => Core::mimeDefaultFunction($object->showCreate()),
            ];
        }

        return $tables;
    }
}
