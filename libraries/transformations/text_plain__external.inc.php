<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

function PMA_EscapeShellArg($string) {
    return '\'' . str_replace('\'', '\\\'', $string) . '\'';
}

function PMA_SecureShellArgs($s) {
    $len = strlen($s);
    $inside_single = FALSE;
    $inside_double = FALSE;
    $is_escaped = FALSE;
    for($i = 0; $i < $len; $i++) {
        if (!$inside_single && $s[$i] == '\\') {
            $is_escaped = ! $is_escaped;
            continue;
        }
        if (!$inside_double && !$is_escaped && $s[$i] == '\'') {
           $inside_single = ! $inside_single;
           continue;
        }
        if (!$inside_single && !$is_escaped && $s[$i] == '"') {
           $inside_double = ! $inside_double;
           continue;
        }
        // escape shell special chars in we're not inside quotes
        if (!$inside_single && !$is_escaped && !$inside_double) {
            if (strstr('><$`|;&', $s[$i])) {
                $s = substr($s, 0, $i) . '\\' . substr($s, $i);
                $i++;
                continue;
            }
        }
        // in double quotes we need to escape more
        if ($inside_double && !$is_escaped) {
            if (strstr('$`', $s[$i])) {
                $s = substr($s, 0, $i) . '\\' . substr($s, $i);
                $i++;
            }
        }
    }
    return $s;
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

    if (!isset($options[0]) ||  $options[0] == '' || !isset($allowed_programs[$options[0]])) {
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

    $cmdline = 'echo ' . PMA_EscapeShellArg($buffer) . ' | ' . $program . ' ' . PMA_SecureShellArgs($poptions);
    $newstring = `$cmdline`;

    if ($options[2] == 1 || $options[2] == '2') {
        $retstring = htmlspecialchars($newstring);
    } else {
        $retstring = $newstring;
    }

    return $retstring;
}

?>
