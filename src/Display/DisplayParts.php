<?php

declare(strict_types=1);

namespace PhpMyAdmin\Display;

/** @psalm-immutable */
final class DisplayParts
{
    private function __construct(
        public readonly bool $hasEditLink,
        public readonly DeleteLinkEnum $deleteLink,
        public readonly bool $hasSortLink,
        public readonly bool $hasNavigationBar,
        public readonly bool $hasBookmarkForm,
        public readonly bool $hasTextButton,
        public readonly bool $hasPrintLink,
    ) {
    }

    /**
     * @param array<string, bool|int> $parts
     * @psalm-param array{
     *     hasEditLink?: bool,
     *     deleteLink?: DeleteLinkEnum,
     *     hasSortLink?: bool,
     *     hasNavigationBar?: bool,
     *     hasBookmarkForm?: bool,
     *     hasTextButton?: bool,
     *     hasPrintLink?: bool
     * } $parts
     */
    public static function fromArray(array $parts): self
    {
        return new self(
            $parts['hasEditLink'] ?? false,
            $parts['deleteLink'] ?? DeleteLinkEnum::NO_DELETE,
            $parts['hasSortLink'] ?? false,
            $parts['hasNavigationBar'] ?? false,
            $parts['hasBookmarkForm'] ?? false,
            $parts['hasTextButton'] ?? false,
            $parts['hasPrintLink'] ?? false,
        );
    }

    /**
     * @param array<string, bool|int> $parts
     * @psalm-param array{
     *     hasEditLink?: bool,
     *     deleteLink?: DeleteLinkEnum,
     *     hasSortLink?: bool,
     *     hasNavigationBar?: bool,
     *     hasBookmarkForm?: bool,
     *     hasTextButton?: bool,
     *     hasPrintLink?: bool
     * } $parts
     */
    public function with(array $parts): self
    {
        return new self(
            $parts['hasEditLink'] ?? $this->hasEditLink,
            $parts['deleteLink'] ?? $this->deleteLink,
            $parts['hasSortLink'] ?? $this->hasSortLink,
            $parts['hasNavigationBar'] ?? $this->hasNavigationBar,
            $parts['hasBookmarkForm'] ?? $this->hasBookmarkForm,
            $parts['hasTextButton'] ?? $this->hasTextButton,
            $parts['hasPrintLink'] ?? $this->hasPrintLink,
        );
    }
}
