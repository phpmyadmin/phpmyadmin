<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Transformation;

use PhpMyAdmin\Controllers\Transformation\OverviewController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function __;

/** @covers \PhpMyAdmin\Controllers\Transformation\OverviewController */
class OverviewControllerTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setGlobalConfig();

        parent::setTheme();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['text_dir'] = 'ltr';

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
    }

    public function testIndexAction(): void
    {
        $response = new ResponseRenderer();

        $controller = new OverviewController($response, new Template(), new Transformations());

        $controller($this->createStub(ServerRequest::class));
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString(
            __('Available media types'),
            $actual,
        );
        $this->assertStringContainsString(
            'id="transformation">' . __('Available browser display transformations'),
            $actual,
        );
        $this->assertStringContainsString(
            'id="input_transformation">' . __('Available input transformations'),
            $actual,
        );
        $this->assertStringContainsString('Text/Plain', $actual);
        $this->assertStringContainsString('Image/JPEG: Inline', $actual);
        $this->assertStringContainsString('Displays a clickable thumbnail.', $actual);
        $this->assertStringContainsString('Image/JPEG: Upload', $actual);
        $this->assertStringContainsString('Image upload functionality which also displays a thumbnail.', $actual);
    }
}
