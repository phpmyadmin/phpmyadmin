<?php

require_once('pma_ofc_chart.php');

/*
 * Implementation of pie chart using OFC.
 */
class PMA_OFC_Pie extends PMA_OFC_Chart
{
    function __construct($titleText, $data, $options = null)
    {
        parent::__construct();

        $this->handleOptions($options);

        include './libraries/chart/ofc/open-flash-chart.php';

        // create and style chart title
        $title = new title($titleText);
        $title->set_style($this->titleStyle);

        // create the main chart element - pie
        $pie = new pie();
        $pie->set_alpha(1);
        $pie->set_start_angle(35);
        $pie->add_animation(new pie_fade());
        $pie->add_animation(new pie_bounce(10));
        $pie->set_tooltip('#val# '._('of').' #total#<br>#percent# '._('of').' 100%');
        $pie->set_colours($this->colors);

        $values = array();
        foreach($data as $key => $value) {
            $values[] = new pie_value($value, $key);
        }

        $pie->set_values($values);
        $pie->gradient_fill();

        // create chart
        $this->chart = new open_flash_chart();
        $this->chart->x_axis = null;
        $this->chart->set_bg_colour($this->bgColor);
        $this->chart->set_title($title);
        $this->chart->add_element($pie);        
    }
}

?>
