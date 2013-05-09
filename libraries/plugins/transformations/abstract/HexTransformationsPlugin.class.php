<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the hex transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Hex
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

/**
 * Provides common methods for all of the hex transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class HexTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays hexadecimal representation of data. Optional first'
            . ' parameter specifies how often space will be added (defaults'
            . ' to 2 nibbles).'
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
        // possibly use a global transform and feed it with special options
        if (!isset($options[0])) {
            $options[0] = 2;
        } else {
            $options[0] = (int)$options[0];
        }

        if ($options[0] < 1) {
            return bin2hex($buffer);
        } else {
            return chunk_split(bin2hex($buffer), $options[0], ' ');
        }
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
        return "Hex";
    }
}
?>