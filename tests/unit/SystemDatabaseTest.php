<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\SystemColumn;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use const MYSQLI_TYPE_STRING;

#[CoversClass(SystemDatabase::class)]
#[CoversClass(SystemColumn::class)]
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
        Config::getInstance()->selectedServer['pmadb'] = '';

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::any())
            ->method('tryQuery')
            ->willReturn($resultStub);

        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::TABLE_COORDS => 'table_name',
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::DATABASE => 'information_schema',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::REL_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::PDF_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::RELATION => 'relation',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

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
        self::assertInstanceOf(DummyResult::class, $ret);
    }

    /**
     * Tests for PMA_getNewTransformationDataSql() method.
     */
    public function testPMAGetNewTransformationDataSql(): void
    {
        $resultStub = self::createMock(DummyResult::class);

        $resultStub->expects(self::any())
            ->method('fetchAssoc')
            ->willReturn([
                'table_name' => 'table_name',
                'column_name' => 'column_name',
                'comment' => 'comment',
                'mimetype' => 'mimetype',
                'transformation' => 'transformation',
                'transformation_options' => 'transformation_options',
            ]);

        $db = 'PMA_db';
        $columnMap = [new SystemColumn('table_name', 'column_name', null)];
        $viewName = 'view_name';

        $ret = $this->sysDb->getNewTransformationDataSql($resultStub, $columnMap, $viewName, $db);

        $sql = 'INSERT INTO `information_schema`.`column_info` '
            . '(`db_name`, `table_name`, `column_name`, `comment`, `mimetype`, '
            . '`transformation`, `transformation_options`) VALUES '
            . "('PMA_db', 'view_name', 'column_name', 'comment', 'mimetype', "
            . "'transformation', 'transformation_options')";

        self::assertSame($sql, $ret);
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

        self::assertEquals(
            new SystemColumn('meta1_table', 'meta1_name', 'view_columns1'),
            $columnMap[0],
        );
        self::assertEquals(
            new SystemColumn('meta2_table', 'meta2_name', 'view_columns2'),
            $columnMap[1],
        );

        $dummyDbi->assertAllQueriesConsumed();
    }
}
