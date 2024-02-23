<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Release;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\VersionInformation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[CoversClass(VersionCheckController::class)]
#[RunTestsInSeparateProcesses]
class VersionCheckControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testWithLatestCompatibleVersion(): void
    {
        $_GET = [];
        $versionInfo = [
            new Release('5.1.3', '2022-02-11', '>=7.1,<8.1', '>=5.5'),
            new Release('4.9.10', '2022-02-11', '>=5.5,<8.0', '>=5.5'),
        ];

        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects(self::once())->method('getLatestVersions')->willReturn($versionInfo);
        $versionInformation->expects(self::once())->method('getLatestCompatibleVersion')
            ->with(self::equalTo($versionInfo))
            ->willReturn($versionInfo[0]);

        (new VersionCheckController(
            new ResponseRenderer(),
            new Template(),
            $versionInformation,
        ))(self::createStub(ServerRequest::class));

        $output = $this->getActualOutputForAssertion();
        self::assertTrue(isset($_GET['ajax_request']));
        self::assertSame('{"version":"5.1.3","date":"2022-02-11"}', $output);
    }

    public function testWithoutLatestCompatibleVersion(): void
    {
        $_GET = [];
        $versionInfo = [
            new Release('5.1.3', '2022-02-11', '>=7.1,<8.1', '>=5.5'),
            new Release('4.9.10', '2022-02-11', '>=5.5,<8.0', '>=5.5'),
        ];

        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects(self::once())->method('getLatestVersions')->willReturn($versionInfo);
        $versionInformation->expects(self::once())->method('getLatestCompatibleVersion')
            ->with(self::equalTo($versionInfo))
            ->willReturn(null);

        (new VersionCheckController(
            new ResponseRenderer(),
            new Template(),
            $versionInformation,
        ))(self::createStub(ServerRequest::class));

        $output = $this->getActualOutputForAssertion();
        self::assertTrue(isset($_GET['ajax_request']));
        self::assertSame('{"version":"","date":""}', $output);
    }

    public function testWithoutLatestVersion(): void
    {
        $_GET = [];

        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects(self::once())->method('getLatestVersions')->willReturn(null);
        $versionInformation->expects(self::never())->method('getLatestCompatibleVersion');

        (new VersionCheckController(
            new ResponseRenderer(),
            new Template(),
            $versionInformation,
        ))(self::createStub(ServerRequest::class));

        $output = $this->getActualOutputForAssertion();
        self::assertTrue(isset($_GET['ajax_request']));
        self::assertSame('[]', $output);
    }
}
