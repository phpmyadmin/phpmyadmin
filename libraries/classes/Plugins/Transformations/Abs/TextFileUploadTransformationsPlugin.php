<?php
/**
 * Abstract class for the text file upload input transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;

use function __;
use function htmlspecialchars;

/**
 * Provides common methods for all of the text file upload
 * input transformations plugins.
 */
abstract class TextFileUploadTransformationsPlugin extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     */
    public static function getInfo(): string
    {
        return __('File upload functionality for TEXT columns. It does not have a textarea for input.');
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
        if (! empty($value)) {
            $html = '<input type="hidden" name="fields_prev' . $columnNameAppendix
                . '" value="' . htmlspecialchars($value) . '">';
            $html .= '<input type="hidden" name="fields' . $columnNameAppendix
                . '" value="' . htmlspecialchars($value) . '">';
        }

        $html .= '<input type="file" name="fields_upload'
            . $columnNameAppendix . '">';

        return $html;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     */
    public static function getName(): string
    {
        return 'Text file upload';
    }
}
