<?php

require_once 'pma_pchart_chart.php';

define('TOP', 0);
define('RIGHT', 1);
define('BOTTOM', 2);
define('LEFT', 3);

class PMA_pChart_bar extends PMA_pChart_Chart
{
    public function __construct($titleText, $data, $options = null)
    {
        parent::__construct($titleText, $data, $options);

        $this->settings['labelHeight'] = 20;

        // as in CSS (top, right, bottom, left)
        $this->settings['areaMargins'] = array(20, 20, 40, 60);
    }

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

        $xLabel = $this->getXLabel();
        if (empty($xLabel)) {
            $xLabel = $keys[0];
        }
        $this->dataSet->SetXAxisName($xLabel);

        $yLabel = $this->getYLabel();
        if (empty($yLabel)) {
            $yLabel = $keys[1];
        }
        $this->dataSet->SetYAxisName($yLabel);
        //$DataSet->SetYAxisUnit("°C");
        //$DataSet->SetXAxisUnit("h");
    }

    protected function prepareChart()
    {
        // Initialise the graph
        $this->chart = new pChart($this->getWidth(), $this->getHeight());
        $this->chart->drawGraphAreaGradient(132,173,131,50,TARGET_BACKGROUND);

        $this->chart->setFontProperties($this->getFontPath().'tahoma.ttf', 8);
        $this->chart->setGraphArea(
                $this->getAreaMargin(LEFT),
                $this->getLabelHeight() + $this->getAreaMargin(TOP),
                $this->getWidth() - $this->getAreaMargin(RIGHT),
                $this->getHeight() - $this->getAreaMargin(BOTTOM)
        );
        $this->chart->drawGraphArea(213,217,221,FALSE);
        $this->chart->drawScale($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),SCALE_ADDALL,213,217,221,TRUE,0,2,TRUE);
        $this->chart->drawGraphAreaGradient(163,203,167,50);
        $this->chart->drawGrid(4,TRUE,230,230,230,20);

        // Draw the bar chart
        $this->chart->drawStackedBarGraph($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),70);

        // Draw the title
        $this->chart->drawTextBox(0,0,$this->getWidth(),$this->getLabelHeight(),$this->titleText,0,255,255,255,ALIGN_CENTER,TRUE,0,0,0,30);

        $this->chart->addBorder(2);
    }

    protected function getLabelHeight()
    {
        return $this->settings['labelHeight'];
    }

    protected function getAreaMargin($side)
    {
        return $this->settings['areaMargins'][$side];
    }
}

?>
