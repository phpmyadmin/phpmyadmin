<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

function PMA_transformation_application_octetstream__download(&$buffer, $options = array(), $meta = '') {
global $row;

    if (isset($options[0]) && !empty($options[0])) {
        $cn = $options[0]; // filename
    } else {
        if (isset($options[1]) && !empty($options[1]) && isset($row[$options[1]])) {
            $cn = $row[$options[1]];
        } else {
            $cn = 'binary_file.dat';
        }
    }

    return
      sprintf(
        '<a href="transformation_wrapper.php%s&amp;ct=application/octet-stream&amp;cn=%s" title="%s">%s</a>',

        $options['wrapper_link'],
        urlencode($cn),
        htmlspecialchars($cn),
        htmlspecialchars($cn)
      );
}

?>
