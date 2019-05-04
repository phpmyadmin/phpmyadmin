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
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\UserPreferencesHeader;

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
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function testGetContentWithSelectedTab(): void
    {
        $_GET['form'] = 'Features';
        $this->assertStringContainsString(
            '<li class="active">' . \PHP_EOL
            . \PHP_EOL
            . '            <a href="prefs_forms.php?form=Features&amp;server=0&amp;lang=en" class="tabactive">' . \PHP_EOL
            . '            <img src="themes/dot.gif" title="Features" alt="Features" class="icon ic_b_tblops">&nbsp;Features' . \PHP_EOL
            . '            </a>' . \PHP_EOL
            . '        </li>',
            UserPreferencesHeader::getContent(new Template())
        );
    }

    /**
     * Test for getContent with "saved" get parameter
     *
     * @return void
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function testGetContentAfterSave(): void
    {
        $_GET['saved'] = true;
        $this->assertStringContainsString(
            'Configuration has been saved.',
            UserPreferencesHeader::getContent(new Template())
        );
    }

    /**
     * Test for getContent with session storage
     *
     * @return void
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function testGetContentWithSessionStorage(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        $this->assertStringContainsString(
            'Your preferences will be saved for current session only. Storing them permanently requires',
            UserPreferencesHeader::getContent(new Template())
        );
    }
}
