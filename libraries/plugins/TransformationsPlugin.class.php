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

/* This class extends the PluginObserver class */
require_once "PluginObserver.class.php";

/**
 * Provides a common interface that will have to implemented by all of the
 * transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class TransformationsPlugin extends PluginObserver
{
    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string $buffer  text to be transformed
     * @param array  $options transformation options
     * @param string $meta    meta information
     *
     * @return void
     */
    abstract public function applyTransformation($buffer, $options, $meta);


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the transformation description
     *
     * @return string
     */
    abstract public function getInfo();

    /**
     * Gets the specific MIME type
     *
     * @return string
     */
    abstract public function getMimeType();

    /**
     * Gets the specific MIME subtype
     *
     * @return string
     */
    abstract public function getMimeSubType();

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    abstract public function getName();
}
?>