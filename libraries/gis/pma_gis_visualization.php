<?php
/**
 * Generates the JavaScripts needed to visualize GIS data. *
 * @package phpMyAdmin
 */
class PMA_GIS_visualization
{
    /**
     * @var array   Raw data for the visualization
     */
    private $data;

    /**
     * @var array   Set of default settigs values are here.
     */
    private $settings = array(

        // Array of colors to be used for GIS visualizations.
        'colors' => array(
            '#BCE02E',
            '#E0642E',
            '#E0D62E',
            '#2E97E0',
            '#B02EE0',
            '#E02E75',
            '#5CE02E',
            '#E0B02E',
            '#000000',
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
        'width' => 750,

         // The height of the GIS visualization.
        'height' => 450,
    );

    /**
     * @var array   Options that the user has specified.
     */
    private $userSpecifiedSettings = null;

    /**
     * Returns the settings array
     * @return the settings array.
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * Constructor. Stores user specified options.
     * @param array $options users specified options
     */
    public function __construct($data, $options) {
        $this->userSpecifiedSettings = $options;
        $this->data = $data;

        $series = array('shadowSize' => 0);
        $grid = array('hoverable' => true, 'backgroundColor' => '#e5e5e5');
        $legend = array('show' => false);
        $zoom = array('interactive' => true);
        $pan = array('interactive' => true);

        $this->settings['flotOptions'] = array('series' => $series, 'zoom' => $zoom,
            'pan' => $pan, 'grid' => $grid, 'legend' => $legend);
    }

    /**
     * All the variable initialization, options handling has to be done here.
     */
    protected function init() {
        $this->handleOptions();
    }

    /**
     * A function which handles passed parameters. Useful if desired
     * chart needs to be a little bit different from the default one.
     */
    private function handleOptions() {
        if (is_null($this->userSpecifiedSettings)) {
            return;
        }

        $this->settings = array_merge($this->settings, $this->userSpecifiedSettings);
    }

    /**
     * Get the JS code for the configured vaisualization
     * @return string   JS code for the vaisualization
     */
    public function toString() {
        $this->init();

        $output = '$("#placeholder").attr("style", "float:right;';
        $output .= 'width:' . $this->settings['width'] . 'px;';
        $output .= 'height:' . $this->settings['height'] . 'px;"); ';

        $data_arr = $this->prepareDataSet($this->data, 0);
        $output .= 'var data = ' . json_encode($data_arr) . '; ';

        $output .= 'var options = ' . json_encode($this->settings['flotOptions']). '; ';

        return $output;
    }


    private function prepareDataSet($data, $color_number) {

        $results_arr = array();

        // loop through the rows
        foreach ($data as $row) {

            $index = $color_number % sizeof($this->settings['colors']);

            // Figure out the data type
            $ref_data = $row[$this->settings['spatialColumn']];
            $type_pos = stripos($ref_data, '(');
            $type = substr($ref_data, 0, $type_pos);

            switch($type) {
                case 'MULTIPOLYGON' :
                    $gis_obj = PMA_GIS_multipolygon::singleton();
                    break;
                case 'POLYGON' :
                    $gis_obj = PMA_GIS_polygon::singleton();
                    break;
                case 'MULTIPOINT' :
                    $gis_obj = PMA_GIS_multipoint::singleton();
                    break;
                case 'POINT' :
                    $gis_obj = PMA_GIS_point::singleton();
                    break;
                case 'MULTILINESTRING' :
                    $gis_obj = PMA_GIS_multilinestring::singleton();
                    break;
                case 'LINESTRING' :
                    $gis_obj = PMA_GIS_linestring::singleton();
                    break;
                case 'GEOMETRYCOLLECTION' :
                    $gis_obj = PMA_GIS_geometrycollection::singleton();
                    break;
                default :
                    die(__('Unknown GIS data type'));
            }

            $label = '';
            if (isset($this->settings['labelColumn']) && isset($row[$this->settings['labelColumn']])) {
                $label = $row[$this->settings['labelColumn']];
            }
            $temp_results = $gis_obj->prepareRow($row[$this->settings['spatialColumn']],
                $label, $this->settings['colors'][$index]);

            if (isset($temp_results[0]) && is_array($temp_results[0])) {
                $results_arr = array_merge($results_arr, $temp_results);
            } else {
                $results_arr[] = $temp_results;
            }

            $color_number++;
        }
        return $results_arr;
    }
}
?>
