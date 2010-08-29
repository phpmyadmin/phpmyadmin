<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */

/**
 *
 */
require_once 'pma_pchart_multi.php';

/**
 * implements stacked bar chart
 * @package phpMyAdmin
 */
class PMA_pChart_stacked_bar extends PMA_pChart_multi
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);
    }

    /**
     * draws stacked bar chart
     */
    protected function drawChart()
    {
        parent::drawChart();
        
        // Draw the bar chart
        $this->chart->drawStackedBarGraph($this->dataSet->GetData(), $this->dataSet->GetDataDescription(), 70);
    }
}

?>
