<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use mysqli_result;
use mysqli_stmt;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
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
use ReflectionMethod;
use stdClass;

use function __;
use function _pgettext;
use function htmlspecialchars;
use function implode;
use function preg_quote;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Server\Privileges
 */
class PrivilegesTest extends AbstractTestCase
{
    /** @var Privileges $serverPrivileges */
    private $serverPrivileges;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        parent::setTheme();
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['table'] = 'table';
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['hostname'] = 'hostname';
        $GLOBALS['username'] = 'username';

        $relation = new Relation($GLOBALS['dbi']);
        $this->serverPrivileges = new Privileges(
            new Template(),
            $GLOBALS['dbi'],
            $relation,
            new RelationCleanup($GLOBALS['dbi'], $relation),
            new Plugins($GLOBALS['dbi'])
        );

        $_POST['pred_password'] = 'none';

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'pmadb',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'menuswork' => true,
            'trackingwork' => true,
            'tracking' => 'tracking',
        ])->toArray();

        $pmaconfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['config'] = $pmaconfig;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    [
                        'grant user1 select',
                        'grant user2 delete',
                    ]
                )
            );

        $fetchSingleRow = [
            'password' => 'pma_password',
            'Table_priv' => 'pri1, pri2',
            'Type' => 'Type',
            '@@old_passwords' => 0,
        ];
        $dbi->expects($this->any())->method('fetchSingleRow')
            ->will($this->returnValue($fetchSingleRow));

        $fetchValue = ['key1' => 'value1'];
        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($fetchValue));

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->any())->method('isCreateUser')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())->method('isGrantUser')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;
        $this->serverPrivileges->relation->dbi = $dbi;
        $GLOBALS['is_reload_priv'] = true;
    }

    /**
     * Test for getDataForDBInfo
     */
    public function testGetDataForDBInfo(): void
    {
        $_REQUEST['username'] = 'PMA_username';
        $_REQUEST['hostname'] = 'PMA_hostname';
        $_REQUEST['tablename'] = 'PMA_tablename';
        $_REQUEST['dbname'] = 'PMA_dbname';
        [
            $username,
            $hostname,
            $dbname,
            $tablename,
            $routinename,
            $db_and_table,
            $dbname_is_wildcard,
        ] = $this->serverPrivileges->getDataForDBInfo();
        self::assertSame('PMA_username', $username);
        self::assertSame('PMA_hostname', $hostname);
        self::assertSame('PMA_dbname', $dbname);
        self::assertSame('PMA_tablename', $tablename);
        self::assertSame('`PMA_dbname`.`PMA_tablename`', $db_and_table);
        self::assertTrue($dbname_is_wildcard);

        //pre variable have been defined
        $_POST['pred_tablename'] = 'PMA_pred__tablename';
        $_POST['pred_dbname'] = ['PMA_pred_dbname'];
        [,,
            $dbname,
            $tablename,
            $routinename,
            $db_and_table,
            $dbname_is_wildcard,
        ] = $this->serverPrivileges->getDataForDBInfo();
        self::assertSame('PMA_pred_dbname', $dbname);
        self::assertSame('PMA_pred__tablename', $tablename);
        self::assertSame('`PMA_pred_dbname`.`PMA_pred__tablename`', $db_and_table);
        self::assertTrue($dbname_is_wildcard);

        // Escaped database
        $_POST['pred_tablename'] = 'PMA_pred__tablename';
        $_POST['pred_dbname'] = ['PMA\_pred\_dbname'];
        [,,
            $dbname,
            $tablename,
            $routinename,
            $db_and_table,
            $dbname_is_wildcard,
        ] = $this->serverPrivileges->getDataForDBInfo();
        self::assertSame('PMA\_pred\_dbname', $dbname);
        self::assertSame('PMA_pred__tablename', $tablename);
        self::assertSame('`PMA_pred_dbname`.`PMA_pred__tablename`', $db_and_table);
        self::assertFalse($dbname_is_wildcard);

        // Multiselect database - pred
        unset($_POST['pred_tablename'], $_REQUEST['tablename'], $_REQUEST['dbname']);
        $_POST['pred_dbname'] = ['PMA\_pred\_dbname', 'PMADbname2'];
        [,,
            $dbname,
            $tablename,,
            $db_and_table,
            $dbname_is_wildcard,
        ] = $this->serverPrivileges->getDataForDBInfo();
        self::assertSame(['PMA\_pred\_dbname', 'PMADbname2'], $dbname);
        self::assertNull($tablename);
        self::assertSame(['PMA\_pred\_dbname.*', 'PMADbname2.*'], $db_and_table);
        self::assertFalse($dbname_is_wildcard);

        // Multiselect database
        unset($_POST['pred_tablename'], $_REQUEST['tablename'], $_POST['pred_dbname']);
        $_REQUEST['dbname'] = ['PMA\_dbname', 'PMADbname2'];
        [,,
            $dbname,
            $tablename,,
            $db_and_table,
            $dbname_is_wildcard,
        ] = $this->serverPrivileges->getDataForDBInfo();
        self::assertSame(['PMA\_dbname', 'PMADbname2'], $dbname);
        self::assertNull($tablename);
        self::assertSame(['PMA\_dbname.*', 'PMADbname2.*'], $db_and_table);
        self::assertFalse($dbname_is_wildcard);
    }

    /**
     * Test for wildcardEscapeForGrant
     */
    public function testWildcardEscapeForGrant(): void
    {
        $dbname = '';
        $tablename = '';
        $db_and_table = $this->serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        self::assertSame('*.*', $db_and_table);

        $dbname = 'dbname';
        $tablename = '';
        $db_and_table = $this->serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        self::assertSame('`dbname`.*', $db_and_table);

        $dbname = 'dbname';
        $tablename = 'tablename';
        $db_and_table = $this->serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        self::assertSame('`dbname`.`tablename`', $db_and_table);
    }

    /**
     * Test for rangeOfUsers
     */
    public function testRangeOfUsers(): void
    {
        $ret = $this->serverPrivileges->rangeOfUsers('INIT');
        self::assertSame(" WHERE `User` LIKE 'INIT%' OR `User` LIKE 'init%'", $ret);

        $ret = $this->serverPrivileges->rangeOfUsers('%');
        self::assertSame(' WHERE `User` LIKE \'\\%%\' OR `User` LIKE \'\\%%\'', $ret);

        $ret = $this->serverPrivileges->rangeOfUsers('');
        self::assertSame(" WHERE `User` = ''", $ret);

        $ret = $this->serverPrivileges->rangeOfUsers();
        self::assertSame('', $ret);
    }

    /**
     * Test for getTableGrantsArray
     */
    public function testGetTableGrantsArray(): void
    {
        $ret = $this->serverPrivileges->getTableGrantsArray();
        self::assertSame([
            'Delete',
            'DELETE',
            __('Allows deleting data.'),
        ], $ret[0]);
        self::assertSame([
            'Create',
            'CREATE',
            __('Allows creating new tables.'),
        ], $ret[1]);
    }

    /**
     * Test for getGrantsArray
     */
    public function testGetGrantsArray(): void
    {
        $ret = $this->serverPrivileges->getGrantsArray();
        self::assertSame([
            'Select_priv',
            'SELECT',
            __('Allows reading data.'),
        ], $ret[0]);
        self::assertSame([
            'Insert_priv',
            'INSERT',
            __('Allows inserting and replacing data.'),
        ], $ret[1]);
    }

    /**
     * Test for getSqlQueryForDisplayPrivTable
     */
    public function testGetSqlQueryForDisplayPrivTable(): void
    {
        $username = 'pma_username';
        $db = '*';
        $table = 'pma_table';
        $hostname = 'pma_hostname';

        //$db == '*'
        $ret = $this->serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $sql = 'SELECT * FROM `mysql`.`user`'
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "';";
        self::assertSame($sql, $ret);

        //$table == '*'
        $db = 'pma_db';
        $table = '*';
        $ret = $this->serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $sql = 'SELECT * FROM `mysql`.`db`'
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . ' AND `Db` = \'' . $db . '\'';

        self::assertSame($sql, $ret);

        //$table == 'pma_table'
        $db = 'pma_db';
        $table = 'pma_table';
        $ret = $this->serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        $sql = 'SELECT `Table_priv`'
            . ' FROM `mysql`.`tables_priv`'
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . " AND `Db` = '" . Util::unescapeMysqlWildcards($db) . "'"
            . " AND `Table_name` = '" . $GLOBALS['dbi']->escapeString($table) . "';";
        self::assertSame($sql, $ret);

        // SQL escaping
        $db = "db' AND";
        $table = 'pma_table';
        $ret = $this->serverPrivileges->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
        self::assertSame('SELECT `Table_priv` FROM `mysql`.`tables_priv` '
        . "WHERE `User` = 'pma_username' AND "
        . "`Host` = 'pma_hostname' AND `Db` = 'db' AND' AND "
        . "`Table_name` = 'pma_table';", $ret);
    }

    /**
     * Test for getDataForChangeOrCopyUser
     */
    public function testGetDataForChangeOrCopyUser(): void
    {
        //$_POST['change_copy'] not set
        [$queries, $password] = $this->serverPrivileges->getDataForChangeOrCopyUser();
        self::assertNull($queries);
        self::assertNull($queries);

        //$_POST['change_copy'] is set
        $_POST['change_copy'] = true;
        $_POST['old_username'] = 'PMA_old_username';
        $_POST['old_hostname'] = 'PMA_old_hostname';
        [$queries, $password] = $this->serverPrivileges->getDataForChangeOrCopyUser();
        self::assertSame('pma_password', $password);
        self::assertSame([], $queries);
        unset($_POST['change_copy']);
    }

    /**
     * Test for getListForExportUserDefinition
     */
    public function testGetHtmlForExportUserDefinition(): void
    {
        $username = 'PMA_username';
        $hostname = 'PMA_hostname';

        [$title, $export] = $this->serverPrivileges->getListForExportUserDefinition($username, $hostname);

        //validate 1: $export
        self::assertStringContainsString('grant user2 delete', $export);
        self::assertStringContainsString('grant user1 select', $export);
        self::assertStringContainsString('<textarea class="export"', $export);

        //validate 2: $title
        $title_user = __('User') . ' `' . htmlspecialchars($username)
            . '`@`' . htmlspecialchars($hostname) . '`';
        self::assertStringContainsString($title_user, $title);
    }

    /**
     * Test for addUser
     */
    public function testAddUser(): void
    {
        // Case 1 : Test with Newer version
        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(50706));
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];

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
            $ret_message,,,
            $sql_query,
            $_add_user_error,
        ] = $this->serverPrivileges->addUser($dbname, $username, $hostname, $dbname, true);
        self::assertSame('You have added a new user.', $ret_message->getMessage());
        self::assertSame("CREATE USER ''@'localhost' IDENTIFIED WITH mysql_native_password AS '***';"
        . "GRANT USAGE ON *.* TO ''@'localhost' REQUIRE NONE;"
        . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';", $sql_query);
        self::assertFalse($_add_user_error);
    }

    /**
     * Test for addUser
     */
    public function testAddUserOld(): void
    {
        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(50506));
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];

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
            $ret_message,,,
            $sql_query,
            $_add_user_error,
        ] = $this->serverPrivileges->addUser($dbname, $username, $hostname, $dbname, true);

        self::assertSame('You have added a new user.', $ret_message->getMessage());
        self::assertSame("CREATE USER ''@'localhost';"
        . "GRANT USAGE ON *.* TO ''@'localhost' REQUIRE NONE;"
        . "SET PASSWORD FOR ''@'localhost' = '***';"
        . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';", $sql_query);
        self::assertFalse($_add_user_error);
    }

    /**
     * Test for updatePassword
     */
    public function testUpdatePassword(): void
    {
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $err_url = 'error.php';
        $_POST['pma_pw'] = 'pma_pw';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        $message = $this->serverPrivileges->updatePassword($err_url, $username, $hostname);

        self::assertSame(
            'The password for \'pma_username\'@\'pma_hostname\' was changed successfully.',
            $message->getMessage()
        );
    }

    /**
     * Test for getMessageAndSqlQueryForPrivilegesRevoke
     */
    public function testGetMessageAndSqlQueryForPrivilegesRevoke(): void
    {
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
        [$message, $sql_query] = $this->serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
            $dbname,
            $tablename,
            $username,
            $hostname,
            ''
        );

        self::assertSame(
            "You have revoked the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        self::assertSame('REVOKE ALL PRIVILEGES ON  `pma_dbname`.`pma_tablename` '
        . "FROM 'pma_username'@'pma_hostname'; "
        . 'REVOKE GRANT OPTION ON  `pma_dbname`.`pma_tablename` '
        . "FROM 'pma_username'@'pma_hostname';", $sql_query);
    }

    /**
     * Test for updatePrivileges
     */
    public function testUpdatePrivileges(): void
    {
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
        [$sql_query, $message] = $this->serverPrivileges->updatePrivileges(
            $username,
            $hostname,
            $tablename,
            $dbname,
            ''
        );

        self::assertSame(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        self::assertSame(
            'REVOKE ALL PRIVILEGES ON  `pma_dbname`.`pma_tablename` FROM \'pma_username\'@\'pma_hostname\';   ',
            $sql_query
        );
    }

    /**
     * Test for updatePrivileges
     */
    public function testUpdatePrivilegesBeforeMySql8Dot11(): void
    {
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
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $this->serverPrivileges->dbi = $dbi;

        [$sql_query, $message] = $this->serverPrivileges->updatePrivileges(
            $username,
            $hostname,
            $tablename,
            $dbname,
            ''
        );

        self::assertSame(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        self::assertSame('  GRANT USAGE ON  *.* TO \'pma_username\'@\'pma_hostname\' REQUIRE NONE'
        . ' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 1000 MAX_CONNECTIONS_PER_HOUR 20'
        . ' MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40; ', $sql_query);
    }

    /**
     * Test for updatePrivileges
     */
    public function testUpdatePrivilegesAfterMySql8Dot11(): void
    {
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
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $this->serverPrivileges->dbi = $dbi;

        [$sql_query, $message] = $this->serverPrivileges->updatePrivileges(
            $username,
            $hostname,
            $tablename,
            $dbname,
            ''
        );

        self::assertSame(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        self::assertSame('  GRANT USAGE ON  *.* TO \'pma_username\'@\'pma_hostname\';'
        . ' ALTER USER \'pma_username\'@\'pma_hostname\'  REQUIRE NONE'
        . ' WITH MAX_QUERIES_PER_HOUR 1000 MAX_CONNECTIONS_PER_HOUR'
        . ' 20 MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40;', $sql_query);
    }

    /**
     * Test for getHtmlToDisplayPrivilegesTable
     *
     * @group medium
     */
    public function testGetHtmlToDisplayPrivilegesTable(): void
    {
        $GLOBALS['hostname'] = 'hostname';
        $GLOBALS['username'] = 'username';
        $GLOBALS['dbi'] = DatabaseInterface::load(new DbiDummy());

        $relation = new Relation($GLOBALS['dbi']);
        $serverPrivileges = new Privileges(
            new Template(),
            $GLOBALS['dbi'],
            $relation,
            new RelationCleanup($GLOBALS['dbi'], $relation),
            new Plugins($GLOBALS['dbi'])
        );
        $html = $serverPrivileges->getHtmlToDisplayPrivilegesTable();
        $GLOBALS['username'] = 'username';

        //validate 1: fieldset
        self::assertStringContainsString(
            '<fieldset id="fieldset_user_privtable_footer" class="pma-fieldset tblFooters">',
            $html
        );

        //validate 2: button
        self::assertStringContainsString(__('Go'), $html);

        //validate 3: getHtmlForGlobalOrDbSpecificPrivs
        self::assertStringContainsString('<fieldset class="pma-fieldset" id="fieldset_user_global_rights">', $html);
        self::assertStringContainsString('<legend data-submenu-label="' . __('Global') . '">', $html);
        self::assertStringContainsString(__('Global privileges'), $html);
        self::assertStringContainsString(__('Check all'), $html);
        self::assertStringContainsString(__('Note: MySQL privilege names are expressed in English'), $html);

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
        self::assertStringContainsString('<legend>' . __('Resource limits') . '</legend>', $html);
        self::assertStringContainsString(__('Note: Setting these options to 0 (zero) removes the limit.'), $html);
        self::assertStringContainsString('MAX QUERIES PER HOUR', $html);
        self::assertStringContainsString('id="text_max_updates" value="0"', $html);
        self::assertStringContainsString(__('Limits the number of new connections the user may open per hour.'), $html);
        self::assertStringContainsString(__('Limits the number of simultaneous connections the user may have.'), $html);

        self::assertStringContainsString('<legend>SSL</legend>', $html);
        self::assertStringContainsString('value="NONE"', $html);
        self::assertStringContainsString('value="ANY"', $html);
        self::assertStringContainsString('value="X509"', $html);
        self::assertStringContainsString('value="SPECIFIED"', $html);
    }

    /**
     * Test case for getSqlQueriesForDisplayAndAddUser
     */
    public function testGetSqlQueriesForDisplayAndAddUserMySql8011(): void
    {
        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(80011));
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';
        $password = 'pma_password';
        $_POST['pred_password'] = 'keep';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        [
            $create_user_real,
            $create_user_show,
        ] = $this->serverPrivileges->getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

        //validate 1: $create_user_real
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password BY \'pma_password\';',
            $create_user_real
        );

        //validate 2: $create_user_show
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password BY \'***\';',
            $create_user_show
        );
    }

    /**
     * Test case for getSqlQueriesForDisplayAndAddUser
     */
    public function testGetSqlQueriesForDisplayAndAddUserMySql8016(): void
    {
        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(80016));
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';
        $password = 'pma_password';
        $_POST['pred_password'] = 'keep';

        [
            $create_user_real,
            $create_user_show,
        ] = $this->serverPrivileges->getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

        //validate 1: $create_user_real
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED BY \'pma_password\';',
            $create_user_real
        );

        //validate 2: $create_user_show
        self::assertSame('CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED BY \'***\';', $create_user_show);
    }

    /**
     * Test for getSqlQueriesForDisplayAndAddUser
     */
    public function testGetSqlQueriesForDisplayAndAddUser(): void
    {
        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(50706));
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];

        $username = 'PMA_username';
        $hostname = 'PMA_hostname';
        $password = 'pma_password';
        $_POST['pred_password'] = 'keep';
        $_POST['authentication_plugin'] = 'mysql_native_password';
        $dbname = 'PMA_db';

        [
            $create_user_real,
            $create_user_show,
            $real_sql_query,
            $sql_query,,,
            $alter_real_sql_query,
            $alter_sql_query,
        ] = $this->serverPrivileges->getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

        //validate 1: $create_user_real
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password AS \'pma_password\';',
            $create_user_real
        );

        //validate 2: $create_user_show
        self::assertSame(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password AS \'***\';',
            $create_user_show
        );

        //validate 3:$real_sql_query
        self::assertSame("GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname' REQUIRE NONE;", $real_sql_query);

        //validate 4:$sql_query
        self::assertSame("GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname' REQUIRE NONE;", $sql_query);

        self::assertSame('', $alter_real_sql_query);

        self::assertSame('', $alter_sql_query);

        //Test for addUserAndCreateDatabase
        [$sql_query, $message] = $this->serverPrivileges->addUserAndCreateDatabase(
            false,
            $real_sql_query,
            $sql_query,
            $username,
            $hostname,
            $dbname,
            $alter_real_sql_query,
            $alter_sql_query,
            false,
            false,
            false
        );

        //validate 5: $sql_query
        self::assertSame("GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname' REQUIRE NONE;", $sql_query);

        self::assertInstanceOf(Message::class, $message);

        //validate 6: $message
        self::assertSame('You have added a new user.', $message->getMessage());
    }

    /**
     * Test for getHtmlForTableSpecificPrivileges
     */
    public function testGetHtmlToDisplayPrivilegesTableWithTableSpecific(): void
    {
        $dbi_old = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = DatabaseInterface::load(new DbiDummy());
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];

        $GLOBALS['username'] = 'PMA_username';
        $GLOBALS['hostname'] = 'PMA_hostname';
        $html = $this->serverPrivileges->getHtmlToDisplayPrivilegesTable('PMA_db', 'PMA_table');

        self::assertStringContainsString('checkbox_Update_priv_none', $html);
        self::assertStringContainsString('<dfn title="Allows changing data.">UPDATE</dfn>', $html);
        self::assertStringContainsString('checkbox_Insert_priv_none', $html);
        self::assertStringContainsString(__('Allows reading data.'), $html);
        self::assertStringContainsString(__('Allows inserting and replacing data'), $html);
        self::assertStringContainsString(__('Allows changing data.'), $html);
        self::assertStringContainsString(__('Has no effect in this MySQL version.'), $html);

        self::assertStringContainsString('title="Allows performing SHOW CREATE VIEW queries." checked>', $html);
        self::assertStringContainsString('<dfn title="Allows creating new views.">', $html);
        self::assertStringContainsString('CREATE VIEW', $html);
        self::assertStringContainsString('Create_view_priv', $html);
        self::assertStringContainsString('Show_view_priv', $html);
        self::assertStringContainsString(_pgettext('None privileges', 'None'), $html);

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getHtmlForLoginInformationFields
     */
    public function testGetHtmlForLoginInformationFields(): void
    {
        $GLOBALS['username'] = 'pma_username';

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = [
            [
                'COLUMN_NAME' => 'Host',
                'CHARACTER_MAXIMUM_LENGTH' => 80,
            ],
            [
                'COLUMN_NAME' => 'User',
                'CHARACTER_MAXIMUM_LENGTH' => 40,
            ],
        ];
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $html = $this->serverPrivileges->getHtmlForLoginInformationFields();

        //validate 1: __('Login Information')
        self::assertStringContainsString(__('Login Information'), $html);
        self::assertStringContainsString(__('User name:'), $html);
        self::assertStringContainsString(__('Any user'), $html);
        self::assertStringContainsString(__('Use text field'), $html);

        $output = Generator::showHint(
            __(
                'When Host table is used, this field is ignored and values stored in Host table are used instead.'
            )
        );
        self::assertStringContainsString($output, $html);

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getWithClauseForAddUserAndUpdatePrivs
     */
    public function testGetWithClauseForAddUserAndUpdatePrivs(): void
    {
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 10;
        $_POST['max_connections'] = 20;
        $_POST['max_updates'] = 30;
        $_POST['max_user_connections'] = 40;

        $sql_query = $this->serverPrivileges->getWithClauseForAddUserAndUpdatePrivs();
        $expect = 'WITH GRANT OPTION MAX_QUERIES_PER_HOUR 10 '
            . 'MAX_CONNECTIONS_PER_HOUR 20'
            . ' MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40';
        self::assertStringContainsString($expect, $sql_query);
    }

    /**
     * Test for getHtmlForAddUser
     *
     * @group medium
     */
    public function testGetHtmlForAddUser(): void
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = [
            [
                'COLUMN_NAME' => 'Host',
                'CHARACTER_MAXIMUM_LENGTH' => 80,
            ],
            [
                'COLUMN_NAME' => 'User',
                'CHARACTER_MAXIMUM_LENGTH' => 40,
            ],
        ];
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));
        $dbi->expects($this->any())->method('isGrantUser')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $dbname = 'pma_dbname';

        $html = $this->serverPrivileges->getHtmlForAddUser($dbname);

        //validate 1: Url::getHiddenInputs
        self::assertStringContainsString(Url::getHiddenInputs('', ''), $html);

        //validate 2: getHtmlForLoginInformationFields
        self::assertStringContainsString($this->serverPrivileges->getHtmlForLoginInformationFields('new'), $html);

        //validate 3: Database for user
        self::assertStringContainsString(__('Database for user'), $html);

        self::assertStringContainsString(__('Grant all privileges on wildcard name (username\\_%).'), $html);
        self::assertStringContainsString('<input type="checkbox" name="createdb-2" id="createdb-2">', $html);

        //validate 4: getHtmlToDisplayPrivilegesTable
        self::assertStringContainsString(
            $this->serverPrivileges->getHtmlToDisplayPrivilegesTable('*', '*', false),
            $html
        );

        //validate 5: button
        self::assertStringContainsString(__('Go'), $html);

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getUserLink
     */
    public function testGetUserLink(): void
    {
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $dbname = 'pma_dbname';
        $tablename = 'pma_tablename';

        $html = $this->serverPrivileges->getUserLink('edit', $username, $hostname, $dbname, $tablename, '');

        $dbname = 'pma_dbname';
        $url_html = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'dbname' => $dbname,
            'tablename' => $tablename,
            'routinename' => '',
        ], '');
        self::assertStringContainsString($url_html, $html);
        self::assertStringContainsString(__('Edit privileges'), $html);

        $dbname = 'pma_dbname';
        $html = $this->serverPrivileges->getUserLink('revoke', $username, $hostname, $dbname, $tablename, '');

        $dbname = 'pma_dbname';
        $url_html = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
                'revokeall' => 1,
            ],
            ''
        );
        self::assertStringContainsString($url_html, $html);
        self::assertStringContainsString(__('Revoke'), $html);

        $html = $this->serverPrivileges->getUserLink('export', $username, $hostname);

        $url_html = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'initial' => '',
            'export' => 1,
        ], '');
        self::assertStringContainsString($url_html, $html);
        self::assertStringContainsString(__('Export'), $html);
    }

    /**
     * Test for getUserLink
     */
    public function testGetUserLinkWildcardsEscaped(): void
    {
        $username = 'pma\_username';
        $hostname = 'pma\_hostname';
        $dbname = 'pma\_dbname';
        $tablename = 'pma\_tablename';

        $html = $this->serverPrivileges->getUserLink('edit', $username, $hostname, $dbname, $tablename, '');

        $dbname = 'pma\_dbname';
        $url_html = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'dbname' => $dbname,
            'tablename' => $tablename,
            'routinename' => '',
        ], '');
        self::assertStringContainsString($url_html, $html);
        self::assertStringContainsString(__('Edit privileges'), $html);

        $dbname = 'pma\_dbname';
        $html = $this->serverPrivileges->getUserLink('revoke', $username, $hostname, $dbname, $tablename, '');

        $dbname = 'pma\_dbname';
        $url_html = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
                'revokeall' => 1,
            ],
            ''
        );
        self::assertStringContainsString($url_html, $html);
        self::assertStringContainsString(__('Revoke'), $html);

        $html = $this->serverPrivileges->getUserLink('export', $username, $hostname);

        $url_html = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'initial' => '',
            'export' => 1,
        ], '');
        self::assertStringContainsString($url_html, $html);
        self::assertStringContainsString(__('Export'), $html);
    }

    /**
     * Test for getExtraDataForAjaxBehavior
     */
    public function testGetExtraDataForAjaxBehavior(): void
    {
        $password = 'pma_password';
        $sql_query = 'pma_sql_query';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $GLOBALS['dbname'] = 'pma_dbname';
        $_POST['adduser_submit'] = 'adduser_submit';
        $_POST['username'] = 'username';
        $_POST['change_copy'] = 'change_copy';
        $_GET['validate_username'] = 'validate_username';
        $_GET['username'] = 'username';
        $_POST['update_privs'] = 'update_privs';

        $extra_data = $this->serverPrivileges->getExtraDataForAjaxBehavior($password, $sql_query, $hostname, $username);

        //user_exists
        self::assertFalse($extra_data['user_exists']);

        //db_wildcard_privs
        self::assertTrue($extra_data['db_wildcard_privs']);

        //user_exists
        self::assertFalse($extra_data['db_specific_privs']);

        //new_user_initial
        self::assertSame('P', $extra_data['new_user_initial']);

        //sql_query
        self::assertSame(Generator::getMessage('', $sql_query), $extra_data['sql_query']);

        //new_user_string
        self::assertStringContainsString(htmlspecialchars($hostname), $extra_data['new_user_string']);
        self::assertStringContainsString(htmlspecialchars($username), $extra_data['new_user_string']);

        //new_privileges
        self::assertStringContainsString(
            implode(', ', $this->serverPrivileges->extractPrivInfo(null, true)),
            $extra_data['new_privileges']
        );
    }

    /**
     * Test for getUserGroupForUser
     */
    public function testGetUserGroupForUser(): void
    {
        $username = 'pma_username';

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $expected_userGroup = 'pma_usergroup';

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($expected_userGroup));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $returned_userGroup = $this->serverPrivileges->getUserGroupForUser($username);

        self::assertSame($expected_userGroup, $returned_userGroup);

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getUsersOverview
     */
    public function testGetUsersOverview(): void
    {
        $resultStub = $this->createMock(DummyResult::class);
        $db_rights = [];
        $text_dir = 'text_dir';

        $html = $this->serverPrivileges->getUsersOverview($resultStub, $db_rights, $text_dir);

        //Url::getHiddenInputs
        self::assertStringContainsString(Url::getHiddenInputs('', ''), $html);

        //items
        self::assertStringContainsString(__('User'), $html);
        self::assertStringContainsString(__('Host'), $html);
        self::assertStringContainsString(__('Password'), $html);
        self::assertStringContainsString(__('Global privileges'), $html);

        //Util::showHint
        self::assertStringContainsString(Generator::showHint(
            __('Note: MySQL privilege names are expressed in English.')
        ), $html);

        //__('User group')
        self::assertStringContainsString(__('User group'), $html);
        self::assertStringContainsString(__('Grant'), $html);
        self::assertStringContainsString(__('Action'), $html);

        //$text_dir
        self::assertStringContainsString($text_dir, $html);

        self::assertStringContainsString(Url::getCommon(['adduser' => 1], ''), $html);

        //labels
        self::assertStringContainsString(__('Add user account'), $html);
        self::assertStringContainsString(__('Remove selected user accounts'), $html);
        self::assertStringContainsString(__('Drop the databases that have the same names as the users.'), $html);
        self::assertStringContainsString(__('Drop the databases that have the same names as the users.'), $html);
    }

    /**
     * Test for getDataForDeleteUsers
     */
    public function testGetDataForDeleteUsers(): void
    {
        $_POST['change_copy'] = 'change_copy';
        $_POST['old_hostname'] = 'old_hostname';
        $_POST['old_username'] = 'old_username';
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([])->toArray();

        $queries = [];

        $ret = $this->serverPrivileges->getDataForDeleteUsers($queries);

        $item = [
            "# Deleting 'old_username'@'old_hostname' ...",
            "DROP USER 'old_username'@'old_hostname';",
        ];
        self::assertSame($item, $ret);
    }

    /**
     * Test for getAddUserHtmlFieldset
     */
    public function testGetAddUserHtmlFieldset(): void
    {
        $html = $this->serverPrivileges->getAddUserHtmlFieldset();

        self::assertStringContainsString(Url::getCommon(['adduser' => 1], ''), $html);
        self::assertStringContainsString(Generator::getIcon('b_usradd'), $html);
        self::assertStringContainsString(__('Add user'), $html);
    }

    /**
     * Test for getHtmlHeaderForUserProperties
     */
    public function testGetHtmlHeaderForUserProperties(): void
    {
        $dbname_is_wildcard = true;
        $url_dbname = 'url_dbname';
        $dbname = 'dbname';
        $username = 'username';
        $hostname = 'hostname';
        $tablename = 'tablename';
        $_REQUEST['tablename'] = 'tablename';

        // $this->serverPrivileges->dbi->expects($this->once())->method('tryQuery')->with

        $html = $this->serverPrivileges->getHtmlForUserProperties(
            $dbname_is_wildcard,
            $url_dbname,
            $username,
            $hostname,
            $tablename,
            $_REQUEST['tablename']
        );

        //title
        self::assertStringContainsString(__('Edit privileges:'), $html);
        self::assertStringContainsString(__('User account'), $html);

        //Url::getCommon
        $item = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'dbname' => '',
            'tablename' => '',
        ], '');
        self::assertStringContainsString($item, $html);

        //$username & $hostname
        self::assertStringContainsString(htmlspecialchars($username), $html);
        self::assertStringContainsString(htmlspecialchars($hostname), $html);

        //$dbname_is_wildcard = true
        self::assertStringContainsString(__('Databases'), $html);

        //$dbname_is_wildcard = true
        self::assertStringContainsString(__('Databases'), $html);

        //Url::getCommon
        $item = Url::getCommon([
            'username' => $username,
            'hostname' => $hostname,
            'dbname' => $url_dbname,
            'tablename' => '',
        ], '');
        self::assertStringContainsString($item, $html);
        self::assertStringContainsString($dbname, $html);
    }

    /**
     * Tests for getHtmlForViewUsersError
     */
    public function testGetHtmlForViewUsersError(): void
    {
        self::assertStringContainsString(
            'Not enough privilege to view users.',
            $this->serverPrivileges->getHtmlForViewUsersError()
        );
    }

    /**
     * Tests for getHtmlForUserProperties
     */
    public function testGetHtmlForUserProperties(): void
    {
        $this->dummyDbi->addResult(
            'SELECT \'1\' FROM `mysql`.`user` WHERE `User` = \'user\' AND `Host` = \'host\';',
            [['1']],
            ['1']
        );
        $this->dummyDbi->addResult(
            'SELECT `Table_priv` FROM `mysql`.`tables_priv` WHERE `User` = \'user\' AND `Host` = \'host\''
                . ' AND `Db` = \'sakila\' AND `Table_name` = \'actor\';',
            [],
            ['Table_priv']
        );
        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `sakila`.`actor`;',
            [
                ['actor_id', 'smallint(5) unsigned', 'NO', 'PRI', null, 'auto_increment'],
                ['first_name', 'varchar(45)', 'NO', '', null, ''],
                ['last_name', 'varchar(45)', 'NO', 'MUL', null, ''],
                ['last_update', 'timestamp', 'NO', '', 'current_timestamp()', 'on update current_timestamp()'],
            ],
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra']
        );
        $this->dummyDbi->addResult(
            'SELECT `Column_name`, `Column_priv` FROM `mysql`.`columns_priv` WHERE `User` = \'user\''
                . ' AND `Host` = \'host\' AND `Db` = \'sakila\' AND `Table_name` = \'actor\';',
            [],
            ['Column_name', 'Column_priv']
        );

        $relation = new Relation($this->dbi);
        $serverPrivileges = new Privileges(
            new Template(),
            $this->dbi,
            $relation,
            new RelationCleanup($this->dbi, $relation),
            new Plugins($this->dbi)
        );

        $GLOBALS['username'] = 'user';
        $GLOBALS['hostname'] = 'host';

        $actual = $serverPrivileges->getHtmlForUserProperties(false, 'sakila', 'user', 'host', 'sakila', 'actor');
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

        self::assertStringContainsString(Url::getHiddenInputs(), $actual);

        //$username & $hostname
        self::assertStringContainsString('user', $actual);
        self::assertStringContainsString('host', $actual);

        //Create a new user with the same privileges
        self::assertStringContainsString('Create a new user account with the same privileges', $actual);

        self::assertStringContainsString(__('Database'), $actual);
        self::assertStringContainsString(Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabDatabase'],
            'database'
        ), $actual);
        $item = Url::getCommon([
            'db' => 'sakila',
            'reload' => 1,
        ], '');
        self::assertStringContainsString($item, $actual);
        self::assertStringContainsString('sakila', $actual);

        //$tablename
        self::assertStringContainsString(__('Table'), $actual);
        self::assertStringContainsString(Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabTable'],
            'table'
        ), $actual);
        $item = Url::getCommon([
            'db' => 'sakila',
            'table' => 'actor',
            'reload' => 1,
        ], '');
        self::assertStringContainsString($item, $actual);
        self::assertStringContainsString('table', $actual);
        $item = Util::getTitleForTarget($GLOBALS['cfg']['DefaultTabTable']);
        self::assertStringContainsString((string) $item, $actual);
    }

    /**
     * Tests for getHtmlForUserOverview
     */
    public function testGetHtmlForUserOverview(): void
    {
        $_REQUEST = ['ajax_page_request' => '1'];
        $actual = $this->serverPrivileges->getHtmlForUserOverview('ltr');
        self::assertStringContainsString('Note: MySQL privilege names are expressed in English.', $actual);
        self::assertStringContainsString(
            'Note: phpMyAdmin gets the users’ privileges directly from MySQL’s privilege tables.',
            $actual
        );
    }

    /**
     * Tests for getHtmlForAllTableSpecificRights
     */
    public function testGetHtmlForAllTableSpecificRights(): void
    {
        // Test case 1
        $actual = $this->serverPrivileges->getHtmlForAllTableSpecificRights('pma', 'host', 'table', 'pmadb');
        self::assertStringContainsString('<input type="hidden" name="username" value="pma">', $actual);
        self::assertStringContainsString('<input type="hidden" name="hostname" value="host">', $actual);
        self::assertStringContainsString('<legend data-submenu-label="Table">', $actual);
        self::assertStringContainsString('Table-specific privileges', $actual);

        // Test case 2
        $GLOBALS['dblist'] = new stdClass();
        $GLOBALS['dblist']->databases = [
            'x',
            'y',
            'z',
        ];
        $actual = $this->serverPrivileges->getHtmlForAllTableSpecificRights('pma2', 'host2', 'database', '');
        self::assertStringContainsString('<legend data-submenu-label="Database">', $actual);
        self::assertStringContainsString('Database-specific privileges', $actual);
    }

    /**
     * Tests for getHtmlForInitials
     */
    public function testGetHtmlForInitials(): void
    {
        // Setup for the test
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->will($this->returnValue($resultStub));
        $resultStub->expects($this->atLeastOnce())
            ->method('fetchRow')
            ->will($this->onConsecutiveCalls(['-'], ['"'], ['%'], ['\\'], [''], []));
        $this->serverPrivileges->dbi = $dbi;

        $actual = $this->serverPrivileges->getHtmlForInitials();
        self::assertStringContainsString(
            '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">A</a>',
            $actual
        );
        self::assertStringContainsString(
            '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Z</a>',
            $actual
        );
        self::assertMatchesRegularExpressionCompat(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=-&lang=en">\s*-\s*<\/a>/',
            $actual
        );
        self::assertMatchesRegularExpressionCompat(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=%22&lang=en">\s*&quot;\s*<\/a>/',
            $actual
        );
        self::assertMatchesRegularExpressionCompat(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=%25&lang=en">\s*%\s*<\/a>/',
            $actual
        );
        self::assertMatchesRegularExpressionCompat(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=%5C&lang=en">\s*\\\\\s*<\/a>/',
            $actual
        );
        self::assertMatchesRegularExpressionCompat(
            '/<a class="page-link" href="index.php\?route=\/server\/privileges&initial=&lang=en">\s*' .
            '<span class="text-danger text-nowrap">' . preg_quote(__('Any')) . '<\/span>' .
            '\s*<\/a>/',
            $actual
        );
        self::assertStringContainsString('Show all', $actual);
    }

    /**
     * Tests for getDbRightsForUserOverview
     */
    public function testGetDbRightsForUserOverview(): void
    {
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
                    [
                        'User' => 'pmauser',
                        'Host' => 'local',
                    ],
                    []
                )
            );
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $_GET['initial'] = 'A';
        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

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
        $actual = $this->serverPrivileges->getDbRightsForUserOverview();
        self::assertSame($expected, $actual);
    }

    /**
     * Tests for deleteUser
     */
    public function testDeleteUser(): void
    {
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
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        // Test case 1 : empty queries
        $queries = [];
        $actual = $this->serverPrivileges->deleteUser($queries);
        self::assertArrayHasKey(0, $actual);
        self::assertArrayHasKey(1, $actual);
        self::assertSame('', $actual[0]);
        self::assertSame('No users selected for deleting!', $actual[1]->getMessage());

        // Test case 2 : all successful queries
        $_POST['mode'] = 3;
        $queries = ['foo'];
        $actual = $this->serverPrivileges->deleteUser($queries);
        self::assertArrayHasKey(0, $actual);
        self::assertArrayHasKey(1, $actual);
        self::assertSame("foo\n# Reloading the privileges …\nFLUSH PRIVILEGES;", $actual[0]);
        self::assertSame('The selected users have been deleted successfully.', $actual[1]->getMessage());

        // Test case 3 : failing queries
        $_POST['mode'] = 1;
        $queries = ['bar'];
        $actual = $this->serverPrivileges->deleteUser($queries);
        self::assertArrayHasKey(0, $actual);
        self::assertArrayHasKey(1, $actual);
        self::assertSame('bar', $actual[0]);
        self::assertSame('Some error occurred!' . "\n", $actual[1]->getMessage());
    }

    public function testGetFormForChangePassword(): void
    {
        global $route;

        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $route = '/server/privileges';

        $html = $this->serverPrivileges->getFormForChangePassword($username, $hostname, false);

        self::assertStringContainsString(Url::getFromRoute('/server/privileges'), $html);

        //Url::getHiddenInputs
        self::assertStringContainsString(Url::getHiddenInputs(), $html);

        //$username & $hostname
        self::assertStringContainsString(htmlspecialchars($username), $html);
        self::assertStringContainsString(htmlspecialchars($hostname), $html);

        //labels
        self::assertStringContainsString(__('Change password'), $html);
        self::assertStringContainsString(__('No Password'), $html);
        self::assertStringContainsString(__('Password:'), $html);
        self::assertStringContainsString(__('Password:'), $html);
    }

    /**
     * @requires PHPUnit < 10
     */
    public function testGetUserPrivileges(): void
    {
        $mysqliResultStub = $this->createMock(mysqli_result::class);
        $mysqliStmtStub = $this->createMock(mysqli_stmt::class);
        $mysqliStmtStub->expects($this->exactly(2))->method('bind_param')->willReturn(true);
        $mysqliStmtStub->expects($this->exactly(2))->method('execute')->willReturn(true);
        $mysqliStmtStub->expects($this->exactly(2))
            ->method('get_result')
            ->willReturn($mysqliResultStub);

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('isMariaDB')->willReturn(true);
        $dbi->expects($this->exactly(2))
            ->method('prepare')
            ->withConsecutive(
                [$this->equalTo('SELECT * FROM `mysql`.`user` WHERE `User` = ? AND `Host` = ?;')],
                [$this->equalTo('SELECT * FROM `mysql`.`global_priv` WHERE `User` = ? AND `Host` = ?;')]
            )
            ->willReturn($mysqliStmtStub);
        $mysqliResultStub->expects($this->exactly(2))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['Host' => 'test.host', 'User' => 'test.user'],
                ['Host' => 'test.host', 'User' => 'test.user', 'Priv' => '{"account_locked":true}']
            );

        $relation = new Relation($this->dbi);
        $serverPrivileges = new Privileges(
            new Template(),
            $dbi,
            $relation,
            new RelationCleanup($this->dbi, $relation),
            new Plugins($this->dbi)
        );
        $method = new ReflectionMethod(Privileges::class, 'getUserPrivileges');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        /** @var array|null $actual */
        $actual = $method->invokeArgs($serverPrivileges, ['test.user', 'test.host', true]);

        self::assertSame(['Host' => 'test.host', 'User' => 'test.user', 'account_locked' => 'Y'], $actual);
    }
}
