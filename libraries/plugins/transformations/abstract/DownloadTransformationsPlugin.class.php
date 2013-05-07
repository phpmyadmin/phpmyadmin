<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the download transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Download
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

/**
 * Provides common methods for all of the download transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class DownloadTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a link to download the binary data of the column. You can'
            . ' use the first option to specify the filename, or use the second'
            . ' option as the name of a column which contains the filename. If'
            . ' you use the second option, you need to set the first option to'
            . ' the empty string.'
        );
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
    public function applyTransformation($buffer, $options = array(), $meta = '')
    {
        global $row, $fields_meta;

        if (isset($options[0]) && !empty($options[0])) {
            $cn = $options[0]; // filename
        } else {
            if (isset($options[1]) && !empty($options[1])) {
                foreach ($fields_meta as $key => $val) {
                    if ($val->name == $options[1]) {
                        $pos = $key;
                        break;
                    }
                }
                if (isset($pos)) {
                    $cn = $row[$pos];
                }
            }
            if (empty($cn)) {
                $cn = 'binary_file.dat';
            }
        }

        return sprintf(
            '<a href="transformation_wrapper.php%s&amp;ct=application'
            . '/octet-stream&amp;cn=%s" title="%s">%s</a>',
            $options['wrapper_link'],
            urlencode($cn),
            htmlspecialchars($cn),
            htmlspecialchars($cn)
        );
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @todo implement
     * @return void
     */
    public function update (SplSubject $subject)
    {
        ;
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Download";
    }
}
?>