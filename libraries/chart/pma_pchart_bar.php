<?php

require_once 'pma_pchart_chart.php';

class PMA_pChart_bar extends PMA_pChart_Chart
{
    private $labelHeight = 20;

    // as in CSS (top, right, bottom, left)
    private $areaMargins = array(20, 20, 40, 60);

    protected function prepareDataSet()
    {
        $values = array_values($this->data);
        $keys = array_keys($this->data);

        // Dataset definition
        $this->dataSet = new pData;
        $this->dataSet->AddPoint($values[1], "Values");
        $this->dataSet->AddPoint($values[0], "Keys");
        $this->dataSet->AddAllSeries();
        //$DataSet->RemoveSerie("Serie3");
        $this->dataSet->SetAbsciseLabelSerie("Keys");
        $this->dataSet->SetXAxisName($keys[0]);
        $this->dataSet->SetYAxisName($keys[1]);
        //$DataSet->SetYAxisUnit("°C");
        //$DataSet->SetXAxisUnit("h");
    }

    protected function prepareChart()
    {
        // Initialise the graph
        $this->chart = new pChart($this->width, $this->height);
        $this->chart->drawGraphAreaGradient(132,173,131,50,TARGET_BACKGROUND);

        $this->chart->setFontProperties($this->fontPath.'tahoma.ttf', 8);
        $this->chart->setGraphArea($this->areaMargins[3],$this->labelHeight + $this->areaMargins[0],$this->width - $this->areaMargins[1],$this->height - $this->areaMargins[2]);
        $this->chart->drawGraphArea(213,217,221,FALSE);
        $this->chart->drawScale($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),SCALE_ADDALL,213,217,221,TRUE,0,2,TRUE);
        $this->chart->drawGraphAreaGradient(163,203,167,50);
        $this->chart->drawGrid(4,TRUE,230,230,230,20);

        // Draw the bar chart
        $this->chart->drawStackedBarGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),70);

        // Draw the title
        $this->chart->drawTextBox(0,0,$this->width,$this->labelHeight,$titleText,0,255,255,255,ALIGN_CENTER,TRUE,0,0,0,30);

        $this->chart->addBorder(2);
    }
}

?>
