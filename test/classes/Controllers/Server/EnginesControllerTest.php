<?php
/**
 * Holds EnginesControllerTest class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;
use function htmlspecialchars;

/**
 * Tests for EnginesController class
 */
class EnginesControllerTest extends AbstractTestCase
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
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testIndex(): void
    {
        $response = new Response();

        $controller = new EnginesController(
            $response,
            $GLOBALS['dbi'],
            new Template()
        );

        $controller->index();
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString(
            '<th>Storage Engine</th>',
            $actual
        );
        $this->assertStringContainsString(
            '<th>Description</th>',
            $actual
        );

        $this->assertStringContainsString(
            '<td>Federated MySQL storage engine</td>',
            $actual
        );
        $this->assertStringContainsString(
            'FEDERATED',
            $actual
        );
        $this->assertStringContainsString(
            'index.php?route=/server/engines/FEDERATED',
            $actual
        );

        $this->assertStringContainsString(
            '<td>dummy comment</td>',
            $actual
        );
        $this->assertStringContainsString(
            'dummy',
            $actual
        );
        $this->assertStringContainsString(
            'index.php?route=/server/engines/dummy',
            $actual
        );
    }

    public function testShow(): void
    {
        $response = new Response();

        $controller = new EnginesController(
            $response,
            $GLOBALS['dbi'],
            new Template()
        );

        $controller->show([
            'engine' => 'Pbxt',
            'page' => 'page',
        ]);
        $actual = $response->getHTMLResult();

        $enginePlugin = StorageEngine::getEngine('Pbxt');

        $this->assertStringContainsString(
            htmlspecialchars($enginePlugin->getTitle()),
            $actual
        );

        $this->assertStringContainsString(
            MySQLDocumentation::show($enginePlugin->getMysqlHelpPage()),
            $actual
        );

        $this->assertStringContainsString(
            htmlspecialchars($enginePlugin->getComment()),
            $actual
        );

        $this->assertStringContainsString(
            __('Variables'),
            $actual
        );
        $this->assertStringContainsString(
            'index.php?route=/server/engines/Pbxt/Documentation',
            $actual
        );
        $this->assertStringContainsString(
            $enginePlugin->getSupportInformationMessage(),
            $actual
        );
        $this->assertStringContainsString(
            'There is no detailed status information available for this '
            . 'storage engine.',
            $actual
        );
    }
}
