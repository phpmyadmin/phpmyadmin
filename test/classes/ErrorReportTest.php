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
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Utils\HttpRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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
        $GLOBALS['PMA_Config']->set('is_https', false);

        if (! defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'Other');
        }
        if (! defined('PMA_USR_BROWSER_VER')) {
            define('PMA_USR_BROWSER_VER', 1);
        }
        if (! defined('PMA_USR_OS')) {
            define('PMA_USR_OS', 'os');
        }

        $template = new Template();
        $this->errorReport = new ErrorReport(new HttpRequest(), new Relation(null, $template), $template);
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

        $template = new Template();
        $this->errorReport = new ErrorReport($httpRequest, new Relation(null, $template), $template);
        $this->errorReport->setSubmissionUrl($submissionUrl);

        $this->assertEquals($return, $this->errorReport->send($report));
    }

    /**
     * @return void
     */
    public function testGetForm(): void
    {
        $_POST['exception'] = [];

        $form = $this->errorReport->getForm();
        $this->assertStringContainsString('<pre class="report-data">[]</pre>', $form);

        $context = [
            'Widget.prototype = {',
            '  close: function() {',
            '    if (this.completion.widget != this) return;',
            '    this.completion.widget = null;',
            '    this.hints.parentNode.removeChild(this.hints);',
            '    this.completion.cm.removeKeyMap(this.keyMap);',
            '',
            '    var cm = this.completion.cm;',
            '    if (this.completion.options.closeOnUnfocus) {',
            '      cm.off("blur", this.onBlur);',
        ];

        $_POST['exception'] = [
            'mode' => 'stack',
            'name' => 'TypeError',
            'message' => 'Cannot read property \'removeChild\' of null',
            'stack' => [
                [
                    'url' => 'http://pma.7.3.local/js/vendor/codemirror/addon/hint/show-hint.js?v=4.8.6-dev',
                    'func' => 'Widget.close',
                    'line' => 307,
                    'column' => 29,
                    'context' => $context,
                ],
            ],
            'url' => 'http://pma.7.3.local/tbl_sql.php?db=aaaaa&table=a&server=14',
        ];
        $_POST['microhistory'] = '';
        $_POST['description'] = 'description';

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
            'script_name' => 'tbl_sql.php',
            'exception_type' => 'js',
            'exception' => [
                'mode' => 'stack',
                'name' => 'TypeError',
                'message' => 'Cannot read property \'removeChild\' of null',
                'stack' => [
                    [
                        'func' => 'Widget.close',
                        'line' => 307,
                        'column' => 29,
                        'context' => $context,
                        'uri' => 'js/vendor/codemirror/addon/hint/show-hint.js?v=4.8.6-dev',
                        'scriptname' => 'js/vendor/codemirror/addon/hint/show-hint.js',
                    ],
                ],
                'uri' => 'tbl_sql.php?',
            ],
            'microhistory' => $_POST['microhistory'],
            'steps' => $_POST['description'],
        ];
        $expectedData = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $form = $this->errorReport->getForm();
        $this->assertStringContainsString('<pre class="report-data">' . $expectedData . '</pre>', $form);
    }

    /**
     * Call private functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return mixed the output from the private method.
     */
    private function _callPrivateFunction($name, $params)
    {
        $class = new ReflectionClass(ErrorReport::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->errorReport, $params);
    }

    /**
     * The urls to be tested for sanitization
     *
     * @return array[]
     */
    public function urlsToSanitize(): array
    {
        return [
            [
                '',
                [
                    'index.php?',
                    'index.php',
                ],
            ],
            [
                'http://localhost/js/vendor/codemirror/addon/hint/show-hint.js?v=4.8.6-dev',
                [
                    'js/vendor/codemirror/addon/hint/show-hint.js?v=4.8.6-dev',
                    'js/vendor/codemirror/addon/hint/show-hint.js',
                ],
            ],
            [
                'http://pma.7.3.local/tbl_sql.php?db=aaaaa&table=a&server=14',
                [
                    'tbl_sql.php?',
                    'tbl_sql.php',
                ],
            ],
            [
                'http://pma.7.3.local/tbl_sql.php?db=aaaaa;table=a;server=14',
                [
                    'tbl_sql.php?',
                    'tbl_sql.php',
                ],
            ],
            [
                'https://pma.7.3.local/tbl_sql.php?db=aaaaa;table=a;server=14',
                [
                    'tbl_sql.php?',
                    'tbl_sql.php',
                ],
            ],
            [
                'https://pma.7.3.local/fileDotPhp.php?db=aaaaa;table=a;server=14',
                [
                    'fileDotPhp.php?',
                    'fileDotPhp.php',
                ],
            ],
            [
                'https://pma.7.3.local/secretFolder/fileDotPhp.php?db=aaaaa;table=a;server=14',
                [
                    'fileDotPhp.php?',
                    'fileDotPhp.php',
                ],
            ],
            [
                'http://7.2.local/@williamdes/theREALphpMyAdminREPO/js/vendor/jquery/jquery-ui.min.js?v=5.0.0-dev',
                [
                    'js/vendor/jquery/jquery-ui.min.js?v=5.0.0-dev',
                    'js/vendor/jquery/jquery-ui.min.js',
                ],
            ],
        ];
    }

    /**
     * Test the url sanitization
     *
     * @dataProvider urlsToSanitize
     * @param string $url    The url to test
     * @param array  $result The result
     * @return void
     */
    public function testSanitizeUrl(string $url, array $result): void
    {
        // $this->errorReport->sanitizeUrl
        $this->assertSame($result, $this->_callPrivateFunction('sanitizeUrl', [$url]));
    }
}
