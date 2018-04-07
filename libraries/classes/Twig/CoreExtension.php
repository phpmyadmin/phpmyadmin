<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\CoreExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class CoreExtension
 *
 * @package PhpMyAdmin\Twig
 */
class CoreExtension extends AbstractExtension
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
                'Core_mimeDefaultFunction',
                'PhpMyAdmin\Core::mimeDefaultFunction',
                array('is_safe' => array('html'))
            ),
        );
    }
}
