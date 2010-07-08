<?php

define('RED', 0);
define('GREEN', 1);
define('BLUE', 2);

class PMA_Chart
{
    /*
     * The settings array. All the default values are here.
     */
    protected $settings = array(
        /*
        * The style of the chart title.
        */
        'titleStyle' => 'font-size: 12px; font-weight: bold;',

        /*
         * Colors for the different slices in the pie chart.
         */
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
            '#1D674A',
            '#87C9BF',
        ),

        /*
         * Chart background color.
         */
        'bgColor' => '#84AD83',

        /*
         * The width of the chart.
         */
        'width' => 520,

         /*
         * The height of the chart.
         */
        'height' => 325,

        /*
         * Default X Axis label. If empty, label will be taken from the data.
         */
        'xLabel' => '',

        /*
         * Default Y Axis label. If empty, label will be taken from the data.
         */
        'yLabel' => '',
    );

    /*
     * Options that the user has specified
     */
    private $userSpecifiedSettings = null;

    function __construct($options = null)
    {
        $this->userSpecifiedSettings = $options;
    }

    protected function init()
    {
        $this->handleOptions();
    }

    /*
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

    protected function getTitleStyle()
    {
        return $this->settings['titleStyle'];
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
        return hexdec(substr($this->settings['bgColor'], ($component * 2) + 1, 2));
    }

    protected function getXLabel()
    {
        return $this->settings['xLabel'];
    }

    protected function getYLabel()
    {
        return $this->settings['yLabel'];
    }

    public function getSettings()
    {
        return $this->settings;
    }
}

?>
