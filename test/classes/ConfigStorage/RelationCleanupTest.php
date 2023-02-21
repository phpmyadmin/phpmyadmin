<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\ConfigStorage\RelationCleanup
 */
class RelationCleanupTest extends AbstractTestCase
{
    public function testColumnWithoutRelations(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->never())->method('queryAsControlUser');

        (new RelationCleanup($dbi, new Relation($dbi)))->column('database', 'table', 'column');
    }

    public function testColumnWithRelations(): void
    {
        $relation = $this->createStub(Relation::class);
        $relation->method('getRelationParameters')->willReturn(RelationParameters::fromArray([
            'user' => 'user',
            'db' => 'pmadb',
            'commwork' => true,
            'displaywork' => true,
            'relwork' => true,
            'relation' => 'relation',
            'table_info' => 'table_info',
            'column_info' => 'column_info',
        ]));

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->any())->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));
        $result = $this->createStub(ResultInterface::class);
        $dbi->expects($this->exactly(4))->method('queryAsControlUser')->willReturnMap([
            [
                "DELETE FROM `pmadb`.`column_info` WHERE db_name = 'database' AND"
                . " table_name = 'table' AND column_name = 'column'",
                $result,
            ],
            [
                "DELETE FROM `pmadb`.`table_info` WHERE db_name = 'database' AND"
                . " table_name = 'table' AND display_field = 'column'",
                $result,
            ],
            [
                "DELETE FROM `pmadb`.`relation` WHERE master_db = 'database' AND"
                . " master_table = 'table' AND master_field = 'column'",
                $result,
            ],
            [
                "DELETE FROM `pmadb`.`relation` WHERE foreign_db = 'database' AND"
                . " foreign_table = 'table' AND foreign_field = 'column'",
                $result,
            ],
        ]);

        (new RelationCleanup($dbi, $relation))->column('database', 'table', 'column');
    }

    public function testTableWithoutRelations(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->never())->method('queryAsControlUser');

        (new RelationCleanup($dbi, new Relation($dbi)))->table('database', 'table');
    }

    public function testTableWithRelations(): void
    {
        $relation = $this->createStub(Relation::class);
        $relation->method('getRelationParameters')->willReturn(RelationParameters::fromArray([
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
        ]));

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->any())->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));
        $result = $this->createStub(ResultInterface::class);
        $dbi->expects($this->exactly(7))->method('queryAsControlUser')->willReturnMap([
            ["DELETE FROM `pmadb`.`column_info` WHERE db_name = 'database' AND table_name = 'table'", $result],
            ["DELETE FROM `pmadb`.`table_info` WHERE db_name = 'database' AND table_name = 'table'", $result],
            ["DELETE FROM `pmadb`.`table_coords` WHERE db_name = 'database' AND table_name = 'table'", $result],
            ["DELETE FROM `pmadb`.`relation` WHERE master_db = 'database' AND master_table = 'table'", $result],
            ["DELETE FROM `pmadb`.`relation` WHERE foreign_db = 'database' AND foreign_table = 'table'", $result],
            ["DELETE FROM `pmadb`.`table_uiprefs` WHERE db_name = 'database' AND table_name = 'table'", $result],
            [
                "DELETE FROM `pmadb`.`navigationhiding` WHERE db_name = 'database' AND"
                . " (table_name = 'table' OR (item_name = 'table' AND item_type = 'table'))",
                $result,
            ],
        ]);

        (new RelationCleanup($dbi, $relation))->table('database', 'table');
    }

    public function testDatabaseWithoutRelations(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->never())->method('queryAsControlUser');

        (new RelationCleanup($dbi, new Relation($dbi)))->database('database');
    }

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

        $dbi = $this->createPartialMock(DatabaseInterface::class, ['queryAsControlUser']);
        $dbi->expects($this->exactly(11))
            ->method('queryAsControlUser')
            ->withConsecutive(
                [$this->equalTo("DELETE FROM `pmadb`.`column_info` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`bookmark` WHERE dbase  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`table_info` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`pdf_pages` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`table_coords` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`relation` WHERE master_db  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`relation` WHERE foreign_db  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`table_uiprefs` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`navigationhiding` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`savedsearches` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`central_columns` WHERE db_name  = 'database'")]
            );

        (new RelationCleanup($dbi, new Relation($dbi)))->database('database');
    }

    public function testUserWithoutRelations(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->never())->method('queryAsControlUser');

        (new RelationCleanup($dbi, new Relation($dbi)))->user('user');
    }

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

        $dbi = $this->createPartialMock(DatabaseInterface::class, ['queryAsControlUser']);
        $dbi->expects($this->exactly(10))
            ->method('queryAsControlUser')
            ->withConsecutive(
                [$this->equalTo("DELETE FROM `pmadb`.`bookmark` WHERE `user`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`history` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`recent` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`favorite` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`table_uiprefs` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`userconfig` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`users` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`navigationhiding` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`savedsearches` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`designer_settings` WHERE `username`  = 'user'")]
            );

        (new RelationCleanup($dbi, new Relation($dbi)))->user('user');
    }
}
