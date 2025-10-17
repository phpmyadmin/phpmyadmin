<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Server\PrivilegesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PrivilegesController::class)]
class PrivilegesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testPrivilegesController(): void
    {
        Current::$database = '';
        Current::$table = '';
        Config::getInstance()->selectedServer['DisableIS'] = false;

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->dummyDbi->addSelectDb('mysql');
        $this->dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS `Password` FROM `mysql`.`user` ORDER BY `User` ASC, `Host` ASC',
            [
                ['localhost', 'pma', 'password', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
                ['localhost', 'root', 'password', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
            ],
            ['Host', 'User', 'Password', 'Select_priv', 'Insert_priv', 'Update_priv', 'Delete_priv', 'Create_priv', 'Drop_priv', 'Reload_priv', 'Shutdown_priv', 'Process_priv', 'File_priv', 'Grant_priv', 'References_priv', 'Index_priv', 'Alter_priv', 'Show_db_priv', 'Super_priv', 'Create_tmp_table_priv', 'Lock_tables_priv', 'Execute_priv', 'Repl_slave_priv', 'Repl_client_priv', 'Create_view_priv', 'Show_view_priv', 'Create_routine_priv', 'Alter_routine_priv', 'Create_user_priv', 'Event_priv', 'Trigger_priv', 'Create_tablespace_priv', 'Delete_history_priv', 'ssl_type', 'ssl_cipher', 'x509_issuer', 'x509_subject', 'max_questions', 'max_updates', 'max_connections', 'max_user_connections', 'plugin', 'authentication_string', 'password_expired', 'is_role', 'default_role', 'max_statement_time', 'Password'],
        );
        $this->dummyDbi->addResult('SELECT COUNT(*) FROM `mysql`.`user`', [[2]], ['COUNT(*)']);
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `mysql`',
            [['columns_priv'], ['db'], ['tables_priv'], ['user']],
            ['Tables_in_mysql'],
        );
        $this->dummyDbi->addResult(
            '(SELECT DISTINCT `User`, `Host` FROM `mysql`.`user` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`db` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`tables_priv` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`columns_priv` ) ORDER BY `User` ASC, `Host` ASC',
            [['pma', 'localhost'], ['root', 'localhost']],
            ['User', 'Host'],
        );
        $this->dummyDbi->addResult("SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND '''pma_test''@''localhost''' LIKE `GRANTEE` UNION SELECT 1 FROM mysql.user WHERE `create_user_priv` = 'Y' COLLATE utf8_general_ci AND 'pma_test' LIKE `User` AND '' LIKE `Host` LIMIT 1", [['1']]);
        // phpcs:enable

        $request = self::createStub(ServerRequest::class);

        $request->method('getParsedBodyParam')->willReturnMap([['old_username', '', ''], ['old_hostname', '', '']]);

        $request->method('getQueryParam')->willReturnMap([['initial', null, null]]);

        $response = new ResponseRenderer();
        (new PrivilegesController(
            $response,
            new Template(),
            new Relation($this->dbi),
            $this->dbi,
            new UserPrivilegesFactory($this->dbi),
            new Config(),
        ))($request);

        $this->dummyDbi->assertAllQueriesConsumed();

        $actual = $response->getHTMLResult();
        self::assertStringContainsString('User accounts overview', $actual);
        self::assertStringContainsString(
            'id="checkbox_sel_users_1" value="pma&amp;amp;#27;localhost" name="selected_usr[]"',
            $actual,
        );
        self::assertStringContainsString('<code><dfn title="No privileges.">USAGE</dfn></code>', $actual);
        self::assertStringContainsString(
            'id="checkbox_sel_users_2" value="root&amp;amp;#27;localhost" name="selected_usr[]"',
            $actual,
        );
        self::assertStringContainsString(
            '<code><dfn title="Includes all privileges except GRANT.">ALL PRIVILEGES</dfn></code>',
            $actual,
        );
    }

    public function testUpdatePrivilegesForMultipleDatabases(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        $_REQUEST['username'] = $_POST['username'] = 'pma_test';
        $_REQUEST['hostname'] = $_POST['hostname'] = 'localhost';
        $_REQUEST['dbname'] = $_POST['dbname'] = ['test_db_1', 'test_db_2'];
        $_POST['Select_priv'] = 'Y';
        $_POST['grant_count'] = '18';
        $_POST['update_privs'] = '1';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('mysql');
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult('SHOW GRANTS FOR CURRENT_USER();', [['GRANT ALL PRIVILEGES ON *.* TO `pma_test`@`localhost` WITH GRANT OPTION']], ['Grants for pma_test@localhost']);
        $dbiDummy->addResult('SHOW GRANTS FOR CURRENT_USER();', [['GRANT ALL PRIVILEGES ON *.* TO `pma_test`@`localhost` WITH GRANT OPTION']], ['Grants for pma_test@localhost']);
        $dbiDummy->addResult("REVOKE ALL PRIVILEGES ON `test_db_1`.* FROM 'pma_test'@'localhost';", []);
        $dbiDummy->addResult("REVOKE GRANT OPTION ON `test_db_1`.* FROM 'pma_test'@'localhost';", []);
        $dbiDummy->addResult("GRANT SELECT ON `test_db_1`.* TO 'pma_test'@'localhost';", []);
        $dbiDummy->addResult("REVOKE ALL PRIVILEGES ON `test_db_2`.* FROM 'pma_test'@'localhost';", []);
        $dbiDummy->addResult("REVOKE GRANT OPTION ON `test_db_2`.* FROM 'pma_test'@'localhost';", []);
        $dbiDummy->addResult("GRANT SELECT ON `test_db_2`.* TO 'pma_test'@'localhost';", []);
        $dbiDummy->addResult("SELECT '1' FROM `mysql`.`user` WHERE `User` = 'pma_test' AND `Host` = 'localhost';", [['1']]);
        $dbiDummy->addResult("SELECT * FROM `mysql`.`db` WHERE `User` = 'pma_test' AND `Host` = 'localhost' AND `Db` = 'test_db_1'", []);
        $dbiDummy->addResult('SHOW COLUMNS FROM `mysql`.`db`;', [['Host', 'char(255)', 'NO', 'PRI', '', ''], ['Db', 'char(64)', 'NO', 'PRI', '', ''], ['User', 'char(128)', 'NO', 'PRI', '', ''], ['Select_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Insert_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Update_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Delete_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Create_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Drop_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Grant_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['References_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Index_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Alter_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Create_tmp_table_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Lock_tables_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Create_view_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Show_view_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Create_routine_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Alter_routine_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Execute_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Event_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Trigger_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Delete_history_priv', "enum('N','Y')", 'NO', '', 'N', ''], ['Show_create_routine_priv', "enum('N','Y')", 'NO', '', 'N', '']], ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra']);
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')->withParsedBody([
            'username' => 'pma_test',
            'hostname' => 'localhost',
            'dbname' => ['test_db_1', 'test_db_2'],
            'Select_priv' => 'Y',
            'grant_count' => '18',
            'update_privs' => '1',
        ]);

        $controller = new PrivilegesController(
            new ResponseRenderer(),
            new Template(),
            new Relation($dbi),
            $dbi,
            new UserPrivilegesFactory($dbi),
            $config,
        );
        $response = $controller($request);

        $output = (string) $response->getBody();
        self::assertStringContainsString("You have updated the privileges for 'pma_test'@'localhost'.", $output);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedSql = '<pre><code class="sql" dir="ltr">'
            . "REVOKE ALL PRIVILEGES ON  `test_db_1`.* FROM 'pma_test'@'localhost'; REVOKE GRANT OPTION ON  `test_db_1`.* FROM 'pma_test'@'localhost'; GRANT SELECT ON  `test_db_1`.* TO 'pma_test'@'localhost'; \n"
            . "REVOKE ALL PRIVILEGES ON  `test_db_2`.* FROM 'pma_test'@'localhost'; REVOKE GRANT OPTION ON  `test_db_2`.* FROM 'pma_test'@'localhost'; GRANT SELECT ON  `test_db_2`.* TO 'pma_test'@'localhost'; "
            . '</code></pre>';
        // phpcs:enable
        self::assertStringContainsString($expectedSql, $output);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }
}
