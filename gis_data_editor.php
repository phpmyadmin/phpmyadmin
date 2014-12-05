<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Editor for Geometry data types.
 *
 * @package PhpMyAdmin
 */

/**
 * Escapes special characters if the variable is set.
 * Returns an empty string otherwise.
 *
 * @param string $variable variable to be escaped
 *
 * @return string escaped variable
 */
function escape($variable)
{
    return isset($variable) ? htmlspecialchars($variable) : '';
}

require_once 'libraries/common.inc.php';
require_once 'libraries/gis/GIS_Factory.class.php';
require_once 'libraries/gis/GIS_Visualization.class.php';
require_once 'libraries/tbl_gis_visualization.lib.php';

// Get data if any posted
$gis_data = array();
if (PMA_isValid($_REQUEST['gis_data'], 'array')) {
    $gis_data = $_REQUEST['gis_data'];
}

$gis_types = array(
    'POINT',
    'MULTIPOINT',
    'LINESTRING',
    'MULTILINESTRING',
    'POLYGON',
    'MULTIPOLYGON',
    'GEOMETRYCOLLECTION'
);

/** @var PMA_String $pmaString */
$pmaString = $GLOBALS['PMA_String'];

// Extract type from the initial call and make sure that it's a valid one.
// Extract from field's values if available, if not use the column type passed.
if (! isset($gis_data['gis_type'])) {
    if (isset($_REQUEST['type']) && $_REQUEST['type'] != '') {
        $gis_data['gis_type'] = /*overload*/mb_strtoupper($_REQUEST['type']);
    }
    if (isset($_REQUEST['value']) && trim($_REQUEST['value']) != '') {
        $start = (substr($_REQUEST['value'], 0, 1) == "'") ? 1 : 0;
        $gis_data['gis_type'] = /*overload*/mb_substr(
            $_REQUEST['value'],
            $start,
            /*overload*/mb_strpos($_REQUEST['value'], "(") - $start
        );
    }
    if ((! isset($gis_data['gis_type']))
        || (! in_array($gis_data['gis_type'], $gis_types))
    ) {
        $gis_data['gis_type'] = $gis_types[0];
    }
}
$geom_type = $gis_data['gis_type'];

// Generate parameters from value passed.
$gis_obj = PMA_GIS_Factory::factory($geom_type);
if (isset($_REQUEST['value'])) {
    $gis_data = array_merge(
        $gis_data, $gis_obj->generateParams($_REQUEST['value'])
    );
}

// Generate Well Known Text
$srid = (isset($gis_data['srid']) && $gis_data['srid'] != '')
    ? htmlspecialchars($gis_data['srid']) : 0;
$wkt = $gis_obj->generateWkt($gis_data, 0);
$wkt_with_zero = $gis_obj->generateWkt($gis_data, 0, '0');
$result = "'" . $wkt . "'," . $srid;

// Generate PNG or SVG based visualization
$format = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER <= 8)
    ? 'png' : 'svg';
$visualizationSettings = array(
    'width' => 450,
    'height' => 300,
    'spatialColumn' => 'wkt'
);
$data = array(array('wkt' => $wkt_with_zero, 'srid' => $srid));
$visualization = PMA_GIS_visualizationResults(
    $data, $visualizationSettings, $format
);
$open_layers = PMA_GIS_visualizationResults($data, $visualizationSettings, 'ol');

// If the call is to update the WKT and visualization make an AJAX response
if (isset($_REQUEST['generate']) && $_REQUEST['generate'] == true) {
    $extra_data = array(
        'result'        => $result,
        'visualization' => $visualization,
        'openLayers'    => $open_layers,
    );
    $response = PMA_Response::getInstance();
    $response->addJSON($extra_data);
    exit;
}

ob_start();

echo '<form id="gis_data_editor_form" action="gis_data_editor.php" method="post">';
echo '<input type="hidden" id="pmaThemeImage"'
    . ' value="' . $GLOBALS['pmaThemeImage'] . '" />';
echo '<div id="gis_data_editor">';

echo '<h3>';
printf(
    __('Value for the column "%s"'),
    htmlspecialchars($_REQUEST['field'])
);
echo '</h3>';

echo '<input type="hidden" name="field" value="'
    . htmlspecialchars($_REQUEST['field']) . '" />';
// The input field to which the final result should be added
// and corresponding null checkbox
if (isset($_REQUEST['input_name'])) {
    echo '<input type="hidden" name="input_name" value="'
        . htmlspecialchars($_REQUEST['input_name']) . '" />';
}
echo PMA_URL_getHiddenInputs();

echo '<!-- Visualization section -->';
echo '<div id="placeholder" style="width:450px;height:300px;'
    . ($srid != 0 ? 'display:none;' : '') . '">';
echo $visualization;
echo '</div>';

echo '<div id="openlayersmap" style="width:450px;height:300px;'
    . ($srid == 0 ? 'display:none;' : '') . '">';
echo '</div>';

echo '<div class="choice" style="float:right;clear:right;">';
echo '<input type="checkbox" id="choice" value="useBaseLayer"'
    . ($srid != 0 ? ' checked="checked"' : '') . '/>';
echo '<label for="choice">' .  __("Use OpenStreetMaps as Base Layer") . '</label>';
echo '</div>';

echo '<script language="javascript" type="text/javascript">';
echo $open_layers;
echo '</script>';
echo '<!-- End of visualization section -->';


echo '<!-- Header section - Inclueds GIS type selector and input field for SRID -->';
echo '<div id="gis_data_header">';
echo '<select name="gis_data[gis_type]" class="gis_type">';
foreach ($gis_types as $gis_type) {
    echo '<option value="' . $gis_type . '"';
    if ($geom_type == $gis_type) {
        echo ' selected="selected"';
    }
    echo '>' . $gis_type . '</option>';
}
echo '</select>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;';
/* l10n: Spatial Reference System Identifier */
echo '<label for="srid">' .  __('SRID:') . '</label>';
echo '<input name="gis_data[srid]" type="text" value="' . $srid . '" />';
echo '</div>';
echo '<!-- End of header section -->';

echo '<!-- Data section -->';
echo '<div id="gis_data">';

$geom_count = 1;
if ($geom_type == 'GEOMETRYCOLLECTION') {
    $geom_count = (isset($gis_data[$geom_type]['geom_count']))
        ? $gis_data[$geom_type]['geom_count'] : 1;
    if (isset($gis_data[$geom_type]['add_geom'])) {
        $geom_count++;
    }
    echo '<input type="hidden" name="gis_data[GEOMETRYCOLLECTION][geom_count]"'
        . ' value="' . $geom_count . '" />';
}

for ($a = 0; $a < $geom_count; $a++) {

    if ($geom_type == 'GEOMETRYCOLLECTION') {
        echo '<br/><br/>';
        printf(__('Geometry %d:'), $a + 1);
        echo '<br/>';
        if (isset($gis_data[$a]['gis_type'])) {
            $type = $gis_data[$a]['gis_type'];
        } else {
            $type = $gis_types[0];
        }
        echo '<select name="gis_data[' . $a . '][gis_type]" class="gis_type">';
        foreach (array_slice($gis_types, 0, 6) as $gis_type) {
            echo '<option value="' . $gis_type . '"';
            if ($type == $gis_type) {
                echo ' selected="selected"';
            }
            echo '>' . $gis_type . '</option>';
        }
        echo '</select>';
    } else {
        $type = $geom_type;
    }

    if ($type == 'POINT') {
        echo '<br/>';
        echo __('Point:');
        echo '<label for="x">' . __("X") . '</label>';
        echo '<input name="gis_data[' . $a . '][POINT][x]" type="text"'
            . ' value="' . escape($gis_data[$a]['POINT']['x']) . '" />';
        echo '<label for="y">' . __("Y") . '</label>';
        echo '<input name="gis_data[' . $a . '][POINT][y]" type="text"'
            . ' value="' . escape($gis_data[$a]['POINT']['y']) . '" />';

    } elseif ($type == 'MULTIPOINT' || $type == 'LINESTRING') {
        $no_of_points = isset($gis_data[$a][$type]['no_of_points'])
            ? $gis_data[$a][$type]['no_of_points'] : 1;
        if ($type == 'LINESTRING' && $no_of_points < 2) {
            $no_of_points = 2;
        }
        if ($type == 'MULTIPOINT' && $no_of_points < 1) {
            $no_of_points = 1;
        }

        if (isset($gis_data[$a][$type]['add_point'])) {
            $no_of_points++;
        }
        echo '<input type="hidden" value="' . $no_of_points . '"'
            . ' name="gis_data[' . $a . '][' . $type . '][no_of_points]" />';

        for ($i = 0; $i < $no_of_points; $i++) {
            echo '<br/>';
            printf(__('Point %d'), $i + 1);
            echo ': ';
            echo '<label for="x">' .  __("X") . '</label>';
            echo '<input type="text"'
                . ' name="gis_data[' . $a . '][' . $type . '][' . $i . '][x]"'
                . ' value="' . escape($gis_data[$a][$type][$i]['x']) . '" />';
            echo '<label for="y">' . __("Y") . '</label>';
            echo '<input type="text"'
                . ' name="gis_data[' . $a . '][' . $type . '][' . $i . '][y]"'
                . ' value="' . escape($gis_data[$a][$type][$i]['y']) . '" />';
        }
        echo '<input type="submit"'
            . ' name="gis_data[' . $a . '][' . $type . '][add_point]"'
            . ' class="add addPoint" value="' . __("Add a point") . '" />';

    } elseif ($type == 'MULTILINESTRING' || $type == 'POLYGON') {
        $no_of_lines = isset($gis_data[$a][$type]['no_of_lines'])
            ? $gis_data[$a][$type]['no_of_lines'] : 1;
        if ($no_of_lines < 1) {
            $no_of_lines = 1;
        }
        if (isset($gis_data[$a][$type]['add_line'])) {
            $no_of_lines++;
        }
        echo '<input type="hidden" value="' . $no_of_lines . '"'
            . ' name="gis_data[' . $a . '][' . $type . '][no_of_lines]" />';

        for ($i = 0; $i < $no_of_lines; $i++) {
            echo '<br/>';
            if ($type == 'MULTILINESTRING') {
                printf(__('Linestring %d:'), $i + 1);
            } else {
                if ($i == 0) {
                    echo __('Outer ring:');
                } else {
                    printf(__('Inner ring %d:'), $i);
                }
            }

            $no_of_points = isset($gis_data[$a][$type][$i]['no_of_points'])
                ? $gis_data[$a][$type][$i]['no_of_points'] : 2;
            if ($type == 'MULTILINESTRING' && $no_of_points < 2) {
                $no_of_points = 2;
            }
            if ($type == 'POLYGON' && $no_of_points < 4) {
                $no_of_points = 4;
            }
            if (isset($gis_data[$a][$type][$i]['add_point'])) {
                $no_of_points++;
            }
            echo '<input type="hidden" value="' . $no_of_points . '"'
                . ' name="gis_data[' . $a . '][' . $type . '][' . $i
                . '][no_of_points]" />';

            for ($j = 0; $j < $no_of_points; $j++) {
                echo('<br/>');
                printf(__('Point %d'), $j + 1);
                echo ': ';
                echo '<label for="x">' .  __("X") . '</label>';
                echo '<input type="text" name="gis_data[' . $a . '][' . $type . ']['
                    . $i . '][' . $j . '][x]" value="'
                    . escape($gis_data[$a][$type][$i][$j]['x']) . '" />';
                echo '<label for="y">' . __("Y") . '</label>';
                echo '<input type="text" name="gis_data[' . $a . '][' . $type . ']['
                    . $i . '][' . $j . '][y]"' . ' value="'
                    . escape($gis_data[$a][$type][$i][$j]['x']) . '" />';
            }
            echo '<input type="submit" name="gis_data[' . $a . '][' . $type . ']['
                . $i . '][add_point]"'
                . ' class="add addPoint" value="' . __("Add a point") . '" />';
        }
        $caption = ($type == 'MULTILINESTRING')
            ? __('Add a linestring')
            : __('Add an inner ring');
        echo '<br/>';
        echo '<input type="submit"'
            . ' name="gis_data[' . $a . '][' . $type . '][add_line]"'
            . ' class="add addLine" value="' . $caption . '" />';

    } elseif ($type == 'MULTIPOLYGON') {
        $no_of_polygons = isset($gis_data[$a][$type]['no_of_polygons'])
            ? $gis_data[$a][$type]['no_of_polygons'] : 1;
        if ($no_of_polygons < 1) {
            $no_of_polygons = 1;
        }
        if (isset($gis_data[$a][$type]['add_polygon'])) {
            $no_of_polygons++;
        }
        echo '<input type="hidden"'
            . ' name="gis_data[' . $a . '][' . $type . '][no_of_polygons]"'
            . ' value="' . $no_of_polygons . '" />';

        for ($k = 0; $k < $no_of_polygons; $k++) {
            echo '<br/>';
            printf(__('Polygon %d:'), $k + 1);
            $no_of_lines = isset($gis_data[$a][$type][$k]['no_of_lines'])
                ? $gis_data[$a][$type][$k]['no_of_lines'] : 1;
            if ($no_of_lines < 1) {
                $no_of_lines = 1;
            }
            if (isset($gis_data[$a][$type][$k]['add_line'])) {
                $no_of_lines++;
            }
            echo '<input type="hidden"'
                . ' name="gis_data[' . $a . '][' . $type . '][' . $k
                . '][no_of_lines]"' . ' value="' . $no_of_lines . '" />';

            for ($i = 0; $i < $no_of_lines; $i++) {
                echo '<br/><br/>';
                if ($i == 0) {
                    echo __('Outer ring:');
                } else {
                    printf(__('Inner ring %d:'), $i);
                }

                $no_of_points = isset($gis_data[$a][$type][$k][$i]['no_of_points'])
                    ? $gis_data[$a][$type][$k][$i]['no_of_points'] : 4;
                if ($no_of_points < 4) {
                    $no_of_points = 4;
                }
                if (isset($gis_data[$a][$type][$k][$i]['add_point'])) {
                    $no_of_points++;
                }
                echo '<input type="hidden"'
                    . ' name="gis_data[' . $a . '][' . $type . '][' . $k . '][' . $i
                    . '][no_of_points]"' . ' value="' . $no_of_points . '" />';

                for ($j = 0; $j < $no_of_points; $j++) {
                    echo '<br/>';
                    printf(__('Point %d'), $j + 1);
                    echo ': ';
                    echo '<label for="x">' .  __("X") . '</label>';
                    echo '<input type="text"'
                        . ' name="gis_data[' . $a . '][' . $type . '][' . $k . ']['
                        . $i . '][' . $j . '][x]"'
                        . ' value="' . escape($gis_data[$a][$type][$k][$i][$j]['x'])
                        . '" />';
                    echo '<label for="y">' . __("Y") . '</label>';
                    echo '<input type="text"'
                        . ' name="gis_data[' . $a . '][' . $type . '][' . $k . ']['
                        . $i . '][' . $j . '][y]"'
                        . ' value="' . escape($gis_data[$a][$type][$k][$i][$j]['y'])
                        . '" />';
                }
                echo '<input type="submit"'
                    . ' name="gis_data[' . $a . '][' . $type . '][' . $k . '][' . $i
                    . '][add_point]"'
                    . ' class="add addPoint" value="' . __("Add a point") . '" />';
            }
            echo '<br/>';
            echo '<input type="submit"'
                . ' name="gis_data[' . $a . '][' . $type . '][' . $k . '][add_line]"'
                . ' class="add addLine" value="' . __('Add an inner ring') . '" />';
            echo '<br/>';
        }
        echo '<br/>';
        echo '<input type="submit"'
            . ' name="gis_data[' . $a . '][' . $type . '][add_polygon]"'
            . ' class="add addPolygon" value="' .  __('Add a polygon') . '" />';
    }
}
if ($geom_type == 'GEOMETRYCOLLECTION') {
    echo '<br/><br/>';
    echo '<input type="submit" name="gis_data[GEOMETRYCOLLECTION][add_geom]"'
        . 'class="add addGeom" value="' . __("Add geometry") . '" />';
}
echo '</div>';
echo '<!-- End of data section -->';

echo '<br/>';
echo '<input type="submit" name="gis_data[save]" value="' . __('Go') . '" />';

echo '<div id="gis_data_output">';
echo '<h3>' . __('Output') . '</h3>';
echo '<p>';
echo __(
    'Choose "GeomFromText" from the "Function" column and paste the'
    . ' string below into the "Value" field.'
);
echo '</p>';
echo '<textarea id="gis_data_textarea" cols="95" rows="5">';
echo $result;
echo '</textarea>';
echo '</div>';

echo '</div>';
echo '</form>';

PMA_Response::getInstance()->addJSON('gis_editor', ob_get_contents());
ob_end_clean();
?>
