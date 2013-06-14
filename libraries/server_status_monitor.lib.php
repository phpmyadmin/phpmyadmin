<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server status sub item: monitor
 *
 * @usedby  server_status_monitor.php
 *  
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Prints html with monitor
 *
 * @param object $ServerStatusData An instance of the PMA_ServerStatusData class
 *
 * @return string
 */
function PMA_getHtmlForMonitor($ServerStatusData)
{
    $retval  = PMA_getHtmlForTabLinks();
    
    $retval .= PMA_getHtmlForSettingsDialog();
    
    $retval .= PMA_getHtmlForInstructionsDialog();

    $retval .= PMA_getHtmlForAddChartDialog();

    if (! PMA_DRIZZLE) {
        $retval .= PMA_getHtmlForAnalyseDialog();
    }

    $retval .= '<table class="clearfloat" id="chartGrid"></table>';
    $retval .= '<div id="logTable">';
    $retval .= '<br/>';
    $retval .= '</div>';

    $retval .= '<script type="text/javascript">';
    $retval .= 'variableNames = [ ';
    $i=0;
    foreach ($ServerStatusData->status as $name=>$value) {
        if (is_numeric($value)) {
            if ($i++ > 0) {
                $retval .= ", ";
            }
            $retval .= "'" . $name . "'";
        }
    }
    $retval .= '];';
    $retval .= '</script>';

    return $retval;
}

/**
 * Builds a <select> list for refresh rates
 *
 * @param string $name         Name of select
 * @param int    $defaultRate  Currently chosen rate
 * @param array  $refreshRates List of refresh rates
 *
 * @return string
 */
function PMA_getHtmlForRefreshList($name,
    $defaultRate = 5,
    $refreshRates = Array(1, 2, 5, 10, 20, 40, 60, 120, 300, 600)
) {
    $return = '<select name="' . $name . '" id="id_' . $name
        . '" class="refreshRate">';
    foreach ($refreshRates as $rate) {
        $selected = ($rate == $defaultRate)?' selected="selected"':'';
        $return .= '<option value="' . $rate . '"' . $selected . '>';
        if ($rate < 60) {
            $return .= sprintf(_ngettext('%d second', '%d seconds', $rate), $rate);
        } else {
            $rate = $rate / 60;
            $return .= sprintf(_ngettext('%d minute', '%d minutes', $rate), $rate);
        }
        $return .=  '</option>';
    }
    $return .= '</select>';
    return $return;
}

/**
 * Returns html for Analyse Dialog
 *
 * @return string
 */
function PMA_getHtmlForAnalyseDialog()
{
    $retval  = '<div id="logAnalyseDialog" title="';
    $retval .= __('Log statistics') . '" style="display:none;">';
    $retval .= '<p>' . __('Selected time range:');
    $retval .= '<input type="text" name="dateStart" class="datetimefield" value="" /> - ';
    $retval .= '<input type="text" name="dateEnd" class="datetimefield" value="" />';
    $retval .= '</p>';
    $retval .= '<input type="checkbox" id="limitTypes" value="1" checked="checked" />';
    $retval .= '<label for="limitTypes">';
    $retval .= __('Only retrieve SELECT,INSERT,UPDATE and DELETE Statements');
    $retval .= '</label>';
    $retval .= '<br/>';
    $retval .= '<input type="checkbox" id="removeVariables" value="1" checked="checked" />';
    $retval .= '<label for="removeVariables">';
    $retval .= __('Remove variable data in INSERT statements for better grouping');
    $retval .= '</label>';
    $retval .= '<p>';
    $retval .= __('Choose from which log you want the statistics to be generated from.');
    $retval .= '</p>';
    $retval .= '<p>';
    $retval .= __('Results are grouped by query text.');
    $retval .= '</p>';
    $retval .= '</div>';
    $retval .= '<div id="queryAnalyzerDialog" title="';
    $retval .= __('Query analyzer') . '" style="display:none;">';
    $retval .= '<textarea id="sqlquery"> </textarea>';
    $retval .= '<p></p>';
    $retval .= '<div class="placeHolder"></div>';
    $retval .= '</div>';
    
    return $retval;
}

/**
 * Returns html for Instructions Dialog
 *
 * @return string
 */
function PMA_getHtmlForInstructionsDialog()
{
    $retval  = '<div id="monitorInstructionsDialog" title="';
    $retval .= __('Monitor Instructions') . '" style="display:none;">';
    $retval .= __(
        'The phpMyAdmin Monitor can assist you in optimizing the server'
        . ' configuration and track down time intensive queries. For the latter you'
        . ' will need to set log_output to \'TABLE\' and have either the'
        . ' slow_query_log or general_log enabled. Note however, that the'
        . ' general_log produces a lot of data and increases server load'
        . ' by up to 15%.'
    );

    if (PMA_MYSQL_INT_VERSION < 50106) {
        $retval .= '<p>';
        $retval .= PMA_Util::getImage('s_attention.png');
        $retval .=  __(
            'Unfortunately your Database server does not support logging to table,'
            . ' which is a requirement for analyzing the database logs with'
            . ' phpMyAdmin. Logging to table is supported by MySQL 5.1.6 and'
            . ' onwards. You may still use the server charting features however.'
        );
        $retval .= '</p>';
    } else {
        $retval .= '<p></p>';
        $retval .= '<img class="ajaxIcon" src="';
        $retval .= $GLOBALS['pmaThemeImage'] . 'ajax_clock_small.gif"';
        $retval .= ' alt="' . __('Loading') . '" />';
        $retval .= '<div class="ajaxContent"></div>';
        $retval .= '<div class="monitorUse" style="display:none;">';
        $retval .= '<p></p>';
        $retval .= '<strong>';
        $retval .= __('Using the monitor:');
        $retval .= '</strong><p>';
        $retval .= __(
            'Your browser will refresh all displayed charts in a regular interval.'
            . ' You may add charts and change the refresh rate under \'Settings\','
            . ' or remove any chart using the cog icon on each respective chart.'
        );
        $retval .= '</p><p>';
        $retval .= __(
            'To display queries from the logs, select the relevant time span on any'
            . ' chart by holding down the left mouse button and panning over the'
            . ' chart. Once confirmed, this will load a table of grouped queries,'
            . ' there you may click on any occurring SELECT statements to further'
            . ' analyze them.'
        );
        $retval .= '</p>';
        $retval .= '<p>';
        $retval .= PMA_Util::getImage('s_attention.png');
        $retval .= '<strong>';
        $retval .= __('Please note:');
        $retval .= '</strong><br />';
        $retval .= __(
            'Enabling the general_log may increase the server load by'
            . ' 5-15%. Also be aware that generating statistics from the logs is a'
            . ' load intensive task, so it is advisable to select only a small time'
            . ' span and to disable the general_log and empty its table once'
            . ' monitoring is not required any more.'
        );
        $retval .= '</p>';
        $retval .= '</div>';
    }
    $retval .= '</div>';
    
    return $retval;
}

/**
 * Returns html for addChartDialog
 *
 * @return string
 */
function PMA_getHtmlForAddChartDialog()
{
    $retval  = '<div id="addChartDialog" title="' . __('Add chart') . '" style="display:none;">';
    $retval .= '<div id="tabGridVariables">';
    $retval .= '<p><input type="text" name="chartTitle" value="' . __('Chart Title') . '" /></p>';
    $retval .= '<input type="radio" name="chartType" value="preset" id="chartPreset" />';
    $retval .= '<label for="chartPreset">' . __('Preset chart') . '</label>';
    $retval .= '<select name="presetCharts"></select><br/>';
    $retval .= '<input type="radio" name="chartType" value="variable" id="chartStatusVar" checked="checked" />';
    $retval .= '<label for="chartStatusVar">';
    $retval .= __('Status variable(s)');
    $retval .= '</label><br/>';
    $retval .= '<div id="chartVariableSettings">';
    $retval .= '<label for="chartSeries">' . __('Select series:') . '</label><br />';
    $retval .= '<select id="chartSeries" name="varChartList" size="1">';
    $retval .= '<option>' . __('Commonly monitored') . '</option>';
    $retval .= '<option>Processes</option>';
    $retval .= '<option>Questions</option>';
    $retval .= '<option>Connections</option>';
    $retval .= '<option>Bytes_sent</option>';
    $retval .= '<option>Bytes_received</option>';
    $retval .= '<option>Threads_connected</option>';
    $retval .= '<option>Created_tmp_disk_tables</option>';
    $retval .= '<option>Handler_read_first</option>';
    $retval .= '<option>Innodb_buffer_pool_wait_free</option>';
    $retval .= '<option>Key_reads</option>';
    $retval .= '<option>Open_tables</option>';
    $retval .= '<option>Select_full_join</option>';
    $retval .= '<option>Slow_queries</option>';
    $retval .= '</select><br />';
    $retval .= '<label for="variableInput">';
    $retval .= __('or type variable name:');
    $retval .= ' </label>';
    $retval .= '<input type="text" name="variableInput" id="variableInput" />';
    $retval .= '<p></p>';
    $retval .= '<input type="checkbox" name="differentialValue"';
    $retval .= ' id="differentialValue" value="differential" checked="checked" />';
    $retval .= '<label for="differentialValue">';
    $retval .= __('Display as differential value');
    $retval .= '</label><br />';
    $retval .= '<input type="checkbox" id="useDivisor" name="useDivisor" value="1" />';
    $retval .= '<label for="useDivisor">' . __('Apply a divisor') . '</label>';
    $retval .= '<span class="divisorInput" style="display:none;">';
    $retval .= '<input type="text" name="valueDivisor" size="4" value="1" />';
    $retval .= '(<a href="#kibDivisor">' . __('KiB') . '</a>, ';
    $retval .= '<a href="#mibDivisor">' . __('MiB') . '</a>)';
    $retval .= '</span><br />';
    $retval .= '<input type="checkbox" id="useUnit" name="useUnit" value="1" />';
    $retval .= '<label for="useUnit">';
    $retval .= __('Append unit to data values');
    $retval .= '</label>';
    $retval .= '<span class="unitInput" style="display:none;">';
    $retval .= '<input type="text" name="valueUnit" size="4" value="" />';
    $retval .= '</span>';
    $retval .= '<p>';
    $retval .= '<a href="#submitAddSeries"><b>' . __('Add this series') . '</b></a>';
    $retval .= '<span id="clearSeriesLink" style="display:none;">';
    $retval .= ' | <a href="#submitClearSeries">' . __('Clear series') . '</a>';
    $retval .= '</span>';
    $retval .= '</p>';
    $retval .= __('Series in Chart:');
    $retval .= '<br/>';
    $retval .= '<span id="seriesPreview">';
    $retval .= '<i>' . __('None') . '</i>';
    $retval .= '</span>';
    $retval .= '</div>';
    $retval .= '</div>';
    $retval .= '</div>';
    
    return $retval;
}

/**
 * Returns html with Tab Links
 *
 * @return string
 */
function PMA_getHtmlForTabLinks()
{
    $retval  = '<div class="tabLinks">';
    $retval .= '<a href="#pauseCharts">';
    $retval .= PMA_Util::getImage('play.png') . __('Start Monitor');
    $retval .= '</a>';
    $retval .= '<a href="#settingsPopup" class="popupLink">';
    $retval .= PMA_Util::getImage('s_cog.png') .  __('Settings');
    $retval .= '</a>';
    if (! PMA_DRIZZLE) {
        $retval .= '<a href="#monitorInstructionsDialog">';
        $retval .= PMA_Util::getImage('b_help.png') . __('Instructions/Setup');
    }
    $retval .= '<a href="#endChartEditMode" style="display:none;">';
    $retval .= PMA_Util::getImage('s_okay.png');
    $retval .= __('Done dragging (rearranging) charts');
    $retval .= '</a>';
    $retval .= '</div>';

    return $retval;
}

/**
 * Returns html with Settings dialog 
 *
 * @return string
 */
function PMA_getHtmlForSettingsDialog()
{
    $retval  = '<div class="popupContent settingsPopup">';
    $retval .= '<a href="#addNewChart">';
    $retval .= PMA_Util::getImage('b_chart.png') . __('Add chart');
    $retval .= '</a>';
    $retval .= '<a href="#rearrangeCharts">';
    $retval .= PMA_Util::getImage('b_tblops.png') . __('Enable charts dragging');
    $retval .= '</a>';
    $retval .= '<div class="clearfloat paddingtop"></div>';
    $retval .= '<div class="floatleft">';
    $retval .= __('Refresh rate') . '<br />';
    $retval .= PMA_getHtmlForRefreshList(
        'gridChartRefresh',
        5,
        Array(2, 3, 4, 5, 10, 20, 40, 60, 120, 300, 600, 1200)
    );
    $retval .= '<br />';
    $retval .= '</div>';
    $retval .= '<div class="floatleft">';
    $retval .= __('Chart columns');
    $retval .= '<br />';
    $retval .= '<select name="chartColumns">';
    $retval .= '<option>1</option>';
    $retval .= '<option>2</option>';
    $retval .= '<option>3</option>';
    $retval .= '<option>4</option>';
    $retval .= '<option>5</option>';
    $retval .= '<option>6</option>';
    $retval .= '<option>7</option>';
    $retval .= '<option>8</option>';
    $retval .= '<option>9</option>';
    $retval .= '<option>10</option>';
    $retval .= '</select>';
    $retval .= '</div>';
    $retval .= '<div class="clearfloat paddingtop">';
    $retval .= '<b>' . __('Chart arrangement') . '</b> ';
    $retval .= PMA_Util::showHint(
        __(
            'The arrangement of the charts is stored to the browsers local storage. '
            . 'You may want to export it if you have a complicated set up.'
        )
    );
    $retval .= '<br/>';
    $retval .= '<a class="ajax" href="#importMonitorConfig">';
    $retval .= __('Import');
    $retval .= '</a>';
    $retval .= '&nbsp;&nbsp;';
    $retval .= '<a class="disableAjax" href="#exportMonitorConfig">';
    $retval .= __('Export');
    $retval .= '</a>';
    $retval .= '&nbsp;&nbsp;';
    $retval .= '<a href="#clearMonitorConfig">';
    $retval .= __('Reset to default');
    $retval .= '</a>';
    $retval .= '</div>';
    $retval .= '</div>';

    return $retval;
}


/**
 * Define some data and links needed on the client side
 *
 * @param object $ServerStatusData An instance of the PMA_ServerStatusData class
 *
 * @return string
 */
function PMA_getHtmlForClientSideDataAndLinks($ServerStatusData)
{
    /**
     * Define some data needed on the client side
     */
    $input = '<input type="hidden" name="%s" value="%s" />';
    $form  = '<form id="js_data" class="hide">';
    $form .= sprintf($input, 'server_time', microtime(true) * 1000);
    $form .= sprintf($input, 'server_os', PHP_OS);
    $form .= sprintf($input, 'is_superuser', $GLOBALS['dbi']->isSuperuser());
    $form .= sprintf($input, 'server_db_isLocal', $ServerStatusData->db_isLocal);
    $form .= '</form>';
    /**
     * Define some links used on client side
     */
    $links  = '<div id="profiling_docu" class="hide">';
    $links .= PMA_Util::showMySQLDocu('general-thread-states', 'general-thread-states');
    $links .= '</div>';
    $links .= '<div id="explain_docu" class="hide">';
    $links .= PMA_Util::showMySQLDocu('explain-output', 'explain-output');
    $links .= '</div>';

    return $form . $links;
}

?>
