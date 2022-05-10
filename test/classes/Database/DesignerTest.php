<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Version;
use ReflectionMethod;

/**
 * @covers \PhpMyAdmin\Database\Designer
 */
class DesignerTest extends AbstractTestCase
{
    /** @var Designer */
    private $designer;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['PDFPageSizes'] = [
            'A3',
            'A4',
        ];
        $GLOBALS['cfg']['PDFDefaultPageSize'] = 'A4';
        $GLOBALS['cfg']['Schema']['pdf_orientation'] = 'L';
        $GLOBALS['cfg']['Schema']['pdf_paper'] = 'A4';

        $_SESSION = [
            'relation' => [
                1 => [
                    'version' => Version::VERSION,
                    'db' => 'pmadb',
                    'pdf_pages' => 'pdf_pages',
                    'table_coords' => 'table_coords',
                    'pdfwork' => true,
                ],
            ],
            ' PMA_token ' => 'token',
        ];
    }

    /**
     * Mocks database interaction for tests.
     *
     * @param string $db database name
     */
    private function mockDatabaseInteraction(string $db): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->with(
                'SELECT `page_nr`, `page_descr` FROM `pmadb`.`pdf_pages`'
                . " WHERE db_name = '" . $db . "' ORDER BY `page_descr`"
            )
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->exactly(3))
            ->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls(
                [
                    'page_nr' => '1',
                    'page_descr' => 'page1',
                ],
                [
                    'page_nr' => '2',
                    'page_descr' => 'page2',
                ],
                []
            );

        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for getPageIdsAndNames()
     */
    public function testGetPageIdsAndNames(): void
    {
        $db = 'db';
        $this->mockDatabaseInteraction($db);

        $this->designer = new Designer($GLOBALS['dbi'], new Relation($GLOBALS['dbi']), new Template());

        $method = new ReflectionMethod(Designer::class, 'getPageIdsAndNames');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->designer, [$db]);

        $this->assertEquals(
            [
                '1' => 'page1',
                '2' => 'page2',
            ],
            $result
        );
    }

    /**
     * Test for getHtmlForEditOrDeletePages()
     */
    public function testGetHtmlForEditOrDeletePages(): void
    {
        $db = 'db';
        $operation = 'edit';
        $this->mockDatabaseInteraction($db);

        $this->designer = new Designer($GLOBALS['dbi'], new Relation($GLOBALS['dbi']), new Template());

        $result = $this->designer->getHtmlForEditOrDeletePages($db, $operation);
        $this->assertStringContainsString('<input type="hidden" name="operation" value="' . $operation . '">', $result);
        $this->assertStringContainsString('<select name="selected_page" id="selected_page">', $result);
        $this->assertStringContainsString('<option value="0">', $result);
        $this->assertStringContainsString('<option value="1">', $result);
        $this->assertStringContainsString('page1', $result);
        $this->assertStringContainsString('<option value="2">', $result);
        $this->assertStringContainsString('page2', $result);
    }

    /**
     * Test for getHtmlForPageSaveAs()
     */
    public function testGetHtmlForPageSaveAs(): void
    {
        $db = 'db';
        $this->mockDatabaseInteraction($db);

        $this->designer = new Designer($GLOBALS['dbi'], new Relation($GLOBALS['dbi']), new Template());

        $result = $this->designer->getHtmlForPageSaveAs($db);
        $this->assertStringContainsString('<input type="hidden" name="operation" value="savePage">', $result);
        $this->assertStringContainsString('<select name="selected_page" id="selected_page">', $result);
        $this->assertStringContainsString('<option value="0">', $result);
        $this->assertStringContainsString('<option value="1">', $result);
        $this->assertStringContainsString('page1', $result);
        $this->assertStringContainsString('<option value="2">', $result);
        $this->assertStringContainsString('page2', $result);

        $this->assertStringContainsString(
            '<input type="radio" name="save_page" id="savePageSameRadio" value="same" checked>',
            $result
        );
        $this->assertStringContainsString(
            '<input type="radio" name="save_page" id="savePageNewRadio" value="new">',
            $result
        );
        $this->assertStringContainsString('<input type="text" name="selected_value" id="selected_value">', $result);
    }

    /**
     * Test for getHtmlForSchemaExport()
     */
    public function testGetHtmlForSchemaExport(): void
    {
        $db = 'db';
        $page = 2;

        $this->designer = new Designer($GLOBALS['dbi'], new Relation($GLOBALS['dbi']), new Template());

        $result = $this->designer->getHtmlForSchemaExport($db, $page);
        // export type
        $this->assertStringContainsString('<select id="plugins" name="export_type">', $result);

        // hidden field
        $this->assertStringContainsString('<input type="hidden" name="page_number" value="' . $page . '">', $result);

        // orientation
        $this->assertStringContainsString(
            '<select class="form-select" name="pdf_orientation" id="select_pdf_orientation">',
            $result
        );
        $this->assertStringContainsString('<option value="L" selected>Landscape</option>', $result);
        $this->assertStringContainsString('<option value="P">Portrait</option>', $result);

        // paper size
        $this->assertStringContainsString(
            '<select class="form-select" name="pdf_paper" id="select_pdf_paper">',
            $result
        );
        $this->assertStringContainsString('<option value="A3">A3</option>', $result);
        $this->assertStringContainsString('<option value="A4" selected>A4</option>', $result);
    }
}
