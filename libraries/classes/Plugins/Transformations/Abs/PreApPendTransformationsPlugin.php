<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the prepend/append transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage PreApPend
 */
namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\Plugins\TransformationsPlugin;

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
    public function applyTransformation($buffer, array $options = array(), $meta = '')
    {
        $cfg = $GLOBALS['cfg'];
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['PreApPend']);

        //just prepend and/or append the options to the original text
        return htmlspecialchars($options[0]) . htmlspecialchars($buffer)
            . htmlspecialchars($options[1]);
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
