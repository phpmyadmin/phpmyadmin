<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
    /**
     * @author      Raj Kissu Rajandran
     * @version     1.0
     * @package     BLOBStreaming
     */

    /**
     * Core library.
     */
    require_once './libraries/common.inc.php';

    /**
     * @var     string  contains database name
     */
    $bsDB = isset($_REQUEST['bs_db']) ? urldecode($_REQUEST['bs_db']) : NULL;

    /**
     * @var     string  contains table name
     */
    $bsTable = isset($_REQUEST['bs_table']) ? urldecode($_REQUEST['bs_table']) : NULL;

    /**
     * @var     string  contains BLOB reference
     */
    $bsReference = isset($_REQUEST['bs_reference']) ? urldecode($_REQUEST['bs_reference']) : NULL;

    /**
     * @var     string  contains MIME type
     */
    $bsNewMIMEType = isset($_REQUEST['bs_new_mime_type']) ? urldecode($_REQUEST['bs_new_mime_type']) : NULL;

    // necessary variables exist
    if ($bsDB && $bsTable && $bsReference && $bsNewMIMEType)
    {
        // load PMA configuration
        $PMA_Config = $_SESSION['PMA_Config'];

        // if PMA configuration exists
        if (!empty($PMA_Config))
        {
            // if BS plugins exist
            if ($PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST'))
            {
                $pbms_ref_tbl = $PMA_Config->get('PBMS_NAME') . '_reference';
                $pbms_cust_content_type_tbl = $PMA_Config->get('PBMS_NAME') . '_custom_content_type';

                // if specified DB is selected
                if (PMA_DBI_select_db($bsDB))
                {
                    $query = "SELECT * FROM " . PMA_backquote($pbms_ref_tbl);
                    $query .= " WHERE Blob_url='" . PMA_sqlAddslashes($bsReference) . "'";

                    $result = PMA_DBI_query($query);

                    // if record exists
                    if ($data = PMA_DBI_fetch_assoc($result))
                    {
                        $query = "SELECT count(*) FROM " . PMA_backquote($pbms_cust_content_type_tbl);
			$query .= " WHERE Blob_url='" . PMA_sqlAddslashes($bsReference) . "'";

                        $result = PMA_DBI_query($query);

                        // if record exists
                        if ($data = PMA_DBI_fetch_assoc($result))
                        {
                            if (1 == $data['count(*)'])
                            {
                                $query = "UPDATE " . PMA_backquote($pbms_cust_content_type_tbl) . " SET Content_type='";
                                $query .= PMA_sqlAddslashes($bsNewMIMEType) . "' WHERE Blob_url='" . PMA_sqlAddslashes($bsReference) . "'";
                            }
                            else
                            {
                                $query = "INSERT INTO " . PMA_backquote($pbms_cust_content_type_tbl) . " (Blob_url, Content_type)";
                                $query .= " VALUES('" . PMA_sqlAddslashes($bsReference) . "', '" . PMA_sqlAddslashes($bsNewMIMEType) . "')";
                            }

                            $result = PMA_DBI_query($query);

                            // if query execution succeeded
                            if ($result)
                            {
                                // determine redirector page
                                $newLoc = $cfg['PmaAbsoluteUri'] . 'sql.php?' . PMA_generate_common_url ('','', '&') . (isset($bsDB) ? '&db=' . urlencode($bsDB) : '') . (isset($bsTable) ? '&table=' . urlencode($bsTable) : '') . (isset($token) ? '&token=' . urlencode($token) : '') . (isset($goto) ? '&goto=' . urlencode($goto) : '') . '&reload=1&purge=1';

                                // redirect to specified page
                                ?>
                                <script>
                                    window.location = "<?php echo $newLoc ?>";
                                </script>
                                <?php
                            } // end if ($result)
                        } // end if ($data = PMA_DBI_fetch_assoc($result))
                    } // end if ($data = PMA_DBI_fetch_assoc($result))
                } // end if (PMA_DBI_select_db($bsDB))
            } // end if ($PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST'))
        } // end if (!empty($PMA_Config))
    } // end if ($bsDB && $bsTable && $bsReference && $bsNewMIMEType)

?>
