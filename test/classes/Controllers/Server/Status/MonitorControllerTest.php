<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\MonitorController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function __;

/** @covers \PhpMyAdmin\Controllers\Server\Status\MonitorController */
class MonitorControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Data $data;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['text_dir'] = 'ltr';

        parent::setGlobalConfig();

        parent::setTheme();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        $this->data = new Data($this->dbi, $GLOBALS['config']);
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new MonitorController(
            $response,
            new Template(),
            $this->data,
            $GLOBALS['dbi'],
        );

        $this->dummyDbi->addSelectDb('mysql');
        $controller($this->createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString('<div class="tabLinks row">', $html);
        $this->assertStringContainsString(
            __('Start Monitor'),
            $html,
        );
        $this->assertStringContainsString(
            __('Settings'),
            $html,
        );
        $this->assertStringContainsString(
            __('Done dragging (rearranging) charts'),
            $html,
        );

        $this->assertStringContainsString('<div class="popupContent settingsPopup">', $html);
        $this->assertStringContainsString('<a href="#settingsPopup" class="popupLink">', $html);
        $this->assertStringContainsString(
            __('Enable charts dragging'),
            $html,
        );
        $this->assertStringContainsString('<option>3</option>', $html);

        $this->assertStringContainsString(
            __('Monitor Instructions'),
            $html,
        );
        $this->assertStringContainsString('monitorInstructionsDialog', $html);

        $this->assertStringContainsString('<div class="modal fade" id="addChartModal"', $html);
        $this->assertStringContainsString('<div id="chartVariableSettings">', $html);
        $this->assertStringContainsString('<option>Processes</option>', $html);
        $this->assertStringContainsString('<option>Connections</option>', $html);

        $this->assertStringContainsString('<form id="js_data" class="hide">', $html);
        $this->assertStringContainsString('<input type="hidden" name="server_time"', $html);
        //validate 2: inputs
        $this->assertStringContainsString('<input type="hidden" name="is_superuser"', $html);
        $this->assertStringContainsString('<input type="hidden" name="server_db_isLocal"', $html);
        $this->assertStringContainsString('<div id="explain_docu" class="hide">', $html);
    }
}
