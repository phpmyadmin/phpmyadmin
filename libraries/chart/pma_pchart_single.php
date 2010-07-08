<?php

require_once 'pma_pchart_chart.php';

/*
 * Base class for every chart that uses only one series.
 */
abstract class PMA_pChart_single extends PMA_pChart_chart
{
    public function __construct($titleText, $data, $options = null)
    {
        parent::__construct($titleText, $data, $options);
    }

    protected function prepareDataSet()
    {
        $values = array_values($this->data);
        $keys = array_keys($this->data);

        // Dataset definition
        $this->dataSet->AddPoint($values[1], "Values");
        $this->dataSet->AddPoint($values[0], "Keys");
        
        //$this->dataSet->AddAllSeries();
        $this->dataSet->AddSerie("Values");

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
    }
}

?>
