<?php
/**
 * Generates the SVG needed for the plot
 *
 * @package PhpMyAdmin
 */

require_once 'pma_svg_data_point.php';

class PMA_Scatter_Plot
{
    /**
     * @var array   Raw data for the plot
     */
    private $_data;

    /**
     * @var array   Data points of the plot
     */
    private $_dataPoints;

    /**
     * @var array   Set of default settigs values are here.
     */
    private $_settings = array(

        // Array of colors to be used for plot.
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
        // Plot background color.
        'bgColor' => '#84AD83',

        // The width of the plot.
        'width' => 520,

         // The height of the plot.
        'height' => 325,

        // Default X Axis label. If empty, label will be taken from the data.
        'xLabel' => '',

        // Default Y Axis label. If empty, label will be taken from the data.
        'yLabel' => '',

        // Data point label. If empty, label will be taken from the data.
        'dataLabel' => '',

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
     * Returns the data array
     *
     * @return the data array.
     */
    public function getData()
    {
        return $this->_data;
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
     */
    protected function init()
    {
        $this->_handleOptions();
    }

    /**
     * A function which handles passed parameters. Useful if desired
     * chart needs to be a little bit different from the default one.
     */
    private function _handleOptions()
    {
        $this->_dataPoints = array();
        if (! is_null($this->_userSpecifiedSettings)) {
            foreach (array_keys($this->_userSpecifiedSettings) as $key) {
                $this->_settings[$key] = $this->_userSpecifiedSettings[$key];
            }
        }
        if ($this->_settings['dataLabel'] == '') {
            $labels = array_keys($this->_data[0]);
            $this->_settings['dataLabel'] = $labels[0];
        }
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
        $output .= '<defs>
            <path id="myTextPath1"
                    d="M10,190 L10,50"/>
                    <path id="myTextPath2"
                    d="M250,10 L370,10"/>
                    </defs>';
        $output .= '<text x="6" y="190"  style="font-family: Arial; font-size  : 54; stroke:none; fill:#000000;" >
                    <textPath xlink:href="#myTextPath1" >';
        $output .= $this->_settings['yLabel'];
        $output .= '</textPath>
                   </text>';

        $output .= '<text x="250" y="10"  style="font-family: Arial; font-size  : 54; stroke:none; fill:#000000;" >
                    <textPath xlink:href="#myTextPath2" >';
        $output .= $this->_settings['xLabel'];
        $output .= '</textPath>
                   </text>';


        $scale_data = $this->_scaleDataSet($this->_data, $this->_settings['xLabel'], $this->_settings['yLabel']);
        $output .= $this->_prepareDataSet($this->_data, 0, $scale_data, $this->_settings['dataLabel']);

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
     * Calculates the scale, horizontal and vertical offset that should be used.
     *
     * @param array $data Row data
     *
     * @return an array containing the scale, x and y offsets
     */
    private function _scaleDataSet($data, $xField, $yField)
    {

        // Currently assuming only numeric fields are selected
        $coordinates = array();
        foreach ($data as $row) {
            $coordinates[0][] = $row[$xField];
            $coordinates[1][] = $row[$yField];
        }
        for ($i = 0 ; $i < 2 ; $i++) {
            $maxC = ($i == 0) ? 500 : 320;

            if ( !is_numeric($coordinates[$i][0])) {
                $uniqueC = array_unique($coordinates[$i]);
                $countC = count(array_unique($coordinates[$i]));
                $map = $tmp = array();
                foreach ($uniqueC as $uc) {
                    $tmp[] = $uc;
                }
                for ($j = 0 ; $j < $countC ; $j++) {
                    $map[$tmp[$j]] = 20 + $j * $maxC / $countC;
                }
                for ($j = 0 ; $j < count($coordinates[$i]) ; $j++) {
                     $coordinates[$i][$j] = $map[$coordinates[$i][$j]];
                }
            } else if (is_numeric($coordinates[$i][0])) {
                $maxC = max($coordinates[$i]);
                for ($j = 0 ; $j < count($coordinates[$i]) ; $j++) {
                    if ($i == 0) {
                         $coordinates[$i][$j] = 20 + 500 * $coordinates[$i][$j] / $maxC;
                    } else {
                         $coordinates[$i][$j] = 20 + 320 * (1 - $coordinates[$i][$j] / $maxC);
                    }
                }
            }
        }
        return $coordinates;
    }

    /**
     * Prepares and return the dataset as needed by the visualization.
     *
     * @param array  $data         Raw data
     * @param int    $color_number Start index to the color array
     * @param array  $scale_data   Data related to scaling
     * @param string $label        Label for the data points
     * @return string the formatted array of data.
     */
    private function _prepareDataSet($data, $color_number, $scale_data, $label)
    {
        $result = '';
        // loop through the rows
        for ($i = 0 ; $i < count($data) ; $i++) {

            $index = $color_number % sizeof($this->_settings['colors']);

            $data_element = new PMA_SVG_Data_Point($scale_data[0][$i], $scale_data[1][$i], $data[$i][$label], $data[$i]);

            $options = array('color' => $this->_settings['colors'][$index], 'id' => $i);
            $this->_dataPoints[] = $data_element;

            $result .= $data_element->prepareRowAsSVG($options);
            $color_number++;
        }

        return $result;
    }
}
?>

