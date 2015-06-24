<?php

/**
 * Context for MySQL 5.
 *
 * @link https://dev.mysql.com/doc/refman/5.0/en/keywords.html
 */

namespace SqlParser\Contexts;

use SqlParser\Context;

/**
 * Context for MySQL 5.
 *
 * @category   Contexts
 * @package    SqlParser
 * @subpackage Contexts
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ContextMySql50000 extends Context
{

    /**
     * List of keywords.
     *
     * The value associated to each keyword represents its flags.
     *
     * @see Token::FLAG_KEYWORD_*
     *
     * @var array
     */
    public static $KEYWORDS = array(

        'DO' => 1, 'IO' => 1, 'NO' => 1, 'XA' => 1,
        'ANY' => 1, 'AVG' => 1, 'BDB' => 1, 'BIT' => 1, 'CPU' => 1, 'DAY' => 1,
        'END' => 1, 'IPC' => 1, 'NDB' => 1, 'NEW' => 1, 'ONE' => 1, 'ROW' => 1,
        'BOOL' => 1, 'BYTE' => 1, 'CODE' => 1, 'CUBE' => 1, 'DATA' => 1, 'FAST' => 1,
        'FILE' => 1, 'FULL' => 1, 'HASH' => 1, 'HELP' => 1, 'HOUR' => 1, 'LAST' => 1,
        'LOGS' => 1, 'MODE' => 1, 'NAME' => 1, 'NEXT' => 1, 'NONE' => 1, 'OPEN' => 1,
        'PAGE' => 1, 'PREV' => 1, 'ROWS' => 1, 'SOME' => 1, 'STOP' => 1, 'TYPE' => 1,
        'USER' => 1, 'VIEW' => 1, 'WEEK' => 1, 'WORK' => 1, 'X509' => 1,
        'AFTER' => 1, 'ASCII' => 1, 'BEGIN' => 1, 'BLOCK' => 1, 'BTREE' => 1,
        'CACHE' => 1, 'CHAIN' => 1, 'CLOSE' => 1, 'FIRST' => 1, 'FIXED' => 1,
        'FLUSH' => 1, 'FOUND' => 1, 'HOSTS' => 1, 'LEVEL' => 1, 'LOCAL' => 1,
        'LOCKS' => 1, 'MERGE' => 1, 'MONTH' => 1, 'MUTEX' => 1, 'NAMES' => 1,
        'NCHAR' => 1, 'PHASE' => 1, 'POINT' => 1, 'QUERY' => 1, 'QUICK' => 1,
        'RAID0' => 1, 'RESET' => 1, 'RTREE' => 1, 'SHARE' => 1, 'SLAVE' => 1,
        'START' => 1, 'SUPER' => 1, 'SWAPS' => 1, 'TYPES' => 1, 'UNTIL' => 1,
        'VALUE' => 1,
        'ACTION' => 1, 'BACKUP' => 1, 'BINLOG' => 1, 'CIPHER' => 1, 'CLIENT' => 1,
        'COMMIT' => 1, 'ENABLE' => 1, 'ENGINE' => 1, 'ERRORS' => 1, 'ESCAPE' => 1,
        'EVENTS' => 1, 'FAULTS' => 1, 'FIELDS' => 1, 'GLOBAL' => 1, 'GRANTS' => 1,
        'IMPORT' => 1, 'INNODB' => 1, 'ISSUER' => 1, 'LEAVES' => 1, 'MASTER' => 1,
        'MEDIUM' => 1, 'MEMORY' => 1, 'MINUTE' => 1, 'MODIFY' => 1, 'OFFSET' => 1,
        'RELOAD' => 1, 'REPAIR' => 1, 'RESUME' => 1, 'ROLLUP' => 1, 'SECOND' => 1,
        'SERIAL' => 1, 'SIGNED' => 1, 'SIMPLE' => 1, 'SOUNDS' => 1, 'SOURCE' => 1,
        'STATUS' => 1, 'STRING' => 1, 'TABLES' => 1,
        'AGAINST' => 1, 'CHANGED' => 1, 'CHARSET' => 1, 'COLUMNS' => 1, 'COMMENT' => 1,
        'COMPACT' => 1, 'CONTEXT' => 1, 'DEFINER' => 1, 'DISABLE' => 1, 'DISCARD' => 1,
        'DYNAMIC' => 1, 'ENGINES' => 1, 'EXECUTE' => 1, 'HANDLER' => 1, 'INDEXES' => 1,
        'INVOKER' => 1, 'MIGRATE' => 1, 'PARTIAL' => 1, 'POLYGON' => 1, 'PREPARE' => 1,
        'PROFILE' => 1, 'QUARTER' => 1, 'RECOVER' => 1, 'RESTORE' => 1, 'RETURNS' => 1,
        'ROUTINE' => 1, 'SESSION' => 1, 'STORAGE' => 1, 'STRIPED' => 1, 'SUBJECT' => 1,
        'SUSPEND' => 1, 'UNICODE' => 1, 'UNKNOWN' => 1, 'UPGRADE' => 1, 'USE_FRM' => 1,
        'CASCADED' => 1, 'CHECKSUM' => 1, 'CONTAINS' => 1, 'DUMPFILE' => 1,
        'EXTENDED' => 1, 'FUNCTION' => 1, 'GEOMETRY' => 1, 'INNOBASE' => 1,
        'LANGUAGE' => 1, 'MAX_ROWS' => 1, 'MIN_ROWS' => 1, 'NATIONAL' => 1,
        'NVARCHAR' => 1, 'ONE_SHOT' => 1, 'PASSWORD' => 1, 'PROFILES' => 1,
        'ROLLBACK' => 1, 'SECURITY' => 1, 'SHUTDOWN' => 1, 'SNAPSHOT' => 1,
        'SWITCHES' => 1, 'TRIGGERS' => 1, 'TRUNCATE' => 1, 'WARNINGS' => 1,
        'AGGREGATE' => 1, 'ALGORITHM' => 1, 'COLLATION' => 1, 'COMMITTED' => 1,
        'DIRECTORY' => 1, 'DUPLICATE' => 1, 'EXPANSION' => 1, 'IO_THREAD' => 1,
        'ISOLATION' => 1, 'PACK_KEYS' => 1, 'RAID_TYPE' => 1, 'REDUNDANT' => 1,
        'SAVEPOINT' => 1, 'SQL_CACHE' => 1, 'TEMPORARY' => 1, 'TEMPTABLE' => 1,
        'UNDEFINED' => 1, 'VARIABLES' => 1,
        'BERKELEYDB' => 1, 'COMPRESSED' => 1, 'CONCURRENT' => 1, 'CONNECTION' => 1,
        'CONSISTENT' => 1, 'DEALLOCATE' => 1, 'GET_FORMAT' => 1, 'IDENTIFIED' => 1,
        'LINESTRING' => 1, 'MASTER_SSL' => 1, 'MULTIPOINT' => 1, 'NDBCLUSTER' => 1,
        'PRIVILEGES' => 1, 'REPEATABLE' => 1, 'ROW_FORMAT' => 1, 'SQL_THREAD' => 1,
        'TABLESPACE' => 1,
        'FRAC_SECOND' => 1, 'MASTER_HOST' => 1, 'MASTER_PORT' => 1, 'MASTER_USER' => 1,
        'MICROSECOND' => 1, 'PROCESSLIST' => 1, 'RAID_CHUNKS' => 1, 'REPLICATION' => 1,
        'SQL_TSI_DAY' => 1, 'TRANSACTION' => 1, 'UNCOMMITTED' => 1,
        'DES_KEY_FILE' => 1, 'MULTIPOLYGON' => 1, 'OLD_PASSWORD' => 1,
        'RELAY_THREAD' => 1, 'SERIALIZABLE' => 1, 'SQL_NO_CACHE' => 1,
        'SQL_TSI_HOUR' => 1, 'SQL_TSI_WEEK' => 1, 'SQL_TSI_YEAR' => 1,
        'TIMESTAMPADD' => 1,
        'INSERT_METHOD' => 1, 'MASTER_SSL_CA' => 1, 'RELAY_LOG_POS' => 1,
        'SQL_TSI_MONTH' => 1, 'TIMESTAMPDIFF' => 1,
        'AUTO_INCREMENT' => 1, 'AVG_ROW_LENGTH' => 1, 'MASTER_LOG_POS' => 1,
        'MASTER_SSL_KEY' => 1, 'RAID_CHUNKSIZE' => 1, 'RELAY_LOG_FILE' => 1,
        'SQL_TSI_MINUTE' => 1, 'SQL_TSI_SECOND' => 1, 'USER_RESOURCES' => 1,
        'DELAY_KEY_WRITE' => 1, 'MASTER_LOG_FILE' => 1, 'MASTER_PASSWORD' => 1,
        'MASTER_SSL_CERT' => 1, 'MULTILINESTRING' => 1, 'SQL_TSI_QUARTER' => 1,
        'MASTER_SERVER_ID' => 1,
        'MASTER_SSL_CAPATH' => 1, 'MASTER_SSL_CIPHER' => 1, 'SQL_BUFFER_RESULT' => 1,
        'GEOMETRYCOLLECTION' => 1,
        'SQL_TSI_FRAC_SECOND' => 1,
        'MASTER_CONNECT_RETRY' => 1, 'MAX_QUERIES_PER_HOUR' => 1,
        'MAX_UPDATES_PER_HOUR' => 1, 'MAX_USER_CONNECTIONS' => 1,
        'MAX_CONNECTIONS_PER_HOUR' => 1,

        'AS' => 3, 'BY' => 3, 'IF' => 3, 'IN' => 3, 'IS' => 3, 'ON' => 3, 'OR' => 3,
        'TO' => 3,
        'ADD' => 3, 'ALL' => 3, 'AND' => 3, 'ASC' => 3, 'DEC' => 3, 'DIV' => 3,
        'FOR' => 3, 'MOD' => 3, 'NOT' => 3, 'OUT' => 3, 'SQL' => 3, 'SSL' => 3,
        'USE' => 3, 'XOR' => 3,
        'BOTH' => 3, 'CALL' => 3, 'CASE' => 3, 'DESC' => 3, 'DROP' => 3, 'DUAL' => 3,
        'EACH' => 3, 'ELSE' => 3, 'EXIT' => 3, 'FROM' => 3, 'INT1' => 3, 'INT2' => 3,
        'INT3' => 3, 'INT4' => 3, 'INT8' => 3, 'INTO' => 3, 'JOIN' => 3, 'KEYS' => 3,
        'KILL' => 3, 'LEFT' => 3, 'LIKE' => 3, 'LOAD' => 3, 'LOCK' => 3, 'LONG' => 3,
        'LOOP' => 3, 'NULL' => 3, 'READ' => 3, 'SHOW' => 3, 'THEN' => 3, 'TRUE' => 3,
        'UNDO' => 3, 'WHEN' => 3, 'WITH' => 3,
        'ALTER' => 3, 'CHECK' => 3, 'CROSS' => 3, 'FALSE' => 3, 'FETCH' => 3,
        'FORCE' => 3, 'GRANT' => 3, 'GROUP' => 3, 'INNER' => 3, 'INOUT' => 3,
        'LEAVE' => 3, 'LIMIT' => 3, 'LINES' => 3, 'MATCH' => 3, 'ORDER' => 3,
        'OUTER' => 3, 'PURGE' => 3, 'READS' => 3, 'RIGHT' => 3, 'RLIKE' => 3,
        'TABLE' => 3, 'UNION' => 3, 'USAGE' => 3, 'USING' => 3, 'WHERE' => 3,
        'WHILE' => 3, 'WRITE' => 3,
        'BEFORE' => 3, 'CHANGE' => 3, 'COLUMN' => 3, 'CREATE' => 3, 'CURSOR' => 3,
        'DELETE' => 3, 'ELSEIF' => 3, 'EXISTS' => 3, 'FLOAT4' => 3, 'FLOAT8' => 3,
        'HAVING' => 3, 'IGNORE' => 3, 'INFILE' => 3, 'INSERT' => 3, 'OPTION' => 3,
        'REGEXP' => 3, 'RENAME' => 3, 'REPEAT' => 3, 'RETURN' => 3, 'REVOKE' => 3,
        'SCHEMA' => 3, 'SELECT' => 3, 'SONAME' => 3, 'UNLOCK' => 3, 'UPDATE' => 3,
        'VALUES' => 3,
        'ANALYZE' => 3, 'BETWEEN' => 3, 'CASCADE' => 3, 'COLLATE' => 3, 'CONVERT' => 3,
        'DECLARE' => 3, 'DEFAULT' => 3, 'DELAYED' => 3, 'ESCAPED' => 3, 'EXPLAIN' => 3,
        'FOREIGN' => 3, 'ITERATE' => 3, 'LEADING' => 3, 'NATURAL' => 3, 'OUTFILE' => 3,
        'PRIMARY' => 3, 'RELEASE' => 3, 'REPLACE' => 3, 'REQUIRE' => 3, 'SCHEMAS' => 3,
        'SPATIAL' => 3, 'TRIGGER' => 3, 'VARYING' => 3,
        'CONTINUE' => 3, 'DATABASE' => 3, 'DAY_HOUR' => 3, 'DESCRIBE' => 3,
        'DISTINCT' => 3, 'ENCLOSED' => 3, 'FULLTEXT' => 3, 'MODIFIES' => 3,
        'OPTIMIZE' => 3, 'RESTRICT' => 3, 'SPECIFIC' => 3, 'SQLSTATE' => 3,
        'STARTING' => 3, 'TRAILING' => 3, 'UNSIGNED' => 3, 'UTC_DATE' => 3,
        'UTC_TIME' => 3, 'ZEROFILL' => 3,
        'CONDITION' => 3, 'DATABASES' => 3, 'LOCALTIME' => 3, 'MIDDLEINT' => 3,
        'PRECISION' => 3, 'PROCEDURE' => 3, 'SENSITIVE' => 3, 'SEPARATOR' => 3,
        'ASENSITIVE' => 3, 'CONSTRAINT' => 3, 'DAY_MINUTE' => 3, 'DAY_SECOND' => 3,
        'OPTIONALLY' => 3, 'REFERENCES' => 3, 'SQLWARNING' => 3, 'TERMINATED' => 3,
        'YEAR_MONTH' => 3,
        'DISTINCTROW' => 3, 'HOUR_MINUTE' => 3, 'HOUR_SECOND' => 3, 'INSENSITIVE' => 3,
        'CURRENT_DATE' => 3, 'CURRENT_TIME' => 3, 'CURRENT_USER' => 3,
        'LOW_PRIORITY' => 3, 'SQLEXCEPTION' => 3, 'VARCHARACTER' => 3,
        'DETERMINISTIC' => 3, 'HIGH_PRIORITY' => 3, 'MINUTE_SECOND' => 3,
        'STRAIGHT_JOIN' => 3, 'UTC_TIMESTAMP' => 3,
        'LOCALTIMESTAMP' => 3, 'SQL_BIG_RESULT' => 3,
        'DAY_MICROSECOND' => 3,
        'HOUR_MICROSECOND' => 3, 'SQL_SMALL_RESULT' => 3,
        'CURRENT_TIMESTAMP' => 3,
        'MINUTE_MICROSECOND' => 3, 'NO_WRITE_TO_BINLOG' => 3, 'SECOND_MICROSECOND' => 3,
        'SQL_CALC_FOUND_ROWS' => 3,

        'NOT NULL' => 5, 'SET NULL' => 5,
        'NO ACTION' => 5, 'ON DELETE' => 5, 'ON UPDATE' => 5,
        'CHARACTER SET' => 5, 'IF NOT EXISTS' => 5,
        'DATA DIRECTORY' => 5,
        'DEFAULT COLLATE' => 5, 'INDEX DIRECTORY' => 5,
        'DEFAULT CHARACTER SET' => 5,

        'GROUP BY' => 7, 'ORDER BY' => 7,

        'XML' => 9,
        'DATE' => 9, 'ENUM' => 9, 'TEXT' => 9, 'TIME' => 9, 'YEAR' => 9,
        'ARRAY' => 9,
        'BOOLEAN' => 9,
        'DATETIME' => 9, 'MULTISET' => 9,
        'TIMESTAMP' => 9,

        'INT' => 11, 'SET' => 11,
        'BLOB' => 11, 'CHAR' => 11, 'REAL' => 11,
        'FLOAT' => 11,
        'BIGINT' => 11, 'BINARY' => 11, 'DOUBLE' => 11,
        'DECIMAL' => 11, 'INTEGER' => 11, 'NUMERIC' => 11, 'TINYINT' => 11, 'VARCHAR' => 11,
        'INTERVAL' => 11, 'LONGBLOB' => 11, 'LONGTEXT' => 11, 'SMALLINT' => 11,
        'TINYBLOB' => 11, 'TINYTEXT' => 11,
        'CHARACTER' => 11, 'MEDIUMINT' => 11, 'VARBINARY' => 11,
        'MEDIUMBLOB' => 11, 'MEDIUMTEXT' => 11,

        'BINARY VARYING' => 13,

        'KEY' => 19,
        'INDEX' => 19,
        'UNIQUE' => 19,

        'INDEX KEY' => 21,
        'UNIQUE KEY' => 21,
        'FOREIGN KEY' => 21, 'PRIMARY KEY' => 21, 'SPATIAL KEY' => 21,
        'FULLTEXT KEY' => 21, 'UNIQUE INDEX' => 21,
        'SPATIAL INDEX' => 21,
        'FULLTEXT INDEX' => 21,

    );
}
