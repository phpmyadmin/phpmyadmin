<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli;
use mysqli_result;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DbiMysqli::class)]
#[CoversClass(Connection::class)]
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
        self::assertNotEmpty($this->object->getClientInfo());
    }

    /**
     * Test for selectDb
     */
    public function testSelectDb(): void
    {
        $databaseName = 'test';
        $mysqli = self::createMock(mysqli::class);
        $mysqli->expects(self::once())
            ->method('select_db')
            ->with(self::equalTo($databaseName))
            ->willReturn(true);

        self::assertTrue($this->object->selectDb($databaseName, new Connection($mysqli)));
    }

    /**
     * Test for realMultiQuery
     */
    public function testRealMultiQuery(): void
    {
        $query = 'test';
        $mysqli = self::createMock(mysqli::class);
        $mysqli->expects(self::once())
            ->method('multi_query')
            ->with(self::equalTo($query))
            ->willReturn(true);

        self::assertTrue($this->object->realMultiQuery(new Connection($mysqli), $query));
    }

    /**
     * Test for realQuery
     */
    public function testrealQuery(): void
    {
        $query = 'test';
        $mysqliResult = self::createMock(mysqli_result::class);
        $mysqli = self::createMock(mysqli::class);
        $mysqli->expects(self::once())
            ->method('query')
            ->with(self::equalTo($query))
            ->willReturn($mysqliResult);

        self::assertInstanceOf(MysqliResult::class, $this->object->realQuery($query, new Connection($mysqli)));
    }

    /**
     * Test for nextResult
     */
    public function testNextResult(): void
    {
        $mysqli = self::createMock(mysqli::class);
        $mysqli->expects(self::once())
            ->method('next_result')
            ->willReturn(true);

        self::assertTrue($this->object->nextResult(new Connection($mysqli)));
    }

    /**
     * Test for storeResult
     */
    public function testStoreResult(): void
    {
        $mysqli = self::createMock(mysqli::class);
        $mysqliResult = self::createMock(mysqli_result::class);
        $mysqli->expects(self::once())
            ->method('store_result')
            ->willReturn($mysqliResult);

        self::assertInstanceOf(MysqliResult::class, $this->object->storeResult(new Connection($mysqli)));
    }

    /**
     * Test for escapeString
     */
    public function testEscapeString(): void
    {
        $string = 'test';
        $mysqli = self::createMock(mysqli::class);
        $mysqli->expects(self::once())
            ->method('real_escape_string')
            ->willReturn($string);

        self::assertSame($string, $this->object->escapeString(new Connection($mysqli), $string));
    }

    public function testGetWarningCount(): void
    {
        $mysqli = (object) ['warning_count' => 30];
        self::assertSame(30, $this->object->getWarningCount(new Connection($mysqli)));
    }
}
