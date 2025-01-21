<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\MonitorController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function __;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Status\MonitorController
 */
class MonitorControllerTest extends AbstractTestCase
{
    /** @var Data */
    private $data;

    protected function setUp(): void
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
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        $this->data = new Data();
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new MonitorController(
            $response,
            new Template(),
            $this->data,
            $GLOBALS['dbi']
        );

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('<div class="tabLinks row">', $html);
        self::assertStringContainsString(__('Start Monitor'), $html);
        self::assertStringContainsString(__('Settings'), $html);
        self::assertStringContainsString(__('Done dragging (rearranging) charts'), $html);

        self::assertStringContainsString('<div class="popupContent settingsPopup">', $html);
        self::assertStringContainsString('<a href="#settingsPopup" class="popupLink">', $html);
        self::assertStringContainsString(__('Enable charts dragging'), $html);
        self::assertStringContainsString('<option>3</option>', $html);

        self::assertStringContainsString(__('Monitor Instructions'), $html);
        self::assertStringContainsString('monitorInstructionsDialog', $html);

        self::assertStringContainsString('<div class="modal fade" id="addChartModal"', $html);
        self::assertStringContainsString('<div id="chartVariableSettings">', $html);
        self::assertStringContainsString('<option>Processes</option>', $html);
        self::assertStringContainsString('<option>Connections</option>', $html);

        self::assertStringContainsString('<form id="js_data" class="hide">', $html);
        self::assertStringContainsString('<input type="hidden" name="server_time"', $html);
        //validate 2: inputs
        self::assertStringContainsString('<input type="hidden" name="is_superuser"', $html);
        self::assertStringContainsString('<input type="hidden" name="server_db_isLocal"', $html);
        self::assertStringContainsString('<div id="explain_docu" class="hide">', $html);
    }
}
