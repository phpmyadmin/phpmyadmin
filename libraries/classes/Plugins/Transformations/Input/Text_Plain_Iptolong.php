<?php
/**
 * Handles the IPv4/IPv6 to long transformation for text plain
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\Utils\FormatConverter;

use function __;
use function htmlspecialchars;

/**
 * Handles the IPv4/IPv6 to long transformation for text plain
 */
class Text_Plain_Iptolong extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the plugin
     */
    public static function getInfo(): string
    {
        return __('Converts an Internet network address in (IPv4/IPv6) format into a long integer.');
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string        $buffer  text to be transformed. a binary string containing
     *                               an IP address, as returned from MySQL's INET6_ATON
     *                               function
     * @param mixed[]       $options transformation options
     * @param FieldMetadata $meta    meta information
     *
     * @return string IP address
     */
    public function applyTransformation(string $buffer, array $options = [], FieldMetadata|null $meta = null): string
    {
        return (string) FormatConverter::ipToLong($buffer);
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
        $val = '';

        if (! empty($value)) {
            $val = FormatConverter::longToIp($value);

            if ($value !== $val) {
                $html = '<input type="hidden" name="fields_prev' . $columnNameAppendix
                    . '" value="' . htmlspecialchars($val) . '"/>';
            }
        }

        return $html . '<input type="text" name="fields' . $columnNameAppendix . '"'
            . ' value="' . htmlspecialchars($val) . '"'
            . ' size="40"'
            . ' dir="' . $textDir . '"'
            . ' class="transform_IPToLong"'
            . ' id="field_' . $fieldIndex . '_3"'
            . ' tabindex="' . $fieldIndex . '" />';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the plugin
     */
    public static function getName(): string
    {
        return 'IPv4/IPv6 To Long';
    }

    /**
     * Gets the plugin`s MIME type
     */
    public static function getMIMEType(): string
    {
        return 'Text';
    }

    /**
     * Gets the plugin`s MIME subtype
     */
    public static function getMIMESubtype(): string
    {
        return 'Plain';
    }
}
