<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

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
                'PhpMyAdmin\Plugins::checkboxCheck',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_choice',
                'PhpMyAdmin\Plugins::getChoice',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_default_plugin',
                'PhpMyAdmin\Plugins::getDefault',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_options',
                'PhpMyAdmin\Plugins::getOptions',
                ['is_safe' => ['html']]
            ),
        ];
    }
}
