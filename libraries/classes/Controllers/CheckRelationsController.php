<?php
/**
 * Displays status of phpMyAdmin configuration storage
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response as ResponseRenderer;
use PhpMyAdmin\Template;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CheckRelationsController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /**
     * @param ResponseRenderer  $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template that should be used
     * @param Relation          $relation Relation object
     */
    public function __construct($response, $dbi, Template $template, Relation $relation)
    {
        parent::__construct($response, $dbi, $template);
        $this->relation = $relation;
    }

    public function index(Request $request, Response $response): Response
    {
        global $db;

        $params = [
            'create_pmadb' => $_POST['create_pmadb'] ?? null,
            'fixall_pmadb' => $_POST['fixall_pmadb'] ?? null,
            'fix_pmadb' => $_POST['fix_pmadb'] ?? null,
        ];

        // If request for creating the pmadb
        if (isset($params['create_pmadb']) && $this->relation->createPmaDatabase()) {
            $this->relation->fixPmaTables('phpmyadmin');
        }

        // If request for creating all PMA tables.
        if (isset($params['fixall_pmadb'])) {
            $this->relation->fixPmaTables($db);
        }

        $cfgRelation = $this->relation->getRelationsParam();
        // If request for creating missing PMA tables.
        if (isset($params['fix_pmadb'])) {
            $this->relation->fixPmaTables($cfgRelation['db']);
        }

        $this->response->addHTML($this->relation->getRelationsParamDiagnostic($cfgRelation));

        return $response;
    }
}
