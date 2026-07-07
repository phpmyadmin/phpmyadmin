<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PDO;
use PDOStatement;
use PhpMyAdmin\Dbal\PdoResult;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(PdoResult::class)]
class PdoResultTest extends AbstractTestCase
{
    /** @psalm-param list<list<string|null>> $rows */
    private function createStatementMock(array $rows): MockObject&PDOStatement
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->method('columnCount')->willReturn(2);
        $statement->method('getColumnMeta')->willReturnMap([
            [
                0,
                [
                    'native_type' => 'LONG',
                    'flags' => ['not_null', 'primary_key'],
                    'table' => 'users',
                    'name' => 'id',
                    'len' => 11,
                    'precision' => 0,
                ],
            ],
            [
                1,
                [
                    'native_type' => 'VAR_STRING',
                    'flags' => [],
                    'table' => 'users',
                    'name' => 'name',
                    'len' => 255,
                    'precision' => 0,
                ],
            ],
        ]);
        $statement->method('fetchAll')->with(PDO::FETCH_NUM)->willReturn($rows);

        return $statement;
    }

    public function testFetchAssoc(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John'], ['2', null]]));

        self::assertSame(['id' => '1', 'name' => 'John'], $result->fetchAssoc());
        self::assertSame(['id' => '2', 'name' => null], $result->fetchAssoc());
        self::assertSame([], $result->fetchAssoc());
    }

    public function testFetchRow(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John']]));

        self::assertSame(['1', 'John'], $result->fetchRow());
        self::assertSame([], $result->fetchRow());
    }

    public function testFetchValue(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John'], ['2', 'Jane']]));

        self::assertSame('1', $result->fetchValue());
        self::assertSame('Jane', $result->fetchValue('name'));
        self::assertFalse($result->fetchValue('unknown'));
    }

    public function testSeek(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John'], ['2', 'Jane']]));

        self::assertTrue($result->seek(1));
        self::assertSame(['2', 'Jane'], $result->fetchRow());
        self::assertFalse($result->seek(2));

        self::assertTrue($result->seek(0));
        self::assertSame(['1', 'John'], $result->fetchRow());
    }

    public function testNumRowsAndFields(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John'], ['2', 'Jane']]));

        self::assertSame(2, $result->numRows());
        self::assertSame(2, $result->numFields());
        self::assertSame(['id', 'name'], $result->getFieldNames());
    }

    public function testFetchAllAssoc(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John'], ['2', 'Jane']]));

        // consume a row first to prove that all rows are still returned
        $result->fetchRow();

        self::assertSame(
            [['id' => '1', 'name' => 'John'], ['id' => '2', 'name' => 'Jane']],
            $result->fetchAllAssoc(),
        );
    }

    public function testFetchAllColumn(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John'], ['2', 'Jane']]));

        self::assertSame(['1', '2'], $result->fetchAllColumn());
        self::assertSame(['John', 'Jane'], $result->fetchAllColumn(1));
        self::assertSame(['John', 'Jane'], $result->fetchAllColumn('name'));
    }

    public function testFetchAllKeyPair(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John'], ['2', 'Jane']]));

        self::assertSame(['1' => 'John', '2' => 'Jane'], $result->fetchAllKeyPair());
    }

    public function testGetIterator(): void
    {
        $result = new PdoResult($this->createStatementMock([['1', 'John'], ['2', 'Jane']]));

        // consume the result first to prove that the iterator starts from the first row
        $result->fetchAllAssoc();

        $rows = [];
        foreach ($result as $row) {
            $rows[] = $row;
        }

        self::assertSame([['id' => '1', 'name' => 'John'], ['id' => '2', 'name' => 'Jane']], $rows);
    }

    public function testGetFieldsMeta(): void
    {
        $result = new PdoResult($this->createStatementMock([]));

        $fieldsMeta = $result->getFieldsMeta();

        self::assertCount(2, $fieldsMeta);
        self::assertSame('id', $fieldsMeta[0]->name);
        self::assertSame('users', $fieldsMeta[0]->table);
        self::assertTrue($fieldsMeta[0]->isPrimaryKey());
        self::assertTrue($fieldsMeta[0]->isNotNull());
        self::assertTrue($fieldsMeta[0]->isNumeric());
        self::assertSame('int', $fieldsMeta[0]->getMappedType());
        self::assertSame('name', $fieldsMeta[1]->name);
        self::assertFalse($fieldsMeta[1]->isPrimaryKey());
        self::assertSame('string', $fieldsMeta[1]->getMappedType());
    }

    public function testResultWithoutFields(): void
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->method('columnCount')->willReturn(0);
        $statement->expects(self::never())->method('fetchAll');

        $result = new PdoResult($statement);

        self::assertSame([], $result->fetchAssoc());
        self::assertSame([], $result->fetchRow());
        self::assertSame(0, $result->numRows());
        self::assertSame(0, $result->numFields());
        self::assertSame([], $result->fetchAllAssoc());
        self::assertFalse($result->seek(0));
    }

    public function testUnbufferedResult(): void
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->method('columnCount')->willReturn(2);
        $statement->method('getColumnMeta')->willReturnMap([
            [0, ['native_type' => 'LONG', 'flags' => [], 'table' => '', 'name' => 'id', 'len' => 11]],
            [1, ['native_type' => 'VAR_STRING', 'flags' => [], 'table' => '', 'name' => 'name', 'len' => 255]],
        ]);
        $statement->method('fetch')->with(PDO::FETCH_NUM)->willReturn(['1', 'John'], ['2', 'Jane'], false);

        $result = new PdoResult($statement, false);

        self::assertSame(['id' => '1', 'name' => 'John'], $result->fetchAssoc());
        self::assertSame(1, $result->numRows());
        self::assertFalse($result->seek(0));
        self::assertSame(['2', 'Jane'], $result->fetchRow());
        self::assertSame([], $result->fetchRow());
    }
}
