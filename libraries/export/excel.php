<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build CSV dumps of tables for excel
 *
 * @package PhpMyAdmin-Export
 * @subpackage CSV-Excel
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['excel'] = array(
        'text' => __('CSV for MS Excel'),
        'extension' => 'csv',
        'mime_type' => 'text/comma-separated-values',
        'options' => array(
            array('type' => 'begin_group', 'name' => 'general_opts'),
            array('type' => 'text', 'name' => 'null', 'text' => __('Replace NULL with:')),
            array('type' => 'bool', 'name' => 'removeCRLF', 'text' => __('Remove carriage return/line feed characters within columns')),
            array('type' => 'bool', 'name' => 'columns', 'text' => __('Put columns names in the first row')),
            array(
                'type' => 'select',
                'name' => 'edition',
                'values' => array(
                    'win' => 'Windows',
                    'mac_excel2003' => 'Excel 2003 / Macintosh',
                    'mac_excel2008' => 'Excel 2008 / Macintosh'),
                'text' => __('Excel edition:')),
            array('type' => 'hidden', 'name' => 'structure_or_data'),
            array('type' => 'end_group'),
            ),
        'options_text' => __('Options'),
        );
} else {
    /* Everything rest is coded in csv plugin */
    include './libraries/export/csv.php';
}
?>
