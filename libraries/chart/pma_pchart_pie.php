<?php

require_once 'pma_pchart_multi.php';

class PMA_pChart_Pie extends PMA_pChart_multi
{
    public function __construct($titleText, $data, $options = null)
    {
        parent::__construct($titleText, $data, $options);

        $this->settings['areaMargins'] = array(20, 20, 20, 10);
    }

    protected function prepareDataSet()
    {
        // Dataset definition 
        $this->dataSet->AddPoint(array_values($this->data),"Values");
        $this->dataSet->AddPoint(array_keys($this->data),"Keys");
        $this->dataSet->AddAllSeries();
        $this->dataSet->SetAbsciseLabelSerie("Keys");
    }

    protected function drawGraphArea()
    {
        $this->chart->drawGraphArea(213,217,221,FALSE);
        $this->chart->drawGraphAreaGradient(163,203,167,50);
    }

    protected function drawChart()
    {
        parent::drawChart();

        $this->chart->drawPieGraph(
                $this->dataSet->GetData(),
                $this->dataSet->GetDataDescription(),
                180,160,120,PIE_PERCENTAGE,FALSE,60,30,10,1);
    }

    protected function drawLegend()
    {
        $this->chart->drawPieLegend(
                $this->getWidth() - $this->getLegendMargin(RIGHT) - $this->getLegendBoxWidth(),
                $this->getLabelHeight() + $this->getLegendMargin(TOP),
                $this->dataSet->GetData(),
                $this->dataSet->GetDataDescription(),
                250,250,250);
    }

    protected function getLegendBoxWidth()
    {
        $legendSize = $this->chart->getPieLegendBoxSize($this->dataSet->GetData());
        return $legendSize[0];
    }
}

?>
