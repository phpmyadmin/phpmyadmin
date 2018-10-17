<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Formatted Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Formatted
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\FormattedTransformationsPlugin;

/**
 * Handles the formatted transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Formatted
 */
// @codingStandardsIgnoreLine
class Text_Plain_Formatted extends FormattedTransformationsPlugin
{
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
