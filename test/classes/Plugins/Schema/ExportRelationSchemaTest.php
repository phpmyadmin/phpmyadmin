<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Plugins\Schema\ExportRelationSchema
 */
class ExportRelationSchemaTest extends AbstractTestCase
{
    /** @var ExportRelationSchema */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $_REQUEST['page_number'] = 33;
        $this->object = new ExportRelationSchema('information_schema', null);
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
     * Test for setPageNumber
     *
     * @group medium
     */
    public function testSetPageNumber(): void
    {
        $this->object->setPageNumber(33);
        self::assertSame(33, $this->object->getPageNumber());
    }

    /**
     * Test for setShowColor
     *
     * @group medium
     */
    public function testSetShowColor(): void
    {
        $this->object->setShowColor(true);
        self::assertTrue($this->object->isShowColor());
        $this->object->setShowColor(false);
        self::assertFalse($this->object->isShowColor());
    }

    /**
     * Test for setOrientation
     *
     * @group medium
     */
    public function testSetOrientation(): void
    {
        $this->object->setOrientation('P');
        self::assertSame('P', $this->object->getOrientation());
        $this->object->setOrientation('A');
        self::assertSame('L', $this->object->getOrientation());
    }

    /**
     * Test for setTableDimension
     *
     * @group medium
     */
    public function testSetTableDimension(): void
    {
        $this->object->setTableDimension(true);
        self::assertTrue($this->object->isTableDimension());
        $this->object->setTableDimension(false);
        self::assertFalse($this->object->isTableDimension());
    }

    /**
     * Test for setPaper
     *
     * @group medium
     */
    public function testSetPaper(): void
    {
        $this->object->setPaper('A5');
        self::assertSame('A5', $this->object->getPaper());
        $this->object->setPaper('A4');
        self::assertSame('A4', $this->object->getPaper());
    }

    /**
     * Test for setAllTablesSameWidth
     *
     * @group medium
     */
    public function testSetAllTablesSameWidth(): void
    {
        $this->object->setAllTablesSameWidth(true);
        self::assertTrue($this->object->isAllTableSameWidth());
        $this->object->setAllTablesSameWidth(false);
        self::assertFalse($this->object->isAllTableSameWidth());
    }

    /**
     * Test for setShowKeys
     *
     * @group medium
     */
    public function testSetShowKeys(): void
    {
        $this->object->setShowKeys(true);
        self::assertTrue($this->object->isShowKeys());
        $this->object->setShowKeys(false);
        self::assertFalse($this->object->isShowKeys());
    }
}
