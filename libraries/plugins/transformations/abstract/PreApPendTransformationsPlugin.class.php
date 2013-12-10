<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the prepend/append transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage PreApPend
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

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
     * @return void
     */
    public function applyTransformation($buffer, $options = array(), $meta = '')
    {
        if (! isset($options[0]) ||  $options[0] == '') {
            $options[0] = '';
        }

        if (! isset($options[1]) ||  $options[1] == '') {
            $options[1] = ''; // default empty strings
        }

        //just prepend and/or append the options to the original text
        $newtext = htmlspecialchars($options[0]) . $buffer
            . htmlspecialchars($options[1]);

        return $newtext;
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
        return "PreApPend";
    }
}
?>
