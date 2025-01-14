<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;

interface Plugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string;

    public function getProperties(): PluginPropertyItem;

    /** Returns locale string for $text or $text if no locale is found */
    public function getTranslatedText(string $text): string;

    public static function isAvailable(): bool;
}
