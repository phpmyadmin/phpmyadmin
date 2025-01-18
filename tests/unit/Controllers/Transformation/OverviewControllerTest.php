<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Transformation;

use PhpMyAdmin\Controllers\Transformation\OverviewController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;

#[CoversClass(OverviewController::class)]
class OverviewControllerTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setGlobalConfig();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        Current::$database = 'db';
        Current::$table = 'table';
    }

    public function testIndexAction(): void
    {
        $response = new ResponseRenderer();

        $controller = new OverviewController($response, new Transformations());

        $controller(self::createStub(ServerRequest::class));
        $actual = $response->getHTMLResult();

        self::assertStringContainsString(
            __('Available media types'),
            $actual,
        );
        self::assertStringContainsString(
            'id="transformation">' . __('Available browser display transformations'),
            $actual,
        );
        self::assertStringContainsString(
            'id="input_transformation">' . __('Available input transformations'),
            $actual,
        );
        self::assertStringContainsString('Text/Plain', $actual);
        self::assertStringContainsString('Image/JPEG: Inline', $actual);
        self::assertStringContainsString('Displays a clickable thumbnail.', $actual);
        self::assertStringContainsString('Image/JPEG: Upload', $actual);
        self::assertStringContainsString('Image upload functionality which also displays a thumbnail.', $actual);
    }
}
