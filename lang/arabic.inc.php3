<?php
/* $Id$ */

$charset = 'windows-1256';
$text_dir = 'rtl'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'Tahoma, verdana, helvetica, arial, sans-serif';
$right_font_family = '"Windows UI", helvetica, arial, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('ÈÇíÊ', 'ßíáæÈÇíÊ', 'ãíÌÇÈÇíÊ', 'ÛíÛÇÈÇíÊ');

$day_of_week = array('ÇáÃÍÏ', 'ÇáÅËäíä', 'ÇáËáÇËÇÁ', 'ÇáÃÑÈÚÇÁ', 'ÇáÎãíÓ', 'ÇáÌãÚå', 'ÇáÓÈÊ');
$month = array('íäÇíÑ', 'İÈÑÇíÑ', 'ãÇÑÓ', 'ÃÈÑíá', 'ãÇíæ', 'íæäíæ', 'íæáíæ', 'ÃÛÓØÓ', 'ÓÈÊãÈÑ', 'ÃßÊæÈÑ', 'äæİãÈÑ', 'ÏíÓãÈÑ');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'ÛíÑ ãÓãæÍ';
$strAction = 'ÇáÍÏË';
$strAddDeleteColumn = 'ÅÖÇİå/ÍĞİ ÚãæÏ ÍŞá';
$strAddDeleteRow = 'ÅÖÇİå/ÍĞİ Õİ ÓÌá';
$strAddNewField = 'ÅÖÇİÉ ÍŞá ÌÏíÏ';
$strAddPriv = 'ÅÖÇİÉ ÅãÊíÇÒ ÌÏíÏ';
$strAddPrivMessage = 'áŞÏ ÃÖİÊ ÅãÊíÇÒ ÌÏíÏ.';
$strAddSearchConditions = 'ÃÖİ ÔÑæØ ÇáÈÍË (ÌÓã ãä ÇáİŞÑå "where" clause):';
$strAddUser = 'ÃÖİ ãÓÊÎÏã ÌÏíÏ';
$strAddUserMessage = 'áŞÏ ÃÖİÊ ãÓÊÎÏã ÌÏíÏ.';
$strAffectedRows = 'Õİæİ ãÄËÑå:';
$strAfter = 'ÈÚÏ';
$strAll = 'Çáßá';
$strAlterOrderBy = 'ÊÚÏíá ÊÑÊíÈ ÇáÌÏæá ÈÜ';
$strAnalyzeTable = 'ÊÍáíá ÇáÌÏæá';
$strAnd = 'æ';
$strAny = 'Ãí';
$strAnyColumn = 'Ãí ÚãæÏ';
$strAnyDatabase = 'Ãí ŞÇÚÏÉ ÈíÇäÇÊ';
$strAnyHost = 'Ãí ãÓÊÖíİ';
$strAnyTable = 'Ãí ÌÏæá';
$strAnyUser = 'Ãí ãÓÊÎÏã';
$strAscending = 'ÊÕÇÚÏíÇğ';
$strAtBeginningOfTable = 'ãä ÈÏÇíÉ ÇáÌÏæá';
$strAtEndOfTable = 'ãä äåÇíÉ ÇáÌÏæá';
$strAttr = 'ÇáÎæÇÕ';

$strBack = 'ÑÌæÚ';
$strBinary = 'ËäÇÆí';
$strBinaryDoNotEdit = 'ËäÇÆí - áÇÊÍÑÑå';
$strBookmarkLabel = 'ÚáÇãå';
$strBookmarkQuery = 'ÚáÇãå ãÑÌÚíå SQL-ÅÓÊÚáÇã';
$strBookmarkThis = 'ÅÌÚá ÚáÇãå ãÑÌÚíå SQL-ÅÓÊÚáÇã';
$strBookmarkView = 'ÚÑÖ İŞØ';
$strBrowse = 'ÅÓÊÚÑÇÖ';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'áÇíãßä ÊÍãíá ÅãÊÏÇÏ MySQL,<br />ÇáÑÌÇÁ İÍÕ ÅÚÏÇÏÇÊ PHP.';
$strCarriage = 'ÅÑÌÇÚ ÇáÍãæáå: \\r';
$strChange = 'ÊÛííÑ';
$strCheckAll = 'ÅÎÊÑ Çáßá';
$strCheckDbPriv = 'İÍÕ ÅãÊíÇÒ ŞÇÚÏÉ ÇáÈíÇäÇÊ';
$strCheckTable = 'ÃÔÑ ááÌÏæá';
$strColumn = 'ÚãæÏ';
$strColumnNames = 'ÅÓã ÇáÚãæÏ';
$strCompleteInserts = 'ÇáÅÏÎÇá áŞÏ ÅßÊãá';
$strConfirm = 'åá ÊÑíÏ ÍŞÇğ Ãä ÊİÚá Ğáß¿';
$strCopyTable = 'äÓÎ ÇáÌÏæá Åáì';
$strCopyTableOK = 'ÇáÌÏæá %s áŞÏ Êã äÓÎå Åáì %s.';
$strCreate = 'Êßæíä';
$strCreateNewDatabase = 'Êßæíä ŞÇÚÏÉ ÈíÇäÇÊ ÌÏíÏå';
$strCreateNewTable = 'Êßæíä ÌÏæá ÌÏíÏ İí ŞÇÚÏÉ ÇáÈíÇäÇÊ ';
$strCriteria = 'ÇáãÚÇííÑ';

$strData = 'ÈíÇäÇÊ';
$strDatabase = 'ŞÇÚÏÉ ÇáÈíÇäÇÊ ';
$strDatabases = 'ŞÇÚÏÉ ÈíÇäÇÊ';
$strDatabasesStats = 'ÅÍÕÇÆíÇÊ ŞÇÚÏÉ ÈíÇäÇÊ';
$strDataOnly = 'ÈíÇäÇÊ İŞØ';
$strDefault = 'ÅİÊÑÇÖí';
$strDelete = 'ÍĞİ';
$strDeleted = 'áŞÏ Êã ÍĞİ ÇáÕİ';
$strDeletedRows = 'ÇáÕİæİ ÇáãÍĞæİå:';
$strDeleteFailed = 'ÇáÍĞİ ÎÇØÆ!';
$strDescending = 'ÊäÇÒáíÇğ';
$strDisplay = 'ÚÑÖ';
$strDisplayOrder = 'ÊÑÊíÈ ÇáÚÑÖ:';
$strDoAQuery = 'ÊÌÚá "ÅÓÊÚáÇã ÈæÇÓØÉ ÇáãËÇá" (wildcard: "%")';
$strDocu = 'ãÓÊäÏÇÊ æËÇÆŞíå';
$strDoYouReally = 'åá ÊÑíÏ ÍŞÇğ ÈÜ ';
$strDrop = 'ÍĞİ';
$strDropDB = 'ÍĞİ ŞÇÚÏÉ ÈíÇäÇÊ ';
$strDropTable = 'ÍĞİ ÌÏæá';
$strDumpingData = 'ÅÑÌÇÚ Ãæ ÅÓÊíÑÇÏ ÈíÇäÇÊ ÇáÌÏæá';
$strDynamic = 'ÏíäÇãíßí';

$strEdit = 'ÊÍÑíÑ';
$strEditPrivileges = 'ÊÍÑíÑ ÇáÅãÊíÇÒÇÊ';
$strEffective = 'İÚÇá';
$strEmpty = 'ÅİÑÇÛ ãÍÊæì';
$strEmptyResultSet = 'MySQL ŞÇã ÈÅÑÌÇÚ äÊíÌÉ ÅÚÏÇÏ İÇÑÛå (ãËáÇğ. Õİ ÕİÑí).';
$strEnd = 'äåÇíå';
$strEnglishPrivileges = ' ãáÇÍÙå: ÅÓã ÇáÅãÊíÇÒ áÜMySQL íÙåÑ æíõŞÑÃ ÈÇááÛå ÇáÅäÌáíÒíå İŞØ ';
$strError = 'ÎØÃ';
$strExtendedInserts = 'ÅÏÎÇá ãõÏÏ';
$strExtra = 'ÅÖÇİí';

$strField = 'ÍŞá';
$strFields = 'ÍŞæá';
$strFieldsEmpty = ' ÊÚÏÇÏ ÇáÍŞá İÇÑÛ! ';
$strFixed = 'ãËÈÊ';
$strFormat = 'ÕíÛå';
$strFormEmpty = 'íæÌÏ Şíãå ãİŞæÏå ÈÇáäãæĞÌ !';
$strFullText = 'äÕæÕ ßÇãáå';
$strFunction = 'ÏÇáå';

$strGenTime = 'ÇáæŞÊ ÇáãÊßæä';
$strGo = 'ÅĞåÈ';
$strGrants = 'Grants';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'áŞÏ ÚõÏöá.';
$strHasBeenCreated = 'áŞÏ Êßæä.';
$strHome = 'ÇáÑÆíÓíå';
$strHomepageOfficial = 'ÇáÕİÍå ÇáÑÆíÓíå ÇáÑÓãíå áÜ phpMyAdmin';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin ÕİÍÉ ÇáÊäÒíá';
$strHost = 'ÇáãÓÊÖíİ';
$strHostEmpty = 'ÅÓã ÇáãÓÊÖíİ İÇÑÛ!';

$strIfYouWish = 'ÅĞÇ ßäÊ ÊÑÛÈ İí Ãä ÊÍãá ÈÚÖ ÃÚãÏÉ ÇáÌÏæá İŞØ, ÍÏÏ ÈÇáİÇÕáå ÇáÊí ÊİÕá ŞÇÆãÉ ÇáÍŞá.';
$strIndex = 'İåÑÓÊ';
$strIndexes = 'İåÇÑÓ';
$strInsert = 'ÅÏÎÇá';
$strInsertAsNewRow = 'ÅÏÎÇá ßÕİ ÌÏíÏ';
$strInsertedRows = 'Õİæİ ãÏÎáå:';
$strInsertNewRow = 'ÅÏÎÇá Õİ ÌÏíÏ';
$strInsertTextfiles = 'ÅÏÎÇá ãáİ äÕí İí ÇáÌÏæá';
$strInstructions = 'ÇáÃæÇãÑ';
$strInUse = 'ŞíÏ ÇáÅÓÊÚãÇá';

$strKeyname = 'ÅÓã ÇáãİÊÇÍ';
$strKill = 'ÅÈØÇá';

$strLength = 'ÇáØæá';
$strLengthSet = 'ÇáØæá/ÇáŞíãå*';
$strLineFeed = 'ÎØæØ ãÚÑİå: \\n';
$strLines = 'ÎØæØ';
$strLocationTextfile = 'ãßÇä ãáİ ÇáäÕæÕ';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'ÊÓÌíá ÎÑæÌ';

$strModifications = 'ÊãÊ ÇáÊÚÏíáÇÊ';
$strModify = 'ÊÚÏíá';
$strMySQLReloaded = 'MySQL ãÚÇÏ ÊÍãíáå.';
$strMySQLSaid = 'MySQL ŞÇá: ';
$strMySQLShowProcess = 'ÚÑÖ ÚãáíÇÊ';
$strMySQLShowStatus = 'ÚÑÖ MySQL runtime ßãÚáæãÇÊ';
$strMySQLShowVars = 'ÚÑÖ MySQL ßãÊÛíÑÇÊå';

$strName = 'ÇáÅÓã';
$strNbRecords = 'ÑŞã ÇáÓÌáÇÊ';
$strNext = 'ÇáÊÇáí';
$strNo = 'áÇ';
$strNoDatabases = 'áÇíæÌÏ ŞæÇÚÏ ÈíÇäÇÊ';
$strNoDropDatabases = 'ãÚØá "ÍĞİ ŞÇÚÏÉ ÈíÇäÇÊ"ÇáÃãÑ ';
$strNoModification = 'áÇ ÊÛííÑÇÊ';
$strNoPassword = 'áÇ ßáãÉ ÓÑ';
$strNoPrivileges = 'ÅãÊíÇÒ ÛíÑ ãæÌæÏ';
$strNoRights = 'áíÓ áÏíß ÇáÍŞæŞ ÇáßÇİíå ÈÃä Êßæä åäÇ ÇáÂä!';
$strNoTablesFound = 'áÇíæÌÏ ÌÏÇæá ãÊæİÑå İí ŞÇÚÏÉ ÇáÈíÇäÇÊ åĞå!.';
$strNotNumber = 'åĞÇ áíÓ ÑŞã!';
$strNotValidNumber = ' åĞÇ áíÓ ÚÏÏ Õİ ÕÍíÍ!';
$strNoUsersFound = 'ÇáãÓÎÏã(Üíä) áã íÊã ÅíÌÇÏåã.';
$strNull = 'ÎÇáí';
$strNumberIndexes = ' ÚÏÏ ÇáİåÇÑÓ ÇáãÊŞÏãå ';

$strOftenQuotation = 'ÛÇáÈÇğ ÚáÇãÇÊ ÇáÅŞÊÈÇÓ. ÅÎÊíÇÑí íÚäí ÈÃä ÇáÍŞæá  char æ varchar ÊÑİŞ ÈÜ " ".';
$strOptimizeTable = 'ãÍÓä ÇáÌÏæá';
$strOptionalControls = 'ÅÎÊíÇÑí. ÇáÊÍßã İí ßíİíÉ ßÊÇÈÉ Ãæ ŞÑÇÁÉ ÇáÃÍÑİ Ãæ ÇáÌãá ÇáÎÇÕå.';
$strOptionally = 'ÅÎÊíÇÑí';
$strOr = 'Ãæ';
$strOverhead = 'ÇáİæŞí';

$strPartialText = 'äÕæÕ ÌÒÆíå';
$strPassword = 'ßáãÉ ÇáÓÑ';
$strPasswordEmpty = 'ßáãÉ ÇáÓÑ İÇÑÛå!';
$strPasswordNotSame = 'ßáãÊÇ ÇáÓÑ ÛíÑ ãÊÔÇÈåÊÇä!';
$strPHPVersion = 'PHPÅÕÏÇÑÉ ';
$strPmaDocumentation = 'phpMyAdmin ãÓÊäÏÇÊ æËÇÆŞíå áÜ';
$strPos1 = 'ÈÏÇíå';
$strPrevious = 'ÓÇÈŞ';
$strPrimary = 'ÃÓÇÓí';
$strPrimaryKey = 'ãİÊÇÍ ÃÓÇÓí';
$strPrintView = 'ÚÑÖ ÇáØÈÇÚå';
$strPrivileges = 'ÇáÅãÊíÇÒÇÊ';
$strProperties = 'ÎÕÇÆÕ';

$strQBE = 'ÅÓÊÚáÇã ÈæÇÓØÉ ãËÇá';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'İí ŞÇÚÏÉ ÇáÈíÇäÇÊ SQL-ÅÓÊÚáÇã ';

$strRecords = 'ÇáÓÌáÇÊ';
$strReloadFailed = ' ÅÚÇÏÉ ÊÍãíá ÎÇØÆåMySQL.';
$strReloadMySQL = 'ÅÚÇÏÉ ÊÍãíá MySQL';
$strRememberReload = 'ÊĞßíÑ áÅÚÇÏÉ ÊÍãíá ÇáÎÇÏã.';
$strRenameTable = 'ÊÛííÑ ÅÓã ÌÏæá Åáì';
$strRenameTableOK = 'Êã ÊÛííÑ ÅÓãåã Åáì %s  ÌÏæá%s';
$strRepairTable = 'ÊäŞíÍ ÌÏæá';
$strReplace = 'ÅÓÊÈÏÇá';
$strReplaceTable = 'ÅÓÊÈÏÇá ÈíÇäÇÊ ÇáÌÏæá ÈÇáãáİ';
$strReset = 'ÅáÛÇÁ';
$strReType = 'ÃÚÏ ßÊÇÈå';
$strRevoke = 'ÅÈØÇá';
$strRevokeGrant = 'ÅÈØÇá Grant';
$strRevokePriv = 'ÅÈØÇá ÅãÊíÇÒÇÊ';
$strRowLength = 'Øæá ÇáÕİ';
$strRows = 'Õİæİ';
$strRowsFrom = 'Õİæİ ÊÈÏÃ ãä';
$strRowSize = ' ãŞÇÓ ÇáÕİ ';
$strRowsStatistic = 'ÅÍÕÇÁ ÇáÕİ';
$strRunning = 'íÚãá Úáì ';
$strRunQuery = 'ÅÑÓÇá ÇáÅÓÊÚáÇã';

$strSave = 'ÍİÙ';
$strSelect = 'ÅÎÊíÇÑ';
$strSelectFields = 'ÅÎÊíÇÑ ÍŞæá (Úáì ÇáÃŞá æÇÍÏ):';
$strSelectNumRows = 'İí ÇáÅÓÊÚáÇã';
$strSequence = 'ÊÓáÓá.';
$strServerChoice = 'ÅÎÊíÇÑ ÇáÎÇÏã';
$strServerVersion = 'ÅÕÏÇÑÉ ÇáÎÇÏã';
$strSetEnumVal = 'ÅĞÇ ßÇä äæÚ ÇáÍŞá åæ "enum" Ãæ "set", ÇáÑÌÇÁ ÅÏÎÇá ÇáŞíã ÈÅÓÊÎÏÇã åĞÇ ÇáÊäÓíŞ: \'a\',\'b\',\'c\'...<br />ÅĞÇ ßäÊ ÊÍÊÇÌ ÈÃä ÊÖÚ ÚáÇãÉ ÇáÔÑØå ÇáãÇÆáå ááíÓÇÑ ("\") Ãæ ÚáÇãÉ ÇáÅŞÊÈÇÓ ÇáİÑÏíå ("\'") İíãÇ Èíä Êáß ÇáŞíã, ÅÌÚáåÇ ßÔÑØå ãÇÆáå ááíÓÇÑ (ãËáÇğ \'\\\\xyz\' Ãæ \'a\\\'b\').';
$strShow = 'ÚÑÖ';
$strShowingRecords = 'ãÔÇåÏÉ ÇáÓÌáÇÊ ';
$strShowPHPInfo = 'ÚÑÖ ãÚáæãÇÊ PHP';
$strShowThisQuery = ' ÚÑÖ åĞÇ ÇáÅÓÊÚáÇã åäÇ ãÑå ÃÎÑì ';
$strSingly = '(İÑÏí)';
$strSize = 'ÇáãŞÇÓ';
$strSort = 'ÊÕäíİ';
$strSpaceUsage = 'ÅÓÊÚãÇá ÇáİÑÇÛÇÊ';
$strSQLQuery = 'SQL-ÅÓÊÚáÇã';
$strStatement = 'ÃæÇãÑ';
$strStrucCSV = 'ÈíÇäÇÊ CSV';
$strStrucData = 'ÇáäÙã Çáåíßáíå æÇáÈíÇäÇÊ';
$strStrucDrop = 'ÃÖİ \'ÍĞİ ÌÏæá\'';
$strStrucExcelCSV = 'CSV áÑäÇãÌ ÅßÓíá Ms Excel';
$strStrucOnly = 'ÇáäÙã Çáåíßáíå İŞØ';
$strSubmit = 'ÅÑÓÇá';
$strSuccess = 'ÇáÎÇÕ Èß Êã ÊäİíĞå ÈäÌÇÍ SQL-ÅÓÊÚáÇã';
$strSum = 'ÌãÚ';

$strTable = 'ÇáÌÏæá ';
$strTableComments = 'ÊÚáíŞÇÊ ÇáÌÏæá';
$strTableEmpty = 'ÅÓã ÇáÌÏæá İÇÑÛ!';
$strTableMaintenance = 'ÕíÇäÉ ÇáÌÏæá';
$strTables = '%s ÌÏæá Ãæ ÌÏÇæá';
$strTableStructure = 'ÇáäÙã Çáåíßáíå ááÌÏæá';
$strTableType = 'äæÚ ÇáÌÏæá';
$strTextAreaLength = ' ÈÓÈÈ Øæáå,<br /> İãä ÇáãÍÊãá Ãä åĞÇ ÇáÍŞá ÛíÑ ŞÇÈá ááÊÍÑíÑ ';
$strTheContent = 'áŞÏ Êã ÅÏÎÇá ãÍÊæíÇÊ ãáİß.';
$strTheContents = 'áŞÏ Êã ÅÓÊÈÏÇá ãÍÊæíÇÊ ÇáÌÏæá ÇáãÍÏÏ ááÕİæİ ÈÇáãİÊÇÍ ÇáããíÒ Ãæ ÇáÃÓÇÓí ÇáããÇËá áåãÇ ÈãÍÊæíÇÊ Çáãáİ.';
$strTheTerminator = 'İÇÕá ÇáÍŞæá.';
$strTotal = 'ÇáãÌãæÚ';
$strType = 'ÇáäæÚ';

$strUncheckAll = 'ÅáÛÇÁ ÊÍÏíÏ Çáßá';
$strUnique = 'ããíÒ';
$strUpdateQuery = 'ÊÌÏíÏ ÅÓÊÚáÇã';
$strUsage = 'ÇáÅÓÊÎÏÇã';
$strUseBackquotes = 'ÅÓÊÎÏã ÚáÇãÉ ÇáÅŞÊÈÇÓ ÇáãİÑÏå \' ÈÇáÌÏÇæá æÃÓãÇÁ ÇáÍŞæá';
$strUser = 'ÇáãÓÊÎÏã';
$strUserEmpty = 'ÅÓã ÇáãÓÊÎÏã İÇÑÛ!';
$strUserName = 'ÅÓã ÇáãÓÊÎÏã';
$strUsers = 'ÇáÓÊÎÏãíä';
$strUseTables = 'ÅÓÊÎÏã ÇáÌÏæá';

$strValue = 'ÇáŞíãå';
$strViewDump = 'ÚÑÖ dump (ÃæÇãÑ ÇáÅäÔÇÁ Çáåíßáíå) ááÌÏæá';
$strViewDumpDB = 'ÚÑÖ dump (ÃæÇãÑ ÇáÅäÔÇÁ Çáåíßáíå) áŞÇÚÏÉ ÈíÇäÇÊ';

$strWrongUser = 'ÎØÃ ÅÓã ÇáãÓÊÎÏã/ßáãÉ ÇáÓÑ. ÇáÏÎæá ããäæÚ.';

$strYes = 'äÚã';


// To translate
$strAddToIndex = 'Add to index &nbsp;%s&nbsp;column(s)';
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Insert a new record';
$strAnIndex = 'An index has been added on %s';
$strAPrimaryKey = 'A primary key has been added on %s';
$strBookmarkDeleted = 'The bookmark has been deleted.';
$strCantRenameIdxToPrimary = 'Can\'t rename index to PRIMARY!';
$strCardinality = 'Cardinality';
$strCreateIndex = 'Create an index on&nbsp;%s&nbsp;columns';
$strCreateIndexTopic = 'Create a new index';
$strDatabaseHasBeenDropped = 'Database %s has been dropped.';
$strDeleteUserMessage = 'You have deleted the user %s.';
$strFieldHasBeenDropped = 'Field %s has been dropped';
$strFieldsEnclosedBy = 'Fields enclosed by';
$strFieldsEscapedBy = 'Fields escaped by';
$strFieldsTerminatedBy = 'Fields terminated by';
$strFlushTable = 'Flush the table ("FLUSH")';
$strIdxFulltext = 'Fulltext';
$strIgnore = 'Ignore';
$strIndexHasBeenDropped = 'Index %s has been dropped';
$strIndexName = 'Index name&nbsp;:';
$strIndexType = 'Index type&nbsp;:';
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.';
$strKeepPass = 'Do not change the password';
$strLimitNumRows = 'Number of records per page';
$strLinesTerminatedBy = 'Lines terminated by';
$strModifyIndexTopic = 'Modify an index';
$strMoveTable = 'Move table to (database<b>.</b>table):';
$strMoveTableOK = 'Table %s has been moved to %s.';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strNoIndex = 'No index defined!';
$strNoIndexPartsDefined = 'No index parts defined!';
$strNone = 'None';
$strNoQuery = 'No SQL query!';
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';
$strPrimaryKeyName = 'The name of the primary key must be... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>must</b> be the name of and <b>only of</b> a primary key!)';
$strRevokeGrantMessage = 'You have revoked the Grant privilege for %s';
$strRevokeMessage = 'You have revoked the privileges for %s';
$strRunningAs = 'as';
$strRunSQLQuery = 'Run SQL query/queries on database %s';
$strSend = 'Save as file';
$strShowAll = 'Show all';
$strShowCols = 'Show columns';
$strShowTables = 'Show tables';
$strStartingRecord = 'Starting record';
$strTableHasBeenDropped = 'Table %s has been dropped';
$strTableHasBeenEmptied = 'Table %s has been emptied';
$strTableHasBeenFlushed = 'Table %s has been flushed';
$strUpdatePrivMessage = 'You have updated the privileges for %s.';
$strUpdateProfile = 'Update profile:';
$strUpdateProfileMessage = 'The profile has been updated.';
$strWelcome = 'Welcome to %s';
$strWithChecked = 'With selected:';
$strZip = '"zipped"';

?>
