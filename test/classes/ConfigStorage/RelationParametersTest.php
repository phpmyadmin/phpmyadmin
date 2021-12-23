<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Version;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\ConfigStorage\RelationParameters
 */
class RelationParametersTest extends TestCase
{
    /**
     * @param array $params
     * @param array $expected
     *
     * @dataProvider providerForTestRelationParameters
     */
    public function testRelationParameters(array $params, array $expected): void
    {
        $relationParameters = RelationParameters::fromArray($params);
        $relationParametersArray = $relationParameters->toArray();
        $this->assertSame(Version::VERSION, $relationParametersArray['version']);
        $this->assertSame($expected['relwork'], $relationParameters->hasRelationFeature());
        $this->assertSame($expected['relwork'], $relationParametersArray['relwork']);
        $this->assertSame($expected['displaywork'], $relationParameters->hasDisplayFeature());
        $this->assertSame($expected['displaywork'], $relationParametersArray['displaywork']);
        $this->assertSame($expected['bookmarkwork'], $relationParameters->hasBookmarkFeature());
        $this->assertSame($expected['bookmarkwork'], $relationParametersArray['bookmarkwork']);
        $this->assertSame($expected['pdfwork'], $relationParameters->hasPdfFeature());
        $this->assertSame($expected['pdfwork'], $relationParametersArray['pdfwork']);
        $this->assertSame($expected['commwork'], $relationParameters->hasColumnCommentsFeature());
        $this->assertSame($expected['commwork'], $relationParametersArray['commwork']);
        $this->assertSame($expected['mimework'], $relationParameters->hasBrowserTransformationFeature());
        $this->assertSame($expected['mimework'], $relationParametersArray['mimework']);
        $this->assertSame($expected['historywork'], $relationParameters->hasSqlHistoryFeature());
        $this->assertSame($expected['historywork'], $relationParametersArray['historywork']);
        $this->assertSame($expected['recentwork'], $relationParameters->hasRecentlyUsedTablesFeature());
        $this->assertSame($expected['recentwork'], $relationParametersArray['recentwork']);
        $this->assertSame($expected['favoritework'], $relationParameters->hasFavoriteTablesFeature());
        $this->assertSame($expected['favoritework'], $relationParametersArray['favoritework']);
        $this->assertSame($expected['uiprefswork'], $relationParameters->hasUiPreferencesFeature());
        $this->assertSame($expected['uiprefswork'], $relationParametersArray['uiprefswork']);
        $this->assertSame($expected['trackingwork'], $relationParameters->hasTrackingFeature());
        $this->assertSame($expected['trackingwork'], $relationParametersArray['trackingwork']);
        $this->assertSame($expected['userconfigwork'], $relationParameters->hasUserPreferencesFeature());
        $this->assertSame($expected['userconfigwork'], $relationParametersArray['userconfigwork']);
        $this->assertSame($expected['menuswork'], $relationParameters->hasConfigurableMenusFeature());
        $this->assertSame($expected['menuswork'], $relationParametersArray['menuswork']);
        $this->assertSame($expected['navwork'], $relationParameters->hasNavigationItemsHidingFeature());
        $this->assertSame($expected['navwork'], $relationParametersArray['navwork']);
        $this->assertSame($expected['savedsearcheswork'], $relationParameters->hasSavedQueryByExampleSearchesFeature());
        $this->assertSame($expected['savedsearcheswork'], $relationParametersArray['savedsearcheswork']);
        $this->assertSame($expected['centralcolumnswork'], $relationParameters->hasCentralColumnsFeature());
        $this->assertSame($expected['centralcolumnswork'], $relationParametersArray['centralcolumnswork']);
        $this->assertSame($expected['designersettingswork'], $relationParameters->hasDatabaseDesignerSettingsFeature());
        $this->assertSame($expected['designersettingswork'], $relationParametersArray['designersettingswork']);
        $this->assertSame($expected['exporttemplateswork'], $relationParameters->hasExportTemplatesFeature());
        $this->assertSame($expected['exporttemplateswork'], $relationParametersArray['exporttemplateswork']);
        $this->assertSame($expected['allworks'], $relationParameters->hasAllFeatures());
        $this->assertSame($expected['allworks'], $relationParametersArray['allworks']);
        $this->assertSame($expected['user'], $relationParameters->user);
        $this->assertSame($expected['user'], $relationParametersArray['user']);
        $this->assertSame(
            $expected['db'],
            $relationParameters->db instanceof DatabaseName ? $relationParameters->db->getName() : null
        );
        $this->assertSame($expected['db'], $relationParametersArray['db']);
        $this->assertSame(
            $expected['bookmark'],
            $relationParameters->bookmark instanceof TableName ? $relationParameters->bookmark->getName() : null
        );
        $this->assertSame($expected['bookmark'], $relationParametersArray['bookmark']);
        $this->assertSame(
            $expected['central_columns'],
            $relationParameters->centralColumns instanceof TableName
                ? $relationParameters->centralColumns->getName()
                : null
        );
        $this->assertSame($expected['central_columns'], $relationParametersArray['central_columns']);
        $this->assertSame(
            $expected['column_info'],
            $relationParameters->columnInfo instanceof TableName ? $relationParameters->columnInfo->getName() : null
        );
        $this->assertSame($expected['column_info'], $relationParametersArray['column_info']);
        $this->assertSame(
            $expected['designer_settings'],
            $relationParameters->designerSettings instanceof TableName
                ? $relationParameters->designerSettings->getName()
                : null
        );
        $this->assertSame($expected['designer_settings'], $relationParametersArray['designer_settings']);
        $this->assertSame(
            $expected['export_templates'],
            $relationParameters->exportTemplates instanceof TableName
                ? $relationParameters->exportTemplates->getName()
                : null
        );
        $this->assertSame($expected['export_templates'], $relationParametersArray['export_templates']);
        $this->assertSame(
            $expected['favorite'],
            $relationParameters->favorite instanceof TableName ? $relationParameters->favorite->getName() : null
        );
        $this->assertSame($expected['favorite'], $relationParametersArray['favorite']);
        $this->assertSame(
            $expected['history'],
            $relationParameters->history instanceof TableName ? $relationParameters->history->getName() : null
        );
        $this->assertSame($expected['history'], $relationParametersArray['history']);
        $this->assertSame(
            $expected['navigationhiding'],
            $relationParameters->navigationhiding instanceof TableName
                ? $relationParameters->navigationhiding->getName()
                : null
        );
        $this->assertSame($expected['navigationhiding'], $relationParametersArray['navigationhiding']);
        $this->assertSame(
            $expected['pdf_pages'],
            $relationParameters->pdfPages instanceof TableName ? $relationParameters->pdfPages->getName() : null
        );
        $this->assertSame($expected['pdf_pages'], $relationParametersArray['pdf_pages']);
        $this->assertSame(
            $expected['recent'],
            $relationParameters->recent instanceof TableName ? $relationParameters->recent->getName() : null
        );
        $this->assertSame($expected['recent'], $relationParametersArray['recent']);
        $this->assertSame(
            $expected['relation'],
            $relationParameters->relation instanceof TableName ? $relationParameters->relation->getName() : null
        );
        $this->assertSame($expected['relation'], $relationParametersArray['relation']);
        $this->assertSame(
            $expected['savedsearches'],
            $relationParameters->savedsearches instanceof TableName
                ? $relationParameters->savedsearches->getName()
                : null
        );
        $this->assertSame($expected['savedsearches'], $relationParametersArray['savedsearches']);
        $this->assertSame(
            $expected['table_coords'],
            $relationParameters->tableCoords instanceof TableName ? $relationParameters->tableCoords->getName() : null
        );
        $this->assertSame($expected['table_coords'], $relationParametersArray['table_coords']);
        $this->assertSame(
            $expected['table_info'],
            $relationParameters->tableInfo instanceof TableName ? $relationParameters->tableInfo->getName() : null
        );
        $this->assertSame($expected['table_info'], $relationParametersArray['table_info']);
        $this->assertSame(
            $expected['table_uiprefs'],
            $relationParameters->tableUiprefs instanceof TableName ? $relationParameters->tableUiprefs->getName() : null
        );
        $this->assertSame($expected['table_uiprefs'], $relationParametersArray['table_uiprefs']);
        $this->assertSame(
            $expected['tracking'],
            $relationParameters->tracking instanceof TableName ? $relationParameters->tracking->getName() : null
        );
        $this->assertSame($expected['tracking'], $relationParametersArray['tracking']);
        $this->assertSame(
            $expected['userconfig'],
            $relationParameters->userconfig instanceof TableName ? $relationParameters->userconfig->getName() : null
        );
        $this->assertSame($expected['userconfig'], $relationParametersArray['userconfig']);
        $this->assertSame(
            $expected['usergroups'],
            $relationParameters->usergroups instanceof TableName ? $relationParameters->usergroups->getName() : null
        );
        $this->assertSame($expected['usergroups'], $relationParametersArray['usergroups']);
        $this->assertSame(
            $expected['users'],
            $relationParameters->users instanceof TableName ? $relationParameters->users->getName() : null
        );
        $this->assertSame($expected['users'], $relationParametersArray['users']);
    }

    /**
     * @return array[][]
     */
    public function providerForTestRelationParameters(): array
    {
        return [
            'default values' => [
                [],
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
            ],
            'invalid values 2' => [
                [
                    'user' => '',
                    'db' => '',
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
            ],
        ];
    }
}
