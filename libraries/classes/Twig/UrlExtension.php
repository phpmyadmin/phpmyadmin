<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UrlExtension extends AbstractExtension
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
                'get_hidden_inputs',
                'PhpMyAdmin\Url::getHiddenInputs',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_hidden_fields',
                'PhpMyAdmin\Url::getHiddenFields',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_common',
                'PhpMyAdmin\Url::getCommon',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_common_raw',
                'PhpMyAdmin\Url::getCommonRaw',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'url',
                'PhpMyAdmin\Url::getFromRoute',
                ['is_safe' => ['html']]
            ),
        ];
    }
}
