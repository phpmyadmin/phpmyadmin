<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use RuntimeException;
use Twig\Attribute\AsTwigFunction;

use function __;

/** @psalm-type FlashMessageList = list<array{context: string, message: string, statement: string}> */
final class FlashMessenger
{
    private const STORAGE_KEY = 'FlashMessenger';

    /** @var mixed[] */
    private array|null $storage = null;

    /** @psalm-var FlashMessageList */
    private array $previousMessages = [];

    /** @psalm-assert !null $this->storage */
    private function initSessionStorage(): void
    {
        if ($this->storage !== null) {
            return;
        }

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
        $this->initSessionStorage();

        $this->storage[self::STORAGE_KEY][] = ['context' => $context, 'message' => $message, 'statement' => $statement];
    }

    /** @psalm-return FlashMessageList */
    #[AsTwigFunction('flash_messages')]
    public function getMessages(): array
    {
        $this->initSessionStorage();

        return $this->previousMessages;
    }

    /** @psalm-return FlashMessageList */
    public function getCurrentMessages(): array
    {
        $this->initSessionStorage();

        return $this->storage[self::STORAGE_KEY];
    }
}
