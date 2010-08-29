<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */

/**
 *
 */
require_once 'pma_pchart_chart.php';

/**
 * Base class for every chart that uses only one series.
 * @abstract
 * @package phpMyAdmin
 */
abstract class PMA_pChart_single extends PMA_pChart_chart
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);
    }

    /**
     * data set preparation for single serie charts
     */
    protected function prepareDataSet()
    {
        $values = array_values($this->data);
        $keys = array_keys($this->data);

        // Dataset definition
        $this->dataSet->AddPoint($values[0], "Values");
        $this->dataSet->AddPoint($values[1], "Keys");
        
        //$this->dataSet->AddAllSeries();
        $this->dataSet->AddSerie("Values");

        $this->dataSet->SetAbsciseLabelSerie("Keys");

        $yLabel = $this->getYLabel();
        if (empty($yLabel)) {
            $this->setYLabel($keys[0]);
        }
        $xLabel = $this->getXLabel();
        if (empty($xLabel)) {
            $this->setXLabel($keys[1]);
        }

        $this->dataSet->SetXAxisName($this->getXLabel());
        $this->dataSet->SetYAxisName($this->getYLabel());
        $this->dataSet->SetSerieName($this->getYLabel(), "Values");
    }
}

?>
