<?php

require_once 'pma_pchart_multi.php';

class PMA_pChart_Pie extends PMA_pChart_multi
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);

        $this->setAreaMargins(array(20, 10, 20, 20));
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

         // draw pie chart in the middle of graph area
        $middleX = ($this->chart->GArea_X1 + $this->chart->GArea_X2) / 2;
        $middleY = ($this->chart->GArea_Y1 + $this->chart->GArea_Y2) / 2;

        $this->chart->drawPieGraph(
                $this->dataSet->GetData(),
                $this->dataSet->GetDataDescription(),
                $middleX,
                // pie graph is skewed. Upper part is shorter than the
                // lower part. This is why we set an offset to the
                // Y middle coordiantes.
                $middleY - 15,
                120,PIE_PERCENTAGE,FALSE,60,30,10,1);
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
