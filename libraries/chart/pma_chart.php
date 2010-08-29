<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the base class that all charts inherit from and some widely used
 * constants.
 * @package phpMyAdmin
 */

/**
 *
 */
define('RED', 0);
define('GREEN', 1);
define('BLUE', 2);

/**
 * The base class that all charts inherit from.
 * @abstract
 * @package phpMyAdmin
 */
abstract class PMA_chart
{
    /**
     * @var array   All the default settigs values are here.
     */
    protected $settings = array(

        // Default title for every chart.
        'titleText' => 'Chart',

        // The style of the chart title.
        'titleColor' => '#FAFAFA',

        // Colors for the different slices in the pie chart.
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

        // Chart background color.
        'bgColor' => '#84AD83',

        // The width of the chart.
        'width' => 520,

         // The height of the chart.
        'height' => 325,

        // Default X Axis label. If empty, label will be taken from the data.
        'xLabel' => '',

        // Default Y Axis label. If empty, label will be taken from the data.
        'yLabel' => '',
    );

    /**
     * @var array   Options that the user has specified
     */
    private $userSpecifiedSettings = null;

    /**
     * @var array   Error codes will be stored here
     */
    protected $errors = array();

    /**
     * Store user specified options
     * @param array $options users specified options
     */
    function __construct($options = null)
    {
        $this->userSpecifiedSettings = $options;
    }

    /**
     * All the variable initialization has to be done here.
     */
    protected function init()
    {
        $this->handleOptions();
    }

    /**
     * A function which handles passed parameters. Useful if desired
     * chart needs to be a little bit different from the default one.
     */
    private function handleOptions()
    {
        if (is_null($this->userSpecifiedSettings)) {
            return;
        }

        $this->settings = array_merge($this->settings, $this->userSpecifiedSettings);
    }

    protected function getTitleText()
    {
        return $this->settings['titleText'];
    }

    protected function getTitleColor($component)
    {
        return $this->hexStrToDecComp($this->settings['titleColor'], $component);
    }

    protected function getColors()
    {
        return $this->settings['colors'];
    }

    protected function getWidth()
    {
        return $this->settings['width'];
    }

    protected function getHeight()
    {
        return $this->settings['height'];
    }

    protected function getBgColor($component)
    {
        return $this->hexStrToDecComp($this->settings['bgColor'], $component);
    }

    protected function setXLabel($label)
    {
        $this->settings['xLabel'] = $label;
    }

    protected function getXLabel()
    {
        return $this->settings['xLabel'];
    }

    protected function setYLabel($label)
    {
        $this->settings['yLabel'] = $label;
    }

    protected function getYLabel()
    {
        return $this->settings['yLabel'];
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get one the dec color component from the hex color string
     * @param string $colorString   color string, i.e. #5F22A99
     * @param int    $component     color component to get, i.e. 0 gets red.
     */
    protected function hexStrToDecComp($colorString, $component)
    {
        return hexdec(substr($colorString, ($component * 2) + 1, 2));
    }
}

?>
