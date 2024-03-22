<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Replication\ReplicationInfo;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function __;
use function array_keys;
use function array_search;
use function count;
use function str_contains;
use function strtolower;

/**
 * Handles viewing and creating and deleting databases
 */
class DatabasesController extends AbstractController
{
    /** @var mixed[] array of database details */
    private array $databases = [];

    /** @var int number of databases */
    private int $databaseCount = 0;

    /** @var string sort by column */
    private string $sortBy = '';

    /** @var string sort order of databases */
    private string $sortOrder = '';

    /** @var bool whether to show database statistics */
    private bool $hasStatistics = false;

    private const SORT_BY_ALLOWED_LIST = [
        'SCHEMA_NAME',
        'DEFAULT_COLLATION_NAME',
        'SCHEMA_TABLES',
        'SCHEMA_TABLE_ROWS',
        'SCHEMA_DATA_LENGTH',
        'SCHEMA_INDEX_LENGTH',
        'SCHEMA_LENGTH',
        'SCHEMA_DATA_FREE',
    ];

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $this->hasStatistics = ! empty($request->getParam('statistics'));
        $position = (int) $request->getParam('pos');

        $sortBy = $request->getParam('sort_by', '');
        Assert::string($sortBy);
        $this->sortBy = self::SORT_BY_ALLOWED_LIST[array_search($sortBy, self::SORT_BY_ALLOWED_LIST, true)];

        $sortOrder = $request->getParam('sort_order', '');
        Assert::string($sortOrder);
        $this->sortOrder = strtolower($sortOrder) !== 'desc' ? 'asc' : 'desc';

        $this->addScriptFiles(['server/databases.js']);
        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $replicationInfo = new ReplicationInfo($this->dbi);
        $replicationInfo->load($request->getParsedBodyParam('primary_connection'));

        $primaryInfo = $replicationInfo->getPrimaryInfo();
        $replicaInfo = $replicationInfo->getReplicaInfo();

        /**
         * Gets the databases list
         */
        if (Current::$server > 0) {
            $this->databases = $this->dbi->getDatabasesFull(
                null,
                $this->hasStatistics,
                ConnectionType::User,
                $this->sortBy,
                $this->sortOrder,
                $position,
                true,
            );
            $this->databaseCount = count($this->dbi->getDatabaseList());
        }

        $urlParams = [
            'statistics' => $this->hasStatistics,
            'pos' => $position,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ];

        $databases = $this->getDatabases($primaryInfo, $replicaInfo);

        $charsetsList = [];
        $config = Config::getInstance();
        if ($config->settings['ShowCreateDb'] && $userPrivileges->isCreateDatabase) {
            $charsets = Charsets::getCharsets($this->dbi, $config->selectedServer['DisableIS']);
            $collations = Charsets::getCollations($this->dbi, $config->selectedServer['DisableIS']);
            $serverCollation = $this->dbi->getServerCollation();
            foreach ($charsets as $charset) {
                $collationsList = [];
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
            'is_create_database_shown' => $config->settings['ShowCreateDb'],
            'has_create_database_privileges' => $userPrivileges->isCreateDatabase,
            'has_statistics' => $this->hasStatistics,
            'database_to_create' => $userPrivileges->databaseToCreate,
            'databases' => $databases['databases'],
            'total_statistics' => $databases['total_statistics'],
            'header_statistics' => $headerStatistics,
            'charsets' => $charsetsList,
            'database_count' => $this->databaseCount,
            'pos' => $position,
            'url_params' => $urlParams,
            'max_db_list' => $config->settings['MaxDbList'],
            'has_primary_replication' => $primaryInfo['status'],
            'has_replica_replication' => $replicaInfo['status'],
            'is_drop_allowed' => $this->dbi->isSuperUser() || $config->settings['AllowUserDropDatabase'],
            'text_dir' => LanguageManager::$textDir,
        ]);
    }

    /**
     * @param mixed[] $primaryInfo
     * @param mixed[] $replicaInfo
     *
     * @return array{databases:mixed[], total_statistics:mixed[]}
     */
    private function getDatabases(array $primaryInfo, array $replicaInfo): array
    {
        $databases = [];
        $totalStatistics = $this->getStatisticsColumns();
        foreach ($this->databases as $database) {
            $replication = [
                'primary' => ['status' => $primaryInfo['status']],
                'replica' => ['status' => $replicaInfo['status']],
            ];

            if ($primaryInfo['status']) {
                $key = array_search($database['SCHEMA_NAME'], $primaryInfo['Ignore_DB']);
                $replication['primary']['is_replicated'] = false;

                if ((string) $key === '') {
                    $key = array_search($database['SCHEMA_NAME'], $primaryInfo['Do_DB']);

                    if ((string) $key !== '' || count($primaryInfo['Do_DB']) === 0) {
                        $replication['primary']['is_replicated'] = true;
                    }
                }
            }

            if ($replicaInfo['status']) {
                $key = array_search($database['SCHEMA_NAME'], $replicaInfo['Ignore_DB']);
                $replication['replica']['is_replicated'] = false;

                if ((string) $key === '') {
                    $key = array_search($database['SCHEMA_NAME'], $replicaInfo['Do_DB']);

                    if ((string) $key !== '' || count($replicaInfo['Do_DB']) === 0) {
                        $replication['replica']['is_replicated'] = true;
                    }
                }
            }

            $statistics = $this->getStatisticsColumns();
            if ($this->hasStatistics) {
                foreach (array_keys($statistics) as $key) {
                    $statistics[$key]['raw'] = (int) ($database[$key] ?? 0);
                    $totalStatistics[$key]['raw'] += (int) ($database[$key] ?? 0);
                }
            }

            $config = Config::getInstance();
            $url = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
            $url .= Url::getCommonRaw(
                ['db' => $database['SCHEMA_NAME']],
                ! str_contains($url, '?') ? '?' : '&',
            );
            $databases[$database['SCHEMA_NAME']] = [
                'name' => $database['SCHEMA_NAME'],
                'collation' => [],
                'statistics' => $statistics,
                'replication' => $replication,
                'is_system_schema' => Utilities::isSystemSchema($database['SCHEMA_NAME'], true),
                'is_pmadb' => $database['SCHEMA_NAME'] === ($config->selectedServer['pmadb'] ?? ''),
                'url' => $url,
            ];
            $collation = Charsets::findCollationByName(
                $this->dbi,
                $config->selectedServer['DisableIS'],
                $database['DEFAULT_COLLATION_NAME'],
            );
            if ($collation === null) {
                continue;
            }

            $databases[$database['SCHEMA_NAME']]['collation'] = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        return ['databases' => $databases, 'total_statistics' => $totalStatistics];
    }

    /**
     * Prepares the statistics columns
     *
     * @return array<string, array{title: string, format: 'number'|'byte', raw: int}>
     */
    private function getStatisticsColumns(): array
    {
        return [
            'SCHEMA_TABLES' => ['title' => __('Tables'), 'format' => 'number', 'raw' => 0],
            'SCHEMA_TABLE_ROWS' => ['title' => __('Rows'), 'format' => 'number', 'raw' => 0],
            'SCHEMA_DATA_LENGTH' => ['title' => __('Data'), 'format' => 'byte', 'raw' => 0],
            'SCHEMA_INDEX_LENGTH' => ['title' => __('Indexes'), 'format' => 'byte', 'raw' => 0],
            'SCHEMA_LENGTH' => ['title' => __('Total'), 'format' => 'byte', 'raw' => 0],
            'SCHEMA_DATA_FREE' => ['title' => __('Overhead'), 'format' => 'byte', 'raw' => 0],
        ];
    }
}
