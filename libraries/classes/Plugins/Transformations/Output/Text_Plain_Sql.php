<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain SQL Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Response;
use PhpMyAdmin\Plugins\Transformations\Abs\SQLTransformationsPlugin;

/**
 * Handles the sql transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
// @codingStandardsIgnoreLine
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
            $scripts->addFile('vendor/codemirror/lib/codemirror.js');
            $scripts->addFile('vendor/codemirror/mode/sql/sql.js');
            $scripts->addFile('vendor/codemirror/addon/runmode/runmode.js');
            $scripts->addFile('functions.js');
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
