<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain SQL Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the sql transformations interface */
require_once "libraries/plugins/abstract/SQLTransformationsPlugin.class.php";

/**
 * Handles the sql transformation for text plain
 *
 * @package PhpMyAdmin
 */
class TransformationTextPlainSQL
    extends SQLTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public function getInfo()
    {
        return __(
            'Formats text as SQL query with syntax highlighting.'
        );
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public function getMIMEType()
    {
        return "Text";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public function getMIMESubtype()
    {
        return "Plain";
    }
}
?>