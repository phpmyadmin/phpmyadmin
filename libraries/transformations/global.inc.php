<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * GLOBAL Plugin function (Garvin Hicking).
 * ---------------
 *
 * THIS FILE PROVIDES BASIC FUNCTIONS TO USE IN OTHER PLUGINS!
 *
 * The basic filename usage for any plugin, residing in the libraries/transformations directory is:
 *
 * -- <mime_type>_<mime_subtype>__<transformation_name>.inc.php
 *
 * The function name has to be the like above filename:
 *
 * -- function PMA_transformation_<mime_type>_<mime_subtype>__<transformation_name>.inc.php
 *
 * Please use short and expressive names. For now, special characters which aren't allowed in
 * filenames or functions should not be used.
 *
 * Please provide a comment for your function, what it does and what parameters are available.
 *
 */

function PMA_transformation_global_plain($buffer, $options = array(), $meta = '') {
    return htmlspecialchars($buffer);
}

function PMA_transformation_global_html($buffer, $options = array(), $meta = '') {
    return $buffer;
}

function PMA_transformation_global_html_replace($buffer, $options = array(), $meta = '') {
    if (!isset($options['string'])) {
        $options['string'] = '';
    }

    if (isset($options['regex']) && isset($options['regex_replace'])) {
        $buffer = preg_replace('@' . str_replace('@', '\@', $options['regex']) . '@si', $options['regex_replace'], $buffer);
    }

    // Replace occurences of [__BUFFER__] with actual text
    $return = str_replace("[__BUFFER__]", $buffer, $options['string']);
    return $return;
}

?>
