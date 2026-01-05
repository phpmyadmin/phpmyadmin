<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(Designer::class)]
class DesignerTest extends AbstractTestCase
{
    private Designer $designer;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $config = Config::getInstance();
        $config->settings['ServerDefault'] = 1;
        $config->settings['PDFPageSizes'] = ['A3', 'A4'];
        $config->settings['PDFDefaultPageSize'] = 'A4';
        $config->settings['Schema']['pdf_orientation'] = 'L';
        $config->settings['Schema']['pdf_paper'] = 'A4';

        $_SESSION = [' PMA_token ' => 'token'];
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::TABLE_COORDS => 'table_coords',
            RelationParameters::PDF_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
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

        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->with(
                'SELECT `page_nr`, `page_descr` FROM `pmadb`.`pdf_pages`'
                . " WHERE db_name = '" . $db . "' ORDER BY `page_descr`",
            )
            ->willReturn($resultStub);

        $resultStub->expects(self::exactly(1))
            ->method('fetchAllKeyPair')
            ->willReturn([
                '1' => 'page1',
                '2' => 'page2',
            ]);

        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
    }

    /**
     * Test for getPageIdsAndNames()
     */
    public function testGetPageIdsAndNames(): void
    {
        $db = 'db';
        $this->mockDatabaseInteraction($db);

        $dbi = DatabaseInterface::getInstance();
        $this->designer = new Designer($dbi, new Relation($dbi), new Template(), new Config());

        $method = new ReflectionMethod(Designer::class, 'getPageIdsAndNames');
        $result = $method->invokeArgs($this->designer, [$db]);

        self::assertSame(
            ['1' => 'page1', '2' => 'page2'],
            $result,
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

        $dbi = DatabaseInterface::getInstance();
        $this->designer = new Designer($dbi, new Relation($dbi), new Template(), new Config());

        $result = $this->designer->getHtmlForEditOrDeletePages($db, $operation);
        self::assertStringContainsString('<input type="hidden" name="operation" value="' . $operation . '">', $result);
        self::assertStringContainsString('<select name="selected_page" id="selected_page">', $result);
        self::assertStringContainsString('<option value="0">', $result);
        self::assertStringContainsString('<option value="1">', $result);
        self::assertStringContainsString('page1', $result);
        self::assertStringContainsString('<option value="2">', $result);
        self::assertStringContainsString('page2', $result);
    }

    /**
     * Test for getHtmlForPageSaveAs()
     */
    public function testGetHtmlForPageSaveAs(): void
    {
        $db = 'db';
        $this->mockDatabaseInteraction($db);

        $dbi = DatabaseInterface::getInstance();
        $this->designer = new Designer($dbi, new Relation($dbi), new Template(), new Config());

        $result = $this->designer->getHtmlForPageSaveAs($db);
        self::assertStringContainsString('<input type="hidden" name="operation" value="savePage">', $result);
        self::assertStringContainsString('<select name="selected_page" id="selected_page">', $result);
        self::assertStringContainsString('<option value="0">', $result);
        self::assertStringContainsString('<option value="1">', $result);
        self::assertStringContainsString('page1', $result);
        self::assertStringContainsString('<option value="2">', $result);
        self::assertStringContainsString('page2', $result);

        self::assertStringContainsString(
            '<input type="radio" name="save_page" id="savePageSameRadio" value="same" checked>',
            $result,
        );
        self::assertStringContainsString(
            '<input type="radio" name="save_page" id="savePageNewRadio" value="new">',
            $result,
        );
        self::assertStringContainsString('<input type="text" name="selected_value" id="selected_value">', $result);
    }

    /**
     * Test for getHtmlForSchemaExport()
     */
    public function testGetHtmlForSchemaExport(): void
    {
        $db = 'db';
        $page = 2;

        $dbi = DatabaseInterface::getInstance();
        $this->designer = new Designer($dbi, new Relation($dbi), new Template(), new Config());

        $result = $this->designer->getHtmlForSchemaExport($db, $page, null, null);
        // export type
        self::assertStringContainsString('<select class="form-select" id="plugins" name="export_type">', $result);

        // hidden field
        self::assertStringContainsString('<input type="hidden" name="page_number" value="' . $page . '">', $result);

        // orientation
        self::assertStringContainsString(
            '<select class="form-select" name="pdf_orientation" id="select_pdf_orientation">',
            $result,
        );
        self::assertStringContainsString('<option value="L" selected>Landscape</option>', $result);
        self::assertStringContainsString('<option value="P">Portrait</option>', $result);

        // paper size
        self::assertStringContainsString(
            '<select class="form-select" name="pdf_paper" id="select_pdf_paper">',
            $result,
        );
        self::assertStringContainsString('<option value="A3">A3</option>', $result);
        self::assertStringContainsString('<option value="A4" selected>A4</option>', $result);
    }
}
