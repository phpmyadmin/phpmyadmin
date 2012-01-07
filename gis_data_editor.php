<?php
require_once './libraries/common.inc.php';
if (! isset($_REQUEST['get_gis_editor']) && ! isset($_REQUEST['generate'])) {
    include_once './libraries/header_http.inc.php';
    include_once './libraries/header_meta_style.inc.php';
}
require_once './libraries/gis/pma_gis_factory.php';
require_once './libraries/gis_visualization.lib.php';

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

// Extract type from the initial call and make sure that it's a valid one.
// Extract from field's values if availbale, if not use the column type passed.
if (! isset($gis_data['gis_type'])) {
    if (isset($_REQUEST['type']) && $_REQUEST['type'] != '') {
        $gis_data['gis_type'] = strtoupper($_REQUEST['type']);
    }
    if (isset($_REQUEST['value']) && trim($_REQUEST['value']) != '') {
        $start = (substr($_REQUEST['value'], 0, 1) == "'") ? 1 : 0;
        $gis_data['gis_type'] = substr($_REQUEST['value'], $start, strpos($_REQUEST['value'], "(") - $start);
    }
    if ((! isset($gis_data['gis_type'])) || (! in_array($gis_data['gis_type'], $gis_types))) {
        $gis_data['gis_type'] = $gis_types[0];
    }
}
$geom_type = $gis_data['gis_type'];

// Generate parameters from value passed.
$gis_obj = PMA_GIS_Factory::factory($geom_type);
if (isset($_REQUEST['value'])) {
    $gis_data = array_merge($gis_data, $gis_obj->generateParams($_REQUEST['value']));
}

// Generate Well Known Text
$srid = (isset($gis_data['srid']) && $gis_data['srid'] != '') ? htmlspecialchars($gis_data['srid']) : 0;
$wkt = $gis_obj->generateWkt($gis_data, 0);
$wkt_with_zero = $gis_obj->generateWkt($gis_data, 0, '0');
$result = "'" . $wkt . "'," . $srid;

// Generate PNG or SVG based visualization
$format = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER <= 8) ? 'png' : 'svg';
$visualizationSettings = array('width' => 450, 'height' => 300, 'spatialColumn' => 'wkt');
$data = array(array('wkt' => $wkt_with_zero, 'srid' => $srid));
$visualization = PMA_GIS_visualizationResults($data, $visualizationSettings, $format);
$open_layers = PMA_GIS_visualizationResults($data, $visualizationSettings, 'ol');

// If the call is to update the WKT and visualization make an AJAX response
if (isset($_REQUEST['generate']) && $_REQUEST['generate'] == true) {
    $extra_data = array(
        'result'        => $result,
        'visualization' => $visualization,
        'openLayers'    => $open_layers,
    );
    PMA_ajaxResponse(null, true, $extra_data);
}

// If the call is to get the whole content, start buffering, skipping </head> and <body> tags
if (isset($_REQUEST['get_gis_editor']) && $_REQUEST['get_gis_editor'] == true) {
    ob_start();
} else {
?>
</head>
<body>
<?php
}
?>
    <form id="gis_data_editor_form" action="gis_data_editor.php" method="post">
    <input type="hidden" id="pmaThemeImage" value="<?php echo($GLOBALS['pmaThemeImage']); ?>" />
    <div id="gis_data_editor">
        <h3><?php printf(__('Value for the column "%s"'), htmlspecialchars($_REQUEST['field'])); ?></h3>

<?php   echo('<input type="hidden" name="field" value="' . htmlspecialchars($_REQUEST['field']) . '" />');
        // The input field to which the final result should be added and corresponding null checkbox
        if (isset($_REQUEST['input_name'])) {
            echo('<input type="hidden" name="input_name" value="' . htmlspecialchars($_REQUEST['input_name']) . '" />');
        }
        echo PMA_generate_common_hidden_inputs();
?>
        <!-- Visualization section -->
        <div id="placeholder" style="width:450px;height:300px;
<?php       if ($srid != 0) {
                echo('display:none;');
            }
?>      ">
<?php       echo ($visualization);
?>      </div>
        <div id="openlayersmap" style="width:450px;height:300px;
<?php       if ($srid == 0) {
                echo('display:none;');
            }
?>      ">
        </div>
        <div class="choice" style="float:right;clear:right;">
            <input type="checkbox" id="choice" value="useBaseLayer"
<?php       if ($srid != 0) {
                echo(' checked="checked"');
            }
?>          />
            <label for="choice"><?php echo __("Use OpenStreetMaps as Base Layer"); ?></label>
        </div>
        <script language="javascript" type="text/javascript">
            <?php echo($open_layers); ?>
        </script>
        <!-- End of visualization section -->

        <!-- Header section - Inclueds GIS type selector and input field for SRID -->
        <div id="gis_data_header">
            <select name="gis_data[gis_type]" class="gis_type">
<?php
                foreach ($gis_types as $gis_type) {
                    echo('<option value="' . $gis_type . '"');
                    if ($geom_type == $gis_type) {
                        echo(' selected="selected"');
                    }
                    echo('>' . $gis_type . '</option>');
                }
?>
            </select>
            <input type="submit" name="gis_data[go]" class="go" value="<?php echo __("Go")?>" />
            <label for="srid"><?php echo __("SRID"); ?>:&nbsp;</label>
            <input name="gis_data[srid]" type="text" value="<?php echo($srid); ?>" />
        </div>
        <!-- End of header section -->

        <!-- Data section -->
        <div id="gis_data">
<?php   $geom_count = 1;
        if ($geom_type == 'GEOMETRYCOLLECTION') {
            $geom_count = (isset($gis_data[$geom_type]['geom_count'])) ? $gis_data[$geom_type]['geom_count'] : 1;
            if (isset($gis_data[$geom_type]['add_geom'])) {
                $geom_count++;
            }
            echo('<input type="hidden" name="gis_data[GEOMETRYCOLLECTION][geom_count]" value="' . $geom_count . '">');
        }
        for ($a = 0; $a < $geom_count; $a++) {
            if ($geom_type == 'GEOMETRYCOLLECTION') {
                echo('<br/><br/>'); echo __("Geometry"); echo($a + 1 . ':<br/>');
                if (isset($gis_data[$a]['gis_type'])) {
                    $type = $gis_data[$a]['gis_type'];
                } else {
                    $type = $gis_types[0];
                }
                echo('<select name="gis_data[' . $a . '][gis_type]" class="gis_type">');
                foreach (array_slice($gis_types, 0, 6) as $gis_type) {
                    echo('<option value="' . $gis_type . '"');
                    if ($type == $gis_type) {
                        echo(' selected="selected"');
                    }
                    echo('>' . $gis_type . '</option>');
                }
                echo('</select>');
                echo('<input type="submit" name="gis_data[' . $a . '][go]" class="go" value="'); echo __("Go"); echo('">');
            } else {
                $type = $geom_type;
            }

            if ($type == 'POINT') {
                echo('<br/>'); echo __("Point"); echo(' :');
?>              <label for="x"><?php echo __("X"); ?></label>
                <input name="gis_data[<?php echo($a); ?>][POINT][x]" type="text" value="<?php echo(isset($gis_data[$a]['POINT']['x']) ? htmlspecialchars($gis_data[$a]['POINT']['x']) : ''); ?>" />
                <label for="y"><?php echo __("Y"); ?></label>
                <input name="gis_data[<?php echo($a); ?>][POINT][y]" type="text" value="<?php echo(isset($gis_data[$a]['POINT']['y']) ? htmlspecialchars($gis_data[$a]['POINT']['y']) : ''); ?>" />
<?php
            } elseif ($type == 'MULTIPOINT' || $type == 'LINESTRING') {

                $no_of_points = isset($gis_data[$a][$type]['no_of_points']) ? $gis_data[$a][$type]['no_of_points'] : 1;
                if ($type == 'LINESTRING' && $no_of_points < 2) {
                    $no_of_points = 2;
                }
                if ($type == 'MULTIPOINT' && $no_of_points < 1) {
                    $no_of_points = 1;
                }

                if (isset($gis_data[$a][$type]['add_point'])) {
                    $no_of_points++;
                }
                echo('<input type="hidden" name="gis_data[' . $a . '][' . $type . '][no_of_points]" value="' . $no_of_points . '">');

                for ($i = 0; $i < $no_of_points; $i++) {
                    echo('<br/>');
                    printf(__('Point %d'), $i + 1);
                    echo ':';
?>                  <label for="x"><?php echo  __("X"); ?></label>
                    <input type="text" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][x]" value="<?php echo(isset($gis_data[$a][$type][$i]['x']) ? htmlspecialchars($gis_data[$a][$type][$i]['x']) : ''); ?>" />
                    <label for="y"><?php echo  __("Y"); ?></label>
                    <input type="text" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][y]" value="<?php echo(isset($gis_data[$a][$type][$i]['y']) ? htmlspecialchars($gis_data[$a][$type][$i]['y']) : ''); ?>" />
<?php
                }
?>
               <input type="submit" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][add_point]" class="add addPoint" value="<?php echo __("Add a point"); ?>">
<?php
            } elseif ($type == 'MULTILINESTRING' || $type == 'POLYGON') {

                $no_of_lines = isset($gis_data[$a][$type]['no_of_lines']) ? $gis_data[$a][$type]['no_of_lines'] : 1;
                if ($no_of_lines < 1) {
                    $no_of_lines = 1;
                }
                if (isset($gis_data[$a][$type]['add_line'])) {
                    $no_of_lines++;
                }
                echo('<input type="hidden" name="gis_data[' . $a . '][' . $type . '][no_of_lines]" value="' . $no_of_lines . '">');

                for ($i = 0; $i < $no_of_lines; $i++) {
                    echo('<br/>');
                    if ($type == 'MULTILINESTRING') {
                        echo __("Linestring"); echo($i + 1 . ':');
                    } else {
                        if ($i == 0) {
                            echo __("Outer Ring") . ':';
                        } else {
                            echo __("Inner Ring"); echo($i . ':');
                        }
                    }

                    $no_of_points = isset($gis_data[$a][$type][$i]['no_of_points']) ? $gis_data[$a][$type][$i]['no_of_points'] : 2;
                    if ($type == 'MULTILINESTRING' && $no_of_points < 2) {
                        $no_of_points = 2;
                    }
                    if ($type == 'POLYGON' && $no_of_points < 4) {
                        $no_of_points = 4;
                    }
                    if (isset($gis_data[$a][$type][$i]['add_point'])) {
                        $no_of_points++;
                    }
                    echo('<input type="hidden" name="gis_data[' . $a . '][' . $type . '][' . $i . '][no_of_points]" value="' . $no_of_points . '">');

                    for ($j = 0; $j < $no_of_points; $j++) {
                        echo('<br/>');
                        printf(__('Point %d'), $j + 1);
                        echo ':';
?>                      <label for="x"><?php echo  __("X"); ?></label>
                        <input type="text" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][<?php echo($j); ?>][x]" value="<?php echo(isset($gis_data[$a][$type][$i][$j]['x']) ? htmlspecialchars($gis_data[$a][$type][$i][$j]['x']) : ''); ?>" />
                        <label for="y"><?php echo  __("Y"); ?></label>
                        <input type="text" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][<?php echo($j); ?>][y]" value="<?php echo(isset($gis_data[$a][$type][$i][$j]['x']) ? htmlspecialchars($gis_data[$a][$type][$i][$j]['y']) : ''); ?>" />
<?php               }
?>                  <input type="submit" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][add_point]" class="add addPoint" value="<?php echo __("Add a point"); ?>">
<?php           }
                $caption = ($type == 'MULTILINESTRING') ? __('Add a linestring') : __('Add an inner ring');
?>              <br/><input type="submit" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][add_line]" class="add addLine" value="<?php echo($caption); ?>">
<?php
            } elseif ($type == 'MULTIPOLYGON') {
                $no_of_polygons = isset($gis_data[$a][$type]['no_of_polygons']) ? $gis_data[$a][$type]['no_of_polygons'] : 1;
                if ($no_of_polygons < 1) {
                    $no_of_polygons = 1;
                }
                if (isset($gis_data[$a][$type]['add_polygon'])) {
                    $no_of_polygons++;
                }
                echo('<input type="hidden" name="gis_data[' . $a . '][' . $type . '][no_of_polygons]" value="' . $no_of_polygons . '">');

                for ($k = 0; $k < $no_of_polygons; $k++) {
                    echo('<br/>'); echo __("Polygon"); echo($k + 1 . ':');
                    $no_of_lines = isset($gis_data[$a][$type][$k]['no_of_lines']) ? $gis_data[$a][$type][$k]['no_of_lines'] : 1;
                    if ($no_of_lines < 1) {
                        $no_of_lines = 1;
                    }
                    if (isset($gis_data[$a][$type][$k]['add_line'])) {
                        $no_of_lines++;
                    }
                    echo('<input type="hidden" name="gis_data[' . $a . '][' . $type . '][' . $k . '][no_of_lines]" value="' . $no_of_lines . '">');

                    for ($i = 0; $i < $no_of_lines; $i++) {
                        echo('<br/><br/>');
                        if ($i == 0) {
                            echo __("Outer Ring") . ':';
                        } else {
                            echo __("Inner Ring"); echo($i . ':');
                        }

                        $no_of_points = isset($gis_data[$a][$type][$k][$i]['no_of_points']) ? $gis_data[$a][$type][$k][$i]['no_of_points'] : 4;
                        if ($no_of_points < 4) {
                            $no_of_points = 4;
                        }
                        if (isset($gis_data[$a][$type][$k][$i]['add_point'])) {
                            $no_of_points++;
                        }
                        echo('<input type="hidden" name="gis_data[' . $a . '][' . $type . '][' . $k . '][' . $i . '][no_of_points]" value="' . $no_of_points . '">');

                        for ($j = 0; $j < $no_of_points; $j++) {
                            echo('<br/>');
                            printf(__('Point %d'), $j + 1);
                            echo ':';
?>                          <label for="x"><?php echo  __("X"); ?></label>
                            <input type="text" name="<?php echo("gis_data[" . $a . "][" . $type . "][" . $k . "][" . $i . "][" . $j . "][x]"); ?>" value="<?php echo(isset($gis_data[$a][$type][$k][$i][$j]['x']) ? htmlspecialchars($gis_data[$a][$type][$k][$i][$j]['x']) : ''); ?>" />
                            <label for="y"><?php echo  __("Y"); ?></label>
                            <input type="text" name="<?php echo("gis_data[" . $a . "][" . $type . "][" . $k . "][" . $i . "][" . $j . "][y]"); ?>" value="<?php echo(isset($gis_data[$a][$type][$k][$i][$j]['y']) ? htmlspecialchars($gis_data[$a][$type][$k][$i][$j]['y']) : ''); ?>" />
<?php                   }
?>                      <input type="submit" name="<?php echo("gis_data[" . $a . "][" . $type . "][" . $k . "][" . $i . "][add_point]"); ?>" class="add addPoint" value="<?php echo __("Add a point"); ?>">
<?php               }
?>                  <br/><input type="submit" name="<?php echo("gis_data[" . $a . "][" . $type . "][" . $k . "][add_line]"); ?>" class="add addLine" value="<?php echo __('Add an inner ring') ?>"><br/>
<?php           }
?>              <br/><input type="submit" name="<?php echo("gis_data[" . $a . "][" . $type . "][add_polygon]"); ?>" class="add addPolygon" value="<?php echo __('Add a polygon') ?>">
<?php       }
        }
        if ($geom_type == 'GEOMETRYCOLLECTION') {
?>          <br/><br/><input type="submit" name="gis_data[GEOMETRYCOLLECTION][add_geom]" class="add addGeom" value="<?php  echo __("Add geometry"); ?>" />
<?php   }
?>      </div>
        <!-- End of data section -->

        <br/><input type="submit" name="gis_data[save]" value="<?php echo __('Go') ?>">
        <div id="gis_data_output">
            <h3><?php echo __('Output'); ?></h3>
            <p><?php echo __('Chose "GeomFromText" from the "Function" column and paste the below string into the "Value" field'); ?></p>
            <textarea id="gis_data_textarea" cols="95" rows="5">
<?php           echo($result);
?>          </textarea>
        </div>
    </div>
    </form>
<?php

// If the call is to get the whole content, get the content in the buffer and make and AJAX response.
if (isset($_REQUEST['get_gis_editor']) && $_REQUEST['get_gis_editor'] == true) {
    $extra_data['gis_editor'] = ob_get_contents();
    PMA_ajaxResponse(null, ob_end_clean(), $extra_data);
}
?>
</body>

<?php
/**
 * Displays the footer
 */
require './libraries/footer.inc.php';

?>
