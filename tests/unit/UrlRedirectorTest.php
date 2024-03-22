<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\UrlRedirector;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(UrlRedirector::class)]
final class UrlRedirectorTest extends AbstractTestCase
{
    public function testRedirectWithDisallowedUrl(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $GLOBALS['lang'] = 'en';
        $config = Config::getInstance();
        $config->settings['PmaAbsoluteUri'] = 'http://localhost/phpmyadmin';

        $response = UrlRedirector::redirect('https://user:pass@example.com/');
        self::assertSame('/phpmyadmin/', $response->getHeaderLine('Location'));
        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
    }

    public function testRedirectWithAllowedUrl(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $GLOBALS['lang'] = 'en';
        $_SERVER['SERVER_NAME'] = 'localhost';

        UrlRedirector::redirect('https://phpmyadmin.net/');
        $output = self::getActualOutputForAssertion();
        $expected = <<<'HTML'
<script>
    window.onload = function () {
        window.location = 'https\u003A\/\/phpmyadmin.net\/';
    };
</script>
Taking you to the target site.
HTML;

        self::assertSame($expected, $output);
    }
}
