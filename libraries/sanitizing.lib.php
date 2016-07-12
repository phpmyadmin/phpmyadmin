<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This is in a separate script because it's called from a number of scripts
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Checks whether given link is valid
 *
 * @param string $url URL to check
 *
 * @return boolean True if string can be used as link
 */
function PMA_checkLink($url)
{
    $valid_starts = array(
        'http://',
        'https://',
        './url.php?url=http%3A%2F%2F',
        './url.php?url=https%3A%2F%2F',
        './doc/html/',
    );
    if (defined('PMA_SETUP')) {
        $valid_starts[] = '?page=form&';
        $valid_starts[] = '?page=servers&';
    }
    foreach ($valid_starts as $val) {
        if (substr($url, 0, strlen($val)) == $val) {
            return true;
        }
    }
    return false;
}

/**
 * Callback function for replacing [a@link@target] links in bb code.
 *
 * @param array $found Array of preg matches
 *
 * @return string Replaced string
 */
function PMA_replaceBBLink($found)
{
    /* Check for valid link */
    if (! PMA_checkLink($found[1])) {
        return $found[0];
    }
    /* a-z and _ allowed in target */
    if (! empty($found[3]) && preg_match('/[^a-z_]+/i', $found[3])) {
        return $found[0];
    }

    /* Construct target */
    $target = '';
    if (! empty($found[3])) {
        $target = ' target="' . $found[3] . '"';
        if ($found[3] == '_blank') {
            $target .= ' rel="noopener noreferrer"';
        }
    }

    /* Construct url */
    if (substr($found[1], 0, 4) == 'http') {
        $url = PMA_linkURL($found[1]);
    } else {
        $url = $found[1];
    }

    return '<a href="' . $url . '"' . $target . '>';
}

/**
 * Callback function for replacing [doc@anchor] links in bb code.
 *
 * @param array $found Array of preg matches
 *
 * @return string Replaced string
 */
function PMA_replaceDocLink($found)
{
    $anchor = $found[1];
    if (strncmp('faq', $anchor, 3) == 0) {
        $page = 'faq';
    } else if (strncmp('cfg', $anchor, 3) == 0) {
        $page = 'cfg';
    } else {
        /* Guess */
        $page = 'setup';
    }
    $link = PMA_Util::getDocuLink($page, $anchor);
    return '<a href="' . $link . '" target="documentation">';
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
 * @param string  $message the message
 * @param boolean $escape  whether to escape html in result
 * @param boolean $safe    whether string is safe (can keep < and > chars)
 *
 * @return string   the sanitized message
 */
function PMA_sanitize($message, $escape = false, $safe = false)
{
    if (!$safe) {
        $message = strtr($message, array('<' => '&lt;', '>' => '&gt;'));
    }

    /* Interpret bb code */
    $replace_pairs = array(
        '[em]'      => '<em>',
        '[/em]'     => '</em>',
        '[strong]'  => '<strong>',
        '[/strong]' => '</strong>',
        '[code]'    => '<code>',
        '[/code]'   => '</code>',
        '[kbd]'     => '<kbd>',
        '[/kbd]'    => '</kbd>',
        '[br]'      => '<br />',
        '[/a]'      => '</a>',
        '[/doc]'      => '</a>',
        '[sup]'     => '<sup>',
        '[/sup]'    => '</sup>',
         // used in common.inc.php:
        '[conferr]' => '<iframe src="show_config_errors.php" />',
    );

    $message = strtr($message, $replace_pairs);

    /* Match links in bb code ([a@url@target], where @target is options) */
    $pattern = '/\[a@([^]"@]*)(@([^]"]*))?\]/';

    /* Find and replace all links */
    $message = preg_replace_callback($pattern, 'PMA_replaceBBLink', $message);

    /* Replace documentation links */
    $message = preg_replace_callback(
        '/\[doc@([a-zA-Z0-9_-]+)\]/',
        'PMA_replaceDocLink',
        $message
    );

    /* Possibly escape result */
    if ($escape) {
        $message = htmlspecialchars($message);
    }

    return $message;
}


/**
 * Sanitize a filename by removing anything besides legit characters
 *
 * Intended usecase:
 *    When using a filename in a Content-Disposition header
 *    the value should not contain ; or "
 *
 *    When exporting, avoiding generation of an unexpected double-extension file
 *
 * @param string  $filename    The filename
 * @param boolean $replaceDots Whether to also replace dots
 *
 * @return string  the sanitized filename
 *
 */
function PMA_sanitizeFilename($filename, $replaceDots = false)
{
    $pattern = '/[^A-Za-z0-9_';
    // if we don't have to replace dots
    if (! $replaceDots) {
        // then add the dot to the list of legit characters
        $pattern .= '.';
    }
    $pattern .= '-]/';
    $filename = preg_replace($pattern, '_', $filename);
    return $filename;
}

?>
