<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the Bool2Text transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Bool2Text
 */
namespace PMA\libraries\plugins\transformations\abs;

use PMA\libraries\plugins\TransformationsPlugin;

/**
 * Provides common methods for all of the Bool2Text transformations plugins.
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Bool2Text
 */
abstract class Bool2TextTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Converts Boolean values to text (default \'T\' and \'F\').'
            . ' First option is for TRUE, second for FALSE. Nonzero=true.'
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
        $options = $this->getOptions($options, array('T', 'F'));

        if ($buffer == '0') {
            return $options[1];   // return false label
        }

        return $options[0];       // or true one if nonzero
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Bool2Text";
    }
}
