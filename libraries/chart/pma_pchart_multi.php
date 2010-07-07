<?php

require_once 'pma_pchart_chart.php';

/*
 * Base class for every chart that uses multiple series.
 * All of these charts will require legend box.
 */
abstract class PMA_pChart_multi extends PMA_pChart_chart
{
    public function __construct($titleText, $data, $options = null)
    {
        parent::__construct($titleText, $data, $options);

        // as in CSS (top, right, bottom, left)
        $this->settings['legendMargins'] = array(20, 10, 0, 0);
    }

    protected function prepareDataSet()
    {
        $values = array_values($this->data);
        $keys = array_keys($this->data);

        // Dataset definition
        $this->dataSet->AddPoint($values[0], "Keys");

        $i = 0;
        foreach ($values[1] as $seriesName => $seriesData) {
            $this->dataSet->AddPoint($seriesData, "Values".$i);
            $this->dataSet->SetSerieName($seriesName, "Values".$i);
            $i++;
        }
        $this->dataSet->AddAllSeries();

        $this->dataSet->RemoveSerie("Keys");
        $this->dataSet->SetAbsciseLabelSerie("Keys");

        $this->dataSet->SetXAxisName($keys[0]);
        $this->dataSet->SetYAxisName($keys[1]);
    }

    protected function setGraphAreaDimensions()
    {
        $this->chart->setGraphArea(
                $this->getAreaMargin(LEFT),
                $this->getLabelHeight() + $this->getAreaMargin(TOP),
                $this->getWidth() - $this->getAreaMargin(RIGHT) - $this->getLegendBoxWidth() - $this->getLegendMargin(LEFT) - $this->getLegendMargin(RIGHT),
                $this->getHeight() - $this->getAreaMargin(BOTTOM)
        );
    }

    protected function drawChart()
    {
        $this->drawLegend();
    }

    protected function drawLegend()
    {
        // Draw the legend
        $this->chart->drawLegend(
                $this->getWidth() - $this->getLegendMargin(RIGHT) - $this->getLegendBoxWidth(),
                $this->getLabelHeight() + $this->getLegendMargin(TOP),
                $this->dataSet->GetDataDescription(),
                250,250,250,50,50,50
        );
    }

    protected function getLegendMargin($side)
    {
        return $this->settings['legendMargins'][$side];
    }

    protected function getLegendBoxWidth()
    {
        $legendSize = $this->chart->getLegendBoxSize($this->dataSet->GetDataDescription());
        return $legendSize[0];
    }
}

?>
