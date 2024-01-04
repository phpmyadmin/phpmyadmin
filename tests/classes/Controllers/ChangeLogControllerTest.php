<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use const TEST_PATH;

#[CoversClass(ChangeLogController::class)]
final class ChangeLogControllerTest extends AbstractTestCase
{
    public function testWithValidFile(): void
    {
        $config = self::createStub(Config::class);
        $config->method('getChangeLogFilePath')->willReturn(TEST_PATH . 'tests/test_data/changelog/ChangeLog');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $responseRenderer = new ResponseRenderer();
        $template = new Template();
        $controller = new ChangeLogController($responseRenderer, $template, $config);
        $controller($request);

        self::assertTrue($responseRenderer->isDisabled());
        $response = $responseRenderer->getResponse();
        self::assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));

        // phpcs:disable Generic.Files.LineLength.TooLong
        $changelog = <<<'HTML'
phpMyAdmin - ChangeLog
======================

5.2.2 (not yet released)
- <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17522">issue #17522</a> Fix case where the routes cache file is invalid
- issue        Upgrade slim/psr7 to 1.4.1 for <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://www.cve.org/CVERecord?id=CVE-2023-30536">CVE-2023-30536</a> - GHSA-q2qj-628g-vhfw

5.2.1 (2023-02-07)
- <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/16418">issue #16418</a> Fix <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://docs.phpmyadmin.net/en/latest/faq.html#faq1-44">FAQ 1.44</a> about manually removing vendor folders
- issue        [security] Fix an XSS attack through the drag-and-drop upload feature (<a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://www.phpmyadmin.net/security/PMASA-2023-01/">PMASA-2023-01</a>)

         --- Older ChangeLogs can be found on our project website ---
                     <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://www.phpmyadmin.net/old-stuff/ChangeLogs/">https://www.phpmyadmin.net/old-stuff/ChangeLogs/</a>

HTML;
        // phpcs:enable
        $expected = $template->render('changelog', ['changelog' => $changelog]);

        self::assertSame($expected, $responseRenderer->getHTMLResult());
    }

    #[RequiresPhpExtension('zlib')]
    public function testWithCompressedFile(): void
    {
        $config = self::createStub(Config::class);
        $config->method('getChangeLogFilePath')->willReturn(TEST_PATH . 'tests/test_data/changelog/ChangeLog.gz');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $responseRenderer = new ResponseRenderer();
        $controller = new ChangeLogController($responseRenderer, new Template(), $config);
        $controller($request);

        self::assertStringContainsString(
            '- <a target="_blank" rel="noopener noreferrer"'
            . ' href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/16418">'
            . 'issue #16418</a> Fix <a target="_blank" rel="noopener noreferrer"'
            . ' href="index.php?route=/url&lang=en&url=https://docs.phpmyadmin.net/en/latest/faq.html#faq1-44">'
            . 'FAQ 1.44</a> about manually removing vendor folders',
            $responseRenderer->getHTMLResult(),
        );
    }

    public function testWithInvalidFile(): void
    {
        $config = self::createStub(Config::class);
        $config->method('getChangeLogFilePath')->willReturn(TEST_PATH . 'tests/test_data/changelog/InvalidChangeLog');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $responseRenderer = new ResponseRenderer();
        $controller = new ChangeLogController($responseRenderer, new Template(), $config);
        $controller($request);

        self::assertSame('', $responseRenderer->getHTMLResult());
        self::assertSame(
            'The ' . TEST_PATH . 'tests/test_data/changelog/InvalidChangeLog file is not available on this system,'
            . ' please visit <a href="https://www.phpmyadmin.net/">phpmyadmin.net</a> for more information.',
            self::getActualOutputForAssertion(),
        );
    }
}
