<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Tracker;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TrackerExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'get_tracker_version',
                Tracker::getVersion(...),
            ),
        ];
    }
}
