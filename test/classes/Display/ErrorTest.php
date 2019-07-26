<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Display\Error
 *
 * @package PhpMyAdmin-test
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

require_once ROOT_PATH . 'libraries/config.default.php';

/**
 * ErrorTest class
 *
 * this class is for testing PhpMyAdmin\Display\Error functions
 *
 * @package PhpMyAdmin-test
 */
class ErrorTest extends TestCase
{
    /**
     * Test for Error::display
     *
     * @return void
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
     * @return void
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
