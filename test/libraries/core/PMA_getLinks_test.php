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

/**
 * Test for PMA_getPHPDocLink, PMA_linkURL  from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_GetLinks_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    function setUp()
    {
        $GLOBALS['server'] = 99;
        $GLOBALS['cfg']['ServerDefault'] = 0;
    }

    /**
     * Test for PMA_getPHPDocLink
     *
     * @return void
     */
    public function testPMA_getPHPDocLink()
    {
        $lang = _pgettext('PHP documentation language', 'en');
        $this->assertEquals(
            PMA_getPHPDocLink('function'),
            './url.php?url=http%3A%2F%2Fphp.net%2Fmanual%2F'
            . $lang . '%2Ffunction'
        );
    }

    /**
     * Data provider for testPMA_linkURL
     *
     * @return array
     */
    public function providerLinkURL()
    {
        return array(
            array('https://wiki.phpmyadmin.net',
             './url.php?url=https%3A%2F%2Fwiki.phpmyadmin.net'),
            array('https://wiki.phpmyadmin.net',
             './url.php?url=https%3A%2F%2Fwiki.phpmyadmin.net'),
            array('wiki.phpmyadmin.net', 'wiki.phpmyadmin.net'),
            array('index.php?db=phpmyadmin', 'index.php?db=phpmyadmin')
        );
    }

    /**
     * Test for PMA_linkURL
     *
     * @param string $link URL where to go
     * @param string $url  Expected value
     *
     * @return void
     *
     * @dataProvider providerLinkURL
     */
    public function testPMA_linkURL($link, $url)
    {
        $this->assertEquals(PMA_linkURL($link), $url);
    }
}
