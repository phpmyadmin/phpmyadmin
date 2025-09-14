<?php
/**
 * Set of functions used to build OpenDocument Spreadsheet dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\OpenDocument;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function bin2hex;
use function date;
use function htmlspecialchars;
use function is_string;
use function strtotime;

/**
 * Handles the export for the ODS class
 */
class ExportOds extends ExportPlugin
{
    public string $buffer = '';
    private bool $columns = false;
    private string $null = '';

    protected function init(): void
    {
        $this->buffer = '';
    }

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'ods';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('OpenDocument Spreadsheet');
        $exportPluginProperties->setExtension('ods');
        $exportPluginProperties->setMimeType('application/vnd.oasis.opendocument.spreadsheet');
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
            'null',
            __('Replace NULL with:'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'columns',
            __('Put columns names in the first row'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new HiddenPropertyItem('structure_or_data');
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        $this->buffer .= '<?xml version="1.0" encoding="utf-8"?>'
            . '<office:document-content '
            . OpenDocument::NS . ' office:version="1.0">'
            . '<office:automatic-styles>'
            . '<number:date-style style:name="N37"'
            . ' number:automatic-order="true">'
            . '<number:month number:style="long"/>'
            . '<number:text>/</number:text>'
            . '<number:day number:style="long"/>'
            . '<number:text>/</number:text>'
            . '<number:year/>'
            . '</number:date-style>'
            . '<number:time-style style:name="N43">'
            . '<number:hours number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:minutes number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:seconds number:style="long"/>'
            . '<number:text> </number:text>'
            . '<number:am-pm/>'
            . '</number:time-style>'
            . '<number:date-style style:name="N50"'
            . ' number:automatic-order="true"'
            . ' number:format-source="language">'
            . '<number:month/>'
            . '<number:text>/</number:text>'
            . '<number:day/>'
            . '<number:text>/</number:text>'
            . '<number:year/>'
            . '<number:text> </number:text>'
            . '<number:hours number:style="long"/>'
            . '<number:text>:</number:text>'
            . '<number:minutes number:style="long"/>'
            . '<number:text> </number:text>'
            . '<number:am-pm/>'
            . '</number:date-style>'
            . '<style:style style:name="DateCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N37"/>'
            . '<style:style style:name="TimeCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N43"/>'
            . '<style:style style:name="DateTimeCell" style:family="table-cell"'
            . ' style:parent-style-name="Default" style:data-style-name="N50"/>'
            . '</office:automatic-styles>'
            . '<office:body>'
            . '<office:spreadsheet>';

        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $this->buffer .= '</office:spreadsheet></office:body></office:document-content>';

        return $this->export->outputHandler(
            OpenDocument::create(
                'application/vnd.oasis.opendocument.spreadsheet',
                $this->buffer,
            ),
        );
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
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBCreate(string $db, string $dbAlias = ''): bool
    {
        return true;
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
        $tableAlias = $this->getTableAlias($aliases, $db, $table);
        $dbi = DatabaseInterface::getInstance();
        // Gets the data from the database
        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);
        $fieldsCnt = $result->numFields();
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $this->buffer .= '<table:table table:name="' . htmlspecialchars($tableAlias) . '">';

        // If required, get fields name at the first line
        if ($this->columns) {
            $this->buffer .= '<table:table-row>';
            foreach ($fieldsMeta as $field) {
                $colAs = $this->getColumnAlias($aliases, $db, $table, $field->name);

                $this->buffer .= '<table:table-cell office:value-type="string">'
                    . '<text:p>'
                    . htmlspecialchars($colAs)
                    . '</text:p>'
                    . '</table:table-cell>';
            }

            $this->buffer .= '</table:table-row>';
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $this->buffer .= '<table:table-row>';
            /** @infection-ignore-all */
            for ($j = 0; $j < $fieldsCnt; $j++) {
                if ($fieldsMeta[$j]->isMappedTypeGeometry) {
                    // export GIS types as hex
                    $row[$j] = '0x' . bin2hex($row[$j]);
                }

                if (! isset($row[$j])) {
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($this->null)
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isBinary && $fieldsMeta[$j]->isBlob) {
                    // ignore BLOB
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isType(FieldMetadata::TYPE_DATE)) {
                    $this->buffer .= '<table:table-cell office:value-type="date"'
                        . ' office:date-value="'
                        . date('Y-m-d', strtotime($row[$j]))
                        . '" table:style-name="DateCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isType(FieldMetadata::TYPE_TIME)) {
                    $this->buffer .= '<table:table-cell office:value-type="time"'
                        . ' office:time-value="'
                        . date('\P\TH\Hi\Ms\S', strtotime($row[$j]))
                        . '" table:style-name="TimeCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isType(FieldMetadata::TYPE_DATETIME)) {
                    $this->buffer .= '<table:table-cell office:value-type="date"'
                        . ' office:date-value="'
                        . date('Y-m-d\TH:i:s', strtotime($row[$j]))
                        . '" table:style-name="DateTimeCell">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif (
                    $fieldsMeta[$j]->isNumeric
                ) {
                    $this->buffer .= '<table:table-cell office:value-type="float"'
                        . ' office:value="' . $row[$j] . '" >'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            }

            $this->buffer .= '</table:table-row>';
        }

        $this->buffer .= '</table:table>';

        return true;
    }

    /**
     * Outputs result raw query in ODS format
     *
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string|null $db, string $sqlQuery): bool
    {
        if ($db !== null) {
            DatabaseInterface::getInstance()->selectDb($db);
        }

        return $this->exportData($db ?? '', '', $sqlQuery);
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('ods_structure_or_data'),
            $exportConfig['ods_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
        $this->columns = (bool) ($request->getParsedBodyParam('ods_columns')
            ?? $exportConfig['ods_columns'] ?? false);
        $this->null = $this->setStringValue(
            $request->getParsedBodyParam('ods_null'),
            $exportConfig['ods_null'] ?? null,
        );
    }

    private function setStringValue(mixed $fromRequest, mixed $fromConfig): string
    {
        if (is_string($fromRequest) && $fromRequest !== '') {
            return $fromRequest;
        }

        if (is_string($fromConfig) && $fromConfig !== '') {
            return $fromConfig;
        }

        return '';
    }
}
