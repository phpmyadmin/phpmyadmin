<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(ExportRelationSchema::class)]
class ExportRelationSchemaTest extends AbstractTestCase
{
    /** @var ExportRelationSchema<null> */
    protected ExportRelationSchema $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $_REQUEST['page_number'] = 33;
        $this->object = new ExportRelationSchema(DatabaseName::from('test_db'), null);
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
     */
    #[Group('medium')]
    public function testSetPageNumber(): void
    {
        $this->object->setPageNumber(33);
        $this->assertEquals(
            33,
            $this->object->getPageNumber(),
        );
    }

    /**
     * Test for setShowColor
     */
    #[Group('medium')]
    public function testSetShowColor(): void
    {
        $this->object->setShowColor(true);
        $this->assertTrue(
            $this->object->isShowColor(),
        );
        $this->object->setShowColor(false);
        $this->assertFalse(
            $this->object->isShowColor(),
        );
    }

    /**
     * Test for setOrientation
     */
    #[Group('medium')]
    public function testSetOrientation(): void
    {
        $this->object->setOrientation('P');
        $this->assertEquals(
            'P',
            $this->object->getOrientation(),
        );
        $this->object->setOrientation('A');
        $this->assertEquals(
            'L',
            $this->object->getOrientation(),
        );
    }

    /**
     * Test for setTableDimension
     */
    #[Group('medium')]
    public function testSetTableDimension(): void
    {
        $this->object->setTableDimension(true);
        $this->assertTrue(
            $this->object->isTableDimension(),
        );
        $this->object->setTableDimension(false);
        $this->assertFalse(
            $this->object->isTableDimension(),
        );
    }

    /**
     * Test for setPaper
     */
    #[Group('medium')]
    public function testSetPaper(): void
    {
        $this->object->setPaper('A5');
        $this->assertEquals(
            'A5',
            $this->object->getPaper(),
        );
        $this->object->setPaper('A4');
        $this->assertEquals(
            'A4',
            $this->object->getPaper(),
        );
    }

    /**
     * Test for setAllTablesSameWidth
     */
    #[Group('medium')]
    public function testSetAllTablesSameWidth(): void
    {
        $this->object->setAllTablesSameWidth(true);
        $this->assertTrue(
            $this->object->isAllTableSameWidth(),
        );
        $this->object->setAllTablesSameWidth(false);
        $this->assertFalse(
            $this->object->isAllTableSameWidth(),
        );
    }

    /**
     * Test for setShowKeys
     */
    #[Group('medium')]
    public function testSetShowKeys(): void
    {
        $this->object->setShowKeys(true);
        $this->assertTrue(
            $this->object->isShowKeys(),
        );
        $this->object->setShowKeys(false);
        $this->assertFalse(
            $this->object->isShowKeys(),
        );
    }
}
