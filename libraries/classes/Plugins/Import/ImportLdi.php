<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Message;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function count;
use function is_array;
use function preg_split;
use function strlen;
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

        if ($GLOBALS['cfg']['Import']['ldi_local_option'] === 'auto') {
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
        $GLOBALS['finished'] ??= null;
        $GLOBALS['import_file'] ??= null;
        $GLOBALS['charset_conversion'] ??= null;
        $GLOBALS['ldi_local_option'] ??= null;
        $GLOBALS['ldi_replace'] ??= null;
        $GLOBALS['ldi_ignore'] ??= null;
        $GLOBALS['ldi_terminated'] ??= null;
        $GLOBALS['ldi_enclosed'] ??= null;
        $GLOBALS['ldi_escaped'] ??= null;
        $GLOBALS['ldi_new_line'] ??= null;
        $GLOBALS['skip_queries'] ??= null;
        $GLOBALS['ldi_columns'] ??= null;

        $sqlStatements = [];
        $compression = '';
        if ($importHandle !== null) {
            $compression = $importHandle->getCompression();
        }

        if ($GLOBALS['import_file'] === 'none' || $compression !== 'none' || $GLOBALS['charset_conversion']) {
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

        $sql .= ' INFILE ' . $GLOBALS['dbi']->quoteString($GLOBALS['import_file']);
        if (isset($GLOBALS['ldi_replace'])) {
            $sql .= ' REPLACE';
        } elseif (isset($GLOBALS['ldi_ignore'])) {
            $sql .= ' IGNORE';
        }

        $sql .= ' INTO TABLE ' . Util::backquote($GLOBALS['table']);

        if (strlen((string) $GLOBALS['ldi_terminated']) > 0) {
            $sql .= ' FIELDS TERMINATED BY \'' . $GLOBALS['ldi_terminated'] . '\'';
        }

        if (strlen((string) $GLOBALS['ldi_enclosed']) > 0) {
            $sql .= ' ENCLOSED BY ' . $GLOBALS['dbi']->quoteString($GLOBALS['ldi_enclosed']);
        }

        if (strlen((string) $GLOBALS['ldi_escaped']) > 0) {
            $sql .= ' ESCAPED BY ' . $GLOBALS['dbi']->quoteString($GLOBALS['ldi_escaped']);
        }

        if (strlen((string) $GLOBALS['ldi_new_line']) > 0) {
            if ($GLOBALS['ldi_new_line'] === 'auto') {
                $GLOBALS['ldi_new_line'] = PHP_EOL;
            }

            $sql .= ' LINES TERMINATED BY \'' . $GLOBALS['ldi_new_line'] . '\'';
        }

        if ($GLOBALS['skip_queries'] > 0) {
            $sql .= ' IGNORE ' . $GLOBALS['skip_queries'] . ' LINES';
            $GLOBALS['skip_queries'] = 0;
        }

        if (strlen((string) $GLOBALS['ldi_columns']) > 0) {
            $sql .= ' (';
            $tmp = preg_split('/,( ?)/', $GLOBALS['ldi_columns']);

            if (! is_array($tmp)) {
                $tmp = [];
            }

            $cntTmp = count($tmp);
            for ($i = 0; $i < $cntTmp; $i++) {
                if ($i > 0) {
                    $sql .= ', ';
                }

                /* Trim also `, if user already included backquoted fields */
                $sql .= Util::backquote(
                    trim($tmp[$i], " \t\r\n\0\x0B`"),
                );
            }

            $sql .= ')';
        }

        $this->import->runQuery($sql, $sqlStatements);
        $this->import->runQuery('', $sqlStatements);
        $GLOBALS['finished'] = true;

        return $sqlStatements;
    }

    public static function isAvailable(): bool
    {
        // We need relations enabled and we work only on database.
        return isset($GLOBALS['plugin_param']) && $GLOBALS['plugin_param'] === 'table';
    }

    private function setLdiLocalOptionConfig(): void
    {
        $GLOBALS['cfg']['Import']['ldi_local_option'] = false;
        $result = $GLOBALS['dbi']->tryQuery('SELECT @@local_infile;');

        if ($result === false || $result->numRows() <= 0) {
            return;
        }

        $tmp = $result->fetchValue();
        if ($tmp !== 'ON' && $tmp !== '1') {
            return;
        }

        $GLOBALS['cfg']['Import']['ldi_local_option'] = true;
    }
}
