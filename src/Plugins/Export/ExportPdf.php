<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
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
use function extension_loaded;

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

    private bool $doRelation = false;

    private bool $doMime = false;

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'pdf';
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

    private function setupExportConfiguration(): void
    {
        $this->pdf->Open();

        $this->pdf->setTitleFontSize(18);
        $this->pdf->setTitleText($this->pdfReportTitle);
        $this->pdf->setTopMargin(30);
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        // instead of $pdf->Output():
        return $this->outputHandler->addLine($this->pdf->getPDFData());
    }

    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string  $db       database name
     * @param string  $table    table name
     * @param string  $sqlQuery SQL query for obtaining data
     * @param mixed[] $aliases  Aliases of db/table/columns
     */
    public function exportData(
        string $db,
        string $table,
        string $sqlQuery,
        array $aliases = [],
    ): bool {
        $dbAlias = $this->getDbAlias($aliases, $db);
        $tableAlias = $this->getTableAlias($aliases, $db, $table);
        $this->pdf->setCurrentDb($db);
        $this->pdf->setCurrentTable($table);
        $this->pdf->setDbAlias($dbAlias);
        $this->pdf->setTableAlias($tableAlias);
        $this->pdf->setAliases($aliases);
        $this->pdf->setPurpose(__('Dumping data'));
        $this->pdf->mysqlReport($sqlQuery);

        return true;
    }

    /**
     * Outputs result of raw query in PDF format
     *
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string|null $db, string $sqlQuery): bool
    {
        $this->pdf->setDbAlias('----');
        $this->pdf->setTableAlias('----');
        $this->pdf->setPurpose(__('Query result data'));

        if ($db !== null) {
            $this->pdf->setCurrentDb($db);
            DatabaseInterface::getInstance()->selectDb($db);
        }

        $this->pdf->mysqlReport($sqlQuery);

        return true;
    }

    /**
     * Outputs table structure
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $exportMode 'create_table', 'triggers', 'create_view', 'stand_in'
     * @param mixed[] $aliases    aliases for db/table/columns
     */
    public function exportStructure(string $db, string $table, string $exportMode, array $aliases = []): bool
    {
        $purpose = '';
        $dbAlias = $this->getDbAlias($aliases, $db);
        $tableAlias = $this->getTableAlias($aliases, $db, $table);
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

        $this->pdf->setCurrentDb($db);
        $this->pdf->setCurrentTable($table);
        $this->pdf->setDbAlias($dbAlias);
        $this->pdf->setTableAlias($tableAlias);
        $this->pdf->setAliases($aliases);
        $this->pdf->setPurpose($purpose);

        match ($exportMode) {
            'create_table',
            'create_view' => $this->pdf->getTableDef($db, $table, $this->doRelation, true, $this->doMime),
            'triggers' => $this->pdf->getTriggers($db, $table),
            default => true,
        };

        return true;
    }

    public static function isAvailable(): bool
    {
        return class_exists(TCPDF::class) && extension_loaded('curl');
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->pdfReportTitle = $request->getParsedBodyParam('pdf_report_title', '');

        $this->pdf = new Pdf('L', 'pt', 'A3');

        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('pdf_structure_or_data'),
            $exportConfig['pdf_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
        $this->doRelation = (bool) ($request->getParsedBodyParam('pdf_relation')
            ?? $exportConfig['pdf_relation'] ?? false);
        $this->doMime = (bool) ($request->getParsedBodyParam('pdf_mime') ?? $exportConfig['pdf_mime'] ?? false);

        $this->setupExportConfiguration();
    }
}
