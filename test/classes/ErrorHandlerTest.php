<?php
/**
 * Tests for ErrorHandler
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ErrorHandler;
use const E_RECOVERABLE_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

class ErrorHandlerTest extends AbstractTestCase
{
    /** @var ErrorHandler */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $this->object = new ErrorHandler();
        $_SESSION['errors'] = [];
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['SendErrorReports'] = 'always';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
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
    public function providerForTestHandleError(): array
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
            $this->assertEquals('', $output);
        } else {
            $this->assertNotEmpty($output_show);// Useless check
            $this->assertStringContainsString($output_hide, $output);
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

        $this->assertIsString($output_hide);// Useless check
        $this->assertStringContainsString(
            $output_show,
            $this->object->getDispErrors()
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
            []
        );
        $this->assertArrayNotHasKey('errors', $_SESSION);
    }

    /**
     * Test for countErrors
     *
     * @group medium
     */
    public function testCountErrors(): void
    {
        $this->object->addError(
            'Compile Error',
            E_WARNING,
            'error.txt',
            15
        );
        $this->assertEquals(
            1,
            $this->object->countErrors()
        );
    }

    /**
     * Test for sliceErrors
     *
     * @group medium
     */
    public function testSliceErrors(): void
    {
        $this->object->addError(
            'Compile Error',
            E_WARNING,
            'error.txt',
            15
        );
        $this->assertEquals(
            1,
            $this->object->countErrors()
        );
        $this->assertEquals(
            [],
            $this->object->sliceErrors(1)
        );
        $this->assertEquals(
            1,
            $this->object->countErrors()
        );
        $this->assertCount(
            1,
            $this->object->sliceErrors(0)
        );
        $this->assertEquals(
            0,
            $this->object->countErrors()
        );
    }

    /**
     * Test for countUserErrors
     */
    public function testCountUserErrors(): void
    {
        $this->object->addError(
            'Compile Error',
            E_WARNING,
            'error.txt',
            15
        );
        $this->assertEquals(
            0,
            $this->object->countUserErrors()
        );
        $this->object->addError(
            'Compile Error',
            E_USER_WARNING,
            'error.txt',
            15
        );
        $this->assertEquals(
            1,
            $this->object->countUserErrors()
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
            $this->object->countDisplayErrors()
        );
    }

    /**
     * Test for countDisplayErrors
     */
    public function testCountDisplayErrorsForDisplayFalse(): void
    {
        $this->assertEquals(
            0,
            $this->object->countDisplayErrors()
        );
    }

    /**
     * Test for hasDisplayErrors
     */
    public function testHasDisplayErrors(): void
    {
        $this->assertFalse($this->object->hasDisplayErrors());
    }
}
