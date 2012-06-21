<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used to generate GIS visualizations.
 *
 * @package PhpMyAdmin
 */



/**
 * Returns a modified sql query with only the label column
 * and spatial column(wrapped with 'ASTEXT()' function).
 *
 * @param string $sql_query             original sql query
 * @param array  $visualizationSettings settings for the visualization
 *
 * @return the modified sql query.
 */
function PMA_GIS_modifyQuery($sql_query, $visualizationSettings)
{
    $modified_query = 'SELECT ';

    $analyzed_query = PMA_SQP_analyze(PMA_SQP_parse($sql_query));
    // If select clause is not *
    if (trim($analyzed_query[0]['select_expr_clause']) != '*') {
        // If label column is chosen add it to the query
        if (isset($visualizationSettings['labelColumn'])
            && $visualizationSettings['labelColumn'] != ''
        ) {
            // Check to see whether an alias has been used on the label column
            $is_label_alias = false;
            foreach ($analyzed_query[0]['select_expr'] as $select) {
                if ($select['alias'] == $visualizationSettings['labelColumn']) {
                    $modified_query .= sanitize($select) . ' AS `'
                    . $select['alias'] . '`, ';
                    $is_label_alias = true;
                    break;
                }
            }
            // If no alias have been used on the label column
            if (! $is_label_alias) {
                foreach ($analyzed_query[0]['select_expr'] as $select) {
                    if ($select['column'] == $visualizationSettings['labelColumn']) {
                        $modified_query .= sanitize($select) . ', ';
                    }
                }
            }
        }

        // Check to see whether an alias has been used on the spatial column
        $is_spatial_alias = false;
        foreach ($analyzed_query[0]['select_expr'] as $select) {
            if ($select['alias'] == $visualizationSettings['spatialColumn']) {
                $sanitized = sanitize($select);
                $modified_query .= 'ASTEXT(' . $sanitized . ') AS `'
                . $select['alias'] . '`, ';
                // Get the SRID
                $modified_query .= 'SRID(' . $sanitized . ') AS `srid` ';
                $is_spatial_alias = true;
                break;
            }
        }
        // If no alias have been used on the spatial column
        if (! $is_spatial_alias) {
            foreach ($analyzed_query[0]['select_expr'] as $select) {
                if ($select['column'] == $visualizationSettings['spatialColumn']) {
                    $sanitized = sanitize($select);
                    $modified_query .= 'ASTEXT(' . $sanitized
                        . ') AS `' . $select['column'] . '`, ';
                    // Get the SRID
                    $modified_query .= 'SRID(' . $sanitized . ') AS `srid` ';
                }
            }
        }
        // If select clause is *
    } else {
        // If label column is chosen add it to the query
        if (isset($visualizationSettings['labelColumn'])
            && $visualizationSettings['labelColumn'] != ''
        ) {
            $modified_query .= '`' . $visualizationSettings['labelColumn'] .'`, ';
        }

        // Wrap the spatial column with 'ASTEXT()' function and add it
        $modified_query .= 'ASTEXT(`' . $visualizationSettings['spatialColumn']
            . '`) AS `' . $visualizationSettings['spatialColumn'] . '`, ';

        // Get the SRID
        $modified_query .= 'SRID(`' . $visualizationSettings['spatialColumn']
            . '`) AS `srid` ';
    }

    // Append the rest of the query
    $from_pos = stripos($sql_query, 'FROM');
    $modified_query .= substr($sql_query, $from_pos);
    return $modified_query;
}

// Local function to sanitize the expression taken
// from the results of PMA_SQP_analyze function.
function sanitize($select)
{
    $table_col = $select['table_name'] . "." . $select['column'];
    $db_table_col = $select['db'] . "." . $select['table_name']
        . "." . $select['column'];

    if ($select['expr'] == $select['column']) {
        return "`" . $select['column'] . "`";
    } elseif ($select['expr'] == $table_col) {
        return "`" . $select['table_name'] . "`.`" . $select['column'] . "`";
    } elseif ($select['expr'] == $db_table_col) {
        return "`" . $select['db'] . "`.`" . $select['table_name']
            . "`.`" . $select['column'] . "`";
    }
    return $select['expr'];
}

/**
 * Formats a visualization for the GIS query results.
 *
 * @param array  $data                   Data for the status chart
 * @param array  &$visualizationSettings Settings used to generate the chart
 * @param string $format                 Format of the visulaization
 *
 * @return string HTML and JS code for the GIS visualization
 */
function PMA_GIS_visualizationResults($data, &$visualizationSettings, $format)
{
    include_once './libraries/gis/pma_gis_visualization.php';
    include_once './libraries/gis/pma_gis_factory.php';

    if (! isset($data[0])) {
        // empty data
        return __('No data found for GIS visualization.');
    } else {
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
    }
}

/**
 * Generate visualization for the GIS query results and save it to a file.
 *
 * @param array  $data                  data for the status chart
 * @param array  $visualizationSettings settings used to generate the chart
 * @param string $format                format of the visulaization
 * @param string $fileName              file name
 *
 * @return file File containing the visualization
 */
function PMA_GIS_saveToFile($data, $visualizationSettings, $format, $fileName)
{
    include_once './libraries/gis/pma_gis_visualization.php';
    include_once './libraries/gis/pma_gis_factory.php';

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
?>
