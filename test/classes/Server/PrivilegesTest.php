<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Dbal\Statement;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use ReflectionClass;
use ReflectionMethod;

use function __;
use function _pgettext;
use function htmlspecialchars;
use function implode;

/** @covers \PhpMyAdmin\Server\Privileges */
class PrivilegesTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    public function testGetDataForDBInfo(): void
    {
        $_REQUEST['username'] = 'PMA_username';
        $_REQUEST['hostname'] = 'PMA_hostname';
        $_REQUEST['tablename'] = 'PMA_tablename';
        $_REQUEST['dbname'] = 'PMA_dbname';

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        [
            $username,
            $hostname,
            $dbname,
            $tablename,
            $routinename,
            $dbnameIsWildcard,
        ] = $serverPrivileges->getDataForDBInfo();
        $this->assertEquals('PMA_username', $username);
        $this->assertEquals('PMA_hostname', $hostname);
        $this->assertEquals('PMA_dbname', $dbname);
        $this->assertEquals('PMA_tablename', $tablename);
        $this->assertTrue($dbnameIsWildcard);

        //pre variable have been defined
        $_POST['pred_tablename'] = 'PMA_pred__tablename';
        $_POST['pred_dbname'] = ['PMA_pred_dbname'];
        [, , $dbname, $tablename, $routinename, $dbnameIsWildcard] = $serverPrivileges->getDataForDBInfo();
        $this->assertEquals('PMA_pred_dbname', $dbname);
        $this->assertEquals('PMA_pred__tablename', $tablename);
        $this->assertTrue($dbnameIsWildcard);

        // Escaped database
        $_POST['pred_tablename'] = 'PMA_pred__tablename';
        $_POST['pred_dbname'] = ['PMA\_pred\_dbname'];
        [, , $dbname, $tablename, $routinename, $dbnameIsWildcard] = $serverPrivileges->getDataForDBInfo();
        $this->assertEquals('PMA\_pred\_dbname', $dbname);
        $this->assertEquals('PMA_pred__tablename', $tablename);
        $this->assertEquals(false, $dbnameIsWildcard);

        // Multiselect database - pred
        unset($_POST['pred_tablename'], $_REQUEST['tablename'], $_REQUEST['dbname']);
        $_POST['pred_dbname'] = ['PMA\_pred\_dbname', 'PMADbname2'];
        [, , $dbname, $tablename, , $dbnameIsWildcard] = $serverPrivileges->getDataForDBInfo();
        $this->assertEquals(['PMA\_pred\_dbname', 'PMADbname2'], $dbname);
        $this->assertEquals(null, $tablename);
        $this->assertEquals(false, $dbnameIsWildcard);

        // Multiselect database
        unset($_POST['pred_tablename'], $_REQUEST['tablename'], $_POST['pred_dbname']);
        $_REQUEST['dbname'] = ['PMA\_dbname', 'PMADbname2'];
        [, , $dbname, $tablename, , $dbnameIsWildcard] = $serverPrivileges->getDataForDBInfo();
        $this->assertEquals(['PMA\_dbname', 'PMADbname2'], $dbname);
        $this->assertEquals(null, $tablename);
        $this->assertEquals(false, $dbnameIsWildcard);
    }

    public function testWildcardEscapeForGrant(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $dbname = '';
        $tablename = '';
        $dbAndTable = $serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals('*.*', $dbAndTable);

        $dbname = 'dbname';
        $tablename = '';
        $dbAndTable = $serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals('`dbname`.*', $dbAndTable);

        $dbname = 'dbname';
        $tablename = 'tablename';
        $dbAndTable = $serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals('`dbname`.`tablename`', $dbAndTable);
    }

    public function testRangeOfUsers(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $ret = $serverPrivileges->rangeOfUsers('INIT');
        $this->assertEquals(' WHERE `User` LIKE \'INIT%\' OR `User` LIKE \'init%\'', $ret);

        $ret = $serverPrivileges->rangeOfUsers('');
        $this->assertEquals('', $ret);
    }

    public function testGetTableGrantsArray(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $ret = $serverPrivileges->getTableGrantsArray();
        $this->assertEquals(
            ['Delete', 'DELETE', __('Allows deleting data.')],
            $ret[0],
        );
        $this->assertEquals(
            ['Create', 'CREATE', __('Allows creating new tables.')],
            $ret[1],
        );
    }

    public function testGetGrantsArray(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $ret = $serverPrivileges->getGrantsArray();
        $this->assertEquals(
            ['Select_priv', 'SELECT', __('Allows reading data.')],
            $ret[0],
        );
        $this->assertEquals(
            ['Insert_priv', 'INSERT', __('Allows inserting and replacing data.')],
            $ret[1],
        );
    }

    public function testGetSqlQueryForDisplayPrivTable(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $username = 'pma_username';
        $db = '*';
        $table = 'pma_table';
        $hostname = 'pma_hostname';

        //$db == '*'
        $ret = $serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $sql = 'SELECT * FROM `mysql`.`user`'
            . " WHERE `User` = '" . $dbi->escapeString($username) . "'"
            . " AND `Host` = '" . $dbi->escapeString($hostname) . "';";
        $this->assertEquals($sql, $ret);

        //$table == '*'
        $db = 'pma_db';
        $table = '*';
        $ret = $serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $sql = 'SELECT * FROM `mysql`.`db`'
            . " WHERE `User` = '" . $dbi->escapeString($username) . "'"
            . " AND `Host` = '" . $dbi->escapeString($hostname) . "'"
            . ' AND `Db` = \'' . $db . '\'';

        $this->assertEquals($sql, $ret);

        //$table == 'pma_table'
        $db = 'pma_db';
        $table = 'pma_table';
        $ret = $serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $sql = 'SELECT `Table_priv`'
            . ' FROM `mysql`.`tables_priv`'
            . " WHERE `User` = '" . $dbi->escapeString($username) . "'"
            . " AND `Host` = '" . $dbi->escapeString($hostname) . "'"
            . " AND `Db` = '" . $serverPrivileges->unescapeGrantWildcards($db) . "'"
            . " AND `Table_name` = '" . $dbi->escapeString($table) . "';";
        $this->assertEquals($sql, $ret);

        // SQL escaping
        $db = "db' AND";
        $table = 'pma_table';
        $ret = $serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $this->assertEquals(
            'SELECT `Table_priv` FROM `mysql`.`tables_priv` '
            . "WHERE `User` = 'pma_username' AND "
            . "`Host` = 'pma_hostname' AND `Db` = 'db\' AND' AND "
            . "`Table_name` = 'pma_table';",
            $ret,
        );
    }

    public function testGetDataForChangeOrCopyUser(): void
    {
        $GLOBALS['lang'] = 'en';

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            'SELECT * FROM `mysql`.`user` WHERE `User` = \'PMA_old_username\' AND `Host` = \'PMA_old_hostname\';',
            [
                ['PMA_old_hostname', 'PMA_old_username', 'pma_password', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'pma_password', 'N', 'N', '', '0.000000'],
            ],
            ['Host', 'User', 'Password', 'Select_priv', 'Insert_priv', 'Update_priv', 'Delete_priv', 'Create_priv', 'Drop_priv', 'Reload_priv', 'Shutdown_priv', 'Process_priv', 'File_priv', 'Grant_priv', 'References_priv', 'Index_priv', 'Alter_priv', 'Show_db_priv', 'Super_priv', 'Create_tmp_table_priv', 'Lock_tables_priv', 'Execute_priv', 'Repl_slave_priv', 'Repl_client_priv', 'Create_view_priv', 'Show_view_priv', 'Create_routine_priv', 'Alter_routine_priv', 'Create_user_priv', 'Event_priv', 'Trigger_priv', 'Create_tablespace_priv', 'Delete_history_priv', 'ssl_type', 'ssl_cipher', 'x509_issuer', 'x509_subject', 'max_questions', 'max_updates', 'max_connections', 'max_user_connections', 'plugin', 'authentication_string', 'password_expired', 'is_role', 'default_role', 'max_statement_time'],
        );
        // phpcs:enable

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        //$_POST['change_copy'] not set
        $password = $serverPrivileges->getDataForChangeOrCopyUser('', '');

        //$_POST['change_copy'] is set
        $_POST['change_copy'] = true;
        $password = $serverPrivileges->getDataForChangeOrCopyUser('PMA_old_username', 'PMA_old_hostname');
        $this->assertEquals('pma_password', $password);
        unset($_POST['change_copy']);
    }

    public function testGetExportUserDefinitionTextarea(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SHOW GRANTS FOR \'PMA_username\'@\'PMA_hostname\'',
            [['grant user2 delete'], ['grant user1 select']],
            ['Grants for PMA_username@PMA_hostname'],
        );
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';

        $export = $serverPrivileges->getExportUserDefinitionTextarea($username, $hostname, null);

        $this->assertStringContainsString('grant user2 delete', $export);
        $this->assertStringContainsString('grant user1 select', $export);
        $this->assertStringContainsString('<textarea class="export"', $export);
    }

    public function testAddUser(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['username'] = 'pma_username';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT \'1\' FROM `mysql`.`user` WHERE `User` = \'\' AND `Host` = \'localhost\';', []);
        $dummyDbi->addResult('SET `old_passwords` = 0;', []);
        $dummyDbi->addResult(
            'CREATE USER \'\'@\'localhost\' IDENTIFIED WITH mysql_native_password AS \'pma_dbname\';',
            [],
        );
        $dummyDbi->addResult('GRANT USAGE ON *.* TO \'\'@\'localhost\' REQUIRE NONE;', []);
        $dummyDbi->addResult('GRANT ALL PRIVILEGES ON `pma_dbname`.* TO \'\'@\'localhost\';', []);

        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dbi->setVersion(['@@version' => '5.7.6', '@@version_comment' => 'MySQL Community Server (GPL)']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $_POST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_POST['pred_password'] = 'keep';
        $_POST['createdb-3'] = true;
        $_POST['userGroup'] = 'username';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        [
            $retMessage,,,
            $sqlQuery,
            $addUserError,
        ] = $serverPrivileges->addUser($dbname, $username, $hostname, $dbname, true);
        $this->assertEquals(
            'You have added a new user.',
            $retMessage->getMessage(),
        );
        $this->assertEquals(
            "CREATE USER ''@'localhost' IDENTIFIED WITH mysql_native_password AS '***';"
            . "GRANT USAGE ON *.* TO ''@'localhost' REQUIRE NONE;"
            . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';",
            $sqlQuery,
        );
        $this->assertFalse($addUserError);
    }

    public function testAddUserOld(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT \'1\' FROM `mysql`.`user` WHERE `User` = \'\' AND `Host` = \'localhost\';', []);
        $dummyDbi->addResult('SET `old_passwords` = 0;', []);
        $dummyDbi->addResult('CREATE USER \'\'@\'localhost\';', []);
        $dummyDbi->addResult('SET `old_passwords` = 0;', []);
        $dummyDbi->addResult('SET PASSWORD FOR \'\'@\'localhost\' = \'pma_dbname\';', []);
        $dummyDbi->addResult('GRANT USAGE ON *.* TO \'\'@\'localhost\' REQUIRE NONE;', []);
        $dummyDbi->addResult('GRANT ALL PRIVILEGES ON `pma_dbname`.* TO \'\'@\'localhost\';', []);

        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dbi->setVersion(['@@version' => '5.5.6', '@@version_comment' => 'MySQL Community Server (GPL)']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $GLOBALS['username'] = 'username';
        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $_POST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_POST['pred_password'] = 'keep';
        $_POST['createdb-3'] = true;
        $_POST['userGroup'] = 'username';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        [
            $retMessage,,,
            $sqlQuery,
            $addUserError,
        ] = $serverPrivileges->addUser($dbname, $username, $hostname, $dbname, true);

        $this->assertEquals(
            'You have added a new user.',
            $retMessage->getMessage(),
        );
        $this->assertEquals(
            "CREATE USER ''@'localhost';"
            . "GRANT USAGE ON *.* TO ''@'localhost' REQUIRE NONE;"
            . "SET PASSWORD FOR ''@'localhost' = '***';"
            . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';",
            $sqlQuery,
        );
        $this->assertFalse($addUserError);
    }

    public function testUpdatePassword(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'ALTER USER \'pma_username\'@\'pma_hostname\' IDENTIFIED WITH mysql_native_password BY \'pma_pw\'',
            [],
        );
        $dummyDbi->addResult('FLUSH PRIVILEGES;', []);
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $errUrl = 'error.php';
        $_POST['pma_pw'] = 'pma_pw';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        $message = $serverPrivileges->updatePassword($errUrl, $username, $hostname);

        $this->assertEquals(
            'The password for \'pma_username\'@\'pma_hostname\' was changed successfully.',
            $message->getMessage(),
        );
    }

    public function testGetMessageAndSqlQueryForPrivilegesRevoke(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'REVOKE ALL PRIVILEGES ON `pma_dbname`.`pma_tablename` FROM \'pma_username\'@\'pma_hostname\';',
            [],
        );
        $dummyDbi->addResult(
            'REVOKE GRANT OPTION ON `pma_dbname`.`pma_tablename` FROM \'pma_username\'@\'pma_hostname\';',
            [],
        );

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $_POST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_POST['createdb-3'] = true;
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        [$message, $sqlQuery] = $serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
            $dbname,
            $tablename,
            $username,
            $hostname,
            '',
        );

        $this->assertEquals(
            "You have revoked the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage(),
        );
        $this->assertEquals(
            'REVOKE ALL PRIVILEGES ON  `pma_dbname`.`pma_tablename` '
            . "FROM 'pma_username'@'pma_hostname'; "
            . 'REVOKE GRANT OPTION ON  `pma_dbname`.`pma_tablename` '
            . "FROM 'pma_username'@'pma_hostname';",
            $sqlQuery,
        );
    }

    public function testUpdatePrivileges(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'REVOKE ALL PRIVILEGES ON `pma_dbname`.`pma_tablename` FROM \'pma_username\'@\'pma_hostname\';',
            [],
        );
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $_POST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_POST['createdb-3'] = true;
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        [$sqlQuery, $message] = $serverPrivileges->updatePrivileges($username, $hostname, $tablename, $dbname, '');

        $this->assertEquals(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage(),
        );
        $this->assertEquals(
            'REVOKE ALL PRIVILEGES ON  `pma_dbname`.`pma_tablename` FROM \'pma_username\'@\'pma_hostname\';   ',
            $sqlQuery,
        );
    }

    public function testUpdatePrivilegesBeforeMySql8Dot11(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $dbname = '';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = '';
        $_POST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        $_POST['max_connections'] = 20;
        $_POST['max_updates'] = 30;
        $_POST['max_user_connections'] = 40;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getVersion')
            ->will($this->returnValue(8003));
        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $serverPrivileges->dbi = $dbi;

        [$sqlQuery, $message] = $serverPrivileges->updatePrivileges($username, $hostname, $tablename, $dbname, '');

        $this->assertEquals(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage(),
        );
        $this->assertEquals(
            '  GRANT USAGE ON  *.* TO \'pma_username\'@\'pma_hostname\' REQUIRE NONE'
            . ' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 1000 MAX_CONNECTIONS_PER_HOUR 20'
            . ' MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40; ',
            $sqlQuery,
        );
    }

    public function testUpdatePrivilegesAfterMySql8Dot11(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $dbname = '';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = '';
        $_POST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        $_POST['max_connections'] = 20;
        $_POST['max_updates'] = 30;
        $_POST['max_user_connections'] = 40;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getVersion')
            ->will($this->returnValue(80011));
        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $serverPrivileges->dbi = $dbi;

        [$sqlQuery, $message] = $serverPrivileges->updatePrivileges($username, $hostname, $tablename, $dbname, '');

        $this->assertEquals(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage(),
        );
        $this->assertEquals(
            '  GRANT USAGE ON  *.* TO \'pma_username\'@\'pma_hostname\';'
            . ' ALTER USER \'pma_username\'@\'pma_hostname\'  REQUIRE NONE'
            . ' WITH MAX_QUERIES_PER_HOUR 1000 MAX_CONNECTIONS_PER_HOUR'
            . ' 20 MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40;',
            $sqlQuery,
        );
    }

    /** @group medium */
    public function testGetHtmlToDisplayPrivilegesTable(): void
    {
        $GLOBALS['hostname'] = 'hostname';
        $GLOBALS['username'] = 'username';
        $dbi = DatabaseInterface::load(new DbiDummy());

        $serverPrivileges = $this->getPrivileges($dbi);
        $html = $serverPrivileges->getHtmlToDisplayPrivilegesTable();
        $GLOBALS['username'] = 'username';

        //validate 2: button
        $this->assertStringContainsString('Update user privileges', $html);

        //validate 3: getHtmlForGlobalOrDbSpecificPrivs
        $this->assertStringContainsString('<div class="card">', $html);
        $this->assertStringContainsString(
            '<div class="card-header js-submenu-label" data-submenu-label="' . __('Global') . '">',
            $html,
        );
        $this->assertStringContainsString(
            __('Global privileges'),
            $html,
        );
        $this->assertStringContainsString(
            __('Check all'),
            $html,
        );
        $this->assertStringContainsString(
            __('Note: MySQL privilege names are expressed in English'),
            $html,
        );

        //validate 4: getHtmlForGlobalPrivTableWithCheckboxes items
        //Select_priv
        $this->assertStringContainsString('<input type="checkbox" class="checkall" name="Select_priv"', $html);
        //Create_user_priv
        $this->assertStringContainsString('<input type="checkbox" class="checkall" name="Create_user_priv"', $html);
        //Insert_priv
        $this->assertStringContainsString('<input type="checkbox" class="checkall" name="Insert_priv"', $html);
        //Update_priv
        $this->assertStringContainsString('<input type="checkbox" class="checkall" name="Update_priv"', $html);
        //Create_priv
        $this->assertStringContainsString('<input type="checkbox" class="checkall" name="Create_priv"', $html);
        //Create_routine_priv
        $this->assertStringContainsString('<input type="checkbox" class="checkall" name="Create_routine_priv"', $html);
        //Execute_priv
        $this->assertStringContainsString('<input type="checkbox" class="checkall" name="Execute_priv"', $html);

        //validate 5: getHtmlForResourceLimits
        $this->assertStringContainsString(
            '<div class="card-header">' . __('Resource limits') . '</div>',
            $html,
        );
        $this->assertStringContainsString(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html,
        );
        $this->assertStringContainsString('MAX QUERIES PER HOUR', $html);
        $this->assertStringContainsString('id="text_max_updates" value="0"', $html);
        $this->assertStringContainsString(
            __('Limits the number of new connections the user may open per hour.'),
            $html,
        );
        $this->assertStringContainsString(
            __('Limits the number of simultaneous connections the user may have.'),
            $html,
        );

        $this->assertStringContainsString('<div class="card-header">SSL</div>', $html);
        $this->assertStringContainsString('value="NONE"', $html);
        $this->assertStringContainsString('value="ANY"', $html);
        $this->assertStringContainsString('value="X509"', $html);
        $this->assertStringContainsString('value="SPECIFIED"', $html);
    }

    public function testGetSqlQueriesForDisplayAndAddUserMySql8011(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SET `old_passwords` = 0;', []);
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dbi->setVersion(['@@version' => '8.0.11', '@@version_comment' => 'MySQL Community Server - GPL']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';
        $password = 'pma_password';
        $_POST['pred_password'] = 'keep';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        [
            $createUserReal,
            $createUserShow,
        ] = $serverPrivileges->getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

        //validate 1: $create_user_real
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password BY \'pma_password\';',
            $createUserReal,
        );

        //validate 2: $create_user_show
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password BY \'***\';',
            $createUserShow,
        );
    }

    public function testGetSqlQueriesForDisplayAndAddUserMySql8016(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dbi = $this->createDatabaseInterface();

        $dbi->setVersion(['@@version' => '8.0.16', '@@version_comment' => 'MySQL Community Server - GPL']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';
        $password = 'pma_password';
        $_POST['pred_password'] = 'keep';

        [
            $createUserReal,
            $createUserShow,
        ] = $serverPrivileges->getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

        //validate 1: $createUserReal
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED BY \'pma_password\';',
            $createUserReal,
        );

        //validate 2: $createUserShow
        $this->assertEquals('CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED BY \'***\';', $createUserShow);
    }

    public function testGetSqlQueriesForDisplayAndAddUser(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SET `old_passwords` = 0;', []);
        $dummyDbi->addResult('GRANT USAGE ON *.* TO \'PMA_username\'@\'PMA_hostname\' REQUIRE NONE;', []);

        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dbi->setVersion(['@@version' => '5.7.6', '@@version_comment' => 'MySQL Community Server (GPL)']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';
        $password = 'pma_password';
        $_POST['pred_password'] = 'keep';
        $_POST['authentication_plugin'] = 'mysql_native_password';
        $dbname = 'PMA_db';

        [
            $createUserReal,
            $createUserShow,
            $realSqlQuery,
            $sqlQuery,,,
            $alterRealSqlQuery,
            $alterSqlQuery,
        ] = $serverPrivileges->getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

        //validate 1: $createUserReal
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password AS \'pma_password\';',
            $createUserReal,
        );

        //validate 2: $createUserShow
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password AS \'***\';',
            $createUserShow,
        );

        //validate 3:$realSqlQuery
        $this->assertEquals('GRANT USAGE ON *.* TO \'PMA_username\'@\'PMA_hostname\' REQUIRE NONE;', $realSqlQuery);

        //validate 4:$sqlQuery
        $this->assertEquals('GRANT USAGE ON *.* TO \'PMA_username\'@\'PMA_hostname\' REQUIRE NONE;', $sqlQuery);

        $this->assertSame('', $alterRealSqlQuery);

        $this->assertSame('', $alterSqlQuery);

        //Test for addUserAndCreateDatabase
        [$sqlQuery, $message] = $serverPrivileges->addUserAndCreateDatabase(
            false,
            $realSqlQuery,
            $sqlQuery,
            $username,
            $hostname,
            $dbname,
            $alterRealSqlQuery,
            $alterSqlQuery,
            false,
            false,
            false,
        );

        //validate 5: $sqlQuery
        $this->assertEquals('GRANT USAGE ON *.* TO \'PMA_username\'@\'PMA_hostname\' REQUIRE NONE;', $sqlQuery);

        $this->assertInstanceOf(Message::class, $message);

        //validate 6: $message
        $this->assertEquals(
            'You have added a new user.',
            $message->getMessage(),
        );
    }

    public function testGetHtmlToDisplayPrivilegesTableWithTableSpecific(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $dbi = DatabaseInterface::load(new DbiDummy());
        $serverPrivileges->dbi = $dbi;

        $GLOBALS['username'] = 'PMA_username';
        $GLOBALS['hostname'] = 'PMA_hostname';
        $html = $serverPrivileges->getHtmlToDisplayPrivilegesTable('PMA_db', 'PMA_table');

        $this->assertStringContainsString('checkbox_Update_priv_none', $html);
        $this->assertStringContainsString('<dfn title="Allows changing data.">UPDATE</dfn>', $html);
        $this->assertStringContainsString('checkbox_Insert_priv_none', $html);
        $this->assertStringContainsString(
            __('Allows reading data.'),
            $html,
        );
        $this->assertStringContainsString(
            __('Allows inserting and replacing data'),
            $html,
        );
        $this->assertStringContainsString(
            __('Allows changing data.'),
            $html,
        );
        $this->assertStringContainsString(
            __('Has no effect in this MySQL version.'),
            $html,
        );

        $this->assertStringContainsString('title="Allows performing SHOW CREATE VIEW queries." checked>', $html);
        $this->assertStringContainsString('<dfn title="Allows creating new views.">', $html);
        $this->assertStringContainsString('CREATE VIEW', $html);
        $this->assertStringContainsString('Create_view_priv', $html);
        $this->assertStringContainsString('Show_view_priv', $html);
        $this->assertStringContainsString(
            _pgettext('None privileges', 'None'),
            $html,
        );
    }

    public function testGetHtmlForLoginInformationFields(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $GLOBALS['username'] = 'pma_username';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fieldsInfo = [
            ['COLUMN_NAME' => 'Host', 'CHARACTER_MAXIMUM_LENGTH' => 80],
            ['COLUMN_NAME' => 'User', 'CHARACTER_MAXIMUM_LENGTH' => 40],
        ];
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fieldsInfo));
        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $serverPrivileges->dbi = $dbi;

        $html = $serverPrivileges->getHtmlForLoginInformationFields();

        //validate 1: __('Login Information')
        $this->assertStringContainsString(
            __('Login Information'),
            $html,
        );
        $this->assertStringContainsString(
            __('User name:'),
            $html,
        );
        $this->assertStringContainsString(
            __('Any user'),
            $html,
        );
        $this->assertStringContainsString(
            __('Use text field'),
            $html,
        );

        $output = Generator::showHint(
            __(
                'When Host table is used, this field is ignored and values stored in Host table are used instead.',
            ),
        );
        $this->assertStringContainsString($output, $html);
    }

    public function testGetWithClauseForAddUserAndUpdatePrivs(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 10;
        $_POST['max_connections'] = 20;
        $_POST['max_updates'] = 30;
        $_POST['max_user_connections'] = 40;

        $sqlQuery = $serverPrivileges->getWithClauseForAddUserAndUpdatePrivs();
        $expect = 'WITH GRANT OPTION MAX_QUERIES_PER_HOUR 10 '
            . 'MAX_CONNECTIONS_PER_HOUR 20'
            . ' MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40';
        $this->assertStringContainsString($expect, $sqlQuery);
    }

    /** @group medium */
    public function testGetHtmlForAddUser(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fieldsInfo = [
            ['COLUMN_NAME' => 'Host', 'CHARACTER_MAXIMUM_LENGTH' => 80],
            ['COLUMN_NAME' => 'User', 'CHARACTER_MAXIMUM_LENGTH' => 40],
        ];
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fieldsInfo));
        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));
        $dbi->expects($this->any())->method('isGrantUser')
            ->will($this->returnValue(true));

        $serverPrivileges->dbi = $dbi;

        $dbname = 'pma_dbname';

        $html = $serverPrivileges->getHtmlForAddUser($dbname);

        //validate 1: Url::getHiddenInputs
        $this->assertStringContainsString(
            Url::getHiddenInputs('', ''),
            $html,
        );

        //validate 2: getHtmlForLoginInformationFields
        $this->assertStringContainsString(
            $serverPrivileges->getHtmlForLoginInformationFields(),
            $html,
        );

        //validate 3: Database for user
        $this->assertStringContainsString(
            __('Database for user'),
            $html,
        );

        $this->assertStringContainsString(
            __('Grant all privileges on wildcard name (username\\_%).'),
            $html,
        );
        $this->assertStringContainsString('<input type="checkbox" name="createdb-2" id="createdb-2">', $html);

        //validate 4: getHtmlToDisplayPrivilegesTable
        $this->assertStringContainsString(
            $serverPrivileges->getHtmlToDisplayPrivilegesTable('*', '*', false),
            $html,
        );

        //validate 5: button
        $this->assertStringContainsString('Create user', $html);
    }

    public function testGetUserLink(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $dbname = 'pma_dbname';
        $tablename = 'pma_tablename';

        $html = $serverPrivileges->getUserLink('edit', $username, $hostname, $dbname, $tablename, '');

        $dbname = 'pma_dbname';
        $urlHtml = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'dbname' => $dbname,
            'tablename' => $tablename,
            'routinename' => '',
        ], '');
        $this->assertStringContainsString($urlHtml, $html);
        $this->assertStringContainsString(
            __('Edit privileges'),
            $html,
        );

        $dbname = 'pma_dbname';
        $html = $serverPrivileges->getUserLink('revoke', $username, $hostname, $dbname, $tablename, '');

        $dbname = 'pma_dbname';
        $urlHtml = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
                'revokeall' => 1,
            ],
            '',
        );
        $this->assertStringContainsString($urlHtml, $html);
        $this->assertStringContainsString(
            __('Revoke'),
            $html,
        );

        $html = $serverPrivileges->getUserLink('export', $username, $hostname);

        $urlHtml = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'initial' => '',
            'export' => 1,
        ], '');
        $this->assertStringContainsString($urlHtml, $html);
        $this->assertStringContainsString(
            __('Export'),
            $html,
        );
    }

    public function testGetUserLinkWildcardsEscaped(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $username = 'pma\_username';
        $hostname = 'pma\_hostname';
        $dbname = 'pma\_dbname';
        $tablename = 'pma\_tablename';

        $html = $serverPrivileges->getUserLink('edit', $username, $hostname, $dbname, $tablename, '');

        $dbname = 'pma\_dbname';
        $urlHtml = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'dbname' => $dbname,
            'tablename' => $tablename,
            'routinename' => '',
        ], '');
        $this->assertStringContainsString($urlHtml, $html);
        $this->assertStringContainsString(
            __('Edit privileges'),
            $html,
        );

        $dbname = 'pma\_dbname';
        $html = $serverPrivileges->getUserLink('revoke', $username, $hostname, $dbname, $tablename, '');

        $dbname = 'pma\_dbname';
        $urlHtml = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
                'revokeall' => 1,
            ],
            '',
        );
        $this->assertStringContainsString($urlHtml, $html);
        $this->assertStringContainsString(
            __('Revoke'),
            $html,
        );

        $html = $serverPrivileges->getUserLink('export', $username, $hostname);

        $urlHtml = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'initial' => '',
            'export' => 1,
        ], '');
        $this->assertStringContainsString($urlHtml, $html);
        $this->assertStringContainsString(
            __('Export'),
            $html,
        );
    }

    public function testGetExtraDataForAjaxBehavior(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT * FROM `mysql`.`user` WHERE `User` = \'username\';', []);

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $password = 'pma_password';
        $sqlQuery = 'pma_sql_query';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $GLOBALS['dbname'] = 'pma_dbname';
        $_POST['adduser_submit'] = 'adduser_submit';
        $_POST['username'] = 'username';
        $_POST['change_copy'] = 'change_copy';
        $_GET['validate_username'] = 'validate_username';
        $_GET['username'] = 'username';
        $_POST['update_privs'] = 'update_privs';

        $extraData = $serverPrivileges->getExtraDataForAjaxBehavior($password, $sqlQuery, $hostname, $username);

        //user_exists
        $this->assertFalse($extraData['user_exists']);

        //db_wildcard_privs
        $this->assertTrue($extraData['db_wildcard_privs']);

        //user_exists
        $this->assertFalse($extraData['db_specific_privs']);

        //new_user_initial
        $this->assertEquals('P', $extraData['new_user_initial']);

        //sql_query
        $this->assertEquals(
            Generator::getMessage('', $sqlQuery),
            $extraData['sql_query'],
        );

        //new_user_string
        $this->assertIsString($extraData['new_user_string']);
        $this->assertStringContainsString(
            htmlspecialchars($hostname),
            $extraData['new_user_string'],
        );
        $this->assertStringContainsString(
            htmlspecialchars($username),
            $extraData['new_user_string'],
        );

        //new_privileges
        $this->assertIsString($extraData['new_privileges']);
        $this->assertStringContainsString(
            implode(', ', $serverPrivileges->extractPrivInfo(null, true)),
            $extraData['new_privileges'],
        );
    }

    public function testGetUserGroupForUser(): void
    {
        $GLOBALS['server'] = 1;
        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'menuswork' => true,
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SELECT `usergroup` FROM `pmadb`.`users` WHERE `username` = \'pma_username\' LIMIT 1',
            [['pma_usergroup']],
        );

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $this->assertEquals('pma_usergroup', $serverPrivileges->getUserGroupForUser('pma_username'));
    }

    public function testGetUsersOverview(): void
    {
        $this->setTheme();

        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['server'] = 1;
        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'menuswork' => true,
            'trackingwork' => true,
            'tracking' => 'tracking',
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT * FROM `pmadb`.`users`', []);
        $dummyDbi->addResult('SELECT COUNT(*) FROM `pmadb`.`usergroups`', [['0']]);

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $resultStub = $this->createMock(DummyResult::class);
        $dbRights = [];
        $textDir = 'text_dir';

        $html = $serverPrivileges->getUsersOverview($resultStub, $dbRights, $textDir);

        //Url::getHiddenInputs
        $this->assertStringContainsString(
            Url::getHiddenInputs('', ''),
            $html,
        );

        //items
        $this->assertStringContainsString(
            __('User'),
            $html,
        );
        $this->assertStringContainsString(
            __('Host'),
            $html,
        );
        $this->assertStringContainsString(
            __('Password'),
            $html,
        );
        $this->assertStringContainsString(
            __('Global privileges'),
            $html,
        );

        //Util::showHint
        $this->assertStringContainsString(
            Generator::showHint(
                __('Note: MySQL privilege names are expressed in English.'),
            ),
            $html,
        );

        //__('User group')
        $this->assertStringContainsString(
            __('User group'),
            $html,
        );
        $this->assertStringContainsString(
            __('Grant'),
            $html,
        );
        $this->assertStringContainsString(
            __('Action'),
            $html,
        );

        //$text_dir
        $this->assertStringContainsString($textDir, $html);

        $this->assertStringContainsString(
            Url::getCommon(['adduser' => 1], ''),
            $html,
        );

        //labels
        $this->assertStringContainsString(
            __('Add user account'),
            $html,
        );
        $this->assertStringContainsString(
            __('Remove selected user accounts'),
            $html,
        );
        $this->assertStringContainsString(
            __('Drop the databases that have the same names as the users.'),
            $html,
        );
        $this->assertStringContainsString(
            __('Drop the databases that have the same names as the users.'),
            $html,
        );
    }

    public function testGetDataForDeleteUsers(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $_POST['change_copy'] = 'change_copy';
        $_POST['old_hostname'] = 'old_hostname';
        $_POST['old_username'] = 'old_username';
        $relationParameters = RelationParameters::fromArray([]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $queries = [];

        $ret = $serverPrivileges->getDataForDeleteUsers($queries);

        $item = ["# Deleting 'old_username'@'old_hostname' ...", "DROP USER 'old_username'@'old_hostname';"];
        $this->assertEquals($item, $ret);
    }

    public function testGetHtmlHeaderForUserProperties(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SELECT \'1\' FROM `mysql`.`user` WHERE `User` = \'username\' AND `Host` = \'hostname\';',
            [['1']],
        );
        $dummyDbi->addResult('SHOW COLUMNS FROM `tablename`.`tablename`;', []);

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $dbnameIsWildcard = true;
        $urlDbname = 'url_dbname';
        $dbname = 'dbname';
        $username = 'username';
        $hostname = 'hostname';
        $tablename = 'tablename';
        $_REQUEST['tablename'] = 'tablename';

        $html = $serverPrivileges->getHtmlForUserProperties(
            $dbnameIsWildcard,
            $urlDbname,
            $username,
            $hostname,
            $tablename,
            $_REQUEST['tablename'],
            '/server/privileges',
        );

        //title
        $this->assertStringContainsString(
            __('Edit privileges:'),
            $html,
        );
        $this->assertStringContainsString(
            __('User account'),
            $html,
        );

        //Url::getCommon
        $item = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'dbname' => '',
            'tablename' => '',
        ], '');
        $this->assertStringContainsString($item, $html);

        //$username & $hostname
        $this->assertStringContainsString(
            htmlspecialchars($username),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($hostname),
            $html,
        );

        //$dbnameIsWildcard = true
        $this->assertStringContainsString(
            __('Databases'),
            $html,
        );

        //$dbnameIsWildcard = true
        $this->assertStringContainsString(
            __('Databases'),
            $html,
        );

        //Url::getCommon
        $item = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'dbname' => $urlDbname,
            'tablename' => '',
        ], '');
        $this->assertStringContainsString($item, $html);
        $this->assertStringContainsString($dbname, $html);
    }

    public function testGetHtmlForUserProperties(): void
    {
        $this->dummyDbi->addResult(
            'SELECT \'1\' FROM `mysql`.`user` WHERE `User` = \'user\' AND `Host` = \'host\';',
            [['1']],
            ['1'],
        );
        $this->dummyDbi->addResult(
            'SELECT `Table_priv` FROM `mysql`.`tables_priv` WHERE `User` = \'user\' AND `Host` = \'host\''
                . ' AND `Db` = \'sakila\' AND `Table_name` = \'actor\';',
            [],
            ['Table_priv'],
        );
        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `sakila`.`actor`;',
            [
                ['actor_id', 'smallint(5) unsigned', 'NO', 'PRI', null, 'auto_increment'],
                ['first_name', 'varchar(45)', 'NO', '', null, ''],
                ['last_name', 'varchar(45)', 'NO', 'MUL', null, ''],
                ['last_update', 'timestamp', 'NO', '', 'current_timestamp()', 'on update current_timestamp()'],
            ],
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
        );
        $this->dummyDbi->addResult(
            'SELECT `Column_name`, `Column_priv` FROM `mysql`.`columns_priv` WHERE `User` = \'user\''
                . ' AND `Host` = \'host\' AND `Db` = \'sakila\' AND `Table_name` = \'actor\';',
            [],
            ['Column_name', 'Column_priv'],
        );

        $serverPrivileges = $this->getPrivileges($this->dbi);

        $GLOBALS['username'] = 'user';
        $GLOBALS['hostname'] = 'host';

        $actual = $serverPrivileges->getHtmlForUserProperties(
            false,
            'sakila',
            'user',
            'host',
            'sakila',
            'actor',
            '/server/privileges',
        );
        $this->assertStringContainsString('addUsersForm', $actual);
        $this->assertStringContainsString('SELECT', $actual);
        $this->assertStringContainsString('Allows reading data.', $actual);
        $this->assertStringContainsString('INSERT', $actual);
        $this->assertStringContainsString('Allows inserting and replacing data.', $actual);
        $this->assertStringContainsString('UPDATE', $actual);
        $this->assertStringContainsString('Allows changing data.', $actual);
        $this->assertStringContainsString('DELETE', $actual);
        $this->assertStringContainsString('Allows deleting data.', $actual);
        $this->assertStringContainsString('CREATE', $actual);
        $this->assertStringContainsString('Allows creating new tables.', $actual);

        $this->assertStringContainsString(
            Url::getHiddenInputs(),
            $actual,
        );

        //$username & $hostname
        $this->assertStringContainsString('user', $actual);
        $this->assertStringContainsString('host', $actual);

        //Create a new user with the same privileges
        $this->assertStringContainsString('Create a new user account with the same privileges', $actual);

        $this->assertStringContainsString(
            __('Database'),
            $actual,
        );
        $this->assertStringContainsString(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database',
            ),
            $actual,
        );
        $item = Url::getCommon(['db' => 'sakila', 'reload' => 1], '');
        $this->assertStringContainsString($item, $actual);
        $this->assertStringContainsString('sakila', $actual);

        //$tablename
        $this->assertStringContainsString(
            __('Table'),
            $actual,
        );
        $this->assertStringContainsString(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'],
                'table',
            ),
            $actual,
        );
        $item = Url::getCommon(['db' => 'sakila', 'table' => 'actor', 'reload' => 1], '');
        $this->assertStringContainsString($item, $actual);
        $this->assertStringContainsString('table', $actual);
        $item = Util::getTitleForTarget($GLOBALS['cfg']['DefaultTabTable']);
        $this->assertStringContainsString((string) $item, $actual);
    }

    public function testGetHtmlForUserOverview(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['lang'] = 'en';
        $GLOBALS['is_reload_priv'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS \'Password\' FROM `mysql`.`user` ORDER BY `User` ASC, `Host` ASC;',
            [
                ['localhost', 'pma', 'password', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
                ['localhost', 'root', 'password', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
            ],
            ['Host', 'User', 'Password', 'Select_priv', 'Insert_priv', 'Update_priv', 'Delete_priv', 'Create_priv', 'Drop_priv', 'Reload_priv', 'Shutdown_priv', 'Process_priv', 'File_priv', 'Grant_priv', 'References_priv', 'Index_priv', 'Alter_priv', 'Show_db_priv', 'Super_priv', 'Create_tmp_table_priv', 'Lock_tables_priv', 'Execute_priv', 'Repl_slave_priv', 'Repl_client_priv', 'Create_view_priv', 'Show_view_priv', 'Create_routine_priv', 'Alter_routine_priv', 'Create_user_priv', 'Event_priv', 'Trigger_priv', 'Create_tablespace_priv', 'Delete_history_priv', 'ssl_type', 'ssl_cipher', 'x509_issuer', 'x509_subject', 'max_questions', 'max_updates', 'max_connections', 'max_user_connections', 'plugin', 'authentication_string', 'password_expired', 'is_role', 'default_role', 'max_statement_time', 'Password'],
        );
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS \'Password\' FROM `mysql`.`user` ;',
            [
                ['localhost', 'root', 'password', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
                ['localhost', 'pma', 'password', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
            ],
            ['Host', 'User', 'Password', 'Select_priv', 'Insert_priv', 'Update_priv', 'Delete_priv', 'Create_priv', 'Drop_priv', 'Reload_priv', 'Shutdown_priv', 'Process_priv', 'File_priv', 'Grant_priv', 'References_priv', 'Index_priv', 'Alter_priv', 'Show_db_priv', 'Super_priv', 'Create_tmp_table_priv', 'Lock_tables_priv', 'Execute_priv', 'Repl_slave_priv', 'Repl_client_priv', 'Create_view_priv', 'Show_view_priv', 'Create_routine_priv', 'Alter_routine_priv', 'Create_user_priv', 'Event_priv', 'Trigger_priv', 'Create_tablespace_priv', 'Delete_history_priv', 'ssl_type', 'ssl_cipher', 'x509_issuer', 'x509_subject', 'max_questions', 'max_updates', 'max_connections', 'max_user_connections', 'plugin', 'authentication_string', 'password_expired', 'is_role', 'default_role', 'max_statement_time', 'Password'],
        );
        $dummyDbi->addResult(
            'SHOW TABLES FROM `mysql`;',
            [['columns_priv'], ['db'], ['tables_priv'], ['user']],
            ['Tables_in_mysql'],
        );
        $dummyDbi->addResult(
            '(SELECT DISTINCT `User`, `Host` FROM `mysql`.`user` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`db` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`tables_priv` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`columns_priv` ) ORDER BY `User` ASC, `Host` ASC',
            [['pma', 'localhost'], ['root', 'localhost']],
            ['User', 'Host'],
        );
        // phpcs:enable

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $_REQUEST = ['ajax_page_request' => '1'];
        $actual = $serverPrivileges->getHtmlForUserOverview('ltr', '');
        $this->assertStringContainsString('Note: MySQL privilege names are expressed in English.', $actual);
        $this->assertStringContainsString(
            'Note: phpMyAdmin gets the users’ privileges directly from MySQL’s privilege tables.',
            $actual,
        );

        // the user does not have enough privileges
        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS \'Password\' FROM `mysql`.`user` ORDER BY `User` ASC, `Host` ASC;',
            false,
        );
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS \'Password\' FROM `mysql`.`user` ;',
            false,
        );
        $dummyDbi->addResult('SELECT 1 FROM `mysql`.`user`', false);
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));
        $html = $serverPrivileges->getHtmlForUserOverview('ltr', '');

        $this->assertStringContainsString(
            Url::getCommon(['adduser' => 1], ''),
            $html,
        );
        $this->assertStringContainsString(
            Generator::getIcon('b_usradd'),
            $html,
        );
        $this->assertStringContainsString(
            __('Add user'),
            $html,
        );

        // MySQL has older table structure
        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS \'Password\' FROM `mysql`.`user` ORDER BY `User` ASC, `Host` ASC;',
            false,
        );
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS \'Password\' FROM `mysql`.`user` ;',
            false,
        );
        $dummyDbi->addResult(
            'SELECT 1 FROM `mysql`.`user`',
            [['1']],
        );
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));
        $actual = $serverPrivileges->getHtmlForUserOverview('ltr', '');

        $this->assertStringContainsString('Your privilege table structure seems to be older than'
            . ' this MySQL version!<br>'
            . 'Please run the <code>mysql_upgrade</code> command'
            . ' that should be included in your MySQL server distribution'
            . ' to solve this problem!', $actual);
    }

    public function testGetHtmlForAllTableSpecificRights(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `mysql`;',
            [['columns_priv'], ['db'], ['tables_priv'], ['user']],
            ['Tables_in_mysql'],
        );
        $dummyDbi->addResult(
            'SHOW TABLES FROM `mysql`;',
            [['columns_priv'], ['db'], ['tables_priv'], ['user']],
            ['Tables_in_mysql'],
        );
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            '(SELECT DISTINCT `Table_name` FROM `mysql`.`columns_priv` WHERE `User` = \'pma\' AND `Host` = \'host\' AND `Db` LIKE \'pmadb\') ORDER BY `Table_name` ASC',
            [],
            ['Table_name'],
        );
        $dummyDbi->addResult(
            'SELECT `Table_name`, `Table_priv`, IF(`Column_priv` = _latin1 \'\', 0, 1) AS \'Column_priv\' FROM `mysql`.`tables_priv` WHERE `User` = \'pma\' AND `Host` = \'host\' AND `Db` LIKE \'pmadb\' ORDER BY `Table_name` ASC;',
            [],
            ['Table_name', 'Table_priv', 'Column_priv'],
        );
        $dummyDbi->addResult(
            '(SELECT DISTINCT `Db` FROM `mysql`.`tables_priv` WHERE `User` = \'pma2\' AND `Host` = \'host2\') UNION (SELECT DISTINCT `Db` FROM `mysql`.`columns_priv` WHERE `User` = \'pma2\' AND `Host` = \'host2\') ORDER BY `Db` ASC',
            [],
            ['Db'],
        );
        $dummyDbi->addResult(
            'SELECT * FROM `mysql`.`db` WHERE `User` = \'pma2\' AND `Host` = \'host2\' ORDER BY `Db` ASC',
            [],
            ['Host', 'Db', 'User', 'Select_priv', 'Insert_priv', 'Update_priv', 'Delete_priv', 'Create_priv', 'Drop_priv', 'Reload_priv', 'Shutdown_priv', 'Process_priv', 'File_priv', 'Grant_priv', 'References_priv', 'Index_priv', 'Alter_priv', 'Show_db_priv', 'Super_priv', 'Create_tmp_table_priv', 'Lock_tables_priv', 'Execute_priv', 'Repl_slave_priv', 'Repl_client_priv', 'Create_view_priv', 'Show_view_priv', 'Create_routine_priv', 'Alter_routine_priv', 'Create_user_priv', 'Event_priv', 'Trigger_priv', 'Create_tablespace_priv', 'Delete_history_priv'],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;
        $serverPrivileges = $this->getPrivileges($dbi);

        // Test case 1
        $actual = $serverPrivileges->getHtmlForAllTableSpecificRights('pma', 'host', 'table', 'pmadb');
        $this->assertStringContainsString('<input type="hidden" name="username" value="pma">', $actual);
        $this->assertStringContainsString('<input type="hidden" name="hostname" value="host">', $actual);
        $this->assertStringContainsString(
            '<div class="card-header js-submenu-label" data-submenu-label="Table">',
            $actual,
        );
        $this->assertStringContainsString('Table-specific privileges', $actual);

        // Test case 2
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['only_db'] = '';
        $dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['x'], ['y'], ['z']],
            ['SCHEMA_NAME'],
        );
        $actual = $serverPrivileges->getHtmlForAllTableSpecificRights('pma2', 'host2', 'database', '');
        $this->assertStringContainsString(
            '<div class="card-header js-submenu-label" data-submenu-label="Database">',
            $actual,
        );
        $this->assertStringContainsString('Database-specific privileges', $actual);
    }

    public function testGetHtmlForInitials(): void
    {
        $GLOBALS['lang'] = 'en';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SELECT DISTINCT UPPER(LEFT(`User`,1)) FROM `user` ORDER BY UPPER(LEFT(`User`,1)) ASC',
            [['-']],
        );

        $dbi = $this->createDatabaseInterface($dummyDbi);
        $serverPrivileges = $this->getPrivileges($dbi);

        $actual = $serverPrivileges->getHtmlForInitials(['"' => true]);
        $this->assertStringContainsString(
            '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">A</a>',
            $actual,
        );
        $this->assertStringContainsString(
            '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Z</a>',
            $actual,
        );
        $this->assertStringContainsString(
            '<a class="page-link" href="index.php?route=/server/privileges&initial=-&lang=en">-</a>',
            $actual,
        );
        $this->assertStringContainsString(
            '<a class="page-link" href="index.php?route=/server/privileges&initial=%22&lang=en">&quot;</a>',
            $actual,
        );
        $this->assertStringContainsString('Show all', $actual);
    }

    public function testGetDbRightsForUserOverview(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $resultStub = $this->createMock(DummyResult::class);

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue($resultStub));
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will($this->returnValue(['db', 'columns_priv']));
        $resultStub->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->onConsecutiveCalls(
                    ['User' => 'pmauser', 'Host' => 'local'],
                    [],
                ),
            );
        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $serverPrivileges->dbi = $dbi;

        $expected = [
            'pmauser' => [
                'local' => [
                    'User' => 'pmauser',
                    'Host' => 'local',
                    'Password' => '?',
                    'Grant_priv' => 'N',
                    'privs' => ['USAGE'],
                ],
            ],
        ];
        $actual = $serverPrivileges->getDbRightsForUserOverview('A');
        $this->assertEquals($expected, $actual);
    }

    public function testDeleteUser(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $resultStub = $this->createMock(DummyResult::class);

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->onConsecutiveCalls($resultStub, $resultStub, false));
        $dbi->expects($this->any())
            ->method('getError')
            ->will($this->returnValue('Some error occurred!'));
        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $serverPrivileges->dbi = $dbi;

        // Test case 1 : empty queries
        $queries = [];
        $actual = $serverPrivileges->deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals('', $actual[0]);
        $this->assertEquals(
            'No users selected for deleting!',
            $actual[1]->getMessage(),
        );

        // Test case 2 : all successful queries
        $_POST['mode'] = 3;
        $queries = ['foo'];
        $actual = $serverPrivileges->deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals("foo\n# Reloading the privileges …\nFLUSH PRIVILEGES;", $actual[0]);
        $this->assertEquals(
            'The selected users have been deleted successfully.',
            $actual[1]->getMessage(),
        );

        // Test case 3 : failing queries
        $_POST['mode'] = 1;
        $queries = ['bar'];
        $actual = $serverPrivileges->deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals('bar', $actual[0]);
        $this->assertEquals(
            'Some error occurred!' . "\n",
            $actual[1]->getMessage(),
        );
    }

    public function testGetFormForChangePassword(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $username = 'pma_username';
        $hostname = 'pma_hostname';

        $html = $serverPrivileges->getFormForChangePassword($username, $hostname, false, '/server/privileges');

        $this->assertStringContainsString(
            Url::getFromRoute('/server/privileges'),
            $html,
        );

        //Url::getHiddenInputs
        $this->assertStringContainsString(
            Url::getHiddenInputs(),
            $html,
        );

        //$username & $hostname
        $this->assertStringContainsString(
            htmlspecialchars($username),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($hostname),
            $html,
        );

        //labels
        $this->assertStringContainsString('change_password_form', $html);
        $this->assertStringContainsString(
            __('No Password'),
            $html,
        );
        $this->assertStringContainsString(
            __('Password:'),
            $html,
        );
        $this->assertStringContainsString(
            __('Password:'),
            $html,
        );
    }

    public function testGetUserPrivileges(): void
    {
        $mysqliResultStub = $this->createMock(ResultInterface::class);
        $mysqliStmtStub = $this->createMock(Statement::class);
        $mysqliStmtStub->expects($this->exactly(2))->method('execute')->willReturn(true);
        $mysqliStmtStub->expects($this->exactly(2))->method('getResult')->willReturn($mysqliResultStub);

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('isMariaDB')->willReturn(true);

        $userQuery = 'SELECT * FROM `mysql`.`user` WHERE `User` = ? AND `Host` = ?;';
        $globalPrivQuery = 'SELECT * FROM `mysql`.`global_priv` WHERE `User` = ? AND `Host` = ?;';
        $dbi->expects($this->exactly(2))->method('prepare')->willReturnMap([
            [$userQuery, Connection::TYPE_USER, $mysqliStmtStub],
            [$globalPrivQuery, Connection::TYPE_USER, $mysqliStmtStub],
        ]);

        $mysqliResultStub->expects($this->exactly(2))
            ->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls(
                ['Host' => 'test.host', 'User' => 'test.user'],
                ['Host' => 'test.host', 'User' => 'test.user', 'Priv' => '{"account_locked":true}'],
            );

        $relation = new Relation($this->dbi);
        $serverPrivileges = new Privileges(
            new Template(),
            $dbi,
            $relation,
            new RelationCleanup($this->dbi, $relation),
            new Plugins($this->dbi),
        );
        $method = new ReflectionMethod(Privileges::class, 'getUserPrivileges');

        /** @var array|null $actual */
        $actual = $method->invokeArgs($serverPrivileges, ['test.user', 'test.host', true]);

        $this->assertEquals(['Host' => 'test.host', 'User' => 'test.user', 'account_locked' => 'Y'], $actual);
    }

    private function getPrivileges(DatabaseInterface $dbi): Privileges
    {
        $relation = new Relation($dbi);

        return new Privileges(new Template(), $dbi, $relation, new RelationCleanup($dbi, $relation), new Plugins($dbi));
    }

    /**
     * data provider for testEscapeMysqlWildcards and testUnescapeMysqlWildcards
     *
     * @psalm-return list<array{string, string}>
     */
    public static function providerUnEscapeMysqlWildcards(): array
    {
        return [
            ['\_test', '_test'],
            ['\_\\', '_\\'],
            ['\\_\%', '_%'],
            ['\\\_', '\_'],
            ['\\\_\\\%', '\_\%'],
            ['\_\\%\_\_\%', '_%__%'],
            ['\%\_', '%_'],
            ['\\\%\\\_', '\%\_'],
        ];
    }

    /**
     * @param string $a Expected value
     * @param string $b String to escape
     *
     * @dataProvider providerUnEscapeMysqlWildcards
     */
    public function testEscapeMysqlWildcards(string $a, string $b): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);
        $this->assertEquals(
            $a,
            $serverPrivileges->escapeGrantWildcards($b),
        );
    }

    /**
     * @param string $a String to unescape
     * @param string $b Expected value
     *
     * @dataProvider providerUnEscapeMysqlWildcards
     */
    public function testUnescapeMysqlWildcards(string $a, string $b): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);
        $this->assertEquals(
            $b,
            $serverPrivileges->unescapeGrantWildcards($a),
        );
    }
}
