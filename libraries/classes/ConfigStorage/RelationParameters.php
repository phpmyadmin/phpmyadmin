<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

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
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\InvalidDatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Version;

use function is_string;

/** @psalm-immutable */
final class RelationParameters
{
    /** @param non-empty-string|null $user */
    public function __construct(
        public readonly string|null $user,
        public readonly DatabaseName|null $db = null,
        public readonly BookmarkFeature|null $bookmarkFeature = null,
        public readonly BrowserTransformationFeature|null $browserTransformationFeature = null,
        public readonly CentralColumnsFeature|null $centralColumnsFeature = null,
        public readonly ColumnCommentsFeature|null $columnCommentsFeature = null,
        public readonly ConfigurableMenusFeature|null $configurableMenusFeature = null,
        public readonly DatabaseDesignerSettingsFeature|null $databaseDesignerSettingsFeature = null,
        public readonly DisplayFeature|null $displayFeature = null,
        public readonly ExportTemplatesFeature|null $exportTemplatesFeature = null,
        public readonly FavoriteTablesFeature|null $favoriteTablesFeature = null,
        public readonly NavigationItemsHidingFeature|null $navigationItemsHidingFeature = null,
        public readonly PdfFeature|null $pdfFeature = null,
        public readonly RecentlyUsedTablesFeature|null $recentlyUsedTablesFeature = null,
        public readonly RelationFeature|null $relationFeature = null,
        public readonly SavedQueryByExampleSearchesFeature|null $savedQueryByExampleSearchesFeature = null,
        public readonly SqlHistoryFeature|null $sqlHistoryFeature = null,
        public readonly TrackingFeature|null $trackingFeature = null,
        public readonly UiPreferencesFeature|null $uiPreferencesFeature = null,
        public readonly UserPreferencesFeature|null $userPreferencesFeature = null,
    ) {
    }

    /** @param mixed[] $params */
    public static function fromArray(array $params): self
    {
        $user = null;
        if (isset($params['user']) && is_string($params['user']) && $params['user'] !== '') {
            $user = $params['user'];
        }

        try {
            $db = DatabaseName::fromValue($params['db'] ?? null);
        } catch (InvalidDatabaseName) {
            return new self($user, null);
        }

        $bookmarkFeature = null;
        if (isset($params['bookmarkwork'], $params['bookmark']) && $params['bookmarkwork']) {
            $bookmark = TableName::tryFromValue($params['bookmark']);
            if ($bookmark !== null) {
                $bookmarkFeature = new BookmarkFeature($db, $bookmark);
            }
        }

        $columnInfo = TableName::tryFromValue($params['column_info'] ?? null);
        $browserTransformationFeature = null;
        if (isset($params['mimework']) && $params['mimework'] && $columnInfo !== null) {
            $browserTransformationFeature = new BrowserTransformationFeature($db, $columnInfo);
        }

        $columnCommentsFeature = null;
        if (isset($params['commwork']) && $params['commwork'] && $columnInfo !== null) {
            $columnCommentsFeature = new ColumnCommentsFeature($db, $columnInfo);
        }

        $centralColumnsFeature = null;
        if (isset($params['centralcolumnswork'], $params['central_columns']) && $params['centralcolumnswork']) {
            $centralColumns = TableName::tryFromValue($params['central_columns']);
            if ($centralColumns !== null) {
                $centralColumnsFeature = new CentralColumnsFeature($db, $centralColumns);
            }
        }

        $configurableMenusFeature = null;
        if (isset($params['menuswork'], $params['usergroups'], $params['users']) && $params['menuswork']) {
            $userGroups = TableName::tryFromValue($params['usergroups']);
            $users = TableName::tryFromValue($params['users']);
            if ($userGroups !== null && $users !== null) {
                $configurableMenusFeature = new ConfigurableMenusFeature($db, $userGroups, $users);
            }
        }

        $databaseDesignerSettingsFeature = null;
        if (isset($params['designersettingswork'], $params['designer_settings']) && $params['designersettingswork']) {
            $designerSettings = TableName::tryFromValue($params['designer_settings']);
            if ($designerSettings !== null) {
                $databaseDesignerSettingsFeature = new DatabaseDesignerSettingsFeature($db, $designerSettings);
            }
        }

        $relation = TableName::tryFromValue($params['relation'] ?? null);
        $displayFeature = null;
        if (isset($params['displaywork'], $params['table_info']) && $params['displaywork'] && $relation !== null) {
            $tableInfo = TableName::tryFromValue($params['table_info']);
            if ($tableInfo !== null) {
                $displayFeature = new DisplayFeature($db, $relation, $tableInfo);
            }
        }

        $exportTemplatesFeature = null;
        if (isset($params['exporttemplateswork'], $params['export_templates']) && $params['exporttemplateswork']) {
            $exportTemplates = TableName::tryFromValue($params['export_templates']);
            if ($exportTemplates !== null) {
                $exportTemplatesFeature = new ExportTemplatesFeature($db, $exportTemplates);
            }
        }

        $favoriteTablesFeature = null;
        if (isset($params['favoritework'], $params['favorite']) && $params['favoritework']) {
            $favorite = TableName::tryFromValue($params['favorite']);
            if ($favorite !== null) {
                $favoriteTablesFeature = new FavoriteTablesFeature($db, $favorite);
            }
        }

        $navigationItemsHidingFeature = null;
        if (isset($params['navwork'], $params['navigationhiding']) && $params['navwork']) {
            $navigationHiding = TableName::tryFromValue($params['navigationhiding']);
            if ($navigationHiding !== null) {
                $navigationItemsHidingFeature = new NavigationItemsHidingFeature($db, $navigationHiding);
            }
        }

        $pdfFeature = null;
        if (isset($params['pdfwork'], $params['pdf_pages'], $params['table_coords']) && $params['pdfwork']) {
            $pdfPages = TableName::tryFromValue($params['pdf_pages']);
            $tableCoords = TableName::tryFromValue($params['table_coords']);
            if ($pdfPages !== null && $tableCoords !== null) {
                $pdfFeature = new PdfFeature($db, $pdfPages, $tableCoords);
            }
        }

        $recentlyUsedTablesFeature = null;
        if (isset($params['recentwork'], $params['recent']) && $params['recentwork']) {
            $recent = TableName::tryFromValue($params['recent']);
            if ($recent !== null) {
                $recentlyUsedTablesFeature = new RecentlyUsedTablesFeature($db, $recent);
            }
        }

        $relationFeature = null;
        if (isset($params['relwork']) && $params['relwork'] && $relation !== null) {
            $relationFeature = new RelationFeature($db, $relation);
        }

        $savedQueryByExampleSearchesFeature = null;
        if (isset($params['savedsearcheswork'], $params['savedsearches']) && $params['savedsearcheswork']) {
            $savedSearches = TableName::tryFromValue($params['savedsearches']);
            if ($savedSearches !== null) {
                $savedQueryByExampleSearchesFeature = new SavedQueryByExampleSearchesFeature($db, $savedSearches);
            }
        }

        $sqlHistoryFeature = null;
        if (isset($params['historywork'], $params['history']) && $params['historywork']) {
            $history = TableName::tryFromValue($params['history']);
            if ($history !== null) {
                $sqlHistoryFeature = new SqlHistoryFeature($db, $history);
            }
        }

        $trackingFeature = null;
        if (isset($params['trackingwork'], $params['tracking']) && $params['trackingwork']) {
            $tracking = TableName::tryFromValue($params['tracking']);
            if ($tracking !== null) {
                $trackingFeature = new TrackingFeature($db, $tracking);
            }
        }

        $uiPreferencesFeature = null;
        if (isset($params['uiprefswork'], $params['table_uiprefs']) && $params['uiprefswork']) {
            $tableUiPrefs = TableName::tryFromValue($params['table_uiprefs']);
            if ($tableUiPrefs !== null) {
                $uiPreferencesFeature = new UiPreferencesFeature($db, $tableUiPrefs);
            }
        }

        $userPreferencesFeature = null;
        if (isset($params['userconfigwork'], $params['userconfig']) && $params['userconfigwork']) {
            $userConfig = TableName::tryFromValue($params['userconfig']);
            if ($userConfig !== null) {
                $userPreferencesFeature = new UserPreferencesFeature($db, $userConfig);
            }
        }

        return new self(
            $user,
            $db,
            $bookmarkFeature,
            $browserTransformationFeature,
            $centralColumnsFeature,
            $columnCommentsFeature,
            $configurableMenusFeature,
            $databaseDesignerSettingsFeature,
            $displayFeature,
            $exportTemplatesFeature,
            $favoriteTablesFeature,
            $navigationItemsHidingFeature,
            $pdfFeature,
            $recentlyUsedTablesFeature,
            $relationFeature,
            $savedQueryByExampleSearchesFeature,
            $sqlHistoryFeature,
            $trackingFeature,
            $uiPreferencesFeature,
            $userPreferencesFeature,
        );
    }

    /**
     * @return array<string, bool|string|null>
     * @psalm-return array{
     *   version: string,
     *   user: (string|null),
     *   db: (string|null),
     *   bookmark: (string|null),
     *   central_columns: (string|null),
     *   column_info: (string|null),
     *   designer_settings: (string|null),
     *   export_templates: (string|null),
     *   favorite: (string|null),
     *   history: (string|null),
     *   navigationhiding: (string|null),
     *   pdf_pages: (string|null),
     *   recent: (string|null),
     *   relation: (string|null),
     *   savedsearches: (string|null),
     *   table_coords: (string|null),
     *   table_info: (string|null),
     *   table_uiprefs: (string|null),
     *   tracking: (string|null),
     *   userconfig: (string|null),
     *   usergroups: (string|null),
     *   users: (string|null),
     *   bookmarkwork: bool,
     *   mimework: bool,
     *   centralcolumnswork: bool,
     *   commwork: bool,
     *   menuswork: bool,
     *   designersettingswork: bool,
     *   displaywork: bool,
     *   exporttemplateswork: bool,
     *   favoritework: bool,
     *   navwork: bool,
     *   pdfwork: bool,
     *   recentwork: bool,
     *   relwork: bool,
     *   savedsearcheswork: bool,
     *   historywork: bool,
     *   trackingwork: bool,
     *   uiprefswork: bool,
     *   userconfigwork: bool,
     *   allworks: bool
     * }
     */
    public function toArray(): array
    {
        $columnInfo = null;
        if ($this->columnCommentsFeature !== null) {
            $columnInfo = $this->columnCommentsFeature->columnInfo->getName();
        } elseif ($this->browserTransformationFeature !== null) {
            $columnInfo = $this->browserTransformationFeature->columnInfo->getName();
        }

        $relation = null;
        if ($this->relationFeature !== null) {
            $relation = $this->relationFeature->relation->getName();
        } elseif ($this->displayFeature !== null) {
            $relation = $this->displayFeature->relation->getName();
        }

        return [
            'version' => Version::VERSION,
            'user' => $this->user,
            'db' => $this->db?->getName(),
            'bookmark' => $this->bookmarkFeature?->bookmark->getName(),
            'central_columns' => $this->centralColumnsFeature?->centralColumns->getName(),
            'column_info' => $columnInfo,
            'designer_settings' => $this->databaseDesignerSettingsFeature?->designerSettings->getName(),
            'export_templates' => $this->exportTemplatesFeature?->exportTemplates->getName(),
            'favorite' => $this->favoriteTablesFeature?->favorite->getName(),
            'history' => $this->sqlHistoryFeature?->history->getName(),
            'navigationhiding' => $this->navigationItemsHidingFeature?->navigationHiding->getName(),
            'pdf_pages' => $this->pdfFeature?->pdfPages->getName(),
            'recent' => $this->recentlyUsedTablesFeature?->recent->getName(),
            'relation' => $relation,
            'savedsearches' => $this->savedQueryByExampleSearchesFeature?->savedSearches->getName(),
            'table_coords' => $this->pdfFeature?->tableCoords->getName(),
            'table_info' => $this->displayFeature?->tableInfo->getName(),
            'table_uiprefs' => $this->uiPreferencesFeature?->tableUiPrefs->getName(),
            'tracking' => $this->trackingFeature?->tracking->getName(),
            'userconfig' => $this->userPreferencesFeature?->userConfig->getName(),
            'usergroups' => $this->configurableMenusFeature?->userGroups->getName(),
            'users' => $this->configurableMenusFeature?->users->getName(),
            'bookmarkwork' => $this->bookmarkFeature !== null,
            'mimework' => $this->browserTransformationFeature !== null,
            'centralcolumnswork' => $this->centralColumnsFeature !== null,
            'commwork' => $this->columnCommentsFeature !== null,
            'menuswork' => $this->configurableMenusFeature !== null,
            'designersettingswork' => $this->databaseDesignerSettingsFeature !== null,
            'displaywork' => $this->displayFeature !== null,
            'exporttemplateswork' => $this->exportTemplatesFeature !== null,
            'favoritework' => $this->favoriteTablesFeature !== null,
            'navwork' => $this->navigationItemsHidingFeature !== null,
            'pdfwork' => $this->pdfFeature !== null,
            'recentwork' => $this->recentlyUsedTablesFeature !== null,
            'relwork' => $this->relationFeature !== null,
            'savedsearcheswork' => $this->savedQueryByExampleSearchesFeature !== null,
            'historywork' => $this->sqlHistoryFeature !== null,
            'trackingwork' => $this->trackingFeature !== null,
            'uiprefswork' => $this->uiPreferencesFeature !== null,
            'userconfigwork' => $this->userPreferencesFeature !== null,
            'allworks' => $this->hasAllFeatures(),
        ];
    }

    public function hasAllFeatures(): bool
    {
        return $this->bookmarkFeature !== null
            && $this->browserTransformationFeature !== null
            && $this->centralColumnsFeature !== null
            && $this->columnCommentsFeature !== null
            && $this->configurableMenusFeature !== null
            && $this->databaseDesignerSettingsFeature !== null
            && $this->displayFeature !== null
            && $this->exportTemplatesFeature !== null
            && $this->favoriteTablesFeature !== null
            && $this->navigationItemsHidingFeature !== null
            && $this->pdfFeature !== null
            && $this->recentlyUsedTablesFeature !== null
            && $this->relationFeature !== null
            && $this->savedQueryByExampleSearchesFeature !== null
            && $this->sqlHistoryFeature !== null
            && $this->trackingFeature !== null
            && $this->uiPreferencesFeature !== null
            && $this->userPreferencesFeature !== null;
    }
}
