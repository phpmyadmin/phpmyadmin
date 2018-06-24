<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PhpMyAdmin\Controllers\Server\ServerVariablesController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Common;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Handles viewing and editing server variables
 *
 * @package PhpMyAdmin\Controllers
 */
class ServerVariablesController extends Controller
{
    /**
     * @var array Documentation links for variables
     */
    protected $variable_doc_links;

    /**
     * Constructs ServerVariablesController
     *
     * @param \PhpMyAdmin\Response          $response Response object
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface object
     */
    public function __construct($response, $dbi)
    {
        parent::__construct($response, $dbi);

        $this->variable_doc_links = $this->_getDocumentLinks();
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        $response = Response::getInstance();
        if ($response->isAjax()
            && isset($_REQUEST['type'])
            && $_REQUEST['type'] === 'getval'
        ) {
            $this->getValueAction();
            return;
        }

        if ($response->isAjax()
            && isset($_REQUEST['type'])
            && $_REQUEST['type'] === 'setval'
        ) {
            $this->setValueAction();
            return;
        }

        include 'libraries/server_common.inc.php';

        $header   = $this->response->getHeader();
        $scripts  = $header->getScripts();
        $scripts->addFile('server_variables');

        /**
         * Displays the sub-page heading
         */
        $this->response->addHTML(
            Template::get('server/sub_page_header')->render([
                'type' => 'variables',
                'link' => 'server_system_variables',
            ])
        );

        /**
         * Sends the queries and buffers the results
         */
        $serverVarsResult = $this->dbi->tryQuery('SHOW SESSION VARIABLES;');

        if ($serverVarsResult !== false) {
            $serverVarsSession = [];
            while ($arr = $this->dbi->fetchRow($serverVarsResult)) {
                $serverVarsSession[$arr[0]] = $arr[1];
            }
            $this->dbi->freeResult($serverVarsResult);

            $serverVars = $this->dbi->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);

            /**
             * Link templates
            */
            $this->response->addHtml($this->_getHtmlForLinkTemplates());

            /**
             * Displays the page
            */
            $this->response->addHtml(
                $this->_getHtmlForServerVariables($serverVars, $serverVarsSession)
            );
        } else {
            /**
             * Display the error message
             */
            $this->response->addHTML(
                Message::error(
                    sprintf(
                        __(
                            'Not enough privilege to view server variables and '
                            . 'settings. %s'
                        ),
                        Util::showMySQLDocu(
                            'server-system-variables',
                            false,
                            'sysvar_show_compatibility_56'
                        )
                    )
                )->getDisplay()
            );
        }
    }

    /**
     * Handle the AJAX request for a single variable value
     *
     * @return void
     */
    public function getValueAction()
    {
        // Send with correct charset
        header('Content-Type: text/html; charset=UTF-8');
        // Do not use double quotes inside the query to avoid a problem
        // when server is running in ANSI_QUOTES sql_mode
        $varValue = $this->dbi->fetchSingleRow(
            'SHOW GLOBAL VARIABLES WHERE Variable_name=\''
            . $this->dbi->escapeString($_REQUEST['varName']) . '\';',
            'NUM'
        );

        if (isset($this->variable_doc_links[$_REQUEST['varName']][3])
            && $this->variable_doc_links[$_REQUEST['varName']][3] == 'byte'
        ) {
            $this->response->addJSON(
                'message',
                implode(
                    ' ',
                    Util::formatByteDown($varValue[1], 3, 3)
                )
            );
        } else {
            $this->response->addJSON(
                'message',
                $varValue[1]
            );
        }
    }

    /**
     * Handle the AJAX request for setting value for a single variable
     *
     * @return void
     */
    public function setValueAction()
    {
        $value = $_REQUEST['varValue'];
        $matches = [];

        if (isset($this->variable_doc_links[$_REQUEST['varName']][3])
            && $this->variable_doc_links[$_REQUEST['varName']][3] == 'byte'
            && preg_match(
                '/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i',
                $value,
                $matches
            )
        ) {
            $exp = [
                'kb' => 1,
                'kib' => 1,
                'mb' => 2,
                'mib' => 2,
                'gb' => 3,
                'gib' => 3
            ];
            $value = floatval($matches[1]) * pow(
                1024,
                $exp[mb_strtolower($matches[3])]
            );
        } else {
            $value = $this->dbi->escapeString($value);
        }

        if (! is_numeric($value)) {
            $value="'" . $value . "'";
        }

        if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName'])
            && $this->dbi->query(
                'SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value
            )
        ) {
            // Some values are rounded down etc.
            $varValue = $this->dbi->fetchSingleRow(
                'SHOW GLOBAL VARIABLES WHERE Variable_name="'
                . $this->dbi->escapeString($_REQUEST['varName'])
                . '";',
                'NUM'
            );
            list($formattedValue, $isHtmlFormatted) = $this->_formatVariable(
                $_REQUEST['varName'],
                $varValue[1]
            );

            if ($isHtmlFormatted == false) {
                $this->response->addJSON(
                    'variable',
                    htmlspecialchars(
                        $formattedValue
                    )
                );
            } else {
                $this->response->addJSON(
                    'variable',
                    $formattedValue
                );
            }
        } else {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'error',
                __('Setting variable failed')
            );
        }
    }

    /**
     * Format Variable
     *
     * @param string  $name  variable name
     * @param integer $value variable value
     *
     * @return array formatted string and bool if string is HTML formatted
     */
    private function _formatVariable($name, $value)
    {
        $isHtmlFormatted = false;
        $formattedValue = $value;

        if (is_numeric($value)) {
            if (isset($this->variable_doc_links[$name][3])
                && $this->variable_doc_links[$name][3] == 'byte'
            ) {
                $isHtmlFormatted = true;
                $formattedValue = '<abbr title="'
                    . htmlspecialchars(Util::formatNumber($value, 0)) . '">'
                    . htmlspecialchars(
                        implode(' ', Util::formatByteDown($value, 3, 3))
                    )
                    . '</abbr>';
            } else {
                $formattedValue = Util::formatNumber($value, 0);
            }
        }

        return [
            $formattedValue,
            $isHtmlFormatted
        ];
    }

    /**
     * Prints link templates
     *
     * @return string
     */
    private function _getHtmlForLinkTemplates()
    {
        $url = 'server_variables.php' . Url::getCommon();
        return Template::get('server/variables/link_template')
            ->render(['url' => $url]);
    }

    /**
     * Prints Html for Server Variables
     *
     * @param array $serverVars        global variables
     * @param array $serverVarsSession session variables
     *
     * @return string
     */
    private function _getHtmlForServerVariables(array $serverVars, array $serverVarsSession)
    {
        // filter
        $filterValue = ! empty($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
        $output = Template::get('filter')
            ->render(['filter_value' => $filterValue]);

        $output .= '<div class="responsivetable">';
        $output .= '<table id="serverVariables" class="width100 data filteredData noclick">';
        $output .= Template::get('server/variables/variable_table_head')->render();
        $output .= '<tbody>';

        $output .= $this->_getHtmlForServerVariablesItems(
            $serverVars,
            $serverVarsSession
        );

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';

        return $output;
    }


    /**
     * Prints Html for Server Variables Items
     *
     * @param array $serverVars        global variables
     * @param array $serverVarsSession session variables
     *
     * @return string
     */
    private function _getHtmlForServerVariablesItems(
        array $serverVars,
        array $serverVarsSession
    ) {
        // list of static (i.e. non-editable) system variables
        $static_variables = $this->_getStaticSystemVariables();

        $output = '';
        foreach ($serverVars as $name => $value) {
            $has_session_value = isset($serverVarsSession[$name])
                && $serverVarsSession[$name] != $value;
            $row_class = ($has_session_value ? ' diffSession' : '');
            $docLink = isset($this->variable_doc_links[$name])
                ? $this->variable_doc_links[$name] : null;

            list($formattedValue, $isHtmlFormatted) = $this->_formatVariable($name, $value);

            $output .= Template::get('server/variables/variable_row')->render([
                'row_class' => $row_class,
                'editable' => ! in_array(
                    strtolower($name),
                    $static_variables
                ),
                'doc_link' => $docLink,
                'name' => $name,
                'value' => $formattedValue,
                'is_superuser' => $this->dbi->isSuperuser(),
                'is_html_formatted' => $isHtmlFormatted,
            ]);

            if ($has_session_value) {
                list($formattedValue, $isHtmlFormatted)= $this->_formatVariable(
                    $name,
                    $serverVarsSession[$name]
                );
                $output .= Template::get('server/variables/session_variable_row')
                    ->render(
                        [
                            'row_class'         => $row_class,
                            'value'             => $formattedValue,
                            'is_html_formatted' => $isHtmlFormatted,
                        ]
                    );
            }
        }

        return $output;
    }

    /**
     * Returns Array of documentation links
     *
     * $variable_doc_links[string $name] = array(
     *    string $name,
     *    string $anchor,
     *    string $chapter,
     *    string $type,
     *    string $format);
     * string $name: name of the system variable
     * string $anchor: anchor to the documentation page
     * string $chapter: chapter of "HTML, one page per chapter" documentation
     * string $type: type of system variable
     * string $format: if set to 'byte' it will format the variable
     * with Util::formatByteDown()
     *
     * @return array
     */
    private function _getDocumentLinks()
    {
        $variable_doc_links = [];
        $variable_doc_links['auto_increment_increment'] = [
            'auto_increment_increment',
            'replication-options-master',
            'sysvar'];
        $variable_doc_links['auto_increment_offset'] = [
            'auto_increment_offset',
            'replication-options-master',
            'sysvar'];
        $variable_doc_links['autocommit'] = [
            'autocommit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['automatic_sp_privileges'] = [
            'automatic_sp_privileges',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['avoid_temporal_upgrade'] = [
            'avoid_temporal_upgrade',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['back_log'] = [
            'back_log',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['basedir'] = [
            'basedir',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['big_tables'] = [
            'big-tables',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['bind_address'] = [
            'bind-address',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['binlog_cache_size'] = [
            'binlog_cache_size',
            'replication-options-binary-log',
            'sysvar',
            'byte'];
        $variable_doc_links['binlog_checksum'] = [
            'binlog_checksum',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_direct_non_transactional_updates'] = [
            'binlog_direct_non_transactional_updates',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_error_action'] = [
            'binlog_error_action',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_format'] = [
            'binlog-format',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['binlog_group_commit_sync_delay'] = [
            'binlog_group_commit_sync_delay',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_group_commit_sync_no_delay_count'] = [
            'binlog_group_commit_sync_no_delay_count',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_gtid_simple_recovery'] = [
            'binlog_gtid_simple_recovery',
            'replication-options-gtids',
            'sysvar'];
        $variable_doc_links['binlog_max_flush_queue_time'] = [
            'binlog_max_flush_queue_time',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_order_commits'] = [
            'binlog_order_commits',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_row_image'] = [
            'binlog_row_image',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_rows_query_log_events'] = [
            'binlog_rows_query_log_events',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['binlog_stmt_cache_size'] = [
            'binlog_stmt_cache_size',
            'replication-options-binary-log',
            'sysvar',
            'byte'];
        $variable_doc_links['block_encryption_mode'] = [
            'block_encryption_mode',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['bulk_insert_buffer_size'] = [
            'bulk_insert_buffer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['character_set_client'] = [
            'character_set_client',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['character_set_connection'] = [
            'character_set_connection',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['character_set_database'] = [
            'character_set_database',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['character_set_filesystem'] = [
            'character-set-filesystem',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['character_set_results'] = [
            'character_set_results',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['character_set_server'] = [
            'character-set-server',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['character_set_system'] = [
            'character_set_system',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['character_sets_dir'] = [
            'character-sets-dir',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['check_proxy_users'] = [
            'check_proxy_users',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['collation_connection'] = [
            'collation_connection',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['collation_database'] = [
            'collation_database',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['collation_server'] = [
            'collation-server',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['completion_type'] = [
            'completion_type',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['concurrent_insert'] = [
            'concurrent_insert',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['connect_timeout'] = [
            'connect_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['core_file'] = [
            'core_file',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['datadir'] = [
            'datadir',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['date_format'] = [
            'date_format',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['datetime_format'] = [
            'datetime_format',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['debug'] = [
            'debug',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['debug_sync'] = [
            'debug_sync',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['default_authentication_plugin'] = [
            'default_authentication_plugin',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['default_password_lifetime'] = [
            'default_password_lifetime',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['default_storage_engine'] = [
            'default-storage-engine',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['default_tmp_storage_engine'] = [
            'default_tmp_storage_engine',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['default_week_format'] = [
            'default_week_format',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['delay_key_write'] = [
            'delay-key-write',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['delayed_insert_limit'] = [
            'delayed_insert_limit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['delayed_insert_timeout'] = [
            'delayed_insert_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['delayed_queue_size'] = [
            'delayed_queue_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['disabled_storage_engines'] = [
            'disabled_storage_engines',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['disconnect_on_expired_password'] = [
            'disconnect_on_expired_password',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['div_precision_increment'] = [
            'div_precision_increment',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['end_markers_in_json'] = [
            'end_markers_in_json',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['enforce_gtid_consistency'] = [
            'enforce_gtid_consistency',
            'replication-options-gtids',
            'sysvar'];
        $variable_doc_links['eq_range_index_dive_limit'] = [
            'eq_range_index_dive_limit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['engine_condition_pushdown'] = [
            'engine-condition-pushdown',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['error_count'] = [
            'error_count',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['event_scheduler'] = [
            'event-scheduler',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['expire_logs_days'] = [
            'expire_logs_days',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['explicit_defaults_for_timestamp'] = [
            'explicit_defaults_for_timestamp',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['external_user'] = [
            'external_user',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['flush'] = [
            'flush',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['flush_time'] = [
            'flush_time',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['foreign_key_checks'] = [
            'foreign_key_checks',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ft_boolean_syntax'] = [
            'ft_boolean_syntax',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ft_max_word_len'] = [
            'ft_max_word_len',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ft_min_word_len'] = [
            'ft_min_word_len',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ft_query_expansion_limit'] = [
            'ft_query_expansion_limit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ft_stopword_file'] = [
            'ft_stopword_file',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['general_log'] = [
            'general-log',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['general_log_file'] = [
            'general_log_file',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['group_concat_max_len'] = [
            'group_concat_max_len',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['gtid_executed'] = [
            'gtid_executed',
            'replication-options-gtids',
            'sysvar'];
        $variable_doc_links['gtid_executed_compression_period'] = [
            'gtid_executed_compression_period',
            'replication-options-gtids',
            'sysvar'];
        $variable_doc_links['gtid_mode'] = [
            'gtid_mode',
            'replication-options-gtids',
            'sysvar'];
        $variable_doc_links['gtid_owned'] = [
            'gtid_owned',
            'replication-options-gtids',
            'sysvar'];
        $variable_doc_links['gtid_purged'] = [
            'gtid_purged',
            'replication-options-gtids',
            'sysvar'];
        $variable_doc_links['have_compress'] = [
            'have_compress',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_crypt'] = [
            'have_crypt',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_csv'] = [
            'have_csv',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_dynamic_loading'] = [
            'have_dynamic_loading',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_geometry'] = [
            'have_geometry',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_innodb'] = [
            'have_innodb',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_ndbcluster'] = [
            'have_ndbcluster',
            'mysql-cluster-system-variables',
            'sysvar'];
        $variable_doc_links['have_openssl'] = [
            'have_openssl',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_partitioning'] = [
            'have_partitioning',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_profiling'] = [
            'have_profiling',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_query_cache'] = [
            'have_query_cache',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_rtree_keys'] = [
            'have_rtree_keys',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_ssl'] = [
            'have_ssl',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_statement_timeout'] = [
            'have_statement_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['have_symlink'] = [
            'have_symlink',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['host_cache_size'] = [
            'host_cache_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['hostname'] = [
            'hostname',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['identity'] = [
            'identity',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ignore_builtin_innodb'] = [
            'ignore-builtin-innodb',
            'innodb-parameters',
            'option_mysqld'];
        $variable_doc_links['ignore_db_dirs'] = [
            'ignore_db_dirs',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['init_connect'] = [
            'init_connect',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['init_file'] = [
            'init-file',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['init_slave'] = [
            'init_slave',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['innodb_adaptive_flushing'] = [
            'innodb_adaptive_flushing',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_adaptive_flushing_lwm'] = [
            'innodb_adaptive_flushing_lwm',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_adaptive_hash_index'] = [
            'innodb_adaptive_hash_index',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_adaptive_hash_index_parts'] = [
            'innodb_adaptive_hash_index_parts',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_adaptive_max_sleep_delay'] = [
            'innodb_adaptive_max_sleep_delay',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_additional_mem_pool_size'] = [
            'innodb_additional_mem_pool_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_api_bk_commit_interval'] = [
            'innodb_api_bk_commit_interval',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_api_disable_rowlock'] = [
            'innodb_api_disable_rowlock',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_api_enable_binlog'] = [
            'innodb_api_enable_binlog',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_api_enable_mdl'] = [
            'innodb_api_enable_mdl',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_api_trx_level'] = [
            'innodb_api_trx_level',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_autoextend_increment'] = [
            'innodb_autoextend_increment',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_autoinc_lock_mode'] = [
            'innodb_autoinc_lock_mode',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_chunk_size'] = [
            'innodb_buffer_pool_chunk_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_buffer_pool_dump_at_shutdown'] = [
            'innodb_buffer_pool_dump_at_shutdown',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_dump_now'] = [
            'innodb_buffer_pool_dump_now',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_dump_pct'] = [
            'innodb_buffer_pool_dump_pct',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_filename'] = [
            'innodb_buffer_pool_filename',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_instances'] = [
            'innodb_buffer_pool_instances',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_load_abort'] = [
            'innodb_buffer_pool_load_abort',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_load_at_startup'] = [
            'innodb_buffer_pool_load_at_startup',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_load_now'] = [
            'innodb_buffer_pool_load_now',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_buffer_pool_size'] = [
            'innodb_buffer_pool_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_change_buffer_max_size'] = [
            'innodb_change_buffer_max_size',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_change_buffering'] = [
            'innodb_change_buffering',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_checksum_algorithm'] = [
            'innodb_checksum_algorithm',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_checksums'] = [
            'innodb_checksums',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_cmp_per_index_enabled'] = [
            'innodb_cmp_per_index_enabled',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_commit_concurrency'] = [
            'innodb_commit_concurrency',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_compression_failure_threshold_pct'] = [
            'innodb_compression_failure_threshold_pct',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_compression_level'] = [
            'innodb_compression_level',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_compression_pad_pct_max'] = [
            'innodb_compression_pad_pct_max',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_concurrency_tickets'] = [
            'innodb_concurrency_tickets',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_data_file_path'] = [
            'innodb_data_file_path',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_data_home_dir'] = [
            'innodb_data_home_dir',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_disable_sort_file_cache'] = [
            'innodb_disable_sort_file_cache',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_doublewrite'] = [
            'innodb_doublewrite',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_fast_shutdown'] = [
            'innodb_fast_shutdown',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_file_format'] = [
            'innodb_file_format',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_file_format_check'] = [
            'innodb_file_format_check',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_file_format_max'] = [
            'innodb_file_format_max',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_file_per_table'] = [
            'innodb_file_per_table',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_fill_factor'] = [
            'innodb_fill_factor',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_flush_log_at_timeout'] = [
            'innodb_flush_log_at_timeout',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_flush_log_at_trx_commit'] = [
            'innodb_flush_log_at_trx_commit',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_flush_method'] = [
            'innodb_flush_method',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_flush_neighbors'] = [
            'innodb_flush_neighbors',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_flush_sync'] = [
            'innodb_flush_sync',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_flushing_avg_loops'] = [
            'innodb_flushing_avg_loops',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_force_load_corrupted'] = [
            'innodb_force_load_corrupted',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_force_recovery'] = [
            'innodb_force_recovery',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_aux_table'] = [
            'innodb_ft_aux_table',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_cache_size'] = [
            'innodb_ft_cache_size',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_enable_diag_print'] = [
            'innodb_ft_enable_diag_print',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_enable_stopword'] = [
            'innodb_ft_enable_stopword',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_max_token_size'] = [
            'innodb_ft_max_token_size',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_min_token_size'] = [
            'innodb_ft_min_token_size',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_num_word_optimize'] = [
            'innodb_ft_num_word_optimize',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_result_cache_limit'] = [
            'innodb_ft_result_cache_limit',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_server_stopword_table'] = [
            'innodb_ft_server_stopword_table',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_sort_pll_degree'] = [
            'innodb_ft_sort_pll_degree',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_total_cache_size'] = [
            'innodb_ft_total_cache_size',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_ft_user_stopword_table'] = [
            'innodb_ft_user_stopword_table',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_io_capacity'] = [
            'innodb_io_capacity',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_io_capacity_max'] = [
            'innodb_io_capacity_max',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_large_prefix'] = [
            'innodb_large_prefix',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_lock_wait_timeout'] = [
            'innodb_lock_wait_timeout',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_locks_unsafe_for_binlog'] = [
            'innodb_locks_unsafe_for_binlog',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_log_buffer_size'] = [
            'innodb_log_buffer_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_log_checksum_algorithm'] = [
            'innodb_log_checksum_algorithm',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_log_compressed_pages'] = [
            'innodb_log_compressed_pages',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_log_file_size'] = [
            'innodb_log_file_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_log_files_in_group'] = [
            'innodb_log_files_in_group',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_log_group_home_dir'] = [
            'innodb_log_group_home_dir',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_log_write_ahead_size'] = [
            'innodb_log_write_ahead_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_lru_scan_depth'] = [
            'innodb_lru_scan_depth',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_max_dirty_pages_pct'] = [
            'innodb_max_dirty_pages_pct',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_max_dirty_pages_pct_lwm'] = [
            'innodb_max_dirty_pages_pct_lwm',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_max_purge_lag'] = [
            'innodb_max_purge_lag',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_max_purge_lag_delay'] = [
            'innodb_max_purge_lag_delay',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_max_undo_log_size'] = [
            'innodb_max_undo_log_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_mirrored_log_groups'] = [
            'innodb_mirrored_log_groups',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_monitor_disable'] = [
            'innodb_monitor_disable',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_monitor_enable'] = [
            'innodb_monitor_enable',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_monitor_reset'] = [
            'innodb_monitor_reset',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_monitor_reset_all'] = [
            'innodb_monitor_reset_all',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_old_blocks_pct'] = [
            'innodb_old_blocks_pct',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_old_blocks_time'] = [
            'innodb_old_blocks_time',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_online_alter_log_max_size'] = [
            'innodb_online_alter_log_max_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_open_files'] = [
            'innodb_open_files',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_optimize_fulltext_only'] = [
            'innodb_optimize_fulltext_only',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_page_cleaners'] = [
            'innodb_page_cleaners',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_page_size'] = [
            'innodb_page_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_print_all_deadlocks'] = [
            'innodb_print_all_deadlocks',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_purge_batch_size'] = [
            'innodb_purge_batch_size',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_purge_rseg_truncate_frequency'] = [
            'innodb_purge_rseg_truncate_frequency',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_purge_threads'] = [
            'innodb_purge_threads',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_random_read_ahead'] = [
            'innodb_random_read_ahead',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_read_ahead_threshold'] = [
            'innodb_read_ahead_threshold',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_read_io_threads'] = [
            'innodb_read_io_threads',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_read_only'] = [
            'innodb_read_only',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_replication_delay'] = [
            'innodb_replication_delay',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_rollback_on_timeout'] = [
            'innodb_rollback_on_timeout',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_rollback_segments'] = [
            'innodb_rollback_segments',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_sort_buffer_size'] = [
            'innodb_sort_buffer_size',
            'innodb-parameters',
            'sysvar',
            'byte'];
        $variable_doc_links['innodb_spin_wait_delay'] = [
            'innodb_spin_wait_delay',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_stats_auto_recalc'] = [
            'innodb_stats_auto_recalc',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_stats_method'] = [
            'innodb_stats_method',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_stats_on_metadata'] = [
            'innodb_stats_on_metadata',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_stats_persistent'] = [
            'innodb_stats_persistent',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_stats_persistent_sample_pages'] = [
            'innodb_stats_persistent_sample_pages',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_stats_sample_pages'] = [
            'innodb_stats_sample_pages',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_stats_transient_sample_pages'] = [
            'innodb_stats_transient_sample_pages',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_status_output'] = [
            'innodb_status_output',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_status_output_locks'] = [
            'innodb_status_output_locks',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_strict_mode'] = [
            'innodb_strict_mode',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_support_xa'] = [
            'innodb_support_xa',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_sync_array_size'] = [
            'innodb_sync_array_size',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_sync_spin_loops'] = [
            'innodb_sync_spin_loops',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_table_locks'] = [
            'innodb_table_locks',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_temp_data_file_path'] = [
            'innodb_temp_data_file_path',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_thread_concurrency'] = [
            'innodb_thread_concurrency',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_thread_sleep_delay'] = [
            'innodb_thread_sleep_delay',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_undo_directory'] = [
            'innodb_undo_directory',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_undo_log_truncate'] = [
            'innodb_undo_log_truncate',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_undo_logs'] = [
            'innodb_undo_logs',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_undo_tablespaces'] = [
            'innodb_undo_tablespaces',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_use_native_aio'] = [
            'innodb_use_native_aio',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_use_sys_malloc'] = [
            'innodb_use_sys_malloc',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_version'] = [
            'innodb_version',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['innodb_write_io_threads'] = [
            'innodb_write_io_threads',
            'innodb-parameters',
            'sysvar'];
        $variable_doc_links['insert_id'] = [
            'insert_id',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['interactive_timeout'] = [
            'interactive_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['internal_tmp_disk_storage_engine'] = [
            'internal_tmp_disk_storage_engine',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['join_buffer_size'] = [
            'join_buffer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['keep_files_on_create'] = [
            'keep_files_on_create',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['key_buffer_size'] = [
            'key_buffer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['key_cache_age_threshold'] = [
            'key_cache_age_threshold',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['key_cache_block_size'] = [
            'key_cache_block_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['key_cache_division_limit'] = [
            'key_cache_division_limit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['language'] = [
            'language',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['large_files_support'] = [
            'large_files_support',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['large_page_size'] = [
            'large_page_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['large_pages'] = [
            'large-pages',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['last_insert_id'] = [
            'last_insert_id',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['lc_messages'] = [
            'lc-messages',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['lc_messages_dir'] = [
            'lc-messages-dir',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['lc_time_names'] = [
            'lc_time_names',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['license'] = [
            'license',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['local_infile'] = [
            'local_infile',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['lock_wait_timeout'] = [
            'lock_wait_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['locked_in_memory'] = [
            'locked_in_memory',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_backward_compatible_user_definitions'] = [
            'log_backward_compatible_user_definitions',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log'] = [
            'log',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['log_bin'] = [
            'log_bin',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['log-bin'] = [
            'log-bin',
            'replication-options-binary-log',
            'option_mysqld'];
        $variable_doc_links['log_bin_basename'] = [
            'log_bin_basename',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['log_bin_index'] = [
            'log_bin_index',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['log_bin_trust_function_creators'] = [
            'log-bin-trust-function-creators',
            'replication-options-binary-log',
            'option_mysqld'];
        $variable_doc_links['log_bin_use_v1_row_events'] = [
            'log_bin_use_v1_row_events',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['log_error'] = [
            'log-error',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['log_error_verbosity'] = [
            'log_error_verbosity',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_output'] = [
            'log-output',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['log_queries_not_using_indexes'] = [
            'log-queries-not-using-indexes',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['log_slave_updates'] = [
            'log-slave-updates',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['log_slow_admin_statements'] = [
            'log_slow_admin_statements',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_slow_slave_statements'] = [
            'log_slow_slave_statements',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['log_syslog'] = [
            'log_syslog',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_syslog_facility'] = [
            'log_syslog_facility',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_syslog_include_pid'] = [
            'log_syslog_include_pid',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_syslog_tag'] = [
            'log_syslog_tag',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_throttle_queries_not_using_indexes'] = [
            'log_throttle_queries_not_using_indexes',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_timestamps'] = [
            'log_timestamps',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['log_slow_queries'] = [
            'log-slow-queries',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['log_warnings'] = [
            'log-warnings',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['long_query_time'] = [
            'long_query_time',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['low_priority_updates'] = [
            'low-priority-updates',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['lower_case_file_system'] = [
            'lower_case_file_system',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['lower_case_table_names'] = [
            'lower_case_table_names',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['master_info_repository'] = [
            'master_info_repository',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['master_verify_checksum'] = [
            'master_verify_checksum',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['master-bind'] = [
            '',
            'replication-options',
            0];
        $variable_doc_links['max_allowed_packet'] = [
            'max_allowed_packet',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_binlog_cache_size'] = [
            'max_binlog_cache_size',
            'replication-options-binary-log',
            'sysvar',
            'byte'];
        $variable_doc_links['max_binlog_size'] = [
            'max_binlog_size',
            'replication-options-binary-log',
            'sysvar',
            'byte'];
        $variable_doc_links['max_binlog_stmt_cache_size'] = [
            'max_binlog_stmt_cache_size',
            'replication-options-binary-log',
            'sysvar',
            'byte'];
        $variable_doc_links['max_connect_errors'] = [
            'max_connect_errors',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_connections'] = [
            'max_connections',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_delayed_threads'] = [
            'max_delayed_threads',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_digest_length'] = [
            'max_digest_length',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_error_count'] = [
            'max_error_count',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_execution_time'] = [
            'max_execution_time',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_heap_table_size'] = [
            'max_heap_table_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['max_insert_delayed_threads'] = [
            'max_insert_delayed_threads',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_join_size'] = [
            'max_join_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_length_for_sort_data'] = [
            'max_length_for_sort_data',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_points_in_geometry'] = [
            'max_points_in_geometry',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_long_data_size'] = [
            'max_long_data_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_prepared_stmt_count'] = [
            'max_prepared_stmt_count',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_relay_log_size'] = [
            'max_relay_log_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['max_seeks_for_key'] = [
            'max_seeks_for_key',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_sort_length'] = [
            'max_sort_length',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_sp_recursion_depth'] = [
            'max_sp_recursion_depth',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_tmp_tables'] = [
            'max_tmp_tables',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_user_connections'] = [
            'max_user_connections',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['max_write_lock_count'] = [
            'max_write_lock_count',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['metadata_locks_cache_size'] = [
            'metadata_locks_cache_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['metadata_locks_hash_instances'] = [
            'metadata_locks_hash_instances',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['memlock'] = [
            'memlock',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['min_examined_row_limit'] = [
            'min-examined-row-limit',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['multi_range_count'] = [
            'multi_range_count',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['myisam_data_pointer_size'] = [
            'myisam_data_pointer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['myisam_max_sort_file_size'] = [
            'myisam_max_sort_file_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['myisam_mmap_size'] = [
            'myisam_mmap_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['myisam_recover_options'] = [
            'myisam_recover_options',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['myisam_repair_threads'] = [
            'myisam_repair_threads',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['myisam_sort_buffer_size'] = [
            'myisam_sort_buffer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['myisam_stats_method'] = [
            'myisam_stats_method',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['myisam_use_mmap'] = [
            'myisam_use_mmap',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['mysql_native_password_proxy_users'] = [
            'mysql_native_password_proxy_users',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['named_pipe'] = [
            'named_pipe',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['net_buffer_length'] = [
            'net_buffer_length',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['net_read_timeout'] = [
            'net_read_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['net_retry_count'] = [
            'net_retry_count',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['net_write_timeout'] = [
            'net_write_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['new'] = [
            'new',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ngram_token_size'] = [
            'ngram_token_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['offline_mode'] = [
            'offline_mode',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['old'] = [
            'old',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['old_alter_table'] = [
            'old-alter-table',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['old_passwords'] = [
            'old-passwords',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['open_files_limit'] = [
            'open-files-limit',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['optimizer_prune_level'] = [
            'optimizer_prune_level',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['optimizer_search_depth'] = [
            'optimizer_search_depth',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['optimizer_switch'] = [
            'optimizer_switch',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['optimizer_trace'] = [
            'optimizer_trace',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['optimizer_trace_features'] = [
            'optimizer_trace_features',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['optimizer_trace_limit'] = [
            'optimizer_trace_limit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['optimizer_trace_max_mem_size'] = [
            'optimizer_trace_max_mem_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['optimizer_trace_offset'] = [
            'optimizer_trace_offset',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['partition'] = [
            'partition',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['performance_schema'] = [
            'performance_schema',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_accounts_size'] = [
            'performance_schema_accounts_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_digests_size'] = [
            'performance_schema_digests_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_events_stages_history_long_size']
            = [
                'performance_schema_events_stages_history_long_size',
                'performance-schema-system-variables',
                'sysvar',
            ];
        $variable_doc_links['performance_schema_events_stages_history_size'] = [
            'performance_schema_events_stages_history_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_events_statements_history_long_size']
            = [
                'performance_schema_events_statements_history_long_size',
                'performance-schema-system-variables',
                'sysvar',
            ];
        $variable_doc_links['performance_schema_events_statements_history_size']
            = [
                'performance_schema_events_statements_history_size',
                'performance-schema-system-variables',
                'sysvar',
            ];
        $variable_doc_links['performance_schema_events_transactions_history_long_size']
            = [
                'performance_schema_events_transactions_history_long_size',
                'performance-schema-system-variables',
                'sysvar',
            ];
        $variable_doc_links['performance_schema_events_transactions_history_size']
            = [
                'performance_schema_events_transactions_history_size',
                'performance-schema-system-variables',
                'sysvar',
            ];
        $variable_doc_links['performance_schema_events_waits_history_long_size']
            = [
                'performance_schema_events_waits_history_long_size',
                'performance-schema-system-variables',
                'sysvar',
            ];
        $variable_doc_links['performance_schema_events_waits_history_size'] = [
            'performance_schema_events_waits_history_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_hosts_size'] = [
            'performance_schema_hosts_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_cond_classes'] = [
            'performance_schema_max_cond_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_cond_instances'] = [
            'performance_schema_max_cond_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_digest_length'] = [
            'performance_schema_max_digest_length',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_file_classes'] = [
            'performance_schema_max_file_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_file_handles'] = [
            'performance_schema_max_file_handles',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_file_instances'] = [
            'performance_schema_max_file_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_index_stat'] = [
            'performance_schema_max_index_stat',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_memory_classes'] = [
            'performance_schema_max_memory_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_metadata_locks'] = [
            'performance_schema_max_metadata_locks',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_mutex_classes'] = [
            'performance_schema_max_mutex_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_mutex_instances'] = [
            'performance_schema_max_mutex_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_prepared_statements_instances'] = [
            'performance_schema_max_prepared_statements_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_program_instances'] = [
            'performance_schema_max_program_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_rwlock_classes'] = [
            'performance_schema_max_rwlock_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_rwlock_instances'] = [
            'performance_schema_max_rwlock_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_socket_classes'] = [
            'performance_schema_max_socket_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_socket_instances'] = [
            'performance_schema_max_socket_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_sql_text_length'] = [
            'performance_schema_max_sql_text_length',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_stage_classes'] = [
            'performance_schema_max_stage_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_statement_classes'] = [
            'performance_schema_max_statement_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_statement_stack'] = [
            'performance_schema_max_statement_stack',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_table_handles'] = [
            'performance_schema_max_table_handles',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_table_instances'] = [
            'performance_schema_max_table_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_table_lock_stat'] = [
            'performance_schema_max_table_lock_stat',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_thread_classes'] = [
            'performance_schema_max_thread_classes',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_max_thread_instances'] = [
            'performance_schema_max_thread_instances',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_session_connect_attrs_size'] = [
            'performance_schema_session_connect_attrs_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_setup_actors_size'] = [
            'performance_schema_setup_actors_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_setup_objects_size'] = [
            'performance_schema_setup_objects_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['performance_schema_users_size'] = [
            'performance_schema_users_size',
            'performance-schema-system-variables',
            'sysvar'];
        $variable_doc_links['pid_file'] = [
            'pid-file',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['plugin_dir'] = [
            'plugin_dir',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['port'] = [
            'port',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['preload_buffer_size'] = [
            'preload_buffer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['profiling'] = [
            'profiling',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['profiling_history_size'] = [
            'profiling_history_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['protocol_version'] = [
            'protocol_version',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['proxy_user'] = [
            'proxy_user',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['pseudo_thread_id'] = [
            'pseudo_thread_id',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['query_alloc_block_size'] = [
            'query_alloc_block_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['query_cache_limit'] = [
            'query_cache_limit',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['query_cache_min_res_unit'] = [
            'query_cache_min_res_unit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['query_cache_size'] = [
            'query_cache_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['query_cache_type'] = [
            'query_cache_type',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['query_cache_wlock_invalidate'] = [
            'query_cache_wlock_invalidate',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['query_prealloc_size'] = [
            'query_prealloc_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['rand_seed1'] = [
            'rand_seed1',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['rand_seed2'] = [
            'rand_seed2',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['range_alloc_block_size'] = [
            'range_alloc_block_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['rbr_exec_mode'] = [
            'rbr_exec_mode',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['read_buffer_size'] = [
            'read_buffer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['read_only'] = [
            'read_only',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['read_rnd_buffer_size'] = [
            'read_rnd_buffer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['relay_log'] = [
            'relay_log',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['relay_log_basename'] = [
            'relay_log_basename',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['relay-log-index'] = [
            'relay-log-index',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['relay_log_index'] = [
            'relay_log_index',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['relay_log_info_file'] = [
            'relay_log_info_file',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['relay_log_info_repository'] = [
            'relay_log_info_repository',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['relay_log_purge'] = [
            'relay_log_purge',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['relay_log_recovery'] = [
            'relay_log_recovery',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['relay_log_space_limit'] = [
            'relay_log_space_limit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['report_host'] = [
            'report-host',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['report_password'] = [
            'report-password',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['report_port'] = [
            'report-port',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['report_user'] = [
            'report-user',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['require_secure_transport'] = [
            'require_secure_transport',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['rpl_stop_slave_timeout'] = [
            'rpl_stop_slave_timeout',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['rpl_recovery_rank'] = [
            'rpl_recovery_rank',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['rpl_semi_sync_master_enabled'] = [
            'rpl_semi_sync_master_enabled',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['rpl_semi_sync_master_timeout'] = [
            'rpl_semi_sync_master_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['rpl_semi_sync_master_trace_level'] = [
            'rpl_semi_sync_master_trace_level',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['rpl_semi_sync_master_wait_no_slave'] = [
            'rpl_semi_sync_master_wait_no_slave',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['rpl_semi_sync_slave_enabled'] = [
            'rpl_semi_sync_slave_enabled',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['rpl_semi_sync_slave_trace_level'] = [
            'rpl_semi_sync_slave_trace_level',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['safe_show_database'] = [
            'safe-show-database',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['secure_auth'] = [
            'secure-auth',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['secure_file_priv'] = [
            'secure-file-priv',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['server_id'] = [
            'server-id',
            'replication-options',
            'option_mysqld'];
        $variable_doc_links['server_id_bits'] = [
            'server_id_bits',
            'mysql-cluster-system-variables',
            'sysvar'];
        $variable_doc_links['server_uuid'] = [
            'server_uuid',
            'replication-options',
            'sysvar'];
        $variable_doc_links['session_track_gtids'] = [
            'session_track_gtids',
            'server_system_variables',
            'sysvar'];
        $variable_doc_links['session_track_schema'] = [
            'session_track_schema',
            'server_system_variables',
            'sysvar'];
        $variable_doc_links['session_track_state_change'] = [
            'session_track_state_change',
            'server_system_variables',
            'sysvar'];
        $variable_doc_links['session_track_system_variables'] = [
            'session_track_system_variables',
            'session_system_variables',
            'sysvar'];
        $variable_doc_links['session_track_transaction_info'] = [
            'session_track_transaction_info',
            'session_system_variables',
            'sysvar'];
        $variable_doc_links['sha256_password_proxy_users'] = [
            'sha256_password_proxy_users',
            'session_system_variables',
            'sysvar'];
        $variable_doc_links['show_compatibility_56'] = [
            'show_compatibility_56',
            'session_system_variables',
            'sysvar'];
        $variable_doc_links['show_old_temporals'] = [
            'show_old_temporals',
            'session_system_variables',
            'sysvar'];
        $variable_doc_links['shared_memory'] = [
            'shared_memory',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['shared_memory_base_name'] = [
            'shared_memory_base_name',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['skip_external_locking'] = [
            'skip-external-locking',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['skip_name_resolve'] = [
            'skip-name-resolve',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['skip_networking'] = [
            'skip-networking',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['skip_show_database'] = [
            'skip-show-database',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['slave_allow_batching'] = [
            'slave_allow_batching',
            'mysql-cluster-system-variables',
            'sysvar'];
        $variable_doc_links['slave_checkpoint_group'] = [
            'slave_checkpoint_group',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_checkpoint_period'] = [
            'slave_checkpoint_period',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_compressed_protocol'] = [
            'slave_compressed_protocol',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_exec_mode'] = [
            'slave_exec_mode',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_load_tmpdir'] = [
            'slave-load-tmpdir',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['slave_max_allowed_packet'] = [
            'slave_max_allowed_packet',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_net_timeout'] = [
            'slave-net-timeout',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['slave_parallel_type'] = [
            'slave_parallel_type',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_parallel_workers'] = [
            'slave_parallel_workers',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_pending_jobs_size_max'] = [
            'slave_pending_jobs_size_max',
            'replication-options-slave',
            'sysvar',
            'byte'];
        $variable_doc_links['slave_preserve_commit_order'] = [
            'slave_preserve_commit_order',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_rows_search_algorithms'] = [
            'slave_rows_search_algorithms',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_skip_errors'] = [
            'slave-skip-errors',
            'replication-options-slave',
            'option_mysqld'];
        $variable_doc_links['slave_sql_verify_checksum'] = [
            'slave_sql_verify_checksum',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_transaction_retries'] = [
            'slave_transaction_retries',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slave_type_conversions'] = [
            'slave_type_conversions',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['slow_launch_time'] = [
            'slow_launch_time',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['slow_query_log'] = [
            'slow-query-log',
            'server-options',
            'server-system-variables'];
        $variable_doc_links['slow_query_log_file'] = [
            'slow_query_log_file',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['socket'] = [
            'socket',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['sort_buffer_size'] = [
            'sort_buffer_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['sql_auto_is_null'] = [
            'sql_auto_is_null',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_big_selects'] = [
            'sql_big_selects',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_big_tables'] = [
            'big-tables',
            'server-options',
            'server-system-variables'];
        $variable_doc_links['sql_buffer_result'] = [
            'sql_buffer_result',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_log_bin'] = [
            'sql_log_bin',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_log_off'] = [
            'sql_log_off',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_log_update'] = [
            'sql_log_update',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_low_priority_updates'] = [
            'sql_low_priority_updates',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_max_join_size'] = [
            'sql_max_join_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_mode'] = [
            'sql-mode',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['sql_notes'] = [
            'sql_notes',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_quote_show_create'] = [
            'sql_quote_show_create',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_safe_updates'] = [
            'sql_safe_updates',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_select_limit'] = [
            'sql_select_limit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sql_slave_skip_counter'] = [
            'sql_slave_skip_counter',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['sql_warnings'] = [
            'sql_warnings',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ssl_ca'] = [
            'ssl-ca',
            'ssl-options',
            'option_general'];
        $variable_doc_links['ssl_capath'] = [
            'ssl-capath',
            'ssl-options',
            'option_general'];
        $variable_doc_links['ssl_cert'] = [
            'ssl-cert',
            'ssl-options',
            'option_general'];
        $variable_doc_links['ssl_cipher'] = [
            'ssl-cipher',
            'ssl-options',
            'option_general'];
        $variable_doc_links['ssl_crl'] = [
            'ssl_crl',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ssl_crlpath'] = [
            'ssl_crlpath',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['ssl_key'] = [
            'ssl-key',
            'ssl-options',
            'option_general'];
        $variable_doc_links['storage_engine'] = [
            'storage_engine',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['stored_program_cache'] = [
            'stored_program_cache',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['super_read_only'] = [
            'super_read_only',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sync_binlog'] = [
            'sync_binlog',
            'replication-options-binary-log',
            'sysvar'];
        $variable_doc_links['sync_frm'] = [
            'sync_frm',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['sync_master_info'] = [
            'sync_master_info',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['sync_relay_log'] = [
            'sync_relay_log',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['sync_relay_log_info'] = [
            'sync_relay_log_info',
            'replication-options-slave',
            'sysvar'];
        $variable_doc_links['system_time_zone'] = [
            'system_time_zone',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['table_definition_cache'] = [
            'table_definition_cache',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['table_lock_wait_timeout'] = [
            'table_lock_wait_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['table_open_cache'] = [
            'table_open_cache',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['table_open_cache_instances'] = [
            'table_open_cache_instances',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['table_type'] = [
            'table_type',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['thread_cache_size'] = [
            'thread_cache_size',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['thread_concurrency'] = [
            'thread_concurrency',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['thread_handling'] = [
            'thread_handling',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['thread_stack'] = [
            'thread_stack',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['time_format'] = [
            'time_format',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['time_zone'] = [
            'time_zone',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['timed_mutexes'] = [
            'timed_mutexes',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['timestamp'] = [
            'timestamp',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['tmp_table_size'] = [
            'tmp_table_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['tmpdir'] = [
            'tmpdir',
            'server-options',
            'option_mysqld'];
        $variable_doc_links['transaction_alloc_block_size'] = [
            'transaction_alloc_block_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['transaction_prealloc_size'] = [
            'transaction_prealloc_size',
            'server-system-variables',
            'sysvar',
            'byte'];
        $variable_doc_links['transaction_write_set_extraction'] = [
            'transaction_write_set_extraction',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['tx_isolation'] = [
            'tx_isolation',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['tx_read_only'] = [
            'tx_read_only',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['unique_checks'] = [
            'unique_checks',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['updatable_views_with_limit'] = [
            'updatable_views_with_limit',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['version'] = [
            'version',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['version_comment'] = [
            'version_comment',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['version_compile_machine'] = [
            'version_compile_machine',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['version_compile_os'] = [
            'version_compile_os',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['wait_timeout'] = [
            'wait_timeout',
            'server-system-variables',
            'sysvar'];
        $variable_doc_links['warning_count'] = [
            'warning_count',
            'server-system-variables',
            'sysvar'];
        return $variable_doc_links;
    }

    /**
     * Returns array of static(i.e. non-editable/ read-only) global system variables
     *
     * See https://dev.mysql.com/doc/refman/5.6/en/server-system-variables.html
     *
     * @return array
     */
    private function _getStaticSystemVariables()
    {
        $static_variables = [
            'audit_log_buffer_size',
            'audit_log_current_session',
            'audit_log_file',
            'audit_log_format',
            'audit_log_policy',
            'audit_log_strategy',
            'auto_generate_certs',
            'back_log',
            'basedir',
            'bind_address',
            'binlog_gtid_simple_recovery',
            'character_set_system',
            'character_sets_dir',
            'core_file',
            'daemon_memcached_enable_binlog',
            'daemon_memcached_engine_lib_name',
            'daemon_memcached_engine_lib_path',
            'daemon_memcached_option',
            'daemon_memcached_r_batch_size',
            'daemon_memcached_w_batch_size',
            'datadir',
            'date_format',
            'datetime_format',
            'default_authentication_plugin',
            'disabled_storage_engines',
            'explicit_defaults_for_timestamp',
            'ft_max_word_len',
            'ft_min_word_len',
            'ft_query_expansion_limit',
            'ft_stopword_file',
            'gtid_owned',
            'have_compress',
            'have_crypt',
            'have_dynamic_loading',
            'have_geometry',
            'have_openssl',
            'have_profiling',
            'have_query_cache',
            'have_rtree_keys',
            'have_ssl',
            'have_statement_timeout',
            'have_symlink',
            'hostname',
            'ignore_builtin_innodb',
            'ignore_db_dirs',
            'init_file',
            'innodb_adaptive_hash_index_parts',
            'innodb_additional_mem_pool_size',
            'innodb_api_disable_rowlock',
            'innodb_api_enable_binlog',
            'innodb_api_enable_mdl',
            'innodb_autoinc_lock_mode',
            'innodb_buffer_pool_chunk_size',
            'innodb_buffer_pool_instances',
            'innodb_buffer_pool_load_at_startup',
            'innodb_checksums',
            'innodb_data_file_path',
            'innodb_data_home_dir',
            'innodb_doublewrite',
            'innodb_file_format_check',
            'innodb_flush_method',
            'innodb_force_load_corrupted',
            'innodb_force_recovery',
            'innodb_ft_cache_size',
            'innodb_ft_max_token_size',
            'innodb_ft_min_token_size',
            'innodb_ft_sort_pll_degree',
            'innodb_ft_total_cache_size',
            'innodb_locks_unsafe_for_binlog',
            'innodb_log_buffer_size',
            'innodb_log_file_size',
            'innodb_log_files_in_group',
            'innodb_log_group_home_dir',
            'innodb_numa_interleave',
            'innodb_open_files',
            'innodb_page_cleaners',
            'innodb_page_size',
            'innodb_purge_threads',
            'innodb_read_io_threads',
            'innodb_read_only',
            'innodb_rollback_on_timeout',
            'innodb_sort_buffer_size',
            'innodb_sync_array_size',
            'innodb_sync_debug',
            'innodb_temp_data_file_path',
            'innodb_undo_directory',
            'innodb_undo_tablespaces',
            'innodb_use_native_aio',
            'innodb_use_sys_malloc',
            'innodb_version',
            'innodb_write_io_threads',
            'language',
            'large_files_support',
            'large_page_size',
            'large_pages',
            'lc_messages_dir',
            'license',
            'locked_in_memory',
            'log-bin',
            'log_bin',
            'log_bin_basename',
            'log_bin_index',
            'log_bin_use_v1_row_events',
            'log_bin_use_v1_row_events',
            'log_error',
            'log_slave_updates',
            'log_slave_updates',
            'lower_case_file_system',
            'lower_case_table_names',
            'max_digest_length',
            'mecab_rc_file',
            'metadata_locks_cache_size',
            'metadata_locks_hash_instances',
            'myisam_mmap_size',
            'myisam_recover_options',
            'named_pipe',
            'ndb-batch-size',
            'ndb-cluster-connection-pool',
            'ndb-cluster-connection-pool-nodeids',
            'ndb_log_apply_status',
            'ndb_log_apply_status',
            'ndb_log_orig',
            'ndb_log_orig',
            'ndb_log_transaction_id',
            'ndb_log_transaction_id',
            'ndb_optimized_node_selection',
            'Ndb_slave_max_replicated_epoch',
            'ndb_use_copying_alter_table',
            'ndb_version',
            'ndb_version_string',
            'ndb-wait-connected',
            'ndb-wait-setup',
            'ndbinfo_database',
            'ndbinfo_version',
            'ngram_token_size',
            'old',
            'open_files_limit',
            'performance_schema',
            'performance_schema_accounts_size',
            'performance_schema_digests_size',
            'performance_schema_events_stages_history_long_size',
            'performance_schema_events_stages_history_size',
            'performance_schema_events_statements_history_long_size',
            'performance_schema_events_statements_history_size',
            'performance_schema_events_transactions_history_long_size',
            'performance_schema_events_transactions_history_size',
            'performance_schema_events_waits_history_long_size',
            'performance_schema_events_waits_history_size',
            'performance_schema_hosts_size',
            'performance_schema_max_cond_classes',
            'performance_schema_max_cond_instances',
            'performance_schema_max_digest_length',
            'performance_schema_max_file_classes',
            'performance_schema_max_file_handles',
            'performance_schema_max_file_instances',
            'performance_schema_max_index_stat',
            'performance_schema_max_memory_classes',
            'performance_schema_max_metadata_locks',
            'performance_schema_max_mutex_classes',
            'performance_schema_max_mutex_instances',
            'performance_schema_max_prepared_statements_instances',
            'performance_schema_max_program_instances',
            'performance_schema_max_rwlock_classes',
            'performance_schema_max_rwlock_instances',
            'performance_schema_max_socket_classes',
            'performance_schema_max_socket_instances',
            'performance_schema_max_sql_text_length',
            'performance_schema_max_stage_classes',
            'performance_schema_max_statement_classes',
            'performance_schema_max_statement_stack',
            'performance_schema_max_table_handles',
            'performance_schema_max_table_instances',
            'performance_schema_max_table_lock_stat',
            'performance_schema_max_thread_classes',
            'performance_schema_max_thread_instances',
            'performance_schema_session_connect_attrs_size',
            'performance_schema_setup_actors_size',
            'performance_schema_setup_objects_size',
            'performance_schema_users_size',
            'pid_file',
            'plugin_dir',
            'port',
            'protocol_version',
            'relay_log',
            'relay_log_basename',
            'relay_log_index',
            'relay_log_index',
            'relay_log_info_file',
            'relay_log_recovery',
            'relay_log_space_limit',
            'report_host',
            'eport_password',
            'report_port',
            'report_user',
            'secure_file_priv',
            'server_id_bits',
            'server_id_bits',
            'server_uuid',
            'sha256_password_auto_generate_rsa_keys',
            'sha256_password_private_key_path',
            'sha256_password_public_key_path',
            'shared_memory',
            'shared_memory_base_name',
            'simplified_binlog_gtid_recovery',
            'skip_external_locking',
            'skip_name_resolve',
            'skip_networking',
            'skip_show_database',
            'slave_load_tmpdir',
            'slave_skip_errors',
            'slave_type_conversions',
            'socket',
            'ssl_ca',
            'ssl_capath',
            'ssl_cert',
            'ssl_cipher',
            'ssl_crl',
            'ssl_crlpath',
            'ssl_key',
            'system_time_zone',
            'table_open_cache_instances',
            'thread_concurrency',
            'thread_handling',
            'thread_stack',
            'time_format',
            'tls_version',
            'tmpdir',
            'validate_user_plugins',
            'version',
            'version_comment',
            'version_compile_machine',
            'version_compile_os',
            'version_tokens_session_number'
        ];

        return $static_variables;
    }
}
