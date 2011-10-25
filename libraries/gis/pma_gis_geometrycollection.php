<?php
/**
 * Handles the visualization of GIS GEOMETRYCOLLECTION objects.
 *
 * @package PhpMyAdmin-GIS
 */
class PMA_GIS_Geometrycollection extends PMA_GIS_Geometry
{
    // Hold the singleton instance of the class
    private static $_instance;

    /**
     * A private constructor; prevents direct creation of object.
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return the singleton
     */
    public static function singleton()
    {
        if (!isset(self::$_instance)) {
            $class = __CLASS__;
            self::$_instance = new $class;
        }

        return self::$_instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array containing the min, max values for x and y cordinates
     */
    public function scaleRow($spatial)
    {
        $min_max = array();

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = substr($spatial, 19, (strlen($spatial) - 20));

        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->_explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = stripos($sub_part, '(');
            $type = substr($sub_part, 0, $type_pos);

            $gis_obj = PMA_GIS_Factory::factory($type);
            if (! $gis_obj) {
                continue;
            }
            $scale_data = $gis_obj->scaleRow($sub_part);

            // Upadate minimum/maximum values for x and y cordinates.
            $c_maxX = (float) $scale_data['maxX'];
            if (! isset($min_max['maxX']) || $c_maxX > $min_max['maxX']) {
                $min_max['maxX'] = $c_maxX;
            }

            $c_minX = (float) $scale_data['minX'];
            if (! isset($min_max['minX']) || $c_minX < $min_max['minX']) {
                $min_max['minX'] = $c_minX;
            }

            $c_maxY = (float) $scale_data['maxY'];
            if (! isset($min_max['maxY']) || $c_maxY > $min_max['maxY']) {
                $min_max['maxY'] = $c_maxY;
            }

            $c_minY = (float) $scale_data['minY'];
            if (! isset($min_max['minY']) || $c_minY < $min_max['minY']) {
                $min_max['minY'] = $c_minY;
            }
        }
        return $min_max;
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS GEOMETRYCOLLECTION object
     * @param string $label      Label for the GIS GEOMETRYCOLLECTION object
     * @param string $color      Color for the GIS GEOMETRYCOLLECTION object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $image      Image object
     *
     * @return the modified image object
     */
    public function prepareRowAsPng($spatial, $label, $color, $scale_data, $image)
    {
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = substr($spatial, 19, (strlen($spatial) - 20));
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->_explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = stripos($sub_part, '(');
            $type = substr($sub_part, 0, $type_pos);

            $gis_obj = PMA_GIS_Factory::factory($type);
            if (! $gis_obj) {
                continue;
            }
            $image = $gis_obj->prepareRowAsPng($sub_part, $label, $color, $scale_data, $image);
        }
        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS GEOMETRYCOLLECTION object
     * @param string $label      Label for the GIS GEOMETRYCOLLECTION object
     * @param string $color      Color for the GIS GEOMETRYCOLLECTION object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $pdf        TCPDF instance
     *
     * @return the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, $label, $color, $scale_data, $pdf)
    {
        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = substr($spatial, 19, (strlen($spatial) - 20));
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->_explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = stripos($sub_part, '(');
            $type = substr($sub_part, 0, $type_pos);

            $gis_obj = PMA_GIS_Factory::factory($type);
            if (! $gis_obj) {
                continue;
            }
            $pdf = $gis_obj->prepareRowAsPdf($sub_part, $label, $color, $scale_data, $pdf);
        }
        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS GEOMETRYCOLLECTION object
     * @param string $label      Label for the GIS GEOMETRYCOLLECTION object
     * @param string $color      Color for the GIS GEOMETRYCOLLECTION object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $color, $scale_data)
    {
        $row = '';

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = substr($spatial, 19, (strlen($spatial) - 20));
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->_explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = stripos($sub_part, '(');
            $type = substr($sub_part, 0, $type_pos);

            $gis_obj = PMA_GIS_Factory::factory($type);
            if (! $gis_obj) {
                continue;
            }
            $row .= $gis_obj->prepareRowAsSvg($sub_part, $label, $color, $scale_data);
        }
        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS GEOMETRYCOLLECTION object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS GEOMETRYCOLLECTION object
     * @param string $color      Color for the GIS GEOMETRYCOLLECTION object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, $srid, $label, $color, $scale_data)
    {
        $row = '';

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = substr($spatial, 19, (strlen($spatial) - 20));
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->_explodeGeomCol($goem_col);

        foreach ($sub_parts as $sub_part) {
            $type_pos = stripos($sub_part, '(');
            $type = substr($sub_part, 0, $type_pos);

            $gis_obj = PMA_GIS_Factory::factory($type);
            if (! $gis_obj) {
                continue;
            }
            $row .= $gis_obj->prepareRowAsOl($sub_part, $srid, $label, $color, $scale_data);
        }
        return $row;
    }

    /**
     * Split the GEOMETRYCOLLECTION object and get its constituents.
     *
     * @param string $goem_col Geometry collection string
     *
     * @return the constituents of the geometry collection object
     */
    private function _explodeGeomCol($goem_col)
    {
        $sub_parts = array();
        $br_count = 0;
        $start = 0;
        $count = 0;
        foreach (str_split($goem_col) as $char) {
            if ($char == '(') {
                $br_count++;
            } elseif ($char == ')') {
                $br_count--;
                if ($br_count == 0) {
                    $sub_parts[] = substr($goem_col, $start, ($count + 1 - $start));
                    $start = $count + 2;
                }
            }
            $count++;
        }
        return $sub_parts;
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array  $gis_data GIS data
     * @param int    $index    Index into the parameter object
     * @param string $empty    Value for empty points
     *
     * @return WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt($gis_data, $index, $empty = '')
    {
        $geom_count = (isset($gis_data['GEOMETRYCOLLECTION']['geom_count']))
            ? $gis_data['GEOMETRYCOLLECTION']['geom_count'] : 1;
        $wkt = 'GEOMETRYCOLLECTION(';
        for ($i = 0; $i < $geom_count; $i++) {
            if (isset($gis_data[$i]['gis_type'])) {
                $type = $gis_data[$i]['gis_type'];
                $gis_obj = PMA_GIS_Factory::factory($type);
                if (! $gis_obj) {
                    continue;
                }
                $wkt .= $gis_obj->generateWkt($gis_data, $i, $empty) . ',';
            }
        }
        if (isset($gis_data[0]['gis_type'])) {
            $wkt = substr($wkt, 0, strlen($wkt) - 1);
        }
        $wkt .= ')';
        return $wkt;
    }

    /** Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value of the GIS column
     * @param index  $index of the geometry
     *
     * @return  parameters for the GIS data editor from the value of the GIS column
     */
    public function generateParams($value)
    {
        $params = array();
        $data = PMA_GIS_Geometry::generateParams($value);
        $params['srid'] = $data['srid'];
        $wkt = $data['wkt'];

        // Trim to remove leading 'GEOMETRYCOLLECTION(' and trailing ')'
        $goem_col = substr($wkt, 19, (strlen($wkt) - 20));
        // Split the geometry collection object to get its constituents.
        $sub_parts = $this->_explodeGeomCol($goem_col);
        $params['GEOMETRYCOLLECTION']['geom_count'] = count($sub_parts);

        $i = 0;
        foreach ($sub_parts as $sub_part) {
            $type_pos = stripos($sub_part, '(');
            $type = substr($sub_part, 0, $type_pos);

            $gis_obj = PMA_GIS_Factory::factory($type);
            if (! $gis_obj) {
                continue;
            }
            $params = array_merge($params, $gis_obj->generateParams($sub_part, $i));
            $i++;
        }
        return $params;
    }
}
?>
