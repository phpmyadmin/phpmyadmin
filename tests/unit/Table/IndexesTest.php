<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Indexes\Index;
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

    public function testGetSqlQueryForRename(): void
    {
        $sqlQuery = 'ALTER TABLE `db`.`table` RENAME INDEX `0` TO `ABC`;';

        $this->dbi->expects(self::any())->method('getVersion')
            ->willReturn(50700);

        $index = new Index(['Key_name' => 'ABC']);

        $indexes = new Indexes($this->dbi);

        $sqlResult = $indexes->getSqlQueryForRename('0', $index, Current::$database, Current::$table);
        self::assertStringContainsString($sqlQuery, $sqlResult);

        // Error message
        $index->setName('NOT PRIMARY'); // Cannot rename primary so the operation should fail
        $indexes->getSqlQueryForRename('PRIMARY', $index, Current::$database, Current::$table);
        $error = $indexes->getError();
        self::assertInstanceOf(Message::class, $error);

        $index->setName('PRIMARY'); // The new name cannot be PRIMARY so the operation should fail
        $indexes->getSqlQueryForRename('NOT PRIMARY', $index, Current::$database, Current::$table);
        $error = $indexes->getError();
        self::assertInstanceOf(Message::class, $error);
    }

    public function testGetSqlQueryForIndexCreateOrEdit(): void
    {
        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dbi->expects(self::any())->method('getTable')
            ->willReturn($table);
        $indexes = new Indexes($this->dbi);

        $db = 'pma_db';
        $table = 'pma_table';
        $index = new Index([
            'Key_name' => 'PRIMARY',
            'columns' => [['Column_name' => 'id']],
        ]);

        $sqlQueryExpected = 'ALTER TABLE `pma_db`.`pma_table` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);';

        $_POST['old_index'] = 'PRIMARY';
        self::assertSame(
            $sqlQueryExpected,
            $indexes->getSqlQueryForIndexCreateOrEdit('PRIMARY', $index, $db, $table),
        );

        // Error message
        $index->setName('NOT PRIMARY'); // Cannot rename primary so the operation should fail
        $indexes->getSqlQueryForIndexCreateOrEdit('PRIMARY', $index, $db, $table);
        self::assertInstanceOf(Message::class, $indexes->getError());
    }
}
