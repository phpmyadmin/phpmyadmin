<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Operations;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;

use function __;
use function count;
use function implode;
use function is_array;
use function is_string;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_replace;
use function str_contains;
use function urldecode;

final class TableController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Operations $operations,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
        private readonly Relation $relation,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['auto_increment'] ??= null;
        $GLOBALS['message_to_show'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        if ($this->dbi->getLowerCaseNames() === 1) {
            Current::$table = mb_strtolower(Current::$table);
        }

        $pmaTable = $this->dbi->getTable(Current::$database, Current::$table);

        $this->response->addScriptFiles(['table/operations.js']);

        if (! $this->response->checkParameters(['db', 'table'])) {
            return $this->response->response();
        }

        $isSystemSchema = Utilities::isSystemSchema(Current::$database);
        UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];
        $config = Config::getInstance();
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon(UrlParams::$params, '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return $this->response->response();
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return $this->response->response();
            }

            $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);

            return $this->response->response();
        }

        UrlParams::$params['goto'] = UrlParams::$params['back'] = Url::getFromRoute('/table/operations');

        $relationParameters = $this->relation->getRelationParameters();

        /**
         * Reselect current db (needed in some cases probably due to the calling of {@link Relation})
         */
        $this->dbi->selectDb(Current::$database);

        $rowFormat = $pmaTable->getStatusInfo('Row_format');
        if ($pmaTable->isView()) {
            $tableIsAView = true;
            $tableStorageEngine = __('View');
            $showComment = '';
        } else {
            $tableIsAView = false;
            $tableStorageEngine = $pmaTable->getStorageEngine();
            $showComment = $pmaTable->getComment();
        }

        $tableCollation = $pmaTable->getCollation();
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

        $pmaTable = $this->dbi->getTable(Current::$database, Current::$table);
        $rereadInfo = false;

        /**
         * If the table has to be moved to some other database
         */
        if ($request->hasBodyParam('submit_move') || $request->hasBodyParam('submit_copy')) {
            $message = $this->operations->moveOrCopyTable($userPrivileges, Current::$database, Current::$table);

            if (! $request->isAjax()) {
                return $this->response->response();
            }

            $this->response->addJSON('message', $message);

            if ($message->isSuccess()) {
                /** @var mixed $targetDbParam */
                $targetDbParam = $request->getParsedBodyParam('target_db');
                if ($request->hasBodyParam('submit_move') && is_string($targetDbParam)) {
                    Current::$database = $targetDbParam; // Used in Header::getJsParams()
                }

                $this->response->addJSON('db', Current::$database);

                return $this->response->response();
            }

            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $newMessage = '';
        $warningMessages = [];
        /**
         * Updates table comment, type and options if required
         */
        if ($request->hasBodyParam('submitoptions')) {
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
                            $userPrivileges,
                            $oldDb,
                            $oldTable,
                            is_string($dbParam) ? $dbParam : '',
                            $newName,
                        );
                    }

                    // Reselect the original DB
                    Current::$database = $oldDb;
                    $this->dbi->selectDb($oldDb);
                    $newMessage .= $pmaTable->getLastMessage();
                    $result = true;
                    Current::$table = $pmaTable->getName();
                    $rereadInfo = true;
                    $GLOBALS['reload'] = true;
                } else {
                    $newMessage .= $pmaTable->getLastError();
                    $result = false;
                }
            }

            /** @var mixed $newTableStorageEngine */
            $newTableStorageEngine = $request->getParsedBodyParam('new_tbl_storage_engine');
            $newTblStorageEngine = '';
            if (
                is_string($newTableStorageEngine) && $newTableStorageEngine !== ''
                && mb_strtoupper($newTableStorageEngine) !== $tableStorageEngine
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
                empty($createOptions['checksum']) ? '0' : '1',
                $createOptions['page_checksum'] ?? '',
                empty($createOptions['delay_key_write']) ? '0' : '1',
                $createOptions['row_format'] ?? $pmaTable->getRowFormat(),
                $newTblStorageEngine,
                isset($createOptions['transactional']) && $createOptions['transactional'] == '0' ? '0' : '1',
                $tableCollation,
                $tableStorageEngine,
            );

            if ($tableAlters !== []) {
                $GLOBALS['sql_query'] = 'ALTER TABLE '
                    . Util::backquote(Current::$table);
                $GLOBALS['sql_query'] .= "\r\n" . implode("\r\n", $tableAlters);
                $GLOBALS['sql_query'] .= ';';
                $this->dbi->query($GLOBALS['sql_query']);
                $result = true;
                $rereadInfo = true;
                $warningMessages = $this->operations->getWarningMessagesArray($newTableStorageEngine);
            }

            /** @var mixed $tableCollationParam */
            $tableCollationParam = $request->getParsedBodyParam('tbl_collation');
            if (
                is_string($tableCollationParam) && $tableCollationParam !== ''
                && $request->getParsedBodyParam('change_all_collations')
            ) {
                $this->operations->changeAllColumnsCollation(Current::$database, Current::$table, $tableCollationParam);
            }

            if ($tableCollationParam !== null && (! is_string($tableCollationParam) || $tableCollationParam === '')) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON(
                        'message',
                        Message::error(__('No collation provided.')),
                    );

                    return $this->response->response();
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
                Current::$table,
                urldecode($orderField),
                is_string($orderOrder) ? $orderOrder : '',
            );
            $this->dbi->query($GLOBALS['sql_query']);
            $result = true;
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
                Current::$table,
                $partitionOperation,
                is_array($partitionNames) ? $partitionNames : [],
            );
            $this->dbi->query($GLOBALS['sql_query']);
            $result = true;
        }

        if ($rereadInfo) {
            // to avoid showing the old value (for example the AUTO_INCREMENT) after
            // a change, clear the cache
            $this->dbi->getCache()->clearTableCache();
            $this->dbi->selectDb(Current::$database);
            $rowFormat = $pmaTable->getStatusInfo('Row_format');
            if ($pmaTable->isView()) {
                $tableIsAView = true;
                $tableStorageEngine = __('View');
                $showComment = '';
            } else {
                $tableIsAView = false;
                $tableStorageEngine = $pmaTable->getStorageEngine();
                $showComment = $pmaTable->getComment();
            }

            $tableCollation = $pmaTable->getCollation();
            $GLOBALS['auto_increment'] = $pmaTable->getAutoIncrement();
            $createOptions = $pmaTable->getCreateOptions();
        }

        if (isset($result) && empty($GLOBALS['message_to_show'])) {
            if ($newMessage === '') {
                if (empty($GLOBALS['sql_query'])) {
                    $newMessage = Message::success(__('No change'));
                } else {
                    $newMessage = $result
                        ? Message::success()
                        : Message::error();
                }

                if ($request->isAjax()) {
                    $this->response->setRequestStatus($newMessage->isSuccess());
                    $this->response->addJSON('message', $newMessage);
                    if (! empty($GLOBALS['sql_query'])) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $GLOBALS['sql_query']),
                        );
                    }

                    return $this->response->response();
                }
            } else {
                $newMessage = $result
                    ? Message::success($newMessage)
                    : Message::error($newMessage);
            }

            if ($warningMessages !== []) {
                $newMessage = new Message();
                $newMessage->addMessagesString($warningMessages);
                $newMessage->setType(MessageType::Error);
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $newMessage);
                    if (! empty($GLOBALS['sql_query'])) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $GLOBALS['sql_query']),
                        );
                    }

                    return $this->response->response();
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

        UrlParams::$params['goto'] = UrlParams::$params['back'] = Url::getFromRoute('/table/operations');

        $columns = $this->dbi->getColumns(Current::$database, Current::$table);

        $hideOrderTable = false;
        // `ALTER TABLE ORDER BY` does not make sense for InnoDB tables that contain
        // a user-defined clustered index (PRIMARY KEY or NOT NULL UNIQUE index).
        // InnoDB always orders table rows according to such an index if one is present.
        if ($tableStorageEngine === 'INNODB') {
            $indexes = Index::getFromTable($this->dbi, Current::$table, Current::$database);
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
                    if ($column->isNullable()) {
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
        if (! str_contains($showComment, '; InnoDB free')) {
            if (! str_contains($showComment, 'InnoDB free')) {
                // only user entered comment
                $comment = $showComment;
            }
        } else {
            // remove InnoDB comment from end, just the minimal part (*? is non greedy)
            $comment = preg_replace('@; InnoDB free:.*?$@', '', $showComment);
        }

        $storageEngines = StorageEngine::getArray();

        $charsets = Charsets::getCharsets($this->dbi, $config->selectedServer['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $config->selectedServer['DisableIS']);

        $hasPackKeys = isset($createOptions['pack_keys'])
            && $pmaTable->isEngine(['MYISAM', 'ARIA', 'ISAM']);
        $hasChecksumAndDelayKeyWrite = $pmaTable->isEngine(['MYISAM', 'ARIA']);
        $hasTransactionalAndPageChecksum = $pmaTable->isEngine('ARIA');
        $hasAutoIncrement = $GLOBALS['auto_increment'] != ''
            && $pmaTable->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB']);

        $possibleRowFormats = $this->operations->getPossibleRowFormat();

        $databaseList = [];
        $listDatabase = $this->dbi->getDatabaseList();
        if (count($listDatabase) <= $config->settings['MaxDbList']) {
            $databaseList = $listDatabase->getList();
        }

        $hasForeignKeys = $this->relation->getForeignKeysData(Current::$database, Current::$table) !== [];
        $hasPrivileges = $userPrivileges->table && $userPrivileges->column && $userPrivileges->isReload;
        $switchToNew = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $partitions = [];
        $partitionsChoices = [];

        if (Partition::havePartitioning()) {
            $partitionNames = Partition::getPartitionNames(Current::$database, Current::$table);
            if (isset($partitionNames[0])) {
                $partitions = $partitionNames;
                $partitionsChoices = $this->operations->getPartitionMaintenanceChoices();
            }
        }

        $foreigners = $this->operations->getForeignersForReferentialIntegrityCheck(
            UrlParams::$params,
            $relationParameters->relationFeature !== null,
        );

        $this->response->render('table/operations/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'url_params' => UrlParams::$params,
            'columns' => $columns,
            'hide_order_table' => $hideOrderTable,
            'table_comment' => $comment,
            'storage_engine' => $tableStorageEngine,
            'storage_engines' => $storageEngines,
            'charsets' => $charsets,
            'collations' => $collations,
            'tbl_collation' => $tableCollation,
            'row_formats' => $possibleRowFormats[$tableStorageEngine] ?? [],
            'row_format_current' => $rowFormat,
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
            'is_view' => $tableIsAView,
            'partitions' => $partitions,
            'partitions_choices' => $partitionsChoices,
            'foreigners' => $foreigners,
        ]);

        return $this->response->response();
    }
}
