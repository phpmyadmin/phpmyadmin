<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\Descriptions;
use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\Tests\AbstractTestCase;

use function array_keys;
use function in_array;

/**
 * @covers \PhpMyAdmin\Config\Descriptions
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DescriptionTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        parent::setGlobalConfig();
    }

    /**
     * @param string $item     item
     * @param string $type     type
     * @param string $expected expected result
     *
     * @dataProvider getValues
     */
    public function testGet(string $item, string $type, string $expected): void
    {
        $this->assertEquals($expected, Descriptions::get($item, $type));
    }

    /**
     * @return array<string, string[]>
     * @psalm-return array<string, array{non-empty-string, 'name'|'desc'|'cmt', string}>
     */
    public static function getValues(): array
    {
        return [
            'valid name' => ['AllowArbitraryServer', 'name', 'Allow login to any MySQL server'],
            'valid description' => [
                'AllowArbitraryServer',
                'desc',
                'If enabled, user can enter any MySQL server in login form for cookie auth.',
            ],
            'valid comment' => ['MaxDbList', 'cmt', 'Users cannot set a higher value'],
            'invalid name' => ['UnknownSetting', 'name', 'UnknownSetting'],
            'invalid description' => ['UnknownSetting', 'desc', ''],
            'invalid comment' => ['UnknownSetting', 'cmt', ''],
            'server number' => ['Servers/1/DisableIS', 'name', 'Disable use of INFORMATION_SCHEMA'],
            'composed name' => ['Import/format', 'name', 'Format of imported file'],
            'bb code' => [
                'NavigationLogoLinkWindow',
                'desc',
                'Open the linked page in the main window (<code>main</code>) or in a new one (<code>new</code>).',
            ],
        ];
    }

    /**
     * Assertion for getting description key
     *
     * @param string $key key
     */
    public function assertGet(string $key): void
    {
        $this->assertNotNull(Descriptions::get($key, 'name'));
        $this->assertNotNull(Descriptions::get($key, 'desc'));
        $this->assertNotNull(Descriptions::get($key, 'cmt'));
    }

    /**
     * Test getting all names for configurations
     */
    public function testAll(): void
    {
        $nested = ['Export', 'Import', 'Schema', 'DBG', 'DefaultTransformations', 'SQLQuery'];

        $settings = new Settings([]);
        $cfg = $settings->asArray();

        foreach ($cfg as $key => $value) {
            $this->assertGet($key);
            if ($key === 'Servers') {
                $this->assertIsArray($value);
                $this->assertIsArray($value[1]);
                foreach ($value[1] as $item => $val) {
                    $this->assertGet($key . '/1/' . $item);
                    if ($item !== 'AllowDeny') {
                        continue;
                    }

                    foreach ($val as $second => $val2) {
                        $this->assertNotNull($val2);
                        $this->assertGet($key . '/1/' . $item . '/' . $second);
                    }
                }
            } elseif (in_array($key, $nested)) {
                $this->assertIsArray($value);
                foreach (array_keys($value) as $item) {
                    $this->assertGet($key . '/' . $item);
                }
            }
        }
    }
}
