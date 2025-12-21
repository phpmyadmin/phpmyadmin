<?php
/**
 * Export to TOON text.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function array_key_exists;

/**
 * Handles the export for the TOON format
 */
class ExportToon extends ExportPlugin
{
    private int $indent = 2;
    private string $separator = ',';

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'toon';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('TOON');
        $exportPluginProperties->setExtension('toon');
        $exportPluginProperties->setMimeType('text/toon');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create leaf items and add them to the group
        $leaf = new TextPropertyItem(
            'separator',
            __('Columns separated with:'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'indent',
            __('Indentation:'),
        );
        $generalOptions->addProperty($leaf);
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem('structure_or_data');
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Outputs the content of a table in JSON format
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
        $dbi = DatabaseInterface::getInstance();
        $result = $dbi->query($sqlQuery);

        $columnsCnt = $result->numFields();
        $rowsCnt = $result->numRows();
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $columns = [];
        foreach ($fieldsMeta as $i => $field) {
            $colAs = $this->getColumnAlias($aliases, $db, $table, $field->name);

            $columns[$i] = $colAs;
        }

        $buffer = "$dbAlias.$tableAlias" . '[' . $rowsCnt . ($this->separator !== ',' ? $this->separator : '') . ']{';
        foreach ($columns as $index => $column) {
            $buffer .= $column;

            if ($index !== count($columns) - 1) {
                $buffer .= $this->separator;
            }
        }
        $buffer .= "}:\n";
        
        if (! $this->outputHandler->addLine($buffer)) {
            return false;
        }

        $insertedLines = 0;
        while ($row = $result->fetchRow()) {
            $buffer = '';

            foreach ($row as $index => $col) {
                if ($index === 0) {
                    $buffer .= str_repeat(' ', $this->indent);
                }

                if ($col === null) {
                    $buffer .= 'null';
                    continue;
                }

                $buffer .= $col;

                if ($index !== $columnsCnt - 1) {
                    $buffer .= $this->separator;
                }
            }

            $insertedLines++;
            $buffer .= $insertedLines === $rowsCnt ? "\n\n" : "\n";
            if (! $this->outputHandler->addLine($buffer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Outputs result raw query in TOON format
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

    private function setupExportConfiguration(): void
    {
        $this->separator = str_replace('\\t', "\011", $this->separator);
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('toon_structure_or_data'),
            $exportConfig['toon_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
        
        $this->separator = $this->setStringValue(
            $request->getParsedBodyParam('toon_separator'),
            $exportConfig['toon_separator'] ?? $this->separator,
        );

        $this->indent = $this->setIntValue(
            (int) $request->getParsedBodyParam('toon_indent'),
            $exportConfig['toon_indent'] ?? $this->indent,
        );

        $this->setupExportConfiguration();
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

    private function setIntValue(mixed $fromRequest, mixed $fromConfig): int
    {
        if (is_int($fromRequest)) {
            return $fromRequest;
        }

        if (is_int($fromConfig)) {
            return $fromConfig;
        }

        return 0;
    }
}
