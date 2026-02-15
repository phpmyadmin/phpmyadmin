<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Items;

use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

use function htmlspecialchars;

/**
 * Single property item class of type select
 */
class SelectPropertyItem extends OptionsPropertyOneItem
{
    public function getHtml(Plugin $plugin, PluginType $pluginType, string $pluginName): string
    {
        $ret = '<li class="list-group-item">';
        $ret .= '<label for="select_' . $this->getName() . '" class="form-label">'
            . $plugin->getTranslatedText($this->getText() ?? '') . '</label>';
        $ret .= '<select class="form-select" name="' . $this->getName() . '"'
            . ' id="select_' . $this->getName() . '">';
        $default = htmlspecialchars($plugin->getTranslatedText(Plugins::getDefault(
            $pluginType,
            $this->getName(),
        )));
        foreach ($this->getValues() as $key => $val) {
            $ret .= '<option value="' . $key . '"';
            if ($key == $default) {
                $ret .= ' selected';
            }

            $ret .= '>' . $plugin->getTranslatedText($val) . '</option>';
        }

        $ret .= '</select>';
        $ret .= Plugins::getDocumentationLinkHtml($this);

        return $ret;
    }
}
