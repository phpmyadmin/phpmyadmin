<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Properties class for the transformations plug-in
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the PluginPropertyItem class */
require_once "PluginPropertyItem.class.php";

/**
 * Defines possible options and getters and setters for them.
 *
 * @todo modify descriptions if needed, when the plug-in properties are integrated
 * @package PhpMyAdmin
 */
class TransformationsPluginProperties extends PluginPropertyItem
{
    /**
     * Information about the transformations plug-in
     *
     * @var string
     */
    private $_info;

    /**
     * MIME Type
     *
     * @var string
     */
    private $_mimeType;


    /**
     * MIME Subtype
     *
     * @var string
     */
    private $_mimeSubype;

    /**
     * Name of the transformation
     *
     * @var string
     */
    private $_transformationName;


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Returns the property item type of either an instance of
     *  - OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public function getItemType()
    {
        return "transformations";
    }

    /**
     * Gets information about the transformations plug-in
     *
     * @return string
     */
    public function getInfo()
    {
        return $this->_info;
    }

    /**
     * Sets information about the transformations plug-in
     *
     * @param string $info information about the transformations plug-in
     *
     * @return void
     */
    public function setInfo($info)
    {
        $this->_info = $info;
    }

    /**
     * Gets the MIME type
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->_mimeType;
    }

    /**
     * Sets the MIME type
     *
     * @param string $mimeType MIME type
     *
     * @return void
     */
    public function setMimeType($mimeType)
    {
        $this->_mimeType = $mimeType;
    }

    /**
     * Gets the MIME subtype
     *
     * @return string
     */
    public function getMimeSubtype()
    {
        return $this->_mimeSubype;
    }

    /**
     * Sets the MIME subtype
     *
     * @param string $mimeSubtype MIME subtype
     *
     * @return void
     */
    public function setMimeSubtype($mimeSubtype)
    {
        $this->_mimeSubype = $mimeSubtype;
    }

    /**
     * Gets the transformation name
     *
     * @return string
     */
    public function getTransformationName()
    {
        return $this->_transformationName;
    }

    /**
     * Sets the transformation name
     *
     * @param string $transformationName transformation name
     *
     * @return void
     */
    public function setTransformationName($transformationName)
    {
        $this->_transformationName = $transformationName;
    }
}
?>