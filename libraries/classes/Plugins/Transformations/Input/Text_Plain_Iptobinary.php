<?php
/**
 * Handles the IPv4/IPv6 to binary transformation for text plain
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\Utils\FormatConverter;

use function __;
use function htmlspecialchars;
use function inet_ntop;
use function pack;
use function strlen;

/**
 * Handles the IPv4/IPv6 to binary transformation for text plain
 */
class Text_Plain_Iptobinary extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the plugin
     */
    public static function getInfo(): string
    {
        return __('Converts an Internet network address in (IPv4/IPv6) format to binary');
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed. a binary string containing
     *                                    an IP address, as returned from MySQL's INET6_ATON
     *                                    function
     * @param mixed[]            $options transformation options
     * @param FieldMetadata|null $meta    meta information
     *
     * @return string IP address
     */
    public function applyTransformation(string $buffer, array $options = [], FieldMetadata|null $meta = null): string
    {
        return FormatConverter::ipToBinary($buffer);
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
            $length = strlen($value);
            if ($length == 4 || $length == 16) {
                $ip = @inet_ntop(pack('A' . $length, $value));
                if ($ip !== false) {
                    $val = $ip;
                }
            }

            $html = '<input type="hidden" name="fields_prev' . $columnNameAppendix
                . '" value="' . htmlspecialchars($val) . '">';
        }

        $class = 'transform_IPToBin';

        return $html . '<input type="text" name="fields' . $columnNameAppendix . '"'
            . ' value="' . htmlspecialchars($val) . '"'
            . ' size="40"'
            . ' dir="' . $textDir . '"'
            . ' class="' . $class . '"'
            . ' id="field_' . $fieldIndex . '_3"'
            . ' tabindex="' . $fieldIndex . '">';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the plugin
     */
    public static function getName(): string
    {
        return 'IPv4/IPv6 To Binary';
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
