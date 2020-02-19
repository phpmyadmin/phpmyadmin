<?php
/**
 * hold PhpMyAdmin\Twig\MessageExtension class
 */
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
    public function getFilters()
    {
        return [
            new TwigFilter(
                'notice',
                function (string $string) {
                    return Message::notice($string)->getDisplay();
                },
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'error',
                function (string $string) {
                    return Message::error($string)->getDisplay();
                },
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'raw_success',
                function (string $string) {
                    return Message::rawSuccess($string)->getDisplay();
                },
                ['is_safe' => ['html']]
            ),
        ];
    }
}
