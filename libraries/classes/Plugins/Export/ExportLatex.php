<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of methods used to build dumps of tables as Latex
 *
 * @package    PhpMyAdmin-Export
 * @subpackage Latex
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

/**
 * Handles the export for the Latex format
 *
 * @package    PhpMyAdmin-Export
 * @subpackage Latex
 */
class ExportLatex extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        // initialize the specific export sql variables
        $this->initSpecificVariables();
        $this->setProperties();
    }

    /**
     * Initialize the local variables that are used for export Latex
     *
     * @return void
     */
    protected function initSpecificVariables()
    {
        /* Messages used in default captions */
        $GLOBALS['strLatexContent'] = __('Content of table @TABLE@');
        $GLOBALS['strLatexContinued'] = __('(continued)');
        $GLOBALS['strLatexStructure'] = __('Structure of table @TABLE@');
    }

    /**
     * Sets the export Latex properties
     *
     * @return void
     */
    protected function setProperties()
    {
        global $plugin_param;
        $hide_structure = false;
        if ($plugin_param['export_type'] == 'table'
            && ! $plugin_param['single_table']
        ) {
            $hide_structure = true;
        }

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('LaTeX');
        $exportPluginProperties->setExtension('tex');
        $exportPluginProperties->setMimeType('application/x-tex');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup(
            "Format Specific Options"
        );

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup("general_opts");
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            "caption",
            __('Include table caption')
        );
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // what to dump (structure/data/both) main group
        $dumpWhat = new OptionsPropertyMainGroup(
            "dump_what",
            __('Dump table')
        );
        // create primary items and add them to the group
        $leaf = new RadioPropertyItem("structure_or_data");
        $leaf->setValues(
            [
                'structure'          => __('structure'),
                'data'               => __('data'),
                'structure_and_data' => __('structure and data'),
            ]
        );
        $dumpWhat->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($dumpWhat);

        // structure options main group
        if (! $hide_structure) {
            $structureOptions = new OptionsPropertyMainGroup(
                "structure",
                __('Object creation options')
            );
            $structureOptions->setForce('data');
            // create primary items and add them to the group
            $leaf = new TextPropertyItem(
                "structure_caption",
                __('Table caption:')
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $leaf = new TextPropertyItem(
                "structure_continued_caption",
                __('Table caption (continued):')
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $leaf = new TextPropertyItem(
                "structure_label",
                __('Label key:')
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            if (! empty($GLOBALS['cfgRelation']['relation'])) {
                $leaf = new BoolPropertyItem(
                    "relation",
                    __('Display foreign key relationships')
                );
                $structureOptions->addProperty($leaf);
            }
            $leaf = new BoolPropertyItem(
                "comments",
                __('Display comments')
            );
            $structureOptions->addProperty($leaf);
            if (! empty($GLOBALS['cfgRelation']['mimework'])) {
                $leaf = new BoolPropertyItem(
                    "mime",
                    __('Display media (MIME) types')
                );
                $structureOptions->addProperty($leaf);
            }
            // add the main group to the root group
            $exportSpecificOptions->addProperty($structureOptions);
        }

        // data options main group
        $dataOptions = new OptionsPropertyMainGroup(
            "data",
            __('Data dump options')
        );
        $dataOptions->setForce('structure');
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            "columns",
            __('Put columns names in the first row:')
        );
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "data_caption",
            __('Table caption:')
        );
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "data_continued_caption",
            __('Table caption (continued):')
        );
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "data_label",
            __('Label key:')
        );
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'null',
            __('Replace NULL with:')
        );
        $dataOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($dataOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
    }

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader()
    {
        global $crlf;
        global $cfg;

        $head = '% phpMyAdmin LaTeX Dump' . $crlf
            . '% version ' . PMA_VERSION . $crlf
            . '% https://www.phpmyadmin.net/' . $crlf
            . '%' . $crlf
            . '% ' . __('Host:') . ' ' . $cfg['Server']['host'];
        if (! empty($cfg['Server']['port'])) {
            $head .= ':' . $cfg['Server']['port'];
        }
        $head .= $crlf
            . '% ' . __('Generation Time:') . ' '
            . Util::localisedDate() . $crlf
            . '% ' . __('Server version:') . ' ' . $GLOBALS['dbi']->getVersionString() . $crlf
            . '% ' . __('PHP Version:') . ' ' . PHP_VERSION . $crlf;

        return $this->export->outputHandler($head);
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter()
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db       Database name
     * @param string $db_alias Aliases of db
     *
     * @return bool Whether it succeeded
     */
    public function exportDBHeader($db, $db_alias = '')
    {
        if (empty($db_alias)) {
            $db_alias = $db;
        }
        global $crlf;
        $head = '% ' . $crlf
            . '% ' . __('Database:') . ' \'' . $db_alias . '\'' . $crlf
            . '% ' . $crlf;

        return $this->export->outputHandler($head);
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBFooter($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db          Database name
     * @param string $export_type 'server', 'database', 'table'
     * @param string $db_alias    Aliases of db
     *
     * @return bool Whether it succeeded
     */
    public function exportDBCreate($db, $export_type, $db_alias = '')
    {
        return true;
    }

    /**
     * Outputs the content of a table in JSON format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     * @param array  $aliases   Aliases of db/table/columns
     *
     * @return bool Whether it succeeded
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $error_url,
        $sql_query,
        array $aliases = []
    ) {
        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        $result = $GLOBALS['dbi']->tryQuery(
            $sql_query,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_UNBUFFERED
        );

        $columns_cnt = $GLOBALS['dbi']->numFields($result);
        $columns = [];
        $columns_alias = [];
        for ($i = 0; $i < $columns_cnt; $i++) {
            $columns[$i] = $col_as = $GLOBALS['dbi']->fieldName($result, $i);
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }
            $columns_alias[$i] = $col_as;
        }

        $buffer = $crlf . '%' . $crlf . '% ' . __('Data:') . ' ' . $table_alias
            . $crlf . '%' . $crlf . ' \\begin{longtable}{|';

        for ($index = 0; $index < $columns_cnt; $index++) {
            $buffer .= 'l|';
        }
        $buffer .= '} ' . $crlf;

        $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . $crlf;
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_data_caption'],
                    [
                        'texEscape',
                        static::class,
                    ],
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\label{'
                . Util::expandUserString(
                    $GLOBALS['latex_data_label'],
                    null,
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\\\';
        }
        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        // show column names
        if (isset($GLOBALS['latex_columns'])) {
            $buffer = '\\hline ';
            for ($i = 0; $i < $columns_cnt; $i++) {
                $buffer .= '\\multicolumn{1}{|c|}{\\textbf{'
                    . self::texEscape(stripslashes($columns_alias[$i])) . '}} & ';
            }

            $buffer = mb_substr($buffer, 0, -2) . '\\\\ \\hline \hline ';
            if (! $this->export->outputHandler($buffer . ' \\endfirsthead ' . $crlf)) {
                return false;
            }
            if (isset($GLOBALS['latex_caption'])) {
                if (! $this->export->outputHandler(
                    '\\caption{'
                    . Util::expandUserString(
                        $GLOBALS['latex_data_continued_caption'],
                        [
                            'texEscape',
                            static::class,
                        ],
                        [
                            'table' => $table_alias,
                            'database' => $db_alias,
                        ]
                    )
                    . '} \\\\ '
                )
                ) {
                    return false;
                }
            }
            if (! $this->export->outputHandler($buffer . '\\endhead \\endfoot' . $crlf)) {
                return false;
            }
        } else {
            if (! $this->export->outputHandler('\\\\ \hline')) {
                return false;
            }
        }

        // print the whole table
        while ($record = $GLOBALS['dbi']->fetchAssoc($result)) {
            $buffer = '';
            // print each row
            for ($i = 0; $i < $columns_cnt; $i++) {
                if ($record[$columns[$i]] !== null
                    && isset($record[$columns[$i]])
                ) {
                    $column_value = self::texEscape(
                        stripslashes($record[$columns[$i]])
                    );
                } else {
                    $column_value = $GLOBALS['latex_null'];
                }

                // last column ... no need for & character
                if ($i == ($columns_cnt - 1)) {
                    $buffer .= $column_value;
                } else {
                    $buffer .= $column_value . " & ";
                }
            }
            $buffer .= ' \\\\ \\hline ' . $crlf;
            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
        }

        $buffer = ' \\end{longtable}' . $crlf;
        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        $GLOBALS['dbi']->freeResult($result);

        return true;
    } // end getTableLaTeX

    /**
     * Outputs table's structure
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        the end of line sequence
     * @param string $error_url   the url to go back in case of error
     * @param string $export_mode 'create_table', 'triggers', 'create_view',
     *                            'stand_in'
     * @param string $export_type 'server', 'database', 'table'
     * @param bool   $do_relation whether to include relation comments
     * @param bool   $do_comments whether to include the pmadb-style column
     *                            comments as comments in the structure;
     *                            this is deprecated but the parameter is
     *                            left here because export.php calls
     *                            exportStructure() also for other
     *                            export types which use this parameter
     * @param bool   $do_mime     whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
     * @param array  $aliases     Aliases of db/table/columns
     *
     * @return bool Whether it succeeded
     */
    public function exportStructure(
        $db,
        $table,
        $crlf,
        $error_url,
        $export_mode,
        $export_type,
        $do_relation = false,
        $do_comments = false,
        $do_mime = false,
        $dates = false,
        array $aliases = []
    ) {
        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        global $cfgRelation;

        /* We do not export triggers */
        if ($export_mode == 'triggers') {
            return true;
        }

        /**
         * Get the unique keys in the table
         */
        $unique_keys = [];
        $keys = $GLOBALS['dbi']->getTableIndexes($db, $table);
        foreach ($keys as $key) {
            if ($key['Non_unique'] == 0) {
                $unique_keys[] = $key['Column_name'];
            }
        }

        /**
         * Gets fields properties
         */
        $GLOBALS['dbi']->selectDb($db);

        // Check if we can use Relations
        list($res_rel, $have_rel) = $this->relation->getRelationsAndStatus(
            $do_relation && ! empty($cfgRelation['relation']),
            $db,
            $table
        );
        /**
         * Displays the table structure
         */
        $buffer = $crlf . '%' . $crlf . '% ' . __('Structure:') . ' '
            . $table_alias . $crlf . '%' . $crlf . ' \\begin{longtable}{';
        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        $alignment = '|l|c|c|c|';
        if ($do_relation && $have_rel) {
            $alignment .= 'l|';
        }
        if ($do_comments) {
            $alignment .= 'l|';
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $alignment .= 'l|';
        }
        $buffer = $alignment . '} ' . $crlf;

        $header = ' \\hline ';
        $header .= '\\multicolumn{1}{|c|}{\\textbf{' . __('Column')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Type')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Null')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Default') . '}}';
        if ($do_relation && $have_rel) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Links to') . '}}';
        }
        if ($do_comments) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Comments') . '}}';
            $comments = $this->relation->getComments($db, $table);
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{MIME}}';
            $mime_map = $this->transformations->getMime($db, $table, true);
        }

        // Table caption for first page and label
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_caption'],
                    [
                        'texEscape',
                        static::class,
                    ],
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\label{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_label'],
                    null,
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\\\' . $crlf;
        }
        $buffer .= $header . ' \\\\ \\hline \\hline' . $crlf
            . '\\endfirsthead' . $crlf;
        // Table caption on next pages
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_continued_caption'],
                    [
                        'texEscape',
                        static::class,
                    ],
                    [
                        'table' => $table_alias,
                        'database' => $db_alias,
                    ]
                )
                . '} \\\\ ' . $crlf;
        }
        $buffer .= $header . ' \\\\ \\hline \\hline \\endhead \\endfoot ' . $crlf;

        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        $fields = $GLOBALS['dbi']->getColumns($db, $table);
        foreach ($fields as $row) {
            $extracted_columnspec = Util::extractColumnSpec($row['Type']);
            $type = $extracted_columnspec['print_type'];
            if (empty($type)) {
                $type = ' ';
            }

            if (! isset($row['Default'])) {
                if ($row['Null'] != 'NO') {
                    $row['Default'] = 'NULL';
                }
            }

            $field_name = $col_as = $row['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }

            $local_buffer = $col_as . "\000" . $type . "\000"
                . (($row['Null'] == '' || $row['Null'] == 'NO')
                    ? __('No') : __('Yes'))
                . "\000" . (isset($row['Default']) ? $row['Default'] : '');

            if ($do_relation && $have_rel) {
                $local_buffer .= "\000";
                $local_buffer .= $this->getRelationString(
                    $res_rel,
                    $field_name,
                    $db,
                    $aliases
                );
            }
            if ($do_comments && $cfgRelation['commwork']) {
                $local_buffer .= "\000";
                if (isset($comments[$field_name])) {
                    $local_buffer .= $comments[$field_name];
                }
            }
            if ($do_mime && $cfgRelation['mimework']) {
                $local_buffer .= "\000";
                if (isset($mime_map[$field_name])) {
                    $local_buffer .= str_replace(
                        '_',
                        '/',
                        $mime_map[$field_name]['mimetype']
                    );
                }
            }
            $local_buffer = self::texEscape($local_buffer);
            if ($row['Key'] == 'PRI') {
                $pos = mb_strpos($local_buffer, "\000");
                $local_buffer = '\\textit{'
                    .
                    mb_substr($local_buffer, 0, $pos)
                    . '}' .
                    mb_substr($local_buffer, $pos);
            }
            if (in_array($field_name, $unique_keys)) {
                $pos = mb_strpos($local_buffer, "\000");
                $local_buffer = '\\textbf{'
                    .
                    mb_substr($local_buffer, 0, $pos)
                    . '}' .
                    mb_substr($local_buffer, $pos);
            }
            $buffer = str_replace("\000", ' & ', $local_buffer);
            $buffer .= ' \\\\ \\hline ' . $crlf;

            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
        } // end while

        $buffer = ' \\end{longtable}' . $crlf;

        return $this->export->outputHandler($buffer);
    } // end of the 'exportStructure' method

    /**
     * Escapes some special characters for use in TeX/LaTeX
     *
     * @param string $string the string to convert
     *
     * @return string the converted string with escape codes
     */
    public static function texEscape($string)
    {
        $escape = [
            '$',
            '%',
            '{',
            '}',
            '&',
            '#',
            '_',
            '^',
        ];
        $cnt_escape = count($escape);
        for ($k = 0; $k < $cnt_escape; $k++) {
            $string = str_replace($escape[$k], '\\' . $escape[$k], $string);
        }

        return $string;
    }
}
