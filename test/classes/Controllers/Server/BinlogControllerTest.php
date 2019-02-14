<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds BinlogControllerTest
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ServerCollationsController class
 *
 * @package PhpMyAdmin-test
 */
class BinlogControllerTest extends TestCase
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

    /**
     * @return void
     */
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

        $controller = new BinlogController(
            Response::getInstance(),
            $dbi
        );
        $actual = $controller->indexAction([
            'log' => 'index1',
            'pos' => '3',
            'is_full_query' => null,
        ]);

        $this->assertContains(
            'Select binary log to view',
            $actual
        );
        $this->assertContains(
            '<option value="index1" selected>',
            $actual
        );
        $this->assertContains(
            '<option value="index2">',
            $actual
        );

        $this->assertContains(
            'Your SQL query has been executed successfully',
            $actual
        );

        $this->assertContains(
            "SHOW BINLOG EVENTS IN 'index1' LIMIT 3, 10",
            $actual
        );

        $this->assertContains(
            '<table id="binlogTable">',
            $actual
        );

        $urlNavigation = 'server_binlog.php" data-post="pos=3&amp;'
            . 'is_full_query=1&amp;server=1&amp';
        $this->assertContains(
            $urlNavigation,
            $actual
        );
        $this->assertContains(
            'title="Previous"',
            $actual
        );

        $this->assertContains(
            'Log name',
            $actual
        );
        $this->assertContains(
            'Position',
            $actual
        );
        $this->assertContains(
            'Event type',
            $actual
        );
        $this->assertContains(
            'Server ID',
            $actual
        );
        $this->assertContains(
            'Original position',
            $actual
        );

        $this->assertContains(
            $value['Log_name'],
            $actual
        );
        $this->assertContains(
            $value['Pos'],
            $actual
        );
        $this->assertContains(
            $value['Event_type'],
            $actual
        );
        $this->assertContains(
            $value['Server_id'],
            $actual
        );
        $this->assertContains(
            $value['Orig_log_pos'],
            $actual
        );
        $this->assertContains(
            $value['Info'],
            $actual
        );
    }
}
