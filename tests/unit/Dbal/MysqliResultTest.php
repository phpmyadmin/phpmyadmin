<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli_result;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use const MYSQLI_ASSOC;
use const MYSQLI_NUM;

#[CoversClass(DbiMysqli::class)]
class MysqliResultTest extends AbstractTestCase
{
    /**
     * Test for fetchAssoc
     */
    public function testFetchAssoc(): void
    {
        $expected = [['foo' => 'bar'], null];
        $mysqliResult = self::createMock(mysqli_result::class);
        $mysqliResult->expects(self::exactly(2))
            ->method('fetch_assoc')
            ->willReturn(...$expected);

        $result = new MysqliResult($mysqliResult);

        self::assertSame(['foo' => 'bar'], $result->fetchAssoc());
        self::assertSame([], $result->fetchAssoc());
    }

    /**
     * Test for fetchRow
     */
    public function testFetchRow(): void
    {
        $expected = [['bar'], null];
        $mysqliResult = self::createMock(mysqli_result::class);
        $mysqliResult->expects(self::exactly(2))
            ->method('fetch_row')
            ->willReturn(...$expected);

        $result = new MysqliResult($mysqliResult);

        self::assertSame(['bar'], $result->fetchRow());
        self::assertSame([], $result->fetchRow());
    }

    /**
     * Test for seek
     */
    public function testDataSeek(): void
    {
        $offset = 1;
        $mysqliResult = self::createMock(mysqli_result::class);
        $mysqliResult->expects(self::once())
            ->method('data_seek')
            ->with(self::equalTo($offset))
            ->willReturn(true);

        $result = new MysqliResult($mysqliResult);

        self::assertTrue($result->seek($offset));
    }

    /**
     * Test for fetchAllColumn
     */
    public function testFetchAllColumn(): void
    {
        $mysqliResult = self::createMock(mysqli_result::class);
        $mysqliResult->expects(self::exactly(3))
            ->method('fetch_all')
            ->willReturnMap([
                [MYSQLI_ASSOC, [['foo' => 'bar']]],
                [MYSQLI_NUM, [['baz', 'foobar']]],
            ]);

        $result = new MysqliResult($mysqliResult);

        self::assertSame(['baz'], $result->fetchAllColumn());
        self::assertSame(['foobar'], $result->fetchAllColumn(1));
        self::assertSame(['bar'], $result->fetchAllColumn('foo'));
    }
}
