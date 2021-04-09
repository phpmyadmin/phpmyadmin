<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\Eps\EpsRelationSchema;
use PhpMyAdmin\Tests\AbstractTestCase;

class EpsRelationSchemaTest extends AbstractTestCase
{
    /** @var EpsRelationSchema */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['config']->enableBc();
        $_REQUEST['page_number'] = 33;
        $_REQUEST['eps_show_color'] = true;
        $_REQUEST['eps_show_keys'] = true;
        $_REQUEST['eps_orientation'] = 'orientation';
        $_REQUEST['eps_show_table_dimension'] = true;
        $_REQUEST['eps_all_tables_same_width'] = true;
        $_REQUEST['t_v'] = [1 => '1'];
        $_REQUEST['t_h'] = [1 => '1'];
        $_REQUEST['t_x'] = [1 => '10'];
        $_REQUEST['t_y'] = [1 => '10'];
        $_POST['t_db'] = ['test_db'];
        $_POST['t_tbl'] = ['test_table'];

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $this->object = new EpsRelationSchema('test_db');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
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
        $this->assertEquals(33, $this->object->getPageNumber());
        $this->assertTrue($this->object->isShowColor());
        $this->assertTrue($this->object->isShowKeys());
        $this->assertTrue($this->object->isTableDimension());
        $this->assertTrue($this->object->isAllTableSameWidth());
        $this->assertEquals('L', $this->object->getOrientation());
    }

    /**
     * Test for setPageNumber
     *
     * @group medium
     */
    public function testSetPageNumber(): void
    {
        $this->object->setPageNumber(33);
        $this->assertEquals(33, $this->object->getPageNumber());
    }
}
