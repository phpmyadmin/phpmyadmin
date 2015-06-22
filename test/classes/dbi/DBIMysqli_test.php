<?php
/**
 * Tests for PMA_DBI_Mysqli class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Index.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/dbi/DBIMysqli.class.php';
require_once 'libraries/Theme.class.php';

/**
 * Tests for PMA_DBI_Mysqli class
 *
 * @package PhpMyAdmin-test
 */
class PMA_DBI_Mysqli_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['cfg']['Server']['ssl'] = false;
        $GLOBALS['cfg']['PersistentConnections'] = false;
        $GLOBALS['cfg']['Server']['compress'] = true;
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ActionLinksMode'] = "both";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $this->object = new PMA_DBI_Mysqli();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for mysqli related functions, using runkit_function_redefine
     *
     * @return void
     *
     * @group medium
     */
    public function testMysqliDBI()
    {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped("Cannot redefine function");
        }

        //FOR UT, we just test the right mysql client API is called
        runkit_function_redefine(
            'mysqli_real_connect', '', 'return "mysqli_real_connect";'
        );
        runkit_function_redefine('mysqli_init', '', 'return "mysqli_init";');
        runkit_function_redefine('mysqli_options', '', 'return "mysqli_options";');
        runkit_function_redefine('mysqli_query', '', 'return "mysqli_query";');
        runkit_function_redefine(
            'mysqli_multi_query', '', 'return "mysqli_multi_query";'
        );
        runkit_function_redefine(
            'mysqli_fetch_array', '', 'return "mysqli_fetch_array";'
        );
        runkit_function_redefine(
            'mysqli_data_seek', '', 'return "mysqli_data_seek";'
        );
        runkit_function_redefine(
            'mysqli_more_results', '', 'return "mysqli_more_results";'
        );
        runkit_function_redefine(
            'mysqli_next_result', '', 'return "mysqli_next_result";'
        );
        runkit_function_redefine(
            'mysqli_get_host_info', '', 'return "mysqli_get_host_info";'
        );
        runkit_function_redefine(
            'mysqli_get_proto_info', '', 'return "mysqli_get_proto_info";'
        );
        runkit_function_redefine(
            'mysqli_get_client_info', '', 'return "mysqli_get_client_info";'
        );

        $user = 'PMA_user';
        $password = 'PMA_password';
        $is_controluser = false;
        $server = array(
            'port' => 8080,
            'socket' => 123,
            'host' => 'locahost',
        );
        $auxiliary_connection = true;

        //test for connect
        $ret = $this->object->connect(
            $user, $password, $is_controluser,
            $server, $auxiliary_connection
        );
        $this->assertEquals(
            'mysqli_init',
            $ret
        );

        //test for realQuery
        $query = 'select * from DBI';
        $link = $ret;
        $options = 0;
        $ret = $this->object->realQuery($query, $link, $options);
        $this->assertEquals(
            'mysqli_query',
            $ret
        );

        //test for realMultiQuery
        $ret = $this->object->realMultiQuery($link, $query);
        $this->assertEquals(
            'mysqli_multi_query',
            $ret
        );

        //test for fetchArray
        $result = $ret;
        $ret = $this->object->fetchArray($result);
        $this->assertEquals(
            'mysqli_fetch_array',
            $ret
        );

        //test for fetchAssoc
        $result = $ret;
        $ret = $this->object->fetchAssoc($result);
        $this->assertEquals(
            'mysqli_fetch_array',
            $ret
        );

        //test for fetchRow
        $result = $ret;
        $ret = $this->object->fetchRow($result);
        $this->assertEquals(
            'mysqli_fetch_array',
            $ret
        );

        //test for dataSeek
        $result = $ret;
        $offset = 10;
        $ret = $this->object->dataSeek($result, $offset);
        $this->assertEquals(
            'mysqli_data_seek',
            $ret
        );

        //test for moreResults
        $link = $ret;
        $ret = $this->object->moreResults($link);
        $this->assertEquals(
            'mysqli_more_results',
            $ret
        );

        //test for nextResult
        $link = $ret;
        $ret = $this->object->nextResult($link);
        $this->assertEquals(
            'mysqli_next_result',
            $ret
        );

        //test for getHostInfo
        $link = $ret;
        $ret = $this->object->getHostInfo($link);
        $this->assertEquals(
            'mysqli_get_host_info',
            $ret
        );

        //test for getProtoInfo
        $link = $ret;
        $ret = $this->object->getProtoInfo($link);
        $this->assertEquals(
            'mysqli_get_proto_info',
            $ret
        );

        //test for getClientInfo
        $ret = $this->object->getClientInfo();
        $this->assertEquals(
            'mysqli_get_client_info',
            $ret
        );
    }

    /**
     * Test for selectDb
     *
     * @return void
     *
     * @group medium
     */
    public function testSelectDb()
    {
        $this->markTestIncomplete('Not testing anything');
        //$link is empty
        $GLOBALS['userlink'] = null;
        $this->assertEquals(
            false,
            $this->object->selectDb("PMA", null)
        );
    }

    /**
     * Test for numRows
     *
     * @return void
     *
     * @group medium
     */
    public function testNumRows()
    {
        $this->assertEquals(
            0,
            $this->object->numRows(true)
        );
    }
}
