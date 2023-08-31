<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UrlExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'get_hidden_inputs',
                Url::getHiddenInputs(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_hidden_fields',
                Url::getHiddenFields(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_common',
                Url::getCommon(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_common_raw',
                Url::getCommonRaw(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'url',
                Url::getFromRoute(...),
                ['is_safe' => ['html']],
            ),
        ];
    }
}
