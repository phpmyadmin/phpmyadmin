<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\MultSubmits class
 *
 * @usedby  mult_submits.inc.php
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Operations;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Table;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Functions for multi submit forms
 *
 * @package PhpMyAdmin
 */
class MultSubmits
{
    /**
     * Gets url params
     *
     * @param string     $what             mult submit type
     * @param bool       $reload           is reload
     * @param string     $action           action type
     * @param string     $db               database name
     * @param string     $table            table name
     * @param array      $selected         selected rows(table,db)
     * @param array|null $views            table views
     * @param string     $originalSqlQuery original sql query
     * @param string     $originalUrlQuery original url query
     *
     * @return array
     */
    public function getUrlParams(
        $what,
        $reload,
        $action,
        $db,
        $table,
        array $selected,
        $views,
        $originalSqlQuery,
        $originalUrlQuery
    ) {
        $urlParams = [
            'query_type' => $what,
            'reload' => (! empty($reload) ? 1 : 0),
        ];
        if (mb_strpos(' ' . $action, 'db_') == 1) {
            $urlParams['db']= $db;
        } elseif (mb_strpos(' ' . $action, 'tbl_') == 1
            || $what == 'row_delete'
        ) {
            $urlParams['db'] = $db;
            $urlParams['table'] = $table;
        }
        foreach ($selected as $selectedValue) {
            if ($what == 'row_delete') {
                $urlParams['selected'][] = 'DELETE FROM '
                    . Util::backquote($table)
                    . ' WHERE ' . $selectedValue . ' LIMIT 1;';
            } else {
                $urlParams['selected'][] = $selectedValue;
            }
        }
        if ($what == 'drop_tbl' && !empty($views)) {
            foreach ($views as $current) {
                $urlParams['views'][] = $current;
            }
        }
        if ($what == 'row_delete') {
            $urlParams['original_sql_query'] = $originalSqlQuery;
            if (! empty($originalUrlQuery)) {
                $urlParams['original_url_query'] = $originalUrlQuery;
            }
        }

        return $urlParams;
    }

    /**
     * Builds or execute queries for multiple elements, depending on $queryType
     *
     * @param string     $queryType  query type
     * @param array      $selected   selected tables
     * @param string     $db         db name
     * @param string     $table      table name
     * @param array|null $views      table views
     * @param string     $primary    table primary
     * @param string     $fromPrefix from prefix original
     * @param string     $toPrefix   to prefix original
     *
     * @return array
     */
    public function buildOrExecuteQuery(
        $queryType,
        array $selected,
        $db,
        $table,
        $views,
        $primary,
        $fromPrefix,
        $toPrefix
    ) {
        $rebuildDatabaseList = false;
        $reload = null;
        $aQuery = null;
        $sqlQuery = '';
        $sqlQueryViews = null;
        // whether to run query after each pass
        $runParts = false;
        // whether to execute the query at the end (to display results)
        $executeQueryLater = false;
        $result = null;

        if ($queryType == 'drop_tbl') {
            $sqlQueryViews = '';
        }

        $selectedCount = count($selected);
        $deletes = false;
        $copyTable = false;

        for ($i = 0; $i < $selectedCount; $i++) {
            switch ($queryType) {
                case 'row_delete':
                    $deletes = true;
                    $aQuery = $selected[$i];
                    $runParts = true;
                    break;

                case 'drop_db':
                    RelationCleanup::database($selected[$i]);
                    $aQuery = 'DROP DATABASE '
                           . Util::backquote($selected[$i]);
                    $reload = 1;
                    $runParts = true;
                    $rebuildDatabaseList = true;
                    break;

                case 'drop_tbl':
                    RelationCleanup::table($db, $selected[$i]);
                    $current = $selected[$i];
                    if (!empty($views) && in_array($current, $views)) {
                        $sqlQueryViews .= (empty($sqlQueryViews) ? 'DROP VIEW ' : ', ')
                            . Util::backquote($current);
                    } else {
                        $sqlQuery .= (empty($sqlQuery) ? 'DROP TABLE ' : ', ')
                            . Util::backquote($current);
                    }
                    $reload    = 1;
                    break;

                case 'check_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'CHECK TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'optimize_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'OPTIMIZE TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'analyze_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'ANALYZE TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'checksum_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'CHECKSUM TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'repair_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'REPAIR TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'empty_tbl':
                    $deletes = true;
                    $aQuery = 'TRUNCATE ';
                    $aQuery .= Util::backquote($selected[$i]);
                    $runParts = true;
                    break;

                case 'drop_fld':
                    RelationCleanup::column($db, $table, $selected[$i]);
                    $sqlQuery .= (empty($sqlQuery)
                        ? 'ALTER TABLE ' . Util::backquote($table)
                        : ',')
                        . ' DROP ' . Util::backquote($selected[$i])
                        . (($i == $selectedCount - 1) ? ';' : '');
                    break;

                case 'primary_fld':
                    $sqlQuery .= (empty($sqlQuery)
                    ? 'ALTER TABLE ' . Util::backquote($table)
                        . (empty($primary)
                        ? ''
                        : ' DROP PRIMARY KEY,') . ' ADD PRIMARY KEY( '
                    : ', ')
                        . Util::backquote($selected[$i])
                        . (($i == $selectedCount - 1) ? ');' : '');
                    break;

                case 'index_fld':
                    $sqlQuery .= (empty($sqlQuery)
                    ? 'ALTER TABLE ' . Util::backquote($table)
                        . ' ADD INDEX( '
                    : ', ')
                        . Util::backquote($selected[$i])
                        . (($i == $selectedCount - 1) ? ');' : '');
                    break;

                case 'unique_fld':
                    $sqlQuery .= (empty($sqlQuery)
                    ? 'ALTER TABLE ' . Util::backquote($table)
                        . ' ADD UNIQUE( '
                    : ', ')
                        . Util::backquote($selected[$i])
                        . (($i == $selectedCount - 1) ? ');' : '');
                    break;

                case 'spatial_fld':
                    $sqlQuery .= (empty($sqlQuery)
                    ? 'ALTER TABLE ' . Util::backquote($table)
                        . ' ADD SPATIAL( '
                    : ', ')
                        . Util::backquote($selected[$i])
                        . (($i == $selectedCount - 1) ? ');' : '');
                    break;

                case 'fulltext_fld':
                    $sqlQuery .= (empty($sqlQuery)
                    ? 'ALTER TABLE ' . Util::backquote($table)
                        . ' ADD FULLTEXT( '
                    : ', ')
                        . Util::backquote($selected[$i])
                        . (($i == $selectedCount - 1) ? ');' : '');
                    break;

                case 'add_prefix_tbl':
                    $newTableName = $_POST['add_prefix'] . $selected[$i];
                    // ADD PREFIX TO TABLE NAME
                    $aQuery = 'ALTER TABLE '
                    . Util::backquote($selected[$i])
                    . ' RENAME '
                    . Util::backquote($newTableName);
                    $runParts = true;
                    break;

                case 'replace_prefix_tbl':
                    $current = $selected[$i];
                    $subFromPrefix = mb_substr(
                        $current,
                        0,
                        mb_strlen($fromPrefix)
                    );
                    if ($subFromPrefix == $fromPrefix) {
                        $newTableName = $toPrefix
                            . mb_substr(
                                $current,
                                mb_strlen($fromPrefix)
                            );
                    } else {
                        $newTableName = $current;
                    }
                    // CHANGE PREFIX PATTERN
                    $aQuery = 'ALTER TABLE '
                    . Util::backquote($selected[$i])
                    . ' RENAME '
                    . Util::backquote($newTableName);
                    $runParts = true;
                    break;

                case 'copy_tbl_change_prefix':
                    $runParts = true;
                    $copyTable = true;

                    $current = $selected[$i];
                    $newTableName = $toPrefix .
                    mb_substr($current, mb_strlen($fromPrefix));

                    // COPY TABLE AND CHANGE PREFIX PATTERN
                    Table::moveCopy(
                        $db,
                        $current,
                        $db,
                        $newTableName,
                        'data',
                        false,
                        'one_table'
                    );
                    break;

                case 'copy_tbl':
                    $runParts = true;
                    $copyTable = true;
                    Table::moveCopy(
                        $db,
                        $selected[$i],
                        $_POST['target_db'],
                        $selected[$i],
                        $_POST['what'],
                        false,
                        'one_table'
                    );
                    if (isset($_POST['adjust_privileges']) && !empty($_POST['adjust_privileges'])) {
                        $operations = new Operations();
                        $operations->adjustPrivilegesCopyTable(
                            $db,
                            $selected[$i],
                            $_POST['target_db'],
                            $selected[$i]
                        );
                    }
                    break;
            } // end switch

            // All "DROP TABLE", "DROP FIELD", "OPTIMIZE TABLE" and "REPAIR TABLE"
            // statements will be run at once below
            if ($runParts && !$copyTable) {
                $sqlQuery .= $aQuery . ';' . "\n";
                if ($queryType != 'drop_db') {
                    $GLOBALS['dbi']->selectDb($db);
                }
                $result = $GLOBALS['dbi']->query($aQuery);

                if ($queryType == 'drop_db') {
                    Transformations::clear($selected[$i]);
                } elseif ($queryType == 'drop_tbl') {
                    Transformations::clear($db, $selected[$i]);
                } elseif ($queryType == 'drop_fld') {
                    Transformations::clear($db, $table, $selected[$i]);
                }
            } // end if
        } // end for

        if ($deletes && ! empty($_REQUEST['pos'])) {
            $sql = new Sql();
            $_REQUEST['pos'] = $sql->calculatePosForLastPage(
                $db,
                $table,
                isset($_REQUEST['pos']) ? $_REQUEST['pos'] : null
            );
        }

        return [
            $result,
            $rebuildDatabaseList,
            $reload,
            $runParts,
            $executeQueryLater,
            $sqlQuery,
            $sqlQueryViews
        ];
    }

    /**
     * Gets HTML for copy tables form
     *
     * @param string $action    action type
     * @param array  $urlParams URL params
     *
     * @return string
     */
    public function getHtmlForCopyMultipleTables($action, array $urlParams)
    {
        $html = '<form id="ajax_form" action="' . $action . '" method="post">';
        $html .= Url::getHiddenInputs($urlParams);
        $html .= '<fieldset class = "input">';
        $databasesList = $GLOBALS['dblist']->databases;
        foreach ($databasesList as $key => $databaseName) {
            if ($databaseName == $GLOBALS['db']) {
                $databasesList->offsetUnset($key);
                break;
            }
        }
        $html .= '<strong><label for="db_name_dropdown">' . __('Database') . ':</label></strong>';
        $html .= '<select id="db_name_dropdown" class="halfWidth" name="target_db" >'
            . $databasesList->getHtmlOptions(true, false)
            . '</select>';
        $html .= '<br><br>';
        $html .= '<strong><label>' . __('Options') . ':</label></strong><br>';
        $html .= '<input type="radio" id ="what_structure" value="structure" name="what"/>';
        $html .= '<label for="what_structure">' . __('Structure only') . '</label><br>';
        $html .= '<input type="radio" id ="what_data" value="data" name="what" checked="checked"/>';
        $html .= '<label for="what_data">' . __('Structure and data') . '</label><br>';
        $html .= '<input type="radio" id ="what_dataonly" value="dataonly" name="what"/>';
        $html .= '<label for="what_dataonly">' . __('Data only') . '</label><br><br>';
        $html .= '<input type="checkbox" id="checkbox_drop" value="true" name="drop_if_exists"/>';
        $html .= '<label for="checkbox_drop">' . __('Add DROP TABLE') . '</label><br>';
        $html .= '<input type="checkbox" id="checkbox_auto_increment_cp" value="1" name="sql_auto_increment"/>';
        $html .= '<label for="checkbox_auto_increment_cp">' . __('Add AUTO INCREMENT value') . '</label><br>';
        $html .= '<input type="checkbox" id="checkbox_constraints" value="1" name="sql_auto_increment" checked="checked"/>';
        $html .= '<label for="checkbox_constraints">' . __('Add constraints') . '</label><br><br>';
        $html .= '<input name="adjust_privileges" value="1" id="checkbox_adjust_privileges" checked="checked" type="checkbox"/>';
        $html .= '<label for="checkbox_adjust_privileges">' . __('Adjust privileges') . '<a href="./doc/html/faq.html#faq6-39" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a></label>';
        $html .= '</fieldset>';
        $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
        $html .= '</form>';
        return $html;
    }

    /**
     * Gets HTML for replace_prefix_tbl or copy_tbl_change_prefix
     *
     * @param string $action    action type
     * @param array  $urlParams URL params
     *
     * @return string
     */
    public function getHtmlForReplacePrefixTable($action, array $urlParams)
    {
        $html  = '<form id="ajax_form" action="' . $action . '" method="post">';
        $html .= Url::getHiddenInputs($urlParams);
        $html .= '<fieldset class = "input">';
        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td>' . __('From') . '</td>';
        $html .= '<td>';
        $html .= '<input type="text" name="from_prefix" id="initialPrefix" />';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>' . __('To') . '</td>';
        $html .= '<td>';
        $html .= '<input type="text" name="to_prefix" id="newPrefix" />';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</fieldset>';
        $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
        $html .= '</form>';

        return $html;
    }

    /**
     * Gets HTML for add_prefix_tbl
     *
     * @param string $action    action type
     * @param array  $urlParams URL params
     *
     * @return string
     */
    public function getHtmlForAddPrefixTable($action, array $urlParams)
    {
        $html  = '<form id="ajax_form" action="' . $action . '" method="post">';
        $html .= Url::getHiddenInputs($urlParams);
        $html .= '<fieldset class = "input">';
        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td>' . __('Add prefix') . '</td>';
        $html .= '<td>';
        $html .= '<input type="text" name="add_prefix" id="txtPrefix" />';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '</table>';
        $html .= '</fieldset>';
        $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
        $html .= '</form>';

        return $html;
    }

    /**
     * Gets HTML for other mult_submits actions
     *
     * @param string $what      mult_submit type
     * @param string $action    action type
     * @param array  $urlParams URL params
     * @param string $fullQuery full sql query string
     *
     * @return string
     */
    public function getHtmlForOtherActions($what, $action, array $urlParams, $fullQuery)
    {
        $html = '<form action="' . $action . '" method="post">';
        $html .= Url::getHiddenInputs($urlParams);
        $html .= '<fieldset class="confirmation">';
        $html .= '<legend>';
        if ($what == 'drop_db') {
            $html .=  __('You are about to DESTROY a complete database!') . ' ';
        }
        $html .= __('Do you really want to execute the following query?');
        $html .= '</legend>';
        $html .= '<code>' . $fullQuery . '</code>';
        $html .= '</fieldset>';
        $html .= '<fieldset class="tblFooters">';
        // Display option to disable foreign key checks while dropping tables
        if ($what === 'drop_tbl' || $what === 'empty_tbl' || $what === 'row_delete') {
            $html .= '<div id="foreignkeychk">';
            $html .= Util::getFKCheckbox();
            $html .= '</div>';
        }
        $html .= '<input id="buttonYes" type="submit" name="mult_btn" value="'
            . __('Yes') . '" />';
        $html .= '<input id="buttonNo" type="submit" name="mult_btn" value="'
            . __('No') . '" />';
        $html .= '</fieldset>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Get query string from Selected
     *
     * @param string $what     mult_submit type
     * @param string $table    table name
     * @param array  $selected the selected columns
     * @param array  $views    table views
     *
     * @return array
     */
    public function getQueryFromSelected($what, $table, array $selected, array $views)
    {
        $reload = false;
        $fullQueryViews = null;
        $fullQuery = '';

        if ($what == 'drop_tbl') {
            $fullQueryViews = '';
        }

        $selectedCount = count($selected);
        $i = 0;
        foreach ($selected as $selectedValue) {
            switch ($what) {
                case 'row_delete':
                    $fullQuery .= 'DELETE FROM '
                    . Util::backquote(htmlspecialchars($table))
                    // Do not append a "LIMIT 1" clause here
                    // (it's not binlog friendly).
                    // We don't need the clause because the calling panel permits
                    // this feature only when there is a unique index.
                    . ' WHERE ' . htmlspecialchars($selectedValue)
                    . ';<br />';
                    break;
                case 'drop_db':
                    $fullQuery .= 'DROP DATABASE '
                    . Util::backquote(htmlspecialchars($selectedValue))
                    . ';<br />';
                    $reload = true;
                    break;

                case 'drop_tbl':
                    $current = $selectedValue;
                    if (!empty($views) && in_array($current, $views)) {
                        $fullQueryViews .= (empty($fullQueryViews) ? 'DROP VIEW ' : ', ')
                        . Util::backquote(htmlspecialchars($current));
                    } else {
                        $fullQuery .= (empty($fullQuery) ? 'DROP TABLE ' : ', ')
                        . Util::backquote(htmlspecialchars($current));
                    }
                    break;

                case 'empty_tbl':
                    $fullQuery .= 'TRUNCATE ';
                    $fullQuery .= Util::backquote(htmlspecialchars($selectedValue))
                            . ';<br />';
                    break;

                case 'primary_fld':
                    if ($fullQuery == '') {
                        $fullQuery .= 'ALTER TABLE '
                        . Util::backquote(htmlspecialchars($table))
                        . '<br />&nbsp;&nbsp;DROP PRIMARY KEY,'
                        . '<br />&nbsp;&nbsp; ADD PRIMARY KEY('
                        . '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                        . Util::backquote(htmlspecialchars($selectedValue))
                        . ',';
                    } else {
                        $fullQuery .= '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                        . Util::backquote(htmlspecialchars($selectedValue))
                        . ',';
                    }
                    if ($i == $selectedCount - 1) {
                        $fullQuery = preg_replace('@,$@', ');<br />', $fullQuery);
                    }
                    break;

                case 'drop_fld':
                    if ($fullQuery == '') {
                        $fullQuery .= 'ALTER TABLE '
                        . Util::backquote(htmlspecialchars($table));
                    }
                    $fullQuery .= '<br />&nbsp;&nbsp;DROP '
                    . Util::backquote(htmlspecialchars($selectedValue))
                    . ',';
                    if ($i == $selectedCount - 1) {
                        $fullQuery = preg_replace('@,$@', ';<br />', $fullQuery);
                    }
                    break;
            } // end switch
            $i++;
        }

        if ($what == 'drop_tbl') {
            if (!empty($fullQuery)) {
                $fullQuery .= ';<br />' . "\n";
            }
            if (!empty($fullQueryViews)) {
                $fullQuery .= $fullQueryViews . ';<br />' . "\n";
            }
            unset($fullQueryViews);
        }

        $fullQueryViews = isset($fullQueryViews) ? $fullQueryViews : null;

        return [$fullQuery, $reload, $fullQueryViews];
    }
}
