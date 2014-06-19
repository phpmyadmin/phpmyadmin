<?php
/**
 * Tests for libraries/pmd_common.php
 *
 * @package PhpMyAdmin-test
 */

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

        require_once 'libraries/relation.lib.php';
        require_once 'libraries/pmd_common.php';
        require_once 'libraries/database_interface.inc.php';
        require_once 'libraries/Util.class.php';
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
            ->with("
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
}
?>