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
use PMA\libraries\Theme;
use PMA\libraries\URL;
use PMA\libraries\Sanitize;

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

class PMA_HeaderLocation_Test extends PMATestCase
{

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

        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', null);
    }

    /**
     * Test for PMA_sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationWithoutSidWithIis()
    {
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', true);

        $testUri = 'https://example.com/test.php';

        $this->mockResponse('Location: ' . $testUri);
        PMA_sendHeaderLocation($testUri); // sets $GLOBALS['header']

        $this->tearDown();

        $this->mockResponse('Refresh: 0; ' . $testUri);
        PMA_sendHeaderLocation($testUri, true); // sets $GLOBALS['header']
    }

    /**
     * Test for PMA_sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationWithoutSidWithoutIis()
    {
        $testUri = 'https://example.com/test.php';

        $this->mockResponse('Location: ' . $testUri);
        PMA_sendHeaderLocation($testUri);            // sets $GLOBALS['header']
    }

    /**
     * Test for PMA_sendHeaderLocation
     *
     * @return void
     */
    public function testSendHeaderLocationIisLongUri()
    {
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', true);

        // over 600 chars
        $testUri = 'https://example.com/test.php?testlonguri=over600chars&test=test'
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
        $testUri_js = Sanitize::escapeJsString($testUri);

        $header = "<html>\n<head>\n    <title>- - -</title>
    <meta http-equiv=\"expires\" content=\"0\" />"
            . "\n    <meta http-equiv=\"Pragma\" content=\"no-cache\" />"
            . "\n    <meta http-equiv=\"Cache-Control\" content=\"no-cache\" />"
            . "\n    <meta http-equiv=\"Refresh\" content=\"0;url=" . $testUri_html . "\" />"
            . "\n    <script type=\"text/javascript\">\n        //<![CDATA[
        setTimeout(\"window.location = decodeURI('" . $testUri_js . "')\", 2000);
        //]]>\n    </script>\n</head>
<body>\n<script type=\"text/javascript\">\n    //<![CDATA[
    document.write('<p><a href=\"" . $testUri_html . "\">" . __('Go') . "</a></p>');
    //]]>\n</script>\n</body>\n</html>
";

        $this->expectOutputString($header);

        $restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('disable', 'header', 'headersSent'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('disable');

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        PMA_sendHeaderLocation($testUri);

        $attrInstance->setValue($restoreInstance);
    }
}