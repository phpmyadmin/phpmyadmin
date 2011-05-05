<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the base class that all charts using pChart inherit from and some
 * widely used constants
 * @package phpMyAdmin
 */

/**
 * 
 */
define('TOP', 0);
define('RIGHT', 1);
define('BOTTOM', 2);
define('LEFT', 3);

require_once 'pma_chart.php';

require_once 'pChart/pData.class';
require_once 'pChart/pChart.class';

/**
 * Base class for every chart implemented using pChart.
 * @abstract
 * @package phpMyAdmin
 */
abstract class PMA_pChart_chart extends PMA_chart
{
    /**
     * @var String  title text
     */
    protected $titleText;

    /**
     * @var array   data for the chart
     */
    protected $data;

    /**
     * @var object  pData object that holds the description of the data
     */
    protected $dataSet;

    /**
     * @var object  pChart object that holds the chart
     */
    protected $chart;

    /**
     * @var array   holds base64 encoded chart image parts
     */
    protected $partsEncoded = array();

    public function __construct($data, $options = null)
    {
        parent::__construct($options);

        $this->data = $data;

        $this->settings['fontPath'] = './libraries/chart/pChart/fonts/';

        $this->settings['scale'] = SCALE_ADDALLSTART0;

        $this->settings['labelHeight'] = 20;

        $this->settings['fontSize'] = 8;

        $this->settings['continuous'] = 'off';

        // as in CSS (top, right, bottom, left)
        $this->setAreaMargins(array(20, 20, 40, 60));
        
        // Get color settings from theme
        $this->settings = array_merge($this->settings,$GLOBALS['cfg']['chartColor']);
    }

    protected function init()
    {
        parent::init();

        // create pChart object
        $this->chart = new pChart($this->getWidth(), $this->getHeight());

        // create pData object
        $this->dataSet = new pData;

        $this->chart->reportWarnings('GD');
        $this->chart->ErrorFontName = $this->getFontPath().'DejaVuSans.ttf';

        // initialize colors
        foreach ($this->getColors() as $key => $color) {
            $this->chart->setColorPalette(
                    $key,
                    hexdec(substr($color, 1, 2)),
                    hexdec(substr($color, 3, 2)),
                    hexdec(substr($color, 5, 2))
            );
        }

        $this->chart->setFontProperties($this->getFontPath().'DejaVuSans.ttf', $this->getFontSize());

        $this->chart->setImageMap(true, 'mapid');
    }

    /**
     * data is put to the $dataSet object according to what type chart is
     * @abstract
     */
    abstract protected function prepareDataSet();

    /**
     * all components of the chart are drawn
     */
    protected function prepareChart()
    {
        $this->drawBackground();
        $this->drawChart();
    }

    /**
     * draws the background
     */
    protected function drawBackground()
    {
        $this->drawCommon();
        $this->drawTitle();
        $this->setGraphAreaDimensions();
        $this->drawGraphArea();
    }

    /**
     * draws the part of the background which is common to most of the charts
     */
    protected function drawCommon()
    {
        $this->chart->drawGraphAreaGradient(
                $this->getBgColor(RED),
                $this->getBgColor(GREEN),
                $this->getBgColor(BLUE),
                // With a gradientIntensity of 0 the background does't draw, oddly
                ($this->settings['gradientIntensity']==0)?1:$this->settings['gradientIntensity'],TARGET_BACKGROUND);
                
        if(is_string($this->settings['border']))
            $this->chart->addBorder(1,$this->getBorderColor(RED),$this->getBorderColor(GREEN),$this->getBorderColor(BLUE));
    }

    /**
     * draws the chart title
     */
    protected function drawTitle()
    {
        // Draw the title
        $this->chart->drawTextBox(
                0,
                0,
                $this->getWidth(),
                $this->getLabelHeight(),
                $this->getTitleText(),
                0,
                $this->getTitleColor(RED),
                $this->getTitleColor(GREEN),
                $this->getTitleColor(BLUE),
                ALIGN_CENTER,
                false,
                $this->getTitleBgColor(RED),
                $this->getTitleBgColor(GREEN),
                $this->getTitleBgColor(BLUE)
        );
    }

    /**
     * calculates and sets the dimensions that will be used for the actual graph
     */
    protected function setGraphAreaDimensions()
    {
        $this->chart->setGraphArea(
                $this->getAreaMargin(LEFT),
                $this->getLabelHeight() + $this->getAreaMargin(TOP),
                $this->getWidth() - $this->getAreaMargin(RIGHT),
                $this->getHeight() - $this->getAreaMargin(BOTTOM)
        );
    }

    /**
     * draws graph area (the area where all bars, lines, points will be seen)
     */
    protected function drawGraphArea()
    {
        $this->chart->drawGraphArea(
                $this->getGraphAreaColor(RED),
                $this->getGraphAreaColor(GREEN),
                $this->getGraphAreaColor(BLUE),
                FALSE
        );
        $this->chart->drawScale(
                $this->dataSet->GetData(),
                $this->dataSet->GetDataDescription(),
                $this->getScale(),
                $this->getScaleColor(RED),
                $this->getScaleColor(GREEN),
                $this->getScaleColor(BLUE),
                TRUE,0,2,TRUE
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
        
        $this->chart->drawGrid(
                4,
                TRUE,
                $this->getGridColor(RED),
                $this->getGridColor(GREEN),
                $this->getGridColor(BLUE),
                20
        );
    }

    /**
     * draws the chart
     * @abstract
     */
    protected abstract function drawChart();

    /**
     * Renders the chart, base 64 encodes the output and puts it into
     * array partsEncoded.
     *
     * Parameter can be used to slice the chart vertically into parts. This
     * solves an issue where some browsers (IE8) accept base64 images only up
     * to some length.
     *
     * @param   integer  $parts         number of parts to render.
     *                                  Default value 1 means that all the
     *                                  chart will be in one piece.
     */
    protected function render($parts = 1)
    {
        $fullWidth = 0;

        for ($i = 0; $i < $parts; $i++) {

            // slicing is vertical so part height is the full height
            $partHeight = $this->chart->YSize;

            // there will be some rounding erros, will compensate later
            $partWidth = round($this->chart->XSize / $parts);
            $fullWidth += $partWidth;
            $partX = $partWidth * $i;

            if ($i == $parts - 1) {
                // if this is the last part, compensate for the rounding errors
                $partWidth += $this->chart->XSize - $fullWidth;
            }

            // get a part from the full chart image
            $part = imagecreatetruecolor($partWidth, $partHeight);
            imagecopy($part, $this->chart->Picture, 0, 0, $partX, 0, $partWidth, $partHeight);

            // render part and save it to variable
            ob_start();
            imagepng($part, NULL, 9, PNG_ALL_FILTERS);
            $output = ob_get_contents();
            ob_end_clean();

            // base64 encode the current part
            $partEncoded = base64_encode($output);
            $this->partsEncoded[$i] = $partEncoded;
        }
    }

    /**
     * get the HTML and JS code for the configured chart
     * @return string   HTML and JS code for the chart
     */
    public function toString()
    {
        if (!function_exists('gd_info')) {
            array_push($this->errors, ERR_NO_GD);
            return '';
        }

        $this->init();
        $this->prepareDataSet();
        $this->prepareChart();

        //$this->chart->debugImageMap();
        //$this->chart->printErrors('GD');

        // check if a user wanted a chart in one part
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

        // add tooltips only if json is available
        if (function_exists('json_encode')) {
            $returnData .= '
                <script type="text/javascript">
                //<![CDATA[
                    imageMap.loadImageMap(\''.json_encode($this->getImageMap()).'\');
                //]]>
                </script>
            ';
        }
        else {
            array_push($this->errors, ERR_NO_JSON);
        }

        return $returnData;
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

    protected function getFontSize()
    {
        return $this->settings['fontSize'];
    }

    protected function isContinuous()
    {
        return $this->settings['continuous'] == 'on';
    }

    protected function getImageMap()
    {
        return $this->chart->getImageMap();
    }

    protected function getGraphAreaColor($component)
    {
        return $this->hexStrToDecComp($this->settings['graphAreaColor'], $component);
    }

    protected function getGraphAreaGradientColor($component)
    {
        return $this->hexStrToDecComp($this->settings['graphAreaGradientColor'], $component);
    }

    protected function getGridColor($component)
    {
        return $this->hexStrToDecComp($this->settings['gridColor'], $component);
    }

    protected function getScaleColor($component)
    {
        return $this->hexStrToDecComp($this->settings['scaleColor'], $component);
    }

    protected function getTitleBgColor($component)
    {
        return $this->hexStrToDecComp($this->settings['titleBgColor'], $component);
    }
    
    protected function getBorderColor($component) 
    {
        return $this->hexStrToDecComp($this->settings['border'], $component);
    }
}

?>
