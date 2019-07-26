<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Dbi\DbiMysqli class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbi;

use mysqli;
use mysqli_result;
use PhpMyAdmin\Dbi\DbiMysqli;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PhpMyAdmin\Dbi\DbiMysqli class
 *
 * @package PhpMyAdmin-test
 */
class DbiMysqliTest extends TestCase
{
    /**
     * @var DbiMysqli
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        $this->object = new DbiMysqli();
    }

    /**
     * Test for selectDb
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function testNumRows(): void
    {
        $this->assertEquals(0, $this->object->numRows(false));
    }

    /**
     * Test for escapeString
     *
     * @return void
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
