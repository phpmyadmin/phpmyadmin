<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Display\DisplayParts;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Display\DisplayParts
 */
class DisplayPartsTest extends TestCase
{
    public function testFromArray(): void
    {
        $displayParts = DisplayParts::fromArray([
            'hasEditLink' => true,
            'deleteLink' => DisplayParts::DELETE_ROW,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => true,
            'hasPrintLink' => true,
        ]);
        $this->assertTrue($displayParts->hasEditLink);
        $this->assertSame(DisplayParts::DELETE_ROW, $displayParts->deleteLink);
        $this->assertTrue($displayParts->hasSortLink);
        $this->assertTrue($displayParts->hasNavigationBar);
        $this->assertTrue($displayParts->hasBookmarkForm);
        $this->assertTrue($displayParts->hasTextButton);
        $this->assertTrue($displayParts->hasPrintLink);
    }

    public function testWith(): void
    {
        $displayParts = DisplayParts::fromArray([]);
        $this->assertFalse($displayParts->hasEditLink);
        $this->assertSame(DisplayParts::NO_DELETE, $displayParts->deleteLink);
        $this->assertFalse($displayParts->hasSortLink);
        $this->assertFalse($displayParts->hasNavigationBar);
        $this->assertFalse($displayParts->hasBookmarkForm);
        $this->assertFalse($displayParts->hasTextButton);
        $this->assertFalse($displayParts->hasPrintLink);

        $displayParts = $displayParts->with([
            'hasEditLink' => true,
            'deleteLink' => DisplayParts::KILL_PROCESS,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => true,
            'hasPrintLink' => true,
        ]);
        $this->assertTrue($displayParts->hasEditLink);
        $this->assertSame(DisplayParts::KILL_PROCESS, $displayParts->deleteLink);
        $this->assertTrue($displayParts->hasSortLink);
        $this->assertTrue($displayParts->hasNavigationBar);
        $this->assertTrue($displayParts->hasBookmarkForm);
        $this->assertTrue($displayParts->hasTextButton);
        $this->assertTrue($displayParts->hasPrintLink);

        $displayParts = $displayParts->with([]);
        $this->assertTrue($displayParts->hasEditLink);
        $this->assertSame(DisplayParts::KILL_PROCESS, $displayParts->deleteLink);
        $this->assertTrue($displayParts->hasSortLink);
        $this->assertTrue($displayParts->hasNavigationBar);
        $this->assertTrue($displayParts->hasBookmarkForm);
        $this->assertTrue($displayParts->hasTextButton);
        $this->assertTrue($displayParts->hasPrintLink);
    }
}
