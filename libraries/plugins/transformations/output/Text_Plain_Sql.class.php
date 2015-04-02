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
require_once 'libraries/plugins/transformations/abstract/'
    . 'SQLTransformationsPlugin.class.php';

/**
 * Handles the sql transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
class Text_Plain_Sql extends SQLTransformationsPlugin
{
    public function __construct()
    {
        if (! empty($GLOBALS['cfg']['CodemirrorEnable'])) {
            $response = PMA_Response::getInstance();
            $scripts = $response->getHeader()->getScripts();
            $scripts->addFile('codemirror/lib/codemirror.js');
            $scripts->addFile('codemirror/mode/sql/sql.js');
            $scripts->addFile('codemirror/addon/runmode/runmode.js');
            $scripts->addFile('function.js');
        }
    }

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
}
?>