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

    /*
     * @var     string  contains media type of BLOB reference
     */
    $mediaType = isset($_REQUEST['media_type']) ? $_REQUEST['media_type'] : NULL;

    /*
     * @var     string	indicates whether media type is of custom type
     */
    $customType = isset($_REQUEST['custom_type']) ? $_REQUEST['custom_type'] : false;

    /*
     * @var     string  contains BLOB reference
     */
    $bsReference = isset($_REQUEST['bs_reference']) ? $_REQUEST['bs_reference'] : NULL;

    // if media type and BS reference are specified
    if (isset($mediaType) && isset($bsReference))
    {
        // load PMA configuration
        $PMA_Config = $_SESSION['PMA_Config'];

        // if PMA configuration exists
        if (!empty($PMA_Config))
        {
            // retrieve BS server variables from PMA configuration
            $bs_server = $PMA_Config->get('BLOBSTREAMING_SERVER');
            if (empty($bs_server)) die('No blob streaming server configured!');

            $bs_file_path = "http://" . $bs_server . '/' . $bsReference;

	    if (isset($customType) && $customType)

		    $bs_file_path = 'bs_disp_as_mime_type.php' . PMA_generate_common_url(array('reference' => $bsReference, 'c_type' => $mediaType));

            ?>
<html>
    <head>
    </head>
    <body>
            <?php

            // supported media types
            switch ($mediaType)
            {
                // audio content
                case 'audio/mpeg':
                    ?><embed width=620 height=100 src="<?php echo htmlspecialchars($bs_file_path); ?>" autostart=true></embed><?php
                    break;
                // video content
                case 'application/x-flash-video':
                case 'video/mpeg':
                    ?><embed width=620 height=460 src="<?php echo htmlspecialchars($bs_file_path); ?>" autostart=true></embed><?php
                    break;
                default:
                    // do nothing
            }
            ?>
    </body>
</html>
            <?php
        } // end if (!empty($PMA_Config))
    } // end if (isset($mediaType) && isset($bsReference))

?>
