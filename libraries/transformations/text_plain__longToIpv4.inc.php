<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin-Transformation
 * @version $Id: text_plain__dateformat.inc.php 10239 2007-04-01 09:51:41Z cybot_tm $
 */

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
