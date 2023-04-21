<?php
/**
 * Abstract class for the image upload input transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\Url;

use function __;
use function bin2hex;
use function intval;

/**
 * Provides common methods for all of the image upload transformations plugins.
 */
abstract class ImageUploadTransformationsPlugin extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     */
    public static function getInfo(): string
    {
        return __(
            'Image upload functionality which also displays a thumbnail.'
            . ' The options are the width and height of the thumbnail'
            . ' in pixels. Defaults to 100 X 100.',
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed
     * @param mixed[]            $options transformation options
     * @param FieldMetadata|null $meta    meta information
     */
    public function applyTransformation(string $buffer, array $options = [], FieldMetadata|null $meta = null): string
    {
        return $buffer;
    }

    /**
     * Returns the html for input field to override default textarea.
     * Note: Return empty string if default textarea is required.
     *
     * @param mixed[] $column             column details
     * @param int     $rowId              row number
     * @param string  $columnNameAppendix the name attribute
     * @param mixed[] $options            transformation options
     * @param string  $value              Current field value
     * @param string  $textDir            text direction
     * @param int     $fieldIndex         field index
     *
     * @return string the html for input field
     */
    public function getInputHtml(
        array $column,
        int $rowId,
        string $columnNameAppendix,
        array $options,
        string $value,
        string $textDir,
        int $fieldIndex,
    ): string {
        $html = '';
        $src = '';
        if ($value !== '') {
            $html = '<input type="hidden" name="fields_prev' . $columnNameAppendix
                . '" value="' . bin2hex($value) . '">';
            $html .= '<input type="hidden" name="fields' . $columnNameAppendix
                . '" value="' . bin2hex($value) . '">';
            $src = Url::getFromRoute('/transformation/wrapper', $options['wrapper_params']);
        }

        $html .= '<img src="' . $src . '" width="'
            . (isset($options[0]) ? intval($options[0]) : '100') . '" height="'
            . (isset($options[1]) ? intval($options[1]) : '100') . '" alt="'
            . __('Image preview here') . '">';
        $html .= '<br><input type="file" name="fields_upload'
            . $columnNameAppendix . '" accept="image/*" class="image-upload">';

        return $html;
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return string[] javascripts to be included
     */
    public function getScripts(): array
    {
        return ['transformations/image_upload.js'];
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     */
    public static function getName(): string
    {
        return 'Image upload';
    }
}
