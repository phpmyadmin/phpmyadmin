<?php
/* $Id$ */


/**
 * This file provides support for older config files.
 */


if (!defined('PMA_CONFIG_IMPORT_LIB_INCLUDED')){
    define('PMA_CONFIG_IMPORT_LIB_INCLUDED', 1);

    if (!isset($cfg['PmaAbsoluteUri'])) {
        if (isset($cfgPmaAbsoluteUri)) {
            $cfg['PmaAbsoluteUri'] = $cfgPmaAbsoluteUri;
            unset($cfgPmaAbsoluteUri);
        } else {
            $cfg['PmaAbsoluteUri'] = '';
        }
    }

    if (!isset($cfg['Servers'])) {
        if (isset($cfgServers)) {
            $cfg['Servers'] = $cfgServers;
            unset($cfgServers);
        } else {
        $server = 0;
        }
    }

    if (isset($cfg['Servers'])) {
        for ($i=1; (isset($cfg['Servers'][$i]['host']) && !empty($cfg['Servers'][$i]['host'])); $i++) {
            if (!isset($cfg['Servers'][$i]['port'])) {
                $cfg['Servers'][$i]['port'] = '';
            }

            if (!isset($cfg['Servers'][$i]['socket'])) {
                $cfg['Servers'][$i]['socket'] = '';
            }

            if (!isset($cfg['Servers'][$i]['connect_type'])) {
                $cfg['Servers'][$i]['connect_type'] = 'tcp';
            }

            if (!isset($cfg['Servers'][$i]['controluser']) && isset($cfg['Servers'][$i]['stduser'])) {
                $cfg['Servers'][$i]['controluser'] = $cfg['Servers'][$i]['stduser'];
                $cfg['Servers'][$i]['controlpass'] = $cfg['Servers'][$i]['stdpass'];
                unset($cfg['Servers'][$i]['stduser']);
                unset($cfg['Servers'][$i]['stdpass']);
            } else if (!isset($cfg['Servers'][$i]['controluser'])) {
                $cfg['Servers'][$i]['controluser'] = $cfg['Servers'][$i]['controlpass'] = '';
            }

            if (!isset($cfg['Servers'][$i]['auth_type'])) {
                $cfg['Servers'][$i]['auth_type']  = (isset($cfg['Servers'][$i]['adv_auth']) && $cfg['Servers'][$i]['adv_auth'])
                                             ? 'http'
                                             : 'config';
                unset($cfg['Servers'][$i]['adv_auth']);
            }

            if (!isset($cfg['Servers'][$i]['user'])) {
                $cfg['Servers'][$i]['user'] = 'root';
            }

            if (!isset($cfg['Servers'][$i]['password'])) {
                $cfg['Servers'][$i]['password'] = '';
            }

            if (!isset($cfg['Servers'][$i]['only_db'])) {
                $cfg['Servers'][$i]['only_db'] = '';
            }

            if (!isset($cfg['Servers'][$i]['verbose'])) {
                $cfg['Servers'][$i]['verbose'] = '';
            }

            if (!isset($cfg['Servers'][$i]['pmadb'])) {
                if (isset($cfg['Servers'][$i]['bookmarkdb'])) {
                    $cfg['Servers'][$i]['pmadb'] = $cfg['Servers'][$i]['bookmarkdb'];
                    unset($cfg['Servers'][$i]['bookmarkdb']);
                } else {
                    $cfg['Servers'][$i]['pmadb'] = '';
                }
            }

            if (!isset($cfg['Servers'][$i]['bookmarktable'])) {
                $cfg['Servers'][$i]['bookmarktable'] = '';
            }

            if (!isset($cfg['Servers'][$i]['relation'])) {
                $cfg['Servers'][$i]['relation'] = '';
            }

            if (!isset($cfg['Servers'][$i]['table_info'])) {
                $cfg['Servers'][$i]['table_info'] = '';
            }

            if (!isset($cfg['Servers'][$i]['table_coords'])) {
                $cfg['Servers'][$i]['table_coords'] = '';
            }

            if (!isset($cfg['Servers'][$i]['column_comments'])) {
                $cfg['Servers'][$i]['column_comments'] = '';
            }

            if (!isset($cfg['Servers'][$i]['pdf_pages'])) {
                $cfg['Servers'][$i]['pdf_pages'] = '';
            }

            if (!isset($cfg['Servers'][$i]['AllowDeny'])) {
                $cfg['Servers'][$i]['AllowDeny'] = array ('order' => '',
                                                          'rules' => array());
            }
        }
    }

    if (!isset($cfg['ServerDefault'])) {
        if (isset($cfgServerDefault)) {
            $cfg['ServerDefault'] = $cfgServerDefault;
            unset($cfgServerDefault);
        } else {
            $cfg['ServerDefault'] = 1;
        }
    }

    if (!isset($cfg['OBGzip'])) {
        if (isset($cfgOBGzip)) {
            $cfg['OBGzip'] = $cfgOBGzip;
            unset($cfgOBGzip);
        } else {
            $cfg['OBGzip'] = TRUE;
        }
    }

    if (!isset($cfg['PersistentConnections'])) {
        if (isset($cfgPersistentConnections)) {
            $cfg['PersistentConnections'] = $cfgPersistentConnections;
            unset($cfgPersistentConnections);
        } else {
            $cfg['PersistentConnections'] = FALSE;
        }
    }

    if (!isset($cfg['ExecTimeLimit'])) {
        if (isset($cfgExecTimeLimit)) {
            $cfg['ExecTimeLimit'] = $cfgExecTimeLimit;
            unset($cfgExecTimeLimit);
        } else {
            $cfg['ExecTimeLimit'] = 300;
        }
    }

    if (!isset($cfg['SkipLockedTables'])) {
        if (isset($cfgSkipLockedTables)) {
            $cfg['SkipLockedTables'] = $cfgSkipLockedTables;
            unset($cfgSkipLockedTables);
        } else {
            $cfg['SkipLockedTables'] = FALSE;
        }
    }

    if (!isset($cfg['ShowSQL'])) {
        if (isset($cfgShowSQL)) {
            $cfg['ShowSQL'] = $cfgShowSQL;
            unset($cfgShowSQL);
        } else {
            $cfg['ShowSQL'] = TRUE;
        }
    }

    if (!isset($cfg['AllowUserDropDatabase'])) {
        if (isset($cfgAllowUserDropDatabase)) {
            $cfg['AllowUserDropDatabase'] = $cfgAllowUserDropDatabase;
            unset($cfgAllowUserDropDatabase);
        } else {
            $cfg['AllowUserDropDatabase'] = FALSE;
        }
    }

    if (!isset($cfg['Confirm'])) {
        if (isset($cfgConfirm)) {
            $cfg['Confirm'] = $cfgConfirm;
            unset($cfgConfirm);
        } else {
            $cfg['Confirm'] = TRUE;
        }
    }

    if (!isset($cfg['LoginCookieRecall'])) {
        if (isset($cfgLoginCookieRecall)) {
            $cfg['LoginCookieRecall'] = $cfgLoginCookieRecall;
            unset($cfgLoginCookieRecall);
        } else {
            $cfg['LoginCookieRecall'] = TRUE;
        }
    }

    if (!isset($cfg['UseDbSearch'])) {
        $cfg['UseDbSearch'] = TRUE;
    }

    if (!isset($cfg['LeftFrameLight'])) {
        if (isset($cfgLeftFrameLight)) {
            $cfg['LeftFrameLight'] = $cfgLeftFrameLight;
            unset($cfgLeftFrameLight);
        } else {
            $cfg['LeftFrameLight'] = TRUE;
        }
    }

    if (!isset($cfg['ShowTooltip'])) {
        if (isset($cfgShowTooltip)) {
            $cfg['ShowTooltip'] = $cfgShowTooltip;
        } else {
            $cfg['ShowTooltip'] = TRUE;
        }
    }

    if (!isset($cfg['LeftDisplayLogo'])) {
        $cfg['LeftDisplayLogo'] = TRUE;
    }

    if (!isset($cfg['ShowStats'])) {
        if (isset($cfgShowStats)) {
            $cfg['ShowStats'] = $cfgShowStats;
            unset($cfgShowStats);
        } else {
            $cfg['ShowStats'] = TRUE;
        }
    }

    if (!isset($cfg['ShowMysqlInfo'])) {
        if (isset($cfgShowMysqlInfo)) {
            $cfg['ShowMysqlInfo'] = $cfgShowMysqlInfo;
            unset($cfgShowMysqlInfo);
        } else {
            $cfg['ShowMysqlInfo'] = FALSE;
        }
    }

    if (!isset($cfg['ShowMysqlVars'])) {
        if (isset($cfgShowMysqlVars)) {
            $cfg['ShowMysqlVars'] = $cfgShowMysqlVars;
            unset($cfgShowMysqlVars);
        } else {
            $cfg['ShowMysqlVars'] = FALSE;
        }
    }

    if (!isset($cfg['ShowPhpInfo'])) {
        if (isset($cfgShowPhpInfo)) {
            $cfg['ShowPhpInfo'] = $cfgShowPhpInfo;
            unset($cfgShowPhpInfo);
        } else {
            $cfg['ShowPhpInfo'] = FALSE;
        }
    }

    if (!isset($cfg['ShowChgPassword'])) {
        if (isset($cfgShowChgPassword)) {
            $cfg['ShowChgPassword'] = $cfgShowChgPassword;
            unset($cfgShowChgPassword);
        } else {
            $cfg['ShowChgPassword'] = FALSE;
        }
    }

    if (!isset($cfg['SuggestDBName'])) {
        $cfg['SuggestDBName'] = TRUE;
    }

    if (!isset($cfg['ShowBlob'])) {
        if (isset($cfgShowBlob)) {
            $cfg['ShowBlob'] = $cfgShowBlob;
            unset($cfgShowBlob);
        } else {
            $cfg['ShowBlob'] = FALSE;
        }
    }

    if (!isset($cfg['NavigationBarIconic'])) {
        if (isset($cfgNavigationBarIconic)) {
            $cfg['NavigationBarIconic'] = $cfgNavigationBarIconic;
            unset($cfgNavigationBarIconic);
        } else {
            $cfg['NavigationBarIconic'] = TRUE;
        }
    }

    if (!isset($cfg['ShowAll'])) {
        if (isset($cfgShowAll)) {
            $cfg['ShowAll'] = $cfgShowAll;
            unset($cfgShowAll);
        } else {
            $cfg['ShowAll'] = FALSE;
        }
    }

    if (!isset($cfg['MaxRows'])) {
        if (isset($cfgMaxRows)) {
            $cfg['MaxRows'] = $cfgMaxRows;
            unset($cfgMaxRows);
        } else {
            $cfg['MaxRows'] = 30;
        }
    }

    if (!isset($cfg['Order'])) {
        if (isset($cfgOrder)) {
            $cfg['Order'] = $cfgOrder;
            unset($cfgOrder);
        } else {
            $cfg['Order'] = 'ASC';
        }
    }

    if (!isset($cfg['ProtectBinary'])) {
        if (isset($cfgProtectBinary)) {
            $cfg['ProtectBinary'] = $cfgProtectBinary;
            unset($cfgProtectBinary);
        } else if (isset($cfg['ProtectBlob'])) {
            $cfg['ProtectBinary']   = ($cfg['ProtectBlob'] ? 'blob' : FALSE);
            unset($cfg['ProtectBlob']);
        } else if (isset($cfgProtectBlob)) {
            $cfg['ProtectBinary']   = ($cfgProtectBlob ? 'blob' : FALSE);
            unset($cfgProtectBlob);
        } else {
            $cfg['ProtectBinary']   = 'blob';
        }
    }

    if (!isset($cfg['ShowFunctionFields'])) {
        if (isset($cfgShowFunctionFields)) {
            $cfg['ShowFunctionFields'] = $cfgShowFunctionFields;
            unset($cfgShowFunctionFields);
        } else {
            $cfg['ShowFunctionFields'] = TRUE;
        }
    }

    if (!isset($cfg['ZipDump'])) {
        if (isset($cfgZipDump)) {
            $cfg['ZipDump'] = $cfgZipDump;
            unset($cfgZipDump);
        } else {
            $cfg['ZipDump'] = TRUE;
        }
    }

    if (!isset($cfg['GZipDump'])) {
        if (isset($cfgGZipDump)) {
            $cfg['GZipDump'] = $cfgGZipDump;
            unset($cfgGZipDump);
        } else {
            $cfg['GZipDump'] = TRUE;
        }
    }

    if (!isset($cfg['BZipDump'])) {
        if (isset($cfgBZipDump)) {
            $cfg['BZipDump'] = $cfgBZipDump;
            unset($cfgBZipDump);
        } else {
            $cfg['BZipDump'] = TRUE;
        }
    }

    if (!isset($cfg['DefaultTabDatabase'])
        // rabus: config.inc.php3 rev. 1.112 had this default value.
        || $cfg['DefaultTabDatabase'] == 'Structure') {
        $cfg['DefaultTabDatabase'] = 'db_details_structure.php3';
    }

    if (!isset($cfg['DefaultTabTable'])
        // rabus: config.inc.php3 rev. 1.112 had this default value.
        || $cfg['DefaultTabTable'] == 'Structure') {
        $cfg['DefaultTabTable'] = 'tbl_properties_structure.php3';
    }

    if (!isset($cfg['ManualBase'])) {
        if (isset($cfgManualBaseShort)) {
            $cfg['ManualBase'] = $cfgManualBaseShort;
            $cfg['MySQLManualType'] = 'old';
            unset($cfgManualBaseShort);
        } else if (isset($cfg['ManualBaseShort'])) {
            $cfg['ManualBase'] = $cfg['ManualBaseShort'];
            $cfg['MySQLManualType'] = 'old';
            unset($cfg['ManualBaseShort']);
        } else {
            $cfg['ManualBase'] = 'http://www.mysql.com/doc/en';
            $cfg['MySQLManualType'] = 'searchable';
        }
    }

    if (!isset($cfg['MySQLManualType'])) {
        $cfg['MySQLManualType'] = 'none';
    }

    if (!isset($cfg['DefaultLang'])) {
        if (isset($cfgDefaultLang)) {
            $cfg['DefaultLang'] = $cfgDefaultLang;
            unset($cfgDefaultLang);
        } else {
            $cfg['DefaultLang'] = 'en-iso-8859-1';
        }
    }

    if (!isset($cfg['DefaultCharset'])) {
        $cfg['DefaultCharset'] = 'iso-8859-1';
    }

    if (!isset($cfg['AllowAnywhereRecoding'])) {
        $cfg['AllowAnywhereRecoding'] = FALSE;
    }

    if (!isset($cfg['Lang']) &&isset($cfgLang)) {
        $cfg['Lang'] = $cfgLang;
        unset($cfgLang);
    }

    if (!isset($cfg['LeftWidth'])) {
        if (isset($cfgLeftWidth)) {
            $cfg['LeftWidth'] = $cfgLeftWidth;
            unset($cfgLeftWidth);
        } else {
            $cfg['LeftWidth'] = 150;
        }
    }

    if (!isset($cfg['LeftBgColor'])) {
        if (isset($cfgLeftBgColor)) {
            $cfg['LeftBgColor'] = $cfgLeftBgColor;
            unset($cfgLeftBgColor);
        } else {
            $cfg['LeftBgColor'] = '#D0DCE0';
        }
    }

    if (!isset($cfg['LeftPointerColor'])) {
        if (isset($cfgLeftPointerColor)) {
            $cfg['LeftPointerColor'] = $cfgLeftPointerColor;
            unset($cfgLeftPointerColor);
        } else {
            $cfg['LeftPointerColor'] = '#CCFFCC';
        }
    }

    if (!isset($cfg['RightBgColor'])) {
        if (isset($cfgRightBgColor)) {
            $cfg['RightBgColor'] = $cfgRightBgColor;
            unset($cfgRightBgColor);
        } else {
            $cfg['RightBgColor'] = '#F5F5F5';
        }
    }

    if (!isset($cfg['RightBgImage'])) {
        $cfg['RightBgImage'] = '';
    }

    if (!isset($cfg['Border'])) {
        if (isset($cfgBorder)) {
            $cfg['Border'] = $cfgBorder;
            unset($cfgBorder);
        } else {
            $cfg['Border'] = 0;
        }
    }

    if (!isset($cfg['ThBgcolor'])) {
        if (isset($cfgThBgcolor)) {
            $cfg['ThBgcolor'] = $cfgThBgcolor;
            unset($cfgThBgcolor);
        } else {
            $cfg['ThBgcolor'] = '#D3DCE3';
        }
    }

    if (!isset($cfg['BgcolorOne'])) {
        if (isset($cfgBgcolorOne)) {
            $cfg['BgcolorOne'] = $cfgBgcolorOne;
            unset($cfgBgcolorOne);
        } else {
            $cfg['BgcolorOne'] = '#CCCCCC';
        }
    }

    if (!isset($cfg['BgcolorTwo'])) {
        if (isset($cfgBgcolorTwo)) {
            $cfg['BgcolorTwo'] = $cfgBgcolorTwo;
            unset($cfgBgcolorTwo);
        } else {
            $cfg['BgcolorTwo'] = '#DDDDDD';
        }
    }

    if (!isset($cfg['BrowsePointerColor'])) {
        if (isset($cfgBrowsePointerColor)) {
            $cfg['BrowsePointerColor'] = $cfgBrowsePointerColor;
            unset($cfgBrowsePointerColor);
        } else {
            $cfg['BrowsePointerColor'] = '#CCFFCC';
        }
    }

    if (!isset($cfg['BrowseMarkerColor'])) {
        if (isset($cfgBrowseMarkerColor)) {
            $cfg['BrowseMarkerColor'] = $cfgBrowseMarkerColor;
            unset($cfgBrowseMarkerColor);
        } else if (isset($cfg['BrowseMarkRow'])) {
            $cfg['BrowseMarkerColor']   = (!empty($cfg['BrowsePointerColor']) && !empty($cfg['BrowseMarkRow']))
                                          ? '#FFCC99'
                                          : '';
            unset($cfg['BrowseMarkRow']);
        } else if (isset($cfgBrowseMarkRow)) {
            $cfg['BrowseMarkerColor']   = (!empty($cfg['BrowsePointerColor']) && !empty($cfgBrowseMarkRow))
                                          ? '#FFCC99'
                                          : '';
            unset($cfgBrowseMarkRow);
        } else {
            $cfg['BrowseMarkerColor'] = '';
        }
    }

    if (!isset($cfg['TextareaCols'])) {
        if (isset($cfgTextareaCols)) {
            $cfg['TextareaCols'] = $cfgTextareaCols;
            unset($cfgTextareaCols);
        } else {
            $cfg['TextareaCols'] = 40;
        }
    }

    if (!isset($cfg['TextareaRows'])) {
        if (isset($cfgTextareaRows)) {
            $cfg['TextareaRows'] = $cfgTextareaRows;
            unset($cfgTextareaRows);
        } else {
            $cfg['TextareaRows'] = 7;
        }
    }

    if (!isset($cfg['TextareaAutoSelect'])) {
        $cfg['TextareaAutoSelect']  = TRUE;
    }

    if (!isset($cfg['LimitChars'])) {
        if (isset($cfgLimitChars)) {
            $cfg['LimitChars'] = $cfgLimitChars;
            unset($cfgLimitChars);
        } else {
            $cfg['LimitChars'] = 50;
        }
    }

    if (!isset($cfg['ModifyDeleteAtLeft'])) {
        if (isset($cfgModifyDeleteAtLeft)) {
            $cfg['ModifyDeleteAtLeft'] = $cfgModifyDeleteAtLeft;
            unset($cfgModifyDeleteAtLeft);
        } else {
            $cfg['ModifyDeleteAtLeft'] = TRUE;
        }
    }

    if (!isset($cfg['ModifyDeleteAtRight'])) {
        if (isset($cfgModifyDeleteAtRight)) {
            $cfg['ModifyDeleteAtRight'] = $cfgModifyDeleteAtRight;
            unset($cfgModifyDeleteAtRight);
        } else {
            $cfg['ModifyDeleteAtRight'] = FALSE;
        }
    }

    if (!isset($cfg['DefaultDisplay'])) {
        if (isset($cfgDefaultDisplay)) {
            $cfg['DefaultDisplay'] = $cfgDefaultDisplay;
            unset($cfgDefaultDisplay);
        } else {
            $cfg['DefaultDisplay'] = 'horizontal';
        }
    }

    if (!isset($cfg['RepeatCells'])) {
        if (isset($cfgRepeatCells)) {
            $cfg['RepeatCells'] = $cfgRepeatCells;
            unset($cfgRepeatCells);
        } else {
            $cfg['RepeatCells'] = 100;
        }
    }

    if (!isset($cfg['SQLQuery']['Edit'])) {
        $cfg['SQLQuery']['Edit'] = TRUE;
    }

    if (!isset($cfg['SQLQuery']['Explain'])) {
        $cfg['SQLQuery']['Explain'] = TRUE;
    }

    if (!isset($cfg['SQLQuery']['ShowAsPHP'])) {
        $cfg['SQLQuery']['ShowAsPHP'] = TRUE;
    }

    if (!isset($cfg['SQLQuery']['Validate'])) {
        $cfg['SQLQuery']['Validate'] = FALSE;
    }

    if (!isset($cfg['UploadDir'])) {
        $cfg['UploadDir'] = './upload/';
    }

    if (!isset($cfg['SQLValidator']['use'])) {
        $cfg['SQLValidator']['use'] = FALSE;
    }

    if (!isset($cfg['SQLValidator']['username'])) {
        $cfg['SQLValidator']['username'] = '';
    }

    if (!isset($cfg['SQLValidator']['password'])) {
        $cfg['SQLValidator']['password'] = '';
    }

    if (!isset($cfg['SQLValidator']['DisplayCopyright'])) {
        $cfg['SQLValidator']['DisplayCopyright'] = TRUE;
    }

    if (!isset($cfg['SQP']['enable'])) {
        $cfg['SQP']['enable'] = TRUE;
    }

    if (!isset($cfg['SQP']['fmtType'])) {
        $cfg['SQP']['fmtType'] = 'html';
    }

    if (!isset($cfg['SQP']['fmtInd'])) {
        $cfg['SQP']['fmtInd'] = '1';
    }

    if (!isset($cfg['SQP']['fmtIndUnit'])) {
        $cfg['SQP']['fmtIndUnit'] = 'em';
    }

    if (!isset($cfg['SQP']['fmtColor']['comment'])) {
        $cfg['SQP']['fmtColor']['comment'] = '#808000';
    }

    if (!isset($cfg['SQP']['fmtColor']['digit'])) {
        $cfg['SQP']['fmtColor']['digit'] = '';
    }

    if (!isset($cfg['SQP']['fmtColor']['digit_hex'])) {
        $cfg['SQP']['fmtColor']['digit_hex'] = 'teal';
    }

    if (!isset($cfg['SQP']['fmtColor']['digit_integer'])) {
        $cfg['SQP']['fmtColor']['digit_integer'] = 'teal';
    }

    if (!isset($cfg['SQP']['fmtColor']['digit_float'])) {
        $cfg['SQP']['fmtColor']['digit_float'] = 'aqua';
    }

    if (!isset($cfg['SQP']['fmtColor']['punct'])) {
        $cfg['SQP']['fmtColor']['punct'] = 'fuchsia';
    }

    if (!isset($cfg['SQP']['fmtColor']['alpha'])) {
        $cfg['SQP']['fmtColor']['alpha'] = '';
    }

    if (!isset($cfg['SQP']['fmtColor']['alpha_columnType'])) {
        $cfg['SQP']['fmtColor']['alpha_columnType'] = '#FF9900';
    }

    if (!isset($cfg['SQP']['fmtColor']['alpha_columnAttrib'])) {
        $cfg['SQP']['fmtColor']['alpha_columnAttrib'] = '#0000FF';
    }

    if (!isset($cfg['SQP']['fmtColor']['alpha_reservedWord'])) {
        $cfg['SQP']['fmtColor']['alpha_reservedWord'] = '#990099';
    }

    if (!isset($cfg['SQP']['fmtColor']['alpha_functionName'])) {
        $cfg['SQP']['fmtColor']['alpha_functionName'] = '#FF0000';
    }

    if (!isset($cfg['SQP']['fmtColor']['alpha_identifier'])) {
        $cfg['SQP']['fmtColor']['alpha_identifier'] = 'black';
    }

    if (!isset($cfg['SQP']['fmtColor']['alpha_variable'])) {
        $cfg['SQP']['fmtColor']['alpha_variable'] = '#800000';
    }

    if (!isset($cfg['SQP']['fmtColor']['quote'])) {
        $cfg['SQP']['fmtColor']['quote'] = '#008000';
    }

    if (!isset($cfg['SQP']['fmtColor']['quote_double'])) {
        $cfg['SQP']['fmtColor']['quote_double'] = '';
    }

    if (!isset($cfg['SQP']['fmtColor']['quote_single'])) {
        $cfg['SQP']['fmtColor']['quote_single'] = '';
    }

    if (!isset($cfg['SQP']['fmtColor']['quote_backtick'])) {
        $cfg['SQP']['fmtColor']['quote_backtick'] = '';
    }

    if (!isset($cfg['AvailableCharsets'])) {
        $cfg['AvailableCharsets'] = array(
            'iso-8859-1',
            'iso-8859-2',
            'iso-8859-3',
            'iso-8859-4',
            'iso-8859-5',
            'iso-8859-6',
            'iso-8859-7',
            'iso-8859-8',
            'iso-8859-9',
            'iso-8859-10',
            'iso-8859-11',
            'iso-8859-12',
            'iso-8859-13',
            'iso-8859-14',
            'iso-8859-15',
            'windows-1250',
            'windows-1251',
            'windows-1252',
            'windows-1257',
            'koi8-r',
            'big5',
            'gb2312',
            'utf-8',
            'utf-7',
            'x-user-defined',
            'euc-jp',
            'ks_c_5601-1987',
            'tis-620',
            'SHIFT_JIS'
        );
    }

    if (!isset($cfg['ColumnTypes'])) {
        if (isset($cfgColumnTypes)) {
            $cfg['ColumnTypes'] = $cfgColumnTypes;
            unset($cfgColumnTypes);
        } else {
            $cfg['ColumnTypes'] = array(
                'VARCHAR',
                'TINYINT',
                'TEXT',
                'DATE',
                'SMALLINT',
                'MEDIUMINT',
                'INT',
                'BIGINT',
                'FLOAT',
                'DOUBLE',
                'DECIMAL',
                'DATETIME',
                'TIMESTAMP',
                'TIME',
                'YEAR',
                'CHAR',
                'TINYBLOB',
                'TINYTEXT',
                'BLOB',
                'MEDIUMBLOB',
                'MEDIUMTEXT',
                'LONGBLOB',
                'LONGTEXT',
                'ENUM',
                'SET'
            );
        }
    }

    if (!isset($cfg['AttributeTypes'])) {
        if (isset($cfgAttributeTypes)) {
            $cfg['AttributeTypes'] = $cfgAttributeTypes;
            unset($cfgAttributeTypes);
        } else {
            $cfg['AttributeTypes'] = array(
               '',
               'BINARY',
               'UNSIGNED',
               'UNSIGNED ZEROFILL'
            );
        }
    }

    if (!isset($cfg['Functions']) && $cfg['ShowFunctionFields']) {
        if (isset($cfgFunctions)) {
            $cfg['Functions'] = $cfgFunctions;
        } else {
            $cfg['Functions'] = array(
               'ASCII',
               'CHAR',
               'SOUNDEX',
               'LCASE',
               'UCASE',
               'NOW',
               'PASSWORD',
               'MD5',
               'ENCRYPT',
               'RAND',
               'LAST_INSERT_ID',
               'COUNT',
               'AVG',
               'SUM',
               'CURDATE',
               'CURTIME',
               'FROM_DAYS',
               'FROM_UNIXTIME',
               'PERIOD_ADD',
               'PERIOD_DIFF',
               'TO_DAYS',
               'UNIX_TIMESTAMP',
               'USER',
               'WEEKDAY',
               'CONCAT'
            );
        }
    }

    if (!isset($cfg['PmaAbsoluteUri_DisableWarning'])) {
        $cfg['PmaAbsoluteUri_DisableWarning'] = FALSE;
    }
    if (!isset($cfg['PmaNoRelation_DisableWarning'])) {
        $cfg['PmaNoRelation_DisableWarning'] = FALSE;
    }
} // $__PMA_CONFIG_IMPORT_LIB__

?>
