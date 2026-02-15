<?php
/**
 * Export to TOON text.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function array_key_last;
use function bin2hex;
use function ctype_digit;
use function is_string;
use function sprintf;
use function str_repeat;
use function str_replace;

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
        $generalOptions = new OptionsPropertyMainGroup('toon_general_opts');
        // create leaf items and add them to the group
        $leaf = new TextPropertyItem(
            'toon_separator',
            __('Columns separated with:'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'toon_indent',
            __('Indentation:'),
        );
        $generalOptions->addProperty($leaf);
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem('toon_structure_or_data');
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
    ): void {
        $dbAlias = $this->getDbAlias($aliases, $db);
        $tableAlias = $this->getTableAlias($aliases, $db, $table);
        // use buffered query to get $rowsCnt
        $result = $this->dbi->queryAsControlUser($sqlQuery);

        $columnsCnt = $result->numFields();
        $rowsCnt = $result->numRows();
        $fieldsMeta = $this->dbi->getFieldsMeta($result);

        $columns = [];
        foreach ($fieldsMeta as $i => $field) {
            $colAs = $this->getColumnAlias($aliases, $db, $table, $field->name);

            $columns[$i] = $colAs;
        }

        $buffer = sprintf(
            '%s.%s[%s%s]{',
            $dbAlias,
            $tableAlias,
            $rowsCnt,
            $this->separator !== ',' ? $this->separator : '',
        );
        foreach ($columns as $index => $column) {
            $buffer .= $column;

            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if ($index !== array_key_last($columns)) {
                $buffer .= $this->separator;
            }
        }

        $buffer .= "}:\n";

        $this->outputHandler->addLine($buffer);

        $insertedLines = 0;
        while ($row = $result->fetchRow()) {
            $buffer = '';

            foreach ($row as $index => $col) {
                if ($index === 0) {
                    $buffer .= str_repeat(' ', $this->indent);
                }

                if (
                    $col !== null
                    && ($fieldsMeta[$index]->isMappedTypeGeometry || $fieldsMeta[$index]->isBinary)
                ) {
                    $col = '0x' . bin2hex($col);
                }

                $buffer .= $col ?? 'null';

                // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
                if ($index !== $columnsCnt - 1) {
                    $buffer .= $this->separator;
                }
            }

            $insertedLines++;
            $buffer .= $insertedLines === $rowsCnt ? "\n\n" : "\n";
            $this->outputHandler->addLine($buffer);
        }
    }

    /**
     * Outputs result raw query in TOON format
     *
     * @param string $db       the database where the query is executed
     * @param string $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string $db, string $sqlQuery): void
    {
        if ($db !== '') {
            $this->dbi->selectDb($db);
        }

        $this->exportData($db, '', $sqlQuery);
    }

    private function setupExportConfiguration(): void
    {
        $this->separator = str_replace('\\t', "\011", $this->separator);
    }

    public function setExportOptions(ServerRequest $request, Export $exportConfig): void
    {
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('toon_structure_or_data'),
            $exportConfig->toon_structure_or_data,
            StructureOrData::StructureAndData,
        );

        $this->separator = $this->setStringValue(
            $request->getParsedBodyParam('toon_separator'),
            $exportConfig->toon_separator ?? $this->separator,
        );

        $this->indent = $this->setIntValue(
            $request->getParsedBodyParam('toon_indent'),
            $exportConfig->toon_indent ?? $this->indent,
        );
        // phpcs:enable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

        $this->setupExportConfiguration();
    }

    private function setStringValue(mixed $fromRequest, string $fromConfig): string
    {
        if (is_string($fromRequest) && $fromRequest !== '') {
            return $fromRequest;
        }

        if ($fromConfig !== '') {
            return $fromConfig;
        }

        return '';
    }

    private function setIntValue(mixed $fromRequest, int $fromConfig): int
    {
        if (ctype_digit((string) $fromRequest)) {
            return (int) $fromRequest;
        }

        return $fromConfig;
    }
}
