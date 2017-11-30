<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\StorageEngineExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class StorageEngineExtension
 *
 * @package PhpMyAdmin\Twig
 */
class StorageEngineExtension extends AbstractExtension
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
                'StorageEngine_getHtmlSelect',
                'PhpMyAdmin\StorageEngine::getHtmlSelect',
                array('is_safe' => array('html'))
            ),
        );
    }
}
