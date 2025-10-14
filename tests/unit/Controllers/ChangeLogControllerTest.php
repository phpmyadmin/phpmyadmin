<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[CoversClass(ChangeLogController::class)]
final class ChangeLogControllerTest extends AbstractTestCase
{
    public function testWithValidFile(): void
    {
        $this->assertChangelogOutputIsValid(__DIR__ . '/../../test_data/changelog/CHANGELOG-5.2.md');
    }

    #[RequiresPhpExtension('zlib')]
    public function testWithCompressedFile(): void
    {
        $this->assertChangelogOutputIsValid(__DIR__ . '/../../test_data/changelog/CHANGELOG-5.2.md.gz');
    }

    private function assertChangelogOutputIsValid(string $changelogPath): void
    {
        $config = self::createStub(Config::class);
        $config->method('getChangeLogFilePath')->willReturn($changelogPath);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/');

        $responseRenderer = new ResponseRenderer();
        $template = new Template();
        $controller = new ChangeLogController($responseRenderer, $config, ResponseFactory::create(), $template);
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));

        // phpcs:disable Generic.Files.LineLength.TooLong
        $changelog = <<<'HTML'
            <h1>Changes in phpMyAdmin 5.2</h1>

            All notable changes of the phpMyAdmin 5.2 release series are documented in this file following the Keep a Changelog format.

            <h2><a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/compare/RELEASE_5_2_1...QA_5_2">5.2.2</a> (not yet released)</h2>

            <h3>Fixed</h3>

            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17522">#17522</a>: Fix case where the routes cache file is invalid

            <h3>Security</h3>

            * Upgrade slim/psr7 to 1.4.1 for <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://www.cve.org/CVERecord?id=CVE-2023-30536">CVE-2023-30536</a> - GHSA-q2qj-628g-vhfw

            <h2><a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/compare/RELEASE_5_2_0...RELEASE_5_2_1">5.2.1</a> 2023-02-07</h2>

            <h3>Added</h3>

            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17519">#17519</a>: Fix Export pages not working in certain conditions
            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17496">#17496</a>: Fix error in table operation page when partitions are broken

            <h3>Changed</h3>

            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17519">#17519</a>: Fix Export pages not working in certain conditions
            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17496">#17496</a>: Fix error in table operation page when partitions are broken

            <h3>Deprecated</h3>

            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17519">#17519</a>: Fix Export pages not working in certain conditions
            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17496">#17496</a>: Fix error in table operation page when partitions are broken

            <h3>Removed</h3>

            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17519">#17519</a>: Fix Export pages not working in certain conditions
            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17496">#17496</a>: Fix error in table operation page when partitions are broken

            <h3>Fixed</h3>

            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17519">#17519</a>: Fix Export pages not working in certain conditions
            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/17496">#17496</a>: Fix error in table operation page when partitions are broken
            * <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://github.com/phpmyadmin/phpmyadmin/issues/16418">#16418</a>: Fix <a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://docs.phpmyadmin.net/en/latest/faq.html#faq1-44">FAQ 1.44</a> about manually removing vendor folders

            <h3>Security</h3>

            * Fix an XSS attack through the drag-and-drop upload feature (<a target="_blank" rel="noopener noreferrer" href="index.php?route=/url&lang=en&url=https://www.phpmyadmin.net/security/PMASA-2023-01/">PMASA-2023-01</a>)


            HTML;
        // phpcs:enable
        $expected = $template->render('changelog', ['changelog' => $changelog]);

        self::assertSame($expected, (string) $response->getBody());
    }

    public function testWithInvalidFile(): void
    {
        $config = self::createStub(Config::class);
        $config->method('getChangeLogFilePath')->willReturn(__DIR__ . '/../../test_data/changelog/InvalidChangeLog');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/');

        $responseRenderer = new ResponseRenderer();
        $controller = new ChangeLogController($responseRenderer, $config, ResponseFactory::create(), new Template());
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
        self::assertSame(
            'The InvalidChangeLog file is not available on this system, please visit'
            . ' <a href="index.php?route=/url&url=https%3A%2F%2Fwww.phpmyadmin.net%2F"'
            . ' rel="noopener noreferrer" target="_blank">phpmyadmin.net</a> for more information.',
            (string) $response->getBody(),
        );
    }
}
