<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(Indexes::class)]
class IndexesTest extends AbstractTestCase
{
    private DatabaseInterface&MockObject $dbi;

    protected function setUp(): void
    {
        parent::setUp();

        Current::$database = 'db';
        Current::$table = 'table';

        $this->dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testDoSaveData(): void
    {
        $sqlQuery = 'ALTER TABLE `db`.`table` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);';

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dbi->expects($this->any())->method('getTable')
            ->willReturn($table);

        $index = new Index();
        $index->set([
            'Key_name' => 'PRIMARY',
            'columns' => [['Column_name' => 'id']],
        ]);

        $indexes = new Indexes($this->dbi);

        $_POST['old_index'] = 'PRIMARY';

        // Preview SQL
        $sqlResult = $indexes->doSaveData($index, false, Current::$database, Current::$table, true);
        $this->assertIsString($sqlResult);
        $this->assertStringContainsString($sqlQuery, $sqlResult);

        // Alter success
        $sqlResult = $indexes->doSaveData($index, false, Current::$database, Current::$table, false);
        $this->assertIsString($sqlResult);
        $this->assertStringContainsString($sqlQuery, $sqlResult);

        // Error message
        $index->setName('NOT PRIMARY'); // Cannot rename primary so the operation should fail
        $sqlResult = $indexes->doSaveData($index, false, Current::$database, Current::$table, false);
        $this->assertInstanceOf(Message::class, $sqlResult);
    }

    public function testGetSqlQueryForIndexCreateOrEdit(): void
    {
        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dbi->expects($this->any())->method('getTable')
            ->willReturn($table);
        $indexes = new Indexes($this->dbi);

        $db = 'pma_db';
        $table = 'pma_table';
        $index = new Index();

        $_POST['old_index'] = 'PRIMARY';
        $sql = $indexes->getSqlQueryForIndexCreateOrEdit($db, $table, $index);
        $this->assertEquals('ALTER TABLE `pma_db`.`pma_table` DROP PRIMARY KEY, ADD UNIQUE;', $sql);

        $_POST['old_index'] = [];
        $_POST['old_index']['Key_name'] = 'PRIMARY';
        $sql = $indexes->getSqlQueryForIndexCreateOrEdit($db, $table, $index);
        $this->assertEquals('ALTER TABLE `pma_db`.`pma_table` DROP PRIMARY KEY, ADD UNIQUE;', $sql);
    }
}
