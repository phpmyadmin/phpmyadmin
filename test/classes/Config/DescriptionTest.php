<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormDisplay class in config folder
 *
 * @package PhpMyAdmin-test
 */
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
    public function setUp()
    {
        $GLOBALS['PMA_Config'] = new Config();
    }

    /**
     * @dataProvider getValues
     */
    public function testGet($item, $type, $expected)
    {
        $this->assertEquals($expected, Descriptions::get($item, $type));
    }

    public function getValues()
    {
        return array(
            array(
                'AllowArbitraryServer',
                'name',
                'Allow login to any MySQL server',
            ),
            array(
                'UnknownSetting',
                'name',
                'UnknownSetting',
            ),
            array(
                'UnknownSetting',
                'desc',
                '',
            ),
        );
    }

    /**
     * Assertion for getting description key
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
     * Test getting all names for configuratons
     */
    public function testAll()
    {
        $nested = array(
            'Export',
            'Import',
            'Schema',
            'DBG',
            'DefaultTransformations',
            'SQLQuery',
        );

        $cfg = array();
        include './libraries/config.default.php';
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
