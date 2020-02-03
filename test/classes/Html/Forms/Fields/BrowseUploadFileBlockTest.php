<?php
/**
 * Test for PhpMyAdmin\Html\Forms\Fields\BrowseUploadFileBlock class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html\Forms\Fields;

use PhpMyAdmin\Html\Forms;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Test for PhpMyAdmin\Html\Forms\Fields\BrowseUploadFileBlock class
 */
class BrowseUploadFileBlockTest extends PmaTestCase
{
    /**
     * Test for \PhpMyAdmin\Html\Forms\Fields\BrowseUploadFileBlock::generate
     *
     * @param string $size Size
     * @param string $unit Unit
     * @param string $res  Result
     *
     * @covers \PhpMyAdmin\Html\Forms\Fields\BrowseUploadFileBlock::generate
     * @dataProvider providerGetBrowseUploadFileBlock
     */
    public function testGetBrowseUploadFileBlock(string $size, string $unit, string $res): void
    {
        $GLOBALS['is_upload'] = false;
        $this->assertEquals(
            Forms\Fields\BrowseUploadFileBlock::generate($size),
            '<label for="input_import_file">' . __('Browse your computer:')
            . '</label>'
            . '<div id="upload_form_status" class="hide"></div>'
            . '<div id="upload_form_status_info" class="hide"></div>'
            . '<input type="file" name="import_file" id="input_import_file">'
            . '(' . __('Max: ') . $res . $unit . ')' . "\n"
            . '<input type="hidden" name="MAX_FILE_SIZE" value="'
            . $size . '">' . "\n"
        );
    }

    /**
     * Data provider for testGetBrowseUploadFileBlock
     *
     * @return array
     */
    public function providerGetBrowseUploadFileBlock(): array
    {
        return [
            [
                '10',
                __('B'),
                '10',
            ],
            [
                '100',
                __('B'),
                '100',
            ],
            [
                '1024',
                __('B'),
                '1,024',
            ],
            [
                '102400',
                __('KiB'),
                '100',
            ],
            [
                '10240000',
                __('MiB'),
                '10',
            ],
            [
                '2147483648',
                __('MiB'),
                '2,048',
            ],
            [
                '21474836480',
                __('GiB'),
                '20',
            ],
        ];
    }
}
