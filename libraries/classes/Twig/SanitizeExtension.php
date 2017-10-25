<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\SanitizeExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class SanitizeExtension
 *
 * @package PhpMyAdmin\Twig
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
                'PhpMyAdmin\Sanitize::escapeJsString',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Sanitize_sanitize',
                'PhpMyAdmin\Sanitize::sanitize',
                array('is_safe' => array('html'))
            ),
        );
    }
}
