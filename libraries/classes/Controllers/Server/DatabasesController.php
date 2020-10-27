<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function array_key_exists;
use function array_keys;
use function array_search;
use function count;
use function explode;
use function in_array;
use function mb_strlen;
use function mb_strtolower;
use function strlen;
use function strpos;

/**
 * Handles viewing and creating and deleting databases
 */
class DatabasesController extends AbstractController
{
    /** @var array array of database details */
    private $databases = [];

    /** @var int number of databases */
    private $databaseCount = 0;

    /** @var string sort by column */
    private $sortBy;

    /** @var string sort order of databases */
    private $sortOrder;

    /** @var bool whether to show database statistics */
    private $hasStatistics;

    /** @var int position in list navigation */
    private $position;

    /** @var Transformations */
    private $transformations;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param DatabaseInterface $dbi
     */
    public function __construct(
        $response,
        Template $template,
        Transformations $transformations,
        RelationCleanup $relationCleanup,
        $dbi
    ) {
        parent::__construct($response, $template);
        $this->transformations = $transformations;
        $this->relationCleanup = $relationCleanup;
        $this->dbi = $dbi;

        $checkUserPrivileges = new CheckUserPrivileges($dbi);
        $checkUserPrivileges->getPrivileges();
    }

    public function index(): void
    {
        global $cfg, $server, $dblist, $is_create_db_priv;
        global $db_to_create, $text_dir, $PMA_Theme, $err_url;

        $params = [
            'statistics' => $_REQUEST['statistics'] ?? null,
            'pos' => $_REQUEST['pos'] ?? null,
            'sort_by' => $_REQUEST['sort_by'] ?? null,
            'sort_order' => $_REQUEST['sort_order'] ?? null,
        ];

        $this->addScriptFiles(['server/databases.js']);
        $err_url = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $replicationInfo = new ReplicationInfo($this->dbi);
        $replicationInfo->load($_POST['master_connection'] ?? null);

        $primaryInfo = $replicationInfo->getPrimaryInfo();
        $replicaInfo = $replicationInfo->getReplicaInfo();

        $this->setSortDetails($params['sort_by'], $params['sort_order']);
        $this->hasStatistics = ! empty($params['statistics']);
        $this->position = ! empty($params['pos']) ? (int) $params['pos'] : 0;

        /**
         * Gets the databases list
         */
        if ($server > 0) {
            $this->databases = $this->dbi->getDatabasesFull(
                null,
                $this->hasStatistics,
                DatabaseInterface::CONNECT_USER,
                $this->sortBy,
                $this->sortOrder,
                $this->position,
                true
            );
            $this->databaseCount = count($dblist->databases);
        }

        $urlParams = [
            'statistics' => $this->hasStatistics,
            'pos' => $this->position,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ];

        $databases = $this->getDatabases($primaryInfo, $replicaInfo);

        $charsetsList = [];
        if ($cfg['ShowCreateDb'] && $is_create_db_priv) {
            $charsets = Charsets::getCharsets($this->dbi, $cfg['Server']['DisableIS']);
            $collations = Charsets::getCollations($this->dbi, $cfg['Server']['DisableIS']);
            $serverCollation = $this->dbi->getServerCollation();
            /** @var Charset $charset */
            foreach ($charsets as $charset) {
                $collationsList = [];
                /** @var Collation $collation */
                foreach ($collations[$charset->getName()] as $collation) {
                    $collationsList[] = [
                        'name' => $collation->getName(),
                        'description' => $collation->getDescription(),
                        'is_selected' => $serverCollation === $collation->getName(),
                    ];
                }
                $charsetsList[] = [
                    'name' => $charset->getName(),
                    'description' => $charset->getDescription(),
                    'collations' => $collationsList,
                ];
            }
        }

        $headerStatistics = $this->getStatisticsColumns();

        $this->render('server/databases/index', [
            'is_create_database_shown' => $cfg['ShowCreateDb'],
            'has_create_database_privileges' => $is_create_db_priv,
            'has_statistics' => $this->hasStatistics,
            'database_to_create' => $db_to_create,
            'databases' => $databases['databases'],
            'total_statistics' => $databases['total_statistics'],
            'header_statistics' => $headerStatistics,
            'charsets' => $charsetsList,
            'database_count' => $this->databaseCount,
            'pos' => $this->position,
            'url_params' => $urlParams,
            'max_db_list' => $cfg['MaxDbList'],
            'has_master_replication' => $primaryInfo['status'],
            'has_slave_replication' => $replicaInfo['status'],
            'is_drop_allowed' => $this->dbi->isSuperUser() || $cfg['AllowUserDropDatabase'],
            'theme_image_path' => $PMA_Theme->getImgPath(),
            'text_dir' => $text_dir,
        ]);
    }

    public function create(): void
    {
        global $cfg, $db;

        $params = [
            'new_db' => $_POST['new_db'] ?? null,
            'db_collation' => $_POST['db_collation'] ?? null,
        ];

        if (! isset($params['new_db']) || mb_strlen($params['new_db']) === 0 || ! $this->response->isAjax()) {
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        // lower_case_table_names=1 `DB` becomes `db`
        if ($this->dbi->getLowerCaseNames() === '1') {
            $params['new_db'] = mb_strtolower(
                $params['new_db']
            );
        }

        /**
         * Builds and executes the db creation sql query
         */
        $sqlQuery = 'CREATE DATABASE ' . Util::backquote($params['new_db']);
        if (! empty($params['db_collation'])) {
            [$databaseCharset] = explode('_', $params['db_collation']);
            $charsets = Charsets::getCharsets(
                $this->dbi,
                $cfg['Server']['DisableIS']
            );
            $collations = Charsets::getCollations(
                $this->dbi,
                $cfg['Server']['DisableIS']
            );
            if (array_key_exists($databaseCharset, $charsets)
                && array_key_exists($params['db_collation'], $collations[$databaseCharset])
            ) {
                $sqlQuery .= ' DEFAULT'
                    . Util::getCharsetQueryPart($params['db_collation']);
            }
        }
        $sqlQuery .= ';';

        $result = $this->dbi->tryQuery($sqlQuery);

        if (! $result) {
            // avoid displaying the not-created db name in header or navi panel
            $db = '';

            $message = Message::rawError((string) $this->dbi->getError());
            $json = ['message' => $message];

            $this->response->setRequestStatus(false);
        } else {
            $db = $params['new_db'];

            $message = Message::success(__('Database %1$s has been created.'));
            $message->addParam($params['new_db']);

            $scriptName = Util::getScriptNameForOption(
                $cfg['DefaultTabDatabase'],
                'database'
            );

            $json = [
                'message' => $message,
                'sql_query' => Generator::getMessage('', $sqlQuery, 'success'),
                'url' => $scriptName . Url::getCommon(
                    ['db' => $params['new_db']],
                    strpos($scriptName, '?') === false ? '?' : '&'
                ),
            ];
        }

        $this->response->addJSON($json);
    }

    /**
     * Handles dropping multiple databases
     */
    public function destroy(): void
    {
        global $selected, $err_url, $cfg, $dblist, $reload;

        $params = [
            'drop_selected_dbs' => $_POST['drop_selected_dbs'] ?? null,
            'selected_dbs' => $_POST['selected_dbs'] ?? null,
        ];
        /** @var Message|int $message */
        $message = -1;

        if (! isset($params['drop_selected_dbs'])
            || ! $this->response->isAjax()
            || (! $this->dbi->isSuperUser() && ! $cfg['AllowUserDropDatabase'])
        ) {
            $message = Message::error();
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return;
        }

        if (! isset($params['selected_dbs'])) {
            $message = Message::error(__('No databases selected.'));
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return;
        }

        $err_url = Url::getFromRoute('/server/databases');
        $selected = $_POST['selected_dbs'];
        $rebuildDatabaseList = false;
        $sqlQuery = '';
        $numberOfDatabases = count($selected);

        for ($i = 0; $i < $numberOfDatabases; $i++) {
            $this->relationCleanup->database($selected[$i]);
            $aQuery = 'DROP DATABASE ' . Util::backquote($selected[$i]);
            $reload = true;
            $rebuildDatabaseList = true;

            $sqlQuery .= $aQuery . ';' . "\n";
            $this->dbi->query($aQuery);
            $this->transformations->clear($selected[$i]);
        }

        if ($rebuildDatabaseList) {
            $dblist->databases->build();
        }

        if ($message === -1) { // no error message
            $message = Message::success(
                _ngettext(
                    '%1$d database has been dropped successfully.',
                    '%1$d databases have been dropped successfully.',
                    $numberOfDatabases
                )
            );
            $message->addParam($numberOfDatabases);
        }

        $json = [];
        if ($message instanceof Message) {
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
        }

        $this->response->addJSON($json);
    }

    /**
     * Extracts parameters sort order and sort by
     *
     * @param string|null $sortBy    sort by
     * @param string|null $sortOrder sort order
     */
    private function setSortDetails(?string $sortBy, ?string $sortOrder): void
    {
        if (empty($sortBy)) {
            $this->sortBy = 'SCHEMA_NAME';
        } else {
            $sortByAllowList = [
                'SCHEMA_NAME',
                'DEFAULT_COLLATION_NAME',
                'SCHEMA_TABLES',
                'SCHEMA_TABLE_ROWS',
                'SCHEMA_DATA_LENGTH',
                'SCHEMA_INDEX_LENGTH',
                'SCHEMA_LENGTH',
                'SCHEMA_DATA_FREE',
            ];
            $this->sortBy = 'SCHEMA_NAME';
            if (in_array($sortBy, $sortByAllowList)) {
                $this->sortBy = $sortBy;
            }
        }

        $this->sortOrder = 'asc';
        if (! isset($sortOrder)
            || mb_strtolower($sortOrder) !== 'desc'
        ) {
            return;
        }

        $this->sortOrder = 'desc';
    }

    /**
     * @param array $primaryInfo
     * @param array $replicaInfo
     *
     * @return array
     */
    private function getDatabases($primaryInfo, $replicaInfo): array
    {
        global $cfg;

        $databases = [];
        $totalStatistics = $this->getStatisticsColumns();
        foreach ($this->databases as $database) {
            $replication = [
                'master' => ['status' => $primaryInfo['status']],
                'slave' => ['status' => $replicaInfo['status']],
            ];

            if ($primaryInfo['status']) {
                $key = array_search($database['SCHEMA_NAME'], $primaryInfo['Ignore_DB']);
                $replication['master']['is_replicated'] = false;

                if (strlen((string) $key) === 0) {
                    $key = array_search($database['SCHEMA_NAME'], $primaryInfo['Do_DB']);

                    if (strlen((string) $key) > 0 || count($primaryInfo['Do_DB']) === 0) {
                        $replication['master']['is_replicated'] = true;
                    }
                }
            }

            if ($replicaInfo['status']) {
                $key = array_search($database['SCHEMA_NAME'], $replicaInfo['Ignore_DB']);
                $replication['slave']['is_replicated'] = false;

                if (strlen((string) $key) === 0) {
                    $key = array_search($database['SCHEMA_NAME'], $replicaInfo['Do_DB']);

                    if (strlen((string) $key) > 0 || count($replicaInfo['Do_DB']) === 0) {
                        $replication['slave']['is_replicated'] = true;
                    }
                }
            }

            $statistics = $this->getStatisticsColumns();
            if ($this->hasStatistics) {
                foreach (array_keys($statistics) as $key) {
                    $statistics[$key]['raw'] = $database[$key] ?? null;
                    $totalStatistics[$key]['raw'] += (int) $database[$key] ?? 0;
                }
            }

            $url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
            $url .= Url::getCommonRaw(
                ['db' => $database['SCHEMA_NAME']],
                strpos($url, '?') === false ? '?' : '&'
            );
            $databases[$database['SCHEMA_NAME']] = [
                'name' => $database['SCHEMA_NAME'],
                'collation' => [],
                'statistics' => $statistics,
                'replication' => $replication,
                'is_system_schema' => Utilities::isSystemSchema(
                    $database['SCHEMA_NAME'],
                    true
                ),
                'is_pmadb' => $database['SCHEMA_NAME'] === ($cfg['Server']['pmadb'] ?? ''),
                'url' => $url,
            ];
            $collation = Charsets::findCollationByName(
                $this->dbi,
                $cfg['Server']['DisableIS'],
                $database['DEFAULT_COLLATION_NAME']
            );
            if ($collation === null) {
                continue;
            }

            $databases[$database['SCHEMA_NAME']]['collation'] = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        return [
            'databases' => $databases,
            'total_statistics' => $totalStatistics,
        ];
    }

    /**
     * Prepares the statistics columns
     *
     * @return array
     */
    private function getStatisticsColumns(): array
    {
        return [
            'SCHEMA_TABLES' => [
                'title' => __('Tables'),
                'format' => 'number',
                'raw' => 0,
            ],
            'SCHEMA_TABLE_ROWS' => [
                'title' => __('Rows'),
                'format' => 'number',
                'raw' => 0,
            ],
            'SCHEMA_DATA_LENGTH' => [
                'title' => __('Data'),
                'format' => 'byte',
                'raw' => 0,
            ],
            'SCHEMA_INDEX_LENGTH' => [
                'title' => __('Indexes'),
                'format' => 'byte',
                'raw' => 0,
            ],
            'SCHEMA_LENGTH' => [
                'title' => __('Total'),
                'format' => 'byte',
                'raw' => 0,
            ],
            'SCHEMA_DATA_FREE' => [
                'title' => __('Overhead'),
                'format' => 'byte',
                'raw' => 0,
            ],
        ];
    }
}
