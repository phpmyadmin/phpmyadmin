<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * URL/hidden inputs generating.
 */


if (!defined('PMA_URL_GENERATION_LIB_INCLUDED')){
    define('PMA_URL_GENERATION_LIB_INCLUDED', 1);

    /**
     * Generates text with hidden inputs.
     *
     * @param   string   optional database name
     * @param   string   optional table name
     *
     * @return  string   string with input fields
     *
     * @global  string   the current language
     * @global  string   the current conversion charset
     * @global  string   the current server
     * @global  array    the configuration array
     * @global  boolean  whether recoding is allowed or not
     *
     * @access  public
     *
     * @author  nijel
     */
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
   
    /**
     * Generates text with URL parameters.
     *
     * @param   string   optional database name
     * @param   string   optional table name
     *
     * @return  string   string with URL parameters
     *
     * @global  string   the current language
     * @global  string   the current conversion charset
     * @global  string   the current server
     * @global  array    the configuration array
     * @global  boolean  whether recoding is allowed or not
     *
     * @access  public
     *
     * @author  nijel
     */
    function PMA_generate_common_url ($db = '', $table = '', $amp = '&amp;')
    {
        global $lang, $convcharset, $server;
        global $cfg, $allow_recoding;

        $result = 'lang=' . $lang
           . $amp . 'server=' . $server;
        if (isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] && $allow_recoding)
            $result .= $amp . 'convcharset=' . $convcharset;
        if (!empty($db))
            $result .= $amp . 'db='.urlencode($db);
        if (!empty($table))
            $result .= $amp . 'table='.urlencode($table);
        return $result;
    }
}
?>
