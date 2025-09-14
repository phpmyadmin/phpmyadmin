<?php
/**
 * Set of functions used to build YAML dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function array_key_exists;
use function is_numeric;
use function str_replace;

/**
 * Handles the export for the YAML format
 */
class ExportYaml extends ExportPlugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'yaml';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('YAML');
        $exportPluginProperties->setExtension('yml');
        $exportPluginProperties->setMimeType('text/yaml');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
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
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        $this->export->outputHandler('%YAML 1.1' . "\n" . '---' . "\n");

        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $this->export->outputHandler('...' . "\n");

        return true;
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
        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);

        $columnsCnt = $result->numFields();
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $columns = [];
        foreach ($fieldsMeta as $i => $field) {
            $colAs = $this->getColumnAlias($aliases, $db, $table, $field->name);

            $columns[$i] = $colAs;
        }

        $recordCnt = 0;
        while ($record = $result->fetchRow()) {
            $recordCnt++;

            // Output table name as comment if this is the first record of the table
            if ($recordCnt === 1) {
                $buffer = '# ' . $dbAlias . '.' . $tableAlias . "\n";
                $buffer .= '-' . "\n";
            } else {
                $buffer = '-' . "\n";
            }

            /** @infection-ignore-all */
            for ($i = 0; $i < $columnsCnt; $i++) {
                if (! array_key_exists($i, $record)) {
                    continue;
                }

                if ($record[$i] === null) {
                    $buffer .= '  ' . $columns[$i] . ': null' . "\n";
                    continue;
                }

                $isNotString = isset($fieldsMeta[$i]) && $fieldsMeta[$i]->isNotType(FieldMetadata::TYPE_STRING);
                if (is_numeric($record[$i]) && $isNotString) {
                    $buffer .= '  ' . $columns[$i] . ': ' . $record[$i] . "\n";
                    continue;
                }

                $record[$i] = str_replace(
                    ['\\', '"', "\n", "\r"],
                    ['\\\\', '\"', '\n', '\r'],
                    $record[$i],
                );
                $buffer .= '  ' . $columns[$i] . ': "' . $record[$i] . '"' . "\n";
            }

            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Outputs result raw query in YAML format
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
            $request->getParsedBodyParam('yaml_structure_or_data'),
            $exportConfig['yaml_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
    }
}
