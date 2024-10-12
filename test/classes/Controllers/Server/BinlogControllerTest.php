<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;
use PhpMyAdmin\Utils\SessionCache;

/**
 * @covers \PhpMyAdmin\Controllers\Server\BinlogController
 */
class BinlogControllerTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        parent::setTheme();

        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = 'server';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        SessionCache::set('profiling_supported', true);
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new BinlogController($response, new Template(), $GLOBALS['dbi']);

        $_POST['log'] = 'index1';
        $_POST['pos'] = '3';
        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        self::assertStringContainsString('Select binary log to view', $actual);
        self::assertStringContainsString('<option value="index1" selected>', $actual);
        self::assertStringContainsString('<option value="index2">', $actual);

        self::assertStringContainsString('Your SQL query has been executed successfully', $actual);

        self::assertStringContainsString("SHOW BINLOG EVENTS IN 'index1' LIMIT 3, 10", $actual);

        self::assertStringContainsString(
            '<table class="table table-striped table-hover align-middle" id="binlogTable">',
            $actual
        );

        $urlNavigation = Url::getFromRoute('/server/binlog') . '" data-post="log=index1&pos=3&'
            . 'is_full_query=1&server=1&';
        self::assertStringContainsString($urlNavigation, $actual);
        self::assertStringContainsString('title="Previous"', $actual);

        self::assertStringContainsString('Log name', $actual);
        self::assertStringContainsString('Position', $actual);
        self::assertStringContainsString('Event type', $actual);
        self::assertStringContainsString('Server ID', $actual);
        self::assertStringContainsString('Original position', $actual);

        self::assertStringContainsString('index1_Log_name', $actual);
        self::assertStringContainsString('index1_Pos', $actual);
        self::assertStringContainsString('index1_Event_type', $actual);
        self::assertStringContainsString('index1_Server_id', $actual);
        self::assertStringContainsString('index1_Orig_log_pos', $actual);
        self::assertStringContainsString('index1_Info', $actual);
    }
}
