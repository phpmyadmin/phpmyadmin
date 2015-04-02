<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain XML Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

/**
 * Handles the XML transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage XML
 */
class Text_Plain_Xml extends TransformationsPlugin
{
    public function __construct()
    {
        if (! empty($GLOBALS['cfg']['CodemirrorEnable'])) {
            $response = PMA_Response::getInstance();
            $scripts = $response->getHeader()->getScripts();
            $scripts->addFile('codemirror/lib/codemirror.js');
            $scripts->addFile('codemirror/mode/xml/xml.js');
            $scripts->addFile('codemirror/addon/runmode/runmode.js');
            $scripts->addFile('transformations/xml.js');
        }
    }

    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Formats text as XML with syntax highlighting.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string $buffer  text to be transformed
     * @param array  $options transformation options
     * @param string $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, $options = array(), $meta = '')
    {
        return '<code class="xml"><pre>' . "\n"
            . htmlspecialchars($buffer) . "\n"
            . '</pre></code>';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Text";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "Plain";
    }

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "XML";
    }
}
?>
