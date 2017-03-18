<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\twig\UrlExtension class
 *
 * @package PMA\libraries\twig
 */
namespace PMA\libraries\twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class UrlExtension
 *
 * @package PMA\libraries\twig
 */
class UrlExtension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'URL_getHiddenInputs',
                'PMA\libraries\URL::getHiddenInputs'
            ),
            new Twig_SimpleFunction(
                'URL_getHiddenFields',
                'PMA\libraries\URL::getHiddenFields'
            ),
            new Twig_SimpleFunction(
                'URL_getCommon',
                'PMA\libraries\URL::getCommon'
            ),
            new Twig_SimpleFunction(
                'URL_getCommonRaw',
                'PMA\libraries\URL::getCommonRaw'
            ),
            new Twig_SimpleFunction(
                'URL_getArgSeparator',
                'PMA\libraries\URL::getArgSeparator'
            ),
        );
    }
}
