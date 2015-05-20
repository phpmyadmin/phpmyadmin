<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Styles for CodeMirror editor
 * for the pmahomme theme
 *
 * @package    PhpMyAdmin-theme
 * @subpackage PMAHomme
 */

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON') && ! defined('TESTSUITE')) {
    exit();
}
?>

.CodeMirror {
  height: <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
}

#inline_editor_outer .CodeMirror {
    height: <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 0.4); ?>em;
}
