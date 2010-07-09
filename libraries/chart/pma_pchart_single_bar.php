<?php

require_once 'pma_pchart_single.php';

class PMA_pChart_single_bar extends PMA_pChart_single
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);
    }

    protected function drawChart()
    {
        // Draw the bar chart
        $this->chart->drawStackedBarGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),70);
    }
}

?>
