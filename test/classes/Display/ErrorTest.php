<?php
/**
 * tests for PhpMyAdmin\Display\Error
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Display\Error;
use PhpMyAdmin\Template;
use PHPUnit\Framework\TestCase;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * ErrorTest class
 *
 * this class is for testing PhpMyAdmin\Display\Error functions
 */
class ErrorTest extends TestCase
{
    /**
     * Test for Error::display
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testDisplaySimple(): void
    {
        $lang = 'fr';
        $dir = 'ltr';
        $error_header = 'Error';
        $error_message = 'Failure';

        $html = Error::display(new Template(), $lang, $dir, $error_header, $error_message);

        $this->assertStringContainsString(
            '<html lang="fr" dir="ltr">',
            $html
        );
        $this->assertStringContainsString(
            'Failure',
            $html
        );
    }

    /**
     * Test for Error::display
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testDisplayToSanitize(): void
    {
        $lang = 'fr';
        $dir = 'ltr';
        $error_header = 'Error';
        $error_message = '[em]Failure[/em]';

        $html = Error::display(new Template(), $lang, $dir, $error_header, $error_message);

        $this->assertStringContainsString(
            '<html lang="fr" dir="ltr">',
            $html
        );
        $this->assertStringContainsString(
            '<em>Failure</em>',
            $html
        );
    }
}
