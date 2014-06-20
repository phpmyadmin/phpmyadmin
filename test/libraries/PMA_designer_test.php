<?php
/**
 * Tests for libraries/designer.lib.php
 *
 * @package PhpMyAdmin-test
 */
/*
 * Include to test.
 */
require_once 'libraries/designer.lib.php';

require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/relation.lib.php';

/**
 * Tests for libraries/designer.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_DesginerTest extends PHPUnit_Framework_TestCase
{

    /**
     * Setup for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;

        $_SESSION = array(
            'relation' => array(
                '1' => array('db' => 'pmadb', 'pdf_pages' => 'pdf_pages')
            ),
            ' PMA_token ' => 'token'
        );
    }

    /**
     * Mocks database interaction for tests.
     *
     * @param string $db database name
     *
     * @return void
     */
    private function _mockDatabaseInteraction($db)
    {
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('tryQuery')
            ->with(
                "SELECT `page_nr`, `page_descr` FROM `pmadb`.`pdf_pages`"
                . " WHERE db_name = '" . $db . "' ORDER BY `page_nr`",
                2,
                PMA_DatabaseInterface::QUERY_STORE,
                false
            )
            ->will($this->returnValue('dummyRS'));

        $dbi->expects($this->at(1))
            ->method('fetchAssoc')
            ->with('dummyRS')
            ->will(
                $this->returnValue(array('page_nr' => '1', 'page_descr' => 'page1'))
            );

        $dbi->expects($this->at(2))
            ->method('fetchAssoc')
            ->with('dummyRS')
            ->will(
                $this->returnValue(array('page_nr' => '2', 'page_descr' => 'page2'))
            );

        $dbi->expects($this->at(3))
            ->method('fetchAssoc')
            ->with('dummyRS')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for PMA_getPageIdsAndNames()
     *
     * @return void
     */
    public function testGetPageIdsAndNames()
    {
        $db = 'db';
        $this->_mockDatabaseInteraction($db);

        $result = PMA_getPageIdsAndNames($db);

        $this->assertEquals(
            array(
                '1' => 'page1',
                '2' => 'page2'
            ),
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForEditOrDeletePages()
     *
     * @return void
     */
    public function testGetHtmlForEditOrDeletePages()
    {
        $db = 'db';
        $operation = 'edit';
        $this->_mockDatabaseInteraction($db);

        $result = PMA_getHtmlForEditOrDeletePages($db, $operation);
        $this->assertContains(
            '<input type="hidden" name="operation" value="' . $operation . '" />',
            $result
        );
        $this->assertContains(
            '<select name="selected_page" id="selected_page">',
            $result
        );
        $this->assertContains('<option value="0">', $result);
        $this->assertContains('<option value="1">page1</option>', $result);
        $this->assertContains('<option value="2">page2</option>', $result);
    }

    /**
     * Test for PMA_getHtmlForPageSaveAs()
     *
     * @return void
     */
    public function testGetHtmlForPageSaveAs()
    {
        $db = 'db';
        $this->_mockDatabaseInteraction($db);

        $result = PMA_getHtmlForPageSaveAs($db);
        $this->assertContains(
            '<input type="hidden" name="operation" value="save" />',
            $result
        );
        $this->assertContains(
            '<select name="selected_page" id="selected_page">',
            $result
        );
        $this->assertContains('<option value="0">', $result);
        $this->assertContains('<option value="1">page1</option>', $result);
        $this->assertContains('<option value="2">page2</option>', $result);

        $this->assertContains(
            '<input type="radio" name="save_page" id="save_page_same" value="same"'
            . ' checked="checked" />',
            $result
        );
        $this->assertContains(
            '<input type="radio" name="save_page" id="save_page_new" value="new" />',
            $result
        );
        $this->assertContains(
            '<input type="text" name="selected_value" id="selected_value" />',
            $result
        );
    }
}
?>