<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MessageExtension extends AbstractExtension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'notice',
                static fn (string $string): string => Message::notice($string)->getDisplay(),
                ['is_safe' => ['html']],
            ),
            new TwigFilter(
                'error',
                static fn (string $string): string => Message::error($string)->getDisplay(),
                ['is_safe' => ['html']],
            ),
            new TwigFilter(
                'raw_success',
                static fn (string $string): string => Message::rawSuccess($string)->getDisplay(),
                ['is_safe' => ['html']],
            ),
        ];
    }

    /** @inheritDoc */
    public function getFunctions(): array
    {
        return [new TwigFunction('statement_message', Generator::getMessage(...), ['is_safe' => ['html']])];
    }
}
