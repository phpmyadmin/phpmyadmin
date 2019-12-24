<?php
/**
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Partition;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * @package PhpMyAdmin\Controllers\Table
 */
class OperationsController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /** @var Relation */
    private $relation;

    /**
     * @param Response            $response            A Response instance.
     * @param DatabaseInterface   $dbi                 A DatabaseInterface instance.
     * @param Template            $template            A Template instance.
     * @param string              $db                  Database name.
     * @param string              $table               Table name.
     * @param Operations          $operations          A Operations instance.
     * @param CheckUserPrivileges $checkUserPrivileges A CheckUserPrivileges instance.
     * @param Relation            $relation            A Relation instance.
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        Operations $operations,
        CheckUserPrivileges $checkUserPrivileges,
        Relation $relation
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);
        $this->operations = $operations;
        $this->checkUserPrivileges = $checkUserPrivileges;
        $this->relation = $relation;
    }

    /**
     * @return void
     */
    public function index(): void
    {
        global $url_query, $url_params, $reread_info, $tbl_is_view, $tbl_storage_engine;
        global $show_comment, $tbl_collation, $table_info_num_rows, $row_format, $auto_increment, $create_options;
        global $table_alters, $warning_messages, $lowerCaseNames, $db, $table, $reload, $result;
        global $new_tbl_storage_engine, $sql_query, $message_to_show, $columns, $hideOrderTable, $indexes;
        global $notNull, $comment, $db_is_system_schema, $truncate_table_url_params, $drop_table_url_params;
        global $this_sql_query;

        $this->checkUserPrivileges->getPrivileges();

        // lower_case_table_names=1 `DB` becomes `db`
        $lowerCaseNames = $this->dbi->getLowerCaseNames() === '1';

        if ($lowerCaseNames) {
            $table = mb_strtolower($table);
        }

        $pma_table = $this->dbi->getTable($db, $table);

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('table/operations.js');

        /**
         * Runs common work
         */
        require ROOT_PATH . 'libraries/tbl_common.inc.php';
        $url_params['goto'] = $url_params['back'] = Url::getFromRoute('/table/operations');
        $url_query .= Url::getCommon($url_params, '&');

        /**
         * Gets relation settings
         */
        $cfgRelation = $this->relation->getRelationsParam();

        // reselect current db (needed in some cases probably due to
        // the calling of PhpMyAdmin\Relation)
        $this->dbi->selectDb($db);

        $reread_info = $pma_table->getStatusInfo(null, false);
        $GLOBALS['showtable'] = $pma_table->getStatusInfo(null, (isset($reread_info) && $reread_info ? true : false));
        if ($pma_table->isView()) {
            $tbl_is_view = true;
            $tbl_storage_engine = __('View');
            $show_comment = null;
        } else {
            $tbl_is_view = false;
            $tbl_storage_engine = $pma_table->getStorageEngine();
            $show_comment = $pma_table->getComment();
        }
        $tbl_collation = $pma_table->getCollation();
        $table_info_num_rows = $pma_table->getNumRows();
        $row_format = $pma_table->getRowFormat();
        $auto_increment = $pma_table->getAutoIncrement();
        $create_options = $pma_table->getCreateOptions();

        // set initial value of these variables, based on the current table engine
        if ($pma_table->isEngine('ARIA')) {
            // the value for transactional can be implicit
            // (no create option found, in this case it means 1)
            // or explicit (option found with a value of 0 or 1)
            // ($create_options['transactional'] may have been set by Table class,
            // from the $create_options)
            $create_options['transactional'] = isset($create_options['transactional']) && $create_options['transactional'] == '0'
                ? '0'
                : '1';
            $create_options['page_checksum'] = $create_options['page_checksum'] ?? '';
        }

        $pma_table = $this->dbi->getTable(
            $db,
            $table
        );
        $reread_info = false;
        $table_alters = [];

        /**
         * If the table has to be moved to some other database
         */
        if (isset($_POST['submit_move']) || isset($_POST['submit_copy'])) {
            //$_message = '';
            $this->operations->moveOrCopyTable($db, $table);
            // This was ended in an Ajax call
            return;
        }
        /**
         * If the table has to be maintained
         */
        if (isset($_POST['table_maintenance'])) {
            include_once ROOT_PATH . 'libraries/entry_points/sql.php';
            unset($result);
        }
        /**
         * Updates table comment, type and options if required
         */
        if (isset($_POST['submitoptions'])) {
            $_message = '';
            $warning_messages = [];

            if (isset($_POST['new_name'])) {
                // lower_case_table_names=1 `DB` becomes `db`
                if ($lowerCaseNames) {
                    $_POST['new_name'] = mb_strtolower(
                        $_POST['new_name']
                    );
                }
                // Get original names before rename operation
                $oldTable = $pma_table->getName();
                $oldDb = $pma_table->getDbName();

                if ($pma_table->rename($_POST['new_name'])) {
                    if (isset($_POST['adjust_privileges'])
                        && ! empty($_POST['adjust_privileges'])
                    ) {
                        $this->operations->adjustPrivilegesRenameOrMoveTable(
                            $oldDb,
                            $oldTable,
                            $_POST['db'],
                            $_POST['new_name']
                        );
                    }

                    // Reselect the original DB
                    $db = $oldDb;
                    $this->dbi->selectDb($oldDb);
                    $_message .= $pma_table->getLastMessage();
                    $result = true;
                    $table = $pma_table->getName();
                    $reread_info = true;
                    $reload = true;
                } else {
                    $_message .= $pma_table->getLastError();
                    $result = false;
                }
            }

            if (! empty($_POST['new_tbl_storage_engine'])
                && mb_strtoupper($_POST['new_tbl_storage_engine']) !== $tbl_storage_engine
            ) {
                $new_tbl_storage_engine = mb_strtoupper($_POST['new_tbl_storage_engine']);

                if ($pma_table->isEngine('ARIA')) {
                    $create_options['transactional'] = isset($create_options['transactional']) && $create_options['transactional'] == '0'
                        ? '0'
                        : '1';
                    $create_options['page_checksum'] = $create_options['page_checksum'] ?? '';
                }
            } else {
                $new_tbl_storage_engine = '';
            }

            $row_format = $create_options['row_format'] ?? $pma_table->getRowFormat();

            $table_alters = $this->operations->getTableAltersArray(
                $pma_table,
                $create_options['pack_keys'],
                (empty($create_options['checksum']) ? '0' : '1'),
                ($create_options['page_checksum'] ?? ''),
                (empty($create_options['delay_key_write']) ? '0' : '1'),
                $row_format,
                $new_tbl_storage_engine,
                (isset($create_options['transactional']) && $create_options['transactional'] == '0' ? '0' : '1'),
                $tbl_collation
            );

            if (count($table_alters) > 0) {
                $sql_query      = 'ALTER TABLE '
                    . Util::backquote($table);
                $sql_query     .= "\r\n" . implode("\r\n", $table_alters);
                $sql_query     .= ';';
                $result         = $this->dbi->query($sql_query) ? true : false;
                $reread_info    = true;
                unset($table_alters);
                $warning_messages = $this->operations->getWarningMessagesArray();
            }

            if (isset($_POST['tbl_collation'], $_POST['change_all_collations'])
                && ! empty($_POST['tbl_collation'])
                && ! empty($_POST['change_all_collations'])
            ) {
                $this->operations->changeAllColumnsCollation(
                    $db,
                    $table,
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
            [$sql_query, $result] = $this->operations->getQueryAndResultForReorderingTable();
        } // end if

        /**
         * A partition operation has been requested by the user
         */
        if (isset($_POST['submit_partition'])
            && ! empty($_POST['partition_operation'])
        ) {
            [$sql_query, $result] = $this->operations->getQueryAndResultForPartition();
        } // end if

        if ($reread_info) {
            // to avoid showing the old value (for example the AUTO_INCREMENT) after
            // a change, clear the cache
            $this->dbi->clearTableCache();
            $this->dbi->selectDb($db);
            $GLOBALS['showtable'] = $pma_table->getStatusInfo(null, true);
            if ($pma_table->isView()) {
                $tbl_is_view = true;
                $tbl_storage_engine = __('View');
                $show_comment = null;
            } else {
                $tbl_is_view = false;
                $tbl_storage_engine = $pma_table->getStorageEngine();
                $show_comment = $pma_table->getComment();
            }
            $tbl_collation = $pma_table->getCollation();
            $table_info_num_rows = $pma_table->getNumRows();
            $row_format = $pma_table->getRowFormat();
            $auto_increment = $pma_table->getAutoIncrement();
            $create_options = $pma_table->getCreateOptions();
        }
        unset($reread_info);

        if (isset($result) && empty($message_to_show)) {
            if (empty($_message)) {
                if (empty($sql_query)) {
                    $_message = Message::success(__('No change'));
                } else {
                    $_message = $result
                        ? Message::success()
                        : Message::error();
                }

                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus($_message->isSuccess());
                    $this->response->addJSON('message', $_message);
                    if (! empty($sql_query)) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $sql_query)
                        );
                    }
                    return;
                }
            } else {
                $_message = $result
                    ? Message::success($_message)
                    : Message::error($_message);
            }

            if (! empty($warning_messages)) {
                $_message = new Message();
                $_message->addMessagesString($warning_messages);
                $_message->isError(true);
                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $_message);
                    if (! empty($sql_query)) {
                        $this->response->addJSON(
                            'sql_query',
                            Generator::getMessage('', $sql_query)
                        );
                    }
                    return;
                }
                unset($warning_messages);
            }

            if (empty($sql_query)) {
                $this->response->addHTML(
                    $_message->getDisplay()
                );
            } else {
                $this->response->addHTML(
                    Generator::getMessage($_message, $sql_query)
                );
            }
            unset($_message);
        }

        $url_params['goto'] = $url_params['back'] = Url::getFromRoute('/table/operations');

        /**
         * Get columns names
         */
        $columns = $this->dbi->getColumns($db, $table);

        /**
         * Displays the page
         */

        /**
         * Order the table
         */
        $hideOrderTable = false;
        // `ALTER TABLE ORDER BY` does not make sense for InnoDB tables that contain
        // a user-defined clustered index (PRIMARY KEY or NOT NULL UNIQUE index).
        // InnoDB always orders table rows according to such an index if one is present.
        if ($tbl_storage_engine == 'INNODB') {
            $indexes = Index::getFromTable($table, $db);
            foreach ($indexes as $name => $idx) {
                if ($name == 'PRIMARY') {
                    $hideOrderTable = true;
                    break;
                } elseif (! $idx->getNonUnique()) {
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
        }
        if (! $hideOrderTable) {
            $this->response->addHTML($this->operations->getHtmlForOrderTheTable($columns));
        }

        /**
         * Move table
         */
        $this->response->addHTML($this->operations->getHtmlForMoveTable());

        if (mb_strstr((string) $show_comment, '; InnoDB free') === false) {
            if (mb_strstr((string) $show_comment, 'InnoDB free') === false) {
                // only user entered comment
                $comment = (string) $show_comment;
            } else {
                // here we have just InnoDB generated part
                $comment = '';
            }
        } else {
            // remove InnoDB comment from end, just the minimal part (*? is non greedy)
            $comment = preg_replace('@; InnoDB free:.*?$@', '', (string) $show_comment);
        }

        // PACK_KEYS: MyISAM or ISAM
        // DELAY_KEY_WRITE, CHECKSUM, : MyISAM only
        // AUTO_INCREMENT: MyISAM and InnoDB since 5.0.3, PBXT

        // Here should be version check for InnoDB, however it is supported
        // in >5.0.4, >4.1.12 and >4.0.11, so I decided not to
        // check for version

        $this->response->addHTML(
            $this->operations->getTableOptionDiv(
                $pma_table,
                $comment ?? '',
                $tbl_collation,
                $tbl_storage_engine,
                $create_options['pack_keys'],
                (string) $auto_increment,
                (empty($create_options['delay_key_write']) ? '0' : '1'),
                (isset($create_options['transactional']) && $create_options['transactional'] == '0' ? '0' : '1'),
                ($create_options['page_checksum'] ?? ''),
                (empty($create_options['checksum']) ? '0' : '1')
            )
        );

        /**
         * Copy table
         */
        $this->response->addHTML($this->operations->getHtmlForCopytable());

        /**
         * Table maintenance
         */
        $this->response->addHTML(
            $this->operations->getHtmlForTableMaintenance($pma_table, $url_params)
        );

        if (! (isset($db_is_system_schema) && $db_is_system_schema)) {
            $truncate_table_url_params = [];
            $drop_table_url_params = [];

            if (! $tbl_is_view
                && ! (isset($db_is_system_schema) && $db_is_system_schema)
            ) {
                $this_sql_query = 'TRUNCATE TABLE '
                    . Util::backquote($table);
                $truncate_table_url_params = array_merge(
                    $url_params,
                    [
                        'sql_query' => $this_sql_query,
                        'goto' => Url::getFromRoute('/table/structure'),
                        'reload' => '1',
                        'message_to_show' => sprintf(
                            __('Table %s has been emptied.'),
                            htmlspecialchars($table)
                        ),
                    ]
                );
            }
            if (! (isset($db_is_system_schema) && $db_is_system_schema)) {
                $this_sql_query = 'DROP TABLE '
                    . Util::backquote($table);
                $drop_table_url_params = array_merge(
                    $url_params,
                    [
                        'sql_query' => $this_sql_query,
                        'goto' => Url::getFromRoute('/database/operations'),
                        'reload' => '1',
                        'purge' => '1',
                        'message_to_show' => sprintf(
                            ($tbl_is_view
                                ? __('View %s has been dropped.')
                                : __('Table %s has been dropped.')
                            ),
                            htmlspecialchars($table)
                        ),
                        // table name is needed to avoid running
                        // PhpMyAdmin\RelationCleanup::database() on the whole db later
                        'table' => $table,
                    ]
                );
            }
            $this->response->addHTML(
                $this->operations->getHtmlForDeleteDataOrTable(
                    $truncate_table_url_params,
                    $drop_table_url_params
                )
            );
        }

        if (Partition::havePartitioning()) {
            $partition_names = Partition::getPartitionNames($db, $table);
            // show the Partition maintenance section only if we detect a partition
            if ($partition_names[0] !== null) {
                $this->response->addHTML(
                    $this->operations->getHtmlForPartitionMaintenance($partition_names, $url_params)
                );
            }
        }

        // Referential integrity check
        if ($cfgRelation['relwork']) {
            $this->dbi->selectDb($db);
            $foreign = $this->relation->getForeigners($db, $table, '', 'internal');

            if (! empty($foreign)) {
                $this->response->addHTML(
                    $this->operations->getHtmlForReferentialIntegrityCheck($foreign, $url_params)
                );
            }
        }
    }
}
