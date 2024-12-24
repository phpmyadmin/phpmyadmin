<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database\Designer;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Designer\Common;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function sprintf;

#[CoversClass(Common::class)]
class CommonTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Common $designerCommon;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'pdf_pages' => 'pdf_pages',
            'pdfwork' => true,
            'table_coords' => 'table_coords',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }

    /**
     * Test for getTablePositions()
     */
    public function testGetTablePositions(): void
    {
        $pg = 1;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('fetchResult')
            ->with(
                "
            SELECT CONCAT_WS('.', `db_name`, `table_name`) AS `name`,
                `db_name` as `dbName`, `table_name` as `tableName`,
                `x` AS `X`,
                `y` AS `Y`,
                1 AS `V`,
                1 AS `H`
            FROM `pmadb`.`table_coords`
            WHERE pdf_page_number = " . $pg,
                'name',
                null,
                ConnectionType::ControlUser,
            );
        DatabaseInterface::$instance = $dbi;

        $this->designerCommon = new Common(DatabaseInterface::getInstance(), new Relation($dbi));

        $this->designerCommon->getTablePositions($pg);
    }

    /**
     * Test for getPageName()
     */
    public function testGetPageName(): void
    {
        $pg = 1;
        $pageName = 'pageName';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('fetchValue')
            ->with(
                'SELECT `page_descr` FROM `pmadb`.`pdf_pages`'
                . ' WHERE `page_nr` = ' . $pg,
                0,
                ConnectionType::ControlUser,
            )
            ->willReturn($pageName);
        DatabaseInterface::$instance = $dbi;

        $this->designerCommon = new Common(DatabaseInterface::getInstance(), new Relation($dbi));

        $result = $this->designerCommon->getPageName($pg);

        self::assertSame($pageName, $result);
    }

    /**
     * Test for deletePage()
     */
    public function testDeletePage(): void
    {
        $pg = 1;

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::exactly(2))
            ->method('queryAsControlUser')
            ->willReturn($resultStub, $resultStub);

        DatabaseInterface::$instance = $dbi;
        $this->designerCommon = new Common(DatabaseInterface::getInstance(), new Relation($dbi));

        $result = $this->designerCommon->deletePage($pg);
        self::assertTrue($result);
    }

    /**
     * Test for testGetDefaultPage() when there is a default page
     * (a page having the same name as database)
     */
    public function testGetDefaultPage(): void
    {
        $db = 'db';
        $defaultPg = '2';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('fetchValue')
            ->with(
                'SELECT `page_nr` FROM `pmadb`.`pdf_pages`'
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                0,
                ConnectionType::ControlUser,
            )
            ->willReturn($defaultPg);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        $this->designerCommon = new Common(DatabaseInterface::getInstance(), new Relation($dbi));

        $result = $this->designerCommon->getDefaultPage($db);
        self::assertSame((int) $defaultPg, $result);
    }

    /**
     * Test for testGetDefaultPage() when there is no default page
     */
    public function testGetDefaultPageWithNoDefaultPage(): void
    {
        $db = 'db';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('fetchValue')
            ->with(
                'SELECT `page_nr` FROM `pmadb`.`pdf_pages`'
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                0,
                ConnectionType::ControlUser,
            )
            ->willReturn(false);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        $this->designerCommon = new Common(DatabaseInterface::getInstance(), new Relation($dbi));

        $result = $this->designerCommon->getDefaultPage($db);
        self::assertSame(-1, $result);
    }

    /**
     * Test for testGetLoadingPage() when there is a default page
     */
    public function testGetLoadingPageWithDefaultPage(): void
    {
        $db = 'db';
        $defaultPg = '2';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('fetchValue')
            ->with(
                'SELECT `page_nr` FROM `pmadb`.`pdf_pages`'
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                0,
                ConnectionType::ControlUser,
            )
            ->willReturn($defaultPg);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        $this->designerCommon = new Common(DatabaseInterface::getInstance(), new Relation($dbi));

        $result = $this->designerCommon->getLoadingPage($db);
        self::assertSame((int) $defaultPg, $result);
    }

    /**
     * Test for testGetLoadingPage() when there is no default page
     */
    public function testGetLoadingPageWithNoDefaultPage(): void
    {
        $db = 'db';
        $firstPg = '1';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::exactly(2))
            ->method('fetchValue')
            ->willReturn(false, $firstPg);

        DatabaseInterface::$instance = $dbi;
        $this->designerCommon = new Common(DatabaseInterface::getInstance(), new Relation($dbi));

        $result = $this->designerCommon->getLoadingPage($db);
        self::assertSame((int) $firstPg, $result);
    }

    private function loadTestDataForRelationDeleteAddTests(string $createTableString): void
    {
        $tableSearchQuery = 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`,'
            . ' `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`,'
            . ' `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`,'
            . ' `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`,'
            . ' `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`,'
            . ' `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`,'
            . ' `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t'
            . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'%s\') AND t.`TABLE_NAME` COLLATE utf8_bin = \'%s\''
            . ' ORDER BY Name ASC';

        $tableStatusQuery = 'SHOW TABLE STATUS FROM `%s` WHERE `Name` LIKE \'%s\'';

        $this->designerCommon = new Common($this->dbi, new Relation($this->dbi));

        $this->dummyDbi->addResult(
            sprintf(
                $tableSearchQuery,
                'db\\\'1',
                'table\\\'1',
            ),
            false, // Make it fallback onto SHOW TABLE STATUS
        );

        $this->dummyDbi->addResult(
            sprintf(
                $tableSearchQuery,
                'db\\\'2',
                'table\\\'2',
            ),
            false, // Make it fallback onto SHOW TABLE STATUS
        );

        $this->dummyDbi->addResult(
            sprintf(
                $tableStatusQuery,
                'db\'1',
                'table\\\'1%',
            ),
            [
                [
                    // Partial
                    'table\'1',
                    'InnoDB',
                ],
            ],
            [
                // Partial
                'Name',
                'Engine',
            ],
        );

        $this->dummyDbi->addResult(
            sprintf(
                $tableStatusQuery,
                'db\'2',
                'table\\\'2%',
            ),
            [
                [
                    // Partial
                    'table\'2',
                    'InnoDB',
                ],
            ],
            [
                // Partial
                'Name',
                'Engine',
            ],
        );

        $this->dummyDbi->addResult(
            'SHOW CREATE TABLE `db\'2`.`table\'2`',
            [['table\'2', $createTableString]],
            ['Table', 'Create Table'],
        );
    }

    public function testRemoveRelationRelationDbNotWorking(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NaturalOrder'] = false;
        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'relation' => 'rel db',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $this->loadTestDataForRelationDeleteAddTests(
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1',
        );

        $result = $this->designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        self::assertEquals(Message::error('Error: Relational features are disabled!'), $result);
    }

    public function testRemoveRelationWorkingRelationDb(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NaturalOrder'] = false;
        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'relwork' => true,
            'relation' => 'rel db',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $this->loadTestDataForRelationDeleteAddTests(
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1',
        );

        $configurationStorageDeleteQuery = 'DELETE FROM `pmadb`.`rel db`'
            . ' WHERE master_db = \'%s\' AND master_table = \'%s\''
            . ' AND master_field = \'%s\' AND foreign_db = \'%s\''
            . ' AND foreign_table = \'%s\' AND foreign_field = \'%s\'';

        $this->dummyDbi->addResult(
            sprintf(
                $configurationStorageDeleteQuery,
                'db\\\'2', // master_db
                'table\\\'2', // master_table
                'field\\\'2', // master_field
                'db\\\'1', // foreign_db
                'table\\\'1', // foreign_table
                'field\\\'1', // foreign_field
            ),
            true,
        );

        $result = $this->designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        self::assertEquals(Message::success('Internal relationship has been removed.'), $result);
    }

    public function testRemoveRelationWorkingRelationDbFoundFk(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NaturalOrder'] = false;
        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'relwork' => true,
            'relation' => 'rel db',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $this->loadTestDataForRelationDeleteAddTests(
            'CREATE TABLE `table\'2` ('
                . '    `field\'1` int(11) NOT NULL,'
                . '    `field\'2` int(5) DEFAULT NULL,'
                . '    `vc1` varchar(32) NOT NULL,'
                . '    UNIQUE KEY `field\'1` (`field\'1`),'
                . '    UNIQUE KEY `field\'2` (`field\'2`),'
                . '    UNIQUE KEY `vc1` (`vc1`),'
                . '    CONSTRAINT `table\'1_ibfk_field\'2` FOREIGN KEY (`field\'2`) REFERENCES `t2` (`field\'1`)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=latin1',
        );

        Current::$database = 'db\'1';// Fallback for Relation::searchColumnInForeigners

        $configurationStorageDeleteQuery = 'DELETE FROM `pmadb`.`rel db`'
            . ' WHERE master_db = \'%s\' AND master_table = \'%s\''
            . ' AND master_field = \'%s\' AND foreign_db = \'%s\''
            . ' AND foreign_table = \'%s\' AND foreign_field = \'%s\'';

        $this->dummyDbi->addResult(
            sprintf(
                $configurationStorageDeleteQuery,
                'db\\\'2', // master_db
                'table\\\'2', // master_table
                'field\\\'2', // master_field
                'db\\\'1', // foreign_db
                'table\\\'1', // foreign_table
                'field\\\'1', // foreign_field
            ),
            true,
        );

        $this->dummyDbi->addResult(
            sprintf(
                'ALTER TABLE `%s`.`%s` DROP FOREIGN KEY `%s`;',
                'db\'2', // db
                'table\'2', // table
                'table\'1_ibfk_field\'2', // fk name
            ),
            true,
        );

        $result = $this->designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        self::assertEquals(Message::success('FOREIGN KEY relationship has been removed.'), $result);
    }

    public function testRemoveRelationWorkingRelationDbDeleteFails(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NaturalOrder'] = false;
        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'relwork' => true,
            'relation' => 'rel db',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $this->loadTestDataForRelationDeleteAddTests(
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1',
        );

        $configurationStorageDeleteQuery = 'DELETE FROM `pmadb`.`rel db`'
            . ' WHERE master_db = \'%s\' AND master_table = \'%s\''
            . ' AND master_field = \'%s\' AND foreign_db = \'%s\''
            . ' AND foreign_table = \'%s\' AND foreign_field = \'%s\'';

        $this->dummyDbi->addResult(
            sprintf(
                $configurationStorageDeleteQuery,
                'db\\\'2', // master_db
                'table\\\'2', // master_table
                'field\\\'2', // master_field
                'db\\\'1', // foreign_db
                'table\\\'1', // foreign_table
                'field\\\'1', // foreign_field
            ),
            false, // Delete failed
        );

        $result = $this->designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        self::assertEquals(Message::error('Error: Internal relationship could not be removed!<br>'), $result);
    }
}
