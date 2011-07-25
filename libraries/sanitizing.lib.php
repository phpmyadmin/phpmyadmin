<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This is in a separate script because it's called from a number of scripts
 *
 * @package phpMyAdmin
 */

/**
 * Checks whether given link is valid
 *
 * @param $url string URL to check.
 *
 * @return bool True if string can be used as link.
 */
function PMA_check_link($url) {
    if (substr($url, 0, 7) == 'http://') {
        return true;
    } elseif (substr($url, 0, 8) == 'https://') {
        return true;
    } elseif (!defined('PMA_SETUP') && substr($url, 0, 20) == './Documentation.html') {
        return true;
    } elseif (defined('PMA_SETUP') && substr($url, 0, 21) == '../Documentation.html') {
        return true;
    }
    return false;
}

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
 * @param string   the message
 * @param boolean  whether to escape html in result
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
    if (defined('PMA_SETUP')) {
        $replace_pairs['[a@Documentation.html'] = '[a@../Documentation.html';
    } else {
        $replace_pairs['[a@Documentation.html'] = '[a@./Documentation.html';
    }
    $message = strtr($message, $replace_pairs);

    $pattern = '/\[a@([^"@]*)(@([^]"]*))?\]/';

    if (preg_match_all($pattern, $message, $founds, PREG_SET_ORDER)) {
        foreach ($founds as $found) {
            // only http... and ./Do... allowed
            if (! PMA_check_link($found[1])) {
                return $message;
            }
            // a-z and _ allowed in target
            if (! empty($found[3]) && preg_match('/[^a-z_]+/i', $found[3])) {
                return $message;
            }
        }

        if (substr($found[1], 0, 4) == 'http') {
            $message = preg_replace($pattern, '<a href="' . PMA_linkURL($found[1]) . '" target="\3">', $message);
        } else {
            $message = preg_replace($pattern, '<a href="\1" target="\3">', $message);
        }
    }

    if ($escape) {
        $message = htmlspecialchars($message);
    }

    return $message;
}
?>
