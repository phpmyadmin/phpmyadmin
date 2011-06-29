<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for supporting foreign key
 *
 * @package phpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_foreignKeySupported_test extends PHPUnit_Framework_TestCase
{
    /**
     * data provider for foreign key supported test
     */
    public function foreignkeySupportedDataProvider() {
        return array(
            array('MyISAM', false),
            array('innodb', true),
            array('pBxT', true)
        );
    }

    /**
     * foreign key supported test
     * @dataProvider foreignkeySupportedDataProvider
     */
    public function testForeignkeySupported($a, $e) {
        $this->assertEquals($e, PMA_foreignkey_supported($a));
    }
}
?>
