<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

function PMA_transformation_application_octetstream__hex($buffer, $options = array(), $meta = '') {
    // possibly use a global transform and feed it with special options:
    // include('./libraries/transformations/global.inc.php');

    return chunk_split(bin2hex($buffer), 2, ' ');
}

?>
