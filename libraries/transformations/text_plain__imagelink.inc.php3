<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!defined('PMA_TRANSFORMATION_TEXT_PLAIN__IMAGELINK')){
    define('PMA_TRANSFORMATION_TEXT_PLAIN__IMAGELINK', 1);
    
    function PMA_transformation_text_plain__imagelink($buffer, $options = array()) {
        include('./libraries/transformations/global.inc.php3');
        
        $transform_options = array ('string' => '<a href="' . (isset($options[0]) ? $options[0] : '') . $buffer . '" target="_blank">' . $buffer . '</a>');
        $buffer = PMA_transformation_global_html_replace($buffer, $transform_options);
        return $buffer;
    }
}
