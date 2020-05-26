<?php
/**
 * Text link generator
 */

declare(strict_types=1);

namespace PhpMyAdmin\Html\ActionLinksModes;

use function htmlspecialchars;

/**
 * Text link generator
 */
class Text implements ActionLinksModesInterface
{
    /**
     * Generate the link in the right form
     *
     * @param string $name  Name of the generated element
     * @param string $class Class of the generated element, if image mode
     * @param string $text  Text of the generated element
     * @param string $image Image of the generated element, if image mode
     * @param string $value Value of the generated element
     */
    public static function generate(
        string $name,
        string $class,
        string $text,
        string $image,
        string $value = ''
    ): string {
        if (empty($value)) {
            $value = $text;
        }

        return ' <button class="btn btn-link ' . $class . '" type="submit" name="' . $name . '"'
            . ' value="' . htmlspecialchars($value) . '"'
            . ' title="' . htmlspecialchars($text) . '">' . htmlspecialchars($text) . '</button>' . "\n";
    }
}
