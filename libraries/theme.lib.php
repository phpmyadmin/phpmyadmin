<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Helper functions for themes.
 *
 * @package phpMyAdmin
 */

/**
 * Remove filter for IE.
 *
 * @return string CSS code.
 */
function PMA_ieClearFilter() {
    return PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 6 && PMA_USR_BROWSER_VER <= 8
        ? 'filter: none'
        : '';
}

/**
 * Generates code for CSS gradient using various browser extensions.
 *
 * @param string $start_color Color of gradient start, hex value without #
 * @param string $end_color   Color of gradient end, hex value without #
 *
 * @return string CSS code.
 */
function PMA_css_gradient($start_color, $end_color)
{
    $result = array();
    $result[] = 'background-image: url(./themes/svg_gradient.php?from=' . $start_color . '&to=' . $end_color . ');';
    $result[] = 'background-size: 100% 100%;';
    $result[] = 'background: -webkit-gradient(linear, left top, left bottom, from(#' . $start_color . '), to(#' . $end_color . '));';
    $result[] = 'background: -moz-linear-gradient(top, #' . $start_color . ', #' . $end_color . ');';
    $result[] = 'background: -o-linear-gradient(top, #' . $start_color . ', #' . $end_color . ');';
    $result[] = PMA_ieFilter($start_color, $end_color);
    if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 6 && PMA_USR_BROWSER_VER <= 8) {
        $result[] = 'filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr="#' . $start_color . '", endColorstr="#' . $end_color . '");';
    }
    return implode("\n", $result);
}

?>
