<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UrlRedirector;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UrlRedirector::class)]
final class UrlRedirectorTest extends AbstractTestCase
{
    public function testRedirectWithDisallowedUrl(): void
    {
        $config = Config::getInstance();
        $config->set('PmaAbsoluteUri', 'http://localhost/phpmyadmin');

        $urlRedirector = new UrlRedirector(new ResponseRenderer(), new Template(), ResponseFactory::create());

        $response = $urlRedirector->redirect('https://user:pass@example.com/');
        self::assertSame('/phpmyadmin/', $response->getHeaderLine('Location'));
        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
    }

    public function testRedirectWithAllowedUrl(): void
    {
        $_SERVER['SERVER_NAME'] = 'localhost';

        $urlRedirector = new UrlRedirector(new ResponseRenderer(), new Template(), ResponseFactory::create());

        $response = $urlRedirector->redirect('https://phpmyadmin.net/');
        $expected = <<<'HTML'
            <script>
                window.onload = function () {
                    window.location = 'https\u003A\/\/phpmyadmin.net\/';
                };
            </script>
            Taking you to the target site.

            HTML;

        self::assertSame($expected, (string) $response->getBody());
    }
}
