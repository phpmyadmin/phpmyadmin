<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\twig\SanitizeExtension class
 *
 * @package PMA\libraries\twig
 */
namespace PMA\libraries\twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class SanitizeExtension
 *
 * @package PMA\libraries\twig
 */
class SanitizeExtension extends Twig_Extension
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
                'Sanitize_escapeJsString',
                'PMA\libraries\Sanitize::escapeJsString',
                array('is_safe' => array('html'))
            ),
        );
    }
}
