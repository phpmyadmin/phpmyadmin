<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\UrlExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class UrlExtension
 *
 * @package PhpMyAdmin\Twig
 */
class UrlExtension extends Twig_Extension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'Url_getHiddenInputs',
                'PhpMyAdmin\Url::getHiddenInputs',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Url_getHiddenFields',
                'PhpMyAdmin\Url::getHiddenFields',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Url_getCommon',
                'PhpMyAdmin\Url::getCommon',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Url_getCommonRaw',
                'PhpMyAdmin\Url::getCommonRaw',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Url_link',
                'PhpMyAdmin\Core::linkURL'
            ),
        );
    }
}
