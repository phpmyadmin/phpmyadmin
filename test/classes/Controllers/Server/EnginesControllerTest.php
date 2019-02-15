<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds EnginesControllerTest class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPStan\Testing\TestCase;

/**
 * Tests for EnginesController class
 *
 * @package PhpMyAdmin-test
 */
class EnginesControllerTest extends TestCase
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
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $controller = new EnginesController(
            Response::getInstance(),
            $GLOBALS['dbi']
        );

        $actual = $controller->index();

        $this->assertContains(
            '<th>Storage Engine</th>',
            $actual
        );
        $this->assertContains(
            '<th>Description</th>',
            $actual
        );

        $this->assertContains(
            '<td>Federated MySQL storage engine</td>',
            $actual
        );
        $this->assertContains(
            'FEDERATED',
            $actual
        );
        $this->assertContains(
            'server_engines.php?engine=FEDERATED',
            $actual
        );

        $this->assertContains(
            '<td>dummy comment</td>',
            $actual
        );
        $this->assertContains(
            'dummy',
            $actual
        );
        $this->assertContains(
            'server_engines.php?engine=dummy',
            $actual
        );
    }

    /**
     * @return void
     */
    public function testShow(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $controller = new EnginesController(
            Response::getInstance(),
            $GLOBALS['dbi']
        );

        $actual = $controller->show([
            'engine' => 'Pbxt',
            'page' => 'page',
        ]);

        $enginePlugin = StorageEngine::getEngine('Pbxt');

        $this->assertContains(
            htmlspecialchars($enginePlugin->getTitle()),
            $actual
        );

        $this->assertContains(
            Util::showMySQLDocu($enginePlugin->getMysqlHelpPage()),
            $actual
        );

        $this->assertContains(
            htmlspecialchars($enginePlugin->getComment()),
            $actual
        );

        $this->assertContains(
            __('Variables'),
            $actual
        );
        $this->assertContains(
            Url::getCommon([
                'engine' => 'Pbxt',
                'page' => 'Documentation'
            ]),
            $actual
        );

        $this->assertContains(
            Url::getCommon(['engine' => 'Pbxt']),
            $actual
        );
        $this->assertContains(
            $enginePlugin->getSupportInformationMessage(),
            $actual
        );
        $this->assertContains(
            'There is no detailed status information available for this '
            . 'storage engine.',
            $actual
        );
    }
}
