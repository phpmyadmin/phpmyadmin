<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getPHPDocLink, PMA_linkURL  from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_getLinks_test extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 99;
        $GLOBALS['cfg']['ServerDefault'] = 0;
    }

    public function testPMA_getPHPDocLink()
    {
        $lang = _pgettext('PHP documentation language', 'en');
        $this->assertEquals(
            PMA_getPHPDocLink('function'),
            './url.php?url=http%3A%2F%2Fphp.net%2Fmanual%2F'
            . $lang . '%2Ffunction&amp;server=99&amp;lang=en&amp;token=token'
        );
    }

    public function providerLinkURL()
    {
        return array(
            array('http://wiki.phpmyadmin.net', './url.php?url=http%3A%2F%2Fwiki.phpmyadmin.net&amp;server=99&amp;lang=en&amp;token=token'),
            array('https://wiki.phpmyadmin.net', './url.php?url=https%3A%2F%2Fwiki.phpmyadmin.net&amp;server=99&amp;lang=en&amp;token=token'),
            array('wiki.phpmyadmin.net', 'wiki.phpmyadmin.net'),
            array('index.php?db=phpmyadmin', 'index.php?db=phpmyadmin')
        );
    }

    /**
     * @dataProvider providerLinkURL
     */
    public function testPMA_linkURL($link, $url)
    {
        $this->assertEquals(PMA_linkURL($link), $url);
    }
}
