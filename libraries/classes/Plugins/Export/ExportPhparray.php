<?php
/**
 * Set of functions used to build dumps of tables as PHP Arrays
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function preg_match;
use function preg_replace;
use function strtr;
use function var_export;

/**
 * Handles the export for the PHP Array class
 */
class ExportPhparray extends ExportPlugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'phparray';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('PHP array');
        $exportPluginProperties->setExtension('php');
        $exportPluginProperties->setMimeType('text/plain');
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
     * Removes end of comment from a string
     *
     * @param string $string String to replace
     */
    public function commentString(string $string): string
    {
        return strtr($string, '*/', '-');
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        $this->export->outputHandler(
            '<?php' . "\n"
            . '/**' . "\n"
            . ' * Export to PHP Array plugin for PHPMyAdmin' . "\n"
            . ' * @version ' . Version::VERSION . "\n"
            . ' */' . "\n\n",
        );

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
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader(string $db, string $dbAlias = ''): bool
    {
        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        $this->export->outputHandler(
            '/**' . "\n"
            . ' * Database ' . $this->commentString(Util::backquote($dbAlias))
            . "\n" . ' */' . "\n",
        );

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
     * Outputs the content of a table in PHP array format
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

        $result = $GLOBALS['dbi']->query($sqlQuery, Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED);

        $columnsCnt = $result->numFields();
        $columns = [];
        foreach ($result->getFieldNames() as $i => $colAs) {
            if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $columns[$i] = $colAs;
        }

        $tableFixed = $table;

        // fix variable names (based on
        // https://www.php.net/manual/en/language.variables.basics.php)
        if (! preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $tableAlias)) {
            // fix invalid characters in variable names by replacing them with
            // underscores
            $tableFixed = preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '_', $tableAlias);

            // variable name must not start with a number or dash...
            if (preg_match('/^[a-zA-Z_\x7f-\xff]/', $tableFixed) === 0) {
                $tableFixed = '_' . $tableFixed;
            }
        }

        $buffer = '';
        $recordCnt = 0;
        // Output table name as comment
        $buffer .= "\n" . '/* '
            . $this->commentString(Util::backquote($dbAlias)) . '.'
            . $this->commentString(Util::backquote($tableAlias)) . ' */' . "\n";
        $buffer .= '$' . $tableFixed . ' = array(';
        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        // Reset the buffer
        $buffer = '';
        while ($record = $result->fetchRow()) {
            $recordCnt++;

            if ($recordCnt == 1) {
                $buffer .= "\n" . '  array(';
            } else {
                $buffer .= ',' . "\n" . '  array(';
            }

            for ($i = 0; $i < $columnsCnt; $i++) {
                $buffer .= var_export($columns[$i], true)
                    . ' => ' . var_export($record[$i], true)
                    . ($i + 1 >= $columnsCnt ? '' : ',');
            }

            $buffer .= ')';
            if (! $this->export->outputHandler($buffer)) {
                return false;
            }

            // Reset the buffer
            $buffer = '';
        }

        $buffer .= "\n" . ');' . "\n";

        return $this->export->outputHandler($buffer);
    }

    /**
     * Outputs result of raw query as PHP array
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
}
