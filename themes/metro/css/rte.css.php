<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Styles for management of Routines, Triggers and Events
 * for the Metro theme
 *
 * @package PhpMyAdmin-theme
 */
declare(strict_types=1);

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON') && ! defined('TESTSUITE')) {
    exit();
}
?>

.rte_table {
    table-layout: fixed;
}

.rte_table td {
    vertical-align: middle;
    padding: 0.2em;
}

.rte_table tr td:nth-child(1) {
    width: 20%;
}

.rte_table input,
.rte_table select,
.rte_table textarea {
    width: 100%;
    margin: 0;
    box-sizing: border-box;
    -ms-box-sizing: border-box;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
}

.rte_table input[type=checkbox],
.rte_table input[type=radio] {
    width: auto;
    margin-right: 6px;
}

.rte_table input[type=submit] {
    width: 49%;
}

.rte_table .routine_params_table {
    width: 100%;
}

.rte_table .half_width {
    width: 49%;
}
