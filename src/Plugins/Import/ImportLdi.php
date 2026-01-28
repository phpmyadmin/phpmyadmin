<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\File;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Message;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function is_array;
use function preg_split;
use function trim;

use const PHP_EOL;

/**
 * CSV import plugin for phpMyAdmin using LOAD DATA
 */
class ImportLdi extends AbstractImportCsv
{
    private bool $localOption = false;
    private bool $replace = false;
    private bool $ignore = false;
    private string $terminated = '';
    private string $enclosed = '';
    private string $escaped = '';
    private string $newLine = '';
    private string $columns = '';

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'ldi';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('CSV using LOAD DATA');
        $importPluginProperties->setExtension('ldi');

        if (! self::isAvailable()) {
            return $importPluginProperties;
        }

        if ($this->config->settings['Import']['ldi_local_option'] === 'auto') {
            $this->setLdiLocalOptionConfig();
        }

        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        $generalOptions = $this->getGeneralOptions();

        $leaf = new TextPropertyItem(
            'columns',
            __('Column names: '),
        );
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'ignore',
            __('Do not abort on INSERT error'),
        );
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'local_option',
            __('Use LOCAL keyword'),
        );
        $generalOptions->addProperty($leaf);

        // add the main group to the root group
        $importSpecificOptions->addProperty($generalOptions);

        // set the options for the import plugin property item
        $importPluginProperties->setOptions($importSpecificOptions);

        return $importPluginProperties;
    }

    public function setImportOptions(ServerRequest $request): void
    {
        $this->localOption = $request->getParsedBodyParam('ldi_local_option') !== null;
        $this->replace = $request->getParsedBodyParam('ldi_replace') !== null;
        $this->ignore = $request->getParsedBodyParam('ldi_ignore') !== null;
        $this->terminated = $request->getParsedBodyParamAsString('ldi_terminated', '');
        $this->enclosed = $request->getParsedBodyParamAsString('ldi_enclosed', '');
        $this->escaped = $request->getParsedBodyParamAsString('ldi_escaped', '');
        $this->newLine = $request->getParsedBodyParamAsString('ldi_new_line', '');
        $this->columns = $request->getParsedBodyParamAsString('ldi_columns', '');
    }

    /**
     * Handles the whole import logic
     *
     * @return string[]
     */
    public function doImport(File|null $importHandle = null): array
    {
        $sqlStatements = [];
        $compression = '';
        if ($importHandle !== null) {
            $compression = $importHandle->getCompression();
        }

        if (ImportSettings::$importFile === 'none' || $compression !== 'none' || ImportSettings::$charsetConversion) {
            // We handle only some kind of data!
            Current::$message = Message::error(
                __('This plugin does not support compressed imports!'),
            );
            Import::$hasError = true;

            return [];
        }

        $sql = 'LOAD DATA';
        if ($this->localOption) {
            $sql .= ' LOCAL';
        }

        $sql .= ' INFILE ' . $this->dbi->quoteString(ImportSettings::$importFile);
        if ($this->replace) {
            $sql .= ' REPLACE';
        } elseif ($this->ignore) {
            $sql .= ' IGNORE';
        }

        $sql .= ' INTO TABLE ' . Util::backquote(Current::$table);

        if ($this->terminated !== '') {
            $sql .= ' FIELDS TERMINATED BY \'' . $this->terminated . '\'';
        }

        if ($this->enclosed !== '') {
            $sql .= ' ENCLOSED BY ' . $this->dbi->quoteString($this->enclosed);
        }

        if ($this->escaped !== '') {
            $sql .= ' ESCAPED BY ' . $this->dbi->quoteString($this->escaped);
        }

        if ($this->newLine !== '') {
            if ($this->newLine === 'auto') {
                $this->newLine = PHP_EOL;
            }

            $sql .= ' LINES TERMINATED BY \'' . $this->newLine . '\'';
        }

        if (ImportSettings::$skipQueries > 0) {
            $sql .= ' IGNORE ' . ImportSettings::$skipQueries . ' LINES';
            ImportSettings::$skipQueries = 0;
        }

        if ($this->columns !== '') {
            $sql .= ' (';
            $tmp = preg_split('/,( ?)/', $this->columns);

            if (! is_array($tmp)) {
                $tmp = [];
            }

            foreach ($tmp as $i => $iValue) {
                if ($i > 0) {
                    $sql .= ', ';
                }

                /* Trim also `, if user already included backquoted fields */
                $sql .= Util::backquote(
                    trim($iValue, " \t\r\n\0\x0B`"),
                );
            }

            $sql .= ')';
        }

        $this->import->runQuery($sql, $sqlStatements);
        $this->import->runQuery('', $sqlStatements);
        ImportSettings::$finished = true;

        return $sqlStatements;
    }

    public static function isAvailable(): bool
    {
        // We need relations enabled and we work only on database.
        return ImportSettings::$importType === 'table';
    }

    private function setLdiLocalOptionConfig(): void
    {
        $this->config->settings['Import']['ldi_local_option'] = false;
        $result = $this->dbi->tryQuery('SELECT @@local_infile;');

        if ($result === false || $result->numRows() <= 0) {
            return;
        }

        $tmp = $result->fetchValue();
        if ($tmp !== 'ON' && $tmp !== '1') {
            return;
        }

        $this->config->settings['Import']['ldi_local_option'] = true;
    }
}
