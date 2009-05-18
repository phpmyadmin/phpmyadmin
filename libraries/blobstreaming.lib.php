<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @author	    Raj Kissu Rajandran
 * @version     1.0
 * @package     BLOBStreaming
 */

/**
 * checks whether the necessary plugins for BLOBStreaming exist
 *
 * @access  public
 * @uses    PMA_Config::get()
 * @uses    PMA_Config::settings()
 * @uses    PMA_Config::set()
 * @uses    PMA_PluginsExist()
 * @uses    PMA_BS_SetVariables()
 * @uses    PMA_BS_GetVariables()
 * @uses    PMA_BS_SetFieldReferences()
 * @return  boolean
*/
function checkBLOBStreamingPlugins()
{
    // load PMA configuration
    $PMA_Config = $_SESSION['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return FALSE;

    // retrieve current server configuration
    $serverCfg = $PMA_Config->get('Servers');

    if (isset($serverCfg[$GLOBALS['server']]))
        $serverCfg = $serverCfg[$GLOBALS['server']];
    else
	$serverCfg = null;

    // return if unable to retrieve current server configuration
    if (!isset($serverCfg))
        return FALSE;

    // if PHP extension in use is 'mysql', specify element 'PersistentConnections'
    if (isset($serverCfg['extension']) && "mysql" == $serverCfg['extension'])
        $serverCfg['PersistentConnections'] = $PMA_Config->settings['PersistentConnections'];

    // if connection type is TCP, unload socket variable
    if (isset($serverCfg['connect_type']) && "tcp" == strtolower($serverCfg['connect_type']))
        $serverCfg['socket'] = "";

    // define BS Plugin variables
    $allPluginsExist = TRUE;

    $PMA_Config->set('PBXT_NAME', 'pbxt');
    $PMA_Config->set('PBMS_NAME', 'pbms');

    $plugins[$PMA_Config->get('PBXT_NAME')]['Library'] = 'libpbxt.so';
    $plugins[$PMA_Config->get('PBXT_NAME')]['Exists'] = FALSE;

    $plugins[$PMA_Config->get('PBMS_NAME')]['Library'] = 'libpbms.so';
    $plugins[$PMA_Config->get('PBMS_NAME')]['Exists'] = FALSE;

    // retrieve state of BS plugins
    PMA_PluginsExist($plugins);

    foreach ($plugins as $plugin_key=>$plugin)
        if (!$plugin['Exists'])
        {
            $allPluginsExist = FALSE;
            break;
        } // end if (!$plugin['Exists'])

    // set variable indicating BS plugin existance
    $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', $allPluginsExist);

    // do the plugins exist?
    if ($allPluginsExist)
    {
        // retrieve BS variables from PMA configuration
        $bs_set_variables = array();

        $bs_set_variables[$PMA_Config->get('PBMS_NAME') . '_garbage_threshold'] = (isset($serverCfg['bs_garbage_threshold'])) ? $serverCfg['bs_garbage_threshold'] : NULL;
        $bs_set_variables[$PMA_Config->get('PBMS_NAME') . '_repository_threshold'] = (isset($serverCfg['bs_repository_threshold'])) ? $serverCfg['bs_repository_threshold'] : NULL;
        $bs_set_variables[$PMA_Config->get('PBMS_NAME') . '_temp_blob_timeout'] = (isset($serverCfg['bs_temp_blob_timeout'])) ? $serverCfg['bs_temp_blob_timeout'] : NULL;
        $bs_set_variables[$PMA_Config->get('PBMS_NAME') . '_temp_log_threshold'] = (isset($serverCfg['bs_temp_log_threshold'])) ? $serverCfg['bs_temp_log_threshold'] : NULL;

        // set BS variables to PMA configuration defaults
        PMA_BS_SetVariables($bs_set_variables);
        
        // retrieve updated BS variables (configurable and unconfigurable)
        $bs_variables = PMA_BS_GetVariables();

        // if no BS variables exist, set plugin existance to false and return
        if (count($bs_variables) <= 0)
        {
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', FALSE);
            return FALSE;
        } // end if (count($bs_variables) <= 0)

        // switch on BS field references
        if (strtolower($bs_variables[$PMA_Config->get('PBMS_NAME') . '_field_references']) == "off")
            if(!PMA_BS_SetFieldReferences('ON'))
		    return FALSE;

        // get BS server port
        $BS_PORT = $bs_variables[$PMA_Config->get('PBMS_NAME') . '_port'];

        // if no BS server port exists, set plugin existance to false and return
        if (!$BS_PORT)
        {
            $PMA_Config->set('BLOBSTREAMING_PLUGINS_EXIST', FALSE);
            return FALSE;
        } // end if (!$BS_PORT)

        // add selected BS, CURL and fileinfo library variables to PMA configuration
        $PMA_Config->set('BLOBSTREAMING_PORT', $BS_PORT);
        $PMA_Config->set('BLOBSTREAMING_HOST', $serverCfg['host']);
        $PMA_Config->set('BLOBSTREAMING_SERVER', $serverCfg['host'] . ':' . $BS_PORT);
        $PMA_Config->set('CURL_EXISTS', FALSE);
        $PMA_Config->set('FILEINFO_EXISTS', FALSE);

        // check if CURL exists
        if (function_exists("curl_init"))
        {
            // initialize curl handler
            $curlHnd = curl_init();

            // CURL exists, set necessary variable and close resource
            if (!empty($curlHnd))
            {
                $PMA_Config->set('CURL_EXISTS', TRUE);
                curl_close($curlHnd);                
            } // end if (!empty($curlHnd))
        } // end if (function_exists("curl_init"))

        // check if PECL's fileinfo library exist
        $finfo = NULL;

        if (function_exists("finfo_open"))
            $finfo = finfo_open(FILEINFO_MIME);

        // fileinfo library exists, set necessary variable and close resource
        if (!empty($finfo))
        {
            $PMA_Config->set('FILEINFO_EXISTS', TRUE);
            finfo_close($finfo);
        } // end if (!empty($finfo))
    } // end if ($allPluginsExist)
    else
        return FALSE;

    $bs_tables = array();

    // specify table structure for BS reference table
    $bs_tables[$PMA_Config->get('PBMS_NAME') . '_reference'] = array();
    $bs_tables[$PMA_Config->get('PBMS_NAME') . '_reference']['struct'] = <<<EOD
        CREATE TABLE {$PMA_Config->get('PBMS_NAME')}_reference
        (
         Table_name        CHAR(64) COMMENT 'The name of the referencing table',
         Blob_id           BIGINT COMMENT 'The BLOB reference number - part of the BLOB URL',
         Column_name       CHAR(64) COMMENT 'The column name of the referencing field',
         Row_condition     VARCHAR(255) COMMENT 'This condition identifies the row in the table',
         Blob_url          VARCHAR(200) COMMENT 'The BLOB URL for HTTP GET access',
         Repository_id     INT COMMENT 'The repository file number of the BLOB',
         Repo_blob_offset  BIGINT COMMENT 'The offset in the repository file',
         Blob_size         BIGINT COMMENT 'The size of the BLOB in bytes',
         Deletion_time     TIMESTAMP COMMENT 'The time the BLOB was deleted',
         Remove_in         INT COMMENT 'The number of seconds before the reference/BLOB is removed perminently',
         Temp_log_id       INT COMMENT 'Temporary log number of the referencing deletion entry',
         Temp_log_offset   BIGINT COMMENT 'Temporary log offset of the referencing deletion entry'
        ) ENGINE=PBMS;
EOD;

    // specify table structure for BS repository table
    $bs_tables[$PMA_Config->get('PBMS_NAME') . '_repository'] = array();
    $bs_tables[$PMA_Config->get('PBMS_NAME') . '_repository']['struct'] = <<<EOD
        CREATE TABLE {$PMA_Config->get('PBMS_NAME')}_repository
        (
         Repository_id     INT COMMENT 'The repository file number',
         Repo_blob_offset  BIGINT COMMENT 'The offset of the BLOB in the repository file',
         Blob_size         BIGINT COMMENT 'The size of the BLOB in bytes',
         Head_size         SMALLINT UNSIGNED COMMENT 'The size of the BLOB header - proceeds the BLOB data',
         Access_code       INT COMMENT 'The 4-byte authorisation code required to access the BLOB - part of the BLOB URL',
         Creation_time     TIMESTAMP COMMENT 'The time the BLOB was created',
         Last_ref_time     TIMESTAMP COMMENT 'The last time the BLOB was referenced',
         Last_access_time  TIMESTAMP COMMENT 'The last time the BLOB was accessed (read)',
         Content_type      CHAR(128) COMMENT 'The content type of the BLOB - returned by HTTP GET calls',
         Blob_data         LONGBLOB COMMENT 'The data of this BLOB'
        ) ENGINE=PBMS;
EOD;

    // specify table structure for BS custom content type table
    $bs_tables[$PMA_Config->get('PBMS_NAME') . '_custom_content_type'] = array();
    $bs_tables[$PMA_Config->get('PBMS_NAME') . '_custom_content_type']['struct'] = <<<EOD
        CREATE TABLE {$PMA_Config->get('PBMS_NAME')}_custom_content_type
        (
         Blob_url           VARCHAR(200) COMMENT 'The BLOB URL for HTTP GET access',
         Content_type       VARCHAR(255) COMMENT 'The custom MIME type for a given BLOB reference as specified by the user',

         PRIMARY KEY(Blob_url)
        );
EOD;

    // add BS tables to PMA configuration
    $PMA_Config->set('BLOBSTREAMING_TABLES', $bs_tables);

    return TRUE;
}

/**
 * checks for databases that support BLOBStreaming
 *
 * @access  public
 * @uses    PMA_GetDatabases()
 * @uses    PMA_TablesExist()
 * @uses    PMA_Config::set()
*/
function checkBLOBStreamableDatabases()
{
    // load PMA configuration
    $PMA_Config = $_SESSION['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return;

    // retrieve BS tables from PMA configuration
    $session_bs_tables = $PMA_Config->get('BLOBSTREAMING_TABLES');

    $bs_databases = array();
    $bs_tables = array();

    // return if BS tables do not exist
    if (!$session_bs_tables)
        return;

    foreach ($session_bs_tables as $table_key=>$table)
    {
        $bs_tables[$table_key] = array();
        $bs_tables[$table_key]['Exists'] = FALSE;
    }

    // retrieve MySQL databases
    $databases = PMA_GetDatabases();

    // check if BS tables exist for each database
    foreach ($databases as $db_key=>$db_name)
    {
        $bs_databases[$db_name] = $bs_tables;

        PMA_TablesExist($bs_databases[$db_name], $db_name);
    }

    // set BS databases in PMA configuration
    $PMA_Config->set('BLOBSTREAMABLE_DATABASES', $bs_databases);
}

/**
 * checks whether a set of plugins exist
 *
 * @access  public
 * @param   array - a list of plugin names and accompanying library filenames to check for
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_fetch_assoc()
*/
function PMA_PluginsExist(&$plugins)
{   
    if (PMA_MYSQL_INT_VERSION < 50109) {
        return;
    }
    // run query to retrieve MySQL plugins
    $query = "SHOW PLUGINS";
    $result = PMA_DBI_query($query);

    // while there are records to parse
	while ($data = @PMA_DBI_fetch_assoc($result))
	{
        // reset plugin state
        $state = TRUE;

        // check if required plugins exist
		foreach ($plugins as $plugin_key=>$plugin)
			if (!$plugin['Exists'])
				if (
					strtolower($data['Library']) == strtolower($plugin['Library']) &&
					$data['Status'] == "ACTIVE"
				   )
					$plugins[$plugin_key]['Exists'] = TRUE;
                else
                    if ($state)
                        $state = FALSE;

        // break if all necessary plugins are found before all records are parsed
        if ($state)
            break;
    } // end while ($data = @PMA_DBI_fetch_assoc($result))
}

/**
 * checks whether a given set of tables exist in a given database
 *
 * @access  public
 * @param   array - list of tables to look for
 * @param   string - name of database
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_fetch_assoc()
 */
function PMA_TablesExist(&$tables, $db_name)
{
    // select specified database
    PMA_DBI_select_db($db_name);

    // run query to retrieve tables in specified database
    $query = "SHOW TABLES";
    $result = PMA_DBI_query($query);

    // while there are records to parse
    while ($data = @PMA_DBI_fetch_assoc($result))
    {
        $state = TRUE;

        // check if necessary tables exist
        foreach ($tables as $table_key=>$table)
            if (!$table['Exists'])
                if ($data['Tables_in_' . $db_name] == $table_key)
                    $tables[$table_key]['Exists'] = TRUE;
                else
                    if ($state)
                        $state = FALSE;

        // break if necessary tables are found before all records are parsed
        if ($state)
            break;
    } // end while ($data = @PMA_DBI_fetch_assoc($result))
}

/**
 * returns a list of databases
 *
 * @access  public
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_fetch_assoc()
 * @return  array - list of databases acquired via MySQL
*/
function PMA_GetDatabases()
{
    // run query to retrieve databases
    $query = "SHOW DATABASES";
    $result = PMA_DBI_query($query);

    $databases = array();

    // while there are records to parse
    while ($data = @PMA_DBI_fetch_assoc($result))
        $databases[] = $data['Database'];

    // return list of databases
    return $databases;
}

/**
 * sets BLOBStreaming variables to a list of specified arguments
 * @access  public
 * @uses    PMA_DBI_query()
 * @returns boolean - success of variables setup
*/

function PMA_BS_SetVariables($bs_variables)
{
    // if no variables exist in array, return false
    if (empty($bs_variables) || count($bs_variables) == 0)
        return FALSE;

    // set BS variables to those specified in array
    foreach ($bs_variables as $key=>$val)
        if (!is_null($val) && strlen($val) > 0)
        {
            // set BS variable to specified value
            $query = "SET GLOBAL $key=" . PMA_sqlAddSlashes($val);
            $result = PMA_DBI_query($query);

            // if query fails execution, return false
            if (!$result)
                return FALSE;
        } // end if (!is_null($val) && strlen($val) > 0)

    // return true on success
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
    $PMA_Config = $_SESSION['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return NULL;

    // run query to retrieve BS variables
    $query = "SHOW VARIABLES LIKE '%" . $PMA_Config->get('PBMS_NAME') . "%'";
    $result = PMA_DBI_query($query);

    $BS_Variables = array();

    // while there are records to retrieve
    while ($data = @PMA_DBI_fetch_assoc($result))
        $BS_Variables[$data['Variable_name']] = $data['Value'];

    // return BS variables
    return $BS_Variables;
}

/**
 * sets the BLOBStreaming global field references to ON/OFF
 *
 * @access  public
 * @param   string - ON or OFF
 * @uses    PMA_Config::get()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_query()
 * @return  boolean - success/failure of query execution
*/
function PMA_BS_SetFieldReferences($val)
{
    // load PMA configuration
    $PMA_Config = $_SESSION['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return FALSE;

    // set field references to value specified
    $query = "SET GLOBAL " . $PMA_Config->get('PBMS_NAME') . "_field_references=" . PMA_sqlAddslashes($val);
    $result = PMA_DBI_try_query($query, null, 0);

    // get last known error (if applicable)
    PMA_DBI_getError();

    // return success of query execution
    if ($result && 0 == $GLOBALS['errno'])
        return TRUE;
    else
        return FALSE;
}

/**
 * gets the SQL table definition for a given BLOBStreaming table
 *
 * @access  public
 * @param   string - table name
 * @uses    PMA_Config::get()
 * @return  string - SQL table definition
*/
function PMA_BS_GetTableStruct($tbl_name)
{
    // retrieve table structures for BS tables
    $bs_tables = $_SESSION['PMA_Config']->get('BLOBSTREAMING_TABLES');
   
    // return if tables don't exist 
    if (!$bs_tables)
        return;

    // return if specified table doesn't exist in collection of BS tables
    if (!isset($bs_tables[$tbl_name]))
        return;

    // return specified table's structure
    return $bs_tables[$tbl_name]['struct'];
}

/**
 * creates the BLOBStreaming tables for a given database
 *
 * @access  public
 * @param   string - database name
 * @uses    PMA_Config::get()
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_DBI_query()
 * @uses    PMA_BS_GetTableStruct()
 * @return  boolean - success/failure of transactional query execution
*/
function PMA_BS_CreateTables($db_name)
{
    // retrieve BS tables
    $bs_tables = $_SESSION['PMA_Config']->get('BLOBSTREAMING_TABLES');

    // select specified database
    PMA_DBI_select_db($db_name);

    // create necessary BS tables for specified database
    foreach ($bs_tables as $table_key=>$table)
    {
        $result = PMA_DBI_query(PMA_BS_GetTableStruct($table_key));

        // return false if query execution fails
        if (!$result)
            return FALSE;
    }

    // return true on success
    return TRUE;
}

/**
 * drops BLOBStreaming tables for a given database
 *
 * @access  public
 * @param   string - database name
 * @uses    PMA_Config::get()
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_query()
 * @return  boolean - success/failure of transactional query execution
*/
function PMA_BS_DropTables($db_name)
{
    // load PMA configuration
    $PMA_Config = $_SESSION['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return FALSE;

    // retrieve BS tables
    $bs_tables = $PMA_Config->get('BLOBSTREAMING_TABLES');

    // select specified database
    PMA_DBI_select_db($db_name);

    // drop BS tables
    foreach ($bs_tables as $table_key=>$table)
    {
        $query = "DROP TABLE IF EXISTS " . PMA_backquote($table_key);
        $result = PMA_DBI_query($query);

        // return false if query execution fails
        if (!$result)
            return FALSE;
    }

    // return true on success
    return TRUE;
}

/**
 * returns the field name for a primary key of a given table in a given database
 *
 * @access  public
 * @param   string - database name
 * @param   string - table name
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_fetch_assoc()
 * @return  string - field name for primary key
*/
function PMA_BS_GetPrimaryField($db_name, $tbl_name)
{
    // load PMA configuration
    $PMA_Config = $_SESSION['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return FALSE;

    // select specified database
    PMA_DBI_select_db($db_name);

    // retrieve table fields
    $query = "SHOW FULL FIELDS FROM " . PMA_backquote($tbl_name);
    $result = PMA_DBI_query($query);

    // while there are records to parse
    while ($data = PMA_DBI_fetch_assoc($result))
        if ("PRI" == $data['Key'])
            return $data['Field'];

    // return NULL on no primary key
    return NULL;
}

/**
 * checks whether a BLOB reference exists in the BLOB repository
 *
 * @access  public
 * @param   string - BLOB reference
 * @param   string - database name
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_backquote()
 * @uses    PMA_Config::get()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_query()
 * @return  boolean - existence of BLOB reference
*/
function PMA_BS_ReferenceExists($bs_reference, $db_name)
{
    $referenceExists = FALSE;

    // return false on invalid BS reference
    if (strlen ($bs_reference) < strlen ("~*$db_name/~") || "~*$db_name/~" != substr ($bs_reference, 0, strlen ($db_name) + 4))
        return $referenceExists;

    // load PMA configuration
    $PMA_Config = $_SESSION['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return $referenceExists;

    // select specified database
    PMA_DBI_select_db($db_name);

    // run query on BS reference retrieval
    $query = "SELECT * FROM " . PMA_backquote($PMA_Config->get('PBMS_NAME') . "_reference") . " WHERE Blob_url='" . PMA_sqlAddslashes($bs_reference) . "'";
    $result = PMA_DBI_query($query);

    // if record exists
    if ($data = @PMA_DBI_fetch_assoc($result))
        $referenceExists = TRUE;

    // return reference existance
    return $referenceExists;
}

/**
 * creates a HTTP link to a given blob reference for a given database
 *
 * @access  public
 * @param   string - BLOB reference
 * @param   string - database name
 * @uses    PMA_Config::get()
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_fetch_assoc()
 * @return  string - HTTP link or Error
*/
function PMA_BS_CreateReferenceLink($bs_reference, $db_name)
{
    // load PMA configuration
    $PMA_Config = $_SESSION['PMA_Config'];

    // return if unable to load PMA configuration
    if (empty($PMA_Config))
        return '';

    // generate bs reference link
    $bs_ref_link = 'http://' . $PMA_Config->get('BLOBSTREAMING_SERVER') . '/' . $bs_reference;

    // select specified database
    PMA_DBI_select_db($db_name);

    $pbms_repo_bq = PMA_backquote($PMA_Config->get('PBMS_NAME') . "_repository");
    $pbms_ref_bq = PMA_backquote($PMA_Config->get('PBMS_NAME') . "_reference");
    $pbms_cust_content_bq = PMA_backquote($PMA_Config->get('PBMS_NAME') . "_custom_content_type");

    // run query on determining specified BS reference
    $query = "SELECT $pbms_repo_bq.Content_type, $pbms_cust_content_bq.Content_type AS Custom_type";
    $query .= " FROM $pbms_repo_bq LEFT JOIN $pbms_ref_bq ON";
    $query .= "$pbms_repo_bq.Repository_id=$pbms_ref_bq.Repository_id";
    $query .= " AND $pbms_repo_bq.Blob_size=$pbms_ref_bq.Blob_size";
    $query .= " AND $pbms_repo_bq.Repo_blob_offset=$pbms_ref_bq.Repo_blob_offset";
    $query .= " LEFT JOIN $pbms_cust_content_bq ON $pbms_cust_content_bq.Blob_url=$pbms_ref_bq.Blob_url";
    $query .= " WHERE $pbms_ref_bq.Blob_url='" . PMA_sqlAddslashes($bs_reference) . "'";

    $result = PMA_DBI_query($query);

    // if record exists
    if ($data = @PMA_DBI_fetch_assoc($result))
    {
        // determine content-type for BS repository file (original or custom)
	$is_custom_type = false;

	if (isset($data['Custom_type']))
	{
	        $content_type = $data['Custom_type'];
		$is_custom_type = true;
	}
	else
		$content_type = $data['Content_type'];

        if (!$content_type)
            $content_type = NULL;

        $output = "<a href=\"#\" onclick=\"requestMIMETypeChange('" . urlencode($db_name) . "', '" . urlencode($GLOBALS['table']) . "', '" . urlencode($bs_reference) . "', '" . urlencode($content_type) . "')\">$content_type</a>";

        // specify custom HTML for various content types
        switch ($content_type)
        {
            // no content specified
            case NULL:
                $output = "NULL";
                break;
            // image content
            case 'image/jpeg':
            case 'image/png':
                $output .= ' (<a href="' . $bs_ref_link . '" target="new">' . $GLOBALS['strViewImage'] . '</a>)';
                break;
            // audio content
            case 'audio/mpeg':
                $output .= ' (<a href="#" onclick="popupBSMedia(\'' . PMA_generate_common_url() . '\',\'' . urlencode($bs_reference) . '\', \'' . urlencode($content_type) . '\',' . ($is_custom_type ? 1 : 0) . ', 640, 120)">' . $GLOBALS['strPlayAudio']. '</a>)';
                break;
            // video content
            case 'application/x-flash-video':
            case 'video/mpeg':
                $output .= ' (<a href="#" onclick="popupBSMedia(\'' . PMA_generate_common_url() . '\',\'' . urlencode($bs_reference) . '\', \'' . urlencode($content_type) . '\',' . ($is_custom_type ? 1 : 0) . ', 640, 480)">' . $GLOBALS['strViewVideo'] . '</a>)';
                break;
            // unsupported content. specify download
            default:
                $output .= ' (<a href="' . $bs_ref_link . '" target="new">' . $GLOBALS['strDownloadFile']. '</a>)';
        }

        // return HTML
        return $output;
    } // end if ($data = @PMA_DBI_fetch_assoc($result))

    // return on error
    return 'Error';
}

?>
