<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use Exception;
use PhpMyAdmin\Error;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseRendererStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;
use Throwable;

use function array_keys;
use function array_pop;
use function count;

use const E_ERROR;
use const E_RECOVERABLE_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

#[CoversClass(ErrorHandler::class)]
class ErrorHandlerTest extends AbstractTestCase
{
    protected ErrorHandler $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['lang'] = 'en';
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
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
     * @return array<array{int, string, string, int, string, string}>
     */
    public static function providerForTestHandleError(): array
    {
        return [
            [E_RECOVERABLE_ERROR, 'Compile Error', 'error.txt', 12, 'Compile Error', ''],
            [E_USER_NOTICE, 'User notice', 'error.txt', 12, 'User notice', 'User notice'],
        ];
    }

    /**
     * Test for getDispErrors when PHP errors are not shown
     *
     * @param int    $errno      error number
     * @param string $errstr     error string
     * @param string $errfile    error file
     * @param int    $errline    error line
     * @param string $outputShow expected output if showing of errors is enabled
     * @param string $outputHide expected output if showing of errors is
     *                           disabled and 'sendErrorReports' is set to 'never'
     */
    #[DataProvider('providerForTestHandleError')]
    public function testGetDispErrorsForDisplayFalse(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline,
        string $outputShow,
        string $outputHide,
    ): void {
        // TODO: Add other test cases for all combination of 'sendErrorReports'
        $GLOBALS['cfg']['SendErrorReports'] = 'never';

        $this->object->handleError($errno, $errstr, $errfile, $errline);

        $output = $this->object->getDispErrors();

        if ($outputHide === '') {
            $this->assertEquals('', $output);
        } else {
            $this->assertNotEmpty($outputShow);// Useless check
            $this->assertStringContainsString($outputHide, $output);
        }
    }

    /**
     * Test for getDispErrors when PHP errors are shown
     *
     * @param int    $errno      error number
     * @param string $errstr     error string
     * @param string $errfile    error file
     * @param int    $errline    error line
     * @param string $outputShow expected output if showing of errors is enabled
     * @param string $outputHide expected output if showing of errors is disabled
     */
    #[DataProvider('providerForTestHandleError')]
    public function testGetDispErrorsForDisplayTrue(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline,
        string $outputShow,
        string $outputHide,
    ): void {
        $this->object->handleError($errno, $errstr, $errfile, $errline);

        $this->assertIsString($outputHide);// Useless check
        $this->assertStringContainsString(
            $outputShow,
            $this->object->getDispErrors(),
        );
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
            [],
        );
        $this->assertArrayNotHasKey('errors', $_SESSION);
    }

    /**
     * Test for countErrors
     */
    #[Group('medium')]
    public function testCountErrors(): void
    {
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 15);
        $this->assertEquals(
            1,
            $this->object->countErrors(),
        );
    }

    /**
     * Test for sliceErrors
     */
    #[Group('medium')]
    public function testSliceErrors(): void
    {
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 15);
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 16);
        $this->assertEquals(
            2,
            $this->object->countErrors(),
        );
        $this->assertEquals(
            [],
            $this->object->sliceErrors(2),
        );
        $this->assertEquals(
            2,
            $this->object->countErrors(),
        );
        $this->assertCount(
            1,
            $this->object->sliceErrors(1),
        );
        $this->assertEquals(
            1,
            $this->object->countErrors(),
        );
    }

    /**
     * Test for sliceErrors with 10 elements as an example
     */
    #[Group('medium')]
    public function testSliceErrorsOtherExample(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->object->addError('Compile Error', E_WARNING, 'error.txt', $i);
        }

        // 10 initial items
        $this->assertEquals(10, $this->object->countErrors());
        $this->assertEquals(10, count($this->object->getCurrentErrors()));

        // slice 9 elements, returns one 10 - 9
        $elements = $this->object->sliceErrors(9);
        $firstKey = array_keys($elements)[0];

        // Gives the last element
        $this->assertEquals(
            [$firstKey => $elements[$firstKey]],
            $elements,
        );
        $this->assertEquals(9, count($this->object->getCurrentErrors()));
        $this->assertEquals(9, $this->object->countErrors());

        // Slice as much as there is (9), does nothing
        $elements = $this->object->sliceErrors(9);
        $this->assertEquals([], $elements);
        $this->assertEquals(9, count($this->object->getCurrentErrors()));
        $this->assertEquals(9, $this->object->countErrors());

        // Slice 0, removes everything
        $elements = $this->object->sliceErrors(0);
        $this->assertEquals(9, count($elements));
        $this->assertEquals(0, count($this->object->getCurrentErrors()));
        $this->assertEquals(0, $this->object->countErrors());
    }

    /**
     * Test for countUserErrors
     */
    public function testCountUserErrors(): void
    {
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 15);
        $this->assertEquals(
            0,
            $this->object->countUserErrors(),
        );
        $this->object->addError('Compile Error', E_USER_WARNING, 'error.txt', 15);
        $this->assertEquals(
            1,
            $this->object->countUserErrors(),
        );
    }

    /**
     * Test for hasUserErrors
     */
    public function testHasUserErrors(): void
    {
        $this->assertFalse($this->object->hasUserErrors());
    }

    /**
     * Test for hasErrors
     */
    public function testHasErrors(): void
    {
        $this->assertFalse($this->object->hasErrors());
    }

    /**
     * Test for countDisplayErrors
     */
    public function testCountDisplayErrorsForDisplayTrue(): void
    {
        $this->assertEquals(
            0,
            $this->object->countDisplayErrors(),
        );
    }

    /**
     * Test for countDisplayErrors
     */
    public function testCountDisplayErrorsForDisplayFalse(): void
    {
        $this->assertEquals(
            0,
            $this->object->countDisplayErrors(),
        );
    }

    /**
     * Test for hasDisplayErrors
     */
    public function testHasDisplayErrors(): void
    {
        $this->assertFalse($this->object->hasDisplayErrors());
    }

    public function testHandleExceptionForDevEnv(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['config']->set('environment', 'development');
        $responseStub = new ResponseRendererStub();
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue($responseStub);
        $responseStub->setHeadersSent(true);
        $errorHandler = new ErrorHandler();
        $this->assertSame([], $errorHandler->getCurrentErrors());
        try {
            $errorHandler->handleException(new Exception('Exception message.'));
        } catch (Throwable $throwable) {
        }

        $this->assertInstanceOf(ExitException::class, $throwable ?? null);
        $output = $responseStub->getHTMLResult();
        $errors = $errorHandler->getCurrentErrors();
        $this->assertCount(1, $errors);
        $error = array_pop($errors);
        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame('Exception: Exception message.', $error->getOnlyMessage());
        $this->assertStringContainsString($error->getDisplay(), $output);
        $this->assertStringContainsString('Internal error', $output);
        $this->assertStringContainsString('ErrorHandlerTest.php#' . $error->getLine(), $output);
        $this->assertStringContainsString('Exception: Exception message.', $output);
    }

    public function testHandleExceptionForProdEnv(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['config']->set('environment', 'production');
        $responseStub = new ResponseRendererStub();
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue($responseStub);
        $responseStub->setHeadersSent(true);
        $errorHandler = new ErrorHandler();
        $this->assertSame([], $errorHandler->getCurrentErrors());
        try {
            $errorHandler->handleException(new Exception('Exception message.'));
        } catch (Throwable $throwable) {
        }

        $this->assertInstanceOf(ExitException::class, $throwable ?? null);
        $output = $responseStub->getHTMLResult();
        $errors = $errorHandler->getCurrentErrors();
        $this->assertCount(1, $errors);
        $error = array_pop($errors);
        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame('Exception: Exception message.', $error->getOnlyMessage());
        $this->assertStringContainsString($error->getDisplay(), $output);
        $this->assertStringContainsString('Exception: Exception message.', $output);
        $this->assertStringNotContainsString('ErrorHandlerTest.php#' . $error->getLine(), $output);
    }

    public function testAddErrorWithFatalErrorAndHeadersSent(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['config']->set('environment', 'production');
        $responseStub = new ResponseRendererStub();
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue($responseStub);
        $responseStub->setHeadersSent(true);
        $errorHandler = new ErrorHandler();
        try {
            $errorHandler->addError('Fatal error message!', E_ERROR, './file/name', 1);
        } catch (Throwable $exception) {
        }

        $this->assertInstanceOf(ExitException::class, $exception ?? null);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedStart = <<<'HTML'
<div class="alert alert-danger" role="alert"><p><strong>Error</strong> in name#1</p><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Fatal error message!<p class="mt-3"><strong>Backtrace</strong></p><ol class="list-group"><li class="list-group-item">
HTML;
        // phpcs:enable
        $output = $responseStub->getHTMLResult();
        $this->assertStringStartsWith($expectedStart, $output);
        $this->assertStringEndsWith('</li></ol></div>' . "\n" . '</body></html>', $output);
    }

    public function testAddErrorWithFatalErrorAndHeadersNotSent(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['config']->set('environment', 'production');
        $responseStub = new ResponseRendererStub();
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue($responseStub);
        $responseStub->setHeadersSent(false);
        $errorHandler = new ErrorHandler();
        try {
            $errorHandler->addError('Fatal error message!', E_ERROR, './file/name', 1);
        } catch (Throwable $exception) {
        }

        $this->assertInstanceOf(ExitException::class, $exception ?? null);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedStart = <<<'HTML'
<html><head><title>Error: Fatal error message!</title></head>
<div class="alert alert-danger" role="alert"><p><strong>Error</strong> in name#1</p><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Fatal error message!<p class="mt-3"><strong>Backtrace</strong></p><ol class="list-group"><li class="list-group-item">
HTML;
        // phpcs:enable
        $output = $responseStub->getHTMLResult();
        $this->assertStringStartsWith($expectedStart, $output);
        $this->assertStringEndsWith('</li></ol></div>' . "\n" . '</body></html>', $output);
    }
}
