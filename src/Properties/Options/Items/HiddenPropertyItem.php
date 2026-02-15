<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Items;

use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

use function htmlspecialchars;

/**
 * Single property item class of type hidden
 */
class HiddenPropertyItem extends OptionsPropertyOneItem
{
    public function getHtml(Plugin $plugin, PluginType $pluginType): string
    {
        return '<li class="list-group-item"><input type="hidden" name="' . $this->getName() . '"'
            . ' value="'
            . htmlspecialchars($plugin->getTranslatedText(Plugins::getDefault(
                $pluginType,
                $this->getName(),
            )))
            . '">';
    }
}
