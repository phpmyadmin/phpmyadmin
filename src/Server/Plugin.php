<?php
/**
 * Server Plugin value object
 */

declare(strict_types=1);

namespace PhpMyAdmin\Server;

/**
 * Server Plugin value object
 */
final class Plugin
{
    /**
     * @param string      $name           Name of the plugin
     * @param string|null $version        Version from the plugin's general type descriptor
     * @param string      $status         Plugin status
     * @param string      $type           Type of plugin
     * @param string|null $typeVersion    Version from the plugin's type-specific descriptor
     * @param string|null $library        Plugin's shared object file name
     * @param string|null $libraryVersion Version from the plugin's API interface
     * @param string|null $author         Author of the plugin
     * @param string|null $description    Description
     * @param string      $license        Plugin's licence
     * @param string|null $loadOption     How the plugin was loaded
     * @param string|null $maturity       Plugin's maturity level
     * @param string|null $authVersion    Plugin's version as determined by the plugin author
     */
    private function __construct(
        private string $name,
        private string|null $version,
        private string $status,
        private string $type,
        private string|null $typeVersion,
        private string|null $library,
        private string|null $libraryVersion,
        private string|null $author,
        private string|null $description,
        private string $license,
        private string|null $loadOption,
        private string|null $maturity,
        private string|null $authVersion,
    ) {
    }

    /** @param mixed[] $state array with the properties */
    public static function fromState(array $state): self
    {
        return new self(
            $state['name'] ?? '',
            $state['version'] ?? null,
            $state['status'] ?? '',
            $state['type'] ?? '',
            $state['typeVersion'] ?? null,
            $state['library'] ?? null,
            $state['libraryVersion'] ?? null,
            $state['author'] ?? null,
            $state['description'] ?? null,
            $state['license'] ?? '',
            $state['loadOption'] ?? null,
            $state['maturity'] ?? null,
            $state['authVersion'] ?? null,
        );
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'status' => $this->getStatus(),
            'type' => $this->getType(),
            'type_version' => $this->getTypeVersion(),
            'library' => $this->getLibrary(),
            'library_version' => $this->getLibraryVersion(),
            'author' => $this->getAuthor(),
            'description' => $this->getDescription(),
            'license' => $this->getLicense(),
            'load_option' => $this->getLoadOption(),
            'maturity' => $this->getMaturity(),
            'auth_version' => $this->getAuthVersion(),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string|null
    {
        return $this->version;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeVersion(): string|null
    {
        return $this->typeVersion;
    }

    public function getLibrary(): string|null
    {
        return $this->library;
    }

    public function getLibraryVersion(): string|null
    {
        return $this->libraryVersion;
    }

    public function getAuthor(): string|null
    {
        return $this->author;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    public function getLicense(): string
    {
        return $this->license;
    }

    public function getLoadOption(): string|null
    {
        return $this->loadOption;
    }

    public function getMaturity(): string|null
    {
        return $this->maturity;
    }

    public function getAuthVersion(): string|null
    {
        return $this->authVersion;
    }
}
