<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\Export\ExportController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\ZipExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use ReflectionProperty;
use ZipArchive;

use function file_put_contents;
use function htmlspecialchars;
use function tempnam;
use function unlink;

use const ENT_COMPAT;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_STRING;

#[CoversClass(ExportController::class)]
final class ExportControllerTest extends AbstractTestCase
{
    public function testExportController(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        Current::$lang = 'en';
        $config = Config::getInstance();
        $config->selectServer('1');
        $config->settings['Export']['sql_procedure_function'] = false;

        $dbiDummy->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['test_db']],
            ['SCHEMA_NAME'],
        );
        $dbiDummy->addResult('SET SQL_MODE=""', true);
        $dbiDummy->addResult('SET time_zone = "+00:00"', true);
        $dbiDummy->addResult('SELECT @@session.time_zone', [['SYSTEM']]);
        $dbiDummy->addResult('SET time_zone = "SYSTEM"', true);
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $dbiDummy->addResult(
            'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = \'test_db\' LIMIT 1',
            [['utf8mb4_general_ci']],
            ['DEFAULT_COLLATION_NAME'],
        );
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'test_db\' AND TABLE_NAME = \'test_table\'',
            [],
            ['TABLE_NAME'],
        );
        $dbiDummy->addResult(
            'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\') AND t.`TABLE_NAME` COLLATE utf8_bin = \'test_table\' ORDER BY Name ASC',
            [['ref', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '']],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'TABLE_TYPE', 'ENGINE', 'VERSION', 'ROW_FORMAT', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_COLLATION', 'CHECKSUM', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'MAX_INDEX_LENGTH', 'TEMPORARY', 'Db', 'Name', 'TABLE_TYPE', 'Engine', 'Type', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment'],
        );
        $dbiDummy->addResult(
            'SELECT `id`, `name`, `datetimefield` FROM `test_db`.`test_table`',
            [
                ['1', 'abcd', '2011-01-20 02:00:02'],
                ['2', 'foo', '2010-01-20 02:00:02'],
                ['3', 'Abcd', '2012-01-20 02:00:02'],
            ],
            ['id', 'name', 'datetimefield'],
            [
                FieldHelper::fromArray([
                    'type' => MYSQLI_TYPE_DECIMAL,
                    'flags' => MYSQLI_PRI_KEY_FLAG | MYSQLI_NUM_FLAG,
                    'name' => 'id',
                ]),
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING, 'name' => 'name']),
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_DATETIME, 'name' => 'datetimefield']),
            ],
        );
        $dbiDummy->addResult(
            'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT, EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA COLLATE utf8_bin= \'test_db\' AND EVENT_OBJECT_TABLE COLLATE utf8_bin = \'test_table\';',
            [],
            ['TRIGGER_SCHEMA', 'TRIGGER_NAME', 'EVENT_MANIPULATION', 'EVENT_OBJECT_TABLE', 'ACTION_TIMING', 'ACTION_STATEMENT', 'EVENT_OBJECT_SCHEMA', 'EVENT_OBJECT_TABLE', 'DEFINER'],
        );
        // phpcs:enable

        $request = $this->createPartialMock(ServerRequest::class, ['getParsedBody']);
        $request->method('getParsedBody')->willReturn([
            'db' => '',
            'table' => '',
            'export_type' => 'server',
            'export_method' => 'quick',
            'template_id' => '',
            'quick_or_custom' => 'custom',
            'what' => 'sql',
            'db_select' => ['test_db'],
            'aliases_new' => '',
            'output_format' => 'astext',
            'filename_template' => '@SERVER@',
            'remember_template' => 'on',
            'charset' => 'utf-8',
            'compression' => 'none',
            'maxsize' => '',
            'sql_include_comments' => 'y',
            'sql_header_comment' => '',
            'sql_use_transaction' => 'y',
            'sql_compatibility' => 'NONE',
            'sql_structure_or_data' => 'structure_and_data',
            'sql_create_table' => 'y',
            'sql_auto_increment' => 'y',
            'sql_create_view' => 'y',
            'sql_create_trigger' => 'y',
            'sql_backquotes' => 'y',
            'sql_type' => 'INSERT',
            'sql_insert_syntax' => 'both',
            'sql_max_query_size' => '50000',
            'sql_hex_for_binary' => 'y',
            'sql_utc_time' => 'y',
        ]);

        $expectedOutput = <<<'SQL'
            SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
            START TRANSACTION;
            SET time_zone = "+00:00";

            --
            -- Database: `test_db`
            --
            CREATE DATABASE IF NOT EXISTS `test_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
            USE `test_db`;

            -- --------------------------------------------------------

            --
            -- Table structure for table `test_table`
            --

            CREATE TABLE `test_table` (
              `id` int(11) NOT NULL,
              `name` varchar(20) NOT NULL,
              `datetimefield` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            --
            -- Dumping data for table `test_table`
            --

            INSERT INTO `test_table` (`id`, `name`, `datetimefield`) VALUES
            (1, 'abcd', '2011-01-20 02:00:02'),
            (2, 'foo', '2010-01-20 02:00:02'),
            (3, 'Abcd', '2012-01-20 02:00:02');

            --
            -- Indexes for dumped tables
            --

            --
            -- Indexes for table `test_table`
            --
            ALTER TABLE `test_table`
              ADD PRIMARY KEY (`id`);

            --
            -- AUTO_INCREMENT for dumped tables
            --

            --
            -- AUTO_INCREMENT for table `test_table`
            --
            ALTER TABLE `test_table`
              MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
            COMMIT;
            SQL;

        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config, new Clock()),
            new LanguageManager($config),
            new ThemeManager(),
        );

        $exportController = new ExportController(
            new ResponseRenderer(),
            new Export($dbi, new OutputHandler()),
            ResponseFactory::create(),
            $config,
            $userPreferencesHandler,
        );
        $response = $exportController($request);
        $output = $this->getActualOutputForAssertion();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringNotContainsString('Missing parameter: what', $output);
        self::assertStringNotContainsString('Missing parameter: export_type', $output);
        self::assertStringContainsString(htmlspecialchars($expectedOutput, ENT_COMPAT), $output);
    }

    /** @see https://github.com/phpmyadmin/phpmyadmin/issues/19213 */
    public function testWithMissingStructureOrDataParam(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        Current::$lang = 'en';
        $config = Config::getInstance();
        $config->selectServer('1');
        $config->settings['Export']['sql_procedure_function'] = false;

        $dbiDummy->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['test_db']],
            ['SCHEMA_NAME'],
        );
        $dbiDummy->addResult('SET SQL_MODE=""', true);
        $dbiDummy->addResult('SET time_zone = "+00:00"', true);
        $dbiDummy->addResult('SELECT @@session.time_zone', [['SYSTEM']]);
        $dbiDummy->addResult('SET time_zone = "SYSTEM"', true);
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $dbiDummy->addResult(
            'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = \'test_db\' LIMIT 1',
            [['utf8mb4_general_ci']],
            ['DEFAULT_COLLATION_NAME'],
        );
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'test_db\' AND TABLE_NAME = \'test_table\'',
            [],
            ['TABLE_NAME'],
        );
        $dbiDummy->addResult(
            'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\') AND t.`TABLE_NAME` COLLATE utf8_bin = \'test_table\' ORDER BY Name ASC',
            [['ref', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '']],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'TABLE_TYPE', 'ENGINE', 'VERSION', 'ROW_FORMAT', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_COLLATION', 'CHECKSUM', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'MAX_INDEX_LENGTH', 'TEMPORARY', 'Db', 'Name', 'TABLE_TYPE', 'Engine', 'Type', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment'],
        );
        $dbiDummy->addResult(
            'SELECT `id`, `name`, `datetimefield` FROM `test_db`.`test_table`',
            [
                ['1', 'abcd', '2011-01-20 02:00:02'],
                ['2', 'foo', '2010-01-20 02:00:02'],
                ['3', 'Abcd', '2012-01-20 02:00:02'],
            ],
            ['id', 'name', 'datetimefield'],
            [
                FieldHelper::fromArray([
                    'type' => MYSQLI_TYPE_DECIMAL,
                    'flags' => MYSQLI_PRI_KEY_FLAG | MYSQLI_NUM_FLAG,
                    'name' => 'id',
                ]),
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING, 'name' => 'name']),
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_DATETIME, 'name' => 'datetimefield']),
            ],
        );
        $dbiDummy->addResult(
            'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT, EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA COLLATE utf8_bin= \'test_db\' AND EVENT_OBJECT_TABLE COLLATE utf8_bin = \'test_table\';',
            [],
            ['TRIGGER_SCHEMA', 'TRIGGER_NAME', 'EVENT_MANIPULATION', 'EVENT_OBJECT_TABLE', 'ACTION_TIMING', 'ACTION_STATEMENT', 'EVENT_OBJECT_SCHEMA', 'EVENT_OBJECT_TABLE', 'DEFINER'],
        );
        // phpcs:enable

        $request = $this->createPartialMock(ServerRequest::class, ['getParsedBody']);
        $request->method('getParsedBody')->willReturn([
            'db' => '',
            'table' => '',
            'export_type' => 'server',
            'export_method' => 'quick',
            'template_id' => '',
            'quick_or_custom' => 'custom',
            'what' => 'sql',
            'db_select' => ['test_db'],
            'aliases_new' => '',
            'output_format' => 'astext',
            'filename_template' => '@SERVER@',
            'remember_template' => 'on',
            'charset' => 'utf-8',
            'compression' => 'none',
            'maxsize' => '',
            'sql_include_comments' => 'y',
            'sql_header_comment' => '',
            'sql_use_transaction' => 'y',
            'sql_compatibility' => 'NONE',
            'sql_create_table' => 'y',
            'sql_auto_increment' => 'y',
            'sql_create_view' => 'y',
            'sql_create_trigger' => 'y',
            'sql_backquotes' => 'y',
            'sql_type' => 'INSERT',
            'sql_insert_syntax' => 'both',
            'sql_max_query_size' => '50000',
            'sql_hex_for_binary' => 'y',
            'sql_utc_time' => 'y',
        ]);

        $expectedOutput = <<<'SQL'
            SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
            START TRANSACTION;
            SET time_zone = "+00:00";

            --
            -- Database: `test_db`
            --
            CREATE DATABASE IF NOT EXISTS `test_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
            USE `test_db`;

            -- --------------------------------------------------------

            --
            -- Table structure for table `test_table`
            --

            CREATE TABLE `test_table` (
              `id` int(11) NOT NULL,
              `name` varchar(20) NOT NULL,
              `datetimefield` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            --
            -- Dumping data for table `test_table`
            --

            INSERT INTO `test_table` (`id`, `name`, `datetimefield`) VALUES
            (1, 'abcd', '2011-01-20 02:00:02'),
            (2, 'foo', '2010-01-20 02:00:02'),
            (3, 'Abcd', '2012-01-20 02:00:02');

            --
            -- Indexes for dumped tables
            --

            --
            -- Indexes for table `test_table`
            --
            ALTER TABLE `test_table`
              ADD PRIMARY KEY (`id`);

            --
            -- AUTO_INCREMENT for dumped tables
            --

            --
            -- AUTO_INCREMENT for table `test_table`
            --
            ALTER TABLE `test_table`
              MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
            COMMIT;
            SQL;

        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config, new Clock()),
            new LanguageManager($config),
            new ThemeManager(),
        );

        $exportController = new ExportController(
            new ResponseRenderer(),
            new Export($dbi, new OutputHandler()),
            ResponseFactory::create(),
            $config,
            $userPreferencesHandler,
        );
        $response = $exportController($request);
        $output = $this->getActualOutputForAssertion();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringNotContainsString('Missing parameter: what', $output);
        self::assertStringNotContainsString('Missing parameter: export_type', $output);
        self::assertStringContainsString(htmlspecialchars($expectedOutput, ENT_COMPAT), $output);
    }

    public function testDownloadFile(): void
    {
        $config = new Config();
        Config::$instance = $config;
        $config->selectedServer['DisableIS'] = true;
        $config->set('SaveDir', '');

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SET SQL_MODE=""', []);
        $dbiDummy->addResult('SELECT @@session.time_zone', [['SYSTEM']]);
        $dbiDummy->addResult('SET time_zone = "+00:00"', []);
        $dbiDummy->addResult(
            'SELECT `id`, `name`, `datetimefield` FROM `test_db`.`test_table`',
            [
                ['1', 'abcd', '2011-01-20 02:00:02'],
                ['2', 'foo', '2010-01-20 02:00:02'],
                ['3', 'Abcd', '2012-01-20 02:00:02'],
            ],
            ['id', 'name', 'datetimefield'],
        );
        $dbiDummy->addResult('SET time_zone = "SYSTEM"', []);

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $parsedBody = [
            'db' => 'test_db',
            'table' => 'test_table',
            'export_type' => 'table',
            'export_method' => 'quick',
            'template_id' => '',
            'single_table' => '1',
            'quick_or_custom' => 'quick',
            'what' => 'sql',
            'allrows' => '1',
            'aliases_new' => '',
            'output_format' => 'sendit',
            'filename_template' => '@TABLE@',
            'remember_template' => 'on',
            'charset' => 'utf-8',
            'compression' => 'none',
            'maxsize' => '',
            'sql_include_comments' => 'y',
            'sql_header_comment' => '',
            'sql_use_transaction' => 'y',
            'sql_compatibility' => 'NONE',
            'sql_structure_or_data' => 'structure_and_data',
            'sql_create_table' => 'y',
            'sql_auto_increment' => 'y',
            'sql_create_view' => 'y',
            'sql_create_trigger' => 'y',
            'sql_backquotes' => 'y',
            'sql_type' => 'INSERT',
            'sql_insert_syntax' => 'both',
            'sql_max_query_size' => '50000',
            'sql_hex_for_binary' => 'y',
            'sql_utc_time' => 'y',
        ];
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody($parsedBody);

        $expected = <<<'SQL'
            SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
            START TRANSACTION;
            SET time_zone = "+00:00";


            /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
            /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
            /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
            /*!40101 SET NAMES utf8mb4 */;

            --
            -- Database: `test_db`
            --

            -- --------------------------------------------------------

            --
            -- Table structure for table `test_table`
            --

            CREATE TABLE `test_table` (
              `id` int(11) NOT NULL,
              `name` varchar(20) NOT NULL,
              `datetimefield` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            --
            -- Dumping data for table `test_table`
            --

            INSERT INTO `test_table` (`id`, `name`, `datetimefield`) VALUES
            ('1', 'abcd', '2011-01-20 02:00:02'),
            ('2', 'foo', '2010-01-20 02:00:02'),
            ('3', 'Abcd', '2012-01-20 02:00:02');

            --
            -- Triggers `test_table`
            --
            DELIMITER $$
            CREATE TRIGGER `test_trigger` AFTER INSERT ON `test_table` FOR EACH ROW BEGIN END
            $$
            DELIMITER ;

            --
            -- Indexes for dumped tables
            --

            --
            -- Indexes for table `test_table`
            --
            ALTER TABLE `test_table`
              ADD PRIMARY KEY (`id`);

            --
            -- AUTO_INCREMENT for dumped tables
            --

            --
            -- AUTO_INCREMENT for table `test_table`
            --
            ALTER TABLE `test_table`
              MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
            COMMIT;

            /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
            /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
            /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

            SQL;

        $container = ContainerBuilder::getContainer();
        $export = $container->get(Export::class);
        (new ReflectionProperty(Export::class, 'dbi'))->setValue($export, $dbi);

        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config, new Clock()),
            new LanguageManager($config),
            new ThemeManager(),
        );

        $exportController = new ExportController(
            new ResponseRenderer(),
            $export,
            ResponseFactory::create(),
            $config,
            $userPreferencesHandler,
        );
        $response = $exportController($request);

        $output = $this->getActualOutputForAssertion();

        self::assertSame('', (string) $response->getBody());
        self::assertStringStartsWith('-- phpMyAdmin SQL Dump', $output);
        self::assertStringEndsWith($expected, $output);

        $dbiDummy->assertAllQueriesConsumed();
    }

    #[RequiresPhpExtension('zip')]
    public function testDownloadFileWithCompression(): void
    {
        $config = new Config();
        Config::$instance = $config;
        $config->selectedServer['DisableIS'] = true;
        $config->set('SaveDir', '');
        $config->settings['CompressOnFly'] = false;

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SET SQL_MODE=""', []);
        $dbiDummy->addResult('SELECT @@session.time_zone', [['SYSTEM']]);
        $dbiDummy->addResult('SET time_zone = "+00:00"', []);
        $dbiDummy->addResult(
            'SELECT `id`, `name`, `datetimefield` FROM `test_db`.`test_table`',
            [
                ['1', 'abcd', '2011-01-20 02:00:02'],
                ['2', 'foo', '2010-01-20 02:00:02'],
                ['3', 'Abcd', '2012-01-20 02:00:02'],
            ],
            ['id', 'name', 'datetimefield'],
        );
        $dbiDummy->addResult('SET time_zone = "SYSTEM"', []);

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $parsedBody = [
            'db' => 'test_db',
            'table' => 'test_table',
            'export_type' => 'table',
            'export_method' => 'quick',
            'template_id' => '',
            'single_table' => '1',
            'quick_or_custom' => 'custom',
            'what' => 'sql',
            'allrows' => '1',
            'aliases_new' => '',
            'output_format' => 'sendit',
            'filename_template' => '@TABLE@',
            'remember_template' => 'on',
            'charset' => 'utf-8',
            'compression' => 'zip',
            'maxsize' => '',
            'sql_include_comments' => 'y',
            'sql_header_comment' => '',
            'sql_use_transaction' => 'y',
            'sql_compatibility' => 'NONE',
            'sql_structure_or_data' => 'structure_and_data',
            'sql_create_table' => 'y',
            'sql_auto_increment' => 'y',
            'sql_create_view' => 'y',
            'sql_create_trigger' => 'y',
            'sql_backquotes' => 'y',
            'sql_type' => 'INSERT',
            'sql_insert_syntax' => 'both',
            'sql_max_query_size' => '50000',
            'sql_hex_for_binary' => 'y',
            'sql_utc_time' => 'y',
        ];
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody($parsedBody);

        $expected = <<<'SQL'
            SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
            START TRANSACTION;
            SET time_zone = "+00:00";


            /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
            /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
            /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
            /*!40101 SET NAMES utf8mb4 */;

            --
            -- Database: `test_db`
            --

            -- --------------------------------------------------------

            --
            -- Table structure for table `test_table`
            --

            CREATE TABLE `test_table` (
              `id` int(11) NOT NULL,
              `name` varchar(20) NOT NULL,
              `datetimefield` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            --
            -- Dumping data for table `test_table`
            --

            INSERT INTO `test_table` (`id`, `name`, `datetimefield`) VALUES
            ('1', 'abcd', '2011-01-20 02:00:02'),
            ('2', 'foo', '2010-01-20 02:00:02'),
            ('3', 'Abcd', '2012-01-20 02:00:02');

            --
            -- Triggers `test_table`
            --
            DELIMITER $$
            CREATE TRIGGER `test_trigger` AFTER INSERT ON `test_table` FOR EACH ROW BEGIN END
            $$
            DELIMITER ;

            --
            -- Indexes for dumped tables
            --

            --
            -- Indexes for table `test_table`
            --
            ALTER TABLE `test_table`
              ADD PRIMARY KEY (`id`);

            --
            -- AUTO_INCREMENT for dumped tables
            --

            --
            -- AUTO_INCREMENT for table `test_table`
            --
            ALTER TABLE `test_table`
              MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
            COMMIT;

            /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
            /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
            /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

            SQL;

        $container = ContainerBuilder::getContainer();
        $export = $container->get(Export::class);
        (new ReflectionProperty(Export::class, 'dbi'))->setValue($export, $dbi);

        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config, new Clock()),
            new LanguageManager($config),
            new ThemeManager(),
        );

        $exportController = new ExportController(
            new ResponseRenderer(),
            $export,
            ResponseFactory::create(),
            $config,
            $userPreferencesHandler,
        );
        $response = $exportController($request);

        $output = (string) $response->getBody();

        $tmpFile = tempnam('./', 'exportFileTest');
        self::assertNotFalse($tmpFile);
        self::assertNotFalse(file_put_contents($tmpFile, $output), 'The temp file should be written');

        $zipExtension = new ZipExtension(new ZipArchive());
        self::assertSame(1, $zipExtension->getNumberOfFiles($tmpFile));
        $extractedFile = $zipExtension->extract($tmpFile, 'test_table.sql');
        self::assertIsString($extractedFile);
        self::assertStringStartsWith('-- phpMyAdmin SQL Dump', $extractedFile);
        self::assertStringEndsWith($expected, $extractedFile);

        $dbiDummy->assertAllQueriesConsumed();

        unset($zipExtension);
        self::assertTrue(unlink($tmpFile));
    }
}
