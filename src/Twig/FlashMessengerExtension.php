<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\FlashMessenger;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FlashMessengerExtension extends AbstractExtension
{
    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [new TwigFunction('flash_messages', [FlashMessenger::class, 'getMessages'])];
    }
}
