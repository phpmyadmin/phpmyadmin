<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup */
class OptionsPropertySubgroupTest extends AbstractTestCase
{
    protected OptionsPropertySubgroup $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new OptionsPropertySubgroup();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    public function testGetItemType(): void
    {
        $this->assertEquals(
            'subgroup',
            $this->object->getItemType(),
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup::getSubgroupHeader
     *     - PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup::setSubgroupHeader
     */
    public function testGetSetSubgroupHeader(): void
    {
        $propertyItem = new OptionsPropertySubgroup();
        $this->object->setSubgroupHeader($propertyItem);

        $this->assertEquals(
            $propertyItem,
            $this->object->getSubgroupHeader(),
        );
    }
}
