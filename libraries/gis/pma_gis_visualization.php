<?php
/**
 * Generates the JavaScripts needed to visualize GIS data.
 *
 * @package PhpMyAdmin-GIS
 */
class PMA_GIS_Visualization
{
    /**
     * @var array   Raw data for the visualization
     */
    private $_data;

    /**
     * @var array   Set of default settigs values are here.
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
        'width' => 600,

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
     * @return the settings array.
     */
    public function getSettings()
    {
        return $this->_settings;
    }

    /**
     * Constructor. Stores user specified options.
     *
     * @param array $data    Data for the visualization
     * @param array $options Users specified options
     */
    public function __construct($data, $options)
    {
        $this->_userSpecifiedSettings = $options;
        $this->_data = $data;
    }

    /**
     * All the variable initialization, options handling has to be done here.
     *
     * @return nothing
     */
    protected function init()
    {
        $this->_handleOptions();
    }

    /**
     * A function which handles passed parameters. Useful if desired
     * chart needs to be a little bit different from the default one.
     *
     * @return nothing
     */
    private function _handleOptions()
    {
        if (! is_null($this->_userSpecifiedSettings)) {
            $this->_settings = array_merge($this->_settings, $this->_userSpecifiedSettings);
        }
    }

    /**
     * Sanitizes the file name.
     *
     * @param string $file_name file name
     * @param string $ext       extension of the file
     *
     * @return the sanitized file name
     */
    private function _sanitizeName($file_name, $ext)
    {
        $file_name = PMA_sanitize_filename($file_name);

        // Check if the user already added extension;
        // get the substring where the extension would be if it was included
        $extension_start_pos = strlen($file_name) - strlen($ext) - 1;
        $user_extension = substr($file_name, $extension_start_pos, strlen($file_name));
        $required_extension = "." . $ext;
        if (strtolower($user_extension) != $required_extension) {
            $file_name  .= $required_extension;
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
     * @return nothing
     */
    private function _toFile($file_name, $type, $ext)
    {
        $file_name = $this->_sanitizeName($file_name, $ext);

        ob_clean();

        PMA_download_header($file_name, $type);
    }

    /**
     * Generate the visualization in SVG format.
     *
     * @return the generated image resource
     */
    private function _svg()
    {
        $this->init();

        $output   = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n";
        $output  .= '<svg version="1.1" xmlns:svg="http://www.w3.org/2000/svg"'
            . ' xmlns="http://www.w3.org/2000/svg" width="' . $this->_settings['width'] . '"'
            . ' height="' . $this->_settings['height'] . '">';
        $output .= '<g id="groupPanel">';

        $scale_data = $this->_scaleDataSet($this->_data);
        $output .= $this->_prepareDataSet($this->_data, $scale_data, 'svg', '');

        $output .= '</g>';
        $output .= '</svg>';

        return $output;
    }

    /**
     * Get the visualization as a SVG.
     *
     * @return the visualization as a SVG
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
     * @return nothing
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
     * @return the generated image resource
     */
    private function _png()
    {
        $this->init();

        // create image
        $image = imagecreatetruecolor($this->_settings['width'], $this->_settings['height']);

        // fill the background
        $bg = imagecolorallocate($image, 229, 229, 229);
        imagefilledrectangle(
            $image, 0, 0, $this->_settings['width'] - 1,
            $this->_settings['height'] - 1, $bg
        );

        $scale_data = $this->_scaleDataSet($this->_data);
        $image = $this->_prepareDataSet($this->_data, $scale_data, 'png', $image);

        return $image;
    }

    /**
     * Get the visualization as a PNG.
     *
     * @return the visualization as a PNG
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
        return '<img src="data:image/png;base64,'. $encoded .'" />';
    }

    /**
     * Saves as a PNG image to a file.
     *
     * @param string $file_name File name
     *
     * @return nothing
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
     * @return the code for visualization with OpenLayers
     */
    public function asOl()
    {
        $this->init();
        $scale_data = $this->_scaleDataSet($this->_data);
        $output
            = 'var options = {'
                . 'projection: new OpenLayers.Projection("EPSG:900913"),'
                . 'displayProjection: new OpenLayers.Projection("EPSG:4326"),'
                . 'units: "m",'
                . 'numZoomLevels: 18,'
                . 'maxResolution: 156543.0339,'
                . 'maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508),'
                . 'restrictedExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508)'
            . '};'
            . 'var map = new OpenLayers.Map("openlayersmap", options);'
            . 'var layerNone = new OpenLayers.Layer.Boxes("None", {isBaseLayer: true});'
            . 'var layerMapnik = new OpenLayers.Layer.OSM.Mapnik("Mapnik");'
            . 'var layerOsmarender = new OpenLayers.Layer.OSM.Osmarender("Osmarender");'
            . 'var layerCycleMap = new OpenLayers.Layer.OSM.CycleMap("CycleMap");'
            . 'map.addLayers([layerMapnik, layerOsmarender, layerCycleMap, layerNone]);'
            . 'var vectorLayer = new OpenLayers.Layer.Vector("Data");'
            . 'var bound;';
        $output .= $this->_prepareDataSet($this->_data, $scale_data, 'ol', '');
        $output .=
              'map.addLayer(vectorLayer);'
            . 'map.zoomToExtent(bound);'
            . 'if (map.getZoom() < 2) {'
                . 'map.zoomTo(2);'
            . '}'
            . 'map.addControl(new OpenLayers.Control.LayerSwitcher());'
            . 'map.addControl(new OpenLayers.Control.MousePosition());';
        return $output;
    }

    /**
     * Saves as a PDF to a file.
     *
     * @param string $file_name File name
     *
     * @return nothing
     */
    public function toFileAsPdf($file_name)
    {
        $this->init();

        include_once './libraries/tcpdf/tcpdf.php';

        // create pdf
        $pdf = new TCPDF('', 'pt', $GLOBALS['cfg']['PDFDefaultPageSize'], true, 'UTF-8', false);

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

        ob_clean();
        $pdf->Output($file_name, 'D');
    }

    /**
     * Calculates the scale, horizontal and vertical offset that should be used.
     *
     * @param array $data Row data
     *
     * @return an array containing the scale, x and y offsets
     */
    private function _scaleDataSet($data)
    {
        $min_max = array();
        $border = 15;
        // effective width and height of the plot
        $plot_width = $this->_settings['width'] - 2 * $border;
        $plot_height = $this->_settings['height'] - 2 * $border;

        foreach ($data as $row) {

            // Figure out the data type
            $ref_data = $row[$this->_settings['spatialColumn']];
            $type_pos = stripos($ref_data, '(');
            $type = substr($ref_data, 0, $type_pos);

            $gis_obj = PMA_GIS_Factory::factory($type);
            if (! $gis_obj) {
                continue;
            }
            $scale_data = $gis_obj->scaleRow($row[$this->_settings['spatialColumn']]);

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
            $y =($min_max['maxY'] + $min_max['minY'] - $plot_height / $scale) / 2;
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
     * @param string $format     Format of the visulaization
     * @param image  $results    Image object in the case of png
     *
     * @return the formatted array of data.
     */
    private function _prepareDataSet($data, $scale_data, $format, $results)
    {
        $color_number = 0;

        // loop through the rows
        foreach ($data as $row) {
            $index = $color_number % sizeof($this->_settings['colors']);

            // Figure out the data type
            $ref_data = $row[$this->_settings['spatialColumn']];
            $type_pos = stripos($ref_data, '(');
            $type = substr($ref_data, 0, $type_pos);

            $gis_obj = PMA_GIS_Factory::factory($type);
            if (! $gis_obj) {
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
                    $row[$this->_settings['spatialColumn']], $label,
                    $this->_settings['colors'][$index], $scale_data
                );
            } elseif ($format == 'png') {
                $results = $gis_obj->prepareRowAsPng(
                    $row[$this->_settings['spatialColumn']], $label,
                    $this->_settings['colors'][$index], $scale_data, $results
                );
            } elseif ($format == 'pdf') {
                $results = $gis_obj->prepareRowAsPdf(
                    $row[$this->_settings['spatialColumn']], $label,
                    $this->_settings['colors'][$index], $scale_data, $results
                );
            } elseif ($format == 'ol') {
                $results .= $gis_obj->prepareRowAsOl(
                    $row[$this->_settings['spatialColumn']], $row['srid'],
                    $label, $this->_settings['colors'][$index], $scale_data
                );
            }
            $color_number++;
        }
        return $results;
    }
}
?>
