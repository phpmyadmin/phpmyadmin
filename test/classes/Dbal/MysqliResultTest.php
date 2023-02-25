<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli_result;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Dbal\DbiMysqli */
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

        $this->assertSame(['foo' => 'bar'], $result->fetchAssoc());
        $this->assertSame([], $result->fetchAssoc());
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

        $this->assertSame(['bar'], $result->fetchRow());
        $this->assertSame([], $result->fetchRow());
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
            ->with($this->equalTo($offset))
            ->willReturn(true);

        $result = new MysqliResult($mysqliResult);

        $this->assertTrue($result->seek($offset));
    }
}
