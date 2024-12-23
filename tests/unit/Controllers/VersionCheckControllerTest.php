<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Release;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\VersionInformation;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(VersionCheckController::class)]
final class VersionCheckControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testWithLatestCompatibleVersion(): void
    {
        $versionInfo = [
            new Release('5.1.3', '2022-02-11', '>=7.1,<8.1', '>=5.5'),
            new Release('4.9.10', '2022-02-11', '>=5.5,<8.0', '>=5.5'),
        ];

        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects(self::once())->method('getLatestVersions')->willReturn($versionInfo);
        $versionInformation->expects(self::once())->method('getLatestCompatibleVersion')
            ->with(self::equalTo($versionInfo))
            ->willReturn($versionInfo[0]);

        $response = (new VersionCheckController(
            $versionInformation,
            ResponseFactory::create(),
        ))(self::createStub(ServerRequest::class));

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['application/json; charset=UTF-8'], $response->getHeader('Content-Type'));
        self::assertSame('{"version":"5.1.3","date":"2022-02-11"}', (string) $response->getBody());
    }

    public function testWithoutLatestCompatibleVersion(): void
    {
        $versionInfo = [
            new Release('5.1.3', '2022-02-11', '>=7.1,<8.1', '>=5.5'),
            new Release('4.9.10', '2022-02-11', '>=5.5,<8.0', '>=5.5'),
        ];

        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects(self::once())->method('getLatestVersions')->willReturn($versionInfo);
        $versionInformation->expects(self::once())->method('getLatestCompatibleVersion')
            ->with(self::equalTo($versionInfo))
            ->willReturn(null);

        $response = (new VersionCheckController(
            $versionInformation,
            ResponseFactory::create(),
        ))(self::createStub(ServerRequest::class));

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['application/json; charset=UTF-8'], $response->getHeader('Content-Type'));
        self::assertSame('{"version":"","date":""}', (string) $response->getBody());
    }

    public function testWithoutLatestVersion(): void
    {
        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects(self::once())->method('getLatestVersions')->willReturn(null);
        $versionInformation->expects(self::never())->method('getLatestCompatibleVersion');

        $response = (new VersionCheckController(
            $versionInformation,
            ResponseFactory::create(),
        ))(self::createStub(ServerRequest::class));

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['application/json; charset=UTF-8'], $response->getHeader('Content-Type'));
        self::assertSame('[]', (string) $response->getBody());
    }
}
