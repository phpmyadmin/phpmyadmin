<?php
/**
 * Text Plain JSON Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\ResponseRenderer;

use function __;
use function htmlspecialchars;

/**
 * Handles the json transformation for text plain
 */
class Text_Plain_Json extends TransformationsPlugin
{
    public function __construct()
    {
        if (empty($GLOBALS['cfg']['CodemirrorEnable'])) {
            return;
        }

        $response = ResponseRenderer::getInstance();
        $scripts = $response->getHeader()
            ->getScripts();
        $scripts->addFile('vendor/codemirror/lib/codemirror.js');
        $scripts->addFile('vendor/codemirror/mode/javascript/javascript.js');
        $scripts->addFile('vendor/codemirror/addon/runmode/runmode.js');
        $scripts->addFile('transformations/json.js');
    }

    /**
     * Gets the transformation description of the specific plugin
     */
    public static function getInfo(): string
    {
        return __('Formats text as JSON with syntax highlighting.');
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed
     * @param array              $options transformation options
     * @param FieldMetadata|null $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, array $options = [], ?FieldMetadata $meta = null)
    {
        return '<code class="json"><pre>' . "\n"
        . htmlspecialchars($buffer) . "\n"
        . '</pre></code>';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the plugin`s MIME type
     */
    public static function getMIMEType(): string
    {
        return 'Text';
    }

    /**
     * Gets the plugin`s MIME subtype
     */
    public static function getMIMESubtype(): string
    {
        return 'Plain';
    }

    /**
     * Gets the transformation name of the specific plugin
     */
    public static function getName(): string
    {
        return 'JSON';
    }
}
