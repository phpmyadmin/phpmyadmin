<?php

class PMA_Chart
{
    /*
     * The style of the chart title.
     */
    protected $titleStyle = 'font-size: 12px; font-weight: bold;';

    /*
     * Colors for the different slices in the pie chart.
     */
    protected $colors = array(
        '#70484A',
        '#705948',
        '#6D4870',
        '#70485E',
        '#485E70',
        '#484A70',
        '#487059',
        '#48706D',
        '#594870',
        '#5E7048',
        '#839CAF',
        '#95775F',
        '#5F7E95',
        '#706D48',
        '#4A7048',
        '#AF9683',
    );

    /*
     * Chart background color.
     */
    protected $bgColor = '#f5f5f5';

    /*
     * The width of the chart.
     */
    protected $width = 400;

    /*
     * The height of the chart.
     */
    protected $height = 250;

    function __construct()
    {

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
        if (is_null($options))
            return;

        if (isset($options['bgColor']))
            $this->bgColor = $options['bgColor'];
        if (isset($options['width']))
            $this->width = $options['width'];
        if (isset($options['height']))
            $this->height = $options['height'];
    }
}

?>
