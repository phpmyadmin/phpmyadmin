<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Index;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;
use stdClass;

use function __;
use function in_array;
use function is_string;
use function str_contains;

/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns.
 */
class StructureController extends AbstractController
{
    /** @var Table  The table object */
    protected $tableObj;

    /** @var CreateAddField */
    private $createAddField;

    /** @var Relation */
    private $relation;

    /** @var Transformations */
    private $transformations;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var DatabaseInterface */
    private $dbi;

    /** @var FlashMessages */
    private $flash;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Relation $relation,
        Transformations $transformations,
        CreateAddField $createAddField,
        RelationCleanup $relationCleanup,
        DatabaseInterface $dbi,
        FlashMessages $flash
    ) {
        parent::__construct($response, $template);
        $this->createAddField = $createAddField;
        $this->relation = $relation;
        $this->transformations = $transformations;
        $this->relationCleanup = $relationCleanup;
        $this->dbi = $dbi;
        $this->flash = $flash;

        $this->tableObj = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['reread_info'] = $GLOBALS['reread_info'] ?? null;
        $GLOBALS['showtable'] = $GLOBALS['showtable'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['tbl_is_view'] = $GLOBALS['tbl_is_view'] ?? null;
        $GLOBALS['tbl_storage_engine'] = $GLOBALS['tbl_storage_engine'] ?? null;
        $GLOBALS['tbl_collation'] = $GLOBALS['tbl_collation'] ?? null;
        $GLOBALS['table_info_num_rows'] = $GLOBALS['table_info_num_rows'] ?? null;

        $this->dbi->selectDb($GLOBALS['db']);
        $GLOBALS['reread_info'] = $this->tableObj->getStatusInfo(null, true);
        $GLOBALS['showtable'] = $this->tableObj->getStatusInfo(
            null,
            (isset($GLOBALS['reread_info']) && $GLOBALS['reread_info'])
        );

        if ($this->tableObj->isView()) {
            $GLOBALS['tbl_is_view'] = true;
            $GLOBALS['tbl_storage_engine'] = __('View');
        } else {
            $GLOBALS['tbl_is_view'] = false;
            $GLOBALS['tbl_storage_engine'] = $this->tableObj->getStorageEngine();
        }

        $GLOBALS['tbl_collation'] = $this->tableObj->getCollation();
        $GLOBALS['table_info_num_rows'] = $this->tableObj->getNumRows();

        $pageSettings = new PageSettings('TableStructure');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['table/structure.js', 'indexes.js']);

        $relationParameters = $this->relation->getRelationParameters();

        $this->checkParameters(['db', 'table']);

        $isSystemSchema = Utilities::isSystemSchema($GLOBALS['db']);
        $url_params = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($url_params, '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        $primary = Index::getPrimary($GLOBALS['table'], $GLOBALS['db']);
        $columns_with_index = $this->dbi
            ->getTable($GLOBALS['db'], $GLOBALS['table'])
            ->getColumnsWithIndex(Index::UNIQUE | Index::INDEX | Index::SPATIAL | Index::FULLTEXT);
        $columns_with_unique_index = $this->dbi
            ->getTable($GLOBALS['db'], $GLOBALS['table'])
            ->getColumnsWithIndex(Index::UNIQUE);

        $fields = $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table'], true);

        $this->response->addHTML($this->displayStructure(
            $relationParameters,
            $columns_with_unique_index,
            $primary,
            $fields,
            $columns_with_index,
            $isSystemSchema,
            $request->getRoute()
        ));
    }

    /**
     * Displays the table structure ('show table' works correct since 3.23.03)
     *
     * @param array       $columns_with_unique_index Columns with unique index
     * @param Index|false $primary_index             primary index or false if no one exists
     * @param array       $fields                    Fields
     * @param array       $columns_with_index        Columns with index
     * @psalm-param non-empty-string $route
     *
     * @return string
     */
    protected function displayStructure(
        RelationParameters $relationParameters,
        array $columns_with_unique_index,
        $primary_index,
        array $fields,
        array $columns_with_index,
        bool $isSystemSchema,
        string $route
    ) {
        $GLOBALS['tbl_is_view'] = $GLOBALS['tbl_is_view'] ?? null;
        $GLOBALS['tbl_storage_engine'] = $GLOBALS['tbl_storage_engine'] ?? null;

        // prepare comments
        $comments_map = [];
        $mime_map = [];

        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            $comments_map = $this->relation->getComments($GLOBALS['db'], $GLOBALS['table']);
            if ($relationParameters->browserTransformationFeature !== null && $GLOBALS['cfg']['BrowseMIME']) {
                $mime_map = $this->transformations->getMime($GLOBALS['db'], $GLOBALS['table'], true);
            }
        }

        $centralColumns = new CentralColumns($this->dbi);
        $central_list = $centralColumns->getFromTable($GLOBALS['db'], $GLOBALS['table']);

        /**
         * Displays Space usage and row statistics
         */
        // BEGIN - Calc Table Space
        // Get valid statistics whatever is the table type
        if ($GLOBALS['cfg']['ShowStats']) {
            //get table stats in HTML format
            $tablestats = $this->getTableStats($isSystemSchema);
            //returning the response in JSON format to be used by Ajax
            $this->response->addJSON('tableStat', $tablestats);
        }

        // END - Calc Table Space

        // logic removed from Template
        $rownum = 0;
        $columns_list = [];
        $attributes = [];
        $displayed_fields = [];
        $row_comments = [];
        $extracted_columnspecs = [];
        $collations = [];
        foreach ($fields as &$field) {
            ++$rownum;
            $columns_list[] = $field['Field'];

            $extracted_columnspecs[$rownum] = Util::extractColumnSpec($field['Type']);
            $attributes[$rownum] = $extracted_columnspecs[$rownum]['attribute'];
            if (str_contains($field['Extra'], 'on update CURRENT_TIMESTAMP')) {
                $attributes[$rownum] = 'on update CURRENT_TIMESTAMP';
            }

            $displayed_fields[$rownum] = new stdClass();
            $displayed_fields[$rownum]->text = $field['Field'];
            $displayed_fields[$rownum]->icon = '';
            $row_comments[$rownum] = '';

            if (isset($comments_map[$field['Field']])) {
                $displayed_fields[$rownum]->comment = $comments_map[$field['Field']];
                $row_comments[$rownum] = $comments_map[$field['Field']];
            }

            if ($primary_index && $primary_index->hasColumn($field['Field'])) {
                $displayed_fields[$rownum]->icon .= Generator::getImage('b_primary', __('Primary'));
            }

            if (in_array($field['Field'], $columns_with_index)) {
                $displayed_fields[$rownum]->icon .= Generator::getImage('bd_primary', __('Index'));
            }

            $collation = Charsets::findCollationByName(
                $this->dbi,
                $GLOBALS['cfg']['Server']['DisableIS'],
                $field['Collation'] ?? ''
            );
            if ($collation === null) {
                continue;
            }

            $collations[$collation->getName()] = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        $engine = $this->tableObj->getStorageEngine();

        return $this->template->render('table/structure/display_structure', [
            'collations' => $collations,
            'is_foreign_key_supported' => ForeignKey::isSupported($engine),
            'indexes' => Index::getFromTable($GLOBALS['table'], $GLOBALS['db']),
            'indexes_duplicates' => Index::findDuplicates($GLOBALS['table'], $GLOBALS['db']),
            'relation_parameters' => $relationParameters,
            'hide_structure_actions' => $GLOBALS['cfg']['HideStructureActions'] === true,
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'db_is_system_schema' => $isSystemSchema,
            'tbl_is_view' => $GLOBALS['tbl_is_view'],
            'mime_map' => $mime_map,
            'tbl_storage_engine' => $GLOBALS['tbl_storage_engine'],
            'primary' => $primary_index,
            'columns_with_unique_index' => $columns_with_unique_index,
            'columns_list' => $columns_list,
            'table_stats' => $tablestats ?? null,
            'fields' => $fields,
            'extracted_columnspecs' => $extracted_columnspecs,
            'columns_with_index' => $columns_with_index,
            'central_list' => $central_list,
            'comments_map' => $comments_map,
            'browse_mime' => $GLOBALS['cfg']['BrowseMIME'],
            'show_column_comments' => $GLOBALS['cfg']['ShowColumnComments'],
            'show_stats' => $GLOBALS['cfg']['ShowStats'],
            'mysql_int_version' => $this->dbi->getVersion(),
            'is_mariadb' => $this->dbi->isMariaDB(),
            'text_dir' => $GLOBALS['text_dir'],
            'is_active' => Tracker::isActive(),
            'have_partitioning' => Partition::havePartitioning(),
            'partitions' => Partition::getPartitions($GLOBALS['db'], $GLOBALS['table']),
            'partition_names' => Partition::getPartitionNames($GLOBALS['db'], $GLOBALS['table']),
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
            'attributes' => $attributes,
            'displayed_fields' => $displayed_fields,
            'row_comments' => $row_comments,
            'route' => $route,
        ]);
    }

    /**
     * Get HTML snippet for display table statistics
     *
     * @return string
     */
    protected function getTableStats(bool $isSystemSchema)
    {
        $GLOBALS['tbl_is_view'] = $GLOBALS['tbl_is_view'] ?? null;
        $GLOBALS['tbl_storage_engine'] = $GLOBALS['tbl_storage_engine'] ?? null;
        $GLOBALS['table_info_num_rows'] = $GLOBALS['table_info_num_rows'] ?? null;
        $GLOBALS['tbl_collation'] = $GLOBALS['tbl_collation'] ?? null;

        if (empty($GLOBALS['showtable'])) {
            $GLOBALS['showtable'] = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table'])->getStatusInfo(null, true);
        }

        if (is_string($GLOBALS['showtable'])) {
            $GLOBALS['showtable'] = [];
        }

        if (empty($GLOBALS['showtable']['Data_length'])) {
            $GLOBALS['showtable']['Data_length'] = 0;
        }

        if (empty($GLOBALS['showtable']['Index_length'])) {
            $GLOBALS['showtable']['Index_length'] = 0;
        }

        $is_innodb = (isset($GLOBALS['showtable']['Type'])
            && $GLOBALS['showtable']['Type'] === 'InnoDB');

        $mergetable = $this->tableObj->isMerge();

        // this is to display for example 261.2 MiB instead of 268k KiB
        $max_digits = 3;
        $decimals = 1;
        [$data_size, $data_unit] = Util::formatByteDown($GLOBALS['showtable']['Data_length'], $max_digits, $decimals);
        if ($mergetable === false) {
            [$index_size, $index_unit] = Util::formatByteDown(
                $GLOBALS['showtable']['Index_length'],
                $max_digits,
                $decimals
            );
        }

        if (isset($GLOBALS['showtable']['Data_free'])) {
            [$free_size, $free_unit] = Util::formatByteDown($GLOBALS['showtable']['Data_free'], $max_digits, $decimals);
            [$effect_size, $effect_unit] = Util::formatByteDown(
                $GLOBALS['showtable']['Data_length']
                + $GLOBALS['showtable']['Index_length']
                - $GLOBALS['showtable']['Data_free'],
                $max_digits,
                $decimals
            );
        } else {
            [$effect_size, $effect_unit] = Util::formatByteDown(
                $GLOBALS['showtable']['Data_length']
                + $GLOBALS['showtable']['Index_length'],
                $max_digits,
                $decimals
            );
        }

        [$tot_size, $tot_unit] = Util::formatByteDown(
            $GLOBALS['showtable']['Data_length'] + $GLOBALS['showtable']['Index_length'],
            $max_digits,
            $decimals
        );

        $avg_size = '';
        $avg_unit = '';
        if ($GLOBALS['table_info_num_rows'] > 0) {
            [$avg_size, $avg_unit] = Util::formatByteDown(
                ($GLOBALS['showtable']['Data_length']
                + $GLOBALS['showtable']['Index_length'])
                / $GLOBALS['showtable']['Rows'],
                6,
                1
            );
        }

        /** @var Innodb $innodbEnginePlugin */
        $innodbEnginePlugin = StorageEngine::getEngine('Innodb');
        $innodb_file_per_table = $innodbEnginePlugin->supportsFilePerTable();

        $tableCollation = [];
        $collation = Charsets::findCollationByName(
            $this->dbi,
            $GLOBALS['cfg']['Server']['DisableIS'],
            $GLOBALS['tbl_collation']
        );
        if ($collation !== null) {
            $tableCollation = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
        }

        return $this->template->render('table/structure/display_table_stats', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'showtable' => $GLOBALS['showtable'],
            'table_info_num_rows' => $GLOBALS['table_info_num_rows'],
            'tbl_is_view' => $GLOBALS['tbl_is_view'],
            'db_is_system_schema' => $isSystemSchema,
            'tbl_storage_engine' => $GLOBALS['tbl_storage_engine'],
            'table_collation' => $tableCollation,
            'is_innodb' => $is_innodb,
            'mergetable' => $mergetable,
            'avg_size' => $avg_size ?? null,
            'avg_unit' => $avg_unit ?? null,
            'data_size' => $data_size,
            'data_unit' => $data_unit,
            'index_size' => $index_size ?? null,
            'index_unit' => $index_unit ?? null,
            'innodb_file_per_table' => $innodb_file_per_table,
            'free_size' => $free_size ?? null,
            'free_unit' => $free_unit ?? null,
            'effect_size' => $effect_size,
            'effect_unit' => $effect_unit,
            'tot_size' => $tot_size,
            'tot_unit' => $tot_unit,
        ]);
    }
}
