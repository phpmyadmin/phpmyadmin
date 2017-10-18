<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\PhpFunctionsExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class PhpFunctionsExtension
 *
 * @package PhpMyAdmin\Twig
 */
class PhpFunctionsExtension extends Twig_Extension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('array_search', 'array_search'),
            new Twig_SimpleFunction('bin2hex', 'bin2hex'),
            new Twig_SimpleFunction('md5', 'md5'),
            new Twig_SimpleFunction('preg_replace', 'preg_replace'),
            new Twig_SimpleFunction('strpos', 'strpos'),
            new Twig_SimpleFunction('strstr', 'strstr'),
            new Twig_SimpleFunction('strtotime', 'strtotime'),
        );
    }
}
