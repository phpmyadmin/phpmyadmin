<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Produce a PDF report (export) from a query
 *
 * @package    PhpMyAdmin-Export
 * @subpackage PDF
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Skip the plugin if TCPDF is not available.
 */
if (! file_exists(TCPDF_INC)) {
    $GLOBALS['skip_import'] = true;
    return;
}

/* Get the export interface */
require_once 'libraries/plugins/ExportPlugin.class.php';
/* Get the PMA_ExportPdf class */
require_once 'libraries/plugins/export/PMA_ExportPdf.class.php';

/**
 * Handles the export for the PDF class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage PDF
 */
class ExportPdf extends ExportPlugin
{
    /**
     * PMA_ExportPdf instance
     *
     * @var PMA_ExportPdf
     */
    private $_pdf;

    /**
     * PDF Report Title
     *
     * @var string
     */
    private $_pdfReportTitle;

    /**
     * Constructor
     */
    public function __construct()
    {
        // initialize the specific export PDF variables
        $this->initSpecificVariables();

        $this->setProperties();
    }

    /**
     * Initialize the local variables that are used for export PDF
     *
     * @return void
     */
    protected function initSpecificVariables()
    {
        if (! empty($_POST['pdf_report_title'])) {
            $this->_setPdfReportTitle($_POST['pdf_report_title']);
        }
        $this->_setPdf(new PMA_ExportPdf('L', 'pt', 'A3'));
    }

    /**
     * Sets the export PDF properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/ExportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/MessageOnlyPropertyItem.class.php";
        include_once "$props/options/items/TextPropertyItem.class.php";
        include_once "$props/options/items/HiddenPropertyItem.class.php";

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('PDF');
        $exportPluginProperties->setExtension('pdf');
        $exportPluginProperties->setMimeType('application/pdf');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup();
        $exportSpecificOptions->setName("Format Specific Options");

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup();
        $generalOptions->setName("general_opts");
        // create primary items and add them to the group
        $leaf = new MessageOnlyPropertyItem();
        $leaf->setName("explanation");
        $leaf->setText(
            __('(Generates a report containing the data of a single table)')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("report_title");
        $leaf->setText(__('Report title:'));
        $generalOptions->addProperty($leaf);
        $leaf = new HiddenPropertyItem();
        $leaf->setName("structure_or_data");
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
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
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader ()
    {
        $pdf_report_title = $this->_getPdfReportTitle();
        $pdf = $this->_getPdf();
        $pdf->Open();

        $attr = array('titleFontSize' => 18, 'titleText' => $pdf_report_title);
        $pdf->setAttributes($attr);
        $pdf->setTopMargin(30);

        return true;
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter ()
    {
        $pdf = $this->_getPdf();

        // instead of $pdf->Output():
        if (! PMA_exportOutputHandler($pdf->getPDFData())) {
            return false;
        }

        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBHeader ($db)
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBFooter ($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBCreate($db)
    {
        return true;
    }
    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return bool Whether it succeeded
     */
    public function exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        $pdf = $this->_getPdf();

        $attr = array('currentDb' => $db, 'currentTable' => $table);
        $pdf->setAttributes($attr);
        $pdf->mysqlReport($sql_query);

        return true;
    } // end of the 'PMA_exportData()' function


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the PMA_ExportPdf instance
     *
     * @return PMA_ExportPdf
     */
    private function _getPdf()
    {
        return $this->_pdf;
    }

    /**
     * Instantiates the PMA_ExportPdf class
     *
     * @param string $pdf PMA_ExportPdf instance
     *
     * @return void
     */
    private function _setPdf($pdf)
    {
        $this->_pdf = $pdf;
    }

    /**
     * Gets the PDF report title
     *
     * @return string
     */
    private function _getPdfReportTitle()
    {
        return $this->_pdfReportTitle;
    }

    /**
     * Sets the PDF report title
     *
     * @param string $pdfReportTitle PDF report title
     *
     * @return void
     */
    private function _setPdfReportTitle($pdfReportTitle)
    {
        $this->_pdfReportTitle = $pdfReportTitle;
    }
}
?>
