<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the improved MySQL extension (MySQLi)
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage MySQLi
 */

/**
 * Names of field flags.
 */
$GLOBALS['pma_mysqli_flag_names'] = array(
    MYSQLI_NUM_FLAG => 'num',
    MYSQLI_PART_KEY_FLAG => 'part_key',
    MYSQLI_SET_FLAG => 'set',
    MYSQLI_TIMESTAMP_FLAG => 'timestamp',
    MYSQLI_AUTO_INCREMENT_FLAG => 'auto_increment',
    MYSQLI_ENUM_FLAG => 'enum',
    MYSQLI_ZEROFILL_FLAG => 'zerofill',
    MYSQLI_UNSIGNED_FLAG => 'unsigned',
    MYSQLI_BLOB_FLAG => 'blob',
    MYSQLI_MULTIPLE_KEY_FLAG => 'multiple_key',
    MYSQLI_UNIQUE_KEY_FLAG => 'unique_key',
    MYSQLI_PRI_KEY_FLAG => 'primary_key',
    MYSQLI_NOT_NULL_FLAG => 'not_null',
);
