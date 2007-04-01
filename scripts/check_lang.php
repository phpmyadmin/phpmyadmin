<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This test script checks all the language files to ensure there is no errors
 * inside and nothing is displayed on screen (eg no extra no blank line).
 *
 * @version $Id$
 * @package phpMyAdmin-test
 */

/**
 *
 */
$failed = array();
$passed = array();

// 1. Do check
$languageDirectory = dir('../lang');
while ($name = $languageDirectory->read()) {
    if (strpos($name, '.inc.php')) {
        // 1.1 Checks parse errors and extra blank line
        include '../lang/' . $name;
        header('X-Ping: pong');
        // 1.1 Checks "^M"
        $content = fread(fopen('../lang/' . $name, 'r'), filesize('../lang/' . $name));
        if ($pos = strpos(' ' . $content, "\015")) {
            $failed[] = $name;
        } else {
            $passed[] = $name;
        }
    } // end if
} // end while
$languageDirectory->close();

// 2. Checking results
$start      = '';
$failed_cnt = count($failed);
sort($failed);
$passed_cnt = count($passed);
sort($passed);
echo ($failed_cnt + $passed_cnt) . ' language files were checked.<br /><br />' . "\n";
if ($failed_cnt) {
    echo '&nbsp;&nbsp;1.&nbsp;' . $failed_cnt . ' contain(s) some "^M":<br />' . "\n";
    for ($i = 0; $i < $failed_cnt; $i++) {
        echo '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . $failed[$i] . '<br />' . "\n";
    } // end for
    if ($passed_cnt) {
        echo '<br />' . "\n";
        echo '&nbsp;&nbsp;2.&nbsp;' . $passed_cnt . ' seems right:<br />' . "\n";
        $start = '&nbsp;&nbsp;';
    }
} // end if
if ($passed_cnt) {
    if (!$failed_cnt) {
        echo 'They all passed checkings:<br />' . "\n";
    }
    for ($i = 0; $i < $passed_cnt; $i++) {
        echo $start . '&nbsp;&nbsp;-&nbsp;' . $passed[$i] . '<br />' . "\n";
    } // end for
} // end if
?>
