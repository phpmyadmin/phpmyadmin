<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli;
use mysqli_result;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Tests\AbstractTestCase;
use const MYSQLI_ASSOC;
use const MYSQLI_BOTH;
use const MYSQLI_NUM;
use const PHP_VERSION_ID;

class DbiMysqliTest extends AbstractTestCase
{
    /** @var DbiMysqli */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new DbiMysqli();
    }

    public function testGetClientInfo(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('This test requires PHP 8.1');
        }

        /** @var mysqli $obj */
        $obj = null;
        $this->assertNotEmpty($this->object->getClientInfo($obj));
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
     * Test for fetchArray
     */
    public function testFetchArray(): void
    {
        $expected = [];
        $result = $this->createMock(mysqli_result::class);
        $result->expects($this->once())
            ->method('fetch_array')
            ->with($this->equalTo(MYSQLI_BOTH))
            ->willReturn($expected);

        $this->assertEquals($expected, $this->object->fetchArray($result));
    }

    /**
     * Test for fetchAssoc
     */
    public function testFetchAssoc(): void
    {
        $expected = [];
        $result = $this->createMock(mysqli_result::class);
        $result->expects($this->once())
            ->method('fetch_array')
            ->with($this->equalTo(MYSQLI_ASSOC))
            ->willReturn($expected);

        $this->assertEquals($expected, $this->object->fetchAssoc($result));
    }

    /**
     * Test for fetchRow
     */
    public function testFetchRow(): void
    {
        $expected = [];
        $result = $this->createMock(mysqli_result::class);
        $result->expects($this->once())
            ->method('fetch_array')
            ->with($this->equalTo(MYSQLI_NUM))
            ->willReturn($expected);

        $this->assertEquals($expected, $this->object->fetchRow($result));
    }

    /**
     * Test for dataSeek
     */
    public function testDataSeek(): void
    {
        $offset = 1;
        $result = $this->createMock(mysqli_result::class);
        $result->expects($this->once())
            ->method('data_seek')
            ->with($this->equalTo($offset))
            ->willReturn(true);

        $this->assertTrue($this->object->dataSeek($result, $offset));
    }

    /**
     * Test for freeResult
     */
    public function testFreeResult(): void
    {
        $result = $this->createMock(mysqli_result::class);
        $result->expects($this->once())
            ->method('close');

        $this->object->freeResult($result);
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
        $mysqli->expects($this->once())
            ->method('store_result')
            ->willReturn(true);

        $this->assertTrue($this->object->storeResult($mysqli));
    }

    /**
     * Test for numRows
     */
    public function testNumRows(): void
    {
        $this->assertEquals(0, $this->object->numRows(false));
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
