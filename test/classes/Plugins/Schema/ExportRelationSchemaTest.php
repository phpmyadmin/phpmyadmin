<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Tests\AbstractTestCase;

class ExportRelationSchemaTest extends AbstractTestCase
{
    /** @var ExportRelationSchema */
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
        parent::defineVersionConstants();
        $_REQUEST['page_number'] = 33;
        $this->object = new ExportRelationSchema('information_schema', null);
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
     * Test for setPageNumber
     *
     * @group medium
     */
    public function testSetPageNumber(): void
    {
        $this->object->setPageNumber(33);
        $this->assertEquals(
            33,
            $this->object->getPageNumber()
        );
    }

    /**
     * Test for setShowColor
     *
     * @group medium
     */
    public function testSetShowColor(): void
    {
        $this->object->setShowColor(true);
        $this->assertTrue(
            $this->object->isShowColor()
        );
        $this->object->setShowColor(false);
        $this->assertFalse(
            $this->object->isShowColor()
        );
    }

    /**
     * Test for setOrientation
     *
     * @group medium
     */
    public function testSetOrientation(): void
    {
        $this->object->setOrientation('P');
        $this->assertEquals(
            'P',
            $this->object->getOrientation()
        );
        $this->object->setOrientation('A');
        $this->assertEquals(
            'L',
            $this->object->getOrientation()
        );
    }

    /**
     * Test for setTableDimension
     *
     * @group medium
     */
    public function testSetTableDimension(): void
    {
        $this->object->setTableDimension(true);
        $this->assertTrue(
            $this->object->isTableDimension()
        );
        $this->object->setTableDimension(false);
        $this->assertFalse(
            $this->object->isTableDimension()
        );
    }

    /**
     * Test for setPaper
     *
     * @group medium
     */
    public function testSetPaper(): void
    {
        $this->object->setPaper('A5');
        $this->assertEquals(
            'A5',
            $this->object->getPaper()
        );
        $this->object->setPaper('A4');
        $this->assertEquals(
            'A4',
            $this->object->getPaper()
        );
    }

    /**
     * Test for setAllTablesSameWidth
     *
     * @group medium
     */
    public function testSetAllTablesSameWidth(): void
    {
        $this->object->setAllTablesSameWidth(true);
        $this->assertTrue(
            $this->object->isAllTableSameWidth()
        );
        $this->object->setAllTablesSameWidth(false);
        $this->assertFalse(
            $this->object->isAllTableSameWidth()
        );
    }

    /**
     * Test for setShowKeys
     *
     * @group medium
     */
    public function testSetShowKeys(): void
    {
        $this->object->setShowKeys(true);
        $this->assertTrue(
            $this->object->isShowKeys()
        );
        $this->object->setShowKeys(false);
        $this->assertFalse(
            $this->object->isShowKeys()
        );
    }
}
