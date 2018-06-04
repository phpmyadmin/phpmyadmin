<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\RelationExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

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
        return [
            new TwigFunction(
                'Relation_foreignDropdown',
                [$relation, 'foreignDropdown'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Relation_getDisplayField',
                [$relation, 'getDisplayField'],
                ['is_safe' => ['html']]
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
        ];
    }
}
