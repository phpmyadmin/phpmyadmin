<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for MySQL Wildcards escaping/unescaping
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

class PMA_EscapeMySqlWildcardsTest extends PHPUnit_Framework_TestCase
{

    public function escapeDataProvider()
    {
        return array(
            array('\_test', '_test'),
            array('\_\\', '_\\'),
            array('\\_\%', '_%'),
            array('\\\_', '\_'),
            array('\\\_\\\%', '\_\%'),
            array('\_\\%\_\_\%', '_%__%'),
            array('\%\_', '%_'),
            array('\\\%\\\_', '\%\_')
        );
    }

    /**
     * PMA_Util::escapeMysqlWildcards tests
     * @dataProvider escapeDataProvider
     */

    public function testEscape($a, $b)
    {
        $this->assertEquals(
            $a, PMA_Util::escapeMysqlWildcards($b)
        );
    }

    /**
     * PMA_Util::unescapeMysqlWildcards tests
     * @dataProvider escapeDataProvider
     */

    public function testUnEscape($a, $b)
    {
        $this->assertEquals(
            $b, PMA_Util::unescapeMysqlWildcards($a)
        );
    }
}
?>
