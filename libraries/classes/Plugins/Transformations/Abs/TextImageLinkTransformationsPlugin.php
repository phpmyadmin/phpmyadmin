<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the image link transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageLink
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Sanitize;
use stdClass;

/**
 * Provides common methods for all of the image link transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class TextImageLinkTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays an image and a link; the column contains the filename. The'
            . ' first option is a URL prefix like "https://www.example.com/". The'
            . ' second and third options are the width and the height in pixels.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string        $buffer  text to be transformed
     * @param array         $options transformation options
     * @param stdClass|null $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, array $options = [], ?stdClass $meta = null)
    {
        $cfg = $GLOBALS['cfg'];
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['TextImageLink']);
        $url = $options[0] . $buffer;
        /* Do not allow javascript links */
        if (! Sanitize::checkLink($url, true, true)) {
            return htmlspecialchars($url);
        }
        return '<a href="' . htmlspecialchars($url)
            . '" rel="noopener noreferrer" target="_blank"><img src="' . htmlspecialchars($url)
            . '" border="0" width="' . intval($options[1])
            . '" height="' . intval($options[2]) . '">'
            . htmlspecialchars($buffer) . '</a>';
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Image Link";
    }
}
