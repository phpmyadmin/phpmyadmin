<?php
/**
 * Holds TransformationOverviewControllerTest class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PhpMyAdmin\Controllers\TransformationOverviewController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\Transformations;

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
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
    }

    public function testIndexAction(): void
    {
        $responseRenderer = new Response();

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $request = $creator->fromGlobals();
        $response = $psr17Factory->createResponse();

        $controller = new TransformationOverviewController(
            $responseRenderer,
            $GLOBALS['dbi'],
            new Template(),
            new Transformations()
        );

        $controller->index($request, $response);
        $actual = $responseRenderer->getHTMLResult();

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
