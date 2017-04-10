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
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'URL_getHiddenInputs',
                'PMA\libraries\URL::getHiddenInputs',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'URL_getHiddenFields',
                'PMA\libraries\URL::getHiddenFields',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'URL_getCommon',
                'PMA\libraries\URL::getCommon',
                array('is_safe' => array('html'))
            )
        );
    }
}
