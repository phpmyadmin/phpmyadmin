<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\VersionInformation;

/**
 * @covers \PhpMyAdmin\Controllers\VersionCheckController
 * @runTestsInSeparateProcesses
 */
class VersionCheckControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
    }

    public function testWithLatestCompatibleVersion(): void
    {
        $_GET = [];
        $versionInfo = (object) [
            'date' => '2022-02-11',
            'version' => '5.1.3',
            'releases' => [
                (object) [
                    'date' => '2022-02-11',
                    'php_versions' => '>=7.1,<8.1',
                    'version' => '5.1.3',
                    'mysql_versions' => '>=5.5',
                ],
                (object) [
                    'date' => '2022-02-11',
                    'php_versions' => '>=5.5,<8.0',
                    'version' => '4.9.10',
                    'mysql_versions' => '>=5.5',
                ],
            ],
        ];

        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects($this->once())->method('getLatestVersion')->willReturn($versionInfo);
        $versionInformation->expects($this->once())->method('getLatestCompatibleVersion')
            ->with($this->equalTo($versionInfo->releases))
            ->willReturn(['version' => '5.1.3', 'date' => '2022-02-11']);

        (new VersionCheckController(
            new ResponseRenderer(),
            new Template(),
            $versionInformation,
        ))($this->createStub(ServerRequest::class));

        $output = $this->getActualOutputForAssertion();
        $this->assertTrue(isset($_GET['ajax_request']));
        $this->assertSame('{"version":"5.1.3","date":"2022-02-11"}', $output);
    }

    public function testWithoutLatestCompatibleVersion(): void
    {
        $_GET = [];
        $versionInfo = (object) [
            'date' => '2022-02-11',
            'version' => '5.1.3',
            'releases' => [
                (object) [
                    'date' => '2022-02-11',
                    'php_versions' => '>=7.1,<8.1',
                    'version' => '5.1.3',
                    'mysql_versions' => '>=5.5',
                ],
                (object) [
                    'date' => '2022-02-11',
                    'php_versions' => '>=5.5,<8.0',
                    'version' => '4.9.10',
                    'mysql_versions' => '>=5.5',
                ],
            ],
        ];

        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects($this->once())->method('getLatestVersion')->willReturn($versionInfo);
        $versionInformation->expects($this->once())->method('getLatestCompatibleVersion')
            ->with($this->equalTo($versionInfo->releases))
            ->willReturn(null);

        (new VersionCheckController(
            new ResponseRenderer(),
            new Template(),
            $versionInformation,
        ))($this->createStub(ServerRequest::class));

        $output = $this->getActualOutputForAssertion();
        $this->assertTrue(isset($_GET['ajax_request']));
        $this->assertSame('{"version":"","date":""}', $output);
    }

    public function testWithoutLatestVersion(): void
    {
        $_GET = [];

        $versionInformation = $this->createMock(VersionInformation::class);
        $versionInformation->expects($this->once())->method('getLatestVersion')->willReturn(null);
        $versionInformation->expects($this->never())->method('getLatestCompatibleVersion');

        (new VersionCheckController(
            new ResponseRenderer(),
            new Template(),
            $versionInformation,
        ))($this->createStub(ServerRequest::class));

        $output = $this->getActualOutputForAssertion();
        $this->assertTrue(isset($_GET['ajax_request']));
        $this->assertSame('[]', $output);
    }
}
