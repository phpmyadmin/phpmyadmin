<?php
/**
 * Holds the PhpMyAdmin\Controllers\BrowseForeignersController
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response as ResponseRenderer;
use PhpMyAdmin\Template;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
     * @param ResponseRenderer  $response         Response instance
     * @param DatabaseInterface $dbi              DatabaseInterface instance
     * @param Template          $template         Template object
     * @param BrowseForeigners  $browseForeigners BrowseForeigners instance
     * @param Relation          $relation         Relation instance
     */
    public function __construct($response, $dbi, Template $template, $browseForeigners, $relation)
    {
        parent::__construct($response, $dbi, $template);
        $this->browseForeigners = $browseForeigners;
        $this->relation = $relation;
    }

    public function index(Request $request, Response $response): Response
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
            return $response;
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

        return $response;
    }
}
