<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormDisplay class in config folder
 *
 * @package PhpMyAdmin-test
 */

use PhpMyAdmin\Config\Descriptions;

require_once 'test/PMATestCase.php';

/**
 * Tests for PMA_FormDisplay class
 *
 * @package PhpMyAdmin-test
 */
class DescriptionTest extends PMATestCase
{
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
}
