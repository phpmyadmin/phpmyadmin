<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used to generate table tracking
 *
 * @package PhpMyAdmin
 */

/**
 * Filters tracking entries
 *
 * @param array  $data           the entries to filter
 * @param string $filter_ts_from "from" date
 * @param string $filter_ts_to   "to" date
 * @param string $filter_users   users
 *
 * @return array filtered entries
 */
function PMA_filterTracking(
    $data, $filter_ts_from, $filter_ts_to, $filter_users
) {
    $tmp_entries = array();
    $id = 0;
    foreach ($data as $entry) {
        $timestamp = strtotime($entry['date']);

        if ($timestamp >= $filter_ts_from
            && $timestamp <= $filter_ts_to
            && (in_array('*', $filter_users) || in_array($entry['username'], $filter_users))
        ) {
            $tmp_entries[] = array(
                'id'        => $id,
                'timestamp' => $timestamp,
                'username'  => $entry['username'],
                'statement' => $entry['statement']
            );
        }
        $id++;
    }
    return($tmp_entries);
}

/**
 * Function to get html for data definition and data manipulation statements
 * 
 * @param string $url_query    url query
 * @param int    $last_version last version
 * 
 * @return string
 */
function PMA_getHtmlForDataDefinitionAndManipulationStatements($url_query,
    $last_version
) {
    $html = '<div id="div_create_version">';
    $html .= '<form method="post" action="tbl_tracking.php?' . $url_query . '">';
    $html .= PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
    $html .= '<fieldset>';
    $html .= '<legend>';
    $html .= printf(
        __('Create version %1$s of %2$s'),
        ($last_version + 1),
        htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
    );
    $html .= '</legend>';
    $html .= '<input type="hidden" name="version" value="' . ($last_version + 1)
        . '" />';
    $html .= '<p>' . __('Track these data definition statements:')
        . '</p>';
    $html .= '<input type="checkbox" name="alter_table" value="true"'
        . ' checked="checked" /> ALTER TABLE<br/>';
    $html .= '<input type="checkbox" name="rename_table" value="true"'
        . ' checked="checked" /> RENAME TABLE<br/>';
    $html .= '<input type="checkbox" name="create_table" value="true"'
        . ' checked="checked" /> CREATE TABLE<br/>';
    $html .= '<input type="checkbox" name="drop_table" value="true"'
        . ' checked="checked" /> DROP TABLE<br/>';
    $html .= '<br/>';
    $html .= '<input type="checkbox" name="create_index" value="true"'
        . ' checked="checked" /> CREATE INDEX<br/>';
    $html .= '<input type="checkbox" name="drop_index" value="true"'
        . ' checked="checked" /> DROP INDEX<br/>';
    $html .= '<p>' . __('Track these data manipulation statements:') . '</p>';
    $html .= '<input type="checkbox" name="insert" value="true"'
        . ' checked="checked" /> INSERT<br/>';
    $html .= '<input type="checkbox" name="update" value="true"'
        . ' checked="checked" /> UPDATE<br/>';
    $html .= '<input type="checkbox" name="delete" value="true"'
        . ' checked="checked" /> DELETE<br/>';
    $html .= '<input type="checkbox" name="truncate" value="true"'
        . ' checked="checked" /> TRUNCATE<br/>';
    $html .= '</fieldset>';

    $html .= '<fieldset class="tblFooters">';
    $html .= '<input type="hidden" name="submit_create_version" value="1" />';
    $html .= '<input type="submit" value="' . __('Create version') . '" />';
    $html .= '</fieldset>';

    $html .= '</form>';
    $html .= '</div>';

    return $html;
}

/**
 * Function to get html for activate tracking
 * 
 * @param string $url_query    url query
 * @param int    $last_version last version
 * 
 * @return string
 */
function PMA_getHtmlForActivateTracking($url_query, $last_version)
{
    $html = '<div id="div_activate_tracking">';
    $html .= '<form method="post" action="tbl_tracking.php?' . $url_query . '">';
    $html .= '<fieldset>';
    $html .= '<legend>';
    $html .= printf(
        __('Activate tracking for %s'),
        htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
    );
    $html .= '</legend>';
    $html .= '<input type="hidden" name="version" value="' . $last_version . '" />';
    $html .= '<input type="hidden" name="submit_activate_now" value="1" />';
    $html .= '<input type="submit" value="' . __('Activate now') . '" />';
    $html .= '</fieldset>';
    $html .= '</form>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Function to get html for deactivating tracking
 * 
 * @param string $url_query    url query
 * @param int    $last_version last version
 * 
 * @return string
 */
function PMA_getHtmlForDeactivateTracking($url_query, $last_version)
{
    $html = '<div id="div_deactivate_tracking">';
    $html .= '<form method="post" action="tbl_tracking.php?' . $url_query . '">';
    $html .= '<fieldset>';
    $html .= '<legend>';
    $html .= printf(
        __('Deactivate tracking for %s'),
        htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
    );
    $html .= '</legend>';
    $html .= '<input type="hidden" name="version" value="' . $last_version . '" />';
    $html .= '<input type="hidden" name="submit_deactivate_now" value="1" />';
    $html .= '<input type="submit" value="' . __('Deactivate now') . '" />';
    $html .= '</fieldset>';
    $html .= '</form>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Function to get the list versions of the table
 * 
 * @return array
 */
function PMA_getListOfVersionsOfTable()
{    
    $sql_query = " SELECT * FROM " .
         PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb']) . "." .
         PMA_Util::backquote($GLOBALS['cfg']['Server']['tracking']) .
         " WHERE db_name = '" . PMA_Util::sqlAddSlashes($_REQUEST['db']) . "' ".
         " AND table_name = '" . PMA_Util::sqlAddSlashes($_REQUEST['table']) ."' ".
         " ORDER BY version DESC ";

    return PMA_queryAsControlUser($sql_query);
}

/**
 * Function to get html for displaying last version number
 * 
 * @param array  $sql_result   sql result
 * @param int    $last_version last version
 * @param array  $url_params   url parameters
 * @param string $url_query    url query
 * 
 * @return string
 */
function PMA_getHtmlForTableVersionDetails($sql_result, $last_version, $url_params,
    $url_query
) {
    $html = '<table id="versions" class="data">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>' . __('Database') . '</th>';
    $html .= '<th>' . __('Table') . '</th>';
    $html .= '<th>' . __('Version') . '</th>';
    $html .= '<th>' . __('Created') . '</th>';
    $html .= '<th>' . __('Updated') . '</th>';
    $html .= '<th>' . __('Status') . '</th>';
    $html .= '<th>' . __('Show') . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    $style = 'odd';
    $GLOBALS['dbi']->dataSeek($sql_result, 0);
    while ($version = $GLOBALS['dbi']->fetchArray($sql_result)) {
        if ($version['tracking_active'] == 1) {
            $version_status = __('active');
        } else {
            $version_status = __('not active');
        }
        if ($version['version'] == $last_version) {
            if ($version['tracking_active'] == 1) {
                $tracking_active = true;
            } else {
                $tracking_active = false;
            }
        }
        $html .= '<tr class="noclick ' . $style . '">';
        $html .= '<td>' . htmlspecialchars($version['db_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($version['table_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($version['version']) . '</td>';
        $html .= '<td>' . htmlspecialchars($version['date_created']) . '</td>';
        $html .= '<td>' . htmlspecialchars($version['date_updated']) . '</td>';
        $html .= '<td>' . $version_status . '</td>';
        $html .= '<td><a href="tbl_tracking.php';
        $html .= PMA_URL_getCommon(
            $url_params + array(
                'report' => 'true', 'version' => $version['version']
            )
        );
        $html .= '">' . __('Tracking report') . '</a>';
        $html .= '| <a href="tbl_tracking.php';
        $html .= PMA_URL_getCommon(
            $url_params + array(
                'snapshot' => 'true', 'version' => $version['version']
            )
        );
        $html .= '">' . __('Structure snapshot') . '</a>';
        $html .= '</td>';
        $html .= '</tr>';

        if ($style == 'even') {
            $style = 'odd';
        } else {
            $style = 'even';
        }
    }

    $html .= '</tbody>';
    $html .= '</table>';

    if ($tracking_active) {
        $html .= PMA_getHtmlForDeactivateTracking($url_query, $last_version);
    } else {
        $html .= PMA_getHtmlForActivateTracking($url_query, $last_version);
    }
    
    return $html;
}

/**
 * Function to get the last version number of a table
 * 
 * @param array $sql_result sql result
 * 
 * @return int
 */
function PMA_getTableLastVersionNumber($sql_result)
{
    $maxversion = $GLOBALS['dbi']->fetchArray($sql_result);
    $last_version = $maxversion['version'];
    
    return $last_version;
}

/**
 * Function to get sql results for selectable tables
 * 
 * @return array
 */
function PMA_getSQLResultForSelectableTables()
{
    include_once 'libraries/relation.lib.php';
    
    $sql_query = " SELECT DISTINCT db_name, table_name FROM " .
             PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb']) . "." .
             PMA_Util::backquote($GLOBALS['cfg']['Server']['tracking']) .
             " WHERE db_name = '" . PMA_Util::sqlAddSlashes($GLOBALS['db']) . "' " .
             " ORDER BY db_name, table_name";

    return PMA_queryAsControlUser($sql_query);
}

/**
 * Function to get html for selectable table rows
 * 
 * @param array  $selectable_tables_sql_result sql results for selectable rows
 * @param string $url_query                    url query
 * 
 * @return string
 */
function PMA_getHtmlForSelectableTables($selectable_tables_sql_result, $url_query)
{
    $html = '<form method="post" action="tbl_tracking.php?' . $url_query . '">';
    $html .= '<select name="table">';
    while ($entries = $GLOBALS['dbi']->fetchArray($selectable_tables_sql_result)) {
        if (PMA_Tracker::isTracked($entries['db_name'], $entries['table_name'])) {
            $status = ' (' . __('active') . ')';
        } else {
            $status = ' (' . __('not active') . ')';
        }
        if ($entries['table_name'] == $_REQUEST['table']) {
            $s = ' selected="selected"';
        } else {
            $s = '';
        }
        $html .= '<option value="' . htmlspecialchars($entries['table_name'])
            . '"' . $s . '>' . htmlspecialchars($entries['db_name']) . ' . '
            . htmlspecialchars($entries['table_name']) . $status . '</option>'
            . "\n";
    }
    $html .= '</select>';
    $html .= '<input type="hidden" name="show_versions_submit" value="1" />';
    $html .= '<input type="submit" value="' . __('Show versions') . '" />';
    $html .= '</form>';
    
    return $html;
}

/**
 * Function to get html for tracking report and tracking report export
 * 
 * @param string $url_query        url query
 * @param array  $data             data
 * @param array  $url_params       url params
 * @param array  $selection_schema selection schema
 * @param array  $selection_data   selection data
 * @param bool   $selection_both   selection both
 * @param int    $filter_ts_to     filter time stamp from
 * @param int    $filter_ts_from   filter time stamp tp
 * @param array  $filter_users     filter users
 * 
 * @return string
 */
function PMA_getHtmlForTrackingReport($url_query, $data, $url_params,
    $selection_schema, $selection_data, $selection_both, $filter_ts_to,
    $filter_ts_from, $filter_users
) {
    $html .= '<h3>' . __('Tracking report')
        . '  [<a href="tbl_tracking.php?' . $url_query . '">' . __('Close')
        . '</a>]</h3>';

    $html .= '<small>' . __('Tracking statements') . ' '
        . htmlspecialchars($data['tracking']) . '</small><br/>';
    $html .= '<br/>';

    $html .= '<form method="post" action="tbl_tracking.php'
        . PMA_URL_getCommon(
            $url_params + array(
                'report' => 'true', 'version' => $_REQUEST['version']
            )
        )
        . '">';

    $str1 = '<select name="logtype">'
        . '<option value="schema"'
        . ($selection_schema ? ' selected="selected"' : '') . '>'
        . __('Structure only') . '</option>'
        . '<option value="data"'
        . ($selection_data ? ' selected="selected"' : ''). '>'
        . __('Data only') . '</option>'
        . '<option value="schema_and_data"'
        . ($selection_both ? ' selected="selected"' : '') . '>'
        . __('Structure and data') . '</option>'
        . '</select>';
    $str2 = '<input type="text" name="date_from" value="'
        . htmlspecialchars($_REQUEST['date_from']) . '" size="19" />';
    $str3 = '<input type="text" name="date_to" value="'
        . htmlspecialchars($_REQUEST['date_to']) . '" size="19" />';
    $str4 = '<input type="text" name="users" value="'
        . htmlspecialchars($_REQUEST['users']) . '" />';
    $str5 = '<input type="hidden" name="list_report" value="1" />'
      . '<input type="submit" value="' . __('Go') . '" />';

    $html .= printf(
        __('Show %1$s with dates from %2$s to %3$s by user %4$s %5$s'),
        $str1, $str2, $str3, $str4, $str5
    );

    // Prepare delete link content here
    $drop_image_or_text = '';
    if (PMA_Util::showIcons('ActionsLinksMode')) {
        $drop_image_or_text .= PMA_Util::getImage(
            'b_drop.png', __('Delete tracking data row from report')
        );
    }
    if (PMA_Util::showText('ActionLinksMode')) {
        $drop_image_or_text .= __('Delete');
    }

    /*
     *  First, list tracked data definition statements
     */
    $i = 1;
    if (count($data['ddlog']) == 0 && count($data['dmlog']) == 0) {
        $msg = PMA_Message::notice(__('No data'));
        $msg->display();
    }

    if ($selection_schema || $selection_both  && count($data['ddlog']) > 0) {
        $html .= '<table id="ddl_versions" class="data" width="100%">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th width="18">#</th>';
        $html .= '<th width="100">' . __('Date') . '</th>';
        $html .= '<th width="60">' . __('Username') . '</th>';
        $html .= '<th>' . __('Data definition statement') . '</th>';
        $html .= '<th>' . __('Delete') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $style = 'odd';
        foreach ($data['ddlog'] as $entry) {
            $statement  = PMA_Util::formatSql($entry['statement'], true);
            $timestamp = strtotime($entry['date']);

            if ($timestamp >= $filter_ts_from
                && $timestamp <= $filter_ts_to
                && (in_array('*', $filter_users) || in_array($entry['username'], $filter_users))
            ) {
                $html .= '<tr class="noclick ' . $style . '">';
                $html .= '<td><small>' . $i . '</small></td>';
                $html .= '<td><small>'
                    . htmlspecialchars($entry['date']) . '</small></td>';
                $html .= '<td><small>'
                    . htmlspecialchars($entry['username']) . '</small></td>';
                $html .= '<td>' . $statement . '</td>';
                $html .= '<td class="nowrap"><a href="tbl_tracking.php?'
                    . PMA_URL_getCommon(
                        $url_params + array(
                            'report' => 'true',
                            'version' => $_REQUEST['version'],
                            'delete_ddlog' => ($i - 1),
                        )
                    )
                    . '">' . $drop_image_or_text
                    . '</a></td>';
                $html .= '</tr>';

                if ($style == 'even') {
                    $style = 'odd';
                } else {
                    $style = 'even';
                }
                $i++;
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';

    } //endif

    // Memorize data definition amount
    $ddlog_count = $i;

    /*
     *  Secondly, list tracked data manipulation statements
     */

    if (($selection_data || $selection_both) && count($data['dmlog']) > 0) {
        $html .= '<table id="dml_versions" class="data" width="100%">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th width="18">#</th>';
        $html .= '<th width="100">' . __('Date') . '</th>';
        $html .= '<th width="60">' . __('Username') . '</th>';
        $html .= '<th>' . __('Data manipulation statement') . '</th>';
        $html .= '<th>' . __('Delete') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $style = 'odd';
        foreach ($data['dmlog'] as $entry) {
            $statement  = PMA_Util::formatSql($entry['statement'], true);
            $timestamp = strtotime($entry['date']);

            if ($timestamp >= $filter_ts_from
                && $timestamp <= $filter_ts_to
                && (in_array('*', $filter_users) || in_array($entry['username'], $filter_users))
            ) {
                $html .= '<tr class="noclick ' . $style . '">';
                $html .= '<td><small>' . $i . '</small></td>';
                $html .= '<td><small>'
                    . htmlspecialchars($entry['date']) . '</small></td>';
                $html .= '<td><small>'
                    . htmlspecialchars($entry['username']) . '</small></td>';
                $html .= '<td>' . $statement . '</td>';
                $html .= '<td class="nowrap"><a href="tbl_tracking.php?'
                    . PMA_URL_getCommon(
                        $url_params + array(
                            'report' => 'true',
                            'version' => $_REQUEST['version'],
                            'delete_dmlog' => ($i - $ddlog_count),
                        )
                    )
                    . '">'
                    . $drop_image_or_text
                    . '</a></td>';
                $html .= '</tr>';

                if ($style == 'even') {
                    $style = 'odd';
                } else {
                    $style = 'even';
                }
                $i++;
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
    }
    $html .= '</form>';
    $html .= '<form method="post" action="tbl_tracking.php'
        . PMA_URL_getCommon(
            $url_params + array(
                'report' => 'true', 'version' => $_REQUEST['version']
            )
        )
        . '">';
    $html .= printf(
        __('Show %1$s with dates from %2$s to %3$s by user %4$s %5$s'),
        $str1, $str2, $str3, $str4, $str5
    );

    $str_export1 =  '<select name="export_type">'
        . '<option value="sqldumpfile">' . __('SQL dump (file download)')
        . '</option>'
        . '<option value="sqldump">' . __('SQL dump') . '</option>'
        . '<option value="execution" onclick="alert(\''
        . PMA_escapeJsString(
            __('This option will replace your table and contained data.')
        )
        .'\')">' . __('SQL execution') . '</option>' . '</select>';

    $str_export2 = '<input type="hidden" name="report_export" value="1" />'
                 . '<input type="submit" value="' . __('Go') .'" />';
    $html .= '</form>';
    $html .= '<form class="disableAjax" method="post" action="tbl_tracking.php'
        . PMA_URL_getCommon(
            $url_params + array('report' => 'true', 'version' => $_REQUEST['version'])
        )
        . '">';
    $html .= '<input type="hidden" name="logtype" value="'
        . htmlspecialchars($_REQUEST['logtype']) . '" />';
    $html .= '<input type="hidden" name="date_from" value="'
        . htmlspecialchars($_REQUEST['date_from']) . '" />';
    $html .= '<input type="hidden" name="date_to" value="'
        . htmlspecialchars($_REQUEST['date_to']) . '" />';
    $html .= '<input type="hidden" name="users" value="'
        . htmlspecialchars($_REQUEST['users']) . '" />';
    $html .= "<br/>" . sprintf(__('Export as %s'), $str_export1)
        . $str_export2 . "<br/>";
    $html .= '</form>';
    $html .= "<br/><br/><hr/><br/>\n";
    
    return $html;
}

/**
 * Function to get html for schema snapshot
 * 
 * @param string $url_query url query
 * 
 * @return string
 */
function PMA_getHtmlForSchemaSnapshot($url_query)
{
    $html = '<h3>' . __('Structure snapshot')
        . '  [<a href="tbl_tracking.php?' . $url_query . '">' . __('Close')
        . '</a>]</h3>';
    $data = PMA_Tracker::getTrackedData(
        $_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version']
    );

    // Get first DROP TABLE/VIEW and CREATE TABLE/VIEW statements
    $drop_create_statements = $data['ddlog'][0]['statement'];

    if (strstr($data['ddlog'][0]['statement'], 'DROP TABLE')
        || strstr($data['ddlog'][0]['statement'], 'DROP VIEW')
    ) {
        $drop_create_statements .= $data['ddlog'][1]['statement'];
    }
    // Print SQL code
    $html .= PMA_Util::getMessage(
        sprintf(
            __('Version %s snapshot (SQL code)'),
            htmlspecialchars($_REQUEST['version'])
        ),
        $drop_create_statements
    );

    // Unserialize snapshot
    $temp = unserialize($data['schema_snapshot']);
    $columns = $temp['COLUMNS'];
    $indexes = $temp['INDEXES'];
    $html .= '<h3>' . __('Structure') . '</h3>';
    $html .= '<table id="tablestructure" class="data">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>' . __('Column') . '</th>';
    $html .= '<th>' . __('Type') . '</th>';
    $html .= '<th>' . __('Collation') . '</th>';
    $html .= '<th>' . __('Null') . '</th>';
    $html .= '<th>' . __('Default') . '</th>';
    $html .= '<th>' . __('Extra') . '</th>';
    $html .= '<th>' . __('Comment') . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $style = 'odd';
    foreach ($columns as $field_index => $field) {
        $html .= '<tr class="noclick ' . $style . '">';
        if ($field['Key'] == 'PRI') {
            $html .= '<td><b><u>' . htmlspecialchars($field['Field']) . '</u></b></td>';
        } else {
            $html .= '<td><b>' . htmlspecialchars($field['Field']) . '</b></td>';
        }
        $html .= "\n";
        $html .= '<td>' . htmlspecialchars($field['Type']) . '</td>';
        $html .= '<td>' . htmlspecialchars($field['Collation']) . '</td>';
        $html .= '<td>' . (($field['Null'] == 'YES') ? __('Yes') : __('No')) . '</td>';
        $html .= '<td>';
        if (isset($field['Default'])) {
            $extracted_columnspec = PMA_Util::extractColumnSpec($field['Type']);
            if ($extracted_columnspec['type'] == 'bit') {
                // here, $field['Default'] contains something like b'010'
                $html .= PMA_Util::convertBitDefaultValue($field['Default']);
            } else {
                $html .= htmlspecialchars($field['Default']);
            }
        } else {
            if ($field['Null'] == 'YES') {
                $html .= '<i>NULL</i>';
            } else {
                $html .= '<i>' . _pgettext('None for default', 'None') . '</i>';
            }
        }
        $html .= '</td>';
        $html .= '<td>' . htmlspecialchars($field['Extra']) . '</td>';
        $html .= '<td>' . htmlspecialchars($field['Comment']) . '</td>';
        $html .= '</tr>';

        if ($style == 'even') {
            $style = 'odd';
        } else {
            $style = 'even';
        }
    }

    $html .= '</tbody>';
    $html .= '</table>';

    if (count($indexes) > 0) {
        $html .= PMA_getHtmlForIndexes($indexes);
    } // endif
    $html .= '<br /><hr /><br />';
    
    return $html;
}

/**
 * Fuunction to get html for the indexes in schema snapshot
 * 
 * @param array $indexes indexes
 * 
 * @return string
 */
function PMA_getHtmlForIndexes($indexes)
{
    $html = '<h3>' . __('Indexes') . '</h3>';
    $html .= '<table id="tablestructure_indexes" class="data">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>' . __('Keyname') . '</th>';
    $html .= '<th>' . __('Type') . '</th>';
    $html .= '<th>' . __('Unique') . '</th>';
    $html .= '<th>' . __('Packed') . '</th>';
    $html .= '<th>' . __('Column') . '</th>';
    $html .= '<th>' . __('Cardinality') . '</th>';
    $html .= '<th>' . __('Collation') . '</th>';
    $html .= '<th>' . __('Null') . '</th>';
    $html .= '<th>' . __('Comment') . '</th>';
    $html .= '</tr>';
    $html .= '<tbody>';

    $style = 'odd';
    foreach ($indexes as $indexes_index => $index) {
        if ($index['Non_unique'] == 0) {
            $str_unique = __('Yes');
        } else {
            $str_unique = __('No');
        }
        if ($index['Packed'] != '') {
            $str_packed = __('Yes');
        } else {
            $str_packed = __('No');
        }

        $html .= '<tr class="noclick ' . $style . '">';
        $html .= '<td><b>' . htmlspecialchars($index['Key_name']) . '</b></td>';
        $html .= '<td>' . htmlspecialchars($index['Index_type']) . '</td>';
        $html .= '<td>' . $str_unique . '</td>';
        $html .= '<td>' . $str_packed . '</td>';
        $html .= '<td>' . htmlspecialchars($index['Column_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($index['Cardinality']) . '</td>';
        $html .= '<td>' . htmlspecialchars($index['Collation']) . '</td>';
        $html .= '<td>' . htmlspecialchars($index['Null']) . '</td>';
        $html .= '<td>' . htmlspecialchars($index['Comment']) . '</td>';
        $html .= '</tr>';

        if ($style == 'even') {
            $style = 'odd';
        } else {
            $style = 'even';
        }
    }
    $html .= '</tbody>';
    $html .= '</table>';
    return $html;
}
?>
