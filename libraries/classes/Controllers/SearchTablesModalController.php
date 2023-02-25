<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Util;

use function array_keys;
use function end;
use function explode;
use function html_entity_decode;

/**
 * Class used to display the Search Table Modal
 */
final class SearchTablesModalController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        /** @var string|null $database */
        $database = $request->getParsedBodyParam('db');
        /** @var string|null $table */
        $table = TableName::tryFromValue($request->getParsedBodyParam('table') ?? '');
        /** @var string|null $currentUrl */
        $currentUrl = $request->getParsedBodyParam('current_url');
        // If user has already selected a table, the current page will remain same upon selecting a different table.
        $url = null;
        if ($table !== null && $currentUrl !== null) {
            $parts = explode('=', explode('&', $currentUrl)[0]);
            $url = end($parts);
        }

        [$tables] = Util::getDbInfo($request, $database ?? '', true);
        $data = $this->template->render('modals/search_tables', [
            'tables' => array_keys($tables),
            'db' => $database,
            'url_query' => $url,
        ]);

        $this->response->addJSON(['data' => html_entity_decode($data), 'db' => $database]);
    }
}
