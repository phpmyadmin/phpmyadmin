<?php
/**
 * Set of functions used to build MediaWiki dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function array_values;
use function count;
use function htmlspecialchars;
use function str_repeat;

/**
 * Handles the export for the MediaWiki class
 */
class ExportMediawiki extends ExportPlugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'mediawiki';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('MediaWiki Table');
        $exportPluginProperties->setExtension('mediawiki');
        $exportPluginProperties->setMimeType('text/plain');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup(
            'general_opts',
            __('Dump table'),
        );

        // what to dump (structure/data/both)
        $subgroup = new OptionsPropertySubgroup(
            'dump_table',
            __('Dump table'),
        );
        $leaf = new RadioPropertyItem('structure_or_data');
        $leaf->setValues(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
        );
        $subgroup->setSubgroupHeader($leaf);
        $generalOptions->addProperty($subgroup);

        // export table name
        $leaf = new BoolPropertyItem(
            'caption',
            __('Export table names'),
        );
        $generalOptions->addProperty($leaf);

        // export table headers
        $leaf = new BoolPropertyItem(
            'headers',
            __('Export table headers'),
        );
        $generalOptions->addProperty($leaf);
        //add the main group to the root group
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
        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Alias of db
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
     * Outputs table's structure
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $errorUrl   the url to go back in case of error
     * @param string  $exportMode 'create_table','triggers','create_view',
     *                             'stand_in'
     * @param string  $exportType 'server', 'database', 'table'
     * @param bool    $doRelation whether to include relation comments
     * @param bool    $doComments whether to include the pmadb-style column
     *                             comments as comments in the structure; this is
     *                             deprecated but the parameter is left here
     *                             because /export calls exportStructure()
     *                             also for other export types which use this
     *                             parameter
     * @param bool    $doMime     whether to include mime comments
     * @param bool    $dates      whether to include creation/update/check dates
     * @param mixed[] $aliases    Aliases of db/table/columns
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
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        $output = '';
        if ($exportMode === 'create_table') {
            $columns = $GLOBALS['dbi']->getColumns($db, $table);
            $columns = array_values($columns);
            $rowCnt = count($columns);

            // Print structure comment
            $output = $this->exportComment(
                'Table structure for '
                . Util::backquote($tableAlias),
            );

            // Begin the table construction
            $output .= '{| class="wikitable" style="text-align:center;"'
                . $this->exportCRLF();

            // Add the table name
            if (isset($GLOBALS['mediawiki_caption'])) {
                $output .= "|+'''" . $tableAlias . "'''" . $this->exportCRLF();
            }

            // Add the table headers
            if (isset($GLOBALS['mediawiki_headers'])) {
                $output .= '|- style="background:#ffdead;"' . $this->exportCRLF();
                $output .= '! style="background:#ffffff" | '
                    . $this->exportCRLF();
                for ($i = 0; $i < $rowCnt; ++$i) {
                    $colAs = $columns[$i]['Field'];
                    if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                        $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                    }

                    $output .= ' | ' . $colAs . $this->exportCRLF();
                }
            }

            // Add the table structure
            $output .= '|-' . $this->exportCRLF();
            $output .= '! Type' . $this->exportCRLF();
            for ($i = 0; $i < $rowCnt; ++$i) {
                $output .= ' | ' . $columns[$i]['Type'] . $this->exportCRLF();
            }

            $output .= '|-' . $this->exportCRLF();
            $output .= '! Null' . $this->exportCRLF();
            for ($i = 0; $i < $rowCnt; ++$i) {
                $output .= ' | ' . $columns[$i]['Null'] . $this->exportCRLF();
            }

            $output .= '|-' . $this->exportCRLF();
            $output .= '! Default' . $this->exportCRLF();
            for ($i = 0; $i < $rowCnt; ++$i) {
                $output .= ' | ' . $columns[$i]['Default'] . $this->exportCRLF();
            }

            $output .= '|-' . $this->exportCRLF();
            $output .= '! Extra' . $this->exportCRLF();
            for ($i = 0; $i < $rowCnt; ++$i) {
                $output .= ' | ' . $columns[$i]['Extra'] . $this->exportCRLF();
            }

            $output .= '|}' . str_repeat($this->exportCRLF(), 2);
        }

        return $this->export->outputHandler($output);
    }

    /**
     * Outputs the content of a table in MediaWiki format
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

        // Print data comment
        $output = $this->exportComment(
            $tableAlias != ''
                ? 'Table data for ' . Util::backquote($tableAlias)
                : 'Query results',
        );

        // Begin the table construction
        // Use the "wikitable" class for style
        // Use the "sortable"  class for allowing tables to be sorted by column
        $output .= '{| class="wikitable sortable" style="text-align:center;"'
            . $this->exportCRLF();

        // Add the table name
        if (isset($GLOBALS['mediawiki_caption'])) {
            $output .= "|+'''" . $tableAlias . "'''" . $this->exportCRLF();
        }

        // Add the table headers
        if (isset($GLOBALS['mediawiki_headers'])) {
            // Get column names
            $columnNames = $GLOBALS['dbi']->getColumnNames($db, $table);

            // Add column names as table headers
            if ($columnNames !== []) {
                // Use '|-' for separating rows
                $output .= '|-' . $this->exportCRLF();

                // Use '!' for separating table headers
                foreach ($columnNames as $column) {
                    if (! empty($aliases[$db]['tables'][$table]['columns'][$column])) {
                        $column = $aliases[$db]['tables'][$table]['columns'][$column];
                    }

                    $output .= ' ! ' . $column . $this->exportCRLF();
                }
            }
        }

        // Get the table data from the database
        $result = $GLOBALS['dbi']->query($sqlQuery, Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $fieldsCnt = $result->numFields();

        while ($row = $result->fetchRow()) {
            $output .= '|-' . $this->exportCRLF();

            // Use '|' for separating table columns
            for ($i = 0; $i < $fieldsCnt; ++$i) {
                $output .= ' | ' . $row[$i] . $this->exportCRLF();
            }
        }

        // End table construction
        $output .= '|}' . str_repeat($this->exportCRLF(), 2);

        return $this->export->outputHandler($output);
    }

    /**
     * Outputs result raw query in MediaWiki format
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string $errorUrl, string|null $db, string $sqlQuery): bool
    {
        if ($db !== null) {
            $GLOBALS['dbi']->selectDb($db);
        }

        return $this->exportData($db ?? '', '', $errorUrl, $sqlQuery);
    }

    /**
     * Outputs comments containing info about the exported tables
     *
     * @param string $text Text of comment
     *
     * @return string The formatted comment
     */
    private function exportComment(string $text = ''): string
    {
        // see https://www.mediawiki.org/wiki/Help:Formatting
        $comment = $this->exportCRLF();
        $comment .= '<!--' . $this->exportCRLF();
        $comment .= htmlspecialchars($text) . $this->exportCRLF();
        $comment .= '-->' . str_repeat($this->exportCRLF(), 2);

        return $comment;
    }

    /**
     * Outputs CRLF
     *
     * @return string CRLF
     */
    private function exportCRLF(): string
    {
        // The CRLF expected by the mediawiki format is "\n"
        return "\n";
    }
}
