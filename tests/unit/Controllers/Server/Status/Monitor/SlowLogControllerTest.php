<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Monitor;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\Monitor\SlowLogController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SlowLogController::class)]
class SlowLogControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Data $data;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function testSlowLog(): void
    {
        $response = new ResponseRenderer();

        $dbi = DatabaseInterface::getInstance();
        $controller = new SlowLogController(
            $response,
            new Template(),
            $this->data,
            new Monitor($dbi),
            $dbi,
        );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
        ->withParsedBody([
            'ajax_request' => 'true',
            'time_start' => '0',
            'time_end' => '10',
        ]);

        $this->dummyDbi->addSelectDb('mysql');
        $controller($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        $ret = $response->getJSONResult();

        $resultRows = [['sql_text' => 'insert sql_text', '#' => 11], ['sql_text' => 'update sql_text', '#' => 10]];
        $resultSum = ['insert' => 11, 'TOTAL' => 21, 'update' => 10];
        self::assertSame(2, $ret['message']['numRows']);
        self::assertEquals($resultRows, $ret['message']['rows']);
        self::assertEquals($resultSum, $ret['message']['sum']);
    }
}
