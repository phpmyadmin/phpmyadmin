<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal\Kusto;

use PhpMyAdmin\Dbal\Kusto\KustoResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KustoResult::class)]
class KustoResultTest extends TestCase
{
    private function createSampleResult(): KustoResult
    {
        $columns = [
            ['ColumnName' => 'Id', 'ColumnType' => 'long'],
            ['ColumnName' => 'Name', 'ColumnType' => 'string'],
            ['ColumnName' => 'Score', 'ColumnType' => 'real'],
        ];

        $rows = [
            [1, 'Alice', 95.5],
            [2, 'Bob', 87.3],
            [3, 'Charlie', 92.1],
        ];

        return new KustoResult($columns, $rows);
    }

    public function testNumRows(): void
    {
        $result = $this->createSampleResult();
        self::assertSame(3, $result->numRows());
    }

    public function testNumFields(): void
    {
        $result = $this->createSampleResult();
        self::assertSame(3, $result->numFields());
    }

    public function testFetchAssoc(): void
    {
        $result = $this->createSampleResult();

        $row = $result->fetchAssoc();
        self::assertSame('1', $row['Id']);
        self::assertSame('Alice', $row['Name']);
        self::assertSame('95.5', $row['Score']);

        $row2 = $result->fetchAssoc();
        self::assertSame('2', $row2['Id']);
        self::assertSame('Bob', $row2['Name']);
    }

    public function testFetchRow(): void
    {
        $result = $this->createSampleResult();

        $row = $result->fetchRow();
        self::assertSame('1', $row[0]);
        self::assertSame('Alice', $row[1]);
        self::assertSame('95.5', $row[2]);
    }

    public function testFetchValue(): void
    {
        $result = $this->createSampleResult();

        // By numeric index
        self::assertSame('1', $result->fetchValue(0));

        // By name (resets cursor via new instance)
        $result2 = $this->createSampleResult();
        self::assertSame('Alice', $result2->fetchValue('Name'));
    }

    public function testFetchAllAssoc(): void
    {
        $result = $this->createSampleResult();

        $all = $result->fetchAllAssoc();
        self::assertCount(3, $all);
        self::assertSame('Alice', $all[0]['Name']);
        self::assertSame('Bob', $all[1]['Name']);
        self::assertSame('Charlie', $all[2]['Name']);
    }

    public function testFetchAllColumn(): void
    {
        $result = $this->createSampleResult();

        $names = $result->fetchAllColumn('Name');
        self::assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    public function testFetchAllColumnByIndex(): void
    {
        $result = $this->createSampleResult();

        $ids = $result->fetchAllColumn(0);
        self::assertSame(['1', '2', '3'], $ids);
    }

    public function testFetchAllKeyPair(): void
    {
        $result = $this->createSampleResult();

        $pairs = $result->fetchAllKeyPair();
        self::assertSame('Alice', $pairs['1']);
        self::assertSame('Bob', $pairs['2']);
        self::assertSame('Charlie', $pairs['3']);
    }

    public function testSeek(): void
    {
        $result = $this->createSampleResult();

        self::assertTrue($result->seek(2));
        $row = $result->fetchAssoc();
        self::assertSame('Charlie', $row['Name']);

        self::assertFalse($result->seek(10)); // Out of bounds
        self::assertFalse($result->seek(-1)); // Negative
    }

    public function testGetFieldNames(): void
    {
        $result = $this->createSampleResult();

        self::assertSame(['Id', 'Name', 'Score'], $result->getFieldNames());
    }

    public function testGetFieldsMeta(): void
    {
        $result = $this->createSampleResult();

        $meta = $result->getFieldsMeta();
        self::assertCount(3, $meta);
        self::assertSame('Id', $meta[0]->name);
        self::assertSame('Name', $meta[1]->name);
        self::assertSame('Score', $meta[2]->name);
    }

    public function testGetIterator(): void
    {
        $result = $this->createSampleResult();

        $rows = [];
        foreach ($result as $row) {
            $rows[] = $row;
        }

        self::assertCount(3, $rows);
        self::assertSame('Alice', $rows[0]['Name']);
    }

    public function testEmptyResult(): void
    {
        $result = KustoResult::empty();

        self::assertSame(0, $result->numRows());
        self::assertSame(0, $result->numFields());
        self::assertSame([], $result->fetchAssoc());
        self::assertSame([], $result->fetchRow());
        self::assertSame([], $result->getFieldNames());
    }

    public function testFromKustoTable(): void
    {
        $table = [
            'Columns' => [
                ['ColumnName' => 'DatabaseName', 'ColumnType' => 'string'],
                ['ColumnName' => 'PersistentStorage', 'ColumnType' => 'string'],
            ],
            'Rows' => [
                ['mydb', '10 GB'],
                ['testdb', '1 GB'],
            ],
        ];

        $result = KustoResult::fromKustoTable($table);

        self::assertSame(2, $result->numRows());
        self::assertSame(2, $result->numFields());
        self::assertSame(['DatabaseName', 'PersistentStorage'], $result->getFieldNames());
    }

    public function testFetchAssocBeyondRows(): void
    {
        $columns = [['ColumnName' => 'x', 'ColumnType' => 'int']];
        $rows = [[42]];
        $result = new KustoResult($columns, $rows);

        $result->fetchAssoc(); // first row
        self::assertSame([], $result->fetchAssoc()); // past end
    }

    public function testNullValues(): void
    {
        $columns = [
            ['ColumnName' => 'A', 'ColumnType' => 'string'],
            ['ColumnName' => 'B', 'ColumnType' => 'string'],
        ];
        $rows = [
            ['hello', null],
        ];

        $result = new KustoResult($columns, $rows);
        $row = $result->fetchAssoc();

        self::assertSame('hello', $row['A']);
        self::assertNull($row['B']);
    }
}
