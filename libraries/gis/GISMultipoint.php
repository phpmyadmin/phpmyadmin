<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles actions related to GIS MULTIPOINT objects
 *
 * @package PhpMyAdmin-GIS
 */

namespace PMA\libraries\gis;

use \TCPDF;

/**
 * Handles actions related to GIS MULTIPOINT objects
 *
 * @package PhpMyAdmin-GIS
 */
class GISMultipoint extends GISGeometry
{
    // Hold the singleton instance of the class
    private static $_instance;

    /**
     * A private constructor; prevents direct creation of object.
     *
     * @access private
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return GISMultipoint the singleton
     * @access public
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
     * @return array an array containing the min, max values for x and y coordinates
     * @access public
     */
    public function scaleRow($spatial)
    {
        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint
            = mb_substr(
                $spatial,
                11,
                mb_strlen($spatial) - 12
            );

        return $this->setMinMax($multipoint, array());
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param string $label       Label for the GIS MULTIPOINT object
     * @param string $point_color Color for the GIS MULTIPOINT object
     * @param array  $scale_data  Array containing data related to scaling
     * @param object $image       Image object
     *
     * @return object the modified image object
     * @access public
     */
    public function prepareRowAsPng(
        $spatial,
        $label,
        $point_color,
        $scale_data,
        $image
    ) {
        // allocate colors
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = hexdec(mb_substr($point_color, 1, 2));
        $green = hexdec(mb_substr($point_color, 3, 2));
        $blue = hexdec(mb_substr($point_color, 4, 2));
        $color = imagecolorallocate($image, $red, $green, $blue);

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint
            = mb_substr(
                $spatial,
                11,
                mb_strlen($spatial) - 12
            );
        $points_arr = $this->extractPoints($multipoint, $scale_data);

        foreach ($points_arr as $point) {
            // draw a small circle to mark the point
            if ($point[0] != '' && $point[1] != '') {
                imagearc($image, $point[0], $point[1], 7, 7, 0, 360, $color);
            }
        }
        // print label for each point
        if ((isset($label) && trim($label) != '')
            && ($points_arr[0][0] != '' && $points_arr[0][1] != '')
        ) {
            imagestring(
                $image,
                1,
                $points_arr[0][0],
                $points_arr[0][1],
                trim($label),
                $black
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param string $label       Label for the GIS MULTIPOINT object
     * @param string $point_color Color for the GIS MULTIPOINT object
     * @param array  $scale_data  Array containing data related to scaling
     * @param TCPDF  $pdf         TCPDF instance
     *
     * @return TCPDF the modified TCPDF instance
     * @access public
     */
    public function prepareRowAsPdf(
        $spatial,
        $label,
        $point_color,
        $scale_data,
        $pdf
    ) {
        // allocate colors
        $red = hexdec(mb_substr($point_color, 1, 2));
        $green = hexdec(mb_substr($point_color, 3, 2));
        $blue = hexdec(mb_substr($point_color, 4, 2));
        $line = array('width' => 1.25, 'color' => array($red, $green, $blue));

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint
            = mb_substr(
                $spatial,
                11,
                mb_strlen($spatial) - 12
            );
        $points_arr = $this->extractPoints($multipoint, $scale_data);

        foreach ($points_arr as $point) {
            // draw a small circle to mark the point
            if ($point[0] != '' && $point[1] != '') {
                $pdf->Circle($point[0], $point[1], 2, 0, 360, 'D', $line);
            }
        }
        // print label for each point
        if ((isset($label) && trim($label) != '')
            && ($points_arr[0][0] != '' && $points_arr[0][1] != '')
        ) {
            $pdf->SetXY($points_arr[0][0], $points_arr[0][1]);
            $pdf->SetFontSize(5);
            $pdf->Cell(0, 0, trim($label));
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param string $label       Label for the GIS MULTIPOINT object
     * @param string $point_color Color for the GIS MULTIPOINT object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     * @access public
     */
    public function prepareRowAsSvg($spatial, $label, $point_color, $scale_data)
    {
        $point_options = array(
            'name'         => $label,
            'class'        => 'multipoint vector',
            'fill'         => 'white',
            'stroke'       => $point_color,
            'stroke-width' => 2,
        );

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint
            = mb_substr(
                $spatial,
                11,
                mb_strlen($spatial) - 12
            );
        $points_arr = $this->extractPoints($multipoint, $scale_data);

        $row = '';
        foreach ($points_arr as $point) {
            if ($point[0] != '' && $point[1] != '') {
                $row .= '<circle cx="' . $point[0] . '" cy="'
                    . $point[1] . '" r="3"';
                $point_options['id'] = $label . rand();
                foreach ($point_options as $option => $val) {
                    $row .= ' ' . $option . '="' . trim($val) . '"';
                }
                $row .= '/>';
            }
        }

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial     GIS MULTIPOINT object
     * @param int    $srid        Spatial reference ID
     * @param string $label       Label for the GIS MULTIPOINT object
     * @param string $point_color Color for the GIS MULTIPOINT object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     * @access public
     */
    public function prepareRowAsOl(
        $spatial,
        $srid,
        $label,
        $point_color,
        $scale_data
    ) {
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

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $multipoint
            = mb_substr(
                $spatial,
                11,
                mb_strlen($spatial) - 12
            );
        $points_arr = $this->extractPoints($multipoint, null);

        $result .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector('
            . 'new OpenLayers.Geometry.MultiPoint('
            . $this->getPointsArrayForOpenLayers($points_arr, $srid)
            . '), null, ' . json_encode($style_options) . '));';

        return $result;
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array  $gis_data GIS data
     * @param int    $index    Index into the parameter object
     * @param string $empty    Multipoint does not adhere to this
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     * @access public
     */
    public function generateWkt($gis_data, $index, $empty = '')
    {
        $no_of_points = isset($gis_data[$index]['MULTIPOINT']['no_of_points'])
            ? $gis_data[$index]['MULTIPOINT']['no_of_points'] : 1;
        if ($no_of_points < 1) {
            $no_of_points = 1;
        }
        $wkt = 'MULTIPOINT(';
        for ($i = 0; $i < $no_of_points; $i++) {
            $wkt .= ((isset($gis_data[$index]['MULTIPOINT'][$i]['x'])
                    && trim($gis_data[$index]['MULTIPOINT'][$i]['x']) != '')
                    ? $gis_data[$index]['MULTIPOINT'][$i]['x'] : '')
                . ' ' . ((isset($gis_data[$index]['MULTIPOINT'][$i]['y'])
                    && trim($gis_data[$index]['MULTIPOINT'][$i]['y']) != '')
                    ? $gis_data[$index]['MULTIPOINT'][$i]['y'] : '') . ',';
        }

        $wkt
            = mb_substr(
                $wkt,
                0,
                mb_strlen($wkt) - 1
            );
        $wkt .= ')';

        return $wkt;
    }

    /**
     * Generate the WKT for the data from ESRI shape files.
     *
     * @param array $row_data GIS data
     *
     * @return string the WKT for the data from ESRI shape files
     * @access public
     */
    public function getShape($row_data)
    {
        $wkt = 'MULTIPOINT(';
        for ($i = 0; $i < $row_data['numpoints']; $i++) {
            $wkt .= $row_data['points'][$i]['x'] . ' '
                . $row_data['points'][$i]['y'] . ',';
        }

        $wkt
            = mb_substr(
                $wkt,
                0,
                mb_strlen($wkt) - 1
            );
        $wkt .= ')';

        return $wkt;
    }

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string  $value Value of the GIS column
     * @param integer $index Index of the geometry
     *
     * @return array params for the GIS data editor from the value of the GIS column
     * @access public
     */
    public function generateParams($value, $index = -1)
    {
        $params = array();
        if ($index == -1) {
            $index = 0;
            $data = GISGeometry::generateParams($value);
            $params['srid'] = $data['srid'];
            $wkt = $data['wkt'];
        } else {
            $params[$index]['gis_type'] = 'MULTIPOINT';
            $wkt = $value;
        }

        // Trim to remove leading 'MULTIPOINT(' and trailing ')'
        $points
            = mb_substr(
                $wkt,
                11,
                mb_strlen($wkt) - 12
            );
        $points_arr = $this->extractPoints($points, null);

        $no_of_points = count($points_arr);
        $params[$index]['MULTIPOINT']['no_of_points'] = $no_of_points;
        for ($i = 0; $i < $no_of_points; $i++) {
            $params[$index]['MULTIPOINT'][$i]['x'] = $points_arr[$i][0];
            $params[$index]['MULTIPOINT'][$i]['y'] = $points_arr[$i][1];
        }

        return $params;
    }

    /**
     * Overridden to make sure that only the points having valid values
     * for x and y coordinates are added.
     *
     * @param array  $points_arr x and y coordinates for each point
     * @param string $srid       spatial reference id
     *
     * @return string JavaScript for adding an array of points to OpenLayers
     * @access protected
     */
    protected function getPointsArrayForOpenLayers($points_arr, $srid)
    {
        $ol_array = 'new Array(';
        foreach ($points_arr as $point) {
            if ($point[0] != '' && $point[1] != '') {
                $ol_array .= $this->getPointForOpenLayers($point, $srid) . ', ';
            }
        }

        $olArrayLength = mb_strlen($ol_array);
        if (mb_substr($ol_array, $olArrayLength - 2) == ', ') {
            $ol_array = mb_substr($ol_array, 0, $olArrayLength - 2);
        }
        $ol_array .= ')';

        return $ol_array;
    }
}
