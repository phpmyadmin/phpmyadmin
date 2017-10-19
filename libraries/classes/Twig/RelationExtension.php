<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\RelationExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class RelationExtension
 *
 * @package PhpMyAdmin\Twig
 */
class RelationExtension extends Twig_Extension
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
                'Relation_foreignDropdown',
                'PhpMyAdmin\Relation::foreignDropdown',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Relation_getDisplayField',
                'PhpMyAdmin\Relation::getDisplayField',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Relation_getForeignData',
                'PhpMyAdmin\Relation::getForeignData'
            ),
            new Twig_SimpleFunction(
                'Relation_searchColumnInForeigners',
                'PhpMyAdmin\Relation::searchColumnInForeigners'
            ),
        );
    }
}
