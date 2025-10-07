<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\Util;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function __;
use function _pgettext;
use function htmlspecialchars;
use function implode;
use function preg_quote;

#[CoversClass(Privileges::class)]
#[Medium]
class PrivilegesTest extends AbstractTestCase
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

    public function testGetUsernameParam(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());
        $requestTemplate = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');

        $request = $requestTemplate->withQueryParams(['username' => 'PMA_username']);
        $username = $serverPrivileges->getUsernameParam($request);
        self::assertSame('PMA_username', $username);
    }

    public function testGetHostnameParam(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());
        $requestTemplate = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');

        $request = $requestTemplate->withQueryParams(['hostname' => 'PMA_hostname']);
        $hostname = $serverPrivileges->getHostnameParam($request);
        self::assertSame('PMA_hostname', $hostname);
    }

    public function testGetDbname(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());
        $requestTemplate = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');

        $request = $requestTemplate->withQueryParams(['dbname' => 'PMA_dbname']);
        $dbname = $serverPrivileges->getDbname($request);
        self::assertSame('PMA_dbname', $dbname);

        //pre variable have been defined
        $request = $requestTemplate->withParsedBody([
            'pred_dbname' => ['PMA_pred_dbname'],
        ]);
        $dbname = $serverPrivileges->getDbname($request);
        self::assertSame('PMA_pred_dbname', $dbname);

        // Escaped database
        $request = $requestTemplate->withParsedBody([
            'pred_dbname' => ['PMA\_pred\_dbname'],
        ]);
        $dbname = $serverPrivileges->getDbname($request);
        self::assertSame('PMA\_pred\_dbname', $dbname);

        // Multiselect database - pred
        $request = $requestTemplate->withParsedBody([
            'pred_dbname' => ['PMA\_pred\_dbname', 'PMADbname2'],
        ]);
        $dbname = $serverPrivileges->getDbname($request);
        self::assertSame(['PMA\_pred\_dbname', 'PMADbname2'], $dbname);

        // Multiselect database
        $request = $requestTemplate->withParsedBody([
            'dbname' => ['PMA\_dbname', 'PMADbname2'],
        ]);
        $dbname = $serverPrivileges->getDbname($request);
        self::assertSame(['PMA\_dbname', 'PMADbname2'], $dbname);
    }

    public function testGetTablename(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());
        $requestTemplate = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');

        $request = $requestTemplate->withQueryParams(['tablename' => 'PMA_tablename']);
        $tablename = $serverPrivileges->getTablename($request);
        self::assertSame('PMA_tablename', $tablename);

        $request = $requestTemplate->withParsedBody(['pred_tablename' => 'PMA_pred__tablename']);
        $tablename = $serverPrivileges->getTablename($request);
        self::assertSame('PMA_pred__tablename', $tablename);

        $request = $requestTemplate->withParsedBody(['pred_tablename' => 'PMA_pred__tablename']);
        $tablename = $serverPrivileges->getTablename($request);
        self::assertSame('PMA_pred__tablename', $tablename);

        $request = $requestTemplate->withParsedBody([]);
        $tablename = $serverPrivileges->getTablename($request);
        self::assertNull($tablename);
    }

    public function testIsDatabaseNameWildcard(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        self::assertTrue($serverPrivileges->isDatabaseNameWildcard('PMA_dbname'));
        self::assertTrue($serverPrivileges->isDatabaseNameWildcard('PMA_pred_dbname'));

        self::assertFalse($serverPrivileges->isDatabaseNameWildcard('PMA\_pred\_dbname'));
        self::assertFalse($serverPrivileges->isDatabaseNameWildcard(['PMA\_pred\_dbname', 'PMADbname2']));
        self::assertFalse($serverPrivileges->isDatabaseNameWildcard(['PMA\_dbname', 'PMADbname2']));
    }

    public function testWildcardEscapeForGrant(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $dbname = '';
        $tablename = '';
        $dbAndTable = $serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        self::assertSame('*.*', $dbAndTable);

        $dbname = 'dbname';
        $tablename = '';
        $dbAndTable = $serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        self::assertSame('`dbname`.*', $dbAndTable);

        $dbname = 'dbname';
        $tablename = 'tablename';
        $dbAndTable = $serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        self::assertSame('`dbname`.`tablename`', $dbAndTable);
    }

    public function testRangeOfUsers(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $ret = $serverPrivileges->rangeOfUsers('INIT');
        self::assertSame('WHERE `User` LIKE \'INIT%\' OR `User` LIKE \'init%\'', $ret);

        $ret = $serverPrivileges->rangeOfUsers('%');
        self::assertSame('WHERE `User` LIKE \'\\\\%%\' OR `User` LIKE \'\\\\%%\'', $ret);

        $ret = $serverPrivileges->rangeOfUsers('');
        self::assertSame("WHERE `User` = ''", $ret);

        $ret = $serverPrivileges->rangeOfUsers();
        self::assertSame('', $ret);
    }

    public function testGetTableGrantsArray(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $ret = $serverPrivileges->getTableGrantsArray();
        self::assertSame(
            ['Delete', 'DELETE', __('Allows deleting data.')],
            $ret[0],
        );
        self::assertSame(
            ['Create', 'CREATE', __('Allows creating new tables.')],
            $ret[1],
        );
    }

    public function testGetGrantsArray(): void
    {
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface());

        $ret = $serverPrivileges->getGrantsArray();
        self::assertSame(
            ['Select_priv', 'SELECT', __('Allows reading data.')],
            $ret[0],
        );
        self::assertSame(
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
            . ' WHERE `User` = ' . $dbi->quoteString($username)
            . ' AND `Host` = ' . $dbi->quoteString($hostname) . ';';
        self::assertSame($sql, $ret);

        //$table == '*'
        $db = 'pma_db';
        $table = '*';
        $ret = $serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $sql = 'SELECT * FROM `mysql`.`db`'
            . ' WHERE `User` = ' . $dbi->quoteString($username)
            . ' AND `Host` = ' . $dbi->quoteString($hostname)
            . ' AND `Db` = \'' . $db . '\'';

        self::assertSame($sql, $ret);

        //$table == 'pma_table'
        $db = 'pma_db';
        $table = 'pma_table';
        $ret = $serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $sql = 'SELECT `Table_priv`'
            . ' FROM `mysql`.`tables_priv`'
            . ' WHERE `User` = ' . $dbi->quoteString($username)
            . ' AND `Host` = ' . $dbi->quoteString($hostname)
            . " AND `Db` = '" . $serverPrivileges->unescapeGrantWildcards($db) . "'"
            . ' AND `Table_name` = ' . $dbi->quoteString($table) . ';';
        self::assertSame($sql, $ret);

        // SQL escaping
        $db = "db' AND";
        $table = 'pma_table';
        $ret = $serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        self::assertSame(
            'SELECT `Table_priv` FROM `mysql`.`tables_priv` '
            . "WHERE `User` = 'pma_username' AND "
            . "`Host` = 'pma_hostname' AND `Db` = 'db\' AND' AND "
            . "`Table_name` = 'pma_table';",
            $ret,
        );
    }

    public function testGetDataForChangeOrCopyUser(): void
    {
        Current::$lang = 'en';

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
        self::assertSame('pma_password', $password);
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

        self::assertStringContainsString('grant user2 delete', $export);
        self::assertStringContainsString('grant user1 select', $export);
        self::assertStringContainsString('<textarea class="export"', $export);
    }

    public function testAddUser(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT \'1\' FROM `mysql`.`user` WHERE `User` = \'\' AND `Host` = \'localhost\';', []);
        $dummyDbi->addResult('SET `old_passwords` = 0;', true);
        $dummyDbi->addResult(
            'CREATE USER \'\'@\'localhost\' IDENTIFIED WITH mysql_native_password AS \'pma_dbname\';',
            true,
        );
        $dummyDbi->addResult('GRANT USAGE ON *.* TO \'\'@\'localhost\' REQUIRE NONE;', true);
        $dummyDbi->addResult('GRANT ALL PRIVILEGES ON `pma_dbname`.* TO \'\'@\'localhost\';', true);

        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dbi->setVersion(['@@version' => '5.7.6', '@@version_comment' => 'MySQL Community Server (GPL)']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $serverPrivileges->username = 'pma_username';
        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'localhost';
        $_POST['pred_username'] = 'any';
        $_POST['pred_password'] = 'keep';
        $_POST['createdb-3'] = true;
        $_POST['userGroup'] = 'username';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        [
            $retMessage,,,
            $sqlQuery,
            $addUserError,
        ] = $serverPrivileges->addUser($dbname, $username, $hostname, $dbname, true);
        self::assertInstanceOf(Message::class, $retMessage);
        self::assertSame(
            'You have added a new user.',
            $retMessage->getMessage(),
        );
        self::assertSame(
            "CREATE USER ''@'localhost' IDENTIFIED WITH mysql_native_password AS '***';"
            . "GRANT USAGE ON *.* TO ''@'localhost' REQUIRE NONE;"
            . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';",
            $sqlQuery,
        );
        self::assertFalse($addUserError);
    }

    public function testAddUserOld(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT \'1\' FROM `mysql`.`user` WHERE `User` = \'\' AND `Host` = \'localhost\';', []);
        $dummyDbi->addResult('SET `old_passwords` = 0;', true);
        $dummyDbi->addResult('CREATE USER \'\'@\'localhost\';', true);
        $dummyDbi->addResult('SET `old_passwords` = 0;', true);
        $dummyDbi->addResult('SET PASSWORD FOR \'\'@\'localhost\' = \'pma_dbname\';', true);
        $dummyDbi->addResult('GRANT USAGE ON *.* TO \'\'@\'localhost\' REQUIRE NONE;', true);
        $dummyDbi->addResult('GRANT ALL PRIVILEGES ON `pma_dbname`.* TO \'\'@\'localhost\';', true);

        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dbi->setVersion(['@@version' => '5.5.6', '@@version_comment' => 'MySQL Community Server (GPL)']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $serverPrivileges->username = 'username';
        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'localhost';
        $_POST['pred_username'] = 'any';
        $_POST['pred_password'] = 'keep';
        $_POST['createdb-3'] = true;
        $_POST['userGroup'] = 'username';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        [
            $retMessage,,,
            $sqlQuery,
            $addUserError,
        ] = $serverPrivileges->addUser($dbname, $username, $hostname, $dbname, true);

        self::assertInstanceOf(Message::class, $retMessage);
        self::assertSame(
            'You have added a new user.',
            $retMessage->getMessage(),
        );
        self::assertSame(
            "CREATE USER ''@'localhost';"
            . "GRANT USAGE ON *.* TO ''@'localhost' REQUIRE NONE;"
            . "SET PASSWORD FOR ''@'localhost' = '***';"
            . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';",
            $sqlQuery,
        );
        self::assertFalse($addUserError);
    }

    public function testUpdatePassword(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'ALTER USER \'pma_username\'@\'pma_hostname\' IDENTIFIED WITH mysql_native_password BY \'pma_pw\'',
            true,
        );
        $dummyDbi->addResult('FLUSH PRIVILEGES;', true);
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $errUrl = 'error.php';
        $_POST['pma_pw'] = 'pma_pw';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        $message = $serverPrivileges->updatePassword($errUrl, $username, $hostname);

        self::assertSame(
            'The password for \'pma_username\'@\'pma_hostname\' was changed successfully.',
            $message->getMessage(),
        );
    }

    public function testGetMessageAndSqlQueryForPrivilegesRevoke(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'REVOKE ALL PRIVILEGES ON `pma_dbname`.`pma_tablename` FROM \'pma_username\'@\'pma_hostname\';',
            true,
        );
        $dummyDbi->addResult(
            'REVOKE GRANT OPTION ON `pma_dbname`.`pma_tablename` FROM \'pma_username\'@\'pma_hostname\';',
            true,
        );

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
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

        self::assertSame(
            "You have revoked the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage(),
        );
        self::assertSame(
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
            true,
        );
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_POST['createdb-3'] = true;
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        [$sqlQuery, $message] = $serverPrivileges->updatePrivileges($username, $hostname, $tablename, $dbname, '');

        self::assertSame(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage(),
        );
        self::assertSame(
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

        $dbi->expects(self::any())->method('getVersion')
            ->willReturn(8003);
        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $serverPrivileges->dbi = $dbi;

        [$sqlQuery, $message] = $serverPrivileges->updatePrivileges($username, $hostname, $tablename, $dbname, '');

        self::assertSame(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage(),
        );
        self::assertSame(
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

        $dbi->expects(self::any())->method('getVersion')
            ->willReturn(80011);
        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $serverPrivileges->dbi = $dbi;

        [$sqlQuery, $message] = $serverPrivileges->updatePrivileges($username, $hostname, $tablename, $dbname, '');

        self::assertSame(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage(),
        );
        self::assertSame(
            '  GRANT USAGE ON  *.* TO \'pma_username\'@\'pma_hostname\';'
            . ' ALTER USER \'pma_username\'@\'pma_hostname\'  REQUIRE NONE'
            . ' WITH MAX_QUERIES_PER_HOUR 1000 MAX_CONNECTIONS_PER_HOUR'
            . ' 20 MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40;',
            $sqlQuery,
        );
    }

    public function testGetHtmlToDisplayPrivilegesTable(): void
    {
        $dbi = $this->createDatabaseInterface();

        $serverPrivileges = $this->getPrivileges($dbi);
        $serverPrivileges->username = 'username';
        $serverPrivileges->hostname = 'hostname';
        $html = $serverPrivileges->getHtmlToDisplayPrivilegesTable();

        //validate 2: button
        self::assertStringContainsString('Update user privileges', $html);

        //validate 3: getHtmlForGlobalOrDbSpecificPrivs
        self::assertStringContainsString('<div class="card">', $html);
        self::assertStringContainsString(
            '<div class="card-header js-submenu-label" data-submenu-label="' . __('Global') . '">',
            $html,
        );
        self::assertStringContainsString(
            __('Global privileges'),
            $html,
        );
        self::assertStringContainsString(
            __('Check all'),
            $html,
        );
        self::assertStringContainsString(
            __('Note: MySQL privilege names are expressed in English'),
            $html,
        );

        //validate 4: getHtmlForGlobalPrivTableWithCheckboxes items
        //Select_priv
        self::assertStringContainsString('<input type="checkbox" class="checkall" name="Select_priv"', $html);
        //Create_user_priv
        self::assertStringContainsString('<input type="checkbox" class="checkall" name="Create_user_priv"', $html);
        //Insert_priv
        self::assertStringContainsString('<input type="checkbox" class="checkall" name="Insert_priv"', $html);
        //Update_priv
        self::assertStringContainsString('<input type="checkbox" class="checkall" name="Update_priv"', $html);
        //Create_priv
        self::assertStringContainsString('<input type="checkbox" class="checkall" name="Create_priv"', $html);
        //Create_routine_priv
        self::assertStringContainsString('<input type="checkbox" class="checkall" name="Create_routine_priv"', $html);
        //Execute_priv
        self::assertStringContainsString('<input type="checkbox" class="checkall" name="Execute_priv"', $html);

        //validate 5: getHtmlForResourceLimits
        self::assertStringContainsString(
            '<div class="card-header">' . __('Resource limits') . '</div>',
            $html,
        );
        self::assertStringContainsString(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html,
        );
        self::assertStringContainsString('MAX QUERIES PER HOUR', $html);
        self::assertStringContainsString('id="text_max_updates" value="0"', $html);
        self::assertStringContainsString(
            __('Limits the number of new connections the user may open per hour.'),
            $html,
        );
        self::assertStringContainsString(
            __('Limits the number of simultaneous connections the user may have.'),
            $html,
        );

        self::assertStringContainsString('<div class="card-header">SSL</div>', $html);
        self::assertStringContainsString('value="NONE"', $html);
        self::assertStringContainsString('value="ANY"', $html);
        self::assertStringContainsString('value="X509"', $html);
        self::assertStringContainsString('value="SPECIFIED"', $html);
    }

    public function testGetSqlQueriesForDisplayAndAddUserMySql8011(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SET `old_passwords` = 0;', true);
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
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password BY \'pma_password\';',
            $createUserReal,
        );

        //validate 2: $create_user_show
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password BY \'***\';',
            $createUserShow,
        );
    }

    public function testGetSqlQueriesForDisplayAndAddUserMySql8016(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;

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
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED BY \'pma_password\';',
            $createUserReal,
        );

        //validate 2: $createUserShow
        self::assertSame('CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED BY \'***\';', $createUserShow);
    }

    public function testGetSqlQueriesForDisplayAndAddUserMySql50500AndUserDefinedPassword(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $dbi->setVersion(['@@version' => '5.5.0']);

        $dbiDummy->addResult('SELECT PASSWORD(\'pma_password\');', [['*ABCDEF']], ['PASSWORD(\'pma_password\')']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';
        $password = 'pma_password';
        $_POST['pred_password'] = 'userdefined';
        $_POST['pma_pw'] = 'pma_password';

        [, , , , $passwordSetReal, $passwordSetShow] = $serverPrivileges->getSqlQueriesForDisplayAndAddUser(
            $username,
            $hostname,
            $password,
        );

        self::assertSame('SET PASSWORD FOR \'PMA_username\'@\'PMA_hostname\' = \'*ABCDEF\';', $passwordSetReal);
        self::assertSame('SET PASSWORD FOR \'PMA_username\'@\'PMA_hostname\' = \'***\';', $passwordSetShow);

        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetSqlQueriesForDisplayAndAddUserWithUserDefinedPassword(): void
    {
        $dbi = $this->createDatabaseInterface();
        $dbi->setVersion(['@@version' => '10.4.3-MariaDB']);

        $serverPrivileges = $this->getPrivileges($dbi);

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';
        $password = 'pma_password';
        $_POST['pred_password'] = 'userdefined';
        $_POST['pma_pw'] = 'pma_password';

        [$createUserReal, $createUserShow] = $serverPrivileges->getSqlQueriesForDisplayAndAddUser(
            $username,
            $hostname,
            $password,
        );

        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED BY \'pma_password\';',
            $createUserReal,
        );
        self::assertSame('CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED BY \'***\';', $createUserShow);
    }

    public function testGetSqlQueriesForDisplayAndAddUser(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SET `old_passwords` = 0;', true);
        $dummyDbi->addResult('GRANT USAGE ON *.* TO \'PMA_username\'@\'PMA_hostname\' REQUIRE NONE;', true);

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
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password AS \'pma_password\';',
            $createUserReal,
        );

        //validate 2: $createUserShow
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password AS \'***\';',
            $createUserShow,
        );

        //validate 3:$realSqlQuery
        self::assertSame('GRANT USAGE ON *.* TO \'PMA_username\'@\'PMA_hostname\' REQUIRE NONE;', $realSqlQuery);

        //validate 4:$sqlQuery
        self::assertSame('GRANT USAGE ON *.* TO \'PMA_username\'@\'PMA_hostname\' REQUIRE NONE;', $sqlQuery);

        self::assertSame('', $alterRealSqlQuery);

        self::assertSame('', $alterSqlQuery);

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
        self::assertSame('GRANT USAGE ON *.* TO \'PMA_username\'@\'PMA_hostname\' REQUIRE NONE;', $sqlQuery);

        //validate 6: $message
        self::assertSame(
            'You have added a new user.',
            $message->getMessage(),
        );
    }

    public function testGetHtmlToDisplayPrivilegesTableWithTableSpecific(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $dbi = $this->createDatabaseInterface();
        $serverPrivileges->dbi = $dbi;

        $serverPrivileges->username = 'PMA_username';
        $serverPrivileges->hostname = 'PMA_hostname';
        $html = $serverPrivileges->getHtmlToDisplayPrivilegesTable('PMA_db', 'PMA_table');

        self::assertStringContainsString('checkbox_Update_priv_none', $html);
        self::assertStringContainsString('<dfn title="Allows changing data.">UPDATE</dfn>', $html);
        self::assertStringContainsString('checkbox_Insert_priv_none', $html);
        self::assertStringContainsString(
            __('Allows reading data.'),
            $html,
        );
        self::assertStringContainsString(
            __('Allows inserting and replacing data'),
            $html,
        );
        self::assertStringContainsString(
            __('Allows changing data.'),
            $html,
        );
        self::assertStringContainsString(
            __('Has no effect in this MySQL version.'),
            $html,
        );

        self::assertStringContainsString('title="Allows performing SHOW CREATE VIEW queries." checked>', $html);
        self::assertStringContainsString('<dfn title="Allows creating new views.">', $html);
        self::assertStringContainsString('CREATE VIEW', $html);
        self::assertStringContainsString('Create_view_priv', $html);
        self::assertStringContainsString('Show_view_priv', $html);
        self::assertStringContainsString(
            _pgettext('None privileges', 'None'),
            $html,
        );
    }

    public function testGetHtmlForLoginInformationFields(): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);

        $serverPrivileges->username = 'pma_username';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fieldsInfo = [
            ['COLUMN_NAME' => 'Host', 'CHARACTER_MAXIMUM_LENGTH' => 80],
            ['COLUMN_NAME' => 'User', 'CHARACTER_MAXIMUM_LENGTH' => 40],
        ];
        $dbi->expects(self::any())->method('fetchResult')
            ->willReturn($fieldsInfo);
        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $serverPrivileges->dbi = $dbi;

        $html = $serverPrivileges->getHtmlForLoginInformationFields();

        //validate 1: __('Login Information')
        self::assertStringContainsString(
            __('Login Information'),
            $html,
        );
        self::assertStringContainsString(
            __('User name:'),
            $html,
        );
        self::assertStringContainsString(
            __('Any user'),
            $html,
        );
        self::assertStringContainsString(
            __('Use text field'),
            $html,
        );

        $output = Generator::showHint(
            __(
                'When Host table is used, this field is ignored and values stored in Host table are used instead.',
            ),
        );
        self::assertStringContainsString($output, $html);
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
        self::assertStringContainsString($expect, $sqlQuery);
    }

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
        $dbi->expects(self::any())->method('fetchResult')
            ->willReturn($fieldsInfo);
        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $dbi->expects(self::any())->method('isGrantUser')
            ->willReturn(true);

        $serverPrivileges->dbi = $dbi;

        $dbname = 'pma_dbname';

        $html = $serverPrivileges->getHtmlForAddUser($dbname);

        //validate 1: Url::getHiddenInputs
        self::assertStringContainsString(
            Url::getHiddenInputs('', ''),
            $html,
        );

        //validate 2: getHtmlForLoginInformationFields
        self::assertStringContainsString(
            $serverPrivileges->getHtmlForLoginInformationFields(),
            $html,
        );

        //validate 3: Database for user
        self::assertStringContainsString(
            __('Database for user'),
            $html,
        );

        self::assertStringContainsString(
            __('Grant all privileges on wildcard name (username\\_%).'),
            $html,
        );
        self::assertStringContainsString('<input type="checkbox" name="createdb-2" id="createdb-2">', $html);

        //validate 4: getHtmlToDisplayPrivilegesTable
        self::assertStringContainsString(
            $serverPrivileges->getHtmlToDisplayPrivilegesTable('*', '*', false),
            $html,
        );

        //validate 5: button
        self::assertStringContainsString('Create user', $html);
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
        self::assertStringContainsString($urlHtml, $html);
        self::assertStringContainsString(
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
        self::assertStringContainsString($urlHtml, $html);
        self::assertStringContainsString(
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
        self::assertStringContainsString($urlHtml, $html);
        self::assertStringContainsString(
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
        self::assertStringContainsString($urlHtml, $html);
        self::assertStringContainsString(
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
        self::assertStringContainsString($urlHtml, $html);
        self::assertStringContainsString(
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
        self::assertStringContainsString($urlHtml, $html);
        self::assertStringContainsString(
            __('Export'),
            $html,
        );
    }

    public function testGetExtraDataForAjaxBehavior(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT * FROM `mysql`.`user` WHERE `User` = \'username\';', []);

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $password = 'pma_password';
        $sqlQuery = 'pma_sql_query';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $dbname = 'pma_dbname';
        $_POST['username'] = 'username';
        $_POST['change_copy'] = 'change_copy';
        $_GET['validate_username'] = 'validate_username';
        $_GET['username'] = 'username';
        $_POST['update_privs'] = 'update_privs';

        $extraData = $serverPrivileges->getExtraDataForAjaxBehavior(
            $password,
            $sqlQuery,
            $hostname,
            $username,
            $dbname,
        );

        //user_exists
        self::assertFalse($extraData['user_exists']);

        //db_wildcard_privs
        self::assertTrue($extraData['db_wildcard_privs']);

        //user_exists
        self::assertFalse($extraData['db_specific_privs']);

        //new_user_initial
        self::assertSame('P', $extraData['new_user_initial']);

        //sql_query
        self::assertSame(
            Generator::getMessage('', $sqlQuery),
            $extraData['sql_query'],
        );

        //new_user_string
        self::assertIsString($extraData['new_user_string']);
        self::assertStringContainsString(
            htmlspecialchars($hostname),
            $extraData['new_user_string'],
        );
        self::assertStringContainsString(
            htmlspecialchars($username),
            $extraData['new_user_string'],
        );

        //new_privileges
        self::assertIsString($extraData['new_privileges']);
        self::assertStringContainsString(
            implode(', ', $serverPrivileges->extractPrivInfo(null, true)),
            $extraData['new_privileges'],
        );
    }

    public function testGetUserGroupForUser(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::USERS => 'users',
            RelationParameters::USER_GROUPS => 'usergroups',
            RelationParameters::MENUS_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SELECT `usergroup` FROM `pmadb`.`users` WHERE `username` = \'pma_username\' LIMIT 1',
            [['pma_usergroup']],
        );

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        self::assertSame('pma_usergroup', $serverPrivileges->getUserGroupForUser('pma_username'));
    }

    public function testGetUsersOverview(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::USERS => 'users',
            RelationParameters::USER_GROUPS => 'usergroups',
            RelationParameters::MENUS_WORK => true,
            RelationParameters::TRACKING_WORK => true,
            RelationParameters::TRACKING => 'tracking',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT * FROM `pmadb`.`users`', []);
        $dummyDbi->addResult('SELECT COUNT(*) FROM `pmadb`.`usergroups`', [['0']]);

        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));

        $resultStub = $this->createMock(DummyResult::class);
        $dbRights = [];

        $html = $serverPrivileges->getUsersOverview($resultStub, $dbRights);

        //Url::getHiddenInputs
        self::assertStringContainsString(
            Url::getHiddenInputs('', ''),
            $html,
        );

        //items
        self::assertStringContainsString(
            __('User'),
            $html,
        );
        self::assertStringContainsString(
            __('Host'),
            $html,
        );
        self::assertStringContainsString(
            __('Password'),
            $html,
        );
        self::assertStringContainsString(
            __('Global privileges'),
            $html,
        );

        //Util::showHint
        self::assertStringContainsString(
            Generator::showHint(
                __('Note: MySQL privilege names are expressed in English.'),
            ),
            $html,
        );

        //__('User group')
        self::assertStringContainsString(
            __('User group'),
            $html,
        );
        self::assertStringContainsString(
            __('Grant'),
            $html,
        );
        self::assertStringContainsString(
            __('Action'),
            $html,
        );

        self::assertStringContainsString(
            Url::getCommon(['adduser' => 1], ''),
            $html,
        );

        //labels
        self::assertStringContainsString(
            __('Add user account'),
            $html,
        );
        self::assertStringContainsString(
            __('Remove selected user accounts'),
            $html,
        );
        self::assertStringContainsString(
            __('Drop the databases that have the same names as the users.'),
            $html,
        );
        self::assertStringContainsString(
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
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $queries = [];

        $ret = $serverPrivileges->getDataForDeleteUsers($queries);

        $item = ["# Deleting 'old_username'@'old_hostname' ...", "DROP USER 'old_username'@'old_hostname';"];
        self::assertSame($item, $ret);
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
        self::assertStringContainsString(
            __('Edit privileges:'),
            $html,
        );
        self::assertStringContainsString(
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
        self::assertStringContainsString($item, $html);

        //$username & $hostname
        self::assertStringContainsString(
            htmlspecialchars($username),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($hostname),
            $html,
        );

        //$dbnameIsWildcard = true
        self::assertStringContainsString(
            __('Databases'),
            $html,
        );

        //$dbnameIsWildcard = true
        self::assertStringContainsString(
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
        self::assertStringContainsString($item, $html);
        self::assertStringContainsString($dbname, $html);
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

        $serverPrivileges->username = 'user';
        $serverPrivileges->hostname = 'host';

        $actual = $serverPrivileges->getHtmlForUserProperties(
            false,
            'sakila',
            'user',
            'host',
            'sakila',
            'actor',
            '/server/privileges',
        );
        self::assertStringContainsString('addUsersForm', $actual);
        self::assertStringContainsString('SELECT', $actual);
        self::assertStringContainsString('Allows reading data.', $actual);
        self::assertStringContainsString('INSERT', $actual);
        self::assertStringContainsString('Allows inserting and replacing data.', $actual);
        self::assertStringContainsString('UPDATE', $actual);
        self::assertStringContainsString('Allows changing data.', $actual);
        self::assertStringContainsString('DELETE', $actual);
        self::assertStringContainsString('Allows deleting data.', $actual);
        self::assertStringContainsString('CREATE', $actual);
        self::assertStringContainsString('Allows creating new tables.', $actual);

        self::assertStringContainsString(
            Url::getHiddenInputs(),
            $actual,
        );

        //$username & $hostname
        self::assertStringContainsString('user', $actual);
        self::assertStringContainsString('host', $actual);

        //Create a new user with the same privileges
        self::assertStringContainsString('Create a new user account with the same privileges', $actual);

        self::assertStringContainsString(
            __('Database'),
            $actual,
        );
        $config = Config::getInstance();
        self::assertStringContainsString(
            Url::getFromRoute(Config::getInstance()->settings['DefaultTabDatabase']),
            $actual,
        );
        $item = Url::getCommon(['db' => 'sakila', 'reload' => 1], '');
        self::assertStringContainsString($item, $actual);
        self::assertStringContainsString('sakila', $actual);

        //$tablename
        self::assertStringContainsString(
            __('Table'),
            $actual,
        );
        self::assertStringContainsString(
            Url::getFromRoute($config->settings['DefaultTabTable']),
            $actual,
        );
        $item = Url::getCommon(['db' => 'sakila', 'table' => 'actor', 'reload' => 1], '');
        self::assertStringContainsString($item, $actual);
        self::assertStringContainsString('table', $actual);
        $item = Util::getTitleForTarget($config->settings['DefaultTabTable']);
        self::assertStringContainsString($item, $actual);
    }

    public function testGetHtmlForUserOverview(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;
        Current::$lang = 'en';
        $userPrivileges = new UserPrivileges(isReload: true);

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS `Password` FROM `mysql`.`user` ORDER BY `User` ASC, `Host` ASC',
            [
                ['localhost', 'pma', 'password', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
                ['localhost', 'root', 'password', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
            ],
            ['Host', 'User', 'Password', 'Select_priv', 'Insert_priv', 'Update_priv', 'Delete_priv', 'Create_priv', 'Drop_priv', 'Reload_priv', 'Shutdown_priv', 'Process_priv', 'File_priv', 'Grant_priv', 'References_priv', 'Index_priv', 'Alter_priv', 'Show_db_priv', 'Super_priv', 'Create_tmp_table_priv', 'Lock_tables_priv', 'Execute_priv', 'Repl_slave_priv', 'Repl_client_priv', 'Create_view_priv', 'Show_view_priv', 'Create_routine_priv', 'Alter_routine_priv', 'Create_user_priv', 'Event_priv', 'Trigger_priv', 'Create_tablespace_priv', 'Delete_history_priv', 'ssl_type', 'ssl_cipher', 'x509_issuer', 'x509_subject', 'max_questions', 'max_updates', 'max_connections', 'max_user_connections', 'plugin', 'authentication_string', 'password_expired', 'is_role', 'default_role', 'max_statement_time', 'Password'],
        );
        $dummyDbi->addResult('SELECT COUNT(*) FROM `mysql`.`user`', [[2]]);
        $dummyDbi->addResult(
            'SHOW TABLES FROM `mysql`',
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
        $actual = $serverPrivileges->getHtmlForUserOverview($userPrivileges, null);
        self::assertStringContainsString('Note: MySQL privilege names are expressed in English.', $actual);
        self::assertStringContainsString(
            'Note: phpMyAdmin gets the users’ privileges directly from MySQL’s privilege tables.',
            $actual,
        );

        // the user does not have enough privileges
        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS `Password` FROM `mysql`.`user` ORDER BY `User` ASC, `Host` ASC',
            false,
        );
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS `Password` FROM `mysql`.`user` ',
            false,
        );
        $dummyDbi->addResult('SELECT 1 FROM `mysql`.`user`', false);
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));
        $html = $serverPrivileges->getHtmlForUserOverview($userPrivileges, null);

        self::assertStringContainsString(
            Url::getCommon(['adduser' => 1], ''),
            $html,
        );
        self::assertStringContainsString(
            Generator::getIcon('b_usradd', 'Add user account'),
            $html,
        );
        self::assertStringContainsString(
            __('Add user'),
            $html,
        );

        // MySQL has older table structure
        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS `Password` FROM `mysql`.`user` ORDER BY `User` ASC, `Host` ASC',
            false,
        );
        $dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS `Password` FROM `mysql`.`user` ',
            false,
        );
        $dummyDbi->addResult('SELECT 1 FROM `mysql`.`user`', [[1]]);
        $serverPrivileges = $this->getPrivileges($this->createDatabaseInterface($dummyDbi));
        $actual = $serverPrivileges->getHtmlForUserOverview($userPrivileges, null);

        self::assertStringContainsString('Your privilege table structure seems to be older than'
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
        DatabaseInterface::$instance = $dbi;
        $serverPrivileges = $this->getPrivileges($dbi);

        // Test case 1
        $actual = $serverPrivileges->getHtmlForAllTableSpecificRights('pma', 'host', 'table', 'pmadb');
        self::assertStringContainsString('<input type="hidden" name="username" value="pma">', $actual);
        self::assertStringContainsString('<input type="hidden" name="hostname" value="host">', $actual);
        self::assertStringContainsString(
            '<div class="card-header js-submenu-label" data-submenu-label="Table">',
            $actual,
        );
        self::assertStringContainsString('Table-specific privileges', $actual);

        // Test case 2
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['only_db'] = '';
        $dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['x'], ['y'], ['z']],
            ['SCHEMA_NAME'],
        );
        $actual = $serverPrivileges->getHtmlForAllTableSpecificRights('pma2', 'host2', 'database', '');
        self::assertStringContainsString(
            '<div class="card-header js-submenu-label" data-submenu-label="Database">',
            $actual,
        );
        self::assertStringContainsString('Database-specific privileges', $actual);
    }

    public function testGetHtmlForInitials(): void
    {
        Current::$lang = 'en';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT COUNT(*) FROM `mysql`.`user`', [[21]]);
        $dummyDbi->addResult(
            'SELECT DISTINCT UPPER(LEFT(`User`, 1)) FROM `user`',
            [['C'], ['-'], ['"'], ['%'], ['\\'], ['']],
        );

        $dbi = $this->createDatabaseInterface($dummyDbi);
        $serverPrivileges = $this->getPrivileges($dbi);

        $actual = $serverPrivileges->getHtmlForInitials();
        self::assertStringContainsString(
            '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">A</a>',
            $actual,
        );
        self::assertMatchesRegularExpression(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=C&lang=en">\s*C\s*<\/a>/',
            $actual,
        );
        self::assertStringContainsString(
            '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Z</a>',
            $actual,
        );
        self::assertMatchesRegularExpression(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=-&lang=en">\s*-\s*<\/a>/',
            $actual,
        );
        self::assertMatchesRegularExpression(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=%22&lang=en">\s*&quot;\s*<\/a>/',
            $actual,
        );
        self::assertMatchesRegularExpression(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=%25&lang=en">\s*%\s*<\/a>/',
            $actual,
        );
        self::assertMatchesRegularExpression(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=%5C&lang=en">\s*\\\\\s*<\/a>/',
            $actual,
        );
        self::assertMatchesRegularExpression(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=&lang=en">\s*' .
                '<span class="text-danger text-nowrap">' . preg_quote(__('Any')) . '<\/span>' .
                '\s*<\/a>/',
            $actual,
        );
        self::assertStringContainsString('Show all', $actual);
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
        $dbi->expects(self::any())
            ->method('query')
            ->willReturn($resultStub);
        $dbi->expects(self::any())
            ->method('fetchResult')
            ->willReturn(['db', 'columns_priv']);
        $resultStub->expects(self::any())
            ->method('fetchAssoc')
            ->willReturn(['User' => 'pmauser', 'Host' => 'local'], []);
        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

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
        self::assertSame($expected, $actual);
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
        $dbi->expects(self::any())
            ->method('tryQuery')
            ->willReturn($resultStub, $resultStub, false);
        $dbi->expects(self::any())
            ->method('getError')
            ->willReturn('Some error occurred!');
        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $serverPrivileges->dbi = $dbi;

        // Test case 1 : empty queries
        $queries = [];
        $actual = $serverPrivileges->deleteUser($queries);
        self::assertSame('', $actual[0]);
        self::assertSame(
            'No users selected for deleting!',
            $actual[1]->getMessage(),
        );

        // Test case 2 : all successful queries
        $_POST['mode'] = 3;
        $queries = ['foo'];
        $actual = $serverPrivileges->deleteUser($queries);
        self::assertSame("foo\n# Reloading the privileges …\nFLUSH PRIVILEGES;", $actual[0]);
        self::assertSame(
            'The selected users have been deleted successfully.',
            $actual[1]->getMessage(),
        );

        // Test case 3 : failing queries
        $_POST['mode'] = 1;
        $queries = ['bar'];
        $actual = $serverPrivileges->deleteUser($queries);
        self::assertSame('bar', $actual[0]);
        self::assertSame(
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

        self::assertStringContainsString(
            Url::getFromRoute('/server/privileges'),
            $html,
        );

        //Url::getHiddenInputs
        self::assertStringContainsString(
            Url::getHiddenInputs(),
            $html,
        );

        //$username & $hostname
        self::assertStringContainsString(
            htmlspecialchars($username),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($hostname),
            $html,
        );

        //labels
        self::assertStringContainsString('change_password_form', $html);
        self::assertStringContainsString(
            __('No Password'),
            $html,
        );
        self::assertStringContainsString(
            __('Password:'),
            $html,
        );
        self::assertStringContainsString(
            __('Password:'),
            $html,
        );
    }

    public function testGetUserPrivileges(): void
    {
        $mysqliResultStub = $this->createMock(ResultInterface::class);

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('isMariaDB')->willReturn(true);

        $userQuery = 'SELECT * FROM `mysql`.`user` WHERE `User` = ? AND `Host` = ?;';
        $globalPrivQuery = 'SELECT * FROM `mysql`.`global_priv` WHERE `User` = ? AND `Host` = ?;';
        $dbi->expects(self::exactly(2))->method('executeQuery')->willReturnMap([
            [$userQuery, ['test.user', 'test.host'], ConnectionType::User, $mysqliResultStub],
            [$globalPrivQuery,['test.user', 'test.host'], ConnectionType::User, $mysqliResultStub],
        ]);

        $mysqliResultStub->expects(self::exactly(2))
            ->method('fetchAssoc')
            ->willReturn(
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
            new Config(),
        );
        $method = new ReflectionMethod(Privileges::class, 'getUserPrivileges');

        /** @var array|null $actual */
        $actual = $method->invokeArgs($serverPrivileges, ['test.user', 'test.host', true]);

        self::assertSame(['Host' => 'test.host', 'User' => 'test.user', 'account_locked' => 'Y'], $actual);
    }

    private function getPrivileges(DatabaseInterface $dbi): Privileges
    {
        $relation = new Relation($dbi);

        return new Privileges(
            new Template(),
            $dbi,
            $relation,
            new RelationCleanup($dbi, $relation),
            new Plugins($dbi),
            new Config(),
        );
    }

    /**
     * data provider for testEscapeMysqlWildcards and testUnescapeMysqlWildcards
     *
     * @return list<array{string, string}>
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
     */
    #[DataProvider('providerUnEscapeMysqlWildcards')]
    public function testEscapeMysqlWildcards(string $a, string $b): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);
        self::assertSame(
            $a,
            $serverPrivileges->escapeGrantWildcards($b),
        );
    }

    /**
     * @param string $a String to unescape
     * @param string $b Expected value
     */
    #[DataProvider('providerUnEscapeMysqlWildcards')]
    public function testUnescapeMysqlWildcards(string $a, string $b): void
    {
        $dbi = $this->createDatabaseInterface();
        $serverPrivileges = $this->getPrivileges($dbi);
        self::assertSame(
            $b,
            $serverPrivileges->unescapeGrantWildcards($a),
        );
    }

    #[DataProvider('providerGetHostname')]
    public function testGetHostname(string $expected, string $predHostname, string $globalHostname): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SELECT USER()',
            [['@pma_host']],
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $serverPrivileges = $this->getPrivileges($dbi);

        self::assertSame(
            $expected,
            $serverPrivileges->getHostname($predHostname, $globalHostname),
        );
    }

    /** @return iterable<int, string[]> */
    public static function providerGetHostname(): iterable
    {
        yield ['%', 'any', ''];
        yield ['localhost', 'localhost', ''];
        yield ['', 'hosttable', ''];
        yield ['pma_host', 'thishost', ''];
        yield ['global', '', 'global'];
    }
}
