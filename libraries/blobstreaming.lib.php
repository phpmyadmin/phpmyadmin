<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package     BLOBStreaming
 */

/**
 * Initializes PBMS database
 *
 * @return bool
 */
function initPBMSDatabase()
{
    // If no other choice then try this.
    $query = "create database IF NOT EXISTS pbms;";
    /*
     * The user may not have privileges to create the 'pbms' database
     * so if it doesn't exist then we perform a select on a pbms system
     * table in an already existing database which will cause the PBMS
     * daemon to create the 'pbms' database.
     */
    $db_array = PMA_DBI_fetch_result('SHOW DATABASES;');
    if (! empty($db_array)) {
        $target = "";
        foreach ($db_array as $current_db) {
            if ($current_db == 'pbms') {
                return true;
            }
            if ($target == "") {
                if ($current_db != 'pbxt'
                    && ! PMA_is_system_schema($current_db, true)
                ) {
                    $target = $current_db;
                }
            }
        }

        if ($target != "") {
            // If it exists this table will not contain much
            $query = "select * from $target.pbms_metadata_header";
        }
    }

    $result = PMA_DBI_query($query);
    if (! $result) {
        return false;
    }
    return true;
}

/**
 * checks whether the necessary plugins for BLOBStreaming exist
 *
 * @access  public
 * @return  boolean
*/
function checkBLOBStreamingPlugins()
{
    if (PMA_cacheGet('skip_blobstreaming', true) === true) {
        return false;
    }

    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config)) {
        return false;
    }

    // If we don't know that we can skip blobstreaming, we continue
    // verifications; anyway, in case we won't skip blobstreaming,
    // we still need to set some variables in non-persistent settings,
    // which is done via $PMA_Config->set().

    /** Retrieve current server configuration;
     *  at this point, $PMA_Config->get('Servers') contains the server parameters
     *  as explicitely defined in config.inc.php, so it cannot be used; it's
     *  better to use $GLOBALS['cfg']['Server'] which contains the explicit
     *  parameters merged with the default ones
     *
     */
    $serverCfg = $GLOBALS['cfg']['Server'];

    // return if unable to retrieve current server configuration
    if (! $serverCfg) {
        return false;
    }

    // if PHP extension in use is 'mysql', specify element 'PersistentConnections'
    if ($serverCfg['extension'] == "mysql") {
        $serverCfg['PersistentConnections'] = $PMA_Config->settings['PersistentConnections'];
    }

    // if connection type is TCP, unload socket variable
    if (strtolower($serverCfg['connect_type']) == "tcp") {
        $serverCfg['socket'] = "";
    }

    $has_blobstreaming = PMA_cacheGet('has_blobstreaming', true);

    if ($has_blobstreaming === null) {
        if (! PMA_DRIZZLE && PMA_MYSQL_INT_VERSION >= 50109) {

            // Retrieve MySQL plugins
            $existing_plugins = PMA_DBI_fetch_result('SHOW PLUGINS');

            foreach ($existing_plugins as $one_existing_plugin) {
                // check if required plugins exist
                if ( strtolower($one_existing_plugin['Library']) == 'libpbms.so'
                    && $one_existing_plugin['Status'] == "ACTIVE"
                ) {
                    $has_blobstreaming = true;
                    break;
                }
            }
            unset($existing_plugins, $one_existing_plugin);
        } else if (PMA_DRIZZLE) {
            $has_blobstreaming = (bool) PMA_DBI_fetch_result(
                "SELECT 1
                FROM data_dictionary.plugins
                WHERE module_name = 'PBMS'
                    AND is_active = true
                LIMIT 1"
            );
        }
        PMA_cacheSet('has_blobstreaming', $has_blobstreaming, true);
    }

    // set variable indicating BS plugin existence
    $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', $has_blobstreaming);

    if (! $has_blobstreaming) {
        PMA_cacheSet('skip_blobstreaming', true, true);
        return false;
    }

    if ($has_blobstreaming) {
        $bs_variables = PMA_BS_GetVariables();

        // if no BS variables exist, set plugin existence to false and return
        if (count($bs_variables) == 0) {
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', false);
            PMA_cacheSet('skip_blobstreaming', true, true);
            PMA_cacheSet('has_blobstreaming', false, true);
            return false;
        } // end if (count($bs_variables) <= 0)

        // Check that the required pbms functions exist:
        if (function_exists("pbms_connect") == false
            || function_exists("pbms_error") == false
            || function_exists("pbms_close") == false
            || function_exists("pbms_is_blob_reference") == false
            || function_exists("pbms_get_info") == false
            || function_exists("pbms_get_metadata_value") == false
            || function_exists("pbms_add_metadata") == false
            || function_exists("pbms_read_stream") == false
        ) {

            // We should probably notify the user that they need to install
            // the pbms client lib and PHP extension to make use of blob streaming.
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', false);
            PMA_cacheSet('skip_blobstreaming', true, true);
            PMA_cacheSet('has_blobstreaming', false, true);
            return false;
        }

        if (function_exists("pbms_connection_pool_size")) {
            if ( isset($PMA_Config->settings['pbms_connection_pool_size'])) {
                $pool_size = $PMA_Config->settings['pbms_connection_pool_size'];
                if ($pool_size == "") {
                    $pool_size = 1;
                }
            } else {
                $pool_size = 1;
            }
            pbms_connection_pool_size($pool_size);
        }

         // get BS server port
        $BS_PORT = $bs_variables['pbms_port'];

        // if no BS server port or 'pbms' database exists,
        // set plugin existance to false and return
        if ((! $BS_PORT) || (! initPBMSDatabase())) {
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', false);
            PMA_cacheSet('skip_blobstreaming', true, true);
            return false;
        } // end if (!$BS_PORT)

        // Ping PBMS: the database doesn't need to exist for this to work.
        if (pbms_connect($serverCfg['host'], $BS_PORT, "anydb") == false) {
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', false);
            PMA_cacheSet('skip_blobstreaming', true, true);
            return false;
        }
        pbms_close();

        if (function_exists("pbms_pconnect")) {
            $PMA_Config->set('PBMS_PCONNECT_EXISTS', true);
        } else {
            $PMA_Config->set('PBMS_PCONNECT_EXISTS', false);
        }

        // add selected BS, CURL and fileinfo library variables to PMA configuration
        $PMA_Config->set('BLOBSTREAMING_PORT', $BS_PORT);
        $PMA_Config->set('BLOBSTREAMING_HOST', $serverCfg['host']);
        $PMA_Config->set('BLOBSTREAMING_SERVER', $serverCfg['host'] . ':' . $BS_PORT);
        $PMA_Config->set('PHP_PBMS_EXISTS', false);
        $PMA_Config->set('FILEINFO_EXISTS', false);

        // check if PECL's fileinfo library exist
        $finfo = null;

        if (function_exists("finfo_open")) {
            $finfo = finfo_open(FILEINFO_MIME);
        }

        // fileinfo library exists, set necessary variable and close resource
        if (! empty($finfo)) {
            $PMA_Config->set('FILEINFO_EXISTS', true);
            finfo_close($finfo);
        } // end if (!empty($finfo))

    } else {
        PMA_cacheSet('skip_blobstreaming', true, true);
        return false;
    } // end if ($has_blobstreaming)

    return true;
}

/**
 * returns a list of BLOBStreaming variables used by MySQL
 *
 * @access  public
 * @return  array - list of BLOBStreaming variables
 */
function PMA_BS_GetVariables()
{
    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config)) {
        return null;
    }
    // run query to retrieve BS variables
    $query = "SHOW VARIABLES LIKE '%pbms%'";
    $result = PMA_DBI_query($query);

    $BS_Variables = array();

    // while there are records to retrieve
    while ($data = @PMA_DBI_fetch_assoc($result)) {
        $BS_Variables[$data['Variable_name']] = $data['Value'];
    }
    // return BS variables
    return $BS_Variables;
}

/**
 * Retrieves and shows PBMS error.
 *
 * @param sting $msg error message
 *
 * @return nothing
 */
function PMA_BS_ReportPBMSError($msg)
{
    $tmp_err = pbms_error();
    PMA_showMessage(__('PBMS error') . " $msg $tmp_err");
}

/**
 * Tries to connect to PBMS server.
 *
 * @param string $db_name Database name
 * @param bool   $quiet   Whether to report errors
 *
 * @return bool Connection status.
 */
function PMA_do_connect($db_name, $quiet)
{
    $PMA_Config = $GLOBALS['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config)) {
        return false;
    }

    // generate bs reference link
    $pbms_host = $PMA_Config->get('BLOBSTREAMING_HOST');
    $pbms_port = $PMA_Config->get('BLOBSTREAMING_PORT');

    if ($PMA_Config->get('PBMS_PCONNECT_EXISTS')) {
        // Open a persistent connection.
        $ok = pbms_pconnect($pbms_host, $pbms_port, $db_name);
    } else {
        $ok = pbms_connect($pbms_host, $pbms_port, $db_name);
    }

    if ($ok == false) {
        if ($quiet == false) {
            PMA_BS_ReportPBMSError(
                __('PBMS connection failed:')
                . " pbms_connect($pbms_host, $pbms_port, $db_name)"
            );
        }
        return false;
    }
    return true;
}

/**
 * Disconnects from PBMS server.
 *
 * @return nothing
 */
function PMA_do_disconnect()
{
    pbms_close();
}

/**
 * Checks whether the BLOB reference looks valid
 *
 * @param string $bs_reference BLOB reference
 * @param string $db_name      Database name
 *
 * @return bool True on success.
 */
function PMA_BS_IsPBMSReference($bs_reference, $db_name)
{
    if (PMA_cacheGet('skip_blobstreaming', true)) {
        return false;
    }

    // You do not really need a connection to the PBMS Daemon
    // to check if a reference looks valid but unfortunalty the API
    // requires one at this point so until the API is updated
    // we need to epen one here. If you use pool connections this
    // will not be a performance problem.
    if (PMA_do_connect($db_name, false) == false) {
        return false;
    }

    $ok = pbms_is_blob_reference($bs_reference);
    return $ok ;
}

//------------
function PMA_BS_CreateReferenceLink($bs_reference, $db_name)
{
    if (PMA_do_connect($db_name, false) == false) {
        return __('Error');
    }

    if (pbms_get_info(trim($bs_reference)) == false) {
        PMA_BS_ReportPBMSError(
            __('PBMS get BLOB info failed:')
            . " pbms_get_info($bs_reference)"
        );
        PMA_do_disconnect();
        return __('Error');
    }

    $content_type = pbms_get_metadata_value("Content-Type");
    if ($content_type == false) {
        $br = trim($bs_reference);
        PMA_BS_ReportPBMSError(
            "PMA_BS_CreateReferenceLink('$br', '$db_name'): "
            . __('PBMS get BLOB Content-Type failed')
        );
    }

    PMA_do_disconnect();

    if (! $content_type) {
        $content_type = "image/jpeg";
    }

    $bs_url = PMA_BS_getURL($bs_reference);
    if (empty($bs_url)) {
        PMA_BS_ReportPBMSError(__('No blob streaming server configured!'));
        return 'Error';
    }

    $output = $content_type;

    // specify custom HTML for various content types
    switch ($content_type) {
    // no content specified
    case null:
        $output = "NULL";
        break;
    // image content
    case 'image/jpeg':
    case 'image/png':
        $output .= ' (<a href="' . $bs_url . '" target="new">'
            . __('View image') . '</a>)';
        break;
    // audio content
    case 'audio/mpeg':
        $output .= ' (<a href="#" onclick="popupBSMedia(\''
            . PMA_generate_common_url() . '\',\'' . urlencode($bs_reference)
            . '\', \'' . urlencode($content_type) . '\','
            . ($is_custom_type ? 1 : 0) . ', 640, 120)">' . __('Play audio')
            . '</a>)';
        break;
    // video content
    case 'application/x-flash-video':
    case 'video/mpeg':
        $output .= ' (<a href="#" onclick="popupBSMedia(\''
            . PMA_generate_common_url() . '\',\'' . urlencode($bs_reference)
            . '\', \'' . urlencode($content_type) . '\','
            . ($is_custom_type ? 1 : 0) . ', 640, 480)">' . __('View video')
            . '</a>)';
        break;
    // unsupported content. specify download
    default:
        $output .= ' (<a href="' . $bs_url . '" target="new">'
            . __('Download file') . '</a>)';
    }

    return $output;
}

/**
 * In the future there may be server variables to turn on/off PBMS
 * BLOB streaming on a per table or database basis. So in anticipation of this
 * PMA_BS_IsTablePBMSEnabled() passes in the table and database name even though
 * they are not currently needed.
 *
 * @param string $db_name  database name
 * @param string $tbl_name table name
 * @param string $tbl_type table type
 *
 * @return bool
 */
function PMA_BS_IsTablePBMSEnabled($db_name, $tbl_name, $tbl_type)
{
    if (PMA_cacheGet('skip_blobstreaming', true)) {
        return false;
    }

    if ((isset($tbl_type) == false) || (strlen($tbl_type) == 0)) {
        return false;
    }

    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config)) {
        return false;
    }

    if (! $PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST')) {
        return false;
    }

    // This information should be cached rather than selecting it each time.
    // $query = "SELECT count(*)  FROM information_schema.TABLES T,
    // pbms.pbms_enabled E where T.table_schema = ". PMA_backquote($db_name) . "
    // and T.table_name = ". PMA_backquote($tbl_name) . " and T.engine = E.name";
    $query = "SELECT count(*)  FROM pbms.pbms_enabled E where E.name = '"
        . PMA_sqlAddSlashes($tbl_type) . "'";
    $result = PMA_DBI_query($query);

    $data = PMA_DBI_fetch_row($result);
    if ($data[0] == 1) {
        return true;
    }

    return false;
}

//------------
function PMA_BS_UpLoadFile($db_name, $tbl_name, $file_type, $file_name)
{

    if (PMA_cacheGet('skip_blobstreaming', true)) {
        return false;
    }

    if (PMA_do_connect($db_name, false) == false) {
        return false;
    }

    $fh = fopen($file_name, 'r');
    if (! $fh) {
        PMA_do_disconnect();
        PMA_showMessage(sprintf(__('Could not open file: %s'), $file_name));
        return false;
    }

    pbms_add_metadata("Content-Type", $file_type);

    $pbms_blob_url = pbms_read_stream($fh, filesize($file_name), $tbl_name);
    if (! $pbms_blob_url) {
        PMA_BS_ReportPBMSError("pbms_read_stream()");
    }

    fclose($fh);
    PMA_do_disconnect();
    return $pbms_blob_url;
}

//------------
function PMA_BS_SetContentType($db_name, $bsTable, $blobReference, $contentType)
{
    if (PMA_cacheGet('skip_blobstreaming', true)) {
        return false;
    }

    // This is a really ugly way to do this but currently there is nothing better.
    // In a future version of PBMS the system tables will be redesigned to make this
    // more efficient.
    $query = "SELECT Repository_id, Repo_blob_offset FROM pbms_reference"
        . " WHERE Blob_url='" . PMA_sqlAddSlashes($blobReference) . "'";
    //error_log(" PMA_BS_SetContentType: $query\n", 3, "/tmp/mylog");
    $result = PMA_DBI_query($query);
    //error_log(" $query\n", 3, "/tmp/mylog");

    // if record exists
    if ($data = PMA_DBI_fetch_assoc($result)) {
        $where = "WHERE Repository_id=" . $data['Repository_id']
           . " AND Repo_blob_offset=" . $data['Repo_blob_offset'] ;
        $query = "SELECT name from  pbms_metadata $where";
        $result = PMA_DBI_query($query);

        if (PMA_DBI_num_rows($result) == 0) {
            $query = "INSERT into pbms_metadata Values( ". $data['Repository_id']
                . ", " . $data['Repo_blob_offset']  . ", 'Content_type', '"
                . PMA_sqlAddSlashes($contentType)  . "')";
        } else {
            $query = "UPDATE pbms_metadata SET name = 'Content_type', Value = '"
                . PMA_sqlAddSlashes($contentType) . "' $where";
        }
        //error_log("$query\n", 3, "/tmp/mylog");
        PMA_DBI_query($query);
    } else {
        return false;
    }
    return true;
}

//------------
function PMA_BS_IsHiddenTable($table)
{
    if ($table === 'pbms_repository'
        || $table === 'pbms_reference'
        || $table === 'pbms_metadata'
        || $table === 'pbms_metadata_header'
        || $table === 'pbms_dump'
    ) {
        return true;
    }
    return false;
}

//------------
function PMA_BS_getURL($reference)
{
    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];
    if (empty($PMA_Config)) {
        return false;
    }

    // retrieve BS server variables from PMA configuration
    $bs_server = $PMA_Config->get('BLOBSTREAMING_SERVER');
    if (empty($bs_server)) {
        return false;
    }

    $bs_url = PMA_linkURL('http://' . $bs_server . '/' . rtrim($reference));
    return $bs_url;
}

?>
