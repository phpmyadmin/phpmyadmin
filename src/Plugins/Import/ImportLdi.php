<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
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

        if (Config::getInstance()->settings['Import']['ldi_local_option'] === 'auto') {
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

    /**
     * Handles the whole import logic
     *
     * @return string[]
     */
    public function doImport(File|null $importHandle = null): array
    {
        $GLOBALS['ldi_local_option'] ??= null;
        $GLOBALS['ldi_replace'] ??= null;
        $GLOBALS['ldi_ignore'] ??= null;
        $GLOBALS['ldi_terminated'] ??= null;
        $GLOBALS['ldi_enclosed'] ??= null;
        $GLOBALS['ldi_escaped'] ??= null;
        $GLOBALS['ldi_new_line'] ??= null;
        $GLOBALS['ldi_columns'] ??= null;

        $sqlStatements = [];
        $compression = '';
        if ($importHandle !== null) {
            $compression = $importHandle->getCompression();
        }

        if (ImportSettings::$importFile === 'none' || $compression !== 'none' || ImportSettings::$charsetConversion) {
            // We handle only some kind of data!
            $GLOBALS['message'] = Message::error(
                __('This plugin does not support compressed imports!'),
            );
            $GLOBALS['error'] = true;

            return [];
        }

        $sql = 'LOAD DATA';
        if (isset($GLOBALS['ldi_local_option'])) {
            $sql .= ' LOCAL';
        }

        $dbi = DatabaseInterface::getInstance();
        $sql .= ' INFILE ' . $dbi->quoteString(ImportSettings::$importFile);
        if (isset($GLOBALS['ldi_replace'])) {
            $sql .= ' REPLACE';
        } elseif (isset($GLOBALS['ldi_ignore'])) {
            $sql .= ' IGNORE';
        }

        $sql .= ' INTO TABLE ' . Util::backquote(Current::$table);

        if ((string) $GLOBALS['ldi_terminated'] !== '') {
            $sql .= ' FIELDS TERMINATED BY \'' . $GLOBALS['ldi_terminated'] . '\'';
        }

        if ((string) $GLOBALS['ldi_enclosed'] !== '') {
            $sql .= ' ENCLOSED BY ' . $dbi->quoteString($GLOBALS['ldi_enclosed']);
        }

        if ((string) $GLOBALS['ldi_escaped'] !== '') {
            $sql .= ' ESCAPED BY ' . $dbi->quoteString($GLOBALS['ldi_escaped']);
        }

        if ((string) $GLOBALS['ldi_new_line'] !== '') {
            if ($GLOBALS['ldi_new_line'] === 'auto') {
                $GLOBALS['ldi_new_line'] = PHP_EOL;
            }

            $sql .= ' LINES TERMINATED BY \'' . $GLOBALS['ldi_new_line'] . '\'';
        }

        if (ImportSettings::$skipQueries > 0) {
            $sql .= ' IGNORE ' . ImportSettings::$skipQueries . ' LINES';
            ImportSettings::$skipQueries = 0;
        }

        if ((string) $GLOBALS['ldi_columns'] !== '') {
            $sql .= ' (';
            $tmp = preg_split('/,( ?)/', $GLOBALS['ldi_columns']);

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
        return isset($GLOBALS['plugin_param']) && $GLOBALS['plugin_param'] === 'table';
    }

    private function setLdiLocalOptionConfig(): void
    {
        $config = Config::getInstance();
        $config->settings['Import']['ldi_local_option'] = false;
        $result = DatabaseInterface::getInstance()->tryQuery('SELECT @@local_infile;');

        if ($result === false || $result->numRows() <= 0) {
            return;
        }

        $tmp = $result->fetchValue();
        if ($tmp !== 'ON' && $tmp !== '1') {
            return;
        }

        $config->settings['Import']['ldi_local_option'] = true;
    }
}
