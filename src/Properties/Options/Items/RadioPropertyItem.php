<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Items;

use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

use function htmlspecialchars;

/**
 * Single property item class of type radio
 */
class RadioPropertyItem extends OptionsPropertyOneItem
{
    public function getHtml(Plugin $plugin, PluginType $pluginType, string $pluginName): string
    {
        $default = htmlspecialchars($plugin->getTranslatedText(Plugins::getDefault(
            $pluginType,
            $pluginName . '_' . $this->getName(),
        )));

        $ret = '<li class="list-group-item">';

        foreach ($this->getValues() as $key => $val) {
            $ret .= '<div class="form-check"><input type="radio" name="' . $pluginName . '_' . $this->getName()
                . '" class="form-check-input" value="' . $key
                . '" id="radio_' . $pluginName . '_' . $this->getName() . '_' . $key . '"';
            if ($key == $default) {
                $ret .= ' checked';
            }

            $ret .= '><label class="form-check-label" for="radio_'
                . $pluginName . '_' . $this->getName() . '_' . $key . '">'
                . $plugin->getTranslatedText($val) . '</label></div>';
        }

        return $ret;
    }
}
