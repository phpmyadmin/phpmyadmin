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
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidDatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Version;

use function is_string;

/** @psalm-immutable */
final readonly class RelationParameters
{
    public const VERSION = 'version';
    public const USER = 'user';
    public const DATABASE = 'db';

    public const BOOKMARK = 'bookmark';
    public const CENTRAL_COLUMNS = 'central_columns';
    public const COLUMN_INFO = 'column_info';
    public const DESIGNER_SETTINGS = 'designer_settings';
    public const EXPORT_TEMPLATES = 'export_templates';
    public const FAVORITE = 'favorite';
    public const HISTORY = 'history';
    public const NAVIGATION_HIDING = 'navigationhiding';
    public const PDF_PAGES = 'pdf_pages';
    public const RECENT = 'recent';
    public const RELATION = 'relation';
    public const SAVED_SEARCHES = 'savedsearches';
    public const TABLE_COORDS = 'table_coords';
    public const TABLE_INFO = 'table_info';
    public const TABLE_UI_PREFS = 'table_uiprefs';
    public const TRACKING = 'tracking';
    public const USERS = 'users';
    public const USER_CONFIG = 'userconfig';
    public const USER_GROUPS = 'usergroups';

    public const ALL_WORKS = 'allworks';
    public const BOOKMARK_WORK = 'bookmarkwork';
    public const CENTRAL_COLUMNS_WORK = 'centralcolumnswork';
    public const COMM_WORK = 'commwork';
    public const DESIGNER_SETTINGS_WORK = 'designersettingswork';
    public const DISPLAY_WORK = 'displaywork';
    public const EXPORT_TEMPLATES_WORK = 'exporttemplateswork';
    public const FAVORITE_WORK = 'favoritework';
    public const HISTORY_WORK = 'historywork';
    public const MENUS_WORK = 'menuswork';
    public const MIME_WORK = 'mimework';
    public const NAV_WORK = 'navwork';
    public const PDF_WORK = 'pdfwork';
    public const RECENT_WORK = 'recentwork';
    public const REL_WORK = 'relwork';
    public const SAVED_SEARCHES_WORK = 'savedsearcheswork';
    public const TRACKING_WORK = 'trackingwork';
    public const UI_PREFS_WORK = 'uiprefswork';
    public const USER_CONFIG_WORK = 'userconfigwork';

    /** @param non-empty-string|null $user */
    public function __construct(
        public string|null $user,
        public DatabaseName|null $db = null,
        public BookmarkFeature|null $bookmarkFeature = null,
        public BrowserTransformationFeature|null $browserTransformationFeature = null,
        public CentralColumnsFeature|null $centralColumnsFeature = null,
        public ColumnCommentsFeature|null $columnCommentsFeature = null,
        public ConfigurableMenusFeature|null $configurableMenusFeature = null,
        public DatabaseDesignerSettingsFeature|null $databaseDesignerSettingsFeature = null,
        public DisplayFeature|null $displayFeature = null,
        public ExportTemplatesFeature|null $exportTemplatesFeature = null,
        public FavoriteTablesFeature|null $favoriteTablesFeature = null,
        public NavigationItemsHidingFeature|null $navigationItemsHidingFeature = null,
        public PdfFeature|null $pdfFeature = null,
        public RecentlyUsedTablesFeature|null $recentlyUsedTablesFeature = null,
        public RelationFeature|null $relationFeature = null,
        public SavedQueryByExampleSearchesFeature|null $savedQueryByExampleSearchesFeature = null,
        public SqlHistoryFeature|null $sqlHistoryFeature = null,
        public TrackingFeature|null $trackingFeature = null,
        public UiPreferencesFeature|null $uiPreferencesFeature = null,
        public UserPreferencesFeature|null $userPreferencesFeature = null,
    ) {
    }

    /** @param mixed[] $params */
    public static function fromArray(array $params): self
    {
        $user = null;
        if (isset($params[self::USER]) && is_string($params[self::USER]) && $params[self::USER] !== '') {
            $user = $params[self::USER];
        }

        try {
            $db = DatabaseName::from($params[self::DATABASE] ?? null);
        } catch (InvalidDatabaseName) {
            return new self($user, null);
        }

        $bookmarkFeature = null;
        if (isset($params[self::BOOKMARK_WORK], $params[self::BOOKMARK]) && $params[self::BOOKMARK_WORK]) {
            $bookmark = TableName::tryFrom($params[self::BOOKMARK]);
            if ($bookmark !== null) {
                $bookmarkFeature = new BookmarkFeature($db, $bookmark);
            }
        }

        $columnInfo = TableName::tryFrom($params[self::COLUMN_INFO] ?? null);
        $browserTransformationFeature = null;
        if (isset($params[self::MIME_WORK]) && $params[self::MIME_WORK] && $columnInfo !== null) {
            $browserTransformationFeature = new BrowserTransformationFeature($db, $columnInfo);
        }

        $columnCommentsFeature = null;
        if (isset($params[self::COMM_WORK]) && $params[self::COMM_WORK] && $columnInfo !== null) {
            $columnCommentsFeature = new ColumnCommentsFeature($db, $columnInfo);
        }

        $centralColumnsFeature = null;
        if (
            isset($params[self::CENTRAL_COLUMNS_WORK], $params[self::CENTRAL_COLUMNS])
            && $params[self::CENTRAL_COLUMNS_WORK]
        ) {
            $centralColumns = TableName::tryFrom($params[self::CENTRAL_COLUMNS]);
            if ($centralColumns !== null) {
                $centralColumnsFeature = new CentralColumnsFeature($db, $centralColumns);
            }
        }

        $configurableMenusFeature = null;
        if (
            isset($params[self::MENUS_WORK], $params[self::USER_GROUPS], $params[self::USERS])
            && $params[self::MENUS_WORK]
        ) {
            $userGroups = TableName::tryFrom($params[self::USER_GROUPS]);
            $users = TableName::tryFrom($params[self::USERS]);
            if ($userGroups !== null && $users !== null) {
                $configurableMenusFeature = new ConfigurableMenusFeature($db, $userGroups, $users);
            }
        }

        $databaseDesignerSettingsFeature = null;
        if (
            isset($params[self::DESIGNER_SETTINGS_WORK], $params[self::DESIGNER_SETTINGS])
            && $params[self::DESIGNER_SETTINGS_WORK]
        ) {
            $designerSettings = TableName::tryFrom($params[self::DESIGNER_SETTINGS]);
            if ($designerSettings !== null) {
                $databaseDesignerSettingsFeature = new DatabaseDesignerSettingsFeature($db, $designerSettings);
            }
        }

        $relation = TableName::tryFrom($params[self::RELATION] ?? null);
        $displayFeature = null;
        if (
            isset($params[self::DISPLAY_WORK], $params[self::TABLE_INFO])
            && $params[self::DISPLAY_WORK] && $relation !== null
        ) {
            $tableInfo = TableName::tryFrom($params[self::TABLE_INFO]);
            if ($tableInfo !== null) {
                $displayFeature = new DisplayFeature($db, $relation, $tableInfo);
            }
        }

        $exportTemplatesFeature = null;
        if (
            isset($params[self::EXPORT_TEMPLATES_WORK], $params[self::EXPORT_TEMPLATES])
            && $params[self::EXPORT_TEMPLATES_WORK]
        ) {
            $exportTemplates = TableName::tryFrom($params[self::EXPORT_TEMPLATES]);
            if ($exportTemplates !== null) {
                $exportTemplatesFeature = new ExportTemplatesFeature($db, $exportTemplates);
            }
        }

        $favoriteTablesFeature = null;
        if (isset($params[self::FAVORITE_WORK], $params[self::FAVORITE]) && $params[self::FAVORITE_WORK]) {
            $favorite = TableName::tryFrom($params[self::FAVORITE]);
            if ($favorite !== null) {
                $favoriteTablesFeature = new FavoriteTablesFeature($db, $favorite);
            }
        }

        $navigationItemsHidingFeature = null;
        if (isset($params[self::NAV_WORK], $params[self::NAVIGATION_HIDING]) && $params[self::NAV_WORK]) {
            $navigationHiding = TableName::tryFrom($params[self::NAVIGATION_HIDING]);
            if ($navigationHiding !== null) {
                $navigationItemsHidingFeature = new NavigationItemsHidingFeature($db, $navigationHiding);
            }
        }

        $pdfFeature = null;
        if (
            isset($params[self::PDF_WORK], $params[self::PDF_PAGES], $params[self::TABLE_COORDS])
            && $params[self::PDF_WORK]
        ) {
            $pdfPages = TableName::tryFrom($params[self::PDF_PAGES]);
            $tableCoords = TableName::tryFrom($params[self::TABLE_COORDS]);
            if ($pdfPages !== null && $tableCoords !== null) {
                $pdfFeature = new PdfFeature($db, $pdfPages, $tableCoords);
            }
        }

        $recentlyUsedTablesFeature = null;
        if (isset($params[self::RECENT_WORK], $params[self::RECENT]) && $params[self::RECENT_WORK]) {
            $recent = TableName::tryFrom($params[self::RECENT]);
            if ($recent !== null) {
                $recentlyUsedTablesFeature = new RecentlyUsedTablesFeature($db, $recent);
            }
        }

        $relationFeature = null;
        if (isset($params[self::REL_WORK]) && $params[self::REL_WORK] && $relation !== null) {
            $relationFeature = new RelationFeature($db, $relation);
        }

        $savedQueryByExampleSearchesFeature = null;
        if (
            isset($params[self::SAVED_SEARCHES_WORK], $params[self::SAVED_SEARCHES])
            && $params[self::SAVED_SEARCHES_WORK]
        ) {
            $savedSearches = TableName::tryFrom($params[self::SAVED_SEARCHES]);
            if ($savedSearches !== null) {
                $savedQueryByExampleSearchesFeature = new SavedQueryByExampleSearchesFeature($db, $savedSearches);
            }
        }

        $sqlHistoryFeature = null;
        if (isset($params[self::HISTORY_WORK], $params[self::HISTORY]) && $params[self::HISTORY_WORK]) {
            $history = TableName::tryFrom($params[self::HISTORY]);
            if ($history !== null) {
                $sqlHistoryFeature = new SqlHistoryFeature($db, $history);
            }
        }

        $trackingFeature = null;
        if (isset($params[self::TRACKING_WORK], $params[self::TRACKING]) && $params[self::TRACKING_WORK]) {
            $tracking = TableName::tryFrom($params[self::TRACKING]);
            if ($tracking !== null) {
                $trackingFeature = new TrackingFeature($db, $tracking);
            }
        }

        $uiPreferencesFeature = null;
        if (isset($params[self::UI_PREFS_WORK], $params[self::TABLE_UI_PREFS]) && $params[self::UI_PREFS_WORK]) {
            $tableUiPrefs = TableName::tryFrom($params[self::TABLE_UI_PREFS]);
            if ($tableUiPrefs !== null) {
                $uiPreferencesFeature = new UiPreferencesFeature($db, $tableUiPrefs);
            }
        }

        $userPreferencesFeature = null;
        if (isset($params[self::USER_CONFIG_WORK], $params[self::USER_CONFIG]) && $params[self::USER_CONFIG_WORK]) {
            $userConfig = TableName::tryFrom($params[self::USER_CONFIG]);
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
     * @return array{
     *   version: string,
     *   user: (non-empty-string|null),
     *   db: (non-empty-string|null),
     *   bookmark: (non-empty-string|null),
     *   central_columns: (non-empty-string|null),
     *   column_info: (non-empty-string|null),
     *   designer_settings: (non-empty-string|null),
     *   export_templates: (non-empty-string|null),
     *   favorite: (non-empty-string|null),
     *   history: (non-empty-string|null),
     *   navigationhiding: (non-empty-string|null),
     *   pdf_pages: (non-empty-string|null),
     *   recent: (non-empty-string|null),
     *   relation: (non-empty-string|null),
     *   savedsearches: (non-empty-string|null),
     *   table_coords: (non-empty-string|null),
     *   table_info: (non-empty-string|null),
     *   table_uiprefs: (non-empty-string|null),
     *   tracking: (non-empty-string|null),
     *   userconfig: (non-empty-string|null),
     *   usergroups: (non-empty-string|null),
     *   users: (non-empty-string|null),
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
            self::VERSION => Version::VERSION,
            self::USER => $this->user,
            self::DATABASE => $this->db?->getName(),
            self::BOOKMARK => $this->bookmarkFeature?->bookmark->getName(),
            self::CENTRAL_COLUMNS => $this->centralColumnsFeature?->centralColumns->getName(),
            self::COLUMN_INFO => $columnInfo,
            self::DESIGNER_SETTINGS => $this->databaseDesignerSettingsFeature?->designerSettings->getName(),
            self::EXPORT_TEMPLATES => $this->exportTemplatesFeature?->exportTemplates->getName(),
            self::FAVORITE => $this->favoriteTablesFeature?->favorite->getName(),
            self::HISTORY => $this->sqlHistoryFeature?->history->getName(),
            self::NAVIGATION_HIDING => $this->navigationItemsHidingFeature?->navigationHiding->getName(),
            self::PDF_PAGES => $this->pdfFeature?->pdfPages->getName(),
            self::RECENT => $this->recentlyUsedTablesFeature?->recent->getName(),
            self::RELATION => $relation,
            self::SAVED_SEARCHES => $this->savedQueryByExampleSearchesFeature?->savedSearches->getName(),
            self::TABLE_COORDS => $this->pdfFeature?->tableCoords->getName(),
            self::TABLE_INFO => $this->displayFeature?->tableInfo->getName(),
            self::TABLE_UI_PREFS => $this->uiPreferencesFeature?->tableUiPrefs->getName(),
            self::TRACKING => $this->trackingFeature?->tracking->getName(),
            self::USER_CONFIG => $this->userPreferencesFeature?->userConfig->getName(),
            self::USER_GROUPS => $this->configurableMenusFeature?->userGroups->getName(),
            self::USERS => $this->configurableMenusFeature?->users->getName(),
            self::BOOKMARK_WORK => $this->bookmarkFeature !== null,
            self::MIME_WORK => $this->browserTransformationFeature !== null,
            self::CENTRAL_COLUMNS_WORK => $this->centralColumnsFeature !== null,
            self::COMM_WORK => $this->columnCommentsFeature !== null,
            self::MENUS_WORK => $this->configurableMenusFeature !== null,
            self::DESIGNER_SETTINGS_WORK => $this->databaseDesignerSettingsFeature !== null,
            self::DISPLAY_WORK => $this->displayFeature !== null,
            self::EXPORT_TEMPLATES_WORK => $this->exportTemplatesFeature !== null,
            self::FAVORITE_WORK => $this->favoriteTablesFeature !== null,
            self::NAV_WORK => $this->navigationItemsHidingFeature !== null,
            self::PDF_WORK => $this->pdfFeature !== null,
            self::RECENT_WORK => $this->recentlyUsedTablesFeature !== null,
            self::REL_WORK => $this->relationFeature !== null,
            self::SAVED_SEARCHES_WORK => $this->savedQueryByExampleSearchesFeature !== null,
            self::HISTORY_WORK => $this->sqlHistoryFeature !== null,
            self::TRACKING_WORK => $this->trackingFeature !== null,
            self::UI_PREFS_WORK => $this->uiPreferencesFeature !== null,
            self::USER_CONFIG_WORK => $this->userPreferencesFeature !== null,
            self::ALL_WORKS => $this->hasAllFeatures(),
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
