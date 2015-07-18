<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface for the transformations plugins
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Provides a common interface that will have to be implemented by all of the
 * transformations plugins.
 *
 * @package PhpMyAdmin
 */
interface TransformationsInterface
{
    /**
     * Gets the transformation description
     *
     * @return string
     */
    public static function getInfo();

    /**
     * Gets the specific MIME type
     *
     * @return string
     */
    public static function getMIMEType();

    /**
     * Gets the specific MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype();

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName();
}

