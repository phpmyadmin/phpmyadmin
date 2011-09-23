<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
    /**
     * @package     BLOBStreaming
     */

    /**
     * Core library.
     */
    require_once './libraries/common.inc.php';

    /*
     * @var     string  contains media type of BLOB reference
     */
    $mediaType = isset($_REQUEST['media_type']) ? $_REQUEST['media_type'] : null;

    /*
     * @var     string  indicates whether media type is of custom type
     */
    $customType = isset($_REQUEST['custom_type']) ? $_REQUEST['custom_type'] : false;

    /*
     * @var     string  contains BLOB reference
     */
    $bsReference = isset($_REQUEST['bs_reference']) ? $_REQUEST['bs_reference'] : null;

    // if media type and BS reference are specified
    if (isset($mediaType) && isset($bsReference)) {
        if (isset($customType) && $customType) {
            $bs_file_path = 'bs_disp_as_mime_type.php' . PMA_generate_common_url(array('reference' => $bsReference, 'c_type' => $mediaType));
        } else {
            // Get the BLOB streaming URL
            $bs_file_path = PMA_BS_getURL($bsReference);
            if (empty($bs_file_path)) {
                die(__('No blob streaming server configured!'));
            }
        }
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
    } // end if (isset($mediaType) && isset($bsReference))

?>
