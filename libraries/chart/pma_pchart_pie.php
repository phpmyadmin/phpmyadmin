<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */

/**
 *
 */
require_once 'pma_pchart_multi.php';

/**
 * implements pie chart
 * @package phpMyAdmin
 */
class PMA_pChart_Pie extends PMA_pChart_multi
{
    public function __construct($data, $options = null)
    {
        // limit data size, no more than 18 pie slices
        $data = array_slice($data, 0, 18, true);
        parent::__construct($data, $options);

        $this->setAreaMargins(array(20, 10, 20, 20));
    }

    /**
     * prepare data set for the pie chart
     */
    protected function prepareDataSet()
    {
        // Dataset definition 
        $this->dataSet->AddPoint(array_values($this->data), "Values");
        $this->dataSet->AddPoint(array_keys($this->data), "Keys");
        $this->dataSet->AddAllSeries();
        $this->dataSet->SetAbsciseLabelSerie("Keys");
    }

    /**
     * graph area for the pie chart does not include grid lines
     */
    protected function drawGraphArea()
    {
        $this->chart->drawGraphArea(
                $this->getGraphAreaColor(RED),
                $this->getGraphAreaColor(GREEN),
                $this->getGraphAreaColor(BLUE),
                FALSE
        );
		
		if($this->settings['gradientIntensity']>0)
			$this->chart->drawGraphAreaGradient(
					$this->getGraphAreaGradientColor(RED),
					$this->getGraphAreaGradientColor(GREEN),
					$this->getGraphAreaGradientColor(BLUE),
					$this->settings['gradientIntensity']
			);
		else
			$this->chart->drawGraphArea(
					$this->getGraphAreaGradientColor(RED),
					$this->getGraphAreaGradientColor(GREEN),
					$this->getGraphAreaGradientColor(BLUE)
			);
		
    }

    /**
     * draw the pie chart
     */
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
                120, PIE_PERCENTAGE, FALSE, 60, 30, 10, 1);
    }

    /**
     * draw legend for the pie chart
     */
    protected function drawLegend()
    {
        $this->chart->drawPieLegend(
                $this->getWidth() - $this->getLegendMargin(RIGHT) - $this->getLegendBoxWidth(),
                $this->getLabelHeight() + $this->getLegendMargin(TOP),
                $this->dataSet->GetData(),
                $this->dataSet->GetDataDescription(),
                250, 250, 250);
    }

    protected function getLegendBoxWidth()
    {
        $legendSize = $this->chart->getPieLegendBoxSize($this->dataSet->GetData());
        return $legendSize[0];
    }
}

?>
