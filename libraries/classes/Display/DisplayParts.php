<?php

declare(strict_types=1);

namespace PhpMyAdmin\Display;

/**
 * @psalm-immutable
 */
final class DisplayParts
{
    public const NO_DELETE = 0;
    public const DELETE_ROW = 1;
    public const KILL_PROCESS = 2;

    /** @var bool */
    public $hasEditLink;

    /**
     * @var int
     * @psalm-var self::NO_DELETE|self::DELETE_ROW|self::KILL_PROCESS
     */
    public $deleteLink;

    /** @var bool */
    public $hasSortLink;

    /** @var bool */
    public $hasNavigationBar;

    /** @var bool */
    public $hasBookmarkForm;

    /** @var bool */
    public $hasTextButton;

    /** @var bool */
    public $hasPrintLink;

    /**
     * @psalm-param self::NO_DELETE|self::DELETE_ROW|self::KILL_PROCESS $deleteLink
     */
    private function __construct(
        bool $hasEditLink,
        int $deleteLink,
        bool $hasSortLink,
        bool $hasNavigationBar,
        bool $hasBookmarkForm,
        bool $hasTextButton,
        bool $hasPrintLink
    ) {
        $this->hasEditLink = $hasEditLink;
        $this->deleteLink = $deleteLink;
        $this->hasSortLink = $hasSortLink;
        $this->hasNavigationBar = $hasNavigationBar;
        $this->hasBookmarkForm = $hasBookmarkForm;
        $this->hasTextButton = $hasTextButton;
        $this->hasPrintLink = $hasPrintLink;
    }

    /**
     * @param array<string, bool|int> $parts
     * @psalm-param array{
     *     hasEditLink?: bool,
     *     deleteLink?: self::NO_DELETE|self::DELETE_ROW|self::KILL_PROCESS,
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
            $parts['deleteLink'] ?? self::NO_DELETE,
            $parts['hasSortLink'] ?? false,
            $parts['hasNavigationBar'] ?? false,
            $parts['hasBookmarkForm'] ?? false,
            $parts['hasTextButton'] ?? false,
            $parts['hasPrintLink'] ?? false
        );
    }

    /**
     * @param array<string, bool|int> $parts
     * @psalm-param array{
     *     hasEditLink?: bool,
     *     deleteLink?: self::NO_DELETE|self::DELETE_ROW|self::KILL_PROCESS,
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
            $parts['hasPrintLink'] ?? $this->hasPrintLink
        );
    }
}
