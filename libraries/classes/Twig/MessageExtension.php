<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\MessageExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Message;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class MessageExtension
 *
 * @package PhpMyAdmin\Twig
 */
class MessageExtension extends AbstractExtension
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
                'Message_notice',
                function ($string) {
                    return Message::notice($string)->getDisplay();
                },
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Message_error',
                function ($string) {
                    return Message::error($string)->getDisplay();
                },
                ['is_safe' => ['html']]
            ),
        ];
    }
}
