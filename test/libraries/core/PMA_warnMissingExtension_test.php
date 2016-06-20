<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PMA_warnMissingExtension warns or fails on missing extension.
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;


require_once 'libraries/js_escape.lib.php';
require_once 'libraries/sanitizing.lib.php';

/**
 * PMA_warnMissingExtension warns or fails on missing extension.
 *
 * @package PhpMyAdmin-test
 */
class PMA_WarnMissingExtension_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server'] = array(
            'host' => 'host',
            'verbose' => 'verbose',
        );
        $GLOBALS['cfg']['OBGzip'] = false;
        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';

        include_once './libraries/ErrorHandler.php';
        $GLOBALS['error_handler'] = new PMA\libraries\ErrorHandler();
    }

    /**
     * Test for PMA_warnMissingExtension
     *
     * @return void
     */
    function testMissingExtensionFatal()
    {
        $ext = 'php_ext';
        $warn = 'The <a href="' . PMA_getPHPDocLink('book.' . $ext . '.php')
            . '" target="Documentation"><em>' . $ext
            . '</em></a> extension is missing. Please check your PHP configuration.';

        $this->expectOutputRegex('@' . preg_quote($warn, '@') . '@');

        PMA_warnMissingExtension($ext, true);
    }

    /**
     * Test for PMA_warnMissingExtension
     *
     * @return void
     */
    function testMissingExtensionFatalWithExtra()
    {
        $ext = 'php_ext';
        $extra = 'Appended Extra String';

        $warn = 'The <a href="' . PMA_getPHPDocLink('book.' . $ext . '.php')
            . '" target="Documentation"><em>' . $ext
            . '</em></a> extension is missing. Please check your PHP configuration.'
            . ' ' . $extra;

        ob_start();
        PMA_warnMissingExtension($ext, true, $extra);
        $printed = ob_get_contents();
        ob_end_clean();

        $this->assertGreaterThan(0, mb_strpos($printed, $warn));
    }
}
