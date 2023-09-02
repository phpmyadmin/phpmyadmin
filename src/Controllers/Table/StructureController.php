<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ColumnFull;
use PhpMyAdmin\Config;
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
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
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
        private PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
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

        $this->pageSettings->init('TableStructure');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['table/structure.js']);

        $relationParameters = $this->relation->getRelationParameters();

        if (! $this->checkParameters(['db', 'table'])) {
            return;
        }

        $isSystemSchema = Utilities::isSystemSchema($GLOBALS['db']);
        $urlParams = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
            Config::getInstance()->settings['DefaultTabTable'],
            'table',
        );
        $GLOBALS['errorUrl'] .= Url::getCommon($urlParams, '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->hasDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return;
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

            return;
        }

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
     * @param mixed[]      $columnsWithUniqueIndex Columns with unique index
     * @param ColumnFull[] $fields                 Fields
     * @param mixed[]      $columnsWithIndex       Columns with index
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

        $config = Config::getInstance();
        if ($config->settings['ShowPropertyComments']) {
            $commentsMap = $this->relation->getComments($GLOBALS['db'], $GLOBALS['table']);
            if ($relationParameters->browserTransformationFeature !== null && $config->settings['BrowseMIME']) {
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
        if ($config->settings['ShowStats']) {
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
            $columnsList[] = $field->field;

            $extractedColumnSpecs[$rownum] = Util::extractColumnSpec($field->type);
            $attributes[$rownum] = $extractedColumnSpecs[$rownum]['attribute'];
            if (str_contains($field->extra, 'on update CURRENT_TIMESTAMP')) {
                $attributes[$rownum] = 'on update CURRENT_TIMESTAMP';
            }

            $displayedFields[$rownum] = new stdClass();
            $displayedFields[$rownum]->text = $field->field;
            $displayedFields[$rownum]->icon = '';
            $rowComments[$rownum] = '';

            if (isset($commentsMap[$field->field])) {
                $displayedFields[$rownum]->comment = $commentsMap[$field->field];
                $rowComments[$rownum] = $commentsMap[$field->field];
            }

            if ($primaryIndex !== null && $primaryIndex->hasColumn($field->field)) {
                $displayedFields[$rownum]->icon .= Generator::getImage('b_primary', __('Primary'));
            }

            if (in_array($field->field, $columnsWithIndex)) {
                $displayedFields[$rownum]->icon .= Generator::getImage('bd_primary', __('Index'));
            }

            $collation = Charsets::findCollationByName(
                $this->dbi,
                $config->selectedServer['DisableIS'],
                $field->collation ?? '',
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
            'hide_structure_actions' => $config->settings['HideStructureActions'] === true,
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
            'browse_mime' => $config->settings['BrowseMIME'],
            'show_column_comments' => $config->settings['ShowColumnComments'],
            'show_stats' => $config->settings['ShowStats'],
            'mysql_int_version' => $this->dbi->getVersion(),
            'is_mariadb' => $this->dbi->isMariaDB(),
            'text_dir' => $GLOBALS['text_dir'],
            'is_active' => Tracker::isActive(),
            'have_partitioning' => Partition::havePartitioning(),
            'partitions' => Partition::getPartitions($GLOBALS['db'], $GLOBALS['table']),
            'partition_names' => Partition::getPartitionNames($GLOBALS['db'], $GLOBALS['table']),
            'default_sliders_state' => $config->settings['InitialSlidersState'],
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
            Config::getInstance()->selectedServer['DisableIS'],
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
            'avg_size' => $avgSize,
            'avg_unit' => $avgUnit,
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
