<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Error;

use Exception;
use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Error\Error;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use Throwable;

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

#[CoversClass(ErrorHandler::class)]
#[Medium]
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

        Current::$lang = 'en';
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $this->object = new ErrorHandler();
        $_SESSION['errors'] = [];
        $config = Config::getInstance();
        $config->set('environment', 'production');
        $config->set('SendErrorReports', 'always');
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

    public function testUniqueness(): void
    {
        $instanceOne = ErrorHandler::getInstance();
        $instanceTwo = ErrorHandler::getInstance();
        self::assertSame($instanceOne, $instanceTwo);
    }

    /** @return array<array{int, string, string, int, string, string}> */
    public static function providerForTestHandleError(): array
    {
        return [
            [E_RECOVERABLE_ERROR, 'Compile Error', 'error.txt', 12, 'never', ''],
            [E_RECOVERABLE_ERROR, 'Compile Error', 'error.txt', 12, 'always', 'Compile Error'],
            [E_RECOVERABLE_ERROR, 'Compile Error', 'error.txt', 12, 'ask', 'Compile Error'],
            [E_USER_NOTICE, 'User notice', 'error.txt', 12, 'never', 'User notice'],
            [E_USER_NOTICE, 'User notice', 'error.txt', 12, 'always', 'User notice'],
            [E_USER_NOTICE, 'User notice', 'error.txt', 12, 'ask', 'User notice'],
        ];
    }

    #[DataProvider('providerForTestHandleError')]
    public function testGetDisplayErrors(
        int $errorNumber,
        string $errorMessage,
        string $errorFile,
        int $errorLine,
        string $reportErrorConfig,
        string $expected,
    ): void {
        $config = new Config();
        $config->set('environment', 'production');
        $config->set('SendErrorReports', $reportErrorConfig);
        Config::$instance = $config;

        $error = new Error($errorNumber, $errorMessage, $errorFile, $errorLine);
        $_SESSION['errors'] = [$error->getHash() => $error];

        $handler = new ErrorHandler();
        if ($expected === '') {
            self::assertSame('', $handler->getDispErrors());
        } else {
            self::assertStringContainsString($expected, $handler->getDispErrors());
        }
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
        self::assertArrayNotHasKey('errors', $_SESSION);
    }

    /**
     * Test for countErrors
     */
    public function testCountErrors(): void
    {
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 15);
        self::assertSame(
            1,
            $this->object->countErrors(),
        );
    }

    #[DataProvider('addErrorProvider')]
    public function testAddError(int $errorNumber, string $expected): void
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->addError('[em]Error[/em]', $errorNumber, 'error.txt', 15);
        $errors = $errorHandler->getCurrentErrors();
        self::assertCount(1, $errors);
        $error = array_pop($errors);
        self::assertSame($errorNumber, $error->getErrorNumber());
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
     */
    public function testSliceErrors(): void
    {
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 15);
        $this->object->addError('Compile Error', E_WARNING, 'error.txt', 16);
        self::assertSame(
            2,
            $this->object->countErrors(),
        );
        self::assertSame(
            [],
            $this->object->sliceErrors(2),
        );
        self::assertSame(
            2,
            $this->object->countErrors(),
        );
        self::assertCount(
            1,
            $this->object->sliceErrors(1),
        );
        self::assertSame(
            1,
            $this->object->countErrors(),
        );
    }

    /**
     * Test for sliceErrors with 10 elements as an example
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
        self::assertSame(
            [$firstKey => $elements[$firstKey]],
            $elements,
        );
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
        self::assertSame(
            0,
            $this->object->countUserErrors(),
        );
        $this->object->addError('Compile Error', E_USER_WARNING, 'error.txt', 15);
        self::assertSame(
            1,
            $this->object->countUserErrors(),
        );
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
        Current::$lang = 'en';
        Config::getInstance()->set('environment', 'development');
        $errorHandler = new ErrorHandler();
        self::assertSame([], $errorHandler->getCurrentErrors());
        try {
            $errorHandler->handleException(new Exception('Exception message.'));
        } catch (Throwable $throwable) {
        }

        self::assertInstanceOf(ExitException::class, $throwable ?? null);
        $output = $this->getActualOutputForAssertion();
        $errors = $errorHandler->getCurrentErrors();
        self::assertCount(1, $errors);
        $error = array_pop($errors);
        self::assertSame('Exception: Exception message.', $error->getOnlyMessage());
        self::assertStringContainsString($error->getDisplay(), $output);
        self::assertStringContainsString('Internal error', $output);
        self::assertStringContainsString('ErrorHandlerTest.php#' . $error->getLine(), $output);
        self::assertStringContainsString('Exception: Exception message.', $output);
    }

    public function testHandleExceptionForProdEnv(): void
    {
        Current::$lang = 'en';
        Config::getInstance()->set('environment', 'production');
        $errorHandler = new ErrorHandler();
        self::assertSame([], $errorHandler->getCurrentErrors());
        try {
            $errorHandler->handleException(new Exception('Exception message.'));
        } catch (Throwable $throwable) {
        }

        self::assertInstanceOf(ExitException::class, $throwable ?? null);
        $output = $this->getActualOutputForAssertion();
        $errors = $errorHandler->getCurrentErrors();
        self::assertCount(1, $errors);
        $error = array_pop($errors);
        self::assertSame('Exception: Exception message.', $error->getOnlyMessage());
        self::assertStringContainsString($error->getDisplay(), $output);
        self::assertStringContainsString('Exception: Exception message.', $output);
        self::assertStringNotContainsString('ErrorHandlerTest.php#' . $error->getLine(), $output);
    }

    public function testAddErrorWithFatalError(): void
    {
        Current::$lang = 'en';
        Config::getInstance()->set('environment', 'production');
        $errorHandler = new ErrorHandler();
        try {
            $errorHandler->addError('Fatal error message!', E_ERROR, './file/name', 1);
        } catch (Throwable $exception) {
        }

        self::assertInstanceOf(ExitException::class, $exception ?? null);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedStart = <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
            <head><title>Error: Fatal error message!</title></head>
            <body>
            <div class="alert alert-danger" role="alert"><p><strong>Error</strong> in name#1</p><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Fatal error message!<p class="mt-3"><strong>Backtrace</strong></p><ol class="list-group"><li class="list-group-item">
            HTML;
        // phpcs:enable
        $output = $this->getActualOutputForAssertion();
        self::assertStringStartsWith($expectedStart, $output);
        self::assertStringEndsWith('</li></ol></div>' . "\n\n" . '</body>' . "\n" . '</html>', $output);
    }
}
