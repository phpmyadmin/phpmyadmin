<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Release;
use PhpMyAdmin\VersionInformation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Large;

#[CoversClass(VersionInformation::class)]
#[CoversClass(Release::class)]
#[Large]
class VersionInformationTest extends AbstractTestCase
{
    /** @var Release[] */
    private array $releases;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setProxySettings();

        $this->releases = [];

        $release = new Release('4.4.14.1', '2015-09-08', '>=5.3,<7.1', '>=5.5');
        $this->releases[] = $release;

        $release = new Release('4.4.13.3', '2015-09-09', '>=5.3,<7.0', '>=5.5');
        $this->releases[] = $release;

        $release = new Release('4.0.10.10', '2015-05-13', '>=5.2,<5.3', '>=5.0');
        $this->releases[] = $release;
    }

    /**
     * Test version checking
     */
    #[Group('network')]
    public function testGetLatestVersion(): void
    {
        $this->setProxySettings();
        Config::getInstance()->settings['VersionCheck'] = true;
        unset($_SESSION['cache']['version_check']);
        $versionInformation = new VersionInformation();
        $version = $versionInformation->getLatestVersions();
        self::assertIsArray($version);
        self::assertNotEmpty($version);
    }

    /**
     * Test version to int conversion.
     *
     * @param string $version Version string
     * @param int    $numeric Integer matching version
     */
    #[DataProvider('dataVersions')]
    public function testVersionToInt(string $version, int $numeric): void
    {
        $versionInformation = new VersionInformation();
        self::assertSame(
            $numeric,
            $versionInformation->versionToInt($version),
        );
    }

    /**
     * Data provider for version parsing
     *
     * @return array<int, array{string, int}>
     */
    public static function dataVersions(): array
    {
        return [
            ['1.0.0', 1000050],
            ['2.0.0.2-dev', 2000002],
            ['3.4.2.1', 3040251],
            ['3.4.2-dev3', 3040203],
            ['3.4.2-dev', 3040200],
            ['3.4.2-pl', 3040260],
            ['3.4.2-pl3', 3040263],
            ['4.4.2-rc22', 4040252],
            ['4.4.2-rc', 4040230],
            ['4.4.22-beta22', 4042242],
            ['4.4.22-beta', 4042220],
            ['4.4.21-alpha22', 4042132],
            ['4.4.20-alpha', 4042010],
            ['4.40.20-alpha-dev', 4402010],
            ['4.4a', 4000050],
            ['4.4.4-test', 4040400],
            ['4.1.0', 4010050],
            ['4.0.1.3', 4000153],
            ['4.1-dev', 4010000],
        ];
    }

    /**
     * Tests getLatestCompatibleVersion() when there is only one server configured
     */
    public function testGetLatestCompatibleVersionWithSingleServer(): void
    {
        Config::getInstance()->settings['Servers'] = [[]];

        $mockVersionInfo = $this->createPartialMock(VersionInformation::class, ['getPHPVersion', 'getMySQLVersion']);
        $mockVersionInfo->expects(self::exactly(6))->method('getPHPVersion')->willReturn('5.6.0');
        $mockVersionInfo->expects(self::exactly(2))->method('getMySQLVersion')->willReturn('5.5.0');

        $compatible = $mockVersionInfo->getLatestCompatibleVersion($this->releases);
        self::assertInstanceOf(Release::class, $compatible);
        self::assertSame('4.4.14.1', $compatible->version);
    }

    /**
     * Tests getLatestCompatibleVersion() when there are multiple servers configured
     */
    public function testGetLatestCompatibleVersionWithMultipleServers(): void
    {
        Config::getInstance()->settings['Servers'] = [[], []];

        $mockVersionInfo = $this->createPartialMock(VersionInformation::class, ['getPHPVersion', 'getMySQLVersion']);
        $mockVersionInfo->expects(self::exactly(6))->method('getPHPVersion')->willReturn('5.6.0');
        $mockVersionInfo->expects(self::never())->method('getMySQLVersion');

        $compatible = $mockVersionInfo->getLatestCompatibleVersion($this->releases);
        self::assertInstanceOf(Release::class, $compatible);
        self::assertSame('4.4.14.1', $compatible->version);
    }

    /**
     * Tests getLatestCompatibleVersion() with an old PHP version
     */
    public function testGetLatestCompatibleVersionWithOldPHPVersion(): void
    {
        Config::getInstance()->settings['Servers'] = [[], []];

        $mockVersionInfo = $this->createPartialMock(VersionInformation::class, ['getPHPVersion', 'getMySQLVersion']);
        $mockVersionInfo->expects(self::exactly(4))->method('getPHPVersion')->willReturn('5.2.1');
        $mockVersionInfo->expects(self::never())->method('getMySQLVersion');

        $compatible = $mockVersionInfo->getLatestCompatibleVersion($this->releases);
        self::assertInstanceOf(Release::class, $compatible);
        self::assertSame('4.0.10.10', $compatible->version);
    }

    /**
     * Tests getLatestCompatibleVersion() with an new PHP version
     *
     * @param list<Release>      $versions           The versions to use
     * @param array{int, string} $conditions         The conditions that will be executed
     * @param string|null        $matchedLastVersion The version that will be matched
     */
    #[DataProvider('dataProviderVersionConditions')]
    public function testGetLatestCompatibleVersionWithNewPHPVersion(
        array $versions,
        array $conditions,
        string|null $matchedLastVersion,
    ): void {
        Config::getInstance()->settings['Servers'] = [];

        $mockVersionInfo = $this->createPartialMock(VersionInformation::class, ['getPHPVersion', 'getMySQLVersion']);
        $mockVersionInfo->expects(self::exactly($conditions[0]))->method('getPHPVersion')->willReturn($conditions[1]);
        $mockVersionInfo->expects(self::never())->method('getMySQLVersion');

        $compatible = $mockVersionInfo->getLatestCompatibleVersion($versions);
        self::assertSame($matchedLastVersion, $compatible->version ?? null);
    }

    /**
     * Provider for testGetLatestCompatibleVersionWithNewPHPVersion
     * Returns the conditions to be used for mocks
     *
     * @return list<array{list<Release>, array{int, string}, string|null}>
     */
    public static function dataProviderVersionConditions(): array
    {
        return [
            [
                [
                    new Release('4.9.3', '2019-12-26', '>=5.5,<8.0', '>=5.5'),
                    new Release('5.0.0', '2019-12-26', '>=7.1,<8.0', '>=5.5'),
                ],
                [3, '7.0.0'],
                '4.9.3',
            ],
            [
                [
                    new Release('6.0.0', '2019-12-26', '>=5.5,<7.0', '>=5.5'),
                    new Release('5.0.0', '2019-12-26', '>=7.1,<8.0', '>=5.5'),
                ],
                [3, '5.6.0'],
                '6.0.0',
            ],
            [
                [
                    new Release('6.0.0-rc1', '2019-12-26', '>=5.5,<7.0', '>=5.5'),
                    new Release('6.0.0-rc2', '2019-12-26', '>=7.1,<8.0', '>=5.5'),
                ],
                [3, '5.6.0'],
                '6.0.0-rc1',
            ],
            [
                [
                    new Release('6.0.0', '2019-12-26', '>=5.5,<7.0', '>=5.5'),
                    new Release('5.0.0', '2019-12-26', '>=7.1,<8.0', '>=5.5'),
                ],
                [3, '7.0.0'],
                null,
            ],
            [
                [
                    new Release('6.0.0', '2019-12-26', '>=5.5,<7.0', '>=5.5'),
                    new Release('5.0.0', '2019-12-26', '>=7.1,<8.0', '>=5.5'),
                ],
                [4, '7.1.0'],
                '5.0.0',
            ],
            [
                [
                    new Release('4.9.3', '2019-12-26', '>=5.5,<8.0', '>=5.5'),
                    new Release('5.0.0', '2019-12-26', '>=7.1,<8.0', '>=5.5'),
                ],
                [4, '7.1.0'],
                '5.0.0',
            ],
            [
                [
                    new Release('5.0.0', '2019-12-26', '>=7.1,<8.0', '>=5.5'),
                    new Release('4.9.3', '2019-12-26', '>=5.5,<8.0', '>=5.5'),
                ],
                [4, '7.2.0'],
                '5.0.0',
            ],
        ];
    }

    /**
     * Tests evaluateVersionCondition() method
     */
    public function testEvaluateVersionCondition(): void
    {
        $mockVersionInfo = $this->getMockBuilder(VersionInformation::class)
            ->onlyMethods(['getPHPVersion'])
            ->getMock();

        $mockVersionInfo->expects(self::any())
            ->method('getPHPVersion')
            ->willReturn('5.2.4');

        self::assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '<=5.3'));
        self::assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '<5.3'));
        self::assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '>=5.2'));
        self::assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '>5.2'));
        self::assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '!=5.3'));

        self::assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '<=5.2'));
        self::assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '<5.2'));
        self::assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '>=7.0'));
        self::assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '>7.0'));
        self::assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '!=5.2'));
    }
}
