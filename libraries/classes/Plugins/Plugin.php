<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;

interface Plugin
{
    public function getProperties(): PluginPropertyItem;

    public function isAvailable(): bool;
}
