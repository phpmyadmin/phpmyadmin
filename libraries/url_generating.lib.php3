<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * URL/hidden inputs generating.
 */


if (!defined('PMA_URL_GENERATION_LIB_INCLUDED')){
    define('PMA_URL_GENERATION_LIB_INCLUDED', 1);

    function PMA_generate_common_hidden_inputs ($db = '', $table = '')
    {
        global $lang, $convcharset, $server;
        global $cfg, $allow_recoding;

        $result = '<input type="hidden" name="lang" value="' . $lang . '" />' . "\n" .
            '<input type="hidden" name="server" value="' . $server . '" />' . "\n";
        if (isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] && $allow_recoding)
            $result .= '<input type="hidden" name="convcharset" value="' . $convcharset . '" />'  . "\n";
        if (!empty($db))
            $result .= '<input type="hidden" name="db" value="'.htmlspecialchars($db).'" />';
        if (!empty($table))
            $result .= '<input type="hidden" name="table" value="'.htmlspecialchars($table).'" />';
        return $result;
    }
   
    function PMA_generate_common_url ($db = '', $table = '')
    {
        global $lang, $convcharset, $server;
        global $cfg, $allow_recoding;

        $result = 'lang=' . $lang
           . '&amp;server=' . $server;
        if (isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] && $allow_recoding)
            $result .= '&amp;convcharset=' . $convcharset;
        if (!empty($db))
            $result .= '&amp;db='.urlencode($db);
        if (!empty($table))
            $result .= '&amp;table='.urlencode($table);
        return $result;
    }
}
?>
