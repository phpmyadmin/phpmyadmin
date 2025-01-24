<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\PhpInfoController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

use function ob_get_clean;
use function ob_start;
use function phpinfo;

use const INFO_CONFIGURATION;
use const INFO_GENERAL;
use const INFO_MODULES;

#[CoversClass(PhpInfoController::class)]
final class PhpInfoControllerTest extends AbstractTestCase
{
    public function testWithShowPhpInfoEqualsTrue(): void
    {
        $config = new Config();
        $config->settings['ShowPhpInfo'] = true;

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $controller = new PhpInfoController(new ResponseRenderer(), ResponseFactory::create(), $config);
        $response = $controller($request);

        ob_start();
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
        $expected = (string) ob_get_clean();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
        self::assertSame($expected, (string) $response->getBody());
    }

    public function testWithShowPhpInfoEqualsFalse(): void
    {
        $config = Config::getInstance();
        $config->settings['ShowPhpInfo'] = false;
        $config->set('PmaAbsoluteUri', 'http://localhost/phpmyadmin');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $controller = new PhpInfoController(new ResponseRenderer(), ResponseFactory::create(), $config);
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame('/phpmyadmin/', $response->getHeaderLine('Location'));
    }
}
