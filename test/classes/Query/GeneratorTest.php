<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Query;

use PhpMyAdmin\Query\Generator;
use PhpMyAdmin\Tests\AbstractTestCase;

class GeneratorTest extends AbstractTestCase
{
    public function testGetColumnsSql(): void
    {
        $this->assertEquals(
            'SHOW  COLUMNS FROM `mydb`.`mytable`',
            Generator::getColumnsSql(
                'mydb',
                'mytable'
            )
        );
        $this->assertEquals(
            'SHOW  COLUMNS FROM `mydb`.`mytable` LIKE \'_idcolumn\'',
            Generator::getColumnsSql(
                'mydb',
                'mytable',
                '_idcolumn'
            )
        );
        $this->assertEquals(
            'SHOW FULL COLUMNS FROM `mydb`.`mytable`',
            Generator::getColumnsSql(
                'mydb',
                'mytable',
                null,
                true
            )
        );
        $this->assertEquals(
            'SHOW FULL COLUMNS FROM `mydb`.`mytable` LIKE \'_idcolumn\'',
            Generator::getColumnsSql(
                'mydb',
                'mytable',
                '_idcolumn',
                true
            )
        );
    }

    public function testGetTableIndexesSql(): void
    {
        $this->assertEquals(
            'SHOW INDEXES FROM `mydb`.`mytable`',
            Generator::getTableIndexesSql(
                'mydb',
                'mytable'
            )
        );
        $this->assertEquals(
            'SHOW INDEXES FROM `mydb`.`mytable` WHERE (1)',
            Generator::getTableIndexesSql(
                'mydb',
                'mytable',
                '1'
            )
        );
    }

    public function testGetSqlQueryForIndexRename(): void
    {
        $this->assertEquals(
            'ALTER TABLE `mydb`.`mytable` RENAME INDEX `oldIndexName` TO `newIndexName`;',
            Generator::getSqlQueryForIndexRename(
                'mydb',
                'mytable',
                'oldIndexName',
                'newIndexName'
            )
        );
    }

    public function testGetQueryForReorderingTable(): void
    {
        $this->assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                ''
            )
        );
        $this->assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                'S'
            )
        );
        $this->assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                'DESC'
            )
        );
        $this->assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` DESC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                'desc'
            )
        );
        $this->assertEquals(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                null
            )
        );
    }

    public function testGetQueryForPartitioningTable(): void
    {
        $this->assertEquals(
            'ALTER TABLE `mytable`  PARTITION ;',
            Generator::getQueryForPartitioningTable(
                'mytable',
                '',
                []
            )
        );
        $this->assertEquals(
            'ALTER TABLE `mytable`  PARTITION p1;',
            Generator::getQueryForPartitioningTable(
                'mytable',
                '',
                ['p1']
            )
        );
        $this->assertEquals(
            'ALTER TABLE `mytable`  PARTITION p1, p2;',
            Generator::getQueryForPartitioningTable(
                'mytable',
                '',
                ['p1', 'p2']
            )
        );
        $this->assertEquals(
            'ALTER TABLE `mytable` COALESCE PARTITION 2',
            Generator::getQueryForPartitioningTable(
                'mytable',
                'COALESCE',
                ['p1', 'p2']
            )
        );
    }
}
