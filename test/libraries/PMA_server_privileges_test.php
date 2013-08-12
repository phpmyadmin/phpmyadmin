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
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/Message.class.php';
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
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;

        $GLOBALS['table'] = "table";
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['server'] = 1;
        
        //$_POST
        $_POST['pred_password'] = 'none';
        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
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

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for PMA_getHtmlForExportUserDefinition
     *
     * @return void
     */
    public function testPMAGetHtmlForExportUserDefinition()
    {
        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['Server']['usergroups'] = 'usergroups';
        $GLOBALS['cfg']['Server']['users'] = 'users';
        $GLOBALS['cfg']['TextareaCols'] = 'TextareaCols';
        $GLOBALS['cfg']['TextareaRows'] = 'TextareaCols';
        
        list($title, $export) 
            = PMA_getHtmlForExportUserDefinition($username, $hostname);
        
        //validate 1: $export
        $result = '<textarea class="export" cols="' . $GLOBALS['cfg']['TextareaCols']
        . '" rows="' . $GLOBALS['cfg']['TextareaRows'];
        $this->assertContains(
            'grant user2 delete',
            $export
        );
        $this->assertContains(
            'grant user1 select',
            $export
        );
        $this->assertContains(
            $result,
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
     * Test for PMA_getHtmlForSubMenusOnUsersPage
     *
     * @return void
     */
    public function testPMAGetHtmlForSubMenusOnUsersPage()
    {
        $html = PMA_getHtmlForSubMenusOnUsersPage('server_privileges.php');

        //validate 1: topmenu2
        $this->assertContains(
            '<ul id="topmenu2">',
            $html
        );
        
        //validate 2: tabactive for server_privileges.php
        $this->assertContains(
            '<a class="tabactive" href="server_privileges.php',
            $html
        );
        $this->assertContains(
            __('Users overview'),
            $html
        );
        
        //validate 3: not-active for server_user_groups.php
        $this->assertContains(
            '<a href="server_user_groups.php',
            $html
        );
        $this->assertContains(
            __('User groups'),
            $html
        );
    }

    /**
     * Test for PMA_getSqlQueriesForDisplayAndAddUser
     *
     * @return void
     */
    public function testPMAGetSqlQueriesForDisplayAndAddUser()
    {
        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $password = "PMA_password";
        $dbname = "PMA_db";

        list($create_user_real, $create_user_show, $real_sql_query, $sql_query)
            = PMA_getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

        //validate 1: $create_user_real
        $this->assertEquals(
            "CREATE USER 'PMA_username'@'PMA_hostname';",
            $create_user_real
        );
        
        //validate 2: $create_user_show
        $this->assertEquals(
            "CREATE USER 'PMA_username'@'PMA_hostname';",
            $create_user_show
        );
        
        //validate 3:$real_sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname';",
            $real_sql_query
        );
        
        //validate 4:$sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname';",
            $sql_query
        );
        
        
        //test for PMA_addUserAndCreateDatabase
        list($sql_query, $message) = PMA_addUserAndCreateDatabase(
            false, $real_sql_query, $sql_query, $username, $hostname, $dbname
        );
        
        //validate 5: $sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname';" 
            . "CREATE DATABASE IF NOT EXISTS `PMA_username`;" 
            . "GRANT ALL PRIVILEGES ON `PMA\_username`.* TO " 
            . "'PMA_username'@'PMA_hostname';" 
            . "GRANT ALL PRIVILEGES ON `PMA_username\_%`.* TO " 
            . "'PMA_username'@'PMA_hostname';" 
            . "GRANT ALL PRIVILEGES ON `PMA_db`.* TO 'PMA_username'@'PMA_hostname';",
            $sql_query
        );
        
        //validate 6: $message
        $this->assertEquals(
            "",
            $message->getMessage()
        );
    }
}
