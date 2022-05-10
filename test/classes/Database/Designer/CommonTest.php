<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database\Designer;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Database\Designer\Common;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Version;

use function sprintf;

/**
 * @covers \PhpMyAdmin\Database\Designer\Common
 */
class CommonTest extends AbstractTestCase
{
    /** @var Common */
    private $designerCommon;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 1;
        $_SESSION = [
            'relation' => [
                '1' => [
                    'version' => Version::VERSION,
                    'db' => 'pmadb',
                    'pdf_pages' => 'pdf_pages',
                    'pdfwork' => true,
                    'table_coords' => 'table_coords',
                ],
            ],
        ];
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
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
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
                DatabaseInterface::CONNECT_CONTROL
            );
        $GLOBALS['dbi'] = $dbi;

        $this->designerCommon = new Common($GLOBALS['dbi'], new Relation($dbi));

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
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                'SELECT `page_descr` FROM `pmadb`.`pdf_pages`'
                . ' WHERE `page_nr` = ' . $pg,
                null,
                null,
                DatabaseInterface::CONNECT_CONTROL
            )
            ->will($this->returnValue([$pageName]));
        $GLOBALS['dbi'] = $dbi;

        $this->designerCommon = new Common($GLOBALS['dbi'], new Relation($dbi));

        $result = $this->designerCommon->getPageName($pg);

        $this->assertEquals($pageName, $result);
    }

    /**
     * Test for deletePage()
     */
    public function testDeletePage(): void
    {
        $pg = 1;

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('queryAsControlUser')
            ->willReturnOnConsecutiveCalls($resultStub, $resultStub);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->designerCommon = new Common($GLOBALS['dbi'], new Relation($dbi));

        $result = $this->designerCommon->deletePage($pg);
        $this->assertTrue($result);
    }

    /**
     * Test for testGetDefaultPage() when there is a default page
     * (a page having the same name as database)
     */
    public function testGetDefaultPage(): void
    {
        $db = 'db';
        $default_pg = '2';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                'SELECT `page_nr` FROM `pmadb`.`pdf_pages`'
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                null,
                null,
                DatabaseInterface::CONNECT_CONTROL
            )
            ->will($this->returnValue([$default_pg]));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->designerCommon = new Common($GLOBALS['dbi'], new Relation($dbi));

        $result = $this->designerCommon->getDefaultPage($db);
        $this->assertEquals($default_pg, $result);
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

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                'SELECT `page_nr` FROM `pmadb`.`pdf_pages`'
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                null,
                null,
                DatabaseInterface::CONNECT_CONTROL
            )
            ->will($this->returnValue([]));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->designerCommon = new Common($GLOBALS['dbi'], new Relation($dbi));

        $result = $this->designerCommon->getDefaultPage($db);
        $this->assertEquals(-1, $result);
    }

    /**
     * Test for testGetLoadingPage() when there is a default page
     */
    public function testGetLoadingPageWithDefaultPage(): void
    {
        $db = 'db';
        $default_pg = '2';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                'SELECT `page_nr` FROM `pmadb`.`pdf_pages`'
                . " WHERE `db_name` = '" . $db . "'"
                . " AND `page_descr` = '" . $db . "'",
                null,
                null,
                DatabaseInterface::CONNECT_CONTROL
            )
            ->will($this->returnValue([$default_pg]));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->designerCommon = new Common($GLOBALS['dbi'], new Relation($dbi));

        $result = $this->designerCommon->getLoadingPage($db);
        $this->assertEquals($default_pg, $result);
    }

    /**
     * Test for testGetLoadingPage() when there is no default page
     */
    public function testGetLoadingPageWithNoDefaultPage(): void
    {
        $db = 'db';
        $first_pg = '1';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                [],
                [[$first_pg]]
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->designerCommon = new Common($GLOBALS['dbi'], new Relation($dbi));

        $result = $this->designerCommon->getLoadingPage($db);
        $this->assertEquals($first_pg, $result);
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
            . ' WHERE `TABLE_SCHEMA` IN (\'%s\') AND t.`TABLE_NAME` = \'%s\' ORDER BY Name ASC';

        $tableStatusQuery = 'SHOW TABLE STATUS FROM `%s` WHERE `Name` LIKE \'%s\'';

        $this->designerCommon = new Common($this->dbi, new Relation($this->dbi));

        $this->dummyDbi->addResult(
            sprintf(
                $tableSearchQuery,
                'db\\\'1',
                'table\\\'1'
            ),
            false// Make it fallback onto SHOW TABLE STATUS
        );

        $this->dummyDbi->addResult(
            sprintf(
                $tableSearchQuery,
                'db\\\'2',
                'table\\\'2'
            ),
            false// Make it fallback onto SHOW TABLE STATUS
        );

        $this->dummyDbi->addResult(
            sprintf(
                $tableStatusQuery,
                'db\'1',
                'table\\\'1%'
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
            ]
        );

        $this->dummyDbi->addResult(
            sprintf(
                $tableStatusQuery,
                'db\'2',
                'table\\\'2%'
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
            ]
        );

        $this->dummyDbi->addResult(
            'SHOW CREATE TABLE `db\'2`.`table\'2`',
            [
                [
                    'table\'2',
                    $createTableString,
                ],
            ],
            ['Table', 'Create Table']
        );
    }

    public function testRemoveRelationRelationDbNotWorking(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NaturalOrder'] = false;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'pmadb',
            'relation' => 'rel db',
        ])->toArray();

        parent::setGlobalDbi();
        $this->loadTestDataForRelationDeleteAddTests(
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1'
        );

        $result = $this->designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        $this->assertSame([
            false,
            'Error: Relational features are disabled!',
        ], $result);
    }

    public function testRemoveRelationWorkingRelationDb(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NaturalOrder'] = false;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'pmadb',
            'relwork' => true,
            'relation' => 'rel db',
        ])->toArray();

        parent::setGlobalDbi();

        $this->loadTestDataForRelationDeleteAddTests(
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1'
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
                'field\\\'1'// foreign_field
            ),
            []
        );

        $result = $this->designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        $this->assertSame([
            true,
            'Internal relationship has been removed.',
        ], $result);
    }

    public function testRemoveRelationWorkingRelationDbFoundFk(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NaturalOrder'] = false;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'pmadb',
            'relwork' => true,
            'relation' => 'rel db',
        ])->toArray();

        parent::setGlobalDbi();

        $this->loadTestDataForRelationDeleteAddTests(
            'CREATE TABLE `table\'2` ('
                . '    `field\'1` int(11) NOT NULL,'
                . '    `field\'2` int(5) DEFAULT NULL,'
                . '    `vc1` varchar(32) NOT NULL,'
                . '    UNIQUE KEY `field\'1` (`field\'1`),'
                . '    UNIQUE KEY `field\'2` (`field\'2`),'
                . '    UNIQUE KEY `vc1` (`vc1`),'
                . '    CONSTRAINT `table\'1_ibfk_field\'2` FOREIGN KEY (`field\'2`) REFERENCES `t2` (`field\'1`)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=latin1'
        );

        $GLOBALS['db'] = 'db\'1';// Fallback for Relation::searchColumnInForeigners

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
                'field\\\'1'// foreign_field
            ),
            []
        );

        $this->dummyDbi->addResult(
            sprintf(
                'ALTER TABLE `%s`.`%s` DROP FOREIGN KEY `%s`;',
                'db\'2', // db
                'table\'2', // table
                'table\'1_ibfk_field\'2' // fk name
            ),
            []
        );

        $result = $this->designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        $this->assertSame([
            true,
            'FOREIGN KEY relationship has been removed.',
        ], $result);
    }

    public function testRemoveRelationWorkingRelationDbDeleteFails(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NaturalOrder'] = false;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'pmadb',
            'relwork' => true,
            'relation' => 'rel db',
        ])->toArray();

        parent::setGlobalDbi();

        $this->loadTestDataForRelationDeleteAddTests(
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1'
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
                'field\\\'1'// foreign_field
            ),
            false// Delete failed
        );

        $result = $this->designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        $this->assertSame([
            false,
            'Error: Internal relationship could not be removed!<br>',
        ], $result);
    }
}
