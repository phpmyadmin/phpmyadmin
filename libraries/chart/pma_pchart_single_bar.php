<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */

/**
 * 
 */
require_once 'pma_pchart_single.php';

/**
 * implements single bar chart
 * @package phpMyAdmin
 */
class PMA_pChart_single_bar extends PMA_pChart_single
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);
    }

    /**
     * draws single bar chart
     */
    protected function drawChart()
    {
        // Draw the bar chart
        // use stacked bar graph function, because it gives bars with alpha
        $this->chart->drawStackedBarGraph($this->dataSet->GetData(), $this->dataSet->GetDataDescription(), 70);
    }
}

?>
