<?php
/**
 * Abstract class for the hex transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;

use function __;
use function bin2hex;
use function chunk_split;
use function intval;

/**
 * Provides common methods for all of the hex transformations plugins.
 */
abstract class HexTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     */
    public static function getInfo(): string
    {
        return __(
            'Displays hexadecimal representation of data. Optional first'
            . ' parameter specifies how often space will be added (defaults'
            . ' to 2 nibbles).',
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
        // possibly use a global transform and feed it with special options
        $cfg = $GLOBALS['cfg'];
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['Hex']);
        $options[0] = intval($options[0]);

        if ($options[0] < 1) {
            return bin2hex($buffer);
        }

        return chunk_split(bin2hex($buffer), $options[0], ' ');
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     */
    public static function getName(): string
    {
        return 'Hex';
    }
}
