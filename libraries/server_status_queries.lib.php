<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying query statistics for the server
 *
 * @usedby  server_status_queries.php
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\ServerStatusData;

/**
 * Returns the html content for the query statistics
 *
 * @param ServerStatusData $ServerStatusData Server status data
 *
 * @return string
 */
function PMA_getHtmlForQueryStatistics($ServerStatusData)
{
    $retval = '';

    $hour_factor   = 3600 / $ServerStatusData->status['Uptime'];
    $used_queries = $ServerStatusData->used_queries;
    $total_queries = array_sum($used_queries);

    $retval .= '<h3 id="serverstatusqueries">';
    /* l10n: Questions is the name of a MySQL Status variable */
    $retval .= sprintf(
        __('Questions since startup: %s'),
        PMA\libraries\Util::formatNumber($total_queries, 0)
    );
    $retval .= ' ';
    $retval .= PMA\libraries\Util::showMySQLDocu(
        'server-status-variables',
        false,
        'statvar_Questions'
    );
    $retval .= '<br />';
    $retval .= '<span>';
    $retval .= '&oslash; ' . __('per hour:') . ' ';
    $retval .= PMA\libraries\Util::formatNumber($total_queries * $hour_factor, 0);
    $retval .= '<br />';
    $retval .= '&oslash; ' . __('per minute:') . ' ';
    $retval .= PMA\libraries\Util::formatNumber(
        $total_queries * 60 / $ServerStatusData->status['Uptime'],
        0
    );
    $retval .= '<br />';
    if ($total_queries / $ServerStatusData->status['Uptime'] >= 1) {
        $retval .= '&oslash; ' . __('per second:') . ' ';
        $retval .= PMA\libraries\Util::formatNumber(
            $total_queries / $ServerStatusData->status['Uptime'],
            0
        );
    }
    $retval .= '</span>';
    $retval .= '</h3>';

    $retval .= PMA_getHtmlForServerStatusQueriesDetails($ServerStatusData);

    return $retval;
}

/**
 * Returns the html content for the query details
 *
 * @param ServerStatusData $ServerStatusData Server status data
 *
 * @return string
 */
function PMA_getHtmlForServerStatusQueriesDetails($ServerStatusData)
{
    $hour_factor   = 3600 / $ServerStatusData->status['Uptime'];
    $used_queries = $ServerStatusData->used_queries;
    $total_queries = array_sum($used_queries);
    // reverse sort by value to show most used statements first
    arsort($used_queries);

    //(- $ServerStatusData->status['Connections']);
    $perc_factor    = 100 / $total_queries;

    $retval = '<table id="serverstatusqueriesdetails" '
        . 'class="data sortable noclick">';
    $retval .= '<col class="namecol" />';
    $retval .= '<col class="valuecol" span="3" />';
    $retval .= '<thead>';
    $retval .= '<tr><th>' . __('Statements') . '</th>';
    $retval .= '<th>';
    /* l10n: # = Amount of queries */
    $retval .= __('#');
    $retval .= '</th>';
    $retval .= '<th>&oslash; ' . __('per hour')
        . '</th>';
    $retval .= '<th>%</div></th>';
    $retval .= '</tr>';
    $retval .= '</thead>';
    $retval .= '<tbody>';

    $chart_json = array();
    $query_sum = array_sum($used_queries);
    $other_sum = 0;
    foreach ($used_queries as $name => $value) {
        // For the percentage column, use Questions - Connections, because
        // the number of connections is not an item of the Query types
        // but is included in Questions. Then the total of the percentages is 100.
        $name = str_replace(array('Com_', '_'), array('', ' '), $name);
        // Group together values that make out less than 2% into "Other", but only
        // if we have more than 6 fractions already
        if ($value < $query_sum * 0.02 && count($chart_json)>6) {
            $other_sum += $value;
        } else {
            $chart_json[$name] = $value;
        }
        $retval .= '<tr>';
        $retval .= '<th class="name">' . htmlspecialchars($name) . '</th>';
        $retval .= '<td class="value">';
        $retval .= htmlspecialchars(
            PMA\libraries\Util::formatNumber($value, 5, 0, true)
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= htmlspecialchars(
            PMA\libraries\Util::formatNumber($value * $hour_factor, 4, 1, true)
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= htmlspecialchars(
            PMA\libraries\Util::formatNumber($value * $perc_factor, 0, 2)
        );
        $retval .= '</td>';
        $retval .= '</tr>';
    }
    $retval .= '</tbody>';
    $retval .= '</table>';

    $retval .= '<div id="serverstatusquerieschart" data-chart="';
    if ($other_sum > 0) {
        $chart_json[__('Other')] = $other_sum;
    }
    $retval .= htmlspecialchars(json_encode($chart_json), ENT_QUOTES);
    $retval .= '"></div>';

    return $retval;
}
