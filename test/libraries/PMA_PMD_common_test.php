<?php
/**
 * Tests for libraries/pmd_common.php
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';

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

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
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
                PMA_DatabaseInterface::QUERY_STORE
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

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('fetchResult')
            ->with(
                "SELECT `page_descr` FROM `pmadb`.`pdf_pages`"
                . " WHERE `page_nr` = " . $pg,
                null,
                null,
                2,
                PMA_DatabaseInterface::QUERY_STORE
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

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('query')
            ->with(
                "DELETE FROM `pmadb`.`table_coords`"
                . " WHERE `pdf_page_number` = " . $pg,
                2,
                PMA_DatabaseInterface::QUERY_STORE,
                false
            )
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('query')
            ->with(
                "DELETE FROM `pmadb`.`pdf_pages` WHERE `page_nr` = " . $pg,
                2,
                PMA_DatabaseInterface::QUERY_STORE,
                false
            )
            ->will($this->returnValue(true));
        $GLOBALS['dbi'] = $dbi;

        $result = PMA_deletePage($pg);
        $this->assertEquals(true, $result);
    }

    /**
     * Test for PMA_getFirstPage()
     *
     * @return void
     */
    public function testGetFirstPage()
    {
        $db = 'db';
        $pg = '1';

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('fetchResult')
            ->with(
                "SELECT MIN(`page_nr`) FROM `pmadb`.`pdf_pages`"
                . " WHERE `db_name` = '" . $db . "'",
                null,
                null,
                2,
                PMA_DatabaseInterface::QUERY_STORE
            )
            ->will($this->returnValue(array($pg)));
        $GLOBALS['dbi'] = $dbi;

        $result = PMA_getFirstPage($db);

        $this->assertEquals($pg, $result);
    }
}
?>