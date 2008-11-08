<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 *
 */
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
    // include './libraries/transformations/global.inc.php';

    // further operations on $buffer using the $options[] array.

    $allowed_programs = array();

    //
    // WARNING:
    //
    // It's up to administrator to allow anything here. Note that users may
    // specify any parameters, so when programs allow output redirection or
    // any other possibly dangerous operations, you should write wrapper
    // script that will publish only functions you really want.
    //
    // Add here program definitions like (note that these are NOT safe
    // programs):
    //
    //$allowed_programs[0] = '/usr/local/bin/tidy';
    //$allowed_programs[1] = '/usr/local/bin/validate';

    // no-op when no allowed programs
    if (count($allowed_programs) == 0) {
        return $buffer;
    }

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

    // needs PHP >= 4.3.0
    $newstring = '';
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w")
    );
    $process = proc_open($program . ' ' . $poptions, $descriptorspec, $pipes);
    if (is_resource($process)) {
        fwrite($pipes[0], $buffer);
        fclose($pipes[0]);

        while (!feof($pipes[1])) {
            $newstring .= fgets($pipes[1], 1024);
        }
        fclose($pipes[1]);
        // we don't currently use the return value
        $return_value = proc_close($process);
    }

    if ($options[2] == 1 || $options[2] == '2') {
        $retstring = htmlspecialchars($newstring);
    } else {
        $retstring = $newstring;
    }

    return $retstring;
}
?>
