<?php
/**
 * Chart functions used to generate various types
 * of charts.
 *
 * @version $Id$
 * @package phpMyAdmin
 */

function PMA_chart_pie($titleText, $data, $options = null)
{
    require_once('./libraries/chart/pma_ofc_pie.php');

    $chart = new PMA_OFC_Pie($titleText, $data, $options);
    echo $chart->toString();
}

?>
