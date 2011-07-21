<?php
/**
 * Handles the visualization of GIS POINT objects.
 *
 * @package phpMyAdmin
 */
class PMA_GIS_Point extends PMA_GIS_Geometry
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
        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        return $this->setMinMax($point, array());
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS POINT object
     * @param string $label      Label for the GIS POINT object
     * @param string $line_color Color for the GIS POINT object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $image      Image object
     *
     * @return the modified image object
     */
    public function prepareRowAsPng($spatial, $label, $line_color, $scale_data, $image)
    {
        // allocate colors
        $r = hexdec(substr($line_color, 1, 2));
        $g = hexdec(substr($line_color, 3, 2));
        $b = hexdec(substr($line_color, 4, 2));
        $color = imagecolorallocate($image, $r, $g, $b);

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        $points_arr = $this->extractPoints($point, $scale_data);

        // draw a small circle to mark the point
        imagearc($image, $points_arr[0][0], $points_arr[0][1], 7, 7, 0, 360, $color);
        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS POINT object
     * @param string $label      Label for the GIS POINT object
     * @param string $line_color Color for the GIS POINT object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $pdf        TCPDF instance
     *
     * @return the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, $label, $line_color, $scale_data, $pdf)
    {
        // allocate colors
        $r = hexdec(substr($line_color, 1, 2));
        $g = hexdec(substr($line_color, 3, 2));
        $b = hexdec(substr($line_color, 4, 2));
        $line = array('width' => 1.25, 'color' => array($r, $g, $b));

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        $points_arr = $this->extractPoints($point, $scale_data);

        // draw a small circle to mark the point
        $pdf->Circle($points_arr[0][0], $points_arr[0][1], 2, 0, 360, 'D', $line);
        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       Label for the GIS POINT object
     * @param string $point_color Color for the GIS POINT object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $point_color, $scale_data)
    {
        $point_options = array(
            'name'        => $label,
            'id'          => $label . rand(),
            'class'       => 'point vector',
            'fill'        => 'white',
            'stroke'      => $point_color,
            'stroke-width'=> 2,
        );

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        $points_arr = $this->extractPoints($point, $scale_data);

        $row = '<circle cx="' . $points_arr[0][0] . '" cy="' . $points_arr[0][1] . '" r="3"';
        foreach ($point_options as $option => $val) {
            $row .= ' ' . $option . '="' . trim($val) . '"';
        }
        $row .= '/>';

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset to visualize it with OpenLayers.
     *
     * @param string $spatial     GIS POINT object
     * @param int    $srid        Spatial reference ID
     * @param string $label       Label for the GIS POINT object
     * @param string $point_color Color for the GIS POINT object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, $srid, $label, $point_color, $scale_data)
    {
        $style_options = array(
            'pointRadius'  => 3,
            'fillColor'    => '#ffffff',
            'strokeColor'  => $point_color,
            'strokeWidth'  => 2,
            'label'        => $label,
            'labelYOffset' => -8,
            'fontSize'     => 10,
        );
        if ($srid == 0) {
            $srid = 4326;
        }
        $result = $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        $points_arr = $this->extractPoints($point, null);

        $result .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector(('
            . 'new OpenLayers.Geometry.Point(' . $points_arr[0][0] . ', ' . $points_arr[0][1] . ')'
            . '.transform(new OpenLayers.Projection("EPSG:' . $srid . '"), map.getProjectionObject())),'
            . ' null, ' . json_encode($style_options) . '));';
        return $result;
    }
}
?>
