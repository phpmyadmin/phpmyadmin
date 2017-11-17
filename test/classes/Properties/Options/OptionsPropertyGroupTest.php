<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Properties\Options\OptionsPropertyGroup class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Properties\Options;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Tests for PhpMyAdmin\Properties\Options\OptionsPropertyGroup class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertyGroupTest extends TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PhpMyAdmin\Properties\Options\OptionsPropertyGroup');
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->stub);
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\OptionsPropertyGroup::addProperty
     *
     * @return void
     */
    public function testAddProperty()
    {
        $properties = new ReflectionProperty('PhpMyAdmin\Properties\Options\OptionsPropertyGroup', '_properties');
        $properties->setAccessible(true);

        $properties->setValue($this->stub, array(1, 2, 3));

        $this->assertNull(
            $this->stub->addProperty(2)
        );

        $this->stub->addProperty('2');

        $this->assertEquals(
            array(1, 2, 3, '2'),
            $properties->getValue($this->stub)
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\OptionsPropertyGroup::removeProperty
     *
     * @return void
     */
    public function testRemoveProperty()
    {
        $properties = new ReflectionProperty('PhpMyAdmin\Properties\Options\OptionsPropertyGroup', '_properties');
        $properties->setAccessible(true);

        $properties->setValue($this->stub, array(1, 2, 'test', 3));
        $this->stub->removeProperty('test');

        $this->assertEquals(
            array(
                0 => 1,
                1 => 2,
                3 => 3
            ),
            $properties->getValue($this->stub)
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\OptionsPropertyGroup::getGroup
     *
     * @return void
     */
    public function testGetGroup()
    {
        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\OptionsPropertyGroup',
            $this->stub->getGroup()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\OptionsPropertyGroup::getProperties
     *
     * @return void
     */
    public function testGetProperties()
    {
        $properties = new ReflectionProperty('PhpMyAdmin\Properties\Options\OptionsPropertyGroup', '_properties');
        $properties->setAccessible(true);
        $properties->setValue($this->stub, array(1, 2, 3));

        $this->assertEquals(
            array(1, 2, 3),
            $this->stub->getProperties()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\OptionsPropertyGroup::getProperties
     *
     * @return void
     */
    public function testGetNrOfProperties()
    {
        $properties = new ReflectionProperty('PhpMyAdmin\Properties\Options\OptionsPropertyGroup', '_properties');
        $properties->setAccessible(true);
        $properties->setValue($this->stub, array(1, 2, 3));

        $this->assertEquals(
            3,
            $this->stub->getNrOfProperties()
        );
    }
}
