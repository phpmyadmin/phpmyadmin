<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Version;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function is_bool;
use function is_string;

/**
 * @psalm-immutable
 */
final class RelationParameters
{
    /**
     * @var string
     * @psalm-var non-empty-string
     */
    public $version;
    /** @var bool */
    public $relwork;
    /** @var bool */
    public $displaywork;
    /** @var bool */
    public $bookmarkwork;
    /** @var bool */
    public $pdfwork;
    /** @var bool */
    public $commwork;
    /** @var bool */
    public $mimework;
    /** @var bool */
    public $historywork;
    /** @var bool */
    public $recentwork;
    /** @var bool */
    public $favoritework;
    /** @var bool */
    public $uiprefswork;
    /** @var bool */
    public $trackingwork;
    /** @var bool */
    public $userconfigwork;
    /** @var bool */
    public $menuswork;
    /** @var bool */
    public $navwork;
    /** @var bool */
    public $savedsearcheswork;
    /** @var bool */
    public $centralcolumnswork;
    /** @var bool */
    public $designersettingswork;
    /** @var bool */
    public $exporttemplateswork;
    /** @var bool */
    public $allworks;
    /** @var string|null */
    public $user;
    /** @var DatabaseName|null */
    public $db;
    /** @var TableName|null */
    public $bookmark;
    /** @var TableName|null */
    public $centralColumns;
    /** @var TableName|null */
    public $columnInfo;
    /** @var TableName|null */
    public $designerSettings;
    /** @var TableName|null */
    public $exportTemplates;
    /** @var TableName|null */
    public $favorite;
    /** @var TableName|null */
    public $history;
    /** @var TableName|null */
    public $navigationhiding;
    /** @var TableName|null */
    public $pdfPages;
    /** @var TableName|null */
    public $recent;
    /** @var TableName|null */
    public $relation;
    /** @var TableName|null */
    public $savedsearches;
    /** @var TableName|null */
    public $tableCoords;
    /** @var TableName|null */
    public $tableInfo;
    /** @var TableName|null */
    public $tableUiprefs;
    /** @var TableName|null */
    public $tracking;
    /** @var TableName|null */
    public $userconfig;
    /** @var TableName|null */
    public $usergroups;
    /** @var TableName|null */
    public $users;

    /**
     * @psalm-param non-empty-string $version
     */
    public function __construct(
        string $version,
        bool $relwork,
        bool $displaywork,
        bool $bookmarkwork,
        bool $pdfwork,
        bool $commwork,
        bool $mimework,
        bool $historywork,
        bool $recentwork,
        bool $favoritework,
        bool $uiprefswork,
        bool $trackingwork,
        bool $userconfigwork,
        bool $menuswork,
        bool $navwork,
        bool $savedsearcheswork,
        bool $centralcolumnswork,
        bool $designersettingswork,
        bool $exporttemplateswork,
        bool $allworks,
        ?string $user,
        ?DatabaseName $db,
        ?TableName $bookmark,
        ?TableName $centralColumns,
        ?TableName $columnInfo,
        ?TableName $designerSettings,
        ?TableName $exportTemplates,
        ?TableName $favorite,
        ?TableName $history,
        ?TableName $navigationhiding,
        ?TableName $pdfPages,
        ?TableName $recent,
        ?TableName $relation,
        ?TableName $savedsearches,
        ?TableName $tableCoords,
        ?TableName $tableInfo,
        ?TableName $tableUiprefs,
        ?TableName $tracking,
        ?TableName $userconfig,
        ?TableName $usergroups,
        ?TableName $users
    ) {
        $this->version = $version;
        $this->relwork = $relwork;
        $this->displaywork = $displaywork;
        $this->bookmarkwork = $bookmarkwork;
        $this->pdfwork = $pdfwork;
        $this->commwork = $commwork;
        $this->mimework = $mimework;
        $this->historywork = $historywork;
        $this->recentwork = $recentwork;
        $this->favoritework = $favoritework;
        $this->uiprefswork = $uiprefswork;
        $this->trackingwork = $trackingwork;
        $this->userconfigwork = $userconfigwork;
        $this->menuswork = $menuswork;
        $this->navwork = $navwork;
        $this->savedsearcheswork = $savedsearcheswork;
        $this->centralcolumnswork = $centralcolumnswork;
        $this->designersettingswork = $designersettingswork;
        $this->exporttemplateswork = $exporttemplateswork;
        $this->allworks = $allworks;
        $this->user = $user;
        $this->db = $db;
        $this->bookmark = $bookmark;
        $this->centralColumns = $centralColumns;
        $this->columnInfo = $columnInfo;
        $this->designerSettings = $designerSettings;
        $this->exportTemplates = $exportTemplates;
        $this->favorite = $favorite;
        $this->history = $history;
        $this->navigationhiding = $navigationhiding;
        $this->pdfPages = $pdfPages;
        $this->recent = $recent;
        $this->relation = $relation;
        $this->savedsearches = $savedsearches;
        $this->tableCoords = $tableCoords;
        $this->tableInfo = $tableInfo;
        $this->tableUiprefs = $tableUiprefs;
        $this->tracking = $tracking;
        $this->userconfig = $userconfig;
        $this->usergroups = $usergroups;
        $this->users = $users;
    }

    /**
     * @param mixed[] $params
     */
    public static function fromArray(array $params): self
    {
        $version = Version::VERSION;
        if (isset($params['version']) && is_string($params['version']) && $params['version'] !== '') {
            $version = $params['version'];
        }

        $user = null;
        if (isset($params['user']) && is_string($params['user']) && $params['user'] !== '') {
            $user = $params['user'];
        }

        try {
            Assert::keyExists($params, 'db');
            $db = DatabaseName::fromValue($params['db']);
        } catch (InvalidArgumentException $exception) {
            $db = null;
        }

        $relwork = false;
        if (isset($params['relwork']) && is_bool($params['relwork'])) {
            $relwork = $params['relwork'];
        }

        $displaywork = false;
        if (isset($params['displaywork']) && is_bool($params['displaywork'])) {
            $displaywork = $params['displaywork'];
        }

        $bookmarkwork = false;
        if (isset($params['bookmarkwork']) && is_bool($params['bookmarkwork'])) {
            $bookmarkwork = $params['bookmarkwork'];
        }

        $pdfwork = false;
        if (isset($params['pdfwork']) && is_bool($params['pdfwork'])) {
            $pdfwork = $params['pdfwork'];
        }

        $commwork = false;
        if (isset($params['commwork']) && is_bool($params['commwork'])) {
            $commwork = $params['commwork'];
        }

        $mimework = false;
        if (isset($params['mimework']) && is_bool($params['mimework'])) {
            $mimework = $params['mimework'];
        }

        $historywork = false;
        if (isset($params['historywork']) && is_bool($params['historywork'])) {
            $historywork = $params['historywork'];
        }

        $recentwork = false;
        if (isset($params['recentwork']) && is_bool($params['recentwork'])) {
            $recentwork = $params['recentwork'];
        }

        $favoritework = false;
        if (isset($params['favoritework']) && is_bool($params['favoritework'])) {
            $favoritework = $params['favoritework'];
        }

        $uiprefswork = false;
        if (isset($params['uiprefswork']) && is_bool($params['uiprefswork'])) {
            $uiprefswork = $params['uiprefswork'];
        }

        $trackingwork = false;
        if (isset($params['trackingwork']) && is_bool($params['trackingwork'])) {
            $trackingwork = $params['trackingwork'];
        }

        $userconfigwork = false;
        if (isset($params['userconfigwork']) && is_bool($params['userconfigwork'])) {
            $userconfigwork = $params['userconfigwork'];
        }

        $menuswork = false;
        if (isset($params['menuswork']) && is_bool($params['menuswork'])) {
            $menuswork = $params['menuswork'];
        }

        $navwork = false;
        if (isset($params['navwork']) && is_bool($params['navwork'])) {
            $navwork = $params['navwork'];
        }

        $savedsearcheswork = false;
        if (isset($params['savedsearcheswork']) && is_bool($params['savedsearcheswork'])) {
            $savedsearcheswork = $params['savedsearcheswork'];
        }

        $centralcolumnswork = false;
        if (isset($params['centralcolumnswork']) && is_bool($params['centralcolumnswork'])) {
            $centralcolumnswork = $params['centralcolumnswork'];
        }

        $designersettingswork = false;
        if (isset($params['designersettingswork']) && is_bool($params['designersettingswork'])) {
            $designersettingswork = $params['designersettingswork'];
        }

        $exporttemplateswork = false;
        if (isset($params['exporttemplateswork']) && is_bool($params['exporttemplateswork'])) {
            $exporttemplateswork = $params['exporttemplateswork'];
        }

        $allworks = false;
        if (isset($params['allworks']) && is_bool($params['allworks'])) {
            $allworks = $params['allworks'];
        }

        $bookmark = self::getTableName($params, 'bookmark');
        $centralColumns = self::getTableName($params, 'central_columns');
        $columnInfo = self::getTableName($params, 'column_info');
        $designerSettings = self::getTableName($params, 'designer_settings');
        $exportTemplates = self::getTableName($params, 'export_templates');
        $favorite = self::getTableName($params, 'favorite');
        $history = self::getTableName($params, 'history');
        $navigationHiding = self::getTableName($params, 'navigationhiding');
        $pdfPages = self::getTableName($params, 'pdf_pages');
        $recent = self::getTableName($params, 'recent');
        $relation = self::getTableName($params, 'relation');
        $savedSearches = self::getTableName($params, 'savedsearches');
        $tableCoords = self::getTableName($params, 'table_coords');
        $tableInfo = self::getTableName($params, 'table_info');
        $tableUiPrefs = self::getTableName($params, 'table_uiprefs');
        $tracking = self::getTableName($params, 'tracking');
        $userConfig = self::getTableName($params, 'userconfig');
        $userGroups = self::getTableName($params, 'usergroups');
        $users = self::getTableName($params, 'users');

        return new self(
            $version,
            $relwork,
            $displaywork,
            $bookmarkwork,
            $pdfwork,
            $commwork,
            $mimework,
            $historywork,
            $recentwork,
            $favoritework,
            $uiprefswork,
            $trackingwork,
            $userconfigwork,
            $menuswork,
            $navwork,
            $savedsearcheswork,
            $centralcolumnswork,
            $designersettingswork,
            $exporttemplateswork,
            $allworks,
            $user,
            $db,
            $bookmark,
            $centralColumns,
            $columnInfo,
            $designerSettings,
            $exportTemplates,
            $favorite,
            $history,
            $navigationHiding,
            $pdfPages,
            $recent,
            $relation,
            $savedSearches,
            $tableCoords,
            $tableInfo,
            $tableUiPrefs,
            $tracking,
            $userConfig,
            $userGroups,
            $users
        );
    }

    /**
     * @return array<string, bool|string|null>
     * @psalm-return array{
     *   version: string,
     *   relwork: bool,
     *   displaywork: bool,
     *   bookmarkwork: bool,
     *   pdfwork: bool,
     *   commwork: bool,
     *   mimework: bool,
     *   historywork: bool,
     *   recentwork: bool,
     *   favoritework: bool,
     *   uiprefswork: bool,
     *   trackingwork: bool,
     *   userconfigwork: bool,
     *   menuswork: bool,
     *   navwork: bool,
     *   savedsearcheswork: bool,
     *   centralcolumnswork: bool,
     *   designersettingswork: bool,
     *   exporttemplateswork: bool,
     *   allworks: bool,
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
     *   users: (string|null)
     * }
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'relwork' => $this->hasRelationFeature(),
            'displaywork' => $this->hasDisplayFeature(),
            'bookmarkwork' => $this->hasBookmarkFeature(),
            'pdfwork' => $this->hasPdfFeature(),
            'commwork' => $this->hasColumnCommentsFeature(),
            'mimework' => $this->hasBrowserTransformationFeature(),
            'historywork' => $this->hasSqlHistoryFeature(),
            'recentwork' => $this->hasRecentlyUsedTablesFeature(),
            'favoritework' => $this->hasFavoriteTablesFeature(),
            'uiprefswork' => $this->hasUiPreferencesFeature(),
            'trackingwork' => $this->hasTrackingFeature(),
            'userconfigwork' => $this->hasUserPreferencesFeature(),
            'menuswork' => $this->hasConfigurableMenusFeature(),
            'navwork' => $this->hasNavigationItemsHidingFeature(),
            'savedsearcheswork' => $this->hasSavedQueryByExampleSearchesFeature(),
            'centralcolumnswork' => $this->hasCentralColumnsFeature(),
            'designersettingswork' => $this->hasDatabaseDesignerSettingsFeature(),
            'exporttemplateswork' => $this->hasExportTemplatesFeature(),
            'allworks' => $this->hasAllFeatures(),
            'user' => $this->user,
            'db' => $this->db !== null ? $this->db->getName() : null,
            'bookmark' => $this->bookmark !== null ? $this->bookmark->getName() : null,
            'central_columns' => $this->centralColumns !== null ? $this->centralColumns->getName() : null,
            'column_info' => $this->columnInfo !== null ? $this->columnInfo->getName() : null,
            'designer_settings' => $this->designerSettings !== null ? $this->designerSettings->getName() : null,
            'export_templates' => $this->exportTemplates !== null ? $this->exportTemplates->getName() : null,
            'favorite' => $this->favorite !== null ? $this->favorite->getName() : null,
            'history' => $this->history !== null ? $this->history->getName() : null,
            'navigationhiding' => $this->navigationhiding !== null ? $this->navigationhiding->getName() : null,
            'pdf_pages' => $this->pdfPages !== null ? $this->pdfPages->getName() : null,
            'recent' => $this->recent !== null ? $this->recent->getName() : null,
            'relation' => $this->relation !== null ? $this->relation->getName() : null,
            'savedsearches' => $this->savedsearches !== null ? $this->savedsearches->getName() : null,
            'table_coords' => $this->tableCoords !== null ? $this->tableCoords->getName() : null,
            'table_info' => $this->tableInfo !== null ? $this->tableInfo->getName() : null,
            'table_uiprefs' => $this->tableUiprefs !== null ? $this->tableUiprefs->getName() : null,
            'tracking' => $this->tracking !== null ? $this->tracking->getName() : null,
            'userconfig' => $this->userconfig !== null ? $this->userconfig->getName() : null,
            'usergroups' => $this->usergroups !== null ? $this->usergroups->getName() : null,
            'users' => $this->users !== null ? $this->users->getName() : null,
        ];
    }

    public function hasRelationFeature(): bool
    {
        return $this->relwork;
    }

    public function hasDisplayFeature(): bool
    {
        return $this->displaywork;
    }

    public function hasBookmarkFeature(): bool
    {
        return $this->bookmarkwork;
    }

    public function hasPdfFeature(): bool
    {
        return $this->pdfwork;
    }

    public function hasColumnCommentsFeature(): bool
    {
        return $this->commwork;
    }

    public function hasBrowserTransformationFeature(): bool
    {
        return $this->mimework;
    }

    public function hasSqlHistoryFeature(): bool
    {
        return $this->historywork;
    }

    public function hasRecentlyUsedTablesFeature(): bool
    {
        return $this->recentwork;
    }

    public function hasFavoriteTablesFeature(): bool
    {
        return $this->favoritework;
    }

    public function hasUiPreferencesFeature(): bool
    {
        return $this->uiprefswork;
    }

    public function hasTrackingFeature(): bool
    {
        return $this->trackingwork;
    }

    public function hasUserPreferencesFeature(): bool
    {
        return $this->userconfigwork;
    }

    public function hasConfigurableMenusFeature(): bool
    {
        return $this->menuswork;
    }

    public function hasNavigationItemsHidingFeature(): bool
    {
        return $this->navwork;
    }

    public function hasSavedQueryByExampleSearchesFeature(): bool
    {
        return $this->savedsearcheswork;
    }

    public function hasCentralColumnsFeature(): bool
    {
        return $this->centralcolumnswork;
    }

    public function hasDatabaseDesignerSettingsFeature(): bool
    {
        return $this->designersettingswork;
    }

    public function hasExportTemplatesFeature(): bool
    {
        return $this->exporttemplateswork;
    }

    public function hasAllFeatures(): bool
    {
        return $this->allworks;
    }

    /**
     * @param mixed[] $params
     * @psalm-param non-empty-string $key
     */
    private static function getTableName(array $params, string $key): ?TableName
    {
        try {
            Assert::keyExists($params, $key);

            return TableName::fromValue($params[$key]);
        } catch (InvalidArgumentException $exception) {
            return null;
        }
    }
}
