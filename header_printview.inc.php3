<?php
/* $Id$ */


/**
 * Gets a core script and starts output buffering work
 */
require('./libraries/common.lib.php3');
require('./libraries/ob.lib.php3');
if ($cfg['OBGzip']) {
    $ob_mode = PMA_outBufferModeGet();
    if ($ob_mode) {
        PMA_outBufferPre($ob_mode);
    }
}


/**
 * Sends http headers
 */
// Don't use cache (required for Opera)
$now = gmdate('D, d M Y H:i:s') . ' GMT';
header('Expires: 0'); // rfc2616 - Section 14.21
header('Last-Modified: ' . $now);
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache'); // HTTP/1.0
// Define the charset to be used
header('Content-Type: text/html; charset=' . $charset);


/**
 * Sends the beginning of the html page then returns to the calling script
 */
// Gets the font sizes to use
PMA_setFontSizes();
// Defines the cell alignment values depending on text direction
if ($text_dir == 'ltr') {
    $cell_align_left  = 'left';
    $cell_align_right = 'right';
} else {
    $cell_align_left  = 'right';
    $cell_align_right = 'left';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
<title><?php echo $strSQLResult; ?> - phpMyAdmin <?php echo PMA_VERSION ?></title>
<style type="text/css">
<!--
body  {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000; background-color: #ffffff}
h1    {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold}
table {border-width:1px; border-color:#000000; border-style:solid; border-collapse:collapse; border-spacing:0}
th    {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold; color: #000000; background-color: #ffffff; border-width:1px; border-color:#000000; border-style:solid; padding:2px}
td    {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000; background-color: #ffffff; border-width:1px; border-color:#000000; border-style:solid; padding:2px}
//-->
</style>
</head>


<body bgcolor="#ffffff">
<h1><?php echo $strSQLResult; ?></h1>
<p>
    <b><?php echo $strHost; ?>:</b> <?php echo $cfg['Server']['host'] . ((!empty($cfg['Server']['port'])) ? ':' . $cfg['Server']['port'] : ''); ?><br />
    <b><?php echo $strDatabase; ?>:</b> <?php echo htmlspecialchars($db); ?><br />
    <b><?php echo $strGenTime; ?>:</b> <?php echo PMA_localisedDate(); ?><br />
    <b><?php echo $strGenBy; ?>:</b> phpMyAdmin <?php echo PMA_VERSION; ?><br />
    <b><?php echo $strSQLQuery; ?>:</b> <?php echo htmlspecialchars($full_sql_query); ?>;
</p>
<?php

/**
 * Sets a variable to remember headers have been sent
 */
$is_header_sent = TRUE;
?>
