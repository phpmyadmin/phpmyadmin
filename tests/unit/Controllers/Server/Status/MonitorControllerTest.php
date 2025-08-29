<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\MonitorController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;

#[CoversClass(MonitorController::class)]
class MonitorControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Data $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setGlobalConfig();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['host'] = 'localhost';

        $this->data = new Data($this->dbi, $config);
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new MonitorController(
            $response,
            new Template(),
            $this->data,
            DatabaseInterface::getInstance(),
        );

        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString(__('Start monitor'), $html);
        self::assertStringContainsString(
            __('Settings'),
            $html,
        );
        self::assertStringContainsString(
            __('Done dragging (rearranging) charts'),
            $html,
        );

        self::assertStringContainsString('<div class="collapse" id="monitorSettingsContent">', $html);
        self::assertStringContainsString(
            __('Enable charts dragging'),
            $html,
        );
        self::assertStringContainsString('<option>3</option>', $html);

        self::assertStringContainsString('System monitor instructions', $html);
        self::assertStringContainsString('monitorInstructionsModal', $html);

        self::assertStringContainsString('<div class="modal fade" id="addChartModal"', $html);
        self::assertStringContainsString('<div id="chartVariableSettings">', $html);
        self::assertStringContainsString('<option>Processes</option>', $html);
        self::assertStringContainsString('<option>Connections</option>', $html);

        self::assertStringContainsString('<form id="js_data" class="d-none disableAjax">', $html);
        self::assertStringContainsString('<input type="hidden" name="server_time"', $html);
        //validate 2: inputs
        self::assertStringContainsString('<input type="hidden" name="is_superuser"', $html);
        self::assertStringContainsString('<input type="hidden" name="server_db_isLocal"', $html);
        self::assertStringContainsString('<div id="explain_docu" class="hide">', $html);
    }
}
