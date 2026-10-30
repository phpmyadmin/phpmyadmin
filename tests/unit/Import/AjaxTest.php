<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Plugins\Import\Upload\UploadNoplugin;
use PhpMyAdmin\Plugins\Import\Upload\UploadProgress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

#[CoversClass(Ajax::class)]
class AjaxTest extends TestCase
{
    public function testUploadProgressSetup(): void
    {
        $_SESSION = [];

        $uploadId = (new Ajax(new Randomizer(new Mt19937(42))))->uploadProgressSetup();

        self::assertSame('66dce15fb33deacb', $uploadId);
        self::assertSame(UploadNoplugin::class, $_SESSION[Ajax::SESSION_KEY]['handler']);
    }

    public function testGetHandler(): void
    {
        $_SESSION = [];
        self::assertSame(UploadNoplugin::class, Ajax::getHandlerFromSession());

        $_SESSION = [Ajax::SESSION_KEY => ['handler' => UploadProgress::class]];
        self::assertSame(UploadProgress::class, Ajax::getHandlerFromSession());

        $_SESSION = [Ajax::SESSION_KEY => ['handler' => self::class]];
        self::assertSame(UploadNoplugin::class, Ajax::getHandlerFromSession());

        $_SESSION = [Ajax::SESSION_KEY => ['handler' => '']];
        self::assertSame(UploadNoplugin::class, Ajax::getHandlerFromSession());

        $_SESSION = [];
    }

    public function testNopluginCheck(): void
    {
        self::assertTrue(Ajax::nopluginCheck());
    }
}
