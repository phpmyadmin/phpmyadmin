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
require_once 'libraries/Template.class.php';

/**
 * Returns a modified sql query with only the label column
 * and spatial column(wrapped with 'ASTEXT()' function).
 *
 * @param string  $sql_query             original sql query
 * @param array   $visualizationSettings settings for the visualization
 * @param integer $rows                  number of rows
 * @param integer $pos                   start position
 *
 * @return string the modified sql query.
 */
function PMA_GIS_modifyQuery(
    $sql_query, $visualizationSettings, $rows = null, $pos = null
) {
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

    // LIMIT clause
    if (is_numeric($rows) && $rows > 0) {
        $modified_query .= ' LIMIT ';
        if (is_numeric($pos) && $pos >= 0) {
            $modified_query .= $pos . ', ' . $rows;
        } else {
            $modified_query .= $rows;
        }
    }

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
    $url_params['sql_query'] = $sql_query;
    $downloadUrl = 'tbl_gis_visualization.php' . PMA_URL_getCommon($url_params)
        . '&saveToFile=true';

    return PMA\Template::get('gis_visualization/gis_visualization')->render(array(
        'url_params' => $url_params,
        'downloadUrl' => $downloadUrl,
        'labelCandidates' => $labelCandidates,
        'spatialCandidates' => $spatialCandidates,
        'visualizationSettings' => $visualizationSettings,
        'sql_query' => $sql_query,
        'visualization' => $visualization,
        'svg_support' => $svg_support,
        'data' => $data
    ));
}
?>
