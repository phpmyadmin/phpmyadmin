<?php

require_once 'pma_pchart_multi.php';

class PMA_pChart_multi_bar extends PMA_pChart_multi
{
    public function __construct($titleText, $data, $options = null)
    {
        parent::__construct($titleText, $data, $options);

        $this->settings['scale'] = SCALE_NORMAL;
    }

    protected function drawChart()
    {
        parent::drawChart();

        // Draw the bar chart
        $this->chart->drawBarGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),70);
    }
}

?>
