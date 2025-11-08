<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Table\PrivilegesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;

#[CoversClass(PrivilegesController::class)]
class PrivilegesControllerTest extends AbstractTestCase
{
    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testIndex(): void
    {
        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;

        $dbiDummy = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult("SHOW SESSION VARIABLES LIKE 'collation_connection';", [['collation_connection', 'utf8mb4_general_ci']]);
        $dbiDummy->addResult("SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t WHERE `IS_GRANTABLE` = 'YES' AND '''pma_test''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8mb4_general_ci AND 'pma_test' LIKE `User` AND '' LIKE `Host` LIMIT 1", [['1']]);
        $dbiDummy->addResult("SHOW SESSION VARIABLES LIKE 'collation_connection';", [['collation_connection', 'utf8mb4_general_ci']]);
        $dbiDummy->addResult("SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND '''pma_test''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8mb4_general_ci AND 'pma_test' LIKE `User` AND '' LIKE `Host` LIMIT 1", [['1']]);
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $privileges = [];

        $serverPrivileges = self::createMock(Privileges::class);
        $serverPrivileges->method('getAllPrivileges')
            ->willReturn($privileges);

        $request = self::createStub(ServerRequest::class);
        $request->method('getParam')->willReturnMap([['db', null, 'db'], ['table', null, 'table']]);

        $response = new ResponseRenderer();
        (new PrivilegesController(
            $response,
            $serverPrivileges,
            DatabaseInterface::getInstance(),
            $config,
        ))($request);
        $actual = $response->getHTMLResult();

        $dbiDummy->assertAllQueriesConsumed();

        self::assertStringContainsString(Current::$database . '.' . Current::$table, $actual);

        //validate 2: Url::getCommon
        $item = Url::getCommon(['db' => Current::$database, 'table' => Current::$table], '');
        self::assertStringContainsString($item, $actual);

        //validate 3: items
        self::assertStringContainsString(
            __('User'),
            $actual,
        );
        self::assertStringContainsString(
            __('Host'),
            $actual,
        );
        self::assertStringContainsString(
            __('Type'),
            $actual,
        );
        self::assertStringContainsString(
            __('Privileges'),
            $actual,
        );
        self::assertStringContainsString(
            __('Grant'),
            $actual,
        );
        self::assertStringContainsString(
            __('Action'),
            $actual,
        );
        self::assertStringContainsString(
            __('No user found'),
            $actual,
        );
    }

    public function testWithInvalidDatabaseName(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('getParam')->willReturnMap([['db', null, ''], ['table', null, 'table']]);

        $response = new ResponseRenderer();
        (new PrivilegesController(
            $response,
            self::createStub(Privileges::class),
            $this->createDatabaseInterface(),
            new Config(),
        ))($request);
        $actual = $response->getHTMLResult();

        self::assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual);
        self::assertStringContainsString('The database name must be a non-empty string.', $actual);
    }

    public function testWithInvalidTableName(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('getParam')->willReturnMap([['db', null, 'db'], ['table', null, '']]);

        $response = new ResponseRenderer();
        (new PrivilegesController(
            $response,
            self::createStub(Privileges::class),
            $this->createDatabaseInterface(),
            new Config(),
        ))($request);
        $actual = $response->getHTMLResult();

        self::assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual);
        self::assertStringContainsString('The table name must be a non-empty string.', $actual);
    }
}
