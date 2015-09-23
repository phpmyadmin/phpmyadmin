<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Config Functions
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/config/config_functions.lib.php';
require_once 'libraries/sanitizing.lib.php';

/**
 * Tests for Config Functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_Config_Functions_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_lang
     *
     * @return void
     * @test
     */
    public function testPMALang()
    {
        $this->assertEquals(
            "&lt;a attr='value'&gt;test&lt;/a&gt;",
            PMA_lang("<a attr='value'>test</a>")
        );

        $GLOBALS["strConfiglangKeyFooBar"] = "<a attr='value'>[em]test[/em]</a>";

        $this->assertEquals(
            "&lt;a attr='value'&gt;<em>test</em>&lt;/a&gt;",
            PMA_lang("langKeyFooBar")
        );

        $this->assertEquals(
            "1988-08-01",
            PMA_lang("%04d-%02d-%02d", "1988", "8", "1")
        );
    }

    /**
     * Test for PMA_langName
     *
     * @return void
     * @test
     */
    public function testLangName()
    {
        $canonicalPath = "Servers/1/2test";

        $this->assertEquals(
            "Servers_2test_name",
            PMA_langName($canonicalPath)
        );

        $this->assertEquals(
            "returnsDefault",
            PMA_langName($canonicalPath, "name", "returnsDefault")
        );

        $GLOBALS["strConfigServers_2test_name"] = "<a>msg</a>";

        $this->assertEquals(
            "<a>msg</a>",
            PMA_langName($canonicalPath)
        );

        $GLOBALS["strConfigServers_2test_desc"] = "<a>msg</a>";

        $this->assertEquals(
            "&lt;a&gt;msg&lt;/a&gt;",
            PMA_langName($canonicalPath, "desc")
        );

    }

}
