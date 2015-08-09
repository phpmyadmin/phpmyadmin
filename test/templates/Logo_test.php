<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for templates/logo view
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/Template.class.php';

/**
 * Test for templates/logo view
 *
 * @package PhpMyAdmin-test
 */
class Logo_Test extends PHPUnit_Framework_TestCase
{
    const VIEW = 'logo';

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['pmaThemeImage'] = './themes/pmahomme/img/';
    }

    /**
     * Test view
     *
     * @return void
     */
    public function testView()
    {
        $html = PMA\Template::get(self::VIEW)
            ->render(array('logo' => 'phpMyAdmin'));

        $this->assertContains('<div id="pmalogo">phpMyAdmin</div>', $html);
    }

    /**
     * Test view
     *
     * @return void
     */
    public function testViewWithLink()
    {
        $html = PMA\Template::get(self::VIEW)
            ->render(
                array(
                    'logo'        => 'phpMyAdmin',
                    'useLogoLink' => true,
                    'logoLink'    => 'www.phpmyadmin.net',
                )
            );

        $this->assertContains(
            '<div id="pmalogo"><a href="www.phpmyadmin.net">phpMyAdmin</a></div>',
            $html
        );
    }

    /**
     * Test view without logo
     *
     * @return void
     */
    public function testViewWOLogo()
    {
        $html = PMA\Template::get(self::VIEW)->render(array('displayLogo' => false));

        $this->assertEquals('<!-- LOGO START --><!-- LOGO END -->', $html);
    }

    /**
     * Test view without parameters
     *
     * @return void
     */
    public function testViewWOParameters()
    {
        $html = PMA\Template::get(self::VIEW)->render();

        $this->assertContains(
            '<img src="' . $GLOBALS['pmaThemeImage'] . 'logo_left.png" '
            . 'alt="phpMyAdmin" id="imgpmalogo" />',
            $html
        );
    }
}
