<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Sanitizes $message, taking into account our special codes
 * for formatting
 *
 * @param   string   the message
 *
 * @return  string   the sanitized message
 *
 * @access  public
 */
function PMA_sanitize($message)
{
    $replace_pairs = array(
        '<'         => '&lt;',
        '>'         => '&gt;',
        '[i]'       => '<em>',      // deprecated by em
        '[/i]'      => '</em>',     // deprecated by em
        '[em]'      => '<em>',
        '[/em]'     => '</em>',
        '[b]'       => '<strong>',  // deprecated by strong
        '[/b]'      => '</strong>', // deprecated by strong
        '[strong]'  => '<strong>',
        '[/strong]' => '</strong>',
        '[tt]'      => '<code>',    // deprecated by CODE or KBD
        '[/tt]'     => '</code>',   // deprecated by CODE or KBD
        '[code]'    => '<code>',
        '[/code]'   => '</code>',
        '[kbd]'     => '<kbd>',
        '[/kbd]'    => '</kbd>',
        '[br]'      => '<br />',
        '[/a]'      => '</a>',
    );
    $sanitized_message = strtr($message, $replace_pairs);
    $sanitized_message = preg_replace(
        '/\[a@([^"@]*)@([^]"]*)\]/e',
        '\'<a href="\' . PMA_sanitizeUri(\'$1\') . \'" target="\2">\'',
        $sanitized_message);

    return $sanitized_message;
}

/**
 * removes javascript
 *
 * @uses    trim()
 * @uses    strtolower()
 * @uses    substr()
 * @param   string  uri
 */
function PMA_sanitizeUri($uri)
{
    $uri = trim($uri);

    if (strtolower(substr($uri, 0, 10)) === 'javascript') {
        return '';
    }

    return $uri;
}
?>
