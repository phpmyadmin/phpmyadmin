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
require_once 'libraries/plugins/import/AbstractImportCsv.class.php';

// We need relations enabled and we work only on database
if ($GLOBALS['plugin_param'] !== 'table') {
    $GLOBALS['skip_import'] = true;
    return;
}

/**
 * Handles the import for the CSV format using load data
 *
 * @package    PhpMyAdmin-Import
 * @subpackage LDI
 */
class ImportLdi extends AbstractImportCsv
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
        if ($GLOBALS['cfg']['Import']['ldi_local_option'] == 'auto') {
            $GLOBALS['cfg']['Import']['ldi_local_option'] = false;

            $result = $GLOBALS['dbi']->tryQuery(
                'SELECT @@local_infile;'
            );
            if ($result != false && $GLOBALS['dbi']->numRows($result) > 0) {
                $tmp = $GLOBALS['dbi']->fetchRow($result);
                if ($tmp[0] == 'ON') {
                    $GLOBALS['cfg']['Import']['ldi_local_option'] = true;
                }
            }
            $GLOBALS['dbi']->freeResult($result);
            unset($result);
        }

        $generalOptions = parent::setProperties();
        $this->properties->setText('CSV using LOAD DATA');
        $this->properties->setExtension('ldi');

        $leaf = new TextPropertyItem();
        $leaf->setName("columns");
        $leaf->setText(__('Column names: '));
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem();
        $leaf->setName("ignore");
        $leaf->setText(__('Do not abort on INSERT error'));
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem();
        $leaf->setName("local_option");
        $leaf->setText(__('Use LOCAL keyword'));
        $generalOptions->addProperty($leaf);
    }

    /**
     * Handles the whole import logic
     *
     * @return void
     */
    public function doImport()
    {
        global $finished, $import_file, $compression, $charset_conversion, $table;
        global $ldi_local_option, $ldi_replace, $ldi_ignore, $ldi_terminated,
            $ldi_enclosed, $ldi_escaped, $ldi_new_line, $skip_queries, $ldi_columns;

        if ($import_file == 'none'
            || $compression != 'none'
            || $charset_conversion
        ) {
            // We handle only some kind of data!
            $GLOBALS['message'] = PMA_Message::error(
                __('This plugin does not support compressed imports!')
            );
            $GLOBALS['error'] = true;
            return;
        }

        $sql = 'LOAD DATA';
        if (isset($ldi_local_option)) {
            $sql .= ' LOCAL';
        }
        $sql .= ' INFILE \'' . PMA_Util::sqlAddSlashes($import_file) . '\'';
        if (isset($ldi_replace)) {
            $sql .= ' REPLACE';
        } elseif (isset($ldi_ignore)) {
            $sql .= ' IGNORE';
        }
        $sql .= ' INTO TABLE ' . PMA_Util::backquote($table);

        if (strlen($ldi_terminated) > 0) {
            $sql .= ' FIELDS TERMINATED BY \'' . $ldi_terminated . '\'';
        }
        if (strlen($ldi_enclosed) > 0) {
            $sql .= ' ENCLOSED BY \''
                . PMA_Util::sqlAddSlashes($ldi_enclosed) . '\'';
        }
        if (strlen($ldi_escaped) > 0) {
            $sql .= ' ESCAPED BY \''
                . PMA_Util::sqlAddSlashes($ldi_escaped) . '\'';
        }
        if (strlen($ldi_new_line) > 0) {
            if ($ldi_new_line == 'auto') {
                $ldi_new_line
                    = (PMA_Util::whichCrlf() == "\n")
                        ? '\n'
                        : '\r\n';
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
                $sql .= PMA_Util::backquote(
                    trim($tmp[$i], " \t\r\n\0\x0B`")
                );
            } // end for
            $sql .= ')';
        }

        PMA_importRunQuery($sql, $sql);
        PMA_importRunQuery();
        $finished = true;
    }
}
