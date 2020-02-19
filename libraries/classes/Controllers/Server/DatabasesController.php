<?php
/**
 * Holds the PhpMyAdmin\Controllers\Server\DatabasesController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Handles viewing and creating and deleting databases
 *
 * @package PhpMyAdmin\Controllers
 */
class DatabasesController extends AbstractController
{
    /**
     * @var array array of database details
     */
    private $databases = [];

    /**
     * @var int number of databases
     */
    private $databaseCount = 0;

    /**
     * @var string sort by column
     */
    private $sortBy;

    /**
     * @var string sort order of databases
     */
    private $sortOrder;

    /**
     * @var boolean whether to show database statistics
     */
    private $hasStatistics;

    /**
     * @var int position in list navigation
     */
    private $position;

    /**
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template that should be used (if provided, default one otherwise)
     */
    public function __construct($response, $dbi, Template $template)
    {
        parent::__construct($response, $dbi, $template);

        $checkUserPrivileges = new CheckUserPrivileges($dbi);
        $checkUserPrivileges->getPrivileges();
    }

    /**
     * Index action
     *
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function index(array $params): string
    {
        global $cfg, $server, $dblist, $is_create_db_priv;
        global $replication_info, $db_to_create, $pmaThemeImage, $text_dir;

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('server/databases.js');

        Common::server();
        ReplicationInfo::load();

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

        $databases = $this->getDatabases($replication_types ?? []);

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

        return $this->template->render('server/databases/index', [
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
            'has_master_replication' => $replication_info['master']['status'],
            'has_slave_replication' => $replication_info['slave']['status'],
            'is_drop_allowed' => $this->dbi->isSuperuser() || $cfg['AllowUserDropDatabase'],
            'pma_theme_image' => $pmaThemeImage,
            'text_dir' => $text_dir,
        ]);
    }

    /**
     * Handles creating a new database
     *
     * @param array $params Request parameters
     *
     * @return array JSON
     */
    public function create(array $params): array
    {
        global $cfg, $db;

        if (! isset($params['new_db']) || mb_strlen($params['new_db']) === 0 || ! $this->response->isAjax()) {
            return ['message' => Message::error()];
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

            $message = Message::rawError($this->dbi->getError());
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
                'sql_query' => Generator::getMessage(null, $sqlQuery, 'success'),
                'url_query' => $scriptName . Url::getCommon(
                    ['db' => $params['new_db']],
                    strpos($scriptName, '?') === false ? '?' : '&'
                ),
            ];
        }

        return $json;
    }

    /**
     * Handles dropping multiple databases
     *
     * @param array $params Request parameters
     *
     * @return array JSON
     */
    public function destroy(array $params): array
    {
        global $submit_mult, $mult_btn, $selected, $err_url, $cfg;

        if (! isset($params['drop_selected_dbs'])
            || ! $this->response->isAjax()
            || (! $this->dbi->isSuperuser() && ! $cfg['AllowUserDropDatabase'])
        ) {
            $message = Message::error();
        } elseif (! isset($params['selected_dbs'])) {
            $message = Message::error(__('No databases selected.'));
        } else {
            // for mult_submits.inc.php
            $action = Url::getFromRoute('/server/databases');
            $err_url = $action;

            $submit_mult = 'drop_db';
            $mult_btn = __('Yes');

            include ROOT_PATH . 'libraries/mult_submits.inc.php';

            if (empty($message)) { // no error message
                $numberOfDatabases = count($selected);
                $message = Message::success(
                    _ngettext(
                        '%1$d database has been dropped successfully.',
                        '%1$d databases have been dropped successfully.',
                        $numberOfDatabases
                    )
                );
                $message->addParam($numberOfDatabases);
            }
        }

        $json = [];
        if ($message instanceof Message) {
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
        }

        return $json;
    }

    /**
     * Extracts parameters sort order and sort by
     *
     * @param string|null $sortBy    sort by
     * @param string|null $sortOrder sort order
     *
     * @return void
     */
    private function setSortDetails(?string $sortBy, ?string $sortOrder): void
    {
        if (empty($sortBy)) {
            $this->sortBy = 'SCHEMA_NAME';
        } else {
            $sortByWhitelist = [
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
            if (in_array($sortBy, $sortByWhitelist)) {
                $this->sortBy = $sortBy;
            }
        }

        $this->sortOrder = 'asc';
        if (isset($sortOrder)
            && mb_strtolower($sortOrder) === 'desc'
        ) {
            $this->sortOrder = 'desc';
        }
    }

    /**
     * Returns database list
     *
     * @param array $replicationTypes replication types
     *
     * @return array
     */
    private function getDatabases(array $replicationTypes): array
    {
        global $cfg, $replication_info;

        $databases = [];
        $totalStatistics = $this->getStatisticsColumns();
        foreach ($this->databases as $database) {
            $replication = [
                'master' => [
                    'status' => $replication_info['master']['status'],
                ],
                'slave' => [
                    'status' => $replication_info['slave']['status'],
                ],
            ];
            foreach ($replicationTypes as $type) {
                if ($replication_info[$type]['status']) {
                    $key = array_search(
                        $database['SCHEMA_NAME'],
                        $replication_info[$type]['Ignore_DB']
                    );
                    if (strlen((string) $key) > 0) {
                        $replication[$type]['is_replicated'] = false;
                    } else {
                        $key = array_search(
                            $database['SCHEMA_NAME'],
                            $replication_info[$type]['Do_DB']
                        );

                        if (strlen((string) $key) > 0
                            || count($replication_info[$type]['Do_DB']) === 0
                        ) {
                            // if ($key != null) did not work for index "0"
                            $replication[$type]['is_replicated'] = true;
                        }
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
                'is_system_schema' => $this->dbi->isSystemSchema(
                    $database['SCHEMA_NAME'],
                    true
                ),
                'url' => $url,
            ];
            $collation = Charsets::findCollationByName(
                $this->dbi,
                $cfg['Server']['DisableIS'],
                $database['DEFAULT_COLLATION_NAME']
            );
            if ($collation !== null) {
                $databases[$database['SCHEMA_NAME']]['collation'] = [
                    'name' => $collation->getName(),
                    'description' => $collation->getDescription(),
                ];
            }
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
