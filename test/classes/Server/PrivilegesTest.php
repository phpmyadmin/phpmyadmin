<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Privileges
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * PhpMyAdmin\Tests\Server\PrivilegesTest class
 *
 * this class is for testing PhpMyAdmin\Server\Privileges methods
 *
 * @package PhpMyAdmin-test
 */
class PrivilegesTest extends TestCase
{
    /**
     * @var Privileges $serverPrivileges
     */
    private $serverPrivileges;

    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        //Constants
        if (! defined("PMA_USR_BROWSER_AGENT")) {
            define("PMA_USR_BROWSER_AGENT", "other");
        }

        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;
        $_GET['initial'] = null;

        //$GLOBALS
        $GLOBALS['lang'] = 'en';
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['SendErrorReports'] = "never";
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = [];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;
        $GLOBALS['cfg']['ActionLinksMode'] = "both";
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['cfg']['DefaultTabTable'] = "structure";
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = "structure";
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'] = "";
        $GLOBALS['cfg']['Confirm'] = "Confirm";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree'] = true;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;
        $GLOBALS['cfg']['enable_drag_drop_import'] = true;

        $GLOBALS['cfgRelation'] = [];
        $GLOBALS['cfgRelation']['menuswork'] = false;
        $GLOBALS['table'] = "table";
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['hostname'] = "hostname";
        $GLOBALS['username'] = "username";
        $GLOBALS['text_dir'] = "text_dir";
        $GLOBALS['is_reload_priv'] = true;

        $relation = new Relation($GLOBALS['dbi']);
        $this->serverPrivileges = new Privileges(
            new Template(),
            $GLOBALS['dbi'],
            $relation,
            new RelationCleanup($GLOBALS['dbi'], $relation)
        );

        //$_POST
        $_POST['pred_password'] = 'none';
        //$_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = [
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'menuswork' => true,
        ];

        $pmaconfig = $this->getMockBuilder('PhpMyAdmin\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['PMA_Config'] = $pmaconfig;

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;
        $this->serverPrivileges->relation->dbi = $dbi;
        $GLOBALS['is_grantuser'] = true;
        $GLOBALS['is_createuser'] = true;
        $GLOBALS['is_reload_priv'] = true;
        $GLOBALS['strPrivDescDeleteHistoricalRows'] = "strPrivDescDeleteHistoricalRows";
    }

    /**
     * Test for getDataForDBInfo
     *
     * @return void
     */
    public function testGetDataForDBInfo()
    {
        $_REQUEST['username'] = "PMA_username";
        $_REQUEST['hostname'] = "PMA_hostname";
        $_REQUEST['tablename'] = "PMA_tablename";
        $_REQUEST['dbname'] = "PMA_dbname";
        list(
            $username, $hostname, $dbname, $tablename, $routinename,
            $db_and_table, $dbname_is_wildcard
        ) = $this->serverPrivileges->getDataForDBInfo();
        $this->assertEquals(
            "PMA_username",
            $username
        );
        $this->assertEquals(
            "PMA_hostname",
            $hostname
        );
        $this->assertEquals(
            "PMA_dbname",
            $dbname
        );
        $this->assertEquals(
            "PMA_tablename",
            $tablename
        );
        $this->assertEquals(
            "`PMA_dbname`.`PMA_tablename`",
            $db_and_table
        );
        $this->assertEquals(
            true,
            $dbname_is_wildcard
        );

        //pre variable have been defined
        $_POST['pred_tablename'] = "PMA_pred__tablename";
        $_POST['pred_dbname'] = ["PMA_pred_dbname"];
        list(
            ,, $dbname, $tablename, $routinename,
            $db_and_table, $dbname_is_wildcard
        ) = $this->serverPrivileges->getDataForDBInfo();
        $this->assertEquals(
            "PMA_pred_dbname",
            $dbname
        );
        $this->assertEquals(
            "PMA_pred__tablename",
            $tablename
        );
        $this->assertEquals(
            "`PMA_pred_dbname`.`PMA_pred__tablename`",
            $db_and_table
        );
        $this->assertEquals(
            true,
            $dbname_is_wildcard
        );
    }

    /**
     * Test for wildcardEscapeForGrant
     *
     * @return void
     */
    public function testWildcardEscapeForGrant()
    {
        $dbname = '';
        $tablename = '';
        $db_and_table = $this->serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '*.*',
            $db_and_table
        );

        $dbname = 'dbname';
        $tablename = '';
        $db_and_table = $this->serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '`dbname`.*',
            $db_and_table
        );

        $dbname = 'dbname';
        $tablename = 'tablename';
        $db_and_table = $this->serverPrivileges->wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '`dbname`.`tablename`',
            $db_and_table
        );
    }

    /**
     * Test for rangeOfUsers
     *
     * @return void
     */
    public function testRangeOfUsers()
    {
        $ret = $this->serverPrivileges->rangeOfUsers("INIT");
        $this->assertEquals(
            " WHERE `User` LIKE 'INIT%' OR `User` LIKE 'init%'",
            $ret
        );

        $ret = $this->serverPrivileges->rangeOfUsers();
        $this->assertEquals(
            '',
            $ret
        );
    }

    /**
     * Test for getTableGrantsArray
     *
     * @return void
     */
    public function testGetTableGrantsArray()
    {
        $GLOBALS['strPrivDescDelete'] = "strPrivDescDelete";
        $GLOBALS['strPrivDescCreateTbl'] = "strPrivDescCreateTbl";
        $GLOBALS['strPrivDescDropTbl'] = "strPrivDescDropTbl";
        $GLOBALS['strPrivDescIndex'] = "strPrivDescIndex";
        $GLOBALS['strPrivDescAlter'] = "strPrivDescAlter";
        $GLOBALS['strPrivDescCreateView'] = "strPrivDescCreateView";
        $GLOBALS['strPrivDescShowView'] = "strPrivDescShowView";
        $GLOBALS['strPrivDescTrigger'] = "strPrivDescTrigger";

        $ret = $this->serverPrivileges->getTableGrantsArray();
        $this->assertEquals(
            [
                'Delete',
                'DELETE',
                $GLOBALS['strPrivDescDelete'],
            ],
            $ret[0]
        );
        $this->assertEquals(
            [
                'Create',
                'CREATE',
                $GLOBALS['strPrivDescCreateTbl'],
            ],
            $ret[1]
        );
    }

    /**
     * Test for getGrantsArray
     *
     * @return void
     */
    public function testGetGrantsArray()
    {
        $ret = $this->serverPrivileges->getGrantsArray();
        $this->assertEquals(
            [
                'Select_priv',
                'SELECT',
                __('Allows reading data.'),
            ],
            $ret[0]
        );
        $this->assertEquals(
            [
                'Insert_priv',
                'INSERT',
                __('Allows inserting and replacing data.'),
            ],
            $ret[1]
        );
    }

    /**
     * Test for getHtmlForColumnPrivileges
     *
     * @return void
     */
    public function testGetHtmlForColumnPrivileges()
    {
        $columns = [
            'row1' => 'name1',
        ];
        $row = [
            'name_for_select' => 'Y',
        ];
        $name_for_select = 'name_for_select';
        $priv_for_header = 'priv_for_header';
        $name = 'name';
        $name_for_dfn = 'name_for_dfn';
        $name_for_current = 'name_for_current';

        $html = $this->serverPrivileges->getHtmlForColumnPrivileges(
            $columns,
            $row,
            $name_for_select,
            $priv_for_header,
            $name,
            $name_for_dfn,
            $name_for_current
        );
        //$name
        $this->assertStringContainsString(
            $name,
            $html
        );
        //$name_for_dfn
        $this->assertStringContainsString(
            $name_for_dfn,
            $html
        );
        //$priv_for_header
        $this->assertStringContainsString(
            $priv_for_header,
            $html
        );
        //$name_for_select
        $this->assertStringContainsString(
            $name_for_select,
            $html
        );
        //$columns and $row
        $this->assertStringContainsString(
            htmlspecialchars('row1'),
            $html
        );
        //$columns and $row
        $this->assertStringContainsString(
            _pgettext('None privileges', 'None'),
            $html
        );
    }

    /**
     * Test for getHtmlForRequires
     *
     * @return void
     */
    public function testGetHtmlForRequires()
    {
        /* Assertion 1 */
        $row = [
            'ssl_type'   => '',
            'ssh_cipher' => '',
        ];

        $html = $this->serverPrivileges->getHtmlForRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertStringContainsString(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertStringContainsString(
            'value="NONE" checked="checked"',
            $html
        );
        $this->assertStringContainsString(
            'value="ANY"',
            $html
        );
        $this->assertStringContainsString(
            'value="X509"',
            $html
        );
        $this->assertStringContainsString(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 2 */
        $row = [
            'ssl_type'   => 'ANY',
            'ssh_cipher' => '',
        ];

        $html = $this->serverPrivileges->getHtmlForRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertStringContainsString(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertStringContainsString(
            'value="NONE"',
            $html
        );
        $this->assertStringContainsString(
            'value="ANY" checked="checked"',
            $html
        );
        $this->assertStringContainsString(
            'value="X509"',
            $html
        );
        $this->assertStringContainsString(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 3 */
        $row = [
            'ssl_type'   => 'X509',
            'ssh_cipher' => '',
        ];

        $html = $this->serverPrivileges->getHtmlForRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertStringContainsString(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertStringContainsString(
            'value="NONE"',
            $html
        );
        $this->assertStringContainsString(
            'value="ANY"',
            $html
        );
        $this->assertStringContainsString(
            'value="X509" checked="checked"',
            $html
        );
        $this->assertStringContainsString(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 4 */
        $row = [
            'ssl_type'   => 'SPECIFIED',
            'ssh_cipher' => '',
        ];

        $html = $this->serverPrivileges->getHtmlForRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertStringContainsString(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertStringContainsString(
            'value="NONE"',
            $html
        );
        $this->assertStringContainsString(
            'value="ANY"',
            $html
        );
        $this->assertStringContainsString(
            'value="X509"',
            $html
        );
        $this->assertStringContainsString(
            'value="SPECIFIED" checked="checked"',
            $html
        );
    }

    /**
     * Test for getHtmlForUserGroupDialog
     *
     * @return void
     */
    public function testGetHtmlForUserGroupDialog()
    {
        $username = "pma_username";
        $is_menuswork = true;
        $_GET['edit_user_group_dialog'] = "edit_user_group_dialog";

        /* Assertion 1 */
        $html = $this->serverPrivileges->getHtmlForUserGroupDialog($username, $is_menuswork);
        $this->assertStringContainsString(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //Url::getHiddenInputs
        $params = ['username' => $username];
        $html_output = Url::getHiddenInputs($params);
        $this->assertStringContainsString(
            $html_output,
            $html
        );
        //__('User group')
        $this->assertStringContainsString(
            __('User group'),
            $html
        );

        /* Assertion 2 */
        $oldDbi = $GLOBALS['dbi'];
        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('fetchValue')
            ->will($this->returnValue('userG'));
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(['userG'], null);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $actualHtml = $this->serverPrivileges->getHtmlForUserGroupDialog($username, $is_menuswork);
        $this->assertStringContainsString(
            '<form class="ajax" id="changeUserGroupForm"',
            $actualHtml
        );
        //Url::getHiddenInputs
        $params = ['username' => $username];
        $html_output = Url::getHiddenInputs($params);
        $this->assertStringContainsString(
            $html_output,
            $actualHtml
        );
        //__('User group')
        $this->assertStringContainsString(
            __('User group'),
            $actualHtml
        );

        // Empty default user group
        $this->assertStringContainsString(
            '<option value=""></option>',
            $actualHtml
        );

        // Current user's group selected
        $this->assertStringContainsString(
            '<option value="userG" selected="selected">userG</option>',
            $actualHtml
        );

        /* reset original dbi */
        $GLOBALS['dbi'] = $oldDbi;
        $this->serverPrivileges->dbi = $oldDbi;
    }

    /**
     * Test for getHtmlToChooseUserGroup
     *
     * @return void
     */
    public function testGetHtmlToChooseUserGroup()
    {
        $username = "pma_username";

        $html = $this->serverPrivileges->getHtmlToChooseUserGroup($username);
        $this->assertStringContainsString(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //Url::getHiddenInputs
        $params = ['username' => $username];
        $html_output = Url::getHiddenInputs($params);
        $this->assertStringContainsString(
            $html_output,
            $html
        );
        //__('User group')
        $this->assertStringContainsString(
            __('User group'),
            $html
        );
    }

    /**
     * Test for getHtmlForResourceLimits
     *
     * @return void
     */
    public function testGetHtmlForResourceLimits()
    {
        $row = [
            'max_questions' => 'max_questions',
            'max_updates' => 'max_updates',
            'max_connections' => 'max_connections',
            'max_user_connections' => 'max_user_connections',
        ];

        $html = $this->serverPrivileges->getHtmlForResourceLimits($row);
        $this->assertStringContainsString(
            '<legend>' . __('Resource limits') . '</legend>',
            $html
        );
        $this->assertStringContainsString(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html
        );
        $this->assertStringContainsString(
            'MAX QUERIES PER HOUR',
            $html
        );
        $this->assertStringContainsString(
            $row['max_connections'],
            $html
        );
        $this->assertStringContainsString(
            $row['max_updates'],
            $html
        );
        $this->assertStringContainsString(
            $row['max_connections'],
            $html
        );
        $this->assertStringContainsString(
            $row['max_user_connections'],
            $html
        );
        $this->assertStringContainsString(
            __('Limits the number of new connections the user may open per hour.'),
            $html
        );
        $this->assertStringContainsString(
            __('Limits the number of simultaneous connections the user may have.'),
            $html
        );
    }

    /**
     * Test for getSqlQueryForDisplayPrivTable
     *
     * @return void
     */
    public function testGetSqlQueryForDisplayPrivTable()
    {
        $username = "pma_username";
        $db = '*';
        $table = "pma_table";
        $hostname = "pma_hostname";

        //$db == '*'
        $ret = $this->serverPrivileges->getSqlQueryForDisplayPrivTable(
            $db,
            $table,
            $username,
            $hostname
        );
        $sql = "SELECT * FROM `mysql`.`user`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "';";
        $this->assertEquals(
            $sql,
            $ret
        );

        //$table == '*'
        $db = "pma_db";
        $table = "*";
        $ret = $this->serverPrivileges->getSqlQueryForDisplayPrivTable(
            $db,
            $table,
            $username,
            $hostname
        );
        $sql = "SELECT * FROM `mysql`.`db`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . " AND '" . Util::unescapeMysqlWildcards($db) . "'"
            . " LIKE `Db`;";
        $this->assertEquals(
            $sql,
            $ret
        );

        //$table == 'pma_table'
        $db = "pma_db";
        $table = "pma_table";
        $ret = $this->serverPrivileges->getSqlQueryForDisplayPrivTable(
            $db,
            $table,
            $username,
            $hostname
        );
        $sql = "SELECT `Table_priv`"
            . " FROM `mysql`.`tables_priv`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . " AND `Db` = '" . Util::unescapeMysqlWildcards($db) . "'"
            . " AND `Table_name` = '" . $GLOBALS['dbi']->escapeString($table) . "';";
        $this->assertEquals(
            $sql,
            $ret
        );

        // SQL escaping
        $db = "db' AND";
        $table = "pma_table";
        $ret = $this->serverPrivileges->getSqlQueryForDisplayPrivTable(
            $db,
            $table,
            $username,
            $hostname
        );
        $this->assertEquals(
            "SELECT `Table_priv` FROM `mysql`.`tables_priv` "
            . "WHERE `User` = 'pma_username' AND "
            . "`Host` = 'pma_hostname' AND `Db` = 'db' AND' AND "
            . "`Table_name` = 'pma_table';",
            $ret
        );
    }

    /**
     * Test for getDataForChangeOrCopyUser
     *
     * @return void
     */
    public function testGetDataForChangeOrCopyUser()
    {
        //$_POST['change_copy'] not set
        list($queries, $password) = $this->serverPrivileges->getDataForChangeOrCopyUser();
        $this->assertEquals(
            null,
            $queries
        );
        $this->assertEquals(
            null,
            $queries
        );

        //$_POST['change_copy'] is set
        $_POST['change_copy'] = true;
        $_POST['old_username'] = 'PMA_old_username';
        $_POST['old_hostname'] = 'PMA_old_hostname';
        list($queries, $password) = $this->serverPrivileges->getDataForChangeOrCopyUser();
        $this->assertEquals(
            'pma_password',
            $password
        );
        $this->assertEquals(
            [],
            $queries
        );
        unset($_POST['change_copy']);
    }


    /**
     * Test for getListForExportUserDefinition
     *
     * @return void
     */
    public function testGetHtmlForExportUserDefinition()
    {
        $username = "PMA_username";
        $hostname = "PMA_hostname";

        list($title, $export)
            = $this->serverPrivileges->getListForExportUserDefinition($username, $hostname);

        //validate 1: $export
        $this->assertStringContainsString(
            'grant user2 delete',
            $export
        );
        $this->assertStringContainsString(
            'grant user1 select',
            $export
        );
        $this->assertStringContainsString(
            '<textarea class="export"',
            $export
        );

        //validate 2: $title
        $title_user = __('User') . ' `' . htmlspecialchars($username)
            . '`@`' . htmlspecialchars($hostname) . '`';
        $this->assertStringContainsString(
            $title_user,
            $title
        );
    }

    /**
     * Test for addUser
     *
     * @return void
     */
    public function testAddUser()
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
        $_POST['userGroup'] = "username";
        $_POST['authentication_plugin'] = 'mysql_native_password';

        list(
            $ret_message,,, $sql_query,
            $_add_user_error
        ) = $this->serverPrivileges->addUser(
            $dbname,
            $username,
            $hostname,
            $dbname,
            true
        );
        $this->assertEquals(
            'You have added a new user.',
            $ret_message->getMessage()
        );
        $this->assertEquals(
            "CREATE USER ''@'localhost' IDENTIFIED WITH mysql_native_password AS '***';"
            . "GRANT USAGE ON *.* TO ''@'localhost' REQUIRE NONE;"
            . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';",
            $sql_query
        );
        $this->assertEquals(
            false,
            $_add_user_error
        );
    }

    /**
     * Test for addUser
     *
     * @return void
     */
    public function testAddUserOld()
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
        $_POST['userGroup'] = "username";
        $_POST['authentication_plugin'] = 'mysql_native_password';

        list(
            $ret_message,,, $sql_query,
            $_add_user_error
        ) = $this->serverPrivileges->addUser(
            $dbname,
            $username,
            $hostname,
            $dbname,
            true
        );

        $this->assertEquals(
            'You have added a new user.',
            $ret_message->getMessage()
        );
        $this->assertEquals(
            "CREATE USER ''@'localhost';"
            . "GRANT USAGE ON *.* TO ''@'localhost' REQUIRE NONE;"
            . "SET PASSWORD FOR ''@'localhost' = '***';"
            . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';",
            $sql_query
        );
        $this->assertEquals(
            false,
            $_add_user_error
        );
    }

    /**
     * Test for updatePassword
     *
     * @return void
     */
    public function testUpdatePassword()
    {
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $err_url = "error.php";
        $_POST['pma_pw'] = 'pma_pw';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        $message = $this->serverPrivileges->updatePassword(
            $err_url,
            $username,
            $hostname
        );

        $this->assertEquals(
            "The password for 'pma_username'@'pma_hostname' "
            . "was changed successfully.",
            $message->getMessage()
        );
    }

    /**
     * Test for getMessageAndSqlQueryForPrivilegesRevoke
     *
     * @return void
     */
    public function testGetMessageAndSqlQueryForPrivilegesRevoke()
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
        list ($message, $sql_query)
            = $this->serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
                $dbname,
                $tablename,
                $username,
                $hostname,
                ''
            );

        $this->assertEquals(
            "You have revoked the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        $this->assertEquals(
            "REVOKE ALL PRIVILEGES ON  `pma_dbname`.`pma_tablename` "
            . "FROM 'pma_username'@'pma_hostname'; "
            . "REVOKE GRANT OPTION ON  `pma_dbname`.`pma_tablename` "
            . "FROM 'pma_username'@'pma_hostname';",
            $sql_query
        );
    }

    /**
     * Test for updatePrivileges
     *
     * @return void
     */
    public function testUpdatePrivileges()
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
        list($sql_query, $message) = $this->serverPrivileges->updatePrivileges(
            $username,
            $hostname,
            $tablename,
            $dbname,
            ''
        );

        $this->assertEquals(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        $this->assertEquals(
            "REVOKE ALL PRIVILEGES ON  `pma_dbname`.`pma_tablename` "
            . "FROM 'pma_username'@'pma_hostname';   ",
            $sql_query
        );
    }

    /**
     * Test for updatePrivileges
     *
     * @return void
     */
    public function testUpdatePrivilegesBeforeMySql8Dot11()
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

        list($sql_query, $message) = $this->serverPrivileges->updatePrivileges(
            $username,
            $hostname,
            $tablename,
            $dbname,
            ''
        );

        $this->assertEquals(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        $this->assertEquals(
            '  GRANT USAGE ON  *.* TO \'pma_username\'@\'pma_hostname\' REQUIRE NONE WITH GRANT OPTION MAX_QUERIES_PER_HOUR 1000 MAX_CONNECTIONS_PER_HOUR 20 MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40; ',
            $sql_query
        );
    }

    /**
     * Test for updatePrivileges
     *
     * @return void
     */
    public function testUpdatePrivilegesAfterMySql8Dot11()
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

        list($sql_query, $message) = $this->serverPrivileges->updatePrivileges(
            $username,
            $hostname,
            $tablename,
            $dbname,
            ''
        );

        $this->assertEquals(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        $this->assertEquals(
            '  GRANT USAGE ON  *.* TO \'pma_username\'@\'pma_hostname\'; ALTER USER \'pma_username\'@\'pma_hostname\'  REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 1000 MAX_CONNECTIONS_PER_HOUR 20 MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40;',
            $sql_query
        );
    }

    /**
     * Test for getHtmlToDisplayPrivilegesTable
     *
     * @return void
     * @group medium
     */
    public function testGetHtmlToDisplayPrivilegesTable()
    {
        $dbi_old = $GLOBALS['dbi'];
        $GLOBALS['hostname'] = "hostname";
        $GLOBALS['username'] = "username";

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchSingleRow = [
            'password' => 'pma_password',
            'max_questions' => 'max_questions',
            'max_updates' => 'max_updates',
            'max_connections' => 'max_connections',
            'max_user_connections' => 'max_user_connections',
            'Table_priv' => 'Select,Insert,Update,Delete,File,Create,Alter,Index,'
                . 'Drop,Super,Process,Reload,Shutdown,Create_routine,Alter_routine,'
                . 'Show_db,Repl_slave,Create_tmp_table,Show_view,Execute,'
                . 'Repl_client,Lock_tables,References,Grant,dd'
                . 'Create_user,Repl_slave,Repl_client',
            'Type' => "'Super1','Select','Insert','Update','Create','Alter','Index',"
                . "'Drop','Delete','File','Super','Process','Reload','Shutdown','"
                . "Show_db','Repl_slave','Create_tmp_table',"
                . "'Show_view','Create_routine','"
                . "Repl_client','Lock_tables','References','Alter_routine','"
                . "Create_user','Repl_slave','Repl_client','Execute','Grant','ddd",
        ];
        $dbi->expects($this->any())->method('fetchSingleRow')
            ->will($this->returnValue($fetchSingleRow));

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));

        $columns = [
            'val1',
            'replace1',
            5,
        ];
        $dbi->expects($this->at(0))
            ->method('fetchRow')
            ->will($this->returnValue($columns));
        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->will($this->returnValue(false));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $html = $this->serverPrivileges->getHtmlToDisplayPrivilegesTable();
        $GLOBALS['username'] = "username";

        //validate 1: fieldset
        $this->assertStringContainsString(
            '<fieldset id="fieldset_user_privtable_footer" ',
            $html
        );

        //validate 2: button
        $this->assertStringContainsString(
            __('Go'),
            $html
        );

        //validate 3: getHtmlForGlobalOrDbSpecificPrivs
        $this->assertStringContainsString(
            '<fieldset id="fieldset_user_global_rights"><legend '
            . 'data-submenu-label="' . __('Global') . '">',
            $html
        );
        $this->assertStringContainsString(
            __('Global privileges'),
            $html
        );
        $this->assertStringContainsString(
            __('Check all'),
            $html
        );
        $this->assertStringContainsString(
            __('Note: MySQL privilege names are expressed in English'),
            $html
        );

        //validate 4: getHtmlForGlobalPrivTableWithCheckboxes items
        //Select_priv
        $this->assertStringContainsString(
            '<input type="checkbox" class="checkall" name="Select_priv"',
            $html
        );
        //Create_user_priv
        $this->assertStringContainsString(
            '<input type="checkbox" class="checkall" name="Create_user_priv"',
            $html
        );
        //Insert_priv
        $this->assertStringContainsString(
            '<input type="checkbox" class="checkall" name="Insert_priv"',
            $html
        );
        //Update_priv
        $this->assertStringContainsString(
            '<input type="checkbox" class="checkall" name="Update_priv"',
            $html
        );
        //Create_priv
        $this->assertStringContainsString(
            '<input type="checkbox" class="checkall" name="Create_priv"',
            $html
        );
        //Create_routine_priv
        $this->assertStringContainsString(
            '<input type="checkbox" class="checkall" name="Create_routine_priv"',
            $html
        );
        //Execute_priv
        $this->assertStringContainsString(
            '<input type="checkbox" class="checkall" name="Execute_priv"',
            $html
        );

        //validate 5: getHtmlForResourceLimits
        $this->assertStringContainsString(
            '<legend>' . __('Resource limits') . '</legend>',
            $html
        );
        $this->assertStringContainsString(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test case for getSqlQueriesForDisplayAndAddUser
     *
     * @return void
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
        ] = $this->serverPrivileges->getSqlQueriesForDisplayAndAddUser(
            $username,
            $hostname,
            $password
        );

        //validate 1: $create_user_real
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password'
            . ' BY \'pma_password\';',
            $create_user_real
        );

        //validate 2: $create_user_show
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED WITH mysql_native_password'
            . ' BY \'***\';',
            $create_user_show
        );
    }

    /**
     * Test case for getSqlQueriesForDisplayAndAddUser
     *
     * @return void
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
        ] = $this->serverPrivileges->getSqlQueriesForDisplayAndAddUser(
            $username,
            $hostname,
            $password
        );

        //validate 1: $create_user_real
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED'
            . ' BY \'pma_password\';',
            $create_user_real
        );

        //validate 2: $create_user_show
        $this->assertEquals(
            'CREATE USER \'PMA_username\'@\'PMA_hostname\' IDENTIFIED'
            . ' BY \'***\';',
            $create_user_show
        );
    }

    /**
     * Test for getSqlQueriesForDisplayAndAddUser
     *
     * @return void
     */
    public function testGetSqlQueriesForDisplayAndAddUser()
    {

        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(50706));
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];

        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $password = "pma_password";
        $_POST['pred_password'] = 'keep';
        $_POST['authentication_plugin'] = 'mysql_native_password';
        $dbname = "PMA_db";

        list(
            $create_user_real,
            $create_user_show,
            $real_sql_query,
            $sql_query,
            ,
            ,
            $alter_real_sql_query,
            $alter_sql_query
        ) = $this->serverPrivileges->getSqlQueriesForDisplayAndAddUser(
            $username,
            $hostname,
            $password
        );

        //validate 1: $create_user_real
        $this->assertEquals(
            "CREATE USER 'PMA_username'@'PMA_hostname' IDENTIFIED "
            . "WITH mysql_native_password AS 'pma_password';",
            $create_user_real
        );

        //validate 2: $create_user_show
        $this->assertEquals(
            "CREATE USER 'PMA_username'@'PMA_hostname' IDENTIFIED "
            . "WITH mysql_native_password AS '***';",
            $create_user_show
        );

        //validate 3:$real_sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname' REQUIRE NONE;",
            $real_sql_query
        );

        //validate 4:$sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname' REQUIRE NONE;",
            $sql_query
        );

        $this->assertSame(
            '',
            $alter_real_sql_query
        );

        $this->assertSame(
            '',
            $alter_sql_query
        );

        //Test for addUserAndCreateDatabase
        list($sql_query, $message) = $this->serverPrivileges->addUserAndCreateDatabase(
            false,
            $real_sql_query,
            $sql_query,
            $username,
            $hostname,
            $dbname,
            $alter_real_sql_query,
            $alter_sql_query
        );

        //validate 5: $sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname' REQUIRE NONE;",
            $sql_query
        );

        //validate 6: $message
        $this->assertEquals(
            "You have added a new user.",
            $message->getMessage()
        );
    }

    /**
     * Test for getHtmlForTableSpecificPrivileges
     *
     * @return void
     */
    public function testGetHtmlForTableSpecificPrivileges()
    {
        $GLOBALS['strPrivDescCreate_viewTbl'] = "strPrivDescCreate_viewTbl";
        $GLOBALS['strPrivDescShowViewTbl'] = "strPrivDescShowViewTbl";
        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $db = "PMA_db";
        $table = "PMA_table";
        $columns = [
            'row1' => 'name1',
        ];
        $row = [
            'Select_priv' => 'Y',
            'Insert_priv' => 'Y',
            'Update_priv' => 'Y',
            'References_priv' => 'Y',
            'Create_view_priv' => 'Y',
            'ShowView_priv' => 'Y',
        ];

        $html = $this->serverPrivileges->getHtmlForTableSpecificPrivileges(
            $username,
            $hostname,
            $db,
            $table,
            $columns,
            $row
        );

        //validate 1: getHtmlForAttachedPrivilegesToTableSpecificColumn
        $item = $this->serverPrivileges->getHtmlForAttachedPrivilegesToTableSpecificColumn(
            $columns,
            $row
        );
        $this->assertStringContainsString(
            $item,
            $html
        );
        $this->assertStringContainsString(
            __('Allows reading data.'),
            $html
        );
        $this->assertStringContainsString(
            __('Allows inserting and replacing data'),
            $html
        );
        $this->assertStringContainsString(
            __('Allows changing data.'),
            $html
        );
        $this->assertStringContainsString(
            __('Has no effect in this MySQL version.'),
            $html
        );

        //validate 2: getHtmlForNotAttachedPrivilegesToTableSpecificColumn
        $item = $this->serverPrivileges->getHtmlForNotAttachedPrivilegesToTableSpecificColumn(
            $row
        );
        $this->assertStringContainsString(
            $item,
            $html
        );
        $this->assertStringContainsString(
            'Create_view_priv',
            $html
        );
        $this->assertStringContainsString(
            'ShowView_priv',
            $html
        );
    }

    /**
     * Test for getHtmlForLoginInformationFields
     *
     * @return void
     */
    public function testGetHtmlForLoginInformationFields()
    {
        $GLOBALS['username'] = 'pma_username';

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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
        $this->assertStringContainsString(
            __('Login Information'),
            $html
        );
        $this->assertStringContainsString(
            __('User name:'),
            $html
        );
        $this->assertStringContainsString(
            __('Any user'),
            $html
        );
        $this->assertStringContainsString(
            __('Use text field'),
            $html
        );

        $output = Util::showHint(
            __(
                'When Host table is used, this field is ignored '
                . 'and values stored in Host table are used instead.'
            )
        );
        $this->assertStringContainsString(
            $output,
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getWithClauseForAddUserAndUpdatePrivs
     *
     * @return void
     */
    public function testGetWithClauseForAddUserAndUpdatePrivs()
    {
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 10;
        $_POST['max_connections'] = 20;
        $_POST['max_updates'] = 30;
        $_POST['max_user_connections'] = 40;

        $sql_query = $this->serverPrivileges->getWithClauseForAddUserAndUpdatePrivs();
        $expect = "WITH GRANT OPTION MAX_QUERIES_PER_HOUR 10 "
            . "MAX_CONNECTIONS_PER_HOUR 20"
            . " MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40";
        $this->assertStringContainsString(
            $expect,
            $sql_query
        );
    }

    /**
     * Test for getListOfPrivilegesAndComparedPrivileges
     *
     * @return void
     */
    public function testGetListOfPrivilegesAndComparedPrivileges()
    {
        list($list_of_privileges, $list_of_compared_privileges)
            = $this->serverPrivileges->getListOfPrivilegesAndComparedPrivileges();
        $expect = "`User`, `Host`, `Select_priv`, `Insert_priv`";
        $this->assertStringContainsString(
            $expect,
            $list_of_privileges
        );
        $expect = "`Select_priv` = 'N' AND `Insert_priv` = 'N'";
        $this->assertStringContainsString(
            $expect,
            $list_of_compared_privileges
        );
        $expect = "`Create_routine_priv` = 'N' AND `Alter_routine_priv` = 'N'";
        $this->assertStringContainsString(
            $expect,
            $list_of_compared_privileges
        );
    }

    /**
     * Test for getHtmlForAddUser
     *
     * @return void
     * @group medium
     */
    public function testGetHtmlForAddUser()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

        $dbname = "pma_dbname";

        $html = $this->serverPrivileges->getHtmlForAddUser($dbname);

        //validate 1: Url::getHiddenInputs
        $this->assertStringContainsString(
            Url::getHiddenInputs('', ''),
            $html
        );

        //validate 2: getHtmlForLoginInformationFields
        $this->assertStringContainsString(
            $this->serverPrivileges->getHtmlForLoginInformationFields('new'),
            $html
        );

        //validate 3: Database for user
        $this->assertStringContainsString(
            __('Database for user'),
            $html
        );

        $template = new Template();
        $item = $template->render('checkbox', [
            'html_field_name' => 'createdb-2',
            'label' => __('Grant all privileges on wildcard name (username\\_%).'),
            'checked' => false,
            'onclick' => false,
            'html_field_id' => 'createdb-2',
        ]);
        $this->assertStringContainsString(
            $item,
            $html
        );

        //validate 4: getHtmlToDisplayPrivilegesTable
        $this->assertStringContainsString(
            $this->serverPrivileges->getHtmlToDisplayPrivilegesTable('*', '*', false),
            $html
        );

        //validate 5: button
        $this->assertStringContainsString(
            __('Go'),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getHtmlForSpecificDbPrivileges
     *
     * @return void
     */
    public function testGetHtmlForSpecificDbPrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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
        $dbi->expects($this->any())->method('isSuperuser')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $db = "pma_dbname";

        $html = $this->serverPrivileges->getHtmlForSpecificDbPrivileges($db);

        //validate 1: Url::getCommon
        $this->assertStringContainsString(
            Url::getCommon(['db' => $db]),
            $html
        );

        //validate 2: htmlspecialchars
        $this->assertStringContainsString(
            htmlspecialchars($db),
            $html
        );

        //validate 3: items
        $this->assertStringContainsString(
            __('User'),
            $html
        );
        $this->assertStringContainsString(
            __('Host'),
            $html
        );
        $this->assertStringContainsString(
            __('Type'),
            $html
        );
        $this->assertStringContainsString(
            __('Privileges'),
            $html
        );
        $this->assertStringContainsString(
            __('Grant'),
            $html
        );
        $this->assertStringContainsString(
            __('Action'),
            $html
        );

        //_pgettext('Create new user', 'New')
        $this->assertStringContainsString(
            _pgettext('Create new user', 'New'),
            $html
        );
        $this->assertStringContainsString(
            Url::getCommon(['checkprivsdb' => $db]),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getHtmlForSpecificTablePrivileges
     *
     * @return void
     */
    public function testGetHtmlForSpecificTablePrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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
        $dbi->expects($this->any())->method('isSuperuser')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $db = "pma_dbname";
        $table = "pma_table";

        $html = $this->serverPrivileges->getHtmlForSpecificTablePrivileges($db, $table);

        //validate 1: $db, $table
        $this->assertStringContainsString(
            htmlspecialchars($db) . '.' . htmlspecialchars($table),
            $html
        );

        //validate 2: Url::getCommon
        $item = Url::getCommon(
            [
                'db' => $db,
                'table' => $table,
            ]
        );
        $this->assertStringContainsString(
            $item,
            $html
        );

        //validate 3: items
        $this->assertStringContainsString(
            __('User'),
            $html
        );
        $this->assertStringContainsString(
            __('Host'),
            $html
        );
        $this->assertStringContainsString(
            __('Type'),
            $html
        );
        $this->assertStringContainsString(
            __('Privileges'),
            $html
        );
        $this->assertStringContainsString(
            __('Grant'),
            $html
        );
        $this->assertStringContainsString(
            __('Action'),
            $html
        );

        //_pgettext('Create new user', 'New')
        $this->assertStringContainsString(
            _pgettext('Create new user', 'New'),
            $html
        );
        $this->assertStringContainsString(
            Url::getCommon(
                [
                    'checkprivsdb' => $db,
                    'checkprivstable' => $table,
                ]
            ),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getHtmlTableBodyForSpecificDbOrTablePrivs
     *
     * @return void
     */
    public function testGetHtmlTableBodyForSpecificDbOrTablePrivss()
    {
        $privMap = null;
        $db = "pma_dbname";

        //$privMap = null
        $html = $this->serverPrivileges->getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
        $this->assertStringContainsString(
            __('No user found.'),
            $html
        );

        //$privMap != null
        $privMap = [
            "user1" => [
                "hostname1" => [
                    [
                        'Type' => 'g',
                        'Grant_priv' => 'Y',
                    ],
                    [
                        'Type' => 'd',
                        'Db' => "dbname",
                        'Grant_priv' => 'Y',
                    ],
                    [
                        'Type' => 't',
                        'Grant_priv' => 'N',
                    ],
                ],
            ],
        ];

        $html = $this->serverPrivileges->getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);

        //validate 1: $current_privileges
        $current_privileges = $privMap["user1"]["hostname1"];
        $current_user = "user1";
        $current_host = "hostname1";
        $this->assertStringContainsString(
            count($current_privileges) . "",
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($current_user),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($current_host),
            $html
        );

        //validate 2: privileges[0]
        $this->assertStringContainsString(
            __('global'),
            $html
        );

        //validate 3: privileges[1]
        $current = $current_privileges[1];
        $this->assertStringContainsString(
            __('wildcard'),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($current['Db']),
            $html
        );

        //validate 4: privileges[2]
        $this->assertStringContainsString(
            __('table-specific'),
            $html
        );
    }

    /**
     * Test for getUserLink
     *
     * @return void
     */
    public function testGetUserLink()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $dbname = "pma_dbname";
        $tablename = "pma_tablename";

        $html = $this->serverPrivileges->getUserLink(
            'edit',
            $username,
            $hostname,
            $dbname,
            $tablename,
            ''
        );

        $url_html = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
            ]
        );
        $this->assertStringContainsString(
            $url_html,
            $html
        );
        $this->assertStringContainsString(
            __('Edit privileges'),
            $html
        );

        $html = $this->serverPrivileges->getUserLink(
            'revoke',
            $username,
            $hostname,
            $dbname,
            $tablename,
            ''
        );

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
        $this->assertStringContainsString(
            $url_html,
            $html
        );
        $this->assertStringContainsString(
            __('Revoke'),
            $html
        );

        $html = $this->serverPrivileges->getUserLink('export', $username, $hostname);

        $url_html = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'initial' => "",
                'export' => 1,
            ]
        );
        $this->assertStringContainsString(
            $url_html,
            $html
        );
        $this->assertStringContainsString(
            __('Export'),
            $html
        );
    }

    /**
     * Test for getExtraDataForAjaxBehavior
     *
     * @return void
     */
    public function testGetExtraDataForAjaxBehavior()
    {
        $password = "pma_password";
        $sql_query = "pma_sql_query";
        $username = "pma_username";
        $hostname = "pma_hostname";
        $GLOBALS['dbname'] = "pma_dbname";
        $_POST['adduser_submit'] = "adduser_submit";
        $_POST['username'] = "username";
        $_POST['change_copy'] = "change_copy";
        $_GET['validate_username'] = "validate_username";
        $_GET['username'] = "username";
        $_POST['update_privs'] = "update_privs";

        $extra_data = $this->serverPrivileges->getExtraDataForAjaxBehavior(
            $password,
            $sql_query,
            $hostname,
            $username
        );

        //user_exists
        $this->assertEquals(
            false,
            $extra_data['user_exists']
        );

        //db_wildcard_privs
        $this->assertEquals(
            true,
            $extra_data['db_wildcard_privs']
        );

        //user_exists
        $this->assertEquals(
            false,
            $extra_data['db_specific_privs']
        );

        //new_user_initial
        $this->assertEquals(
            'P',
            $extra_data['new_user_initial']
        );

        //sql_query
        $this->assertEquals(
            Util::getMessage(null, $sql_query),
            $extra_data['sql_query']
        );

        //new_user_string
        $this->assertStringContainsString(
            htmlspecialchars($hostname),
            $extra_data['new_user_string']
        );
        $this->assertStringContainsString(
            htmlspecialchars($username),
            $extra_data['new_user_string']
        );

        //new_privileges
        $this->assertStringContainsString(
            implode(', ', $this->serverPrivileges->extractPrivInfo(null, true)),
            $extra_data['new_privileges']
        );
    }

    /**
     * Test for getChangeLoginInformationHtmlForm
     *
     * @return void
     */
    public function testGetChangeLoginInformationHtmlForm()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

        $expected_userGroup = "pma_usergroup";

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($expected_userGroup));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $html = $this->serverPrivileges->getChangeLoginInformationHtmlForm($username, $hostname);

        //Url::getHiddenInputs
        $this->assertStringContainsString(
            Url::getHiddenInputs('', ''),
            $html
        );

        //$username & $hostname
        $this->assertStringContainsString(
            htmlspecialchars($username),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($hostname),
            $html
        );

        $this->assertStringContainsString(
            $this->serverPrivileges->getHtmlForLoginInformationFields('change', $username, $hostname),
            $html
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="old_usergroup" value="'
                . $expected_userGroup . '">',
            $html
        );

        //Create a new user with the same privileges
        $this->assertStringContainsString(
            "Create a new user account with the same privileges",
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getUserGroupForUser
     *
     * @return void
     */
    public function testGetUserGroupForUser()
    {
        $username = "pma_username";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $expected_userGroup = "pma_usergroup";

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($expected_userGroup));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $returned_userGroup = $this->serverPrivileges->getUserGroupForUser($username);

        $this->assertEquals(
            $expected_userGroup,
            $returned_userGroup
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getLinkToDbAndTable
     *
     * @return void
     */
    public function testGetLinkToDbAndTable()
    {
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $tablename = "tablename";

        $html = $this->serverPrivileges->getLinkToDbAndTable($url_dbname, $dbname, $tablename);

        //$dbname
        $this->assertStringContainsString(
            __('Database'),
            $html
        );
        $this->assertStringContainsString(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            ),
            $html
        );
        $item = Url::getCommon(
            [
                'db' => $url_dbname,
                'reload' => 1,
            ]
        );
        $this->assertStringContainsString(
            $item,
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($dbname),
            $html
        );

        //$tablename
        $this->assertStringContainsString(
            __('Table'),
            $html
        );
        $this->assertStringContainsString(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'],
                'table'
            ),
            $html
        );
        $item = Url::getCommon(
            [
                'db' => $url_dbname,
                'table' => $tablename,
                'reload' => 1,
            ]
        );
        $this->assertStringContainsString(
            $item,
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($tablename),
            $html
        );
        $item = Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabTable']
        );
        $this->assertStringContainsString(
            $item,
            $html
        );
    }

    /**
     * Test for getUsersOverview
     *
     * @return void
     */
    public function testGetUsersOverview()
    {
        $result = [];
        $db_rights = [];
        $pmaThemeImage = "pmaThemeImage";
        $text_dir = "text_dir";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $html = $this->serverPrivileges->getUsersOverview(
            $result,
            $db_rights,
            $pmaThemeImage,
            $text_dir
        );

        //Url::getHiddenInputs
        $this->assertStringContainsString(
            Url::getHiddenInputs('', ''),
            $html
        );

        //items
        $this->assertStringContainsString(
            __('User'),
            $html
        );
        $this->assertStringContainsString(
            __('Host'),
            $html
        );
        $this->assertStringContainsString(
            __('Password'),
            $html
        );
        $this->assertStringContainsString(
            __('Global privileges'),
            $html
        );

        //Util::showHint
        $this->assertStringContainsString(
            Util::showHint(
                __('Note: MySQL privilege names are expressed in English.')
            ),
            $html
        );

        //__('User group')
        $this->assertStringContainsString(
            __('User group'),
            $html
        );
        $this->assertStringContainsString(
            __('Grant'),
            $html
        );
        $this->assertStringContainsString(
            __('Action'),
            $html
        );

        //$pmaThemeImage
        $this->assertStringContainsString(
            $pmaThemeImage,
            $html
        );

        //$text_dir
        $this->assertStringContainsString(
            $text_dir,
            $html
        );

        $this->assertStringContainsString(
            $this->serverPrivileges->getFieldsetForAddDeleteUser(),
            $html
        );
    }

    /**
     * Test for getFieldsetForAddDeleteUser
     *
     * @return void
     */
    public function testGetFieldsetForAddDeleteUser()
    {
        $result = [];
        $db_rights = [];
        $pmaThemeImage = "pmaThemeImage";
        $text_dir = "text_dir";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $html = $this->serverPrivileges->getUsersOverview(
            $result,
            $db_rights,
            $pmaThemeImage,
            $text_dir
        );

        //Url::getCommon
        $this->assertStringContainsString(
            Url::getCommon(['adduser' => 1]),
            $html
        );

        //labels
        $this->assertStringContainsString(
            __('Add user account'),
            $html
        );
        $this->assertStringContainsString(
            __('Remove selected user accounts'),
            $html
        );
        $this->assertStringContainsString(
            __('Drop the databases that have the same names as the users.'),
            $html
        );
        $this->assertStringContainsString(
            __('Drop the databases that have the same names as the users.'),
            $html
        );
    }

    /**
     * Test for getDataForDeleteUsers
     *
     * @return void
     */
    public function testGetDataForDeleteUsers()
    {
        $_POST['change_copy'] = "change_copy";
        $_POST['old_hostname'] = "old_hostname";
        $_POST['old_username'] = "old_username";
        $_SESSION['relation'][1] = [
            'PMA_VERSION' => PMA_VERSION,
            'bookmarkwork' => false,
            'historywork' => false,
            'recentwork' => false,
            'favoritework' => false,
            'uiprefswork' => false,
            'userconfigwork' => false,
            'menuswork' => false,
            'navwork' => false,
            'savedsearcheswork' => false,
            'designersettingswork' => false,
        ];

        $queries = [];

        $ret = $this->serverPrivileges->getDataForDeleteUsers($queries);

        $item = [
            "# Deleting 'old_username'@'old_hostname' ...",
            "DROP USER 'old_username'@'old_hostname';",
        ];
        $this->assertEquals(
            $item,
            $ret
        );
    }

    /**
     * Test for getAddUserHtmlFieldset
     *
     * @return void
     */
    public function testGetAddUserHtmlFieldset()
    {
        $html = $this->serverPrivileges->getAddUserHtmlFieldset();

        $this->assertStringContainsString(
            Url::getCommon(['adduser' => 1]),
            $html
        );
        $this->assertStringContainsString(
            Util::getIcon('b_usradd'),
            $html
        );
        $this->assertStringContainsString(
            __('Add user'),
            $html
        );
    }

    /**
     * Test for getHtmlHeaderForUserProperties
     *
     * @return void
     */
    public function testGetHtmlHeaderForUserProperties()
    {
        $dbname_is_wildcard = true;
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $username = "username";
        $hostname = "hostname";
        $tablename = "tablename";
        $_REQUEST['tablename'] = "tablename";

        $html = $this->serverPrivileges->getHtmlHeaderForUserProperties(
            $dbname_is_wildcard,
            $url_dbname,
            $dbname,
            $username,
            $hostname,
            $tablename,
            'table'
        );

        //title
        $this->assertStringContainsString(
            __('Edit privileges:'),
            $html
        );
        $this->assertStringContainsString(
            __('User account'),
            $html
        );

        //Url::getCommon
        $item = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => '',
                'tablename' => '',
            ]
        );
        $this->assertStringContainsString(
            $item,
            $html
        );

        //$username & $hostname
        $this->assertStringContainsString(
            htmlspecialchars($username),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($hostname),
            $html
        );

        //$dbname_is_wildcard = true
        $this->assertStringContainsString(
            __('Databases'),
            $html
        );

        //$dbname_is_wildcard = true
        $this->assertStringContainsString(
            __('Databases'),
            $html
        );

        //Url::getCommon
        $item = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $url_dbname,
                'tablename' => '',
            ]
        );
        $this->assertStringContainsString(
            $item,
            $html
        );
        $this->assertStringContainsString(
            $dbname,
            $html
        );
    }

    /**
     * Tests for getHtmlForViewUsersError
     *
     * @return void
     */
    public function testGetHtmlForViewUsersError()
    {
        $this->assertStringContainsString(
            'Not enough privilege to view users.',
            $this->serverPrivileges->getHtmlForViewUsersError()
        );
    }

    /**
     * Tests for getHtmlForUserProperties
     *
     * @return void
     */
    public function testGetHtmlForUserProperties()
    {
        $actual = $this->serverPrivileges->getHtmlForUserProperties(
            false,
            'db',
            'user',
            'host',
            'db',
            'table'
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
    }

    /**
     * Tests for getHtmlForUserOverview
     *
     * @return void
     */
    public function testGetHtmlForUserOverview()
    {
        $actual = $this->serverPrivileges->getHtmlForUserOverview('theme', '');
        $this->assertStringContainsString(
            'Note: MySQL privilege names are expressed in English.',
            $actual
        );
        $this->assertStringContainsString(
            'Note: phpMyAdmin gets the users’ privileges directly '
            . 'from MySQL’s privilege tables.',
            $actual
        );
    }

    /**
     * Tests for getHtmlForAllTableSpecificRights
     *
     * @return void
     */
    public function testGetHtmlForAllTableSpecificRights()
    {
        // Test case 1
        $actual = $this->serverPrivileges->getHtmlForAllTableSpecificRights('pma', 'host', 'table', 'pmadb');
        $this->assertStringContainsString(
            '<input type="hidden" name="username" value="pma">',
            $actual
        );
        $this->assertStringContainsString(
            '<input type="hidden" name="hostname" value="host">',
            $actual
        );
        $this->assertStringContainsString(
            '<legend data-submenu-label="Table">',
            $actual
        );
        $this->assertStringContainsString(
            'Table-specific privileges',
            $actual
        );

        // Test case 2
        $GLOBALS['dblist'] = new stdClass();
        $GLOBALS['dblist']->databases = [
            'x',
            'y',
            'z',
        ];
        $actual = $this->serverPrivileges->getHtmlForAllTableSpecificRights('pma2', 'host2', 'database', '');
        $this->assertStringContainsString(
            '<legend data-submenu-label="Database">',
            $actual
        );
        $this->assertStringContainsString(
            'Database-specific privileges',
            $actual
        );
    }

    /**
     * Tests for getHtmlForInitials
     *
     * @return void
     */
    public function testGetHtmlForInitials()
    {
        // Setup for the test
        $GLOBALS['dbi']->expects($this->any())->method('fetchRow')
            ->will($this->onConsecutiveCalls(['-']));
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];
        $actual = $this->serverPrivileges->getHtmlForInitials(['"' => true]);
        $this->assertStringContainsString('<td>A</td>', $actual);
        $this->assertStringContainsString('<td>Z</td>', $actual);
        $this->assertStringContainsString(
            '<a class="ajax" href="server_privileges.php?initial=-&amp;'
            . 'server=1&amp;lang=en">-</a>',
            $actual
        );
        $this->assertStringContainsString(
            '<a class="ajax" href="server_privileges.php?initial=%22&amp;'
            . 'server=1&amp;lang=en">"</a>',
            $actual
        );
        $this->assertStringContainsString('Show all', $actual);
    }

    /**
     * Tests for getDbRightsForUserOverview
     *
     * @return void
     */
    public function testGetDbRightsForUserOverview()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will($this->returnValue(['db', 'columns_priv']));
        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->onConsecutiveCalls(
                    [
                        'User' => 'pmauser',
                        'Host' => 'local',
                    ]
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
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test for getHtmlForAuthPluginsDropdown()
     *
     * @return void
     */
    public function testGetHtmlForAuthPluginsDropdown()
    {
        $oldDbi = $GLOBALS['dbi'];

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('query')
            ->will($this->onConsecutiveCalls(true, true));

        $plugins = [
            [
                'PLUGIN_NAME' => 'mysql_native_password',
                'PLUGIN_DESCRIPTION' => 'Native MySQL authentication',
            ],
            [
                'PLUGIN_NAME' => 'sha256_password',
                'PLUGIN_DESCRIPTION' => 'SHA256 password authentication',
            ],
        ];
        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->onConsecutiveCalls(
                    $plugins[0],
                    $plugins[1],
                    null, /* For Assertion 1 */
                    $plugins[0],
                    $plugins[1],
                    null  /* For Assertion 2 */
                )
            );
        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        /* Assertion 1 */
        $actualHtml = $this->serverPrivileges->getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'new',
            'new'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" id="select_authentication_plugin">'
            . "\n"
            . '<option value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>'
            . "\n"
            . '<option value="sha256_password">'
            . 'SHA256 password authentication</option>' . "\n" . '</select>'
            . "\n",
            $actualHtml
        );

        /* Assertion 2 */
        $actualHtml = $this->serverPrivileges->getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'change_pw',
            'new'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" '
            . 'id="select_authentication_plugin_cp">'
            . "\n" . '<option '
            . 'value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>'
            . "\n" . '<option value="sha256_password">'
            . 'SHA256 password authentication</option>' . "\n" . '</select>'
            . "\n",
            $actualHtml
        );

        /* Assertion 3 */
        $actualHtml = $this->serverPrivileges->getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'new',
            'old'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" '
            . 'id="select_authentication_plugin">'
            . "\n" . '<option '
            . 'value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>' . "\n" . '</select>'
            . "\n",
            $actualHtml
        );

        /* Assertion 4 */
        $actualHtml = $this->serverPrivileges->getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'change_pw',
            'old'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" '
            . 'id="select_authentication_plugin_cp">'
            . "\n"
            . '<option value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>'
            . "\n" . '</select>'
            . "\n",
            $actualHtml
        );

        // Restore old DBI
        $GLOBALS['dbi'] = $oldDbi;
        $this->serverPrivileges->dbi = $oldDbi;
    }

    /**
     * Tests for deleteUser
     *
     * @return void
     */
    public function testDeleteUser()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->onConsecutiveCalls(true, true, false));
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
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals('', $actual[0]);
        $this->assertEquals(
            'No users selected for deleting!',
            $actual[1]->getMessage()
        );

        // Test case 2 : all successful queries
        $_POST['mode'] = 3;
        $queries = ['foo'];
        $actual = $this->serverPrivileges->deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals(
            "foo\n# Reloading the privileges …\nFLUSH PRIVILEGES;",
            $actual[0]
        );
        $this->assertEquals(
            'The selected users have been deleted successfully.',
            $actual[1]->getMessage()
        );

        // Test case 3 : failing queries
        $_POST['mode'] = 1;
        $queries = ['bar'];
        $actual = $this->serverPrivileges->deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals("bar", $actual[0]);
        $this->assertEquals(
            'Some error occurred!' . "\n",
            $actual[1]->getMessage()
        );
    }
}
