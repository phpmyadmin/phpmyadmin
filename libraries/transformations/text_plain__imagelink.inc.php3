<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!defined('PMA_TRANSFORMATION_TEXT_PLAIN__IMAGELINK')){
    define('PMA_TRANSFORMATION_TEXT_PLAIN__IMAGELINK', 1);
    
    function PMA_transformation_text_plain__imagelink($buffer, $options = array()) {
        include('./libraries/transformations/global.inc.php3');
        
        $transform_options = array ('string' => '<a href="' . (isset($options[0]) ? $options[0] : '') . $buffer . '" target="_blank"><img src="' . (isset($options[0]) ? $options[0] : '') . $buffer . '" border="0" width="' . (isset($options[1]) ? $options[1] : 100) . '" height="' . (isset($options[2]) ? $options[2] : 50) . '">' . $buffer . '</a>');
        $buffer = PMA_transformation_global_html_replace($buffer, $transform_options);
        return $buffer;
    }
}
