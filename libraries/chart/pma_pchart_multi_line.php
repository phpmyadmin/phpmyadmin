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
 * implements multi line chart
 * @package phpMyAdmin
 */
class PMA_pChart_multi_line extends PMA_pChart_multi
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);

        $this->settings['scale'] = SCALE_NORMAL;
    }

    /**
     * draws multi line chart
     */
    protected function drawChart()
    {
        parent::drawChart();

        // Draw the bar chart
        $this->chart->drawLineGraph($this->dataSet->GetData(), $this->dataSet->GetDataDescription());
        $this->chart->drawPlotGraph($this->dataSet->GetData(), $this->dataSet->GetDataDescription(), 3, 1, -1, -1, -1, TRUE);
    }
}

?>
