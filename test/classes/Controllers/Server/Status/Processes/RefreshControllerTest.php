<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Processes;

use PhpMyAdmin\Controllers\Server\Status\Processes\RefreshController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;

use function __;
use function htmlspecialchars;

/** @covers \PhpMyAdmin\Controllers\Server\Status\Processes\RefreshController */
class RefreshControllerTest extends AbstractTestCase
{
    private Data $data;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['text_dir'] = 'ltr';

        parent::setGlobalConfig();

        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        $this->data = new Data($GLOBALS['dbi'], $GLOBALS['config']);
    }

    public function testRefresh(): void
    {
        $process = [
            'User' => 'User1',
            'Host' => 'Host1',
            'Id' => 'Id1',
            'Db' => 'db1',
            'Command' => 'Command1',
            'Info' => 'Info1',
            'State' => 'State1',
            'Time' => 'Time1',
        ];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 12;

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $controller = new RefreshController(
            $response,
            new Template(),
            $this->data,
            new Processes($GLOBALS['dbi']),
        );

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['column_name', '', ''],
            ['order_by_field', '', 'process'],
            ['sort_order', '', 'DESC'],
        ]);
        $request->method('hasBodyParam')->willReturnMap([['full', true], ['showExecuting', false]]);

        $controller($request);
        $html = $response->getHTMLResult();

        $this->assertStringContainsString('index.php?route=/server/status/processes', $html);
        $killProcess = 'data-post="'
            . Url::getCommon(['kill' => $process['Id']], '') . '"';
        $this->assertStringContainsString($killProcess, $html);
        $this->assertStringContainsString('ajax kill_process', $html);
        $this->assertStringContainsString(
            __('Kill'),
            $html,
        );

        //validate 2: $process['User']
        $this->assertStringContainsString(
            htmlspecialchars($process['User']),
            $html,
        );

        //validate 3: $process['Host']
        $this->assertStringContainsString(
            htmlspecialchars($process['Host']),
            $html,
        );

        //validate 4: $process['db']
        $this->assertStringContainsString($process['Db'], $html);

        //validate 5: $process['Command']
        $this->assertStringContainsString(
            htmlspecialchars($process['Command']),
            $html,
        );

        //validate 6: $process['Time']
        $this->assertStringContainsString($process['Time'], $html);

        //validate 7: $process['state']
        $this->assertStringContainsString($process['State'], $html);

        //validate 8: $process['info']
        $this->assertStringContainsString($process['Info'], $html);
    }
}
