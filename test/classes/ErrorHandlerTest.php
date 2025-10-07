<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use Exception;
use PhpMyAdmin\Error;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseRendererStub;
use ReflectionProperty;

use function array_keys;
use function array_pop;

use const E_COMPILE_WARNING;
use const E_CORE_WARNING;
use const E_ERROR;
use const E_NOTICE;
use const E_RECOVERABLE_ERROR;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\ErrorHandler
 */
class ErrorHandlerTest extends AbstractTestCase
{
    /** @var ErrorHandler */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new ErrorHandler();
        $_SESSION['errors'] = [];
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['environment'] = 'production';
        $GLOBALS['cfg']['SendErrorReports'] = 'always';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Data provider for testHandleError
     *
     * @return array data for testHandleError
     */
    public static function providerForTestHandleError(): array
    {
        return [
            [
                E_RECOVERABLE_ERROR,
                'Compile Error',
                'error.txt',
                12,
                'Compile Error',
                '',
            ],
            [
                E_USER_NOTICE,
                'User notice',
                'error.txt',
                12,
                'User notice',
                'User notice',
            ],
        ];
    }

    /**
     * Test for getDispErrors when PHP errors are not shown
     *
     * @param int    $errno       error number
     * @param string $errstr      error string
     * @param string $errfile     error file
     * @param int    $errline     error line
     * @param string $output_show expected output if showing of errors is
     *                            enabled
     * @param string $output_hide expected output if showing of errors is
     *                            disabled and 'sendErrorReports' is set to 'never'
     *
     * @dataProvider providerForTestHandleError
     */
    public function testGetDispErrorsForDisplayFalse(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline,
        string $output_show,
        string $output_hide
    ): void {
        // TODO: Add other test cases for all combination of 'sendErrorReports'
        $GLOBALS['cfg']['SendErrorReports'] = 'never';

        $this->object->handleError($errno, $errstr, $errfile, $errline);

        $output = $this->object->getDispErrors();

        if ($output_hide === '') {
            self::assertSame('', $output);
        } else {
            self::assertNotEmpty($output_show);// Useless check
            self::assertStringContainsString($output_hide, $output);
        }
    }

    /**
     * Test for getDispErrors when PHP errors are shown
     *
     * @param int    $errno       error number
     * @param string $errstr      error string
     * @param string $errfile     error file
     * @param int    $errline     error line
     * @param string $output_show expected output if showing of errors is
     *                            enabled
     * @param string $output_hide expected output if showing of errors is
     *                            disabled
     *
     * @dataProvider providerForTestHandleError
     * @requires PHPUnit < 10
     */
    public function testGetDispErrorsForDisplayTrue(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline,
        string $output_show,
        string $output_hide
    ): void {
        $this->object->handleError($errno, $errstr, $errfile, $errline);

        self::assertIsString($output_hide);// Useless check
        self::assertStringContainsString($output_show, $this->object->getDispErrors());
    }

    /**
     * Test for checkSavedErrors
     */
    public function testCheckSavedErrors(): void
    {
        $this->callFunction(
            $this->object,
            ErrorHandler::class,
            'checkSavedErrors',
            []
        );
        self::assertArrayNotHasKey('errors', $_SESSION);
    }

    /**
     * Test for countErrors
     *
     * @group medium
     */
    public function testCountErrors(): void
    {
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 15);
        self::assertSame(1, $this->object->countErrors());
    }

    /** @dataProvider addErrorProvider */
    public function testAddError(int $errorNumber, string $expected): void
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->addError('[em]Error[/em]', $errorNumber, 'error.txt', 15);
        $errors = $errorHandler->getCurrentErrors();
        self::assertCount(1, $errors);
        $error = array_pop($errors);
        self::assertSame($errorNumber, $error->getNumber());
        self::assertSame($expected, $error->getMessage());
    }

    /** @return iterable<string, array{int, string}> */
    public static function addErrorProvider(): iterable
    {
        yield 'E_STRICT' => [@E_STRICT, '[em]Error[/em]'];
        yield 'E_NOTICE' => [E_NOTICE, '[em]Error[/em]'];
        yield 'E_WARNING' => [E_WARNING, '[em]Error[/em]'];
        yield 'E_CORE_WARNING' => [E_CORE_WARNING, '[em]Error[/em]'];
        yield 'E_COMPILE_WARNING' => [E_COMPILE_WARNING, '[em]Error[/em]'];
        yield 'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, '[em]Error[/em]'];
        yield 'E_USER_NOTICE' => [E_USER_NOTICE, '<em>Error</em>'];
        yield 'E_USER_WARNING' => [E_USER_WARNING, '<em>Error</em>'];
        yield 'E_USER_ERROR' => [E_USER_ERROR, '<em>Error</em>'];
        yield 'E_USER_DEPRECATED' => [E_USER_DEPRECATED, '<em>Error</em>'];
    }

    /**
     * Test for sliceErrors
     *
     * @group medium
     */
    public function testSliceErrors(): void
    {
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 15);
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 16);
        self::assertSame(2, $this->object->countErrors());
        self::assertSame([], $this->object->sliceErrors(2));
        self::assertSame(2, $this->object->countErrors());
        self::assertCount(1, $this->object->sliceErrors(1));
        self::assertSame(1, $this->object->countErrors());
    }

    /**
     * Test for sliceErrors with 10 elements as an example
     *
     * @group medium
     */
    public function testSliceErrorsOtherExample(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->object->addError('Compile Error', E_WARNING, 'error.txt', $i);
        }

        // 10 initial items
        self::assertSame(10, $this->object->countErrors());
        self::assertCount(10, $this->object->getCurrentErrors());

        // slice 9 elements, returns one 10 - 9
        $elements = $this->object->sliceErrors(9);
        $firstKey = array_keys($elements)[0];

        // Gives the last element
        self::assertSame([
            $firstKey => $elements[$firstKey],
        ], $elements);
        self::assertCount(9, $this->object->getCurrentErrors());
        self::assertSame(9, $this->object->countErrors());

        // Slice as much as there is (9), does nothing
        $elements = $this->object->sliceErrors(9);
        self::assertSame([], $elements);
        self::assertCount(9, $this->object->getCurrentErrors());
        self::assertSame(9, $this->object->countErrors());

        // Slice 0, removes everything
        $elements = $this->object->sliceErrors(0);
        self::assertCount(9, $elements);
        self::assertCount(0, $this->object->getCurrentErrors());
        self::assertSame(0, $this->object->countErrors());
    }

    /**
     * Test for countUserErrors
     */
    public function testCountUserErrors(): void
    {
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 15);
        self::assertSame(0, $this->object->countUserErrors());
        $this->object->addError('Compile Error', E_USER_WARNING, 'error.txt', 15);
        self::assertSame(1, $this->object->countUserErrors());
    }

    /**
     * Test for hasUserErrors
     */
    public function testHasUserErrors(): void
    {
        self::assertFalse($this->object->hasUserErrors());
    }

    /**
     * Test for hasErrors
     */
    public function testHasErrors(): void
    {
        self::assertFalse($this->object->hasErrors());
    }

    /**
     * Test for countDisplayErrors
     */
    public function testCountDisplayErrorsForDisplayTrue(): void
    {
        self::assertSame(0, $this->object->countDisplayErrors());
    }

    /**
     * Test for countDisplayErrors
     */
    public function testCountDisplayErrorsForDisplayFalse(): void
    {
        self::assertSame(0, $this->object->countDisplayErrors());
    }

    /**
     * Test for hasDisplayErrors
     */
    public function testHasDisplayErrors(): void
    {
        self::assertFalse($this->object->hasDisplayErrors());
    }

    public function testHandleExceptionForDevEnv(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['config']->set('environment', 'development');
        $responseStub = new ResponseRendererStub();
        $property = new ReflectionProperty(ResponseRenderer::class, 'instance');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        $property->setValue(null, $responseStub);
        $responseStub->setHeadersSent(true);
        $errorHandler = new ErrorHandler();
        self::assertSame([], $errorHandler->getCurrentErrors());
        $errorHandler->handleException(new Exception('Exception message.'));
        $output = $responseStub->getHTMLResult();
        $errors = $errorHandler->getCurrentErrors();
        self::assertCount(1, $errors);
        $error = array_pop($errors);
        self::assertInstanceOf(Error::class, $error);
        self::assertSame('Exception: Exception message.', $error->getOnlyMessage());
        self::assertStringContainsString($error->getDisplay(), $output);
        self::assertStringContainsString('Internal error', $output);
        self::assertStringContainsString('ErrorHandlerTest.php#' . $error->getLine(), $output);
        self::assertStringContainsString('Exception: Exception message.', $output);
    }

    public function testHandleExceptionForProdEnv(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['config']->set('environment', 'production');
        $responseStub = new ResponseRendererStub();
        $property = new ReflectionProperty(ResponseRenderer::class, 'instance');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        $property->setValue(null, $responseStub);
        $responseStub->setHeadersSent(true);
        $errorHandler = new ErrorHandler();
        self::assertSame([], $errorHandler->getCurrentErrors());
        $errorHandler->handleException(new Exception('Exception message.'));
        $output = $responseStub->getHTMLResult();
        $errors = $errorHandler->getCurrentErrors();
        self::assertCount(1, $errors);
        $error = array_pop($errors);
        self::assertInstanceOf(Error::class, $error);
        self::assertSame('Exception: Exception message.', $error->getOnlyMessage());
        self::assertStringContainsString($error->getDisplay(), $output);
        self::assertStringContainsString('Exception: Exception message.', $output);
        self::assertStringNotContainsString('Internal error', $output);
        self::assertStringNotContainsString('ErrorHandlerTest.php#' . $error->getLine(), $output);
    }

    public function testAddErrorWithFatalErrorAndHeadersSent(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['config']->set('environment', 'production');
        $responseStub = new ResponseRendererStub();
        $property = new ReflectionProperty(ResponseRenderer::class, 'instance');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        $property->setValue(null, $responseStub);
        $responseStub->setHeadersSent(true);
        $errorHandler = new ErrorHandler();
        $errorHandler->addError('Fatal error message!', E_ERROR, './file/name', 1);
        $expectedStart = <<<'HTML'
<div class="alert alert-danger" role="alert"><strong>Error</strong> in name#1<br>
<img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Fatal error message!<br>
<br>
<strong>Backtrace</strong><br>
<br>
HTML;

        $output = $responseStub->getHTMLResult();
        self::assertStringStartsWith($expectedStart, $output);
        self::assertStringEndsWith('</div></body></html>', $output);
    }

    public function testAddErrorWithFatalErrorAndHeadersNotSent(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['config']->set('environment', 'production');
        $responseStub = new ResponseRendererStub();
        $property = new ReflectionProperty(ResponseRenderer::class, 'instance');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        $property->setValue(null, $responseStub);
        $responseStub->setHeadersSent(false);
        $errorHandler = new ErrorHandler();
        $errorHandler->addError('Fatal error message!', E_ERROR, './file/name', 1);
        $expectedStart = <<<'HTML'
<html><head><title>Error: Fatal error message!</title></head>
<div class="alert alert-danger" role="alert"><strong>Error</strong> in name#1<br>
<img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Fatal error message!<br>
<br>
<strong>Backtrace</strong><br>
<br>
HTML;

        $output = $responseStub->getHTMLResult();
        self::assertStringStartsWith($expectedStart, $output);
        self::assertStringEndsWith('</div></body></html>', $output);
    }
}
