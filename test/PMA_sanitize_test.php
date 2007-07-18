<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_sanitize()
 *
 * @version $Id$
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once './libraries/sanitizing.lib.php';

class PMA_sanitize_test extends PHPUnit_Framework_TestCase
{
    public function testXssInHref()
    {
        $this->assertEquals('[a@javascript:alert(\'XSS\');@target]link</a>',
            PMA_sanitize('[a@javascript:alert(\'XSS\');@target]link[/a]'));
    }

    public function testLink()
    {
        $this->assertEquals('<a href="http://www.phpmyadmin.net/" target="target">link</a>',
            PMA_sanitize('[a@http://www.phpmyadmin.net/@target]link[/a]'));
    }

    public function testHtmlTags()
    {
        $this->assertEquals('&lt;div onclick=""&gt;',
            PMA_sanitize('<div onclick="">'));
    }

    public function testBbcoe()
    {
        $this->assertEquals('<strong>strong</strong>',
            PMA_sanitize('[b]strong[/b]'));
    }
}
?>