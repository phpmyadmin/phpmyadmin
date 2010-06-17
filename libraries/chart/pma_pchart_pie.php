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
        $dataSet = new pData;
        $dataSet->AddPoint(array_values($data),"Values");
        $dataSet->AddPoint(array_keys($data),"Keys");
        $dataSet->AddAllSeries();
        $dataSet->SetAbsciseLabelSerie("Keys");

        // Initialise the graph
        $chart = new pChart($this->width, $this->height);
        foreach ($this->colors as $key => $color) {
            $chart->setColorPalette(
                    $key,
                    hexdec(substr($color, 1, 2)),
                    hexdec(substr($color, 3, 2)),
                    hexdec(substr($color, 5, 2))
            );
        }
        $chart->setFontProperties($this->fontPath.'tahoma.ttf', 8);
        $chart->drawFilledRoundedRectangle(
                $this->border1Width,
                $this->border1Width,
                $this->width - $this->border1Width,
                $this->height - $this->border1Width,
                5,
                $this->getBgColorComp(0),
                $this->getBgColorComp(1),
                $this->getBgColorComp(2)
                );
        $chart->drawRoundedRectangle(
                $this->border1Width,
                $this->border1Width,
                $this->width - $this->border1Width,
                $this->height - $this->border1Width,
                5,0,0,0);

        // Draw the pie chart
        $chart->AntialiasQuality = 0;
        $chart->setShadowProperties(2,2,200,200,200);
        //$Test->drawFlatPieGraphWithShadow($DataSet->GetData(),$DataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,8);
        //$Test->drawBasicPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,255,255,218,2);
        $chart->drawPieGraph($dataSet->GetData(),$dataSet->GetDataDescription(),180,160,120,PIE_PERCENTAGE,FALSE,60,30,10,1);
        $chart->clearShadow();

        $chart->drawTitle(20,20,$titleText,0,0,0);
        $chart->drawPieLegend(350,15,$dataSet->GetData(),$dataSet->GetDataDescription(),250,250,250);

        ob_start();
        imagepng($chart->Picture);
        $output = ob_get_contents();
        ob_end_clean();

        $this->imageEncoded = base64_encode($output);
    }
}

?>
