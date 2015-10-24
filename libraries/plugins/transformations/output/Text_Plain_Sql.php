<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain SQL Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
namespace PMA\libraries\plugins\transformations\output;

use PMA\libraries\Response;
use PMA\libraries\plugins\transformations\abs\SQLTransformationsPlugin;

/**
 * Handles the sql transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
class Text_Plain_Sql extends SQLTransformationsPlugin
{
    /**
     * No-arg constructor
     */
    public function __construct()
    {
        if (!empty($GLOBALS['cfg']['CodemirrorEnable'])) {
            $response = Response::getInstance();
            $scripts = $response->getHeader()
                ->getScripts();
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
