<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\PrivilegesController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;

use function __;
use function _pgettext;

/**
 * @covers \PhpMyAdmin\Controllers\Table\PrivilegesController
 */
class PrivilegesControllerTest extends AbstractTestCase
{
    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setTheme();
    }

    public function testIndex(): void
    {
        global $db, $table, $server, $cfg, $PMA_PHP_SELF;

        $db = 'db';
        $table = 'table';
        $server = 0;
        $cfg['Server']['DisableIS'] = false;
        $PMA_PHP_SELF = 'index.php';

        $dbiDummy = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult('SHOW SESSION VARIABLES LIKE \'collation_connection\';', [['collation_connection', 'utf8mb4_general_ci']]);
        $dbiDummy->addResult("SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND '''pma_test''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8mb4_general_ci AND 'pma_test' LIKE `User` AND '' LIKE `Host` LIMIT 1", [['1']]);
        $dbiDummy->addResult('SHOW SESSION VARIABLES LIKE \'collation_connection\';', [['collation_connection', 'utf8mb4_general_ci']]);
        $dbiDummy->addResult("SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION SELECT `GRANTEE`, `IS_GRANTABLE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t WHERE `IS_GRANTABLE` = 'YES' AND '''pma_test''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8mb4_general_ci AND 'pma_test' LIKE `User` AND '' LIKE `Host` LIMIT 1", [['1']]);
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;

        $privileges = [];

        $serverPrivileges = $this->createMock(Privileges::class);
        $serverPrivileges->method('getAllPrivileges')
            ->willReturn($privileges);

        $actual = (new PrivilegesController(
            ResponseRenderer::getInstance(),
            new Template(),
            $db,
            $table,
            $serverPrivileges,
            $dbi
        ))(['checkprivsdb' => $db, 'checkprivstable' => $table]);

        $dbiDummy->assertAllQueriesConsumed();

        self::assertStringContainsString($db . '.' . $table, $actual);

        //validate 2: Url::getCommon
        $item = Url::getCommon([
            'db' => $db,
            'table' => $table,
        ], '');
        self::assertStringContainsString($item, $actual);

        //validate 3: items
        self::assertStringContainsString(__('User'), $actual);
        self::assertStringContainsString(__('Host'), $actual);
        self::assertStringContainsString(__('Type'), $actual);
        self::assertStringContainsString(__('Privileges'), $actual);
        self::assertStringContainsString(__('Grant'), $actual);
        self::assertStringContainsString(__('Action'), $actual);
        self::assertStringContainsString(__('No user found'), $actual);

        //_pgettext('Create new user', 'New')
        self::assertStringContainsString(_pgettext('Create new user', 'New'), $actual);
        self::assertStringContainsString(Url::getCommon([
            'checkprivsdb' => $db,
            'checkprivstable' => $table,
        ]), $actual);
    }
}
