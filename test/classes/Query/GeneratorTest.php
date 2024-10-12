<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Query;

use PhpMyAdmin\Query\Generator;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Query\Generator
 */
class GeneratorTest extends AbstractTestCase
{
    public function testGetColumnsSql(): void
    {
        self::assertEquals(
            'SHOW  COLUMNS FROM `mydb`.`mytable`',
            Generator::getColumnsSql('mydb', 'mytable')
        );
        self::assertEquals(
            'SHOW  COLUMNS FROM `mydb`.`mytable` LIKE \'_idcolumn\'',
            Generator::getColumnsSql('mydb', 'mytable', '_idcolumn')
        );
        self::assertEquals(
            'SHOW FULL COLUMNS FROM `mydb`.`mytable`',
            Generator::getColumnsSql('mydb', 'mytable', null, true)
        );
        self::assertEquals(
            'SHOW FULL COLUMNS FROM `mydb`.`mytable` LIKE \'_idcolumn\'',
            Generator::getColumnsSql('mydb', 'mytable', '_idcolumn', true)
        );
    }

    public function testGetTableIndexesSql(): void
    {
        self::assertEquals(
            'SHOW INDEXES FROM `mydb`.`mytable`',
            Generator::getTableIndexesSql('mydb', 'mytable')
        );
        self::assertEquals(
            'SHOW INDEXES FROM `mydb`.`mytable` WHERE (1)',
            Generator::getTableIndexesSql('mydb', 'mytable', '1')
        );
    }

    public function testGetSqlQueryForIndexRename(): void
    {
        self::assertEquals(
            'ALTER TABLE `mydb`.`mytable` RENAME INDEX `oldIndexName` TO `newIndexName`;',
            Generator::getSqlQueryForIndexRename('mydb', 'mytable', 'oldIndexName', 'newIndexName')
        );
    }

    public function testGetQueryForReorderingTable(): void
    {
        self::assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable('mytable', 'myOrderField', '')
        );
        self::assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable('mytable', 'myOrderField', 'S')
        );
        self::assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable('mytable', 'myOrderField', 'DESC')
        );
        self::assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` DESC;',
            Generator::getQueryForReorderingTable('mytable', 'myOrderField', 'desc')
        );
        self::assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable('mytable', 'myOrderField', null)
        );
    }

    public function testGetQueryForPartitioningTable(): void
    {
        self::assertEquals(
            'ALTER TABLE `mytable`  PARTITION ;',
            Generator::getQueryForPartitioningTable('mytable', '', [])
        );
        self::assertEquals(
            'ALTER TABLE `mytable`  PARTITION p1;',
            Generator::getQueryForPartitioningTable('mytable', '', ['p1'])
        );
        self::assertEquals(
            'ALTER TABLE `mytable`  PARTITION p1, p2;',
            Generator::getQueryForPartitioningTable('mytable', '', ['p1', 'p2'])
        );
        self::assertEquals(
            'ALTER TABLE `mytable` COALESCE PARTITION 2',
            Generator::getQueryForPartitioningTable('mytable', 'COALESCE', ['p1', 'p2'])
        );
    }
}
