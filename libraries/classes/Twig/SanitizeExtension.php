<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\SanitizeExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class SanitizeExtension
 *
 * @package PhpMyAdmin\Twig
 */
class SanitizeExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'Sanitize_escapeJsString',
                'PhpMyAdmin\Sanitize::escapeJsString',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Sanitize_jsFormat',
                'PhpMyAdmin\Sanitize::jsFormat',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Sanitize_sanitize',
                'PhpMyAdmin\Sanitize::sanitize',
                ['is_safe' => ['html']]
            ),
        ];
    }
}
