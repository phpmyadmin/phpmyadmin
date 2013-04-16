<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the transformations plugins
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* It extends the PluginObserver abstract class */
require_once 'PluginObserver.class.php';
/* It also implements the transformations interface */
require_once 'TransformationsInterface.int.php';

/**
 * Extends PluginObserver and provides a common interface that will have to
 * be implemented by all of the transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class TransformationsPlugin extends PluginObserver
    implements TransformationsInterface
{
    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param array $options transformation options
     *
     * @return void
     */
    public function applyTransformationNoWrap($options = array())
    {
        ;
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
    abstract public function applyTransformation(
        $buffer, $options = array(), $meta = ''
    );
}
?>
