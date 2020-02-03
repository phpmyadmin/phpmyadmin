<?php
/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PHPUnit\Framework\TestCase;

/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup class
 */
class OptionsPropertyMainGroupTest extends TestCase
{
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        $this->object = new OptionsPropertyMainGroup();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup::getItemType
     *
     * @return void
     */
    public function testGetItemType()
    {
        $this->assertEquals(
            'main',
            $this->object->getItemType()
        );
    }
}
