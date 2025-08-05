<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RelationCleanup::class)]
class RelationCleanupTest extends AbstractTestCase
{
    public function testColumnWithoutRelations(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::never())->method('queryAsControlUser');

        (new RelationCleanup($dbi, new Relation($dbi)))->column('database', 'table', 'column');
    }

    public function testColumnWithRelations(): void
    {
        $relation = self::createStub(Relation::class);
        $relation->method('getRelationParameters')->willReturn(RelationParameters::fromArray([
            RelationParameters::USER => 'user',
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::COMM_WORK => true,
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::REL_WORK => true,
            RelationParameters::RELATION => 'relation',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::COLUMN_INFO => 'column_info',
        ]));

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $result = self::createStub(ResultInterface::class);
        $dbi->expects(self::exactly(4))->method('queryAsControlUser')->willReturnMap([
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
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::never())->method('queryAsControlUser');

        (new RelationCleanup($dbi, new Relation($dbi)))->table('database', 'table');
    }

    public function testTableWithRelations(): void
    {
        $relation = self::createStub(Relation::class);
        $relation->method('getRelationParameters')->willReturn(RelationParameters::fromArray([
            RelationParameters::USER => 'user',
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::COMM_WORK => true,
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::PDF_WORK => true,
            RelationParameters::REL_WORK => true,
            RelationParameters::UI_PREFS_WORK => true,
            RelationParameters::NAV_WORK => true,
            RelationParameters::RELATION => 'relation',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::TABLE_COORDS => 'table_coords',
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::TABLE_UI_PREFS => 'table_uiprefs',
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
        ]));

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $result = self::createStub(ResultInterface::class);
        $dbi->expects(self::exactly(7))->method('queryAsControlUser')->willReturnMap([
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
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::never())->method('queryAsControlUser');

        (new RelationCleanup($dbi, new Relation($dbi)))->database('database');
    }

    public function testDatabaseWithRelations(): void
    {
        $relation = self::createStub(Relation::class);
        $relation->method('getRelationParameters')->willReturn(RelationParameters::fromArray([
            RelationParameters::USER => 'user',
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::COMM_WORK => true,
            RelationParameters::BOOKMARK_WORK => true,
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::PDF_WORK => true,
            RelationParameters::REL_WORK => true,
            RelationParameters::UI_PREFS_WORK => true,
            RelationParameters::NAV_WORK => true,
            RelationParameters::SAVED_SEARCHES_WORK => true,
            RelationParameters::CENTRAL_COLUMNS_WORK => true,
            RelationParameters::BOOKMARK => 'bookmark',
            RelationParameters::RELATION => 'relation',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::TABLE_COORDS => 'table_coords',
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::TABLE_UI_PREFS => 'table_uiprefs',
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
            RelationParameters::SAVED_SEARCHES => 'savedsearches',
            RelationParameters::CENTRAL_COLUMNS => 'central_columns',
        ]));

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $result = self::createStub(ResultInterface::class);
        $dbi->expects(self::exactly(11))->method('queryAsControlUser')->willReturnMap([
            ["DELETE FROM `pmadb`.`column_info` WHERE db_name = 'database'", $result],
            ["DELETE FROM `pmadb`.`bookmark` WHERE dbase = 'database'", $result],
            ["DELETE FROM `pmadb`.`table_info` WHERE db_name = 'database'", $result],
            ["DELETE FROM `pmadb`.`pdf_pages` WHERE db_name = 'database'", $result],
            ["DELETE FROM `pmadb`.`table_coords` WHERE db_name = 'database'", $result],
            ["DELETE FROM `pmadb`.`relation` WHERE master_db = 'database'", $result],
            ["DELETE FROM `pmadb`.`relation` WHERE foreign_db = 'database'", $result],
            ["DELETE FROM `pmadb`.`table_uiprefs` WHERE db_name = 'database'", $result],
            ["DELETE FROM `pmadb`.`navigationhiding` WHERE db_name = 'database'", $result],
            ["DELETE FROM `pmadb`.`savedsearches` WHERE db_name = 'database'", $result],
            ["DELETE FROM `pmadb`.`central_columns` WHERE db_name = 'database'", $result],
        ]);

        (new RelationCleanup($dbi, $relation))->database('database');
    }

    public function testUserWithoutRelations(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::never())->method('queryAsControlUser');

        (new RelationCleanup($dbi, new Relation($dbi)))->user('user');
    }

    public function testUserWithRelations(): void
    {
        $relation = self::createStub(Relation::class);
        $relation->method('getRelationParameters')->willReturn(RelationParameters::fromArray([
            RelationParameters::USER => 'user',
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::BOOKMARK_WORK => true,
            RelationParameters::HISTORY_WORK => true,
            RelationParameters::RECENT_WORK => true,
            RelationParameters::FAVORITE_WORK => true,
            RelationParameters::UI_PREFS_WORK => true,
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::MENUS_WORK => true,
            RelationParameters::NAV_WORK => true,
            RelationParameters::SAVED_SEARCHES_WORK => true,
            RelationParameters::DESIGNER_SETTINGS_WORK => true,
            RelationParameters::BOOKMARK => 'bookmark',
            RelationParameters::HISTORY => 'history',
            RelationParameters::RECENT => 'recent',
            RelationParameters::FAVORITE => 'favorite',
            RelationParameters::TABLE_UI_PREFS => 'table_uiprefs',
            RelationParameters::USER_CONFIG => 'userconfig',
            RelationParameters::USERS => 'users',
            RelationParameters::USER_GROUPS => 'usergroups',
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
            RelationParameters::SAVED_SEARCHES => 'savedsearches',
            RelationParameters::DESIGNER_SETTINGS => 'designer_settings',
        ]));

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $result = self::createStub(ResultInterface::class);
        $dbi->expects(self::exactly(10))->method('queryAsControlUser')->willReturnMap([
            ["DELETE FROM `pmadb`.`bookmark` WHERE `user` = 'user'", $result],
            ["DELETE FROM `pmadb`.`history` WHERE `username` = 'user'", $result],
            ["DELETE FROM `pmadb`.`recent` WHERE `username` = 'user'", $result],
            ["DELETE FROM `pmadb`.`favorite` WHERE `username` = 'user'", $result],
            ["DELETE FROM `pmadb`.`table_uiprefs` WHERE `username` = 'user'", $result],
            ["DELETE FROM `pmadb`.`userconfig` WHERE `username` = 'user'", $result],
            ["DELETE FROM `pmadb`.`users` WHERE `username` = 'user'", $result],
            ["DELETE FROM `pmadb`.`navigationhiding` WHERE `username` = 'user'", $result],
            ["DELETE FROM `pmadb`.`savedsearches` WHERE `username` = 'user'", $result],
            ["DELETE FROM `pmadb`.`designer_settings` WHERE `username` = 'user'", $result],
        ]);

        (new RelationCleanup($dbi, $relation))->user('user');
    }
}
