<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\VersionInformation;
use stdClass;

class VersionInformationTest extends AbstractTestCase
{
    /** @var stdClass[] */
    private $releases;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setProxySettings();
        $this->releases = [];

        $release = new stdClass();
        $release->date = '2015-09-08';
        $release->php_versions = '>=5.3,<7.1';
        $release->version = '4.4.14.1';
        $release->mysql_versions = '>=5.5';
        $this->releases[] = $release;

        $release = new stdClass();
        $release->date = '2015-09-09';
        $release->php_versions = '>=5.3,<7.0';
        $release->version = '4.4.13.3';
        $release->mysql_versions = '>=5.5';
        $this->releases[] = $release;

        $release = new stdClass();
        $release->date = '2015-05-13';
        $release->php_versions = '>=5.2,<5.3';
        $release->version = '4.0.10.10';
        $release->mysql_versions = '>=5.0';
        $this->releases[] = $release;
    }

    /**
     * Test version checking
     *
     * @group large
     * @group network
     */
    public function testGetLatestVersion(): void
    {
        $GLOBALS['cfg']['ProxyUrl'] = PROXY_URL;
        $GLOBALS['cfg']['ProxyUser'] = PROXY_USER;
        $GLOBALS['cfg']['ProxyPass'] = PROXY_PASS;
        $GLOBALS['cfg']['VersionCheck'] = true;
        $versionInformation = new VersionInformation();
        $version = $versionInformation->getLatestVersion();
        $this->assertIsObject($version);
        $this->assertNotEmpty($version->version);
        $this->assertNotEmpty($version->date);
    }

    /**
     * Test version to int conversion.
     *
     * @param string $version Version string
     * @param int    $numeric Integer matching version
     *
     * @dataProvider dataVersions
     */
    public function testVersionToInt(string $version, int $numeric): void
    {
        $versionInformation = new VersionInformation();
        $this->assertEquals(
            $numeric,
            $versionInformation->versionToInt($version)
        );
    }

    /**
     * Data provider for version parsing
     */
    public function dataVersions(): array
    {
        return [
            [
                '1.0.0',
                1000050,
            ],
            [
                '2.0.0.2-dev',
                2000002,
            ],
            [
                '3.4.2.1',
                3040251,
            ],
            [
                '3.4.2-dev3',
                3040203,
            ],
            [
                '3.4.2-dev',
                3040200,
            ],
            [
                '3.4.2-pl',
                3040260,
            ],
            [
                '3.4.2-pl3',
                3040263,
            ],
            [
                '4.4.2-rc22',
                4040252,
            ],
            [
                '4.4.2-rc',
                4040230,
            ],
            [
                '4.4.22-beta22',
                4042242,
            ],
            [
                '4.4.22-beta',
                4042220,
            ],
            [
                '4.4.21-alpha22',
                4042132,
            ],
            [
                '4.4.20-alpha',
                4042010,
            ],
            [
                '4.40.20-alpha-dev',
                4402010,
            ],
            [
                '4.4a',
                4000050,
            ],
            [
                '4.4.4-test',
                4040400,
            ],
            [
                '4.1.0',
                4010050,
            ],
            [
                '4.0.1.3',
                4000153,
            ],
            [
                '4.1-dev',
                4010000,
            ],
        ];
    }

    /**
     * Tests getLatestCompatibleVersion() when there is only one server confgiured
     */
    public function testGetLatestCompatibleVersionWithSingleServer(): void
    {
        $GLOBALS['cfg']['Servers'] = [
            [],
        ];

        $mockVersionInfo = $this->getMockBuilder(VersionInformation::class)
            ->setMethods(['evaluateVersionCondition'])
            ->getMock();

        $mockVersionInfo->expects($this->at(0))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.3')
            ->will($this->returnValue(true));

        $mockVersionInfo->expects($this->at(1))
            ->method('evaluateVersionCondition')
            ->with('PHP', '<7.1')
            ->will($this->returnValue(true));

        $mockVersionInfo->expects($this->at(2))
            ->method('evaluateVersionCondition')
            ->with('MySQL', '>=5.5')
            ->will($this->returnValue(true));

        $compatible = $mockVersionInfo->getLatestCompatibleVersion($this->releases);
        $this->assertIsArray($compatible);
        $this->assertEquals('4.4.14.1', $compatible['version']);
    }

    /**
     * Tests getLatestCompatibleVersion() when there are multiple servers configured
     */
    public function testGetLatestCompatibleVersionWithMultipleServers(): void
    {
        $GLOBALS['cfg']['Servers'] = [
            [],
            [],
        ];

        $mockVersionInfo = $this->getMockBuilder(VersionInformation::class)
            ->setMethods(['evaluateVersionCondition'])
            ->getMock();

        $mockVersionInfo->expects($this->at(0))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.3')
            ->will($this->returnValue(true));

        $mockVersionInfo->expects($this->at(1))
            ->method('evaluateVersionCondition')
            ->with('PHP', '<7.1')
            ->will($this->returnValue(true));

        $compatible = $mockVersionInfo->getLatestCompatibleVersion($this->releases);
        $this->assertIsArray($compatible);
        $this->assertEquals('4.4.14.1', $compatible['version']);
    }

    /**
     * Tests getLatestCompatibleVersion() with an old PHP version
     */
    public function testGetLatestCompatibleVersionWithOldPHPVersion(): void
    {
        $GLOBALS['cfg']['Servers'] = [
            [],
            [],
        ];

        $mockVersionInfo = $this->getMockBuilder(VersionInformation::class)
            ->setMethods(['evaluateVersionCondition'])
            ->getMock();

        $mockVersionInfo->expects($this->at(0))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.3')
            ->will($this->returnValue(false));

        $mockVersionInfo->expects($this->at(1))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.3')
            ->will($this->returnValue(false));

        $mockVersionInfo->expects($this->at(2))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.2')
            ->will($this->returnValue(true));

        $mockVersionInfo->expects($this->at(3))
            ->method('evaluateVersionCondition')
            ->with('PHP', '<5.3')
            ->will($this->returnValue(true));

        $compatible = $mockVersionInfo->getLatestCompatibleVersion($this->releases);
        $this->assertIsArray($compatible);
        $this->assertEquals('4.0.10.10', $compatible['version']);
    }

    /**
     * Tests getLatestCompatibleVersion() with an new PHP version
     *
     * @param array[]     $versions           The versions to use
     * @param array[]     $conditions         The conditions that will be executed
     * @param string|null $matchedLastVersion The version that will be matched
     *
     * @dataProvider dataProviderVersionConditions
     */
    public function testGetLatestCompatibleVersionWithNewPHPVersion(
        array $versions,
        array $conditions,
        ?string $matchedLastVersion
    ): void {
        $GLOBALS['cfg']['Servers'] = [];

        $mockVersionInfo = $this->getMockBuilder(VersionInformation::class)
            ->setMethods(['evaluateVersionCondition'])
            ->getMock();

        $i = 0;
        foreach ($conditions as $conditionArray) {
            [
                $condition,
                $returnValue,
            ] = $conditionArray;
            $mockVersionInfo->expects($this->at($i))
                ->method('evaluateVersionCondition')
                ->with('PHP', $condition)
                ->will($this->returnValue($returnValue));
            $i++;
        }
        /** @var VersionInformation $mockVersionInfo */
        $compatible = $mockVersionInfo->getLatestCompatibleVersion($versions);
        $this->assertEquals($matchedLastVersion, $compatible['version'] ?? null);
    }

    /**
     * Provider for testGetLatestCompatibleVersionWithNewPHPVersion
     * Returns the conditions to be used for mocks
     *
     * @return array[]
     */
    public function dataProviderVersionConditions(): array
    {
        return [
            [
                [
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=5.5,<8.0',
                        'version' => '4.9.3',
                        'mysql_versions' => '>=5.5',
                    ]),
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=7.1,<8.0',
                        'version' => '5.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                ],
                [
                    [
                        '>=5.5',
                        true,
                    ],
                    [
                        '<8.0',
                        true,
                    ],
                    [
                        '>=7.1',
                        true,
                    ],
                    [
                        '<8.0',
                        false,
                    ],
                ],
                '4.9.3',
            ],
            [
                [
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=5.5,<7.0',
                        'version' => '6.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=7.1,<8.0',
                        'version' => '5.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                ],
                [
                    [
                        '>=5.5',
                        true,
                    ],
                    [
                        '<7.0',
                        true,
                    ],
                    [
                        '>=7.1',
                        false,
                    ],
                ],
                '6.0.0',
            ],
            [
                [
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=5.5,<7.0',
                        'version' => '6.0.0-rc1',
                        'mysql_versions' => '>=5.5',
                    ]),
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=7.1,<8.0',
                        'version' => '6.0.0-rc2',
                        'mysql_versions' => '>=5.5',
                    ]),
                ],
                [
                    [
                        '>=5.5',
                        true,
                    ],
                    [
                        '<7.0',
                        true,
                    ],
                    [
                        '>=7.1',
                        false,
                    ],
                ],
                '6.0.0-rc1',
            ],
            [
                [
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=5.5,<7.0',
                        'version' => '6.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=7.1,<8.0',
                        'version' => '5.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                ],
                [
                    [
                        '>=5.5',
                        false,
                    ],
                    [
                        '>=7.1',
                        true,
                    ],
                    [
                        '<8.0',
                        false,
                    ],
                ],
                null,
            ],
            [
                [
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=5.5,<7.0',
                        'version' => '6.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=7.1,<8.0',
                        'version' => '5.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                ],
                [
                    [
                        '>=5.5',
                        false,
                    ],
                    [
                        '>=7.1',
                        true,
                    ],
                    [
                        '<8.0',
                        true,
                    ],
                ],
                '5.0.0',
            ],
            [
                [
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=5.5,<8.0',
                        'version' => '4.9.3',
                        'mysql_versions' => '>=5.5',
                    ]),
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=7.1,<8.0',
                        'version' => '5.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                ],
                [
                    [
                        '>=5.5',
                        true,
                    ],
                    [
                        '<8.0',
                        true,
                    ],
                    [
                        '>=7.1',
                        true,
                    ],
                    [
                        '<8.0',
                        true,
                    ],
                ],
                '5.0.0',
            ],
            [
                [
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=7.1,<8.0',
                        'version' => '5.0.0',
                        'mysql_versions' => '>=5.5',
                    ]),
                    ((object) [
                        'date' => '2019-12-26',
                        'php_versions' => '>=5.5,<8.0',
                        'version' => '4.9.3',
                        'mysql_versions' => '>=5.5',
                    ]),
                ],
                [
                    [
                        '>=7.1',
                        true,
                    ],
                    [
                        '<8.0',
                        true,
                    ],
                    [
                        '>=5.5',
                        true,
                    ],
                    [
                        '<8.0',
                        true,
                    ],
                ],
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
            ->setMethods(['getPHPVersion'])
            ->getMock();

        $mockVersionInfo->expects($this->any())
            ->method('getPHPVersion')
            ->will($this->returnValue('5.2.4'));

        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '<=5.3'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '<5.3'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '>=5.2'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '>5.2'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '!=5.3'));

        $this->assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '<=5.2'));
        $this->assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '<5.2'));
        $this->assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '>=7.0'));
        $this->assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '>7.0'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '!=5.2'));
    }
}
