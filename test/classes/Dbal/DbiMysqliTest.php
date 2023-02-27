<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli;
use mysqli_result;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Dbal\DbiMysqli
 * @covers \PhpMyAdmin\Dbal\Connection
 */
class DbiMysqliTest extends AbstractTestCase
{
    protected DbiMysqli $object;

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

        $this->assertTrue($this->object->selectDb($databaseName, new Connection($mysqli)));
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

        $this->assertTrue($this->object->realMultiQuery(new Connection($mysqli), $query));
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

        $this->assertInstanceOf(MysqliResult::class, $this->object->realQuery($query, new Connection($mysqli), 0));
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

        $this->assertTrue($this->object->moreResults(new Connection($mysqli)));
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

        $this->assertTrue($this->object->nextResult(new Connection($mysqli)));
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

        $this->assertInstanceOf(MysqliResult::class, $this->object->storeResult(new Connection($mysqli)));
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

        $this->assertEquals($string, $this->object->escapeString(new Connection($mysqli), $string));
    }

    public function testGetWarningCount(): void
    {
        $mysqli = (object) ['warning_count' => 30];
        $this->assertSame(30, $this->object->getWarningCount(new Connection($mysqli)));
    }
}
