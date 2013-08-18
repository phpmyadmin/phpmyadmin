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
?>
