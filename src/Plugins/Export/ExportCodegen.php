<?php
/**
 * Set of functions used to build NHibernate dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\Export\Helpers\TableProperty;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function implode;
use function is_numeric;
use function preg_match;
use function preg_replace;
use function sprintf;
use function ucfirst;

/**
 * Handles the export for the CodeGen class
 */
class ExportCodegen extends ExportPlugin
{
    private const CODEGEN_FORMATS = [
        self::HANDLER_NHIBERNATE_CS => 'NHibernate C# DO',
        self::HANDLER_NHIBERNATE_XML => 'NHibernate XML',
    ];

    private const HANDLER_NHIBERNATE_CS = 0;
    private const HANDLER_NHIBERNATE_XML = 1;

    /** @var self::HANDLER_NHIBERNATE_* */
    private int $format = self::HANDLER_NHIBERNATE_CS;

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'codegen';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('CodeGen');
        $exportPluginProperties->setExtension('cs');
        $exportPluginProperties->setMimeType('text/cs');
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
        $leaf = new SelectPropertyItem(
            'format',
            __('Format:'),
        );
        $leaf->setValues(self::CODEGEN_FORMATS);
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
        if ($this->format === self::HANDLER_NHIBERNATE_XML) {
            return $this->export->outputHandler($this->handleNHibernateXMLBody($db, $table, $aliases));
        }

        return $this->export->outputHandler($this->handleNHibernateCSBody($db, $table, $aliases));
    }

    /**
     * Used to make identifiers (from table or database names)
     *
     * @param string $str     name to be converted
     * @param bool   $ucfirst whether to make the first character uppercase
     *
     * @return string identifier
     */
    public static function cgMakeIdentifier(string $str, bool $ucfirst = true): string
    {
        // remove unsafe characters
        $str = (string) preg_replace('/[^\p{L}\p{Nl}_]/u', '', $str);
        // make sure first character is a letter or _
        if (preg_match('/^\pL/u', $str) !== 1) {
            $str = '_' . $str;
        }

        if ($ucfirst) {
            return ucfirst($str);
        }

        return $str;
    }

    /**
     * C# Handler
     *
     * @param string  $db      database name
     * @param string  $table   table name
     * @param mixed[] $aliases Aliases of db/table/columns
     *
     * @return string containing C# code lines, separated by "\n"
     */
    private function handleNHibernateCSBody(string $db, string $table, array $aliases = []): string
    {
        $dbAlias = $this->getDbAlias($aliases, $db);
        $tableAlias = $this->getTableAlias($aliases, $db, $table);

        $result = DatabaseInterface::getInstance()->query(
            sprintf(
                'DESC %s.%s',
                Util::backquote($db),
                Util::backquote($table),
            ),
        );

        /** @var TableProperty[] $tableProperties */
        $tableProperties = [];
        while ($row = $result->fetchRow()) {
            $row[0] = $this->getColumnAlias($aliases, $db, $table, $row[0]);

            $tableProperties[] = new TableProperty($row);
        }

        unset($result);

        $lines = [];
        $lines[] = 'using System;';
        $lines[] = 'using System.Collections;';
        $lines[] = 'using System.Collections.Generic;';
        $lines[] = 'using System.Text;';
        $lines[] = 'namespace ' . self::cgMakeIdentifier($dbAlias);
        $lines[] = '{';
        $lines[] = '    #region '
            . self::cgMakeIdentifier($tableAlias);
        $lines[] = '    public class '
            . self::cgMakeIdentifier($tableAlias);
        $lines[] = '    {';
        $lines[] = '        #region Member Variables';
        foreach ($tableProperties as $tableProperty) {
            $lines[] = $tableProperty->formatCs('        protected #dotNetPrimitiveType# _#name#;');
        }

        $lines[] = '        #endregion';
        $lines[] = '        #region Constructors';
        $lines[] = '        public '
            . self::cgMakeIdentifier($tableAlias) . '() { }';
        $temp = [];
        foreach ($tableProperties as $tableProperty) {
            if ($tableProperty->isPK()) {
                continue;
            }

            $temp[] = $tableProperty->formatCs('#dotNetPrimitiveType# #name#');
        }

        $lines[] = '        public '
            . self::cgMakeIdentifier($tableAlias)
            . '('
            . implode(', ', $temp)
            . ')';
        $lines[] = '        {';
        foreach ($tableProperties as $tableProperty) {
            if ($tableProperty->isPK()) {
                continue;
            }

            $lines[] = $tableProperty->formatCs('            this._#name#=#name#;');
        }

        $lines[] = '        }';
        $lines[] = '        #endregion';
        $lines[] = '        #region Public Properties';
        foreach ($tableProperties as $tableProperty) {
            $lines[] = $tableProperty->formatCs(
                '        public virtual #dotNetPrimitiveType# #ucfirstName#'
                . "\n"
                . '        {' . "\n"
                . '            get {return _#name#;}' . "\n"
                . '            set {_#name#=value;}' . "\n"
                . '        }',
            );
        }

        $lines[] = '        #endregion';
        $lines[] = '    }';
        $lines[] = '    #endregion';
        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * XML Handler
     *
     * @param string  $db      database name
     * @param string  $table   table name
     * @param mixed[] $aliases Aliases of db/table/columns
     *
     * @return string containing XML code lines, separated by "\n"
     */
    private function handleNHibernateXMLBody(
        string $db,
        string $table,
        array $aliases = [],
    ): string {
        $dbAlias = $this->getDbAlias($aliases, $db);
        $tableAlias = $this->getTableAlias($aliases, $db, $table);
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="utf-8" ?>';
        $lines[] = '<hibernate-mapping xmlns="urn:nhibernate-mapping-2.2" '
            . 'namespace="' . self::cgMakeIdentifier($dbAlias) . '" '
            . 'assembly="' . self::cgMakeIdentifier($dbAlias) . '">';
        $lines[] = '    <class '
            . 'name="' . self::cgMakeIdentifier($tableAlias) . '" '
            . 'table="' . self::cgMakeIdentifier($tableAlias) . '">';
        $result = DatabaseInterface::getInstance()->query(
            sprintf(
                'DESC %s.%s',
                Util::backquote($db),
                Util::backquote($table),
            ),
        );

        while ($row = $result->fetchRow()) {
            $row[0] = $this->getColumnAlias($aliases, $db, $table, $row[0]);

            $tableProperty = new TableProperty($row);
            if ($tableProperty->isPK()) {
                $lines[] = $tableProperty->formatXml(
                    '        <id name="#ucfirstName#" type="#dotNetObjectType#"'
                    . ' unsaved-value="0">' . "\n"
                    . '            <column name="#name#" sql-type="#type#"'
                    . ' not-null="#notNull#" unique="#unique#"'
                    . ' index="PRIMARY"/>' . "\n"
                    . '            <generator class="native" />' . "\n"
                    . '        </id>',
                );
            } else {
                $lines[] = $tableProperty->formatXml(
                    '        <property name="#ucfirstName#"'
                    . ' type="#dotNetObjectType#">' . "\n"
                    . '            <column name="#name#" sql-type="#type#"'
                    . ' not-null="#notNull#" #indexName#/>' . "\n"
                    . '        </property>',
                );
            }
        }

        $lines[] = '    </class>';
        $lines[] = '</hibernate-mapping>';

        return implode("\n", $lines);
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('codegen_structure_or_data'),
            $exportConfig['codegen_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
        $this->format = $this->setFormat(
            $request->getParsedBodyParam('codegen_format'),
            $exportConfig['codegen_format'] ?? null,
        );
    }

    /** @return self::HANDLER_NHIBERNATE_* */
    private function setFormat(mixed $fromRequest, mixed $fromConfig): int
    {
        $value = self::HANDLER_NHIBERNATE_CS;
        if (is_numeric($fromRequest)) {
            $value = (int) $fromRequest;
        } elseif (is_numeric($fromConfig)) {
            $value = (int) $fromConfig;
        }

        return $value === self::HANDLER_NHIBERNATE_XML ? self::HANDLER_NHIBERNATE_XML : self::HANDLER_NHIBERNATE_CS;
    }
}
