<?php
/**
 * HTML Generator for hidden "max file size" field
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\Forms\Fields;

/**
 * HTML Generator for hidden "max file size" field
 *
 * @package PhpMyAdmin
 */
class MaxFileSize implements FieldGenerator
{

    /**
     * Generates a hidden field which should indicate to the browser
     * the maximum size for upload
     *
     * @param integer $max_size the size
     *
     * @return string the INPUT field
     *
     * @access  public
     */
    public static function generate($max_size): string
    {
        return '<input type="hidden" name="MAX_FILE_SIZE" value="' . $max_size . '">';
    }
}
