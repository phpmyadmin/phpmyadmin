<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\ConfigStorage\Relation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RelationExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        $relation = new Relation($GLOBALS['dbi']);

        return [
            new TwigFunction(
                'get_tables',
                [$relation, 'getTables']
            ),
        ];
    }
}
