<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Features\BrowserTransformationFeature;
use PhpMyAdmin\ConfigStorage\Features\CentralColumnsFeature;
use PhpMyAdmin\ConfigStorage\Features\ColumnCommentsFeature;
use PhpMyAdmin\ConfigStorage\Features\ConfigurableMenusFeature;
use PhpMyAdmin\ConfigStorage\Features\DatabaseDesignerSettingsFeature;
use PhpMyAdmin\ConfigStorage\Features\DisplayFeature;
use PhpMyAdmin\ConfigStorage\Features\ExportTemplatesFeature;
use PhpMyAdmin\ConfigStorage\Features\FavoriteTablesFeature;
use PhpMyAdmin\ConfigStorage\Features\NavigationItemsHidingFeature;
use PhpMyAdmin\ConfigStorage\Features\PdfFeature;
use PhpMyAdmin\ConfigStorage\Features\RecentlyUsedTablesFeature;
use PhpMyAdmin\ConfigStorage\Features\RelationFeature;
use PhpMyAdmin\ConfigStorage\Features\SavedQueryByExampleSearchesFeature;
use PhpMyAdmin\ConfigStorage\Features\SqlHistoryFeature;
use PhpMyAdmin\ConfigStorage\Features\TrackingFeature;
use PhpMyAdmin\ConfigStorage\Features\UiPreferencesFeature;
use PhpMyAdmin\ConfigStorage\Features\UserPreferencesFeature;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelationParameters::class)]
#[CoversClass(BookmarkFeature::class)]
#[CoversClass(BrowserTransformationFeature::class)]
#[CoversClass(CentralColumnsFeature::class)]
#[CoversClass(ColumnCommentsFeature::class)]
#[CoversClass(ConfigurableMenusFeature::class)]
#[CoversClass(DatabaseDesignerSettingsFeature::class)]
#[CoversClass(DisplayFeature::class)]
#[CoversClass(ExportTemplatesFeature::class)]
#[CoversClass(FavoriteTablesFeature::class)]
#[CoversClass(NavigationItemsHidingFeature::class)]
#[CoversClass(PdfFeature::class)]
#[CoversClass(RecentlyUsedTablesFeature::class)]
#[CoversClass(RelationFeature::class)]
#[CoversClass(SavedQueryByExampleSearchesFeature::class)]
#[CoversClass(SqlHistoryFeature::class)]
#[CoversClass(TrackingFeature::class)]
#[CoversClass(UiPreferencesFeature::class)]
#[CoversClass(UserPreferencesFeature::class)]
final class RelationParametersTest extends TestCase
{
    public function testFeaturesWithTwoTables(): void
    {
        self::assertNull(RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::TABLE_COORDS => ' invalid ',
            RelationParameters::PDF_WORK => true,
        ])->pdfFeature);
        self::assertNull(RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::PDF_PAGES => ' invalid ',
            RelationParameters::TABLE_COORDS => 'table_coords',
            RelationParameters::PDF_WORK => true,
        ])->pdfFeature);
        self::assertNull(RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::RELATION => 'relation',
            RelationParameters::TABLE_INFO => ' invalid ',
            RelationParameters::DISPLAY_WORK => true,
        ])->displayFeature);
        self::assertNull(RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::RELATION => ' invalid ',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::DISPLAY_WORK => true,
        ])->displayFeature);
        self::assertNull(RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::USER_GROUPS => 'usergroups',
            RelationParameters::USERS => ' invalid ',
            RelationParameters::MENUS_WORK => true,
        ])->configurableMenusFeature);
        self::assertNull(RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::USER_GROUPS => ' invalid ',
            RelationParameters::USERS => 'users',
            RelationParameters::MENUS_WORK => true,
        ])->configurableMenusFeature);
    }

    public function testFeaturesWithSharedTable(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::RELATION => 'relation',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::MIME_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::REL_WORK => true,
        ]);
        self::assertNotNull($relationParameters->browserTransformationFeature);
        self::assertNotNull($relationParameters->columnCommentsFeature);
        self::assertNotNull($relationParameters->displayFeature);
        self::assertNotNull($relationParameters->relationFeature);
        self::assertSame(
            $relationParameters->browserTransformationFeature->columnInfo,
            $relationParameters->columnCommentsFeature->columnInfo,
        );
        self::assertSame($relationParameters->relationFeature->relation, $relationParameters->displayFeature->relation);

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::RELATION => 'relation',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::MIME_WORK => false,
            RelationParameters::COMM_WORK => true,
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::REL_WORK => false,
        ]);
        self::assertNull($relationParameters->browserTransformationFeature);
        self::assertNotNull($relationParameters->columnCommentsFeature);
        self::assertNotNull($relationParameters->displayFeature);
        self::assertNull($relationParameters->relationFeature);
    }

    public function testFeaturesHaveSameDatabase(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::BOOKMARK => 'bookmark',
            RelationParameters::CENTRAL_COLUMNS => 'central_columns',
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::DESIGNER_SETTINGS => 'designer_settings',
            RelationParameters::EXPORT_TEMPLATES => 'export_templates',
            RelationParameters::FAVORITE => 'favorite',
            RelationParameters::HISTORY => 'history',
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::RECENT => 'recent',
            RelationParameters::RELATION => 'relation',
            RelationParameters::SAVED_SEARCHES => 'savedsearches',
            RelationParameters::TABLE_COORDS => 'table_coords',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::TABLE_UI_PREFS => 'table_uiprefs',
            RelationParameters::TRACKING => 'tracking',
            RelationParameters::USER_CONFIG => 'userconfig',
            RelationParameters::USER_GROUPS => 'usergroups',
            RelationParameters::USERS => 'users',
            RelationParameters::BOOKMARK_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::CENTRAL_COLUMNS_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MENUS_WORK => true,
            RelationParameters::DESIGNER_SETTINGS_WORK => true,
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::EXPORT_TEMPLATES_WORK => true,
            RelationParameters::FAVORITE_WORK => true,
            RelationParameters::NAV_WORK => true,
            RelationParameters::PDF_WORK => true,
            RelationParameters::RECENT_WORK => true,
            RelationParameters::REL_WORK => true,
            RelationParameters::SAVED_SEARCHES_WORK => true,
            RelationParameters::HISTORY_WORK => true,
            RelationParameters::TRACKING_WORK => true,
            RelationParameters::UI_PREFS_WORK => true,
            RelationParameters::USER_CONFIG_WORK => true,
        ]);
        self::assertInstanceOf(DatabaseName::class, $relationParameters->db);
        self::assertSame('db', $relationParameters->db->getName());
        self::assertNotNull($relationParameters->bookmarkFeature);
        self::assertSame($relationParameters->db, $relationParameters->bookmarkFeature->database);
        self::assertNotNull($relationParameters->browserTransformationFeature);
        self::assertSame($relationParameters->db, $relationParameters->browserTransformationFeature->database);
        self::assertNotNull($relationParameters->centralColumnsFeature);
        self::assertSame($relationParameters->db, $relationParameters->centralColumnsFeature->database);
        self::assertNotNull($relationParameters->columnCommentsFeature);
        self::assertSame($relationParameters->db, $relationParameters->columnCommentsFeature->database);
        self::assertNotNull($relationParameters->configurableMenusFeature);
        self::assertSame($relationParameters->db, $relationParameters->configurableMenusFeature->database);
        self::assertNotNull($relationParameters->databaseDesignerSettingsFeature);
        self::assertSame($relationParameters->db, $relationParameters->databaseDesignerSettingsFeature->database);
        self::assertNotNull($relationParameters->displayFeature);
        self::assertSame($relationParameters->db, $relationParameters->displayFeature->database);
        self::assertNotNull($relationParameters->exportTemplatesFeature);
        self::assertSame($relationParameters->db, $relationParameters->exportTemplatesFeature->database);
        self::assertNotNull($relationParameters->favoriteTablesFeature);
        self::assertSame($relationParameters->db, $relationParameters->favoriteTablesFeature->database);
        self::assertNotNull($relationParameters->navigationItemsHidingFeature);
        self::assertSame($relationParameters->db, $relationParameters->navigationItemsHidingFeature->database);
        self::assertNotNull($relationParameters->pdfFeature);
        self::assertSame($relationParameters->db, $relationParameters->pdfFeature->database);
        self::assertNotNull($relationParameters->recentlyUsedTablesFeature);
        self::assertSame($relationParameters->db, $relationParameters->recentlyUsedTablesFeature->database);
        self::assertNotNull($relationParameters->relationFeature);
        self::assertSame($relationParameters->db, $relationParameters->relationFeature->database);
        self::assertNotNull($relationParameters->savedQueryByExampleSearchesFeature);
        self::assertSame($relationParameters->db, $relationParameters->savedQueryByExampleSearchesFeature->database);
        self::assertNotNull($relationParameters->sqlHistoryFeature);
        self::assertSame($relationParameters->db, $relationParameters->sqlHistoryFeature->database);
        self::assertNotNull($relationParameters->trackingFeature);
        self::assertSame($relationParameters->db, $relationParameters->trackingFeature->database);
        self::assertNotNull($relationParameters->uiPreferencesFeature);
        self::assertSame($relationParameters->db, $relationParameters->uiPreferencesFeature->database);
        self::assertNotNull($relationParameters->userPreferencesFeature);
        self::assertSame($relationParameters->db, $relationParameters->userPreferencesFeature->database);
    }

    public function testHasAllFeatures(): void
    {
        $params = [
            RelationParameters::DATABASE => 'db',
            RelationParameters::BOOKMARK => 'bookmark',
            RelationParameters::CENTRAL_COLUMNS => 'central_columns',
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::DESIGNER_SETTINGS => 'designer_settings',
            RelationParameters::EXPORT_TEMPLATES => 'export_templates',
            RelationParameters::FAVORITE => 'favorite',
            RelationParameters::HISTORY => 'history',
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::RECENT => 'recent',
            RelationParameters::RELATION => 'relation',
            RelationParameters::SAVED_SEARCHES => 'savedsearches',
            RelationParameters::TABLE_COORDS => 'table_coords',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::TABLE_UI_PREFS => 'table_uiprefs',
            RelationParameters::TRACKING => 'tracking',
            RelationParameters::USER_CONFIG => 'userconfig',
            RelationParameters::USER_GROUPS => 'usergroups',
            RelationParameters::USERS => 'users',
            RelationParameters::BOOKMARK_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::CENTRAL_COLUMNS_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MENUS_WORK => true,
            RelationParameters::DESIGNER_SETTINGS_WORK => true,
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::EXPORT_TEMPLATES_WORK => true,
            RelationParameters::FAVORITE_WORK => true,
            RelationParameters::NAV_WORK => true,
            RelationParameters::PDF_WORK => true,
            RelationParameters::RECENT_WORK => true,
            RelationParameters::REL_WORK => true,
            RelationParameters::SAVED_SEARCHES_WORK => true,
            RelationParameters::HISTORY_WORK => true,
            RelationParameters::TRACKING_WORK => true,
            RelationParameters::UI_PREFS_WORK => true,
            RelationParameters::USER_CONFIG_WORK => true,
        ];
        self::assertFalse(RelationParameters::fromArray([])->hasAllFeatures());
        self::assertTrue(RelationParameters::fromArray($params)->hasAllFeatures());
        $params[RelationParameters::BOOKMARK_WORK] = false;
        self::assertFalse(RelationParameters::fromArray($params)->hasAllFeatures());
    }

    /**
     * @param array<string, bool|string|int|null> $params
     * @param array<string, bool|string|null>     $expected
     */
    #[DataProvider('providerForTestToArray')]
    public function testToArray(array $params, array $expected): void
    {
        self::assertSame($expected, RelationParameters::fromArray($params)->toArray());
    }

    /** @return array<string, array{array<string, bool|string|int|null>, array<string, bool|string|null>}> */
    public static function providerForTestToArray(): array
    {
        return [
            'default values' => [
                [],
                [
                    RelationParameters::VERSION => Version::VERSION,
                    RelationParameters::USER => null,
                    RelationParameters::DATABASE => null,
                    RelationParameters::BOOKMARK => null,
                    RelationParameters::CENTRAL_COLUMNS => null,
                    RelationParameters::COLUMN_INFO => null,
                    RelationParameters::DESIGNER_SETTINGS => null,
                    RelationParameters::EXPORT_TEMPLATES => null,
                    RelationParameters::FAVORITE => null,
                    RelationParameters::HISTORY => null,
                    RelationParameters::NAVIGATION_HIDING => null,
                    RelationParameters::PDF_PAGES => null,
                    RelationParameters::RECENT => null,
                    RelationParameters::RELATION => null,
                    RelationParameters::SAVED_SEARCHES => null,
                    RelationParameters::TABLE_COORDS => null,
                    RelationParameters::TABLE_INFO => null,
                    RelationParameters::TABLE_UI_PREFS => null,
                    RelationParameters::TRACKING => null,
                    RelationParameters::USER_CONFIG => null,
                    RelationParameters::USER_GROUPS => null,
                    RelationParameters::USERS => null,
                    RelationParameters::BOOKMARK_WORK => false,
                    RelationParameters::MIME_WORK => false,
                    RelationParameters::CENTRAL_COLUMNS_WORK => false,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MENUS_WORK => false,
                    RelationParameters::DESIGNER_SETTINGS_WORK => false,
                    RelationParameters::DISPLAY_WORK => false,
                    RelationParameters::EXPORT_TEMPLATES_WORK => false,
                    RelationParameters::FAVORITE_WORK => false,
                    RelationParameters::NAV_WORK => false,
                    RelationParameters::PDF_WORK => false,
                    RelationParameters::RECENT_WORK => false,
                    RelationParameters::REL_WORK => false,
                    RelationParameters::SAVED_SEARCHES_WORK => false,
                    RelationParameters::HISTORY_WORK => false,
                    RelationParameters::TRACKING_WORK => false,
                    RelationParameters::UI_PREFS_WORK => false,
                    RelationParameters::USER_CONFIG_WORK => false,
                    RelationParameters::ALL_WORKS => false,
                ],
            ],
            'default values 2' => [
                [
                    RelationParameters::REL_WORK => false,
                    RelationParameters::DISPLAY_WORK => false,
                    RelationParameters::BOOKMARK_WORK => false,
                    RelationParameters::PDF_WORK => false,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MIME_WORK => false,
                    RelationParameters::HISTORY_WORK => false,
                    RelationParameters::RECENT_WORK => false,
                    RelationParameters::FAVORITE_WORK => false,
                    RelationParameters::UI_PREFS_WORK => false,
                    RelationParameters::TRACKING_WORK => false,
                    RelationParameters::USER_CONFIG_WORK => false,
                    RelationParameters::MENUS_WORK => false,
                    RelationParameters::NAV_WORK => false,
                    RelationParameters::SAVED_SEARCHES_WORK => false,
                    RelationParameters::CENTRAL_COLUMNS_WORK => false,
                    RelationParameters::DESIGNER_SETTINGS_WORK => false,
                    RelationParameters::EXPORT_TEMPLATES_WORK => false,
                    RelationParameters::ALL_WORKS => false,
                    RelationParameters::USER => null,
                    RelationParameters::DATABASE => null,
                    RelationParameters::BOOKMARK => null,
                    RelationParameters::CENTRAL_COLUMNS => null,
                    RelationParameters::COLUMN_INFO => null,
                    RelationParameters::DESIGNER_SETTINGS => null,
                    RelationParameters::EXPORT_TEMPLATES => null,
                    RelationParameters::FAVORITE => null,
                    RelationParameters::HISTORY => null,
                    RelationParameters::NAVIGATION_HIDING => null,
                    RelationParameters::PDF_PAGES => null,
                    RelationParameters::RECENT => null,
                    RelationParameters::RELATION => null,
                    RelationParameters::SAVED_SEARCHES => null,
                    RelationParameters::TABLE_COORDS => null,
                    RelationParameters::TABLE_INFO => null,
                    RelationParameters::TABLE_UI_PREFS => null,
                    RelationParameters::TRACKING => null,
                    RelationParameters::USER_CONFIG => null,
                    RelationParameters::USER_GROUPS => null,
                    RelationParameters::USERS => null,
                ],
                [
                    RelationParameters::VERSION => Version::VERSION,
                    RelationParameters::USER => null,
                    RelationParameters::DATABASE => null,
                    RelationParameters::BOOKMARK => null,
                    RelationParameters::CENTRAL_COLUMNS => null,
                    RelationParameters::COLUMN_INFO => null,
                    RelationParameters::DESIGNER_SETTINGS => null,
                    RelationParameters::EXPORT_TEMPLATES => null,
                    RelationParameters::FAVORITE => null,
                    RelationParameters::HISTORY => null,
                    RelationParameters::NAVIGATION_HIDING => null,
                    RelationParameters::PDF_PAGES => null,
                    RelationParameters::RECENT => null,
                    RelationParameters::RELATION => null,
                    RelationParameters::SAVED_SEARCHES => null,
                    RelationParameters::TABLE_COORDS => null,
                    RelationParameters::TABLE_INFO => null,
                    RelationParameters::TABLE_UI_PREFS => null,
                    RelationParameters::TRACKING => null,
                    RelationParameters::USER_CONFIG => null,
                    RelationParameters::USER_GROUPS => null,
                    RelationParameters::USERS => null,
                    RelationParameters::BOOKMARK_WORK => false,
                    RelationParameters::MIME_WORK => false,
                    RelationParameters::CENTRAL_COLUMNS_WORK => false,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MENUS_WORK => false,
                    RelationParameters::DESIGNER_SETTINGS_WORK => false,
                    RelationParameters::DISPLAY_WORK => false,
                    RelationParameters::EXPORT_TEMPLATES_WORK => false,
                    RelationParameters::FAVORITE_WORK => false,
                    RelationParameters::NAV_WORK => false,
                    RelationParameters::PDF_WORK => false,
                    RelationParameters::RECENT_WORK => false,
                    RelationParameters::REL_WORK => false,
                    RelationParameters::SAVED_SEARCHES_WORK => false,
                    RelationParameters::HISTORY_WORK => false,
                    RelationParameters::TRACKING_WORK => false,
                    RelationParameters::UI_PREFS_WORK => false,
                    RelationParameters::USER_CONFIG_WORK => false,
                    RelationParameters::ALL_WORKS => false,
                ],
            ],
            'valid values' => [
                [
                    RelationParameters::REL_WORK => true,
                    RelationParameters::DISPLAY_WORK => true,
                    RelationParameters::BOOKMARK_WORK => true,
                    RelationParameters::PDF_WORK => true,
                    RelationParameters::COMM_WORK => true,
                    RelationParameters::MIME_WORK => true,
                    RelationParameters::HISTORY_WORK => true,
                    RelationParameters::RECENT_WORK => true,
                    RelationParameters::FAVORITE_WORK => true,
                    RelationParameters::UI_PREFS_WORK => true,
                    RelationParameters::TRACKING_WORK => true,
                    RelationParameters::USER_CONFIG_WORK => true,
                    RelationParameters::MENUS_WORK => true,
                    RelationParameters::NAV_WORK => true,
                    RelationParameters::SAVED_SEARCHES_WORK => true,
                    RelationParameters::CENTRAL_COLUMNS_WORK => true,
                    RelationParameters::DESIGNER_SETTINGS_WORK => true,
                    RelationParameters::EXPORT_TEMPLATES_WORK => true,
                    RelationParameters::ALL_WORKS => true,
                    RelationParameters::USER => 'user',
                    RelationParameters::DATABASE => 'db',
                    RelationParameters::BOOKMARK => 'bookmark',
                    RelationParameters::CENTRAL_COLUMNS => 'central_columns',
                    RelationParameters::COLUMN_INFO => 'column_info',
                    RelationParameters::DESIGNER_SETTINGS => 'designer_settings',
                    RelationParameters::EXPORT_TEMPLATES => 'export_templates',
                    RelationParameters::FAVORITE => 'favorite',
                    RelationParameters::HISTORY => 'history',
                    RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
                    RelationParameters::PDF_PAGES => 'pdf_pages',
                    RelationParameters::RECENT => 'recent',
                    RelationParameters::RELATION => 'relation',
                    RelationParameters::SAVED_SEARCHES => 'savedsearches',
                    RelationParameters::TABLE_COORDS => 'table_coords',
                    RelationParameters::TABLE_INFO => 'table_info',
                    RelationParameters::TABLE_UI_PREFS => 'table_uiprefs',
                    RelationParameters::TRACKING => 'tracking',
                    RelationParameters::USER_CONFIG => 'userconfig',
                    RelationParameters::USER_GROUPS => 'usergroups',
                    RelationParameters::USERS => 'users',
                ],
                [
                    RelationParameters::VERSION => Version::VERSION,
                    RelationParameters::USER => 'user',
                    RelationParameters::DATABASE => 'db',
                    RelationParameters::BOOKMARK => 'bookmark',
                    RelationParameters::CENTRAL_COLUMNS => 'central_columns',
                    RelationParameters::COLUMN_INFO => 'column_info',
                    RelationParameters::DESIGNER_SETTINGS => 'designer_settings',
                    RelationParameters::EXPORT_TEMPLATES => 'export_templates',
                    RelationParameters::FAVORITE => 'favorite',
                    RelationParameters::HISTORY => 'history',
                    RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
                    RelationParameters::PDF_PAGES => 'pdf_pages',
                    RelationParameters::RECENT => 'recent',
                    RelationParameters::RELATION => 'relation',
                    RelationParameters::SAVED_SEARCHES => 'savedsearches',
                    RelationParameters::TABLE_COORDS => 'table_coords',
                    RelationParameters::TABLE_INFO => 'table_info',
                    RelationParameters::TABLE_UI_PREFS => 'table_uiprefs',
                    RelationParameters::TRACKING => 'tracking',
                    RelationParameters::USER_CONFIG => 'userconfig',
                    RelationParameters::USER_GROUPS => 'usergroups',
                    RelationParameters::USERS => 'users',
                    RelationParameters::BOOKMARK_WORK => true,
                    RelationParameters::MIME_WORK => true,
                    RelationParameters::CENTRAL_COLUMNS_WORK => true,
                    RelationParameters::COMM_WORK => true,
                    RelationParameters::MENUS_WORK => true,
                    RelationParameters::DESIGNER_SETTINGS_WORK => true,
                    RelationParameters::DISPLAY_WORK => true,
                    RelationParameters::EXPORT_TEMPLATES_WORK => true,
                    RelationParameters::FAVORITE_WORK => true,
                    RelationParameters::NAV_WORK => true,
                    RelationParameters::PDF_WORK => true,
                    RelationParameters::RECENT_WORK => true,
                    RelationParameters::REL_WORK => true,
                    RelationParameters::SAVED_SEARCHES_WORK => true,
                    RelationParameters::HISTORY_WORK => true,
                    RelationParameters::TRACKING_WORK => true,
                    RelationParameters::UI_PREFS_WORK => true,
                    RelationParameters::USER_CONFIG_WORK => true,
                    RelationParameters::ALL_WORKS => true,
                ],
            ],
            'valid values 2' => [
                [
                    RelationParameters::USER => 'user',
                    RelationParameters::DATABASE => 'db',
                    RelationParameters::COLUMN_INFO => 'column_info',
                    RelationParameters::RELATION => 'relation',
                    RelationParameters::TABLE_INFO => 'table_info',
                    RelationParameters::REL_WORK => false,
                    RelationParameters::DISPLAY_WORK => true,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MIME_WORK => true,
                ],
                [
                    RelationParameters::VERSION => Version::VERSION,
                    RelationParameters::USER => 'user',
                    RelationParameters::DATABASE => 'db',
                    RelationParameters::BOOKMARK => null,
                    RelationParameters::CENTRAL_COLUMNS => null,
                    RelationParameters::COLUMN_INFO => 'column_info',
                    RelationParameters::DESIGNER_SETTINGS => null,
                    RelationParameters::EXPORT_TEMPLATES => null,
                    RelationParameters::FAVORITE => null,
                    RelationParameters::HISTORY => null,
                    RelationParameters::NAVIGATION_HIDING => null,
                    RelationParameters::PDF_PAGES => null,
                    RelationParameters::RECENT => null,
                    RelationParameters::RELATION => 'relation',
                    RelationParameters::SAVED_SEARCHES => null,
                    RelationParameters::TABLE_COORDS => null,
                    RelationParameters::TABLE_INFO => 'table_info',
                    RelationParameters::TABLE_UI_PREFS => null,
                    RelationParameters::TRACKING => null,
                    RelationParameters::USER_CONFIG => null,
                    RelationParameters::USER_GROUPS => null,
                    RelationParameters::USERS => null,
                    RelationParameters::BOOKMARK_WORK => false,
                    RelationParameters::MIME_WORK => true,
                    RelationParameters::CENTRAL_COLUMNS_WORK => false,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MENUS_WORK => false,
                    RelationParameters::DESIGNER_SETTINGS_WORK => false,
                    RelationParameters::DISPLAY_WORK => true,
                    RelationParameters::EXPORT_TEMPLATES_WORK => false,
                    RelationParameters::FAVORITE_WORK => false,
                    RelationParameters::NAV_WORK => false,
                    RelationParameters::PDF_WORK => false,
                    RelationParameters::RECENT_WORK => false,
                    RelationParameters::REL_WORK => false,
                    RelationParameters::SAVED_SEARCHES_WORK => false,
                    RelationParameters::HISTORY_WORK => false,
                    RelationParameters::TRACKING_WORK => false,
                    RelationParameters::UI_PREFS_WORK => false,
                    RelationParameters::USER_CONFIG_WORK => false,
                    RelationParameters::ALL_WORKS => false,
                ],
            ],
            'invalid values' => [
                [
                    RelationParameters::REL_WORK => 1,
                    RelationParameters::DISPLAY_WORK => 1,
                    RelationParameters::BOOKMARK_WORK => 1,
                    RelationParameters::PDF_WORK => 1,
                    RelationParameters::COMM_WORK => 1,
                    RelationParameters::MIME_WORK => 1,
                    RelationParameters::HISTORY_WORK => 1,
                    RelationParameters::RECENT_WORK => 1,
                    RelationParameters::FAVORITE_WORK => 1,
                    RelationParameters::UI_PREFS_WORK => 1,
                    RelationParameters::TRACKING_WORK => 1,
                    RelationParameters::USER_CONFIG_WORK => 1,
                    RelationParameters::MENUS_WORK => 1,
                    RelationParameters::NAV_WORK => 1,
                    RelationParameters::SAVED_SEARCHES_WORK => 1,
                    RelationParameters::CENTRAL_COLUMNS_WORK => 1,
                    RelationParameters::DESIGNER_SETTINGS_WORK => 1,
                    RelationParameters::EXPORT_TEMPLATES_WORK => 1,
                    RelationParameters::ALL_WORKS => 1,
                    RelationParameters::USER => 1,
                    RelationParameters::DATABASE => 1,
                    RelationParameters::BOOKMARK => 1,
                    RelationParameters::CENTRAL_COLUMNS => 1,
                    RelationParameters::COLUMN_INFO => 1,
                    RelationParameters::DESIGNER_SETTINGS => 1,
                    RelationParameters::EXPORT_TEMPLATES => 1,
                    RelationParameters::FAVORITE => 1,
                    RelationParameters::HISTORY => 1,
                    RelationParameters::NAVIGATION_HIDING => 1,
                    RelationParameters::PDF_PAGES => 1,
                    RelationParameters::RECENT => 1,
                    RelationParameters::RELATION => 1,
                    RelationParameters::SAVED_SEARCHES => 1,
                    RelationParameters::TABLE_COORDS => 1,
                    RelationParameters::TABLE_INFO => 1,
                    RelationParameters::TABLE_UI_PREFS => 1,
                    RelationParameters::TRACKING => 1,
                    RelationParameters::USER_CONFIG => 1,
                    RelationParameters::USER_GROUPS => 1,
                    RelationParameters::USERS => 1,
                ],
                [
                    RelationParameters::VERSION => Version::VERSION,
                    RelationParameters::USER => null,
                    RelationParameters::DATABASE => null,
                    RelationParameters::BOOKMARK => null,
                    RelationParameters::CENTRAL_COLUMNS => null,
                    RelationParameters::COLUMN_INFO => null,
                    RelationParameters::DESIGNER_SETTINGS => null,
                    RelationParameters::EXPORT_TEMPLATES => null,
                    RelationParameters::FAVORITE => null,
                    RelationParameters::HISTORY => null,
                    RelationParameters::NAVIGATION_HIDING => null,
                    RelationParameters::PDF_PAGES => null,
                    RelationParameters::RECENT => null,
                    RelationParameters::RELATION => null,
                    RelationParameters::SAVED_SEARCHES => null,
                    RelationParameters::TABLE_COORDS => null,
                    RelationParameters::TABLE_INFO => null,
                    RelationParameters::TABLE_UI_PREFS => null,
                    RelationParameters::TRACKING => null,
                    RelationParameters::USER_CONFIG => null,
                    RelationParameters::USER_GROUPS => null,
                    RelationParameters::USERS => null,
                    RelationParameters::BOOKMARK_WORK => false,
                    RelationParameters::MIME_WORK => false,
                    RelationParameters::CENTRAL_COLUMNS_WORK => false,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MENUS_WORK => false,
                    RelationParameters::DESIGNER_SETTINGS_WORK => false,
                    RelationParameters::DISPLAY_WORK => false,
                    RelationParameters::EXPORT_TEMPLATES_WORK => false,
                    RelationParameters::FAVORITE_WORK => false,
                    RelationParameters::NAV_WORK => false,
                    RelationParameters::PDF_WORK => false,
                    RelationParameters::RECENT_WORK => false,
                    RelationParameters::REL_WORK => false,
                    RelationParameters::SAVED_SEARCHES_WORK => false,
                    RelationParameters::HISTORY_WORK => false,
                    RelationParameters::TRACKING_WORK => false,
                    RelationParameters::UI_PREFS_WORK => false,
                    RelationParameters::USER_CONFIG_WORK => false,
                    RelationParameters::ALL_WORKS => false,
                ],
            ],
            'invalid values 2' => [
                [
                    RelationParameters::USER => '',
                    RelationParameters::DATABASE => 'db',
                    RelationParameters::BOOKMARK => '',
                    RelationParameters::CENTRAL_COLUMNS => '',
                    RelationParameters::COLUMN_INFO => '',
                    RelationParameters::DESIGNER_SETTINGS => '',
                    RelationParameters::EXPORT_TEMPLATES => '',
                    RelationParameters::FAVORITE => '',
                    RelationParameters::HISTORY => '',
                    RelationParameters::NAVIGATION_HIDING => '',
                    RelationParameters::PDF_PAGES => '',
                    RelationParameters::RECENT => '',
                    RelationParameters::RELATION => '',
                    RelationParameters::SAVED_SEARCHES => '',
                    RelationParameters::TABLE_COORDS => '',
                    RelationParameters::TABLE_INFO => '',
                    RelationParameters::TABLE_UI_PREFS => '',
                    RelationParameters::TRACKING => '',
                    RelationParameters::USER_CONFIG => '',
                    RelationParameters::USER_GROUPS => '',
                    RelationParameters::USERS => '',
                ],
                [
                    RelationParameters::VERSION => Version::VERSION,
                    RelationParameters::USER => null,
                    RelationParameters::DATABASE => 'db',
                    RelationParameters::BOOKMARK => null,
                    RelationParameters::CENTRAL_COLUMNS => null,
                    RelationParameters::COLUMN_INFO => null,
                    RelationParameters::DESIGNER_SETTINGS => null,
                    RelationParameters::EXPORT_TEMPLATES => null,
                    RelationParameters::FAVORITE => null,
                    RelationParameters::HISTORY => null,
                    RelationParameters::NAVIGATION_HIDING => null,
                    RelationParameters::PDF_PAGES => null,
                    RelationParameters::RECENT => null,
                    RelationParameters::RELATION => null,
                    RelationParameters::SAVED_SEARCHES => null,
                    RelationParameters::TABLE_COORDS => null,
                    RelationParameters::TABLE_INFO => null,
                    RelationParameters::TABLE_UI_PREFS => null,
                    RelationParameters::TRACKING => null,
                    RelationParameters::USER_CONFIG => null,
                    RelationParameters::USER_GROUPS => null,
                    RelationParameters::USERS => null,
                    RelationParameters::BOOKMARK_WORK => false,
                    RelationParameters::MIME_WORK => false,
                    RelationParameters::CENTRAL_COLUMNS_WORK => false,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MENUS_WORK => false,
                    RelationParameters::DESIGNER_SETTINGS_WORK => false,
                    RelationParameters::DISPLAY_WORK => false,
                    RelationParameters::EXPORT_TEMPLATES_WORK => false,
                    RelationParameters::FAVORITE_WORK => false,
                    RelationParameters::NAV_WORK => false,
                    RelationParameters::PDF_WORK => false,
                    RelationParameters::RECENT_WORK => false,
                    RelationParameters::REL_WORK => false,
                    RelationParameters::SAVED_SEARCHES_WORK => false,
                    RelationParameters::HISTORY_WORK => false,
                    RelationParameters::TRACKING_WORK => false,
                    RelationParameters::UI_PREFS_WORK => false,
                    RelationParameters::USER_CONFIG_WORK => false,
                    RelationParameters::ALL_WORKS => false,
                ],
            ],
            'invalid values 3' => [
                [
                    RelationParameters::USER => '',
                    RelationParameters::DATABASE => 'db',
                    RelationParameters::BOOKMARK_WORK => true,
                    RelationParameters::BOOKMARK => ' invalid name ',
                ],
                [
                    RelationParameters::VERSION => Version::VERSION,
                    RelationParameters::USER => null,
                    RelationParameters::DATABASE => 'db',
                    RelationParameters::BOOKMARK => null,
                    RelationParameters::CENTRAL_COLUMNS => null,
                    RelationParameters::COLUMN_INFO => null,
                    RelationParameters::DESIGNER_SETTINGS => null,
                    RelationParameters::EXPORT_TEMPLATES => null,
                    RelationParameters::FAVORITE => null,
                    RelationParameters::HISTORY => null,
                    RelationParameters::NAVIGATION_HIDING => null,
                    RelationParameters::PDF_PAGES => null,
                    RelationParameters::RECENT => null,
                    RelationParameters::RELATION => null,
                    RelationParameters::SAVED_SEARCHES => null,
                    RelationParameters::TABLE_COORDS => null,
                    RelationParameters::TABLE_INFO => null,
                    RelationParameters::TABLE_UI_PREFS => null,
                    RelationParameters::TRACKING => null,
                    RelationParameters::USER_CONFIG => null,
                    RelationParameters::USER_GROUPS => null,
                    RelationParameters::USERS => null,
                    RelationParameters::BOOKMARK_WORK => false,
                    RelationParameters::MIME_WORK => false,
                    RelationParameters::CENTRAL_COLUMNS_WORK => false,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MENUS_WORK => false,
                    RelationParameters::DESIGNER_SETTINGS_WORK => false,
                    RelationParameters::DISPLAY_WORK => false,
                    RelationParameters::EXPORT_TEMPLATES_WORK => false,
                    RelationParameters::FAVORITE_WORK => false,
                    RelationParameters::NAV_WORK => false,
                    RelationParameters::PDF_WORK => false,
                    RelationParameters::RECENT_WORK => false,
                    RelationParameters::REL_WORK => false,
                    RelationParameters::SAVED_SEARCHES_WORK => false,
                    RelationParameters::HISTORY_WORK => false,
                    RelationParameters::TRACKING_WORK => false,
                    RelationParameters::UI_PREFS_WORK => false,
                    RelationParameters::USER_CONFIG_WORK => false,
                    RelationParameters::ALL_WORKS => false,
                ],
            ],
            'invalid values 4' => [
                [RelationParameters::USER => '', RelationParameters::DATABASE => ''],
                [
                    RelationParameters::VERSION => Version::VERSION,
                    RelationParameters::USER => null,
                    RelationParameters::DATABASE => null,
                    RelationParameters::BOOKMARK => null,
                    RelationParameters::CENTRAL_COLUMNS => null,
                    RelationParameters::COLUMN_INFO => null,
                    RelationParameters::DESIGNER_SETTINGS => null,
                    RelationParameters::EXPORT_TEMPLATES => null,
                    RelationParameters::FAVORITE => null,
                    RelationParameters::HISTORY => null,
                    RelationParameters::NAVIGATION_HIDING => null,
                    RelationParameters::PDF_PAGES => null,
                    RelationParameters::RECENT => null,
                    RelationParameters::RELATION => null,
                    RelationParameters::SAVED_SEARCHES => null,
                    RelationParameters::TABLE_COORDS => null,
                    RelationParameters::TABLE_INFO => null,
                    RelationParameters::TABLE_UI_PREFS => null,
                    RelationParameters::TRACKING => null,
                    RelationParameters::USER_CONFIG => null,
                    RelationParameters::USER_GROUPS => null,
                    RelationParameters::USERS => null,
                    RelationParameters::BOOKMARK_WORK => false,
                    RelationParameters::MIME_WORK => false,
                    RelationParameters::CENTRAL_COLUMNS_WORK => false,
                    RelationParameters::COMM_WORK => false,
                    RelationParameters::MENUS_WORK => false,
                    RelationParameters::DESIGNER_SETTINGS_WORK => false,
                    RelationParameters::DISPLAY_WORK => false,
                    RelationParameters::EXPORT_TEMPLATES_WORK => false,
                    RelationParameters::FAVORITE_WORK => false,
                    RelationParameters::NAV_WORK => false,
                    RelationParameters::PDF_WORK => false,
                    RelationParameters::RECENT_WORK => false,
                    RelationParameters::REL_WORK => false,
                    RelationParameters::SAVED_SEARCHES_WORK => false,
                    RelationParameters::HISTORY_WORK => false,
                    RelationParameters::TRACKING_WORK => false,
                    RelationParameters::UI_PREFS_WORK => false,
                    RelationParameters::USER_CONFIG_WORK => false,
                    RelationParameters::ALL_WORKS => false,
                ],
            ],
        ];
    }
}
