<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets a core script and starts output buffering work
 */
require_once('./libraries/common.lib.php');
require_once('./libraries/ob.lib.php');
if ($cfg['OBGzip']) {
    $ob_mode = PMA_outBufferModeGet();
    if ($ob_mode) {
        PMA_outBufferPre($ob_mode);
    }
}

// Check parameters

PMA_checkParameters(array('db', 'full_sql_query'));


// garvin: For re-usability, moved http-headers
// to a seperate file. It can now be included by header.inc.php,
// queryframe.php, querywindow.php.

require_once('./libraries/header_http.inc.php');

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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php?lang=<?php echo $lang; ?>&amp;js_frame=print" />
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
    <?php if (isset($num_rows)) { ?><br />
    <b><?php echo $strRows; ?>:</b> <?php echo $num_rows; ?>
    <?php } ?>
</p>


<?php

/**
 * Sets a variable to remember headers have been sent
 */
$is_header_sent = TRUE;
?>
