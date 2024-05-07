<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\LicenseController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LicenseController::class)]
final class LicenseControllerTest extends AbstractTestCase
{
    public function testWithValidFile(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $controller = new LicenseController(new ResponseRenderer(), ResponseFactory::create());
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['text/plain; charset=utf-8'], $response->getHeader('Content-Type'));

        $body = (string) $response->getBody();
        self::assertStringContainsString('GNU GENERAL PUBLIC LICENSE', $body);
        self::assertStringContainsString('Version 2, June 1991', $body);
    }
}
