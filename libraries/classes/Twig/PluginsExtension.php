<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\PluginsExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class PluginsExtension
 *
 * @package PhpMyAdmin\Twig
 */
class PluginsExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction(
                'Plugins_checkboxCheck',
                'PhpMyAdmin\Plugins::checkboxCheck',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Plugins_getChoice',
                'PhpMyAdmin\Plugins::getChoice',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Plugins_getDefault',
                'PhpMyAdmin\Plugins::getDefault',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Plugins_getOptions',
                'PhpMyAdmin\Plugins::getOptions',
                array('is_safe' => array('html'))
            ),
        );
    }
}
