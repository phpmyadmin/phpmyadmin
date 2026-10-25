<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \PhpMyAdmin\ConfigStorage\RelationCleanup
 */
#[CoversClass(RelationCleanup::class)]
#[AllowMockObjectsWithoutExpectations]
class RelationCleanupTest extends AbstractTestCase
{
    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var DatabaseInterface&MockObject */
    protected $dbi;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 1;

        $relation = $this->getMockBuilder(Relation::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['queryAsControlUser'])
            ->getMock();
        $this->relationCleanup = new RelationCleanup($this->dbi, $relation);
    }

    /**
     * Test for column method
     */
    public function testColumnWithoutRelations(): void
    {
        $this->dbi->expects($this->never())
            ->method('queryAsControlUser');

        $this->relationCleanup->column('database', 'table', 'column');
    }

    /**
     * Test for column method
     */
    public function testColumnWithRelations(): void
    {
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'user' => 'user',
            'db' => 'pmadb',
            'commwork' => true,
            'displaywork' => true,
            'relwork' => true,
            'relation' => 'relation',
            'table_info' => 'table_info',
            'column_info' => 'column_info',
        ])->toArray();

        $resultStub = self::createStub(DummyResult::class);
        $this->dbi->expects(self::exactly(4))->method('queryAsControlUser')->willReturnMap([
            [
                "DELETE FROM `pmadb`.`column_info` WHERE db_name  = 'database' AND"
                    . " table_name = 'table' AND column_name = 'column'",
                $resultStub,
            ],
            [
                "DELETE FROM `pmadb`.`table_info` WHERE db_name  = 'database' AND"
                    . " table_name = 'table' AND display_field = 'column'",
                $resultStub,
            ],
            [
                "DELETE FROM `pmadb`.`relation` WHERE master_db  = 'database' AND"
                    . " master_table = 'table' AND master_field = 'column'",
                $resultStub,
            ],
            [
                "DELETE FROM `pmadb`.`relation` WHERE foreign_db  = 'database' AND"
                    . " foreign_table = 'table' AND foreign_field = 'column'",
                $resultStub,
            ],
        ]);

        $this->relationCleanup->column('database', 'table', 'column');
    }

    /**
     * Test for table method
     */
    public function testTableWithoutRelations(): void
    {
        $this->dbi->expects($this->never())
            ->method('queryAsControlUser');

        $this->relationCleanup->table('database', 'table');
    }

    /**
     * Test for table method
     */
    public function testTableWithRelations(): void
    {
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'user' => 'user',
            'db' => 'pmadb',
            'commwork' => true,
            'displaywork' => true,
            'pdfwork' => true,
            'relwork' => true,
            'uiprefswork' => true,
            'navwork' => true,
            'relation' => 'relation',
            'table_info' => 'table_info',
            'table_coords' => 'table_coords',
            'column_info' => 'column_info',
            'pdf_pages' => 'pdf_pages',
            'table_uiprefs' => 'table_uiprefs',
            'navigationhiding' => 'navigationhiding',
        ])->toArray();

        $resultStub = self::createStub(DummyResult::class);
        $this->dbi->expects(self::exactly(7))->method('queryAsControlUser')->willReturnMap([
            ["DELETE FROM `pmadb`.`column_info` WHERE db_name  = 'database' AND table_name = 'table'", $resultStub],
            ["DELETE FROM `pmadb`.`table_info` WHERE db_name  = 'database' AND table_name = 'table'", $resultStub],
            ["DELETE FROM `pmadb`.`table_coords` WHERE db_name  = 'database' AND table_name = 'table'", $resultStub],
            ["DELETE FROM `pmadb`.`relation` WHERE master_db  = 'database' AND master_table = 'table'", $resultStub],
            ["DELETE FROM `pmadb`.`relation` WHERE foreign_db  = 'database' AND foreign_table = 'table'", $resultStub],
            ["DELETE FROM `pmadb`.`table_uiprefs` WHERE db_name  = 'database' AND table_name = 'table'", $resultStub],
            [
                "DELETE FROM `pmadb`.`navigationhiding` WHERE db_name  = 'database' AND"
                    . " (table_name = 'table' OR (item_name = 'table' AND item_type = 'table'))",
                $resultStub,
            ],
        ]);

        $this->relationCleanup->table('database', 'table');
    }

    /**
     * Test for database method
     */
    public function testDatabaseWithoutRelations(): void
    {
        $this->dbi->expects($this->never())
            ->method('queryAsControlUser');

        $this->relationCleanup->database('database');
    }

    /**
     * Test for database method
     */
    public function testDatabaseWithRelations(): void
    {
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'user' => 'user',
            'db' => 'pmadb',
            'commwork' => true,
            'bookmarkwork' => true,
            'displaywork' => true,
            'pdfwork' => true,
            'relwork' => true,
            'uiprefswork' => true,
            'navwork' => true,
            'savedsearcheswork' => true,
            'centralcolumnswork' => true,
            'bookmark' => 'bookmark',
            'relation' => 'relation',
            'table_info' => 'table_info',
            'table_coords' => 'table_coords',
            'column_info' => 'column_info',
            'pdf_pages' => 'pdf_pages',
            'table_uiprefs' => 'table_uiprefs',
            'navigationhiding' => 'navigationhiding',
            'savedsearches' => 'savedsearches',
            'central_columns' => 'central_columns',
        ])->toArray();

        $resultStub = self::createStub(DummyResult::class);
        $this->dbi->expects(self::exactly(11))->method('queryAsControlUser')->willReturnMap([
            ["DELETE FROM `pmadb`.`column_info` WHERE db_name  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`bookmark` WHERE dbase  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`table_info` WHERE db_name  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`pdf_pages` WHERE db_name  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`table_coords` WHERE db_name  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`relation` WHERE master_db  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`relation` WHERE foreign_db  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`table_uiprefs` WHERE db_name  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`navigationhiding` WHERE db_name  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`savedsearches` WHERE db_name  = 'database'", $resultStub],
            ["DELETE FROM `pmadb`.`central_columns` WHERE db_name  = 'database'", $resultStub],
        ]);

        $this->relationCleanup->database('database');
    }

    /**
     * Test for user method
     */
    public function testUserWithoutRelations(): void
    {
        $this->dbi->expects($this->never())
            ->method('queryAsControlUser');

        $this->relationCleanup->user('user');
    }

    /**
     * Test for user method
     */
    public function testUserWithRelations(): void
    {
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'user' => 'user',
            'db' => 'pmadb',
            'bookmarkwork' => true,
            'historywork' => true,
            'recentwork' => true,
            'favoritework' => true,
            'uiprefswork' => true,
            'userconfigwork' => true,
            'menuswork' => true,
            'navwork' => true,
            'savedsearcheswork' => true,
            'designersettingswork' => true,
            'bookmark' => 'bookmark',
            'history' => 'history',
            'recent' => 'recent',
            'favorite' => 'favorite',
            'table_uiprefs' => 'table_uiprefs',
            'userconfig' => 'userconfig',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'navigationhiding' => 'navigationhiding',
            'savedsearches' => 'savedsearches',
            'designer_settings' => 'designer_settings',
        ])->toArray();

        $resultStub = self::createStub(DummyResult::class);
        $this->dbi->expects(self::exactly(10))->method('queryAsControlUser')->willReturnMap([
            ["DELETE FROM `pmadb`.`bookmark` WHERE `user`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`history` WHERE `username`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`recent` WHERE `username`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`favorite` WHERE `username`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`table_uiprefs` WHERE `username`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`userconfig` WHERE `username`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`users` WHERE `username`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`navigationhiding` WHERE `username`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`savedsearches` WHERE `username`  = 'user'", $resultStub],
            ["DELETE FROM `pmadb`.`designer_settings` WHERE `username`  = 'user'", $resultStub],
        ]);

        $this->relationCleanup->user('user');
    }
}
