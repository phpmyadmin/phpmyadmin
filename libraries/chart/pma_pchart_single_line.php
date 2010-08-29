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
 * implements single line chart
 * @package phpMyAdmin
 */
class PMA_pChart_single_line extends PMA_pChart_single
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);
    }

    /**
     * draws single line chart
     */
    protected function drawChart()
    {
        // Draw the line chart
        $this->chart->drawLineGraph($this->dataSet->GetData(), $this->dataSet->GetDataDescription());
        $this->chart->drawPlotGraph($this->dataSet->GetData(), $this->dataSet->GetDataDescription(), 3, 1, -1, -1, -1, TRUE);
    }
}

?>
