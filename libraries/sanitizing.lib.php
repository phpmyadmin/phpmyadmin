<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This is in a separate script because it's called from a number of scripts
 *
 * @package phpMyAdmin
 */

/**
 * Sanitizes $message, taking into account our special codes
 * for formatting.
 *
 * If you want to include result in element attribute, you should escape it.
 *
 * Examples:
 *
 * <p><?php echo PMA_sanitize($foo); ?></p>
 *
 * <a title="<?php echo PMA_sanitize($foo, true); ?>">bar</a>
 *
 * @uses    preg_replace()
 * @uses    strtr()
 * @param   string   the message
 * @param   boolean  whether to escape html in result
 *
 * @return  string   the sanitized message
 *
 * @access  public
 */
function PMA_sanitize($message, $escape = false, $safe = false)
{
    if (!$safe) {
        $message = strtr($message, array('<' => '&lt;', '>' => '&gt;'));
    }
    $replace_pairs = array(
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
        '[sup]'      => '<sup>',
        '[/sup]'      => '</sup>',
    );
    $message = strtr($message, $replace_pairs);

    $pattern = '/\[a@([^"@]*)@([^]"]*)\]/';

    if (preg_match_all($pattern, $message, $founds, PREG_SET_ORDER)) {
        $valid_links = array(
            'http',  // default http:// links (and https://)
            './Do',  // ./Documentation
            './ur',  // url.php redirector
        );

        foreach ($founds as $found) {
            // only http... and ./Do... allowed
            if (! in_array(substr($found[1], 0, 4), $valid_links)) {
                return $message;
            }
            // a-z and _ allowed in target
            if (! empty($found[2]) && preg_match('/[^a-z_]+/i', $found[2])) {
                return $message;
            }
        }

        if (substr($found[1], 0, 4) == 'http') {
            $message = preg_replace($pattern, '<a href="' . PMA_linkURL($found[1]) . '" target="\2">', $message);
        } else {
            $message = preg_replace($pattern, '<a href="\1" target="\2">', $message);
        }
    }

    if ($escape) {
        $message = htmlspecialchars($message);
    }

    return $message;
}


/**
 * Sanitize a filename by removing anything besides A-Za-z0-9_.-
 *
 * Intended usecase:
 *    When using a filename in a Content-Disposition header the value should not contain ; or "
 *
 * @param   string  The filename
 *
 * @return  string  the sanitized filename
 *
 */
function PMA_sanitize_filename($filename) {
    $filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename);
    return $filename;
}

?>
