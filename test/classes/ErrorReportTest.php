<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\ErrorReport
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Error;
use PhpMyAdmin\ErrorReport;
use PhpMyAdmin\Utils\HttpRequest;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\ErrorReportTest class
 *
 * this class is for testing PhpMyAdmin\ErrorReport methods
 *
 * @package PhpMyAdmin-test
 */
class ErrorReportTest extends TestCase
{
    /**
     * @var ErrorReport $errorReport
     */
    private $errorReport;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['ProxyUrl'] = '';
        $GLOBALS['cfg']['ProxyUser'] = '';
        $GLOBALS['cfg']['ProxyPass'] = '';
        $_SERVER['SERVER_SOFTWARE'] = 'SERVER_SOFTWARE';
        $_SERVER['HTTP_USER_AGENT'] = 'HTTP_USER_AGENT';
        $_COOKIE['pma_lang'] = 'en';

        if (! defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'Other');
        }
        if (! defined('PMA_USR_BROWSER_VER')) {
            define('PMA_USR_BROWSER_VER', 1);
        }
        if (! defined('PMA_USR_OS')) {
            define('PMA_USR_OS', 'os');
        }

        $this->errorReport = new ErrorReport(new HttpRequest());
        $this->errorReport->setSubmissionUrl('http://localhost');
    }

    /**
     * @return void
     */
    public function testGetData(): void
    {
        $actual = $this->errorReport->getData('unknown');
        $this->assertEquals([], $actual);

        $actual = $this->errorReport->getData('php');
        $this->assertEquals([], $actual);

        $_SESSION['prev_errors'] = [];

        $actual = $this->errorReport->getData('php');
        $this->assertEquals([], $actual);

        $_SESSION['prev_errors'] = [
            new Error(0, 'error 0', 'file', 1),
            new Error(1, 'error 1', 'file', 2),
        ];

        $report = [
            'pma_version' => PMA_VERSION,
            'browser_name' => PMA_USR_BROWSER_AGENT,
            'browser_version' => PMA_USR_BROWSER_VER,
            'user_os' => PMA_USR_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'user_agent_string' => $_SERVER['HTTP_USER_AGENT'],
            'locale' => $_COOKIE['pma_lang'],
            'configuration_storage' => 'disabled',
            'php_version' => phpversion(),
            'exception_type' => 'php',
            'errors' => [
                0 => [
                    'lineNum' => $_SESSION['prev_errors'][0]->getLine(),
                    'file' => $_SESSION['prev_errors'][0]->getFile(),
                    'type' => $_SESSION['prev_errors'][0]->getType(),
                    'msg' => $_SESSION['prev_errors'][0]->getOnlyMessage(),
                    'stackTrace' => $_SESSION['prev_errors'][0]->getBacktrace(5),
                    'stackhash' => $_SESSION['prev_errors'][0]->getHash(),
                ],
                1 => [
                    'lineNum' => $_SESSION['prev_errors'][1]->getLine(),
                    'file' => $_SESSION['prev_errors'][1]->getFile(),
                    'type' => $_SESSION['prev_errors'][1]->getType(),
                    'msg' => $_SESSION['prev_errors'][1]->getOnlyMessage(),
                    'stackTrace' => $_SESSION['prev_errors'][1]->getBacktrace(5),
                    'stackhash' => $_SESSION['prev_errors'][1]->getHash(),
                ],
            ],
        ];

        $actual = $this->errorReport->getData('php');
        $this->assertEquals($report, $actual);
    }

    /**
     * @return void
     */
    public function testSend(): void
    {
        $submissionUrl = 'http://localhost';
        $report = [];
        $return = 'return';

        $httpRequest = $this->getMockBuilder(HttpRequest::class)
            ->setMethods(['create'])
            ->getMock();
        $httpRequest->expects($this->once())
            ->method('create')
            ->with(
                $submissionUrl,
                "POST",
                false,
                json_encode($report),
                "Content-Type: application/json"
            )
            ->willReturn($return);

        $this->errorReport = new ErrorReport($httpRequest);
        $this->errorReport->setSubmissionUrl($submissionUrl);

        $this->assertEquals($return, $this->errorReport->send($report));
    }

    /**
     * @return void
     */
    public function testGetForm(): void
    {
        $_REQUEST['exception'] = [];

        $form = $this->errorReport->getForm();
        $this->assertContains('<pre class="report-data">[]</pre>', $form);

        $_REQUEST['exception'] = ['stack' => [], 'url' => 'http://localhost/index.php'];
        $_REQUEST['microhistory'] = '';
        $_REQUEST['description'] = 'description';

        $report = [
            'pma_version' => PMA_VERSION,
            'browser_name' => PMA_USR_BROWSER_AGENT,
            'browser_version' => PMA_USR_BROWSER_VER,
            'user_os' => PMA_USR_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'user_agent_string' => $_SERVER['HTTP_USER_AGENT'],
            'locale' => $_COOKIE['pma_lang'],
            'configuration_storage' => 'disabled',
            'php_version' => phpversion(),
            'exception_type' => 'js',
            'exception' => ['stack' => [], 'uri' => 'index.php?'],
            'script_name' => 'index.php',
            'microhistory' => $_REQUEST['microhistory'],
            'steps' => $_REQUEST['description'],
        ];
        $expectedData = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $form = $this->errorReport->getForm();
        $this->assertContains('<pre class="report-data">' . $expectedData . '</pre>', $form);
    }
}
