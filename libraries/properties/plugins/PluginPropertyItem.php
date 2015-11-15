<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The top-level class of the "Plugin" subtree of the object-oriented
 * properties system (the other subtree is "Options").
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\properties\plugins;

use PMA\libraries\properties\options\groups\OptionsPropertyRootGroup;
use PMA\libraries\properties\PropertyItem;

/**
 * Superclass for
 *  - PMA\libraries\properties\plugins\ExportPluginProperties,
 *  - PMA\libraries\properties\plugins\ImportPluginProperties and
 *  - TransformationsPluginProperties
 *
 * @package PhpMyAdmin
 */
abstract class PluginPropertyItem extends PropertyItem
{
    /**
     * Text
     *
     * @var string
     */
    private $_text;
    /**
     * Extension
     *
     * @var string
     */
    private $_extension;
    /**
     * Options
     *
     * @var OptionsPropertyRootGroup
     */
    private $_options;
    /**
     * Options text
     *
     * @var string
     */
    private $_optionsText;
    /**
     * MIME Type
     *
     * @var string
     */
    private $_mimeType;
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the text
     *
     * @return string
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * Sets the text
     *
     * @param string $text text
     *
     * @return void
     */
    public function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * Gets the extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     * Sets the extension
     *
     * @param string $extension extension
     *
     * @return void
     */
    public function setExtension($extension)
    {
        $this->_extension = $extension;
    }

    /**
     * Gets the options
     *
     * @return OptionsPropertyRootGroup
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Sets the options
     *
     * @param OptionsPropertyRootGroup $options options
     *
     * @return void
     */
    public function setOptions($options)
    {
        $this->_options = $options;
    }

    /**
     * Gets the options text
     *
     * @return string
     */
    public function getOptionsText()
    {
        return $this->_optionsText;
    }

    /**
     * Sets the options text
     *
     * @param string $optionsText optionsText
     *
     * @return void
     */
    public function setOptionsText($optionsText)
    {
        $this->_optionsText = $optionsText;
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
     * Returns the property type ( either "options", or "plugin" ).
     *
     * @return string
     */
    public function getPropertyType()
    {
        return "plugin";
    }
}
