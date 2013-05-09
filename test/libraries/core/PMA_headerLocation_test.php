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

    public function __construct()
    {
        parent::__construct();
        $this->runkitExt = false;
        if (function_exists("runkit_constant_redefine")) {
            $this->runkitExt = true;
        }

        $this->apdExt = false;
        if (function_exists("rename_function")) {
            $this->apdExt = true;
        }

        if ($this->apdExt && !$GLOBALS['test_header']) {

            /*
             * using apd extension to overriding header and headers_sent
             * functions for test purposes
             */
            $GLOBALS['test_header'] = 1;

            /*
             * rename_function() of header and headers_sent may cause CLI error
             * report in Windows XP
             */
            rename_function('header', 'test_header');
            rename_function('headers_sent', 'test_headers_sent');

            /*
             * solution from:
             * http://unixwars.com/2008/11/29/override_function-in-php/
             * to overriding more than one function
             */

            $substs = array(
                    'header' =>
                        'if (isset($GLOBALS["header"])) {'
                        . '$GLOBALS["header"] .= $a;'
                        . '} else {'
                        . '$GLOBALS["header"] = $a;'
                        . '}',
                    'headers_sent' => 'return false;'
                );

            $args = array(
                    'header' => '$a',
                    'headers_sent' => ''
                );

            foreach ($substs as $func => $ren_func) {
                if (function_exists("__overridden__")) {
                    rename_function(
                        "__overridden__",
                        str_replace(
                            array('.', ' '),
                            array('', ''),
                            microtime()
                        )
                    );
                }
                override_function($func, $args[$func], $substs[$func]);
                rename_function(
                    "__overridden__",
                    str_replace(array('.', ' '), array('', ''), microtime())
                );
            }

        }
    }

    public function __destruct()
    {
        /*
         * rename_function may causes CLI error report in Windows XP, but
         * nothing more happen
         */

        if ($this->apdExt && $GLOBALS['test_header']) {
            $GLOBALS['test_header'] = 0;

            rename_function(
                'header',
                'header' . str_replace(
                    array('.', ' '), array('', ''), microtime()
                )
            );
            rename_function(
                'headers_sent',
                'headers_sent' . str_replace(
                    array('.', ' '), array('', ''), microtime()
                )
            );

            rename_function('test_header', 'header');
            rename_function('test_headers_sent', 'headers_sent');
        }
    }

    public function setUp()
    {
        //session_start();

        // cleaning constants
        if ($this->runkitExt) {

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
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['lang'] = 'en';
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
    }


    public function tearDown()
    {
        //session_destroy();

        // cleaning constants
        if ($this->runkitExt) {

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

        if ($this->apdExt) {
            unset($GLOBALS['header']);
        }
    }


    public function testSendHeaderLocationWithSidUrlWithQuestionMark()
    {
        if ($this->runkitExt && $this->apdExt) {

            runkit_constant_redefine('SID', md5('test_hash'));

            $testUri = 'http://testurl.com/test.php?test=test';
            $separator = PMA_get_arg_separator();

            $header = 'Location: ' . $testUri . $separator . SID;

            /* sets $GLOBALS['header'] */
            PMA_sendHeaderLocation($testUri);

            $this->assertEquals($header, $GLOBALS['header']);

        } else {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing APD or/and runkit extension'
            );
        }

    }

    public function testSendHeaderLocationWithSidUrlWithoutQuestionMark()
    {
        if ($this->runkitExt && $this->apdExt) {

            runkit_constant_redefine('SID', md5('test_hash'));

            $testUri = 'http://testurl.com/test.php';
            $separator = PMA_get_arg_separator();

            $header = 'Location: ' . $testUri . '?' . SID;

            PMA_sendHeaderLocation($testUri);            // sets $GLOBALS['header']
            $this->assertEquals($header, $GLOBALS['header']);

        } else {
            $this->markTestSkipped('Cannot redefine constant/function - missing APD or/and runkit extension');
        }

    }

    public function testSendHeaderLocationWithoutSidWithIis()
    {
        if ($this->runkitExt && $this->apdExt) {

            runkit_constant_redefine('PMA_IS_IIS', true);

            $testUri = 'http://testurl.com/test.php';
            $separator = PMA_get_arg_separator();

            $header = 'Location: ' . $testUri;
            PMA_sendHeaderLocation($testUri);            // sets $GLOBALS['header']
            $this->assertEquals($header, $GLOBALS['header']);

            //reset $GLOBALS['header'] for the next assertion
            unset($GLOBALS['header']);

            $header = 'Refresh: 0; ' . $testUri;
            PMA_sendHeaderLocation($testUri, true);            // sets $GLOBALS['header']
            $this->assertEquals($header, $GLOBALS['header']);

        } else {
            $this->markTestSkipped('Cannot redefine constant/function - missing APD or/and runkit extension');
        }

    }

    public function testSendHeaderLocationWithoutSidWithoutIis()
    {
        if ($this->apdExt) {

            $testUri = 'http://testurl.com/test.php';
            $header = 'Location: ' . $testUri;

            PMA_sendHeaderLocation($testUri);            // sets $GLOBALS['header']
            $this->assertEquals($header, $GLOBALS['header']);

        } else {
            $this->markTestSkipped('Cannot redefine constant/function - missing APD or/and runkit extension');
        }

    }

    public function testSendHeaderLocationIisLongUri()
    {
        if (defined('PMA_IS_IIS') && $this->runkitExt) {
            runkit_constant_redefine('PMA_IS_IIS', true);
        } elseif (!defined('PMA_IS_IIS')) {
            define('PMA_IS_IIS', true);
        } else {
            $this->markTestSkipped('Cannot redefine constant/function - missing APD or/and runkit extension');
        }

        // over 600 chars
        $testUri = 'http://testurl.com/test.php?testlonguri=over600chars&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test&test=test';
        $testUri_html = htmlspecialchars($testUri);
        $testUri_js = PMA_escapeJsString($testUri);

        $header =    "<html><head><title>- - -</title>\n" .
                    "<meta http-equiv=\"expires\" content=\"0\">\n" .
                    "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n" .
                    "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n" .
                    "<meta http-equiv=\"Refresh\" content=\"0;url=" . $testUri_html . "\">\n" .
                    "<script type=\"text/javascript\">\n".
                    "//<![CDATA[\n" .
                    "setTimeout(\"window.location = unescape('\"" . $testUri_js . "\"')\", 2000);\n" .
                    "//]]>\n" .
                    "</script>\n" .
                    "</head>\n" .
                    "<body>\n" .
                    "<script type=\"text/javascript\">\n" .
                    "//<![CDATA[\n" .
                    "document.write('<p><a href=\"" . $testUri_html . "\">" . __('Go') . "</a></p>');\n" .
                    "//]]>\n" .
                    "</script></body></html>\n";


        $this->expectOutputString($header);

        PMA_sendHeaderLocation($testUri);
    }
}
?>
