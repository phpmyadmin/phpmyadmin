<?php
/**
 * Interface for links (image, text, …)
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\ActionLinksModes;

/**
 * Interface for links (image, text, …)
 */
interface ActionLinksModesInterface
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
    ): string;
}
