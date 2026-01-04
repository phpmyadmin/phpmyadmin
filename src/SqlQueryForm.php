<?php
/**
 * functions for displaying the sql query form
 *
 * @usedby  /server/sql
 * @usedby  /database/sql
 * @usedby  /table/sql
 * @usedby  /table/structure
 * @usedby  /table/tracking
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function htmlspecialchars;
use function sprintf;
use function str_contains;

/**
 * PhpMyAdmin\SqlQueryForm class
 */
class SqlQueryForm
{
    private readonly Config $config;

    public function __construct(
        private Template $template,
        private DatabaseInterface $dbi,
        private readonly BookmarkRepository $bookmarkRepository,
    ) {
        $this->config = Config::getInstance();
    }

    /**
     * return HTML for the sql query boxes
     *
     * @param bool|string $query      query to display in the textarea
     *                                or true to display last executed
     * @param bool|string $displayTab sql|full|false
     *                                 what part to display
     *                                 false if not inside querywindow
     * @param string      $delimiter  delimiter
     *
     * @usedby  /server/sql
     * @usedby  /database/sql
     * @usedby  /table/sql
     * @usedby  /table/structure
     * @usedby  /table/tracking
     */
    public function getHtml(
        string $db,
        string $table,
        bool|string $query = true,
        bool|string $displayTab = false,
        string $delimiter = ';',
    ): string {
        if ($displayTab === false || $displayTab === '') {
            $displayTab = 'full';
        }

        // query to show
        if ($query === true) {
            $query = Current::$sqlQuery;
            if ($query === '' && (isset($_GET['show_query']) || isset($_POST['show_query']))) {
                $query = $_GET['sql_query'] ?? $_POST['sql_query'] ?? '';
            }
        }

        if ($db === '') {
            // prepare for server related
            $goto = UrlParams::$goto === '' ? Url::getFromRoute('/server/sql') : UrlParams::$goto;
        } elseif ($table === '') {
            // prepare for db related
            $goto = UrlParams::$goto === '' ? Url::getFromRoute('/database/sql') : UrlParams::$goto;
        } else {
            $goto = UrlParams::$goto === '' ? Url::getFromRoute('/table/sql') : UrlParams::$goto;
        }

        if ($displayTab === 'full' || $displayTab === 'sql') {
            [$legend, $query, $columnsList] = $this->init($query);
        }

        $relation = new Relation($this->dbi);
        $bookmarkFeature = $relation->getRelationParameters()->bookmarkFeature;

        $bookmarks = [];
        if ($displayTab === 'full' && $bookmarkFeature !== null) {
            $bookmarkList = $this->bookmarkRepository->getList($this->config->selectedServer['user'], $db);

            foreach ($bookmarkList as $bookmarkItem) {
                $bookmarks[] = [
                    'id' => $bookmarkItem->getId(),
                    'variable_count' => $bookmarkItem->getVariableCount(),
                    'label' => $bookmarkItem->getLabel(),
                    'is_shared' => $bookmarkItem->getUser() === '',
                ];
            }
        }

        return $this->template->render('sql/query', [
            'legend' => $legend ?? '',
            'textarea_cols' => $this->config->config->TextareaCols,
            'textarea_rows' => $this->config->config->TextareaRows,
            'textarea_auto_select' => $this->config->settings['TextareaAutoSelect'],
            'columns_list' => $columnsList ?? [],
            'codemirror_enable' => $this->config->config->CodemirrorEnable,
            'has_bookmark' => $bookmarkFeature !== null,
            'delimiter' => $delimiter,
            'retain_query_box' => $this->config->config->RetainQueryBox,
            'is_upload' => $this->config->isUploadEnabled(),
            'db' => $db,
            'table' => $table,
            'goto' => $goto,
            'query' => $query,
            'display_tab' => $displayTab,
            'bookmarks' => $bookmarks,
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
            'allow_shared_bookmarks' => $this->config->config->AllowSharedBookmarks,
        ]);
    }

    /**
     * Get initial values for Sql Query Form Insert
     *
     * @param string $query query to display in the textarea
     *
     * @return array{string, string, Column[]}
     */
    public function init(string $query): array
    {
        $columnsList = [];
        if (Current::$database === '') {
            // prepare for server related
            $legend = sprintf(
                __('Run SQL query/queries on server “%s”'),
                htmlspecialchars(
                    ! empty($this->config->settings['Servers'][Current::$server]['verbose'])
                    ? $this->config->settings['Servers'][Current::$server]['verbose']
                    : $this->config->settings['Servers'][Current::$server]['host'],
                ),
            );
        } elseif (Current::$table === '') {
            // prepare for db related
            $db = Current::$database;
            // if you want navigation:
            $scriptName = Url::getFromRoute($this->config->config->DefaultTabDatabase);
            $tmpDbLink = '<a href="' . $scriptName
                . Url::getCommon(['db' => $db], ! str_contains($scriptName, '?') ? '?' : '&')
                . '">';
            $tmpDbLink .= htmlspecialchars($db) . '</a>';
            $legend = sprintf(__('Run SQL query/queries on database %s'), $tmpDbLink);
            if ($query === '') {
                $query = Util::expandUserString($this->config->settings['DefaultQueryDatabase'], Util::backquote(...));
            }
        } else {
            $db = Current::$database;
            $table = Current::$table;
            // Get the list and number of fields
            // we do a try_query here, because we could be in the query window,
            // trying to synchronize and the table has not yet been created
            $columnsList = $this->dbi->getColumns($db, Current::$table);

            $scriptName = Url::getFromRoute($this->config->config->DefaultTabTable);
            $tmpTblLink = '<a href="' . $scriptName . Url::getCommon(['db' => $db, 'table' => $table], '&') . '">';
            $tmpTblLink .= htmlspecialchars($db) . '.' . htmlspecialchars($table) . '</a>';
            $legend = sprintf(__('Run SQL query/queries on table %s'), $tmpTblLink);
            if ($query === '') {
                $query = Util::expandUserString($this->config->settings['DefaultQueryTable'], Util::backquote(...));
            }
        }

        $legend .= ': ' . MySQLDocumentation::show('SELECT');

        return [$legend, $query, $columnsList];
    }
}
