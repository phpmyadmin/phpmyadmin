<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Transformation
 */

function PMA_transformation_text_plain__formatted_info()
{
    return array(
        'info' => __('Displays the contents of the column as-is, without running it through htmlspecialchars(). That is, the column is assumed to contain valid HTML.'),
        );
}

/**
 *
 */
function PMA_transformation_text_plain__formatted($buffer, $options = array(), $meta = '')
{
    return $buffer;
}

?>
