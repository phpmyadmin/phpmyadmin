<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under PhpMyAdmin\UserPreferencesHeader class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\UserPreferencesHeader;
use Throwable;

/**
 * tests for methods under PhpMyAdmin\UserPreferencesHeader class
 *
 * @package PhpMyAdmin-test
 */
class UserPreferencesHeaderTest extends PmaTestCase
{
    /**
     * Setup various pre conditions
     *
     * @return void
     */
    protected function setUp(): void
    {
        /*
        global $cfg;
        include ROOT_PATH . 'libraries/config.default.php';
        */
        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_PHP_SELF'] = '/prefs_forms.php';
    }

    /**
     * Test for getContent with selected tab
     *
     * @return void
     * @throws Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function testGetContentWithSelectedTab(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        $_GET['form'] = 'Features';

        $template = new Template();
        $content = UserPreferencesHeader::getContent($template, new Relation($dbi, $template));

        $this->assertStringContainsString(
            '<li class="active">',
            $content
        );
        $this->assertStringContainsString(
            '<a href="prefs_forms.php?form=Features&amp;server=0&amp;lang=en" class="tabactive">',
            $content
        );
        $this->assertStringContainsString(
            '<img src="themes/dot.gif" title="Features" alt="Features" class="icon ic_b_tblops">&nbsp;Features',
            $content
        );
    }

    /**
     * Test for getContent with "saved" get parameter
     *
     * @return void
     * @throws Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function testGetContentAfterSave(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        $_GET['saved'] = true;

        $template = new Template();
        $this->assertStringContainsString(
            'Configuration has been saved.',
            UserPreferencesHeader::getContent($template, new Relation($dbi, $template))
        );
    }

    /**
     * Test for getContent with session storage
     *
     * @return void
     * @throws Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function testGetContentWithSessionStorage(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        $template = new Template();
        $this->assertStringContainsString(
            'Your preferences will be saved for current session only. Storing them permanently requires',
            UserPreferencesHeader::getContent($template, new Relation($dbi, $template))
        );
    }
}
