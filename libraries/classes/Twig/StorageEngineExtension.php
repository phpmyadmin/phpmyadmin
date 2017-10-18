<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\StorageEngineExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class StorageEngineExtension
 *
 * @package PhpMyAdmin\Twig
 */
class StorageEngineExtension extends Twig_Extension
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
                'StorageEngine_getHtmlSelect',
                'PhpMyAdmin\StorageEngine::getHtmlSelect',
                array('is_safe' => array('html'))
            ),
        );
    }
}
