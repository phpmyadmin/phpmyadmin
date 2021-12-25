<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseName;
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
        $this->assertSame($expected['relwork'], $relationParameters->relationFeature !== null);
        $this->assertSame($expected['relwork'], $relationParametersArray['relwork']);
        $this->assertSame($expected['displaywork'], $relationParameters->displayFeature !== null);
        $this->assertSame($expected['displaywork'], $relationParametersArray['displaywork']);
        $this->assertSame($expected['bookmarkwork'], $relationParametersArray['bookmarkwork']);
        $this->assertSame($expected['pdfwork'], $relationParameters->pdfFeature !== null);
        $this->assertSame($expected['pdfwork'], $relationParametersArray['pdfwork']);
        $this->assertSame($expected['commwork'], $relationParameters->columnCommentsFeature !== null);
        $this->assertSame($expected['commwork'], $relationParametersArray['commwork']);
        $this->assertSame($expected['mimework'], $relationParameters->browserTransformationFeature !== null);
        $this->assertSame($expected['mimework'], $relationParametersArray['mimework']);
        $this->assertSame($expected['historywork'], $relationParameters->sqlHistoryFeature !== null);
        $this->assertSame($expected['historywork'], $relationParametersArray['historywork']);
        $this->assertSame($expected['recentwork'], $relationParameters->recentlyUsedTablesFeature !== null);
        $this->assertSame($expected['recentwork'], $relationParametersArray['recentwork']);
        $this->assertSame($expected['favoritework'], $relationParameters->favoriteTablesFeature !== null);
        $this->assertSame($expected['favoritework'], $relationParametersArray['favoritework']);
        $this->assertSame($expected['uiprefswork'], $relationParameters->uiPreferencesFeature !== null);
        $this->assertSame($expected['uiprefswork'], $relationParametersArray['uiprefswork']);
        $this->assertSame($expected['trackingwork'], $relationParameters->trackingFeature !== null);
        $this->assertSame($expected['trackingwork'], $relationParametersArray['trackingwork']);
        $this->assertSame($expected['userconfigwork'], $relationParameters->userPreferencesFeature !== null);
        $this->assertSame($expected['userconfigwork'], $relationParametersArray['userconfigwork']);
        $this->assertSame($expected['menuswork'], $relationParameters->configurableMenusFeature !== null);
        $this->assertSame($expected['menuswork'], $relationParametersArray['menuswork']);
        $this->assertSame($expected['navwork'], $relationParameters->navigationItemsHidingFeature !== null);
        $this->assertSame($expected['navwork'], $relationParametersArray['navwork']);
        $this->assertSame(
            $expected['savedsearcheswork'],
            $relationParameters->savedQueryByExampleSearchesFeature !== null
        );
        $this->assertSame($expected['savedsearcheswork'], $relationParametersArray['savedsearcheswork']);
        $this->assertSame($expected['centralcolumnswork'], $relationParameters->centralColumnsFeature !== null);
        $this->assertSame($expected['centralcolumnswork'], $relationParametersArray['centralcolumnswork']);
        $this->assertSame(
            $expected['designersettingswork'],
            $relationParameters->databaseDesignerSettingsFeature !== null
        );
        $this->assertSame($expected['designersettingswork'], $relationParametersArray['designersettingswork']);
        $this->assertSame($expected['exporttemplateswork'], $relationParameters->exportTemplatesFeature !== null);
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
        $this->assertSame($expected['bookmark'], $relationParametersArray['bookmark']);
        $this->assertSame($expected['central_columns'], $relationParametersArray['central_columns']);
        $this->assertSame($expected['column_info'], $relationParametersArray['column_info']);
        $this->assertSame($expected['designer_settings'], $relationParametersArray['designer_settings']);
        $this->assertSame($expected['export_templates'], $relationParametersArray['export_templates']);
        $this->assertSame($expected['favorite'], $relationParametersArray['favorite']);
        $this->assertSame($expected['history'], $relationParametersArray['history']);
        $this->assertSame($expected['navigationhiding'], $relationParametersArray['navigationhiding']);
        $this->assertSame($expected['pdf_pages'], $relationParametersArray['pdf_pages']);
        $this->assertSame($expected['recent'], $relationParametersArray['recent']);
        $this->assertSame($expected['relation'], $relationParametersArray['relation']);
        $this->assertSame($expected['savedsearches'], $relationParametersArray['savedsearches']);
        $this->assertSame($expected['table_coords'], $relationParametersArray['table_coords']);
        $this->assertSame($expected['table_info'], $relationParametersArray['table_info']);
        $this->assertSame($expected['table_uiprefs'], $relationParametersArray['table_uiprefs']);
        $this->assertSame($expected['tracking'], $relationParametersArray['tracking']);
        $this->assertSame($expected['userconfig'], $relationParametersArray['userconfig']);
        $this->assertSame($expected['usergroups'], $relationParametersArray['usergroups']);
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
