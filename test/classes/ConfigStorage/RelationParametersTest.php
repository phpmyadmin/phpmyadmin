<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Version;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\ConfigStorage\RelationParameters
 * @covers \PhpMyAdmin\ConfigStorage\Features\BookmarkFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\BrowserTransformationFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\CentralColumnsFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\ColumnCommentsFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\ConfigurableMenusFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\DatabaseDesignerSettingsFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\DisplayFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\ExportTemplatesFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\FavoriteTablesFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\NavigationItemsHidingFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\PdfFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\RecentlyUsedTablesFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\RelationFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\SavedQueryByExampleSearchesFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\SqlHistoryFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\TrackingFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\UiPreferencesFeature
 * @covers \PhpMyAdmin\ConfigStorage\Features\UserPreferencesFeature
 */
class RelationParametersTest extends TestCase
{
    public function testFeaturesWithTwoTables(): void
    {
        $this->assertNull(RelationParameters::fromArray([
            'db' => 'db',
            'pdf_pages' => 'pdf_pages',
            'table_coords' => ' invalid ',
            'pdfwork' => true,
        ])->pdfFeature);
        $this->assertNull(RelationParameters::fromArray([
            'db' => 'db',
            'pdf_pages' => ' invalid ',
            'table_coords' => 'table_coords',
            'pdfwork' => true,
        ])->pdfFeature);
        $this->assertNull(RelationParameters::fromArray([
            'db' => 'db',
            'relation' => 'relation',
            'table_info' => ' invalid ',
            'displaywork' => true,
        ])->displayFeature);
        $this->assertNull(RelationParameters::fromArray([
            'db' => 'db',
            'relation' => ' invalid ',
            'table_info' => 'table_info',
            'displaywork' => true,
        ])->displayFeature);
        $this->assertNull(RelationParameters::fromArray([
            'db' => 'db',
            'usergroups' => 'usergroups',
            'users' => ' invalid ',
            'menuwork' => true,
        ])->configurableMenusFeature);
        $this->assertNull(RelationParameters::fromArray([
            'db' => 'db',
            'usergroups' => ' invalid ',
            'users' => 'users',
            'menuswork' => true,
        ])->configurableMenusFeature);
    }

    public function testFeaturesWithSharedTable(): void
    {
        $relationParameters = RelationParameters::fromArray([
            'db' => 'db',
            'column_info' => 'column_info',
            'relation' => 'relation',
            'table_info' => 'table_info',
            'mimework' => true,
            'commwork' => true,
            'displaywork' => true,
            'relwork' => true,
        ]);
        $this->assertNotNull($relationParameters->browserTransformationFeature);
        $this->assertNotNull($relationParameters->columnCommentsFeature);
        $this->assertNotNull($relationParameters->displayFeature);
        $this->assertNotNull($relationParameters->relationFeature);
        $this->assertSame(
            $relationParameters->browserTransformationFeature->columnInfo,
            $relationParameters->columnCommentsFeature->columnInfo,
        );
        $this->assertSame(
            $relationParameters->relationFeature->relation,
            $relationParameters->displayFeature->relation,
        );

        $relationParameters = RelationParameters::fromArray([
            'db' => 'db',
            'column_info' => 'column_info',
            'relation' => 'relation',
            'table_info' => 'table_info',
            'mimework' => false,
            'commwork' => true,
            'displaywork' => true,
            'relwork' => false,
        ]);
        $this->assertNull($relationParameters->browserTransformationFeature);
        $this->assertNotNull($relationParameters->columnCommentsFeature);
        $this->assertNotNull($relationParameters->displayFeature);
        $this->assertNull($relationParameters->relationFeature);
    }

    public function testFeaturesHaveSameDatabase(): void
    {
        $relationParameters = RelationParameters::fromArray([
            'db' => 'db',
            'bookmark' => 'bookmark',
            'central_columns' => 'central_columns',
            'column_info' => 'column_info',
            'designer_settings' => 'designer_settings',
            'export_templates' => 'export_templates',
            'favorite' => 'favorite',
            'history' => 'history',
            'navigationhiding' => 'navigationhiding',
            'pdf_pages' => 'pdf_pages',
            'recent' => 'recent',
            'relation' => 'relation',
            'savedsearches' => 'savedsearches',
            'table_coords' => 'table_coords',
            'table_info' => 'table_info',
            'table_uiprefs' => 'table_uiprefs',
            'tracking' => 'tracking',
            'userconfig' => 'userconfig',
            'usergroups' => 'usergroups',
            'users' => 'users',
            'bookmarkwork' => true,
            'mimework' => true,
            'centralcolumnswork' => true,
            'commwork' => true,
            'menuswork' => true,
            'designersettingswork' => true,
            'displaywork' => true,
            'exporttemplateswork' => true,
            'favoritework' => true,
            'navwork' => true,
            'pdfwork' => true,
            'recentwork' => true,
            'relwork' => true,
            'savedsearcheswork' => true,
            'historywork' => true,
            'trackingwork' => true,
            'uiprefswork' => true,
            'userconfigwork' => true,
        ]);
        $this->assertInstanceOf(DatabaseName::class, $relationParameters->db);
        $this->assertEquals('db', $relationParameters->db->getName());
        $this->assertNotNull($relationParameters->bookmarkFeature);
        $this->assertSame($relationParameters->db, $relationParameters->bookmarkFeature->database);
        $this->assertNotNull($relationParameters->browserTransformationFeature);
        $this->assertSame($relationParameters->db, $relationParameters->browserTransformationFeature->database);
        $this->assertNotNull($relationParameters->centralColumnsFeature);
        $this->assertSame($relationParameters->db, $relationParameters->centralColumnsFeature->database);
        $this->assertNotNull($relationParameters->columnCommentsFeature);
        $this->assertSame($relationParameters->db, $relationParameters->columnCommentsFeature->database);
        $this->assertNotNull($relationParameters->configurableMenusFeature);
        $this->assertSame($relationParameters->db, $relationParameters->configurableMenusFeature->database);
        $this->assertNotNull($relationParameters->databaseDesignerSettingsFeature);
        $this->assertSame($relationParameters->db, $relationParameters->databaseDesignerSettingsFeature->database);
        $this->assertNotNull($relationParameters->displayFeature);
        $this->assertSame($relationParameters->db, $relationParameters->displayFeature->database);
        $this->assertNotNull($relationParameters->exportTemplatesFeature);
        $this->assertSame($relationParameters->db, $relationParameters->exportTemplatesFeature->database);
        $this->assertNotNull($relationParameters->favoriteTablesFeature);
        $this->assertSame($relationParameters->db, $relationParameters->favoriteTablesFeature->database);
        $this->assertNotNull($relationParameters->navigationItemsHidingFeature);
        $this->assertSame($relationParameters->db, $relationParameters->navigationItemsHidingFeature->database);
        $this->assertNotNull($relationParameters->pdfFeature);
        $this->assertSame($relationParameters->db, $relationParameters->pdfFeature->database);
        $this->assertNotNull($relationParameters->recentlyUsedTablesFeature);
        $this->assertSame($relationParameters->db, $relationParameters->recentlyUsedTablesFeature->database);
        $this->assertNotNull($relationParameters->relationFeature);
        $this->assertSame($relationParameters->db, $relationParameters->relationFeature->database);
        $this->assertNotNull($relationParameters->savedQueryByExampleSearchesFeature);
        $this->assertSame($relationParameters->db, $relationParameters->savedQueryByExampleSearchesFeature->database);
        $this->assertNotNull($relationParameters->sqlHistoryFeature);
        $this->assertSame($relationParameters->db, $relationParameters->sqlHistoryFeature->database);
        $this->assertNotNull($relationParameters->trackingFeature);
        $this->assertSame($relationParameters->db, $relationParameters->trackingFeature->database);
        $this->assertNotNull($relationParameters->uiPreferencesFeature);
        $this->assertSame($relationParameters->db, $relationParameters->uiPreferencesFeature->database);
        $this->assertNotNull($relationParameters->userPreferencesFeature);
        $this->assertSame($relationParameters->db, $relationParameters->userPreferencesFeature->database);
    }

    public function testHasAllFeatures(): void
    {
        $params = [
            'db' => 'db',
            'bookmark' => 'bookmark',
            'central_columns' => 'central_columns',
            'column_info' => 'column_info',
            'designer_settings' => 'designer_settings',
            'export_templates' => 'export_templates',
            'favorite' => 'favorite',
            'history' => 'history',
            'navigationhiding' => 'navigationhiding',
            'pdf_pages' => 'pdf_pages',
            'recent' => 'recent',
            'relation' => 'relation',
            'savedsearches' => 'savedsearches',
            'table_coords' => 'table_coords',
            'table_info' => 'table_info',
            'table_uiprefs' => 'table_uiprefs',
            'tracking' => 'tracking',
            'userconfig' => 'userconfig',
            'usergroups' => 'usergroups',
            'users' => 'users',
            'bookmarkwork' => true,
            'mimework' => true,
            'centralcolumnswork' => true,
            'commwork' => true,
            'menuswork' => true,
            'designersettingswork' => true,
            'displaywork' => true,
            'exporttemplateswork' => true,
            'favoritework' => true,
            'navwork' => true,
            'pdfwork' => true,
            'recentwork' => true,
            'relwork' => true,
            'savedsearcheswork' => true,
            'historywork' => true,
            'trackingwork' => true,
            'uiprefswork' => true,
            'userconfigwork' => true,
        ];
        $this->assertFalse(RelationParameters::fromArray([])->hasAllFeatures());
        $this->assertTrue(RelationParameters::fromArray($params)->hasAllFeatures());
        $params['bookmarkwork'] = false;
        $this->assertFalse(RelationParameters::fromArray($params)->hasAllFeatures());
    }

    /**
     * @param mixed[] $params
     * @param mixed[] $expected
     *
     * @dataProvider providerForTestToArray
     */
    public function testToArray(array $params, array $expected): void
    {
        $this->assertSame($expected, RelationParameters::fromArray($params)->toArray());
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function providerForTestToArray(): array
    {
        return [
            'default values' => [
                [],
                [
                    'version' => Version::VERSION,
                    'user' => null,
                    'db' => null,
                    'bookmark' => null,
                    'central_columns' => null,
                    'column_info' => null,
                    'designer_settings' => null,
                    'export_templates' => null,
                    'favorite' => null,
                    'history' => null,
                    'navigationhiding' => null,
                    'pdf_pages' => null,
                    'recent' => null,
                    'relation' => null,
                    'savedsearches' => null,
                    'table_coords' => null,
                    'table_info' => null,
                    'table_uiprefs' => null,
                    'tracking' => null,
                    'userconfig' => null,
                    'usergroups' => null,
                    'users' => null,
                    'bookmarkwork' => false,
                    'mimework' => false,
                    'centralcolumnswork' => false,
                    'commwork' => false,
                    'menuswork' => false,
                    'designersettingswork' => false,
                    'displaywork' => false,
                    'exporttemplateswork' => false,
                    'favoritework' => false,
                    'navwork' => false,
                    'pdfwork' => false,
                    'recentwork' => false,
                    'relwork' => false,
                    'savedsearcheswork' => false,
                    'historywork' => false,
                    'trackingwork' => false,
                    'uiprefswork' => false,
                    'userconfigwork' => false,
                    'allworks' => false,
                ],
            ],
            'default values 2' => [
                [
                    'relwork' => false,
                    'displaywork' => false,
                    'bookmarkwork' => false,
                    'pdfwork' => false,
                    'commwork' => false,
                    'mimework' => false,
                    'historywork' => false,
                    'recentwork' => false,
                    'favoritework' => false,
                    'uiprefswork' => false,
                    'trackingwork' => false,
                    'userconfigwork' => false,
                    'menuswork' => false,
                    'navwork' => false,
                    'savedsearcheswork' => false,
                    'centralcolumnswork' => false,
                    'designersettingswork' => false,
                    'exporttemplateswork' => false,
                    'allworks' => false,
                    'user' => null,
                    'db' => null,
                    'bookmark' => null,
                    'central_columns' => null,
                    'column_info' => null,
                    'designer_settings' => null,
                    'export_templates' => null,
                    'favorite' => null,
                    'history' => null,
                    'navigationhiding' => null,
                    'pdf_pages' => null,
                    'recent' => null,
                    'relation' => null,
                    'savedsearches' => null,
                    'table_coords' => null,
                    'table_info' => null,
                    'table_uiprefs' => null,
                    'tracking' => null,
                    'userconfig' => null,
                    'usergroups' => null,
                    'users' => null,
                ],
                [
                    'version' => Version::VERSION,
                    'user' => null,
                    'db' => null,
                    'bookmark' => null,
                    'central_columns' => null,
                    'column_info' => null,
                    'designer_settings' => null,
                    'export_templates' => null,
                    'favorite' => null,
                    'history' => null,
                    'navigationhiding' => null,
                    'pdf_pages' => null,
                    'recent' => null,
                    'relation' => null,
                    'savedsearches' => null,
                    'table_coords' => null,
                    'table_info' => null,
                    'table_uiprefs' => null,
                    'tracking' => null,
                    'userconfig' => null,
                    'usergroups' => null,
                    'users' => null,
                    'bookmarkwork' => false,
                    'mimework' => false,
                    'centralcolumnswork' => false,
                    'commwork' => false,
                    'menuswork' => false,
                    'designersettingswork' => false,
                    'displaywork' => false,
                    'exporttemplateswork' => false,
                    'favoritework' => false,
                    'navwork' => false,
                    'pdfwork' => false,
                    'recentwork' => false,
                    'relwork' => false,
                    'savedsearcheswork' => false,
                    'historywork' => false,
                    'trackingwork' => false,
                    'uiprefswork' => false,
                    'userconfigwork' => false,
                    'allworks' => false,
                ],
            ],
            'valid values' => [
                [
                    'relwork' => true,
                    'displaywork' => true,
                    'bookmarkwork' => true,
                    'pdfwork' => true,
                    'commwork' => true,
                    'mimework' => true,
                    'historywork' => true,
                    'recentwork' => true,
                    'favoritework' => true,
                    'uiprefswork' => true,
                    'trackingwork' => true,
                    'userconfigwork' => true,
                    'menuswork' => true,
                    'navwork' => true,
                    'savedsearcheswork' => true,
                    'centralcolumnswork' => true,
                    'designersettingswork' => true,
                    'exporttemplateswork' => true,
                    'allworks' => true,
                    'user' => 'user',
                    'db' => 'db',
                    'bookmark' => 'bookmark',
                    'central_columns' => 'central_columns',
                    'column_info' => 'column_info',
                    'designer_settings' => 'designer_settings',
                    'export_templates' => 'export_templates',
                    'favorite' => 'favorite',
                    'history' => 'history',
                    'navigationhiding' => 'navigationhiding',
                    'pdf_pages' => 'pdf_pages',
                    'recent' => 'recent',
                    'relation' => 'relation',
                    'savedsearches' => 'savedsearches',
                    'table_coords' => 'table_coords',
                    'table_info' => 'table_info',
                    'table_uiprefs' => 'table_uiprefs',
                    'tracking' => 'tracking',
                    'userconfig' => 'userconfig',
                    'usergroups' => 'usergroups',
                    'users' => 'users',
                ],
                [
                    'version' => Version::VERSION,
                    'user' => 'user',
                    'db' => 'db',
                    'bookmark' => 'bookmark',
                    'central_columns' => 'central_columns',
                    'column_info' => 'column_info',
                    'designer_settings' => 'designer_settings',
                    'export_templates' => 'export_templates',
                    'favorite' => 'favorite',
                    'history' => 'history',
                    'navigationhiding' => 'navigationhiding',
                    'pdf_pages' => 'pdf_pages',
                    'recent' => 'recent',
                    'relation' => 'relation',
                    'savedsearches' => 'savedsearches',
                    'table_coords' => 'table_coords',
                    'table_info' => 'table_info',
                    'table_uiprefs' => 'table_uiprefs',
                    'tracking' => 'tracking',
                    'userconfig' => 'userconfig',
                    'usergroups' => 'usergroups',
                    'users' => 'users',
                    'bookmarkwork' => true,
                    'mimework' => true,
                    'centralcolumnswork' => true,
                    'commwork' => true,
                    'menuswork' => true,
                    'designersettingswork' => true,
                    'displaywork' => true,
                    'exporttemplateswork' => true,
                    'favoritework' => true,
                    'navwork' => true,
                    'pdfwork' => true,
                    'recentwork' => true,
                    'relwork' => true,
                    'savedsearcheswork' => true,
                    'historywork' => true,
                    'trackingwork' => true,
                    'uiprefswork' => true,
                    'userconfigwork' => true,
                    'allworks' => true,
                ],
            ],
            'valid values 2' => [
                [
                    'user' => 'user',
                    'db' => 'db',
                    'column_info' => 'column_info',
                    'relation' => 'relation',
                    'table_info' => 'table_info',
                    'relwork' => false,
                    'displaywork' => true,
                    'commwork' => false,
                    'mimework' => true,
                ],
                [
                    'version' => Version::VERSION,
                    'user' => 'user',
                    'db' => 'db',
                    'bookmark' => null,
                    'central_columns' => null,
                    'column_info' => 'column_info',
                    'designer_settings' => null,
                    'export_templates' => null,
                    'favorite' => null,
                    'history' => null,
                    'navigationhiding' => null,
                    'pdf_pages' => null,
                    'recent' => null,
                    'relation' => 'relation',
                    'savedsearches' => null,
                    'table_coords' => null,
                    'table_info' => 'table_info',
                    'table_uiprefs' => null,
                    'tracking' => null,
                    'userconfig' => null,
                    'usergroups' => null,
                    'users' => null,
                    'bookmarkwork' => false,
                    'mimework' => true,
                    'centralcolumnswork' => false,
                    'commwork' => false,
                    'menuswork' => false,
                    'designersettingswork' => false,
                    'displaywork' => true,
                    'exporttemplateswork' => false,
                    'favoritework' => false,
                    'navwork' => false,
                    'pdfwork' => false,
                    'recentwork' => false,
                    'relwork' => false,
                    'savedsearcheswork' => false,
                    'historywork' => false,
                    'trackingwork' => false,
                    'uiprefswork' => false,
                    'userconfigwork' => false,
                    'allworks' => false,
                ],
            ],
            'invalid values' => [
                [
                    'relwork' => 1,
                    'displaywork' => 1,
                    'bookmarkwork' => 1,
                    'pdfwork' => 1,
                    'commwork' => 1,
                    'mimework' => 1,
                    'historywork' => 1,
                    'recentwork' => 1,
                    'favoritework' => 1,
                    'uiprefswork' => 1,
                    'trackingwork' => 1,
                    'userconfigwork' => 1,
                    'menuswork' => 1,
                    'navwork' => 1,
                    'savedsearcheswork' => 1,
                    'centralcolumnswork' => 1,
                    'designersettingswork' => 1,
                    'exporttemplateswork' => 1,
                    'allworks' => 1,
                    'user' => 1,
                    'db' => 1,
                    'bookmark' => 1,
                    'central_columns' => 1,
                    'column_info' => 1,
                    'designer_settings' => 1,
                    'export_templates' => 1,
                    'favorite' => 1,
                    'history' => 1,
                    'navigationhiding' => 1,
                    'pdf_pages' => 1,
                    'recent' => 1,
                    'relation' => 1,
                    'savedsearches' => 1,
                    'table_coords' => 1,
                    'table_info' => 1,
                    'table_uiprefs' => 1,
                    'tracking' => 1,
                    'userconfig' => 1,
                    'usergroups' => 1,
                    'users' => 1,
                ],
                [
                    'version' => Version::VERSION,
                    'user' => null,
                    'db' => null,
                    'bookmark' => null,
                    'central_columns' => null,
                    'column_info' => null,
                    'designer_settings' => null,
                    'export_templates' => null,
                    'favorite' => null,
                    'history' => null,
                    'navigationhiding' => null,
                    'pdf_pages' => null,
                    'recent' => null,
                    'relation' => null,
                    'savedsearches' => null,
                    'table_coords' => null,
                    'table_info' => null,
                    'table_uiprefs' => null,
                    'tracking' => null,
                    'userconfig' => null,
                    'usergroups' => null,
                    'users' => null,
                    'bookmarkwork' => false,
                    'mimework' => false,
                    'centralcolumnswork' => false,
                    'commwork' => false,
                    'menuswork' => false,
                    'designersettingswork' => false,
                    'displaywork' => false,
                    'exporttemplateswork' => false,
                    'favoritework' => false,
                    'navwork' => false,
                    'pdfwork' => false,
                    'recentwork' => false,
                    'relwork' => false,
                    'savedsearcheswork' => false,
                    'historywork' => false,
                    'trackingwork' => false,
                    'uiprefswork' => false,
                    'userconfigwork' => false,
                    'allworks' => false,
                ],
            ],
            'invalid values 2' => [
                [
                    'user' => '',
                    'db' => 'db',
                    'bookmark' => '',
                    'central_columns' => '',
                    'column_info' => '',
                    'designer_settings' => '',
                    'export_templates' => '',
                    'favorite' => '',
                    'history' => '',
                    'navigationhiding' => '',
                    'pdf_pages' => '',
                    'recent' => '',
                    'relation' => '',
                    'savedsearches' => '',
                    'table_coords' => '',
                    'table_info' => '',
                    'table_uiprefs' => '',
                    'tracking' => '',
                    'userconfig' => '',
                    'usergroups' => '',
                    'users' => '',
                ],
                [
                    'version' => Version::VERSION,
                    'user' => null,
                    'db' => 'db',
                    'bookmark' => null,
                    'central_columns' => null,
                    'column_info' => null,
                    'designer_settings' => null,
                    'export_templates' => null,
                    'favorite' => null,
                    'history' => null,
                    'navigationhiding' => null,
                    'pdf_pages' => null,
                    'recent' => null,
                    'relation' => null,
                    'savedsearches' => null,
                    'table_coords' => null,
                    'table_info' => null,
                    'table_uiprefs' => null,
                    'tracking' => null,
                    'userconfig' => null,
                    'usergroups' => null,
                    'users' => null,
                    'bookmarkwork' => false,
                    'mimework' => false,
                    'centralcolumnswork' => false,
                    'commwork' => false,
                    'menuswork' => false,
                    'designersettingswork' => false,
                    'displaywork' => false,
                    'exporttemplateswork' => false,
                    'favoritework' => false,
                    'navwork' => false,
                    'pdfwork' => false,
                    'recentwork' => false,
                    'relwork' => false,
                    'savedsearcheswork' => false,
                    'historywork' => false,
                    'trackingwork' => false,
                    'uiprefswork' => false,
                    'userconfigwork' => false,
                    'allworks' => false,
                ],
            ],
            'invalid values 3' => [
                ['user' => '', 'db' => 'db', 'bookmarkwork' => true, 'bookmark' => ' invalid name '],
                [
                    'version' => Version::VERSION,
                    'user' => null,
                    'db' => 'db',
                    'bookmark' => null,
                    'central_columns' => null,
                    'column_info' => null,
                    'designer_settings' => null,
                    'export_templates' => null,
                    'favorite' => null,
                    'history' => null,
                    'navigationhiding' => null,
                    'pdf_pages' => null,
                    'recent' => null,
                    'relation' => null,
                    'savedsearches' => null,
                    'table_coords' => null,
                    'table_info' => null,
                    'table_uiprefs' => null,
                    'tracking' => null,
                    'userconfig' => null,
                    'usergroups' => null,
                    'users' => null,
                    'bookmarkwork' => false,
                    'mimework' => false,
                    'centralcolumnswork' => false,
                    'commwork' => false,
                    'menuswork' => false,
                    'designersettingswork' => false,
                    'displaywork' => false,
                    'exporttemplateswork' => false,
                    'favoritework' => false,
                    'navwork' => false,
                    'pdfwork' => false,
                    'recentwork' => false,
                    'relwork' => false,
                    'savedsearcheswork' => false,
                    'historywork' => false,
                    'trackingwork' => false,
                    'uiprefswork' => false,
                    'userconfigwork' => false,
                    'allworks' => false,
                ],
            ],
            'invalid values 4' => [
                ['user' => '', 'db' => ''],
                [
                    'version' => Version::VERSION,
                    'user' => null,
                    'db' => null,
                    'bookmark' => null,
                    'central_columns' => null,
                    'column_info' => null,
                    'designer_settings' => null,
                    'export_templates' => null,
                    'favorite' => null,
                    'history' => null,
                    'navigationhiding' => null,
                    'pdf_pages' => null,
                    'recent' => null,
                    'relation' => null,
                    'savedsearches' => null,
                    'table_coords' => null,
                    'table_info' => null,
                    'table_uiprefs' => null,
                    'tracking' => null,
                    'userconfig' => null,
                    'usergroups' => null,
                    'users' => null,
                    'bookmarkwork' => false,
                    'mimework' => false,
                    'centralcolumnswork' => false,
                    'commwork' => false,
                    'menuswork' => false,
                    'designersettingswork' => false,
                    'displaywork' => false,
                    'exporttemplateswork' => false,
                    'favoritework' => false,
                    'navwork' => false,
                    'pdfwork' => false,
                    'recentwork' => false,
                    'relwork' => false,
                    'savedsearcheswork' => false,
                    'historywork' => false,
                    'trackingwork' => false,
                    'uiprefswork' => false,
                    'userconfigwork' => false,
                    'allworks' => false,
                ],
            ],
        ];
    }
}
