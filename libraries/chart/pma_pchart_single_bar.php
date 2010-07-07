<?php

require_once 'pma_pchart_single.php';

class PMA_pChart_single_bar extends PMA_pChart_single
{
    public function __construct($titleText, $data, $options = null)
    {
        parent::__construct($titleText, $data, $options);
    }

    protected function drawChart()
    {
        // Draw the bar chart
        $this->chart->drawStackedBarGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),70);
        //$this->chart->drawLineGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription());
        //$this->chart->drawPlotGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),4,2,-1,-1,-1,TRUE);
    }
}

?>
