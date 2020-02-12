<?php
/**
 * Holds BinlogControllerTest
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BinlogController class
 */
class BinlogControllerTest extends TestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = 'server';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        Util::cacheSet('profiling_supported', true);
    }

    public function testIndex(): void
    {
        $binaryLogs = [
            [
                'Log_name' => 'index1',
                'File_size' => 100,
            ],
            [
                'Log_name' => 'index2',
                'File_size' => 200,
            ],
        ];
        $result = [
            [
                "SHOW BINLOG EVENTS IN 'index1' LIMIT 3, 10",
                null,
                1,
                true,
                ['log1' => 'logd'],
            ],
            [
                ['log2' => 'logb'],
                null,
                0,
                false,
                'executed',
            ],
        ];
        $value = [
            'Info' => 'index1_Info',
            'Log_name' => 'index1_Log_name',
            'Pos' => 'index1_Pos',
            'Event_type' => 'index1_Event_type',
            'Orig_log_pos' => 'index1_Orig_log_pos',
            'End_log_pos' => 'index1_End_log_pos',
            'Server_id' => 'index1_Server_id',
        ];
        $count = 3;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())->method('fetchResult')
            ->will($this->returnValue($binaryLogs));
        $dbi->expects($this->once())->method('query')
            ->will($this->returnValue($result));
        $dbi->expects($this->once())->method('numRows')
            ->will($this->returnValue($count));
        $dbi->expects($this->at(3))->method('fetchAssoc')
            ->will($this->returnValue($value));
        $dbi->expects($this->at(4))->method('fetchAssoc')
            ->will($this->returnValue(false));

        $response = new Response();

        $controller = new BinlogController(
            $response,
            $dbi,
            new Template()
        );
        $controller->index([
            'log' => 'index1',
            'pos' => '3',
            'is_full_query' => null,
        ]);
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString(
            'Select binary log to view',
            $actual
        );
        $this->assertStringContainsString(
            '<option value="index1" selected>',
            $actual
        );
        $this->assertStringContainsString(
            '<option value="index2">',
            $actual
        );

        $this->assertStringContainsString(
            'Your SQL query has been executed successfully',
            $actual
        );

        $this->assertStringContainsString(
            "SHOW BINLOG EVENTS IN 'index1' LIMIT 3, 10",
            $actual
        );

        $this->assertStringContainsString(
            '<table id="binlogTable">',
            $actual
        );

        $urlNavigation = Url::getFromRoute('/server/binlog') . '" data-post="pos=3&amp;'
            . 'is_full_query=1&amp;server=1&amp';
        $this->assertStringContainsString(
            $urlNavigation,
            $actual
        );
        $this->assertStringContainsString(
            'title="Previous"',
            $actual
        );

        $this->assertStringContainsString(
            'Log name',
            $actual
        );
        $this->assertStringContainsString(
            'Position',
            $actual
        );
        $this->assertStringContainsString(
            'Event type',
            $actual
        );
        $this->assertStringContainsString(
            'Server ID',
            $actual
        );
        $this->assertStringContainsString(
            'Original position',
            $actual
        );

        $this->assertStringContainsString(
            $value['Log_name'],
            $actual
        );
        $this->assertStringContainsString(
            $value['Pos'],
            $actual
        );
        $this->assertStringContainsString(
            $value['Event_type'],
            $actual
        );
        $this->assertStringContainsString(
            $value['Server_id'],
            $actual
        );
        $this->assertStringContainsString(
            $value['Orig_log_pos'],
            $actual
        );
        $this->assertStringContainsString(
            $value['Info'],
            $actual
        );
    }
}
