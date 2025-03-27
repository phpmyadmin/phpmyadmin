<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\Pdf\PdfRelationSchema;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Plugins\Schema\Pdf\PdfRelationSchema
 */
class PdfRelationSchemaTest extends AbstractTestCase
{
    /** @var PdfRelationSchema */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $_REQUEST['page_number'] = 33;
        $_REQUEST['pdf_show_grid'] = true;
        $_REQUEST['pdf_show_color'] = true;
        $_REQUEST['pdf_show_keys'] = true;
        $_REQUEST['pdf_orientation'] = 'orientation';
        $_REQUEST['pdf_show_table_dimension'] = true;
        $_REQUEST['pdf_all_tables_same_width'] = true;
        $_REQUEST['pdf_paper'] = 'paper';
        $_REQUEST['pdf_table_order'] = '';
        $_REQUEST['t_v'] = [1 => '1'];
        $_REQUEST['t_h'] = [1 => '1'];
        $_REQUEST['t_x'] = [1 => '10'];
        $_REQUEST['t_y'] = [1 => '10'];
        $_POST['t_db'] = ['test_db'];
        $_POST['t_tbl'] = ['test_table'];

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $this->object = new PdfRelationSchema('test_db');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for construct
     *
     * @group large
     */
    public function testConstructor(): void
    {
        self::assertSame(33, $this->object->getPageNumber());
        self::assertTrue($this->object->isShowGrid());
        self::assertTrue($this->object->isShowColor());
        self::assertTrue($this->object->isShowKeys());
        self::assertTrue($this->object->isTableDimension());
        self::assertTrue($this->object->isAllTableSameWidth());
        self::assertSame('L', $this->object->getOrientation());
        self::assertSame('paper', $this->object->getPaper());
    }
}
