<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\UrlExtension class
 *
 * @package PhpMyAdmin\Twig
 */
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
        return array(
            new TwigFunction(
                'Url_getHiddenInputs',
                'PhpMyAdmin\Url::getHiddenInputs',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Url_getHiddenFields',
                'PhpMyAdmin\Url::getHiddenFields',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Url_getCommon',
                'PhpMyAdmin\Url::getCommon',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Url_getCommonRaw',
                'PhpMyAdmin\Url::getCommonRaw',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Url_link',
                'PhpMyAdmin\Core::linkURL'
            ),
        );
    }
}
