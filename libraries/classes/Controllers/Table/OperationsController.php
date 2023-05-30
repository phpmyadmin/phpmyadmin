<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function implode;
use function is_array;
use function is_string;
use function mb_strstr;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_replace;
use function strlen;
use function urldecode;

class OperationsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Operations $operations,
        private CheckUserPrivileges $checkUserPrivileges,
        private Relation $relation,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['reread_info'] ??= null;
        $GLOBALS['tbl_is_view'] ??= null;
        $GLOBALS['tbl_storage_engine'] ??= null;
        $GLOBALS['tbl_collation'] ??= null;
        $GLOBALS['table_info_num_rows'] ??= null;
        $GLOBALS['auto_increment'] ??= null;
        $GLOBALS['reload'] ??= null;
        $GLOBALS['result'] ??= null;
        $GLOBALS['message_to_show'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->checkUserPrivileges->getPrivileges();

        if ($this->dbi->getLowerCaseNames() === 1) {
            $GLOBALS['table'] = mb_strtolower($GLOBALS['table']);
        }

        $pmaTable = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);

        $this->addScriptFiles(['table/operations.js']);

        $this->checkParameters(['db', 'table']);

        $isSystemSchema = Utilities::isSystemSchema($GLOBALS['db']);
        $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        $GLOBALS['urlParams']['goto'] = $GLOBALS['urlParams']['back'] = Url::getFromRoute('/table/operations');

        $relationParameters = $this->relation->getRelationParameters();

        /**
         * Reselect current db (needed in some cases probably due to the calling of {@link Relation})
         */
        $this->dbi->selectDb($GLOBALS['db']);

        $GLOBALS['reread_info'] = $pmaTable->getStatusInfo(null, false);
        $GLOBALS['showtable'] = $pmaTable->getStatusInfo(
            null,
            (isset($GLOBALS['reread_info']) && $GLOBALS['reread_info']),
        );
        if ($pmaTable->isView()) {
            $GLOBALS['tbl_is_view'] = true;
            $GLOBALS['tbl_storage_engine'] = __('View');
            $showComment = '';
        } else {
            $GLOBALS['tbl_is_view'] = false;
            $GLOBALS['tbl_storage_engine'] = $pmaTable->getStorageEngine();
            $showComment = $pmaTable->getComment();
        }

        $GLOBALS['tbl_collation'] = $pmaTable->getCollation();
        $GLOBALS['table_info_num_rows'] = $pmaTable->getNumRows();
        $GLOBALS['auto_increment'] = $pmaTable->getAutoIncrement();
        $createOptions = $pmaTable->getCreateOptions();

        // set initial value of these variables, based on the current table engine
        if ($pmaTable->isEngine('ARIA')) {
            // the value for transactional can be implicit
            // (no create option found, in this case it means 1)
            // or explicit (option found with a value of 0 or 1)
            // ($createOptions['transactional'] may have been set by Table class,
            // from the $createOptions)
            $createOptions['transactional'] = ($createOptions['transactional'] ?? '') == '0'
                ? '0'
                : '1';
            $createOptions['page_checksum'] ??= '';
        }

        $pmaTable = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);
        $GLOBALS['reread_info'] = false;

        /**
         * If the table has to be moved to some other database
         */
        if ($request->hasBodyParam('submit_move') || $request->hasBodyParam('submit_copy')) {
            $message = $this->operations->moveOrCopyTable($GLOBALS['db'], $GLOBALS['table']);

            if (! $this->response->isAjax()) {
                return;
            }

            $this->response->addJSON('message', $message);

            if ($message->isSuccess()) {
                /** @var mixed $targetDbParam */
                $targetDbParam = $request->getParsedBodyParam('target_db');
                if ($request->hasBodyParam('submit_move') && is_string($targetDbParam)) {
                    $GLOBALS['db'] = $targetDbParam; // Used in Header::getJsParams()
                }

                $this->response->addJSON('db', $GLOBALS['db']);

                return;
            }

            $this->response->setRequestStatus(false);

            return;
        }

        $warningMessages = [];
        /**
         * Updates table comment, type and options if required
         */
        if ($request->hasBodyParam('submitoptions')) {
            $newMessage = '';

            /** @var mixed $newName */
            $newName = $request->getParsedBodyParam('new_name');
            if (is_string($newName)) {
                if ($this->dbi->getLowerCaseNames() === 1) {
                    $newName = mb_strtolower($newName);
                }

                // Get original names before rename operation
                $oldTable = $pmaTable->getName();
                $oldDb = $pmaTable->getDbName();

                if ($pmaTable->rename($newName)) {
                    if ($request->getParsedBodyParam('adjust_privileges')) {
                        /** @var mixed $dbParam */
                        $dbParam = $request->getParsedBodyParam('db');
                        $this->operations->adjustPrivilegesRenameOrMoveTable(
                            $oldDb,
                            $oldTable,
                            is_string($dbParam) ? $dbParam : '',
                            $newName,
                        );
                    }

                    // Reselect the original DB
                    $GLOBALS['db'] = $oldDb;
                    $this->dbi->selectDb($oldDb);
                    $newMessage .= $pmaTable->getLastMessage();
                    $GLOBALS['result'] = true;
                    $GLOBALS['table'] = $pmaTable->getName();
                    $GLOBALS['reread_info'] = true;
                    $GLOBALS['reload'] = true;
                } else {
                    $newMessage .= $pmaTable->getLastError();
                    $GLOBALS['result'] = false;
                }
            }

            /** @var mixed $newTableStorageEngine */
            $newTableStorageEngine = $request->getParsedBodyParam('new_tbl_storage_engine');
            $newTblStorageEngine = '';
            if (
                is_string($newTableStorageEngine) && $newTableStorageEngine !== ''
                && mb_strtoupper($newTableStorageEngine) !== $GLOBALS['tbl_storage_engine']
            ) {
                $newTblStorageEngine = mb_strtoupper($newTableStorageEngine);

                if ($pmaTable->isEngine('ARIA')) {
                    $createOptions['transactional'] = ($createOptions['transactional'] ?? '')
                        == '0' ? '0' : '1';
                    $createOptions['page_checksum'] ??= '';
                }
            }

            $tableAlters = $this->operations->getTableAltersArray(
                $pmaTable,
                $createOptions['pack_keys'],
                (empty($createOptions['checksum']) ? '0' : '1'),
                ($createOptions['page_checksum'] ?? ''),
                (empty($createOptions['delay_key_write']) ? '0' : '1'),
                $createOptions['row_format'] ?? $pmaTable->getRowFormat(),
                $newTblStorageEngine,
                (isset($createOptions['transactional'])
                    && $createOptions['transactional'] == '0' ? '0' : '1'),
                $GLOBALS['tbl_collation'],
            );

            if ($tableAlters !== []) {
                $GLOBALS['sql_query'] = 'ALTER TABLE '
                    . Util::backquote($GLOBALS['table']);
                $GLOBALS['sql_query'] .= "\r\n" . implode("\r\n", $tableAlters);
                $GLOBALS['sql_query'] .= ';';
                $GLOBALS['result'] = (bool) $this->dbi->query($GLOBALS['sql_query']);
                $GLOBALS['reread_info'] = true;
                $warningMessages = $this->operations->getWarningMessagesArray();
            }

            /** @var mixed $tableCollationParam */
            $tableCollationParam = $request->getParsedBodyParam('tbl_collation');
            if (
                is_string($tableCollationParam) && $tableCollationParam !== ''
                && $request->getParsedBodyParam('change_all_collations')
            ) {
                $this->operations->changeAllColumnsCollation($GLOBALS['db'], $GLOBALS['table'], $tableCollationParam);
            }

            if ($tableCollationParam !== null && (! is_string($tableCollationParam) || $tableCollationParam === '')) {
                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON(
                        'message',
                        Message::error(__('No collation provided.')),
                    );

                    return;
                }
            }
        }

        /** @var mixed $orderField */
        $orderField = $request->getParsedBodyParam('order_field');

        /**
         * Reordering the table has been requested by the user
         */
        if ($request->hasBodyParam('submitorderby') && is_string($orderField) && $orderField !== '') {
            /** @var mixed $orderOrder */
            $orderOrder = $request->getParsedBodyParam('order_order');
            $GLOBALS['sql_query'] = QueryGenerator::getQueryForReorderingTable(
                $GLOBALS['table'],
                urldecode($orderField),
                is_string($orderOrder) ? $orderOrder : '',
            );
            $GLOBALS['result'] = $this->dbi->query($GLOBALS['sql_query']);
        }

        /** @var mixed $partitionOperation */
        $partitionOperation = $request->getParsedBodyParam('partition_operation');

        /**
         * A partition operation has been requested by the user
         */
        if (
            $request->hasBodyParam('submit_partition') && is_string($partitionOperation) && $partitionOperation !== ''
        ) {
            /** @var mixed $partitionNames */
            $partitionNames = $request->getParsedBodyParam('partition_name');
            $GLOBALS['sql_query'] = QueryGenerator::getQueryForPartitioningTable(
                $GLOBALS['table'],
                $partitionOperation,
                is_array($partitionNames) ? $partitionNames : [],
            );
            $GLOBALS['result'] = $this->dbi->query($GLOBALS['sql_query']);
        }

        if ($GLOBALS['reread_info']) {
            // to avoid showing the old value (for example the AUTO_INCREMENT) after
            // a change, clear the cache
            $this->dbi->getCache()->clearTableCache();
            $this->dbi->selectDb($GLOBALS['db']);
            $GLOBALS['showtable'] = $pmaTable->getStatusInfo(null, true);
            if ($pmaTable->isView()) {
                $GLOBALS['tbl_is_view'] = true;
                $GLOBALS['tbl_storage_engine'] = __('View');
                $showComment = '';
            } else {
                $GLOBALS['tbl_is_view'] = false;
                $GLOBALS['tbl_storage_engine'] = $pmaTable->getStorageEngine();
                $showComment = $pmaTable->getComment();
            }

            $GLOBALS['tbl_collation'] = $pmaTable->getCollation();
            $GLOBALS['table_info_num_rows'] = $pmaTable->getNumRows();
            $GLOBALS['auto_increment'] = $pmaTable->getAutoIncrement();
            $createOptions = $pmaTable->getCreateOptions();
        }

        unset($GLOBALS['reread_info']);

        if (isset($GLOBALS['result']) && empty($GLOBALS['message_to_show'])) {
            if (empty($newMessage)) {
                if (empty($GLOBALS['sql_query'])) {
                    $newMessage = Message::success(__('No change'));
                } else {
                    $newMessage = $GLOBALS['result']
                        ? Message::success()
                        : Message::error();
                }

                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus($newMessage->isSuccess());
                    $this->response->addJSON('message', $newMessage);
                    if (! empty($GLOBALS['sql_query'])) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $GLOBALS['sql_query']),
                        );
                    }

                    return;
                }
            } else {
                $newMessage = $GLOBALS['result']
                    ? Message::success($newMessage)
                    : Message::error($newMessage);
            }

            if ($warningMessages !== []) {
                $newMessage = new Message();
                $newMessage->addMessagesString($warningMessages);
                $newMessage->isError(true);
                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $newMessage);
                    if (! empty($GLOBALS['sql_query'])) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $GLOBALS['sql_query']),
                        );
                    }

                    return;
                }
            }

            if (empty($GLOBALS['sql_query'])) {
                $this->response->addHTML(
                    $newMessage->getDisplay(),
                );
            } else {
                $this->response->addHTML(
                    Generator::getMessage($newMessage, $GLOBALS['sql_query']),
                );
            }

            unset($newMessage);
        }

        $GLOBALS['urlParams']['goto'] = $GLOBALS['urlParams']['back'] = Url::getFromRoute('/table/operations');

        $columns = $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table']);

        $hideOrderTable = false;
        // `ALTER TABLE ORDER BY` does not make sense for InnoDB tables that contain
        // a user-defined clustered index (PRIMARY KEY or NOT NULL UNIQUE index).
        // InnoDB always orders table rows according to such an index if one is present.
        if ($GLOBALS['tbl_storage_engine'] === 'INNODB') {
            $indexes = Index::getFromTable($this->dbi, $GLOBALS['table'], $GLOBALS['db']);
            foreach ($indexes as $name => $idx) {
                if ($name === 'PRIMARY') {
                    $hideOrderTable = true;
                    break;
                }

                if ($idx->getNonUnique()) {
                    continue;
                }

                $notNull = true;
                foreach ($idx->getColumns() as $column) {
                    if ($column->getNull()) {
                        $notNull = false;
                        break;
                    }
                }

                if ($notNull) {
                    $hideOrderTable = true;
                    break;
                }
            }
        }

        $comment = '';
        if (mb_strstr($showComment, '; InnoDB free') === false) {
            if (mb_strstr($showComment, 'InnoDB free') === false) {
                // only user entered comment
                $comment = $showComment;
            }
        } else {
            // remove InnoDB comment from end, just the minimal part (*? is non greedy)
            $comment = preg_replace('@; InnoDB free:.*?$@', '', $showComment);
        }

        $storageEngines = StorageEngine::getArray();

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);

        $hasPackKeys = isset($createOptions['pack_keys'])
            && $pmaTable->isEngine(['MYISAM', 'ARIA', 'ISAM']);
        $hasChecksumAndDelayKeyWrite = $pmaTable->isEngine(['MYISAM', 'ARIA']);
        $hasTransactionalAndPageChecksum = $pmaTable->isEngine('ARIA');
        $hasAutoIncrement = strlen($GLOBALS['auto_increment']) > 0
            && $pmaTable->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB']);

        $possibleRowFormats = $this->operations->getPossibleRowFormat();

        $databaseList = [];
        $listDatabase = $this->dbi->getDatabaseList();
        if (count($listDatabase) <= $GLOBALS['cfg']['MaxDbList']) {
            $databaseList = $listDatabase->getList();
        }

        $hasForeignKeys = ! empty($this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'foreign'));
        $hasPrivileges = $GLOBALS['table_priv'] && $GLOBALS['col_priv'] && $GLOBALS['is_reload_priv'];
        $switchToNew = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $partitions = [];
        $partitionsChoices = [];

        if (Partition::havePartitioning()) {
            $partitionNames = Partition::getPartitionNames($GLOBALS['db'], $GLOBALS['table']);
            if (isset($partitionNames[0])) {
                $partitions = $partitionNames;
                $partitionsChoices = $this->operations->getPartitionMaintenanceChoices();
            }
        }

        $foreigners = $this->operations->getForeignersForReferentialIntegrityCheck(
            $GLOBALS['urlParams'],
            $relationParameters->relationFeature !== null,
        );

        $this->render('table/operations/index', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'url_params' => $GLOBALS['urlParams'],
            'columns' => $columns,
            'hide_order_table' => $hideOrderTable,
            'table_comment' => $comment,
            'storage_engine' => $GLOBALS['tbl_storage_engine'],
            'storage_engines' => $storageEngines,
            'charsets' => $charsets,
            'collations' => $collations,
            'tbl_collation' => $GLOBALS['tbl_collation'],
            'row_formats' => $possibleRowFormats[$GLOBALS['tbl_storage_engine']] ?? [],
            'row_format_current' => $GLOBALS['showtable']['Row_format'],
            'has_auto_increment' => $hasAutoIncrement,
            'auto_increment' => $GLOBALS['auto_increment'],
            'has_pack_keys' => $hasPackKeys,
            'pack_keys' => $createOptions['pack_keys'] ?? '',
            'has_transactional_and_page_checksum' => $hasTransactionalAndPageChecksum,
            'has_checksum_and_delay_key_write' => $hasChecksumAndDelayKeyWrite,
            'delay_key_write' => empty($createOptions['delay_key_write']) ? '0' : '1',
            'transactional' => ($createOptions['transactional'] ?? '') == '0' ? '0' : '1',
            'page_checksum' => $createOptions['page_checksum'] ?? '',
            'checksum' => empty($createOptions['checksum']) ? '0' : '1',
            'database_list' => $databaseList,
            'has_foreign_keys' => $hasForeignKeys,
            'has_privileges' => $hasPrivileges,
            'switch_to_new' => $switchToNew,
            'is_system_schema' => $isSystemSchema,
            'is_view' => $GLOBALS['tbl_is_view'],
            'partitions' => $partitions,
            'partitions_choices' => $partitionsChoices,
            'foreigners' => $foreigners,
        ]);
    }
}
