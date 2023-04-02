<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Engines\Bdb;
use PhpMyAdmin\Engines\Berkeleydb;
use PhpMyAdmin\Engines\Binlog;
use PhpMyAdmin\Engines\Innobase;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Engines\Memory;
use PhpMyAdmin\Engines\Merge;
use PhpMyAdmin\Engines\MrgMyisam;
use PhpMyAdmin\Engines\Myisam;
use PhpMyAdmin\Engines\Ndbcluster;
use PhpMyAdmin\Engines\Pbxt;
use PhpMyAdmin\Engines\PerformanceSchema;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Utils\SessionCache;

use function __;
use function array_key_exists;
use function array_keys;
use function explode;
use function htmlspecialchars;
use function in_array;
use function json_decode;
use function mb_stripos;
use function mb_strtolower;
use function sprintf;
use function strlen;
use function strncmp;

/**
 * Library for extracting information about the available storage engines
 */
class StorageEngine
{
    protected const SUPPORT_NO = 0;
    protected const SUPPORT_DISABLED = 1;
    protected const SUPPORT_YES = 2;
    protected const SUPPORT_DEFAULT = 3;

    protected const DETAILS_TYPE_PLAINTEXT = 0;
    protected const DETAILS_TYPE_SIZE = 1;
    protected const DETAILS_TYPE_NUMERIC = 2; // Has no effect yet...
    protected const DETAILS_TYPE_BOOLEAN = 3; // 'ON' or 'OFF'

    /** @var string engine name */
    public string $engine = 'dummy';

    /** @var string engine title/description */
    public string $title = 'PMA Dummy Engine Class';

    /** @var string engine lang description */
    public string $comment = 'If you read this text inside phpMyAdmin, something went wrong...';

    /**
     * Engine supported by current server.
     *
     * @psalm-var self::SUPPORT_NO|self::SUPPORT_DISABLED|self::SUPPORT_YES|self::SUPPORT_DEFAULT
     */
    public int $support = self::SUPPORT_NO;

    /** @param string $engine The engine ID */
    public function __construct(string $engine)
    {
        $storageEngines = self::getStorageEngines();
        if (empty($storageEngines[$engine])) {
            return;
        }

        $this->engine = $engine;
        $this->title = $storageEngines[$engine]['Engine'];
        $this->comment = ($storageEngines[$engine]['Comment'] ?? '');
        $this->support = match ($storageEngines[$engine]['Support']) {
            'DEFAULT' => self::SUPPORT_DEFAULT,
            'YES' => self::SUPPORT_YES,
            'DISABLED' => self::SUPPORT_DISABLED,
            default => self::SUPPORT_NO,
        };
    }

    /**
     * Returns array of storage engines
     *
     * @return mixed[][] array of storage engines
     *
     * @staticvar array $storage_engines storage engines
     */
    public static function getStorageEngines(): array
    {
        static $storageEngines = null;

        if ($storageEngines == null) {
            $storageEngines = $GLOBALS['dbi']->fetchResult('SHOW STORAGE ENGINES', 'Engine');
            if (! $GLOBALS['dbi']->isMariaDB() && $GLOBALS['dbi']->getVersion() >= 50708) {
                $disabled = (string) SessionCache::get(
                    'disabled_storage_engines',
                    /** @return mixed|false */
                    static fn () => $GLOBALS['dbi']->fetchValue(
                        'SELECT @@disabled_storage_engines',
                    )
                );
                foreach (explode(',', $disabled) as $engine) {
                    if (! isset($storageEngines[$engine])) {
                        continue;
                    }

                    $storageEngines[$engine]['Support'] = 'DISABLED';
                }
            }
        }

        return $storageEngines;
    }

    /**
     * Returns if Mroonga is available to be used
     *
     * This is public to be used in the StructureComtroller, the first release
     * of this function was looking Mroonga in the engines list but this second
     *  method checks too that mroonga is installed successfully
     */
    public static function hasMroongaEngine(): bool
    {
        $cacheKey = 'storage-engine.mroonga.has.mroonga_command';

        if (Cache::has($cacheKey)) {
            return (bool) Cache::get($cacheKey, false);
        }

        $supportsMroonga = $GLOBALS['dbi']->tryQuery('SELECT mroonga_command(\'object_list\');') !== false;
        Cache::set($cacheKey, $supportsMroonga);

        return $supportsMroonga;
    }

    /**
     * Get the lengths of a table of database
     *
     * @param string $dbName    DB name
     * @param string $tableName Table name
     *
     * @return int[]
     */
    public static function getMroongaLengths(string $dbName, string $tableName): array
    {
        $cacheKey = 'storage-engine.mroonga.object_list.' . $dbName;

        $GLOBALS['dbi']->selectDb($dbName);// Needed for mroonga_command calls

        if (! Cache::has($cacheKey)) {
            $result = $GLOBALS['dbi']->fetchSingleRow(
                'SELECT mroonga_command(\'object_list\');',
                DatabaseInterface::FETCH_NUM,
            );
            $objectList = (array) json_decode($result[0] ?? '', true);
            foreach ($objectList as $mroongaName => $mroongaData) {
                /**
                 * We only need the objects of table or column types, more info:
                 * - https://groonga.org/docs/reference/commands/object_list.html#object-type
                 * - https://groonga.org/docs/reference/commands/object_inspect.html#table-type-id
                 * - https://groonga.org/docs/reference/commands/object_inspect.html#column-type-raw-id
                 */
                if (in_array($mroongaData['type']['id'], [48, 49, 50, 51, 64, 65, 72])) {
                    continue;
                }

                unset($objectList[$mroongaName]);
            }

            // At this point, we can remove all the data because only need the mroongaName values
            Cache::set($cacheKey, array_keys($objectList));
        }

        /** @var string[] $objectList */
        $objectList = Cache::get($cacheKey, []);

        $dataLength = 0;
        $indexLength = 0;
        foreach ($objectList as $mroongaName) {
            if (strncmp($tableName, $mroongaName, strlen($tableName)) !== 0) {
                continue;
            }

            $result = $GLOBALS['dbi']->fetchSingleRow(
                'SELECT mroonga_command(\'object_inspect ' . $mroongaName . '\');',
                DatabaseInterface::FETCH_NUM,
            );
            $decodedData = json_decode($result[0] ?? '', true);
            if ($decodedData === null) {
                // Invalid for some strange reason, maybe query failed
                continue;
            }

            $indexPrefix = $tableName . '#' . $tableName;
            if (strncmp($indexPrefix, $mroongaName, strlen($indexPrefix)) === 0) {
                $indexLength += $decodedData['disk_usage'];
                continue;
            }

            $dataLength += $decodedData['disk_usage'];
        }

        return [$dataLength, $indexLength];
    }

    /** @return array<int|string, array<string, mixed>> */
    public static function getArray(): array
    {
        $engines = [];

        foreach (self::getStorageEngines() as $details) {
            // Don't show PERFORMANCE_SCHEMA engine (MySQL 5.5)
            if (
                $details['Support'] === 'NO'
                || $details['Support'] === 'DISABLED'
                || $details['Engine'] === 'PERFORMANCE_SCHEMA'
            ) {
                continue;
            }

            $engines[$details['Engine']] = [
                'name' => $details['Engine'],
                'comment' => $details['Comment'],
                'is_default' => $details['Support'] === 'DEFAULT',
            ];
        }

        return $engines;
    }

    /**
     * Loads the corresponding engine plugin, if available.
     *
     * @param string $engine The engine ID
     *
     * @return StorageEngine The engine plugin
     */
    public static function getEngine(string $engine): StorageEngine
    {
        return match (mb_strtolower($engine)) {
            'bdb' => new Bdb($engine),
            'berkeleydb' => new Berkeleydb($engine),
            'binlog' => new Binlog($engine),
            'innobase' => new Innobase($engine),
            'innodb' => new Innodb($engine),
            'memory' => new Memory($engine),
            'merge' => new Merge($engine),
            'mrg_myisam' => new MrgMyisam($engine),
            'myisam' => new Myisam($engine),
            'ndbcluster' => new Ndbcluster($engine),
            'pbxt' => new Pbxt($engine),
            'performance_schema' => new PerformanceSchema($engine),
            default => new StorageEngine($engine),
        };
    }

    /**
     * Returns true if given engine name is supported/valid, otherwise false
     *
     * @param string $engine name of engine
     */
    public static function isValid(string $engine): bool
    {
        if ($engine === 'PBMS') {
            return true;
        }

        $storageEngines = self::getStorageEngines();

        return isset($storageEngines[$engine]);
    }

    /**
     * Returns as HTML table of the engine's server variables
     *
     * @return string The table that was generated based on the retrieved
     *                information
     */
    public function getHtmlVariables(): string
    {
        $ret = '';

        foreach ($this->getVariablesStatus() as $details) {
            $ret .= '<tr>' . "\n"
                  . '    <td>' . "\n";
            if (! empty($details['desc'])) {
                $ret .= '        '
                    . Generator::showHint($details['desc'])
                    . "\n";
            }

            $ret .= '    </td>' . "\n"
                  . '    <th scope="row">' . htmlspecialchars($details['title']) . '</th>'
                  . "\n"
                  . '    <td class="font-monospace text-end">';
            switch ($details['type']) {
                case self::DETAILS_TYPE_SIZE:
                    $parsedSize = $this->resolveTypeSize($details['value']);
                    if ($parsedSize !== null) {
                        $ret .= $parsedSize[0] . '&nbsp;' . $parsedSize[1];
                    }

                    break;
                case self::DETAILS_TYPE_NUMERIC:
                    $ret .= Util::formatNumber($details['value']) . ' ';
                    break;
                default:
                    $ret .= htmlspecialchars($details['value']) . '   ';
            }

            $ret .= '</td>' . "\n"
                  . '</tr>' . "\n";
        }

        if (! $ret) {
            return '<p>' . "\n"
                . '    '
                . __('There is no detailed status information available for this storage engine.')
                . "\n"
                . '</p>' . "\n";
        }

        return '<table class="table table-striped table-hover w-auto">'
            . "\n" . $ret . '</table>' . "\n";
    }

    /**
     * Returns the engine specific handling for
     * DETAILS_TYPE_SIZE type variables.
     *
     * This function should be overridden when
     * DETAILS_TYPE_SIZE type needs to be
     * handled differently for a particular engine.
     *
     * @param int|string $value Value to format
     *
     * @return mixed[]|null the formatted value and its unit
     */
    public function resolveTypeSize(int|string $value): array|null
    {
        return Util::formatByteDown($value);
    }

    /**
     * Returns array with detailed info about engine specific server variables
     *
     * @return mixed[] array with detailed info about specific engine server variables
     */
    public function getVariablesStatus(): array
    {
        $variables = $this->getVariables();
        $like = $this->getVariablesLikePattern();

        if ($like) {
            $like = " LIKE '" . $like . "' ";
        } else {
            $like = '';
        }

        $mysqlVars = [];

        $sqlQuery = 'SHOW GLOBAL VARIABLES ' . $like . ';';
        $res = $GLOBALS['dbi']->query($sqlQuery);
        foreach ($res as $row) {
            if (isset($variables[$row['Variable_name']])) {
                $mysqlVars[$row['Variable_name']] = $variables[$row['Variable_name']];
            } elseif (! $like && mb_stripos($row['Variable_name'], $this->engine) !== 0) {
                continue;
            }

            $mysqlVars[$row['Variable_name']]['value'] = $row['Value'];

            if (empty($mysqlVars[$row['Variable_name']]['title'])) {
                $mysqlVars[$row['Variable_name']]['title'] = $row['Variable_name'];
            }

            if (isset($mysqlVars[$row['Variable_name']]['type'])) {
                continue;
            }

            $mysqlVars[$row['Variable_name']]['type'] = self::DETAILS_TYPE_PLAINTEXT;
        }

        return $mysqlVars;
    }

    /**
     * Reveals the engine's title
     *
     * @return string The title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Fetches the server's comment about this engine
     *
     * @return string The comment
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * Information message on whether this storage engine is supported
     *
     * @return string The localized message.
     */
    public function getSupportInformationMessage(): string
    {
        $message = match ($this->support) {
            self::SUPPORT_DEFAULT => __('%s is the default storage engine on this MySQL server.'),
            self::SUPPORT_YES => __('%s is available on this MySQL server.'),
            self::SUPPORT_DISABLED => __('%s has been disabled for this MySQL server.'),
            default => __('This MySQL server does not support the %s storage engine.'),
        };

        return sprintf($message, htmlspecialchars($this->title));
    }

    /**
     * Generates a list of MySQL variables that provide information about this
     * engine. This function should be overridden when extending this class
     * for a particular engine.
     *
     * @return mixed[] The list of variables.
     */
    public function getVariables(): array
    {
        return [];
    }

    /**
     * Returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string MySQL help page filename
     */
    public function getMysqlHelpPage(): string
    {
        return $this->engine . '-storage-engine';
    }

    /**
     * Returns the pattern to be used in the query for SQL variables
     * related to the storage engine
     *
     * @return string SQL query LIKE pattern
     */
    public function getVariablesLikePattern(): string
    {
        return '';
    }

    /**
     * Returns a list of available information pages with labels
     *
     * @return string[] The list
     */
    public function getInfoPages(): array
    {
        return [];
    }

    /**
     * Generates the requested information page
     *
     * @param string $id page id
     *
     * @return string html output
     */
    public function getPage(string $id): string
    {
        if (! array_key_exists($id, $this->getInfoPages())) {
            return '';
        }

        $id = 'getPage' . $id;

        return $this->$id();
    }
}
