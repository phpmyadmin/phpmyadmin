<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Index;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;
use stdClass;

use function __;
use function in_array;
use function is_string;
use function str_contains;
use function strtotime;

/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns.
 */
class StructureController extends AbstractController
{
    protected Table $tableObj;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Relation $relation,
        private Transformations $transformations,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);

        $this->tableObj = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['reread_info'] ??= null;
        $GLOBALS['showtable'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['tbl_is_view'] ??= null;
        $GLOBALS['tbl_storage_engine'] ??= null;
        $GLOBALS['tbl_collation'] ??= null;
        $GLOBALS['table_info_num_rows'] ??= null;

        $this->dbi->selectDb($GLOBALS['db']);
        $GLOBALS['reread_info'] = $this->tableObj->getStatusInfo(null, true);
        $GLOBALS['showtable'] = $this->tableObj->getStatusInfo(
            null,
            (isset($GLOBALS['reread_info']) && $GLOBALS['reread_info']),
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

        $this->addScriptFiles(['table/structure.js']);

        $relationParameters = $this->relation->getRelationParameters();

        $this->checkParameters(['db', 'table']);

        $isSystemSchema = Utilities::isSystemSchema($GLOBALS['db']);
        $urlParams = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($urlParams, '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        $primary = Index::getPrimary($this->dbi, $GLOBALS['table'], $GLOBALS['db']);
        $columnsWithIndex = $this->dbi
            ->getTable($GLOBALS['db'], $GLOBALS['table'])
            ->getColumnsWithIndex(Index::UNIQUE | Index::INDEX | Index::SPATIAL | Index::FULLTEXT);
        $columnsWithUniqueIndex = $this->dbi
            ->getTable($GLOBALS['db'], $GLOBALS['table'])
            ->getColumnsWithIndex(Index::UNIQUE);

        $fields = $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table'], true);

        $this->response->addHTML($this->displayStructure(
            $relationParameters,
            $columnsWithUniqueIndex,
            $primary,
            $fields,
            $columnsWithIndex,
            $isSystemSchema,
            $request->getRoute(),
        ));
    }

    /**
     * Displays the table structure ('show table' works correct since 3.23.03)
     *
     * @param mixed[] $columnsWithUniqueIndex Columns with unique index
     * @param mixed[] $fields                 Fields
     * @param mixed[] $columnsWithIndex       Columns with index
     * @psalm-param non-empty-string $route
     */
    protected function displayStructure(
        RelationParameters $relationParameters,
        array $columnsWithUniqueIndex,
        Index|null $primaryIndex,
        array $fields,
        array $columnsWithIndex,
        bool $isSystemSchema,
        string $route,
    ): string {
        $GLOBALS['tbl_is_view'] ??= null;
        $GLOBALS['tbl_storage_engine'] ??= null;

        // prepare comments
        $commentsMap = [];
        $mimeMap = [];

        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            $commentsMap = $this->relation->getComments($GLOBALS['db'], $GLOBALS['table']);
            if ($relationParameters->browserTransformationFeature !== null && $GLOBALS['cfg']['BrowseMIME']) {
                $mimeMap = $this->transformations->getMime($GLOBALS['db'], $GLOBALS['table'], true);
            }
        }

        $centralColumns = new CentralColumns($this->dbi);
        $centralList = $centralColumns->getFromTable($GLOBALS['db'], $GLOBALS['table']);

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
        $columnsList = [];
        $attributes = [];
        $displayedFields = [];
        $rowComments = [];
        $extractedColumnSpecs = [];
        $collations = [];
        foreach ($fields as $field) {
            ++$rownum;
            $columnsList[] = $field['Field'];

            $extractedColumnSpecs[$rownum] = Util::extractColumnSpec($field['Type']);
            $attributes[$rownum] = $extractedColumnSpecs[$rownum]['attribute'];
            if (str_contains($field['Extra'], 'on update CURRENT_TIMESTAMP')) {
                $attributes[$rownum] = 'on update CURRENT_TIMESTAMP';
            }

            $displayedFields[$rownum] = new stdClass();
            $displayedFields[$rownum]->text = $field['Field'];
            $displayedFields[$rownum]->icon = '';
            $rowComments[$rownum] = '';

            if (isset($commentsMap[$field['Field']])) {
                $displayedFields[$rownum]->comment = $commentsMap[$field['Field']];
                $rowComments[$rownum] = $commentsMap[$field['Field']];
            }

            if ($primaryIndex !== null && $primaryIndex->hasColumn($field['Field'])) {
                $displayedFields[$rownum]->icon .= Generator::getImage('b_primary', __('Primary'));
            }

            if (in_array($field['Field'], $columnsWithIndex)) {
                $displayedFields[$rownum]->icon .= Generator::getImage('bd_primary', __('Index'));
            }

            $collation = Charsets::findCollationByName(
                $this->dbi,
                $GLOBALS['cfg']['Server']['DisableIS'],
                $field['Collation'] ?? '',
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
            'indexes' => Index::getFromTable($this->dbi, $GLOBALS['table'], $GLOBALS['db']),
            'indexes_duplicates' => Index::findDuplicates($GLOBALS['table'], $GLOBALS['db']),
            'relation_parameters' => $relationParameters,
            'hide_structure_actions' => $GLOBALS['cfg']['HideStructureActions'] === true,
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'db_is_system_schema' => $isSystemSchema,
            'tbl_is_view' => $GLOBALS['tbl_is_view'],
            'mime_map' => $mimeMap,
            'tbl_storage_engine' => $GLOBALS['tbl_storage_engine'],
            'primary' => $primaryIndex,
            'columns_with_unique_index' => $columnsWithUniqueIndex,
            'columns_list' => $columnsList,
            'table_stats' => $tablestats ?? null,
            'fields' => $fields,
            'extracted_columnspecs' => $extractedColumnSpecs,
            'columns_with_index' => $columnsWithIndex,
            'central_list' => $centralList,
            'comments_map' => $commentsMap,
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
            'displayed_fields' => $displayedFields,
            'row_comments' => $rowComments,
            'route' => $route,
        ]);
    }

    /**
     * Get HTML snippet for display table statistics
     */
    protected function getTableStats(bool $isSystemSchema): string
    {
        $GLOBALS['tbl_is_view'] ??= null;
        $GLOBALS['tbl_storage_engine'] ??= null;
        $GLOBALS['table_info_num_rows'] ??= null;
        $GLOBALS['tbl_collation'] ??= null;

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

        $isInnoDB = (isset($GLOBALS['showtable']['Type'])
            && $GLOBALS['showtable']['Type'] === 'InnoDB');

        $mergetable = $this->tableObj->isMerge();

        // this is to display for example 261.2 MiB instead of 268k KiB
        $maxDigits = 3;
        $decimals = 1;
        [$dataSize, $dataUnit] = Util::formatByteDown($GLOBALS['showtable']['Data_length'], $maxDigits, $decimals);
        if ($mergetable === false) {
            [$indexSize, $indexUnit] = Util::formatByteDown(
                $GLOBALS['showtable']['Index_length'],
                $maxDigits,
                $decimals,
            );
        }

        if (isset($GLOBALS['showtable']['Data_free'])) {
            [$freeSize, $freeUnit] = Util::formatByteDown($GLOBALS['showtable']['Data_free'], $maxDigits, $decimals);
            [$effectSize, $effectUnit] = Util::formatByteDown(
                $GLOBALS['showtable']['Data_length']
                + $GLOBALS['showtable']['Index_length']
                - $GLOBALS['showtable']['Data_free'],
                $maxDigits,
                $decimals,
            );
        } else {
            [$effectSize, $effectUnit] = Util::formatByteDown(
                $GLOBALS['showtable']['Data_length']
                + $GLOBALS['showtable']['Index_length'],
                $maxDigits,
                $decimals,
            );
        }

        [$totSize, $totUnit] = Util::formatByteDown(
            $GLOBALS['showtable']['Data_length'] + $GLOBALS['showtable']['Index_length'],
            $maxDigits,
            $decimals,
        );

        $avgSize = '';
        $avgUnit = '';
        if ($GLOBALS['table_info_num_rows'] > 0) {
            [$avgSize, $avgUnit] = Util::formatByteDown(
                ($GLOBALS['showtable']['Data_length']
                + $GLOBALS['showtable']['Index_length'])
                / $GLOBALS['showtable']['Rows'],
                6,
                1,
            );
        }

        /** @var Innodb $innodbEnginePlugin */
        $innodbEnginePlugin = StorageEngine::getEngine('Innodb');
        $innodbFilePerTable = $innodbEnginePlugin->supportsFilePerTable();

        $tableCollation = [];
        $collation = Charsets::findCollationByName(
            $this->dbi,
            $GLOBALS['cfg']['Server']['DisableIS'],
            $GLOBALS['tbl_collation'],
        );
        if ($collation !== null) {
            $tableCollation = ['name' => $collation->getName(), 'description' => $collation->getDescription()];
        }

        if (isset($GLOBALS['showtable']['Create_time'])) {
            $GLOBALS['showtable']['Create_time'] = Util::localisedDate(strtotime($GLOBALS['showtable']['Create_time']));
        }

        if (isset($GLOBALS['showtable']['Update_time'])) {
            $GLOBALS['showtable']['Update_time'] = Util::localisedDate(strtotime($GLOBALS['showtable']['Update_time']));
        }

        if (isset($GLOBALS['showtable']['Check_time'])) {
            $GLOBALS['showtable']['Check_time'] = Util::localisedDate(strtotime($GLOBALS['showtable']['Check_time']));
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
            'is_innodb' => $isInnoDB,
            'mergetable' => $mergetable,
            'avg_size' => $avgSize ?? null,
            'avg_unit' => $avgUnit ?? null,
            'data_size' => $dataSize,
            'data_unit' => $dataUnit,
            'index_size' => $indexSize ?? null,
            'index_unit' => $indexUnit ?? null,
            'innodb_file_per_table' => $innodbFilePerTable,
            'free_size' => $freeSize ?? null,
            'free_unit' => $freeUnit ?? null,
            'effect_size' => $effectSize,
            'effect_unit' => $effectUnit,
            'tot_size' => $totSize,
            'tot_unit' => $totUnit,
        ]);
    }
}
