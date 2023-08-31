<?php
/**
 * Text Plain Regex Validation Input Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\Plugins\Transformations\Abs\RegexValidationTransformationsPlugin;

/**
 * Handles the input regex validation transformation for text plain.
 * Has one option: the regular expression
 */
class Text_Plain_RegexValidation extends RegexValidationTransformationsPlugin
{
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
}
