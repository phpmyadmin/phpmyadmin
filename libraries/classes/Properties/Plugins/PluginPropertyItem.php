<?php
/**
 * The top-level class of the "Plugin" subtree of the object-oriented
 * properties system (the other subtree is "Options").
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Plugins;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\PropertyItem;

/**
 * Superclass for
 *  - PhpMyAdmin\Properties\Plugins\ExportPluginProperties,
 *  - PhpMyAdmin\Properties\Plugins\ImportPluginProperties and
 *  - TransformationsPluginProperties
 */
abstract class PluginPropertyItem extends PropertyItem
{
    private string $text = '';
    private string $extension = '';
    private OptionsPropertyRootGroup|null $options = null;
    private string $optionsText = '';
    private string $mimeType = '';

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function getOptions(): OptionsPropertyRootGroup|null
    {
        return $this->options;
    }

    public function setOptions(OptionsPropertyRootGroup $options): void
    {
        $this->options = $options;
    }

    public function getOptionsText(): string
    {
        return $this->optionsText;
    }

    public function setOptionsText(string $optionsText): void
    {
        $this->optionsText = $optionsText;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * Returns the property type ( either "options", or "plugin" ).
     */
    public function getPropertyType(): string
    {
        return 'plugin';
    }

    /**
     * Whether each plugin has to be saved as a file
     */
    public function getForceFile(): bool
    {
        return false;
    }
}
