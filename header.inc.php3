<?php
/* $Id$ */


/**
 * Gets a core script and starts output buffering work
 */
require('./libraries/common.lib.php3');
require('./libraries/ob.lib.php3');
if ($cfgOBGzip) {
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
header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
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
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
<title>phpMyAdmin</title>
<?php
if (!empty($cfgPmaAbsoluteUri)) {
    echo '<base href="' . $cfgPmaAbsoluteUri . '" />' . "\n";
}
?>
<style type="text/css">
<!--
body          {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
pre, tt       {font-size: <?php echo $font_size; ?>}
th            {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold; background-color: <?php echo $cfgThBgcolor; ?>}
td            {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>}
form          {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>}
input         {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
select        {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
textarea      {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
h1            {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold}
A:link        {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; text-decoration: none; color: #0000FF}
A:visited     {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; text-decoration: none; color: #0000FF}
A:hover       {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; text-decoration: underline; color: #FF0000}
A:link.nav    {font-family: <?php echo $right_font_family; ?>; color: #000000}
A:visited.nav {font-family: <?php echo $right_font_family; ?>; color: #000000}
A:hover.nav   {font-family: <?php echo $right_font_family; ?>; color: #FF0000}
.nav          {font-family: <?php echo $right_font_family; ?>; color: #000000}
.warning      {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold; color: #FF0000}
//-->
</style>

<?php
$title     = '';
if (isset($db)) {
    $title .= str_replace('\'', '\\\'', $db);
}
if (isset($table)) {
    $title .= (empty($title) ? '' : '.') . str_replace('\'', '\\\'', $table);
}
if (!empty($cfgServer) && isset($cfgServer['host'])) {
    $title .= (empty($title) ? 'phpMyAdmin ' : ' ')
           . sprintf($strRunning, (empty($cfgServer['verbose']) ? str_replace('\'', '\\\'', $cfgServer['host']) : str_replace('\'', '\\\'', $cfgServer['verbose'])));
}
$title     .= (empty($title) ? '' : ' - ') . 'phpMyAdmin ' . PMA_VERSION;
?>
<script type="text/javascript" language="javascript">
<!--
// Updates the title of the frameset if possible (ns4 does not allow this)
if (typeof(parent.document.title) == 'string') {
    parent.document.title = '<?php echo $title; ?>';
}
<?php
// Add some javascript instructions if required
if (isset($js_to_run) && $js_to_run == 'functions.js') {
    echo "\n";
    ?>
// js form validation stuff
var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $strFormEmpty); ?>';
var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $strNotNumber); ?>';
var errorMsg2   = '<?php echo str_replace('\'', '\\\'', $strNotValidNumber); ?>';
var noDropDbMsg = '<?php echo((!$cfgAllowUserDropDatabase) ? str_replace('\'', '\\\'', $strNoDropDatabases) : ''); ?>';
var confirmMsg  = '<?php echo(($cfgConfirm) ? str_replace('\'', '\\\'', $strDoYouReally) : ''); ?>';
//-->
</script>
<script src="libraries/functions.js" type="text/javascript" language="javascript"></script>
    <?php
} else if (isset($js_to_run) && $js_to_run == 'user_details.js') {
    echo "\n";
    ?>
// js form validation stuff
var jsHostEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strHostEmpty']); ?>';
var jsUserEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strUserEmpty']); ?>';
var jsPasswordEmpty   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordEmpty']); ?>';
var jsPasswordNotSame = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordNotSame']); ?>';
//-->
</script>
<script src="libraries/user_details.js" type="text/javascript" language="javascript"></script>
    <?php
} else if (isset($js_to_run) && $js_to_run == 'indexes.js') {
    echo "\n";
    ?>
// js index validation stuff
var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $strFormEmpty); ?>';
var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $strNotNumber); ?>';
var errorMsg2   = '<?php echo str_replace('\'', '\\\'', $strNotValidNumber); ?>';
//-->
</script>
<script src="libraries/indexes.js" type="text/javascript" language="javascript"></script>
    <?php
} else {
    echo "\n";
    ?>
//-->
</script>
    <?php
}
echo "\n";
?>
</head>


<body bgcolor="<?php echo $cfgRightBgColor; ?>" background="images/bkg.gif">
<?php
if (isset($db)) {
    echo '<h1>' . $strDatabase . ' <i>' . htmlspecialchars($db) . '</i>';
    if (!empty($table)) {
        echo ' - ' . $strTable . ' <i>' . htmlspecialchars($table) . '</i>';
    }
    echo ' ' . sprintf($strRunning, ' <i>' . (($cfgServer['verbose']) ? $cfgServer['verbose'] : $cfgServer['host']) . '</i>');
    echo '</h1>' . "\n";
}
echo "\n";


/**
 * Sets a variable to remember headers have been sent
 */
$is_header_sent = TRUE;
?>
