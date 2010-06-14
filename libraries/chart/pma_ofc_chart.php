<?php

require_once('pma_chart.php');

/*
 * Base class for every chart implemented using OFC.
 * Has the code used to embed OFC chart into the page.
 */
class PMA_OFC_Chart extends PMA_Chart
{
    protected $flashBaseUrl = 'js/';

    protected $chart = null;

    function __construct()
    {
        parent::__construct();
    }

    function get_embed_code($data)
    {
        $url = urlencode($url);

        // output buffer
        $out = array();

        // check for http or https:
        if (isset($_SERVER['HTTPS']))
        {
            if (strtoupper ($_SERVER['HTTPS']) == 'ON')
            {
                $protocol = 'https';
            }
            else
            {
                $protocol = 'http';
            }
        }
        else
        {
            $protocol = 'http';
        }

        // if there are more than one charts on the
        // page, give each a different ID
        global $open_flash_chart_seqno;
        $chart_id = 'chart';

        if (!isset($open_flash_chart_seqno))
        {
            $open_flash_chart_seqno = 1;
        }
        else
        {
            $open_flash_chart_seqno++;
        }

        $obj_id .= '_'. $open_flash_chart_seqno;
        $data_func_name = 'get_data_for_'.$obj_id;

        // all parameters for the OFC will be given by the return value
        // of the JS function
        $out[] = '<script type="text/javascript">';
        $out[] = 'function '.$data_func_name.'()';
        $out[] = '{';
        $out[] = "return '".str_replace("\n", '', $data)."';";
        $out[] = '}';
        $out[] = '</script>';

        $out[] = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="'.$protocol.'://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" ';
        $out[] = 'width="'.$this->width.'" height="'.$this->height.'" id="'.$obj_id.'" align="middle">';
        $out[] = '<param name="allowScriptAccess" value="sameDomain" />';
        $out[] = '<param name="movie" value="'.$this->flashBaseUrl.'open-flash-chart.swf?get-data='.$data_func_name.'" />';
        $out[] = '<param name="quality" value="high" />';
        $out[] = '<param name="bgcolor" value="#FFFFFF" />';
        $out[] = '<embed src="'.$this->flashBaseUrl.'open-flash-chart.swf?get-data='.$data_func_name.'" quality="high" bgcolor="#FFFFFF" width="'.$this->width.'" height="'.$this->height.'" name="'.$obj_id.'" align="middle" allowScriptAccess="sameDomain" ';
        $out[] = 'type="application/x-shockwave-flash" pluginspage="'.$protocol.'://www.macromedia.com/go/getflashplayer" id="'.$obj_id.'"/>';
        $out[] = '</object>';

        return implode("\n", $out);
    }

    function toString()
    {
       return $this->get_embed_code($this->chart->toPrettyString());
    }
}

?>