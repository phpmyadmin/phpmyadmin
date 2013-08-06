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
require_once 'libraries/sqlparser.lib.php';

class PMA_FormatSql_Test extends PHPUnit_Framework_TestCase
{
    function testFormatSQLfmTypeText()
    {

        $this->assertEquals(
            '<span class="inner_sql"><pre>' . "\n"
            . 'SELECT 1 &lt; 2' . "\n"
            . '</pre></span>',
            PMA_Util::formatSql('SELECT 1 < 2')
        );
    }
}
