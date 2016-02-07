<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the substring transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Substring
 */
namespace PMA\libraries\plugins\transformations\abs;

use PMA\libraries\plugins\TransformationsPlugin;

/**
 * Provides common methods for all of the substring transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class SubstringTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a part of a string. The first option is the number of'
            . ' characters to skip from the beginning of the string (Default 0).'
            . ' The second option is the number of characters to return (Default:'
            . ' until end of string). The third option is the string to append'
            . ' and/or prepend when truncation occurs (Default: "…").'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string $buffer  text to be transformed
     * @param array  $options transformation options
     * @param string $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, $options = array(), $meta = '')
    {
        // possibly use a global transform and feed it with special options

        // further operations on $buffer using the $options[] array.
        $options = $this->getOptions($options, array(0, 'all', '…'));

        if ($options[1] != 'all') {
            $newtext = mb_substr(
                $buffer,
                $options[0],
                $options[1]
            );
        } else {
            $newtext = mb_substr($buffer, $options[0]);
        }

        $length = mb_strlen($newtext);
        $baselength = mb_strlen($buffer);
        if ($length != $baselength) {
            if ($options[0] != 0) {
                $newtext = $options[2] . $newtext;
            }

            if (($length + $options[0]) != $baselength) {
                $newtext .= $options[2];
            }
        }

        return $newtext;
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Substring";
    }
}
