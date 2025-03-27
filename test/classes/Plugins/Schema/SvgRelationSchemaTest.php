<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\Svg\SvgRelationSchema;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Plugins\Schema\Svg\SvgRelationSchema
 * @requires extension xmlwriter
 */
class SvgRelationSchemaTest extends AbstractTestCase
{
    /** @var SvgRelationSchema */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $_REQUEST['page_number'] = 33;
        $_REQUEST['svg_show_color'] = true;
        $_REQUEST['svg_show_keys'] = true;
        $_REQUEST['svg_show_table_dimension'] = true;
        $_REQUEST['svg_all_tables_same_width'] = true;
        $_REQUEST['t_v'] = [1 => '1'];
        $_REQUEST['t_h'] = [1 => '1'];
        $_REQUEST['t_x'] = [1 => '10'];
        $_REQUEST['t_y'] = [1 => '10'];
        $_POST['t_db'] = ['test_db'];
        $_POST['t_tbl'] = ['test_table'];

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'en';
        $GLOBALS['PMA_PHP_SELF'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $this->object = new SvgRelationSchema('test_db');
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
     * @group medium
     */
    public function testConstructor(): void
    {
        self::assertSame(33, $this->object->getPageNumber());
        self::assertTrue($this->object->isShowColor());
        self::assertTrue($this->object->isShowKeys());
        self::assertTrue($this->object->isTableDimension());
        self::assertTrue($this->object->isAllTableSameWidth());
    }
}
