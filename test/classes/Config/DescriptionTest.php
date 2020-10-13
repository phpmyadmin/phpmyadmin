<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\Descriptions;
use PhpMyAdmin\Tests\AbstractTestCase;
use function in_array;

class DescriptionTest extends AbstractTestCase
{
    /**
     * Setup tests
     */
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
     * @return array
     */
    public function getValues(): array
    {
        return [
            [
                'AllowArbitraryServer',
                'name',
                'Allow login to any MySQL server',
            ],
            [
                'UnknownSetting',
                'name',
                'UnknownSetting',
            ],
            [
                'UnknownSetting',
                'desc',
                '',
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
        $nested = [
            'Export',
            'Import',
            'Schema',
            'DBG',
            'DefaultTransformations',
            'SQLQuery',
        ];

        $cfg = [];
        include ROOT_PATH . 'libraries/config.default.php';
        foreach ($cfg as $key => $value) {
            $this->assertGet($key);
            if ($key == 'Servers') {
                foreach ($value[1] as $item => $val) {
                    $this->assertGet($key . '/1/' . $item);
                    if ($item != 'AllowDeny') {
                        continue;
                    }

                    foreach ($val as $second => $val2) {
                        $this->assertNotNull($val2);
                        $this->assertGet($key . '/1/' . $item . '/' . $second);
                    }
                }
            } elseif (in_array($key, $nested)) {
                foreach ($value as $item => $val) {
                    $this->assertGet($key . '/' . $item);
                }
            }
        }
    }
}
