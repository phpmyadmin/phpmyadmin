<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

function PMA_EscapeShellArg($string, $prepend = '\'') {
    return $prepend . ereg_replace("'", "'\\''", $string) . $prepend;
}

function PMA_transformation_text_plain__external_nowrap($options = array()) {
    if (!isset($options[3]) || $options[3] == '') {
        $nowrap = true;
    } elseif ($options[3] == '1' || $options[3] == 1) {
        $nowrap = true;
    } else {
        $nowrap = false;
    }

    return $nowrap;
}

function PMA_transformation_text_plain__external($buffer, $options = array(), $meta = '') {
    // possibly use a global transform and feed it with special options:
    // include('./libraries/transformations/global.inc.php');

    // further operations on $buffer using the $options[] array.

    $allowed_programs = array();
    $allowed_programs[0] = '/usr/local/bin/tidy';
    $allowed_programs[1] = '/usr/local/bin/validate';

    if (!isset($options[0]) ||  $options[0] == '') {
        $program = $allowed_programs[0];
    } else {
        $program = $allowed_programs[$options[0]];
    }

    if (!isset($options[1]) || $options[1] == '') {
        $poptions = '-f /dev/null -i -wrap -q';
    } else {
        $poptions = $options[1];
    }

    if (!isset($options[2]) || $options[2] == '') {
        $options[2] = 1;
    }

    if (!isset($options[3]) || $options[3] == '') {
        $options[3] = 1;
    }

    $cmdline = 'echo ' . PMA_EscapeShellArg($buffer) . ' | ' . $program . ' ' . PMA_EscapeShellArg($poptions, '');
    $newstring = `$cmdline`;

    if ($options[2] == 1 || $options[2] == '2') {
        $retstring = htmlspecialchars($newstring);
    } else {
        $retstring = $newstring;
    }

    return $retstring;
}

?>
