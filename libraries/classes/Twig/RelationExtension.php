<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\RelationExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Relation;
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
        $relation = new Relation();
        return array(
            new TwigFunction(
                'Relation_foreignDropdown',
                [$relation, 'foreignDropdown'],
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Relation_getDisplayField',
                [$relation, 'getDisplayField'],
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Relation_getForeignData',
                [$relation, 'getForeignData']
            ),
            new TwigFunction(
                'Relation_getTables',
                [$relation, 'getTables']
            ),
            new TwigFunction(
                'Relation_searchColumnInForeigners',
                [$relation, 'searchColumnInForeigners']
            ),
        );
    }
}
