<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Processes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\Processes\RefreshController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;
use function htmlspecialchars;

#[CoversClass(RefreshController::class)]
class RefreshControllerTest extends AbstractTestCase
{
    private Data $data;

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['host'] = 'localhost';

        $this->data = new Data(DatabaseInterface::getInstance(), $config);
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
        Config::getInstance()->settings['MaxCharactersInDisplayedSQL'] = 12;

        $response = new ResponseRenderer();

        $controller = new RefreshController(
            $response,
            new Template(),
            $this->data,
            new Processes(DatabaseInterface::getInstance()),
        );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'ajax_request' => 'true',
                'column_name' => '',
                'order_by_field' => 'process',
                'sort_order' => 'DESC',
                'full' => 'true',
            ]);

        $controller($request);
        $html = $response->getHTMLResult();

        self::assertStringContainsString('index.php?route=/server/status/processes', $html);
        $killProcess = 'data-post="'
            . Url::getCommon(['kill' => $process['Id']], '') . '"';
        self::assertStringContainsString($killProcess, $html);
        self::assertStringContainsString('ajax kill_process', $html);
        self::assertStringContainsString(
            __('Kill'),
            $html,
        );

        //validate 2: $process['User']
        self::assertStringContainsString(
            htmlspecialchars($process['User']),
            $html,
        );

        //validate 3: $process['Host']
        self::assertStringContainsString(
            htmlspecialchars($process['Host']),
            $html,
        );

        //validate 4: $process['db']
        self::assertStringContainsString($process['Db'], $html);

        //validate 5: $process['Command']
        self::assertStringContainsString(
            htmlspecialchars($process['Command']),
            $html,
        );

        //validate 6: $process['Time']
        self::assertStringContainsString($process['Time'], $html);

        //validate 7: $process['state']
        self::assertStringContainsString($process['State'], $html);

        //validate 8: $process['info']
        self::assertStringContainsString($process['Info'], $html);
    }
}
