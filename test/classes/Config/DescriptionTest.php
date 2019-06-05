<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormDisplay class in config folder
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Descriptions;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PMA_FormDisplay class
 *
 * @package PhpMyAdmin-test
 */
class DescriptionTest extends PmaTestCase
{
    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
    }

    /**
     * @param string $item     item
     * @param string $type     type
     * @param string $expected expected result
     *
     * @return void
     *
     * @dataProvider getValues
     */
    public function testGet($item, $type, $expected): void
    {
        $this->assertEquals($expected, Descriptions::get($item, $type));
    }

    /**
     * @return array
     */
    public function getValues()
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
     *
     * @return void
     */
    public function assertGet($key)
    {
        $this->assertNotNull(Descriptions::get($key, 'name'));
        $this->assertNotNull(Descriptions::get($key, 'desc'));
        $this->assertNotNull(Descriptions::get($key, 'cmt'));
    }

    /**
     * Test getting all names for configurations
     *
     * @return void
     */
    public function testAll()
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
                    if ($item == 'AllowDeny') {
                        foreach ($val as $second => $val2) {
                            $this->assertGet($key . '/1/' . $item . '/' . $second);
                        }
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
