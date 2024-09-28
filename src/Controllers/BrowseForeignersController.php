<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;

/**
 * Display selection for relational field values
 */
final class BrowseForeignersController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly BrowseForeigners $browseForeigners,
        private readonly Relation $relation,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string|null $database */
        $database = $request->getParsedBodyParam('db');
        /** @var string|null $table */
        $table = $request->getParsedBodyParam('table');
        /** @var string|null $field */
        $field = $request->getParsedBodyParam('field');
        /** @var string $fieldKey */
        $fieldKey = $request->getParsedBodyParam('fieldkey', '');
        /** @var string $data */
        $data = $request->getParsedBodyParam('data', '');
        /** @var string|null $foreignShowAll */
        $foreignShowAll = $request->getParsedBodyParam('foreign_showAll');
        /** @var string $foreignFilter */
        $foreignFilter = $request->getParsedBodyParam('foreign_filter', '');

        if (! isset($database, $table, $field)) {
            return $this->response->response();
        }

        $this->response->setMinimalFooter();
        $header = $this->response->getHeader();
        $header->disableMenuAndConsole();
        $header->setBodyId('body_browse_foreigners');

        $foreignLimit = $this->browseForeigners->getForeignLimit($foreignShowAll);
        $foreignData = $this->relation->getForeignData(
            $this->relation->getForeigners($database, $table),
            $field,
            true,
            $foreignFilter,
            $foreignLimit ?? '',
            true,
        );

        $this->response->addHTML($this->browseForeigners->getHtmlForRelationalFieldSelection(
            $database,
            $table,
            $field,
            $foreignData,
            $fieldKey,
            $data,
        ));

        return $this->response->response();
    }
}
