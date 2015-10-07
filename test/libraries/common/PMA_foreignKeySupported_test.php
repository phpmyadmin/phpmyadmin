<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for supporting foreign key
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */


/**
 * Test for supporting foreign key
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_IsForeignKeySupportedTest extends PHPUnit_Framework_TestCase
{
    /**
     * data provider for foreign key supported test
     *
     * @return array
     */
    public function foreignkeySupportedDataProvider()
    {
        return array(
            array('MyISAM', false),
            array('innodb', true),
            array('pBxT', true)
        );
    }

    /**
     * foreign key supported test
     *
     * @param string $a Engine
     * @param bool   $e Expected Value
     *
     * @return void
     *
     * @dataProvider foreignkeySupportedDataProvider
     */
    public function testForeignkeySupported($a, $e)
    {
        $this->assertEquals(
            $e, PMA\libraries\Util::isForeignKeySupported($a)
        );
    }
}
