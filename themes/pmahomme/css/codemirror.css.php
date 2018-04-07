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
if (! defined('PHPMYADMIN') && ! defined('TESTSUITE')) {
    exit();
}
?>

.CodeMirror {
    height: <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
    direction: ltr;
}
#inline_editor_outer .CodeMirror {
    height: <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 0.4); ?>em;
}
.insertRowTable .CodeMirror {
    height: <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 0.6); ?>em;
    width: <?php echo ceil($GLOBALS['cfg']['TextareaCols'] * 0.6); ?>em;
    border: 1px solid #a9a9a9;
}
#pma_console .CodeMirror-gutters {
    background-color: initial;
    border: none;
}
span.cm-keyword, span.cm-statement-verb {
    color: #909;
}
span.cm-variable {
    color: black;
}
span.cm-comment {
    color: #808000;
}
span.cm-mysql-string {
    color: #008000;
}
span.cm-operator {
    color: fuchsia;
}
span.cm-mysql-word {
    color: black;
}
span.cm-builtin {
    color: #f00;
}
span.cm-variable-2 {
    color: #f90;
}
span.cm-variable-3 {
    color: #00f;
}
span.cm-separator {
    color: fuchsia;
}
span.cm-number {
    color: teal;
}
.autocomplete-column-name {
    display: inline-block;
}
.autocomplete-column-hint {
    display: inline-block;
    float: right;
    color: #666;
    margin-left: 1em;
}
.CodeMirror-hints {
    z-index: 200;
}
.CodeMirror-lint-tooltip {
    z-index: 200;
    font-family: inherit;
}
.CodeMirror-lint-tooltip code {
  font-family: monospace;
  font-weight: bold;
}
