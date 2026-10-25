<?php
/**
 * Text Plain SQL Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\SQLTransformationsPlugin;
use PhpMyAdmin\ResponseRenderer;

/**
 * Handles the sql transformation for text plain
 */
class Text_Plain_Sql extends SQLTransformationsPlugin
{
    public function __construct()
    {
        // See #20159, why we need to check for HTTP_USER_AGENT here
        if (empty($GLOBALS['cfg']['CodemirrorEnable']) || ! isset($_SERVER['HTTP_USER_AGENT'])) {
            return;
        }

        $response = ResponseRenderer::getInstance();
        $scripts = $response->getHeader()
            ->getScripts();
        $scripts->addFile('vendor/codemirror/lib/codemirror.js');
        $scripts->addFile('vendor/codemirror/mode/sql/sql.js');
        $scripts->addFile('vendor/codemirror/addon/runmode/runmode.js');
        $scripts->addFile('functions.js');
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return 'Text';
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return 'Plain';
    }
}
