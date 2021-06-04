<?php
/**
 * Fake database driver for testing purposes
 *
 * It has hardcoded results for given queries what makes easy to use it
 * in testsuite. Feel free to include other queries which your test will
 * need.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Stubs;

use PhpMyAdmin\Dbal\DbiExtension;
use function addslashes;
use function count;
use function is_array;
use function is_bool;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Fake database driver for testing purposes
 *
 * It has hardcoded results for given queries what makes easy to use it
 * in testsuite. Feel free to include other queries which your test will
 * need.
 */
class DbiDummy implements DbiExtension
{
    /**
     * First in, last out queries
     *
     * The results will be distributed in the filo way
     *
     * @var array
     * @phpstan-var array{
     *     'query': string,
     *     'result': ((int[]|string[]|array{string: string})[])|bool|empty-array,
     *     'columns'?: string[],
     *     'metadata'?: object[]|empty-array,
     *     'used'?: bool,
     *     'pos'?: int
     * }[]
     */
    private $filoQueries = [];

    /**
     * @var array
     * @phpstan-var array{
     *     'query': string,
     *     'result': ((int[]|string[]|array{string: string})[])|bool|empty-array,
     *     'columns'?: string[],
     *     'metadata'?: object[]|empty-array,
     *     'pos'?: int
     * }[]
     */
    private $dummyQueries = [];

    public const OFFSET_GLOBAL = 1000;

    public function __construct()
    {
        $this->init();
    }

    /**
     * connects to the database server
     *
     * @param string $user     mysql user name
     * @param string $password mysql user password
     * @param array  $server   host/port/socket/persistent
     *
     * @return mixed false on error or a mysqli object on success
     */
    public function connect(
        $user,
        $password,
        array $server = []
    ) {
        return true;
    }

    /**
     * selects given database
     *
     * @param string $dbname name of db to select
     * @param object $link   mysql link resource
     *
     * @return bool
     */
    public function selectDb($dbname, $link)
    {
        $GLOBALS['dummy_db'] = $dbname;

        return true;
    }

    public function hasUnUsedQueries(): bool
    {
        foreach ($this->filoQueries as $query) {
            if (($query['used'] ?? false) === true) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return false|int|null
     */
    private function findFiloQuery(string $query)
    {
        for ($i = 0, $nb = count($this->filoQueries); $i < $nb; $i++) {
            if ($this->filoQueries[$i]['query'] !== $query) {
                continue;
            }

            if ($this->filoQueries[$i]['used'] ?? false) {
                continue;// Is has already been used
            }

            $this->filoQueries[$i]['pos'] = 0;
            $this->filoQueries[$i]['used'] = true;

            if (! is_array($this->filoQueries[$i]['result'])) {
                return false;
            }

            return $i;
        }

        return null;
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query   query to run
     * @param object $link    mysql link resource
     * @param int    $options query options
     *
     * @return mixed
     */
    public function realQuery($query, $link = null, $options = 0)
    {
        $query = trim((string) preg_replace('/  */', ' ', str_replace("\n", ' ', $query)));
        $filoQuery = $this->findFiloQuery($query);
        if ($filoQuery !== null) {// Found a matching query
            return $filoQuery;
        }
        for ($i = 0, $nb = count($this->dummyQueries); $i < $nb; $i++) {
            if ($this->dummyQueries[$i]['query'] !== $query) {
                continue;
            }

            $this->dummyQueries[$i]['pos'] = 0;
            if (! is_array($this->dummyQueries[$i]['result'])) {
                return false;
            }

            return $i + self::OFFSET_GLOBAL;
        }
        echo 'Not supported query: ' . $query . "\n";

        return false;
    }

    /**
     * Run the multi query and output the results
     *
     * @param object $link  connection object
     * @param string $query multi query statement to execute
     *
     * @return array|bool
     */
    public function realMultiQuery($link, $query)
    {
        return false;
    }

    /**
     * returns result data from $result
     *
     * @param object $result MySQL result
     */
    public function fetchAny($result): ?array
    {
        $query_data = &$this->getQueryData($result);
        if ($query_data['pos'] >= count((array) $query_data['result'])) {
            return null;
        }
        $ret = $query_data['result'][$query_data['pos']];
        $query_data['pos'] += 1;

        return $ret;
    }

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param object $result result  MySQL result
     */
    public function fetchArray($result): ?array
    {
        $query_data = &$this->getQueryData($result);
        $data = $this->fetchAny($result);
        if (! is_array($data)
            || ! isset($query_data['columns'])
        ) {
            return $data;
        }

        foreach ($data as $key => $val) {
            $data[$query_data['columns'][$key]] = $val;
        }

        return $data;
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param object $result MySQL result
     */
    public function fetchAssoc($result): ?array
    {
        $data = $this->fetchAny($result);
        $query_data = &$this->getQueryData($result);
        if (! is_array($data) || ! isset($query_data['columns'])) {
            return $data;
        }

        $ret = [];
        foreach ($data as $key => $val) {
            $ret[$query_data['columns'][$key]] = $val;
        }

        return $ret;
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param object $result MySQL result
     */
    public function fetchRow($result): ?array
    {
        return $this->fetchAny($result);
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param object $result database result
     * @param int    $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, $offset)
    {
        $query_data = &$this->getQueryData($result);
        if ($offset > count($query_data['result'])) {
            return false;
        }
        $query_data['pos'] = $offset;

        return true;
    }

    /**
     * Frees memory associated with the result
     *
     * @param object $result database result
     *
     * @return void
     */
    public function freeResult($result)
    {
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param object $link the connection object
     *
     * @return bool false
     */
    public function moreResults($link)
    {
        return false;
    }

    /**
     * Prepare next result from multi_query
     *
     * @param object $link the connection object
     *
     * @return bool false
     */
    public function nextResult($link)
    {
        return false;
    }

    /**
     * Store the result returned from multi query
     *
     * @param object $link the connection object
     *
     * @return mixed false when empty results / result set when not empty
     */
    public function storeResult($link)
    {
        return false;
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param object $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link)
    {
        return '';
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param object $link mysql link
     *
     * @return int version of the MySQL protocol used
     */
    public function getProtoInfo($link)
    {
        return -1;
    }

    /**
     * returns a string that represents the client library version
     *
     * @param object $link connection link
     *
     * @return string MySQL client library version
     */
    public function getClientInfo($link)
    {
        return 'libmysql - mysqlnd x.x.x-dev (phpMyAdmin tests)';
    }

    /**
     * returns last error message or false if no errors occurred
     *
     * @param object $link connection link
     *
     * @return string|bool error or false
     */
    public function getError($link)
    {
        return false;
    }

    /**
     * returns the number of rows returned by last query
     *
     * @param object $result MySQL result
     *
     * @return string|int
     */
    public function numRows($result)
    {
        if (is_bool($result)) {
            return 0;
        }

        $query_data = &$this->getQueryData($result);

        return count($query_data['result']);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param object $link           the mysql object
     * @param bool   $get_from_cache whether to retrieve from cache
     *
     * @return string|int
     */
    public function affectedRows($link = null, $get_from_cache = true)
    {
        global $cached_affected_rows;

        return $cached_affected_rows ?? 0;
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param object $result result set identifier
     *
     * @return array meta info for fields in $result
     */
    public function getFieldsMeta($result)
    {
        $query_data = &$this->getQueryData($result);
        if (! isset($query_data['metadata'])) {
            return [];
        }

        return $query_data['metadata'];
    }

    /**
     * return number of fields in given $result
     *
     * @param object $result MySQL result
     *
     * @return int  field count
     */
    public function numFields($result)
    {
        $query_data = &$this->getQueryData($result);
        if (! isset($query_data['columns'])) {
            return 0;
        }

        return count($query_data['columns']);
    }

    /**
     * returns the length of the given field $i in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return int length of field
     */
    public function fieldLen($result, $i)
    {
        return -1;
    }

    /**
     * returns name of $i. field in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, $i)
    {
        return '';
    }

    /**
     * returns concatenated string of human readable field flags
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i)
    {
        return '';
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param mixed  $link database link
     * @param string $str  string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString($link, $str)
    {
        return addslashes($str);
    }

    /**
     * Adds query result for testing
     *
     * @param string     $query    SQL
     * @param array|bool $result   Expected result
     * @param string[]   $columns  The result columns
     * @param object[]   $metadata The result metadata
     *
     * @return void
     *
     * @phpstan-param (int[]|string[]|array{string: string})[]|bool $result
     */
    public function addResult(string $query, $result, array $columns = [], array $metadata = [])
    {
        $this->filoQueries[] = [
            'query' => $query,
            'result' => $result,
            'columns' => $columns,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param mixed  $link  link
     * @param string $query query
     *
     * @return object|false
     */
    public function prepare($link, string $query)
    {
        return false;
    }

    /**
     * Return query data for ID
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    private function &getQueryData($result)
    {
        if ($result >= self::OFFSET_GLOBAL) {
            return $this->dummyQueries[$result - self::OFFSET_GLOBAL];
        }

        return $this->filoQueries[$result];
    }

    private function init(): void
    {
        /**
         * Array of queries this "driver" supports
         */
        $this->dummyQueries = [
            [
                'query' => 'SELECT 1',
                'result' => [['1']],
            ],
            [
                'query'  => 'SELECT CURRENT_USER();',
                'result' => [['pma_test@localhost']],
            ],
            [
                'query'  => "SHOW VARIABLES LIKE 'lower_case_table_names'",
                'result' => [
                    [
                        'lower_case_table_names',
                        '1',
                    ],
                ],
            ],
            [
                'query'  => 'SELECT 1 FROM mysql.user LIMIT 1',
                'result' => [['1']],
            ],
            [
                'query'  => 'SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                    . " WHERE `PRIVILEGE_TYPE` = 'CREATE USER'"
                    . " AND '''pma_test''@''localhost''' LIKE `GRANTEE` LIMIT 1",
                'result' => [['1']],
            ],
            [
                'query'  => 'SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE`'
                    . ' FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES`'
                    . ' UNION SELECT `GRANTEE`, `IS_GRANTABLE`'
                    . ' FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES`'
                    . ' UNION SELECT `GRANTEE`, `IS_GRANTABLE`'
                    . ' FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                    . ' UNION SELECT `GRANTEE`, `IS_GRANTABLE`'
                    . ' FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t'
                    . " WHERE `IS_GRANTABLE` = 'YES'"
                    . " AND '''pma_test''@''localhost''' LIKE `GRANTEE` LIMIT 1",
                'result' => [['1']],
            ],
            [
                'query'  => 'SHOW MASTER LOGS',
                'result' => [
                    [
                        'Log_name' => 'index1',
                        'File_size' => 100,
                    ],
                    [
                        'Log_name' => 'index2',
                        'File_size' => 200,
                    ],
                ],
            ],
            [
                'query'  => 'SHOW STORAGE ENGINES',
                'result' => [
                    [
                        'Engine'  => 'dummy',
                        'Support' => 'YES',
                        'Comment' => 'dummy comment',
                    ],
                    [
                        'Engine'  => 'dummy2',
                        'Support' => 'NO',
                        'Comment' => 'dummy2 comment',
                    ],
                    [
                        'Engine'  => 'FEDERATED',
                        'Support' => 'NO',
                        'Comment' => 'Federated MySQL storage engine',
                    ],
                    [
                        'Engine'  => 'Pbxt',
                        'Support' => 'NO',
                        'Comment' => 'Pbxt storage engine',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW STATUS WHERE Variable_name'
                    . ' LIKE \'Innodb\\_buffer\\_pool\\_%\''
                    . ' OR Variable_name = \'Innodb_page_size\';',
                'result' => [
                    [
                        'Innodb_buffer_pool_pages_data',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_pages_dirty',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_pages_flushed',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_pages_free',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_pages_misc',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_pages_total',
                        4096,
                    ],
                    [
                        'Innodb_buffer_pool_read_ahead_rnd',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_read_ahead',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_read_ahead_evicted',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_read_requests',
                        64,
                    ],
                    [
                        'Innodb_buffer_pool_reads',
                        32,
                    ],
                    [
                        'Innodb_buffer_pool_wait_free',
                        0,
                    ],
                    [
                        'Innodb_buffer_pool_write_requests',
                        64,
                    ],
                    [
                        'Innodb_page_size',
                        16384,
                    ],
                ],
            ],
            [
                'query'  => 'SHOW ENGINE INNODB STATUS;',
                'result' => false,
            ],
            [
                'query'  => 'SELECT @@innodb_version;',
                'result' => [
                    ['1.1.8'],
                ],
            ],
            [
                'query'  => 'SELECT @@disabled_storage_engines',
                'result' => [
                    [''],
                ],
            ],
            [
                'query' => 'SHOW GLOBAL VARIABLES ;',
                'result' => [],
            ],
            [
                'query'  => 'SHOW GLOBAL VARIABLES LIKE \'innodb_file_per_table\';',
                'result' => [
                    [
                        'innodb_file_per_table',
                        'OFF',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW GLOBAL VARIABLES LIKE \'innodb_file_format\';',
                'result' => [
                    [
                        'innodb_file_format',
                        'Antelope',
                    ],
                ],
            ],
            [
                'query'  => 'SELECT @@collation_server',
                'result' => [
                    ['utf8_general_ci'],
                ],
            ],
            [
                'query'  => 'SELECT @@lc_messages;',
                'result' => [],
            ],
            [
                'query'  => 'SHOW SESSION VARIABLES LIKE \'FOREIGN_KEY_CHECKS\';',
                'result' => [
                    [
                        'foreign_key_checks',
                        'ON',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW TABLES FROM `pma_test`;',
                'result' => [
                    ['table1'],
                    ['table2'],
                ],
            ],
            [
                'query'  => 'SHOW TABLES FROM `pmadb`',
                'result' => [
                    ['column_info'],
                ],
            ],
            [
                'query'   => 'SHOW COLUMNS FROM `pma_test`.`table1`',
                'columns' => [
                    'Field',
                    'Type',
                    'Null',
                    'Key',
                    'Default',
                    'Extra',
                ],
                'result'  => [
                    [
                        'i',
                        'int(11)',
                        'NO',
                        'PRI',
                        'NULL',
                        'auto_increment',
                    ],
                    [
                        'o',
                        'int(11)',
                        'NO',
                        'MUL',
                        'NULL',
                        '',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW INDEXES FROM `pma_test`.`table1` WHERE (Non_unique = 0)',
                'result' => [],
            ],
            [
                'query'   => 'SHOW COLUMNS FROM `pma_test`.`table2`',
                'columns' => [
                    'Field',
                    'Type',
                    'Null',
                    'Key',
                    'Default',
                    'Extra',
                ],
                'result'  => [
                    [
                        'i',
                        'int(11)',
                        'NO',
                        'PRI',
                        'NULL',
                        'auto_increment',
                    ],
                    [
                        'o',
                        'int(11)',
                        'NO',
                        'MUL',
                        'NULL',
                        '',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW INDEXES FROM `pma_test`.`table1`',
                'result' => [],
            ],
            [
                'query'  => 'SHOW INDEXES FROM `pma_test`.`table2`',
                'result' => [],
            ],
            [
                'query'   => 'SHOW COLUMNS FROM `pma`.`table1`',
                'columns' => [
                    'Field',
                    'Type',
                    'Null',
                    'Key',
                    'Default',
                    'Extra',
                    'Privileges',
                    'Comment',
                ],
                'result'  => [
                    [
                        'i',
                        'int(11)',
                        'NO',
                        'PRI',
                        'NULL',
                        'auto_increment',
                        'select,insert,update,references',
                        '',
                    ],
                    [
                        'o',
                        'varchar(100)',
                        'NO',
                        'MUL',
                        'NULL',
                        '',
                        'select,insert,update,references',
                        '',
                    ],
                ],
            ],
            [
                'query'   => 'SELECT `CHARACTER_SET_NAME` AS `Charset`,'
                    . ' `DEFAULT_COLLATE_NAME` AS `Default collation`,'
                    . ' `DESCRIPTION` AS `Description`,'
                    . ' `MAXLEN` AS `Maxlen`'
                    . ' FROM `information_schema`.`CHARACTER_SETS`',
                'columns' => [
                    'Charset',
                    'Default collation',
                    'Description',
                    'Maxlen',
                ],
                'result'  => [
                    [
                        'armscii8',
                        'ARMSCII-8 Armenian',
                        'armscii8_general_ci',
                        '1',
                    ],
                    [
                        'utf8',
                        'utf8_general_ci',
                        'UTF-8 Unicode',
                        '3',
                    ],
                    [
                        'utf8mb4',
                        'UTF-8 Unicode',
                        'utf8mb4_0900_ai_ci',
                        '4',
                    ],
                    [
                        'latin1',
                        'latin1_swedish_ci',
                        'cp1252 West European',
                        '1',
                    ],
                ],
            ],
            [
                'query'   => 'SELECT `COLLATION_NAME` AS `Collation`,'
                    . ' `CHARACTER_SET_NAME` AS `Charset`,'
                    . ' `ID` AS `Id`,'
                    . ' `IS_DEFAULT` AS `Default`,'
                    . ' `IS_COMPILED` AS `Compiled`,'
                    . ' `SORTLEN` AS `Sortlen`'
                    . ' FROM `information_schema`.`COLLATIONS`',
                'columns' => [
                    'Collation',
                    'Charset',
                    'Id',
                    'Default',
                    'Compiled',
                    'Sortlen',
                ],
                'result'  => [
                    [
                        'utf8mb4_general_ci',
                        'utf8mb4',
                        '45',
                        'Yes',
                        'Yes',
                        '1',
                    ],
                    [
                        'armscii8_general_ci',
                        'armscii8',
                        '32',
                        'Yes',
                        'Yes',
                        '1',
                    ],
                    [
                        'utf8_general_ci',
                        'utf8',
                        '33',
                        'Yes',
                        'Yes',
                        '1',
                    ],
                    [
                        'utf8_bin',
                        'utf8',
                        '83',
                        '',
                        'Yes',
                        '1',
                    ],
                    [
                        'latin1_swedish_ci',
                        'latin1',
                        '8',
                        'Yes',
                        'Yes',
                        '1',
                    ],
                ],
            ],
            [
                'query'  => 'SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES`'
                    . ' WHERE `TABLE_SCHEMA`=\'pma_test\' AND `TABLE_TYPE` IN (\'BASE TABLE\', \'SYSTEM VERSIONED\')',
                'result' => [],
            ],
            [
                'query'   => 'SELECT `column_name`, `mimetype`, `transformation`,'
                    . ' `transformation_options`, `input_transformation`,'
                    . ' `input_transformation_options`'
                    . ' FROM `pmadb`.`column_info`'
                    . ' WHERE `db_name` = \'pma_test\' AND `table_name` = \'table1\''
                    . ' AND ( `mimetype` != \'\' OR `transformation` != \'\''
                    . ' OR `transformation_options` != \'\''
                    . ' OR `input_transformation` != \'\''
                    . ' OR `input_transformation_options` != \'\')',
                'columns' => [
                    'column_name',
                    'mimetype',
                    'transformation',
                    'transformation_options',
                    'input_transformation',
                    'input_transformation_options',
                ],
                'result'  => [
                    [
                        'o',
                        'text/plain',
                        'sql',
                        '',
                        'regex',
                        '/pma/i',
                    ],
                    [
                        'col',
                        't',
                        'o/p',
                        '',
                        'i/p',
                        '',
                    ],
                ],
            ],
            [
                'query'   => 'SELECT `column_name`, `mimetype`, `transformation`,'
                    . ' `transformation_options`, `input_transformation`,'
                    . ' `input_transformation_options`'
                    . ' FROM `information_schema`.`column_info`'
                    . ' WHERE `db_name` = \'my_db\' AND `table_name` = \'test_tbl\''
                    . ' AND ( `mimetype` != \'\' OR `transformation` != \'\''
                    . ' OR `transformation_options` != \'\''
                    . ' OR `input_transformation` != \'\''
                    . ' OR `input_transformation_options` != \'\')',
                'columns' => [
                    'column_name',
                    'mimetype',
                    'transformation',
                    'transformation_options',
                    'input_transformation',
                    'input_transformation_options',
                ],
                'result'  => [
                    [
                        'vc',
                        '',
                        'output/text_plain_json.php',
                        '',
                        'Input/Text_Plain_JsonEditor.php',
                        '',
                    ],
                    [
                        'vc',
                        '',
                        'output/text_plain_formatted.php',
                        '',
                        'Text_Plain_Substring.php',
                        '1',
                    ],
                ],
            ],
            [
                'query'  => 'SELECT TABLE_NAME FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'pma_test\' AND TABLE_NAME = \'table1\'',
                'result' => [],
            ],
            [
                'query'  => 'SELECT TABLE_NAME FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'ODS_DB\' AND TABLE_NAME = \'Shop\'',
                'result' => [],
            ],
            [
                'query'  => 'SELECT TABLE_NAME FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'ODS_DB\' AND TABLE_NAME = \'pma_bookmark\'',
                'result' => [],
            ],
            [
                'query'  => 'SELECT TABLE_NAME FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'my_dataset\' AND TABLE_NAME = \'company_users\'',
                'result' => [],
            ],
            [
                'query'  => 'SELECT TABLE_NAME FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'my_db\' '
                    . 'AND TABLE_NAME = \'test_tbl\' AND IS_UPDATABLE = \'YES\'',
                'result' => [],
            ],
            [
                'query'   => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
                    . ' `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`,'
                    . ' `ENGINE` AS `Type`, `VERSION` AS `Version`,'
                    . ' `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`,'
                    . ' `AVG_ROW_LENGTH` AS `Avg_row_length`,'
                    . ' `DATA_LENGTH` AS `Data_length`,'
                    . ' `MAX_DATA_LENGTH` AS `Max_data_length`,'
                    . ' `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`,'
                    . ' `AUTO_INCREMENT` AS `Auto_increment`,'
                    . ' `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`,'
                    . ' `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`,'
                    . ' `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`,'
                    . ' `TABLE_COMMENT` AS `Comment`'
                    . ' FROM `information_schema`.`TABLES` t'
                    . ' WHERE `TABLE_SCHEMA` IN (\'pma_test\')'
                    . ' AND t.`TABLE_NAME` = \'table1\' ORDER BY Name ASC',
                'columns' => [
                    'TABLE_CATALOG',
                    'TABLE_SCHEMA',
                    'TABLE_NAME',
                    'TABLE_TYPE',
                    'ENGINE',
                    'VERSION',
                    'ROW_FORMAT',
                    'TABLE_ROWS',
                    'AVG_ROW_LENGTH',
                    'DATA_LENGTH',
                    'MAX_DATA_LENGTH',
                    'INDEX_LENGTH',
                    'DATA_FREE',
                    'AUTO_INCREMENT',
                    'CREATE_TIME',
                    'UPDATE_TIME',
                    'CHECK_TIME',
                    'TABLE_COLLATION',
                    'CHECKSUM',
                    'CREATE_OPTIONS',
                    'TABLE_COMMENT',
                    'Db',
                    'Name',
                    'TABLE_TYPE',
                    'Engine',
                    'Type',
                    'Version',
                    'Row_format',
                    'Rows',
                    'Avg_row_length',
                    'Data_length',
                    'Max_data_length',
                    'Index_length',
                    'Data_free',
                    'Auto_increment',
                    'Create_time',
                    'Update_time',
                    'Check_time',
                    'Collation',
                    'Checksum',
                    'Create_options',
                    'Comment',
                ],
                'result'  => [
                    [
                        'def',
                        'smash',
                        'issues_issue',
                        'BASE TABLE',
                        'InnoDB',
                        '10',
                        'Compact',
                        '9136',
                        '862',
                        '7880704',
                        '0',
                        '1032192',
                        '420478976',
                        '155862',
                        '2012-08-29 13:28:28',
                        'NULL',
                        'NULL',
                        'utf8_general_ci',
                        'NULL',
                        '',
                        '',
                        'smash',
                        'issues_issue',
                        'BASE TABLE',
                        'InnoDB',
                        'InnoDB',
                        '10',
                        'Compact',
                        '9136',
                        '862',
                        '7880704',
                        '0',
                        '1032192',
                        '420478976',
                        '155862',
                        '2012-08-29 13:28:28',
                        'NULL',
                        'NULL',
                        'utf8_general_ci',
                        'NULL',
                    ],
                ],
            ],
            [
                'query'   => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
                    . ' `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`,'
                    . ' `ENGINE` AS `Type`, `VERSION` AS `Version`,'
                    . ' `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`,'
                    . ' `AVG_ROW_LENGTH` AS `Avg_row_length`,'
                    . ' `DATA_LENGTH` AS `Data_length`,'
                    . ' `MAX_DATA_LENGTH` AS `Max_data_length`,'
                    . ' `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`,'
                    . ' `AUTO_INCREMENT` AS `Auto_increment`,'
                    . ' `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`,'
                    . ' `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`,'
                    . ' `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`,'
                    . ' `TABLE_COMMENT` AS `Comment`'
                    . ' FROM `information_schema`.`TABLES` t'
                    . ' WHERE `TABLE_SCHEMA` IN (\'pma_test\')'
                    . ' AND t.`TABLE_NAME` = \'table1\' ORDER BY Name ASC',
                'columns' => [
                    'TABLE_CATALOG',
                    'TABLE_SCHEMA',
                    'TABLE_NAME',
                    'TABLE_TYPE',
                    'ENGINE',
                    'VERSION',
                    'ROW_FORMAT',
                    'TABLE_ROWS',
                    'AVG_ROW_LENGTH',
                    'DATA_LENGTH',
                    'MAX_DATA_LENGTH',
                    'INDEX_LENGTH',
                    'DATA_FREE',
                    'AUTO_INCREMENT',
                    'CREATE_TIME',
                    'UPDATE_TIME',
                    'CHECK_TIME',
                    'TABLE_COLLATION',
                    'CHECKSUM',
                    'CREATE_OPTIONS',
                    'TABLE_COMMENT',
                    'Db',
                    'Name',
                    'TABLE_TYPE',
                    'Engine',
                    'Type',
                    'Version',
                    'Row_format',
                    'Rows',
                    'Avg_row_length',
                    'Data_length',
                    'Max_data_length',
                    'Index_length',
                    'Data_free',
                    'Auto_increment',
                    'Create_time',
                    'Update_time',
                    'Check_time',
                    'Collation',
                    'Checksum',
                    'Create_options',
                    'Comment',
                ],
                'result'  => [
                    [
                        'def',
                        'smash',
                        'issues_issue',
                        'BASE TABLE',
                        'InnoDB',
                        '10',
                        'Compact',
                        '9136',
                        '862',
                        '7880704',
                        '0',
                        '1032192',
                        '420478976',
                        '155862',
                        '2012-08-29 13:28:28',
                        'NULL',
                        'NULL',
                        'utf8_general_ci',
                        'NULL',
                        '',
                        '',
                        'smash',
                        'issues_issue',
                        'BASE TABLE',
                        'InnoDB',
                        'InnoDB',
                        '10',
                        'Compact',
                        '9136',
                        '862',
                        '7880704',
                        '0',
                        '1032192',
                        '420478976',
                        '155862',
                        '2012-08-29 13:28:28',
                        'NULL',
                        'NULL',
                        'utf8_general_ci',
                        'NULL',
                    ],
                ],
            ],
            [
                'query'   => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
                    . ' `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`,'
                    . ' `ENGINE` AS `Type`, `VERSION` AS `Version`,'
                    . ' `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`,'
                    . ' `AVG_ROW_LENGTH` AS `Avg_row_length`,'
                    . ' `DATA_LENGTH` AS `Data_length`,'
                    . ' `MAX_DATA_LENGTH` AS `Max_data_length`,'
                    . ' `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`,'
                    . ' `AUTO_INCREMENT` AS `Auto_increment`,'
                    . ' `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`,'
                    . ' `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`,'
                    . ' `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`,'
                    . ' `TABLE_COMMENT` AS `Comment`'
                    . ' FROM `information_schema`.`TABLES` t'
                    . ' WHERE `TABLE_SCHEMA` IN (\'my_db\')'
                    . ' AND t.`TABLE_NAME` = \'test_tbl\' ORDER BY Name ASC',
                'columns' => [
                    'TABLE_CATALOG',
                    'TABLE_SCHEMA',
                    'TABLE_NAME',
                    'TABLE_TYPE',
                    'ENGINE',
                    'VERSION',
                    'ROW_FORMAT',
                    'TABLE_ROWS',
                    'AVG_ROW_LENGTH',
                    'DATA_LENGTH',
                    'MAX_DATA_LENGTH',
                    'INDEX_LENGTH',
                    'DATA_FREE',
                    'AUTO_INCREMENT',
                    'CREATE_TIME',
                    'UPDATE_TIME',
                    'CHECK_TIME',
                    'TABLE_COLLATION',
                    'CHECKSUM',
                    'CREATE_OPTIONS',
                    'TABLE_COMMENT',
                    'Db',
                    'Name',
                    'TABLE_TYPE',
                    'Engine',
                    'Type',
                    'Version',
                    'Row_format',
                    'Rows',
                    'Avg_row_length',
                    'Data_length',
                    'Max_data_length',
                    'Index_length',
                    'Data_free',
                    'Auto_increment',
                    'Create_time',
                    'Update_time',
                    'Check_time',
                    'Collation',
                    'Checksum',
                    'Create_options',
                    'Comment',
                ],
                'result'  => [],
            ],
            [
                'query'  => 'SELECT COUNT(*) FROM `pma_test`.`table1`',
                'result' => [[0]],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`USER_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'TRIGGER\'',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`SCHEMA_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'TRIGGER\' AND \'pma_test\''
                    . ' LIKE `TABLE_SCHEMA`',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`TABLE_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'TRIGGER\' AND \'pma_test\''
                    . ' LIKE `TABLE_SCHEMA` AND TABLE_NAME=\'table1\'',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`USER_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'EVENT\'',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`SCHEMA_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'EVENT\' AND \'pma_test\''
                    . ' LIKE `TABLE_SCHEMA`',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`TABLE_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'EVENT\''
                    . ' AND TABLE_SCHEMA=\'pma\\\\_test\' AND TABLE_NAME=\'table1\'',
                'result' => [],
            ],
            [
                'query'  => 'RENAME TABLE `pma_test`.`table1` TO `pma_test`.`table3`;',
                'result' => [],
            ],
            [
                'query'  => 'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION,'
                    . ' EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT, '
                    . 'EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER'
                    . ' FROM information_schema.TRIGGERS'
                    . ' WHERE EVENT_OBJECT_SCHEMA= \'pma_test\''
                    . ' AND EVENT_OBJECT_TABLE = \'table1\';',
                'result' => [],
            ],
            [
                'query'  => 'SHOW TABLES FROM `pma`;',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . "`SCHEMA_PRIVILEGES` WHERE GRANTEE='''pma_test''@''localhost'''"
                    . " AND PRIVILEGE_TYPE='EVENT' AND TABLE_SCHEMA='pma'",
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . "`SCHEMA_PRIVILEGES` WHERE GRANTEE='''pma_test''@''localhost'''"
                    . " AND PRIVILEGE_TYPE='TRIGGER' AND TABLE_SCHEMA='pma'",
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . "`TABLE_PRIVILEGES` WHERE GRANTEE='''pma_test''@''localhost'''"
                    . " AND PRIVILEGE_TYPE='TRIGGER' AND 'db' LIKE `TABLE_SCHEMA` AND TABLE_NAME='table'",
                'result' => [],
            ],
            [
                'query'   => 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA'
                    . ' WHERE SCHEMA_NAME = \'pma_test\' LIMIT 1',
                'columns' => ['DEFAULT_COLLATION_NAME'],
                'result'  => [
                    ['utf8_general_ci'],
                ],
            ],
            [
                'query'   => 'SELECT @@collation_database',
                'columns' => ['@@collation_database'],
                'result'  => [
                    ['bar'],
                ],
            ],
            [
                'query'  => 'SHOW TABLES FROM `phpmyadmin`',
                'result' => [],
            ],
            [
                'query'   => 'SELECT tracking_active FROM `pmadb`.`tracking`' .
                    " WHERE db_name = 'pma_test_db'" .
                    " AND table_name = 'pma_test_table'" .
                    ' ORDER BY version DESC LIMIT 1',
                'columns' => ['tracking_active'],
                'result'  => [
                    [1],
                ],
            ],
            [
                'query'  => 'SELECT tracking_active FROM `pmadb`.`tracking`' .
                    " WHERE db_name = 'pma_test_db'" .
                    " AND table_name = 'pma_test_table2'" .
                    ' ORDER BY version DESC LIMIT 1',
                'result' => [],
            ],
            [
                'query'  => 'SHOW SLAVE STATUS',
                'result' => [
                    [
                        'Slave_IO_State'              => 'running',
                        'Master_Host'                 => 'localhost',
                        'Master_User'                 => 'Master_User',
                        'Master_Port'                 => '1002',
                        'Connect_Retry'               => 'Connect_Retry',
                        'Master_Log_File'             => 'Master_Log_File',
                        'Read_Master_Log_Pos'         => 'Read_Master_Log_Pos',
                        'Relay_Log_File'              => 'Relay_Log_File',
                        'Relay_Log_Pos'               => 'Relay_Log_Pos',
                        'Relay_Master_Log_File'       => 'Relay_Master_Log_File',
                        'Slave_IO_Running'            => 'NO',
                        'Slave_SQL_Running'           => 'NO',
                        'Replicate_Do_DB'             => 'Replicate_Do_DB',
                        'Replicate_Ignore_DB'         => 'Replicate_Ignore_DB',
                        'Replicate_Do_Table'          => 'Replicate_Do_Table',
                        'Replicate_Ignore_Table'      => 'Replicate_Ignore_Table',
                        'Replicate_Wild_Do_Table'     => 'Replicate_Wild_Do_Table',
                        'Replicate_Wild_Ignore_Table' => 'Replicate_Wild_Ignore_Table',
                        'Last_Errno'                  => 'Last_Errno',
                        'Last_Error'                  => 'Last_Error',
                        'Skip_Counter'                => 'Skip_Counter',
                        'Exec_Master_Log_Pos'         => 'Exec_Master_Log_Pos',
                        'Relay_Log_Space'             => 'Relay_Log_Space',
                        'Until_Condition'             => 'Until_Condition',
                        'Until_Log_File'              => 'Until_Log_File',
                        'Until_Log_Pos'               => 'Until_Log_Pos',
                        'Master_SSL_Allowed'          => 'Master_SSL_Allowed',
                        'Master_SSL_CA_File'          => 'Master_SSL_CA_File',
                        'Master_SSL_CA_Path'          => 'Master_SSL_CA_Path',
                        'Master_SSL_Cert'             => 'Master_SSL_Cert',
                        'Master_SSL_Cipher'           => 'Master_SSL_Cipher',
                        'Master_SSL_Key'              => 'Master_SSL_Key',
                        'Seconds_Behind_Master'       => 'Seconds_Behind_Master',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW MASTER STATUS',
                'result' => [
                    [
                        'File'             => 'master-bin.000030',
                        'Position'         => '107',
                        'Binlog_Do_DB'     => 'Binlog_Do_DB',
                        'Binlog_Ignore_DB' => 'Binlog_Ignore_DB',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW GRANTS',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, '
                    . '(SELECT DB_first_level FROM ( SELECT DISTINCT '
                    . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level "
                    . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY '
                    . 'DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE('
                    . "CONCAT(DB_first_level, '_'), CONCAT(SCHEMA_NAME, '_')) "
                    . 'ORDER BY SCHEMA_NAME ASC',
                'columns' => ['SCHEMA_NAME'],
                'result' => [
                    ['test'],
                ],
            ],
            [
                'query'  => 'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX('
                    . "SCHEMA_NAME, '_', 1) DB_first_level "
                    . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t',
                'result' => [
                    [1],
                ],
            ],
            [
                'query'  => 'SELECT `PARTITION_METHOD` '
                    . 'FROM `information_schema`.`PARTITIONS` '
                    . "WHERE `TABLE_SCHEMA` = 'db' AND `TABLE_NAME` = 'table' LIMIT 1",
                'result' => [],
            ],
            [
                'query' => 'SHOW PLUGINS',
                'result' => [
                    [
                        'Name' => 'partition',
                        'Status' => 'ACTIVE',
                        'Type' => 'STORAGE ENGINE',
                        'Library' => null,
                        'License' => 'GPL',
                    ],
                ],
            ],
            [
                'query' => 'SELECT * FROM information_schema.PLUGINS ORDER BY PLUGIN_TYPE, PLUGIN_NAME',
                'result' => [
                    [
                        'PLUGIN_NAME' => 'BLACKHOLE',
                        'PLUGIN_VERSION' => '1.0',
                        'PLUGIN_STATUS' => 'ACTIVE',
                        'PLUGIN_TYPE' => 'STORAGE ENGINE',
                        'PLUGIN_TYPE_VERSION' => '100316.0',
                        'PLUGIN_LIBRARY' => 'ha_blackhole.so',
                        'PLUGIN_LIBRARY_VERSION' => '1.13',
                        'PLUGIN_AUTHOR' => 'MySQL AB',
                        'PLUGIN_DESCRIPTION' => '/dev/null storage engine (anything you write to it disappears)',
                        'PLUGIN_LICENSE' => 'GPL',
                        'LOAD_OPTION' => 'ON',
                        'PLUGIN_MATURITY' => 'Stable',
                        'PLUGIN_AUTH_VERSION' => '1.0',
                    ],
                ],
            ],
            [
                'query'  => "SHOW FULL TABLES FROM `default` WHERE `Table_type` IN('BASE TABLE', 'SYSTEM VERSIONED')",
                'result' => [
                    [
                        'test1',
                        'BASE TABLE',
                    ],
                    [
                        'test2',
                        'BASE TABLE',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW FULL TABLES FROM `default` '
                    . "WHERE `Table_type` NOT IN('BASE TABLE', 'SYSTEM VERSIONED')",
                'result' => [],
            ],
            [
                'query'  => "SHOW FUNCTION STATUS WHERE `Db`='default'",
                'result' => [['Name' => 'testFunction']],
            ],
            [
                'query'  => "SHOW PROCEDURE STATUS WHERE `Db`='default'",
                'result' => [],
            ],
            [
                'query'  => 'SHOW EVENTS FROM `default`',
                'result' => [],
            ],
            [
                'query'  => 'FLUSH PRIVILEGES',
                'result' => [],
            ],
            [
                'query'  => 'SELECT * FROM `mysql`.`db` LIMIT 1',
                'result' => [],
            ],
            [
                'query'  => 'SELECT * FROM `mysql`.`columns_priv` LIMIT 1',
                'result' => [],
            ],
            [
                'query'  => 'SELECT * FROM `mysql`.`tables_priv` LIMIT 1',
                'result' => [],
            ],
            [
                'query'  => 'SELECT * FROM `mysql`.`procs_priv` LIMIT 1',
                'result' => [],
            ],
            [
                'query' => 'DELETE FROM `mysql`.`db` WHERE `host` = "" '
                    . 'AND `Db` = "" AND `User` = ""',
                'result' => true,
            ],
            [
                'query' => 'DELETE FROM `mysql`.`columns_priv` WHERE '
                    . '`host` = "" AND `Db` = "" AND `User` = ""',
                'result' => true,
            ],
            [
                'query' => 'DELETE FROM `mysql`.`tables_priv` WHERE '
                    . '`host` = "" AND `Db` = "" AND `User` = "" AND Table_name = ""',
                'result' => true,
            ],
            [
                'query'  => 'DELETE FROM `mysql`.`procs_priv` WHERE '
                    . '`host` = "" AND `Db` = "" AND `User` = "" AND `Routine_name` = "" '
                    . 'AND `Routine_type` = ""',
                'result' => true,
            ],
            [
                'query' => 'SELECT `plugin` FROM `mysql`.`user` WHERE '
                    . '`User` = "pma_username" AND `Host` = "pma_hostname" LIMIT 1',
                'result' => [],
            ],
            [
                'query'  => 'SELECT @@default_authentication_plugin',
                'result' => [
                    ['@@default_authentication_plugin' => 'mysql_native_password'],
                ],
            ],
            [
                'query'  => 'SELECT TABLE_NAME FROM information_schema.VIEWS WHERE '
                    . "TABLE_SCHEMA = 'db' AND TABLE_NAME = 'table'",
                'result' => [],
            ],
            [
                'query'  => 'SELECT *, `TABLE_SCHEMA` AS `Db`, '
                    . '`TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, '
                    . '`ENGINE` AS `Engine`, `ENGINE` AS `Type`, '
                    . '`VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, '
                    . '`TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, '
                    . '`DATA_LENGTH` AS `Data_length`, '
                    . '`MAX_DATA_LENGTH` AS `Max_data_length`, '
                    . '`INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, '
                    . '`AUTO_INCREMENT` AS `Auto_increment`, '
                    . '`CREATE_TIME` AS `Create_time`, '
                    . '`UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, '
                    . '`TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, '
                    . '`CREATE_OPTIONS` AS `Create_options`, '
                    . '`TABLE_COMMENT` AS `Comment` '
                    . 'FROM `information_schema`.`TABLES` t '
                    . "WHERE `TABLE_SCHEMA` IN ('db') "
                    . "AND t.`TABLE_NAME` = 'table' ORDER BY Name ASC",
                'result' => [],
            ],
            [
                'query'  => "SHOW TABLE STATUS FROM `db` WHERE `Name` LIKE 'table%'",
                'result' => [],
            ],
            [
                'query'  => "SHOW TABLE STATUS FROM `my_dataset` WHERE `Name` LIKE 'company\_users%'",
                'result' => [],
            ],
            [
                'query'  => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
                . ' `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`,'
                . ' `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`,'
                . ' `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`,'
                . ' `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`,'
                . ' `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`,'
                . ' `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`,'
                . ' `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`,'
                . ' `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`,'
                . ' `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment`'
                . " FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` IN ('table1')"
                . " AND t.`TABLE_NAME` = 'pma_test' ORDER BY Name ASC",
                'columns' => [
                    'TABLE_CATALOG',
                    'TABLE_SCHEMA',
                    'TABLE_NAME',
                    'TABLE_TYPE',
                    'ENGINE',
                    'VERSION',
                    'ROW_FORMAT',
                    'TABLE_ROWS',
                    'AVG_ROW_LENGTH',
                    'DATA_LENGTH',
                    'MAX_DATA_LENGTH',
                    'INDEX_LENGTH',
                    'DATA_FREE',
                    'AUTO_INCREMENT',
                    'CREATE_TIME',
                    'UPDATE_TIME',
                    'CHECK_TIME',
                    'TABLE_COLLATION',
                    'CHECKSUM',
                    'CREATE_OPTIONS',
                    'TABLE_COMMENT',
                    'Db',
                    'Name',
                    'TABLE_TYPE',
                    'Engine',
                    'Type',
                    'Version',
                    'Row_format',
                    'Rows',
                    'Avg_row_length',
                    'Data_length',
                    'Max_data_length',
                    'Index_length',
                    'Data_free',
                    'Auto_increment',
                    'Create_time',
                    'Update_time',
                    'Check_time',
                    'Collation',
                    'Checksum',
                    'Create_options',
                    'Comment',
                ],
                'result' => [
                    [
                        'ref',
                        'pma_test',
                        'table1',
                        'BASE TABLE',
                        'DBIdummy',
                        '11',
                        'Redundant',
                        '123456',
                        '42',
                        '21708991',
                        '281474976710655',// MyISAM
                        '2048',// MyISAM
                        '2547',
                        '5',
                        '2014-06-24 17:30:00',
                        '2018-06-25 18:35:12',
                        '2015-04-24 19:30:59',
                        'utf8mb4_general_ci',
                        '3844432963',
                        'row_format=REDUNDANT',
                        'Test comment for "table1" in \'pma_test\'',
                        'table1',
                        'DBIdummy',
                        '11',
                        'Redundant',
                        '123456',
                        '42',
                        '21708991',
                        '281474976710655',// MyISAM
                        '2048',// MyISAM
                        '2547',
                        '5',
                        '2014-06-24 17:30:00',
                        '2018-06-25 18:35:12',
                        '2015-04-24 19:30:59',
                        'utf8mb4_general_ci',
                        '3844432963',
                        'row_format=REDUNDANT',
                        'Test comment for "table1" in \'pma_test\'',
                    ],
                ],
            ],
            [
                'query'  => "SHOW TABLE STATUS FROM `table1` WHERE `Name` LIKE 'pma\_test%'",
                'columns' => [
                    'Name',
                    'TABLE_TYPE',
                    'Engine',
                    'Type',
                    'Version',
                    'Row_format',
                    'Rows',
                    'Avg_row_length',
                    'Data_length',
                    'Max_data_length',
                    'Index_length',
                    'Data_free',
                    'Auto_increment',
                    'Create_time',
                    'Update_time',
                    'Check_time',
                    'Collation',
                    'Checksum',
                    'Create_options',
                    'Comment',
                ],
                'result' => [
                    [
                        'table1',
                        'DBIdummy',
                        '11',
                        'Redundant',
                        '123456',
                        '42',
                        '21708991',
                        '281474976710655',// MyISAM
                        '2048',// MyISAM
                        '2547',
                        '5',
                        '2014-06-24 17:30:00',
                        '2018-06-25 18:35:12',
                        '2015-04-24 19:30:59',
                        'utf8mb4_general_ci',
                        '3844432963',
                        'row_format=REDUNDANT',
                        'Test comment for "table1" in \'pma_test\'',
                    ],
                ],
            ],
            [
                'query'  => 'SELECT *, CAST(BIN_NAME AS CHAR CHARACTER SET utf8) AS SCHEMA_NAME'
                    . ' FROM (SELECT BINARY s.SCHEMA_NAME AS BIN_NAME, s.DEFAULT_COLLATION_NAME'
                    . " FROM `information_schema`.SCHEMATA s WHERE `SCHEMA_NAME` LIKE 'pma_test'"
                    . ' GROUP BY BINARY s.SCHEMA_NAME, s.DEFAULT_COLLATION_NAME ORDER BY'
                    . ' BINARY `SCHEMA_NAME` ASC) a',
                'result' => [
                    [
                        'BIN_NAME' => 'pma_test',
                        'DEFAULT_COLLATION_NAME' => 'utf8mb4_general_ci',
                        'SCHEMA_NAME' => 'pma_test',
                    ],
                ],
            ],
            [
                'query' => 'SELECT *, CAST(BIN_NAME AS CHAR CHARACTER SET utf8) AS SCHEMA_NAME'
                    . ' FROM (SELECT BINARY s.SCHEMA_NAME AS BIN_NAME, s.DEFAULT_COLLATION_NAME'
                    . ' FROM `information_schema`.SCHEMATA s GROUP BY BINARY s.SCHEMA_NAME,'
                    . ' s.DEFAULT_COLLATION_NAME ORDER BY BINARY `SCHEMA_NAME` ASC) a',
                'columns' => [
                    'BIN_NAME',
                    'DEFAULT_COLLATION_NAME',
                    'SCHEMA_NAME',
                ],
                'result' => [
                    [
                        'sakila',
                        'utf8_general_ci',
                        'sakila',
                    ],
                    [
                        'employees',
                        'latin1_swedish_ci',
                        'employees',
                    ],
                ],
            ],

            [
                'query' => 'SELECT *, CAST(BIN_NAME AS CHAR CHARACTER SET utf8) AS SCHEMA_NAME'
                    . ' FROM (SELECT BINARY s.SCHEMA_NAME AS BIN_NAME, s.DEFAULT_COLLATION_NAME,'
                    . ' COUNT(t.TABLE_SCHEMA) AS SCHEMA_TABLES, SUM(t.TABLE_ROWS) AS'
                    . ' SCHEMA_TABLE_ROWS, SUM(t.DATA_LENGTH) AS SCHEMA_DATA_LENGTH,'
                    . ' SUM(t.MAX_DATA_LENGTH) AS SCHEMA_MAX_DATA_LENGTH, SUM(t.INDEX_LENGTH)'
                    . ' AS SCHEMA_INDEX_LENGTH, SUM(t.DATA_LENGTH + t.INDEX_LENGTH) AS'
                    . " SCHEMA_LENGTH, SUM(IF(t.ENGINE <> 'InnoDB', t.DATA_FREE, 0)) AS"
                    . ' SCHEMA_DATA_FREE FROM `information_schema`.SCHEMATA s LEFT JOIN'
                    . ' `information_schema`.TABLES t ON BINARY t.TABLE_SCHEMA = BINARY'
                    . ' s.SCHEMA_NAME GROUP BY BINARY s.SCHEMA_NAME,'
                    . ' s.DEFAULT_COLLATION_NAME ORDER BY `SCHEMA_TABLES` DESC) a',
                'columns' => [
                    'BIN_NAME',
                    'DEFAULT_COLLATION_NAME',
                    'SCHEMA_TABLES',
                    'SCHEMA_TABLE_ROWS',
                    'SCHEMA_DATA_LENGTH',
                    'SCHEMA_INDEX_LENGTH',
                    'SCHEMA_LENGTH',
                    'SCHEMA_DATA_FREE',
                    'SCHEMA_NAME',
                ],
                'result' => [
                    [
                        'sakila',
                        'utf8_general_ci',
                        '23',
                        '47274',
                        '4358144',
                        '2392064',
                        '6750208',
                        '0',
                        'sakila',
                    ],
                    [
                        'employees',
                        'latin1_swedish_ci',
                        '8',
                        '3912174',
                        '148111360',
                        '5816320',
                        '153927680',
                        '0',
                        'employees',
                    ],
                ],
            ],
            [
                'query'  => 'SELECT @@have_partitioning;',
                'result' => [],
            ],
            [
                'query'  => 'SELECT @@lower_case_table_names',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PLUGIN_NAME`, `PLUGIN_DESCRIPTION` '
                    . 'FROM `information_schema`.`PLUGINS` '
                    . "WHERE `PLUGIN_TYPE` = 'AUTHENTICATION';",
                'result' => [],
            ],
            [
                'query'  => 'SHOW TABLES FROM `db`;',
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM '
                    . '`INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` '
                    . "WHERE GRANTEE='''pma_test''@''localhost''' "
                    . "AND PRIVILEGE_TYPE='EVENT' AND 'db' LIKE `TABLE_SCHEMA`",
                'result' => [],
            ],
            [
                'query'  => 'SELECT `PRIVILEGE_TYPE` FROM '
                    . '`INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` '
                    . "WHERE GRANTEE='''pma_test''@''localhost''' "
                    . "AND PRIVILEGE_TYPE='TRIGGER' AND 'db' LIKE `TABLE_SCHEMA`",
                'result' => [],
            ],
            [
                'query'  => 'SELECT (COUNT(DB_first_level) DIV 100) * 100 from '
                    . "( SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) "
                    . 'DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA '
                    . "WHERE `SCHEMA_NAME` < 'db' ) t",
                'result' => [],
            ],
            [
                'query'  => 'SELECT (COUNT(DB_first_level) DIV 100) * 100 from '
                    . "( SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) "
                    . 'DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA '
                    . "WHERE `SCHEMA_NAME` < 'pma_test' ) t",
                'result' => [],
            ],
            [
                'query'  => 'SELECT `SCHEMA_NAME` FROM '
                    . '`INFORMATION_SCHEMA`.`SCHEMATA`, '
                    . '(SELECT DB_first_level FROM ( SELECT DISTINCT '
                    . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level FROM "
                    . 'INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t '
                    . 'ORDER BY DB_first_level ASC LIMIT , 100) t2 WHERE TRUE AND '
                    . "1 = LOCATE(CONCAT(DB_first_level, '_'), "
                    . "CONCAT(SCHEMA_NAME, '_')) ORDER BY SCHEMA_NAME ASC",
                'result' => [],
            ],
            [
                'query' => 'SELECT @@ndb_version_string',
                'result' => [['ndb-7.4.10']],
            ],
            [
                'query' => 'SELECT *, `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS'
                    . ' `Type`, `COLLATION_NAME` AS `Collation`, `IS_NULLABLE` AS'
                    . ' `Null`, `COLUMN_KEY` AS `Key`, `COLUMN_DEFAULT` AS `Default`,'
                    . ' `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                    . ' `COLUMN_COMMENT` AS `Comment` FROM `information_schema`.`COLUMNS`'
                    . " WHERE `TABLE_SCHEMA` = 'information_schema' AND `TABLE_NAME` = 'PMA'",
                'result' => [],
            ],
            [
                'query' => 'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME,'
                    . ' REFERENCED_COLUMN_NAME FROM information_schema.key_column_usage'
                    . " WHERE referenced_table_name IS NOT NULL AND TABLE_SCHEMA = 'test'"
                    . " AND TABLE_NAME IN ('table1','table2') AND"
                    . " REFERENCED_TABLE_NAME IN ('table1','table2');",
                'result' => [
                    [
                        'TABLE_NAME' => 'table2',
                        'COLUMN_NAME' => 'idtable2',
                        'REFERENCED_TABLE_NAME' => 'table1',
                        'REFERENCED_COLUMN_NAME' => 'idtable1',
                    ],
                ],
            ],
            [
                'query' => 'SELECT `item_name`, `item_type` FROM `pmadb`.`navigationhiding`'
                    . " WHERE `username`='user' AND `db_name`='db' AND `table_name`=''",
                'result' => [
                    [
                        'item_name' => 'tableName',
                        'item_type' => 'table',
                    ],
                    [
                        'item_name' => 'viewName',
                        'item_type' => 'view',
                    ],
                ],
            ],
            [
                'query' => 'SELECT `Table_priv` FROM `mysql`.`tables_priv` WHERE `User` ='
                    . ' \'PMA_username\' AND `Host` = \'PMA_hostname\' AND `Db` ='
                    . ' \'PMA_db\' AND `Table_name` = \'PMA_table\';',
                'result' => [
                    ['Table_priv' => 'Select,Insert,Update,References,Create View,Show view'],
                ],
            ],
            [
                'query' => 'SHOW COLUMNS FROM `my_db`.`test_tbl`',
                'result' => [],
            ],
            [
                'query' => 'SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';',
                'result' => [
                    ['Type' => 'set(\'Select\',\'Insert\',\'Update\',\'References\',\'Create View\',\'Show view\')'],
                ],
            ],
            [
                'query' => 'SHOW COLUMNS FROM `PMA_db`.`PMA_table`;',
                'columns' => [
                    'Field',
                    'Type',
                    'Null',
                    'Key',
                    'Default',
                    'Extra',
                ],
                'result' => [
                    [
                        'id',
                        'int(11)',
                        'NO',
                        'PRI',
                        null,
                        'auto_increment',
                    ],
                    [
                        'name',
                        'varchar(20)',
                        'NO',
                        '',
                        null,
                        '',
                    ],
                    [
                        'datetimefield',
                        'datetime',
                        'NO',
                        '',
                        null,
                        '',
                    ],
                ],
            ],
            [
                'query' => 'SELECT `Column_name`, `Column_priv` FROM `mysql`.`columns_priv`'
                    . ' WHERE `User` = \'PMA_username\' AND `Host` = \'PMA_hostname\' AND'
                    . ' `Db` = \'PMA_db\' AND `Table_name` = \'PMA_table\';',
                'columns' => [
                    'Column_name',
                    'Column_priv',
                ],
                'result' => [
                    [
                        'id',
                        'Select',
                    ],
                    [
                        'name',
                        'Select',
                    ],
                    [
                        'datetimefield',
                        'Select',
                    ],
                ],
            ],
            [
                'query' => 'SHOW GLOBAL STATUS',
                'columns' => ['Variable_name', 'Value'],
                'result' => [
                    ['Aborted_clients', '0'],
                    ['Aborted_connects', '0'],
                    ['Com_delete_multi', '0'],
                    ['Com_create_function', '0'],
                    ['Com_empty_query', '0'],
                ],
            ],
            [
                'query' => 'SHOW GLOBAL VARIABLES',
                'columns' => ['Variable_name', 'Value'],
                'result' => [
                    ['auto_increment_increment', '1'],
                    ['auto_increment_offset', '1'],
                    ['automatic_sp_privileges', 'ON'],
                    ['back_log', '50'],
                    ['big_tables', 'OFF'],
                    ['version', '8.0.2'],
                ],
            ],
            [
                'query' => 'SELECT start_time, user_host, Sec_to_Time(Sum(Time_to_Sec(query_time))) '
                    . 'as query_time, Sec_to_Time(Sum(Time_to_Sec(lock_time))) as lock_time,'
                    . ' SUM(rows_sent) AS rows_sent, SUM(rows_examined) AS rows_examined,'
                    . ' db, sql_text, COUNT(sql_text) AS \'#\' FROM `mysql`.`slow_log` WHERE'
                    . ' start_time > FROM_UNIXTIME(0) AND start_time < FROM_UNIXTIME(10) GROUP BY sql_text',
                'columns' => ['sql_text', '#'],
                'result' => [
                    ['insert sql_text', 11],
                    ['update sql_text', 10],
                ],
            ],
            [
                'query' => 'SELECT TIME(event_time) as event_time, user_host, thread_id,'
                    . ' server_id, argument, count(argument) as \'#\' FROM `mysql`.`general_log`'
                    . ' WHERE command_type=\'Query\' AND event_time > FROM_UNIXTIME(0)'
                    . ' AND event_time < FROM_UNIXTIME(10) AND argument REGEXP \'^(INSERT'
                    . '|SELECT|UPDATE|DELETE)\' GROUP by argument',
                'columns' => ['sql_text', '#', 'argument'],
                'result' => [
                    ['insert sql_text', 10, 'argument argument2'],
                    ['update sql_text', 11, 'argument3 argument4'],
                ],
            ],
            [
                'query' => 'SET PROFILING=1;',
                'result' => [],
            ],
            [
                'query' => 'query',
                'result' => [],
            ],
            [
                'query' => 'EXPLAIN query',
                'columns' => ['sql_text', '#', 'argument'],
                'result' => [
                    ['insert sql_text', 10, 'argument argument2'],
                ],
            ],
            [
                'query' => 'SELECT seq,state,duration FROM INFORMATION_SCHEMA.PROFILING WHERE QUERY_ID=1 ORDER BY seq',
                'result' => [],
            ],
            [
                'query' => 'SHOW GLOBAL VARIABLES WHERE Variable_name IN '
                    . '("general_log","slow_query_log","long_query_time","log_output")',
                'columns' => ['Variable_name', 'Value'],
                'result' => [
                    ['general_log', 'OFF'],
                    ['log_output', 'FILE'],
                    ['long_query_time', '10.000000'],
                    ['slow_query_log', 'OFF'],
                ],
            ],
            [
                'query' => 'INSERT INTO `db`.`table` (`username`, `export_type`, `template_name`, `template_data`)'
                    . ' VALUES (\'user\', \'type\', \'name\', \'data\');',
                'result' => [],
            ],
            [
                'query' => 'SELECT * FROM `db`.`table` WHERE `username` = \'user\''
                    . ' AND `export_type` = \'type\' ORDER BY `template_name`;',
                'columns' => ['id', 'username', 'export_type', 'template_name', 'template_data'],
                'result' => [
                    ['1', 'user1', 'type1', 'name1', 'data1'],
                    ['2', 'user2', 'type2', 'name2', 'data2'],
                ],
            ],
            [
                'query' => 'DELETE FROM `db`.`table` WHERE `id` = 1 AND `username` = \'user\';',
                'result' => [],
            ],
            [
                'query' => 'SELECT * FROM `db`.`table` WHERE `id` = 1 AND `username` = \'user\';',
                'columns' => ['id', 'username', 'export_type', 'template_name', 'template_data'],
                'result' => [
                    ['1', 'user1', 'type1', 'name1', 'data1'],
                ],
            ],
            [
                'query' => 'UPDATE `db`.`table` SET `template_data` = \'data\''
                    . ' WHERE `id` = 1 AND `username` = \'user\';',
                'result' => [],
            ],
            [
                'query' => 'SHOW SLAVE HOSTS',
                'columns' => ['Server_id', 'Host'],
                'result' => [
                    ['Server_id1', 'Host1'],
                    ['Server_id2', 'Host2'],
                ],
            ],
            [
                'query' => 'SHOW ALL SLAVES STATUS',
                'result' => [],
            ],
            [
                'query' => 'SHOW COLUMNS FROM `mysql`.`user`',
                'columns' => ['Field', 'Type', 'Null'],
                'result' => [['host', 'char(60)', 'NO']],
            ],
            [
                'query' => 'SHOW INDEXES FROM `mysql`.`user`',
                'result' => [],
            ],
            [
                'query' => 'SHOW INDEXES FROM `my_db`.`test_tbl`',
                'result' => [],
            ],
            [
                'query' => 'SELECT USER();',
                'result' => [],
            ],
            [
                'query' => 'SHOW PROCESSLIST',
                'columns' => ['Id', 'User', 'Host', 'db', 'Command', 'Time', 'State', 'Info'],
                'result' => [['Id1', 'User1', 'Host1', 'db1', 'Command1', 'Time1', 'State1', 'Info1']],
            ],
            [
                'query' => 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` ORDER BY `db` ASC',
                'columns' => ['Id', 'User', 'Host', 'db', 'Command', 'Time', 'State', 'Info'],
                'result' => [['Id1', 'User1', 'Host1', 'db1', 'Command1', 'Time1', 'State1', 'Info1']],
            ],
            [
                'query' => 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` ORDER BY `Host` DESC',
                'columns' => ['Id', 'User', 'Host', 'db', 'Command', 'Time', 'State', 'Info'],
                'result' => [['Id1', 'User1', 'Host1', 'db1', 'Command1', 'Time1', 'State1', 'Info1']],
            ],
            [
                'query' => 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` ORDER BY `process` DESC',
                'columns' => ['Id', 'User', 'Host', 'db', 'Command', 'Time', 'State', 'Info'],
                'result' => [['Id1', 'User1', 'Host1', 'db1', 'Command1', 'Time1', 'State1', 'Info1']],
            ],
            [
                'query' => 'SELECT UNIX_TIMESTAMP() - 36000',
                'result' => [],
            ],
            [
                'query' => 'SELECT MAX(version) FROM `pmadb`.`tracking` WHERE `db_name` = \'db\''
                    . ' AND `table_name` = \'hello_world\'',
                'columns' => ['version'],
                'result' => [['10']],
            ],
            [
                'query' => 'SELECT MAX(version) FROM `pmadb`.`tracking` WHERE `db_name` = \'db\''
                    . ' AND `table_name` = \'hello_lovely_world\'',
                'columns' => ['version'],
                'result' => [['10']],
            ],
            [
                'query' => 'SELECT MAX(version) FROM `pmadb`.`tracking` WHERE `db_name` = \'db\''
                    . ' AND `table_name` = \'hello_lovely_world2\'',
                'columns' => ['version'],
                'result' => [['10']],
            ],
            [
                'query' => 'SELECT DISTINCT db_name, table_name FROM `pmadb`.`tracking`'
                    . ' WHERE db_name = \'PMA_db\' ORDER BY db_name, table_name',
                'columns' => ['db_name', 'table_name', 'version'],
                'result' => [['PMA_db', 'PMA_table', '10']],
            ],
            [
                'query' => 'SELECT * FROM `pmadb`.`tracking` WHERE db_name = \'PMA_db\''
                    . ' AND table_name = \'PMA_table\' ORDER BY version DESC',
                'columns' => ['db_name', 'table_name', 'version', 'date_created', 'date_updated', 'tracking_active'],
                'result' => [
                    ['PMA_db', 'PMA_table', '1', 'date_created', 'date_updated', '1'],
                    ['PMA_db', 'PMA_table', '2', 'date_created', 'date_updated', '0'],
                ],
            ],
            [
                'query' => 'SELECT tracking_active FROM `pmadb`.`tracking` WHERE db_name = \'PMA_db\''
                    . ' AND table_name = \'PMA_table\' ORDER BY version DESC LIMIT 1',
                'columns' => ['tracking_active'],
                'result' => [['1']],
            ],
            [
                'query' => 'SHOW TABLE STATUS FROM `PMA_db` WHERE `Name` LIKE \'PMA\_table%\'',
                'columns' => ['Name'],
                'result' => [['PMA_table']],
            ],
            [
                'query' => 'SELECT `id` FROM `table_1` WHERE `id` > 10 AND (`id` <> 20)',
                'columns' => ['id'],
                'result' => [['11'], ['12']],
            ],
            [
                'query' => 'SELECT * FROM `table_1` WHERE `id` > 10',
                'columns' => ['column'],
                'result' => [['row1'], ['row2']],
            ],
            [
                'query' => 'SELECT * FROM `PMA`.`table_1` LIMIT 1',
                'columns' => ['column'],
                'result' => [['table']],
            ],
            [
                'query' => 'SELECT * FROM `PMA`.`table_2` LIMIT 1',
                'columns' => ['column'],
                'result' => [['table']],
            ],
            [
                'query' => 'SELECT `ENGINE` FROM `information_schema`.`tables` WHERE `table_name` = "table_1"'
                    . ' AND `table_schema` = "PMA" AND UPPER(`engine`)'
                    . ' IN ("INNODB", "FALCON", "NDB", "INFINIDB", "TOKUDB", "XTRADB", "SEQUENCE", "BDB")',
                'columns' => ['ENGINE'],
                'result' => [['INNODB']],
            ],
            [
                'query' => 'SELECT `ENGINE` FROM `information_schema`.`tables` WHERE `table_name` = "table_2"'
                    . ' AND `table_schema` = "PMA" AND UPPER(`engine`)'
                    . ' IN ("INNODB", "FALCON", "NDB", "INFINIDB", "TOKUDB", "XTRADB", "SEQUENCE", "BDB")',
                'columns' => ['ENGINE'],
                'result' => [['INNODB']],
            ],
            [
                'query' => 'SHOW BINLOG EVENTS IN \'index1\' LIMIT 3, 10',
                'columns' => ['Info', 'Log_name', 'Pos', 'Event_type', 'Orig_log_pos', 'End_log_pos', 'Server_id'],
                'result' => [
                    [
                        'index1_Info',
                        'index1_Log_name',
                        'index1_Pos',
                        'index1_Event_type',
                        'index1_Orig_log_pos',
                        'index1_End_log_pos',
                        'index1_Server_id',
                    ],
                ],
            ],
            [
                'query' => 'SHOW FULL COLUMNS FROM `testdb`.`mytable` LIKE \'\_id\'',
                'columns' => ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
                'result' => [
                    [
                        '_id',
                        'tinyint(4)',
                        null,
                        'NO',
                        '',
                        null,
                        '',
                        'select,insert,update,references',
                        '',
                    ],
                ],
            ],
            [
                'query' => 'SHOW FULL COLUMNS FROM `testdb`.`mytable`',
                'columns' => ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
                'result' => [
                    [
                        'aid',
                        'tinyint(4)',
                        null,
                        'NO',
                        'PRI',
                        null,
                        '',
                        'select,insert,update,references',
                        '',
                    ],
                    [
                        '_id',
                        'tinyint(4)',
                        null,
                        'NO',
                        '',
                        null,
                        '',
                        'select,insert,update,references',
                        '',
                    ],
                ],
            ],
            [
                'query'  => 'SHOW INDEXES FROM `testdb`.`mytable`',
                'result' => [],
            ],
            [
                'query' => 'SHOW CREATE TABLE `testdb`.`mytable`',
                'columns' => ['Table', 'Create Table'],
                'result' => [
                    [
                        'test',
                        'CREATE TABLE `test` ('
                        . '    `aid` tinyint(4) NOT NULL,'
                        . '    `_id` tinyint(4) NOT NULL,'
                        . '    PRIMARY KEY (`aid`)'
                        . ') ENGINE=InnoDB DEFAULT CHARSET=latin1',
                    ],
                ],
            ],
            [
                'query' => 'SELECT * FROM `testdb`.`mytable` LIMIT 1',
                'columns' => ['aid', '_id'],
                'result' => [
                    [
                        1,
                        1,
                    ],
                ],
            ],
            [
                'query' => 'UPDATE `test_tbl` SET `vc` = \'…zff s sf\' WHERE `test`.`ser` = 2',
                'result' => [],
            ],
            [
                'query' => 'UPDATE `test_tbl` SET `vc` = \'…ss s s\' WHERE `test`.`ser` = 1',
                'result' => [],
            ],
            [
                'query' => 'SELECT LAST_INSERT_ID();',
                'result' => [],
            ],
            [
                'query' => 'SHOW WARNINGS',
                'result' => [],
            ],
            [
                'query' => 'SELECT * FROM `information_schema`.`bookmark` WHERE dbase = \'my_db\''
                . ' AND (user = \'user\') AND `label` = \'test_tbl\' LIMIT 1',
                'result' => [],
            ],
            [
                'query' => 'SELECT `prefs` FROM `information_schema`.`table_uiprefs` WHERE `username` = \'user\''
                . ' AND `db_name` = \'my_db\' AND `table_name` = \'test_tbl\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT DATABASE()',
                'result' => [],
            ],
            [
                'query' => 'SELECT * FROM `test_tbl` LIMIT 0, 25',
                'columns' => ['vc', 'text', 'ser'],
                'result' => [
                    [
                        'sss s s  ',
                        '…z',
                        1,
                    ],
                    [
                        'zzff s sf',
                        '…zff',
                        2,
                    ],
                ],
            ],
            [
                'query' => 'SELECT @@have_profiling',
                'result' => [],
            ],
            [
                'query'  => 'SELECT TABLE_NAME FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'my_db\' AND TABLE_NAME = \'test_tbl\'',
                'result' => [],
            ],
            [
                'query' => 'SHOW FULL COLUMNS FROM `my_db`.`test_tbl`',
                'result' => [],
            ],
            [
                'query' => 'SHOW TABLE STATUS FROM `my_db` WHERE `Name` LIKE \'test\_tbl%\'',
                'result' => [],
            ],
            [
                'query' => 'SHOW CREATE TABLE `my_db`.`test_tbl`',
                'result' => [],
            ],
            [
                'query' => 'SELECT COUNT(*) FROM `my_db`.`test_tbl`',
                'result' => [],
            ],
            [
                'query' => 'SELECT `master_field`, `foreign_db`, `foreign_table`, `foreign_field`'
                . ' FROM `information_schema`.`relation`'
                . ' WHERE `master_db` = \'my_db\' AND `master_table` = \'test_tbl\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT `test_tbl`.`vc` FROM `my_db`.`test_tbl` WHERE `test`.`ser` = 2',
                'result' => [],
                'metadata' => [
                    (object) ['type' => 'string'],
                ],
            ],
            [
                'query' => 'SELECT COUNT(*) FROM (SELECT * FROM company_users WHERE not_working_count != 0 ) as cnt',
                'result' => false,
            ],
            [
                'query' => 'SELECT COUNT(*) FROM (SELECT * FROM company_users ) as cnt',
                'result' => [
                    [4],
                ],
            ],
            [
                'query' => 'SELECT COUNT(*) FROM (SELECT * FROM company_users WHERE working_count = 0 ) as cnt',
                'result' => [
                    [15],
                ],
            ],
            [
                'query' => 'SELECT COUNT(*) FROM `my_dataset`.`company_users`',
                'result' => [
                    [18],
                ],
            ],
            [
                'query' => 'SELECT COUNT(*) FROM ('
                . 'SELECT *, 1, (SELECT COUNT(*) FROM tbl1) as c1, '
                . '(SELECT 1 FROM tbl2) as c2 FROM company_users WHERE subquery_case = 0 ) as cnt',
                'result' => [
                    [42],
                ],
            ],
            [
                'query' => 'CREATE TABLE `event` SELECT DISTINCT `eventID`, `Start_time`,'
                . ' `DateOfEvent`, `NumberOfGuests`, `NameOfVenue`, `LocationOfVenue` FROM `test_tbl`;',
                'result' => [],
            ],
            [
                'query' => 'ALTER TABLE `event` ADD PRIMARY KEY(`eventID`);',
                'result' => [],
            ],
            [
                'query' => 'CREATE TABLE `table2` SELECT DISTINCT `Start_time`,'
                            . ' `TypeOfEvent`, `period` FROM `test_tbl`;',
                'result' => [],
            ],
            [
                'query' => 'ALTER TABLE `table2` ADD PRIMARY KEY(`Start_time`);',
                'result' => [],
            ],
            [
                'query' => 'DROP TABLE `test_tbl`',
                'result' => [],
            ],
            [
                'query' => 'CREATE TABLE `batch_log2` SELECT DISTINCT `ID`, `task` FROM `test_tbl`;',
                'result' => [],
            ],
            [
                'query' => 'ALTER TABLE `batch_log2` ADD PRIMARY KEY(`ID`, `task`);',
                'result' => [],
            ],
            [
                'query' => 'CREATE TABLE `table2` SELECT DISTINCT `task`, `timestamp` FROM `test_tbl`;',
                'result' => [],
            ],
            [
                'query' => 'ALTER TABLE `table2` ADD PRIMARY KEY(`task`);',
                'result' => [],
            ],
        ];
        /**
         * Current database.
         */
        $GLOBALS['dummy_db'] = '';

        /* Some basic setup for dummy driver */
        $GLOBALS['cfg']['DBG']['sql'] = false;
    }
}
