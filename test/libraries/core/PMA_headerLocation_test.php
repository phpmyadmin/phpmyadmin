<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_sendHeaderLocation
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/vendor_config.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/select_lang.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Response.class.php';

/**
 * Test function sending headers.
 * Warning - these tests set constants, so it can interfere with other tests
 * If you have runkit extension, then it is possible to back changes made on
 * constants rest of options can be tested only with apd, when functions header
 * and headers_sent are redefined rename_function() of header and headers_sent
 * may cause CLI error report in Windows XP (but tests are done correctly)
 * additional functions which were created during tests must be stored to
 * coverage test e.g.
 *
 * <code>
 * rename_function(
 *     'headers_sent',
 *     'headers_sent'.str_replace(array('.', ' '),array('', ''),microtime())
 * );
 * </code>
 *
 * @package PhpMyAdmin-test
 */

class PMA_HeaderLocation_Test extends PHPUnit_Framework_TestCase
{

    protected $oldIISvalue;
    protected $oldSIDvalue;
    protected $runkitExt;
    protected $apdExt;

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        //session_start();

        // cleaning constants
        if (PMA_HAS_RUNKIT) {

            $this->oldIISvalue = 'non-defined';

            $defined_constants = get_defined_constants(true);
            $user_defined_constants = $defined_constants['user'];
            if (array_key_exists('PMA_IS_IIS', $user_defined_constants)) {
                $this->oldIISvalue = PMA_IS_IIS;
                runkit_constant_redefine('PMA_IS_IIS', null);
            } else {
                runkit_constant_add('PMA_IS_IIS', null);
            }

            $this->oldSIDvalue = 'non-defined';

            if (array_key_exists('SID', $user_defined_constants)) {
                $this->oldSIDvalue = SID;
                runkit_constant_redefine('SID', null);
            } else {
                runkit_constant_add('SID', null);
            }

        }
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        //session_destroy();

        // cleaning constants
        if (PMA_HAS_RUNKIT) {

            if ($this->oldIISvalue != 'non-defined') {
                runkit_constant_redefine('PMA_IS_IIS', $this->oldIISvalue);
            } elseif (defined('PMA_IS_IIS')) {
                runkit_constant_remove('PMA_IS_IIS');
            }

            if ($this->oldSIDvalue != 'non-defined') {
                runkit_constant_redefine('SID', $this->oldSIDvalue);
            } elseif (defined('SID')) {
                runkit_constant_remove('SID');
            }
        }
    }

    /**
     * Test for PMA_sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationWithSidUrlWithQuestionMark()
    {
        if (defined('PMA_TEST_HEADERS')) {

            runkit_constant_redefine('SID', md5('test_hash'));

            $testUri = 'http://testurl.com/test.php?test=test';
            $separator = PMA_URL_getArgSeparator();

            $header = array('Location: ' . $testUri . $separator . SID);

            /* sets $GLOBALS['header'] */
            PMA_sendHeaderLocation($testUri);

            $this->assertEquals($header, $GLOBALS['header']);

        } else {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

    }

    /**
     * Test for PMA_sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationWithSidUrlWithoutQuestionMark()
    {
        if (defined('PMA_TEST_HEADERS')) {

            runkit_constant_redefine('SID', md5('test_hash'));

            $testUri = 'http://testurl.com/test.php';

            $header = array('Location: ' . $testUri . '?' . SID);

            PMA_sendHeaderLocation($testUri);            // sets $GLOBALS['header']
            $this->assertEquals($header, $GLOBALS['header']);

        } else {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

    }

    /**
     * Test for PMA_sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationWithoutSidWithIis()
    {
        if (defined('PMA_TEST_HEADERS')) {

            runkit_constant_redefine('PMA_IS_IIS', true);

            $testUri = 'http://testurl.com/test.php';

            $header = array('Location: ' . $testUri);
            PMA_sendHeaderLocation($testUri); // sets $GLOBALS['header']
            $this->assertEquals($header, $GLOBALS['header']);

            //reset $GLOBALS['header'] for the next assertion
            unset($GLOBALS['header']);

            $header = array('Refresh: 0; ' . $testUri);
            PMA_sendHeaderLocation($testUri, true); // sets $GLOBALS['header']
            $this->assertEquals($header, $GLOBALS['header']);

        } else {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

    }

    /**
     * Test for PMA_sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationWithoutSidWithoutIis()
    {
        if (defined('PMA_TEST_HEADERS')) {

            $testUri = 'http://testurl.com/test.php';
            $header = array('Location: ' . $testUri);

            PMA_sendHeaderLocation($testUri);            // sets $GLOBALS['header']
            $this->assertEquals($header, $GLOBALS['header']);

        } else {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

    }

    /**
     * Test for PMA_sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationIisLongUri()
    {
        if (defined('PMA_IS_IIS') && PMA_HAS_RUNKIT) {
            runkit_constant_redefine('PMA_IS_IIS', true);
        } elseif (!defined('PMA_IS_IIS')) {
            define('PMA_IS_IIS', true);
        } else {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

        // over 600 chars
        $testUri = 'http://testurl.com/test.php?testlonguri=over600chars&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test&test=test&test=test&test=test&test=test'
            . '&test=test&test=test';
        $testUri_html = htmlspecialchars($testUri);
        $testUri_js = PMA_escapeJsString($testUri);

        $header = "<html><head><title>- - -</title>
    <meta http-equiv=\"expires\" content=\"0\">"
            . "<meta http-equiv=\"Pragma\" content=\"no-cache\">"
            . "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">"
            . "<meta http-equiv=\"Refresh\" content=\"0;url=" . $testUri_html . "\">"
            . "<script type=\"text/javascript\">//<![CDATA[
        setTimeout(\"window.location = decodeURI('" . $testUri_js . "')\", 2000);
        //]]></script></head>
<body><script type=\"text/javascript\">//<![CDATA[
    document.write('<p><a href=\"" . $testUri_html . "\">" . __('Go') . "</a></p>');
    //]]></script></body></html>
";

        $this->expectOutputString($header);

        PMA_sendHeaderLocation($testUri);
    }
}
