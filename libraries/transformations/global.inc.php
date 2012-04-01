<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * GLOBAL Plugin function.
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
 * @package PhpMyAdmin-Transformation
 */

/**
 * Replaces "[__BUFFER__]" occurences found in $options['string'] with the text
 * in $buffer, after performing a regular expression search and replace on 
 * $buffer using $options['regex'] and $options['regex_replace'].
 *
 * @param string $buffer        text that will be replaced in $options['string'],
 *                              after being formatted
 * @param array  $options       the options required to format $buffer
 *               = array (
 *                 'string'          => 'string',    // text containing "[__BUFFER__]"
 *                 'regex'           => 'mixed',     // the pattern to search for
 *                 'regex_replace'   => 'mixed',     // string or array of strings to replace with
 *               );
 *
 * @return string containing the text with all the replacements
 */
function PMA_transformation_global_html_replace($buffer, $options = array())
{
    if ( ! isset($options['string']) ) {
        $options['string'] = '';
    }

    if (
          isset($options['regex'])
          &&
          isset($options['regex_replace'])
    ) {
        $buffer = preg_replace('@' . str_replace('@', '\@', $options['regex']) . '@si', $options['regex_replace'], $buffer);
    }

    // Replace occurences of [__BUFFER__] with actual text
    $return = str_replace("[__BUFFER__]", $buffer, $options['string']);
    return $return;
}

?>
