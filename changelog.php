<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Simple script to set correct charset for changelog
 *
 * @package PhpMyAdmin
 */

/**
 * Gets core libraries and defines some variables
 */
require 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$response->disable();

$filename = CHANGELOG_FILE;

/**
 * Read changelog.
 */
// Check if the file is available, some distributions remove these.
if (is_readable($filename)) {

    // Test if the if is in a compressed format
    if (substr($filename, -3) == '.gz') {
        ob_start();
        readgzfile($filename);
        $changelog = ob_get_contents();
        ob_end_clean();
    } else {
        $changelog = file_get_contents($filename);
    }
} else {
    printf(
        __('The %s file is not available on this system, please visit www.phpmyadmin.net for more information.'),
        $filename
    );
    exit;
}

/**
 * Whole changelog in variable.
 */
$changelog = htmlspecialchars($changelog);

$tracker_url = 'https://sourceforge.net/support/tracker.php?aid=\\1';
$tracker_url_bug = 'https://sourceforge.net/p/phpmyadmin/bugs/\\1/';
$tracker_url_rfe = 'https://sourceforge.net/p/phpmyadmin/feature-requests/\\1/';
$tracker_url_patch = 'https://sourceforge.net/p/phpmyadmin/patches/\\1/';
$github_url = 'https://github.com/phpmyadmin/phpmyadmin/';

$replaces = array(
    '@(http://[./a-zA-Z0-9.-_-]*[/a-zA-Z0-9_])@'
    => '<a href="./url.php?url=\\1">\\1</a>',

    // sourceforge users
    '/([0-9]{4}-[0-9]{2}-[0-9]{2}) (.+[^ ]) +&lt;(.*)@users.sourceforge.net&gt;/i'
    => '\\1 <a href="./url.php?url=https://sourceforge.net/users/\\3/">\\2</a>',
    '/thanks to ([^\(\r\n]+) \(([-\w]+)\)/i'
    => 'thanks to <a href="./url.php?url=https://sourceforge.net/users/\\2/">\\1</a>',
    '/thanks to ([^\(\r\n]+) -\s+([-\w]+)/i'
    => 'thanks to <a href="./url.php?url=https://sourceforge.net/users/\\2/">\\1</a>',

    // mail address
    '/([0-9]{4}-[0-9]{2}-[0-9]{2}) (.+[^ ]) +&lt;(.*@.*)&gt;/i'
    => '\\1 <a href="mailto:\\3">\\2</a>',

    // linking patches
    '/patch\s*#?([0-9]{6,})/i'
    => '<a href="./url.php?url=' . $tracker_url . '">patch #\\1</a>',

    // linking RFE
    '/(?:rfe|feature)\s*#?([0-9]{6,})/i'
    => '<a href="./url.php?url=https://sourceforge.net/support/tracker.php?aid=\\1">RFE #\\1</a>',

    // linking files
    '/(\s+)([\\/a-z_0-9\.]+\.(?:php3?|html|pl|js|sh))/i'
    => '\\1<a href="./url.php?url=' . $github_url . 'commits/HEAD/\\2">\\2</a>',

    // FAQ entries
    '/FAQ ([0-9]+)\.([0-9a-z]+)/i'
    => '<a href="./url.php?url=http://docs.phpmyadmin.net/en/latest/faq.html#faq\\1-\\2">FAQ \\1.\\2</a>',

    // linking bugs
    '/bug\s*#?([0-9]{6,})/i'
    => '<a href="./url.php?url=https://sourceforge.net/support/tracker.php?aid=\\1">bug #\\1</a>',

    // all other 6+ digit numbers are treated as bugs
    '/(?<!bug|RFE|patch) #?([0-9]{6,})/i'
    => '<a href="./url.php?url=' . $tracker_url . '">bug #\\1</a>',

    // transitioned SF.net project bug/rfe/patch links
    // by the time we reach 6-digit numbers, we can probably retire the above links
    '/patch\s*#?([0-9]{4,5}) /i'
    => '<a href="./url.php?url=' . $tracker_url_patch . '">patch #\\1</a> ',
    '/(?:rfe|feature)\s*#?([0-9]{4,5}) /i'
    => '<a href="./url.php?url=' . $tracker_url_rfe . '">RFE #\\1</a> ',
    '/bug\s*#?([0-9]{4,5}) /i'
    => '<a href="./url.php?url=' . $tracker_url_bug . '">bug #\\1</a> ',
    '/(?<!bug|RFE|patch) #?([0-9]{4,5}) /i'
    => '<a href="./url.php?url=' . $tracker_url_bug . '">bug #\\1</a> ',

    // CVE/CAN entries
    '/((CAN|CVE)-[0-9]+-[0-9]+)/'
    => '<a href="./url.php?url=http://cve.mitre.org/cgi-bin/cvename.cgi?name=\\1">\\1</a>',

    // PMASAentries
    '/(PMASA-[0-9]+-[0-9]+)/'
    => '<a href="./url.php?url=https://www.phpmyadmin.net/security/\\1/">\\1</a>',

    // Highlight releases (with links)
    '/([0-9]+)\.([0-9]+)\.([0-9]+)\.0 (\([0-9-]+\))/'
    => '<a name="\\1_\\2_\\3"></a>'
        . '<a href="./url.php?url=' . $github_url . 'commits/RELEASE_\\1_\\2_\\3">'
        . '\\1.\\2.\\3.0 \\4</a>',
    '/([0-9]+)\.([0-9]+)\.([0-9]+)\.([1-9][0-9]*) (\([0-9-]+\))/'
    => '<a name="\\1_\\2_\\3_\\4"></a>'
        . '<a href="./url.php?url=' . $github_url . 'commits/RELEASE_\\1_\\2_\\3_\\4">'
        . '\\1.\\2.\\3.\\4 \\5</a>',

    // Highlight releases (not linkable)
    '/(    ### )(.*)/'
    => '\\1<b>\\2</b>',

);

header('Content-type: text/html; charset=utf-8');
?>
<!DOCTYPE HTML>
<html lang="en" dir="ltr">
<head>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <title>phpMyAdmin - ChangeLog</title>
    <meta charset="utf-8" />
</head>
<body>
<h1>phpMyAdmin - ChangeLog</h1>
<?php
echo '<pre>';
echo preg_replace(array_keys($replaces), $replaces, $changelog);
echo '</pre>';
?>
<script type="text/javascript">
var links = document.getElementsByTagName("a");
for(var i = 0; i < links.length; i++) {
    links[i].target = "_blank";
}
</script>
</body>
</html>
