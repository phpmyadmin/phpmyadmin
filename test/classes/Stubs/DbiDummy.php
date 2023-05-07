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

use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Dbal\Statement;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Tests\FieldHelper;
use PHPUnit\Framework\Assert;
use stdClass;

use function addslashes;
use function array_map;
use function is_array;
use function preg_replace;
use function str_replace;
use function trim;

use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_STRING;

// phpcs:disable Generic.Files.LineLength.TooLong

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
     * First in, first out queries.
     * The results will be distributed in the fifo way
     *
     * @psalm-var array<int<0, max>, array{
     *     query: string,
     *     result: list<non-empty-list<string|float|int|null>>|bool,
     *     columns?: list<non-empty-string>,
     *     metadata?: list<FieldMetadata>,
     * }>
     */
    private array $fifoQueries = [];

    /**
     * First in, last out queries
     *
     * The results will be distributed in the fifo way
     *
     * @var string[]
     */
    private array $fifoDatabasesToSelect = [];

    /**
     * @var array<array<string,array<int,array<string,string|float|int|null>>|bool|string[]|FieldMetaData[]>>
     * @phpstan-var list<array{
     *     query: string,
     *     result: list<non-empty-list<string|float|int|null>>|bool,
     *     columns?: list<non-empty-string>,
     *     metadata?: list<FieldMetadata>,
     * }>
     */
    private array $dummyQueries = [];

    /**
     * @var string[]
     * @psalm-var non-empty-string[]
     */
    private array $fifoErrorCodes = [];

    public function __construct()
    {
        $this->init();
    }

    public function connect(Server $server): Connection|null
    {
        return new Connection(new stdClass());
    }

    /**
     * selects given database
     *
     * @param string|DatabaseName $databaseName name of db to select
     */
    public function selectDb(string|DatabaseName $databaseName, Connection $connection): bool
    {
        $databaseName = $databaseName instanceof DatabaseName
                        ? $databaseName->getName() : $databaseName;

        foreach ($this->fifoDatabasesToSelect as $key => $databaseNameItem) {
            if ($databaseNameItem !== $databaseName) {
                continue;
            }

            // It was used
            unset($this->fifoDatabasesToSelect[$key]);

            return true;
        }

        Assert::markTestIncomplete('Non expected select of database: ' . $databaseName);
    }

    public function assertAllQueriesConsumed(): void
    {
        Assert::assertSame([], $this->fifoQueries, 'Some queries were not used!');
    }

    /**
     * @psalm-return array{
     *     query: string,
     *     result: list<non-empty-list<string|float|int|null>>|bool,
     *     columns?: list<non-empty-string>,
     *     metadata?: list<FieldMetadata>,
     * }|null
     */
    private function findFifoQuery(string $query): array|null
    {
        foreach ($this->fifoQueries as $idx => $fifoQuery) {
            if ($fifoQuery['query'] !== $query) {
                continue;
            }

            unset($this->fifoQueries[$idx]);

            return $fifoQuery;
        }

        return null;
    }

    /**
     * @psalm-return array{
     *     query: string,
     *     result: list<non-empty-list<string|float|int|null>>|bool,
     *     columns?: list<non-empty-string>,
     *     metadata?: list<FieldMetadata>,
     * }|null
     */
    private function findDummyQuery(string $query): array|null
    {
        foreach ($this->dummyQueries as $found) {
            if ($found['query'] === $query) {
                return $found;
            }
        }

        return null;
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query   query to run
     * @param int    $options query options
     */
    public function realQuery(string $query, Connection $connection, int $options): DummyResult|false
    {
        $query = trim((string) preg_replace('/  */', ' ', str_replace("\n", ' ', $query)));
        $found = $this->findFifoQuery($query) ?? $this->findDummyQuery($query);
        if (! $found) {
            Assert::markTestIncomplete('Not supported query: ' . $query);
        }

        if ($found['result'] === false) {
            return false;
        }

        if (is_array($found['result'])) {
            // PMA uses mostly textual mysqli protocol. In comparison to prepared statements (binary protocol),
            // it returns all data types as strings. PMA is not ready to enable automatic cast to int/float, so
            // in our dummy class we will require values to be string or null.
            $found['result'] = array_map(static function (array $row): array {
                return array_map(static function (string|float|int|null $value): string|null {
                    return $value === null ? null : (string) $value;
                }, $row);
            }, $found['result']);
        }

        return new DummyResult($found);
    }

    /**
     * Run the multi query and output the results
     *
     * @param string $query multi query statement to execute
     */
    public function realMultiQuery(Connection $connection, string $query): bool
    {
        return false;
    }

    /**
     * Check if there are any more query results from a multi query
     */
    public function moreResults(Connection $connection): bool
    {
        return false;
    }

    /**
     * Prepare next result from multi_query
     */
    public function nextResult(Connection $connection): bool
    {
        return false;
    }

    /**
     * Store the result returned from multi query
     *
     * @return ResultInterface|false false when empty results / result set when not empty
     */
    public function storeResult(Connection $connection): ResultInterface|false
    {
        return false;
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @return string type of connection used
     */
    public function getHostInfo(Connection $connection): string
    {
        return '';
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @return int version of the MySQL protocol used
     */
    public function getProtoInfo(Connection $connection): int
    {
        return -1;
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo(): string
    {
        return 'libmysql - mysqlnd x.x.x-dev (phpMyAdmin tests)';
    }

    /**
     * Returns last error message or an empty string if no errors occurred.
     */
    public function getError(Connection $connection): string
    {
        foreach ($this->fifoErrorCodes as $i => $code) {
            unset($this->fifoErrorCodes[$i]);

            return $code;
        }

        return '';
    }

    /**
     * returns the number of rows affected by last query
     *
     * @psalm-return int|numeric-string
     */
    public function affectedRows(Connection $connection): int|string
    {
        return $GLOBALS['cached_affected_rows'] ?? 0;
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $string string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(Connection $connection, string $string): string
    {
        return addslashes($string);
    }

    public function addSelectDb(string $databaseName): void
    {
        $this->fifoDatabasesToSelect[] = $databaseName;
    }

    /**
     * Adds query result for testing
     *
     * @param string                                           $query    SQL
     * @param array<int,array<int,string|float|int|null>>|bool $result   Expected result
     * @param string[]                                         $columns  The result columns
     * @param FieldMetadata[]                                  $metadata The result metadata
     * @psalm-param list<non-empty-list<string|float|int|null>>|bool $result
     * @psalm-param list<non-empty-string> $columns
     * @psalm-param list<FieldMetadata> $metadata
     */
    public function addResult(string $query, array|bool $result, array $columns = [], array $metadata = []): void
    {
        $this->fifoQueries[] = [
            'query' => $query,
            'result' => $result,
            'columns' => $columns,
            'metadata' => $metadata,
        ];
    }

    /**
     * Adds an error or null as no error to the stack
     *
     * @psalm-param non-empty-string $code
     */
    public function addErrorCode(string $code): void
    {
        $this->fifoErrorCodes[] = $code;
    }

    public function removeDefaultResults(): void
    {
        $this->dummyQueries = [];
    }

    public function prepare(Connection $connection, string $query): Statement|null
    {
        return null;
    }

    /**
     * Returns the number of warnings from the last query.
     */
    public function getWarningCount(Connection $connection): int
    {
        return 0;
    }

    public function assertAllSelectsConsumed(): void
    {
        Assert::assertSame([], $this->fifoDatabasesToSelect, 'Some database selects were not used!');
    }

    public function assertAllErrorCodesConsumed(): void
    {
        Assert::assertSame([], $this->fifoErrorCodes, 'Some error codes were not used!');
    }

    private function init(): void
    {
        /**
         * Array of queries this "driver" supports
         */
        $this->dummyQueries = [
            ['query' => 'SELECT 1', 'result' => [['1']]],
            ['query' => 'SELECT CURRENT_USER();', 'result' => [['pma_test@localhost']]],
            ['query' => "SHOW VARIABLES LIKE 'lower_case_table_names'", 'result' => [['lower_case_table_names','1']]],
            ['query' => 'SELECT 1 FROM mysql.user LIMIT 1', 'result' => [['1']]],
            [
                'query' => 'SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                    . " WHERE `PRIVILEGE_TYPE` = 'CREATE USER'"
                    . " AND '''pma_test''@''localhost''' LIKE `GRANTEE` LIMIT 1",
                'result' => [['1']],
            ],
            [
                'query' => 'SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE`'
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
                'query' => 'SHOW MASTER LOGS',
                'columns' => ['Log_name', 'File_size'],
                'result' => [['index1', 100], ['index2', 200]],
            ],
            [
                'query' => 'SHOW STORAGE ENGINES',
                'columns' => ['Engine', 'Support', 'Comment'],
                'result' => [
                    ['dummy', 'YES', 'dummy comment'],
                    ['dummy2', 'NO', 'dummy2 comment'],
                    ['FEDERATED', 'NO', 'Federated MySQL storage engine'],
                    ['Pbxt', 'NO', 'Pbxt storage engine'],
                ],
            ],
            [
                'query' => 'SHOW STATUS WHERE Variable_name'
                    . ' LIKE \'Innodb\\_buffer\\_pool\\_%\''
                    . ' OR Variable_name = \'Innodb_page_size\';',
                'result' => [
                    ['Innodb_buffer_pool_pages_data', 0],
                    ['Innodb_buffer_pool_pages_dirty', 0],
                    ['Innodb_buffer_pool_pages_flushed', 0],
                    ['Innodb_buffer_pool_pages_free', 0],
                    ['Innodb_buffer_pool_pages_misc', 0],
                    ['Innodb_buffer_pool_pages_total', 4096],
                    ['Innodb_buffer_pool_read_ahead_rnd', 0],
                    ['Innodb_buffer_pool_read_ahead', 0],
                    ['Innodb_buffer_pool_read_ahead_evicted', 0],
                    ['Innodb_buffer_pool_read_requests', 64],
                    ['Innodb_buffer_pool_reads', 32],
                    ['Innodb_buffer_pool_wait_free', 0],
                    ['Innodb_buffer_pool_write_requests', 64],
                    ['Innodb_page_size', 16384],
                ],
            ],
            ['query' => 'SHOW ENGINE INNODB STATUS;', 'result' => false],
            ['query' => 'SELECT @@innodb_version;', 'result' => [['1.1.8']]],
            ['query' => 'SELECT @@disabled_storage_engines', 'result' => [['']]],
            ['query' => 'SHOW GLOBAL VARIABLES ;', 'result' => []],
            [
                'query' => 'SHOW GLOBAL VARIABLES LIKE \'innodb_file_per_table\';',
                'result' => [['innodb_file_per_table', 'OFF']],
            ],
            [
                'query' => 'SHOW GLOBAL VARIABLES LIKE \'innodb_file_format\';',
                'result' => [['innodb_file_format', 'Antelope']],
            ],
            ['query' => 'SELECT @@collation_server', 'result' => [['utf8_general_ci']]],
            ['query' => 'SELECT @@lc_messages;', 'result' => []],
            [
                'query' => 'SHOW SESSION VARIABLES LIKE \'FOREIGN_KEY_CHECKS\';',
                'result' => [['foreign_key_checks', 'ON']],
            ],
            ['query' => 'SHOW TABLES FROM `pma_test`;', 'result' => [['table1'], ['table2']]],
            ['query' => 'SHOW TABLES FROM `pmadb`', 'result' => [['column_info']]],
            [
                'query' => 'SHOW COLUMNS FROM `pma_test`.`table1`',
                'columns' => ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                'result' => [
                    ['i', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'],
                    ['o', 'int(11)', 'NO', 'MUL', 'NULL', ''],
                ],
            ],
            ['query' => 'SHOW INDEXES FROM `pma_test`.`table1` WHERE (Non_unique = 0)', 'result' => []],
            [
                'query' => 'SHOW COLUMNS FROM `pma_test`.`table2`',
                'columns' => ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                'result' => [
                    ['i', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'],
                    ['o', 'int(11)', 'NO', 'MUL', 'NULL', ''],
                ],
            ],
            ['query' => 'SHOW INDEXES FROM `pma_test`.`table1`', 'result' => []],
            ['query' => 'SHOW INDEXES FROM `pma_test`.`table2`', 'result' => []],
            [
                'query' => 'SHOW COLUMNS FROM `pma`.`table1`',
                'columns' => ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
                'result' => [
                    ['i', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment', 'select,insert,update,references', ''],
                    ['o', 'varchar(100)', 'NO', 'MUL', 'NULL', '', 'select,insert,update,references', ''],
                ],
            ],
            [
                'query' => 'SELECT `CHARACTER_SET_NAME` AS `Charset`,'
                    . ' `DEFAULT_COLLATE_NAME` AS `Default collation`,'
                    . ' `DESCRIPTION` AS `Description`,'
                    . ' `MAXLEN` AS `Maxlen`'
                    . ' FROM `information_schema`.`CHARACTER_SETS`',
                'columns' => ['Charset', 'Default collation', 'Description', 'Maxlen'],
                'result' => [
                    ['armscii8', 'ARMSCII-8 Armenian', 'armscii8_general_ci', '1'],
                    ['utf8', 'utf8_general_ci', 'UTF-8 Unicode', '3'],
                    ['utf8mb4', 'UTF-8 Unicode', 'utf8mb4_0900_ai_ci', '4'],
                    ['latin1', 'latin1_swedish_ci', 'cp1252 West European', '1'],
                ],
            ],
            [
                'query' => 'SELECT `COLLATION_NAME` AS `Collation`,'
                    . ' `CHARACTER_SET_NAME` AS `Charset`,'
                    . ' `ID` AS `Id`,'
                    . ' `IS_DEFAULT` AS `Default`,'
                    . ' `IS_COMPILED` AS `Compiled`,'
                    . ' `SORTLEN` AS `Sortlen`'
                    . ' FROM `information_schema`.`COLLATIONS`',
                'columns' => ['Collation', 'Charset', 'Id', 'Default', 'Compiled', 'Sortlen'],
                'result' => [
                    ['utf8mb4_general_ci', 'utf8mb4', '45', 'Yes', 'Yes', '1'],
                    ['armscii8_general_ci', 'armscii8', '32', 'Yes', 'Yes', '1'],
                    ['utf8_general_ci', 'utf8', '33', 'Yes', 'Yes', '1'],
                    ['utf8_bin', 'utf8', '83', '', 'Yes', '1'],
                    ['latin1_swedish_ci', 'latin1', '8', 'Yes', 'Yes', '1'],
                ],
            ],
            [
                'query' => 'SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES`'
                    . ' WHERE `TABLE_SCHEMA`=\'pma_test\' AND `TABLE_TYPE` IN (\'BASE TABLE\', \'SYSTEM VERSIONED\')',
                'result' => [],
            ],
            [
                'query' => 'SELECT `column_name`, `mimetype`, `transformation`,'
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
                'result' => [['o', 'text/plain', 'sql', '', 'regex', '/pma/i'], ['col', 't', 'o/p', '', 'i/p', '']],
            ],
            [
                'query' => 'SELECT `column_name`, `mimetype`, `transformation`,'
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
                'result' => [
                    ['vc', '', 'output/text_plain_json.php', '', 'Input/Text_Plain_JsonEditor.php', ''],
                    ['vc', '', 'output/text_plain_formatted.php', '', 'Text_Plain_Substring.php', '1'],
                ],
            ],
            [
                'query' => 'SELECT CONCAT(`db_name`, \'.\', `table_name`, \'.\', `column_name`) AS column_name, `mimetype`, `transformation`,'
                    . ' `transformation_options`, `input_transformation`,'
                    . ' `input_transformation_options`'
                    . ' FROM `information_schema`.`column_info`'
                    . ' WHERE `db_name` = \'my_db\' AND `table_name` = \'\''
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
                'result' => [
                    ['vc', '', 'output/text_plain_json.php', '', 'Input/Text_Plain_JsonEditor.php', ''],
                    ['vc', '', 'output/text_plain_formatted.php', '', 'Text_Plain_Substring.php', '1'],
                ],
            ],
            [
                'query' => 'SELECT 1 FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'pma_test\' AND TABLE_NAME = \'table1\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT 1 FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'ODS_DB\' AND TABLE_NAME = \'Shop\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT 1 FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'ODS_DB\' AND TABLE_NAME = \'pma_bookmark\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT 1 FROM information_schema.VIEWS'
                . ' WHERE TABLE_SCHEMA = \'ODS_DB\' AND TABLE_NAME = \'Feuille 1\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT TABLE_NAME FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'my_dataset\' AND TABLE_NAME = \'company_users\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT 1 FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'my_db\' '
                    . 'AND TABLE_NAME = \'test_tbl\' AND IS_UPDATABLE = \'YES\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
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
                    . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'pma_test\')'
                    . ' AND t.`TABLE_NAME` COLLATE utf8_bin = \'table1\' ORDER BY Name ASC',
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
                        '281474976710655', // MyISAM
                        '2048', // MyISAM
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
                        '281474976710655', // MyISAM
                        '2048', // MyISAM
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
                'query' => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
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
                    . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'my_db\')'
                    . ' AND t.`TABLE_NAME` COLLATE utf8_bin = \'test_tbl\' ORDER BY Name ASC',
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
                'result' => [],
            ],
            ['query' => 'SELECT COUNT(*) FROM `pma_test`.`table1`', 'result' => []],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`USER_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'TRIGGER\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`SCHEMA_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'TRIGGER\' AND \'pma_test\''
                    . ' LIKE `TABLE_SCHEMA`',
                'result' => [],
            ],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`TABLE_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'TRIGGER\' AND \'pma_test\''
                    . ' LIKE `TABLE_SCHEMA` AND TABLE_NAME=\'table1\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`USER_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'EVENT\'',
                'result' => [],
            ],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`SCHEMA_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'EVENT\' AND \'pma_test\''
                    . ' LIKE `TABLE_SCHEMA`',
                'result' => [],
            ],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . '`TABLE_PRIVILEGES`'
                    . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
                    . ' AND PRIVILEGE_TYPE=\'EVENT\''
                    . ' AND TABLE_SCHEMA=\'pma\\\\_test\' AND TABLE_NAME=\'table1\'',
                'result' => [],
            ],
            ['query' => 'RENAME TABLE `pma_test`.`table1` TO `pma_test`.`table3`;', 'result' => []],
            [
                'query' => 'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION,'
                    . ' EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT, '
                    . 'EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER'
                    . ' FROM information_schema.TRIGGERS'
                    . ' WHERE EVENT_OBJECT_SCHEMA= \'pma_test\''
                    . ' AND EVENT_OBJECT_TABLE = \'table1\';',
                'result' => [],
            ],
            ['query' => 'SHOW TABLES FROM `pma`;', 'result' => []],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . "`SCHEMA_PRIVILEGES` WHERE GRANTEE='''pma_test''@''localhost'''"
                    . " AND PRIVILEGE_TYPE='EVENT' AND TABLE_SCHEMA='pma'",
                'result' => [],
            ],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . "`SCHEMA_PRIVILEGES` WHERE GRANTEE='''pma_test''@''localhost'''"
                    . " AND PRIVILEGE_TYPE='TRIGGER' AND TABLE_SCHEMA='pma'",
                'result' => [],
            ],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
                    . "`TABLE_PRIVILEGES` WHERE GRANTEE='''pma_test''@''localhost'''"
                    . " AND PRIVILEGE_TYPE='TRIGGER' AND 'db' LIKE `TABLE_SCHEMA` AND TABLE_NAME='table'",
                'result' => [],
            ],
            [
                'query' => 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA'
                    . ' WHERE SCHEMA_NAME = \'pma_test\' LIMIT 1',
                'columns' => ['DEFAULT_COLLATION_NAME'],
                'result' => [['utf8_general_ci']],
            ],
            ['query' => 'SELECT @@collation_database', 'columns' => ['@@collation_database'], 'result' => [['bar']]],
            ['query' => 'SHOW TABLES FROM `phpmyadmin`', 'result' => []],
            [
                'query' => 'SELECT tracking_active FROM `pmadb`.`tracking`' .
                    " WHERE db_name = 'pma_test_db'" .
                    " AND table_name = 'pma_test_table'" .
                    ' ORDER BY version DESC LIMIT 1',
                'columns' => ['tracking_active'],
                'result' => [[1]],
            ],
            [
                'query' => 'SELECT tracking_active FROM `pmadb`.`tracking`' .
                    " WHERE db_name = 'pma_test_db'" .
                    " AND table_name = 'pma_test_table2'" .
                    ' ORDER BY version DESC LIMIT 1',
                'result' => [],
            ],
            [
                'query' => 'SHOW SLAVE STATUS',
                'columns' => [
                    'Slave_IO_State',
                    'Master_Host',
                    'Master_User',
                    'Master_Port',
                    'Connect_Retry',
                    'Master_Log_File',
                    'Read_Master_Log_Pos',
                    'Relay_Log_File',
                    'Relay_Log_Pos',
                    'Relay_Master_Log_File',
                    'Slave_IO_Running',
                    'Slave_SQL_Running',
                    'Replicate_Do_DB',
                    'Replicate_Ignore_DB',
                    'Replicate_Do_Table',
                    'Replicate_Ignore_Table',
                    'Replicate_Wild_Do_Table',
                    'Replicate_Wild_Ignore_Table',
                    'Last_Errno',
                    'Last_Error',
                    'Skip_Counter',
                    'Exec_Master_Log_Pos',
                    'Relay_Log_Space',
                    'Until_Condition',
                    'Until_Log_File',
                    'Until_Log_Pos',
                    'Master_SSL_Allowed',
                    'Master_SSL_CA_File',
                    'Master_SSL_CA_Path',
                    'Master_SSL_Cert',
                    'Master_SSL_Cipher',
                    'Master_SSL_Key',
                    'Seconds_Behind_Master',
                ],
                'result' => [
                    [
                        'running',
                        'localhost',
                        'Master_User',
                        '1002',
                        'Connect_Retry',
                        'Master_Log_File',
                        'Read_Master_Log_Pos',
                        'Relay_Log_File',
                        'Relay_Log_Pos',
                        'Relay_Master_Log_File',
                        'NO',
                        'NO',
                        'Replicate_Do_DB',
                        'Replicate_Ignore_DB',
                        'Replicate_Do_Table',
                        'Replicate_Ignore_Table',
                        'Replicate_Wild_Do_Table',
                        'Replicate_Wild_Ignore_Table',
                        'Last_Errno',
                        'Last_Error',
                        'Skip_Counter',
                        'Exec_Master_Log_Pos',
                        'Relay_Log_Space',
                        'Until_Condition',
                        'Until_Log_File',
                        'Until_Log_Pos',
                        'Master_SSL_Allowed',
                        'Master_SSL_CA_File',
                        'Master_SSL_CA_Path',
                        'Master_SSL_Cert',
                        'Master_SSL_Cipher',
                        'Master_SSL_Key',
                        'Seconds_Behind_Master',
                    ],
                ],
            ],
            [
                'query' => 'SHOW MASTER STATUS',
                'columns' => ['File', 'Position', 'Binlog_Do_DB', 'Binlog_Ignore_DB'],
                'result' => [['primary-bin.000030', '107', 'Binlog_Do_DB', 'Binlog_Ignore_DB']],
            ],
            ['query' => 'SHOW GRANTS', 'result' => []],
            [
                'query' => 'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, '
                    . '(SELECT DB_first_level FROM ( SELECT DISTINCT '
                    . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level "
                    . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY '
                    . 'DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE('
                    . "CONCAT(DB_first_level, '_'), CONCAT(SCHEMA_NAME, '_')) "
                    . 'ORDER BY SCHEMA_NAME ASC',
                'columns' => ['SCHEMA_NAME'],
                'result' => [['test']],
            ],
            [
                'query' => 'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX('
                    . "SCHEMA_NAME, '_', 1) DB_first_level "
                    . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t',
                'result' => [[1]],
            ],
            [
                'query' => 'SELECT `PARTITION_METHOD` '
                    . 'FROM `information_schema`.`PARTITIONS` '
                    . "WHERE `TABLE_SCHEMA` = 'db' AND `TABLE_NAME` = 'table' LIMIT 1",
                'result' => [],
            ],
            [
                'query' => 'SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS` WHERE '
                    . '`TABLE_SCHEMA` = \'database\' AND `TABLE_NAME` = \'no_partition_method\' LIMIT 1',
                'result' => [],
            ],
            [
                'query' => 'SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS` WHERE '
                    . '`TABLE_SCHEMA` = \'database\' AND `TABLE_NAME` = \'range_partition_method\' LIMIT 1',
                'columns' => ['PARTITION_METHOD'],
                'result' => [['RANGE']],
            ],
            [
                'query' => 'SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS` WHERE '
                    . '`TABLE_SCHEMA` = \'database\' AND `TABLE_NAME` = \'range_columns_partition_method\' LIMIT 1',
                'columns' => ['PARTITION_METHOD'],
                'result' => [['RANGE COLUMNS']],
            ],
            [
                'query' => 'SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS` WHERE '
                    . '`TABLE_SCHEMA` = \'database\' AND `TABLE_NAME` = \'list_partition_method\' LIMIT 1',
                'columns' => ['PARTITION_METHOD'],
                'result' => [['LIST']],
            ],
            [
                'query' => 'SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS` WHERE '
                    . '`TABLE_SCHEMA` = \'database\' AND `TABLE_NAME` = \'list_columns_partition_method\' LIMIT 1',
                'columns' => ['PARTITION_METHOD'],
                'result' => [['LIST COLUMNS']],
            ],
            [
                'query' => 'SHOW PLUGINS',
                'columns' => ['Name', 'Status', 'Type', 'Library', 'License'],
                'result' => [['partition', 'ACTIVE', 'STORAGE ENGINE', null, 'GPL']],
            ],
            [
                'query' => 'SELECT * FROM information_schema.PLUGINS ORDER BY PLUGIN_TYPE, PLUGIN_NAME',
                'columns' => [
                    'PLUGIN_NAME',
                    'PLUGIN_VERSION',
                    'PLUGIN_STATUS',
                    'PLUGIN_TYPE',
                    'PLUGIN_TYPE_VERSION',
                    'PLUGIN_LIBRARY',
                    'PLUGIN_LIBRARY_VERSION',
                    'PLUGIN_AUTHOR',
                    'PLUGIN_DESCRIPTION',
                    'PLUGIN_LICENSE',
                    'LOAD_OPTION',
                    'PLUGIN_MATURITY',
                    'PLUGIN_AUTH_VERSION',
                ],
                'result' => [
                    [
                        'BLACKHOLE',
                        '1.0',
                        'ACTIVE',
                        'STORAGE ENGINE',
                        '100316.0',
                        'ha_blackhole.so',
                        '1.13',
                        'MySQL AB',
                        '/dev/null storage engine (anything you write to it disappears)',
                        'GPL',
                        'ON',
                        'Stable',
                        '1.0',
                    ],
                ],
            ],
            [
                'query' => "SHOW FULL TABLES FROM `default` WHERE `Table_type` IN('BASE TABLE', 'SYSTEM VERSIONED')",
                'result' => [['test1', 'BASE TABLE'], ['test2', 'BASE TABLE']],
            ],
            [
                'query' => 'SHOW FULL TABLES FROM `default` '
                    . "WHERE `Table_type` NOT IN('BASE TABLE', 'SYSTEM VERSIONED')",
                'result' => [],
            ],
            [
                'query' => "SHOW FUNCTION STATUS WHERE `Db`='default'",
                'columns' => ['Name'],
                'result' => [['testFunction']],
            ],
            ['query' => "SHOW PROCEDURE STATUS WHERE `Db`='default'", 'result' => []],
            ['query' => 'SHOW EVENTS FROM `default`', 'result' => []],
            ['query' => 'FLUSH PRIVILEGES', 'result' => []],
            ['query' => 'SELECT * FROM `mysql`.`db` LIMIT 1', 'result' => []],
            ['query' => 'SELECT * FROM `mysql`.`columns_priv` LIMIT 1', 'result' => []],
            ['query' => 'SELECT * FROM `mysql`.`tables_priv` LIMIT 1', 'result' => []],
            ['query' => 'SELECT * FROM `mysql`.`procs_priv` LIMIT 1', 'result' => []],
            ['query' => 'DELETE FROM `mysql`.`db` WHERE `host` = "" AND `Db` = "" AND `User` = ""', 'result' => true],
            [
                'query' => 'DELETE FROM `mysql`.`columns_priv` WHERE `host` = "" AND `Db` = "" AND `User` = ""',
                'result' => true,
            ],
            [
                'query' => 'DELETE FROM `mysql`.`tables_priv` WHERE '
                    . '`host` = "" AND `Db` = "" AND `User` = "" AND Table_name = ""',
                'result' => true,
            ],
            [
                'query' => 'DELETE FROM `mysql`.`procs_priv` WHERE '
                    . '`host` = "" AND `Db` = "" AND `User` = "" AND `Routine_name` = "" '
                    . 'AND `Routine_type` = ""',
                'result' => true,
            ],
            [
                'query' => 'SELECT `plugin` FROM `mysql`.`user` WHERE '
                    . '`User` = \'pma_username\' AND `Host` = \'pma_hostname\' LIMIT 1',
                'result' => [],
            ],
            [
                'query' => 'SELECT @@default_authentication_plugin',
                'columns' => ['@@default_authentication_plugin'],
                'result' => [['mysql_native_password']],
            ],
            [
                'query' => 'SELECT TABLE_NAME FROM information_schema.VIEWS WHERE '
                    . "TABLE_SCHEMA = 'db' AND TABLE_NAME = 'table'",
                'result' => [],
            ],
            [
                'query' => 'SELECT *, `TABLE_SCHEMA` AS `Db`, '
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
            ['query' => "SHOW TABLE STATUS FROM `db` WHERE `Name` LIKE 'table%'", 'result' => []],
            [
                'query' => "SHOW TABLE STATUS FROM `my_dataset` WHERE `Name` LIKE 'company\\\\_users%'",
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
                        'company_users',
                        'DBIdummy',
                        '11',
                        'Redundant',
                        '123456',
                        '42',
                        '18',
                        '281474976710655', // MyISAM
                        '2048', // MyISAM
                        '2547',
                        '5',
                        '2014-06-24 17:30:00',
                        '2018-06-25 18:35:12',
                        '2015-04-24 19:30:59',
                        'utf8mb4_general_ci',
                        '3844432963',
                        'row_format=REDUNDANT',
                        'Test comment for "company_users" in \'my_dataset\'',
                    ],
                ],
            ],
            [
                'query' => "SHOW TABLE STATUS FROM `table1` WHERE `Name` LIKE 'pma\_test%'",
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
                        '281474976710655', // MyISAM
                        '2048', // MyISAM
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
                'query' => 'SELECT *, CAST(BIN_NAME AS CHAR CHARACTER SET utf8) AS SCHEMA_NAME'
                    . ' FROM (SELECT BINARY s.SCHEMA_NAME AS BIN_NAME, s.DEFAULT_COLLATION_NAME'
                    . " FROM `information_schema`.SCHEMATA s WHERE `SCHEMA_NAME` LIKE 'pma_test'"
                    . ' GROUP BY BINARY s.SCHEMA_NAME, s.DEFAULT_COLLATION_NAME ORDER BY'
                    . ' BINARY `SCHEMA_NAME` ASC) a',
                'columns' => ['BIN_NAME', 'DEFAULT_COLLATION_NAME', 'SCHEMA_NAME'],
                'result' => [['pma_test', 'utf8mb4_general_ci', 'pma_test']],
            ],
            [
                'query' => 'SELECT *, CAST(BIN_NAME AS CHAR CHARACTER SET utf8) AS SCHEMA_NAME'
                    . ' FROM (SELECT BINARY s.SCHEMA_NAME AS BIN_NAME, s.DEFAULT_COLLATION_NAME'
                    . ' FROM `information_schema`.SCHEMATA s GROUP BY BINARY s.SCHEMA_NAME,'
                    . ' s.DEFAULT_COLLATION_NAME ORDER BY BINARY `SCHEMA_NAME` ASC) a',
                'columns' => ['BIN_NAME', 'DEFAULT_COLLATION_NAME', 'SCHEMA_NAME'],
                'result' => [['sakila','utf8_general_ci','sakila'], ['employees','latin1_swedish_ci','employees']],
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
                    ['sakila', 'utf8_general_ci', '23', '47274', '4358144', '2392064', '6750208', '0', 'sakila'],
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
            ['query' => 'SELECT @@have_partitioning;', 'result' => []],
            ['query' => 'SELECT @@lower_case_table_names', 'result' => [['0']]],
            [
                'query' => 'SELECT `PLUGIN_NAME`, `PLUGIN_DESCRIPTION` FROM `information_schema`.`PLUGINS`'
                    . ' WHERE `PLUGIN_TYPE` = \'AUTHENTICATION\';',
                'columns' => ['PLUGIN_NAME', 'PLUGIN_DESCRIPTION'],
                'result' => [
                    ['mysql_old_password', 'Old MySQL-4.0 authentication'],
                    ['mysql_native_password', 'Native MySQL authentication'],
                    ['sha256_password', 'SHA256 password authentication'],
                    ['caching_sha2_password', 'Caching sha2 authentication'],
                    ['auth_socket', 'Unix Socket based authentication'],
                    ['unknown_auth_plugin', 'Unknown authentication'],
                ],
            ],
            ['query' => 'SHOW TABLES FROM `db`;', 'result' => []],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM '
                    . '`INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` '
                    . "WHERE GRANTEE='''pma_test''@''localhost''' "
                    . "AND PRIVILEGE_TYPE='EVENT' AND 'db' LIKE `TABLE_SCHEMA`",
                'result' => [],
            ],
            [
                'query' => 'SELECT `PRIVILEGE_TYPE` FROM '
                    . '`INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` '
                    . "WHERE GRANTEE='''pma_test''@''localhost''' "
                    . "AND PRIVILEGE_TYPE='TRIGGER' AND 'db' LIKE `TABLE_SCHEMA`",
                'result' => [],
            ],
            [
                'query' => 'SELECT (COUNT(DB_first_level) DIV 100) * 100 from '
                    . "( SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) "
                    . 'DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA '
                    . "WHERE `SCHEMA_NAME` < 'db' ) t",
                'result' => [],
            ],
            [
                'query' => 'SELECT (COUNT(DB_first_level) DIV 100) * 100 from '
                    . "( SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) "
                    . 'DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA '
                    . "WHERE `SCHEMA_NAME` < 'pma_test' ) t",
                'result' => [],
            ],
            [
                'query' => 'SELECT `SCHEMA_NAME` FROM '
                    . '`INFORMATION_SCHEMA`.`SCHEMATA`, '
                    . '(SELECT DB_first_level FROM ( SELECT DISTINCT '
                    . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level FROM "
                    . 'INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t '
                    . 'ORDER BY DB_first_level ASC LIMIT , 100) t2 WHERE TRUE AND '
                    . "1 = LOCATE(CONCAT(DB_first_level, '_'), "
                    . "CONCAT(SCHEMA_NAME, '_')) ORDER BY SCHEMA_NAME ASC",
                'result' => [],
            ],
            ['query' => 'SELECT @@ndb_version_string', 'result' => [['ndb-7.4.10']]],
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
                'columns' => ['TABLE_NAME', 'COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'],
                'result' => [['table2', 'idtable2', 'table1', 'idtable1']],
            ],
            [
                'query' => 'SELECT `item_name`, `item_type` FROM `pmadb`.`navigationhiding`'
                    . " WHERE `username`='user' AND `db_name`='db' AND `table_name`=''",
                'columns' => ['item_name', 'item_type'],
                'result' => [['tableName', 'table'], ['viewName', 'view']],
            ],
            [
                'query' => 'SELECT `Table_priv` FROM `mysql`.`tables_priv` WHERE `User` ='
                    . ' \'PMA_username\' AND `Host` = \'PMA_hostname\' AND `Db` ='
                    . ' \'PMA_db\' AND `Table_name` = \'PMA_table\';',
                'columns' => ['Table_priv'],
                'result' => [['Select,Insert,Update,References,Create View,Show view']],
            ],
            ['query' => 'SHOW COLUMNS FROM `my_db`.`test_tbl`', 'result' => []],
            [
                'query' => 'SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';',
                'columns' => ['Type'],
                'result' => [['set(\'Select\',\'Insert\',\'Update\',\'References\',\'Create View\',\'Show view\')']],
            ],
            [
                'query' => 'SHOW COLUMNS FROM `PMA_db`.`PMA_table`;',
                'columns' => ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                'result' => [
                    ['id', 'int(11)', 'NO', 'PRI', null, 'auto_increment'],
                    ['name', 'varchar(20)', 'NO', '', null, ''],
                    ['datetimefield', 'datetime', 'NO', '', null, ''],
                ],
            ],
            [
                'query' => 'SELECT `Column_name`, `Column_priv` FROM `mysql`.`columns_priv`'
                    . ' WHERE `User` = \'PMA_username\' AND `Host` = \'PMA_hostname\' AND'
                    . ' `Db` = \'PMA_db\' AND `Table_name` = \'PMA_table\';',
                'columns' => ['Column_name', 'Column_priv'],
                'result' => [['id', 'Select'], ['name', 'Select'], ['datetimefield', 'Select']],
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
                    . ' start_time > FROM_UNIXTIME(0) AND start_time < FROM_UNIXTIME(10) GROUP BY start_time, user_host, db, sql_text',
                'columns' => ['sql_text', '#'],
                'result' => [['insert sql_text', 11], ['update sql_text', 10]],
            ],
            [
                'query' => 'SELECT TIME(event_time) as event_time, user_host, thread_id,'
                    . ' server_id, argument, count(argument) as \'#\' FROM `mysql`.`general_log`'
                    . ' WHERE command_type=\'Query\' AND event_time > FROM_UNIXTIME(0)'
                    . ' AND event_time < FROM_UNIXTIME(10) AND argument REGEXP \'^(INSERT'
                    . '|SELECT|UPDATE|DELETE)\' GROUP by event_time, user_host, thread_id, server_id, argument',
                'columns' => ['sql_text', '#', 'argument'],
                'result' => [
                    ['insert sql_text', 10, 'argument argument2'],
                    ['update sql_text', 11, 'argument3 argument4'],
                ],
            ],
            ['query' => 'SET PROFILING=1;', 'result' => []],
            ['query' => 'query', 'result' => []],
            [
                'query' => 'EXPLAIN query',
                'columns' => ['sql_text', '#', 'argument'],
                'result' => [['insert sql_text', 10, 'argument argument2']],
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
                'result' => [['1', 'user1', 'type1', 'name1', 'data1'], ['2', 'user2', 'type2', 'name2', 'data2']],
            ],
            ['query' => 'DELETE FROM `db`.`table` WHERE `id` = 1 AND `username` = \'user\';', 'result' => []],
            [
                'query' => 'SELECT * FROM `db`.`table` WHERE `id` = 1 AND `username` = \'user\';',
                'columns' => ['id', 'username', 'export_type', 'template_name', 'template_data'],
                'result' => [['1', 'user1', 'type1', 'name1', 'data1']],
            ],
            [
                'query' => 'UPDATE `db`.`table` SET `template_data` = \'data\''
                    . ' WHERE `id` = 1 AND `username` = \'user\';',
                'result' => [],
            ],
            [
                'query' => 'SHOW SLAVE HOSTS',
                'columns' => ['Server_id', 'Host'],
                'result' => [['Server_id1', 'Host1'], ['Server_id2', 'Host2']],
            ],
            ['query' => 'SHOW ALL SLAVES STATUS', 'result' => []],
            [
                'query' => 'SHOW COLUMNS FROM `mysql`.`user`',
                'columns' => ['Field', 'Type', 'Null'],
                'result' => [['host', 'char(60)', 'NO']],
            ],
            ['query' => 'SHOW INDEXES FROM `mysql`.`user`', 'result' => []],
            ['query' => 'SHOW INDEXES FROM `my_db`.`test_tbl`', 'result' => []],
            ['query' => 'SELECT USER();', 'result' => []],
            [
                'query' => 'SHOW PROCESSLIST',
                'columns' => ['Id', 'User', 'Host', 'db', 'Command', 'Time', 'State', 'Info'],
                'result' => [['Id1', 'User1', 'Host1', 'db1', 'Command1', 'Time1', 'State1', 'Info1']],
            ],
            [
                'query' => 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` ORDER BY `Db` ASC',
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
            ['query' => 'SELECT UNIX_TIMESTAMP() - 36000', 'result' => []],
            [
                'query' => 'SELECT MAX(version) FROM `pmadb`.`tracking` WHERE `db_name` = \'db\''
                    . ' AND `table_name` = \'hello_world\'',
                'columns' => ['version'],
                'result' => [],
            ],
            [
                'query' => 'SELECT MAX(version) FROM `pmadb`.`tracking` WHERE `db_name` = \'db\''
                    . ' AND `table_name` = \'hello_lovely_world\'',
                'columns' => ['version'],
                'result' => [],
            ],
            [
                'query' => 'SELECT MAX(version) FROM `pmadb`.`tracking` WHERE `db_name` = \'db\''
                    . ' AND `table_name` = \'hello_lovely_world2\'',
                'columns' => ['version'],
                'result' => [['10']],
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
                'query' => 'SELECT table_name, tracking_active '
                . 'FROM ( '
                    . 'SELECT table_name, MAX(version) version '
                    . "FROM `pmadb`.`tracking` WHERE db_name = 'dummyDb' AND table_name <> '' "
                    . 'GROUP BY table_name '
                . ') filtered_tables '
                . 'JOIN `pmadb`.`tracking` USING(table_name, version)',
                'columns' => ['table_name', 'tracking_active'],
                'result' => [['0', '1'], ['actor', '0']],
            ],
            [
                'query' => 'SHOW TABLES FROM `dummyDb`;',
                'columns' => ['Tables_in_dummyDb'],
                'result' => [['0'], ['actor'], ['untrackedTable']],
            ],
            [
                'query' => 'SHOW TABLE STATUS FROM `PMA_db` WHERE `Name` LIKE \'PMA\\\\_table%\'',
                'columns' => ['Name', 'Engine'],
                'result' => [['PMA_table', 'InnoDB']],
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
            ['query' => 'SELECT * FROM `PMA`.`table_1` LIMIT 1', 'columns' => ['column'], 'result' => [['table']]],
            ['query' => 'SELECT * FROM `PMA`.`table_2` LIMIT 1', 'columns' => ['column'], 'result' => [['table']]],
            [
                'query' => 'SELECT `ENGINE` FROM `information_schema`.`tables` WHERE `table_name` = \'table_1\''
                    . ' AND `table_schema` = \'PMA\' AND UPPER(`engine`)'
                    . ' IN ("INNODB", "FALCON", "NDB", "INFINIDB", "TOKUDB", "XTRADB", "SEQUENCE", "BDB")',
                'columns' => ['ENGINE'],
                'result' => [['INNODB']],
            ],
            [
                'query' => 'SELECT `ENGINE` FROM `information_schema`.`tables` WHERE `table_name` = \'table_2\''
                    . ' AND `table_schema` = \'PMA\' AND UPPER(`engine`)'
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
                'query' => 'SHOW FULL COLUMNS FROM `testdb`.`mytable` LIKE \'\\\\_id\'',
                'columns' => ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
                'result' => [['_id', 'tinyint(4)', null, 'NO', '', null, '', 'select,insert,update,references', '']],
            ],
            [
                'query' => 'SHOW FULL COLUMNS FROM `testdb`.`mytable`',
                'columns' => ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
                'result' => [
                    ['aid', 'tinyint(4)', null, 'NO', 'PRI', null, '', 'select,insert,update,references', ''],
                    ['_id', 'tinyint(4)', null, 'NO', '', null, '', 'select,insert,update,references', ''],
                ],
            ],
            ['query' => 'SHOW INDEXES FROM `testdb`.`mytable`', 'result' => []],
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
            ['query' => 'SELECT * FROM `testdb`.`mytable` LIMIT 1', 'columns' => ['aid','_id'], 'result' => [[1,1]]],
            [
                'query' => 'SHOW CREATE TABLE `test_db`.`test_table`',
                'columns' => ['Table', 'Create Table'],
                'result' => [['test_table', 'CREATE TABLE `test_table` (' . "\n" . '  `id` int(11) NOT NULL AUTO_INCREMENT,' . "\n" . '  `name` varchar(20) NOT NULL,' . "\n" . '  `datetimefield` datetime NOT NULL,' . "\n" . '  PRIMARY KEY (`id`)' . "\n" . ') ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4']],
            ],
            [
                'query' => 'SHOW COLUMNS FROM `test_db`.`test_table`',
                'columns' => ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                'result' => [
                    ['id', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'],
                    ['name', 'varchar(20)', 'NO', '', 'NULL', ''],
                    ['datetimefield', 'datetime', 'NO', '', 'NULL', ''],
                ],
            ],
            [
                'query' => 'SHOW FULL COLUMNS FROM `test_db`.`test_table`',
                'columns' => ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                'result' => [
                    ['id', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'],
                    ['name', 'varchar(20)', 'NO', '', 'NULL', ''],
                    ['datetimefield', 'datetime', 'NO', '', 'NULL', ''],
                ],
            ],
            [
                'query' => 'DESC `test_db`.`test_table`',
                'columns' => ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                'result' => [
                    ['id', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'],
                    ['name', 'varchar(20)', 'NO', '', 'NULL', ''],
                    ['datetimefield', 'datetime', 'NO', '', 'NULL', ''],
                ],
            ],
            [
                'query' => 'SHOW TABLE STATUS FROM `test_db` WHERE `Name` LIKE \'test\\\\_table%\'',
                'columns' => ['Name', 'Engine', 'Rows'],
                'result' => [['test_table', 'InnoDB', '3']],
            ],
            [
                'query' => 'SHOW TABLE STATUS FROM `test_db` WHERE Name = \'test_table\'',
                'columns' => ['Name', 'Engine', 'Rows'],
                'result' => [['test_table', 'InnoDB', '3']],
            ],
            [
                'query' => 'SHOW INDEXES FROM `test_db`.`test_table`',
                'columns' => ['Table', 'Non_unique', 'Key_name', 'Column_name'],
                'result' => [['test_table', '0', 'PRIMARY', 'id']],
            ],
            [
                'query' => 'SHOW INDEX FROM `test_table`;',
                'columns' => ['Table', 'Non_unique', 'Key_name', 'Column_name'],
                'result' => [['test_table', '0', 'PRIMARY', 'id']],
            ],
            [
                'query' => 'SHOW TRIGGERS FROM `test_db` LIKE \'test_table\';',
                'columns' => ['Trigger', 'Event', 'Table', 'Statement', 'Timing', 'Definer'],
                'result' => [['test_trigger', 'INSERT', 'test_table', 'BEGIN END', 'AFTER', 'definer@localhost']],
            ],
            [
                'query' => 'SELECT * FROM `test_db`.`test_table_yaml`;',
                'columns' => ['id', 'name', 'datetimefield', 'textfield'],
                'metadata' => [
                    FieldHelper::fromArray(['type' => MYSQLI_TYPE_DECIMAL]),
                    FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING]),
                    FieldHelper::fromArray(['type' => MYSQLI_TYPE_DATETIME]),
                    FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING]),
                ],
                'result' => [
                    ['1', 'abcd', '2011-01-20 02:00:02', null],
                    ['2', 'foo', '2010-01-20 02:00:02', null],
                    ['3', 'Abcd', '2012-01-20 02:00:02', null],
                    ['4', 'Abcd', '2012-01-20 02:00:02', '123'],
                    ['5', 'Abcd', '2012-01-20 02:00:02', '+30.2103210000'],
                ],
            ],
            [
                'query' => 'SELECT * FROM `test_db`.`test_table`;',
                'columns' => ['id', 'name', 'datetimefield'],
                'result' => [
                    ['1', 'abcd', '2011-01-20 02:00:02'],
                    ['2', 'foo', '2010-01-20 02:00:02'],
                    ['3', 'Abcd', '2012-01-20 02:00:02'],
                ],
            ],
            [
                'query' => 'SELECT * FROM `test_db`.`test_table_complex`;',
                'columns' => ['f1', 'f2', 'f3', 'f4'],
                'result' => [
                    ['"\'"><iframe onload=alert(1)>шеллы', '0x12346857fefe', "My awesome\nText", '0xaf1234f68c57fefe'],
                    [null, null, null, null],
                    ['', '0x1', 'шеллы', '0x2'],
                ],
                'metadata' => [
                    FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING, 'charsetnr' => 33]),
                    FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING, 'charsetnr' => 63]),
                    FieldHelper::fromArray(['type' => MYSQLI_TYPE_BLOB, 'charsetnr' => 23]),
                    FieldHelper::fromArray(['type' => MYSQLI_TYPE_BLOB, 'charsetnr' => 63]),
                ],
            ],
            [
                'query' => 'SHOW PROCEDURE STATUS;',
                'columns' => ['Db', 'Name', 'Type'],
                'result' => [['test_db', 'test_proc1', 'PROCEDURE'], ['test_db', 'test_proc2', 'PROCEDURE']],
            ],
            [
                'query' => 'SHOW FUNCTION STATUS;',
                'columns' => ['Db', 'Name', 'Type'],
                'result' => [['test_db', 'test_func', 'FUNCTION']],
            ],
            [
                'query' => 'SHOW CREATE PROCEDURE `test_db`.`test_proc1`',
                'columns' => ['Procedure', 'Create Procedure'],
                'result' => [['test_proc1', 'CREATE PROCEDURE `test_proc1` (p INT) BEGIN END']],
            ],
            [
                'query' => 'SHOW CREATE PROCEDURE `test_db`.`test_proc2`',
                'columns' => ['Procedure', 'Create Procedure'],
                'result' => [['test_proc2', 'CREATE PROCEDURE `test_proc2` (p INT) BEGIN END']],
            ],
            [
                'query' => 'SHOW CREATE FUNCTION `test_db`.`test_func`',
                'columns' => ['Function', 'Create Function'],
                'result' => [['test_func', 'CREATE FUNCTION `test_func` (p INT) RETURNS int(11) BEGIN END']],
            ],
            ['query' => 'USE `test_db`', 'result' => []],
            ['query' => 'SET SQL_QUOTE_SHOW_CREATE = 0', 'result' => []],
            ['query' => 'SET SQL_QUOTE_SHOW_CREATE = 1', 'result' => []],
            ['query' => 'UPDATE `test_tbl` SET `vc` = \'…zff s sf\' WHERE `test`.`ser` = 2', 'result' => []],
            ['query' => 'UPDATE `test_tbl` SET `vc` = \'…ss s s\' WHERE `test`.`ser` = 1', 'result' => []],
            ['query' => 'SELECT LAST_INSERT_ID();', 'result' => []],
            ['query' => 'SHOW WARNINGS', 'result' => []],
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
            ['query' => 'SELECT DATABASE()', 'result' => []],
            [
                'query' => 'SELECT * FROM `test_tbl` LIMIT 0, 25',
                'columns' => ['vc', 'text', 'ser'],
                'result' => [['sss s s  ', '…z', '1'], ['zzff s sf', '…zff', '2']],
            ],
            ['query' => 'SELECT @@have_profiling', 'result' => []],
            [
                'query' => 'SELECT 1 FROM information_schema.VIEWS'
                    . ' WHERE TABLE_SCHEMA = \'my_db\' AND TABLE_NAME = \'test_tbl\'',
                'result' => [],
            ],
            ['query' => 'SHOW FULL COLUMNS FROM `my_db`.`test_tbl`', 'result' => []],
            ['query' => 'SHOW TABLE STATUS FROM `my_db` WHERE `Name` LIKE \'test\\\\_tbl%\'', 'result' => []],
            ['query' => 'SHOW CREATE TABLE `my_db`.`test_tbl`', 'result' => []],
            ['query' => 'SELECT COUNT(*) FROM `my_db`.`test_tbl`', 'result' => []],
            [
                'query' => 'SELECT `master_field`, `foreign_db`, `foreign_table`, `foreign_field`'
                . ' FROM `information_schema`.`relation`'
                . ' WHERE `master_db` = \'my_db\' AND `master_table` = \'test_tbl\'',
                'result' => [],
            ],
            ['query' => 'SELECT `test_tbl`.`vc` FROM `my_db`.`test_tbl` WHERE `test`.`ser` = 2', 'result' => []],
            [
                'query' => 'SELECT * FROM `pmadb`.`usergroups` ORDER BY `usergroup` ASC',
                'columns' => ['usergroup', 'tab', 'allowed'],
                'result' => [['user<br>group', 'server_sql', 'Y']],
            ],
            [
                'query' => 'DESCRIBE `test_table`',
                'columns' => ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                'result' => [
                    ['id', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'],
                    ['name', 'varchar(20)', 'NO', '', 'NULL', ''],
                    ['datetimefield', 'datetime', 'NO', '', 'NULL', ''],
                ],
            ],
            [
                'query' => 'SELECT `name` FROM `test_table` WHERE `id` = 4',
                'columns' => ['name'],
                'result' => [['101']],
            ],
            [
                'query' => 'SELECT * FROM `mysql`.`user` WHERE `User` = \'username\' AND `Host` = \'hostname\';',
                'columns' => ['Host', 'User', 'Password'],
                'result' => [['hostname', 'username', 'password']],
            ],
            [
                'query' => 'SELECT COUNT(*) FROM (SELECT 1 FROM company_users WHERE not_working_count != 0 ) as cnt',
                'result' => false,
            ],
            ['query' => 'SELECT COUNT(*) FROM (SELECT 1 FROM company_users ) as cnt', 'result' => [[4]]],
            [
                'query' => 'SELECT COUNT(*) FROM (SELECT 1 FROM company_users WHERE working_count = 0 ) as cnt',
                'result' => [[15]],
            ],
            ['query' => 'SELECT COUNT(*) FROM `my_dataset`.`company_users`', 'result' => [[18]]],
            [
                'query' => 'SELECT COUNT(*) FROM ('
                . 'SELECT *, 1, (SELECT COUNT(*) FROM tbl1) AS `c1`, '
                . '(SELECT 1 FROM tbl2) AS `c2` FROM company_users WHERE subquery_case = 0 ) as cnt',
                'result' => [[42]],
            ],
            [
                'query' => 'CREATE TABLE `event` SELECT DISTINCT `eventID`, `Start_time`,'
                . ' `DateOfEvent`, `NumberOfGuests`, `NameOfVenue`, `LocationOfVenue` FROM `test_tbl`;',
                'result' => [],
            ],
            ['query' => 'ALTER TABLE `event` ADD PRIMARY KEY(`eventID`);', 'result' => []],
            [
                'query' => 'CREATE TABLE `table2` SELECT DISTINCT `Start_time`,'
                            . ' `TypeOfEvent`, `period` FROM `test_tbl`;',
                'result' => [],
            ],
            ['query' => 'ALTER TABLE `table2` ADD PRIMARY KEY(`Start_time`);', 'result' => []],
            ['query' => 'DROP TABLE `test_tbl`', 'result' => []],
            ['query' => 'CREATE TABLE `batch_log2` SELECT DISTINCT `ID`, `task` FROM `test_tbl`;', 'result' => []],
            ['query' => 'ALTER TABLE `batch_log2` ADD PRIMARY KEY(`ID`, `task`);', 'result' => []],
            ['query' => 'CREATE TABLE `table2` SELECT DISTINCT `task`, `timestamp` FROM `test_tbl`;', 'result' => []],
            ['query' => 'ALTER TABLE `table2` ADD PRIMARY KEY(`task`);', 'result' => []],
            ['query' => 'CREATE DATABASE `test_db_error`;', 'result' => false],
            ['query' => 'CREATE DATABASE `test_db` DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;', 'result' => []],
            [
                'query' => 'SHOW TABLE STATUS FROM `test_db`',
                'columns' => ['Name', 'Engine', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment', 'Max_index_length', 'Temporary'],
                'result' => [
                    ['test_table', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '4', '2011-12-13 14:15:16', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N'],
                ],
            ],
            [
                'query' => 'SHOW TABLE STATUS FROM `test_db` WHERE `Name` IN (\'test_table\')',
                'columns' => ['Name', 'Engine', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment', 'Max_index_length', 'Temporary'],
                'result' => [
                    ['test_table', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '4', '2011-12-13 14:15:16', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N'],
                ],
            ],
            [
                'query' => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
                    . ' `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`,'
                    . ' `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`,'
                    . ' `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`,'
                    . ' `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`,'
                    . ' `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`,'
                    . ' `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`,'
                    . ' `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`,'
                    . ' `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`,'
                    . ' `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t'
                    . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\') ORDER BY Name ASC',
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
                    'MAX_INDEX_LENGTH',
                    'TEMPORARY',
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
                        'def',
                        'test_db',
                        'test_table',
                        'BASE TABLE',
                        'InnoDB',
                        '10',
                        'Dynamic',
                        '3',
                        '5461',
                        '16384',
                        '0',
                        '0',
                        '0',
                        '4',
                        '2011-12-13 14:15:16',
                        null,
                        null,
                        'utf8mb4_general_ci',
                        null,
                        '',
                        '',
                        '0',
                        'N',
                        'test_db',
                        'test_table',
                        'BASE TABLE',
                        'InnoDB',
                        'InnoDB',
                        '10',
                        'Dynamic',
                        '3',
                        '5461',
                        '16384',
                        '0',
                        '0',
                        '0',
                        '4',
                        '2011-12-13 14:15:16',
                        null,
                        null,
                        'utf8mb4_general_ci',
                        null,
                        '',
                        '',
                    ],
                ],
            ],
            [
                'query' => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
                    . ' `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`,'
                    . ' `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`,'
                    . ' `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`,'
                    . ' `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`,'
                    . ' `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`,'
                    . ' `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`,'
                    . ' `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`,'
                    . ' `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`,'
                    . ' `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t'
                    . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\')'
                    . ' AND t.`TABLE_NAME` COLLATE utf8_bin IN (\'test_table\') ORDER BY Name ASC',
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
                    'MAX_INDEX_LENGTH',
                    'TEMPORARY',
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
                        'def',
                        'test_db',
                        'test_table',
                        'BASE TABLE',
                        'InnoDB',
                        '10',
                        'Dynamic',
                        '3',
                        '5461',
                        '16384',
                        '0',
                        '0',
                        '0',
                        '4',
                        '2011-12-13 14:15:16',
                        null,
                        null,
                        'utf8mb4_general_ci',
                        null,
                        '',
                        '',
                        '0',
                        'N',
                        'test_db',
                        'test_table',
                        'BASE TABLE',
                        'InnoDB',
                        'InnoDB',
                        '10',
                        'Dynamic',
                        '3',
                        '5461',
                        '16384',
                        '0',
                        '0',
                        '0',
                        '4',
                        '2011-12-13 14:15:16',
                        null,
                        null,
                        'utf8mb4_general_ci',
                        null,
                        '',
                        '',
                    ],
                ],
            ],
            [
                'query' => 'SHOW TABLE STATUS FROM `pma_test` WHERE `Name` LIKE \'table1%\'',
                'columns' => [
                    'Name',
                    'Engine',
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
                    'Max_index_length',
                    'Temporary',
                ],
                'result' => [
                    [
                        'table1',
                        'InnoDB',
                        '10',
                        'Dynamic',
                        '4046',
                        '101',
                        '409600',
                        '0',
                        '114688',
                        '0',
                        '4080',
                        '2020-07-03 17:24:47',
                        null,
                        null,
                        'utf8mb4_general_ci',
                        null,
                        '',
                        '',
                        '0',
                        'N',
                    ],
                ],
            ],
            [
                'query' => "SHOW TABLE STATUS FROM `world` WHERE `Name` IN ('City', 'Country', 'CountryLanguage')",
                'columns' => [
                    'Name',
                    'Engine',
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
                    'Max_index_length',
                    'Temporary',
                ],
                'result' => [
                    [
                        'City',
                        'InnoDB',
                        '10',
                        'Dynamic',
                        '4046',
                        '101',
                        '409600',
                        '0',
                        '114688',
                        '0',
                        '4080',
                        '2020-07-03 17:24:47',
                        null,
                        null,
                        'utf8mb4_general_ci',
                        null,
                        '',
                        '',
                        '0',
                        'N',
                    ],
                    [
                        'Country',
                        'InnoDB',
                        '10',
                        'Dynamic',
                        '239',
                        '479',
                        '114688',
                        '0',
                        '0',
                        '0',
                        null,
                        '2020-07-03 17:24:47',
                        null,
                        null,
                        'utf8mb4_general_ci',
                        null,
                        '',
                        '',
                        '0',
                        'N',
                    ],
                    [
                        'CountryLanguage',
                        'InnoDB',
                        '10',
                        'Dynamic',
                        '984',
                        '99',
                        '98304',
                        '0',
                        '65536',
                        '0',
                        null,
                        '2020-07-03 17:24:47',
                        null,
                        null,
                        'utf8mb4_general_ci',
                        null,
                        '',
                        '',
                        '0',
                        'N',
                    ],
                ],
            ],
            [
                'query' => 'SHOW TABLES FROM `world`;',
                'columns' => ['Tables_in_world'],
                'result' => [['City'], ['Country'], ['CountryLanguage']],
            ],
            [
                'query' => 'SELECT COUNT(*) AS `row_count` FROM `world`.`City`',
                'columns' => ['row_count'],
                'result' => [['4079']],
            ],
            [
                'query' => 'SELECT COUNT(*) AS `row_count` FROM `world`.`Country`',
                'columns' => ['row_count'],
                'result' => [['239']],
            ],
            [
                'query' => 'SELECT COUNT(*) AS `row_count` FROM `world`.`CountryLanguage`',
                'columns' => ['row_count'],
                'result' => [['984']],
            ],
        ];

        /* Some basic setup for dummy driver */
        $GLOBALS['cfg']['DBG']['sql'] = false;
    }
}
