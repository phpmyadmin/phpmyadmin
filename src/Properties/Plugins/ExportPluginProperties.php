<?php
/**
 * Properties class for the export plug-in
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Plugins;

/**
 * Defines possible options and getters and setters for them.
 *
 * @todo    modify descriptions if needed, when the plug-in properties are integrated
 */
class ExportPluginProperties extends PluginPropertyItem
{
    /**
     * Whether each plugin has to be saved as a file
     */
    private bool $forceFile = false;
    private string $mimeType = '';

    /**
     * Gets the force file parameter
     */
    public function getForceFile(): bool
    {
        return $this->forceFile;
    }

    /**
     * Sets the force file parameter
     */
    public function setForceFile(bool $forceFile): void
    {
        $this->forceFile = $forceFile;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}
