<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles visualization of GIS data
 *
 * @package PhpMyAdmin-GIS
 */

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Core;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;
use TCPDF;

/**
 * Handles visualization of GIS data
 *
 * @package PhpMyAdmin-GIS
 */
class GisVisualization
{
    /**
     * @var array   Raw data for the visualization
     */
    private $_data;
    private $_modified_sql;
    /**
     * @var array   Set of default settings values are here.
     */
    private $_settings = array(
        // Array of colors to be used for GIS visualizations.
        'colors' => array(
            '#B02EE0',
            '#E0642E',
            '#E0D62E',
            '#2E97E0',
            '#BCE02E',
            '#E02E75',
            '#5CE02E',
            '#E0B02E',
            '#0022E0',
            '#726CB1',
            '#481A36',
            '#BAC658',
            '#127224',
            '#825119',
            '#238C74',
            '#4C489B',
            '#87C9BF',
        ),
        // The width of the GIS visualization.
        'width'  => 600,
        // The height of the GIS visualization.
        'height' => 450,
    );
    /**
     * @var array   Options that the user has specified.
     */
    private $_userSpecifiedSettings = null;

    /**
     * Returns the settings array
     *
     * @return array the settings array
     * @access public
     */
    public function getSettings()
    {
        return $this->_settings;
    }

    /**
     * Factory
     *
     * @param string  $sql_query SQL to fetch raw data for visualization
     * @param array   $options   Users specified options
     * @param integer $row       number of rows
     * @param integer $pos       start position
     *
     * @return GisVisualization
     *
     * @access public
     */
    public static function get($sql_query, array $options, $row, $pos)
    {
        return new GisVisualization($sql_query, $options, $row, $pos);
    }

    /**
     * Get visualization
     *
     * @param array $data    Raw data, if set, parameters other than $options will be
     *                       ignored
     * @param array $options Users specified options
     *
     * @return GisVisualization
     */
    public static function getByData(array $data, array $options)
    {
        return new GisVisualization(null, $options, null, null, $data);
    }

    /**
     * Check if data has SRID
     *
     * @return bool
     */
    public function hasSrid()
    {
        foreach ($this->_data as $row) {
            if ($row['srid'] != 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Constructor. Stores user specified options.
     *
     * @param string     $sql_query SQL to fetch raw data for visualization
     * @param array      $options   Users specified options
     * @param integer    $row       number of rows
     * @param integer    $pos       start position
     * @param array|null $data      raw data. If set, parameters other than $options
     *                              will be ignored
     *
     * @access public
     */
    private function __construct($sql_query, array $options, $row, $pos, $data = null)
    {
        $this->_userSpecifiedSettings = $options;
        if (isset($data)) {
            $this->_data = $data;
        } else {
            $this->_modified_sql = $this->_modifySqlQuery($sql_query, $row, $pos);
            $this->_data = $this->_fetchRawData();
        }
    }

    /**
     * All the variable initialization, options handling has to be done here.
     *
     * @return void
     * @access protected
     */
    protected function init()
    {
        $this->_handleOptions();
    }

    /**
     * Returns sql for fetching raw data
     *
     * @param string  $sql_query The SQL to modify.
     * @param integer $rows      Number of rows.
     * @param integer $pos       Start position.
     *
     * @return string the modified sql query.
     */
    private function _modifySqlQuery($sql_query, $rows, $pos)
    {
        $modified_query = 'SELECT ';
        $spatialAsText = 'ASTEXT';
        $spatialSrid = 'SRID';

        if ($this->_userSpecifiedSettings['mysqlVersion'] >= 50600) {
            $spatialAsText = 'ST_ASTEXT';
            $spatialSrid = 'ST_SRID';
        }

        // If label column is chosen add it to the query
        if (!empty($this->_userSpecifiedSettings['labelColumn'])) {
            $modified_query .= Util::backquote(
                $this->_userSpecifiedSettings['labelColumn']
            )
            . ', ';
        }
        // Wrap the spatial column with 'ST_ASTEXT()' function and add it
        $modified_query .= $spatialAsText . '('
            . Util::backquote($this->_userSpecifiedSettings['spatialColumn'])
            . ') AS ' . Util::backquote(
                $this->_userSpecifiedSettings['spatialColumn']
            )
            . ', ';

        // Get the SRID
        $modified_query .= $spatialSrid . '('
            . Util::backquote($this->_userSpecifiedSettings['spatialColumn'])
            . ') AS ' . Util::backquote('srid') . ' ';

        // Append the original query as the inner query
        $modified_query .= 'FROM (' . $sql_query . ') AS '
            . Util::backquote('temp_gis');

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
     * Returns raw data for GIS visualization.
     *
     * @return string the raw data.
     */
    private function _fetchRawData()
    {
        $modified_result = $GLOBALS['dbi']->tryQuery($this->_modified_sql);

        if ($modified_result === false) {
            return array();
        }

        $data = array();
        while ($row = $GLOBALS['dbi']->fetchAssoc($modified_result)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * A function which handles passed parameters. Useful if desired
     * chart needs to be a little bit different from the default one.
     *
     * @return void
     * @access private
     */
    private function _handleOptions()
    {
        if (!is_null($this->_userSpecifiedSettings)) {
            $this->_settings = array_merge(
                $this->_settings,
                $this->_userSpecifiedSettings
            );
        }
    }

    /**
     * Sanitizes the file name.
     *
     * @param string $file_name file name
     * @param string $ext       extension of the file
     *
     * @return string the sanitized file name
     * @access private
     */
    private function _sanitizeName($file_name, $ext)
    {
        $file_name = Sanitize::sanitizeFilename($file_name);

        // Check if the user already added extension;
        // get the substring where the extension would be if it was included
        $extension_start_pos = mb_strlen($file_name) - mb_strlen($ext) - 1;
        $user_extension
            = mb_substr(
                $file_name,
                $extension_start_pos,
                mb_strlen($file_name)
            );
        $required_extension = "." . $ext;
        if (mb_strtolower($user_extension) != $required_extension) {
            $file_name .= $required_extension;
        }

        return $file_name;
    }

    /**
     * Handles common tasks of writing the visualization to file for various formats.
     *
     * @param string $file_name file name
     * @param string $type      mime type
     * @param string $ext       extension of the file
     *
     * @return void
     * @access private
     */
    private function _toFile($file_name, $type, $ext)
    {
        $file_name = $this->_sanitizeName($file_name, $ext);
        Core::downloadHeader($file_name, $type);
    }

    /**
     * Generate the visualization in SVG format.
     *
     * @return string the generated image resource
     * @access private
     */
    private function _svg()
    {
        $this->init();

        $output = '<?xml version="1.0" encoding="UTF-8" standalone="no"?' . ' >'
            . "\n"
            . '<svg version="1.1" xmlns:svg="http://www.w3.org/2000/svg"'
            . ' xmlns="http://www.w3.org/2000/svg"'
            . ' width="' . intval($this->_settings['width']) . '"'
            . ' height="' . intval($this->_settings['height']) . '">'
            . '<g id="groupPanel">';

        $scale_data = $this->_scaleDataSet($this->_data);
        $output .= $this->_prepareDataSet($this->_data, $scale_data, 'svg', '');

        $output .= '</g></svg>';

        return $output;
    }

    /**
     * Get the visualization as a SVG.
     *
     * @return string the visualization as a SVG
     * @access public
     */
    public function asSVG()
    {
        $output = $this->_svg();

        return $output;
    }

    /**
     * Saves as a SVG image to a file.
     *
     * @param string $file_name File name
     *
     * @return void
     * @access public
     */
    public function toFileAsSvg($file_name)
    {
        $img = $this->_svg();
        $this->_toFile($file_name, 'image/svg+xml', 'svg');
        echo($img);
    }

    /**
     * Generate the visualization in PNG format.
     *
     * @return resource the generated image resource
     * @access private
     */
    private function _png()
    {
        $this->init();

        // create image
        $image = imagecreatetruecolor(
            $this->_settings['width'],
            $this->_settings['height']
        );

        // fill the background
        $bg = imagecolorallocate($image, 229, 229, 229);
        imagefilledrectangle(
            $image,
            0,
            0,
            $this->_settings['width'] - 1,
            $this->_settings['height'] - 1,
            $bg
        );

        $scale_data = $this->_scaleDataSet($this->_data);
        $image = $this->_prepareDataSet($this->_data, $scale_data, 'png', $image);

        return $image;
    }

    /**
     * Get the visualization as a PNG.
     *
     * @return string the visualization as a PNG
     * @access public
     */
    public function asPng()
    {
        $img = $this->_png();

        // render and save it to variable
        ob_start();
        imagepng($img, null, 9, PNG_ALL_FILTERS);
        imagedestroy($img);
        $output = ob_get_contents();
        ob_end_clean();

        // base64 encode
        $encoded = base64_encode($output);

        return '<img src="data:image/png;base64,' . $encoded . '" />';
    }

    /**
     * Saves as a PNG image to a file.
     *
     * @param string $file_name File name
     *
     * @return void
     * @access public
     */
    public function toFileAsPng($file_name)
    {
        $img = $this->_png();
        $this->_toFile($file_name, 'image/png', 'png');
        imagepng($img, null, 9, PNG_ALL_FILTERS);
        imagedestroy($img);
    }

    /**
     * Get the code for visualization with OpenLayers.
     *
     * @todo Should return JSON to avoid eval() in gis_data_editor.js
     *
     * @return string the code for visualization with OpenLayers
     * @access public
     */
    public function asOl()
    {
        $this->init();
        $scale_data = $this->_scaleDataSet($this->_data);
        $output
            = 'if (typeof OpenLayers !== "undefined") {'
            . 'var options = {'
            . 'projection: new OpenLayers.Projection("EPSG:900913"),'
            . 'displayProjection: new OpenLayers.Projection("EPSG:4326"),'
            . 'units: "m",'
            . 'numZoomLevels: 18,'
            . 'maxResolution: 156543.0339,'
            . 'maxExtent: new OpenLayers.Bounds('
            . '-20037508, -20037508, 20037508, 20037508),'
            . 'restrictedExtent: new OpenLayers.Bounds('
            . '-20037508, -20037508, 20037508, 20037508)'
            . '};'
            . 'var map = new OpenLayers.Map("openlayersmap", options);'
            . 'var layerNone = new OpenLayers.Layer.Boxes('
            . '"None", {isBaseLayer: true});'
            . 'var layerOSM = new OpenLayers.Layer.OSM("OSM",'
            . '['
            . '"https://a.tile.openstreetmap.org/${z}/${x}/${y}.png",'
            . '"https://b.tile.openstreetmap.org/${z}/${x}/${y}.png",'
            . '"https://c.tile.openstreetmap.org/${z}/${x}/${y}.png"'
            . ']);'
            . 'map.addLayers([layerOSM,layerNone]);'
            . 'var vectorLayer = new OpenLayers.Layer.Vector("Data");'
            . 'var bound;';
        $output .= $this->_prepareDataSet($this->_data, $scale_data, 'ol', '');
        $output .= 'map.addLayer(vectorLayer);'
            . 'map.zoomToExtent(bound);'
            . 'if (map.getZoom() < 2) {'
            . 'map.zoomTo(2);'
            . '}'
            . 'map.addControl(new OpenLayers.Control.LayerSwitcher());'
            . 'map.addControl(new OpenLayers.Control.MousePosition());'
            . '}';

        return $output;
    }

    /**
     * Saves as a PDF to a file.
     *
     * @param string $file_name File name
     *
     * @return void
     * @access public
     */
    public function toFileAsPdf($file_name)
    {
        $this->init();

        // create pdf
        $pdf = new TCPDF(
            '', 'pt', $GLOBALS['cfg']['PDFDefaultPageSize'], true, 'UTF-8', false
        );

        // disable header and footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        //set auto page breaks
        $pdf->SetAutoPageBreak(false);

        // add a page
        $pdf->AddPage();

        $scale_data = $this->_scaleDataSet($this->_data);
        $pdf = $this->_prepareDataSet($this->_data, $scale_data, 'pdf', $pdf);

        // sanitize file name
        $file_name = $this->_sanitizeName($file_name, 'pdf');
        $pdf->Output($file_name, 'D');
    }

    /**
     * Convert file to image
     *
     * @param string $format Output format
     *
     * @return string File
     */
    public function toImage($format)
    {
        if ($format == 'svg') {
            return $this->asSvg();
        } elseif ($format == 'png') {
            return $this->asPng();
        } elseif ($format == 'ol') {
            return $this->asOl();
        }
    }

    /**
     * Convert file to given format
     *
     * @param string $filename Filename
     * @param string $format   Output format
     *
     * @return void
     */
    public function toFile($filename, $format)
    {
        if ($format == 'svg') {
            $this->toFileAsSvg($filename);
        } elseif ($format == 'png') {
            $this->toFileAsPng($filename);
        } elseif ($format == 'pdf') {
            $this->toFileAsPdf($filename);
        }
    }

    /**
     * Calculates the scale, horizontal and vertical offset that should be used.
     *
     * @param array $data Row data
     *
     * @return array an array containing the scale, x and y offsets
     * @access private
     */
    private function _scaleDataSet(array $data)
    {
        $min_max = array(
            'maxX' => 0.0,
            'maxY' => 0.0,
            'minX' => 0.0,
            'minY' => 0.0
        );
        $border = 15;
        // effective width and height of the plot
        $plot_width = $this->_settings['width'] - 2 * $border;
        $plot_height = $this->_settings['height'] - 2 * $border;

        foreach ($data as $row) {

            // Figure out the data type
            $ref_data = $row[$this->_settings['spatialColumn']];
            $type_pos = mb_strpos($ref_data, '(');
            if ($type_pos === false) {
                continue;
            }
            $type = mb_substr($ref_data, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (!$gis_obj) {
                continue;
            }
            $scale_data = $gis_obj->scaleRow(
                $row[$this->_settings['spatialColumn']]
            );

            // Update minimum/maximum values for x and y coordinates.
            $c_maxX = (float)$scale_data['maxX'];
            if (!isset($min_max['maxX']) || $c_maxX > $min_max['maxX']) {
                $min_max['maxX'] = $c_maxX;
            }

            $c_minX = (float)$scale_data['minX'];
            if (!isset($min_max['minX']) || $c_minX < $min_max['minX']) {
                $min_max['minX'] = $c_minX;
            }

            $c_maxY = (float)$scale_data['maxY'];
            if (!isset($min_max['maxY']) || $c_maxY > $min_max['maxY']) {
                $min_max['maxY'] = $c_maxY;
            }

            $c_minY = (float)$scale_data['minY'];
            if (!isset($min_max['minY']) || $c_minY < $min_max['minY']) {
                $min_max['minY'] = $c_minY;
            }
        }

        // scale the visualization
        $x_ratio = ($min_max['maxX'] - $min_max['minX']) / $plot_width;
        $y_ratio = ($min_max['maxY'] - $min_max['minY']) / $plot_height;
        $ratio = ($x_ratio > $y_ratio) ? $x_ratio : $y_ratio;

        $scale = ($ratio != 0) ? (1 / $ratio) : 1;

        if ($x_ratio < $y_ratio) {
            // center horizontally
            $x = ($min_max['maxX'] + $min_max['minX'] - $plot_width / $scale) / 2;
            // fit vertically
            $y = $min_max['minY'] - ($border / $scale);
        } else {
            // fit horizontally
            $x = $min_max['minX'] - ($border / $scale);
            // center vertically
            $y = ($min_max['maxY'] + $min_max['minY'] - $plot_height / $scale) / 2;
        }

        return array(
            'scale'  => $scale,
            'x'      => $x,
            'y'      => $y,
            'minX'   => $min_max['minX'],
            'maxX'   => $min_max['maxX'],
            'minY'   => $min_max['minY'],
            'maxY'   => $min_max['maxY'],
            'height' => $this->_settings['height'],
        );
    }

    /**
     * Prepares and return the dataset as needed by the visualization.
     *
     * @param array  $data       Raw data
     * @param array  $scale_data Data related to scaling
     * @param string $format     Format of the visualization
     * @param object $results    Image object in the case of png
     *                           TCPDF object in the case of pdf
     *
     * @return mixed the formatted array of data
     * @access private
     */
    private function _prepareDataSet(array $data, array $scale_data, $format, $results)
    {
        $color_number = 0;

        // loop through the rows
        foreach ($data as $row) {
            $index = $color_number % sizeof($this->_settings['colors']);

            // Figure out the data type
            $ref_data = $row[$this->_settings['spatialColumn']];
            $type_pos = mb_strpos($ref_data, '(');
            if ($type_pos === false) {
                continue;
            }
            $type = mb_substr($ref_data, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (!$gis_obj) {
                continue;
            }
            $label = '';
            if (isset($this->_settings['labelColumn'])
                && isset($row[$this->_settings['labelColumn']])
            ) {
                $label = $row[$this->_settings['labelColumn']];
            }

            if ($format == 'svg') {
                $results .= $gis_obj->prepareRowAsSvg(
                    $row[$this->_settings['spatialColumn']],
                    $label,
                    $this->_settings['colors'][$index],
                    $scale_data
                );
            } elseif ($format == 'png') {
                $results = $gis_obj->prepareRowAsPng(
                    $row[$this->_settings['spatialColumn']],
                    $label,
                    $this->_settings['colors'][$index],
                    $scale_data,
                    $results
                );
            } elseif ($format == 'pdf') {
                $results = $gis_obj->prepareRowAsPdf(
                    $row[$this->_settings['spatialColumn']],
                    $label,
                    $this->_settings['colors'][$index],
                    $scale_data,
                    $results
                );
            } elseif ($format == 'ol') {
                $results .= $gis_obj->prepareRowAsOl(
                    $row[$this->_settings['spatialColumn']],
                    $row['srid'],
                    $label,
                    $this->_settings['colors'][$index],
                    $scale_data
                );
            }
            $color_number++;
        }

        return $results;
    }

    /**
     * Set user specified settings
     *
     * @param array $userSpecifiedSettings User specified settings
     *
     * @return void
     */
    public function setUserSpecifiedSettings(array $userSpecifiedSettings)
    {
        $this->_userSpecifiedSettings = $userSpecifiedSettings;
    }
}
