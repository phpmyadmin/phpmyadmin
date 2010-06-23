<?php

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
        'bgColor' => '#E5E5E5',

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

    function __construct($options = null)
    {
        $this->handleOptions($options);
    }

    /*
     * A function which handles passed parameters. Useful if desired
     * chart needs to be a little bit different from the default one.
     *
     * Option handling could be made more efficient if options would be
     * stored in an array.
     */
    function handleOptions($options)
    {
        if (is_null($options)) {
            return;
        }

        $this->settings = array_merge($this->settings, $options);
    }

    function getTitleStyle()
    {
        return $this->settings['titleStyle'];
    }

    function getColors()
    {
        return $this->settings['colors'];
    }

    function getWidth()
    {
        return $this->settings['width'];
    }

    function getHeight()
    {
        return $this->settings['height'];
    }

    function getBgColorComp($component)
    {
        return hexdec(substr($this->settings['bgColor'], ($component * 2) + 1, 2));
    }

    function getXLabel()
    {
        return $this->settings['xLabel'];
    }

    function getYLabel()
    {
        return $this->settings['yLabel'];
    }

    function getSettings()
    {
        return $this->settings;
    }
}

?>
