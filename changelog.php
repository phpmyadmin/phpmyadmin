<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Simple script to set correct charset for changelog
 *
 * @version $Id$
 */

$changelog = htmlspecialchars(file_get_contents('ChangeLog'));

$replaces = array(
    '@(http://[./a-zA-Z0-9.-]*[/a-zA-Z0-9])@'
    => '<a href="\\1">\\1</a>',

    // sourceforge users
    '/([0-9]{4}-[0-9]{2}-[0-9]{2}) (.+[^ ]) +&lt;(.*)@users.sourceforge.net&gt;/i'
    => '\\1 <a href="https://sourceforge.net/users/\\3/">\\2</a>',
    '/thanks to ([^\(\r\n]+) \(([-\w]+)\)/i'
    => 'thanks to <a href="https://sourceforge.net/users/\\2/">\\1</a>',
    '/thanks to ([^\(\r\n]+) -\s+([-\w]+)/i'
    => 'thanks to <a href="https://sourceforge.net/users/\\2/">\\1</a>',

    // mail adresse
    '/([0-9]{4}-[0-9]{2}-[0-9]{2}) (.+[^ ]) +&lt;(.*@.*)&gt;/i'
    => '\\1 <a href="mailto:\\3">\\2</a>',

    // linking patches
    '/patch\s*#?([0-9]{6,})/i'
    => '<a href="https://sourceforge.net/support/tracker.php?aid=\\1">patch #\\1</a>',

    // linking RFE
    '/(?:rfe|feature)\s*#?([0-9]{6,})/i'
    => '<a href="https://sourceforge.net/support/tracker.php?aid=\\1">RFE #\\1</a>',

    // linking files
    '/(\s+)([\\/a-z_0-9\.]+\.(?:php3?|html|pl|js|sh))/i'
    => '\\1<a href="http://phpmyadmin.svn.sourceforge.net/viewvc/phpmyadmin/trunk/phpMyAdmin/\\2?annotate=HEAD">\\2</a>',

    // FAQ entries
    '/FAQ ([0-9]+)\.([0-9a-z]+)/i'
    => '<a href="http://localhost/phpMyAdmin/Documentation.html#faq\\1_\\2">FAQ \\1.\\2</a>',

    // linking bugs
    '/bug\s*#?([0-9]{6,})/i'
    => '<a href="https://sourceforge.net/support/tracker.php?aid=\\1">bug #\\1</a>',

    // all other 6+ digit numbers are treated as bugs
    '/(?<!BUG|RFE|patch) #?([0-9]{6,})/i'
    => ' <a href="https://sourceforge.net/support/tracker.php?aid=\\1">bug #\\1</a>',

    // CVE/CAN entries
    '/((CAN|CVE)-[0-9]+-[0-9]+)/'
    => '<a href="http://cve.mitre.org/cgi-bin/cvename.cgi?name=\\1">\\1</a>',

    // Highlight releases (with links)
    '/((    ### )(([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+) (.*)))/'
    => '<a name="\\4_\\5_\\6_\\7"></a>\\2<a href="http://svn.sourceforge.net/viewvc/phpmyadmin/tags/RELEASE_\\4_\\5_\\6_\\7/phpMyAdmin">\\4.\\5.\\6.\\7 \\8</a>',
    '/((    ### )(([0-9]+)\.([0-9]+)\.([0-9]+) (.*)))/'
    => '<a name="\\4_\\5_\\6_\\7"></a>\\2<a href="http://svn.sourceforge.net/viewvc/phpmyadmin/tags/RELEASE_\\4_\\5_\\6/phpMyAdmin">\\4.\\5.\\6 \\7</a>',

    // Highlight releases (not linkable)
    '/(    ### )(.*)/'
    => '\\1<b>\\2</b>',

);

header('Content-type: text/html; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?'.'>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<link rel="icon" href="./favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
<title>phpMyAdmin - ChangeLog</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<h1>phpMyAdmin - ChangeLog</h1>
<?php
echo '<pre>';
echo preg_replace(array_keys($replaces), $replaces, $changelog);
echo '</pre>';
?>
</body>
</html>
