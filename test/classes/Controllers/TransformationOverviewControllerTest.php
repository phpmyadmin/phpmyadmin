<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds TransformationOverviewControllerTest class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\TransformationOverviewController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TransformationOverviewController class
 *
 * @package PhpMyAdmin-test
 */
class TransformationOverviewControllerTest extends TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
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

    /**
     * @return void
     */
    public function testIndexAction(): void
    {
        $controller = new TransformationOverviewController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Transformations()
        );

        $actual = $controller->indexAction();

        $this->assertContains(
            __('Available MIME types'),
            $actual
        );
        $this->assertContains(
            'id="transformation">' . __('Available browser display transformations'),
            $actual
        );
        $this->assertContains(
            'id="input_transformation">' . __('Available input transformations'),
            $actual
        );
        $this->assertContains(
            'Text/Plain',
            $actual
        );
        $this->assertContains(
            'Image/JPEG: Inline',
            $actual
        );
        $this->assertContains(
            'Displays a clickable thumbnail.',
            $actual
        );
        $this->assertContains(
            'Image/JPEG: Upload',
            $actual
        );
        $this->assertContains(
            'Image upload functionality which also displays a thumbnail.',
            $actual
        );
    }
}
