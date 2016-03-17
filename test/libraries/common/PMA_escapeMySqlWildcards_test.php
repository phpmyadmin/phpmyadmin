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


/**
 * Test for MySQL Wildcards escaping/unescaping
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_EscapeMySqlWildcardsTest extends PHPUnit_Framework_TestCase
{
    /**
     * data provider for testEscape and testUnEscape
     *
     * @return array
     */
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
     * PMA\libraries\Util::escapeMysqlWildcards tests
     *
     * @param string $a String to escape
     * @param string $b Expected value
     *
     * @return void
     *
     * @dataProvider escapeDataProvider
     */
    public function testEscape($a, $b)
    {
        $this->assertEquals(
            $a, PMA\libraries\Util::escapeMysqlWildcards($b)
        );
    }

    /**
     * PMA\libraries\Util::unescapeMysqlWildcards tests
     *
     * @param string $a String to escape
     * @param string $b Expected value
     *
     * @return void
     *
     * @dataProvider escapeDataProvider
     */
    public function testUnEscape($a, $b)
    {
        $this->assertEquals(
            $b, PMA\libraries\Util::unescapeMysqlWildcards($a)
        );
    }
}
