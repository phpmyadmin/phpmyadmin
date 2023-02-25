<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

/** @psalm-immutable */
final class Template
{
    /** @var string JSON */
    private string $data;

    private function __construct(
        private int $id,
        private string $username,
        private string $exportType,
        private string $name,
        string $data,
    ) {
        $this->data = $data;
    }

    /** @param array<string, mixed> $state */
    public static function fromArray(array $state): self
    {
        return new self(
            $state['id'] ?? 0,
            $state['username'],
            $state['exportType'] ?? '',
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

    public function getExportType(): string
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
