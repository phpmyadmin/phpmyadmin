<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The top-level class of the "Plugin" subtree of the object-oriented
 * properties system (the other subtree is "Options").
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the PropertyItem class */
require_once "../PropertyItem.class.php";

/**
 * Superclass for
 *  - ExportPluginProperties,
 *  - ImportPluginProperties and
 *  - TransformationsPluginProperties
 *
 * @package PhpMyAdmin
 */
abstract class OptionsPropertyItem extends PropertyItem
{
    /**
     * Returns the property type ( either "options", or "plugin" ).
     *
     * @return string
     */
    public abstract function getPropertyType()
    {
        return "plugin";
    }
}
?>