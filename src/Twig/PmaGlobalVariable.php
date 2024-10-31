<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Version;
use RuntimeException;

use function sprintf;

/**
 * @method string version()
 * @method string text_dir()
 */
final class PmaGlobalVariable
{
    /** @param mixed[] $arguments */
    public function __call(string $name, array $arguments): string
    {
        return match ($name) {
            'version' => Version::VERSION,
            'text_dir' => LanguageManager::$textDirection->value,
            default => throw new RuntimeException(sprintf('The "pma.%s" variable is not available.', $name)),
        };
    }
}
