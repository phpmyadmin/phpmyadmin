<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_privileges.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;


require_once 'libraries/url_generating.lib.php';

require_once 'libraries/database_interface.inc.php';

require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';


require_once 'libraries/relation.lib.php';
require_once 'libraries/relation_cleanup.lib.php';
require_once 'libraries/server_privileges.lib.php';

/**
 * PMA_ServerPrivileges_Test class
 *
 * this class is for testing server_privileges.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerPrivileges_Test extends PHPUnit_Framework_TestCase
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
        if (!defined("PMA_MYSQL_VERSION_COMMENT")) {
            define("PMA_MYSQL_VERSION_COMMENT", "MySQL");
        }

        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

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
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['server'] = 1;
        $GLOBALS['hostname'] = "hostname";
        $GLOBALS['username'] = "username";
        $GLOBALS['collation_connection'] = "collation_connection";
        $GLOBALS['text_dir'] = "text_dir";
        $GLOBALS['is_reload_priv'] = true;

        //$_POST
        $_POST['pred_password'] = 'none';
        //$_SESSION
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();
        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'menuswork' => true
        );

        $pmaconfig = $this->getMockBuilder('PMA\libraries\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['PMA_Config'] = $pmaconfig;

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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
        $GLOBALS['is_superuser'] = true;
        $GLOBALS['is_grantuser'] = true;
        $GLOBALS['is_createuser'] = true;
        $GLOBALS['is_reload_priv'] = true;
    }

    /**
     * Test for PMA_getDataForDBInfo
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
        ) = PMA_getDataForDBInfo();
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
        $_REQUEST['pred_tablename'] = "PMA_pred__tablename";
        $_REQUEST['pred_dbname'] = array("PMA_pred_dbname");
        list(
            ,, $dbname, $tablename, $routinename,
            $db_and_table, $dbname_is_wildcard
        ) = PMA_getDataForDBInfo();
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
     * Test for PMA_wildcardEscapeForGrant
     *
     * @return void
     */
    public function testPMAWildcardEscapeForGrant()
    {
        $dbname = '';
        $tablename = '';
        $db_and_table = PMA_wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '*.*',
            $db_and_table
        );

        $dbname = 'dbname';
        $tablename = '';
        $db_and_table = PMA_wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '`dbname`.*',
            $db_and_table
        );

        $dbname = 'dbname';
        $tablename = 'tablename';
        $db_and_table = PMA_wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '`dbname`.`tablename`',
            $db_and_table
        );
    }

    /**
     * Test for PMA_rangeOfUsers
     *
     * @return void
     */
    public function testPMARangeOfUsers()
    {
        $ret = PMA_rangeOfUsers("INIT");
        $this->assertEquals(
            " WHERE `User` LIKE 'INIT%' OR `User` LIKE 'init%'",
            $ret
        );

        $ret = PMA_rangeOfUsers();
        $this->assertEquals(
            '',
            $ret
        );
    }

    /**
     * Test for PMA_getTableGrantsArray
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

        $ret = PMA_getTableGrantsArray();
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
     * Test for PMA_getGrantsArray
     *
     * @return void
     */
    public function testPMAGetGrantsArray()
    {
        $ret = PMA_getGrantsArray();
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
     * Test for PMA_getHtmlForColumnPrivileges
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

        $html = PMA_getHtmlForColumnPrivileges(
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
     * Test for PMA_getHtmlForUserGroupDialog
     *
     * @return void
     */
    public function testPMAGetHtmlForUserGroupDialog()
    {
        $username = "pma_username";
        $is_menuswork = true;
        $_REQUEST['edit_user_group_dialog'] = "edit_user_group_dialog";
        $GLOBALS['is_ajax_request'] = false;

        //PMA_getHtmlForUserGroupDialog
        $html = PMA_getHtmlForUserGroupDialog($username, $is_menuswork);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //PMA_URL_getHiddenInputs
        $params = array('username' => $username);
        $html_output = PMA_URL_getHiddenInputs($params);
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
     * Test for PMA_getHtmlToChooseUserGroup
     *
     * @return void
     */
    public function testPMAGetHtmlToChooseUserGroup()
    {
        $username = "pma_username";

        //PMA_getHtmlToChooseUserGroup
        $html = PMA_getHtmlToChooseUserGroup($username);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //PMA_URL_getHiddenInputs
        $params = array('username' => $username);
        $html_output = PMA_URL_getHiddenInputs($params);
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
     * Test for PMA_getHtmlForResourceLimits
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

        //PMA_getHtmlForResourceLimits
        $html = PMA_getHtmlForResourceLimits($row);
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
            __('Limits the number of simultaneous connections the user may have.'),
            $html
        );
        $this->assertContains(
            __('Limits the number of simultaneous connections the user may have.'),
            $html
        );
    }

    /**
     * Test for PMA_getSqlQueryForDisplayPrivTable
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
        $ret = PMA_getSqlQueryForDisplayPrivTable(
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
        $ret = PMA_getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
        );
        $sql = "SELECT * FROM `mysql`.`db`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . " AND '" . PMA\libraries\Util::unescapeMysqlWildcards($db) . "'"
            . " LIKE `Db`;";
        $this->assertEquals(
            $sql,
            $ret
        );

        //$table == 'pma_table'
        $db = "pma_db";
        $table = "pma_table";
        $ret = PMA_getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
        );
        $sql = "SELECT `Table_priv`"
            . " FROM `mysql`.`tables_priv`"
            . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username) . "'"
            . " AND `Host` = '" . $GLOBALS['dbi']->escapeString($hostname) . "'"
            . " AND `Db` = '" . PMA\libraries\Util::unescapeMysqlWildcards($db) . "'"
            . " AND `Table_name` = '" . $GLOBALS['dbi']->escapeString($table) . "';";
        $this->assertEquals(
            $sql,
            $ret
        );

        // SQL escaping
        $db = "db' AND";
        $table = "pma_table";
        $ret = PMA_getSqlQueryForDisplayPrivTable(
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
     * Test for PMA_getDataForChangeOrCopyUser
     *
     * @return void
     */
    public function testPMAGetDataForChangeOrCopyUser()
    {
        //$_REQUEST['change_copy'] not set
        list($queries, $password) = PMA_getDataForChangeOrCopyUser();
        $this->assertEquals(
            null,
            $queries
        );
        $this->assertEquals(
            null,
            $queries
        );

        //$_REQUEST['change_copy'] is set
        $_REQUEST['change_copy'] = true;
        $_REQUEST['old_username'] = 'PMA_old_username';
        $_REQUEST['old_hostname'] = 'PMA_old_hostname';
        list($queries, $password) = PMA_getDataForChangeOrCopyUser();
        $this->assertEquals(
            'pma_password',
            $password
        );
        $this->assertEquals(
            array(),
            $queries
        );
        unset($_REQUEST['change_copy']);
    }


    /**
     * Test for PMA_getListForExportUserDefinition
     *
     * @return void
     */
    public function testPMAGetHtmlForExportUserDefinition()
    {
        $username = "PMA_username";
        $hostname = "PMA_hostname";

        list($title, $export)
            = PMA_getListForExportUserDefinition($username, $hostname);

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
     * Test for PMA_addUser
     *
     * @return void
     */
    public function testPMAAddUser()
    {
        // Case 1 : Test with Newer version
        $restoreMySQLVersion = "PMANORESTORE";
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped(
                'Cannot redefine constant. Missing runkit extension'
            );
        } else {
            $restoreMySQLVersion = PMA_MYSQL_INT_VERSION;
            runkit_constant_redefine('PMA_MYSQL_INT_VERSION', 50706);
        }

        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $_REQUEST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_POST['pred_password'] = 'keep';
        $_REQUEST['createdb-3'] = true;
        $_REQUEST['userGroup'] = "username";
        $_REQUEST['authentication_plugin'] = 'mysql_native_password';

        list(
            $ret_message,,, $sql_query,
            $_add_user_error
        ) = PMA_addUser(
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

        if ($restoreMySQLVersion !== "PMANORESTORE") {
            runkit_constant_redefine('PMA_MYSQL_INT_VERSION', $restoreMySQLVersion);
        }


        // Case 2 : Test with older versions
        $restoreMySQLVersion = "PMANORESTORE";
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped(
                'Cannot redefine constant. Missing runkit extension'
            );
        } else {
            $restoreMySQLVersion = PMA_MYSQL_INT_VERSION;
            runkit_constant_redefine('PMA_MYSQL_INT_VERSION', 50506);
        }

        list(
            $ret_message,,, $sql_query,
            $_add_user_error
        ) = PMA_addUser(
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

        if ($restoreMySQLVersion !== "PMANORESTORE") {
            runkit_constant_redefine('PMA_MYSQL_INT_VERSION', $restoreMySQLVersion);
        }
    }

    /**
     * Test for PMA_updatePassword
     *
     * @return void
     */
    public function testPMAUpdatePassword()
    {
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $err_url = "error.php";
        $_POST['pma_pw'] = 'pma_pw';
        $_REQUEST['authentication_plugin'] = 'mysql_native_password';

        $message = PMA_updatePassword(
            $err_url, $username, $hostname
        );

        $this->assertEquals(
            "The password for 'pma_username'@'pma_hostname' "
            . "was changed successfully.",
            $message->getMessage()
        );
    }

    /**
     * Test for PMA_getMessageAndSqlQueryForPrivilegesRevoke
     *
     * @return void
     */
    public function testPMAGetMessageAndSqlQueryForPrivilegesRevoke()
    {
        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $_REQUEST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_REQUEST['createdb-3'] = true;
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        list ($message, $sql_query)
            = PMA_getMessageAndSqlQueryForPrivilegesRevoke(
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
     * Test for PMA_updatePrivileges
     *
     * @return void
     */
    public function testPMAUpdatePrivileges()
    {
        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $_REQUEST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_REQUEST['createdb-3'] = true;
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        list($sql_query, $message) = PMA_updatePrivileges(
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
     * Test for PMA_getHtmlToDisplayPrivilegesTable
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
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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

        $html = PMA_getHtmlToDisplayPrivilegesTable();
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

        //validate 3: PMA_getHtmlForGlobalOrDbSpecificPrivs
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

        //validate 4: PMA_getHtmlForGlobalPrivTableWithCheckboxes items
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

        //validate 5: PMA_getHtmlForResourceLimits
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
     * Test for PMA_getSqlQueriesForDisplayAndAddUser
     *
     * @return void
     */
    public function testPMAGetSqlQueriesForDisplayAndAddUser()
    {
        $restoreMySQLVersion = "PMANORESTORE";

        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped(
                'Cannot redefine constant. Missing runkit extension'
            );
        } else {
            $restoreMySQLVersion = PMA_MYSQL_INT_VERSION;
            runkit_constant_redefine('PMA_MYSQL_INT_VERSION', 50706);
        }

        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $password = "pma_password";
        $_POST['pred_password'] = 'keep';
        $_REQUEST['authentication_plugin'] = 'mysql_native_password';
        $dbname = "PMA_db";

        list($create_user_real, $create_user_show, $real_sql_query, $sql_query)
            = PMA_getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

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

        //test for PMA_addUserAndCreateDatabase
        list($sql_query, $message) = PMA_addUserAndCreateDatabase(
            false, $real_sql_query, $sql_query, $username, $hostname, $dbname
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

        if ($restoreMySQLVersion !== "PMANORESTORE") {
            runkit_constant_redefine('PMA_MYSQL_INT_VERSION', $restoreMySQLVersion);
        }
    }

    /**
     * Test for PMA_getHtmlForTableSpecificPrivileges
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

        $html = PMA_getHtmlForTableSpecificPrivileges(
            $username, $hostname, $db, $table, $columns, $row
        );

        //validate 1: PMA_getHtmlForAttachedPrivilegesToTableSpecificColumn
        $item = PMA_getHtmlForAttachedPrivilegesToTableSpecificColumn(
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

        //validate 2: PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn
        $item = PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn(
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
     * Test for PMA_getHtmlForLoginInformationFields
     *
     * @return void
     */
    public function testPMAGetHtmlForLoginInformationFields()
    {
        $GLOBALS['username'] = 'pma_username';

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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

        $html = PMA_getHtmlForLoginInformationFields();

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

        $output = PMA\libraries\Util::showHint(
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
     * Test for PMA_getWithClauseForAddUserAndUpdatePrivs
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

        $sql_query = PMA_getWithClauseForAddUserAndUpdatePrivs();
        $expect = "WITH GRANT OPTION MAX_QUERIES_PER_HOUR 10 "
            . "MAX_CONNECTIONS_PER_HOUR 20"
            . " MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40";
        $this->assertContains(
            $expect,
            $sql_query
        );

    }

    /**
     * Test for PMA_getListOfPrivilegesAndComparedPrivileges
     *
     * @return void
     */
    public function testPMAGetListOfPrivilegesAndComparedPrivileges()
    {
        list($list_of_privileges, $list_of_compared_privileges)
            = PMA_getListOfPrivilegesAndComparedPrivileges();
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
     * Test for PMA_getHtmlForAddUser
     *
     * @return void
     * @group medium
     */
    public function testPMAGetHtmlForAddUser()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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

        $html = PMA_getHtmlForAddUser($dbname);

        //validate 1: PMA_URL_getHiddenInputs
        $this->assertContains(
            PMA_URL_getHiddenInputs('', ''),
            $html
        );

        //validate 2: PMA_getHtmlForLoginInformationFields
        $this->assertContains(
            PMA_getHtmlForLoginInformationFields('new'),
            $html
        );

        //validate 3: Database for user
        $this->assertContains(
            __('Database for user'),
            $html
        );

        $item = PMA\libraries\Util::getCheckbox(
            'createdb-2',
            __('Grant all privileges on wildcard name (username\\_%).'),
            false, false, 'createdb-2'
        );
        $this->assertContains(
            $item,
            $html
        );

        //validate 4: PMA_getHtmlToDisplayPrivilegesTable
        $this->assertContains(
            PMA_getHtmlToDisplayPrivilegesTable('*', '*', false),
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
     * Test for PMA_getHtmlForSpecificDbPrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForSpecificDbPrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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

        $db = "pma_dbname";

        $html = PMA_getHtmlForSpecificDbPrivileges($db);

        //validate 1: PMA_URL_getCommon
        $this->assertContains(
            PMA_URL_getCommon(array('db' => $db)),
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
            PMA_URL_getCommon(array('checkprivsdb' => $db)),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getHtmlForSpecificTablePrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForSpecificTablePrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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

        $db = "pma_dbname";
        $table = "pma_table";

        $html = PMA_getHtmlForSpecificTablePrivileges($db, $table);

        //validate 1: $db, $table
        $this->assertContains(
            htmlspecialchars($db) . '.' . htmlspecialchars($table),
            $html
        );

        //validate 2: PMA_URL_getCommon
        $item = PMA_URL_getCommon(
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
            PMA_URL_getCommon(
                array('checkprivsdb' => $db, 'checkprivstable' => $table)
            ),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getHtmlTableBodyForSpecificDbOrTablePrivs
     *
     * @return void
     */
    public function testPMAGetHtmlTableBodyForSpecificDbOrTablePrivss()
    {
        $privMap = null;
        $db = "pma_dbname";

        //$privMap = null
        $html = PMA_getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
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

        $html = PMA_getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);

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
     * Test for PMA_getUserLink
     *
     * @return void
     */
    public function testPMAGetUserLink()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $dbname = "pma_dbname";
        $tablename = "pma_tablename";

        $html = PMA_getUserLink(
            'edit', $username, $hostname, $dbname, $tablename, ''
        );

        $url_html = PMA_URL_getCommon(
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

        $html = PMA_getUserLink(
            'revoke', $username, $hostname, $dbname, $tablename, ''
        );

        $url_html = PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
                'revokeall' => 1,
            )
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Revoke'),
            $html
        );

        $html = PMA_getUserLink('export', $username, $hostname);

        $url_html = PMA_URL_getCommon(
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
     * Test for PMA_getExtraDataForAjaxBehavior
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
        $_REQUEST['adduser_submit'] = "adduser_submit";
        $_REQUEST['change_copy'] = "change_copy";
        $_REQUEST['validate_username'] = "validate_username";
        $_REQUEST['username'] = "username";
        $_POST['update_privs'] = "update_privs";

        //PMA_getExtraDataForAjaxBehavior
        $extra_data = PMA_getExtraDataForAjaxBehavior(
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
            PMA\libraries\Util::getMessage(null, $sql_query),
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
            join(', ', PMA_extractPrivInfo(null, true)),
            $extra_data['new_privileges']
        );
    }

    /**
     * Test for PMA_getChangeLoginInformationHtmlForm
     *
     * @return void
     */
    public function testPMAGetChangeLoginInformationHtmlForm()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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

        //PMA_getChangeLoginInformationHtmlForm
        $html = PMA_getChangeLoginInformationHtmlForm($username, $hostname);

        //PMA_URL_getHiddenInputs
        $this->assertContains(
            PMA_URL_getHiddenInputs('', ''),
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

        //PMA_getHtmlForLoginInformationFields
        $this->assertContains(
            PMA_getHtmlForLoginInformationFields('change', $username, $hostname),
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
     * Test for PMA_getUserGroupForUser
     *
     * @return void
     */
    public function testPMAGetUserGroupForUser()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $expected_userGroup = "pma_usergroup";

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($expected_userGroup));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $returned_userGroup = PMA_getUserGroupForUser($username);

        $this->assertEquals(
            $expected_userGroup,
            $returned_userGroup
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getLinkToDbAndTable
     *
     * @return void
     */
    public function testPMAGetLinkToDbAndTable()
    {
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $tablename = "tablename";

        $html = PMA_getLinkToDbAndTable($url_dbname, $dbname, $tablename);

        //$dbname
        $this->assertContains(
            __('Database'),
            $html
        );
        $this->assertContains(
            PMA\libraries\Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            ),
            $html
        );
        $item = PMA_URL_getCommon(
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
            PMA\libraries\Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'], 'table'
            ),
            $html
        );
        $item = PMA_URL_getCommon(
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
        $item = PMA\libraries\Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabTable']
        );
        $this->assertContains(
            $item,
            $html
        );
    }

    /**
     * Test for PMA_getUsersOverview
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

        $html = PMA_getUsersOverview(
            $result, $db_rights, $pmaThemeImage, $text_dir
        );

        //PMA_URL_getHiddenInputs
        $this->assertContains(
            PMA_URL_getHiddenInputs('', ''),
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

        //PMA\libraries\Util::showHint
        $this->assertContains(
            PMA\libraries\Util::showHint(
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

        //PMA_getFieldsetForAddDeleteUser
        $this->assertContains(
            PMA_getFieldsetForAddDeleteUser(),
            $html
        );
    }

    /**
     * Test for PMA_getFieldsetForAddDeleteUser
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

        $html = PMA_getUsersOverview(
            $result, $db_rights, $pmaThemeImage, $text_dir
        );

        //PMA_URL_getCommon
        $this->assertContains(
            PMA_URL_getCommon(array('adduser' => 1)),
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
     * Test for PMA_getDataForDeleteUsers
     *
     * @return void
     */
    public function testPMAGetDataForDeleteUsers()
    {
        $_REQUEST['change_copy'] = "change_copy";
        $_REQUEST['old_hostname'] = "old_hostname";
        $_REQUEST['old_username'] = "old_username";
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

        $ret = PMA_getDataForDeleteUsers($queries);

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
     * Test for PMA_getAddUserHtmlFieldset
     *
     * @return void
     */
    public function testPMAGetAddUserHtmlFieldset()
    {
        $html = PMA_getAddUserHtmlFieldset();

        $this->assertContains(
            PMA_URL_getCommon(array('adduser' => 1)),
            $html
        );
        $this->assertContains(
            PMA\libraries\Util::getIcon('b_usradd.png'),
            $html
        );
        $this->assertContains(
            __('Add user'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlHeaderForUserProperties
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

        $html = PMA_getHtmlHeaderForUserProperties(
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

        //PMA_URL_getCommon
        $item = PMA_URL_getCommon(
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

        //PMA_URL_getCommon
        $item = PMA_URL_getCommon(
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
     * Tests for PMA_getHtmlForViewUsersError
     *
     * @return void
     */
    function testPMAGetHtmlForViewUsersError()
    {
        $this->assertContains(
            'Not enough privilege to view users.',
            PMA_getHtmlForViewUsersError()
        );
    }

    /**
     * Tests for PMA_getHtmlForUserProperties
     *
     * @return void
     */
    function testPMAGetHtmlForUserProperties()
    {
        $actual = PMA_getHtmlForUserProperties(
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
     * Tests for PMA_getHtmlForUserOverview
     *
     * @return void
     */
    function testPMAGetHtmlForUserOverview()
    {
        $actual = PMA_getHtmlForUserOverview('theme', '');
        $this->assertContains(
            'Note: MySQL privilege names are expressed in English.', $actual
        );
        $this->assertContains(
            'Note: phpMyAdmin gets the users\' privileges directly '
            . 'from MySQL\'s privilege tables.',
            $actual
        );
    }

    /**
     * Tests for PMA_getHtmlForAllTableSpecificRights
     *
     * @return void
     */
    function testPMAGetHtmlForAllTableSpecificRights()
    {
        // Test case 1
        $actual = PMA_getHtmlForAllTableSpecificRights('pma', 'host', 'table', 'pmadb');
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
        $GLOBALS['dblist'] = new stdClass();
        $GLOBALS['dblist']->databases = array('x', 'y', 'z');
        $actual = PMA_getHtmlForAllTableSpecificRights('pma2', 'host2', 'database', '');
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
     * Tests for PMA_getHtmlForInitials
     *
     * @return void
     */
    function testPMAGetHtmlForInitials()
    {
        // Setup for the test
        $GLOBALS['dbi']->expects($this->any())->method('fetchRow')
            ->will($this->onConsecutiveCalls(array('-')));
        $actual = PMA_getHtmlForInitials(array('"' => true));
        $this->assertContains('<td>A</td>', $actual);
        $this->assertContains('<td>Z</td>', $actual);
        $this->assertContains(
            '<td><a class="ajax" href="server_privileges.php?initial=-&amp;'
            . 'server=1&amp;lang=en&amp;collation_connection='
            . 'collation_connection&amp;token=token">-</a></td>',
            $actual
        );
        $this->assertContains(
            '<td><a class="ajax" href="server_privileges.php?initial=%22&amp;'
            . 'server=1&amp;lang=en&amp;collation_connection='
            . 'collation_connection&amp;token=token">"</a>',
            $actual
        );
        $this->assertContains('Show all', $actual);
    }

    /**
     * Tests for PMA_getDbRightsForUserOverview
     *
     * @return void
     */
    function testPMAGetDbRightsForUserOverview()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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
        $actual = PMA_getDbRightsForUserOverview();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests for PMA_deleteUser
     *
     * @return void
     */
    function testPMADeleteUser()
    {
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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
        $actual = PMA_deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals('', $actual[0]);
        $this->assertEquals(
            'No users selected for deleting!', $actual[1]->getMessage()
        );

        // Test case 2 : all successful queries
        $_REQUEST['mode'] = 3;
        $queries = array('foo');
        $actual = PMA_deleteUser($queries);
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
        $_REQUEST['mode'] = 1;
        $queries = array('bar');
        $actual = PMA_deleteUser($queries);
        $this->assertArrayHasKey(0, $actual);
        $this->assertArrayHasKey(1, $actual);
        $this->assertEquals("bar", $actual[0]);
        $this->assertEquals(
            'Some error occurred!' . "\n",
            $actual[1]->getMessage()
        );
    }
}
