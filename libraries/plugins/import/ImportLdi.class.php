<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * CSV import plugin for phpMyAdmin using LOAD DATA
 *
 * @package    PhpMyAdmin-Import
 * @subpackage LDI
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the import interface */
require_once "libraries/plugins/ImportPlugin.class.php";

/**
 * Handles the import for the CSV format using load data
 *
 * @package PhpMyAdmin-Import
 */
class ImportLdi extends ImportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the import plugin properties.
     * Called in the constructor.
     *
     * @return void
     */
    protected function setProperties()
    {
        global $plugin_param;
        if ($plugin_param !== 'table') {
            return;
        }

        if ($GLOBALS['cfg']['Import']['ldi_local_option'] == 'auto') {
            $GLOBALS['cfg']['Import']['ldi_local_option'] = false;

            $result = PMA_DBI_try_query('SHOW VARIABLES LIKE \'local\\_infile\';');
            if ($result != false && PMA_DBI_num_rows($result) > 0) {
                $tmp = PMA_DBI_fetch_row($result);
                if ($tmp[1] == 'ON') {
                    $GLOBALS['cfg']['Import']['ldi_local_option'] = true;
                }
            }
            PMA_DBI_free_result($result);
            unset($result);
        }

        $this->properties = array(
            'text' => __('CSV using LOAD DATA'),
            // Following is nonsense, however we want to default to our
            // parser for csv
            'extension' => 'ldi',
            'options' => array(),
            'options_text' => __('Options'),
        );

        $this->properties['options'] = array(
            array(
                'type' => 'begin_group',
                'name' => 'general_opts'
            ),
            array(
                'type' => 'bool',
                'name' => 'replace',
                'text' => __('Replace table data with file')
            ),
            array(
                'type' => 'bool',
                'name' => 'ignore',
                'text' => __('Do not abort on INSERT error')
            ),
            array(
                'type' => 'text',
                'name' => 'terminated',
                'text' => __('Columns terminated by'),
                'size' => 2,
                'len'  => 2
            ),
            array(
                'type' => 'text',
                'name' => 'enclosed',
                'text' => __('Columns enclosed by'),
                'size' => 2,
                'len'  => 2
            ),
            array(
                'type' => 'text',
                'name' => 'escaped',
                'text' => __('Columns escaped by'),
                'size' => 2,
                'len'  => 2
            ),
            array(
                'type' => 'text',
                'name' => 'new_line',
                'text' => __('Lines terminated by'),
                'size' => 2
            ),
            array(
                'type' => 'text',
                'name' => 'columns',
                'text' => __('Column names')
            ),
            array(
                'type' => 'bool',
                'name' => 'local_option',
                'text' => __('Use LOCAL keyword')
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

    /**
     * Handles the whole import logic
     *
     * @return void
     */
    public function doImport()
    {
        global $finished, $error, $import_file, $compression, $charset_conversion;
        global $ldi_local_option, $ldi_replace, $ldi_terminated, $ldi_enclosed,
            $ldi_escaped, $ldi_new_line, $skip_queries, $ldi_columns;
        
        if ($import_file == 'none'
            || $compression != 'none'
            || $charset_conversion
        ) {
            // We handle only some kind of data!
            $message = PMA_Message::error(
                __('This plugin does not support compressed imports!')
            );
            $error = true;
            return;
        }

        $sql = 'LOAD DATA';
        if (isset($ldi_local_option)) {
            $sql .= ' LOCAL';
        }
        $sql .= ' INFILE \'' . PMA_sqlAddSlashes($import_file) . '\'';
        if (isset($ldi_replace)) {
            $sql .= ' REPLACE';
        } elseif (isset($ldi_ignore)) {
            $sql .= ' IGNORE';
        }
        $sql .= ' INTO TABLE ' . PMA_backquote($table);

        if (strlen($ldi_terminated) > 0) {
            $sql .= ' FIELDS TERMINATED BY \'' . $ldi_terminated . '\'';
        }
        if (strlen($ldi_enclosed) > 0) {
            $sql .= ' ENCLOSED BY \'' . PMA_sqlAddSlashes($ldi_enclosed) . '\'';
        }
        if (strlen($ldi_escaped) > 0) {
            $sql .= ' ESCAPED BY \'' . PMA_sqlAddSlashes($ldi_escaped) . '\'';
        }
        if (strlen($ldi_new_line) > 0) {
            if ($ldi_new_line == 'auto') {
                $ldi_new_line = PMA_whichCrlf() == "\n" ? '\n' : '\r\n';
            }
            $sql .= ' LINES TERMINATED BY \'' . $ldi_new_line . '\'';
        }
        if ($skip_queries > 0) {
            $sql .= ' IGNORE ' . $skip_queries . ' LINES';
            $skip_queries = 0;
        }
        if (strlen($ldi_columns) > 0) {
            $sql .= ' (';
            $tmp   = preg_split('/,( ?)/', $ldi_columns);
            $cnt_tmp = count($tmp);
            for ($i = 0; $i < $cnt_tmp; $i++) {
                if ($i > 0) {
                    $sql .= ', ';
                }
                /* Trim also `, if user already included backquoted fields */
                $sql .= PMA_backquote(trim($tmp[$i], " \t\r\n\0\x0B`"));
            } // end for
            $sql .= ')';
        }

        PMA_importRunQuery($sql, $sql);
        PMA_importRunQuery();
        $finished = true;
    }
}