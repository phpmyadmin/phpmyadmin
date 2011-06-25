<?php

require_once './libraries/common.inc.php';
require_once './libraries/header_http.inc.php';
require_once './libraries/header_meta_style.inc.php';
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

$no_visual = true;
if (! isset($gis_data['gis_type'])) {
    $gis_data['gis_type'] = $gis_types[0];
    $no_visual = true;
}

$geom_type = $gis_data['gis_type'];

$gis_obj = PMA_GIS_Factory::factory($geom_type);
$srid = isset($gis_data['srid']) ? htmlspecialchars($gis_data['srid']) : '';
$wkt = $gis_obj->generateWkt($gis_data, 0);

$format = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER <= 8) ? 'svg' : 'png';
$visualizationSettings = array('width' => 400, 'height' => 400,);
$data = array($wkt);
if (! $no_visual) {
    $visualization = PMA_GIS_visualization_results($data, $visualizationSettings, $format);
}

if(isset($_REQUEST['generate']) && $_REQUEST['generate'] == true) {
    $extra_data = array(
        'wkt'           => $wkt,
        'srid'          => $srid,
        'visualization' => $visualization,
    );
    PMA_ajaxResponse(null, true, $extra_data);
}
?>

</head>

<body>
    <form action="gis_data_editor.php" method="post">
    <div id="gis_data_editor_no_js">
        <h3><?php printf(__('Value for the column "%s"'), htmlspecialchars($_REQUEST['field'])); ?></h3>
<?php   echo('<input type="hidden" name="field" value="' . htmlspecialchars($_REQUEST['field']) . '">');
        echo PMA_generate_common_hidden_inputs($url_params);
?>
        <div id="placeholder">
             <?php if (! $no_visual) {echo $visualization;} ?>
        </div>
        <div id="gis_data_header">
            <select name="gis_data[gis_type]">
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
            <input type="submit" name="gis_data[go]" value="<?php echo __("Go")?>" />
            <label for="srid"><?php echo __("SRID"); ?>:&nbsp;</label>
            <input name="gis_data[srid]" type="text" value="<?php echo($srid); ?>" />
        </div>
        <div id="gis_data">
<?php
        $geom_count = 1;
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
                echo('<select name="gis_data[' . $a . '][gis_type]">');
                foreach (array_slice($gis_types, 0, 6) as $gis_type) {
                    echo('<option value="' . $gis_type . '"');
                    if ($type == $gis_type) {
                        echo(' selected="selected"');
                    }
                    echo('>' . $gis_type . '</option>');
                }
                echo('</select>');
                echo('<input type="submit" name="gis_data[' . $a . '][go]" value="'); echo __("Go"); echo('">');
            } else {
                $type = $geom_type;
            }

            if ($type == 'POINT') {
                echo('<br/>'); echo __("Point");
?>              <label for="x"><?php echo __("X"); ?>:&nbsp;</label>
                <input name="gis_data[<?php echo($a); ?>][POINT][x]" type="text" value="<?php echo(isset($gis_data[$a]['POINT']['x']) ? htmlspecialchars($gis_data[$a]['POINT']['x']) : ''); ?>" />
                <label for="y"><?php echo __("Y"); ?>:&nbsp;</label>
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
                    echo('<br/>'); echo __("Point"); echo($i + 1 . ':');
?>                  <label for="x"><?php echo  __("X"); ?></label>
                    <input type="text" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][x]" value="<?php echo(isset($gis_data[$a][$type][$i]['x']) ? htmlspecialchars($gis_data[$a][$type][$i]['x']) : ''); ?>" />
                    <label for="y"><?php echo  __("Y"); ?></label>
                    <input type="text" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][y]" value="<?php echo(isset($gis_data[$a][$type][$i]['y']) ? htmlspecialchars($gis_data[$a][$type][$i]['y']) : ''); ?>" />
<?php
                }
?>
               <input type="submit" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][add_point]" value="<?php echo __("Add a point"); ?>">
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
                            echo __("Outer Ring:");
                        } else {
                            echo __("Inner Ring"); echo($i . ':');
                        }
                    }

                    $no_of_points = isset($gis_data[$a][$type][$i]['no_of_points']) ? $gis_data[$a][$type][$i]['no_of_points'] : 2;
                    if ($type == 'MULTILINESTRING' && $no_of_points < 2) {
                        $no_of_points = 2;
                    }
                    if ($type == 'POLYGON' && $no_of_points < 3) {
                        $no_of_points = 3;
                    }
                    if (isset($gis_data[$a][$type][$i]['add_point'])) {
                        $no_of_points++;
                    }
                    echo('<input type="hidden" name="gis_data[' . $a . '][' . $type . '][' . $i . '][no_of_points]" value="' . $no_of_points . '">');

                    for ($j = 0; $j < $no_of_points; $j++) {
                        echo('<br/>'); echo __("Point"); echo($j + 1 . ':');
?>                      <label for="x"><?php echo  __("X"); ?></label>
                        <input type="text" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][<?php echo($j); ?>][x]" value="<?php echo(isset($gis_data[$a][$type][$i][$j]['x']) ? htmlspecialchars($gis_data[$a][$type][$i][$j]['x']) : ''); ?>" />
                        <label for="y"><?php echo  __("Y"); ?></label>
                        <input type="text" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][<?php echo($j); ?>][y]" value="<?php echo(isset($gis_data[$a][$type][$i][$j]['x']) ? htmlspecialchars($gis_data[$a][$type][$i][$j]['y']) : ''); ?>" />
<?php               }
?>                  <input type="submit" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][<?php echo($i); ?>][add_point]" value="<?php echo __("Add a point"); ?>">
<?php           }
                $caption = ($type == 'MULTILINESTRING') ? __('Add a linestring') : __('Add an inner ring');
?>              <br/><input type="submit" name="gis_data[<?php echo($a); ?>][<?php echo($type); ?>][add_line]" value="<?php echo($caption); ?>">
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
                            echo __("Outer Ring:");
                        } else {
                            echo __("Inner Ring"); echo($i . ':');
                        }

                        $no_of_points = isset($gis_data[$a][$type][$k][$i]['no_of_points']) ? $gis_data[$a][$type][$k][$i]['no_of_points'] : 3;
                        if ($no_of_points < 3) {
                            $no_of_points = 3;
                        }
                        if (isset($gis_data[$a][$type][$k][$i]['add_point'])) {
                            $no_of_points++;
                        }
                        echo('<input type="hidden" name="gis_data[' . $a . '][' . $type . '][' . $k . '][' . $i . '][no_of_points]" value="' . $no_of_points . '">');

                        for ($j = 0; $j < $no_of_points; $j++) {
                            echo('<br/>'); echo __("Point"); echo($j + 1 . ':');
?>                          <label for="x"><?php echo  __("X"); ?></label>
                            <input type="text" name="<?php echo("gis_data[" . $a . "][" . $type . "][" . $k . "][" . $i . "][" . $j . "][x]"); ?>" value="<?php echo(isset($gis_data[$a][$type][$k][$i][$j]['x']) ? htmlspecialchars($gis_data[$a][$type][$k][$i][$j]['x']) : ''); ?>" />
                            <label for="y"><?php echo  __("Y"); ?></label>
                            <input type="text" name="<?php echo("gis_data[" . $a . "][" . $type . "][" . $k . "][" . $i . "][" . $j . "][y]"); ?>" value="<?php echo(isset($gis_data[$a][$type][$k][$i][$j]['y']) ? htmlspecialchars($gis_data[$a][$type][$k][$i][$j]['y']) : ''); ?>" />
<?php                   }
?>                      <input type="submit" name="<?php echo("gis_data[" . $a . "][" . $type . "][" . $k . "][" . $i . "][add_point]"); ?>" value="<?php echo __("Add a point"); ?>">
<?php               }
?>                  <br/><input type="submit" name="<?php echo("gis_data[" . $a . "][" . $type . "][" . $k . "][add_line]"); ?>" value="<?php echo __('Add an inner ring') ?>">
<?php           }
?>              <br/><br/><input type="submit" name="<?php echo("gis_data[" . $a . "][" . $type . "][add_polygon]"); ?>" value="<?php echo __('Add a polygon') ?>">
<?php       }
        }
        if ($geom_type == 'GEOMETRYCOLLECTION') {
?>          <br/><br/><input type="submit" name="gis_data[GEOMETRYCOLLECTION][add_geom]" value="<?php  echo __("Add geometry"); ?>" />
<?php   }
?>
        </div>
        <br/><input type="submit" name="gis_data[save]" value="<?php echo __('Go') ?>">
        <div id="gis_data_output">
            <h3><?php echo __('Output'); ?></h3>
            <p><?php echo __('Chose "GeomFromText" from the "Function" column and paste the below string into the "Value" field'); ?></p>
            <textarea id="gis_data_textarea" cols="95" rows="5">
<?php       echo("'" . $wkt . "'");
            if ($srid != '') {
                echo(',' . $srid);
            }
?>          </textarea>
        </div>
    </div>
    </form>
</body>
<?php
/**
 * Displays the footer
 */
require './libraries/footer.inc.php';

?>