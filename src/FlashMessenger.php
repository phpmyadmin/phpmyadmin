<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use RuntimeException;

use function __;

final class FlashMessenger
{
    private const STORAGE_KEY = 'FlashMessenger';

    /** @var mixed[] */
    private array $storage;

    /** @psalm-var list<array{context: string, message: string, statement: string}> */
    private array $previousMessages = [];

    public function __construct()
    {
        if (! isset($_SESSION)) {
            throw new RuntimeException(__('Session not found.'));
        }

        $this->storage = &$_SESSION;

        if (isset($this->storage[self::STORAGE_KEY])) {
            $this->previousMessages = $this->storage[self::STORAGE_KEY];
        }

        $this->storage[self::STORAGE_KEY] = [];
    }

    public function addMessage(string $context, string $message, string $statement = ''): void
    {
        $this->storage[self::STORAGE_KEY][] = ['context' => $context, 'message' => $message, 'statement' => $statement];
    }

    /** @psalm-return list<array{context: string, message: string, statement: string}> */
    public function getMessages(): array
    {
        return $this->previousMessages;
    }

    /** @psalm-return list<array{context: string, message: string, statement: string}> */
    public function getCurrentMessages(): array
    {
        return $this->storage[self::STORAGE_KEY];
    }
}
