<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

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
                'PhpMyAdmin\Sanitize::sanitizeMessage',
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'get_js_value',
                'PhpMyAdmin\Sanitize::getJsValue',
                ['is_safe' => ['html']]
            ),
        ];
    }
}
