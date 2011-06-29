<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getPHPDocLink, PMA_linkURL, PMA_includeJS  from libraries/core.lib.php
 *
 * @package phpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/url_generating.lib.php';

class PMA_getLinks_test extends PHPUnit_Framework_TestCase
{
    public function testPMA_getPHPDocLink()
    {
        $lang = _pgettext('PHP documentation language', 'en');
        $this->assertEquals(PMA_getPHPDocLink('function'), 'http://php.net/manual/' . $lang . '/function');
    }

    public function providerLinkURL(){
        return array(
            array('http://wiki.phpmyadmin.net', './url.php?url=http%3A%2F%2Fwiki.phpmyadmin.net&amp;lang=en'),
            array('https://wiki.phpmyadmin.net', './url.php?url=https%3A%2F%2Fwiki.phpmyadmin.net&amp;lang=en'),
            array('wiki.phpmyadmin.net', 'wiki.phpmyadmin.net'),
            array('index.php?db=phpmyadmin', 'index.php?db=phpmyadmin')
        );
    }

    /**
     * @dataProvider providerLinkURL
     */
    public function testPMA_linkURL($link, $url){
        $this->assertEquals(PMA_linkURL($link), $url);
    }

    public function testPMA_includeJS()
    {
        $filename = "common.js";
        $mod = 0;

        if (file_exists('./js/'.$filename)) {
            $mod = filemtime('./js/'.$filename);
        }
        else{
            $this->fail("JS file doesn't exists.");
        }
        $this->assertEquals(PMA_includeJS($filename), '<script src="./js/'.$filename.'?ts='.$mod.'" type="text/javascript"></script>'. "\n");

        $filename = '?file.js';
        //$this->assertEquals(PMA_includeJS($filename), '<script src="./js/?file.js" type="text/javascript"></script>\n');
        $this->assertEquals(PMA_includeJS($filename), '<script src="./js/'.$filename.'" type="text/javascript"></script>'."\n");

        //$this->assertFalse(PMA_includeJS(null));
    }

}
