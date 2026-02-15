<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Items;

use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

/**
 * Single property item class of type bool
 */
class BoolPropertyItem extends OptionsPropertyOneItem
{
    public function getHtml(Plugin $plugin, PluginType $pluginType): string
    {
        $ret = '<li class="list-group-item">';
        $ret .= '<div class="form-check form-switch">';
        $ret .= '<input class="form-check-input" type="checkbox" role="switch" name="'
            . $this->getName() . '"'
            . ' value="y" id="checkbox_' . $this->getName() . '"'
            . ' '
            . Plugins::checkboxCheck(
                $pluginType,
                $this->getName(),
            );

        $ret .= '>';
        $ret .= '<label class="form-check-label" for="checkbox_' . $this->getName() . '">'
            . $plugin->getTranslatedText($this->getText() ?? '') . '</label></div>';
        $ret .= Plugins::getDocumentationLinkHtml($this);

        return $ret;
    }
}
