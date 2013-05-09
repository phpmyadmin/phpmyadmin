<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Properties class for the import plug-in
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the PluginPropertyItem class */
require_once 'PluginPropertyItem.class.php';

/**
 * Defines possible options and getters and setters for them.
 *
 * @package PhpMyAdmin
 */
class ImportPluginProperties extends PluginPropertyItem
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
     * Returns the property item type of either an instance of
     *  - OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public function getItemType()
    {
        return "import";
    }

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
     * @param string $optionsText options text
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
}
?>