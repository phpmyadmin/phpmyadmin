<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Display\DeleteLinkEnum;
use PhpMyAdmin\Display\DisplayParts;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisplayParts::class)]
class DisplayPartsTest extends TestCase
{
    public function testFromArray(): void
    {
        $displayParts = DisplayParts::fromArray([
            'hasEditLink' => true,
            'deleteLink' => DeleteLinkEnum::DELETE_ROW,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => true,
            'hasPrintLink' => true,
        ]);
        self::assertTrue($displayParts->hasEditLink);
        self::assertSame(DeleteLinkEnum::DELETE_ROW, $displayParts->deleteLink);
        self::assertTrue($displayParts->hasSortLink);
        self::assertTrue($displayParts->hasNavigationBar);
        self::assertTrue($displayParts->hasBookmarkForm);
        self::assertTrue($displayParts->hasTextButton);
        self::assertTrue($displayParts->hasPrintLink);
    }

    public function testWith(): void
    {
        $displayParts = DisplayParts::fromArray([]);
        self::assertFalse($displayParts->hasEditLink);
        self::assertSame(DeleteLinkEnum::NO_DELETE, $displayParts->deleteLink);
        self::assertFalse($displayParts->hasSortLink);
        self::assertFalse($displayParts->hasNavigationBar);
        self::assertFalse($displayParts->hasBookmarkForm);
        self::assertFalse($displayParts->hasTextButton);
        self::assertFalse($displayParts->hasPrintLink);

        $displayParts = $displayParts->with([
            'hasEditLink' => true,
            'deleteLink' => DeleteLinkEnum::KILL_PROCESS,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => true,
            'hasPrintLink' => true,
        ]);
        self::assertTrue($displayParts->hasEditLink);
        self::assertSame(DeleteLinkEnum::KILL_PROCESS, $displayParts->deleteLink);
        self::assertTrue($displayParts->hasSortLink);
        self::assertTrue($displayParts->hasNavigationBar);
        self::assertTrue($displayParts->hasBookmarkForm);
        self::assertTrue($displayParts->hasTextButton);
        self::assertTrue($displayParts->hasPrintLink);

        $displayParts = $displayParts->with([]);
        self::assertTrue($displayParts->hasEditLink);
        self::assertSame(DeleteLinkEnum::KILL_PROCESS, $displayParts->deleteLink);
        self::assertTrue($displayParts->hasSortLink);
        self::assertTrue($displayParts->hasNavigationBar);
        self::assertTrue($displayParts->hasBookmarkForm);
        self::assertTrue($displayParts->hasTextButton);
        self::assertTrue($displayParts->hasPrintLink);
    }
}
