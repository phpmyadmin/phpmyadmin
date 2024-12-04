<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Error;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Error\Error;
use PhpMyAdmin\Error\ErrorReport;
use PhpMyAdmin\Http\RequestMethod;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Utils\HttpRequest;
use PhpMyAdmin\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function htmlspecialchars;
use function json_encode;
use function phpversion;

use const ENT_QUOTES;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

#[CoversClass(ErrorReport::class)]
class ErrorReportTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private ErrorReport $errorReport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $config = Config::getInstance();
        $config->settings['ServerDefault'] = 1;
        $config->settings['ProxyUrl'] = '';
        $config->settings['ProxyUser'] = '';
        $config->settings['ProxyPass'] = '';
        $_SERVER['SERVER_SOFTWARE'] = 'SERVER_SOFTWARE';
        $_SERVER['HTTP_USER_AGENT'] = 'HTTP_USER_AGENT';
        $_COOKIE['pma_lang'] = 'en';
        $config->set('is_https', false);

        $this->errorReport = new ErrorReport(
            new HttpRequest(),
            new Relation($this->dbi),
            new Template(),
            $config,
        );
        $this->errorReport->setSubmissionUrl('http://localhost');
    }

    public function testGetData(): void
    {
        $actual = $this->errorReport->getData('unknown');
        self::assertSame([], $actual);

        $actual = $this->errorReport->getData('php');
        self::assertSame([], $actual);

        $_SESSION['prev_errors'] = [];

        $actual = $this->errorReport->getData('php');
        self::assertSame([], $actual);

        $_SESSION['prev_errors'] = [new Error(0, 'error 0', 'file', 1), new Error(1, 'error 1', 'file', 2)];

        $config = Config::getInstance();
        $report = [
            'pma_version' => Version::VERSION,
            'browser_name' => $config->get('PMA_USR_BROWSER_AGENT'),
            'browser_version' => $config->get('PMA_USR_BROWSER_VER'),
            'user_os' => $config->get('PMA_USR_OS'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'user_agent_string' => $_SERVER['HTTP_USER_AGENT'],
            'locale' => $_COOKIE['pma_lang'],
            'configuration_storage' => 'disabled',
            'php_version' => phpversion(),
            'exception_type' => 'php',
            'errors' => [
                [
                    'lineNum' => $_SESSION['prev_errors'][0]->getLine(),
                    'file' => $_SESSION['prev_errors'][0]->getFile(),
                    'type' => $_SESSION['prev_errors'][0]->getType(),
                    'msg' => $_SESSION['prev_errors'][0]->getOnlyMessage(),
                    'stackTrace' => $_SESSION['prev_errors'][0]->getBacktrace(5),
                    'stackhash' => $_SESSION['prev_errors'][0]->getHash(),
                ],
                [
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
        self::assertSame($report, $actual);
    }

    public function testSend(): void
    {
        $submissionUrl = 'http://localhost';
        $report = [];
        $return = 'return';

        $httpRequest = $this->getMockBuilder(HttpRequest::class)
            ->onlyMethods(['create'])
            ->getMock();
        $httpRequest->expects(self::once())
            ->method('create')
            ->with(
                $submissionUrl,
                RequestMethod::Post,
                false,
                json_encode($report),
                'Content-Type: application/json',
            )
            ->willReturn($return);

        $this->errorReport = new ErrorReport(
            $httpRequest,
            new Relation($this->dbi),
            new Template(),
            Config::getInstance(),
        );
        $this->errorReport->setSubmissionUrl($submissionUrl);

        self::assertSame($return, $this->errorReport->send($report));
    }

    public function testGetForm(): void
    {
        $_POST['exception'] = [];

        $form = $this->errorReport->getForm();
        self::assertStringContainsString('<pre class="pre-scrollable">[]</pre>', $form);

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
            'url' => 'http://pma.7.3.local/index.php?route=/table/sql&db=aaaaa&table=a&server=14',
        ];
        $_POST['description'] = 'description';

        $config = Config::getInstance();
        $report = [
            'pma_version' => Version::VERSION,
            'browser_name' => $config->get('PMA_USR_BROWSER_AGENT'),
            'browser_version' => $config->get('PMA_USR_BROWSER_VER'),
            'user_os' => $config->get('PMA_USR_OS'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'user_agent_string' => $_SERVER['HTTP_USER_AGENT'],
            'locale' => $_COOKIE['pma_lang'],
            'configuration_storage' => 'disabled',
            'php_version' => phpversion(),
            'script_name' => 'index.php',
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
                'uri' => 'index.php?route=%2Ftable%2Fsql',
            ],
            'steps' => $_POST['description'],
        ];
        $expectedData = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $form = $this->errorReport->getForm();
        self::assertStringContainsString(
            '<pre class="pre-scrollable">' . htmlspecialchars((string) $expectedData, ENT_QUOTES) . '</pre>',
            $form,
        );
    }

    public function testTruncateJsTrace(): void
    {
        $context = [
            '        success: function (response) {',
            '            Functions.ajaxRemoveMessage($msgbox);',
            '            if (response.success) {',
            '                // Get the column min value.',
            '                var min = response.column_data.min',
            '                    ? \'(\' + window.Messages.strColumnMin +',
            '    this.completion.cm.removeKeyMap(this.keyMap);',
            '                        \' \' + response.column_data.min + \')\'',
            '                    : \'\';',
            '    if (this.completion.options.closeOnUnfocus) {',
            '      cm.off("blur", this.onBlur);',
        ];

        $data = [
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
                [
                    'func' => 'Object.fireWith [as resolveWith]',
                    'line' => '2',
                    'column' => '29039',
                    'context' => [
                        '/*! jQuery v3.5.1 | (c) JS Foundation and other contributors | jquery.org/license */',
                        '!function(e,t){"use strict";"object"==typeof module&&'
                            . '"object"==typeof module.exports?module.exports=e.document?t',
                    ],
                    'url' => 'js/vendor/jquery/jquery.min.js?v=5.1.0-rc2',
                    'scriptname' => 'js/vendor/jquery/jquery.min.js',
                ],
            ],
            'url' => 'http://pma.7.3.local/index.php?route=/table/sql&db=aaaaa&table=a&server=14',
        ];
        $_POST['exception'] = $data;

        $actual = $this->errorReport->getData('js');
        // Adjust the data
        unset($data['stack'][0]['url']);
        $data['stack'][0]['uri'] = 'js/vendor/codemirror/addon/hint/show-hint.js?v=4.8.6-dev';
        $data['stack'][0]['scriptname'] = 'js/vendor/codemirror/addon/hint/show-hint.js';
        unset($data['stack'][1]['url']);
        $data['stack'][1]['uri'] = 'js/vendor/jquery/jquery.min.js?v=5.1.0-rc2';
        unset($data['url']);
        $data['uri'] = 'index.php?route=%2Ftable%2Fsql';
        $data['stack'][1]['context'][0] = '/*! jQuery v3.5.1 | (c) JS Foundation'
                                        . ' and other contributors | jquery.org/l//...';
        $data['stack'][1]['context'][1] = '!function(e,t){"use strict";"object"='
                                        . '=typeof module&&"object"==typeof modul//...';

        self::assertSame($data, $actual['exception']);
    }

    /**
     * The urls to be tested for sanitization
     *
     * @return mixed[][]
     */
    public static function urlsToSanitize(): array
    {
        return [
            ['', ['index.php?', 'index.php']],
            [
                'http://localhost/js/vendor/codemirror/addon/hint/show-hint.js?v=4.8.6-dev',
                [
                    'js/vendor/codemirror/addon/hint/show-hint.js?v=4.8.6-dev',
                    'js/vendor/codemirror/addon/hint/show-hint.js',
                ],
            ],
            ['http://pma.7.3.local/tbl_sql.php?db=aaaaa&table=a&server=14', ['tbl_sql.php?', 'tbl_sql.php']],
            ['http://pma.7.3.local/tbl_sql.php?db=aaaaa;table=a;server=14', ['tbl_sql.php?', 'tbl_sql.php']],
            ['https://pma.7.3.local/tbl_sql.php?db=aaaaa;table=a;server=14', ['tbl_sql.php?', 'tbl_sql.php']],
            ['https://pma.7.3.local/fileDotPhp.php?db=aaaaa;table=a;server=14', ['fileDotPhp.php?','fileDotPhp.php']],
            [
                'https://pma.7.3.local/secretFolder/fileDotPhp.php?db=aaaaa;table=a;server=14',
                ['fileDotPhp.php?', 'fileDotPhp.php'],
            ],
            [
                'http://7.2.local/@williamdes/theREALphpMyAdminREPO/js/vendor/jquery/jquery-ui.min.js?v=5.0.0-dev',
                ['js/vendor/jquery/jquery-ui.min.js?v=5.0.0-dev', 'js/vendor/jquery/jquery-ui.min.js'],
            ],
        ];
    }

    /**
     * Test the url sanitization
     *
     * @param string  $url    The url to test
     * @param mixed[] $result The result
     */
    #[DataProvider('urlsToSanitize')]
    public function testSanitizeUrl(string $url, array $result): void
    {
        // $this->errorReport->sanitizeUrl
        self::assertSame(
            $result,
            $this->callFunction(
                $this->errorReport,
                ErrorReport::class,
                'sanitizeUrl',
                [$url],
            ),
        );
    }
}
