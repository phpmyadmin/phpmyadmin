<?php
/**
 * Test for PhpMyAdmin\Html\Forms\Fields\MaxFileSize class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html\Forms\Fields;

use PhpMyAdmin\Html\Forms;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Test for PhpMyAdmin\Html\Forms\Fields\MaxFileSize class
 *
 * @package PhpMyAdmin-test
 */
class MaxFileSizeTest extends PmaTestCase
{
    /**
     * Test for MaxFileSize::generate
     *
     * @param int $size Size
     *
     * @return void
     *
     * @dataProvider providerGenerate
     */
    public function testGenerate($size): void
    {
        $this->assertEquals(
            Forms\Fields\MaxFileSize::generate($size),
            '<input type="hidden" name="MAX_FILE_SIZE" value="' . $size . '">'
        );
    }

    /**
     * Data provider for testGenerate
     *
     * @return array
     */
    public function providerGenerate(): array
    {
        return [
            [10],
            ['100'],
            [1024],
            ['1024Mb'],
            [2147483648],
            ['some_string'],
        ];
    }
}
