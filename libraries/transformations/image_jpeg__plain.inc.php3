<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!defined('PMA_TRANSFORMATION_IMAGE_JPEG__PLAIN')){
    define('PMA_TRANSFORMATION_IMAGE_JPEG__PLAIN', 1);
    
    function PMA_transformation_image_jpeg__plain($buffer, $options = array()) {
        include('./libraries/transformations/global.inc.php3');
        
        $transform_options = array ('string' => '<img src="' . (isset($options[0]) ? $options[0] : '') . '%s" border="0">');
        $buffer = PMA_transformation_global_html_replace($buffer, $transform_options);
        
        return $buffer;
    }
}
