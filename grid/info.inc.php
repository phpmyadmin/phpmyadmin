<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/* info.inc.php 2007/04/26 windkiel */

/**
 * 2007-05 "Grid 2.9e working with version 2.8 .. 2.1x.y ...
 * the previous version had some refresh issues after switching from original
 * (all browser windows had to be closed to force a new session)
 * main differences to theme "original" in:
 *
 * smaller table margins/paddings
 * border-rounding only working in Geckos like Firefox 1 .. 2 ( -moz...),
 * IE6 ( left pointer/marker working with links ),
 * Opera 7 ~ 9.01 ( changing cursor "error.ico" on hover not supported )
 * ( Opera 9.01 needs a Ctrl F5 or restart to show theme/fontsize changes ! )
 *
 * comments, suggestions, bugreports are welcome:
 * http://sourceforge.net/users/windkiel/
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Grid
 */

/* Theme information */
$theme_name = 'Grid';
$theme_version = 2;
$theme_full_version = '2.9';
?>
