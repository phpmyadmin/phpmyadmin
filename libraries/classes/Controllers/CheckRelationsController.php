<?php
/**
 * Displays status of phpMyAdmin configuration storage
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * @package PhpMyAdmin\Controllers
 */
class CheckRelationsController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /**
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template that should be used
     * @param Relation          $relation Relation object
     */
    public function __construct($response, $dbi, Template $template, Relation $relation)
    {
        parent::__construct($response, $dbi, $template);
        $this->relation = $relation;
    }

    /**
     * @param array $params Request parameters
     *
     * @return string
     */
    public function index(array $params): string
    {
        global $db;

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

        return $this->relation->getRelationsParamDiagnostic($cfgRelation);
    }
}
