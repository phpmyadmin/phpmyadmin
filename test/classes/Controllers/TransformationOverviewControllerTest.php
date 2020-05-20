<?php
/**
 * Holds TransformationOverviewControllerTest class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\TransformationOverviewController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Tests for TransformationOverviewController class
 */
class TransformationOverviewControllerTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
    }

    public function testIndexAction(): void
    {
        $response = new Response();

        $controller = new TransformationOverviewController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            new Transformations()
        );

        $controller->index();
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString(
            __('Available media types'),
            $actual
        );
        $this->assertStringContainsString(
            'id="transformation">' . __('Available browser display transformations'),
            $actual
        );
        $this->assertStringContainsString(
            'id="input_transformation">' . __('Available input transformations'),
            $actual
        );
        $this->assertStringContainsString(
            'Text/Plain',
            $actual
        );
        $this->assertStringContainsString(
            'Image/JPEG: Inline',
            $actual
        );
        $this->assertStringContainsString(
            'Displays a clickable thumbnail.',
            $actual
        );
        $this->assertStringContainsString(
            'Image/JPEG: Upload',
            $actual
        );
        $this->assertStringContainsString(
            'Image upload functionality which also displays a thumbnail.',
            $actual
        );
    }
}
