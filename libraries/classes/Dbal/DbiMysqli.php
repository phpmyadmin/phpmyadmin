<?php
/**
 * Interface to the MySQL Improved extension (MySQLi)
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use mysqli;
use mysqli_result;
use mysqli_stmt;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Query\Utilities;
use stdClass;
use function mysqli_report;
use const E_USER_WARNING;
use const MYSQLI_ASSOC;
use const MYSQLI_BOTH;
use const MYSQLI_CLIENT_COMPRESS;
use const MYSQLI_CLIENT_SSL;
use const MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
use const MYSQLI_NUM;
use const MYSQLI_OPT_LOCAL_INFILE;
use const MYSQLI_OPT_SSL_VERIFY_SERVER_CERT;
use const MYSQLI_REPORT_OFF;
use const MYSQLI_STORE_RESULT;
use const MYSQLI_USE_RESULT;
use function defined;
use function is_array;
use function is_bool;
use function mysqli_init;
use function stripos;
use function trigger_error;

/**
 * Interface to the MySQL Improved extension (MySQLi)
 */
class DbiMysqli implements DbiExtension
{
    /**
     * connects to the database server
     *
     * @param string $user     mysql user name
     * @param string $password mysql user password
     * @param array  $server   host/port/socket/persistent
     *
     * @return mysqli|bool false on error or a mysqli object on success
     */
    public function connect($user, $password, array $server)
    {
        if ($server) {
            $server['host'] = empty($server['host'])
                ? 'localhost'
                : $server['host'];
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        $mysqli = mysqli_init();

        $client_flags = 0;

        /* Optionally compress connection */
        if ($server['compress'] && defined('MYSQLI_CLIENT_COMPRESS')) {
            $client_flags |= MYSQLI_CLIENT_COMPRESS;
        }

        /* Optionally enable SSL */
        if ($server['ssl']) {
            $client_flags |= MYSQLI_CLIENT_SSL;
            if (! empty($server['ssl_key']) ||
                ! empty($server['ssl_cert']) ||
                ! empty($server['ssl_ca']) ||
                ! empty($server['ssl_ca_path']) ||
                ! empty($server['ssl_ciphers'])
            ) {
                $mysqli->ssl_set(
                    $server['ssl_key'] ?? '',
                    $server['ssl_cert'] ?? '',
                    $server['ssl_ca'] ?? '',
                    $server['ssl_ca_path'] ?? '',
                    $server['ssl_ciphers'] ?? ''
                );
            }
            /*
             * disables SSL certificate validation on mysqlnd for MySQL 5.6 or later
             * @link https://bugs.php.net/bug.php?id=68344
             * @link https://github.com/phpmyadmin/phpmyadmin/pull/11838
             */
            if (! $server['ssl_verify']) {
                $mysqli->options(
                    MYSQLI_OPT_SSL_VERIFY_SERVER_CERT,
                    $server['ssl_verify']
                );
                $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
        }

        if ($GLOBALS['cfg']['PersistentConnections']) {
            $host = 'p:' . $server['host'];
        } else {
            $host = $server['host'];
        }

        $return_value = $mysqli->real_connect(
            $host,
            $user,
            $password,
            '',
            $server['port'],
            (string) $server['socket'],
            $client_flags
        );

        if ($return_value === false || $return_value === null) {
            /*
             * Switch to SSL if server asked us to do so, unfortunately
             * there are more ways MySQL server can tell this:
             *
             * - MySQL 8.0 and newer should return error 3159
             * - #2001 - SSL Connection is required. Please specify SSL options and retry.
             * - #9002 - SSL connection is required. Please specify SSL options and retry.
             */
            $error_number = $mysqli->connect_errno;
            $error_message = $mysqli->connect_error;
            if (! $server['ssl']
                && ($error_number == 3159
                    || (($error_number == 2001 || $error_number == 9002)
                        && stripos($error_message, 'SSL Connection is required') !== false))
            ) {
                trigger_error(
                    __('SSL connection enforced by server, automatically enabling it.'),
                    E_USER_WARNING
                );
                $server['ssl'] = true;

                return self::connect($user, $password, $server);
            }

            return false;
        }

        if (defined('PMA_ENABLE_LDI')) {
            $mysqli->options(MYSQLI_OPT_LOCAL_INFILE, true);
        } else {
            $mysqli->options(MYSQLI_OPT_LOCAL_INFILE, false);
        }

        return $mysqli;
    }

    /**
     * selects given database
     *
     * @param string $databaseName database name to select
     * @param mysqli $mysqli       the mysqli object
     *
     * @return bool
     */
    public function selectDb($databaseName, $mysqli)
    {
        return $mysqli->select_db($databaseName);
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query   query to execute
     * @param mysqli $mysqli  mysqli object
     * @param int    $options query options
     *
     * @return mysqli_result|bool
     */
    public function realQuery($query, $mysqli, $options)
    {
        if ($options == ($options | DatabaseInterface::QUERY_STORE)) {
            $method = MYSQLI_STORE_RESULT;
        } elseif ($options == ($options | DatabaseInterface::QUERY_UNBUFFERED)) {
            $method = MYSQLI_USE_RESULT;
        } else {
            $method = 0;
        }

        return $mysqli->query($query, $method);
    }

    /**
     * Run the multi query and output the results
     *
     * @param mysqli $mysqli mysqli object
     * @param string $query  multi query statement to execute
     *
     * @return bool
     */
    public function realMultiQuery($mysqli, $query)
    {
        return $mysqli->multi_query($query);
    }

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param mysqli_result $result result set identifier
     */
    public function fetchArray($result): ?array
    {
        if (! $result instanceof mysqli_result) {
            return null;
        }

        return $result->fetch_array(MYSQLI_BOTH);
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param mysqli_result $result result set identifier
     */
    public function fetchAssoc($result): ?array
    {
        if (! $result instanceof mysqli_result) {
            return null;
        }

        return $result->fetch_array(MYSQLI_ASSOC);
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param mysqli_result $result result set identifier
     */
    public function fetchRow($result): ?array
    {
        if (! $result instanceof mysqli_result) {
            return null;
        }

        return $result->fetch_array(MYSQLI_NUM);
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param mysqli_result $result database result
     * @param int           $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, $offset)
    {
        return $result->data_seek($offset);
    }

    /**
     * Frees memory associated with the result
     *
     * @param mysqli_result $result database result
     *
     * @return void
     */
    public function freeResult($result)
    {
        if (! ($result instanceof mysqli_result)) {
            return;
        }

        $result->close();
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param mysqli $mysqli the mysqli object
     *
     * @return bool true or false
     */
    public function moreResults($mysqli)
    {
        return $mysqli->more_results();
    }

    /**
     * Prepare next result from multi_query
     *
     * @param mysqli $mysqli the mysqli object
     *
     * @return bool true or false
     */
    public function nextResult($mysqli)
    {
        return $mysqli->next_result();
    }

    /**
     * Store the result returned from multi query
     *
     * @param mysqli $mysqli the mysqli object
     *
     * @return mysqli_result|bool false when empty results / result set when not empty
     */
    public function storeResult($mysqli)
    {
        return $mysqli->store_result();
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param mysqli $mysqli mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($mysqli)
    {
        return $mysqli->host_info;
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param mysqli $mysqli mysql link
     *
     * @return string version of the MySQL protocol used
     */
    public function getProtoInfo($mysqli)
    {
        return $mysqli->protocol_version;
    }

    /**
     * returns a string that represents the client library version
     *
     * @param mysqli $mysqli mysql link
     *
     * @return string MySQL client library version
     */
    public function getClientInfo($mysqli)
    {
        return $mysqli->get_client_info();
    }

    /**
     * returns last error message or false if no errors occurred
     *
     * @param mysqli $mysqli mysql link
     *
     * @return string|bool error or false
     */
    public function getError($mysqli)
    {
        $GLOBALS['errno'] = 0;

        if ($mysqli !== null && $mysqli !== false) {
            $error_number = $mysqli->errno;
            $error_message = $mysqli->error;
        } else {
            $error_number = $mysqli->connect_errno;
            $error_message = $mysqli->connect_error;
        }
        if ($error_number == 0) {
            return false;
        }

        // keep the error number for further check after
        // the call to getError()
        $GLOBALS['errno'] = $error_number;

        return Utilities::formatError($error_number, $error_message);
    }

    /**
     * returns the number of rows returned by last query
     *
     * @param mysqli_result $result result set identifier
     *
     * @return string|int
     */
    public function numRows($result)
    {
        // see the note for tryQuery();
        if (is_bool($result)) {
            return 0;
        }

        return $result->num_rows;
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param mysqli $mysqli the mysqli object
     *
     * @return int
     */
    public function affectedRows($mysqli)
    {
        return $mysqli->affected_rows;
    }

    /**
     * returns meta info for fields in $result
     *
     * @param mysqli_result $result result set identifier
     *
     * @return FieldMetadata[]|null meta info for fields in $result
     */
    public function getFieldsMeta($result): ?array
    {
        if (! $result instanceof mysqli_result) {
            return null;
        }
        $fields = $result->fetch_fields();
        if (! is_array($fields)) {
            return null;
        }

        foreach ($fields as $k => $field) {
            $fields[$k] = new FieldMetadata($field->type, $field->flags, $field);
        }

        return $fields;
    }

    /**
     * return number of fields in given $result
     *
     * @param mysqli_result $result result set identifier
     *
     * @return int field count
     */
    public function numFields($result)
    {
        return $result->field_count;
    }

    /**
     * returns the length of the given field $i in $result
     *
     * @param mysqli_result $result result set identifier
     * @param int           $i      field
     *
     * @return int|bool length of field
     */
    public function fieldLen($result, $i)
    {
        if ($i >= $this->numFields($result)) {
            return false;
        }
        /** @var stdClass $fieldDefinition */
        $fieldDefinition = $result->fetch_field_direct($i);
        if ($fieldDefinition !== false) {
            return $fieldDefinition->length;
        }

        return false;
    }

    /**
     * returns name of $i. field in $result
     *
     * @param mysqli_result $result result set identifier
     * @param int           $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, $i)
    {
        if ($i >= $this->numFields($result)) {
            return '';
        }
        /** @var stdClass $fieldDefinition */
        $fieldDefinition = $result->fetch_field_direct($i);
        if ($fieldDefinition !== false) {
            return $fieldDefinition->name;
        }

        return '';
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param mysqli $mysqli database link
     * @param string $string string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString($mysqli, $string)
    {
        return $mysqli->real_escape_string($string);
    }

    /**
     * Prepare an SQL statement for execution.
     *
     * @param mysqli $mysqli database link
     * @param string $query  The query, as a string.
     *
     * @return mysqli_stmt|false A statement object or false.
     */
    public function prepare($mysqli, string $query)
    {
        return $mysqli->prepare($query);
    }
}
