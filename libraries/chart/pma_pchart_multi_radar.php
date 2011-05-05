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
 * implements multi radar chart
 * @package phpMyAdmin
 */
class PMA_pChart_multi_radar extends PMA_pChart_multi
{
    public function __construct($data, $options = null)
    {
        parent::__construct($data, $options);

        $this->normalizeValues();
    }

    /**
     * Get the largest value from the data and normalize all the other values.
     */
    private function normalizeValues()
    {
        $maxValue = 0;
        $keys = array_keys($this->data);
        $valueKey = $keys[1];

        // get the max value
        foreach ($this->data[$valueKey] as $values) {
            if (max($values) > $maxValue) {
                $maxValue = max($values);
            }
        }

        // normalize all the values according to the max value
        foreach ($this->data[$valueKey] as &$values) {
            foreach ($values as &$value) {
                $value = $value / $maxValue * 10;
            }
        }
    }

    /**
     * graph area for the radar chart does not include grid lines
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
     * draw multi radar chart
     */
    protected function drawChart()
    {
        parent::drawChart();

        // when drawing radar graph we can specify the border from the top of
        // graph area. We want border to be dynamic, so that either the top
        // or the side of the radar is some distance away from the top or the
        // side of the graph area.
        $areaWidth = $this->chart->GArea_X2 - $this->chart->GArea_X1;
        $areaHeight = $this->chart->GArea_Y2 - $this->chart->GArea_Y1;

        if ($areaHeight > $areaWidth) {
            $borderOffset = ($areaHeight - $areaWidth) / 2;
        }
        else {
            $borderOffset = 0;
        }

        // the least ammount that radar is away from the graph area side.
        $borderOffset += 40;

        // Draw the radar chart
        $this->chart->drawRadarAxis($this->dataSet->GetData(), $this->dataSet->GetDataDescription(), TRUE, $borderOffset, 
            120, 120, 120, 230, 230, 230, -1, 2);
        $this->chart->drawFilledRadar($this->dataSet->GetData(), $this->dataSet->GetDataDescription(), 50, $borderOffset);
    }
}

?>
