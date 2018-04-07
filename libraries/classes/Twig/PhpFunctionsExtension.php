<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\PhpFunctionsExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class PhpFunctionsExtension
 *
 * @package PhpMyAdmin\Twig
 */
class PhpFunctionsExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('array_search', 'array_search'),
            new TwigFunction('bin2hex', 'bin2hex'),
            new TwigFunction('htmlentities', 'htmlentities'),
            new TwigFunction('md5', 'md5'),
            new TwigFunction('preg_quote', 'preg_quote'),
            new TwigFunction('preg_replace', 'preg_replace'),
            new TwigFunction('strpos', 'strpos'),
            new TwigFunction('strstr', 'strstr'),
            new TwigFunction('strtotime', 'strtotime'),
        );
    }
}
