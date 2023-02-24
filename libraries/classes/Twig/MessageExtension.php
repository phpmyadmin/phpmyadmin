<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Message;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

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
                static fn (string $string) => Message::notice($string)->getDisplay(),
                ['is_safe' => ['html']],
            ),
            new TwigFilter(
                'error',
                static fn (string $string) => Message::error($string)->getDisplay(),
                ['is_safe' => ['html']],
            ),
            new TwigFilter(
                'raw_success',
                static fn (string $string) => Message::rawSuccess($string)->getDisplay(),
                ['is_safe' => ['html']],
            ),
        ];
    }
}
