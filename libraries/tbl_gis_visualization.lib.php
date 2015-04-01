<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used to generate GIS visualizations.
 *
 * @package PhpMyAdmin
 */
if (!defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/sql.lib.php';

/**
 * Returns a modified sql query with only the label column
 * and spatial column(wrapped with 'ASTEXT()' function).
 *
 * @param string $sql_query             original sql query
 * @param array  $visualizationSettings settings for the visualization
 *
 * @return string the modified sql query.
 */
function PMA_GIS_modifyQuery($sql_query, $visualizationSettings)
{
    $modified_query = 'SELECT ';
    // If label column is chosen add it to the query
    if (! empty($visualizationSettings['labelColumn'])) {
        $modified_query .= PMA_Util::backquote($visualizationSettings['labelColumn'])
            . ', ';
    }
    // Wrap the spatial column with 'ASTEXT()' function and add it
    $modified_query .= 'ASTEXT('
        . PMA_Util::backquote($visualizationSettings['spatialColumn'])
        . ') AS ' . PMA_Util::backquote($visualizationSettings['spatialColumn'])
        . ', ';

    // Get the SRID
    $modified_query .= 'SRID('
        . PMA_Util::backquote($visualizationSettings['spatialColumn'])
        . ') AS ' . PMA_Util::backquote('srid') . ' ';

    // Append the original query as the inner query
    $modified_query .= 'FROM (' . $sql_query . ') AS '
        . PMA_Util::backquote('temp_gis');

    return $modified_query;
}

/**
 * Formats a visualization for the GIS query results.
 *
 * @param array  $data                   Data for the status chart
 * @param array  &$visualizationSettings Settings used to generate the chart
 * @param string $format                 Format of the visualization
 *
 * @return string|bool HTML and JS code for the GIS visualization or false on failure
 */
function PMA_GIS_visualizationResults($data, &$visualizationSettings, $format)
{
    include_once './libraries/gis/GIS_Visualization.class.php';
    include_once './libraries/gis/GIS_Factory.class.php';

    if (! isset($data[0])) {
        // empty data
        return __('No data found for GIS visualization.');
    }

    $visualization = new PMA_GIS_Visualization($data, $visualizationSettings);
    if ($visualizationSettings != null) {
        foreach ($visualization->getSettings() as $setting => $val) {
            if (! isset($visualizationSettings[$setting])) {
                $visualizationSettings[$setting] = $val;
            }
        }
    }

    if ($format == 'svg') {
        return $visualization->asSvg();
    } elseif ($format == 'png') {
        return $visualization->asPng();
    } elseif ($format == 'ol') {
        return $visualization->asOl();
    }

    return false;
}

/**
 * Generate visualization for the GIS query results and save it to a file.
 *
 * @param array  $data                  data for the status chart
 * @param array  $visualizationSettings settings used to generate the chart
 * @param string $format                format of the visualization
 * @param string $fileName              file name
 *
 * @return file File containing the visualization
 */
function PMA_GIS_saveToFile($data, $visualizationSettings, $format, $fileName)
{
    include_once './libraries/gis/GIS_Visualization.class.php';
    include_once './libraries/gis/GIS_Factory.class.php';

    if (isset($data[0])) {
        $visualization = new PMA_GIS_Visualization($data, $visualizationSettings);

        if ($format == 'svg') {
            $visualization->toFileAsSvg($fileName);
        } elseif ($format == 'png') {
            $visualization->toFileAsPng($fileName);
        } elseif ($format == 'pdf') {
            $visualization->toFileAsPdf($fileName);
        }
    }
}

/**
 * Function to get html for the label column and spatial column
 *
 * @param string $column                the column type. i.e either "labelColumn"
 *                                      or "spatialColumn"
 * @param array  $columnCandidates      the list of select options
 * @param array  $visualizationSettings visualization settings
 *
 * @return string  $html
 */
function PMA_getHtmlForSelect($column, $columnCandidates, $visualizationSettings)
{
    $html = '<label for="' . $column . '">';
    $html .= ($column=="labelColumn") ? __("Label column") : __("Spatial column");
    $html .= '</label>';

    $html .= '<select name="visualizationSettings[' . $column . ']" id="'
        . $column . '" class="autosubmit">';

    if ($column == "labelColumn") {
        $html .= '<option value="">' . __("-- None --") . '</option>';
    }

    $html .= PMA_getHtmlForOptionsList(
        $columnCandidates, array($visualizationSettings[$column])
    );

    $html .= '</select>';

    return $html;
}

/**
 * Function to get HTML for the option of using open street maps
 *
 * @param boolean $isSelected the default value
 *
 * @return string HTML string
 */
function PMA_getHtmlForUseOpenStreetMaps($isSelected)
{
    $html = '<tr><td class="choice" colspan="2">';
    $html .= '<input type="checkbox" name="visualizationSettings[choice]"'
        . 'id="choice" value="useBaseLayer"';
    if ($isSelected) {
        $html .= ' checked="checked"';
    }
    $html .= '/>';
    $html .= '<label for="choice">';
    $html .= __("Use OpenStreetMaps as Base Layer");
    $html .= '</label>';
    $html .= '</td></tr>';

    return $html;
}

/**
 * Get the link for downloading GIS visualization in a particular format.
 *
 * @param string $url   base url
 * @param string $name  format name
 * @param string $label format label
 *
 * @return string HTML for download link
 */
function PMA_getHtmlForGisDownloadLink($url, $name, $label)
{
    $html  = '<li class="warp_link">';
    $html .= '<a href="' . $url . '&fileFormat=' . $name . '"'
        . ' class="disableAjax">' . $label . '</a>';
    $html .= '</li>';

    return $html;
}

/**
 * Function to generate HTML for the GIS visualization page
 *
 * @param array   $url_params            url parameters
 * @param array   $labelCandidates       list of candidates for the label
 * @param array   $spatialCandidates     list of candidates for the spatial column
 * @param array   $visualizationSettings visualization settings
 * @param String  $sql_query             the sql query
 * @param String  $visualization         HTML and js code for the visualization
 * @param boolean $svg_support           whether svg download format is supported
 * @param array   $data                  array of visualizing data
 *
 * @return string HTML code for the GIS visualization
 */
function PMA_getHtmlForGisVisualization(
    $url_params, $labelCandidates, $spatialCandidates, $visualizationSettings,
    $sql_query, $visualization, $svg_support, $data
) {
    $html = '<div id="div_view_options">';
    $html .= '<fieldset>';
    $html .= '<legend>' . __('Display GIS Visualization') . '</legend>';

    $html .= '<div id="gis_div" style="position:relative;">';
    $html .= '<form method="post" action="tbl_gis_visualization.php">';
    $html .= PMA_URL_getHiddenInputs($url_params);

    $html .= PMA_getHtmlForSelect(
        "labelColumn", $labelCandidates, $visualizationSettings
    );
    $html .= PMA_getHtmlForSelect(
        "spatialColumn", $spatialCandidates, $visualizationSettings
    );

    $html .= '<input type="hidden" name="displayVisualization" value="redraw">';
    $html .= '<input type="hidden" name="sql_query" value="';
    $html .= htmlspecialchars($sql_query) . '" />';
    $html .= '</form>';

    if (! $GLOBALS['PMA_Config']->isHttps()) {
        $isSelected = isset($visualizationSettings['choice']) ? true : false;
        $html .= PMA_getHtmlForUseOpenStreetMaps($isSelected);
    }

    $html .= '<div class="pma_quick_warp" style="width: 50px; position: absolute;'
        . ' right: 0; top: 0; cursor: pointer;">';
    $html .= '<div class="drop_list">';
    $html .= '<span class="drop_button" style="padding: 0; border: 0;">';
    $html .= PMA_Util::getImage('b_saveimage', __('Save'));
    $html .= '</span>';

    $url_params['sql_query'] = $sql_query;
    $url_params['saveToFile'] = 'download';
    $url = 'tbl_gis_visualization.php' . PMA_URL_getCommon($url_params);

    $html .= '<ul>';
    $html .= PMA_getHtmlForGisDownloadLink($url, 'png', 'PNG');
    $html .= PMA_getHtmlForGisDownloadLink($url, 'pdf', 'PDF');
    if ($svg_support) {
        $html .= PMA_getHtmlForGisDownloadLink($url, 'svg', 'SVG');
    }
    $html .= '</ul>';
    $html .= '</div></div>';

    $html .= '</div>';

    $html .= '<div style="clear:both;">&nbsp;</div>';

    $html .= '<div id="placeholder" style="width:';
    $html .= htmlspecialchars($visualizationSettings['width']) . 'px;height:';
    $html .= htmlspecialchars($visualizationSettings['height']) . 'px;">';
    $html .= $visualization;
    $html .= '</div>';

    $html .= '<div id="openlayersmap"></div>';
    $html .= '<input type="hidden" id="pmaThemeImage" value="';
    $html .= $GLOBALS['pmaThemeImage'] . '" />';
    $html .= '<script language="javascript" type="text/javascript">';
    $html .= 'function drawOpenLayers()';
    $html .= '{';

    if (! $GLOBALS['PMA_Config']->isHttps()) {
        $html .= PMA_GIS_visualizationResults($data, $visualizationSettings, 'ol');
    }
    $html .= '}';
    $html .= '</script>';
    $html .= '</fieldset>';
    $html .= '</div>';

    return $html;
}
?>
