<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\ShowEngineController;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function __;
use function htmlspecialchars;

/**
 * @covers \PhpMyAdmin\Controllers\Server\ShowEngineController
 */
class ShowEngineControllerTest extends AbstractTestCase
{
    public function testShowEngine(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $response = new ResponseRenderer();
        $request = $this->createMock(ServerRequest::class);
        $this->dummyDbi->addSelectDb('mysql');

        (new ShowEngineController($response, new Template(), $GLOBALS['dbi']))($request, [
            'engine' => 'Pbxt',
            'page' => 'page',
        ]);

        $this->assertAllSelectsConsumed();
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
        $this->assertStringContainsString('index.php?route=/server/engines/Pbxt/Documentation', $actual);
        $this->assertStringContainsString(
            $enginePlugin->getSupportInformationMessage(),
            $actual
        );
        $this->assertStringContainsString(
            'There is no detailed status information available for this storage engine.',
            $actual
        );
    }
}
