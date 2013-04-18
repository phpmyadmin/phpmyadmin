<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Super class of CSV import plugins for phpMyAdmin
 *
 * @package    PhpMyAdmin-Import
 * @subpackage CSV
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the import interface */
require_once 'libraries/plugins/ImportPlugin.class.php';

/**
 * Super class of the import plugins for the CSV format
 *
 * @package    PhpMyAdmin-Import
 * @subpackage CSV
 */
abstract class AbstractImportCsv extends ImportPlugin
{
    /**
     * Populates the passed OptionsPropertyMainGroup object with options common to
     * CSV type imports.
     *
     * @param object $generalOptions main group of format specific options
     *
     * @return object main group of format specific options populated
     *                with common options
     */
    protected function populateCommonOptions($generalOptions)
    {
        // create common items and add them to the group
        $leaf = new BoolPropertyItem();
        $leaf->setName("replace");
        $leaf->setText(__('Replace table data with file'));
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("terminated");
        $leaf->setText(__('Columns separated with:'));
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("enclosed");
        $leaf->setText(__('Columns enclosed with:'));
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("escaped");
        $leaf->setText(__('Columns escaped with:'));
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("new_line");
        $leaf->setText(__('Lines terminated with:'));
        $leaf->setSize(2);
        $generalOptions->addProperty($leaf);

        return $generalOptions;
    }
}
?>