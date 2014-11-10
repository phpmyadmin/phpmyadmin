<?php
/**
 * Tests for PMA_DBI_Drizzle class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test and mock Drizzle class.
 */
/**
 * Drizzle for Mock drizzle class
 *
 * this class is for Mock Drizzle
 *
 * @package PhpMyAdmin-test
 */
if (!defined("DRIZZLE_CAPABILITIES_COMPRESS")) {
    define("DRIZZLE_CAPABILITIES_COMPRESS", 2);
}

/**
 * function to return drizzle_version
 *
 * @return string
 */
function drizzle_version()
{
    return "1.0.0";
}

require_once 'libraries/Util.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Index.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/dbi/DBIDrizzle.class.php';
require_once 'libraries/Theme.class.php';

/**
 * Tests for PMA_DBI_Drizzle class
 *
 * @package PhpMyAdmin-test
 */
class PMA_DBI_Drizzle_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['Server']['socket'] = "socket";
        $GLOBALS['cfg']['Server']['port'] = 4080;
        $GLOBALS['cfg']['Server']['connect_type'] = "http";
        $GLOBALS['cfg']['PersistentConnections'] = false;
        $GLOBALS['cfg']['Server']['compress'] = true;
        $GLOBALS['cfg']['Server']['ssl'] = false;
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ActionLinksMode'] = "both";
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'db_structure.php';
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        $this->object = new PMA_DBI_Drizzle();
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
     * Test for realMultiQuery
     *
     * @return void
     *
     * @group medium
     */
    public function testRealMultiQuery()
    {
        //does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->realMultiQuery(null, "select * from PMA")
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
     * Test for moreResults
     *
     * @return void
     *
     * @group medium
     */
    public function testrMoreResults()
    {
        //PHP's 'mysql' extension does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->moreResults(null)
        );
        //PHP's 'mysql' extension does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->nextResult(null)
        );
    }

    /**
     * Test for getClientInfo
     *
     * @return void
     *
     * @group medium
     */
    public function testrGetClientInfo()
    {
        $drizzle_info = 'libdrizzle (Drizzle ' . drizzle_version() . ')';
        $this->assertEquals(
            $drizzle_info,
            $this->object->getClientInfo()
        );
    }

    /**
     * Test for numRows
     *
     * @return void
     *
     * @group medium
     */
    public function testrNumRows()
    {
        $this->assertEquals(
            false,
            $this->object->numRows(true)
        );
    }

    /**
     * Test for storeResult
     *
     * @return void
     *
     * @group medium
     */
    public function testrStoreResult()
    {
        $this->assertEquals(
            false,
            $this->object->storeResult(null)
        );
    }

    /**
     * Test for connect
     *
     * @return void
     *
     * @group medium
     */
    public function testDBIFunction()
    {
        $user = "PMA_user";
        $password = "pma_password";
        $server = null;

        //$server = null;
        $link = $this->object->connect($user, $password);
        $this->assertEquals(
            "DrizzleCon_addUds",
            $link->getType()
        );

        //$server['host'] = 'host'
        $server['host'] = 'host';
        $link = $this->object->connect($user, $password);
        $this->assertEquals(
            "DrizzleCon_addUds",
            $link->getType()
        );

        //selectDb
        $dbname = "dbname";
        $this->assertEquals(
            "selectDb" . $dbname,
            $this->object->selectDb($dbname, $link)
        );

        //realQuery
        $query = "query";
        $options = false;
        $this->assertEquals(
            "query" . $query,
            $this->object->realQuery($query, $link, $options)
        );

        //fetchArray
        $result = $link;
        $this->assertEquals(
            "fetchRow " . PMA_Drizzle::FETCH_BOTH,
            $this->object->fetchArray($result)
        );

        //fetchAssoc
        $result = $link;
        $this->assertEquals(
            "fetchRow " . PMA_Drizzle::FETCH_ASSOC,
            $this->object->fetchAssoc($result)
        );

        //fetchRow
        $result = $link;
        $this->assertEquals(
            "fetchRow " . PMA_Drizzle::FETCH_NUM,
            $this->object->fetchRow($result)
        );

        //dataSeek
        $result = $link;
        $offset = 10;
        $this->assertEquals(
            "seek" . $offset,
            $this->object->dataSeek($result, 10)
        );

        //numRows
        $result = $link;
        $this->assertEquals(
            "numRows",
            $this->object->numRows($result)
        );

        //numFields
        $result = $link;
        $this->assertEquals(
            "numColumns",
            $this->object->numFields($result)
        );

    }
}

/**
 * Mock class for Drizzle
 *
 * @package PhpMyAdmin-test
 */
class Drizzle
{
    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Creates a new connection using unix domain socket
     *
     * @param string  $uds      socket
     * @param string  $user     username
     * @param string  $password password
     * @param string  $db       database name
     * @param integer $options  connection options
     *
     * @return Mock_Con
     */
    public function addUds($uds, $user, $password, $db, $options)
    {
        return new Mock_Con("DrizzleCon_addUds");
    }

    /**
     * Creates a new database connection using TCP
     *
     * @param string  $host     Drizzle host
     * @param integer $port     Drizzle port
     * @param string  $user     username
     * @param string  $password password
     * @param string  $db       database name
     * @param integer $options  connection options
     *
     * @return Mock_Con
     */
    public function addTcp($host, $port, $user, $password, $db, $options)
    {
        return new Mock_Con("DrizzleCon_addTcp");
    }

}

/**
 * Mock class for Mock_Connection
 *
 * @package PhpMyAdmin-test
 */
class Mock_Con
{
    var $type;

    /**
     * Constructor
     *
     * @param string $type type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }


    /**
     * Creates a new database connection using TCP
     *
     * @return Mock_Con
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * selectDb
     *
     * @param string $dbname database name
     *
     * @return string
     */
    public function selectDb($dbname)
    {
        return "selectDb" . $dbname;
    }

    /**
     * query
     *
     * @param string $query       query string
     * @param int    $buffer_mode buffer mode
     *
     * @return string
     */
    public function query($query, $buffer_mode)
    {
        return "query" . $query;
    }

    /**
     * fetchRow
     *
     * @param string $mode fetch mode
     *
     * @return string
     */
    public function fetchRow($mode)
    {
        return "fetchRow " . $mode;
    }

    /**
     * $offset
     *
     * @param int $offset offset
     *
     * @return string
     */
    public function seek($offset)
    {
        return "seek" . $offset;
    }

    /**
     * numRows
     *
     * @return string
     */
    public function numRows()
    {
        return "numRows";
    }

    /**
     * numColumns
     *
     * @return string
     */
    public function numColumns()
    {
        return "numColumns";
    }
}
