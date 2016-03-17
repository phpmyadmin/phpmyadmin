<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the prepend/append transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage PreApPend
 */
namespace PMA\libraries\plugins\transformations\abs;

use PMA\libraries\plugins\TransformationsPlugin;

/**
 * Provides common methods for all of the prepend/append transformations plugins.
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage PreApPend
 */
abstract class PreApPendTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Prepends and/or Appends text to a string. First option is text'
            . ' to be prepended, second is appended (enclosed in single'
            . ' quotes, default empty string).'
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
        $options = $this->getOptions($options, array('', ''));

        //just prepend and/or append the options to the original text
        $newtext = htmlspecialchars($options[0]) . $buffer
            . htmlspecialchars($options[1]);

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
        return "PreApPend";
    }
}
