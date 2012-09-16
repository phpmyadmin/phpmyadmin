<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getDbLink_test from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_getDbLink_test extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        global $cfg;
        include_once 'libraries/vendor_config.php';
        include 'libraries/config.default.php';
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 99;
    }

    function testGetDbLinkEmpty()
    {
        $GLOBALS['db'] = null;
        $this->assertEmpty(PMA_Util::getDbLink());
    }

    function testGetDbLinkNull()
    {
        global $cfg;
        $GLOBALS['db'] = 'test_db';
        $database = $GLOBALS['db'];
        $this->assertEquals(
            '<a href="' . $cfg['DefaultTabDatabase'] . '?db=' . $database
            . '&amp;server=99&amp;lang=en&amp;token=token" title="Jump to database &quot;'
            . htmlspecialchars($database) . '&quot;.">'
            . htmlspecialchars($database) . '</a>',
            PMA_Util::getDbLink()
        );
    }

    function testGetDbLink()
    {
        global $cfg;
        $database = 'test_database';
        $this->assertEquals(
            '<a href="' . $cfg['DefaultTabDatabase'] . '?db=' . $database
            . '&amp;server=99&amp;lang=en&amp;token=token" title="Jump to database &quot;'
            . htmlspecialchars($database) . '&quot;.">'
            . htmlspecialchars($database) . '</a>',
            PMA_Util::getDbLink($database)
        );
    }

    function testGetDbLinkWithSpecialChars()
    {
        global $cfg;
        $database = 'test&data\'base';
        $this->assertEquals(
            '<a href="' . $cfg['DefaultTabDatabase'] . '?db='
            . htmlspecialchars(urlencode($database))
            . '&amp;server=99&amp;lang=en&amp;token=token" title="Jump to database &quot;'
            . htmlspecialchars($database) . '&quot;.">'
            . htmlspecialchars($database) . '</a>',
            PMA_Util::getDbLink($database)
        );
    }
}
