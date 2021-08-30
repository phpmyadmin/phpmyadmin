<?php
/**
 * Displays status of phpMyAdmin configuration storage
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

class CheckRelationsController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /**
     * @param Response $response
     */
    public function __construct($response, Template $template, Relation $relation)
    {
        parent::__construct($response, $template);
        $this->relation = $relation;
    }

    public function index(): void
    {
        global $db;

        $params = [
            'create_pmadb' => $_POST['create_pmadb'] ?? null,
            'fixall_pmadb' => $_POST['fixall_pmadb'] ?? null,
            'fix_pmadb' => $_POST['fix_pmadb'] ?? null,
        ];

        $cfgStorageDbName = $this->relation->getConfigurationStorageDbName();

        // If request for creating the pmadb
        if (isset($params['create_pmadb']) && $this->relation->createPmaDatabase($cfgStorageDbName)) {
            $this->relation->fixPmaTables($cfgStorageDbName);
        }

        // If request for creating all PMA tables.
        if (isset($params['fixall_pmadb'])) {
            $this->relation->fixPmaTables($db);
        }

        // If request for creating missing PMA tables.
        if (isset($params['fix_pmadb'])) {
            $cfgRelation = $this->relation->getRelationsParam();
            $this->relation->fixPmaTables($cfgRelation['db']);
        }

        // Do not use any previous $cfgRelation value as it could have changed after a successfull fixPmaTables()
        $cfgRelation = $this->relation->getRelationsParam();
        $this->response->addHTML($this->relation->getRelationsParamDiagnostic($cfgRelation));
    }
}
