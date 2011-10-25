<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Transformation
 */

function PMA_transformation_image_jpeg__link_info()
{
    return array(
        'info' => __('Displays a link to download this image.'),
        );
}

/**
 *
 */
function PMA_transformation_image_jpeg__link($buffer, $options = array(), $meta = '')
{
    include_once './libraries/transformations/global.inc.php';

    $transform_options = array ('string' => '<a href="transformation_wrapper.php' . $options['wrapper_link'] . '" alt="[__BUFFER__]">[BLOB]</a>');
    $buffer = PMA_transformation_global_html_replace($buffer, $transform_options);

    return $buffer;
}

?>
