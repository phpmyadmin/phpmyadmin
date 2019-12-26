<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\UrlExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class UrlExtension
 *
 * @package PhpMyAdmin\Twig
 */
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
        ];
    }
}
