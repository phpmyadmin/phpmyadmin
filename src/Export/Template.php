<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

use PhpMyAdmin\Plugins\ExportType;

/** @psalm-immutable */
final class Template
{
    private function __construct(
        private int $id,
        private string $username,
        private ExportType $exportType,
        private string $name,
        private string $data,
    ) {
    }

    /** @param array<string, mixed> $state */
    public static function fromArray(array $state): self
    {
        return new self(
            $state['id'] ?? 0,
            $state['username'],
            ExportType::tryFrom($state['exportType'] ?? '') ?? ExportType::Server,
            $state['name'] ?? '',
            $state['data'],
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getExportType(): ExportType
    {
        return $this->exportType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
