<?php
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * Simple script to set correct charset for changelog
 *
 * @id      $Id$
 * @todo    link release tags
 */

$changelog = htmlspecialchars(file_get_contents('ChangeLog'));

$replaces = array(
    // sourceforge addresses
    '/([0-9]{4}-[0-9]{2}-[0-9]{2}) (.+) &lt;(.*)@users.sourceforge.net&gt;/i'
    => '\\1 <a href="https://sourceforge.net/users/\\3/">\\2</a>',

    // mail adresse
    '/([0-9]{4}-[0-9]{2}-[0-9]{2}) (.+) &lt;(.*@.*)&gt;/i'
    => '\\1 <a href="mailto:\\3/">\\2</a>',

    // linking bugs
    '/bug\s+#([0-9]*)/i'
    => '<a href="https://sourceforge.net/tracker/index.php?func=detail&aid=\\1&amp;group_id=23067&amp;atid=377408">bug \\1</a>',

    // linking pacthes
    '/patch\s+#([0-9]*)/i'
    => '<a href="https://sourceforge.net/tracker/index.php?func=detail&aid=\\1&amp;group_id=23067&amp;atid=377410">patch \\1</a>',

    // linking RFE
    '/rfe\s+#([0-9]*)/i'
    => '<a href="https://sourceforge.net/tracker/index.php?func=detail&aid=\\1&amp;group_id=23067&amp;atid=377411">RFE \\1</a>',

    // linking files
    '/(\s+)([\\/a-z_0-9\.]+\.php)/i'
    => '\\1<a href="http://cvs.sourceforge.net/viewcvs.py/phpmyadmin/phpMyAdmin/\\2?annotate=HEAD">\\2</a>',
);

header('Content-type: text/html; charset=utf-8');
echo '<pre>';
echo preg_replace(array_keys($replaces), $replaces, $changelog);
echo '</pre>';
?>
