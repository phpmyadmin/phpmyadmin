<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin-Transformation
 * @version $Id$
 */

/**
 *
 */
function PMA_transformation_image_jpeg__inline($buffer, $options = array(), $meta = '') {
    require_once './libraries/transformations/global.inc.php';

    if (PMA_IS_GD2) {
        $transform_options = array ('string' => '<a href="transformation_wrapper.php' . $options['wrapper_link'] . '" target="_blank"><img src="transformation_wrapper.php' . $options['wrapper_link'] . '&amp;resize=jpeg&amp;newWidth=' . (isset($options[0]) ? $options[0] : '100') . '&amp;newHeight=' . (isset($options[1]) ? $options[1] : 100) . '" alt="[__BUFFER__]" border="0" /></a>');
    } else {
        $transform_options = array ('string' => '<img src="transformation_wrapper.php' . $options['wrapper_link'] . '" alt="[__BUFFER__]" width="320" height="240" />');
    }
    $buffer = PMA_transformation_global_html_replace($buffer, $transform_options);

    return $buffer;
}

?>
