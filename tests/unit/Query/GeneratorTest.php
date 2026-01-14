<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Query;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Query\Generator;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Generator::class)]
class GeneratorTest extends AbstractTestCase
{
    public function testGetColumnsSql(): void
    {
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        self::assertSame(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`, `COLUMN_DEFAULT` AS `Default`,'
                . ' `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`, `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'mydb\''
                . ' AND `TABLE_NAME` COLLATE utf8_bin = \'mytable\''
                . ' ORDER BY `ORDINAL_POSITION`',
            Generator::getColumns(
                "'mydb'",
                "'mytable'",
            ),
        );
        self::assertSame(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`, `COLUMN_DEFAULT` AS `Default`,'
                . ' `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`, `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'mydb\''
                . ' AND `TABLE_NAME` COLLATE utf8_bin = \'mytable\' AND `COLUMN_NAME` = \'_idcolumn\''
                . ' ORDER BY `ORDINAL_POSITION`',
            Generator::getColumns(
                "'mydb'",
                "'mytable'",
                "'_idcolumn'",
            ),
        );
        self::assertSame(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`, `COLUMN_DEFAULT` AS `Default`,'
                . ' `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`, `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'mydb\''
                . ' AND `TABLE_NAME` COLLATE utf8_bin = \'mytable\''
                . ' ORDER BY `ORDINAL_POSITION`',
            Generator::getColumns(
                "'mydb'",
                "'mytable'",
            ),
        );
        self::assertSame(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`, `COLUMN_DEFAULT` AS `Default`,'
                . ' `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`, `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'mydb\''
                . ' AND `TABLE_NAME` COLLATE utf8_bin = \'mytable\' AND `COLUMN_NAME` = \'_idcolumn\''
                . ' ORDER BY `ORDINAL_POSITION`',
            Generator::getColumns(
                "'mydb'",
                "'mytable'",
                "'_idcolumn'",
            ),
        );
    }

    public function testGetTableIndexesSql(): void
    {
        self::assertSame(
            'SHOW INDEXES FROM `mydb`.`mytable`',
            Generator::getTableIndexesSql(
                'mydb',
                'mytable',
            ),
        );
        self::assertSame(
            'SHOW INDEXES FROM `mydb`.`mytable` WHERE (1)',
            Generator::getTableIndexesSql(
                'mydb',
                'mytable',
                '1',
            ),
        );
    }

    public function testGetSqlQueryForIndexRename(): void
    {
        self::assertSame(
            'ALTER TABLE `mydb`.`mytable` RENAME INDEX `oldIndexName` TO `newIndexName`;',
            Generator::getSqlQueryForIndexRename(
                'mydb',
                'mytable',
                'oldIndexName',
                'newIndexName',
            ),
        );
    }

    public function testGetQueryForReorderingTable(): void
    {
        self::assertSame(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                '',
            ),
        );
        self::assertSame(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                'S',
            ),
        );
        self::assertSame(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                'DESC',
            ),
        );
        self::assertSame(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` DESC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                'desc',
            ),
        );
        self::assertSame(
            'ALTER TABLE `mytable` ORDER BY `myOrderField` ASC;',
            Generator::getQueryForReorderingTable(
                'mytable',
                'myOrderField',
                null,
            ),
        );
    }

    public function testGetQueryForPartitioningTable(): void
    {
        self::assertSame(
            'ALTER TABLE `mytable`  PARTITION ;',
            Generator::getQueryForPartitioningTable(
                'mytable',
                '',
                [],
            ),
        );
        self::assertSame(
            'ALTER TABLE `mytable`  PARTITION p1;',
            Generator::getQueryForPartitioningTable(
                'mytable',
                '',
                ['p1'],
            ),
        );
        self::assertSame(
            'ALTER TABLE `mytable`  PARTITION p1, p2;',
            Generator::getQueryForPartitioningTable(
                'mytable',
                '',
                ['p1', 'p2'],
            ),
        );
        self::assertSame(
            'ALTER TABLE `mytable` COALESCE PARTITION 2',
            Generator::getQueryForPartitioningTable(
                'mytable',
                'COALESCE',
                ['p1', 'p2'],
            ),
        );
    }

    /**
     * Test for buildSqlQuery
     */
    public function testBuildSqlQuery(): void
    {
        $queryFields = ['a', 'b'];
        $valueSets = ['1', '2'];

        self::assertSame(
            'INSERT IGNORE INTO `table` (a, b) VALUES (1), (2)',
            Generator::buildInsertSqlQuery('table', true, $queryFields, $valueSets),
        );

        self::assertSame(
            'INSERT INTO `table` (a, b) VALUES (1), (2)',
            Generator::buildInsertSqlQuery('table', false, $queryFields, $valueSets),
        );
    }
}
