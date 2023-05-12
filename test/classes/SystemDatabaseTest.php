<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\SystemColumn;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use ReflectionClass;

use const MYSQLI_TYPE_STRING;

/** @covers \PhpMyAdmin\SystemDatabase */
class SystemDatabaseTest extends AbstractTestCase
{
    /**
     * SystemDatabase instance
     */
    private SystemDatabase $sysDb;

    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * SET these to avoid undefine d index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['pmadb'] = '';

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $relationParameters = RelationParameters::fromArray([
            'table_coords' => 'table_name',
            'displaywork' => true,
            'db' => 'information_schema',
            'table_info' => 'table_info',
            'relwork' => true,
            'commwork' => true,
            'pdfwork' => true,
            'mimework' => true,
            'column_info' => 'column_info',
            'relation' => 'relation',
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $this->sysDb = new SystemDatabase($dbi);
    }

    /**
     * Tests for PMA_getExistingTransformationData() method.
     */
    public function testPMAGetExistingTransformationData(): void
    {
        $db = 'PMA_db';
        $ret = $this->sysDb->getExistingTransformationData($db);

        //validate that is the same as $dbi->tryQuery
        $this->assertInstanceOf(DummyResult::class, $ret);
    }

    /**
     * Tests for PMA_getNewTransformationDataSql() method.
     */
    public function testPMAGetNewTransformationDataSql(): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $resultStub->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    [
                        'table_name' => 'table_name',
                        'column_name' => 'column_name',
                        'comment' => 'comment',
                        'mimetype' => 'mimetype',
                        'transformation' => 'transformation',
                        'transformation_options' => 'transformation_options',
                    ],
                ),
            );

        $db = 'PMA_db';
        $columnMap = [new SystemColumn('table_name', 'column_name', null)];
        $viewName = 'view_name';

        $ret = $this->sysDb->getNewTransformationDataSql($resultStub, $columnMap, $viewName, $db);

        $sql = 'INSERT INTO `information_schema`.`column_info` '
            . '(`db_name`, `table_name`, `column_name`, `comment`, `mimetype`, '
            . '`transformation`, `transformation_options`) VALUES '
            . "('PMA_db', 'view_name', 'column_name', 'comment', 'mimetype', "
            . "'transformation', 'transformation_options')";

        $this->assertEquals($sql, $ret);
    }

    public function testGetColumnMapFromSql(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->addResult(
            'PMA_sql_query',
            true,
            [],
            [
                FieldHelper::fromArray([
                    'type' => MYSQLI_TYPE_STRING,
                    'table' => 'meta1_table',
                    'name' => 'meta1_name',
                ]),
                FieldHelper::fromArray([
                    'type' => MYSQLI_TYPE_STRING,
                    'table' => 'meta2_table',
                    'name' => 'meta2_name',
                ]),
            ],
        );

        $sqlQuery = 'PMA_sql_query';
        $viewColumns = ['view_columns1', 'view_columns2'];

        $systemDatabase = new SystemDatabase($dbi);
        $columnMap = $systemDatabase->getColumnMapFromSql($sqlQuery, $viewColumns);

        $this->assertEquals(
            new SystemColumn('meta1_table', 'meta1_name', 'view_columns1'),
            $columnMap[0],
        );
        $this->assertEquals(
            new SystemColumn('meta2_table', 'meta2_name', 'view_columns2'),
            $columnMap[1],
        );

        $dummyDbi->assertAllQueriesConsumed();
    }
}
