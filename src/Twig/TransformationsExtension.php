<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Transformations;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TransformationsExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_description', [Transformations::class, 'getDescription']),
            new TwigFunction('get_name', [Transformations::class, 'getName']),
        ];
    }
}
