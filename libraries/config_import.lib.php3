<?php        
/* $Id$ */


/**
 * This file is used for importing settings from
 * config file versions earlier than 1.81
 */


$cfg['PmaAbsoluteUri']          = $cfgPmaAbsoluteUri;
unset($cfgPmaAbsoluteUri);
$cfg['Servers']                 = $cfgServers;
unset($cfgServers);
$cfg['ServerDefault']           = $cfgServerDefault;
unset($cfgServerDefault);
$cfg['OBGzip']                  = $cfgOBGzip;
unset($cfgOBGzip);
$cfg['PersistentConnections']   = $cfgPersistentConnections;
unset($cfgPersistentConnections);

if (!isset($cfgExecTimeLimit)) {
    $cfg['ExecTimeLimit']       = 300; // 5 minutes
} else {
    $cfg['ExecTimeLimit']       = $cfgExecTimeLimit;
    unset($cfgExecTimeLimit);
}

$cfg['SkipLockedTables']        = $cfgSkipLockedTables;
unset($cfgSkipLockedTables);
$cfg['ShowSQL']                 = $cfgShowSQL;
unset($cfgShowSQL);
$cfg['AllowUserDropDatabase']   = $cfgAllowUserDropDatabase;
unset($cfgAllowUserDropDatabase);
$cfg['Confirm']                 = $cfgConfirm;
unset($cfgConfirm);

if (!isset($cfgLoginCookieRecall)) {
    $cfg['LoginCookieRecall']   = TRUE;
} else
    $cfg['LoginCookieRecall']   = $cfgLoginCookieRecall;
    unset($cfgLoginCookieRecall);
}

if (!isset($cfgLeftFrameLight)) {
    $cfg['LeftFrameLight']      = TRUE;
} else {
    $cfg['LeftFrameLight']      = $cfgLeftFrameLight;
    unset($cfgLeftFrameLight);
}

if (!isset($cfgShowTooltip)) {
    $cfg['ShowTooltip']         = TRUE;
} else {
    $cfg['ShowTooltip']         = $cfgShowTooltip;
    unset($cfgShowTooltip);
}

if (!isset($cfgShowStats)) {
    $cfg['ShowStats']           = TRUE;
} else {
    $cfg['ShowStats']           = $cfgShowStats;
    unset($cfgShowStats);
}

if (!isset($cfgShowMysqlInfo)) {
    $cfg['ShowMysqlInfo']       = FALSE;
} else {
    $cfg['ShowMysqlInfo']       = $cfgShowMysqlInfo;
    unset($cfgShowMysqlInfo);
}

if (!isset($cfgShowMysqlVars)) {
    $cfg['ShowMysqlVars']       = FALSE;
} else {
    $cfg['ShowMysqlVars']       = $cfgShowMysqlVars;
    unset($cfgShowMysqlVars);
}

if (!isset($cfgShowPhpInfo)) {
    $cfg['ShowPhpInfo']         = FALSE;
} else {
    $cfg['ShowPhpInfo']         = $cfgShowPhpInfo;
    unset($cfgShowPhpInfo);
}

if (!isset($cfgShowChgPassword)) {
    $cfg['ShowChgPassword']     = FALSE;
} else {
    $cfg['ShowChgPassword']     = $cfgShowChgPassword;
    unset($cfgShowChgPassword);
}

$cfg['ShowBlob']                = $cfgShowBlob;
unset($cfgShowBlob);

if (!isset($cfgNavigationBarIconic)) {
    $cfg['NavigationBarIconic'] = TRUE;
} else {
    $cfg['NavigationBarIconic'] = $cfgNavigationBarIconic;
    unset($cfgNavigationBarIconic);
}

if (!isset($cfgShowAll)) {
    $cfg['ShowAll']             = FALSE;
} else {
    $cfg['ShowAll']             = $cfgShowAll;
    unset($cfgShowAll);
}

$cfg['MaxRows']                 = $cfgMaxRows;
unset($cfgMaxRows);
$cfg['Order']                   = $cfgOrder;
unset($cfgOrder);

if (!isset($cfgProtectBinary)) {
    if (isset($cfgProtectBlob)) {
        $cfg['ProtectBinary']   = ($cfgProtectBlob ? 'blob' : FALSE);
        unset($cfgProtectBlob);
    } else {
        $cfg['ProtectBinary']   = 'blob';
    }
} else {
    $cfg['ProtectBinary']       = $cfgProtectBinary;
    unset($cfgProtectBinary);
}

if (!isset($cfgShowFunctionFields)) {
    $cfg['ShowFunctionFields']  = TRUE;
} else {
    $cfg['ShowFunctionFields']  = $cfgShowFunctionFields;
    unset($cfgShowFunctionFields);
}

if (!isset($cfgZipDump)) {
    $cfg['ZipDump']             = (isset($cfgGZipDump) ? $cfgGZipDump : TRUE);
} else {
    $cfg['ZipDump']             = $cfgZipDump;
    unset($cfgZipDump);
}

if (!isset($cfgGZipDump)) {
    $cfg['GZipDump']            = TRUE;
} else {
    $cfg['GZipDump']            = $cfgGZipDump;
    unset($cfgGZipDump);
}

if (!isset($cfgBZipDump)) {
    $cfg['BZipDump']            = TRUE;
} else {
    $cfg['BZipDump']            = $cfgBZipDump;
    unset($cfgBZipDump);
}

$cfg['ManualBaseShort']         = $cfgManualBaseShort;
unset($cfgManualBaseShort);
$cfg['DefaultLang']             = $cfgDefaultLang;
unset($cfgDefaultLang);

if (isset($cfgLang)) {
    $cfg['Lang']                = $cfgLang;
    unset($cfgLang);
}

$cfg['LeftWidth']               = $cfgLeftWidth;
unset($cfgLeftWidth);

if (!isset($cfgLeftBgColor)) {
    $cfg['LeftBgColor']         = '#D0DCE0';
} else {
    $cfg['LeftBgColor']         = $cfgLeftBgColor;
    unset($cfgLeftBgColor);
}

if (!isset($cfgLeftPointerColor)) {
    $cfg['LeftPointerColor']    = '';
} else {
    $cfg['LeftPointerColor']    = $cfgLeftPointerColor;
    unset($cfgLeftPointerColor);
}

if (!isset($cfgRightBgColor)) {
    $cfg['RightBgColor']        = '#F5F5F5';
} else {
    $cfg['RightBgColor']        = $cfgRightBgColor;
    unset($cfgRightBgColor);
}

$cfg['Border']                  = $cfgBorder;
unset($cfgBorder);
$cfg['ThBgcolor']               = $cfgThBgcolor;
unset($cfgThBgcolor);
$cfg['BgcolorOne']              = $cfgBgcolorOne;
unset($cfgBgcolorOne);
$cfg['BgcolorTwo']              = $cfgBgcolorTwo;
unset($cfgBgcolorTwo);

if (!isset($cfgBrowsePointerColor)) {
    $cfg['BrowsePointerColor']  = '';
} else {
    $cfg['BrowsePointerColor']  = $cfgBrowsePointerColor;
    unset($cfgBrowsePointerColor);
}

if (!isset($cfgBrowseMarkerColor)) {
    $cfg['BrowseMarkerColor']   = (!empty($cfg['BrowsePointerColor']) && !empty($cfgBrowseMarkRow))
                                  ? '#FFCC99'
                                  : '';
    unset($cfgBrowseMarkRow);
} else {
    $cfg['BrowseMarkerColor']   = $cfgBrowseMarkerColor;
    unset($cfgBrowseMarkerColor);
}

if (!isset($cfgTextareaCols)) {
    $cfg['TextareaCols']        = 40;
} else {
    $cfg['TextareaCols']        = $cfgTextareaCols;
    unset($cfgTextareaCols);
}

if (!isset($cfgTextareaRows)) {
    $cfg['TextareaRows']        = 7;
} else {
    $cfg['TextareaRows']        = $cfgTextareaRows;
    unset($cfgTextareaRows);
}

$cfg['LimitChars']              = $cfgLimitChars;
unset($cfgLimitChars);
$cfg['ModifyDeleteAtLeft']      = $cfgModifyDeleteAtLeft;
unset($cfgModifyDeleteAtLeft);
$cfg['ModifyDeleteAtRight']     = $cfgModifyDeleteAtRight;
unset($cfgModifyDeleteAtRight);

if (!isset($cfgDefaultDisplay)) {
    $cfg['DefaultDisplay']      = 'horizontal';
} else {
    $cfg['DefaultDisplay']      = $cfgDefaultDisplay;
    unset($cfgDefaultDisplay);
}

if (!isset($cfgRepeatCells)) {
    $cfg['RepeatCells']         = 100;
} else {
    $cfg['RepeatCells']         = $cfgRepeatCells;
    unset($cfgRepeatCells);
}

$cfg['ColumnTypes']             = $cfgColumnTypes;
unset($cfgColumnTypes);
$cfg['AttributeTypes']          = $cfgAttributeTypes;
unset($cfgAttributeTypes);

if (isset($cfgFunctions)) {
    $cfg['Functions']           = $cfgFunctions;
    unset($cfgFunctions);
}

?>