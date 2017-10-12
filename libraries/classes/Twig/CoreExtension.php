<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\CoreExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class CoreExtension
 *
 * @package PhpMyAdmin\Twig
 */
class CoreExtension extends Twig_Extension
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
                'Core_mimeDefaultFunction',
                'PhpMyAdmin\Core::mimeDefaultFunction',
                array('is_safe' => array('html'))
            ),
        );
    }
}
