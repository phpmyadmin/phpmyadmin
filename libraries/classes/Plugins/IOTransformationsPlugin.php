<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the I/O transformations plugins
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Plugins\TransformationsPlugin;

/**
 * Provides a common interface that will have to be implemented
 * by all of the Input/Output transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class IOTransformationsPlugin extends TransformationsPlugin
{
    // specifies whether transformation was successful or not
    protected $success = true;
    // to store the error message in case of failed transformations
    protected $error = '';

    /**
     * Returns the html for input field to override default textarea.
     * Note: Return empty string if default textarea is required.
     *
     * @param array  $column               column details
     * @param int    $row_id               row number
     * @param string $column_name_appendix the name attribute
     * @param array  $options              transformation options
     * @param string $value                Current field value
     * @param string $text_dir             text direction
     * @param int    $tabindex             tab index
     * @param int    $tabindex_for_value   offset for the values tabindex
     * @param int    $idindex              id index
     *
     * @return string the html for input field
     */
    public function getInputHtml(
        array $column,
        $row_id,
        $column_name_appendix,
        array $options,
        $value,
        $text_dir,
        $tabindex,
        $tabindex_for_value,
        $idindex
    ) {
        return '';
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return array javascripts to be included
     */
    public function getScripts()
    {
        return array();
    }

    /**
     * Returns the error message
     *
     * @return string error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the success status
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * Resets the object properties
     *
     * @return void
     */
    public function reset()
    {
        $this->success = true;
        $this->error = '';
    }
}
