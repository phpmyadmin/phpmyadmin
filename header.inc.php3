<?php
/* $Id$ */


/**
 * Gets a core script and starts output buffering work 
 */
require('./lib.inc.php3');
require('./ob_lib.inc.php3');

if ($cfgOBGzip)
{
   $ob_mode = out_buffer_mode_get();
   if ($ob_mode) {
       out_buffer_pre($ob_mode);
   }
}

/**
 * Sends http headers
 */
// Don't use cache (required for Opera)
$now = gmdate('D, d M Y H:i:s') . ' GMT';
header('Expires: ' . $now);
header('Last-Modified: ' . $now);
header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache'); // HTTP/1.0
// Define the charset to be used
header('Content-Type: text/html; charset=' . $charset);


/**
 * Sends the beginning of the html page then returns to the calling script
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>

<head>
<title>phpMyAdmin</title>
<style type="text/css">
<!--
body          {font-family: <?php echo $right_font_family; ?>; font-size: 10pt}
th            {font-family: <?php echo $right_font_family; ?>; font-size: 10pt; font-weight: bold; background-color: <?php echo $cfgThBgcolor;?>}
td            {font-family: <?php echo $right_font_family; ?>; font-size: 10pt}
form          {font-family: <?php echo $right_font_family; ?>; font-size: 10pt}
h1            {font-family: <?php echo $right_font_family; ?>; font-size: 16pt; font-weight: bold}
A:link        {font-family: <?php echo $right_font_family; ?>; font-size: 10pt; text-decoration: none; color: #0000ff}
A:visited     {font-family: <?php echo $right_font_family; ?>; font-size: 10pt; text-decoration: none; color: #0000ff}
A:hover       {font-family: <?php echo $right_font_family; ?>; font-size: 10pt; text-decoration: underline; color: #FF0000}
A:link.nav    {font-family: <?php echo $right_font_family; ?>; color: #000000}
A:visited.nav {font-family: <?php echo $right_font_family; ?>; color: #000000}
A:hover.nav   {font-family: <?php echo $right_font_family; ?>; color: #FF0000}
.nav          {font-family: <?php echo $right_font_family; ?>; color: #000000}
//-->
</style>
</head>

<body bgcolor="#F5F5F5" text="#000000" background="images/bkg.gif">
<?php
if (isset($db)) {
    echo '<h1> ' . $strDatabase . ' ' . htmlspecialchars($db);
    if (isset($table) && !isset($btnDrop)) {
        echo ' - ' . $strTable . ' '  . htmlspecialchars($table);
    }
    echo '</h1>' . "\n";
}
echo "\n";
?>
