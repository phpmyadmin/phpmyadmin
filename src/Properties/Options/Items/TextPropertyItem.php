<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Items;

use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

use function htmlspecialchars;

/**
 * Single property item class of type text
 */
class TextPropertyItem extends OptionsPropertyOneItem
{
    public function getHtml(Plugin $plugin, PluginType $pluginType, string $pluginName): string
    {
        $ret = '<li class="list-group-item">';
        $ret .= '<label for="text_' . $pluginName . '_' . $this->getName() . '" class="form-label">'
            . $plugin->getTranslatedText($this->getText() ?? '') . '</label>';
        $ret .= '<input class="form-control" type="text" name="' . $pluginName . '_' . $this->getName() . '"'
            . ' value="'
            . htmlspecialchars($plugin->getTranslatedText(Plugins::getDefault(
                $pluginType,
                $pluginName . '_' . $this->getName(),
            ))) . '"'
            . ' id="text_' . $pluginName . '_' . $this->getName() . '"'
            . ($this->getSize() !== 0
                ? ' size="' . $this->getSize() . '"'
                : '')
            . ($this->getLen() !== 0
                ? ' maxlength="' . $this->getLen() . '"'
                : '')
            . '>';
        $ret .= Plugins::getDocumentationLinkHtml($this);

        return $ret;
    }
}
