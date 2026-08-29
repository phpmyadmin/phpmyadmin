<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\RoutineItem;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\Database\RoutineType;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Routines::class)]
class RoutinesTest extends AbstractTestCase
{
    private Routines $routines;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $dbiDummy = $this->createDbiDummy();
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);

        $this->routines = new Routines($dbi, $config);
    }

    /**
     * Test for getDataFromRequest
     *
     * @param array<string, mixed> $in  Input
     * @param RoutineItem          $out Expected output
     */
    #[DataProvider('providerGetDataFromRequest')]
    public function testGetDataFromRequest(array $in, RoutineItem $out): void
    {
        $postData = [];
        foreach ($in as $key => $value) {
            if ($value === '') {
                continue;
            }

            $postData[$key] = $value;
        }

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody($postData);

        self::assertEquals($out, $this->routines->getDataFromRequest($request));
    }

    /**
     * Data provider for testGetDataFromRequest
     *
     * @return array<array{array<string, mixed>, RoutineItem}>
     */
    public static function providerGetDataFromRequest(): array
    {
        return [
            [
                [
                    'item_name' => '',
                    'item_original_name' => '',
                    'item_returnlength' => '',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => '',
                    'item_definition' => '',
                    'item_comment' => '',
                    'item_definer' => '',
                    'item_type' => '',
                    'item_type_toggle' => '',
                    'item_original_type' => '',
                    'item_param_dir' => '',
                    'item_param_name' => '',
                    'item_param_type' => '',
                    'item_param_length' => '',
                    'item_param_opts_num' => '',
                    'item_param_opts_text' => '',
                    'item_returntype' => '',
                    'item_isdeterministic' => '',
                    'item_securitytype' => '',
                    'item_sqldataaccess' => '',
                ],
                new RoutineItem(
                    name: '',
                    originalName: '',
                    returnLength: '',
                    returnOptsNum: '',
                    returnOptsText: '',
                    definition: '',
                    comment: '',
                    definer: '',
                    type: RoutineType::Procedure,
                    originalType: RoutineType::Procedure,
                ),
            ],
            [
                [
                    'item_name' => 'proc2',
                    'item_original_name' => 'proc',
                    'item_returnlength' => '',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => '',
                    'item_definition' => 'SELECT NULL',
                    'item_comment' => 'some text',
                    'item_definer' => 'root@localhost',
                    'item_type' => 'PROCEDURE',
                    'item_type_toggle' => 'FUNCTION',
                    'item_original_type' => 'PROCEDURE',
                    'item_param_dir' => ['IN', 'FAIL'],
                    'item_param_name' => ['bar', 'baz'],
                    'item_param_type' => ['INT', 'FAIL'],
                    'item_param_length' => ['20', ''],
                    'item_param_opts_num' => ['UNSIGNED', ''],
                    'item_param_opts_text' => ['', 'latin1'],
                    'item_returntype' => '',
                    'item_isdeterministic' => 'ON',
                    'item_securitytype' => 'INVOKER',
                    'item_sqldataaccess' => 'NO SQL',
                ],
                new RoutineItem(
                    name: 'proc2',
                    originalName: 'proc',
                    returnLength: '',
                    returnOptsNum: '',
                    returnOptsText: '',
                    definition: 'SELECT NULL',
                    comment: 'some text',
                    definer: 'root@localhost',
                    type: RoutineType::Procedure,
                    originalType: RoutineType::Procedure,
                    numParams: 2,
                    paramDir: ['IN', ''],
                    paramName: ['bar', 'baz'],
                    paramType: ['INT', ''],
                    paramLength: ['20', ''],
                    paramOptsNum: ['UNSIGNED', ''],
                    paramOptsText: ['', 'latin1'],
                    isDeterministic: true,
                    securityTypeInvoker: true,
                    sqlDataAccess: 'NO SQL',
                ),
            ],
            [
                [
                    'item_name' => 'func2',
                    'item_original_name' => 'func',
                    'item_returnlength' => '20',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => 'CHARSET utf8',
                    'item_definition' => 'SELECT NULL',
                    'item_comment' => 'some text',
                    'item_definer' => 'root@localhost',
                    'item_type' => 'FUNCTION',
                    'item_type_toggle' => 'PROCEDURE',
                    'item_original_type' => 'FUNCTION',
                    'item_param_dir' => ['', ''],
                    'item_param_name' => ['bar', 'baz'],
                    'item_param_type' => ['<s>XSS</s>', 'TEXT'],
                    'item_param_length' => ['10,10', ''],
                    'item_param_opts_num' => ['UNSIGNED', ''],
                    'item_param_opts_text' => ['', 'utf8'],
                    'item_returntype' => 'VARCHAR',
                    'item_isdeterministic' => '',
                    'item_securitytype' => 'DEFINER',
                    'item_sqldataaccess' => '',
                ],
                new RoutineItem(
                    name: 'func2',
                    originalName: 'func',
                    returnLength: '20',
                    returnOptsNum: '',
                    returnOptsText: 'CHARSET utf8',
                    definition: 'SELECT NULL',
                    comment: 'some text',
                    definer: 'root@localhost',
                    type: RoutineType::Function,
                    originalType: RoutineType::Function,
                    numParams: 2,
                    paramDir: [],
                    paramName: ['bar', 'baz'],
                    paramType: ['', 'TEXT'],
                    paramLength: ['10,10', ''],
                    paramOptsNum: ['UNSIGNED', ''],
                    paramOptsText: ['', 'utf8'],
                    returnType: 'VARCHAR',
                    securityTypeDefiner: true,
                ),
            ],
        ];
    }

    /**
     * Test for getQueryFromRequest
     *
     * @param array<string, string|array<string>> $request Request
     * @param string                              $query   Query
     * @param int                                 $numErr  Error number
     */
    #[DataProvider('providerGetQueryFromRequest')]
    public function testGetQueryFromRequest(array $request, string $query, int $numErr): void
    {
        $config = new Config();
        $dbi = self::createStub(DatabaseInterface::class);
        $dbi->types = new Types($dbi);
        $dbi->method('quoteString')->willReturnMap([
            ['foo', ConnectionType::User, "'foo'"],
            ["foo's bar", ConnectionType::User, "'foo\'s bar'"],
        ]);

        $routines = new Routines($dbi, $config);

        $_POST = $request;
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody($request);
        self::assertSame($query, $routines->getQueryFromRequest($request));
        self::assertSame($numErr, $routines->getErrorCount());
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return array<array{array<string, string|array<string>>, string, int}>
     */
    public static function providerGetQueryFromRequest(): array
    {
        return [
            // Testing success
            [
                [
                    'item_name' => 'p r o c',
                    'item_returnlength' => '',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => '',
                    'item_definition' => 'SELECT 0;',
                    'item_comment' => 'foo',
                    'item_definer' => 'me@home',
                    'item_type' => 'PROCEDURE',
                    'item_num_params' => '0',
                    'item_param_dir' => [],
                    'item_param_name' => '',
                    'item_param_type' => '',
                    'item_param_length' => '',
                    'item_param_opts_num' => '',
                    'item_param_opts_text' => '',
                    'item_returntype' => '',
                    'item_isdeterministic' => '',
                    'item_securitytype' => 'INVOKER',
                    'item_sqldataaccess' => 'NO SQL',
                ],
                'CREATE DEFINER=`me`@`home` PROCEDURE `p r o c`() COMMENT \'foo\' '
                . 'DETERMINISTIC NO SQL SQL SECURITY INVOKER SELECT 0;',
                0,
            ],
            [
                [
                    'item_name' => 'pr``oc',
                    'item_returnlength' => '',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => '',
                    'item_definition' => 'SELECT \'foobar\';',
                    'item_comment' => '',
                    'item_definer' => 'someuser@somehost',
                    'item_type' => 'PROCEDURE',
                    'item_num_params' => '2',
                    'item_param_dir' => ['IN', 'INOUT'],
                    'item_param_name' => ['pa`ram', 'par 2'],
                    'item_param_type' => ['INT', 'ENUM'],
                    'item_param_length' => ['10', '\'a\', \'b\''],
                    'item_param_opts_num' => ['ZEROFILL', ''],
                    'item_param_opts_text' => ['utf8', 'latin1'],
                    'item_returntype' => '',
                    'item_securitytype' => 'DEFINER',
                    'item_sqldataaccess' => 'foobar',
                ],
                'CREATE DEFINER=`someuser`@`somehost` PROCEDURE `pr````oc`'
                . '(IN `pa``ram` INT(10) ZEROFILL, INOUT `par 2` ENUM(\'a\', \'b\')'
                . ' CHARSET latin1) NOT DETERMINISTIC SQL SECURITY DEFINER SELECT '
                . '\'foobar\';',
                0,
            ],
            [
                [
                    'item_name' => 'func\\',
                    'item_returnlength' => '5,5',
                    'item_returnopts_num' => 'UNSIGNED ZEROFILL',
                    'item_returnopts_text' => '',
                    'item_definition' => 'SELECT \'foobar\';',
                    'item_comment' => 'foo\'s bar',
                    'item_definer' => '',
                    'item_type' => 'FUNCTION',
                    'item_num_params' => '1',
                    'item_param_dir' => [],
                    'item_param_name' => ['pa`ram'],
                    'item_param_type' => ['VARCHAR'],
                    'item_param_length' => ['45'],
                    'item_param_opts_num' => [''],
                    'item_param_opts_text' => ['latin1'],
                    'item_returntype' => 'DECIMAL',
                    'item_isdeterministic' => 'ON',
                    'item_securitytype' => 'DEFINER',
                    'item_sqldataaccess' => 'READ SQL DATA',
                ],
                'CREATE FUNCTION `func\\`(`pa``ram` VARCHAR(45) CHARSET latin1) '
                . 'RETURNS DECIMAL(5,5) UNSIGNED ZEROFILL COMMENT \'foo\\\'s bar\' '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT \'foobar\';',
                0,
            ],
            [
                [
                    'item_name' => 'func',
                    'item_returnlength' => '20',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => 'utf8',
                    'item_definition' => 'SELECT 0;',
                    'item_comment' => '',
                    'item_definer' => '',
                    'item_type' => 'FUNCTION',
                    'item_num_params' => '1',
                    'item_returntype' => 'VARCHAR',
                    'item_securitytype' => 'DEFINER',
                    'item_sqldataaccess' => 'READ SQL DATA',
                ],
                'CREATE FUNCTION `func`() RETURNS VARCHAR(20) CHARSET utf8 NOT '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT 0;',
                0,
            ],
            // Testing failures
            [
                [],
                'CREATE () NOT DETERMINISTIC ', // invalid query
                3,
            ],
            [
                [
                    'item_name' => 'proc',
                    'item_returnlength' => '',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => '',
                    'item_definition' => 'SELECT 0;',
                    'item_comment' => 'foo',
                    'item_definer' => 'mehome', // invalid definer format
                    'item_type' => 'PROCEDURE',
                    'item_num_params' => '0',
                    'item_param_dir' => '',
                    'item_param_name' => '',
                    'item_param_type' => '',
                    'item_param_length' => '',
                    'item_param_opts_num' => '',
                    'item_param_opts_text' => '',
                    'item_returntype' => '',
                    'item_isdeterministic' => '',
                    'item_securitytype' => 'INVOKER',
                    'item_sqldataaccess' => 'NO SQL',
                ],
                // valid query
                'CREATE PROCEDURE `proc`() COMMENT \'foo\' DETERMINISTIC NO SQL SQL SECURITY INVOKER SELECT 0;',
                1,
            ],
            [
                [
                    'item_name' => 'proc',
                    'item_returnlength' => '',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => '',
                    'item_definition' => 'SELECT 0;',
                    'item_comment' => '',
                    'item_definer' => '',
                    'item_type' => 'PROCEDURE',
                    'item_num_params' => '2',
                    'item_param_dir' => ['FAIL', 'INOUT'], // invalid direction
                    'item_param_name' => ['pa`ram', 'goo'],
                    'item_param_type' => ['INT', 'ENUM'],
                    'item_param_length' => ['10', ''], // missing ENUM values
                    'item_param_opts_num' => ['ZEROFILL', ''],
                    'item_param_opts_text' => ['utf8', 'latin1'],
                    'item_returntype' => '',
                    'item_securitytype' => 'DEFINER',
                    'item_sqldataaccess' => 'foobar', // invalid, will just be ignored without throwing errors
                ],
                'CREATE PROCEDURE `proc`((10) ZEROFILL, '
                . 'INOUT `goo` ENUM CHARSET latin1) NOT DETERMINISTIC '
                . 'SQL SECURITY DEFINER SELECT 0;', // invalid query
                2,
            ],
            [
                [
                    'item_name' => 'func',
                    'item_returnlength' => '', // missing length for VARCHAR
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => 'utf8',
                    'item_definition' => 'SELECT 0;',
                    'item_comment' => '',
                    'item_definer' => '',
                    'item_type' => 'FUNCTION',
                    'item_num_params' => '2',
                    'item_param_dir' => ['IN'],
                    'item_param_name' => [''], // missing name
                    'item_param_type' => ['INT'],
                    'item_param_length' => ['10'],
                    'item_param_opts_num' => ['ZEROFILL'],
                    'item_param_opts_text' => ['latin1'],
                    'item_returntype' => 'VARCHAR',
                    'item_securitytype' => 'DEFINER',
                    'item_sqldataaccess' => '',
                ],
                'CREATE FUNCTION `func`() RETURNS VARCHAR CHARSET utf8 NOT '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT 0;', // invalid query
                2,
            ],
            [
                [
                    'item_name' => 'func',
                    'item_returnlength' => '',
                    'item_returnopts_num' => '',
                    'item_returnopts_text' => '',
                    'item_definition' => 'SELECT 0;',
                    'item_comment' => '',
                    'item_definer' => '',
                    'item_type' => 'FUNCTION',
                    'item_num_params' => '0',
                    'item_returntype' => 'FAIL', // invalid return type
                    'item_securitytype' => 'DEFINER',
                    'item_sqldataaccess' => '',
                ],
                'CREATE FUNCTION `func`()  NOT DETERMINISTIC SQL SECURITY DEFINER SELECT 0;', // invalid query
                1,
            ],
        ];
    }

    public function testGetFunctionNames(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT SPECIFIC_NAME FROM information_schema.ROUTINES '
            . "WHERE ROUTINE_SCHEMA = 'test_db' AND ROUTINE_TYPE = 'FUNCTION' AND SPECIFIC_NAME != ''",
            [
                ['test_func1'],
                ['test_func2'],
            ],
            ['Name'],
        );

        $names = Routines::getNames($this->createDatabaseInterface($dbiDummy), 'test_db', RoutineType::Function);
        self::assertSame(['test_func1', 'test_func2'], $names);

        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetFunctionNamesWithEmptyReturn(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT SPECIFIC_NAME FROM information_schema.ROUTINES '
            . "WHERE ROUTINE_SCHEMA = 'test_db' AND ROUTINE_TYPE = 'FUNCTION' AND SPECIFIC_NAME != ''",
            [],
            ['Db', 'Name', 'Type'],
        );

        $names = Routines::getNames($this->createDatabaseInterface($dbiDummy), 'test_db', RoutineType::Function);
        self::assertSame([], $names);

        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetProcedureNames(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT SPECIFIC_NAME FROM information_schema.ROUTINES '
            . "WHERE ROUTINE_SCHEMA = 'test_db' AND ROUTINE_TYPE = 'PROCEDURE' AND SPECIFIC_NAME != ''",
            [
                ['test_proc1', 'PROCEDURE'],
                ['test_proc2', 'PROCEDURE'],
            ],
            ['Name'],
        );

        $names = Routines::getNames($this->createDatabaseInterface($dbiDummy), 'test_db', RoutineType::Procedure);
        self::assertSame(['test_proc1', 'test_proc2'], $names);

        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetProcedureNamesWithEmptyReturn(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT SPECIFIC_NAME FROM information_schema.ROUTINES '
            . "WHERE ROUTINE_SCHEMA = 'test_db' AND ROUTINE_TYPE = 'PROCEDURE' AND SPECIFIC_NAME != ''",
            [],
            ['Db', 'Name', 'Type'],
        );

        $names = Routines::getNames($this->createDatabaseInterface($dbiDummy), 'test_db', RoutineType::Procedure);
        self::assertSame([], $names);

        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetDataFromName(): void
    {
        Current::$database = 'test_db';

        $dbiDummy = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            "SELECT SPECIFIC_NAME, ROUTINE_TYPE, DTD_IDENTIFIER, ROUTINE_DEFINITION, IS_DETERMINISTIC, SQL_DATA_ACCESS, ROUTINE_COMMENT, SECURITY_TYPE FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA COLLATE utf8_bin='test_db' AND SPECIFIC_NAME='test_function' AND ROUTINE_TYPE='FUNCTION';",
            [['test_function', 'FUNCTION', 'char(50)', "BEGIN RETURN CONCAT('Hello, ', s, '!'); END", 'YES', 'CONTAINS SQL', 'Comment', 'DEFINER']],
            ['SPECIFIC_NAME', 'ROUTINE_TYPE', 'DTD_IDENTIFIER', 'ROUTINE_DEFINITION', 'IS_DETERMINISTIC', 'SQL_DATA_ACCESS', 'ROUTINE_COMMENT', 'SECURITY_TYPE'],
        );
        $dbiDummy->addResult(
            'SHOW CREATE FUNCTION `test_db`.`test_function`',
            [["CREATE DEFINER=`test_user`@`localhost` FUNCTION `test_function`(`s` CHAR(50) CHARSET utf8mb4) RETURNS char(50) CHARSET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DETERMINISTIC COMMENT 'Comment' BEGIN RETURN CONCAT('Hello, ', s, '!'); END"]],
            ['Create Function'],
        );
        // phpcs:enable

        $routines = new Routines($this->createDatabaseInterface($dbiDummy), new Config());
        $data = $routines->getDataFromName('test_function', 'FUNCTION');
        $dbiDummy->assertAllQueriesConsumed();

        self::assertEquals(
            new RoutineItem(
                name: 'test_function',
                originalName: 'test_function',
                returnLength: '50',
                returnOptsNum: '',
                returnOptsText: 'utf8mb4',
                definition: "BEGIN RETURN CONCAT('Hello, ', s, '!'); END",
                comment: 'Comment',
                definer: '`test_user`@`localhost`',
                type: RoutineType::Function,
                originalType: RoutineType::Function,
                numParams: 1,
                paramDir: [''],
                paramName: ['s'],
                paramType: ['CHAR'],
                paramLength: ['50'],
                paramOptsNum: ['utf8mb4'],
                paramOptsText: ['utf8mb4'],
                returnType: 'CHAR',
                isDeterministic: true,
                securityTypeDefiner: true,
                sqlDataAccess: 'CONTAINS SQL',
                paramLengthArray: [['50']],
            ),
            $data,
        );
    }
}
