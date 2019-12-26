<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the long to IPv4 transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage LongToIPv4
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Util;
use stdClass;

/**
 * Provides common methods for all of the long to IPv4 transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class LongToIPv4TransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Converts an (IPv4) Internet network address stored as a BIGINT'
            . ' into a string in Internet standard dotted format.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string        $buffer  text to be transformed
     * @param array         $options transformation options
     * @param stdClass|null $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, array $options = [], ?stdClass $meta = null)
    {
        if (! Util::isInteger($buffer) || $buffer < 0 || $buffer > 4294967295) {
            return htmlspecialchars($buffer);
        }

        return long2ip((int) $buffer);
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Long To IPv4";
    }
}
