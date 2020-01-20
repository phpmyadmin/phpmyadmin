<?php
/**
 * HTML Generator for "browse"
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\Forms\Fields;

use PhpMyAdmin\Html\Forms;
use PhpMyAdmin\Util;

/**
 * HTML Generator for "browse"
 *
 * @package PhpMyAdmin
 */
class BrowseUploadFileBlock implements FieldGenerator
{
    /**
     * Prepare the form used to browse anywhere on the local server for a file to
     * import
     *
     * @param string $max_upload_size maximum upload size
     *
     * @return string
     */
    public static function generate(string $max_upload_size): string
    {
        $block_html = '';

        if ($GLOBALS['is_upload'] && ! empty($GLOBALS['cfg']['UploadDir'])) {
            $block_html .= '<label for="radio_import_file">';
        } else {
            $block_html .= '<label for="input_import_file">';
        }

        $block_html .= __('Browse your computer:') . '</label>'
            . '<div id="upload_form_status" class="hide"></div>'
            . '<div id="upload_form_status_info" class="hide"></div>'
            . '<input type="file" name="import_file" id="input_import_file">'
            . Util::getFormattedMaximumUploadSize($max_upload_size) . "\n"
            // some browsers should respect this :)
            . Forms\Fields\MaxFileSize::generate($max_upload_size) . "\n";

        return $block_html;
    }
}
