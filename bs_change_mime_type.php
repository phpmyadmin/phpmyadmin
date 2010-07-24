<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
    /**
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
        if (PMA_BS_SetContentType($bsDB, $bsTable, $bsReference, $bsNewMIMEType)) {
            // determine redirector page
            $newLoc = $cfg['PmaAbsoluteUri'] . 'sql.php?' . PMA_generate_common_url ('','', '&') . (isset($bsDB) ? '&db=' . urlencode($bsDB) : '') . (isset($bsTable) ? '&table=' . urlencode($bsTable) : '') . (isset($token) ? '&token=' . urlencode($token) : '') . (isset($goto) ? '&goto=' . urlencode($goto) : '') . '&reload=1&purge=1';

            // redirect to specified page
            ?>
<script>
window.location = "<?php echo $newLoc ?>";
</script>
            <?php
        } // end if ($result)
    } // end if ($bsDB && $bsTable && $bsReference && $bsNewMIMEType)

?>
