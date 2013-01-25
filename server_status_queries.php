<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays query statistics for the server
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/ServerStatusData.class.php';
if (PMA_DRIZZLE) {
    $server_master_status = false;
    $server_slave_status = false;
} else {
    include_once 'libraries/replication.inc.php';
    include_once 'libraries/replication_gui.lib.php';
}

$ServerStatusData = new PMA_ServerStatusData();

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_status_queries.js');
/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $scripts->addFile('jqplot/excanvas.js');
}

// for charting
$scripts->addFile('jqplot/jquery.jqplot.js');
$scripts->addFile('jqplot/plugins/jqplot.pieRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasTextRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasAxisLabelRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.dateAxisRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.highlighter.js');
$scripts->addFile('jqplot/plugins/jqplot.cursor.js');
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('server_status_sorter.js');

// Add the html content to the response
$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(getQueryStatisticsHtml($ServerStatusData));
$response->addHTML('</div>');
exit;

/**
 * Returns the html content for the query statistics
 *
 * @param object $ServerStatusData An instance of the PMA_ServerStatusData class
 *
 * @return string
 */
function getQueryStatisticsHtml($ServerStatusData)
{
    $retval = '';

    $hour_factor   = 3600 / $ServerStatusData->status['Uptime'];
    $used_queries = $ServerStatusData->used_queries;
    $total_queries = array_sum($used_queries);

    $retval .= '<h3 id="serverstatusqueries">';
    /* l10n: Questions is the name of a MySQL Status variable */
    $retval .= sprintf(
        __('Questions since startup: %s'),
        PMA_Util::formatNumber($total_queries, 0)
    );
    $retval .= ' ';
    $retval .= PMA_Util::showMySQLDocu(
        'server-status-variables',
        'server-status-variables',
        false,
        'statvar_Questions'
    );
    $retval .= '<br />';
    $retval .= '<span>';
    $retval .= '&oslash; ' . __('per hour') . ': ';
    $retval .= PMA_Util::formatNumber($total_queries * $hour_factor, 0);
    $retval .= '<br />';
    $retval .= '&oslash; ' . __('per minute') . ': ';
    $retval .= PMA_Util::formatNumber($total_queries * 60 / $ServerStatusData->status['Uptime'], 0);
    $retval .= '<br />';
    if ($total_queries / $ServerStatusData->status['Uptime'] >= 1) {
        $retval .= '&oslash; ' . __('per second') . ': ';
        $retval .= PMA_Util::formatNumber($total_queries / $ServerStatusData->status['Uptime'], 0);
    }
    $retval .= '</span>';
    $retval .= '</h3>';

    // reverse sort by value to show most used statements first
    arsort($used_queries);

    $odd_row        = true;
    $perc_factor    = 100 / $total_queries; //(- $ServerStatusData->status['Connections']);

    $retval .= '<table id="serverstatusqueriesdetails" class="data sortable noclick">';
    $retval .= '<col class="namecol" />';
    $retval .= '<col class="valuecol" span="3" />';
    $retval .= '<thead>';
    $retval .= '<tr><th>' . __('Statements') . '</th>';
    $retval .= '<th>';
    /* l10n: # = Amount of queries */
    $retval .= __('#');
    $retval .= '</th>';
    $retval .= '<th>&oslash; ' . __('per hour') . '</th>';
    $retval .= '<th>%</th>';
    $retval .= '</tr>';
    $retval .= '</thead>';
    $retval .= '<tbody>';

    $chart_json = array();
    $query_sum = array_sum($used_queries);
    $other_sum = 0;
    foreach ($used_queries as $name => $value) {
        $odd_row = !$odd_row;
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
        $retval .= '<tr class="';
        $retval .= $odd_row ? 'odd' : 'even';
        $retval .= '">';
        $retval .= '<th class="name">' . htmlspecialchars($name) . '</th>';
        $retval .= '<td class="value">';
        $retval .= htmlspecialchars(PMA_Util::formatNumber($value, 5, 0, true));
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= htmlspecialchars(
            PMA_Util::formatNumber($value * $hour_factor, 4, 1, true)
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= htmlspecialchars(
            PMA_Util::formatNumber($value * $perc_factor, 0, 2)
        );
        $retval .= '</td>';
        $retval .= '</tr>';
    }
    $retval .= '</tbody>';
    $retval .= '</table>';

    $retval .= '<div id="serverstatusquerieschart"></div>';
    $retval .= '<div id="serverstatusquerieschart_data" style="display:none;">';
    if ($other_sum > 0) {
        $chart_json[__('Other')] = $other_sum;
    }
    $retval .= htmlspecialchars(json_encode($chart_json));
    $retval .= '</div>';

    return $retval;
}

?>
