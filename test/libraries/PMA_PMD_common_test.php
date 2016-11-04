<?php
/**
 * Tests for libraries/pmd_common.php
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/database_interface.inc.php';


/**
 * Tests for libraries/pmd_common.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_PMD_CommonTest extends PHPUnit_Framework_TestCase
{

    /**
     * Setup for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['controllink'] = 2;
        $_SESSION = array(
            'relation' => array(
                '1' => array(
                    'PMA_VERSION' => PMA_VERSION,
                    'db' => 'pmadb',
                    'pdf_pages' => 'pdf_pages',
                    'pdfwork' => true,
                    'table_coords' => 'table_coords'
                )
            )
        );

        include_once 'libraries/relation.lib.php';
        include_once 'libraries/pmd_common.php';
    }


    /**
     * Test for PMA_getTablePositions()
     *
     * @return void
     */
    public function testGetTablePositions()
    {
        $pg = 1;

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                "
        SELECT CONCAT_WS('.', `db_name`, `table_name`) AS `name`,
            `x` AS `X`,
            `y` AS `Y`,
            1 AS `V`,
            1 AS `H`
        FROM `pmadb`.`table_coords`
        WHERE pdf_page_number = " . $pg,
                'name',
                null,
                2,
                PMA\libraries\DatabaseInterface::QUERY_STORE
            );
        $GLOBALS['dbi'] = $dbi;

        PMA_getTablePositions($pg);
    }

    /**
     * Test for PMA_getPageName()
     *
     * @return void
     */
    public function testGetPageName()
    {
        $pg = 1;
        $pageName = 'pageName';

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                "SELECT `page_descr` FROM `pmadb`.`pdf_pages`"
                . " WHERE `page_nr` = " . $pg,
                null,
                null,
                2,
                PMA\libraries\DatabaseInterface::QUERY_STORE
            )
            ->will($this->returnValue(array($pageName)));
        $GLOBALS['dbi'] = $dbi;

        $result = PMA_getPageName($pg);

        $this->assertEquals($pageName, $result);
    }

    /**
     * Test for PMA_deletePage()
     *
     * @return void
     */
    public function testDeletePage()
    {
        $pg = 1;

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = PMA_deletePage($pg);
        $this->assertEquals(true, $result);
    }

    /**
     * Test for testGetDefaultPage() when there is a default page
     * (a page having the same name as database)
     *
     * @return void
     */
    public function testGetDefaultPage()
    {
        $db = 'db';
        $default_pg = '2';

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                "SELECT `page_nr` FROM `pmadb`.`pdf_pages`"
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                null,
                null,
                2,
                PMA\libraries\DatabaseInterface::QUERY_STORE
            )
            ->will($this->returnValue(array($default_pg)));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = PMA_getDefaultPage($db);
        $this->assertEquals($default_pg, $result);
    }

    /**
     * Test for testGetDefaultPage() when there is no default page
     *
     * @return void
     */
    public function testGetDefaultPageWithNoDefaultPage()
    {
        $db = 'db';

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                "SELECT `page_nr` FROM `pmadb`.`pdf_pages`"
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                null,
                null,
                2,
                PMA\libraries\DatabaseInterface::QUERY_STORE
            )
            ->will($this->returnValue(array()));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = PMA_getDefaultPage($db);
        $this->assertEquals(-1, $result);
    }

    /**
     * Test for testGetLoadingPage() when there is a default page
     *
     * @return void
     */
    public function testGetLoadingPageWithDefaultPage()
    {
        $db = 'db';
        $default_pg = '2';

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                "SELECT `page_nr` FROM `pmadb`.`pdf_pages`"
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                null,
                null,
                2,
                PMA\libraries\DatabaseInterface::QUERY_STORE
            )
            ->will($this->returnValue(array($default_pg)));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = PMA_getLoadingPage($db);
        $this->assertEquals($default_pg, $result);
    }

    /**
     * Test for testGetLoadingPage() when there is no default page
     *
     * @return void
     */
    public function testGetLoadingPageWithNoDefaultPage()
    {
        $db = 'db';
        $first_pg = '1';

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                array(),
                array($first_pg)
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = PMA_getLoadingPage($db);
        $this->assertEquals($first_pg, $result);
    }
}
