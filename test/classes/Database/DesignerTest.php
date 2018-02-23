<?php
/**
 * Tests for PhpMyAdmin\Database\Designer
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Tests for PhpMyAdmin\Database\Designer
 *
 * @package PhpMyAdmin-test
 */
class DesignerTest extends TestCase
{
    /**
     * @var Designer
     */
    private $designer;

    /**
     * Setup for test cases
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['PDFPageSizes'] = ['A3', 'A4'];
        $GLOBALS['cfg']['PDFDefaultPageSize'] = 'A4';
        $GLOBALS['cfg']['Schema']['pdf_orientation'] = 'L';
        $GLOBALS['cfg']['Schema']['pdf_paper'] = 'A4';

        $_SESSION = [
            'relation' => [
                '1' => [
                    'PMA_VERSION' => PMA_VERSION,
                    'db' => 'pmadb',
                    'pdf_pages' => 'pdf_pages',
                    'pdfwork' => true
                ]
            ],
            ' PMA_token ' => 'token'
        ];

        $this->designer = new Designer();
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
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with(
                "SELECT `page_nr`, `page_descr` FROM `pmadb`.`pdf_pages`"
                . " WHERE db_name = '" . $db . "' ORDER BY `page_descr`",
                DatabaseInterface::CONNECT_CONTROL,
                DatabaseInterface::QUERY_STORE,
                false
            )
            ->will($this->returnValue('dummyRS'));

        $dbi->expects($this->exactly(3))
            ->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls(
                ['page_nr' => '1', 'page_descr' => 'page1'],
                ['page_nr' => '2', 'page_descr' => 'page2'],
                false
            );

        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for getPageIdsAndNames()
     *
     * @return void
     */
    public function testGetPageIdsAndNames()
    {
        $db = 'db';
        $this->_mockDatabaseInteraction($db);

        $method = new ReflectionMethod(Designer::class, 'getPageIdsAndNames');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->designer, [$db]);

        $this->assertEquals(
            [
                '1' => 'page1',
                '2' => 'page2'
            ],
            $result
        );
    }

    /**
     * Test for getHtmlForEditOrDeletePages()
     *
     * @return void
     */
    public function testGetHtmlForEditOrDeletePages()
    {
        $db = 'db';
        $operation = 'edit';
        $this->_mockDatabaseInteraction($db);

        $result = $this->designer->getHtmlForEditOrDeletePages($db, $operation);
        $this->assertContains(
            '<input type="hidden" name="operation" value="' . $operation . '" />',
            $result
        );
        $this->assertContains(
            '<select name="selected_page" id="selected_page">',
            $result
        );
        $this->assertContains('<option value="0">', $result);
        $this->assertContains('<option value="1">', $result);
        $this->assertContains('page1', $result);
        $this->assertContains('<option value="2">', $result);
        $this->assertContains('page2', $result);
    }

    /**
     * Test for getHtmlForPageSaveAs()
     *
     * @return void
     */
    public function testGetHtmlForPageSaveAs()
    {
        $db = 'db';
        $this->_mockDatabaseInteraction($db);

        $result = $this->designer->getHtmlForPageSaveAs($db);
        $this->assertContains(
            '<input type="hidden" name="operation" value="savePage" />',
            $result
        );
        $this->assertContains(
            '<select name="selected_page" id="selected_page">',
            $result
        );
        $this->assertContains('<option value="0">', $result);
        $this->assertContains('<option value="1">', $result);
        $this->assertContains('page1', $result);
        $this->assertContains('<option value="2">', $result);
        $this->assertContains('page2', $result);

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

    /**
     * Test for getHtmlForSchemaExport()
     *
     * @return void
     */
    public function testGetHtmlForSchemaExport()
    {
        $db = 'db';
        $page = 2;

        $result = $this->designer->getHtmlForSchemaExport($db, $page);
        // export type
        $this->assertContains(
            '<select id="plugins" name="export_type">',
            $result
        );

        // hidden field
        $this->assertContains(
            '<input type="hidden" name="page_number" value="' . $page . '" />',
            $result
        );

        // orientation
        $this->assertContains(
            '<select name="pdf_orientation" id="select_pdf_orientation">',
            $result
        );
        $this->assertContains(
            '<option value="L" selected="selected">Landscape</option>',
            $result
        );
        $this->assertContains('<option value="P">Portrait</option>', $result);

        // paper size
        $this->assertContains(
            '<select name="pdf_paper" id="select_pdf_paper">',
            $result
        );
        $this->assertContains('<option value="A3">A3</option>', $result);
        $this->assertContains(
            '<option value="A4" selected="selected">A4</option>',
            $result
        );
    }
}
