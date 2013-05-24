<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL Parser Matching Data
 *
 * Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
 * http://www.orbis-terrarum.net/?l=people.robbat2
 *
 * This data is used by the SQL Parser to recognize keywords
 *
 * It has been extracted from the lex.h file in the MySQL BK tree
 * (around 4.0.2) as well as the MySQL documentation.
 *
 * It's easier to use only uppercase for proper sorting. In case of
 * doubt, use the test case to verify.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (! isset($GLOBALS['sql_delimiter'])) {
        $GLOBALS['sql_delimiter'] = ';';
}

/**
 * @global array MySQL function names
 */
$PMA_SQPdata_function_name = array (
    'ABS',
    'ACOS',
    'ADDDATE',
    'ADDTIME',
    'AES_DECRYPT',
    'AES_ENCRYPT',
    'AREA',                     // polygon-property-functions.html
    'ASBINARY',
    'ASCII',
    'ASIN',
    'ASTEXT',
    'ATAN',
    'ATAN2',
    'AVG',
    'BDMPOLYFROMTEXT',
    'BDMPOLYFROMWKB',
    'BDPOLYFROMTEXT',
    'BDPOLYFROMWKB',
    'BENCHMARK',
    'BIN',
    'BIT_AND',
    'BIT_COUNT',
    'BIT_LENGTH',
    'BIT_OR',
    'BIT_XOR',                  // group-by-functions.html
    'BOUNDARY',                 // general-geometry-property-functions.html
    'BUFFER',
    'CAST',
    'CEIL',
    'CEILING',
    'CENTROID',                 // multipolygon-property-functions.html
    'CHAR',                     // string-functions.html
    'CHARACTER_LENGTH',
    'CHARSET',                  // information-functions.html
    'CHAR_LENGTH',
    'COALESCE',
    'COERCIBILITY',             // information-functions.html
    'COLLATION',                // information-functions.html
    'COMPRESS',                 // string-functions.html
    'CONCAT',
    'CONCAT_WS',
    'CONNECTION_ID',
    'CONTAINS',
    'CONV',
    'CONVERT',
    'CONVERT_TZ',
    'CONVEXHULL',
    'COS',
    'COT',
    'COUNT',
    'CRC32',                    // mathematical-functions.html
    'CROSSES',
    'CURDATE',
    'CURRENT_DATE',
    'CURRENT_TIME',
    'CURRENT_TIMESTAMP',
    'CURRENT_USER',
    'CURTIME',
    'DATABASE',
    'DATE',                     // date-and-time-functions.html
    'DATEDIFF',                 // date-and-time-functions.html
    'DATE_ADD',
    'DATE_DIFF',
    'DATE_FORMAT',
    'DATE_SUB',
    'DAY',
    'DAYNAME',
    'DAYOFMONTH',
    'DAYOFWEEK',
    'DAYOFYEAR',
    'DECODE',
    'DEFAULT',                  // miscellaneous-functions.html
    'DEGREES',
    'DES_DECRYPT',
    'DES_ENCRYPT',
    'DIFFERENCE',
    'DIMENSION',                // general-geometry-property-functions.html
    'DISJOINT',
    'DISTANCE',
    'ELT',
    'ENCODE',
    'ENCRYPT',
    'ENDPOINT',                 // linestring-property-functions.html
    'ENVELOPE',                 // general-geometry-property-functions.html
    'EQUALS',
    'EXP',
    'EXPORT_SET',
    'EXTERIORRING',             // polygon-property-functions.html
    'EXTRACT',
    'EXTRACTVALUE',             // xml-functions.html
    'FIELD',
    'FIND_IN_SET',
    'FLOOR',
    'FORMAT',
    'FOUND_ROWS',
    'FROM_DAYS',
    'FROM_UNIXTIME',
    'GEOMCOLLFROMTEXT',
    'GEOMCOLLFROMWKB',
    'GEOMETRYCOLLECTION',
    'GEOMETRYCOLLECTIONFROMTEXT',
    'GEOMETRYCOLLECTIONFROMWKB',
    'GEOMETRYFROMTEXT',
    'GEOMETRYFROMWKB',
    'GEOMETRYN',                // geometrycollection-property-functions.html
    'GEOMETRYTYPE',             // general-geometry-property-functions.html
    'GEOMFROMTEXT',
    'GEOMFROMWKB',
    'GET_FORMAT',
    'GET_LOCK',
    'GLENGTH',                  // linestring-property-functions.html
    'GREATEST',
    'GROUP_CONCAT',
    'GROUP_UNIQUE_USERS',
    'HEX',
    'HOUR',
    'IF',                       //control-flow-functions.html
    'IFNULL',
    'INET_ATON',
    'INET_NTOA',
    'INSERT',                   // string-functions.html
    'INSTR',
    'INTERIORRINGN',            // polygon-property-functions.html
    'INTERSECTION',
    'INTERSECTS',
    'INTERVAL',
    'ISCLOSED',                 // multilinestring-property-functions.html
    'ISEMPTY',                  // general-geometry-property-functions.html
    'ISNULL',
    'ISRING',                   // linestring-property-functions.html
    'ISSIMPLE',                 // general-geometry-property-functions.html
    'IS_FREE_LOCK',
    'IS_USED_LOCK',             // miscellaneous-functions.html
    'LAST_DAY',
    'LAST_INSERT_ID',
    'LCASE',
    'LEAST',
    'LEFT',
    'LENGTH',
    'LINEFROMTEXT',
    'LINEFROMWKB',
    'LINESTRING',
    'LINESTRINGFROMTEXT',
    'LINESTRINGFROMWKB',
    'LN',
    'LOAD_FILE',
    'LOCALTIME',
    'LOCALTIMESTAMP',
    'LOCATE',
    'LOG',
    'LOG10',
    'LOG2',
    'LOWER',
    'LPAD',
    'LTRIM',
    'MAKEDATE',
    'MAKETIME',
    'MAKE_SET',
    'MASTER_POS_WAIT',
    'MAX',
    'MBRCONTAINS',
    'MBRDISJOINT',
    'MBREQUAL',
    'MBRINTERSECTS',
    'MBROVERLAPS',
    'MBRTOUCHES',
    'MBRWITHIN',
    'MD5',
    'MICROSECOND',
    'MID',
    'MIN',
    'MINUTE',
    'MLINEFROMTEXT',
    'MLINEFROMWKB',
    'MOD',
    'MONTH',
    'MONTHNAME',
    'MPOINTFROMTEXT',
    'MPOINTFROMWKB',
    'MPOLYFROMTEXT',
    'MPOLYFROMWKB',
    'MULTILINESTRING',
    'MULTILINESTRINGFROMTEXT',
    'MULTILINESTRINGFROMWKB',
    'MULTIPOINT',
    'MULTIPOINTFROMTEXT',
    'MULTIPOINTFROMWKB',
    'MULTIPOLYGON',
    'MULTIPOLYGONFROMTEXT',
    'MULTIPOLYGONFROMWKB',
    'NAME_CONST',               // NAME_CONST()
    'NOW',
    'NULLIF',
    'NUMGEOMETRIES',            // geometrycollection-property-functions.html
    'NUMINTERIORRINGS',         // polygon-property-functions.html
    'NUMPOINTS',                // linestring-property-functions.html
    'OCT',
    'OCTET_LENGTH',
    'OLD_PASSWORD',
    'ORD',
    'OVERLAPS',
    'PASSWORD',
    'PERIOD_ADD',
    'PERIOD_DIFF',
    'PI',
    'POINT',
    'POINTFROMTEXT',
    'POINTFROMWKB',
    'POINTN',                   // inestring-property-functions.html
    'POINTONSURFACE',           // multipolygon-property-functions.html
    'POLYFROMTEXT',
    'POLYFROMWKB',
    'POLYGON',
    'POLYGONFROMTEXT',
    'POLYGONFROMWKB',
    'POSITION',
    'POW',
    'POWER',
    'QUARTER',
    'QUOTE',
    'RADIANS',
    'RAND',
    'RELATED',
    'RELEASE_LOCK',
    'REPEAT',
    'REPLACE',                  // string-functions.html
    'REVERSE',
    'RIGHT',
    'ROUND',
    'ROW_COUNT',                // information-functions.html
    'RPAD',
    'RTRIM',
    'SCHEMA',                   // information-functions.html
    'SECOND',
    'SEC_TO_TIME',
    'SESSION_USER',
    'SHA',
    'SHA1',
    'SIGN',
    'SIN',
    'SLEEP',                    // miscellaneous-functions.html
    'SOUNDEX',
    'SPACE',
    'SQRT',
    'SRID',                     // general-geometry-property-functions.html
    'STARTPOINT',               // linestring-property-functions.html
    'STD',
    'STDDEV',
    'STDDEV_POP',               // group-by-functions.html
    'STDDEV_SAMP',              // group-by-functions.html
    'STRCMP',
    'STR_TO_DATE',
    'SUBDATE',
    'SUBSTR',
    'SUBSTRING',
    'SUBSTRING_INDEX',
    'SUBTIME',
    'SUM',
    'SYMDIFFERENCE',
    'SYSDATE',
    'SYSTEM_USER',
    'TAN',
    'TIME',
    'TIMEDIFF',
    'TIMESTAMP',
    'TIMESTAMPADD',
    'TIMESTAMPDIFF',
    'TIME_FORMAT',
    'TIME_TO_SEC',
    'TOUCHES',
    'TO_DAYS',
    'TRIM',
    'TRUNCATE',                 // mathematical-functions.html
    'UCASE',
    'UNCOMPRESS',               // string-functions.html
    'UNCOMPRESSED_LENGTH',      // string-functions.html
    'UNHEX',                    // string-functions.html
    'UNIQUE_USERS',
    'UNIX_TIMESTAMP',
    'UPDATEXML',                // xml-functions.html
    'UPPER',
    'USER',
    'UTC_DATE',
    'UTC_TIME',
    'UTC_TIMESTAMP',
    'UUID',                     // miscellaneous-functions.html
    'VARIANCE',                 // group-by-functions.html
    'VAR_POP',                  // group-by-functions.html
    'VAR_SAMP',                 // group-by-functions.html
    'VERSION',
    'WEEK',
    'WEEKDAY',
    'WEEKOFYEAR',
    'WITHIN',
    'X',                        // point-property-functions.html
    'Y',                        // point-property-functions.html
    'YEAR',
    'YEARWEEK'
);

/**
 * @global array MySQL attributes
 */
$PMA_SQPdata_column_attrib = array (
    'ARCHIVE',          // Engine
    'ASCII',
    'AUTO_INCREMENT',
    'BDB',              // Engine
    'BERKELEYDB',       // Engine alias BDB
    'BINARY',
    'BLACKHOLE',        // Engine
    'CSV',              // Engine
    'DEFAULT',
    'EXAMPLE',          // Engine
    'FEDERATED',        // Engine
    'HEAP',             // Engine
    'INNOBASE',         // Engine alias InnoDB
    'INNODB',           // Engine InnoDB
    'ISAM',             // Engine
    'MARIA',            // Engine
    'MEMORY',           // Engine alias HEAP, but preferred
    'MERGE',            // Engine
    'MRG_ISAM',         // Engine
    'MRG_MYISAM',       // Engine alias MERGE
    'MYISAM',           // Engine MyISAM
    'NATIONAL',
    'NDB',              // Engine alias NDBCLUSTER
    'NDBCLUSTER',       // Engine
    'PRECISION',
    'UNDEFINED',
    'UNICODE',
    'UNSIGNED',
    'VARYING',
    'ZEROFILL'
);

/**
 * words that are reserved by MySQL and may not be used as identifiers without
 * quotes
 *
 * @see http://dev.mysql.com/doc/refman/5.5/en/reserved-words.html
 *
 * @global array MySQL reserved words
 */
$PMA_SQPdata_reserved_word = array (
    'ACCESSIBLE',       // 5.1
    'ACTION',
    'ADD',
    'AFTER',
    'AGAINST',
    'AGGREGATE',
    'ALGORITHM',
    'ALL',
    'ALTER',
    'ANALYSE',
    'ANALYZE',
    'AND',
    'AS',
    'ASC',
    'AUTOCOMMIT',
    'AUTO_INCREMENT',
    'AVG_ROW_LENGTH',
    'BACKUP',
    'BEGIN',
    'BETWEEN',
    'BINLOG',
    'BOTH',
    'BY',
    'CASCADE',
    'CASE',
    'CHANGE',
    'CHANGED',
    'CHARSET',
    'CHECK',
    'CHECKSUM',
    'COLLATE',
    'COLLATION',
    'COLUMN',
    'COLUMNS',
    'COMMENT',
    'COMMIT',
    'COMMITTED',
    'COMPRESSED',
    'CONCURRENT',
    'CONSTRAINT',
    'CONTAINS',
    'CONVERT',
    'CREATE',
    'CROSS',
    'CURRENT_TIMESTAMP',
    'DATABASE',
    'DATABASES',
    'DAY',
    'DAY_HOUR',
    'DAY_MINUTE',
    'DAY_SECOND',
    'DECLARE',
    'DEFINER',
    'DELAYED',
    'DELAY_KEY_WRITE',
    'DELETE',
    'DESC',
    'DESCRIBE',
    'DETERMINISTIC',
    'DISTINCT',
    'DISTINCTROW',
    'DIV',
    'DO',
    'DROP',
    'DUMPFILE',
    'DUPLICATE',
    'DYNAMIC',
    'ELSE',
    'ENCLOSED',
    'END',
    'ENGINE',
    'ENGINES',
    'ESCAPE',
    'ESCAPED',
    'EVENTS',
    'EXECUTE',
    'EXISTS',
    'EXPLAIN',
    'EXTENDED',
    'FALSE',
    'FAST',
    'FIELDS',
    'FILE',
    'FIRST',
    'FIXED',
    'FLUSH',
    'FOR',
    'FORCE',
    'FOREIGN',
    'FROM',
    'FULL',
    'FULLTEXT',
    'FUNCTION',
    'GEMINI',
    'GEMINI_SPIN_RETRIES',
    'GENERAL',
    'GLOBAL',
    'GRANT',
    'GRANTS',
    'GROUP',
    'HAVING',
    'HEAP',
    'HIGH_PRIORITY',
    'HOSTS',
    'HOUR',
    'HOUR_MINUTE',
    'HOUR_SECOND',
    'IDENTIFIED',
    'IF',
    'IGNORE',
    'IGNORE_SERVER_IDS',
    'IN',
    'INDEX',
    'INDEXES',
    'INFILE',
    'INNER',
    'INSERT',
    'INSERT_ID',
    'INSERT_METHOD',
    'INTERVAL',
    'INTO',
    'INVOKER',
    'IS',
    'ISOLATION',
    'JOIN',
    'KEY',
    'KEYS',
    'KILL',
    'LAST_INSERT_ID',
    'LEADING',
    'LEFT',
    'LIKE',
    'LIMIT',
    'LINEAR',               // 5.1
    'LINES',
    'LOAD',
    'LOCAL',
    'LOCK',
    'LOCKS',
    'LOGS',
    'LOW_PRIORITY',
    'MARIA',                // 5.1 ?
    'MASTER_CONNECT_RETRY',
    'MASTER_HEARTBEAT_PERIOD',
    'MASTER_HOST',
    'MASTER_LOG_FILE',
    'MASTER_LOG_POS',
    'MASTER_PASSWORD',
    'MASTER_PORT',
    'MASTER_USER',
    'MATCH',
    'MAXVALUE',
    'MAX_CONNECTIONS_PER_HOUR',
    'MAX_QUERIES_PER_HOUR',
    'MAX_ROWS',
    'MAX_UPDATES_PER_HOUR',
    'MAX_USER_CONNECTIONS',
    'MEDIUM',
    'MERGE',
    'MINUTE',
    'MINUTE_SECOND',
    'MIN_ROWS',
    'MODE',
    'MODIFY',
    'MONTH',
    'MRG_MYISAM',
    'MYISAM',
    'NAMES',
    'NATURAL',
    // 'NO' is not allowed in SQL-99 but is allowed in MySQL
    //'NO',
    'NOT',
    'NULL',
    'OFFSET',
    'ON',
    'OPEN',
    'OPTIMIZE',
    'OPTION',
    'OPTIONALLY',
    'OR',
    'ORDER',
    'OUTER',
    'OUTFILE',
    'PACK_KEYS',
    'PAGE',                 // 5.1-maria ?
    'PAGE_CHECKSUM',        // 5.1
    'PARTIAL',
    'PARTITION',            // 5.1
    'PARTITIONS',           // 5.1
    'PASSWORD',
    'PRIMARY',
    'PRIVILEGES',
    'PROCEDURE',
    'PROCESS',
    'PROCESSLIST',
    'PURGE',
    'QUICK',
    'RAID0',
    'RAID_CHUNKS',
    'RAID_CHUNKSIZE',
    'RAID_TYPE',
    'RANGE',                // 5.1
    'READ',
    'READ_ONLY',            // 5.1
    'READ_WRITE',           // 5.1
    'REFERENCES',
    'REGEXP',
    'RELOAD',
    'RENAME',
    'REPAIR',
    'REPEATABLE',
    'REPLACE',
    'REPLICATION',
    'RESET',
    'RESIGNAL',
    'RESTORE',
    'RESTRICT',
    'RETURN',
    'RETURNS',
    'REVOKE',
    'RIGHT',
    'RLIKE',
    'ROLLBACK',
    'ROW',
    'ROWS',
    'ROW_FORMAT',
    'SECOND',
    'SECURITY',
    'SELECT',
    'SEPARATOR',
    'SERIALIZABLE',
    'SESSION',
    'SHARE',
    'SHOW',
    'SHUTDOWN',
    'SIGNAL',
    'SLAVE',
    'SLOW',
    'SONAME',
    'SOUNDS',                   // string-functions.html
    'SQL',
    'SQL_AUTO_IS_NULL',
    'SQL_BIG_RESULT',
    'SQL_BIG_SELECTS',
    'SQL_BIG_TABLES',
    'SQL_BUFFER_RESULT',
    'SQL_CACHE',
    'SQL_CALC_FOUND_ROWS',
    'SQL_LOG_BIN',
    'SQL_LOG_OFF',
    'SQL_LOG_UPDATE',
    'SQL_LOW_PRIORITY_UPDATES',
    'SQL_MAX_JOIN_SIZE',
    'SQL_NO_CACHE',
    'SQL_QUOTE_SHOW_CREATE',
    'SQL_SAFE_UPDATES',
    'SQL_SELECT_LIMIT',
    'SQL_SLAVE_SKIP_COUNTER',
    'SQL_SMALL_RESULT',
    'SQL_WARNINGS',
    'START',
    'STARTING',
    'STATUS',
    'STOP',
    'STORAGE',
    'STRAIGHT_JOIN',
    'STRING',
    'STRIPED',
    'SUPER',
    'TABLE',
    'TABLES',
    'TEMPORARY',
    'TERMINATED',
    'THEN',
    'TO',
    'TRAILING',
    'TRANSACTIONAL',    // 5.1 ?
    'TRUE',
    'TRUNCATE',
    'TYPE',
    'TYPES',
    'UNCOMMITTED',
    'UNION',
    'UNIQUE',
    'UNLOCK',
    'UPDATE',
    'USAGE',
    'USE',
    'USING',
    'VALUES',
    'VARIABLES',
    'VIEW',
    'WHEN',
    'WHERE',
    'WITH',
    'WORK',
    'WRITE',
    'XOR',
    'YEAR_MONTH'
);

/**
 * words forbidden to be used as column or table name without quotes
 * as seen in http://dev.mysql.com/doc/refman/5.6/en/reserved-words.html
 *
 * @global array MySQL forbidden words
 */
$PMA_SQPdata_forbidden_word = array (
    'ACCESSIBLE',
    'ADD',
    'ALL',
    'ALTER',
    'ANALYZE',
    'AND',
    'AS',
    'ASC',
    'ASENSITIVE',
    'BEFORE',
    'BETWEEN',
    'BIGINT',
    'BINARY',
    'BLOB',
    'BOTH',
    'BY',
    'CALL',
    'CASCADE',
    'CASE',
    'CHANGE',
    'CHAR',
    'CHARACTER',
    'CHECK',
    'COLLATE',
    'COLUMN',
    'CONDITION',
    'CONSTRAINT',
    'CONTINUE',
    'CONVERT',
    'CREATE',
    'CROSS',
    'CURRENT_DATE',
    'CURRENT_TIME',
    'CURRENT_TIMESTAMP',
    'CURRENT_USER',
    'CURSOR',
    'DATABASE',
    'DATABASES',
    'DAY_HOUR',
    'DAY_MICROSECOND',
    'DAY_MINUTE',
    'DAY_SECOND',
    'DEC',
    'DECIMAL',
    'DECLARE',
    'DEFAULT',
    'DELAYED',
    'DELETE',
    'DESC',
    'DESCRIBE',
    'DETERMINISTIC',
    'DISTINCT',
    'DISTINCTROW',
    'DIV',
    'DOUBLE',
    'DROP',
    'DUAL',
    'EACH',
    'ELSE',
    'ELSEIF',
    'ENCLOSED',
    'ESCAPED',
    'EXISTS',
    'EXIT',
    'EXPLAIN',
    'FALSE',
    'FETCH',
    'FLOAT',
    'FLOAT4',
    'FLOAT8',
    'FOR',
    'FORCE',
    'FOREIGN',
    'FROM',
    'FULLTEXT',
    'GENERAL',
    'GET',
    'GRANT',
    'GROUP',
    'HAVING',
    'HIGH_PRIORITY',
    'HOUR_MICROSECOND',
    'HOUR_MINUTE',
    'HOUR_SECOND',
    'IF',
    'IGNORE',
    'IGNORE_SERVER_IDS',
    'IN',
    'INDEX',
    'INFILE',
    'INNER',
    'INOUT',
    'INSENSITIVE',
    'INSERT',
    'INT',
    'INT1',
    'INT2',
    'INT3',
    'INT4',
    'INT8',
    'INTEGER',
    'INTERVAL',
    'INTO',
    'IO_AFTER_GTIDS',
    'IO_BEFORE_GTIDS',
    'IS',
    'ITERATE',
    'JOIN',
    'KEY',
    'KEYS',
    'KILL',
    'LEADING',
    'LEAVE',
    'LEFT',
    'LIKE',
    'LIMIT',
    'LINEAR',
    'LINES',
    'LOAD',
    'LOCALTIME',
    'LOCALTIMESTAMP',
    'LOCK',
    'LONG',
    'LONGBLOB',
    'LONGTEXT',
    'LOOP',
    'LOW_PRIORITY',
    'MASTER_BIND',
    'MASTER_HEARTBEAT_PERIOD',
    'MASTER_SSL_VERIFY_SERVER_CERT',
    'MATCH',
    'MAXVALUE',
    'MEDIUMBLOB',
    'MEDIUMINT',
    'MEDIUMTEXT',
    'MIDDLEINT',
    'MINUTE_MICROSECOND',
    'MINUTE_SECOND',
    'MOD',
    'MODIFIES',
    'NATURAL',
    'NOT',
    'NO_WRITE_TO_BINLOG',
    'NULL',
    'NUMERIC',
    'ON',
    'ONE_SHOT',
    'OPTIMIZE',
    'OPTION',
    'OPTIONALLY',
    'OR',
    'ORDER',
    'OUT',
    'OUTER',
    'OUTFILE',
    'PARTITION',
    'PRECISION',
    'PRIMARY',
    'PROCEDURE',
    'PURGE',
    'RANGE',
    'READ',
    'READS',
    'READ_WRITE',
    'REAL',
    'REFERENCES',
    'REGEXP',
    'RELEASE',
    'RENAME',
    'REPEAT',
    'REPLACE',
    'REQUIRE',
    'RESIGNAL',
    'RESTRICT',
    'RETURN',
    'REVOKE',
    'RIGHT',
    'RLIKE',
    'SCHEMA',
    'SCHEMAS',
    'SECOND_MICROSECOND',
    'SELECT',
    'SENSITIVE',
    'SEPARATOR',
    'SET',
    'SHOW',
    'SIGNAL',
    'SLOW',
    'SMALLINT',
    'SPATIAL',
    'SPECIFIC',
    'SQL',
    'SQLEXCEPTION',
    'SQLSTATE',
    'SQLWARNING',
    'SQL_AFTER_GTIDS',
    'SQL_BEFORE_GTIDS',
    'SQL_BIG_RESULT',
    'SQL_CALC_FOUND_ROWS',
    'SQL_SMALL_RESULT',
    'SSL',
    'STARTING',
    'STRAIGHT_JOIN',
    'TABLE',
    'TERMINATED',
    'THEN',
    'TINYBLOB',
    'TINYINT',
    'TINYTEXT',
    'TO',
    'TRAILING',
    'TRIGGER',
    'TRUE',
    'UNDO',
    'UNION',
    'UNIQUE',
    'UNLOCK',
    'UNSIGNED',
    'UPDATE',
    'USAGE',
    'USE',
    'USING',
    'UTC_DATE',
    'UTC_TIME',
    'UTC_TIMESTAMP',
    'VALUES',
    'VARBINARY',
    'VARCHAR',
    'VARCHARACTER',
    'VARYING',
    'WHEN',
    'WHERE',
    'WHILE',
    'WITH',
    'WRITE',
    'XOR',
    'YEAR_MONTH',
    'ZEROFILL'
);

/**
 * the MySQL column/data types
 *
 * @see http://dev.mysql.com/doc/refman/5.1/en/data-types.html
 * @see http://dev.mysql.com/doc/refman/5.1/en/mysql-spatial-datatypes.html
 *
 * @global array MySQL column types
 */
$PMA_SQPdata_column_type = array (
    'BIGINT',
    'BINARY',
    'BIT',
    'BLOB',
    'BOOL',
    'BOOLEAN',              // numeric-type-overview.html
    'CHAR',
    'CHARACTER',
    'DATE',
    'DATETIME',
    'DEC',
    'DECIMAL',
    'DOUBLE',
    'ENUM',
    'FLOAT',
    'FLOAT4',
    'FLOAT8',
    'GEOMETRY',             // spatial
    'GEOMETRYCOLLECTION',   // spatial
    'INT',
    'INT1',
    'INT2',
    'INT3',
    'INT4',
    'INT8',
    'INTEGER',
    'LINESTRING',           // spatial
    'LONG',
    'LONGBLOB',
    'LONGTEXT',
    'MEDIUMBLOB',
    'MEDIUMINT',
    'MEDIUMTEXT',
    'MIDDLEINT',
    'MULTILINESTRING',      // spatial
    'MULTIPOINT',           // spatial
    'MULTIPOLYGON',         // spatial
    'NCHAR',
    'NUMERIC',
    'POINT',                // spatial
    'POLYGON',              // spatial
    'REAL',
    'SERIAL',               // alias
    'SET',
    'SMALLINT',
    'TEXT',
    'TIME',
    'TIMESTAMP',
    'TINYBLOB',
    'TINYINT',
    'TINYTEXT',
    'VARBINARY',
    'VARCHAR',
    'YEAR'
);

/**
 * Documentation links for operators.
 */
$PMA_SQPdata_operators_docs = array(
   '!=' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_not-equal',
    ),
   '<>' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_not-equal',
    ),
   '!' => array(
        'link' => 'logical-operators',
        'anchor' => 'operator_not',
    ),
   '||' => array(
        'link' => 'logical-operators',
        'anchor' => 'operator_or',
    ),
   '+' => array(
        'link' => 'arithmetic-functions',
        'anchor' => 'operator_plus',
    ),
   '>>' => array(
        'link' => 'bit-functions',
        'anchor' => 'operator_right-shift',
    ),
   '-' => array(
        'link' => 'arithmetic-functions',
        'anchor' => 'operator_minus',
    ),
   '*' => array(
        'link' => 'arithmetic-functions',
        'anchor' => 'operator_times',
    ),
   '&&' => array(
        'link' => 'logical-operators',
        'anchor' => 'operator_and',
    ),
   '&' => array(
        'link' => 'bit-functions',
        'anchor' => 'operator_bitwise-and',
    ),
   '~' => array(
        'link' => 'bit-functions',
        'anchor' => 'operator_bitwise-invert',
    ),
   '|' => array(
        'link' => 'bit-functions',
        'anchor' => 'operator_bitwise-or',
    ),
   '^' => array(
        'link' => 'bit-functions',
        'anchor' => 'operator_bitwise-xor',
    ),
   //FIXME:duplicated key "="
   '=' => array(
       'link' => 'assignment-operators',
       'anchor' => 'operator_assign-equal',
   ),
   ':=' => array(
        'link' => 'assignment-operators',
        'anchor' => 'operator_assign-value',
    ),
   '/' => array(
        'link' => 'arithmetic-functions',
        'anchor' => 'operator_divide',
    ),
   '<=>' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_equal-to',
    ),
   //FIXME:duplicated key "="
   '=' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_equal',
    ),
   '>=' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_greater-than-or-equal',
    ),
   '>' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_greater-than',
    ),
   '<<' => array(
        'link' => 'bit-functions',
        'anchor' => 'operator_left-shift',
    ),
   '<=' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_less-than-or-equal',
    ),
   '<' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_less-than',
    ),
   '%' => array(
        'link' => 'arithmetic-functions',
        'anchor' => 'operator_mod',
    )
);

/**
 * Documentation links for functions.
 */
$PMA_SQPdata_functions_docs = array(
   'ABS' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_abs',
    ),
   'ACOS' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_acos',
    ),
   'ADDDATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_adddate',
    ),
   'ADDTIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_addtime',
    ),
   'AES_DECRYPT' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_aes_decrypt',
    ),
   'AES_ENCRYPT' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_aes_encrypt',
    ),
   'AND' => array(
        'link' => 'logical-operators',
        'anchor' => 'operator_and',
    ),
   'ASCII' => array(
        'link' => 'string-functions',
        'anchor' => 'function_ascii',
    ),
   'ASIN' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_asin',
    ),
   'ATAN2' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_atan2',
    ),
   'ATAN' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_atan',
    ),
   'AVG' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_avg',
    ),
   'BENCHMARK' => array(
        'link' => 'information-functions',
        'anchor' => 'function_benchmark',
    ),
   'BIN' => array(
        'link' => 'string-functions',
        'anchor' => 'function_bin',
    ),
   'BINARY' => array(
        'link' => 'cast-functions',
        'anchor' => 'operator_binary',
    ),
   'BIT_AND' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_bit_and',
    ),
   'BIT_COUNT' => array(
        'link' => 'bit-functions',
        'anchor' => 'function_bit_count',
    ),
   'BIT_LENGTH' => array(
        'link' => 'string-functions',
        'anchor' => 'function_bit_length',
    ),
   'BIT_OR' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_bit_or',
    ),
   'BIT_XOR' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_bit_xor',
    ),
   'CASE' => array(
        'link' => 'control-flow-functions',
        'anchor' => 'operator_case',
    ),
   'CAST' => array(
        'link' => 'cast-functions',
        'anchor' => 'function_cast',
    ),
   'CEIL' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_ceil',
    ),
   'CEILING' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_ceiling',
    ),
   'CHAR_LENGTH' => array(
        'link' => 'string-functions',
        'anchor' => 'function_char_length',
    ),
   'CHAR' => array(
        'link' => 'string-functions',
        'anchor' => 'function_char',
    ),
   'CHARACTER_LENGTH' => array(
        'link' => 'string-functions',
        'anchor' => 'function_character_length',
    ),
   'CHARSET' => array(
        'link' => 'information-functions',
        'anchor' => 'function_charset',
    ),
   'COALESCE' => array(
        'link' => 'comparison-operators',
        'anchor' => 'function_coalesce',
    ),
   'COERCIBILITY' => array(
        'link' => 'information-functions',
        'anchor' => 'function_coercibility',
    ),
   'COLLATION' => array(
        'link' => 'information-functions',
        'anchor' => 'function_collation',
    ),
   'COMPRESS' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_compress',
    ),
   'CONCAT_WS' => array(
        'link' => 'string-functions',
        'anchor' => 'function_concat_ws',
    ),
   'CONCAT' => array(
        'link' => 'string-functions',
        'anchor' => 'function_concat',
    ),
   'CONNECTION_ID' => array(
        'link' => 'information-functions',
        'anchor' => 'function_connection_id',
    ),
   'CONV' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_conv',
    ),
   'CONVERT_TZ' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_convert_tz',
    ),
   'Convert' => array(
        'link' => 'cast-functions',
        'anchor' => 'function_convert',
    ),
   'COS' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_cos',
    ),
   'COT' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_cot',
    ),
   'COUNT' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_count',
    ),
   'CRC32' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_crc32',
    ),
   'CURDATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_curdate',
    ),
   'CURRENT_DATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_current_date',
    ),
   'CURRENT_TIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_current_time',
    ),
   'CURRENT_TIMESTAMP' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_current_timestamp',
    ),
   'CURRENT_USER' => array(
        'link' => 'information-functions',
        'anchor' => 'function_current_user',
    ),
   'CURTIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_curtime',
    ),
   'DATABASE' => array(
        'link' => 'information-functions',
        'anchor' => 'function_database',
    ),
   'DATE_ADD' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_date_add',
    ),
   'DATE_FORMAT' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_date_format',
    ),
   'DATE_SUB' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_date_sub',
    ),
   'DATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_date',
    ),
   'DATEDIFF' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_datediff',
    ),
   'DAY' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_day',
    ),
   'DAYNAME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_dayname',
    ),
   'DAYOFMONTH' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_dayofmonth',
    ),
   'DAYOFWEEK' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_dayofweek',
    ),
   'DAYOFYEAR' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_dayofyear',
    ),
   'DECLARE' => array('link' => 'declare', 'anchor' => 'declare'),
   'DECODE' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_decode',
    ),
   'DEFAULT' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_default',
    ),
   'DEGREES' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_degrees',
    ),
   'DES_DECRYPT' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_des_decrypt',
    ),
   'DES_ENCRYPT' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_des_encrypt',
    ),
   'DIV' => array(
        'link' => 'arithmetic-functions',
        'anchor' => 'operator_div',
    ),
   'ELT' => array(
        'link' => 'string-functions',
        'anchor' => 'function_elt',
    ),
   'ENCODE' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_encode',
    ),
   'ENCRYPT' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_encrypt',
    ),
   'EXP' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_exp',
    ),
   'EXPORT_SET' => array(
        'link' => 'string-functions',
        'anchor' => 'function_export_set',
    ),
   'EXTRACT' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_extract',
    ),
   'ExtractValue' => array(
        'link' => 'xml-functions',
        'anchor' => 'function_extractvalue',
    ),
   'FIELD' => array(
        'link' => 'string-functions',
        'anchor' => 'function_field',
    ),
   'FIND_IN_SET' => array(
        'link' => 'string-functions',
        'anchor' => 'function_find_in_set',
    ),
   'FLOOR' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_floor',
    ),
   'FORMAT' => array(
        'link' => 'string-functions',
        'anchor' => 'function_format',
    ),
   'FOUND_ROWS' => array(
        'link' => 'information-functions',
        'anchor' => 'function_found_rows',
    ),
   'FROM_DAYS' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_from_days',
    ),
   'FROM_UNIXTIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_from_unixtime',
    ),
   'GET_FORMAT' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_get_format',
    ),
   'GET_LOCK' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_get_lock',
    ),
   'GREATEST' => array(
        'link' => 'comparison-operators',
        'anchor' => 'function_greatest',
    ),
   'GROUP_CONCAT' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_group_concat',
    ),
   'HEX' => array(
        'link' => 'string-functions',
        'anchor' => 'function_hex',
    ),
   'HOUR' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_hour',
    ),
   'IF' => array(
        'link' => 'control-flow-functions',
        'anchor' => 'function_if',
    ),
   'IFNULL' => array(
        'link' => 'control-flow-functions',
        'anchor' => 'function_ifnull',
    ),
   'IN' => array(
        'link' => 'comparison-operators',
        'anchor' => 'function_in',
    ),
   'INET_ATON' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_inet_aton',
    ),
   'INET_NTOA' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_inet_ntoa',
    ),
   'INSERT' => array(
        'link' => 'string-functions',
        'anchor' => 'function_insert',
    ),
   'INSTR' => array(
        'link' => 'string-functions',
        'anchor' => 'function_instr',
    ),
   'INTERVAL' => array(
        'link' => 'comparison-operators',
        'anchor' => 'function_interval',
    ),
   'IS_FREE_LOCK' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_is_free_lock',
    ),
   'IS_USED_LOCK' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_is_used_lock',
    ),
   'IS' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_is',
    ),
   'ISNULL' => array(
        'link' => 'comparison-operators',
        'anchor' => 'function_isnull',
    ),
   'LAST_DAY' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_last_day',
    ),
   'LAST_INSERT_ID' => array(
        'link' => 'information-functions',
        'anchor' => 'function_last_insert_id',
    ),
   'LCASE' => array(
        'link' => 'string-functions',
        'anchor' => 'function_lcase',
    ),
   'LEAST' => array(
        'link' => 'comparison-operators',
        'anchor' => 'function_least',
    ),
   'LEFT' => array(
        'link' => 'string-functions',
        'anchor' => 'function_left',
    ),
   'LENGTH' => array(
        'link' => 'string-functions',
        'anchor' => 'function_length',
    ),
   'LIKE' => array(
        'link' => 'string-comparison-functions',
        'anchor' => 'operator_like',
    ),
   'LN' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_ln',
    ),
   'LOAD_FILE' => array(
        'link' => 'string-functions',
        'anchor' => 'function_load_file',
    ),
   'LOCALTIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_localtime',
    ),
   'LOCALTIMESTAMP' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_localtimestamp',
    ),
   'LOCATE' => array(
        'link' => 'string-functions',
        'anchor' => 'function_locate',
    ),
   'LOG10' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_log10',
    ),
   'LOG2' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_log2',
    ),
   'LOG' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_log',
    ),
   'LOWER' => array(
        'link' => 'string-functions',
        'anchor' => 'function_lower',
    ),
   'LPAD' => array(
        'link' => 'string-functions',
        'anchor' => 'function_lpad',
    ),
   'LTRIM' => array(
        'link' => 'string-functions',
        'anchor' => 'function_ltrim',
    ),
   'MAKE_SET' => array(
        'link' => 'string-functions',
        'anchor' => 'function_make_set',
    ),
   'MAKEDATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_makedate',
    ),
   'MAKETIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_maketime',
    ),
   'MASTER_POS_WAIT' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_master_pos_wait',
    ),
   'MATCH' => array(
        'link' => 'fulltext-search',
        'anchor' => 'function_match',
    ),
   'MAX' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_max',
    ),
   'MD5' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_md5',
    ),
   'MICROSECOND' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_microsecond',
    ),
   'MID' => array(
        'link' => 'string-functions',
        'anchor' => 'function_mid',
    ),
   'MIN' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_min',
    ),
   'MINUTE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_minute',
    ),
   'MOD' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_mod',
    ),
   'MONTH' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_month',
    ),
   'MONTHNAME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_monthname',
    ),
   'NAME_CONST' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_name_const',
    ),
   'NOT' => array(
        'link' => 'logical-operators',
        'anchor' => 'operator_not',
    ),
   'NOW' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_now',
    ),
   'NULLIF' => array(
        'link' => 'control-flow-functions',
        'anchor' => 'function_nullif',
    ),
   'OCT' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_oct',
    ),
   'OCTET_LENGTH' => array(
        'link' => 'string-functions',
        'anchor' => 'function_octet_length',
    ),
   'OLD_PASSWORD' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_old_password',
    ),
   'OR' => array(
        'link' => 'logical-operators',
        'anchor' => 'operator_or',
    ),
   'ORD' => array(
        'link' => 'string-functions',
        'anchor' => 'function_ord',
    ),
   'PASSWORD' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_password',
    ),
   'PERIOD_ADD' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_period_add',
    ),
   'PERIOD_DIFF' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_period_diff',
    ),
   'PI' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_pi',
    ),
   'POSITION' => array(
        'link' => 'string-functions',
        'anchor' => 'function_position',
    ),
   'POW' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_pow',
    ),
   'POWER' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_power',
    ),
   'QUARTER' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_quarter',
    ),
   'QUOTE' => array(
        'link' => 'string-functions',
        'anchor' => 'function_quote',
    ),
   'RADIANS' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_radians',
    ),
   'RAND' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_rand',
    ),
   'REGEXP' => array(
        'link' => 'regexp',
        'anchor' => 'operator_regexp',
    ),
   'RELEASE_LOCK' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_release_lock',
    ),
   'REPEAT' => array(
        'link' => 'string-functions',
        'anchor' => 'function_repeat',
    ),
   'REPLACE' => array(
        'link' => 'string-functions',
        'anchor' => 'function_replace',
    ),
   'REVERSE' => array(
        'link' => 'string-functions',
        'anchor' => 'function_reverse',
    ),
   'RIGHT' => array(
        'link' => 'string-functions',
        'anchor' => 'function_right',
    ),
   'RLIKE' => array(
        'link' => 'regexp',
        'anchor' => 'operator_rlike',
    ),
   'ROUND' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_round',
    ),
   'ROW_COUNT' => array(
        'link' => 'information-functions',
        'anchor' => 'function_row_count',
    ),
   'RPAD' => array(
        'link' => 'string-functions',
        'anchor' => 'function_rpad',
    ),
   'RTRIM' => array(
        'link' => 'string-functions',
        'anchor' => 'function_rtrim',
    ),
   'SCHEMA' => array(
        'link' => 'information-functions',
        'anchor' => 'function_schema',
    ),
   'SEC_TO_TIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_sec_to_time',
    ),
   'SECOND' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_second',
    ),
   'SESSION_USER' => array(
        'link' => 'information-functions',
        'anchor' => 'function_session_user',
    ),
   'SHA' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_sha1',
    ),
   'SHA1' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_sha1',
    ),
   'SIGN' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_sign',
    ),
   'SIN' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_sin',
    ),
   'SLEEP' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_sleep',
    ),
   'SOUNDEX' => array(
        'link' => 'string-functions',
        'anchor' => 'function_soundex',
    ),
   'SPACE' => array(
        'link' => 'string-functions',
        'anchor' => 'function_space',
    ),
   'SQRT' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_sqrt',
    ),
   'STD' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_std',
    ),
   'STDDEV_POP' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_stddev_pop',
    ),
   'STDDEV_SAMP' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_stddev_samp',
    ),
   'STDDEV' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_stddev',
    ),
   'STR_TO_DATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_str_to_date',
    ),
   'STRCMP' => array(
        'link' => 'string-comparison-functions',
        'anchor' => 'function_strcmp',
    ),
   'SUBDATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_subdate',
    ),
   'SUBSTR' => array(
        'link' => 'string-functions',
        'anchor' => 'function_substr',
    ),
   'SUBSTRING_INDEX' => array(
        'link' => 'string-functions',
        'anchor' => 'function_substring_index',
    ),
   'SUBSTRING' => array(
        'link' => 'string-functions',
        'anchor' => 'function_substring',
    ),
   'SUBTIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_subtime',
    ),
   'SUM' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_sum',
    ),
   'SYSDATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_sysdate',
    ),
   'SYSTEM_USER' => array(
        'link' => 'information-functions',
        'anchor' => 'function_system_user',
    ),
   'TAN' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_tan',
    ),
   'TIME_FORMAT' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_time_format',
    ),
   'TIME_TO_SEC' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_time_to_sec',
    ),
   'TIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_time',
    ),
   'TIMEDIFF' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_timediff',
    ),
   'TIMESTAMP' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_timestamp',
    ),
   'TIMESTAMPADD' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_timestampadd',
    ),
   'TIMESTAMPDIFF' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_timestampdiff',
    ),
   'TO_DAYS' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_to_days',
    ),
   'TRIM' => array(
        'link' => 'string-functions',
        'anchor' => 'function_trim',
    ),
   'TRUNCATE' => array(
        'link' => 'mathematical-functions',
        'anchor' => 'function_truncate',
    ),
   'UCASE' => array(
        'link' => 'string-functions',
        'anchor' => 'function_ucase',
    ),
   'UNCOMPRESS' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_uncompress',
    ),
   'UNCOMPRESSED_LENGTH' => array(
        'link' => 'encryption-functions',
        'anchor' => 'function_uncompressed_length',
    ),
   'UNHEX' => array(
        'link' => 'string-functions',
        'anchor' => 'function_unhex',
    ),
   'UNIX_TIMESTAMP' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_unix_timestamp',
    ),
   'UpdateXML' => array(
        'link' => 'xml-functions',
        'anchor' => 'function_updatexml',
    ),
   'UPPER' => array(
        'link' => 'string-functions',
        'anchor' => 'function_upper',
    ),
   'USER' => array(
        'link' => 'information-functions',
        'anchor' => 'function_user',
    ),
   'UTC_DATE' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_utc_date',
    ),
   'UTC_TIME' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_utc_time',
    ),
   'UTC_TIMESTAMP' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_utc_timestamp',
    ),
   'UUID_SHORT' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_uuid_short',
    ),
   'UUID' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_uuid',
    ),
   'VALUES' => array(
        'link' => 'miscellaneous-functions',
        'anchor' => 'function_values',
    ),
   'VAR_POP' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_var_pop',
    ),
   'VAR_SAMP' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_var_samp',
    ),
   'VARIANCE' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_variance',
    ),
   'VERSION' => array(
        'link' => 'information-functions',
        'anchor' => 'function_version',
    ),
   'WEEK' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_week',
    ),
   'WEEKDAY' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_weekday',
    ),
   'WEEKOFYEAR' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_weekofyear',
    ),
   'XOR' => array(
        'link' => 'logical-operators',
        'anchor' => 'operator_xor',
    ),
   'YEAR' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_year',
    ),
   'YEARWEEK' => array(
        'link' => 'date-and-time-functions',
        'anchor' => 'function_yearweek',
    ),
   'SOUNDS_LIKE' => array(
        'link' => 'string-functions',
        'anchor' => 'operator_sounds-like',
    ),
   'IS_NOT_NULL' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_is-not-null',
    ),
   'IS_NOT' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_is-not',
    ),
   'IS_NULL' => array(
        'link' => 'comparison-operators',
        'anchor' => 'operator_is-null',
    ),
   'NOT_LIKE' => array(
        'link' => 'string-comparison-functions',
        'anchor' => 'operator_not-like',
    ),
   'NOT_REGEXP' => array(
        'link' => 'regexp',
        'anchor' => 'operator_not-regexp',
    ),
   'COUNT_DISTINCT' => array(
        'link' => 'group-by-functions',
        'anchor' => 'function_count-distinct',
    ),
   'NOT_IN' => array(
        'link' => 'comparison-operators',
        'anchor' => 'function_not-in',
    )
);

?>
