<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli_result;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @covers \PhpMyAdmin\Dbal\DbiMysqli
 */
#[CoversClass(DbiMysqli::class)]
class MysqliResultTest extends AbstractTestCase
{
    /**
     * Test for fetchAssoc
     */
    public function testFetchAssoc(): void
    {
        $expected = [['foo' => 'bar'], null];
        $mysqliResult = $this->createMock(mysqli_result::class);
        $mysqliResult->expects($this->exactly(2))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(...$expected);

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
        $mysqliResult = $this->createMock(mysqli_result::class);
        $mysqliResult->expects($this->exactly(2))
            ->method('fetch_row')
            ->willReturnOnConsecutiveCalls(...$expected);

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
        $mysqliResult = $this->createMock(mysqli_result::class);
        $mysqliResult->expects($this->once())
            ->method('data_seek')
            ->with($offset)
            ->willReturn(true);

        $result = new MysqliResult($mysqliResult);

        self::assertTrue($result->seek($offset));
    }
}
