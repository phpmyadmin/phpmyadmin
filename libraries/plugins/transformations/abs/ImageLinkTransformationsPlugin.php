<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the link transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Link
 */
namespace PMA\libraries\plugins\transformations\abs;

use PMA\libraries\plugins\TransformationsPlugin;

if (!defined('PHPMYADMIN')) {
    exit;
}

/* For PMA_Transformation_globalHtmlReplace */
require_once 'libraries/transformations.lib.php';

/**
 * Provides common methods for all of the link transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class ImageLinkTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a link to download this image.'
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
        // must disable the page loader, see
        // https://wiki.phpmyadmin.net/pma/Page_loader#Bypassing_the_page_loader
        $transform_options = array(
            'string' => '<a class="disableAjax"'
                . ' target="_new" href="transformation_wrapper.php'
                . $options['wrapper_link'] . '" alt="[__BUFFER__]">[BLOB]</a>',
        );

        return PMA_Transformation_globalHtmlReplace(
            $buffer,
            $transform_options
        );
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "ImageLink";
    }
}
