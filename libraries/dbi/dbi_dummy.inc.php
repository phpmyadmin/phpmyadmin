<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Fake database driver for testing purposes
 *
 * It has hardcoded results for given queries what makes easy to use it
 * in testsuite. Feel free to include other queries which your test will
 * need.
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Dummy
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Array of queries this "driver" supports
 */
$GLOBALS['dummy_queries'] = array(
    array('query' => 'SELECT 1', 'result' => array(array('1'))),
    array(
        'query'  => 'SELECT CURRENT_USER();',
        'result' => array(array('pma_test@localhost')),
    ),
    array(
        'query'  => "SHOW VARIABLES LIKE 'lower_case_table_names'",
        'result' => array(array('lower_case_table_names', '1')),
    ),
    array(
        'query'  => 'SELECT 1 FROM mysql.user LIMIT 1',
        'result' => array(array('1')),
    ),
    array(
        'query'  => "SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`"
            . " WHERE `PRIVILEGE_TYPE` = 'CREATE USER'"
            . " AND '''pma_test''@''localhost''' LIKE `GRANTEE` LIMIT 1",
        'result' => array(array('1')),
    ),
    array(
        'query'  => "SELECT 1 FROM (SELECT `GRANTEE`, `IS_GRANTABLE`"
            . " FROM `INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES`"
            . " UNION SELECT `GRANTEE`, `IS_GRANTABLE`"
            . " FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES`"
            . " UNION SELECT `GRANTEE`, `IS_GRANTABLE`"
            . " FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`"
            . " UNION SELECT `GRANTEE`, `IS_GRANTABLE`"
            . " FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t"
            . " WHERE `IS_GRANTABLE` = 'YES'"
            . " AND '''pma_test''@''localhost''' LIKE `GRANTEE` LIMIT 1",
        'result' => array(array('1')),
    ),
    array(
        'query'  => 'SHOW MASTER LOGS',
        'result' => false,
    ),
    array(
        'query'  => 'SHOW STORAGE ENGINES',
        'result' => array(
            array(
                'Engine'  => 'dummy',
                'Support' => 'YES',
                'Comment' => 'dummy comment',
            ),
            array(
                'Engine'  => 'dummy2',
                'Support' => 'NO',
                'Comment' => 'dummy2 comment',
            ),
            array(
                'Engine'  => 'FEDERATED',
                'Support' => 'NO',
                'Comment' => 'Federated MySQL storage engine',
            ),
        ),
    ),
    array(
        'query'  => 'SHOW STATUS WHERE Variable_name'
            . ' LIKE \'Innodb\\_buffer\\_pool\\_%\''
            . ' OR Variable_name = \'Innodb_page_size\';',
        'result' => array(
            array('Innodb_buffer_pool_pages_data', 0),
            array('Innodb_buffer_pool_pages_dirty', 0),
            array('Innodb_buffer_pool_pages_flushed', 0),
            array('Innodb_buffer_pool_pages_free', 0),
            array('Innodb_buffer_pool_pages_misc', 0),
            array('Innodb_buffer_pool_pages_total', 4096),
            array('Innodb_buffer_pool_read_ahead_rnd', 0),
            array('Innodb_buffer_pool_read_ahead', 0),
            array('Innodb_buffer_pool_read_ahead_evicted', 0),
            array('Innodb_buffer_pool_read_requests', 64),
            array('Innodb_buffer_pool_reads', 32),
            array('Innodb_buffer_pool_wait_free', 0),
            array('Innodb_buffer_pool_write_requests', 64),
            array('Innodb_page_size', 16384),
        ),
    ),
    array(
        'query'  => 'SHOW ENGINE INNODB STATUS;',
        'result' => false,
    ),
    array(
        'query'  => 'SELECT @@innodb_version;',
        'result' => array(
            array('1.1.8'),
        ),
    ),
    array(
        'query'  => 'SELECT @@disabled_storage_engines',
        'result' => array(
            array(''),
        ),
    ),
    array(
        'query'  => 'SHOW GLOBAL VARIABLES LIKE \'innodb_file_per_table\';',
        'result' => array(
            array('innodb_file_per_table', 'OFF'),
        ),
    ),
    array(
        'query'  => 'SHOW GLOBAL VARIABLES LIKE \'innodb_file_format\';',
        'result' => array(
            array('innodb_file_format', 'Antelope'),
        ),
    ),
    array(
        'query'  => 'SELECT @@collation_server',
        'result' => array(
            array('utf8_general_ci'),
        ),
    ),
    array(
        'query'  => 'SELECT @@lc_messages;',
        'result' => array(),
    ),
    array(
        'query'  => 'SHOW SESSION VARIABLES LIKE \'FOREIGN_KEY_CHECKS\';',
        'result' => array(
            array('foreign_key_checks', 'ON'),
        ),
    ),
    array(
        'query'  => 'SHOW TABLES FROM `pma_test`;',
        'result' => array(
            array('table1'),
            array('table2'),
        ),
    ),
    array(
        'query'  => 'SHOW TABLES FROM `pmadb`',
        'result' => array(
            array('column_info'),
        ),
    ),
    array(
        'query'   => 'SHOW COLUMNS FROM `pma_test`.`table1`',
        'columns' => array(
            'Field',
            'Type',
            'Null',
            'Key',
            'Default',
            'Extra',
        ),
        'result'  => array(
            array('i', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'),
            array('o', 'int(11)', 'NO', 'MUL', 'NULL', ''),
        ),
    ),
    array(
        'query'  => 'SHOW INDEXES FROM `pma_test`.`table1` WHERE (Non_unique = 0)',
        'result' => array(),
    ),
    array(
        'query'   => 'SHOW COLUMNS FROM `pma_test`.`table2`',
        'columns' => array(
            'Field',
            'Type',
            'Null',
            'Key',
            'Default',
            'Extra',
        ),
        'result'  => array(
            array('i', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'),
            array('o', 'int(11)', 'NO', 'MUL', 'NULL', ''),
        ),
    ),
    array(
        'query'  => 'SHOW INDEXES FROM `pma_test`.`table1`',
        'result' => array(),
    ),
    array(
        'query'  => 'SHOW INDEXES FROM `pma_test`.`table2`',
        'result' => array(),
    ),
    array(
        'query'   => 'SHOW COLUMNS FROM `pma`.`table1`',
        'columns' => array(
            'Field',
            'Type',
            'Null',
            'Key',
            'Default',
            'Extra',
            'Privileges',
            'Comment',
        ),
        'result'  => array(
            array(
                'i',
                'int(11)',
                'NO',
                'PRI',
                'NULL',
                'auto_increment',
                'select,insert,update,references',
                '',
            ),
            array(
                'o',
                'varchar(100)',
                'NO',
                'MUL',
                'NULL',
                '',
                'select,insert,update,references',
                '',
            ),
        ),
    ),
    array(
        'query'   => 'SELECT `CHARACTER_SET_NAME` AS `Charset`,'
            . ' `DESCRIPTION` AS `Description`'
            . ' FROM `information_schema`.`CHARACTER_SETS`',
        'columns' => array(
            'Charset',
            'Description',
        ),
        'result'  => array(
            array('utf8', 'UTF-8 Unicode'),
            array('latin1', 'cp1252 West European'),
        ),
    ),
    array(
        'query'   => 'SELECT `CHARACTER_SET_NAME` AS `Charset`,'
            . ' `COLLATION_NAME` AS `Collation`, `IS_DEFAULT` AS `Default`'
            . ' FROM `information_schema`.`COLLATIONS`',
        'columns' => array(
            'Charset',
            'Collation',
            'Default',
        ),
        'result'  => array(
            array('utf8', 'utf8_general_ci','Yes'),
            array('utf8', 'utf8_bin', ''),
            array('latin1', 'latin1_swedish_ci', 'Yes'),
        ),
    ),
    array(
        'query'  => 'SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES`'
            . ' WHERE `TABLE_SCHEMA`=\'pma_test\' AND `TABLE_TYPE`=\'BASE TABLE\'',
        'result' => array(),
    ),
    array(
        'query'   => 'SELECT `column_name`, `mimetype`, `transformation`,'
            . ' `transformation_options`, `input_transformation`,'
            . ' `input_transformation_options`'
            . ' FROM `pmadb`.`column_info`'
            . ' WHERE `db_name` = \'pma_test\' AND `table_name` = \'table1\''
            . ' AND ( `mimetype` != \'\' OR `transformation` != \'\''
            . ' OR `transformation_options` != \'\''
            . ' OR `input_transformation` != \'\''
            . ' OR `input_transformation_options` != \'\')',
        'columns' => array(
            'column_name',
            'mimetype',
            'transformation',
            'transformation_options',
            'input_transformation',
            'input_transformation_options',
        ),
        'result'  => array(
            array('o', 'text/plain', 'sql', '', 'regex', '/pma/i'),
            array('col', 't', 'o/p', '', 'i/p', ''),
        ),
    ),
    array(
        'query'  => 'SELECT TABLE_NAME FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'pma_test\' AND TABLE_NAME = \'table1\'',
        'result' => array(),
    ),
    array(
        'query'   => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
            . ' `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`,'
            . ' `ENGINE` AS `Type`, `VERSION` AS `Version`,'
            . ' `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`,'
            . ' `AVG_ROW_LENGTH` AS `Avg_row_length`,'
            . ' `DATA_LENGTH` AS `Data_length`,'
            . ' `MAX_DATA_LENGTH` AS `Max_data_length`,'
            . ' `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`,'
            . ' `AUTO_INCREMENT` AS `Auto_increment`,'
            . ' `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`,'
            . ' `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`,'
            . ' `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`,'
            . ' `TABLE_COMMENT` AS `Comment`'
            . ' FROM `information_schema`.`TABLES` t'
            . ' WHERE `TABLE_SCHEMA` IN (\'pma_test\')'
            . ' AND t.`TABLE_NAME` = \'table1\' ORDER BY Name ASC',
        'columns' => array(
            'TABLE_CATALOG',
            'TABLE_SCHEMA',
            'TABLE_NAME',
            'TABLE_TYPE',
            'ENGINE',
            'VERSION',
            'ROW_FORMAT',
            'TABLE_ROWS',
            'AVG_ROW_LENGTH',
            'DATA_LENGTH',
            'MAX_DATA_LENGTH',
            'INDEX_LENGTH',
            'DATA_FREE',
            'AUTO_INCREMENT',
            'CREATE_TIME',
            'UPDATE_TIME',
            'CHECK_TIME',
            'TABLE_COLLATION',
            'CHECKSUM',
            'CREATE_OPTIONS',
            'TABLE_COMMENT',
            'Db',
            'Name',
            'TABLE_TYPE',
            'Engine',
            'Type',
            'Version',
            'Row_format',
            'Rows',
            'Avg_row_length',
            'Data_length',
            'Max_data_length',
            'Index_length',
            'Data_free',
            'Auto_increment',
            'Create_time',
            'Update_time',
            'Check_time',
            'Collation',
            'Checksum',
            'Create_options',
            'Comment',
        ),
        'result'  => array(
            array(
                'def',
                'smash',
                'issues_issue',
                'BASE TABLE',
                'InnoDB',
                '10',
                'Compact',
                '9136',
                '862',
                '7880704',
                '0',
                '1032192',
                '420478976',
                '155862',
                '2012-08-29 13:28:28',
                'NULL',
                'NULL',
                'utf8_general_ci',
                'NULL',
                '',
                '',
                'smash',
                'issues_issue',
                'BASE TABLE',
                'InnoDB',
                'InnoDB',
                '10',
                'Compact',
                '9136',
                '862',
                '7880704',
                '0',
                '1032192',
                '420478976',
                '155862',
                '2012-08-29 13:28:28',
                'NULL',
                'NULL',
                'utf8_general_ci',
                'NULL',
            ),
        ),
    ),
    array(
        'query'   => 'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`,'
            . ' `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`,'
            . ' `ENGINE` AS `Type`, `VERSION` AS `Version`,'
            . ' `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`,'
            . ' `AVG_ROW_LENGTH` AS `Avg_row_length`,'
            . ' `DATA_LENGTH` AS `Data_length`,'
            . ' `MAX_DATA_LENGTH` AS `Max_data_length`,'
            . ' `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`,'
            . ' `AUTO_INCREMENT` AS `Auto_increment`,'
            . ' `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`,'
            . ' `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`,'
            . ' `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`,'
            . ' `TABLE_COMMENT` AS `Comment`'
            . ' FROM `information_schema`.`TABLES` t'
            . ' WHERE `TABLE_SCHEMA` IN (\'pma_test\')'
            . ' AND t.`TABLE_NAME` = \'table1\' ORDER BY Name ASC',
        'columns' => array(
            'TABLE_CATALOG',
            'TABLE_SCHEMA',
            'TABLE_NAME',
            'TABLE_TYPE',
            'ENGINE',
            'VERSION',
            'ROW_FORMAT',
            'TABLE_ROWS',
            'AVG_ROW_LENGTH',
            'DATA_LENGTH',
            'MAX_DATA_LENGTH',
            'INDEX_LENGTH',
            'DATA_FREE',
            'AUTO_INCREMENT',
            'CREATE_TIME',
            'UPDATE_TIME',
            'CHECK_TIME',
            'TABLE_COLLATION',
            'CHECKSUM',
            'CREATE_OPTIONS',
            'TABLE_COMMENT',
            'Db',
            'Name',
            'TABLE_TYPE',
            'Engine',
            'Type',
            'Version',
            'Row_format',
            'Rows',
            'Avg_row_length',
            'Data_length',
            'Max_data_length',
            'Index_length',
            'Data_free',
            'Auto_increment',
            'Create_time',
            'Update_time',
            'Check_time',
            'Collation',
            'Checksum',
            'Create_options',
            'Comment',
        ),
        'result'  => array(
            array(
                'def',
                'smash',
                'issues_issue',
                'BASE TABLE',
                'InnoDB',
                '10',
                'Compact',
                '9136',
                '862',
                '7880704',
                '0',
                '1032192',
                '420478976',
                '155862',
                '2012-08-29 13:28:28',
                'NULL',
                'NULL',
                'utf8_general_ci',
                'NULL',
                '',
                '',
                'smash',
                'issues_issue',
                'BASE TABLE',
                'InnoDB',
                'InnoDB',
                '10',
                'Compact',
                '9136',
                '862',
                '7880704',
                '0',
                '1032192',
                '420478976',
                '155862',
                '2012-08-29 13:28:28',
                'NULL',
                'NULL',
                'utf8_general_ci',
                'NULL',
            ),
        ),
    ),
    array(
        'query'  => 'SELECT COUNT(*) FROM `pma_test`.`table1`',
        'result' => array(array(0)),
    ),
    array(
        'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
            . '`USER_PRIVILEGES`'
            . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
            . ' AND PRIVILEGE_TYPE=\'TRIGGER\'',
        'result' => array(),
    ),
    array(
        'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
            . '`SCHEMA_PRIVILEGES`'
            . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
            . ' AND PRIVILEGE_TYPE=\'TRIGGER\' AND \'pma_test\''
            . ' LIKE `TABLE_SCHEMA`',
        'result' => array(),
    ),
    array(
        'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
            . '`TABLE_PRIVILEGES`'
            . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
            . ' AND PRIVILEGE_TYPE=\'TRIGGER\' AND \'pma_test\''
            . ' LIKE `TABLE_SCHEMA` AND TABLE_NAME=\'table1\'',
        'result' => array(),
    ),
    array(
        'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
            . '`USER_PRIVILEGES`'
            . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
            . ' AND PRIVILEGE_TYPE=\'EVENT\'',
        'result' => array(),
    ),
    array(
        'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
            . '`SCHEMA_PRIVILEGES`'
            . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
            . ' AND PRIVILEGE_TYPE=\'EVENT\' AND \'pma_test\''
            . ' LIKE `TABLE_SCHEMA`',
        'result' => array(),
    ),
    array(
        'query'  => 'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.'
            . '`TABLE_PRIVILEGES`'
            . ' WHERE GRANTEE=\'\'\'pma_test\'\'@\'\'localhost\'\'\''
            . ' AND PRIVILEGE_TYPE=\'EVENT\''
            . ' AND TABLE_SCHEMA=\'pma\\\\_test\' AND TABLE_NAME=\'table1\'',
        'result' => array(),
    ),
    array(
        'query'  => 'RENAME TABLE `pma_test`.`table1` TO `pma_test`.`table3`;',
        'result' => array(),
    ),
    array(
        'query'  => 'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION,'
            . ' EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT, '
            . 'EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER'
            . ' FROM information_schema.TRIGGERS'
            . ' WHERE EVENT_OBJECT_SCHEMA= \'pma_test\''
            . ' AND EVENT_OBJECT_TABLE = \'table1\';',
        'result' => array(),
    ),
    array(
        'query'  => 'SHOW TABLES FROM `pma`;',
        'result' => array(),
    ),
    array(
        'query'  => "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`."
            . "`SCHEMA_PRIVILEGES` WHERE GRANTEE='''pma_test''@''localhost'''"
            . " AND PRIVILEGE_TYPE='EVENT' AND TABLE_SCHEMA='pma'",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`."
            . "`SCHEMA_PRIVILEGES` WHERE GRANTEE='''pma_test''@''localhost'''"
            . " AND PRIVILEGE_TYPE='TRIGGER' AND TABLE_SCHEMA='pma'",
        'result' => array(),
    ),
    array(
        'query'   => 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA'
            . ' WHERE SCHEMA_NAME = \'pma_test\' LIMIT 1',
        'columns' => array('DEFAULT_COLLATION_NAME'),
        'result'  => array(
            array('utf8_general_ci'),
        ),
    ),
    array(
        'query'   => 'SELECT @@collation_database',
        'columns' => array('@@collation_database'),
        'result'  => array(
            array('bar'),
        ),
    ),
    array(
        'query'  => "SHOW TABLES FROM `phpmyadmin`",
        'result' => array(),
    ),
    array(
        'query'   => "SELECT tracking_active FROM `pmadb`.`tracking`" .
            " WHERE db_name = 'pma_test_db'" .
            " AND table_name = 'pma_test_table'" .
            " ORDER BY version DESC LIMIT 1",
        'columns' => array('tracking_active'),
        'result'  => array(
            array(1),
        ),
    ),
    array(
        'query'  => "SELECT tracking_active FROM `pmadb`.`tracking`" .
            " WHERE db_name = 'pma_test_db'" .
            " AND table_name = 'pma_test_table2'" .
            " ORDER BY version DESC LIMIT 1",
        'result' => array(),
    ),
    array(
        'query'  => "SHOW SLAVE STATUS",
        'result' => array(
            array(
                'Slave_IO_State'              => 'running',
                'Master_Host'                 => 'locahost',
                'Master_User'                 => 'Master_User',
                'Master_Port'                 => '1002',
                'Connect_Retry'               => 'Connect_Retry',
                'Master_Log_File'             => 'Master_Log_File',
                'Read_Master_Log_Pos'         => 'Read_Master_Log_Pos',
                'Relay_Log_File'              => 'Relay_Log_File',
                'Relay_Log_Pos'               => 'Relay_Log_Pos',
                'Relay_Master_Log_File'       => 'Relay_Master_Log_File',
                'Slave_IO_Running'            => 'NO',
                'Slave_SQL_Running'           => 'NO',
                'Replicate_Do_DB'             => 'Replicate_Do_DB',
                'Replicate_Ignore_DB'         => 'Replicate_Ignore_DB',
                'Replicate_Do_Table'          => 'Replicate_Do_Table',
                'Replicate_Ignore_Table'      => 'Replicate_Ignore_Table',
                'Replicate_Wild_Do_Table'     => 'Replicate_Wild_Do_Table',
                'Replicate_Wild_Ignore_Table' => 'Replicate_Wild_Ignore_Table',
                'Last_Errno'                  => 'Last_Errno',
                'Last_Error'                  => 'Last_Error',
                'Skip_Counter'                => 'Skip_Counter',
                'Exec_Master_Log_Pos'         => 'Exec_Master_Log_Pos',
                'Relay_Log_Space'             => 'Relay_Log_Space',
                'Until_Condition'             => 'Until_Condition',
                'Until_Log_File'              => 'Until_Log_File',
                'Until_Log_Pos'               => 'Until_Log_Pos',
                'Master_SSL_Allowed'          => 'Master_SSL_Allowed',
                'Master_SSL_CA_File'          => 'Master_SSL_CA_File',
                'Master_SSL_CA_Path'          => 'Master_SSL_CA_Path',
                'Master_SSL_Cert'             => 'Master_SSL_Cert',
                'Master_SSL_Cipher'           => 'Master_SSL_Cipher',
                'Master_SSL_Key'              => 'Master_SSL_Key',
                'Seconds_Behind_Master'       => 'Seconds_Behind_Master',
            ),
        ),
    ),
    array(
        'query'  => "SHOW MASTER STATUS",
        'result' => array(
            array(
                "File"             => "master-bin.000030",
                "Position"         => "107",
                "Binlog_Do_DB"     => "Binlog_Do_DB",
                "Binlog_Ignore_DB" => "Binlog_Ignore_DB",
            ),
        ),
    ),
    array(
        'query'  => "SHOW GRANTS",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, "
            . "(SELECT DB_first_level FROM ( SELECT DISTINCT "
            . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level "
            . "FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY "
            . "DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE("
            . "CONCAT(DB_first_level, '_'), CONCAT(SCHEMA_NAME, '_')) "
            . "ORDER BY SCHEMA_NAME ASC",
        'result' => array(
            "test",
        ),
    ),
    array(
        'query'  => "SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX("
            . "SCHEMA_NAME, '_', 1) DB_first_level "
            . "FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t",
        'result' => array(
            array(1),
        ),
    ),
    array(
        'query'  => "SELECT `PARTITION_METHOD` "
            . "FROM `information_schema`.`PARTITIONS` "
            . "WHERE `TABLE_SCHEMA` = 'db' AND `TABLE_NAME` = 'table' LIMIT 1",
        'result' => array(),
    ),
    array(
        'query'  => "SHOW PLUGINS",
        'result' => array(
            array('Name' => 'partition'),
        ),
    ),
    array(
        'query'  => "SHOW FULL TABLES FROM `default` WHERE `Table_type`='BASE TABLE'",
        'result' => array(
            array("test1", "BASE TABLE"),
            array("test2", "BASE TABLE"),
        ),
    ),
    array(
        'query'  => "SHOW FULL TABLES FROM `default` "
            . "WHERE `Table_type`!='BASE TABLE'",
        'result' => array(),
    ),
    array(
        'query'  => "SHOW FUNCTION STATUS WHERE `Db`='default'",
        'result' => array(array("Name" => "testFunction")),
    ),
    array(
        'query'  => "SHOW PROCEDURE STATUS WHERE `Db`='default'",
        'result' => array(),
    ),
    array(
        'query'  => "SHOW EVENTS FROM `default`",
        'result' => array(),
    ),
    array(
        'query'  => "FLUSH PRIVILEGES",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT * FROM `mysql`.`db` LIMIT 1",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT * FROM `mysql`.`columns_priv` LIMIT 1",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT * FROM `mysql`.`tables_priv` LIMIT 1",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT * FROM `mysql`.`procs_priv` LIMIT 1",
        'result' => array(),
    ),
    array(
        'query' => 'DELETE FROM `mysql`.`db` WHERE `host` = "" '
            . 'AND `Db` = "" AND `User` = ""',
        'result' => true
    ),
    array(
        'query' => 'DELETE FROM `mysql`.`columns_priv` WHERE '
            . '`host` = "" AND `Db` = "" AND `User` = ""',
        'result' => true
    ),
    array(
        'query' => 'DELETE FROM `mysql`.`tables_priv` WHERE '
            . '`host` = "" AND `Db` = "" AND `User` = "" AND Table_name = ""',
        'result' => true
    ),
    array(
        'query'  => 'DELETE FROM `mysql`.`procs_priv` WHERE '
            . '`host` = "" AND `Db` = "" AND `User` = "" AND `Routine_name` = "" '
            . 'AND `Routine_type` = ""',
        'result' => true
    ),
    array(
        'query' => 'SELECT `plugin` FROM `mysql`.`user` WHERE '
            . '`User` = "pma_username" AND `Host` = "pma_hostname" LIMIT 1',
        'result' => array()
    ),
    array(
        'query'  => 'SELECT @@default_authentication_plugin',
        'result' => array(
            array('@@default_authentication_plugin' => 'mysql_native_password'),
        ),
    ),
    array(
        'query'  => "SELECT TABLE_NAME FROM information_schema.VIEWS WHERE "
            . "TABLE_SCHEMA = 'db' AND TABLE_NAME = 'table'",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT *, `TABLE_SCHEMA` AS `Db`, "
            . "`TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, "
            . "`ENGINE` AS `Engine`, `ENGINE` AS `Type`, "
            . "`VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, "
            . "`TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, "
            . "`DATA_LENGTH` AS `Data_length`, "
            . "`MAX_DATA_LENGTH` AS `Max_data_length`, "
            . "`INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, "
            . "`AUTO_INCREMENT` AS `Auto_increment`, "
            . "`CREATE_TIME` AS `Create_time`, "
            . "`UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, "
            . "`TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, "
            . "`CREATE_OPTIONS` AS `Create_options`, "
            . "`TABLE_COMMENT` AS `Comment` "
            . "FROM `information_schema`.`TABLES` t "
            . "WHERE `TABLE_SCHEMA` IN ('db') "
            . "AND t.`TABLE_NAME` = 'table' ORDER BY Name ASC",
        'result' => array(),
    ),
    array(
        'query'  => "SHOW TABLE STATUS FROM `db` WHERE `Name` LIKE 'table%'",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT @@have_partitioning;",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT @@lower_case_table_names",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT `PLUGIN_NAME`, `PLUGIN_DESCRIPTION` "
            . "FROM `information_schema`.`PLUGINS` "
            . "WHERE `PLUGIN_TYPE` = 'AUTHENTICATION';",
        'result' => array(),
    ),
    array(
        'query'  => "SHOW TABLES FROM `db`;",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT `PRIVILEGE_TYPE` FROM "
            . "`INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` "
            . "WHERE GRANTEE='''pma_test''@''localhost''' "
            . "AND PRIVILEGE_TYPE='EVENT' AND 'db' LIKE `TABLE_SCHEMA`",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT `PRIVILEGE_TYPE` FROM "
            . "`INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` "
            . "WHERE GRANTEE='''pma_test''@''localhost''' "
            . "AND PRIVILEGE_TYPE='TRIGGER' AND 'db' LIKE `TABLE_SCHEMA`",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT (COUNT(DB_first_level) DIV 100) * 100 from "
            . "( SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) "
            . "DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA "
            . "WHERE `SCHEMA_NAME` < 'db' ) t",
        'result' => array(),
    ),
    array(
        'query'  => "SELECT `SCHEMA_NAME` FROM "
            . "`INFORMATION_SCHEMA`.`SCHEMATA`, "
            . "(SELECT DB_first_level FROM ( SELECT DISTINCT "
            . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level FROM "
            . "INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t "
            . "ORDER BY DB_first_level ASC LIMIT , 100) t2 WHERE TRUE AND "
            . "1 = LOCATE(CONCAT(DB_first_level, '_'), "
            . "CONCAT(SCHEMA_NAME, '_')) ORDER BY SCHEMA_NAME ASC",
        'result' => array(),
    ),
    array(
        'query' => 'SELECT @@ndb_version_string',
        'result' => array(array('ndb-7.4.10')),
    ),
    array(
        'query' => "SELECT *, `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`, `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`, `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`, `COLUMN_COMMENT` AS `Comment` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = 'information_schema' AND `TABLE_NAME` = 'PMA'",
        'result' => array(),
    ),
    [
        'query' => "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.key_column_usage WHERE referenced_table_name IS NOT NULL AND TABLE_SCHEMA = 'test' AND TABLE_NAME IN ('table1','table2') AND REFERENCED_TABLE_NAME IN ('table1','table2');",
        'result' => [
            [
                'TABLE_NAME' => 'table2',
                'COLUMN_NAME' => 'idtable2',
                'REFERENCED_TABLE_NAME' => 'table1',
                'REFERENCED_COLUMN_NAME' => 'idtable1',
            ]
        ],
    ],
);
/**
 * Current database.
 */
$GLOBALS['dummy_db'] = '';

/* Some basic setup for dummy driver */
$GLOBALS['cfg']['DBG']['sql'] = false;
