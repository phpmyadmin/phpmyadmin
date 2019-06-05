<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PHPUnit\Framework\TestCase;

/**
 * tests for PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup class
 *
 * @package PhpMyAdmin-test
 */
class OptionsPropertyMainGroupTest extends TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->object = new OptionsPropertyMainGroup();
    }

    /**
     * tearDown for test cases
     *
     * @return void
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
