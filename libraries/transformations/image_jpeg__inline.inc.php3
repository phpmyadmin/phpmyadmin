<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!defined('PMA_TRANSFORMATION_IMAGE_JPEG__INLINE')){
    define('PMA_TRANSFORMATION_IMAGE_JPEG__INLINE', 1);
    
    function PMA_transformation_image_jpeg__inline($buffer, $options = array()) {
        include('./libraries/transformations/global.inc.php3');
        
        $transform_options = array ('string' => '<img src="transformation_wrapper.php3' . $options['wrapper_link'] . '" alt="[__BUFFER__]" width="320" height="240">');
        $buffer = PMA_transformation_global_html_replace($buffer, $transform_options);
        
        return $buffer;
    }
}
