<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class MessageExtension
{
    #[AsTwigFilter('notice', isSafe: ['html'])]
    public static function getNotice(string $string): string
    {
        return Message::notice($string)->getDisplay();
    }

    #[AsTwigFilter('error', isSafe: ['html'])]
    public static function getError(string $string): string
    {
        return Message::error($string)->getDisplay();
    }

    #[AsTwigFilter('raw_success', isSafe: ['html'])]
    public static function getRawSuccess(string $string): string
    {
        return Message::rawSuccess($string)->getDisplay();
    }

    #[AsTwigFunction('statement_message', isSafe: ['html'])]
    public static function getStatementMessage(string $message, string $statement, string $context): string
    {
        $type = match ($context) {
            'success' => MessageType::Success,
            'error', 'danger' => MessageType::Error,
            default => MessageType::Notice,
        };

        return Generator::getMessage($message, $statement, $type);
    }
}
