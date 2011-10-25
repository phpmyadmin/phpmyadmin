<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Transformation
 */

function PMA_transformation_text_plain__longToIpv4_info()
{
    return array(
        'info' => __('Converts an (IPv4) Internet network address into a string in Internet standard dotted format.'),
        );
}

/**
 * returns IPv4 address
 *
 * @see http://php.net/long2ip
 */
function PMA_transformation_text_plain__longToIpv4($buffer, $options = array(), $meta = '')
{
    if ($buffer < 0 || $buffer > 4294967295) {
        return $buffer;
    }

    return long2ip($buffer);
}

?>
