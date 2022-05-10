<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli;
use mysqli_result;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Dbal\DbiMysqli
 */
class DbiMysqliTest extends AbstractTestCase
{
    /** @var DbiMysqli */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new DbiMysqli();
    }

    public function testGetClientInfo(): void
    {
        $this->assertNotEmpty($this->object->getClientInfo());
    }

    /**
     * Test for selectDb
     */
    public function testSelectDb(): void
    {
        $databaseName = 'test';
        $mysqli = $this->createMock(mysqli::class);
        $mysqli->expects($this->once())
            ->method('select_db')
            ->with($this->equalTo($databaseName))
            ->willReturn(true);

        $this->assertTrue($this->object->selectDb($databaseName, $mysqli));
    }

    /**
     * Test for realMultiQuery
     */
    public function testRealMultiQuery(): void
    {
        $query = 'test';
        $mysqli = $this->createMock(mysqli::class);
        $mysqli->expects($this->once())
            ->method('multi_query')
            ->with($this->equalTo($query))
            ->willReturn(true);

        $this->assertTrue($this->object->realMultiQuery($mysqli, $query));
    }

    /**
     * Test for realQuery
     */
    public function testrealQuery(): void
    {
        $query = 'test';
        $mysqliResult = $this->createMock(mysqli_result::class);
        $mysqli = $this->createMock(mysqli::class);
        $mysqli->expects($this->once())
            ->method('query')
            ->with($this->equalTo($query))
            ->willReturn($mysqliResult);

        $this->assertInstanceOf(MysqliResult::class, $this->object->realQuery($query, $mysqli, 0));
    }

    /**
     * Test for moreResults
     */
    public function testMoreResults(): void
    {
        $mysqli = $this->createMock(mysqli::class);
        $mysqli->expects($this->once())
            ->method('more_results')
            ->willReturn(true);

        $this->assertTrue($this->object->moreResults($mysqli));
    }

    /**
     * Test for nextResult
     */
    public function testNextResult(): void
    {
        $mysqli = $this->createMock(mysqli::class);
        $mysqli->expects($this->once())
            ->method('next_result')
            ->willReturn(true);

        $this->assertTrue($this->object->nextResult($mysqli));
    }

    /**
     * Test for storeResult
     */
    public function testStoreResult(): void
    {
        $mysqli = $this->createMock(mysqli::class);
        $mysqliResult = $this->createMock(mysqli_result::class);
        $mysqli->expects($this->once())
            ->method('store_result')
            ->willReturn($mysqliResult);

        $this->assertInstanceOf(MysqliResult::class, $this->object->storeResult($mysqli));
    }

    /**
     * Test for escapeString
     */
    public function testEscapeString(): void
    {
        $string = 'test';
        $mysqli = $this->createMock(mysqli::class);
        $mysqli->expects($this->once())
            ->method('real_escape_string')
            ->willReturn($string);

        $this->assertEquals($string, $this->object->escapeString($mysqli, $string));
    }
}
