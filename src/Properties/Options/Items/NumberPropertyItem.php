<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Items;

use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

use function htmlspecialchars;

/**
 * Single property item class of type number
 */
class NumberPropertyItem extends OptionsPropertyOneItem
{
    public function getHtml(Plugin $plugin, PluginType $pluginType): string
    {
        $ret = '<li class="list-group-item">';
        $ret .= '<label for="number_' . $this->getName() . '" class="form-label">'
            . $plugin->getTranslatedText($this->getText() ?? '') . '</label>';
        $ret .= '<input class="form-control" type="number" name="' . $this->getName() . '"'
            . ' value="'
            . htmlspecialchars($plugin->getTranslatedText(Plugins::getDefault(
                $pluginType,
                $this->getName(),
            ))) . '"'
            . ' id="number_' . $this->getName() . '"'
            . ' min="0"'
            . '>';

        return $ret;
    }
}
