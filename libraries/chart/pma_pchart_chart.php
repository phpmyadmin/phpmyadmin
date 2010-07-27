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

    protected $partsEncoded = array();

    public function __construct($data, $options = null)
    {
        parent::__construct($options);

        $this->data = $data;

        $this->settings['fontPath'] = './libraries/chart/pChart/fonts/';

        $this->settings['scale'] = SCALE_ADDALLSTART0;

        $this->settings['labelHeight'] = 20;

        // as in CSS (top, right, bottom, left)
        $this->setAreaMargins(array(20, 20, 40, 60));
    }

    protected function init()
    {
        parent::init();

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

        $this->chart->setFontProperties($this->getFontPath().'tahoma.ttf', 8);

        $this->chart->setImageMap(true, 'mapid');
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
        $this->chart->drawTextBox(0,0,$this->getWidth(),$this->getLabelHeight(),$this->getTitleText(),0,255,255,255,ALIGN_CENTER,TRUE,0,0,0,30);
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

    protected function render($parts = 1)
    {
        $fullWidth = 0;

        for ($i = 0; $i < $parts; $i++) {
            $partHeight = $this->chart->YSize;
            $partWidth = round($this->chart->XSize / $parts);
            $fullWidth += $partWidth;
            $partX = $partWidth * $i;

            if ($i == $parts - 1) {
                // compensate for the rounding errors in the last part
                $partWidth += $this->chart->XSize - $fullWidth;
            }

            $part = imagecreatetruecolor($partWidth, $partHeight);
            imagecopy($part, $this->chart->Picture, 0, 0, $partX, 0, $partWidth, $partHeight);

            ob_start();
            imagepng($part, NULL, 9, PNG_ALL_FILTERS);
            $output = ob_get_contents();
            ob_end_clean();

            $partEncoded = base64_encode($output);
            $this->partsEncoded[$i] = $partEncoded;
        }
    }

    public function toString()
    {
        if (function_exists('gd_info')) {
            $this->init();
            $this->prepareDataSet();
            $this->prepareChart();

            if ($this->isContinuous()) {
                $this->render(1);
            }
            else {
                $this->render(20);
            }

            $returnData = '<div id="chart">';
            foreach ($this->partsEncoded as $part) {
                $returnData .= '<img src="data:image/png;base64,'.$part.'" />';
            }
            $returnData .= '</div>';
            $returnData .= '
                <script type="text/javascript">
                imageMap.loadImageMap(\''.json_encode($this->getImageMap()).'\');
                </script>
            ';

            return $returnData;
        }
        else {
            array_push($this->errors, ERR_NO_GD);
            return '';
        }
    }

    protected function getLabelHeight()
    {
        return $this->settings['labelHeight'];
    }

    protected function setAreaMargins($areaMargins)
    {
        $this->settings['areaMargins'] = $areaMargins;
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

    protected function isContinuous()
    {
        return $this->settings['continuous'] == 'on';
    }

    protected function getImageMap()
    {
        return $this->chart->ImageMap;
    }
}

?>
