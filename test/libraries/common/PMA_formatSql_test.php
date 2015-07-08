<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::formatSql from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

/**
 ** Test for PMA_Util::formatSql from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_FormatSql_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for formatSql
     *
     * @return void
     */
    function testFormatSQL()
    {

        $this->assertEquals(
            '<code class="sql"><pre>' . "\n"
            . 'SELECT 1 &lt; 2' . "\n"
            . '</pre></code>',
            PMA_Util::formatSql('SELECT 1 < 2')
        );
    }

    /**
     * Test for formatSql
     *
     * @return void
     */
    function testFormatSQLTruncate()
    {
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 6;

        $this->assertEquals(
            '<code class="sql"><pre>' . "\n"
            . 'SELECT[...]' . "\n"
            . '</pre></code>',
            PMA_Util::formatSql('SELECT 1 < 2', true)
        );
    }
}
