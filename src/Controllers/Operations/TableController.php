<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Operations;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
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

#[Route('/table/operations', ['GET', 'POST'])]
final readonly class TableController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Operations $operations,
        private UserPrivilegesFactory $userPrivilegesFactory,
        private Relation $relation,
        private DatabaseInterface $dbi,
        private DbTableExists $dbTableExists,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        if ($this->dbi->getLowerCaseNames() === 1) {
            Current::$table = mb_strtolower(Current::$table);
        }

        $pmaTable = $this->dbi->getTable(Current::$database, Current::$table);

        $this->response->addScriptFiles(['table/operations.js']);

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        $isSystemSchema = Utilities::isSystemSchema(Current::$database);
        UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);
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
            $tableStorageEngine = mb_strtoupper($pmaTable->getStorageEngine());
            $showComment = $pmaTable->getComment();
        }

        $tableCollation = $pmaTable->getCollation();
        Operations::$autoIncrement = $pmaTable->getAutoIncrement();
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
                $targetDbParam = $request->getParsedBodyParamAsStringOrNull('target_db');
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
            $newName = $request->getParsedBodyParamAsStringOrNull('new_name');
            if (is_string($newName)) {
                if ($this->dbi->getLowerCaseNames() === 1) {
                    $newName = mb_strtolower($newName);
                }

                // Get original names before rename operation
                $oldTable = $pmaTable->getName();
                $oldDb = $pmaTable->getDbName();

                if ($pmaTable->rename($newName)) {
                    if ($request->getParsedBodyParam('adjust_privileges')) {
                        $dbParam = $request->getParsedBodyParamAsString('db', '');
                        $this->operations->adjustPrivilegesRenameOrMoveTable(
                            $userPrivileges,
                            $oldDb,
                            $oldTable,
                            $dbParam,
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
                    ResponseRenderer::$reload = true;
                } else {
                    $newMessage .= $pmaTable->getLastError();
                    $result = false;
                }
            }

            $newTableStorageEngine = mb_strtoupper($request->getParsedBodyParamAsString('new_tbl_storage_engine', ''));
            $newStorageEngine = '';
            if ($newTableStorageEngine !== '' && $newTableStorageEngine !== $tableStorageEngine) {
                $newStorageEngine = $newTableStorageEngine;
                if ($pmaTable->isEngine('ARIA')) {
                    $createOptions['transactional'] = ($createOptions['transactional'] ?? '') == '0' ? '0' : '1';
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
                $newStorageEngine,
                isset($createOptions['transactional']) && $createOptions['transactional'] == '0' ? '0' : '1',
                $tableCollation,
                $tableStorageEngine,
            );

            if ($tableAlters !== []) {
                Current::$sqlQuery = 'ALTER TABLE '
                    . Util::backquote(Current::$table);
                Current::$sqlQuery .= "\r\n" . implode("\r\n", $tableAlters);
                Current::$sqlQuery .= ';';
                $this->dbi->query(Current::$sqlQuery);
                $result = true;
                $rereadInfo = true;
                $warningMessages = $this->operations->getWarningMessagesArray($newTableStorageEngine);
            }

            $tableCollationParam = $request->getParsedBodyParamAsStringOrNull('tbl_collation');
            if (
                is_string($tableCollationParam) && $tableCollationParam !== ''
                && $request->getParsedBodyParam('change_all_collations')
            ) {
                $this->operations->changeAllColumnsCollation(Current::$database, Current::$table, $tableCollationParam);
            }

            if ($tableCollationParam === '' && $request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON(
                    'message',
                    Message::error(__('No collation provided.')),
                );

                return $this->response->response();
            }
        }

        $orderField = $request->getParsedBodyParamAsStringOrNull('order_field');

        /**
         * Reordering the table has been requested by the user
         */
        if ($request->hasBodyParam('submitorderby') && is_string($orderField) && $orderField !== '') {
            $orderOrder = $request->getParsedBodyParamAsString('order_order', '');
            Current::$sqlQuery = QueryGenerator::getQueryForReorderingTable(
                Current::$table,
                urldecode($orderField),
                $orderOrder,
            );
            $this->dbi->query(Current::$sqlQuery);
            $result = true;
        }

        $partitionOperation = $request->getParsedBodyParamAsStringOrNull('partition_operation');

        /**
         * A partition operation has been requested by the user
         */
        if (
            $request->hasBodyParam('submit_partition') && is_string($partitionOperation) && $partitionOperation !== ''
        ) {
            /** @var mixed $partitionNames */
            $partitionNames = $request->getParsedBodyParam('partition_name');
            Current::$sqlQuery = QueryGenerator::getQueryForPartitioningTable(
                Current::$table,
                $partitionOperation,
                is_array($partitionNames) ? $partitionNames : [],
            );
            $this->dbi->query(Current::$sqlQuery);
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
                $tableStorageEngine = mb_strtoupper($pmaTable->getStorageEngine());
                $showComment = $pmaTable->getComment();
            }

            $tableCollation = $pmaTable->getCollation();
            Operations::$autoIncrement = $pmaTable->getAutoIncrement();
            $createOptions = $pmaTable->getCreateOptions();
        }

        if (isset($result) && Current::$messageToShow === '') {
            if ($newMessage === '') {
                if (Current::$sqlQuery === '') {
                    $newMessage = Message::success(__('No change'));
                } else {
                    $newMessage = $result
                        ? Message::success()
                        : Message::error();
                }

                if ($request->isAjax()) {
                    $this->response->setRequestStatus($newMessage->isSuccess());
                    $this->response->addJSON('message', $newMessage);
                    if (Current::$sqlQuery !== '') {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', Current::$sqlQuery),
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
                    if (Current::$sqlQuery !== '') {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', Current::$sqlQuery),
                        );
                    }

                    return $this->response->response();
                }
            }

            if (Current::$sqlQuery === '') {
                $this->response->addHTML(
                    $newMessage->getDisplay(),
                );
            } else {
                $this->response->addHTML(
                    Generator::getMessage($newMessage, Current::$sqlQuery),
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

        $charsets = Charsets::getCharsets($this->dbi, $this->config->selectedServer['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $this->config->selectedServer['DisableIS']);

        $hasPackKeys = isset($createOptions['pack_keys'])
            && $pmaTable->isEngine(['MYISAM', 'ARIA', 'ISAM']);
        $hasChecksumAndDelayKeyWrite = $pmaTable->isEngine(['MYISAM', 'ARIA']);
        $hasTransactionalAndPageChecksum = $pmaTable->isEngine('ARIA');
        $hasAutoIncrement = Operations::$autoIncrement !== ''
            && $pmaTable->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB']);

        $possibleRowFormats = $this->operations->getPossibleRowFormat();

        $databaseList = [];
        $listDatabase = $this->dbi->getDatabaseList();
        if (count($listDatabase) <= $this->config->settings['MaxDbList']) {
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
            'auto_increment' => Operations::$autoIncrement,
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
