<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for templates/header_location view
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/Template.class.php';

require_once 'libraries/js_escape.lib.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Test for templates/header_location view
 *
 * @package PhpMyAdmin-test
 */
class HeaderLocation_Test extends PHPUnit_Framework_TestCase
{
    const VIEW = 'header_location';

    /**
     * Test view
     *
     * @return void
     */
    public function testView()
    {
        $html = PMA\Template::get(self::VIEW)
            ->render(array('uri' => 'http://www.google.fr'));

        $this->assertContains(
            '<meta http-equiv="Refresh" content="0;url=http://www.google.fr">',
            $html
        );

        $this->assertContains(
            'setTimeout("window.location = decodeURI(\'http://www.google.fr\')", '
            . '2000);',
            $html
        );

        $this->assertContains(
            'document.write(\'<p><a href="http://www.google.fr">Go</a></p>\');',
            $html
        );
    }

    /**
     * Test view without parameters
     *
     * @return void
     */
    public function testViewWOParameters()
    {
        $html = PMA\Template::get(self::VIEW)
            ->render();

        $this->assertContains('<meta http-equiv="Refresh" content="0;url=">', $html);

        $this->assertContains(
            'setTimeout("window.location = decodeURI(\'\')", 2000);',
            $html
        );

        $this->assertContains(
            'document.write(\'<p><a href="">Go</a></p>\');',
            $html
        );
    }
}
