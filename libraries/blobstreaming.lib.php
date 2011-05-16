<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package     BLOBStreaming
 */

function initPBMSDatabase()
{
    $query = "create database IF NOT EXISTS pbms;"; // If no other choice then try this.
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
                return TRUE;
            }
            if ($target == "") {
                if ($current_db != 'pbxt' && $current_db != 'mysql' && strtolower($current_db) != 'information_schema'
                        && (!PMA_DRIZZLE || strtolower($current_db) != 'data_dictionary')) {
                    $target = $current_db;
                }
            }
        }

        if ($target != "") {
            $query = "select * from $target.pbms_metadata_header"; // If it exists this table will not contain much
        }
    }

    $result = PMA_DBI_query($query );
    if (! $result) {
        return FALSE;
    }
    return TRUE;
}

/**
 * checks whether the necessary plugins for BLOBStreaming exist
 *
 * @access  public
 * @uses    PMA_Config::get()
 * @uses    PMA_Config::settings()
 * @uses    PMA_Config::set()
 * @uses    PMA_BS_GetVariables()
 * @uses    PMA_cacheSet()
 * @uses    PMA_cacheGet()
 * @return  boolean
*/
function checkBLOBStreamingPlugins()
{
    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config)) {
        return FALSE;
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
        return FALSE;
    }

    // if PHP extension in use is 'mysql', specify element 'PersistentConnections'
    if ($serverCfg['extension'] == "mysql") {
        $serverCfg['PersistentConnections'] = $PMA_Config->settings['PersistentConnections'];
    }

    // if connection type is TCP, unload socket variable
    if (strtolower($serverCfg['connect_type']) == "tcp") {
        $serverCfg['socket'] = "";
    }

    $has_blobstreaming = false;
    if (PMA_MYSQL_INT_VERSION >= 50109) {

        // Retrieve MySQL plugins
        $existing_plugins = PMA_DBI_fetch_result('SHOW PLUGINS');

        foreach ($existing_plugins as $one_existing_plugin) {
            // check if required plugins exist
            if ( strtolower($one_existing_plugin['Library']) == 'libpbms.so'
                && $one_existing_plugin['Status'] == "ACTIVE") {
                $has_blobstreaming = true;
                break;
            }
        }
        unset($existing_plugins, $one_existing_plugin);
    }

    // set variable indicating BS plugin existence
    $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', $has_blobstreaming);

    if ($has_blobstreaming) {
        $bs_variables = PMA_BS_GetVariables();

       // if no BS variables exist, set plugin existence to false and return
        if (count($bs_variables) <= 0) {
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', FALSE);
            PMA_cacheSet('skip_blobstreaming', true, true);
            return FALSE;
        } // end if (count($bs_variables) <= 0)

        // Check that the required pbms functions exist:
        if ((function_exists("pbms_connect") == FALSE) ||
            (function_exists("pbms_error") == FALSE) ||
            (function_exists("pbms_close") == FALSE) ||
            (function_exists("pbms_is_blob_reference") == FALSE) ||
            (function_exists("pbms_get_info") == FALSE) ||
            (function_exists("pbms_get_metadata_value") == FALSE) ||
            (function_exists("pbms_add_metadata") == FALSE) ||
            (function_exists("pbms_read_stream") == FALSE)) {

            // We should probably notify the user that they need to install
            // the pbms client lib and PHP extension to make use of blob streaming.
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', FALSE);
            PMA_cacheSet('skip_blobstreaming', true, true);
            return FALSE;
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

        // if no BS server port or 'pbms' database exists, set plugin existance to false and return
        if ((! $BS_PORT) || (! initPBMSDatabase())) {
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', FALSE);
            PMA_cacheSet('skip_blobstreaming', true, true);
            return FALSE;
        } // end if (!$BS_PORT)

        // Ping PBMS: the database doesn't need to exist for this to work.
        if (pbms_connect($serverCfg['host'], $BS_PORT, "anydb") == FALSE) {
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', FALSE);
            PMA_cacheSet('skip_blobstreaming', true, true);
            return FALSE;
        }
        pbms_close();

        if (function_exists("pbms_pconnect")) {
            $PMA_Config->set('PBMS_PCONNECT_EXISTS', TRUE);
        } else {
            $PMA_Config->set('PBMS_PCONNECT_EXISTS', FALSE);
        }

        // add selected BS, CURL and fileinfo library variables to PMA configuration
        $PMA_Config->set('BLOBSTREAMING_PORT', $BS_PORT);
        $PMA_Config->set('BLOBSTREAMING_HOST', $serverCfg['host']);
        $PMA_Config->set('BLOBSTREAMING_SERVER', $serverCfg['host'] . ':' . $BS_PORT);
        $PMA_Config->set('PHP_PBMS_EXISTS', FALSE);
        $PMA_Config->set('FILEINFO_EXISTS', FALSE);

        // check if PECL's fileinfo library exist
        $finfo = NULL;

        if (function_exists("finfo_open")) {
            $finfo = finfo_open(FILEINFO_MIME);
        }

        // fileinfo library exists, set necessary variable and close resource
        if (! empty($finfo)) {
            $PMA_Config->set('FILEINFO_EXISTS', TRUE);
            finfo_close($finfo);
        } // end if (!empty($finfo))

    } else {
        PMA_cacheSet('skip_blobstreaming', true, true);
        return FALSE;
    } // end if ($has_blobstreaming)

    return TRUE;
}

/**
 * returns a list of BLOBStreaming variables used by MySQL
 *
 * @access  public
 * @uses    PMA_Config::get()
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_fetch_assoc()
 * @return  array - list of BLOBStreaming variables
*/
function PMA_BS_GetVariables()
{
    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return NULL;

    // run query to retrieve BS variables
    $query = "SHOW VARIABLES LIKE '%pbms%'";
    $result = PMA_DBI_query($query);

    $BS_Variables = array();

    // while there are records to retrieve
    while ($data = @PMA_DBI_fetch_assoc($result))
        $BS_Variables[$data['Variable_name']] = $data['Value'];

    // return BS variables
    return $BS_Variables;
}

//========================
//========================
function PMA_BS_ReportPBMSError($msg)
{
    $tmp_err = pbms_error();
    PMA_showMessage(__('PBMS error') . " $msg $tmp_err");
}

//------------
function PMA_do_connect($db_name, $quiet)
{
    $PMA_Config = $GLOBALS['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config)) {
        return FALSE;
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

    if ($ok == FALSE) {
        if ($quiet == FALSE) {
            PMA_BS_ReportPBMSError(__('PBMS connection failed:') . " pbms_connect($pbms_host, $pbms_port, $db_name)");
        }
        return FALSE;
    }
    return TRUE;
}

//------------
function PMA_do_disconnect()
{
    pbms_close();
}

//------------
/**
 * checks whether the BLOB reference looks valid
 *
*/
function PMA_BS_IsPBMSReference($bs_reference, $db_name)
{
    if (PMA_cacheGet('skip_blobstreaming', true)) {
        return FALSE;
    }

    // You do not really need a connection to the PBMS Daemon
    // to check if a reference looks valid but unfortunalty the API
    // requires one at this point so until the API is updated
    // we need to epen one here. If you use pool connections this
    // will not be a performance problem.
     if (PMA_do_connect($db_name, FALSE) == FALSE) {
        return FALSE;
    }

    $ok = pbms_is_blob_reference($bs_reference);
    return $ok ;
}

//------------
function PMA_BS_CreateReferenceLink($bs_reference, $db_name)
{
    if (PMA_do_connect($db_name, FALSE) == FALSE) {
        return __('Error');
    }

    if (pbms_get_info(trim($bs_reference)) == FALSE) {
        PMA_BS_ReportPBMSError(__('PBMS get BLOB info failed:') . " pbms_get_info($bs_reference)");
        PMA_do_disconnect();
        return __('Error');
    }

    $content_type = pbms_get_metadata_value("Content-Type");
    if ($content_type == FALSE) {
        $br = trim($bs_reference);
        PMA_BS_ReportPBMSError("PMA_BS_CreateReferenceLink('$br', '$db_name'): " . __('get BLOB Content-Type failed'));
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

    //$output = "<a href=\"#\" onclick=\"requestMIMETypeChange('" . urlencode($db_name) . "', '" . urlencode($GLOBALS['table']) . "', '" . urlencode($bs_reference) . "', '" . urlencode($content_type) . "')\">$content_type</a>";
    $output = $content_type;

    // specify custom HTML for various content types
    switch ($content_type) {
        // no content specified
        case NULL:
            $output = "NULL";
            break;
        // image content
        case 'image/jpeg':
        case 'image/png':
            $output .= ' (<a href="' . $bs_url . '" target="new">' . __('View image') . '</a>)';
        break;
        // audio content
        case 'audio/mpeg':
            $output .= ' (<a href="#" onclick="popupBSMedia(\'' . PMA_generate_common_url() . '\',\'' . urlencode($bs_reference) . '\', \'' . urlencode($content_type) . '\',' . ($is_custom_type ? 1 : 0) . ', 640, 120)">' . __('Play audio'). '</a>)';
            break;
        // video content
        case 'application/x-flash-video':
        case 'video/mpeg':
            $output .= ' (<a href="#" onclick="popupBSMedia(\'' . PMA_generate_common_url() . '\',\'' . urlencode($bs_reference) . '\', \'' . urlencode($content_type) . '\',' . ($is_custom_type ? 1 : 0) . ', 640, 480)">' . __('View video') . '</a>)';
            break;
        // unsupported content. specify download
        default:
            $output .= ' (<a href="' . $bs_url . '" target="new">' . __('Download file'). '</a>)';
    }

    return $output;
}

//------------
// In the future there may be server variables to turn on/off PBMS
// BLOB streaming on a per table or database basis. So in anticipation of this
// PMA_BS_IsTablePBMSEnabled() passes in the table and database name even though
// they are not currently needed.
function PMA_BS_IsTablePBMSEnabled($db_name, $tbl_name, $tbl_type)
{
    if (PMA_cacheGet('skip_blobstreaming', true)) {
        return FALSE;
    }

    if ((isset($tbl_type) == FALSE) || (strlen($tbl_type) == 0)) {
        return FALSE;
    }

    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config)) {
        return FALSE;
    }

    if (! $PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST')) {
        return FALSE;
    }

    // This information should be cached rather than selecting it each time.
    //$query = "SELECT count(*)  FROM information_schema.TABLES T, pbms.pbms_enabled E where T.table_schema = ". PMA_backquote($db_name) . " and T.table_name = ". PMA_backquote($tbl_name) . " and T.engine = E.name";
    $query = "SELECT count(*)  FROM pbms.pbms_enabled E where E.name = '" . PMA_sqlAddslashes($tbl_type) . "'";
    $result = PMA_DBI_query($query);

    $data = PMA_DBI_fetch_row($result);
    if ($data[0] == 1) {
        return TRUE;
    }

    return FALSE;
}

//------------
function PMA_BS_UpLoadFile($db_name, $tbl_name, $file_type, $file_name)
{

    if (PMA_cacheGet('skip_blobstreaming', true)) {
        return FALSE;
    }

    if (PMA_do_connect($db_name, FALSE) == FALSE) {
        return FALSE;
    }

    $fh = fopen($file_name, 'r');
    if (! $fh) {
        PMA_do_disconnect();
        PMA_showMessage(sprintf(__('Could not open file: %s'), $file_name));
        return FALSE;
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
        return FALSE;
    }

    // This is a really ugly way to do this but currently there is nothing better.
    // In a future version of PBMS the system tables will be redesigned to make this
    // more efficient.
    $query = "SELECT Repository_id, Repo_blob_offset FROM pbms_reference  WHERE Blob_url='" . PMA_sqlAddslashes($blobReference) . "'";
    //error_log(" PMA_BS_SetContentType: $query\n", 3, "/tmp/mylog");
    $result = PMA_DBI_query($query);
    //error_log(" $query\n", 3, "/tmp/mylog");

// if record exists
    if ($data = PMA_DBI_fetch_assoc($result)) {
        $where = "WHERE Repository_id=" . $data['Repository_id'] . " AND Repo_blob_offset=" . $data['Repo_blob_offset'] ;
        $query = "SELECT name from  pbms_metadata $where";
        $result = PMA_DBI_query($query);

        if (PMA_DBI_num_rows($result) == 0) {
            $query = "INSERT into pbms_metadata Values( ". $data['Repository_id'] . ", " . $data['Repo_blob_offset']  . ", 'Content_type', '" . PMA_sqlAddslashes($contentType)  . "')";
        } else {
            $query = "UPDATE pbms_metadata SET name = 'Content_type', Value = '" . PMA_sqlAddslashes($contentType)  . "' $where";
        }
//error_log("$query\n", 3, "/tmp/mylog");
        PMA_DBI_query($query);
    } else {
        return FALSE;
    }
    return TRUE;
}

//------------
function PMA_BS_IsHiddenTable($table)
{
    if ($table === 'pbms_repository' || $table === 'pbms_reference' || $table === 'pbms_metadata'
    || $table === 'pbms_metadata_header' || $table === 'pbms_dump') {
        return TRUE;
    }
    return FALSE;
}

//------------
function PMA_BS_getURL($reference)
{
    // load PMA configuration
    $PMA_Config = $GLOBALS['PMA_Config'];
    if (empty($PMA_Config)) {
        return FALSE;
    }

    // retrieve BS server variables from PMA configuration
    $bs_server = $PMA_Config->get('BLOBSTREAMING_SERVER');
    if (empty($bs_server)) {
        return FALSE;
    }

    $bs_url = PMA_linkURL('http://' . $bs_server . '/' . rtrim($reference));
    return $bs_url;
}

?>
