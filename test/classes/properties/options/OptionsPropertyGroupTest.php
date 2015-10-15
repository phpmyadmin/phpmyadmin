<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\properties\options\OptionsPropertyGroup class
 *
 * @package PhpMyAdmin-test
 */

/**
 * Tests for PMA\libraries\properties\options\OptionsPropertyGroup class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertyGroupTest extends PHPUnit_Framework_TestCase
{
    protected $stub;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setup()
    {
        $this->stub = $this->getMockForAbstractClass('PMA\libraries\properties\options\OptionsPropertyGroup');
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
     * Test for PMA\libraries\properties\options\OptionsPropertyGroup::addProperty
     *
     * @return void
     */
    public function testAddProperty()
    {
        $properties = new \ReflectionProperty('PMA\libraries\properties\options\OptionsPropertyGroup', '_properties');
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
     * Test for PMA\libraries\properties\options\OptionsPropertyGroup::removeProperty
     *
     * @return void
     */
    public function testRemoveProperty()
    {
        $properties = new \ReflectionProperty('PMA\libraries\properties\options\OptionsPropertyGroup', '_properties');
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
     * Test for PMA\libraries\properties\options\OptionsPropertyGroup::getGroup
     *
     * @return void
     */
    public function testGetGroup()
    {
        $this->assertInstanceOf(
            'PMA\libraries\properties\options\OptionsPropertyGroup',
            $this->stub->getGroup()
        );
    }

    /**
     * Test for PMA\libraries\properties\options\OptionsPropertyGroup::getProperties
     *
     * @return void
     */
    public function testGetProperties()
    {
        $properties = new \ReflectionProperty('PMA\libraries\properties\options\OptionsPropertyGroup', '_properties');
        $properties->setAccessible(true);
        $properties->setValue($this->stub, array(1, 2, 3));

        $this->assertEquals(
            array(1, 2, 3),
            $this->stub->getProperties()
        );
    }

    /**
     * Test for PMA\libraries\properties\options\OptionsPropertyGroup::getProperties
     *
     * @return void
     */
    public function testGetNrOfProperties()
    {
        $properties = new \ReflectionProperty('PMA\libraries\properties\options\OptionsPropertyGroup', '_properties');
        $properties->setAccessible(true);
        $properties->setValue($this->stub, array(1, 2, 3));

        $this->assertEquals(
            3,
            $this->stub->getNrOfProperties()
        );
    }
}
