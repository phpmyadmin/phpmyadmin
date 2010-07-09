<?php

require_once 'pma_pchart_multi.php';

class PMA_pChart_stacked_bar extends PMA_pChart_multi
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);
    }

    protected function drawChart()
    {
        parent::drawChart();
        
        // Draw the bar chart
        $this->chart->drawStackedBarGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),70);
    }
}

?>
