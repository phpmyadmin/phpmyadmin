<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Privileges
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Core;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

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
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        //Constants
        if (!defined("PMA_USR_BROWSER_AGENT")) {
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
        $GLOBALS['cfg']['SQP'] = array();
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

        $GLOBALS['cfgRelation'] = array();
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

        //$_POST
        $_POST['pred_password'] = 'none';
        //$_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'menuswork' => true
        );

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
                    array(
                        'grant user1 select',
                        'grant user2 delete'
                    )
                )
            );

        $fetchSingleRow = array(
            'password' => 'pma_password',
            'Table_priv' => 'pri1, pri2',
            'Type' => 'Type',
            '@@old_passwords' => 0,
        );
        $dbi->expects($this->any())->method('fetchSingleRow')
            ->will($this->returnValue($fetchSingleRow));

        $fetchValue = array('key1' => 'value1');
        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($fetchValue));

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['is_grantuser'] = true;
        $GLOBALS['is_createuser'] = true;
        $GLOBALS['is_reload_priv'] = true;
    }

    /**
     * Test for getDataForDBInfo
     *
     * @return void
     */
    public function testPMAGetDataForDBInfo()
    {
        $_REQUEST['username'] = "PMA_username";
        $_REQUEST['hostname'] = "PMA_hostname";
        $_REQUEST['tablename'] = "PMA_tablename";
        $_REQUEST['dbname'] = "PMA_dbname";
        list(
            $username, $hostname, $dbname, $tablename, $routinename,
            $db_and_table, $dbname_is_wildcard
        ) = Privileges::getDataForDBInfo();
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
        $_POST['pred_dbname'] = array("PMA_pred_dbname");
        list(
            ,, $dbname, $tablename, $routinename,
            $db_and_table, $dbname_is_wildcard
        ) = Privileges::getDataForDBInfo();
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
    public function testPMAWildcardEscapeForGrant()
    {
        $dbname = '';
        $tablename = '';
        $db_and_table = Privileges::wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '*.*',
            $db_and_table
        );

        $dbname = 'dbname';
        $tablename = '';
        $db_and_table = Privileges::wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '`dbname`.*',
            $db_and_table
        );

        $dbname = 'dbname';
        $tablename = 'tablename';
        $db_and_table = Privileges::wildcardEscapeForGrant($dbname, $tablename);
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
    public function testPMARangeOfUsers()
    {
        $ret = Privileges::rangeOfUsers("INIT");
        $this->assertEquals(
            " WHERE `User` LIKE 'INIT%' OR `User` LIKE 'init%'",
            $ret
        );

        $ret = Privileges::rangeOfUsers();
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
    public function testPMAGetTableGrantsArray()
    {
        $GLOBALS['strPrivDescDelete'] = "strPrivDescDelete";
        $GLOBALS['strPrivDescCreateTbl'] = "strPrivDescCreateTbl";
        $GLOBALS['strPrivDescDropTbl'] = "strPrivDescDropTbl";
        $GLOBALS['strPrivDescIndex'] = "strPrivDescIndex";
        $GLOBALS['strPrivDescAlter'] = "strPrivDescAlter";
        $GLOBALS['strPrivDescCreateView'] = "strPrivDescCreateView";
        $GLOBALS['strPrivDescShowView'] = "strPrivDescShowView";
        $GLOBALS['strPrivDescTrigger'] = "strPrivDescTrigger";

        $ret = Privileges::getTableGrantsArray();
        $this->assertEquals(
            array(
                'Delete',
                'DELETE',
                $GLOBALS['strPrivDescDelete']
            ),
            $ret[0]
        );
        $this->assertEquals(
            array(
                'Create',
                'CREATE',
                $GLOBALS['strPrivDescCreateTbl']
            ),
            $ret[1]
        );
    }

    /**
     * Test for getGrantsArray
     *
     * @return void
     */
    public function testPMAGetGrantsArray()
    {
        $ret = Privileges::getGrantsArray();
        $this->assertEquals(
            array(
                'Select_priv',
                'SELECT',
                __('Allows reading data.')
            ),
            $ret[0]
        );
        $this->assertEquals(
            array(
                'Insert_priv',
                'INSERT',
                __('Allows inserting and replacing data.')
            ),
            $ret[1]
        );
    }

    /**
     * Test for getHtmlForColumnPrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForColumnPrivileges()
    {
        $columns = array(
            'row1' => 'name1'
        );
        $row = array(
            'name_for_select' => 'Y'
        );
        $name_for_select = 'name_for_select';
        $priv_for_header = 'priv_for_header';
        $name = 'name';
        $name_for_dfn = 'name_for_dfn';
        $name_for_current = 'name_for_current';

        $html = Privileges::getHtmlForColumnPrivileges(
            $columns, $row, $name_for_select,
            $priv_for_header, $name, $name_for_dfn, $name_for_current
        );
        //$name
        $this->assertContains(
            $name,
            $html
        );
        //$name_for_dfn
        $this->assertContains(
            $name_for_dfn,
            $html
        );
        //$priv_for_header
        $this->assertContains(
            $priv_for_header,
            $html
        );
        //$name_for_select
        $this->assertContains(
            $name_for_select,
            $html
        );
        //$columns and $row
        $this->assertContains(
            htmlspecialchars('row1'),
            $html
        );
        //$columns and $row
        $this->assertContains(
            _pgettext('None privileges', 'None'),
            $html
        );
    }

    /**
     * Test for getHtmlForRequires
     *
     * @return void
     */
    public function testPMAGetHtmlForRequires()
    {
        /* Assertion 1 */
        $row = array(
            'ssl_type'   => '',
            'ssh_cipher' => ''
        );

        $html = Privileges::getHtmlForRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertContains(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertContains(
            'value="NONE" checked="checked"',
            $html
        );
        $this->assertContains(
            'value="ANY"',
            $html
        );
        $this->assertContains(
            'value="X509"',
            $html
        );
        $this->assertContains(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 2 */
        $row = array(
            'ssl_type'   => 'ANY',
            'ssh_cipher' => ''
        );

        $html = Privileges::getHtmlForRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertContains(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertContains(
            'value="NONE"',
            $html
        );
        $this->assertContains(
            'value="ANY" checked="checked"',
            $html
        );
        $this->assertContains(
            'value="X509"',
            $html
        );
        $this->assertContains(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 3 */
        $row = array(
            'ssl_type'   => 'X509',
            'ssh_cipher' => ''
        );

        $html = Privileges::getHtmlForRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertContains(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertContains(
            'value="NONE"',
            $html
        );
        $this->assertContains(
            'value="ANY"',
            $html
        );
        $this->assertContains(
            'value="X509" checked="checked"',
            $html
        );
        $this->assertContains(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 4 */
        $row = array(
            'ssl_type'   => 'SPECIFIED',
            'ssh_cipher' => ''
        );

        $html = Privileges::getHtmlForRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertContains(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertContains(
            'value="NONE"',
            $html
        );
        $this->assertContains(
            'value="ANY"',
            $html
        );
        $this->assertContains(
            'value="X509"',
            $html
        );
        $this->assertContains(
            'value="SPECIFIED" checked="checked"',
            $html
        );
    }

    /**
     * Test for getHtmlForUserGroupDialog
     *
     * @return void
     */
    public function testPMAGetHtmlForUserGroupDialog()
    {
        $username = "pma_username";
        $is_menuswork = true;
        $_GET['edit_user_group_dialog'] = "edit_user_group_dialog";

        /* Assertion 1 */
        //Privileges::getHtmlForUserGroupDialog
        $html = Privileges::getHtmlForUserGroupDialog($username, $is_menuswork);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //Url::getHiddenInputs
        $params = array('username' => $username);
        $html_output = Url::getHiddenInputs($params);
        $this->assertContains(
            $html_output,
            $html
        );
        //__('User group')
        $this->assertContains(
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
            ->willReturnOnConsecutiveCalls(array('userG'), null);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $actualHtml = Privileges::getHtmlForUserGroupDialog($username, $is_menuswork);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $actualHtml
        );
        //Url::getHiddenInputs
        $params = array('username' => $username);
        $html_output = Url::getHiddenInputs($params);
        $this->assertContains(
            $html_output,
            $actualHtml
        );
        //__('User group')
        $this->assertContains(
            __('User group'),
            $actualHtml
        );

        // Empty default user group
        $this->assertContains(
            '<option value=""></option>',
            $actualHtml
        );

        // Current user's group selected
        $this->assertContains(
            '<option value="userG" selected="selected">userG</option>',
            $actualHtml
        );

        /* reset original dbi */
        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * Test for getHtmlToChooseUserGroup
     *
     * @return void
     */
    public function testPMAGetHtmlToChooseUserGroup()
    {
        $username = "pma_username";

        //Privileges::getHtmlToChooseUserGroup
        $html = Privileges::getHtmlToChooseUserGroup($username);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //Url::getHiddenInputs
        $params = array('username' => $username);
        $html_output = Url::getHiddenInputs($params);
        $this->assertContains(
            $html_output,
            $html
        );
        //__('User group')
        $this->assertContains(
            __('User group'),
            $html
        );
    }

    /**
     * Test for getHtmlForResourceLimits
     *
     * @return void
     */
    public function testPMAGetHtmlForResourceLimits()
    {
        $row = array(
            'max_questions' => 'max_questions',
            'max_updates' => 'max_updates',
            'max_connections' => 'max_connections',
            'max_user_connections' => 'max_user_connections',
        );

        //Privileges::getHtmlForResourceLimits
        $html = Privileges::getHtmlForResourceLimits($row);
        $this->assertContains(
            '<legend>' . __('Resource limits') . '</legend>',
            $html
        );
        $this->assertContains(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html
        );
        $this->assertContains(
            'MAX QUERIES PER HOUR',
            $html
        );
        $this->assertContains(
            $row['max_connections'],
            $html
        );
        $this->assertContains(
            $row['max_updates'],
            $html
        );
        $this->assertContains(
            $row['max_connections'],
            $html
        );
        $this->assertContains(
            $row['max_user_connections'],
            $html
        );
        $this->assertContains(
            __('Limits the number of new connections the user may open per hour.'),
            $html
        );
        $this->assertContains(
            __('Limits the number of simultaneous connections the user may have.'),
            $html
        );
    }

    /**
     * Test for getSqlQueryForDisplayPrivTable
     *
     * @return void
     */
    public function testPMAGetSqlQueryForDisplayPrivTable()
    {
        $username = "pma_username";
        $db = '*';
        $table = "pma_table";
        $hostname = "pma_hostname";

        //$db == '*'
        $ret = Privileges::getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
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
        $ret = Privileges::getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
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
        $ret = Privileges::getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
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
        $ret = Privileges::getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
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
    public function testPMAGetDataForChangeOrCopyUser()
    {
        //$_POST['change_copy'] not set
        list($queries, $password) = Privileges::getDataForChangeOrCopyUser();
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
        list($queries, $password) = Privileges::getDataForChangeOrCopyUser();
        $this->assertEquals(
            'pma_password',
            $password
        );
        $this->assertEquals(
            array(),
            $queries
        );
        unset($_POST['change_copy']);
    }


    /**
     * Test for getListForExportUserDefinition
     *
     * @return void
     */
    public function testPMAGetHtmlForExportUserDefinition()
    {
        $username = "PMA_username";
        $hostname = "PMA_hostname";

        list($title, $export)
            = Privileges::getListForExportUserDefinition($username, $hostname);

        //validate 1: $export
        $this->assertContains(
            'grant user2 delete',
            $export
        );
        $this->assertContains(
            'grant user1 select',
            $export
        );
        $this->assertContains(
            '<textarea class="export"',
            $export
        );

        //validate 2: $title
        $title_user = __('User') . ' `' . htmlspecialchars($username)
            . '`@`' . htmlspecialchars($hostname) . '`';
        $this->assertContains(
            $title_user,
            $title
        );
    }

    /**
     * Test for addUser
     *
     * @return void
     */
    public function testPMAAddUser()
    {
        // Case 1 : Test with Newer version
        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(50706));

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
        ) = Privileges::addUser(
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
    public function testPMAAddUserOld()
    {
        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(50506));

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
        ) = Privileges::addUser(
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
    public function testPMAUpdatePassword()
    {
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $err_url = "error.php";
        $_POST['pma_pw'] = 'pma_pw';
        $_POST['authentication_plugin'] = 'mysql_native_password';

        $message = Privileges::updatePassword(
            $err_url, $username, $hostname
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
    public function testPMAGetMessageAndSqlQueryForPrivilegesRevoke()
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
            = Privileges::getMessageAndSqlQueryForPrivilegesRevoke(
                $dbname, $tablename, $username, $hostname, ''
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
    public function testPMAUpdatePrivileges()
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
        list($sql_query, $message) = Privileges::updatePrivileges(
            $username, $hostname, $tablename, $dbname, ''
        );

        $this->assertEquals(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        $this->assertEquals(
            "REVOKE ALL PRIVILEGES ON  `pma_dbname`.`pma_tablename` "
            . "FROM 'pma_username'@'pma_hostname';  ",
            $sql_query
        );
    }

    /**
     * Test for getHtmlToDisplayPrivilegesTable
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlToDisplayPrivilegesTable()
    {
        $dbi_old = $GLOBALS['dbi'];
        $GLOBALS['hostname'] = "hostname";
        $GLOBALS['username'] = "username";

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchSingleRow = array(
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
        );
        $dbi->expects($this->any())->method('fetchSingleRow')
            ->will($this->returnValue($fetchSingleRow));

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));

        $columns = array('val1', 'replace1', 5);
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

        $html = Privileges::getHtmlToDisplayPrivilegesTable();
        $GLOBALS['username'] = "username";

        //validate 1: fieldset
        $this->assertContains(
            '<fieldset id="fieldset_user_privtable_footer" ',
            $html
        );

        //validate 2: button
        $this->assertContains(
            __('Go'),
            $html
        );

        //validate 3: Privileges::getHtmlForGlobalOrDbSpecificPrivs
        $this->assertContains(
            '<fieldset id="fieldset_user_global_rights"><legend '
            . 'data-submenu-label="' . __('Global') . '">',
            $html
        );
        $this->assertContains(
            __('Global privileges'),
            $html
        );
        $this->assertContains(
            __('Check all'),
            $html
        );
        $this->assertContains(
            __('Note: MySQL privilege names are expressed in English'),
            $html
        );

        //validate 4: Privileges::getHtmlForGlobalPrivTableWithCheckboxes items
        //Select_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Select_priv"',
            $html
        );
        //Create_user_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Create_user_priv"',
            $html
        );
        //Insert_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Insert_priv"',
            $html
        );
        //Update_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Update_priv"',
            $html
        );
        //Create_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Create_priv"',
            $html
        );
        //Create_routine_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Create_routine_priv"',
            $html
        );
        //Execute_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Execute_priv"',
            $html
        );

        //validate 5: Privileges::getHtmlForResourceLimits
        $this->assertContains(
            '<legend>' . __('Resource limits') . '</legend>',
            $html
        );
        $this->assertContains(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for getSqlQueriesForDisplayAndAddUser
     *
     * @return void
     */
    public function testPMAGetSqlQueriesForDisplayAndAddUser()
    {
        $restoreMySQLVersion = "PMANORESTORE";

        $GLOBALS['dbi']->expects($this->any())->method('getVersion')
            ->will($this->returnValue(50706));

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
        ) = Privileges::getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

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
        list($sql_query, $message) = Privileges::addUserAndCreateDatabase(
            false, $real_sql_query, $sql_query, $username, $hostname, $dbname, $alter_real_sql_query, $alter_sql_query
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
    public function testPMAGetHtmlForTableSpecificPrivileges()
    {
        $GLOBALS['strPrivDescCreate_viewTbl'] = "strPrivDescCreate_viewTbl";
        $GLOBALS['strPrivDescShowViewTbl'] = "strPrivDescShowViewTbl";
        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $db = "PMA_db";
        $table = "PMA_table";
        $columns = array(
            'row1' => 'name1'
        );
        $row = array(
            'Select_priv' => 'Y',
            'Insert_priv' => 'Y',
            'Update_priv' => 'Y',
            'References_priv' => 'Y',
            'Create_view_priv' => 'Y',
            'ShowView_priv' => 'Y',
        );

        $html = Privileges::getHtmlForTableSpecificPrivileges(
            $username, $hostname, $db, $table, $columns, $row
        );

        //validate 1: Privileges::getHtmlForAttachedPrivilegesToTableSpecificColumn
        $item = Privileges::getHtmlForAttachedPrivilegesToTableSpecificColumn(
            $columns, $row
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            __('Allows reading data.'),
            $html
        );
        $this->assertContains(
            __('Allows inserting and replacing data'),
            $html
        );
        $this->assertContains(
            __('Allows changing data.'),
            $html
        );
        $this->assertContains(
            __('Has no effect in this MySQL version.'),
            $html
        );

        //validate 2: Privileges::getHtmlForNotAttachedPrivilegesToTableSpecificColumn
        $item = Privileges::getHtmlForNotAttachedPrivilegesToTableSpecificColumn(
            $row
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            'Create_view_priv',
            $html
        );
        $this->assertContains(
            'ShowView_priv',
            $html
        );
    }

    /**
     * Test for getHtmlForLoginInformationFields
     *
     * @return void
     */
    public function testPMAGetHtmlForLoginInformationFields()
    {
        $GLOBALS['username'] = 'pma_username';

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            array('COLUMN_NAME' => 'Host', 'CHARACTER_MAXIMUM_LENGTH' => 80),
            array('COLUMN_NAME' => 'User', 'CHARACTER_MAXIMUM_LENGTH' => 40),
        );
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $html = Privileges::getHtmlForLoginInformationFields();

        //validate 1: __('Login Information')
        $this->assertContains(
            __('Login Information'),
            $html
        );
        $this->assertContains(
            __('User name:'),
            $html
        );
        $this->assertContains(
            __('Any user'),
            $html
        );
        $this->assertContains(
            __('Use text field'),
            $html
        );

        $output = Util::showHint(
            __(
                'When Host table is used, this field is ignored '
                . 'and values stored in Host table are used instead.'
            )
        );
        $this->assertContains(
            $output,
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for getWithClauseForAddUserAndUpdatePrivs
     *
     * @return void
     */
    public function testPMAGetWithClauseForAddUserAndUpdatePrivs()
    {
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 10;
        $_POST['max_connections'] = 20;
        $_POST['max_updates'] = 30;
        $_POST['max_user_connections'] = 40;

        $sql_query = Privileges::getWithClauseForAddUserAndUpdatePrivs();
        $expect = "WITH GRANT OPTION MAX_QUERIES_PER_HOUR 10 "
            . "MAX_CONNECTIONS_PER_HOUR 20"
            . " MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40";
        $this->assertContains(
            $expect,
            $sql_query
        );

    }

    /**
     * Test for getListOfPrivilegesAndComparedPrivileges
     *
     * @return void
     */
    public function testPMAGetListOfPrivilegesAndComparedPrivileges()
    {
        list($list_of_privileges, $list_of_compared_privileges)
            = Privileges::getListOfPrivilegesAndComparedPrivileges();
        $expect = "`User`, `Host`, `Select_priv`, `Insert_priv`";
        $this->assertContains(
            $expect,
            $list_of_privileges
        );
        $expect = "`Select_priv` = 'N' AND `Insert_priv` = 'N'";
        $this->assertContains(
            $expect,
            $list_of_compared_privileges
        );
        $expect = "`Create_routine_priv` = 'N' AND `Alter_routine_priv` = 'N'";
        $this->assertContains(
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
    public function testPMAGetHtmlForAddUser()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            array('COLUMN_NAME' => 'Host', 'CHARACTER_MAXIMUM_LENGTH' => 80),
            array('COLUMN_NAME' => 'User', 'CHARACTER_MAXIMUM_LENGTH' => 40),
        );
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $dbname = "pma_dbname";

        $html = Privileges::getHtmlForAddUser($dbname);

        //validate 1: Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs('', ''),
            $html
        );

        //validate 2: Privileges::getHtmlForLoginInformationFields
        $this->assertContains(
            Privileges::getHtmlForLoginInformationFields('new'),
            $html
        );

        //validate 3: Database for user
        $this->assertContains(
            __('Database for user'),
            $html
        );

        $item = Template::get('checkbox')
            ->render(
                array(
                    'html_field_name'   => 'createdb-2',
                    'label'             => __('Grant all privileges on wildcard name (username\\_%).'),
                    'checked'           => false,
                    'onclick'           => false,
                    'html_field_id'     => 'createdb-2',
                )
            );
        $this->assertContains(
            $item,
            $html
        );

        //validate 4: Privileges::getHtmlToDisplayPrivilegesTable
        $this->assertContains(
            Privileges::getHtmlToDisplayPrivilegesTable('*', '*', false),
            $html
        );

        //validate 5: button
        $this->assertContains(
            __('Go'),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for getHtmlForSpecificDbPrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForSpecificDbPrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            array('COLUMN_NAME' => 'Host', 'CHARACTER_MAXIMUM_LENGTH' => 80),
            array('COLUMN_NAME' => 'User', 'CHARACTER_MAXIMUM_LENGTH' => 40),
        );
        $dbi->expects($this->any())->method('isSuperuser')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $db = "pma_dbname";

        $html = Privileges::getHtmlForSpecificDbPrivileges($db);

        //validate 1: Url::getCommon
        $this->assertContains(
            Url::getCommon(array('db' => $db)),
            $html
        );

        //validate 2: htmlspecialchars
        $this->assertContains(
            htmlspecialchars($db),
            $html
        );

        //validate 3: items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Privileges'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //_pgettext('Create new user', 'New')
        $this->assertContains(
            _pgettext('Create new user', 'New'),
            $html
        );
        $this->assertContains(
            Url::getCommon(array('checkprivsdb' => $db)),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for getHtmlForSpecificTablePrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForSpecificTablePrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            array('COLUMN_NAME' => 'Host', 'CHARACTER_MAXIMUM_LENGTH' => 80),
            array('COLUMN_NAME' => 'User', 'CHARACTER_MAXIMUM_LENGTH' => 40),
        );
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));
        $dbi->expects($this->any())->method('isSuperuser')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;

        $db = "pma_dbname";
        $table = "pma_table";

        $html = Privileges::getHtmlForSpecificTablePrivileges($db, $table);

        //validate 1: $db, $table
        $this->assertContains(
            htmlspecialchars($db) . '.' . htmlspecialchars($table),
            $html
        );

        //validate 2: Url::getCommon
        $item = Url::getCommon(
            array(
                'db' => $db,
                'table' => $table,
            )
        );
        $this->assertContains(
            $item,
            $html
        );

        //validate 3: items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Privileges'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //_pgettext('Create new user', 'New')
        $this->assertContains(
            _pgettext('Create new user', 'New'),
            $html
        );
        $this->assertContains(
            Url::getCommon(
                array('checkprivsdb' => $db, 'checkprivstable' => $table)
            ),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for getHtmlTableBodyForSpecificDbOrTablePrivs
     *
     * @return void
     */
    public function testPMAGetHtmlTableBodyForSpecificDbOrTablePrivss()
    {
        $privMap = null;
        $db = "pma_dbname";

        //$privMap = null
        $html = Privileges::getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
        $this->assertContains(
            __('No user found.'),
            $html
        );

        //$privMap != null
        $privMap = array(
            "user1" => array(
                "hostname1" => array(
                    array('Type'=>'g', 'Grant_priv'=>'Y'),
                    array('Type'=>'d', 'Db'=>"dbname", 'Grant_priv'=>'Y'),
                    array('Type'=>'t', 'Grant_priv'=>'N'),
                )
            )
        );

        $html = Privileges::getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);

        //validate 1: $current_privileges
        $current_privileges = $privMap["user1"]["hostname1"];
        $current_user = "user1";
        $current_host = "hostname1";
        $this->assertContains(
            count($current_privileges) . "",
            $html
        );
        $this->assertContains(
            htmlspecialchars($current_user),
            $html
        );
        $this->assertContains(
            htmlspecialchars($current_host),
            $html
        );

        //validate 2: privileges[0]
        $this->assertContains(
            __('global'),
            $html
        );

        //validate 3: privileges[1]
        $current = $current_privileges[1];
        $this->assertContains(
            __('wildcard'),
            $html
        );
        $this->assertContains(
            htmlspecialchars($current['Db']),
            $html
        );

        //validate 4: privileges[2]
        $this->assertContains(
            __('table-specific'),
            $html
        );
    }

    /**
     * Test for getUserLink
     *
     * @return void
     */
    public function testPMAGetUserLink()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $dbname = "pma_dbname";
        $tablename = "pma_tablename";

        $html = Privileges::getUserLink(
            'edit', $username, $hostname, $dbname, $tablename, ''
        );

        $url_html = Url::getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
            )
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Edit privileges'),
            $html
        );

        $html = Privileges::getUserLink(
            'revoke', $username, $hostname, $dbname, $tablename, ''
        );

        $url_html = Url::getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
                'revokeall' => 1,
            ),
            ''
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Revoke'),
            $html
        );

        $html = Privileges::getUserLink('export', $username, $hostname);

        $url_html = Url::getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'initial' => "",
                'export' => 1,
            )
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Export'),
            $html
        );
    }

    /**
     * Test for getExtraDataForAjaxBehavior
     *
     * @return void
     */
    public function testPMAGetExtraDataForAjaxBehavior()
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

        //Privileges::getExtraDataForAjaxBehavior
        $extra_data = Privileges::getExtraDataForAjaxBehavior(
            $password, $sql_query, $hostname, $username
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
        $this->assertContains(
            htmlspecialchars($hostname),
            $extra_data['new_user_string']
        );
        $this->assertContains(
            htmlspecialchars($username),
            $extra_data['new_user_string']
        );

        //new_privileges
        $this->assertContains(
            join(', ', Privileges::extractPrivInfo(null, true)),
            $extra_data['new_privileges']
        );
    }

    /**
     * Test for getChangeLoginInformationHtmlForm
     *
     * @return void
     */
    public function testPMAGetChangeLoginInformationHtmlForm()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            array('COLUMN_NAME' => 'Host', 'CHARACTER_MAXIMUM_LENGTH' => 80),
            array('COLUMN_NAME' => 'User', 'CHARACTER_MAXIMUM_LENGTH' => 40),
        );
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));

        $expected_userGroup = "pma_usergroup";

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($expected_userGroup));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        //Privileges::getChangeLoginInformationHtmlForm
        $html = Privileges::getChangeLoginInformationHtmlForm($username, $hostname);

        //Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs('', ''),
            $html
        );

        //$username & $hostname
        $this->assertContains(
            htmlspecialchars($username),
            $html
        );
        $this->assertContains(
            htmlspecialchars($hostname),
            $html
        );

        //Privileges::getHtmlForLoginInformationFields
        $this->assertContains(
            Privileges::getHtmlForLoginInformationFields('change', $username, $hostname),
            $html
        );

        $this->assertContains(
            '<input type="hidden" name="old_usergroup" value="'
                . $expected_userGroup . '" />',
            $html
        );

        //Create a new user with the same privileges
        $this->assertContains(
            "Create a new user account with the same privileges",
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for getUserGroupForUser
     *
     * @return void
     */
    public function testPMAGetUserGroupForUser()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
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

        $returned_userGroup = Privileges::getUserGroupForUser($username);

        $this->assertEquals(
            $expected_userGroup,
            $returned_userGroup
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for getLinkToDbAndTable
     *
     * @return void
     */
    public function testPMAGetLinkToDbAndTable()
    {
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $tablename = "tablename";

        $html = Privileges::getLinkToDbAndTable($url_dbname, $dbname, $tablename);

        //$dbname
        $this->assertContains(
            __('Database'),
            $html
        );
        $this->assertContains(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            ),
            $html
        );
        $item = Url::getCommon(
            array(
                'db' => $url_dbname,
                'reload' => 1
            )
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            htmlspecialchars($dbname),
            $html
        );

        //$tablename
        $this->assertContains(
            __('Table'),
            $html
        );
        $this->assertContains(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'], 'table'
            ),
            $html
        );
        $item = Url::getCommon(
            array(
                'db' => $url_dbname,
                'table' => $tablename,
                'reload' => 1,
            )
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            htmlspecialchars($tablename),
            $html
        );
        $item = Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabTable']
        );
        $this->assertContains(
            $item,
            $html
        );
    }

    /**
     * Test for getUsersOverview
     *
     * @return void
     */
    public function testPMAGetUsersOverview()
    {
        $result = array();
        $db_rights = array();
        $pmaThemeImage = "pmaThemeImage";
        $text_dir = "text_dir";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $html = Privileges::getUsersOverview(
            $result, $db_rights, $pmaThemeImage, $text_dir
        );

        //Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs('', ''),
            $html
        );

        //items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Password'),
            $html
        );
        $this->assertContains(
            __('Global privileges'),
            $html
        );

        //Util::showHint
        $this->assertContains(
            Util::showHint(
                __('Note: MySQL privilege names are expressed in English.')
            ),
            $html
        );

        //__('User group')
        $this->assertContains(
            __('User group'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //$pmaThemeImage
        $this->assertContains(
            $pmaThemeImage,
            $html
        );

        //$text_dir
        $this->assertContains(
            $text_dir,
            $html
        );

        //Privileges::getFieldsetForAddDeleteUser
        $this->assertContains(
            Privileges::getFieldsetForAddDeleteUser(),
            $html
        );
    }

    /**
     * Test for getFieldsetForAddDeleteUser
     *
     * @return void
     */
    public function testPMAGetFieldsetForAddDeleteUser()
    {
        $result = array();
        $db_rights = array();
        $pmaThemeImage = "pmaThemeImage";
        $text_dir = "text_dir";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $html = Privileges::getUsersOverview(
            $result, $db_rights, $pmaThemeImage, $text_dir
        );

        //Url::getCommon
        $this->assertContains(
            Url::getCommon(array('adduser' => 1)),
            $html
        );

        //labels
        $this->assertContains(
            __('Add user account'),
            $html
        );
        $this->assertContains(
            __('Remove selected user accounts'),
            $html
        );
        $this->assertContains(
            __('Drop the databases that have the same names as the users.'),
            $html
        );
        $this->assertContains(
            __('Drop the databases that have the same names as the users.'),
            $html
        );
    }

    /**
     * Test for getDataForDeleteUsers
     *
     * @return void
     */
    public function testPMAGetDataForDeleteUsers()
    {
        $_POST['change_copy'] = "change_copy";
        $_POST['old_hostname'] = "old_hostname";
        $_POST['old_username'] = "old_username";
        $_SESSION['relation'][1] = array(
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
        );

        $queries = array();

        $ret = Privileges::getDataForDeleteUsers($queries);

        $item = array(
            "# Deleting 'old_username'@'old_hostname' ...",
            "DROP USER 'old_username'@'old_hostname';",
        );
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
    public function testPMAGetAddUserHtmlFieldset()
    {
        $html = Privileges::getAddUserHtmlFieldset();

        $this->assertContains(
            Url::getCommon(array('adduser' => 1)),
            $html
        );
        $this->assertContains(
            Util::getIcon('b_usradd'),
            $html
        );
        $this->assertContains(
            __('Add user'),
            $html
        );
    }

    /**
     * Test for getHtmlHeaderForUserProperties
     *
     * @return void
     */
    public function testPMAGetHtmlHeaderForUserProperties()
    {
        $dbname_is_wildcard = true;
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $username = "username";
        $hostname = "hostname";
        $tablename = "tablename";
        $_REQUEST['tablename'] = "tablename";

        $html = Privileges::getHtmlHeaderForUserProperties(
            $dbname_is_wildcard, $url_dbname, $dbname,
            $username, $hostname, $tablename, 'table'
        );

        //title
        $this->assertContains(
            __('Edit privileges:'),
            $html
        );
        $this->assertContains(
            __('User account'),
            $html
        );

        //Url::getCommon
        $item = Url::getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => '',
                'tablename' => '',
            )
        );
        $this->assertContains(
            $item,
            $html
        );

        //$username & $hostname
        $this->assertContains(
            htmlspecialchars($username),
            $html
        );
        $this->assertContains(
            htmlspecialchars($hostname),
            $html
        );

        //$dbname_is_wildcard = true
        $this->assertContains(
            __('Databases'),
            $html
        );

        //$dbname_is_wildcard = true
        $this->assertContains(
            __('Databases'),
            $html
        );

        //Url::getCommon
        $item = Url::getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $url_dbname,
                'tablename' => '',
            )
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            $dbname,
            $html
        );
    }

    /**
     * Tests for Privileges::getHtmlForViewUsersError
     *
     * @return void
     */
    function testPMAGetHtmlForViewUsersError()
    {
        $this->assertContains(
            'Not enough privilege to view users.',
            Privileges::getHtmlForViewUsersError()
        );
    }

    /**
     * Tests for Privileges::getHtmlForUserProperties
     *
     * @return void
     */
    function testPMAGetHtmlForUserProperties()
    {
        $actual = Privileges::getHtmlForUserProperties(
            false, 'db', 'user', 'host', 'db', 'table'
        );
        $this->assertContains('addUsersForm', $actual);
        $this->assertContains('SELECT', $actual);
        $this->assertContains('Allows reading data.', $actual);
        $this->assertContains('INSERT', $actual);
        $this->assertContains('Allows inserting and replacing data.', $actual);
        $this->assertContains('UPDATE', $actual);
        $this->assertContains('Allows changing data.', $actual);
        $this->assertContains('DELETE', $actual);
        $this->assertContains('Allows deleting data.', $actual);
        $this->assertContains('CREATE', $actual);
        $this->assertContains('Allows creating new tables.', $actual);
    }

    /**
     * Tests for Privileges::getHtmlForUserOverview
     *
     * @return void
     */
    function testPMAGetHtmlForUserOverview()
    {
        $actual = Privileges::getHtmlForUserOverview('theme', '');
        $this->assertContains(
            'Note: MySQL privilege names are expressed in English.', $actual
        );
        $this->assertContains(
            'Note: phpMyAdmin gets the users’ privileges directly '
            . 'from MySQL’s privilege tables.',
            $actual
        );
    }

    /**
     * Tests for Privileges::getHtmlForAllTableSpecificRights
     *
     * @return void
     */
    function testPMAGetHtmlForAllTableSpecificRights()
    {
        // Test case 1
        $actual = Privileges::getHtmlForAllTableSpecificRights('pma', 'host', 'table', 'pmadb');
        $this->assertContains(
            '<input type="hidden" name="username" value="pma" />',
            $actual
        );
        $this->assertContains(
            '<input type="hidden" name="hostname" value="host" />',
            $actual
        );
        $this->assertContains(
            '<legend data-submenu-label="Table">',
            $actual
        );
        $this->assertContains(
            'Table-specific privileges',
            $actual
        );

        // Test case 2
        $GLOBALS['dblist'] = new \stdClass();
        $GLOBALS['dblist']->databases = array('x', 'y', 'z');
        $actual = Privileges::getHtmlForAllTableSpecificRights('pma2', 'host2', 'database', '');
        $this->assertContains(
            '<legend data-submenu-label="Database">',
            $actual
        );
        $this->assertContains(
            'Database-specific privileges',
            $actual
        );
    }

    /**
     * Tests for Privileges::getHtmlForInitials
     *
     * @return void
     */
    function testPMAGetHtmlForInitials()
    {
        // Setup for the test
        $GLOBALS['dbi']->expects($this->any())->method('fetchRow')
            ->will($this->onConsecutiveCalls(array('-')));
        $actual = Privileges::getHtmlForInitials(array('"' => true));
        $this->assertContains('<td>A</td>', $actual);
        $this->assertContains('<td>Z</td>', $actual);
        $this->assertContains(
            '<a class="ajax" href="server_privileges.php?initial=-&amp;'
            . 'server=1&amp;lang=en">-</a>',
            $actual
        );
        $this->assertContains(
            '<a class="ajax" href="server_privileges.php?initial=%22&amp;'
            . 'server=1&amp;lang=en">"</a>',
            $actual
        );
        $this->assertContains('Show all', $actual);
    }

    /**
     * Tests for Privileges::getDbRightsForUserOverview
     *
     * @return void
     */
    function testPMAGetDbRightsForUserOverview()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will($this->returnValue(array('db', 'columns_priv')));
        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->onConsecutiveCalls(
                    array(
                        'User' => 'pmauser',
                        'Host' => 'local'
                    )
                )
            );
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $_GET['initial'] = 'A';
        $GLOBALS['dbi'] = $dbi;

        $expected = array(
            'pmauser' => array(
                'local' => array(
                    'User' => 'pmauser',
                    'Host' => 'local',
                    'Password' => '?',
                    'Grant_priv' => 'N',
                    'privs' => array('USAGE')
                )
            )
        );
        $actual = Privileges::getDbRightsForUserOverview();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test for getHtmlForAuthPluginsDropdown()
     *
     * @return void
     */
    function testPMAGetHtmlForAuthPluginsDropdown()
    {
        $oldDbi = $GLOBALS['dbi'];

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('query')
            ->will($this->onConsecutiveCalls(true, true));

        $plugins = array(
            array(
                'PLUGIN_NAME'=>'mysql_native_password',
                'PLUGIN_DESCRIPTION' => 'Native MySQL authentication'
            ),
            array(
                'PLUGIN_NAME' => 'sha256_password',
                'PLUGIN_DESCRIPTION' => 'SHA256 password authentication'
            )
        );
        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->onConsecutiveCalls(
                    $plugins[0], $plugins[1], null, /* For Assertion 1 */
                    $plugins[0], $plugins[1], null  /* For Assertion 2 */
                )
            );
        $GLOBALS['dbi'] = $dbi;

        /* Assertion 1 */
        $actualHtml = Privileges::getHtmlForAuthPluginsDropdown(
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
        $actualHtml = Privileges::getHtmlForAuthPluginsDropdown(
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
        $actualHtml = Privileges::getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'new',
            'old'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" '
            . 'id="select_authentication_plugin">'
            . "\n" . '<option '
            . 'value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>'. "\n" .'</select>'
            . "\n",
            $actualHtml
        );

        /* Assertion 4 */
        $actualHtml = Privileges::getHtmlForAuthPluginsDropdown(
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
    }

    /**
     * Tests for deleteUser
     *
     * @return void
     */
    function testPMADeleteUser()
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

        // Test case 1 : empty queries
        $queries = array();
        $actual = Privileges::deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals('', $actual[0]);
        $this->assertEquals(
            'No users selected for deleting!', $actual[1]->getMessage()
        );

        // Test case 2 : all successful queries
        $_POST['mode'] = 3;
        $queries = array('foo');
        $actual = Privileges::deleteUser($queries);
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
        $queries = array('bar');
        $actual = Privileges::deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals("bar", $actual[0]);
        $this->assertEquals(
            'Some error occurred!' . "\n",
            $actual[1]->getMessage()
        );
    }
}
