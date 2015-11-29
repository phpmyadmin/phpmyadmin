<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Properties class for the export plug-in
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\properties\plugins;

/**
 * Defines possible options and getters and setters for them.
 *
 * @todo    modify descriptions if needed, when the plug-in properties are integrated
 * @package PhpMyAdmin
 */
class ExportPluginProperties extends PluginPropertyItem
{
    /**
     * Whether to force or not
     *
     * @var bool
     */
    private $_forceFile;
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Returns the property item type of either an instance of
     *  - PMA\libraries\properties\options\OptionsPropertyOneItem ( f.e. "bool",
     *  "text", "radio", etc ) or
     *  - PMA\libraries\properties\options\OptionsPropertyGroup   ( "root", "main"
     *  or "subgroup" )
     *  - PMA\libraries\properties\plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public function getItemType()
    {
        return "export";
    }

    /**
     * Gets the force file parameter
     *
     * @return bool
     */
    public function getForceFile()
    {
        return $this->_forceFile;
    }

    /**
     * Sets the force file parameter
     *
     * @param bool $forceFile the force file parameter
     *
     * @return void
     */
    public function setForceFile($forceFile)
    {
        $this->_forceFile = $forceFile;
    }
}
