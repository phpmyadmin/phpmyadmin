<?php
/**
 * Holds the PhpMyAdmin\Controllers\BrowseForeignersController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Display selection for relational field values
 *
 * @package PhpMyAdmin\Controllers
 */
class BrowseForeignersController extends AbstractController
{
    /**
     * @var BrowseForeigners
     */
    private $browseForeigners;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * @param Response          $response         Response instance
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

    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function index(array $params): string
    {
        if (! isset($params['db'], $params['table'], $params['field'])) {
            return '';
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

        return $this->browseForeigners->getHtmlForRelationalFieldSelection(
            $params['db'],
            $params['table'],
            $params['field'],
            $foreignData,
            $params['fieldkey'] ?? '',
            $params['data'] ?? ''
        );
    }
}
