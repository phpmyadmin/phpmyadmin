<?php

define('TOP', 0);
define('RIGHT', 1);
define('BOTTOM', 2);
define('LEFT', 3);

require_once 'pma_chart.php';

require_once 'pChart/pData.class';
require_once 'pChart/pChart.class';

/*
 * Base class for every chart implemented using pChart.
 */
abstract class PMA_pChart_Chart extends PMA_Chart
{
    protected $titleText;
    protected $data;

    protected $dataSet;
    protected $chart;

    protected $imageEncoded;

    public function __construct($titleText, $data, $options = null)
    {
        parent::__construct($options);

        $this->titleText = $titleText;
        $this->data = $data;

        $this->settings['fontPath'] = './libraries/chart/pChart/fonts/';

        $this->settings['scale'] = SCALE_ADDALLSTART0;

        $this->settings['labelHeight'] = 20;

        // as in CSS (top, right, bottom, left)
        $this->settings['areaMargins'] = array(20, 20, 40, 60);

        // create pChart object
        $this->chart = new pChart($this->getWidth(), $this->getHeight());

        // create pData object
        $this->dataSet = new pData;

        // initialize colors
        foreach ($this->getColors() as $key => $color) {
            $this->chart->setColorPalette(
                    $key,
                    hexdec(substr($color, 1, 2)),
                    hexdec(substr($color, 3, 2)),
                    hexdec(substr($color, 5, 2))
            );
        }
    }

    abstract protected function prepareDataSet();

    protected function prepareChart()
    {
        $this->drawBackground();
        $this->drawChart();
    }

    protected function drawBackground()
    {
        $this->drawCommon();
        $this->drawTitle();
        $this->setGraphAreaDimensions();
        $this->drawGraphArea();
    }

    protected function drawCommon()
    {
        $this->chart->setFontProperties($this->getFontPath().'tahoma.ttf', 8);
        $this->chart->drawGraphAreaGradient(
                $this->getBgColor(RED),
                $this->getBgColor(GREEN),
                $this->getBgColor(BLUE),
                50,TARGET_BACKGROUND);
        $this->chart->addBorder(2);
    }

    protected function drawTitle()
    {
        // Draw the title
        $this->chart->drawTextBox(0,0,$this->getWidth(),$this->getLabelHeight(),$this->titleText,0,255,255,255,ALIGN_CENTER,TRUE,0,0,0,30);
    }

    protected function setGraphAreaDimensions()
    {
        $this->chart->setGraphArea(
                $this->getAreaMargin(LEFT),
                $this->getLabelHeight() + $this->getAreaMargin(TOP),
                $this->getWidth() - $this->getAreaMargin(RIGHT),
                $this->getHeight() - $this->getAreaMargin(BOTTOM)
        );
    }

    protected function drawGraphArea()
    {
        $this->chart->drawGraphArea(213,217,221,FALSE);
        $this->chart->drawScale($this->dataSet->GetData(),$this->dataSet->GetDataDescription(),$this->getScale(),213,217,221,TRUE,0,2,TRUE);
        $this->chart->drawGraphAreaGradient(163,203,167,50);
        $this->chart->drawGrid(4,TRUE,230,230,230,20);
    }

    protected abstract function drawChart();

    protected function render()
    {
        ob_start();
        imagepng($this->chart->Picture);
        $output = ob_get_contents();
        ob_end_clean();

        $this->imageEncoded = base64_encode($output);
    }

    public function toString()
    {
        if (function_exists('gd_info')) {
            $this->prepareDataSet();
            $this->prepareChart();
            $this->render();

            return '<img id="pChartPicture1" src="data:image/png;base64,'.$this->imageEncoded.'" />';
        }
        else {
            return 'Missing GD library.';
        }
    }

    protected function getLabelHeight()
    {
        return $this->settings['labelHeight'];
    }

    protected function getAreaMargin($side)
    {
        return $this->settings['areaMargins'][$side];
    }

    protected function getFontPath()
    {
        return $this->settings['fontPath'];
    }

    protected function getScale()
    {
        return $this->settings['scale'];
    }
}

?>
