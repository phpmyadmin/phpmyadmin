<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

/**
 * Display selection for relational field values
 */
#[Route('/browse-foreigners', ['GET', 'POST'])]
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
        $database = $request->getParsedBodyParamAsStringOrNull('db');
        $table = $request->getParsedBodyParamAsStringOrNull('table');
        $field = $request->getParsedBodyParamAsStringOrNull('field');
        $fieldKey = $request->getParsedBodyParamAsString('fieldkey', '');
        $data = $request->getParsedBodyParamAsString('data', '');
        $foreignShowAll = $request->getParsedBodyParamAsStringOrNull('foreign_showAll');
        $foreignFilter = $request->getParsedBodyParamAsString('foreign_filter', '');
        $rownumber = $request->getParsedBodyParamAsStringOrNull('rownumber');

        if (! isset($database, $table, $field)) {
            return $this->response->response();
        }

        $this->response->setMinimalFooter();
        $header = $this->response->getHeader();
        $header->disableMenuAndConsole();
        $header->setBodyId('body_browse_foreigners');

        $pos = (int) $request->getParsedBodyParamAsStringOrNull('pos');
        $foreignLimit = $this->browseForeigners->getForeignLimit($foreignShowAll, $pos);
        $foreignData = $this->relation->getForeignData(
            $this->relation->getForeigners($database, $table),
            $field,
            true,
            $foreignFilter,
            $foreignLimit,
            true,
        );

        $this->response->addHTML($this->browseForeigners->getHtmlForRelationalFieldSelection(
            $database,
            $table,
            $field,
            $foreignData,
            $fieldKey,
            $data,
            $pos,
            $foreignFilter,
            $rownumber,
        ));

        return $this->response->response();
    }
}
