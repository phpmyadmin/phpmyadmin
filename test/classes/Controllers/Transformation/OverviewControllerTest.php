<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Transformation;

use PhpMyAdmin\Controllers\Transformation\OverviewController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function __;

/**
 * @covers \PhpMyAdmin\Controllers\Transformation\OverviewController
 */
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
        $GLOBALS['text_dir'] = 'ltr';

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
    }

    public function testIndexAction(): void
    {
        $response = new ResponseRenderer();

        $controller = new OverviewController($response, new Template(), new Transformations());

        $controller();
        $actual = $response->getHTMLResult();

        self::assertStringContainsString(__('Available media types'), $actual);
        self::assertStringContainsString(
            'id="transformation">' . __('Available browser display transformations'),
            $actual
        );
        self::assertStringContainsString('id="input_transformation">' . __('Available input transformations'), $actual);
        self::assertStringContainsString('Text/Plain', $actual);
        self::assertStringContainsString('Image/JPEG: Inline', $actual);
        self::assertStringContainsString('Displays a clickable thumbnail.', $actual);
        self::assertStringContainsString('Image/JPEG: Upload', $actual);
        self::assertStringContainsString('Image upload functionality which also displays a thumbnail.', $actual);
    }
}
