<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used for database and table tracking
 *
 * @package PhpMyAdmin
 */

/**
 * Filters tracking entries
 *
 * @param array  $data           the entries to filter
 * @param string $filter_ts_from "from" date
 * @param string $filter_ts_to   "to" date
 * @param array  $filter_users   users
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
        $filtered_user = in_array($entry['username'], $filter_users);
        if ($timestamp >= $filter_ts_from
            && $timestamp <= $filter_ts_to
            && (in_array('*', $filter_users) || $filtered_user)
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
 * @param string $db           database
 * @param array  $selected     selected tables
 * @param string $type         type of the table; table, view or both
 *
 * @return string
 */
function PMA_getHtmlForDataDefinitionAndManipulationStatements($url_query,
    $last_version, $db, $selected, $type = 'both'
) {
    $html  = '<div id="div_create_version">';
    $html .= '<form method="post" action="' . $url_query . '">';
    $html .= PMA_URL_getHiddenInputs($db);
    foreach ($selected as $selected_table) {
        $html .= '<input type="hidden" name="selected[]"'
            . ' value="' . htmlspecialchars($selected_table) . '" />';
    }

    $html .= '<fieldset>';
    $html .= '<legend>';
    if (count($selected) == 1) {
        $html .= sprintf(
            __('Create version %1$s of %2$s'),
            ($last_version + 1),
            htmlspecialchars($db . '.' . $selected[0])
        );
    } else {
        $html .= sprintf(__('Create version %1$s'), ($last_version + 1));
    }
    $html .= '</legend>';
    $html .= '<input type="hidden" name="version" value="' . ($last_version + 1)
        . '" />';
    $html .= '<p>' . __('Track these data definition statements:')
        . '</p>';

    if ($type == 'both' || $type == 'table') {
        $html .= '<input type="checkbox" name="alter_table" value="true"'
            . (/*overload*/mb_stripos(
                $GLOBALS['cfg']['Server']['tracking_default_statements'],
                'ALTER TABLE'
            ) !== false ? ' checked="checked"' : '')
            . ' /> ALTER TABLE<br/>';
        $html .= '<input type="checkbox" name="rename_table" value="true"'
            . (/*overload*/mb_stripos(
                $GLOBALS['cfg']['Server']['tracking_default_statements'],
                'RENAME TABLE'
            ) !== false ? ' checked="checked"' : '')
            . ' /> RENAME TABLE<br/>';
        $html .= '<input type="checkbox" name="create_table" value="true"'
            . (/*overload*/mb_stripos(
                $GLOBALS['cfg']['Server']['tracking_default_statements'],
                'CREATE TABLE'
            ) !== false ? ' checked="checked"' : '')
            . ' /> CREATE TABLE<br/>';
        $html .= '<input type="checkbox" name="drop_table" value="true"'
            . (/*overload*/mb_stripos(
                $GLOBALS['cfg']['Server']['tracking_default_statements'],
                'DROP TABLE'
            ) !== false ? ' checked="checked"' : '')
            . ' /> DROP TABLE<br/>';
    }
    if ($type == 'both') {
        $html .= '<br/>';
    }
    if ($type == 'both' || $type == 'view') {
        $html .= '<input type="checkbox" name="alter_view" value="true"'
            . (/*overload*/mb_stripos(
                $GLOBALS['cfg']['Server']['tracking_default_statements'],
                'ALTER VIEW'
            ) !== false ? ' checked="checked"' : '')
            . ' /> ALTER VIEW<br/>';
        $html .= '<input type="checkbox" name="create_view" value="true"'
            . (/*overload*/mb_stripos(
                $GLOBALS['cfg']['Server']['tracking_default_statements'],
                'CREATE VIEW'
            ) !== false ? ' checked="checked"' : '')
            . ' /> CREATE VIEW<br/>';
        $html .= '<input type="checkbox" name="drop_view" value="true"'
            . (/*overload*/mb_stripos(
                $GLOBALS['cfg']['Server']['tracking_default_statements'],
                'DROP VIEW'
            ) !== false ? ' checked="checked"' : '')
            . ' /> DROP VIEW<br/>';
    }
    $html .= '<br/>';

    $html .= '<input type="checkbox" name="create_index" value="true"'
        . (/*overload*/mb_stripos(
            $GLOBALS['cfg']['Server']['tracking_default_statements'],
            'CREATE INDEX'
        ) !== false ? ' checked="checked"' : '')
        . ' /> CREATE INDEX<br/>';
    $html .= '<input type="checkbox" name="drop_index" value="true"'
        . (/*overload*/mb_stripos(
            $GLOBALS['cfg']['Server']['tracking_default_statements'],
            'DROP INDEX'
        ) !== false ? ' checked="checked"' : '')
        . ' /> DROP INDEX<br/>';
    $html .= '<p>' . __('Track these data manipulation statements:') . '</p>';
    $html .= '<input type="checkbox" name="insert" value="true"'
        . (/*overload*/mb_stripos(
            $GLOBALS['cfg']['Server']['tracking_default_statements'],
            'INSERT'
        ) !== false ? ' checked="checked"' : '')
        . ' /> INSERT<br/>';
    $html .= '<input type="checkbox" name="update" value="true"'
        . (/*overload*/mb_stripos(
            $GLOBALS['cfg']['Server']['tracking_default_statements'],
            'UPDATE'
        ) !== false ? ' checked="checked"' : '')
        . ' /> UPDATE<br/>';
    $html .= '<input type="checkbox" name="delete" value="true"'
        . (/*overload*/mb_stripos(
            $GLOBALS['cfg']['Server']['tracking_default_statements'],
            'DELETE'
        ) !== false ? ' checked="checked"' : '')
        . ' /> DELETE<br/>';
    $html .= '<input type="checkbox" name="truncate" value="true"'
        . (/*overload*/mb_stripos(
            $GLOBALS['cfg']['Server']['tracking_default_statements'],
            'TRUNCATE'
        ) !== false ? ' checked="checked"' : '')
        . ' /> TRUNCATE<br/>';
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
 * Function to get html for activate/deactivate tracking
 *
 * @param string $action       activate|deactivate
 * @param string $url_query    url query
 * @param int    $last_version last version
 *
 * @return string
 */
function PMA_getHtmlForActivateDeactivateTracking(
    $action, $url_query, $last_version
) {
    $html = '<div>';
    $html .= '<form method="post" action="tbl_tracking.php' . $url_query . '">';
    $html .= '<fieldset>';
    $html .= '<legend>';

    switch($action) {
    case 'activate':
        $legend = __('Activate tracking for %s');
        $value = "activate_now";
        $button = __('Activate now');
        break;
    case 'deactivate':
        $legend = __('Deactivate tracking for %s');
        $value = "deactivate_now";
        $button = __('Deactivate now');
        break;
    default:
        $legend = '';
        $value = '';
        $button = '';
    }

    $html .= sprintf(
        $legend,
        htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
    );
    $html .= '</legend>';
    $html .= '<input type="hidden" name="version" value="' . $last_version . '" />';
    $html .= '<input type="hidden" name="toggle_activation" value="' . $value
        . '" />';
    $html .= '<input type="submit" value="' . $button . '" />';
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
    $cfgRelation = PMA_getRelationsParam();
    $sql_query = " SELECT * FROM " .
         PMA_Util::backquote($cfgRelation['db']) . "." .
         PMA_Util::backquote($cfgRelation['tracking']) .
         " WHERE db_name = '" . PMA_Util::sqlAddSlashes($_REQUEST['db']) . "' " .
         " AND table_name = '" . PMA_Util::sqlAddSlashes($_REQUEST['table']) . "' " .
         " ORDER BY version DESC ";

    return PMA_queryAsControlUser($sql_query);
}

/**
 * Function to get html for displaying last version number
 *
 * @param array  $sql_result    sql result
 * @param int    $last_version  last version
 * @param array  $url_params    url parameters
 * @param string $url_query     url query
 * @param string $pmaThemeImage path to theme's image folder
 * @param string $text_dir      text direction
 *
 * @return string
 */
function PMA_getHtmlForTableVersionDetails(
    $sql_result, $last_version, $url_params,
    $url_query, $pmaThemeImage, $text_dir
) {
    $tracking_active = false;

    $html  = '<form method="post" action="tbl_tracking.php" name="versionsForm"'
        . ' id="versionsForm" class="ajax">';
    $html .= PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
    $html .= '<table id="versions" class="data">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th></th>';
    $html .= '<th>' . __('Version') . '</th>';
    $html .= '<th>' . __('Created') . '</th>';
    $html .= '<th>' . __('Updated') . '</th>';
    $html .= '<th>' . __('Status') . '</th>';
    $html .= '<th>' . __('Action') . '</th>';
    $html .= '<th>' . __('Show') . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    $style = 'odd';
    $GLOBALS['dbi']->dataSeek($sql_result, 0);
    $delete = PMA_Util::getIcon('b_drop.png', __('Delete version'));
    $report = PMA_Util::getIcon('b_report.png', __('Tracking report'));
    $structure = PMA_Util::getIcon('b_props.png', __('Structure snapshot'));

    while ($version = $GLOBALS['dbi']->fetchArray($sql_result)) {
        if ($version['version'] == $last_version) {
            if ($version['tracking_active'] == 1) {
                $tracking_active = true;
            } else {
                $tracking_active = false;
            }
        }
        $delete_link = 'tbl_tracking.php' . $url_query . '&amp;version='
            . htmlspecialchars($version['version'])
            . '&amp;submit_delete_version=true';
        $checkbox_id = 'selected_versions_' . htmlspecialchars($version['version']);

        $html .= '<tr class="' . $style . '">';
        $html .= '<td class="center">';
        $html .= '<input type="checkbox" name="selected_versions[]"'
            . ' class="checkall" id="' . $checkbox_id . '"'
            . ' value="' . htmlspecialchars($version['version']) . '"/>';
        $html .= '</td>';
        $html .= '<th class="floatright">';
        $html .= '<label for="' . $checkbox_id . '">'
            . htmlspecialchars($version['version']) . '</label>';
        $html .= '</th>';
        $html .= '<td>' . htmlspecialchars($version['date_created']) . '</td>';
        $html .= '<td>' . htmlspecialchars($version['date_updated']) . '</td>';
        $html .= '<td>' . PMA_getVersionStatus($version) . '</td>';
        $html .= '<td><a class="delete_version_anchor ajax"'
            . ' href="' . $delete_link . '" >' . $delete . '</a></td>';
        $html .= '<td><a href="tbl_tracking.php';
        $html .= PMA_URL_getCommon(
            $url_params + array(
                'report' => 'true', 'version' => $version['version']
            )
        );
        $html .= '">' . $report . '</a>';
        $html .= '&nbsp;&nbsp;';
        $html .= '<a href="tbl_tracking.php';
        $html .= PMA_URL_getCommon(
            $url_params + array(
                'snapshot' => 'true', 'version' => $version['version']
            )
        );
        $html .= '">' . $structure . '</a>';
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

    $html .= PMA_Util::getWithSelected($pmaThemeImage, $text_dir, "versionsForm");
    $html .= PMA_Util::getButtonOrImage(
        'submit_mult', 'mult_submit', 'submit_mult_delete_version',
        __('Delete version'), 'b_drop.png', 'delete_version'
    );

    $html .= '</form>';

    if ($tracking_active) {
        $html .= PMA_getHtmlForActivateDeactivateTracking(
            'deactivate', $url_query, $last_version
        );
    } else {
        $html .= PMA_getHtmlForActivateDeactivateTracking(
            'activate', $url_query, $last_version
        );
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
    $cfgRelation = PMA_getRelationsParam();

    $sql_query = " SELECT DISTINCT db_name, table_name FROM " .
             PMA_Util::backquote($cfgRelation['db']) . "." .
             PMA_Util::backquote($cfgRelation['tracking']) .
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
    $html = '<form method="post" action="tbl_tracking.php' . $url_query . '">';
    $html .= '<select name="table" class="autosubmit">';
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
    $html .= '</form>';

    return $html;
}

/**
 * Function to get html for tracking report and tracking report export
 *
 * @param string  $url_query        url query
 * @param array   $data             data
 * @param array   $url_params       url params
 * @param boolean $selection_schema selection schema
 * @param boolean $selection_data   selection data
 * @param boolean $selection_both   selection both
 * @param int     $filter_ts_to     filter time stamp from
 * @param int     $filter_ts_from   filter time stamp tp
 * @param array   $filter_users     filter users
 *
 * @return string
 */
function PMA_getHtmlForTrackingReport($url_query, $data, $url_params,
    $selection_schema, $selection_data, $selection_both, $filter_ts_to,
    $filter_ts_from, $filter_users
) {
    $html = '<h3>' . __('Tracking report')
        . '  [<a href="tbl_tracking.php' . $url_query . '">' . __('Close')
        . '</a>]</h3>';

    $html .= '<small>' . __('Tracking statements') . ' '
        . htmlspecialchars($data['tracking']) . '</small><br/>';
    $html .= '<br/>';

    list($str1, $str2, $str3, $str4, $str5) = PMA_getHtmlForElementsOfTrackingReport(
        $selection_schema, $selection_data, $selection_both
    );

    // Prepare delete link content here
    $drop_image_or_text = '';
    if (PMA_Util::showIcons('ActionLinksMode')) {
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
    if (count($data['ddlog']) == 0 && count($data['dmlog']) == 0) {
        $msg = PMA_Message::notice(__('No data'));
        $msg->display();
    }

    $html .= PMA_getHtmlForTrackingReportExportForm1(
        $data, $url_params, $selection_schema, $selection_data, $selection_both,
        $filter_ts_to, $filter_ts_from, $filter_users, $str1, $str2, $str3,
        $str4, $str5, $drop_image_or_text
    );

    $html .= PMA_getHtmlForTrackingReportExportForm2(
        $url_params, $str1, $str2, $str3, $str4, $str5
    );

    $html .= "<br/><br/><hr/><br/>\n";

    return $html;
}

/**
 * Generate HTML element for report form
 *
 * @param boolean $selection_schema selection schema
 * @param boolean $selection_data   selection data
 * @param boolean $selection_both   selection both
 *
 * @return array
 */
function PMA_getHtmlForElementsOfTrackingReport(
    $selection_schema, $selection_data, $selection_both
) {
    $str1 = '<select name="logtype">'
        . '<option value="schema"'
        . ($selection_schema ? ' selected="selected"' : '') . '>'
        . __('Structure only') . '</option>'
        . '<option value="data"'
        . ($selection_data ? ' selected="selected"' : '') . '>'
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
    return array($str1, $str2, $str3, $str4, $str5);
}

/**
 * Generate HTML for export form
 *
 * @param array   $data               data
 * @param array   $url_params         url params
 * @param boolean $selection_schema   selection schema
 * @param boolean $selection_data     selection data
 * @param boolean $selection_both     selection both
 * @param int     $filter_ts_to       filter time stamp from
 * @param int     $filter_ts_from     filter time stamp tp
 * @param array   $filter_users       filter users
 * @param string  $str1               HTML for logtype select
 * @param string  $str2               HTML for "from date"
 * @param string  $str3               HTML for "to date"
 * @param string  $str4               HTML for user
 * @param string  $str5               HTML for "list report"
 * @param string  $drop_image_or_text HTML for image or text
 *
 * @return string HTML for form
 */
function PMA_getHtmlForTrackingReportExportForm1(
    $data, $url_params, $selection_schema, $selection_data, $selection_both,
    $filter_ts_to, $filter_ts_from, $filter_users, $str1, $str2, $str3,
    $str4, $str5, $drop_image_or_text
) {
    $ddlog_count = 0;

    $html = '<form method="post" action="tbl_tracking.php'
        . PMA_URL_getCommon(
            $url_params + array(
                'report' => 'true', 'version' => $_REQUEST['version']
            )
        )
        . '">';

    $html .= sprintf(
        __('Show %1$s with dates from %2$s to %3$s by user %4$s %5$s'),
        $str1, $str2, $str3, $str4, $str5
    );

    if ($selection_schema || $selection_both && count($data['ddlog']) > 0) {
        list($temp, $ddlog_count) = PMA_getHtmlForDataDefinitionStatements(
            $data, $filter_users, $filter_ts_from, $filter_ts_to, $url_params,
            $drop_image_or_text
        );
        $html .= $temp;
        unset($temp);
    } //endif

    /*
     *  Secondly, list tracked data manipulation statements
     */
    if (($selection_data || $selection_both) && count($data['dmlog']) > 0) {
        $html .= PMA_getHtmlForDataManipulationStatements(
            $data, $filter_users, $filter_ts_from, $filter_ts_to, $url_params,
            $ddlog_count, $drop_image_or_text
        );
    }
    $html .= '</form>';
    return $html;
}

/**
 * Generate HTML for export form
 *
 * @param array  $url_params Parameters
 * @param string $str1       HTML for logtype select
 * @param string $str2       HTML for "from date"
 * @param string $str3       HTML for "to date"
 * @param string $str4       HTML for user
 * @param string $str5       HTML for "list report"
 *
 * @return string HTML for form
 */
function PMA_getHtmlForTrackingReportExportForm2(
    $url_params, $str1, $str2, $str3, $str4, $str5
) {
    $html = '<form method="post" action="tbl_tracking.php'
        . PMA_URL_getCommon(
            $url_params + array(
                'report' => 'true', 'version' => $_REQUEST['version']
            )
        )
        . '">';
    $html .= sprintf(
        __('Show %1$s with dates from %2$s to %3$s by user %4$s %5$s'),
        $str1, $str2, $str3, $str4, $str5
    );
    $html .= '</form>';

    $html .= '<form class="disableAjax" method="post" action="tbl_tracking.php'
        . PMA_URL_getCommon(
            $url_params
            + array('report' => 'true', 'version' => $_REQUEST['version'])
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

    $str_export1 = '<select name="export_type">'
        . '<option value="sqldumpfile">' . __('SQL dump (file download)')
        . '</option>'
        . '<option value="sqldump">' . __('SQL dump') . '</option>'
        . '<option value="execution" onclick="alert(\''
        . PMA_escapeJsString(
            __('This option will replace your table and contained data.')
        )
        . '\')">' . __('SQL execution') . '</option>' . '</select>';

    $str_export2 = '<input type="hidden" name="report_export" value="1" />'
        . '<input type="submit" value="' . __('Go') . '" />';

    $html .= "<br/>" . sprintf(__('Export as %s'), $str_export1)
        . $str_export2 . "<br/>";
    $html .= '</form>';
    return $html;
}

/**
 * Function to get html for data manipulation statements
 *
 * @param array  $data               data
 * @param array  $filter_users       filter users
 * @param int    $filter_ts_from     filter time staml from
 * @param int    $filter_ts_to       filter time stamp to
 * @param array  $url_params         url parameters
 * @param int    $ddlog_count        data definition log count
 * @param string $drop_image_or_text drop image or text
 *
 * @return string
 */
function PMA_getHtmlForDataManipulationStatements($data, $filter_users,
    $filter_ts_from, $filter_ts_to, $url_params, $ddlog_count,
    $drop_image_or_text
) {
    // no need for the secondth returned parameter
    list($html,) = PMA_getHtmlForDataStatements(
        $data, $filter_users, $filter_ts_from, $filter_ts_to, $url_params,
        $drop_image_or_text, 'dmlog', __('Data manipulation statement'),
        $ddlog_count, 'dml_versions'
    );

    return $html;
}

/**
 * Function to get html for one data manipulation statement
 *
 * @param array  $entry              entry
 * @param array  $filter_users       filter users
 * @param int    $filter_ts_from     filter time stamp from
 * @param int    $filter_ts_to       filter time stamp to
 * @param string $style              style
 * @param int    $line_number        line number
 * @param array  $url_params         url parameters
 * @param int    $offset             line number offset
 * @param string $drop_image_or_text drop image or text
 * @param string $delete_param       parameter for delete
 *
 * @return string
 */
function PMA_getHtmlForOneStatement($entry, $filter_users,
    $filter_ts_from, $filter_ts_to, $style, $line_number, $url_params, $offset,
    $drop_image_or_text, $delete_param
) {
    $statement  = PMA_Util::formatSql($entry['statement'], true);
    $timestamp = strtotime($entry['date']);
    $filtered_user = in_array($entry['username'], $filter_users);
    $html = null;

    if ($timestamp >= $filter_ts_from
        && $timestamp <= $filter_ts_to
        && (in_array('*', $filter_users) || $filtered_user)
    ) {
        $html = '<tr class="noclick ' . $style . '">';
        $html .= '<td class="right"><small>' . $line_number . '</small></td>';
        $html .= '<td><small>'
            . htmlspecialchars($entry['date']) . '</small></td>';
        $html .= '<td><small>'
            . htmlspecialchars($entry['username']) . '</small></td>';
        $html .= '<td>' . $statement . '</td>';
        $html .= '<td class="nowrap"><a  class="delete_entry_anchor ajax"'
            . ' href="tbl_tracking.php'
            . PMA_URL_getCommon(
                $url_params + array(
                    'report' => 'true',
                    'version' => $_REQUEST['version'],
                    $delete_param => ($line_number - $offset),
                )
            )
            . '">'
            . $drop_image_or_text
            . '</a></td>';
        $html .= '</tr>';
    }

    return $html;
}
/**
 * Function to get html for data definition statements in schema snapshot
 *
 * @param array  $data               data
 * @param array  $filter_users       filter users
 * @param int    $filter_ts_from     filter time stamp from
 * @param int    $filter_ts_to       filter time stamp to
 * @param array  $url_params         url parameters
 * @param string $drop_image_or_text drop image or text
 *
 * @return array
 */
function PMA_getHtmlForDataDefinitionStatements($data, $filter_users,
    $filter_ts_from, $filter_ts_to, $url_params, $drop_image_or_text
) {
    list($html, $line_number) = PMA_getHtmlForDataStatements(
        $data, $filter_users, $filter_ts_from, $filter_ts_to, $url_params,
        $drop_image_or_text, 'ddlog', __('Data definition statement'),
        1, 'ddl_versions'
    );

    return array($html, $line_number);
}

/**
 * Function to get html for data statements in schema snapshot
 *
 * @param array  $data               data
 * @param array  $filter_users       filter users
 * @param int    $filter_ts_from     filter time stamp from
 * @param int    $filter_ts_to       filter time stamp to
 * @param array  $url_params         url parameters
 * @param string $drop_image_or_text drop image or text
 * @param string $which_log          dmlog|ddlog
 * @param string $header_message     message for this section
 * @param int    $line_number        line number
 * @param string $table_id           id for the table element
 *
 * @return array
 */
function PMA_getHtmlForDataStatements($data, $filter_users,
    $filter_ts_from, $filter_ts_to, $url_params, $drop_image_or_text,
    $which_log, $header_message, $line_number, $table_id
) {
    $offset = $line_number;
    $html  = '<table id="' . $table_id . '" class="data" width="100%">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th width="18">#</th>';
    $html .= '<th width="100">' . __('Date') . '</th>';
    $html .= '<th width="60">' . __('Username') . '</th>';
    $html .= '<th>' . $header_message . '</th>';
    $html .= '<th>' . __('Action') . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    $style = 'odd';
    foreach ($data[$which_log] as $entry) {
        $html .= PMA_getHtmlForOneStatement(
            $entry, $filter_users, $filter_ts_from, $filter_ts_to, $style,
            $line_number, $url_params, $offset, $drop_image_or_text,
            'delete_' . $which_log
        );
        if ($style == 'even') {
            $style = 'odd';
        } else {
            $style = 'even';
        }
        $line_number++;
    }
    $html .= '</tbody>';
    $html .= '</table>';

    return array($html, $line_number);
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
        . '  [<a href="tbl_tracking.php' . $url_query . '">' . __('Close')
        . '</a>]</h3>';
    $data = PMA_Tracker::getTrackedData(
        $_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version']
    );

    // Get first DROP TABLE/VIEW and CREATE TABLE/VIEW statements
    $drop_create_statements = $data['ddlog'][0]['statement'];

    if (/*overload*/mb_strstr($data['ddlog'][0]['statement'], 'DROP TABLE')
        || /*overload*/mb_strstr($data['ddlog'][0]['statement'], 'DROP VIEW')
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
    $html .= PMA_getHtmlForColumns($columns);

    if (count($indexes) > 0) {
        $html .= PMA_getHtmlForIndexes($indexes);
    } // endif
    $html .= '<br /><hr /><br />';

    return $html;
}

/**
 * Function to get html for displaying columns in the schema snapshot
 *
 * @param array $columns columns
 *
 * @return string
 */
function PMA_getHtmlForColumns($columns)
{
    $html = '<h3>' . __('Structure') . '</h3>';
    $html .= '<table id="tablestructure" class="data">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>' . __('#') . '</th>';
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
    $index = 1;
    foreach ($columns as $field) {
        $html .= PMA_getHtmlForField($index++, $field, $style);
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

/**
 * Function to get html for field
 *
 * @param int    $index index
 * @param array  $field field
 * @param string $style style
 *
 * @return string
 */
function PMA_getHtmlForField($index, $field, $style)
{
    $html = '<tr class="noclick ' . $style . '">';
    $html .= '<td>' . $index . '</td>';
    $html .= '<td><b>' . htmlspecialchars($field['Field']);
    if ($field['Key'] == 'PRI') {
        $html .= ' ' . PMA_Util::getImage(
            'b_primary.png', __('Primary')
        );
    } elseif (! empty($field['Key'])) {
        $html .= ' ' . PMA_Util::getImage(
            'bd_primary.png', __('Index')
        );
    }
    $html .= '</b></td>';
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

    return $html;
}

/**
 * Function to get html for the indexes in schema snapshot
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
    foreach ($indexes as $index) {
        $html .= PMA_getHtmlForIndex($index, $style);
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

/**
 * Function to get html for an index in schema snapshot
 *
 * @param array  $index index
 * @param string $style style
 *
 * @return string
 */
function PMA_getHtmlForIndex($index, $style)
{
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

    $html  = '<tr class="noclick ' . $style . '">';
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

    return $html;
}

/**
 * Function to handle the tracking report
 *
 * @param array &$data tracked data
 *
 * @return string HTML for the message
 */
function PMA_deleteTrackingReportRows(&$data)
{
    $html = '';
    if (isset($_REQUEST['delete_ddlog'])) {
        // Delete ddlog row data
        $html .= PMA_deleteFromTrackingReportLog(
            $data,
            'ddlog',
            'DDL',
            __('Tracking data definition successfully deleted')
        );
    }

    if (isset($_REQUEST['delete_dmlog'])) {
        // Delete dmlog row data
        $html .= PMA_deleteFromTrackingReportLog(
            $data,
            'dmlog',
            'DML',
            __('Tracking data manipulation successfully deleted')
        );
    }
    return $html;
}

/**
 * Function to delete from a tracking report log
 *
 * @param array  &$data     tracked data
 * @param string $which_log ddlog|dmlog
 * @param string $type      DDL|DML
 * @param string $message   success message
 *
 * @return string HTML for the message
 */
function PMA_deleteFromTrackingReportLog(&$data, $which_log, $type, $message)
{
    $html = '';
    $delete_id = $_REQUEST['delete_' . $which_log];

    // Only in case of valid id
    if ($delete_id == (int)$delete_id) {
        unset($data[$which_log][$delete_id]);

        $successfullyDeleted = PMA_Tracker::changeTrackingData(
            $_REQUEST['db'],
            $_REQUEST['table'],
            $_REQUEST['version'],
            $type,
            $data[$which_log]
        );
        if ($successfullyDeleted) {
            $msg = PMA_Message::success($message);
        } else {
            $msg = PMA_Message::rawError(__('Query error'));
        }
        $html .= $msg->getDisplay();
    }
    return $html;
}

/**
 * Function to export as sql dump
 *
 * @param array $entries entries
 *
 * @return string HTML SQL query form
 */
function PMA_exportAsSQLDump($entries)
{
    $html = '';
    $new_query = "# "
        . __(
            'You can execute the dump by creating and using a temporary database. '
            . 'Please ensure that you have the privileges to do so.'
        )
        . "\n"
        . "# " . __('Comment out these two lines if you do not need them.') . "\n"
        . "\n"
        . "CREATE database IF NOT EXISTS pma_temp_db; \n"
        . "USE pma_temp_db; \n"
        . "\n";

    foreach ($entries as $entry) {
        $new_query .= $entry['statement'];
    }
    $msg = PMA_Message::success(
        __('SQL statements exported. Please copy the dump or execute it.')
    );
    $html .= $msg->getDisplay();

    $db_temp = $GLOBALS['db'];
    $table_temp = $GLOBALS['table'];

    $GLOBALS['db'] = $GLOBALS['table'] = '';
    include_once './libraries/sql_query_form.lib.php';

    $html .= PMA_getHtmlForSqlQueryForm($new_query, 'sql');

    $GLOBALS['db'] = $db_temp;
    $GLOBALS['table'] = $table_temp;

    return $html;
}

/**
 * Function to export as sql execution
 *
 * @param array $entries entries
 *
 * @return array
 */
function PMA_exportAsSQLExecution($entries)
{
    $sql_result = array();
    foreach ($entries as $entry) {
        $sql_result = $GLOBALS['dbi']->query("/*NOTRACK*/\n" . $entry['statement']);
    }

    return $sql_result;
}

/**
 * Function to export as entries
 *
 * @param array $entries entries
 *
 * @return void
 */
function PMA_exportAsFileDownload($entries)
{
    @ini_set('url_rewriter.tags', '');

    $dump = "# " . sprintf(
        __('Tracking report for table `%s`'), htmlspecialchars($_REQUEST['table'])
    )
    . "\n" . "# " . date('Y-m-d H:i:s') . "\n";
    foreach ($entries as $entry) {
        $dump .= $entry['statement'];
    }
    $filename = 'log_' . htmlspecialchars($_REQUEST['table']) . '.sql';
    PMA_Response::getInstance()->disable();
    PMA_downloadHeader(
        $filename,
        'text/x-sql',
        /*overload*/mb_strlen($dump)
    );
    echo $dump;

    exit();
}

/**
 * Function to activate tracking
 *
 * @return string HTML for the success message
 */
function PMA_activateTracking()
{
    $html = '';
    $activated = PMA_Tracker::activateTracking(
        $GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version']
    );
    if ($activated) {
        $msg = PMA_Message::success(
            sprintf(
                __('Tracking for %1$s was activated at version %2$s.'),
                htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table']),
                htmlspecialchars($_REQUEST['version'])
            )
        );
        $html .= $msg->getDisplay();
    }

    return $html;
}

/**
 * Function to deactivate tracking
 *
 * @return string HTML of the success message
 */
function PMA_deactivateTracking()
{
    $html = '';
    $deactivated = PMA_Tracker::deactivateTracking(
        $GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version']
    );
    if ($deactivated) {
        $msg = PMA_Message::success(
            sprintf(
                __('Tracking for %1$s was deactivated at version %2$s.'),
                htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table']),
                htmlspecialchars($_REQUEST['version'])
            )
        );
        $html .= $msg->getDisplay();
    }

    return $html;
}

/**
 * Function to get tracking set
 *
 * @return string
 */
function PMA_getTrackingSet()
{
    $tracking_set = '';

    // a key is absent from the request if it has been removed from
    // tracking_default_statements in the config
    if (isset($_REQUEST['alter_table']) && $_REQUEST['alter_table'] == true) {
        $tracking_set .= 'ALTER TABLE,';
    }
    if (isset($_REQUEST['rename_table']) && $_REQUEST['rename_table'] == true) {
        $tracking_set .= 'RENAME TABLE,';
    }
    if (isset($_REQUEST['create_table']) && $_REQUEST['create_table'] == true) {
        $tracking_set .= 'CREATE TABLE,';
    }
    if (isset($_REQUEST['drop_table']) && $_REQUEST['drop_table'] == true) {
        $tracking_set .= 'DROP TABLE,';
    }
    if (isset($_REQUEST['alter_view']) && $_REQUEST['alter_view'] == true) {
        $tracking_set .= 'ALTER VIEW,';
    }
    if (isset($_REQUEST['create_view']) && $_REQUEST['create_view'] == true) {
        $tracking_set .= 'CREATE VIEW,';
    }
    if (isset($_REQUEST['drop_view']) && $_REQUEST['drop_view'] == true) {
        $tracking_set .= 'DROP VIEW,';
    }
    if (isset($_REQUEST['create_index']) && $_REQUEST['create_index'] == true) {
        $tracking_set .= 'CREATE INDEX,';
    }
    if (isset($_REQUEST['drop_index']) && $_REQUEST['drop_index'] == true) {
        $tracking_set .= 'DROP INDEX,';
    }
    if (isset($_REQUEST['insert']) && $_REQUEST['insert'] == true) {
        $tracking_set .= 'INSERT,';
    }
    if (isset($_REQUEST['update']) && $_REQUEST['update'] == true) {
        $tracking_set .= 'UPDATE,';
    }
    if (isset($_REQUEST['delete']) && $_REQUEST['delete'] == true) {
        $tracking_set .= 'DELETE,';
    }
    if (isset($_REQUEST['truncate']) && $_REQUEST['truncate'] == true) {
        $tracking_set .= 'TRUNCATE,';
    }
    $tracking_set = rtrim($tracking_set, ',');

    return $tracking_set;
}

/**
 * Deletes a tracking version
 *
 * @param string $version tracking version
 *
 * @return string HTML of the success message
 */
function PMA_deleteTrackingVersion($version)
{
    $html = '';
    $versionDeleted = PMA_Tracker::deleteTracking(
        $GLOBALS['db'],
        $GLOBALS['table'],
        $version
    );
    if ($versionDeleted) {
        $msg = PMA_Message::success(
            sprintf(
                __('Version %1$s of %2$s was deleted.'),
                htmlspecialchars($version),
                htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
            )
        );
        $html .= $msg->getDisplay();
    }

    return $html;
}

/**
 * Function to create the tracking version
 *
 * @return string HTML of the success message
 */
function PMA_createTrackingVersion()
{
    $html = '';
    $tracking_set = PMA_getTrackingSet();

    $versionCreated = PMA_Tracker::createVersion(
        $GLOBALS['db'],
        $GLOBALS['table'],
        $_REQUEST['version'],
        $tracking_set,
        $GLOBALS['dbi']->getTable($GLOBALS['db'], $GLOBALS['table'])->isView()
    );
    if ($versionCreated) {
        $msg = PMA_Message::success(
            sprintf(
                __('Version %1$s was created, tracking for %2$s is active.'),
                htmlspecialchars($_REQUEST['version']),
                htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
            )
        );
        $html .= $msg->getDisplay();
    }

    return $html;
}

/**
 * Create tracking version for multiple tables
 *
 * @param array $selected list of selected tables
 *
 * @return void
 */
function PMA_createTrackingForMultipleTables($selected)
{
    $tracking_set = PMA_getTrackingSet();

    foreach ($selected as $selected_table) {
        PMA_Tracker::createVersion(
            $GLOBALS['db'],
            $selected_table,
            $_REQUEST['version'],
            $tracking_set,
            $GLOBALS['dbi']->getTable($GLOBALS['db'], $selected_table)->isView()
        );
    }
}

/**
 * Function to get the entries
 *
 * @param array $data           data
 * @param int   $filter_ts_from filter time stamp from
 * @param int   $filter_ts_to   filter time stamp to
 * @param array $filter_users   filter users
 *
 * @return array
 */
function PMA_getEntries($data, $filter_ts_from, $filter_ts_to, $filter_users)
{
    $entries = array();
    // Filtering data definition statements
    if ($_REQUEST['logtype'] == 'schema'
        || $_REQUEST['logtype'] == 'schema_and_data'
    ) {
        $entries = array_merge(
            $entries,
            PMA_filterTracking(
                $data['ddlog'], $filter_ts_from, $filter_ts_to, $filter_users
            )
        );
    }

    // Filtering data manipulation statements
    if ($_REQUEST['logtype'] == 'data'
        || $_REQUEST['logtype'] == 'schema_and_data'
    ) {
        $entries = array_merge(
            $entries,
            PMA_filterTracking(
                $data['dmlog'], $filter_ts_from, $filter_ts_to, $filter_users
            )
        );
    }

    // Sort it
    $ids = $timestamps = $usernames = $statements = array();
    foreach ($entries as $key => $row) {
        $ids[$key]        = $row['id'];
        $timestamps[$key] = $row['timestamp'];
        $usernames[$key]  = $row['username'];
        $statements[$key] = $row['statement'];
    }

    array_multisort(
        $timestamps, SORT_ASC, $ids, SORT_ASC, $usernames,
        SORT_ASC, $statements, SORT_ASC, $entries
    );

    return $entries;
}

/**
 * Function to get version status
 *
 * @param array $version version info
 *
 * @return string $version_status The status message
 */
function PMA_getVersionStatus($version)
{
    if ($version['tracking_active'] == 1) {
        return __('active');
    } else {
        return __('not active');
    }
}

/**
 * Display untracked tables
 *
 * @param string $db               current database
 * @param array  $untracked_tables untracked tables
 * @param string $url_query        url query string
 * @param string $pmaThemeImage    path to theme's image folder
 * @param string $text_dir         text direction
 *
 * @return void
 */
function PMA_displayUntrackedTables(
    $db, $untracked_tables, $url_query, $pmaThemeImage, $text_dir
) {
    ?>
    <h3><?php echo __('Untracked tables');?></h3>
    <form method="post" action="db_tracking.php" name="untrackedForm"
        id="untrackedForm" class="ajax">
    <?php
    echo PMA_URL_getHiddenInputs($db)
    ?>
    <table id="noversions" class="data">
    <thead>
    <tr>
        <th></th>
        <th style="width: 300px"><?php echo __('Table');?></th>
        <th><?php echo __('Action');?></th>
    </tr>
    </thead>
    <tbody>
    <?php

    // Print out list of untracked tables
    $style = 'odd';
    foreach ($untracked_tables as $key => $tablename) {
        $style = PMA_displayOneUntrackedTable($db, $tablename, $url_query, $style);
    }
    ?>
    </tbody>
    </table>
    <?php
    echo PMA_Util::getWithSelected($pmaThemeImage, $text_dir, "untrackedForm");
    echo PMA_Util::getButtonOrImage(
        'submit_mult', 'mult_submit', 'submit_mult_track',
        __('Track table'), 'eye.png', 'track'
    );
    ?>
    </form>
    <?php
}

/**
 * Display one untracked table
 *
 * @param string $db        current database
 * @param string $tablename the table name for which to display a line
 * @param string $url_query url query string
 * @param string $style     odd|even
 *
 * @return string $style        changed style (even|odd)
 */
function PMA_displayOneUntrackedTable($db, $tablename, $url_query, $style)
{
    $checkbox_id = "selected_tbl_"
        . htmlspecialchars($tablename);
    if (PMA_Tracker::getVersion($db, $tablename) == -1) {
        $my_link = '<a href="tbl_tracking.php' . $url_query
            . '&amp;table=' . htmlspecialchars($tablename) . '">';
        $my_link .= PMA_Util::getIcon('eye.png', __('Track table'));
        $my_link .= '</a>';
        ?>
        <tr class="<?php echo $style;?>">
            <td class="center">
                <input type="checkbox" name="selected_tbl[]"
                    class="checkall" id="<?php echo $checkbox_id;?>"
                    value="<?php echo htmlspecialchars($tablename);?>"/>
            </td>
            <th>
                <label for="<?php echo $checkbox_id;?>">
                    <?php echo htmlspecialchars($tablename);?>
                </label>
            </th>
            <td><?php echo $my_link;?></td>
        </tr>
        <?php
        if ($style == 'even') {
            $style = 'odd';
        } else {
            $style = 'even';
        }
    }
    return $style;
}

/**
 * Get untracked tables
 *
 * @param string $db current database
 *
 * @return array $untracked_tables
 */
function PMA_getUntrackedTables($db)
{
    $untracked_tables = array();
    $sep = $GLOBALS['cfg']['NavigationTreeTableSeparator'];

    // Get list of tables
    $table_list = PMA_Util::getTableList($db);

    // For each table try to get the tracking version
    foreach ($table_list as $key => $value) {
        // If $value is a table group.
        if (array_key_exists(('is' . $sep . 'group'), $value)
            && $value['is' . $sep . 'group']
        ) {
            foreach ($value as $temp_table) {
                // If $temp_table is a table with the value for 'Name' is set,
                // rather than a property of the table group.
                if (is_array($temp_table)
                    && array_key_exists('Name', $temp_table)
                ) {
                    $tracking_version = PMA_Tracker::getVersion(
                        $db,
                        $temp_table['Name']
                    );
                    if ($tracking_version == -1) {
                        $untracked_tables[] = $temp_table['Name'];
                    }
                }
            }
        } else { // If $value is a table.
            if (PMA_Tracker::getVersion($db, $value['Name']) == -1) {
                $untracked_tables[] = $value['Name'];
            }
        }
    }
    return $untracked_tables;
}

/**
 * Display tracked tables
 *
 * @param string $db                current database
 * @param object $all_tables_result result set of tracked tables
 * @param string $url_query         url query string
 * @param string $pmaThemeImage     path to theme's image folder
 * @param string $text_dir          text direction
 * @param array  $cfgRelation       configuration storage info
 *
 * @return void
 */
function PMA_displayTrackedTables(
    $db, $all_tables_result, $url_query, $pmaThemeImage, $text_dir, $cfgRelation
) {
    ?>
    <div id="tracked_tables">
    <h3><?php echo __('Tracked tables');?></h3>

    <form method="post" action="db_tracking.php" name="trackedForm"
        id="trackedForm" class="ajax">
    <?php
    echo PMA_URL_getHiddenInputs($db)
    ?>
    <table id="versions" class="data">
    <thead>
    <tr>
        <th></th>
        <th><?php echo __('Table');?></th>
        <th><?php echo __('Last version');?></th>
        <th><?php echo __('Created');?></th>
        <th><?php echo __('Updated');?></th>
        <th><?php echo __('Status');?></th>
        <th><?php echo __('Action');?></th>
        <th><?php echo __('Show');?></th>
    </tr>
    </thead>
    <tbody>
    <?php

    // Print out information about versions

    $delete = PMA_Util::getIcon('b_drop.png', __('Delete tracking'));
    $versions = PMA_Util::getIcon('b_versions.png', __('Versions'));
    $report = PMA_Util::getIcon('b_report.png', __('Tracking report'));
    $structure = PMA_Util::getIcon('b_props.png', __('Structure snapshot'));

    $style = 'odd';
    while ($one_result = $GLOBALS['dbi']->fetchArray($all_tables_result)) {
        list($table_name, $version_number) = $one_result;
        $table_query = ' SELECT * FROM ' .
             PMA_Util::backquote($cfgRelation['db']) . '.' .
             PMA_Util::backquote($cfgRelation['tracking']) .
             ' WHERE `db_name` = \'' . PMA_Util::sqlAddSlashes($_REQUEST['db'])
             . '\' AND `table_name`  = \'' . PMA_Util::sqlAddSlashes($table_name)
             . '\' AND `version` = \'' . $version_number . '\'';

        $table_result = PMA_queryAsControlUser($table_query);
        $version_data = $GLOBALS['dbi']->fetchArray($table_result);

        $tbl_link = 'tbl_tracking.php' . $url_query . '&amp;table='
            . htmlspecialchars($version_data['table_name']);
        $delete_link = 'db_tracking.php' . $url_query . '&amp;table='
            . htmlspecialchars($version_data['table_name'])
            . '&amp;delete_tracking=true&amp';
        $checkbox_id = "selected_tbl_"
            . htmlspecialchars($version_data['table_name']);
        ?>
        <tr class="<?php echo $style;?>">
            <td class="center">
                <input type="checkbox" name="selected_tbl[]"
                class="checkall" id="<?php echo $checkbox_id;?>"
                value="<?php echo htmlspecialchars($version_data['table_name']);?>"/>
            </td>
            <th>
                <label for="<?php echo $checkbox_id;?>">
                    <?php echo htmlspecialchars($version_data['table_name']);?>
                </label>
            </th>
            <td class="right"><?php echo $version_data['version'];?></td>
            <td><?php echo $version_data['date_created'];?></td>
            <td><?php echo $version_data['date_updated'];?></td>
            <td>
            <?php
            PMA_displayStatusButton($version_data, $tbl_link);
            ?>
            </td>
            <td>
            <a class="delete_tracking_anchor ajax"
               href="<?php echo $delete_link;?>" >
            <?php echo $delete; ?></a>
        <?php
        echo '</td>'
            . '<td>'
            . '<a href="' . $tbl_link . '">' . $versions . '</a>'
            . '&nbsp;&nbsp;'
            . '<a href="' . $tbl_link . '&amp;report=true&amp;version='
            . $version_data['version'] . '">' . $report . '</a>'
            . '&nbsp;&nbsp;'
            . '<a href="' . $tbl_link . '&amp;snapshot=true&amp;version='
            . $version_data['version'] . '">' . $structure . '</a>'
            . '</td>'
            . '</tr>';
        if ($style == 'even') {
            $style = 'odd';
        } else {
            $style = 'even';
        }
    }
    ?>
    </tbody>
    </table>
    <?php
    echo PMA_Util::getWithSelected($pmaThemeImage, $text_dir, "trackedForm");
    echo PMA_Util::getButtonOrImage(
        'submit_mult', 'mult_submit', 'submit_mult_delete_tracking',
        __('Delete tracking'), 'b_drop.png', 'delete_tracking'
    );
    ?>
    </form>
    </div>
    <?php
}

/**
 * Display tracking status button
 *
 * @param array  $version_data data about tracking versions
 * @param string $tbl_link     link for tbl_tracking.php
 *
 * @return void
 */
function PMA_displayStatusButton($version_data, $tbl_link)
{
    $state = PMA_getVersionStatus($version_data);
    $options = array(
        0 => array(
            'label' => __('not active'),
            'value' => 'deactivate_now',
            'selected' => ($state != 'active')
        ),
        1 => array(
            'label' => __('active'),
            'value' => 'activate_now',
            'selected' => ($state == 'active')
        )
    );
    echo PMA_Util::toggleButton(
        $tbl_link . '&amp;version=' . $version_data['version'],
        'toggle_activation',
        $options,
        null
    );
}
