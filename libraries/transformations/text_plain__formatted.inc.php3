<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!defined('PMA_TRANSFORMATION_TEXT_PLAIN__FORMATTED')){
    define('PMA_TRANSFORMATION_TEXT_PLAIN__FORMATTED', 1);
    
    function PMA_transformation_text_plain__formatted($buffer, $options = array()) {
        return $buffer;
    }
}
