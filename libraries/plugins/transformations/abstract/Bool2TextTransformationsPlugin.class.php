<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the Bool2Text transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Bool2Text
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

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
     * @return void
     */
    public function applyTransformation($buffer, $options = array(), $meta = '')
    {
        error_log('apply');
        if (! isset($options[0])) {
            $options[0] = 'T';    // default true  option
        }
        if (! isset($options[1])) {
            $options[1] = 'F';    // default false option
        }

        if ($buffer == '0') {
            return $options[1];   // return false label
        }
        return $options[0];       // or true one if nonzero
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @todo implement
     * @return void
     */
    public function update (SplSubject $subject)
    {
        ;
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
?>
