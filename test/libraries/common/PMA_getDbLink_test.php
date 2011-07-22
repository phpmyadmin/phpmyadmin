<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getDbLink_test from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_getDbLink_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_getDbLink_test extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        global $cfg;
        require 'libraries/config.default.php';
    }

    function testGetDbLinkEmpty()
    {
        $GLOBALS['db'] = null;
        $this->assertEmpty(PMA_getDbLink());
    }

    function testGetDbLinkNull()
    {
        global $cfg;
        $GLOBALS['db'] = 'test_db';
        $database = $GLOBALS['db'];
        $this->assertEquals('<a href="' . $cfg['DefaultTabDatabase'] . '?db=' . $database
                            . '&amp;server=server&amp;lang=en" title="Jump to database &quot;' . htmlspecialchars($database) . '&quot;.">'
                            . htmlspecialchars($database) . '</a>',PMA_getDbLink());
    }

    function testGetDbLink()
    {
        global $cfg;
        $database = 'test_database';
        $this->assertEquals('<a href="' . $cfg['DefaultTabDatabase'] . '?db=' . $database
                            . '&amp;server=server&amp;lang=en" title="Jump to database &quot;' . htmlspecialchars($database) . '&quot;.">'
                            . htmlspecialchars($database) . '</a>',PMA_getDbLink($database));
    }

    function testGetDbLinkWithSpecialChars()
    {
        global $cfg;
        $database = 'test&data\'base';
        $this->assertEquals('<a href="' . $cfg['DefaultTabDatabase'] . '?db=' . htmlspecialchars(urlencode($database))
                            . '&amp;server=server&amp;lang=en" title="Jump to database &quot;' . htmlspecialchars($database) . '&quot;.">'
                            . htmlspecialchars($database) . '</a>',PMA_getDbLink($database));
    }
}