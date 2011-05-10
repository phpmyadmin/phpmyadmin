<?php
/**
 * Chart functions used to generate GIS visualizations
 * @package phpMyAdmin
 */

require_once './libraries/gis/pma_gis_visualization.php';
require_once './libraries/gis/pma_gis_geometry.php';
require_once './libraries/gis/pma_gis_polygon.php';
require_once './libraries/gis/pma_gis_multipolygon.php';
require_once './libraries/gis/pma_gis_linestring.php';
require_once './libraries/gis/pma_gis_multilinestring.php';
require_once './libraries/gis/pma_gis_point.php';
require_once './libraries/gis/pma_gis_multipoint.php';
require_once './libraries/gis/pma_gis_geometrycollection.php';

/**
 * Returns a modified sql query with only the label column
 * and spatial column(wrapped with 'ASTEXT()' function).
 * @param string $sql_query original sql query
 * @param array $visualizationSettings settings for the visualization
 * @return the modified sql query.
 */
function PMA_GIS_modify_query($sql_query, $visualizationSettings) {
    $modified_query = 'SELECT ';

    /**
     * @todo here 0 is not good, resolve it.
     */
    $analyzed_query = PMA_SQP_analyze(PMA_SQP_parse($sql_query));
    // If select clause is not *
    if (trim($analyzed_query[0]['select_expr_clause']) != '*') {
        // If label column is chosen add it to the query
        if (isset($visualizationSettings['labelColumn'])
            && $visualizationSettings['labelColumn'] != '')
        {
            // Check to see whether an alias has been used on the label column
            $is_label_alias = false;
            foreach ($analyzed_query[0]['select_expr'] as $select) {
                if ($select['alias'] == $visualizationSettings['labelColumn']) {
                    $modified_query .= sanitize($select['expr']) . ' AS `'
                    . $select['alias'] . '`, ';
                    $is_label_alias = true;
                    break;
                }
            }
            // If no alias have been used on the label column
            if (! $is_label_alias) {
                foreach ($analyzed_query[0]['select_expr'] as $select) {
                    if ($select['column'] == $visualizationSettings['labelColumn']) {
                        $modified_query .= sanitize($select['expr']) . ', ';
                    }
                }
            }
        }

        // Check to see whether an alias has been used on the spatial column
        $is_spatial_alias = false;
        foreach ($analyzed_query[0]['select_expr'] as $select) {
            if ($select['alias'] == $visualizationSettings['spatialColumn']) {
                $modified_query .= 'ASTEXT(' . sanitize($select['expr']) . ') AS `'
                . $select['alias'] . '` ';
                $is_spatial_alias = true;
                break;
            }
        }
        // If no alias have been used on the spatial column
        if (! $is_spatial_alias) {
            foreach ($analyzed_query[0]['select_expr'] as $select) {
                if ($select['column'] == $visualizationSettings['spatialColumn']) {
                    $modified_query .= 'ASTEXT(' . sanitize($select['expr']) . ') AS `'
                    . $select['column'] . '` ';
                }
            }
        }
        // If select cluase is *
    } else {
        // If label column is chosen add it to the query
        if ($visualizationSettings['labelColumn'] != '') {
            $modified_query .= '`' . $visualizationSettings['labelColumn'] .'`, ';
        }

        // Wrap the spacial column with 'ASTEXT()' function and add it
        $modified_query .= 'ASTEXT(`' . $visualizationSettings['spatialColumn'] . '`) AS `'
        . $visualizationSettings['spatialColumn'] . '` ';
    }

    // Append the rest of the query
    $from_pos = stripos($sql_query, 'FROM');
    $modified_query .= substr($sql_query, $from_pos);
    return $modified_query;
}

// Local function to sanitize the expression taken
// from the results of PMA_SQP_analyze function.
function sanitize($expr) {
    /**
     * @todo code to add missing backquotes
     */
    return $expr;
}

/**
 * Formats a visualization for the GIS query results.
 * @param array $data data for the status chart
 * @param array $chartSettings settings used to generate the chart
 * @return string HTML and JS code for the GIS visualization
 */
function PMA_GIS_visualization_results($data, &$visualizationSettings) {

    $visualizationData = array();

    if (!isset($data[0])) {
        // empty data
        return __('No data found for GIS visualization.');
    } else {
        $visualization = new PMA_GIS_visualization($data, $visualizationSettings);

        if ($visualizationSettings != null) {
            foreach ($visualization->getSettings() as $setting => $val) {
                if (! isset($visualizationSettings[$setting])) {
                    $visualizationSettings[$setting] = $val;
                }
            }
        }
        // Generate the visualization code
        $visualizationCode = $visualization->toString();

        return $visualizationCode;
    }
}
?>
