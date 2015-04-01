<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server variables
 *
 * @usedby  server_variables.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Get Ajax return when $_REQUEST['type'] === 'getval'
 *
 * @param Array $variable_doc_links documentation links
 *
 * @return null
 */
function PMA_getAjaxReturnForGetVal($variable_doc_links)
{
    $response = PMA_Response::getInstance();

    // Send with correct charset
    header('Content-Type: text/html; charset=UTF-8');
    $varValue = $GLOBALS['dbi']->fetchSingleRow(
        'SHOW GLOBAL VARIABLES WHERE Variable_name="'
        . PMA_Util::sqlAddSlashes($_REQUEST['varName']) . '";',
        'NUM'
    );
    if (isset($variable_doc_links[$_REQUEST['varName']][3])
        && $variable_doc_links[$_REQUEST['varName']][3] == 'byte'
    ) {
        $response->addJSON(
            'message',
            implode(
                ' ', PMA_Util::formatByteDown($varValue[1], 3, 3)
            )
        );
    } else {
        $response->addJSON(
            'message',
            $varValue[1]
        );
    }
}
/**
 * Get Ajax return when $_REQUEST['type'] === 'setval'
 *
 * @param Array $variable_doc_links documentation links
 *
 * @return null
 */
function PMA_getAjaxReturnForSetVal($variable_doc_links)
{
    $response = PMA_Response::getInstance();

    $value = $_REQUEST['varValue'];
    $matches = array();

    if (isset($variable_doc_links[$_REQUEST['varName']][3])
        && $variable_doc_links[$_REQUEST['varName']][3] == 'byte'
        && preg_match(
            '/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i',
            $value,
            $matches
        )
    ) {
        $exp = array(
            'kb' => 1,
            'kib' => 1,
            'mb' => 2,
            'mib' => 2,
            'gb' => 3,
            'gib' => 3
        );
        $value = floatval($matches[1]) * PMA_Util::pow(
            1024,
            $exp[/*overload*/mb_strtolower($matches[3])]
        );
    } else {
        $value = PMA_Util::sqlAddSlashes($value);
    }

    if (! is_numeric($value)) {
        $value="'" . $value . "'";
    }

    if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName'])
        && $GLOBALS['dbi']->query(
            'SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value
        )
    ) {
        // Some values are rounded down etc.
        $varValue = $GLOBALS['dbi']->fetchSingleRow(
            'SHOW GLOBAL VARIABLES WHERE Variable_name="'
            . PMA_Util::sqlAddSlashes($_REQUEST['varName'])
            . '";', 'NUM'
        );
        $response->addJSON(
            'variable',
            PMA_formatVariable(
                $_REQUEST['varName'],
                $varValue[1],
                $variable_doc_links
            )
        );
    } else {
        $response->isSuccess(false);
        $response->addJSON(
            'error',
            __('Setting variable failed')
        );
    }
}

/**
 * Format Variable
 *
 * @param string $name               variable name
 * @param number $value              variable value
 * @param array  $variable_doc_links documentation links
 *
 * @return string formatted string
 */
function PMA_formatVariable($name, $value, $variable_doc_links)
{
    if (is_numeric($value)) {
        if (isset($variable_doc_links[$name][3])
            && $variable_doc_links[$name][3]=='byte'
        ) {
            return '<abbr title="'
                . PMA_Util::formatNumber($value, 0) . '">'
                . implode(' ', PMA_Util::formatByteDown($value, 3, 3))
                . '</abbr>';
        } else {
            return PMA_Util::formatNumber($value, 0);
        }
    }
    return htmlspecialchars($value);
}

/**
 * Prints link templates
 *
 * @return string
 */
function PMA_getHtmlForLinkTemplates()
{
    $url = 'server_variables.php' . PMA_URL_getCommon();
    $output  = '<a style="display: none;" href="'
        . $url . '" class="ajax saveLink">';
    $output .= PMA_Util::getIcon('b_save.png', __('Save')) . '</a> ';
    $output .= '<a style="display: none;" href="#" class="cancelLink">';
    $output .= PMA_Util::getIcon('b_close.png', __('Cancel')) . '</a> ';
    $output .= PMA_Util::getImage(
        'b_help.png',
        __('Documentation'),
        array('style' => 'display:none', 'id' => 'docImage')
    );
    return $output;
}

/**
 * Prints Html for Server Variables
 *
 * @param Array $variable_doc_links documentation links
 *
 * @return string
 */
function PMA_getHtmlForServerVariables($variable_doc_links)
{
    $value = ! empty($_REQUEST['filter'])
        ? htmlspecialchars($_REQUEST['filter'])
        : '';
    $output = '<fieldset id="tableFilter">'
        . '<legend>' . __('Filters') . '</legend>'
        . '<div class="formelement">'
        . '<label for="filterText">' .  __('Containing the word:') . '</label>'
        . '<input name="filterText" type="text" id="filterText"'
        . ' style="vertical-align: baseline;" value="' . $value . '" />'
        . '</div>'
        . '</fieldset>';

    $output .= '<table id="serverVariables" class="data filteredData noclick">'
        . '<thead><tr class="var-header var-row">'
        . '<td class="var-action">' . __('Action') . '</td>'
        . '<td class="var-name">' .  __('Variable') . '</td>'
        . '<td class="var-value">'
        . __('Session value') . ' / ' . __('Global value')
        . '</td>'
        . '</tr>'
        . '</thead>';

    $output .= PMA_getHtmlForServerVariablesItems($variable_doc_links);

    $output .= '</table>';

    return $output;
}


/**
 * Prints Html for Server Variables Items
 *
 * @param Array $variable_doc_links documentation links
 *
 * @return string
 */
function PMA_getHtmlForServerVariablesItems($variable_doc_links)
{
    /**
     * Sends the queries and buffers the results
     */
    $serverVarsSession
        = $GLOBALS['dbi']->fetchResult('SHOW SESSION VARIABLES;', 0, 1);
    $serverVars = $GLOBALS['dbi']->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);

    $output = '<tbody>';
    $odd_row = true;
    foreach ($serverVars as $name => $value) {
        $has_session_value = isset($serverVarsSession[$name])
            && $serverVarsSession[$name] != $value;
        $row_class = ($odd_row ? ' odd' : ' even')
            . ($has_session_value ? ' diffSession' : '');

        $output .= '<tr class="var-row' . $row_class . '">';

        $output .= '<td class="var-action">';
        $output .=  '<a href="#" class="editLink">'
            . PMA_Util::getIcon('b_edit.png', __('Edit')) . '</a>';
        $output .= '</td>';

        $output .= '<td class="var-name">';
        // To display variable documentation link
        if (isset($variable_doc_links[$name])) {
            $output .= '<span title="'
                . htmlspecialchars(str_replace('_', ' ', $name)) . '">';
            $output .= PMA_Util::showMySQLDocu(
                $variable_doc_links[$name][1],
                false,
                $variable_doc_links[$name][2] . '_' . $variable_doc_links[$name][0],
                true
            );
            $output .= htmlspecialchars(str_replace('_', ' ', $name));
            $output .= '</a>';
            $output .= '</span>';
        } else {
            $output .= htmlspecialchars(str_replace('_', ' ', $name));
        }
        $output .= '</td>';

        $output .= '<td class="var-value value'
            . ($GLOBALS['dbi']->isSuperuser() ? ' editable' : '') . '">&nbsp;'
            . PMA_formatVariable($name, $value, $variable_doc_links)
            . '</td>'
            . '</tr>';

        if ($has_session_value) {
            $output .= '<tr class="var-row' . ($odd_row ? ' odd' : ' even') . '">'
                . '<td class="var-action"></td>'
                . '<td class="var-name session">(' . __('Session value') . ')</td>'
                . '<td class="var-value value">&nbsp;'
                . PMA_formatVariable(
                    $name,
                    $serverVarsSession[$name],
                    $variable_doc_links
                ) . '</td>'
                . '</tr>';
        }

        $odd_row = ! $odd_row;
    }
    $output .= '</tbody>';

    return $output;
}

/**
 * Returns Array of documentation links
 *
 * $variable_doc_links[string $name] = array(
 *    string $anchor,
 *    string $chapter,
 *    string $type);
 * string $name: name of the system variable
 * string $anchor: anchor to the documentation page
 * string $chapter: chapter of "HTML, one page per chapter" documentation
 * string $type: type of system variable
 * string $format: if set to 'byte' it will format the variable
 * with PMA_Util::formatByteDown()
 *
 * @return array
 */
function PMA_getArrayForDocumentLinks()
{
    $variable_doc_links = array();
    $variable_doc_links['auto_increment_increment'] = array(
        'auto_increment_increment',
        'replication-options-master',
        'sysvar');
    $variable_doc_links['auto_increment_offset'] = array(
        'auto_increment_offset',
        'replication-options-master',
        'sysvar');
    $variable_doc_links['autocommit'] = array(
        'autocommit',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['automatic_sp_privileges'] = array(
        'automatic_sp_privileges',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['back_log'] = array(
        'back_log',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['basedir'] = array(
        'basedir',
        'server-options',
        'option_mysqld');
    $variable_doc_links['big_tables'] = array(
        'big-tables',
        'server-options',
        'option_mysqld');
    $variable_doc_links['bind_address'] = array(
        'bind-address',
        'server-options',
        'option_mysqld');
    $variable_doc_links['binlog_cache_size'] = array(
        'binlog_cache_size',
        'replication-options-binary-log',
        'sysvar',
        'byte');
    $variable_doc_links['binlog_direct_non_transactional_updates'] = array(
        'binlog_direct_non_transactional_updates',
        'replication-options-binary-log',
        'sysvar');
    $variable_doc_links['binlog_format'] = array(
        'binlog-format',
        'server-options',
        'sysvar');
    $variable_doc_links['binlog_stmt_cache_size'] = array(
        'binlog_stmt_cache_size',
        'replication-options-binary-log',
        'sysvar',
        'byte');
    $variable_doc_links['bulk_insert_buffer_size'] = array(
        'bulk_insert_buffer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['character_set_client'] = array(
        'character_set_client',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['character_set_connection'] = array(
        'character_set_connection',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['character_set_database'] = array(
        'character_set_database',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['character_set_filesystem'] = array(
        'character-set-filesystem',
        'server-options',
        'option_mysqld');
    $variable_doc_links['character_set_results'] = array(
        'character_set_results',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['character_set_server'] = array(
        'character-set-server',
        'server-options',
        'option_mysqld');
    $variable_doc_links['character_set_system'] = array(
        'character_set_system',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['character_sets_dir'] = array(
        'character-sets-dir',
        'server-options',
        'option_mysqld');
    $variable_doc_links['collation_connection'] = array(
        'collation_connection',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['collation_database'] = array(
        'collation_database',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['collation_server'] = array(
        'collation-server',
        'server-options',
        'option_mysqld');
    $variable_doc_links['completion_type'] = array(
        'completion_type',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['concurrent_insert'] = array(
        'concurrent_insert',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['connect_timeout'] = array(
        'connect_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['datadir'] = array(
        'datadir',
        'server-options',
        'option_mysqld');
    $variable_doc_links['date_format'] = array(
        'date_format',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['datetime_format'] = array(
        'datetime_format',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['debug'] = array(
        'debug',
        'server-options',
        'option_mysqld');
    $variable_doc_links['debug_sync'] = array(
        'debug_sync',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['default_storage_engine'] = array(
        'default-storage-engine',
        'server-options',
        'option_mysqld');
    $variable_doc_links['default_week_format'] = array(
        'default_week_format',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['delay_key_write'] = array(
        'delay-key-write',
        'server-options',
        'option_mysqld');
    $variable_doc_links['delayed_insert_limit'] = array(
        'delayed_insert_limit',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['delayed_insert_timeout'] = array(
        'delayed_insert_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['delayed_queue_size'] = array(
        'delayed_queue_size',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['div_precision_increment'] = array(
        'div_precision_increment',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['engine_condition_pushdown'] = array(
        'engine-condition-pushdown',
        'server-options',
        'option_mysqld');
    $variable_doc_links['error_count'] = array(
        'error_count',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['event_scheduler'] = array(
        'event-scheduler',
        'server-options',
        'option_mysqld');
    $variable_doc_links['expire_logs_days'] = array(
        'expire_logs_days',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['external_user'] = array(
        'external_user',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['flush'] = array(
        'flush',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['flush_time'] = array(
        'flush_time',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['foreign_key_checks'] = array(
        'foreign_key_checks',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['ft_boolean_syntax'] = array(
        'ft_boolean_syntax',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['ft_max_word_len'] = array(
        'ft_max_word_len',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['ft_min_word_len'] = array(
        'ft_min_word_len',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['ft_query_expansion_limit'] = array(
        'ft_query_expansion_limit',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['ft_stopword_file'] = array(
        'ft_stopword_file',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['general_log'] = array(
        'general-log',
        'server-options',
        'option_mysqld');
    $variable_doc_links['general_log_file'] = array(
        'general_log_file',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['group_concat_max_len'] = array(
        'group_concat_max_len',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_compress'] = array(
        'have_compress',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_crypt'] = array(
        'have_crypt',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_csv'] = array(
        'have_csv',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_dynamic_loading'] = array(
        'have_dynamic_loading',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_geometry'] = array(
        'have_geometry',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_innodb'] = array(
        'have_innodb',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_openssl'] = array(
        'have_openssl',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_partitioning'] = array(
        'have_partitioning',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_profiling'] = array(
        'have_profiling',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_query_cache'] = array(
        'have_query_cache',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_rtree_keys'] = array(
        'have_rtree_keys',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_ssl'] = array(
        'have_ssl',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['have_symlink'] = array(
        'have_symlink',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['hostname'] = array(
        'hostname',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['identity'] = array(
        'identity',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['ignore_builtin_innodb'] = array(
        'ignore-builtin-innodb',
        'innodb-parameters',
        'option_mysqld');
    $variable_doc_links['init_connect'] = array(
        'init_connect',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['init_file'] = array(
        'init-file',
        'server-options',
        'option_mysqld');
    $variable_doc_links['init_slave'] = array(
        'init_slave',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['innodb_adaptive_flushing'] = array(
        'innodb_adaptive_flushing',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_adaptive_hash_index'] = array(
        'innodb_adaptive_hash_index',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_additional_mem_pool_size'] = array(
        'innodb_additional_mem_pool_size',
        'innodb-parameters',
        'sysvar',
        'byte');
    $variable_doc_links['innodb_autoextend_increment'] = array(
        'innodb_autoextend_increment',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_autoinc_lock_mode'] = array(
        'innodb_autoinc_lock_mode',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_buffer_pool_instances'] = array(
        'innodb_buffer_pool_instances',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_buffer_pool_size'] = array(
        'innodb_buffer_pool_size',
        'innodb-parameters',
        'sysvar',
        'byte');
    $variable_doc_links['innodb_change_buffering'] = array(
        'innodb_change_buffering',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_checksums'] = array(
        'innodb_checksums',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_commit_concurrency'] = array(
        'innodb_commit_concurrency',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_concurrency_tickets'] = array(
        'innodb_concurrency_tickets',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_data_file_path'] = array(
        'innodb_data_file_path',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_data_home_dir'] = array(
        'innodb_data_home_dir',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_doublewrite'] = array(
        'innodb_doublewrite',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_fast_shutdown'] = array(
        'innodb_fast_shutdown',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_file_format'] = array(
        'innodb_file_format',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_file_format_check'] = array(
        'innodb_file_format_check',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_file_format_max'] = array(
        'innodb_file_format_max',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_file_per_table'] = array(
        'innodb_file_per_table',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_flush_log_at_trx_commit'] = array(
        'innodb_flush_log_at_trx_commit',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_flush_method'] = array(
        'innodb_flush_method',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_force_recovery'] = array(
        'innodb_force_recovery',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_io_capacity'] = array(
        'innodb_io_capacity',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_lock_wait_timeout'] = array(
        'innodb_lock_wait_timeout',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_locks_unsafe_for_binlog'] = array(
        'innodb_locks_unsafe_for_binlog',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_log_buffer_size'] = array(
        'innodb_log_buffer_size',
        'innodb-parameters',
        'sysvar',
        'byte');
    $variable_doc_links['innodb_log_file_size'] = array(
        'innodb_log_file_size',
        'innodb-parameters',
        'sysvar',
        'byte');
    $variable_doc_links['innodb_log_files_in_group'] = array(
        'innodb_log_files_in_group',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_log_group_home_dir'] = array(
        'innodb_log_group_home_dir',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_max_dirty_pages_pct'] = array(
        'innodb_max_dirty_pages_pct',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_max_purge_lag'] = array(
        'innodb_max_purge_lag',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_mirrored_log_groups'] = array(
        'innodb_mirrored_log_groups',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_old_blocks_pct'] = array(
        'innodb_old_blocks_pct',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_old_blocks_time'] = array(
        'innodb_old_blocks_time',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_open_files'] = array(
        'innodb_open_files',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_purge_batch_size'] = array(
        'innodb_purge_batch_size',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_purge_threads'] = array(
        'innodb_purge_threads',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_read_ahead_threshold'] = array(
        'innodb_read_ahead_threshold',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_read_io_threads'] = array(
        'innodb_read_io_threads',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_replication_delay'] = array(
        'innodb_replication_delay',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_rollback_on_timeout'] = array(
        'innodb_rollback_on_timeout',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_spin_wait_delay'] = array(
        'innodb_spin_wait_delay',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_stats_on_metadata'] = array(
        'innodb_stats_on_metadata',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_stats_sample_pages'] = array(
        'innodb_stats_sample_pages',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_strict_mode'] = array(
        'innodb_strict_mode',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_support_xa'] = array(
        'innodb_support_xa',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_sync_spin_loops'] = array(
        'innodb_sync_spin_loops',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_table_locks'] = array(
        'innodb_table_locks',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_thread_concurrency'] = array(
        'innodb_thread_concurrency',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_thread_sleep_delay'] = array(
        'innodb_thread_sleep_delay',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_use_native_aio'] = array(
        'innodb_use_native_aio',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_use_sys_malloc'] = array(
        'innodb_use_sys_malloc',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_version'] = array(
        'innodb_version',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['innodb_write_io_threads'] = array(
        'innodb_write_io_threads',
        'innodb-parameters',
        'sysvar');
    $variable_doc_links['insert_id'] = array(
        'insert_id',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['interactive_timeout'] = array(
        'interactive_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['join_buffer_size'] = array(
        'join_buffer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['keep_files_on_create'] = array(
        'keep_files_on_create',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['key_buffer_size'] = array(
        'key_buffer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['key_cache_age_threshold'] = array(
        'key_cache_age_threshold',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['key_cache_block_size'] = array(
        'key_cache_block_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['key_cache_division_limit'] = array(
        'key_cache_division_limit',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['language'] = array(
        'language',
        'server-options',
        'option_mysqld');
    $variable_doc_links['large_files_support'] = array(
        'large_files_support',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['large_page_size'] = array(
        'large_page_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['large_pages'] = array(
        'large-pages',
        'server-options',
        'option_mysqld');
    $variable_doc_links['last_insert_id'] = array(
        'last_insert_id',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['lc_messages'] = array(
        'lc-messages',
        'server-options',
        'option_mysqld');
    $variable_doc_links['lc_messages_dir'] = array(
        'lc-messages-dir',
        'server-options',
        'option_mysqld');
    $variable_doc_links['lc_time_names'] = array(
        'lc_time_names',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['license'] = array(
        'license',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['local_infile'] = array(
        'local_infile',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['lock_wait_timeout'] = array(
        'lock_wait_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['locked_in_memory'] = array(
        'locked_in_memory',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['log'] = array(
        'log',
        'server-options',
        'option_mysqld');
    $variable_doc_links['log_bin'] = array(
        'log_bin',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['log-bin'] = array(
        'log-bin',
        'replication-options-binary-log',
        'option_mysqld');
    $variable_doc_links['log_bin_trust_function_creators'] = array(
        'log-bin-trust-function-creators',
        'replication-options-binary-log',
        'option_mysqld');
    $variable_doc_links['log_error'] = array(
        'log-error',
        'server-options',
        'option_mysqld');
    $variable_doc_links['log_output'] = array(
        'log-output',
        'server-options',
        'option_mysqld');
    $variable_doc_links['log_queries_not_using_indexes'] = array(
        'log-queries-not-using-indexes',
        'server-options',
        'option_mysqld');
    $variable_doc_links['log_slave_updates'] = array(
        'log-slave-updates',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['log_slow_queries'] = array(
        'log-slow-queries',
        'server-options',
        'option_mysqld');
    $variable_doc_links['log_warnings'] = array(
        'log-warnings',
        'server-options',
        'option_mysqld');
    $variable_doc_links['long_query_time'] = array(
        'long_query_time',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['low_priority_updates'] = array(
        'low-priority-updates',
        'server-options',
        'option_mysqld');
    $variable_doc_links['lower_case_file_system'] = array(
        'lower_case_file_system',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['lower_case_table_names'] = array(
        'lower_case_table_names',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['master-bind'] = array(
        '',
        'replication-options',
        0);
    $variable_doc_links['max_allowed_packet'] = array(
        'max_allowed_packet',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_binlog_cache_size'] = array(
        'max_binlog_cache_size',
        'replication-options-binary-log',
        'sysvar',
        'byte');
    $variable_doc_links['max_binlog_size'] = array(
        'max_binlog_size',
        'replication-options-binary-log',
        'sysvar',
        'byte');
    $variable_doc_links['max_binlog_stmt_cache_size'] = array(
        'max_binlog_stmt_cache_size',
        'replication-options-binary-log',
        'sysvar',
        'byte');
    $variable_doc_links['max_connect_errors'] = array(
        'max_connect_errors',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_connections'] = array(
        'max_connections',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_delayed_threads'] = array(
        'max_delayed_threads',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_error_count'] = array(
        'max_error_count',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_heap_table_size'] = array(
        'max_heap_table_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['max_insert_delayed_threads'] = array(
        'max_insert_delayed_threads',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_join_size'] = array(
        'max_join_size',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_length_for_sort_data'] = array(
        'max_length_for_sort_data',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_prepared_stmt_count'] = array(
        'max_prepared_stmt_count',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_relay_log_size'] = array(
        'max_relay_log_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['max_seeks_for_key'] = array(
        'max_seeks_for_key',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_sort_length'] = array(
        'max_sort_length',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_sp_recursion_depth'] = array(
        'max_sp_recursion_depth',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_tmp_tables'] = array(
        'max_tmp_tables',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_user_connections'] = array(
        'max_user_connections',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['max_write_lock_count'] = array(
        'max_write_lock_count',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['memlock'] = array(
        'memlock',
        'server-options',
        'option_mysqld');
    $variable_doc_links['min_examined_row_limit'] = array(
        'min-examined-row-limit',
        'server-options',
        'option_mysqld');
    $variable_doc_links['myisam_data_pointer_size'] = array(
        'myisam_data_pointer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['myisam_max_sort_file_size'] = array(
        'myisam_max_sort_file_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['myisam_mmap_size'] = array(
        'myisam_mmap_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['myisam_recover_options'] = array(
        'myisam_recover_options',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['myisam_repair_threads'] = array(
        'myisam_repair_threads',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['myisam_sort_buffer_size'] = array(
        'myisam_sort_buffer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['myisam_stats_method'] = array(
        'myisam_stats_method',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['myisam_use_mmap'] = array(
        'myisam_use_mmap',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['named_pipe'] = array(
        'named_pipe',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['net_buffer_length'] = array(
        'net_buffer_length',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['net_read_timeout'] = array(
        'net_read_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['net_retry_count'] = array(
        'net_retry_count',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['net_write_timeout'] = array(
        'net_write_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['new'] = array(
        'new',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['old'] = array(
        'old',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['old_alter_table'] = array(
        'old-alter-table',
        'server-options',
        'option_mysqld');
    $variable_doc_links['old_passwords'] = array(
        'old-passwords',
        'server-options',
        'option_mysqld');
    $variable_doc_links['open_files_limit'] = array(
        'open-files-limit',
        'server-options',
        'option_mysqld');
    $variable_doc_links['optimizer_prune_level'] = array(
        'optimizer_prune_level',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['optimizer_search_depth'] = array(
        'optimizer_search_depth',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['optimizer_switch'] = array(
        'optimizer_switch',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['partition'] = array(
        'partition',
        'server-options',
        'option_mysqld');
    $variable_doc_links['performance_schema'] = array(
        'performance_schema',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_events_waits_history_long_size'] = array(
        'performance_schema_events_waits_history_long_size',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_events_waits_history_size'] = array(
        'performance_schema_events_waits_history_size',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_cond_classes'] = array(
        'performance_schema_max_cond_classes',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_cond_instances'] = array(
        'performance_schema_max_cond_instances',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_file_classes'] = array(
        'performance_schema_max_file_classes',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_file_handles'] = array(
        'performance_schema_max_file_handles',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_file_instances'] = array(
        'performance_schema_max_file_instances',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_mutex_classes'] = array(
        'performance_schema_max_mutex_classes',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_mutex_instances'] = array(
        'performance_schema_max_mutex_instances',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_rwlock_classes'] = array(
        'performance_schema_max_rwlock_classes',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_rwlock_instances'] = array(
        'performance_schema_max_rwlock_instances',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_table_handles'] = array(
        'performance_schema_max_table_handles',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_table_instances'] = array(
        'performance_schema_max_table_instances',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_thread_classes'] = array(
        'performance_schema_max_thread_classes',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['performance_schema_max_thread_instances'] = array(
        'performance_schema_max_thread_instances',
        'performance-schema-system-variables',
        'sysvar');
    $variable_doc_links['pid_file'] = array(
        'pid-file',
        'server-options',
        'option_mysqld');
    $variable_doc_links['plugin_dir'] = array(
        'plugin_dir',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['port'] = array(
        'port',
        'server-options',
        'option_mysqld');
    $variable_doc_links['preload_buffer_size'] = array(
        'preload_buffer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['profiling'] = array(
        'profiling',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['profiling_history_size'] = array(
        'profiling_history_size',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['protocol_version'] = array(
        'protocol_version',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['proxy_user'] = array(
        'proxy_user',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['pseudo_thread_id'] = array(
        'pseudo_thread_id',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['query_alloc_block_size'] = array(
        'query_alloc_block_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['query_cache_limit'] = array(
        'query_cache_limit',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['query_cache_min_res_unit'] = array(
        'query_cache_min_res_unit',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['query_cache_size'] = array(
        'query_cache_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['query_cache_type'] = array(
        'query_cache_type',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['query_cache_wlock_invalidate'] = array(
        'query_cache_wlock_invalidate',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['query_prealloc_size'] = array(
        'query_prealloc_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['rand_seed1'] = array(
        'rand_seed1',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['rand_seed2'] = array(
        'rand_seed2',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['range_alloc_block_size'] = array(
        'range_alloc_block_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['read_buffer_size'] = array(
        'read_buffer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['read_only'] = array(
        'read_only',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['read_rnd_buffer_size'] = array(
        'read_rnd_buffer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['relay-log-index'] = array(
        'relay-log-index',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['relay_log_index'] = array(
        'relay_log_index',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['relay_log_info_file'] = array(
        'relay_log_info_file',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['relay_log_purge'] = array(
        'relay_log_purge',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['relay_log_recovery'] = array(
        'relay_log_recovery',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['relay_log_space_limit'] = array(
        'relay_log_space_limit',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['report_host'] = array(
        'report-host',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['report_password'] = array(
        'report-password',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['report_port'] = array(
        'report-port',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['report_user'] = array(
        'report-user',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['rpl_recovery_rank'] = array(
        'rpl_recovery_rank',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['rpl_semi_sync_master_enabled'] = array(
        'rpl_semi_sync_master_enabled',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['rpl_semi_sync_master_timeout'] = array(
        'rpl_semi_sync_master_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['rpl_semi_sync_master_trace_level'] = array(
        'rpl_semi_sync_master_trace_level',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['rpl_semi_sync_master_wait_no_slave'] = array(
        'rpl_semi_sync_master_wait_no_slave',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['rpl_semi_sync_slave_enabled'] = array(
        'rpl_semi_sync_slave_enabled',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['rpl_semi_sync_slave_trace_level'] = array(
        'rpl_semi_sync_slave_trace_level',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['safe_show_database'] = array(
        'safe-show-database',
        'server-options',
        'option_mysqld');
    $variable_doc_links['secure_auth'] = array(
        'secure-auth',
        'server-options',
        'option_mysqld');
    $variable_doc_links['secure_file_priv'] = array(
        'secure-file-priv',
        'server-options',
        'option_mysqld');
    $variable_doc_links['server_id'] = array(
        'server-id',
        'replication-options',
        'option_mysqld');
    $variable_doc_links['shared_memory'] = array(
        'shared_memory',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['shared_memory_base_name'] = array(
        'shared_memory_base_name',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['skip_external_locking'] = array(
        'skip-external-locking',
        'server-options',
        'option_mysqld');
    $variable_doc_links['skip_name_resolve'] = array(
        'skip-name-resolve',
        'server-options',
        'option_mysqld');
    $variable_doc_links['skip_networking'] = array(
        'skip-networking',
        'server-options',
        'option_mysqld');
    $variable_doc_links['skip_show_database'] = array(
        'skip-show-database',
        'server-options',
        'option_mysqld');
    $variable_doc_links['slave_compressed_protocol'] = array(
        'slave_compressed_protocol',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['slave_exec_mode'] = array(
        'slave_exec_mode',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['slave_load_tmpdir'] = array(
        'slave-load-tmpdir',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['slave_net_timeout'] = array(
        'slave-net-timeout',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['slave_skip_errors'] = array(
        'slave-skip-errors',
        'replication-options-slave',
        'option_mysqld');
    $variable_doc_links['slave_transaction_retries'] = array(
        'slave_transaction_retries',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['slave_type_conversions'] = array(
        'slave_type_conversions',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['slow_launch_time'] = array(
        'slow_launch_time',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['slow_query_log'] = array(
        'slow-query-log',
        'server-options',
        'server-system-variables');
    $variable_doc_links['slow_query_log_file'] = array(
        'slow_query_log_file',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['socket'] = array(
        'socket',
        'server-options',
        'option_mysqld');
    $variable_doc_links['sort_buffer_size'] = array(
        'sort_buffer_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['sql_auto_is_null'] = array(
        'sql_auto_is_null',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_big_selects'] = array(
        'sql_big_selects',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_big_tables'] = array(
        'big-tables',
        'server-options',
        'server-system-variables');
    $variable_doc_links['sql_buffer_result'] = array(
        'sql_buffer_result',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_log_bin'] = array(
        'sql_log_bin',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_log_off'] = array(
        'sql_log_off',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_log_update'] = array(
        'sql_log_update',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_low_priority_updates'] = array(
        'sql_low_priority_updates',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_max_join_size'] = array(
        'sql_max_join_size',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_mode'] = array(
        'sql-mode',
        'server-options',
        'option_mysqld');
    $variable_doc_links['sql_notes'] = array(
        'sql_notes',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_quote_show_create'] = array(
        'sql_quote_show_create',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_safe_updates'] = array(
        'sql_safe_updates',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_select_limit'] = array(
        'sql_select_limit',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sql_slave_skip_counter'] = array(
        'sql_slave_skip_counter',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['sql_warnings'] = array(
        'sql_warnings',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['ssl_ca'] = array(
        'ssl-ca',
        'ssl-options',
        'option_general');
    $variable_doc_links['ssl_capath'] = array(
        'ssl-capath',
        'ssl-options',
        'option_general');
    $variable_doc_links['ssl_cert'] = array(
        'ssl-cert',
        'ssl-options',
        'option_general');
    $variable_doc_links['ssl_cipher'] = array(
        'ssl-cipher',
        'ssl-options',
        'option_general');
    $variable_doc_links['ssl_key'] = array(
        'ssl-key',
        'ssl-options',
        'option_general');
    $variable_doc_links['storage_engine'] = array(
        'storage_engine',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sync_binlog'] = array(
        'sync_binlog',
        'replication-options-binary-log',
        'sysvar');
    $variable_doc_links['sync_frm'] = array(
        'sync_frm',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['sync_master_info'] = array(
        'sync_master_info',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['sync_relay_log'] = array(
        'sync_relay_log',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['sync_relay_log_info'] = array(
        'sync_relay_log_info',
        'replication-options-slave',
        'sysvar');
    $variable_doc_links['system_time_zone'] = array(
        'system_time_zone',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['table_definition_cache'] = array(
        'table_definition_cache',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['table_lock_wait_timeout'] = array(
        'table_lock_wait_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['table_open_cache'] = array(
        'table_open_cache',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['table_type'] = array(
        'table_type',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['thread_cache_size'] = array(
        'thread_cache_size',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['thread_concurrency'] = array(
        'thread_concurrency',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['thread_handling'] = array(
        'thread_handling',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['thread_stack'] = array(
        'thread_stack',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['time_format'] = array(
        'time_format',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['time_zone'] = array(
        'time_zone',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['timed_mutexes'] = array(
        'timed_mutexes',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['timestamp'] = array(
        'timestamp',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['tmp_table_size'] = array(
        'tmp_table_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['tmpdir'] = array(
        'tmpdir',
        'server-options',
        'option_mysqld');
    $variable_doc_links['transaction_alloc_block_size'] = array(
        'transaction_alloc_block_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['transaction_prealloc_size'] = array(
        'transaction_prealloc_size',
        'server-system-variables',
        'sysvar',
        'byte');
    $variable_doc_links['tx_isolation'] = array(
        'tx_isolation',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['unique_checks'] = array(
        'unique_checks',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['updatable_views_with_limit'] = array(
        'updatable_views_with_limit',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['version'] = array(
        'version',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['version_comment'] = array(
        'version_comment',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['version_compile_machine'] = array(
        'version_compile_machine',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['version_compile_os'] = array(
        'version_compile_os',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['wait_timeout'] = array(
        'wait_timeout',
        'server-system-variables',
        'sysvar');
    $variable_doc_links['warning_count'] = array(
        'warning_count',
        'server-system-variables',
        'sysvar');
    return $variable_doc_links;
}

?>
