<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\RelationExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class RelationExtension
 *
 * @package PhpMyAdmin\Twig
 */
class RelationExtension extends AbstractExtension
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
                'Relation_foreignDropdown',
                'PhpMyAdmin\Relation::foreignDropdown',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Relation_getDisplayField',
                'PhpMyAdmin\Relation::getDisplayField',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Relation_getForeignData',
                'PhpMyAdmin\Relation::getForeignData'
            ),
            new TwigFunction(
                'Relation_getTables',
                'PhpMyAdmin\Relation::getTables'
            ),
            new TwigFunction(
                'Relation_searchColumnInForeigners',
                'PhpMyAdmin\Relation::searchColumnInForeigners'
            ),
        );
    }
}
