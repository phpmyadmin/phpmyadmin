<?php
/**
 * Tests for PMA_Export_Relation_Schema class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/schema/Export_Relation_Schema.class.php';

/**
 * Tests for PMA_Export_Relation_Schema class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Export_Relation_Schema_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new PMA_Export_Relation_Schema();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for setPageNumber
     *
     * @return void
     *
     * @group medium
     */
    public function testSetPageNumbere()
    {
        $this->object->setPageNumber(33);
        $this->assertEquals(
            33,
            $this->object->pageNumber
        );
    }

    /**
     * Test for setShowGrid
     *
     * @return void
     *
     * @group medium
     */
    public function testSetShowGrid()
    {
        $this->object->setShowGrid('on');
        $this->assertEquals(
            1,
            $this->object->showGrid
        );
        $this->object->setShowGrid('off');
        $this->assertEquals(
            0,
            $this->object->showGrid
        );
    }

    /**
     * Test for setExportType
     *
     * @return void
     *
     * @group medium
     */
    public function testSetExportType()
    {
        $this->object->setExportType('PMA_ExportType');
        $this->assertEquals(
            'PMA_ExportType',
            $this->object->exportType
        );
    }

    /**
     * Test for setShowColor
     *
     * @return void
     *
     * @group medium
     */
    public function testSetShowColor()
    {
        $this->object->setShowColor('on');
        $this->assertEquals(
            1,
            $this->object->showColor
        );
        $this->object->setShowColor('off');
        $this->assertEquals(
            0,
            $this->object->showColor
        );
    }

    /**
     * Test for setOrientation
     *
     * @return void
     *
     * @group medium
     */
    public function testSetOrientation()
    {
        $this->object->setOrientation('P');
        $this->assertEquals(
            'P',
            $this->object->orientation
        );
        $this->object->setOrientation('A');
        $this->assertEquals(
            'L',
            $this->object->orientation
        );
    }

    /**
     * Test for setTableDimension
     *
     * @return void
     *
     * @group medium
     */
    public function testSetTableDimension()
    {
        $this->object->setTableDimension('on');
        $this->assertEquals(
            1,
            $this->object->tableDimension
        );
        $this->object->setTableDimension('off');
        $this->assertEquals(
            0,
            $this->object->tableDimension
        );
    }

    /**
     * Test for setPaper
     *
     * @return void
     *
     * @group medium
     */
    public function testSetPaper()
    {
        $this->object->setPaper('A5');
        $this->assertEquals(
            'A5',
            $this->object->paper
        );
        $this->object->setPaper('A4');
        $this->assertEquals(
            'A4',
            $this->object->paper
        );
    }

    /**
     * Test for setAllTablesSameWidth
     *
     * @return void
     *
     * @group medium
     */
    public function testSetAllTablesSameWidth()
    {
        $this->object->setAllTablesSameWidth('on');
        $this->assertEquals(
            1,
            $this->object->sameWide
        );
        $this->object->setAllTablesSameWidth('off');
        $this->assertEquals(
            0,
            $this->object->sameWide
        );
    }

    /**
     * Test for setWithDataDictionary
     *
     * @return void
     *
     * @group medium
     */
    public function testSetWithDataDictionary()
    {
        $this->object->setWithDataDictionary('on');
        $this->assertEquals(
            1,
            $this->object->withDoc
        );
        $this->object->setWithDataDictionary('off');
        $this->assertEquals(
            0,
            $this->object->withDoc
        );
    }

    /**
     * Test for setShowKeys
     *
     * @return void
     *
     * @group medium
     */
    public function testSetShowKeys()
    {
        $this->object->setShowKeys('on');
        $this->assertEquals(
            1,
            $this->object->showKeys
        );
        $this->object->setShowKeys('off');
        $this->assertEquals(
            0,
            $this->object->showKeys
        );
    }
}
