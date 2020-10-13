<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Display selection for relational field values
 */
class BrowseForeignersController extends AbstractController
{
    /** @var BrowseForeigners */
    private $browseForeigners;

    /** @var Relation */
    private $relation;

    /**
     * @param Response         $response
     * @param BrowseForeigners $browseForeigners
     * @param Relation         $relation
     */
    public function __construct($response, Template $template, $browseForeigners, $relation)
    {
        parent::__construct($response, $template);
        $this->browseForeigners = $browseForeigners;
        $this->relation = $relation;
    }

    public function index(): void
    {
        $params = [
            'db' => $_POST['db'] ?? null,
            'table' => $_POST['table'] ?? null,
            'field' => $_POST['field'] ?? null,
            'fieldkey' => $_POST['fieldkey'] ?? null,
            'data' => $_POST['data'] ?? null,
            'foreign_showAll' => $_POST['foreign_showAll'] ?? null,
            'foreign_filter' => $_POST['foreign_filter'] ?? null,
        ];

        if (! isset($params['db'], $params['table'], $params['field'])) {
            return;
        }

        $this->response->getFooter()->setMinimal();
        $header = $this->response->getHeader();
        $header->disableMenuAndConsole();
        $header->setBodyId('body_browse_foreigners');

        $foreigners = $this->relation->getForeigners(
            $params['db'],
            $params['table']
        );
        $foreignLimit = $this->browseForeigners->getForeignLimit(
            $params['foreign_showAll']
        );
        $foreignData = $this->relation->getForeignData(
            $foreigners,
            $params['field'],
            true,
            $params['foreign_filter'] ?? '',
            $foreignLimit ?? null,
            true
        );

        $this->response->addHTML($this->browseForeigners->getHtmlForRelationalFieldSelection(
            $params['db'],
            $params['table'],
            $params['field'],
            $foreignData,
            $params['fieldkey'] ?? '',
            $params['data'] ?? ''
        ));
    }
}
