<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Database\PrivilegesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;

#[CoversClass(PrivilegesController::class)]
class PrivilegesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testIndex(): void
    {
        Current::$database = 'test_db';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;

        $this->dummyDbi->addResult('SELECT @@collation_server', [['utf8mb4_uca1400_ai_ci']]);
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $this->dummyDbi->addResult("SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t WHERE `IS_GRANTABLE` = 'YES' AND '''pma_test''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8mb4_uca1400_ai_ci AND 'pma_test' LIKE `User` AND '' LIKE `Host` LIMIT 1", [['1']]);
        $this->dummyDbi->addResult('SELECT @@collation_server', [['utf8mb4_uca1400_ai_ci']]);
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $this->dummyDbi->addResult("SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND '''pma_test''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8mb4_uca1400_ai_ci AND 'pma_test' LIKE `User` AND '' LIKE `Host` LIMIT 1", [['1']]);

        $privileges = [];

        $serverPrivileges = self::createMock(Privileges::class);
        $serverPrivileges->method('getAllPrivileges')
            ->willReturn($privileges);

        $request = self::createStub(ServerRequest::class);
        $request->method('getParam')->willReturnMap([['db', null, 'test_db']]);

        $response = new ResponseRenderer();
        (new PrivilegesController(
            $response,
            $serverPrivileges,
            DatabaseInterface::getInstance(),
            $config,
        ))($request);
        $actual = $response->getHTMLResult();

        $this->dummyDbi->assertAllQueriesConsumed();

        self::assertStringContainsString(
            Url::getCommon(['db' => Current::$database], ''),
            $actual,
        );

        self::assertStringContainsString(Current::$database, $actual);

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
    }

    public function testWithInvalidDatabaseName(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('getParam')->willReturnMap([['db', null, '']]);

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
}
