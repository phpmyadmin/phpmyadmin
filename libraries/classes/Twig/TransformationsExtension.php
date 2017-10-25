<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\TransformationsExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class TransformationsExtension
 *
 * @package PhpMyAdmin\Twig
 */
class TransformationsExtension extends Twig_Extension
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
                'Transformations_getDescription',
                'PhpMyAdmin\Transformations::getDescription'
            ),
            new Twig_SimpleFunction(
                'Transformations_getName',
                'PhpMyAdmin\Transformations::getName'
            ),
        );
    }
}
