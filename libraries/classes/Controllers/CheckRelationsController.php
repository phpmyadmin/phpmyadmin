<?php
/**
 * Displays status of phpMyAdmin configuration storage
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Relation;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

class CheckRelationsController extends AbstractController
{
    /** @var Relation */
    private $relation;

    public function __construct(ResponseRenderer $response, Template $template, Relation $relation)
    {
        parent::__construct($response, $template);
        $this->relation = $relation;
    }

    public function __invoke(ServerRequest $request): void
    {
        global $db;

        /** @var string|null $createPmaDb */
        $createPmaDb = $request->getParsedBodyParam('create_pmadb');
        /** @var string|null $fixAllPmaDb */
        $fixAllPmaDb = $request->getParsedBodyParam('fixall_pmadb');
        /** @var string|null $fixPmaDb */
        $fixPmaDb = $request->getParsedBodyParam('fix_pmadb');

        $cfgStorageDbName = $this->relation->getConfigurationStorageDbName();

        // If request for creating the pmadb
        if (isset($createPmaDb) && $this->relation->createPmaDatabase($cfgStorageDbName)) {
            $this->relation->fixPmaTables($cfgStorageDbName);
        }

        // If request for creating all PMA tables.
        if (isset($fixAllPmaDb)) {
            $this->relation->fixPmaTables($db);
        }

        // If request for creating missing PMA tables.
        if (isset($fixPmaDb)) {
            $relationParameters = $this->relation->getRelationParameters();
            $this->relation->fixPmaTables((string) $relationParameters->db);
        }

        // Do not use any previous $relationParameters value as it could have changed after a successful fixPmaTables()
        $relationParameters = $this->relation->getRelationParameters();
        $this->response->addHTML($this->relation->getRelationsParamDiagnostic($relationParameters));
    }
}
