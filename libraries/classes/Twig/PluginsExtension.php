<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Plugins;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PluginsExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'checkbox_check',
                [Plugins::class, 'checkboxCheck'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_choice',
                [Plugins::class, 'getChoice'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_default_plugin',
                [Plugins::class, 'getDefault'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_options',
                [Plugins::class, 'getOptions'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
