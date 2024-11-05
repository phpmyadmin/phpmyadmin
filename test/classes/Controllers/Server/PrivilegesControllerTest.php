<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Server\PrivilegesController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Server\PrivilegesController */
final class PrivilegesControllerTest extends AbstractTestCase
{
    public function testUpdatePrivilegesForMultipleDatabases(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

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

        $GLOBALS['dbi'] = $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();

        $GLOBALS['dblist'] = (object) ['databases' => ['test_db_1', 'test_db_2']];

        $controller = new PrivilegesController($responseRenderer, new Template(), new Relation($dbi), $dbi);
        $controller();

        $output = $responseRenderer->getHTMLResult();
        self::assertStringContainsString("You have updated the privileges for 'pma_test'@'localhost'.", $output);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedSql = '<pre>' . "\n"
            . "REVOKE ALL PRIVILEGES ON  `test_db_1`.* FROM 'pma_test'@'localhost'; REVOKE GRANT OPTION ON  `test_db_1`.* FROM 'pma_test'@'localhost'; GRANT SELECT ON  `test_db_1`.* TO 'pma_test'@'localhost'; \n"
            . "REVOKE ALL PRIVILEGES ON  `test_db_2`.* FROM 'pma_test'@'localhost'; REVOKE GRANT OPTION ON  `test_db_2`.* FROM 'pma_test'@'localhost'; GRANT SELECT ON  `test_db_2`.* TO 'pma_test'@'localhost'; \n"
            . '</pre>';
        // phpcs:enable
        self::assertStringContainsString($expectedSql, $output);

        $this->assertAllSelectsConsumed();
        $this->assertAllQueriesConsumed();
    }
}
