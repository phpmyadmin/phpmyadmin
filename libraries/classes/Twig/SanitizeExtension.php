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
use Twig\TwigFilter;

/**
 * Class SanitizeExtension
 *
 * @package PhpMyAdmin\Twig
 */
class SanitizeExtension extends AbstractExtension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'escape_js_string',
                'PhpMyAdmin\Sanitize::escapeJsString',
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'js_format',
                'PhpMyAdmin\Sanitize::jsFormat',
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'sanitize',
                'PhpMyAdmin\Sanitize::sanitize',
                ['is_safe' => ['html']]
            ),
        ];
    }
}
