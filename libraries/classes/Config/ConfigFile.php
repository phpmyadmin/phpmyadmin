<?php
/**
 * Config file management
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Core;

use function __;
use function _pgettext;
use function array_diff;
use function array_flip;
use function array_keys;
use function array_merge;
use function count;
use function is_array;
use function preg_replace;

/**
 * Config file management class.
 * Stores its data in $_SESSION
 */
class ConfigFile
{
    /**
     * Stores default phpMyAdmin config
     *
     * @see Settings
     *
     * @var mixed[]
     */
    private array $defaultCfg;

    /**
     * Stores allowed values for non-standard fields
     *
     * @var array<string, string|mixed[]>
     */
    private array $cfgDb;

    /**
     * Stores original PMA config, not modified by user preferences
     *
     * @var mixed[]|null
     */
    private array|null $baseCfg = null;

    /**
     * Whether we are currently working in PMA Setup context
     */
    private bool $isInSetup;

    /**
     * Keys which will be always written to config file
     *
     * @var mixed[]
     */
    private array $persistKeys = [];

    /**
     * Changes keys while updating config in {@link updateWithGlobalConfig()}
     * or reading by {@link getConfig()} or {@link getConfigArray()}
     *
     * @var mixed[]
     */
    private array $cfgUpdateReadMapping = [];

    /**
     * Key filter for {@link set()}
     */
    private array|null $setFilter = null;

    /**
     * Instance id (key in $_SESSION array, separate for each server -
     * ConfigFile{server id})
     */
    private string $id;

    /**
     * @param mixed[]|null $baseConfig base configuration read from
     *                               {@link PhpMyAdmin\Config::$base_config},
     *                               use only when not in PMA Setup
     */
    public function __construct(array|null $baseConfig = null)
    {
        // load default config values
        $settings = new Settings([]);
        $this->defaultCfg = $settings->asArray();

        // load additional config information
        $this->cfgDb = $this->getAllowedValues();

        $this->baseCfg = $baseConfig;
        $this->isInSetup = $baseConfig === null;
        $this->id = 'ConfigFile' . $GLOBALS['server'];
        if (isset($_SESSION[$this->id])) {
            return;
        }

        $_SESSION[$this->id] = [];
    }

    /**
     * Sets names of config options which will be placed in config file even if
     * they are set to their default values (use only full paths)
     *
     * @param mixed[] $keys the names of the config options
     */
    public function setPersistKeys(array $keys): void
    {
        // checking key presence is much faster than searching so move values
        // to keys
        $this->persistKeys = array_flip($keys);
    }

    /**
     * Returns flipped array set by {@link setPersistKeys()}
     *
     * @return mixed[]
     */
    public function getPersistKeysMap(): array
    {
        return $this->persistKeys;
    }

    /**
     * By default ConfigFile allows setting of all configuration keys, use
     * this method to set up a filter on {@link set()} method
     *
     * @param mixed[]|null $keys array of allowed keys or null to remove filter
     */
    public function setAllowedKeys(array|null $keys): void
    {
        if ($keys === null) {
            $this->setFilter = null;

            return;
        }

        // checking key presence is much faster than searching so move values
        // to keys
        $this->setFilter = array_flip($keys);
    }

    /**
     * Sets path mapping for updating config in
     * {@link updateWithGlobalConfig()} or reading
     * by {@link getConfig()} or {@link getConfigArray()}
     *
     * @param mixed[] $mapping Contains the mapping of "Server/config options"
     *                       to "Server/1/config options"
     */
    public function setCfgUpdateReadMapping(array $mapping): void
    {
        $this->cfgUpdateReadMapping = $mapping;
    }

    /**
     * Resets configuration data
     */
    public function resetConfigData(): void
    {
        $_SESSION[$this->id] = [];
    }

    /**
     * Sets configuration data (overrides old data)
     *
     * @param mixed[] $cfg Configuration options
     */
    public function setConfigData(array $cfg): void
    {
        $_SESSION[$this->id] = $cfg;
    }

    /**
     * Sets config value
     */
    public function set(string $path, mixed $value, string|null $canonicalPath = null): void
    {
        if ($canonicalPath === null) {
            $canonicalPath = $this->getCanonicalPath($path);
        }

        if ($this->setFilter !== null && ! isset($this->setFilter[$canonicalPath])) {
            return;
        }

        // if the path isn't protected it may be removed
        if (isset($this->persistKeys[$canonicalPath])) {
            Core::arrayWrite($path, $_SESSION[$this->id], $value);

            return;
        }

        $defaultValue = $this->getDefault($canonicalPath);
        $removePath = $value === $defaultValue;
        if ($this->isInSetup) {
            // remove if it has a default value or is empty
            $removePath = $removePath
                || (empty($value) && empty($defaultValue));
        } else {
            // get original config values not overwritten by user
            // preferences to allow for overwriting options set in
            // config.inc.php with default values
            $instanceDefaultValue = Core::arrayRead($canonicalPath, $this->baseCfg);
            // remove if it has a default value and base config (config.inc.php)
            // uses default value
            $removePath = $removePath
                && ($instanceDefaultValue === $defaultValue);
        }

        if ($removePath) {
            Core::arrayRemove($path, $_SESSION[$this->id]);

            return;
        }

        Core::arrayWrite($path, $_SESSION[$this->id], $value);
    }

    /**
     * Flattens multidimensional array, changes indices to paths
     * (eg. 'key/subkey').
     *
     * @param mixed[] $array  Multidimensional array
     * @param string  $prefix Prefix
     *
     * @return mixed[]
     */
    private function getFlatArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && ! isset($value[0])) {
                $result += $this->getFlatArray($value, $prefix . $key . '/');
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns default config in a flattened array
     *
     * @return mixed[]
     */
    public function getFlatDefaultConfig(): array
    {
        return $this->getFlatArray($this->defaultCfg);
    }

    /**
     * Updates config with values read from given array
     * (config will contain differences to defaults from {@see \PhpMyAdmin\Config\Settings}).
     *
     * @param mixed[] $cfg Configuration
     */
    public function updateWithGlobalConfig(array $cfg): void
    {
        // load config array and flatten it
        $flatConfig = $this->getFlatArray($cfg);

        // save values map for translating a few user preferences paths,
        // should be complemented by code reading from generated config
        // to perform inverse mapping
        foreach ($flatConfig as $path => $value) {
            if (isset($this->cfgUpdateReadMapping[$path])) {
                $path = $this->cfgUpdateReadMapping[$path];
            }

            $this->set($path, $value, $path);
        }
    }

    /**
     * Returns config value or $default if it's not set
     *
     * @param string $path    Path of config file
     * @param mixed  $default Default values
     */
    public function get(string $path, mixed $default = null): mixed
    {
        return Core::arrayRead($path, $_SESSION[$this->id], $default);
    }

    /**
     * Returns default config value or $default it it's not set ie. it doesn't
     * exist in {@see \PhpMyAdmin\Config\Settings} ($cfg).
     *
     * @param string $canonicalPath Canonical path
     * @param mixed  $default       Default value
     */
    public function getDefault(string $canonicalPath, mixed $default = null): mixed
    {
        return Core::arrayRead($canonicalPath, $this->defaultCfg, $default);
    }

    /**
     * Returns config value, if it's not set uses the default one; returns
     * $default if the path isn't set and doesn't contain a default value
     *
     * @param string $path    Path
     * @param mixed  $default Default value
     */
    public function getValue(string $path, mixed $default = null): mixed
    {
        $v = Core::arrayRead($path, $_SESSION[$this->id], null);
        if ($v !== null) {
            return $v;
        }

        $path = $this->getCanonicalPath($path);

        return $this->getDefault($path, $default);
    }

    /**
     * Returns canonical path
     *
     * @param string $path Path
     */
    public function getCanonicalPath(string $path): string
    {
        return preg_replace('#^Servers/([\d]+)/#', 'Servers/1/', $path);
    }

    /**
     * Returns config database entry for $path
     *
     * @param string $path    path of the variable in config db
     * @param mixed  $default default value
     */
    public function getDbEntry(string $path, mixed $default = null): mixed
    {
        return Core::arrayRead($path, $this->cfgDb, $default);
    }

    /**
     * Returns server count
     */
    public function getServerCount(): int
    {
        return isset($_SESSION[$this->id]['Servers'])
            ? count($_SESSION[$this->id]['Servers'])
            : 0;
    }

    /**
     * Returns server list
     *
     * @return mixed[]
     */
    public function getServers(): array
    {
        return $_SESSION[$this->id]['Servers'] ?? [];
    }

    /**
     * Returns DSN of given server
     *
     * @param int $server server index
     */
    public function getServerDSN(int $server): string
    {
        if (! isset($_SESSION[$this->id]['Servers'][$server])) {
            return '';
        }

        $path = 'Servers/' . $server;
        $dsn = 'mysqli://';
        if ($this->getValue($path . '/auth_type') === 'config') {
            $dsn .= $this->getValue($path . '/user');
            if (! empty($this->getValue($path . '/password'))) {
                $dsn .= ':***';
            }

            $dsn .= '@';
        }

        if ($this->getValue($path . '/host') !== 'localhost') {
            $dsn .= $this->getValue($path . '/host');
            $port = $this->getValue($path . '/port');
            if ($port) {
                $dsn .= ':' . $port;
            }
        } else {
            $dsn .= $this->getValue($path . '/socket');
        }

        return $dsn;
    }

    /**
     * Returns server name
     *
     * @param int $id server index
     */
    public function getServerName(int $id): string
    {
        if (! isset($_SESSION[$this->id]['Servers'][$id])) {
            return '';
        }

        $verbose = $this->get('Servers/' . $id . '/verbose');
        if (! empty($verbose)) {
            return $verbose;
        }

        $host = $this->get('Servers/' . $id . '/host');

        return empty($host) ? 'localhost' : $host;
    }

    /**
     * Removes server
     *
     * @param int $server server index
     */
    public function removeServer(int $server): void
    {
        if (! isset($_SESSION[$this->id]['Servers'][$server])) {
            return;
        }

        $lastServer = $this->getServerCount();

        for ($i = $server; $i < $lastServer; $i++) {
            $_SESSION[$this->id]['Servers'][$i] = $_SESSION[$this->id]['Servers'][$i + 1];
        }

        unset($_SESSION[$this->id]['Servers'][$lastServer]);

        if (! isset($_SESSION[$this->id]['ServerDefault']) || $_SESSION[$this->id]['ServerDefault'] != $lastServer) {
            return;
        }

        unset($_SESSION[$this->id]['ServerDefault']);
    }

    /**
     * Returns configuration array (full, multidimensional format)
     *
     * @return mixed[]
     */
    public function getConfig(): array
    {
        $c = $_SESSION[$this->id];
        foreach ($this->cfgUpdateReadMapping as $mapTo => $mapFrom) {
            // if the key $c exists in $map_to
            if (Core::arrayRead($mapTo, $c) === null) {
                continue;
            }

            Core::arrayWrite($mapTo, $c, Core::arrayRead($mapFrom, $c));
            Core::arrayRemove($mapFrom, $c);
        }

        return $c;
    }

    /**
     * Returns configuration array (flat format)
     *
     * @return mixed[]
     */
    public function getConfigArray(): array
    {
        $c = $this->getFlatArray($_SESSION[$this->id]);

        $persistKeys = array_diff(
            array_keys($this->persistKeys),
            array_keys($c),
        );
        foreach ($persistKeys as $k) {
            $c[$k] = $this->getDefault($this->getCanonicalPath($k));
        }

        foreach ($this->cfgUpdateReadMapping as $mapTo => $mapFrom) {
            if (! isset($c[$mapFrom])) {
                continue;
            }

            $c[$mapTo] = $c[$mapFrom];
            unset($c[$mapFrom]);
        }

        return $c;
    }

    /**
     * Database with allowed values for configuration stored in the $cfg array,
     * used by setup script and user preferences to generate forms.
     *
     * Value meaning:
     *   array - select field, array contains allowed values
     *   string - type override
     *
     * @return array<string, string|mixed[]>
     */
    public function getAllowedValues(): array
    {
        return [
            'Servers' => [
                1 => [
                    'port' => 'integer',
                    'auth_type' => ['config', 'http', 'signon', 'cookie'],
                    'AllowDeny' => ['order' => ['', 'deny,allow', 'allow,deny', 'explicit']],
                    'only_db' => 'array',
                ],
            ],
            'RecodingEngine' => ['auto', 'iconv', 'recode', 'mb', 'none'],
            'OBGzip' => ['auto', true, false],
            'MemoryLimit' => 'short_string',
            'NavigationLogoLinkWindow' => ['main', 'new'],
            'NavigationTreeDefaultTabTable' => [
                // fields list
                'structure' => __('Structure'),
                // SQL form
                'sql' => __('SQL'),
                // search page
                'search' => __('Search'),
                // insert row page
                'insert' => __('Insert'),
                // browse page
                'browse' => __('Browse'),
            ],
            'NavigationTreeDefaultTabTable2' => [
                //don't display
                '' => '',
                // fields list
                'structure' => __('Structure'),
                // SQL form
                'sql' => __('SQL'),
                // search page
                'search' => __('Search'),
                // insert row page
                'insert' => __('Insert'),
                // browse page
                'browse' => __('Browse'),
            ],
            'NavigationTreeDbSeparator' => 'short_string',
            'NavigationTreeTableSeparator' => 'short_string',
            'NavigationWidth' => 'integer',
            'TableNavigationLinksMode' => ['icons' => __('Icons'), 'text' => __('Text'), 'both' => __('Both')],
            'MaxRows' => [25, 50, 100, 250, 500],
            'Order' => ['ASC', 'DESC', 'SMART'],
            'RowActionLinks' => [
                'none' => __('Nowhere'),
                'left' => __('Left'),
                'right' => __('Right'),
                'both' => __('Both'),
            ],
            'TablePrimaryKeyOrder' => ['NONE' => __('None'), 'ASC' => __('Ascending'), 'DESC' => __('Descending')],
            'ProtectBinary' => [false, 'blob', 'noblob', 'all'],
            'CharEditing' => ['input', 'textarea'],
            'TabsMode' => ['icons' => __('Icons'), 'text' => __('Text'), 'both' => __('Both')],
            'PDFDefaultPageSize' => [
                'A3' => 'A3',
                'A4' => 'A4',
                'A5' => 'A5',
                'letter' => 'letter',
                'legal' => 'legal',
            ],
            'ActionLinksMode' => ['icons' => __('Icons'), 'text' => __('Text'), 'both' => __('Both')],
            'GridEditing' => [
                'click' => __('Click'),
                'double-click' => __('Double click'),
                'disabled' => __('Disabled'),
            ],
            'RelationalDisplay' => ['K' => __('key'), 'D' => __('display column')],
            'DefaultTabServer' => [
                // the welcome page (recommended for multiuser setups)
                'welcome' => __('Welcome'),
                // list of databases
                'databases' => __('Databases'),
                // runtime information
                'status' => __('Status'),
                // MySQL server variables
                'variables' => __('Variables'),
                // user management
                'privileges' => __('Privileges'),
            ],
            'DefaultTabDatabase' => [
                // tables list
                'structure' => __('Structure'),
                // SQL form
                'sql' => __('SQL'),
                // search query
                'search' => __('Search'),
                // operations on database
                'operations' => __('Operations'),
            ],
            'DefaultTabTable' => [
                // fields list
                'structure' => __('Structure'),
                // SQL form
                'sql' => __('SQL'),
                // search page
                'search' => __('Search'),
                // insert row page
                'insert' => __('Insert'),
                // browse page
                'browse' => __('Browse'),
            ],
            'InitialSlidersState' => ['open' => __('Open'), 'closed' => __('Closed'), 'disabled' => __('Disabled')],
            'FirstDayOfCalendar' => [
                '1' => _pgettext('Week day name', 'Monday'),
                '2' => _pgettext('Week day name', 'Tuesday'),
                '3' => _pgettext('Week day name', 'Wednesday'),
                '4' => _pgettext('Week day name', 'Thursday'),
                '5' => _pgettext('Week day name', 'Friday'),
                '6' => _pgettext('Week day name', 'Saturday'),
                '7' => _pgettext('Week day name', 'Sunday'),
            ],
            'SendErrorReports' => [
                'ask' => __('Ask before sending error reports'),
                'always' => __('Always send error reports'),
                'never' => __('Never send error reports'),
            ],
            'DefaultForeignKeyChecks' => [
                'default' => __('Server default'),
                'enable' => __('Enable'),
                'disable' => __('Disable'),
            ],

            'Import' => [
                'format' => [
                    // CSV
                    'csv',
                    // DocSQL
                    'docsql',
                    // CSV using LOAD DATA
                    'ldi',
                    // SQL
                    'sql',
                ],
                'charset' => array_merge([''], $GLOBALS['cfg']['AvailableCharsets'] ?? []),
                'sql_compatibility' => [
                    'NONE',
                    'ANSI',
                    'DB2',
                    'MAXDB',
                    'MYSQL323',
                    'MYSQL40',
                    'MSSQL',
                    'ORACLE',
                    // removed; in MySQL 5.0.33, this produces exports that
                    // can't be read by POSTGRESQL (see our bug #1596328)
                    //'POSTGRESQL',
                    'TRADITIONAL',
                ],
                'csv_terminated' => 'short_string',
                'csv_enclosed' => 'short_string',
                'csv_escaped' => 'short_string',
                'ldi_terminated' => 'short_string',
                'ldi_enclosed' => 'short_string',
                'ldi_escaped' => 'short_string',
                'ldi_local_option' => ['auto', true, false],
            ],

            'Export' => [
                '_sod_select' => [
                    'structure' => __('structure'),
                    'data' => __('data'),
                    'structure_and_data' => __('structure and data'),
                ],
                'method' => [
                    'quick' => __('Quick - display only the minimal options to configure'),
                    'custom' => __('Custom - display all possible options to configure'),
                    'custom-no-form' => __('Custom - like above, but without the quick/custom choice'),
                ],
                'format' => [
                    'codegen',
                    'csv',
                    'excel',
                    'htmlexcel',
                    'htmlword',
                    'latex',
                    'ods',
                    'odt',
                    'pdf',
                    'sql',
                    'texytext',
                    'xml',
                    'yaml',
                ],
                'compression' => ['none', 'zip', 'gzip'],
                'charset' => array_merge([''], $GLOBALS['cfg']['AvailableCharsets'] ?? []),
                'sql_compatibility' => [
                    'NONE',
                    'ANSI',
                    'DB2',
                    'MAXDB',
                    'MYSQL323',
                    'MYSQL40',
                    'MSSQL',
                    'ORACLE',
                    // removed; in MySQL 5.0.33, this produces exports that
                    // can't be read by POSTGRESQL (see our bug #1596328)
                    //'POSTGRESQL',
                    'TRADITIONAL',
                ],
                'codegen_format' => ['#', 'NHibernate C# DO', 'NHibernate XML'],
                'csv_separator' => 'short_string',
                'csv_terminated' => 'short_string',
                'csv_enclosed' => 'short_string',
                'csv_escaped' => 'short_string',
                'csv_null' => 'short_string',
                'excel_null' => 'short_string',
                'excel_edition' => [
                    'win' => 'Windows',
                    'mac_excel2003' => 'Excel 2003 / Macintosh',
                    'mac_excel2008' => 'Excel 2008 / Macintosh',
                ],
                'sql_structure_or_data' => [
                    'structure' => __('structure'),
                    'data' => __('data'),
                    'structure_and_data' => __('structure and data'),
                ],
                'sql_type' => ['INSERT', 'UPDATE', 'REPLACE'],
                'sql_insert_syntax' => [
                    'complete' => __('complete inserts'),
                    'extended' => __('extended inserts'),
                    'both' => __('both of the above'),
                    'none' => __('neither of the above'),
                ],
                'htmlword_structure_or_data' => [
                    'structure' => __('structure'),
                    'data' => __('data'),
                    'structure_and_data' => __('structure and data'),
                ],
                'htmlword_null' => 'short_string',
                'ods_null' => 'short_string',
                'odt_null' => 'short_string',
                'odt_structure_or_data' => [
                    'structure' => __('structure'),
                    'data' => __('data'),
                    'structure_and_data' => __('structure and data'),
                ],
                'texytext_structure_or_data' => [
                    'structure' => __('structure'),
                    'data' => __('data'),
                    'structure_and_data' => __('structure and data'),
                ],
                'texytext_null' => 'short_string',
            ],

            'Console' => [
                'Mode' => ['info', 'show', 'collapse'],
                'OrderBy' => ['exec', 'time', 'count'],
                'Order' => ['asc', 'desc'],
            ],

            /**
             * Basic validator assignments (functions from libraries/config/Validator.php
             * and 'window.validators' object in js/config.js)
             * Use only full paths and form ids
             */
            '_validators' => [
                'Console/Height' => 'validateNonNegativeNumber',
                'CharTextareaCols' => 'validatePositiveNumber',
                'CharTextareaRows' => 'validatePositiveNumber',
                'ExecTimeLimit' => 'validateNonNegativeNumber',
                'Export/sql_max_query_size' => 'validatePositiveNumber',
                'FirstLevelNavigationItems' => 'validatePositiveNumber',
                'ForeignKeyMaxLimit' => 'validatePositiveNumber',
                'Import/csv_enclosed' => [['validateByRegex', '/^.?$/']],
                'Import/csv_escaped' => [['validateByRegex', '/^.$/']],
                'Import/csv_terminated' => [['validateByRegex', '/^.$/']],
                'Import/ldi_enclosed' => [['validateByRegex', '/^.?$/']],
                'Import/ldi_escaped' => [['validateByRegex', '/^.$/']],
                'Import/ldi_terminated' => [['validateByRegex', '/^.$/']],
                'Import/skip_queries' => 'validateNonNegativeNumber',
                'InsertRows' => 'validatePositiveNumber',
                'NumRecentTables' => 'validateNonNegativeNumber',
                'NumFavoriteTables' => 'validateNonNegativeNumber',
                'LimitChars' => 'validatePositiveNumber',
                'LoginCookieValidity' => 'validatePositiveNumber',
                'LoginCookieStore' => 'validateNonNegativeNumber',
                'MaxDbList' => 'validatePositiveNumber',
                'MaxNavigationItems' => 'validatePositiveNumber',
                'MaxCharactersInDisplayedSQL' => 'validatePositiveNumber',
                'MaxRows' => 'validatePositiveNumber',
                'MaxSizeForInputField' => 'validatePositiveNumber',
                'MinSizeForInputField' => 'validateNonNegativeNumber',
                'MaxTableList' => 'validatePositiveNumber',
                'MemoryLimit' => [['validateByRegex', '/^(-1|(\d+(?:[kmg])?))$/i']],
                'NavigationTreeDisplayItemFilterMinimum' => 'validatePositiveNumber',
                'NavigationTreeTableLevel' => 'validatePositiveNumber',
                'NavigationWidth' => 'validateNonNegativeNumber',
                'QueryHistoryMax' => 'validatePositiveNumber',
                'RepeatCells' => 'validateNonNegativeNumber',
                'Server' => 'validateServer',
                'Server_pmadb' => 'validatePMAStorage',
                'Servers/1/port' => 'validatePortNumber',
                'Servers/1/hide_db' => 'validateRegex',
                'TextareaCols' => 'validatePositiveNumber',
                'TextareaRows' => 'validatePositiveNumber',
                'TrustedProxies' => 'validateTrustedProxies',
            ],

            /**
             * Additional validators used for user preferences
             */
            '_userValidators' => [
                'MaxDbList' => [['validateUpperBound', 'value:MaxDbList']],
                'MaxTableList' => [['validateUpperBound', 'value:MaxTableList']],
                'QueryHistoryMax' => [['validateUpperBound', 'value:QueryHistoryMax']],
            ],
        ];
    }
}
