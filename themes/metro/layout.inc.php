<?php

$GLOBALS['cfg']['NaviWidth']                = '250';
$GLOBALS['cfg']['FontFamily']               = '"Open Sans", "Segoe UI"';
$GLOBALS['cfg']['FontFamilyLight']          = '"Open Sans Light", "Segoe UI Light", "Segoe UI"; font-weight: 300';
$GLOBALS['cfg']['FontFamilyFixed']          = 'Consolas, Monospace, "Lucida Grande"';

/* Theme color scheme
 * Values: "teal", "redmond", "blueeyes", "mono", "win"
 * Set this value for the desired color scheme
 */

$scheme                                     = "win";
$GLOBALS['cfg']['Scheme']                   = $scheme;

switch($scheme)
{
    case "win":

        $GLOBALS['cfg']['NaviColor']                = '#EEEEEE';
        $GLOBALS['cfg']['NaviBackground']           = '#377796';
        $GLOBALS['cfg']['NaviBackgroundLight']      = '#428EB4';
        $GLOBALS['cfg']['NaviPointerColor']         = '#333333';
        $GLOBALS['cfg']['NaviPointerBackground']    = '#377796';
        $GLOBALS['cfg']['NaviDatabaseNameColor']    = '#333333';
        $GLOBALS['cfg']['NaviHoverBackground']      = '#428EB4';
        $GLOBALS['cfg']['MainColor']                = '#444444';
        $GLOBALS['cfg']['MainBackground']           = '#FFFFFF';
        $GLOBALS['cfg']['BrowsePointerColor']       = '#377796';
        $GLOBALS['cfg']['BrowseMarkerColor']        = '#000000';
        $GLOBALS['cfg']['BrowseWarningColor']       = '#D44A26';
        $GLOBALS['cfg']['BrowseSuccessColor']       = '#01A31C';
        $GLOBALS['cfg']['BrowseGrayColor']          = '#CCCCCC';
        $GLOBALS['cfg']['BrowseMarkerBackground']   = '#EEEEEE';
        $GLOBALS['cfg']['BorderColor']              = '#DDDDDD';
        $GLOBALS['cfg']['ButtonColor']              = '#FFFFFF';
        $GLOBALS['cfg']['ButtonBackground']         = '#377796';
        $GLOBALS['cfg']['ButtonHover']              = '#428EB4';
        $GLOBALS['cfg']['ThBackground']             = '#F7F7F7';
        $GLOBALS['cfg']['ThDisabledBackground']     = '#F3F3F3';
        $GLOBALS['cfg']['ThColor']                  = '#666666';
        $GLOBALS['cfg']['ThPointerColor']           = '#000000';
        $GLOBALS['cfg']['BgOne']                    = '#F7F7F7';
        $GLOBALS['cfg']['BgTwo']                    = '#FFFFFF';
        $GLOBALS['cfg']['BlueHeader']               = '#3A7EAD';
        break;

    case "teal":

        $GLOBALS['cfg']['NaviColor']                = '#FFFFFF';
        $GLOBALS['cfg']['NaviBackground']           = '#004D60';
        $GLOBALS['cfg']['NaviBackgroundLight']      = '#04627C';
        $GLOBALS['cfg']['NaviPointerColor']         = '#666666';
        $GLOBALS['cfg']['NaviPointerBackground']    = '#004D60';
        $GLOBALS['cfg']['NaviDatabaseNameColor']    = '#FFFFFF';
        $GLOBALS['cfg']['NaviHoverBackground']      = '#216475';
        $GLOBALS['cfg']['MainColor']                = '#444444';
        $GLOBALS['cfg']['MainBackground']           = '#FFFFFF';
        $GLOBALS['cfg']['BrowsePointerColor']       = '#004d60';
        $GLOBALS['cfg']['BrowseMarkerColor']        = '#000000';
        $GLOBALS['cfg']['BrowseWarningColor']       = '#D44A26';
        $GLOBALS['cfg']['BrowseSuccessColor']       = '#01A31C';
        $GLOBALS['cfg']['BrowseGrayColor']          = '#CCCCCC';
        $GLOBALS['cfg']['BrowseMarkerBackground']   = '#EEEEEE';
        $GLOBALS['cfg']['BorderColor']              = '#DDDDDD';
        $GLOBALS['cfg']['ButtonColor']              = '#FFFFFF';
        $GLOBALS['cfg']['ButtonBackground']         = '#AAAAAA';
        $GLOBALS['cfg']['ButtonHover']              = '#000000';
        $GLOBALS['cfg']['ThBackground']             = '#F7F7F7';
        $GLOBALS['cfg']['ThDisabledBackground']     = '#F3F3F3';
        $GLOBALS['cfg']['ThColor']                  = '#666666';
        $GLOBALS['cfg']['ThPointerColor']           = '#000000';
        $GLOBALS['cfg']['BgOne']                    = '#F7F7F7';
        $GLOBALS['cfg']['BgTwo']                    = '#FFFFFF';
        $GLOBALS['cfg']['BlueHeader']               = '#3A7EAD';
        break;

    case "redmond":

        $GLOBALS['cfg']['NaviColor']                = '#FFFFFF';
        $GLOBALS['cfg']['NaviBackground']           = '#780505';
        $GLOBALS['cfg']['NaviBackgroundLight']      = '#A10707';
        $GLOBALS['cfg']['NaviPointerColor']         = '#666666';
        $GLOBALS['cfg']['NaviPointerBackground']    = '#780505';
        $GLOBALS['cfg']['NaviDatabaseNameColor']    = '#FFFFFF';
        $GLOBALS['cfg']['NaviHoverBackground']      = '#A10707';
        $GLOBALS['cfg']['MainColor']                = '#444444';
        $GLOBALS['cfg']['MainBackground']           = '#FFFFFF';
        $GLOBALS['cfg']['BrowsePointerColor']       = '#780505';
        $GLOBALS['cfg']['BrowseMarkerColor']        = '#000000';
        $GLOBALS['cfg']['BrowseWarningColor']       = '#D44A26';
        $GLOBALS['cfg']['BrowseSuccessColor']       = '#01A31C';
        $GLOBALS['cfg']['BrowseGrayColor']          = '#CCCCCC';
        $GLOBALS['cfg']['BrowseMarkerBackground']   = '#EEEEEE';
        $GLOBALS['cfg']['BorderColor']              = '#DDDDDD';
        $GLOBALS['cfg']['ButtonColor']              = '#FFFFFF';
        $GLOBALS['cfg']['ButtonBackground']         = '#AAAAAA';
        $GLOBALS['cfg']['ButtonHover']              = '#000000';
        $GLOBALS['cfg']['ThBackground']             = '#F7F7F7';
        $GLOBALS['cfg']['ThDisabledBackground']     = '#F3F3F3';
        $GLOBALS['cfg']['ThColor']                  = '#666666';
        $GLOBALS['cfg']['ThPointerColor']           = '#000000';
        $GLOBALS['cfg']['BgOne']                    = '#F7F7F7';
        $GLOBALS['cfg']['BgTwo']                    = '#FFFFFF';
        $GLOBALS['cfg']['BlueHeader']               = '#3A7EAD';
        break;

    case "blueeyes":

        $GLOBALS['cfg']['NaviColor']                = '#FFFFFF';
        $GLOBALS['cfg']['NaviBackground']           = '#377796';
        $GLOBALS['cfg']['NaviBackgroundLight']      = '#428EB4';
        $GLOBALS['cfg']['NaviPointerColor']         = '#666666';
        $GLOBALS['cfg']['NaviPointerBackground']    = '#377796';
        $GLOBALS['cfg']['NaviDatabaseNameColor']    = '#FFFFFF';
        $GLOBALS['cfg']['NaviHoverBackground']      = '#428EB4';
        $GLOBALS['cfg']['MainColor']                = '#444444';
        $GLOBALS['cfg']['MainBackground']           = '#FFFFFF';
        $GLOBALS['cfg']['BrowsePointerColor']       = '#377796';
        $GLOBALS['cfg']['BrowseMarkerColor']        = '#000000';
        $GLOBALS['cfg']['BrowseWarningColor']       = '#D44A26';
        $GLOBALS['cfg']['BrowseSuccessColor']       = '#01A31C';
        $GLOBALS['cfg']['BrowseGrayColor']          = '#CCCCCC';
        $GLOBALS['cfg']['BrowseMarkerBackground']   = '#EEEEEE';
        $GLOBALS['cfg']['BorderColor']              = '#DDDDDD';
        $GLOBALS['cfg']['ButtonColor']              = '#FFFFFF';
        $GLOBALS['cfg']['ButtonBackground']         = '#377796';
        $GLOBALS['cfg']['ButtonHover']              = '#000000';
        $GLOBALS['cfg']['ThBackground']             = '#F7F7F7';
        $GLOBALS['cfg']['ThDisabledBackground']     = '#F3F3F3';
        $GLOBALS['cfg']['ThColor']                  = '#666666';
        $GLOBALS['cfg']['ThPointerColor']           = '#000000';
        $GLOBALS['cfg']['BgOne']                    = '#F7F7F7';
        $GLOBALS['cfg']['BgTwo']                    = '#FFFFFF';
        $GLOBALS['cfg']['BlueHeader']               = '#3A7EAD';
        break;

    case "mono":

        $GLOBALS['cfg']['NaviColor']                = '#FFFFFF';
        $GLOBALS['cfg']['NaviBackground']           = '#666666';
        $GLOBALS['cfg']['NaviBackgroundLight']      = '#999999';
        $GLOBALS['cfg']['NaviPointerColor']         = '#666666';
        $GLOBALS['cfg']['NaviPointerBackground']    = '#666666';
        $GLOBALS['cfg']['NaviDatabaseNameColor']    = '#FFFFFF';
        $GLOBALS['cfg']['NaviHoverBackground']      = '#999999';
        $GLOBALS['cfg']['MainColor']                = '#444444';
        $GLOBALS['cfg']['MainBackground']           = '#FFFFFF';
        $GLOBALS['cfg']['BrowsePointerColor']       = '#666666';
        $GLOBALS['cfg']['BrowseMarkerColor']        = '#000000';
        $GLOBALS['cfg']['BrowseWarningColor']       = '#666666';
        $GLOBALS['cfg']['BrowseSuccessColor']       = '#888888';
        $GLOBALS['cfg']['BrowseGrayColor']          = '#CCCCCC';
        $GLOBALS['cfg']['BrowseMarkerBackground']   = '#EEEEEE';
        $GLOBALS['cfg']['BorderColor']              = '#DDDDDD';
        $GLOBALS['cfg']['ButtonColor']              = '#FFFFFF';
        $GLOBALS['cfg']['ButtonBackground']         = '#AAAAAA';
        $GLOBALS['cfg']['ButtonHover']              = '#000000';
        $GLOBALS['cfg']['ThBackground']             = '#F7F7F7';
        $GLOBALS['cfg']['ThDisabledBackground']     = '#F3F3F3';
        $GLOBALS['cfg']['ThColor']                  = '#666666';
        $GLOBALS['cfg']['ThPointerColor']           = '#000000';
        $GLOBALS['cfg']['BgOne']                    = '#F7F7F7';
        $GLOBALS['cfg']['BgTwo']                    = '#FFFFFF';
        $GLOBALS['cfg']['BlueHeader']               = '#555555';
        break;
}
