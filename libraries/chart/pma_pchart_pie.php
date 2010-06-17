<?php

require_once 'pma_pchart_chart.php';

class PMA_pChart_Pie extends PMA_pChart_Chart
{
    private $border1Width = 7;
    private $border2Width = 5;

    function __construct($titleText, $data, $options = null)
    {
        parent::__construct($options);

        require_once './libraries/chart/pChart/pData.class';
        require_once './libraries/chart/pChart/pChart.class';

        // Dataset definition 
        $DataSet = new pData;
        $DataSet->AddPoint(array_values($data),"Serie1");
        $DataSet->AddPoint(array_keys($data),"Serie2");
        $DataSet->AddAllSeries();
        $DataSet->SetAbsciseLabelSerie("Serie2");

        // Initialise the graph
        $Test = new pChart($this->width, $this->height);
        foreach ($this->colors as $key => $color) {
            $Test->setColorPalette(
                    $key,
                    hexdec(substr($color, 1, 2)),
                    hexdec(substr($color, 3, 2)),
                    hexdec(substr($color, 5, 2))
            );
        }
        $Test->setFontProperties($this->fontPath.'tahoma.ttf', 8);
        $Test->drawFilledRoundedRectangle(
                $this->border1Width,
                $this->border1Width,
                $this->width - $this->border1Width,
                $this->height - $this->border1Width,
                5,240,240,240);
        $Test->drawRoundedRectangle(
                $this->border1Width,
                $this->border1Width,
                $this->width - $this->border1Width,
                $this->height - $this->border1Width,
                5,230,230,230);

        // Draw the pie chart
        $Test->AntialiasQuality = 0;
        $Test->setShadowProperties(2,2,200,200,200);
        //$Test->drawFlatPieGraphWithShadow($DataSet->GetData(),$DataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,8);
        //$Test->drawBasicPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,255,255,218,2);
        $Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,FALSE,60,30,10,1);
        $Test->clearShadow();

        $Test->drawTitle(10,20,$titleText,0,0,0);
        $Test->drawPieLegend(340,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250);

        ob_start();
        imagepng($Test->Picture);
        $output = ob_get_contents();
        ob_end_clean();

        $this->imageEncoded = base64_encode($output);
    }
}

?>
