<?php
/* $Id$ */

require("./lib.inc.php3");

/**
 * Send http headers
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

?>
<html>
<head>
<title>phpMyAdmin</title>
<style type="text/css">
<!--
<?php
// Hard coded font name and size depends on charset. This is a temporary and
// uggly fix
$font_family = ($charset == 'iso-8859-1')
             ? 'helvetica, arial, geneva, sans-serif'
             : 'sans-serif';
?>
body          {font-family: <?php echo $font_family; ?>; font-size: 10pt}
th            {font-family: <?php echo $font_family; ?>; font-size: 10pt; font-weight: bold; background-color: <?php echo $cfgThBgcolor;?>;}
td            {font-family: <?php echo $font_family; ?>; font-size: 10pt;}
form          {font-family: <?php echo $font_family; ?>; font-size: 10pt}
h1            {font-family: <?php echo $font_family; ?>; font-size: 16pt; font-weight: bold}
A:link        {font-family: <?php echo $font_family; ?>; font-size: 10pt; text-decoration: none; color: blue}
A:visited     {font-family: <?php echo $font_family; ?>; font-size: 10pt; text-decoration: none; color: blue}
A:hover       {font-family: <?php echo $font_family; ?>; font-size: 10pt; text-decoration: underline; color: red}
A:link.nav    {font-family: <?php echo $font_family; ?>; color: #000000}
A:visited.nav {font-family: <?php echo $font_family; ?>; color: #000000}
A:hover.nav   {font-family: <?php echo $font_family; ?>; color: red;}
.nav          {font-family: <?php echo $font_family; ?>; color: #000000}
//-->
</style>
</head>

<body bgcolor="#F5F5F5" text="#000000" background="images/bkg.gif">
<?php
if(isset($db))
{
    echo "<h1> $strDatabase $db";
    if (isset($table) && !isset($btnDrop))
    {
        echo " - $strTable $table";
    }
    echo "</h1>";
}
?>
