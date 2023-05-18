<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Plugins\Export\Helpers\Pdf;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use TCPDF;

use function __;
use function class_exists;

/**
 * Produce a PDF report (export) from a query
 */
class ExportPdf extends ExportPlugin
{
    /**
     * PhpMyAdmin\Plugins\Export\Helpers\Pdf instance
     */
    private Pdf $pdf;

    /**
     * PDF Report Title
     */
    private string $pdfReportTitle = '';

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'pdf';
    }

    /**
     * Initialize the local variables that are used for export PDF.
     */
    protected function init(): void
    {
        if (! empty($_POST['pdf_report_title'])) {
            $this->pdfReportTitle = $_POST['pdf_report_title'];
        }

        $this->setPdf(new Pdf('L', 'pt', 'A3'));
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('PDF');
        $exportPluginProperties->setExtension('pdf');
        $exportPluginProperties->setMimeType('application/pdf');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new TextPropertyItem(
            'report_title',
            __('Report title:'),
        );
        $generalOptions->addProperty($leaf);
        // add the group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // what to dump (structure/data/both) main group
        $dumpWhat = new OptionsPropertyMainGroup(
            'dump_what',
            __('Dump table'),
        );
        $leaf = new RadioPropertyItem('structure_or_data');
        $leaf->setValues(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
        );
        $dumpWhat->addProperty($leaf);
        // add the group to the root group
        $exportSpecificOptions->addProperty($dumpWhat);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        $pdf = $this->getPdf();
        $pdf->Open();

        $pdf->setTitleFontSize(18);
        $pdf->setTitleText($this->pdfReportTitle);
        $pdf->setTopMargin(30);

        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $pdf = $this->getPdf();

        // instead of $pdf->Output():
        return $this->export->outputHandler($pdf->getPDFData());
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader(string $db, string $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter(string $db): bool
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    public function exportDBCreate(string $db, string $exportType, string $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string  $db       database name
     * @param string  $table    table name
     * @param string  $errorUrl the url to go back in case of error
     * @param string  $sqlQuery SQL query for obtaining data
     * @param mixed[] $aliases  Aliases of db/table/columns
     */
    public function exportData(
        string $db,
        string $table,
        string $errorUrl,
        string $sqlQuery,
        array $aliases = [],
    ): bool {
        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);
        $pdf = $this->getPdf();
        $pdf->setCurrentDb($db);
        $pdf->setCurrentTable($table);
        $pdf->setDbAlias($dbAlias);
        $pdf->setTableAlias($tableAlias);
        $pdf->setAliases($aliases);
        $pdf->setPurpose(__('Dumping data'));
        $pdf->mysqlReport($sqlQuery);

        return true;
    }

    /**
     * Outputs result of raw query in PDF format
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string $errorUrl, string|null $db, string $sqlQuery): bool
    {
        $pdf = $this->getPdf();
        $pdf->setDbAlias('----');
        $pdf->setTableAlias('----');
        $pdf->setPurpose(__('Query result data'));

        if ($db !== null) {
            $pdf->setCurrentDb($db);
            $GLOBALS['dbi']->selectDb($db);
        }

        $pdf->mysqlReport($sqlQuery);

        return true;
    }

    /**
     * Outputs table structure
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $errorUrl   the url to go back in case of error
     * @param string  $exportMode 'create_table', 'triggers', 'create_view',
     *                             'stand_in'
     * @param string  $exportType 'server', 'database', 'table'
     * @param bool    $doRelation whether to include relation comments
     * @param bool    $doComments whether to include the pmadb-style column
     *                             comments as comments in the structure;
     *                             this is deprecated but the parameter is
     *                             left here because /export calls
     *                             PMA_exportStructure() also for other
     *                             export types which use this parameter
     * @param bool    $doMime     whether to include mime comments
     * @param bool    $dates      whether to include creation/update/check dates
     * @param mixed[] $aliases    aliases for db/table/columns
     */
    public function exportStructure(
        string $db,
        string $table,
        string $errorUrl,
        string $exportMode,
        string $exportType,
        bool $doRelation = false,
        bool $doComments = false,
        bool $doMime = false,
        bool $dates = false,
        array $aliases = [],
    ): bool {
        $dbAlias = $db;
        $tableAlias = $table;
        $purpose = '';
        $this->initAlias($aliases, $dbAlias, $tableAlias);
        $pdf = $this->getPdf();
        // getting purpose to show at top
        switch ($exportMode) {
            case 'create_table':
                $purpose = __('Table structure');
                break;
            case 'triggers':
                $purpose = __('Triggers');
                break;
            case 'create_view':
                $purpose = __('View structure');
                break;
            case 'stand_in':
                $purpose = __('Stand in');
        }

        $pdf->setCurrentDb($db);
        $pdf->setCurrentTable($table);
        $pdf->setDbAlias($dbAlias);
        $pdf->setTableAlias($tableAlias);
        $pdf->setAliases($aliases);
        $pdf->setPurpose($purpose);

        /**
         * comment display set true as presently in pdf
         * format, no option is present to take user input.
         */
        switch ($exportMode) {
            case 'create_table':
                $pdf->getTableDef($db, $table, $doRelation, true, $doMime);
                break;
            case 'triggers':
                $pdf->getTriggers($db, $table);
                break;
            case 'create_view':
                $pdf->getTableDef($db, $table, $doRelation, true, $doMime);
                break;
            case 'stand_in':
                // export a stand-in definition to resolve view dependencies
                // Yet to develop this function
                //$pdf->getTableDefStandIn($db, $table);
        }

        return true;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the PhpMyAdmin\Plugins\Export\Helpers\Pdf instance
     */
    private function getPdf(): Pdf
    {
        return $this->pdf;
    }

    /**
     * Instantiates the PhpMyAdmin\Plugins\Export\Helpers\Pdf class
     *
     * @param Pdf $pdf The instance
     */
    private function setPdf(Pdf $pdf): void
    {
        $this->pdf = $pdf;
    }

    public static function isAvailable(): bool
    {
        return class_exists(TCPDF::class);
    }
}
