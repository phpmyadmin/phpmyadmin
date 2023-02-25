<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use const SQL_DIR;

/**
 * Displays status of phpMyAdmin configuration storage
 */
class CheckRelationsController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Relation $relation)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $cfgStorageDbName = $this->relation->getConfigurationStorageDbName();

        $db = DatabaseName::tryFromValue($GLOBALS['db']);

        // If request for creating the pmadb
        if ($request->hasBodyParam('create_pmadb') && $this->relation->createPmaDatabase($cfgStorageDbName)) {
            $this->relation->fixPmaTables($cfgStorageDbName);
        }

        // If request for creating all PMA tables.
        if ($request->hasBodyParam('fixall_pmadb') && $db !== null) {
            $this->relation->fixPmaTables($db->getName());
        }

        // If request for creating missing PMA tables.
        if ($request->hasBodyParam('fix_pmadb')) {
            $relationParameters = $this->relation->getRelationParameters();
            $this->relation->fixPmaTables((string) $relationParameters->db);
        }

        // Do not use any previous $relationParameters value as it could have changed after a successful fixPmaTables()
        $relationParameters = $this->relation->getRelationParameters();

        $this->render('relation/check_relations', [
            'db' => $db?->getName() ?? '',
            'zero_conf' => $GLOBALS['cfg']['ZeroConf'],
            'relation_parameters' => $relationParameters->toArray(),
            'sql_dir' => SQL_DIR,
            'config_storage_database_name' => $cfgStorageDbName,
            'are_config_storage_tables_defined' => $this->relation->arePmadbTablesDefined(),
        ]);
    }
}
