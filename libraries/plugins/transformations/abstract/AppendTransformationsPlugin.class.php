<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the append transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Append
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

/**
 * Provides common methods for all of the append transformations plugins.
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Append
 */
abstract class AppendTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Appends text to a string. The only option is the text to be appended'
            . ' (enclosed in single quotes, default empty string).'
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
        if (! isset($options[0]) ||  $options[0] == '') {
            $options[0] = '';
        }
        //just append the option to the original text
        return $buffer . htmlspecialchars($options[0]);
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
        return "Append";
    }
}
?>
