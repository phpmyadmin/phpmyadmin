<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\PluginsExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class PluginsExtension
 *
 * @package PhpMyAdmin\Twig
 */
class PluginsExtension extends Twig_Extension
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
                'Plugins_getChoice',
                'PhpMyAdmin\Plugins::getChoice',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Plugins_getOptions',
                'PhpMyAdmin\Plugins::getOptions',
                array('is_safe' => array('html'))
            ),
        );
    }
}
