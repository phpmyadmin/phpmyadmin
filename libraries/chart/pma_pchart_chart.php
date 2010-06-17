<?php

require_once 'pma_chart.php';

/*
 * Base class for every chart implemented using pChart.
 */
class PMA_pChart_Chart extends PMA_Chart
{
    protected $imageEncoded;

    protected $fontPath = './libraries/chart/pChart/fonts/';

    function __construct($options = null)
    {
        parent::__construct($options);
    }    

    function toString()
    {
        return '<img id="pChartPicture1" src="data:image/png;base64,'.$this->imageEncoded.'" />';
    }
}

?>
