<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for MySQL Wildcards escaping/unescaping
 *
 * @author Michal Biniek <michal@bystrzyca.pl>
 * @package phpMyAdmin-test
 * @version $Id$
 */

/**
 * Tests core.
 */
require_once 'PHPUnit/Framework.php';

/**
 * Include to test.
 */
require_once './libraries/common.lib.php';

/**
 * Test MySQL escaping.
 *
 */
class PMA_escapeMySqlWildcards_test extends PHPUnit_Framework_TestCase
{

    /** 
     * PMA_escape_mysql_wildcards tests 
     */

    public function testEscape_1()
    {
        $this->assertEquals('\_test', PMA_escape_mysql_wildcards('_test'));
    }

	public function testEscape_2()
    {
        $this->assertEquals('\_\\', PMA_escape_mysql_wildcards('_\\'));
    }

	public function testEscape_3()
    {
        $this->assertEquals('\\_\%', PMA_escape_mysql_wildcards('_%'));
    }

	public function testEscape_4()
    {
        $this->assertEquals('\\\_', PMA_escape_mysql_wildcards('\_'));
    }

	public function testEscape_5()
    {
        $this->assertEquals('\\\_\\\%', PMA_escape_mysql_wildcards('\_\%'));
    }

	/** 
	 * PMA_unescape_mysql_wildcards tests 
	 */

	public function testUnEscape_1()
    {
        $this->assertEquals('_test', PMA_unescape_mysql_wildcards('\_test'));
    }

	public function testUnEscape_2()
    {
        $this->assertEquals('_%__%', PMA_unescape_mysql_wildcards('\_\\%\_\_\%'));
    }

	public function testUnEscape_3()
    {
        $this->assertEquals('\_', PMA_unescape_mysql_wildcards('\\\_'));
    }

	public function testUnEscape_4()
    {
        $this->assertEquals('%_', PMA_unescape_mysql_wildcards('%\_'));
    }
	
	public function testUnEscape_5()
    {
        $this->assertEquals('\%\_', PMA_unescape_mysql_wildcards('\\\%\\\_'));
    }
}
?>
