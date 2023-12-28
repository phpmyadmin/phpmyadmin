<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Indexes::class)]
class IndexesTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * SET these to avoid undefined index error
         */
        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['DisableIS'] = false;
        $GLOBALS['urlParams'] = ['db' => 'db', 'server' => 1];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indexs = [
            ['Schema' => 'Schema1', 'Key_name' => 'Key_name1', 'Column_name' => 'Column_name1'],
            ['Schema' => 'Schema2', 'Key_name' => 'Key_name2', 'Column_name' => 'Column_name2'],
            ['Schema' => 'Schema3', 'Key_name' => 'Key_name3', 'Column_name' => 'Column_name3'],
        ];

        $dbi->expects($this->any())->method('getTableIndexes')
            ->willReturn($indexs);

        DatabaseInterface::$instance = $dbi;

        //$_SESSION
    }

    public function testDoSaveData(): void
    {
        $sqlQuery = 'ALTER TABLE `db`.`table` DROP PRIMARY KEY, ADD UNIQUE ;';

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->any())->method('getSqlQueryForIndexCreateOrEdit')
            ->willReturn($sqlQuery);

        $dbi = DatabaseInterface::getInstance();
        $dbi->expects($this->any())->method('getTable')
            ->willReturn($table);

        $index = new Index();

        $indexes = new Indexes($dbi);

        // Preview SQL
        $sqlResult = $indexes->doSaveData($index, false, Current::$database, Current::$table, true);
        $this->assertIsString($sqlResult);
        $this->assertStringContainsString($sqlQuery, $sqlResult);

        // Alter success
        $sqlResult = $indexes->doSaveData($index, false, Current::$database, Current::$table, false);
        $this->assertIsString($sqlResult);
        $this->assertStringContainsString($sqlQuery, $sqlResult);

        // Error message
        // Cannot be tested at the moment.
        // $index->setName('PRIMARY'); // Cannot rename any index to primary so the operation should fail
        // $indexes->doSaveData($index, false, Current::$database, Current::$table, false);
        // $this->assertInstanceOf(Message::class, $sqlResult);
    }
}
