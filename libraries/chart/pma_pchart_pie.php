<?php

require_once 'pma_pchart_chart.php';

class PMA_pChart_Pie extends PMA_pChart_Chart
{
    private $border1Width = 7;
    private $border2Width = 5;

    protected function prepareDataSet()
    {
        // Dataset definition 
        $this->dataSet = new pData;
        $this->dataSet->AddPoint(array_values($this->data),"Values");
        $this->dataSet->AddPoint(array_keys($this->data),"Keys");
        $this->dataSet->AddAllSeries();
        $this->dataSet->SetAbsciseLabelSerie("Keys");
    }

    protected function prepareChart()
    {
        // Initialise the graph
        $this->chart = new pChart($this->width, $this->height);
        foreach ($this->colors as $key => $color) {
            $this->chart->setColorPalette(
                    $key,
                    hexdec(substr($color, 1, 2)),
                    hexdec(substr($color, 3, 2)),
                    hexdec(substr($color, 5, 2))
            );
        }
        $this->chart->setFontProperties($this->fontPath.'tahoma.ttf', 8);
        $this->chart->drawFilledRoundedRectangle(
                $this->border1Width,
                $this->border1Width,
                $this->width - $this->border1Width,
                $this->height - $this->border1Width,
                5,
                $this->getBgColorComp(0),
                $this->getBgColorComp(1),
                $this->getBgColorComp(2)
                );
        $this->chart->drawRoundedRectangle(
                $this->border1Width,
                $this->border1Width,
                $this->width - $this->border1Width,
                $this->height - $this->border1Width,
                5,0,0,0);

        // Draw the pie chart
        $this->chart->AntialiasQuality = 0;
        $this->chart->setShadowProperties(2,2,200,200,200);
        //$Test->drawFlatPieGraphWithShadow($DataSet->GetData(),$DataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,8);
        //$Test->drawBasicPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,255,255,218,2);
        $this->chart->drawPieGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,FALSE,60,30,10,1);
        $this->chart->clearShadow();

        $this->chart->drawTitle(20,20,$this->titleText,0,0,0);
        $this->chart->drawPieLegend(350,15,$this->dataSet->GetData(),$this->dataSet->GetDataDescription(),250,250,250);
    }
}

?>
