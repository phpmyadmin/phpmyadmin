<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Sanitize;
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
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'sanitize',
                Sanitize::sanitizeMessage(...),
                ['is_safe' => ['html']],
            ),
        ];
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'get_js_value',
                Sanitize::getJsValue(...),
                ['is_safe' => ['html']],
            ),
        ];
    }
}
