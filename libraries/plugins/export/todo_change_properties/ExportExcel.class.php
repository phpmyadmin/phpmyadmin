<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Class for exporting CSV dumps of tables for excel
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CSV-Excel
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Extend the export CSV class */
require_once "libraries/plugins/export/ExportCsv.class.php";

/**
 * Handles the export for the CSV-Excel format
 *
 * @package PhpMyAdmin-Export
 */
class ExportExcel extends ExportCsv
{
    /**
     * Sets the export CSV for Excel properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $this->properties = array(
            'text' => __('CSV for MS Excel'),
            'extension' => 'csv',
            'mime_type' => 'text/comma-separated-values',
            'options' => array(),
            'options_text' => __('Options')
        );

        $this->properties['options'] = array(
            array(
                'type' => 'begin_group',
                'name' => 'general_opts'
            ),
            array(
                'type' => 'text',
                'name' => 'null',
                'text' => __('Replace NULL with:')
            ),
            array(
                'type' => 'bool',
                'name' => 'removeCRLF',
                'text' => __(
                    'Remove carriage return/line feed characters within columns'
                )
            ),
            array(
                'type' => 'bool',
                'name' => 'columns',
                'text' => __('Put columns names in the first row')
            ),
            array(
                'type' => 'select',
                'name' => 'edition',
                'values' => array(
                    'win' => 'Windows',
                    'mac_excel2003' => 'Excel 2003 / Macintosh',
                    'mac_excel2008' => 'Excel 2008 / Macintosh'),
                'text' => __('Excel edition:')
            ),
            array(
                'type' => 'hidden',
                'name' => 'structure_or_data'
            ),
            array(
                'type' => 'end_group'
            )
        );
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     */
    public function update (SplSubject $subject)
    {
    }
}
?>