<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin-Transformation
 * Has one option: the text to be appended (default '')
 */

function PMA_transformation_text_plain__append_info() {
    return array(
        'info' => __('Appends text to a string. The only option is the text to be appended (enclosed in single quotes, default empty string).'),
        );
}

function PMA_transformation_text_plain__append($buffer, $options = array(), $meta = '') {

    if (! isset($options[0]) ||  $options[0] == '') {
        $options[0] = '';
    }

    $newtext = $buffer . htmlspecialchars($options[0]);  //just append the option to the original text

    return $newtext;
}

?>
