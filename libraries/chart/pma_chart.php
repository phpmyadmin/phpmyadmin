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
        '#485E70',
        '#484A70',
        '#594870',
        '#6D4870',
        '#70485E',
        '#70484A',
        '#705948',
        '#706D48',
        '#5E7048',
        '#4A7048',
        '#487059',
        '#48706D',
        '#5F7E95',
        '#839CAF',
        '#95775F',
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

    /*
     * Colors in the colors array have been written down in an gradient
     * order. Without shuffling pie chart has an angular gradient.
     * Colors could also be shuffles in the array initializer.
     */
    function __construct()
    {
        shuffle(&$this->colors);
    }
}

?>