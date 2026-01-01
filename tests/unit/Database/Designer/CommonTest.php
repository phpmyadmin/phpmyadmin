<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database\Designer;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Database\Designer\Common;
use PhpMyAdmin\Message;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function sprintf;

#[CoversClass(Common::class)]
final class CommonTest extends AbstractTestCase
{
    public function testGetTablePositionsWithoutRelationParameters(): void
    {
        self::clearRelationParameters();
        $dbi = $this->createDatabaseInterface();
        self::assertSame([], (new Common($dbi, new Relation($dbi), new Config()))->getTablePositions(1));
    }

    public function testGetTablePositions(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT CONCAT_WS('.', `db_name`, `table_name`) AS `name`, `db_name` as `dbName`, `table_name` as `tableName`, `x` AS `X`, `y` AS `Y`, 1 AS `V`, 1 AS `H` FROM `pmadb`.`table_coords` WHERE pdf_page_number = 1",
            [
                ['sakila.actor', 'sakila', 'actor', '78', '211', '1', '1'],
                ['sakila.address', 'sakila', 'address', '550', '526', '1', '1'],
            ],
            ['name', 'dbName', 'tableName', 'X', 'Y', 'V', 'H'],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame(
            [
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'sakila.actor' => ['name' => 'sakila.actor', 'dbName' => 'sakila', 'tableName' => 'actor', 'X' => '78', 'Y' => '211', 'V' => '1', 'H' => '1'],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'sakila.address' => ['name' => 'sakila.address', 'dbName' => 'sakila', 'tableName' => 'address', 'X' => '550', 'Y' => '526', 'V' => '1', 'H' => '1'],
            ],
            (new Common($dbi, new Relation($dbi), new Config()))->getTablePositions(1),
        );

        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetPageNameWithoutRelationParameters(): void
    {
        self::clearRelationParameters();
        $dbi = $this->createDatabaseInterface();
        self::assertNull((new Common($dbi, new Relation($dbi), new Config()))->getPageName(1));
    }

    public function testGetPageName(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT `page_descr` FROM `pmadb`.`pdf_pages` WHERE `page_nr` = 1', [['pageName']]);
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame('pageName', (new Common($dbi, new Relation($dbi), new Config()))->getPageName(1));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testDeletePageWithoutRelationParameters(): void
    {
        self::clearRelationParameters();
        $dbi = $this->createDatabaseInterface();
        self::assertFalse((new Common($dbi, new Relation($dbi), new Config()))->deletePage(1));
    }

    public function testDeletePage(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('DELETE FROM `pmadb`.`table_coords` WHERE `pdf_page_number` = 1', true);
        $dbiDummy->addResult('DELETE FROM `pmadb`.`pdf_pages` WHERE `page_nr` = 1', true);
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertTrue((new Common($dbi, new Relation($dbi), new Config()))->deletePage(1));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetDefaultPageWithoutRelationParameters(): void
    {
        self::clearRelationParameters();
        $dbi = $this->createDatabaseInterface();
        self::assertSame(-1, (new Common($dbi, new Relation($dbi), new Config()))->getDefaultPage('test_db'));
    }

    /**
     * Test for testGetDefaultPage() when there is a default page
     * (a page having the same name as database)
     */
    public function testGetDefaultPage(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT `page_nr` FROM `pmadb`.`pdf_pages` WHERE `db_name` = 'test_db' AND `page_descr` = 'test_db'",
            [['2']],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame(2, (new Common($dbi, new Relation($dbi), new Config()))->getDefaultPage('test_db'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    /**
     * Test for testGetDefaultPage() when there is no default page
     */
    public function testGetDefaultPageWithNoDefaultPage(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT `page_nr` FROM `pmadb`.`pdf_pages` WHERE `db_name` = 'test_db' AND `page_descr` = 'test_db'",
            false,
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame(-1, (new Common($dbi, new Relation($dbi), new Config()))->getDefaultPage('test_db'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetLoadingPageWithoutRelationParameters(): void
    {
        self::clearRelationParameters();
        $dbi = $this->createDatabaseInterface();
        self::assertSame(-1, (new Common($dbi, new Relation($dbi), new Config()))->getLoadingPage('test_db'));
    }

    /**
     * Test for testGetLoadingPage() when there is a default page
     */
    public function testGetLoadingPageWithDefaultPage(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT `page_nr` FROM `pmadb`.`pdf_pages` WHERE `db_name` = 'test_db' AND `page_descr` = 'test_db'",
            [['2']],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame(2, (new Common($dbi, new Relation($dbi), new Config()))->getLoadingPage('test_db'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    /**
     * Test for testGetLoadingPage() when there is no default page
     */
    public function testGetLoadingPageWithNoDefaultPage(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT `page_nr` FROM `pmadb`.`pdf_pages` WHERE `db_name` = 'test_db' AND `page_descr` = 'test_db'",
            false,
        );
        $dbiDummy->addResult(
            "SELECT MIN(`page_nr`) FROM `pmadb`.`pdf_pages` WHERE `db_name` = 'test_db'",
            [['1']],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);

        self::assertSame(1, (new Common($dbi, new Relation($dbi), new Config()))->getLoadingPage('test_db'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    private function loadTestDataForRelationDeleteAddTests(DbiDummy $dbiDummy, string $createTableString): void
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

        $dbiDummy->addResult(
            sprintf(
                $tableSearchQuery,
                'db\\\'1',
                'table\\\'1',
            ),
            false, // Make it fallback onto SHOW TABLE STATUS
        );

        $dbiDummy->addResult(
            sprintf(
                $tableSearchQuery,
                'db\\\'2',
                'table\\\'2',
            ),
            false, // Make it fallback onto SHOW TABLE STATUS
        );

        $dbiDummy->addResult(
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

        $dbiDummy->addResult(
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

        $dbiDummy->addResult(
            'SHOW CREATE TABLE `db\'2`.`table\'2`',
            [['table\'2', $createTableString]],
            ['Table', 'Create Table'],
        );
    }

    public function testRemoveRelationRelationDbNotWorking(): void
    {
        self::clearRelationParameters();

        $dbiDummy = $this->createDbiDummy();

        $this->loadTestDataForRelationDeleteAddTests(
            $dbiDummy,
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1',
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $designerCommon = new Common($dbi, new Relation($dbi), new Config());
        $result = $designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        self::assertEquals(Message::error('Error: Relational features are disabled!'), $result);
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testRemoveRelationWorkingRelationDb(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();

        $this->loadTestDataForRelationDeleteAddTests(
            $dbiDummy,
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1',
        );

        $configurationStorageDeleteQuery = 'DELETE FROM `pmadb`.`rel db`'
            . ' WHERE master_db = \'%s\' AND master_table = \'%s\''
            . ' AND master_field = \'%s\' AND foreign_db = \'%s\''
            . ' AND foreign_table = \'%s\' AND foreign_field = \'%s\'';

        $dbiDummy->addResult(
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
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $designerCommon = new Common($dbi, new Relation($dbi), new Config());
        $result = $designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');

        self::assertEquals(Message::success('Internal relationship has been removed.'), $result);
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testRemoveRelationWorkingRelationDbFoundFk(): void
    {
        self::clearRelationParameters();

        $dbiDummy = $this->createDbiDummy();

        $this->loadTestDataForRelationDeleteAddTests(
            $dbiDummy,
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

        $dbiDummy->addResult(
            sprintf(
                'ALTER TABLE `%s`.`%s` DROP FOREIGN KEY `%s`;',
                'db\'2', // db
                'table\'2', // table
                'table\'1_ibfk_field\'2', // fk name
            ),
            true,
        );

        $dbi = $this->createDatabaseInterface($dbiDummy);

        $designerCommon = new Common($dbi, new Relation($dbi), new Config());
        $result = $designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');
        $dbiDummy->assertAllQueriesConsumed();

        self::assertEquals(Message::success('FOREIGN KEY relationship has been removed.'), $result);
    }

    public function testRemoveRelationWorkingRelationDbDeleteFails(): void
    {
        self::setRelationParameters();

        $dbiDummy = $this->createDbiDummy();

        $this->loadTestDataForRelationDeleteAddTests(
            $dbiDummy,
            'CREATE TABLE `table\'2` (`field\'1` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1',
        );

        $configurationStorageDeleteQuery = 'DELETE FROM `pmadb`.`rel db`'
            . ' WHERE master_db = \'%s\' AND master_table = \'%s\''
            . ' AND master_field = \'%s\' AND foreign_db = \'%s\''
            . ' AND foreign_table = \'%s\' AND foreign_field = \'%s\'';

        $dbiDummy->addResult(
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

        $dbi = $this->createDatabaseInterface($dbiDummy);

        $designerCommon = new Common($dbi, new Relation($dbi), new Config());

        $result = $designerCommon->removeRelation('db\'1.table\'1', 'field\'1', 'db\'2.table\'2', 'field\'2');
        $dbiDummy->assertAllQueriesConsumed();

        self::assertEquals(Message::error('Error: Internal relationship could not be removed!<br>'), $result);
    }

    private static function clearRelationParameters(): void
    {
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
    }

    private static function setRelationParameters(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::PDF_WORK => true,
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::TABLE_COORDS => 'table_coords',
            RelationParameters::REL_WORK => true,
            RelationParameters::RELATION => 'rel db',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }
}
