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
use function mb_strstr;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_replace;
use function strlen;
use function urldecode;

class OperationsController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Operations $operations,
        CheckUserPrivileges $checkUserPrivileges,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->operations = $operations;
        $this->checkUserPrivileges = $checkUserPrivileges;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['reread_info'] = $GLOBALS['reread_info'] ?? null;
        $GLOBALS['tbl_is_view'] = $GLOBALS['tbl_is_view'] ?? null;
        $GLOBALS['tbl_storage_engine'] = $GLOBALS['tbl_storage_engine'] ?? null;
        $GLOBALS['show_comment'] = $GLOBALS['show_comment'] ?? null;
        $GLOBALS['tbl_collation'] = $GLOBALS['tbl_collation'] ?? null;
        $GLOBALS['table_info_num_rows'] = $GLOBALS['table_info_num_rows'] ?? null;
        $GLOBALS['row_format'] = $GLOBALS['row_format'] ?? null;
        $GLOBALS['auto_increment'] = $GLOBALS['auto_increment'] ?? null;
        $GLOBALS['create_options'] = $GLOBALS['create_options'] ?? null;
        $GLOBALS['table_alters'] = $GLOBALS['table_alters'] ?? null;
        $GLOBALS['warning_messages'] = $GLOBALS['warning_messages'] ?? null;
        $GLOBALS['lowerCaseNames'] = $GLOBALS['lowerCaseNames'] ?? null;
        $GLOBALS['reload'] = $GLOBALS['reload'] ?? null;
        $GLOBALS['result'] = $GLOBALS['result'] ?? null;
        $GLOBALS['new_tbl_storage_engine'] = $GLOBALS['new_tbl_storage_engine'] ?? null;
        $GLOBALS['message_to_show'] = $GLOBALS['message_to_show'] ?? null;
        $GLOBALS['columns'] = $GLOBALS['columns'] ?? null;
        $GLOBALS['hideOrderTable'] = $GLOBALS['hideOrderTable'] ?? null;
        $GLOBALS['indexes'] = $GLOBALS['indexes'] ?? null;
        $GLOBALS['notNull'] = $GLOBALS['notNull'] ?? null;
        $GLOBALS['comment'] = $GLOBALS['comment'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        $this->checkUserPrivileges->getPrivileges();

        // lower_case_table_names=1 `DB` becomes `db`
        $GLOBALS['lowerCaseNames'] = $this->dbi->getLowerCaseNames() === '1';

        if ($GLOBALS['lowerCaseNames']) {
            $GLOBALS['table'] = mb_strtolower($GLOBALS['table']);
        }

        $pma_table = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);

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

        $GLOBALS['reread_info'] = $pma_table->getStatusInfo(null, false);
        $GLOBALS['showtable'] = $pma_table->getStatusInfo(
            null,
            (isset($GLOBALS['reread_info']) && $GLOBALS['reread_info'])
        );
        if ($pma_table->isView()) {
            $GLOBALS['tbl_is_view'] = true;
            $GLOBALS['tbl_storage_engine'] = __('View');
            $GLOBALS['show_comment'] = null;
        } else {
            $GLOBALS['tbl_is_view'] = false;
            $GLOBALS['tbl_storage_engine'] = $pma_table->getStorageEngine();
            $GLOBALS['show_comment'] = $pma_table->getComment();
        }

        $GLOBALS['tbl_collation'] = $pma_table->getCollation();
        $GLOBALS['table_info_num_rows'] = $pma_table->getNumRows();
        $GLOBALS['row_format'] = $pma_table->getRowFormat();
        $GLOBALS['auto_increment'] = $pma_table->getAutoIncrement();
        $GLOBALS['create_options'] = $pma_table->getCreateOptions();

        // set initial value of these variables, based on the current table engine
        if ($pma_table->isEngine('ARIA')) {
            // the value for transactional can be implicit
            // (no create option found, in this case it means 1)
            // or explicit (option found with a value of 0 or 1)
            // ($create_options['transactional'] may have been set by Table class,
            // from the $create_options)
            $GLOBALS['create_options']['transactional'] = ($GLOBALS['create_options']['transactional'] ?? '') == '0'
                ? '0'
                : '1';
            $GLOBALS['create_options']['page_checksum'] = $GLOBALS['create_options']['page_checksum'] ?? '';
        }

        $pma_table = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);
        $GLOBALS['reread_info'] = false;
        $GLOBALS['table_alters'] = [];

        /**
         * If the table has to be moved to some other database
         */
        if (isset($_POST['submit_move']) || isset($_POST['submit_copy'])) {
            $message = $this->operations->moveOrCopyTable($GLOBALS['db'], $GLOBALS['table']);

            if (! $this->response->isAjax()) {
                return;
            }

            $this->response->addJSON('message', $message);

            if ($message->isSuccess()) {
                if (isset($_POST['submit_move'], $_POST['target_db'])) {
                    $GLOBALS['db'] = $_POST['target_db'];// Used in Header::getJsParams()
                }

                $this->response->addJSON('db', $GLOBALS['db']);

                return;
            }

            $this->response->setRequestStatus(false);

            return;
        }

        /**
         * Updates table comment, type and options if required
         */
        if (isset($_POST['submitoptions'])) {
            $_message = '';
            $GLOBALS['warning_messages'] = [];

            if (isset($_POST['new_name'])) {
                // lower_case_table_names=1 `DB` becomes `db`
                if ($GLOBALS['lowerCaseNames']) {
                    $_POST['new_name'] = mb_strtolower($_POST['new_name']);
                }

                // Get original names before rename operation
                $oldTable = $pma_table->getName();
                $oldDb = $pma_table->getDbName();

                if ($pma_table->rename($_POST['new_name'])) {
                    if (isset($_POST['adjust_privileges']) && ! empty($_POST['adjust_privileges'])) {
                        $this->operations->adjustPrivilegesRenameOrMoveTable(
                            $oldDb,
                            $oldTable,
                            $_POST['db'],
                            $_POST['new_name']
                        );
                    }

                    // Reselect the original DB
                    $GLOBALS['db'] = $oldDb;
                    $this->dbi->selectDb($oldDb);
                    $_message .= $pma_table->getLastMessage();
                    $GLOBALS['result'] = true;
                    $GLOBALS['table'] = $pma_table->getName();
                    $GLOBALS['reread_info'] = true;
                    $GLOBALS['reload'] = true;
                } else {
                    $_message .= $pma_table->getLastError();
                    $GLOBALS['result'] = false;
                }
            }

            if (
                ! empty($_POST['new_tbl_storage_engine'])
                && mb_strtoupper($_POST['new_tbl_storage_engine']) !== $GLOBALS['tbl_storage_engine']
            ) {
                $GLOBALS['new_tbl_storage_engine'] = mb_strtoupper($_POST['new_tbl_storage_engine']);

                if ($pma_table->isEngine('ARIA')) {
                    $GLOBALS['create_options']['transactional'] = ($GLOBALS['create_options']['transactional'] ?? '')
                        == '0' ? '0' : '1';
                    $GLOBALS['create_options']['page_checksum'] = $GLOBALS['create_options']['page_checksum'] ?? '';
                }
            } else {
                $GLOBALS['new_tbl_storage_engine'] = '';
            }

            $GLOBALS['row_format'] = $GLOBALS['create_options']['row_format'] ?? $pma_table->getRowFormat();

            $GLOBALS['table_alters'] = $this->operations->getTableAltersArray(
                $pma_table,
                $GLOBALS['create_options']['pack_keys'],
                (empty($GLOBALS['create_options']['checksum']) ? '0' : '1'),
                ($GLOBALS['create_options']['page_checksum'] ?? ''),
                (empty($GLOBALS['create_options']['delay_key_write']) ? '0' : '1'),
                $GLOBALS['row_format'],
                $GLOBALS['new_tbl_storage_engine'],
                (isset($GLOBALS['create_options']['transactional'])
                    && $GLOBALS['create_options']['transactional'] == '0' ? '0' : '1'),
                $GLOBALS['tbl_collation']
            );

            if (count($GLOBALS['table_alters']) > 0) {
                $GLOBALS['sql_query'] = 'ALTER TABLE '
                    . Util::backquote($GLOBALS['table']);
                $GLOBALS['sql_query'] .= "\r\n" . implode("\r\n", $GLOBALS['table_alters']);
                $GLOBALS['sql_query'] .= ';';
                $GLOBALS['result'] = (bool) $this->dbi->query($GLOBALS['sql_query']);
                $GLOBALS['reread_info'] = true;
                unset($GLOBALS['table_alters']);
                $GLOBALS['warning_messages'] = $this->operations->getWarningMessagesArray();
            }

            if (! empty($_POST['tbl_collation']) && ! empty($_POST['change_all_collations'])) {
                $this->operations->changeAllColumnsCollation(
                    $GLOBALS['db'],
                    $GLOBALS['table'],
                    $_POST['tbl_collation']
                );
            }

            if (isset($_POST['tbl_collation']) && empty($_POST['tbl_collation'])) {
                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON(
                        'message',
                        Message::error(__('No collation provided.'))
                    );

                    return;
                }
            }
        }

        /**
         * Reordering the table has been requested by the user
         */
        if (isset($_POST['submitorderby']) && ! empty($_POST['order_field'])) {
            $GLOBALS['sql_query'] = QueryGenerator::getQueryForReorderingTable(
                $GLOBALS['table'],
                urldecode($_POST['order_field']),
                $_POST['order_order'] ?? null
            );
            $GLOBALS['result'] = $this->dbi->query($GLOBALS['sql_query']);
        }

        /**
         * A partition operation has been requested by the user
         */
        if (isset($_POST['submit_partition']) && ! empty($_POST['partition_operation'])) {
            $GLOBALS['sql_query'] = QueryGenerator::getQueryForPartitioningTable(
                $GLOBALS['table'],
                $_POST['partition_operation'],
                $_POST['partition_name']
            );
            $GLOBALS['result'] = $this->dbi->query($GLOBALS['sql_query']);
        }

        if ($GLOBALS['reread_info']) {
            // to avoid showing the old value (for example the AUTO_INCREMENT) after
            // a change, clear the cache
            $this->dbi->getCache()->clearTableCache();
            $this->dbi->selectDb($GLOBALS['db']);
            $GLOBALS['showtable'] = $pma_table->getStatusInfo(null, true);
            if ($pma_table->isView()) {
                $GLOBALS['tbl_is_view'] = true;
                $GLOBALS['tbl_storage_engine'] = __('View');
                $GLOBALS['show_comment'] = null;
            } else {
                $GLOBALS['tbl_is_view'] = false;
                $GLOBALS['tbl_storage_engine'] = $pma_table->getStorageEngine();
                $GLOBALS['show_comment'] = $pma_table->getComment();
            }

            $GLOBALS['tbl_collation'] = $pma_table->getCollation();
            $GLOBALS['table_info_num_rows'] = $pma_table->getNumRows();
            $GLOBALS['row_format'] = $pma_table->getRowFormat();
            $GLOBALS['auto_increment'] = $pma_table->getAutoIncrement();
            $GLOBALS['create_options'] = $pma_table->getCreateOptions();
        }

        unset($GLOBALS['reread_info']);

        if (isset($GLOBALS['result']) && empty($GLOBALS['message_to_show'])) {
            if (empty($_message)) {
                if (empty($GLOBALS['sql_query'])) {
                    $_message = Message::success(__('No change'));
                } else {
                    $_message = $GLOBALS['result']
                        ? Message::success()
                        : Message::error();
                }

                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus($_message->isSuccess());
                    $this->response->addJSON('message', $_message);
                    if (! empty($GLOBALS['sql_query'])) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $GLOBALS['sql_query'])
                        );
                    }

                    return;
                }
            } else {
                $_message = $GLOBALS['result']
                    ? Message::success($_message)
                    : Message::error($_message);
            }

            if (! empty($GLOBALS['warning_messages'])) {
                $_message = new Message();
                $_message->addMessagesString($GLOBALS['warning_messages']);
                $_message->isError(true);
                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $_message);
                    if (! empty($GLOBALS['sql_query'])) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $GLOBALS['sql_query'])
                        );
                    }

                    return;
                }

                unset($GLOBALS['warning_messages']);
            }

            if (empty($GLOBALS['sql_query'])) {
                $this->response->addHTML(
                    $_message->getDisplay()
                );
            } else {
                $this->response->addHTML(
                    Generator::getMessage($_message, $GLOBALS['sql_query'])
                );
            }

            unset($_message);
        }

        $GLOBALS['urlParams']['goto'] = $GLOBALS['urlParams']['back'] = Url::getFromRoute('/table/operations');

        $GLOBALS['columns'] = $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table']);

        $GLOBALS['hideOrderTable'] = false;
        // `ALTER TABLE ORDER BY` does not make sense for InnoDB tables that contain
        // a user-defined clustered index (PRIMARY KEY or NOT NULL UNIQUE index).
        // InnoDB always orders table rows according to such an index if one is present.
        if ($GLOBALS['tbl_storage_engine'] === 'INNODB') {
            $GLOBALS['indexes'] = Index::getFromTable($GLOBALS['table'], $GLOBALS['db']);
            foreach ($GLOBALS['indexes'] as $name => $idx) {
                if ($name === 'PRIMARY') {
                    $GLOBALS['hideOrderTable'] = true;
                    break;
                }

                if ($idx->getNonUnique()) {
                    continue;
                }

                $GLOBALS['notNull'] = true;
                foreach ($idx->getColumns() as $column) {
                    if ($column->getNull()) {
                        $GLOBALS['notNull'] = false;
                        break;
                    }
                }

                if ($GLOBALS['notNull']) {
                    $GLOBALS['hideOrderTable'] = true;
                    break;
                }
            }
        }

        $GLOBALS['comment'] = '';
        if (mb_strstr((string) $GLOBALS['show_comment'], '; InnoDB free') === false) {
            if (mb_strstr((string) $GLOBALS['show_comment'], 'InnoDB free') === false) {
                // only user entered comment
                $GLOBALS['comment'] = (string) $GLOBALS['show_comment'];
            } else {
                // here we have just InnoDB generated part
                $GLOBALS['comment'] = '';
            }
        } else {
            // remove InnoDB comment from end, just the minimal part (*? is non greedy)
            $GLOBALS['comment'] = preg_replace('@; InnoDB free:.*?$@', '', (string) $GLOBALS['show_comment']);
        }

        $storageEngines = StorageEngine::getArray();

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);

        $hasPackKeys = isset($GLOBALS['create_options']['pack_keys'])
            && $pma_table->isEngine(['MYISAM', 'ARIA', 'ISAM']);
        $hasChecksumAndDelayKeyWrite = $pma_table->isEngine(['MYISAM', 'ARIA']);
        $hasTransactionalAndPageChecksum = $pma_table->isEngine('ARIA');
        $hasAutoIncrement = strlen((string) $GLOBALS['auto_increment']) > 0
            && $pma_table->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB']);

        $possibleRowFormats = $this->operations->getPossibleRowFormat();

        $databaseList = [];
        if (count($GLOBALS['dblist']->databases) <= $GLOBALS['cfg']['MaxDbList']) {
            $databaseList = $GLOBALS['dblist']->databases->getList();
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
            $relationParameters->relationFeature !== null
        );

        $this->render('table/operations/index', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'url_params' => $GLOBALS['urlParams'],
            'columns' => $GLOBALS['columns'],
            'hide_order_table' => $GLOBALS['hideOrderTable'],
            'table_comment' => $GLOBALS['comment'],
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
            'pack_keys' => $GLOBALS['create_options']['pack_keys'] ?? '',
            'has_transactional_and_page_checksum' => $hasTransactionalAndPageChecksum,
            'has_checksum_and_delay_key_write' => $hasChecksumAndDelayKeyWrite,
            'delay_key_write' => empty($GLOBALS['create_options']['delay_key_write']) ? '0' : '1',
            'transactional' => ($GLOBALS['create_options']['transactional'] ?? '') == '0' ? '0' : '1',
            'page_checksum' => $GLOBALS['create_options']['page_checksum'] ?? '',
            'checksum' => empty($GLOBALS['create_options']['checksum']) ? '0' : '1',
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
