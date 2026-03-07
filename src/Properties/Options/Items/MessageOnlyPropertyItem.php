<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Items;

use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

/**
 * Single property item class of type messageOnly
 */
class MessageOnlyPropertyItem extends OptionsPropertyOneItem
{
    public function getHtml(Plugin $plugin, PluginType $pluginType): string
    {
        $ret = '<li class="list-group-item">';
        $ret .= $plugin->getTranslatedText($this->getText());
        $ret .= Plugins::getDocumentationLinkHtml($this);

        return $ret;
    }
}
