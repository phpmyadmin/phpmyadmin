<?php
/* $Id$ */

/**
 * Original Translation to Arabic by Fisal <fisal77 at hotmail.com>
 * Update by Tarik kallida <kallida at caramail.com>
 */


$charset = 'windows-1256';
$text_dir = 'rtl'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'Tahoma, verdana, arial, helvetica, sans-serif';
$right_font_family = '"Windows UI", Tahoma, verdana, arial, helvetica, sans-serif';
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
$strAddToIndex = 'ÅÖÇİå ßİåÑÓ &nbsp;%s&nbsp;Õİ(Üæİ)';
$strAddUser = 'ÃÖİ ãÓÊÎÏã ÌÏíÏ';
$strAddUserMessage = 'áŞÏ ÃÖİÊ ãÓÊÎÏã ÌÏíÏ.';
$strAffectedRows = 'Õİæİ ãÄËÑå:';
$strAfter = 'ÈÚÏ %s';
$strAfterInsertBack = 'ÅÑÌÇÚ';
$strAfterInsertNewInsert = 'ÅÏÎÇá ÓÌá ÌÏíÏ';
$strAll = 'Çáßá';
$strAlterOrderBy = 'ÊÚÏíá ÊÑÊíÈ ÇáÌÏæá ÈÜ';
$strAnalyzeTable = 'ÊÍáíá ÇáÌÏæá';
$strAnd = 'æ';
$strAnIndex = 'áŞÏ ÃõÖíİ ÇáİåÑÓ İí %s';
$strAny = 'Ãí';
$strAnyColumn = 'Ãí ÚãæÏ';
$strAnyDatabase = 'Ãí ŞÇÚÏÉ ÈíÇäÇÊ';
$strAnyHost = 'Ãí ãÓÊÖíİ';
$strAnyTable = 'Ãí ÌÏæá';
$strAnyUser = 'Ãí ãÓÊÎÏã';
$strAPrimaryKey = 'áŞÏ ÃõÖíİ ÇáãİÊÇÍ ÇáÃÓÇÓí İí %s';
$strAscending = 'ÊÕÇÚÏíÇğ';
$strAtBeginningOfTable = 'ãä ÈÏÇíÉ ÇáÌÏæá';
$strAtEndOfTable = 'ãä äåÇíÉ ÇáÌÏæá';
$strAttr = 'ÇáÎæÇÕ';

$strBack = 'ÑÌæÚ';
$strBinary = 'ËäÇÆí';
$strBinaryDoNotEdit = 'ËäÇÆí - áÇÊÍÑÑå';
$strBookmarkDeleted = 'áŞÏ ÍõĞİÊ ÇáÚáÇãå ÇáãÑÌÚíå.';
$strBookmarkLabel = 'ÚáÇãå';
$strBookmarkQuery = 'ÚáÇãå ãÑÌÚíå SQL-ÅÓÊÚáÇã';
$strBookmarkThis = 'ÅÌÚá ÚáÇãå ãÑÌÚíå SQL-ÅÓÊÚáÇã';
$strBookmarkView = 'ÚÑÖ İŞØ';
$strBrowse = 'ÅÓÊÚÑÇÖ';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'áÇíãßä ÊÍãíá ÅãÊÏÇÏ MySQL,<br />ÇáÑÌÇÁ İÍÕ ÅÚÏÇÏÇÊ PHP.';
$strCantRenameIdxToPrimary = 'áÇíãßä ÊÛííÑ ÅÓã ÇáİåÑÓ Åáì ÇáÃÓÇÓí!';
$strCarriage = 'ÅÑÌÇÚ ÇáÍãæáå: \\r';
$strChange = 'ÊÛííÑ';
$strChangePassword = 'ÊÛííÑ ßáãÉ ÇáÓÑ';
$strCheckAll = 'ÅÎÊÑ Çáßá';
$strCheckDbPriv = 'İÍÕ ÅãÊíÇÒ ŞÇÚÏÉ ÇáÈíÇäÇÊ';
$strCheckTable = 'ÃÔÑ ááÌÏæá';
$strColumn = 'ÚãæÏ';
$strColumnNames = 'ÅÓã ÇáÚãæÏ';
$strCompleteInserts = 'ÇáÅÏÎÇá áŞÏ ÅßÊãá';
$strConfirm = 'åá ÊÑíÏ ÍŞÇğ Ãä ÊİÚá Ğáß¿';
$strCookiesRequired = 'íÌÈ ÊİÚíá ÏÚã ÇáßæßíÒ İí åĞå ÇáãÑÍáÉ.';
$strCopyTable = 'äÓÎ ÇáÌÏæá Åáì';
$strCopyTableOK = 'ÇáÌÏæá %s áŞÏ Êã äÓÎå Åáì %s.';
$strCreate = 'Êßæíä';
$strCreateIndex = 'ÊÕãíã İåÑÓå Úáì&nbsp;%s&nbsp;ÚãæÏ';
$strCreateIndexTopic = 'ÊÕãíã İåÑÓå ÌÏíÏå';
$strCreateNewDatabase = 'Êßæíä ŞÇÚÏÉ ÈíÇäÇÊ ÌÏíÏå';
$strCreateNewTable = 'Êßæíä ÌÏæá ÌÏíÏ İí ŞÇÚÏÉ ÇáÈíÇäÇÊ %s';
$strCriteria = 'ÇáãÚÇííÑ';

$strData = 'ÈíÇäÇÊ';
$strDatabase = 'ŞÇÚÏÉ ÇáÈíÇäÇÊ ';
$strDatabaseHasBeenDropped = 'ŞÇÚÏÉ ÈíÇäÇÊ %s ãÍĞæİå.';
$strDatabases = 'ŞÇÚÏÉ ÈíÇäÇÊ';
$strDatabasesStats = 'ÅÍÕÇÆíÇÊ ŞÇÚÏÉ ÈíÇäÇÊ';
$strDatabaseWildcard = 'ŞÇÚÏÉ ÈíÇäÇÊ:';
$strDataOnly = 'ÈíÇäÇÊ İŞØ';
$strDefault = 'ÅİÊÑÇÖí';
$strDelete = 'ÍĞİ';
$strDeleted = 'áŞÏ Êã ÍĞİ ÇáÕİ';
$strDeletedRows = 'ÇáÕİæİ ÇáãÍĞæİå:';
$strDeleteFailed = 'ÇáÍĞİ ÎÇØÆ!';
$strDeleteUserMessage = 'áŞÏ ÍĞİÊ ÇáãÓÊÎÏã %s.';
$strDescending = 'ÊäÇÒáíÇğ';
$strDisplay = 'ÚÑÖ';
$strDisplayOrder = 'ÊÑÊíÈ ÇáÚÑÖ:';
$strDoAQuery = 'ÊÌÚá "ÅÓÊÚáÇã ÈæÇÓØÉ ÇáãËÇá" (wildcard: "%")';
$strDocu = 'ãÓÊäÏÇÊ æËÇÆŞíå';
$strDoYouReally = 'åá ÊÑíÏ ÍŞÇğ ÈÜ ';
$strDrop = 'ÍĞİ';
$strDropDB = 'ÍĞİ ŞÇÚÏÉ ÈíÇäÇÊ %s';
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
$strFieldHasBeenDropped = 'ÍŞá ãÍĞæİ %s';
$strFields = 'ÍŞæá';
$strFieldsEmpty = ' ÊÚÏÇÏ ÇáÍŞá İÇÑÛ! ';
$strFieldsEnclosedBy = 'ÍŞá ãÖãä ÈÜ';
$strFieldsEscapedBy = 'ÍŞá ãõÊÌÇåá ÈÜ';
$strFieldsTerminatedBy = 'ÍŞá ãİÕæá ÈÜ';
$strFixed = 'ãËÈÊ';
$strFlushTable = 'ÊÏİíŞ ÇáÌÏæá ("FLUSH")';
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

$strIdxFulltext = 'ÇáäÕ ßÇãáÇğ';
$strIfYouWish = 'ÅĞÇ ßäÊ ÊÑÛÈ İí Ãä ÊÍãá ÈÚÖ ÃÚãÏÉ ÇáÌÏæá İŞØ, ÍÏÏ ÈÇáİÇÕáå ÇáÊí ÊİÕá ŞÇÆãÉ ÇáÍŞá.';
$strIgnore = 'ÊÌÇåá';
$strIndex = 'İåÑÓÊ';
$strIndexHasBeenDropped = 'İåÑÓå ãÍĞæİå %s';
$strIndexName = 'ÅÓã ÇáİåÑÓ&nbsp;:';
$strIndexType = 'äæÚ ÇáİåÑÓ&nbsp;:';
$strIndexes = 'İåÇÑÓ';
$strInsert = 'ÅÏÎÇá';
$strInsertAsNewRow = 'ÅÏÎÇá ßÕİ ÌÏíÏ';
$strInsertedRows = 'Õİæİ ãÏÎáå:';
$strInsertNewRow = 'ÅÏÎÇá Õİ ÌÏíÏ';
$strInsertTextfiles = 'ÅÏÎÇá ãáİ äÕí İí ÇáÌÏæá';
$strInstructions = 'ÇáÃæÇãÑ';
$strInUse = 'ŞíÏ ÇáÅÓÊÚãÇá';
$strInvalidName = '"%s" ßáãå ãÍÌæÒå, áÇíãßäß ÅÓÊÎÏÇãåÇ ßÅÓã ŞÇÚÏÉ ÈíÇäÇÊ/ÌÏæá/ÍŞá.';

$strKeepPass = 'áÇÊÛíÑ ßáãÉ ÇáÓÑ';
$strKeyname = 'ÅÓã ÇáãİÊÇÍ';
$strKill = 'ÅÈØÇá';

$strLength = 'ÇáØæá';
$strLengthSet = 'ÇáØæá/ÇáŞíãå*';
$strLimitNumRows = 'ÑŞã ÇáÓÌáÇÊ áßá ÕİÍå';
$strLineFeed = 'ÎØæØ ãÚÑİå: \\n';
$strLines = 'ÎØæØ';
$strLinesTerminatedBy = 'ÎØæØ ãİÕæáå ÈÜ';
$strLocationTextfile = 'ãßÇä ãáİ ÇáäÕæÕ';
$strLogin = 'ÇáãõÚÑİ';
$strLogout = 'ÊÓÌíá ÎÑæÌ';
$strLogPassword = 'ßáãÉ ÇáÓÑ:';
$strLogUsername = 'ÅÓã ÇáãõÓÊÎÏã:';

$strModifications = 'ÊãÊ ÇáÊÚÏíáÇÊ';
$strModify = 'ÊÚÏíá';
$strModifyIndexTopic = 'ÊÚÏíá ÇáİåÑÓå';
$strMoveTable = 'äŞá ÌÏæá Åáì (ŞÇÚÏÉ ÈíÇäÇÊ<b>.</b>ÌÏæá):';
$strMoveTableOK = '%s ÌÏæá Êã äŞáå Åáì %s.';
$strMySQLReloaded = 'MySQL ãÚÇÏ ÊÍãíáå.';
$strMySQLSaid = 'MySQL ŞÇá: ';
$strMySQLServerProcess = 'MySQL %pma_s1% íÚãá Úáì %pma_s2% ßÜ %pma_s3%';
$strMySQLShowProcess = 'ÚÑÖ ÚãáíÇÊ';
$strMySQLShowStatus = 'ÚÑÖ MySQL runtime ßãÚáæãÇÊ';
$strMySQLShowVars = 'ÚÑÖ MySQL ßãÊÛíÑÇÊå';

$strName = 'ÇáÅÓã';
$strNbRecords = 'ÑŞã ÇáÓÌáÇÊ';
$strNext = 'ÇáÊÇáí';
$strNo = 'áÇ';
$strNoDatabases = 'áÇíæÌÏ ŞæÇÚÏ ÈíÇäÇÊ';
$strNoDropDatabases = 'ãÚØá "ÍĞİ ŞÇÚÏÉ ÈíÇäÇÊ"ÇáÃãÑ ';
$strNoFrames = 'phpMyAdmin ÃßËÑ ÊİåãÇğ ãÚ ãÓÊÚÑÖ <b>ÇáÅØÇÑÇÊ</b>.';
$strNoIndex = 'İåÑÓ ÛíÑ ãÚÑİ!';
$strNoIndexPartsDefined = 'ÅÌÒÇÁ ÇáİåÑÓå ÛíÑ ãÚÑİå!';
$strNoModification = 'áÇ ÊÛííÑÇÊ';
$strNone = 'áÇÔÆ';
$strNoPassword = 'áÇ ßáãÉ ÓÑ';
$strNoPrivileges = 'ÅãÊíÇÒ ÛíÑ ãæÌæÏ';
$strNoQuery = 'áíÓÊ ÅÓÊÚáÇã SQL!';
$strNoRights = 'áíÓ áÏíß ÇáÍŞæŞ ÇáßÇİíå ÈÃä Êßæä åäÇ ÇáÂä!';
$strNoTablesFound = 'áÇíæÌÏ ÌÏÇæá ãÊæİÑå İí ŞÇÚÏÉ ÇáÈíÇäÇÊ åĞå!.';
$strNotNumber = 'åĞÇ áíÓ ÑŞã!';
$strNotValidNumber = ' åĞÇ áíÓ ÚÏÏ Õİ ÕÍíÍ!';
$strNoUsersFound = 'ÇáãÓÎÏã(Üíä) áã íÊã ÅíÌÇÏåã.';
$strNull = 'ÎÇáí';

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
$strPmaUriError = 'ÇáãÊÛíÑ <tt>$cfgPmaAbsoluteUri</tt> íÌÈ ÊÚÏíáå İí ãáİ Çáßæİíß config.inc.php !';
$strPos1 = 'ÈÏÇíå';
$strPrevious = 'ÓÇÈŞ';
$strPrimary = 'ÃÓÇÓí';
$strPrimaryKey = 'ãİÊÇÍ ÃÓÇÓí';
$strPrimaryKeyHasBeenDropped = 'áŞÏ Êã ÍĞİ ÇáãİÊÇÍ ÇáÃÓÇÓí';
$strPrimaryKeyName = 'ÅÓã ÇáãİÊÇÍ ÇáÃÓÇÓí íÌÈ Ãä íßæä ÃÓÇÓí... PRIMARY!';
$strPrimaryKeyWarning = '("ÇáÃÓÇÓí" <b>íÌÈ</b> íÌÈ Ãä íßæä ÇáÃÓã <b>æÃíÖÇğ İŞØ</b> ÇáãİÊÇÍ ÇáÃÓÇÓí!)';
$strPrintView = 'ÚÑÖ ÇáØÈÇÚå';
$strPrivileges = 'ÇáÅãÊíÇÒÇÊ';
$strProperties = 'ÎÕÇÆÕ';

$strQBE = 'ÅÓÊÚáÇã ÈæÇÓØÉ ãËÇá';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'İí ŞÇÚÏÉ ÇáÈíÇäÇÊ SQL-ÅÓÊÚáÇã <b>%s</b>:';

$strRecords = 'ÇáÓÌáÇÊ';
$strReferentialIntegrity = 'ÊÍÏíÏ referential integrity:';
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
$strRevokeGrantMessage = 'áŞÏ ÃÈØáÊ ÅãÊíÇÒ Grant áÜ %s';
$strRevokeMessage = 'áŞÏ ÃÈØáÊ ÇáÃãÊíÇÒÇÊ áÜ %s';
$strRevokePriv = 'ÅÈØÇá ÅãÊíÇÒÇÊ';
$strRowLength = 'Øæá ÇáÕİ';
$strRows = 'Õİæİ';
$strRowsFrom = 'Õİæİ ÊÈÏÃ ãä';
$strRowSize = ' ãŞÇÓ ÇáÕİ ';
$strRowsModeHorizontal = 'ÃİŞí';
$strRowsModeOptions = ' %s æ ÅÚÇÏÉ ÇáÑÄæÓ ÈÚÏ %s ÍŞá';
$strRowsModeVertical = 'ÚãæÏí';
$strRowsStatistic = 'ÅÍÕÇÁ ÇáÕİ';
$strRunning = 'íÚãá Úáì %s';
$strRunQuery = 'ÅÑÓÇá ÇáÅÓÊÚáÇã';
$strRunSQLQuery = 'ÊäİíĞ ÅÓÊÚáÇã/ÅÓÊÚáÇãÇÊ SQL Úáì ŞÇÚÏÉ ÈíÇäÇÊ %s';

$strSave = 'ÍİÙ';
$strSelect = 'ÅÎÊíÇÑ';
$strSelectADb = 'ÅÎÊÑ ŞÇÚÏÉ ÈíÇäÇÊ ãä ÇáŞÇÆãÉ';
$strSelectAll = 'ÊÍÏíÏ Çáßá';
$strSelectFields = 'ÅÎÊíÇÑ ÍŞæá (Úáì ÇáÃŞá æÇÍÏ):';
$strSelectNumRows = 'İí ÇáÅÓÊÚáÇã';
$strSend = 'ÍİÙ ßãáİ';
$strServerChoice = 'ÅÎÊíÇÑ ÇáÎÇÏã';
$strServerVersion = 'ÅÕÏÇÑÉ ÇáÎÇÏã';
$strSetEnumVal = 'ÅĞÇ ßÇä äæÚ ÇáÍŞá åæ "enum" Ãæ "set", ÇáÑÌÇÁ ÅÏÎÇá ÇáŞíã ÈÅÓÊÎÏÇã åĞÇ ÇáÊäÓíŞ: \'a\',\'b\',\'c\'...<br />ÅĞÇ ßäÊ ÊÍÊÇÌ ÈÃä ÊÖÚ ÚáÇãÉ ÇáÔÑØå ÇáãÇÆáå ááíÓÇÑ ("\") Ãæ ÚáÇãÉ ÇáÅŞÊÈÇÓ ÇáİÑÏíå ("\'") İíãÇ Èíä Êáß ÇáŞíã, ÅÌÚáåÇ ßÔÑØå ãÇÆáå ááíÓÇÑ (ãËáÇğ \'\\\\xyz\' Ãæ \'a\\\'b\').';
$strShow = 'ÚÑÖ';
$strShowAll = 'ÔÇåÏ Çáßá';
$strShowCols = 'ÔÇåÏ ÇáÃÚãÏå';
$strShowingRecords = 'ãÔÇåÏÉ ÇáÓÌáÇÊ ';
$strShowPHPInfo = 'ÚÑÖ ãÚáæãÇÊ PHP';
$strShowTables = 'ÔÇåÏ ÇáÌÏæá';
$strShowThisQuery = ' ÚÑÖ åĞÇ ÇáÅÓÊÚáÇã åäÇ ãÑå ÃÎÑì ';
$strSingly = '(İÑÏí)';
$strSize = 'ÇáãŞÇÓ';
$strSort = 'ÊÕäíİ';
$strSpaceUsage = 'ÅÓÊÚãÇá ÇáİÑÇÛÇÊ';
$strSQLQuery = 'SQL-ÅÓÊÚáÇã';
$strStartingRecord = 'ÈÏÇíÉ ÇáÓÌá';
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
$strTableHasBeenDropped = 'ÌÏæá %s ÍõĞİÊ';
$strTableHasBeenEmptied = 'ÌÏæá %s ÃõİÑÛÊ ãÍÊæíÇÊåÇ';
$strTableHasBeenFlushed = 'ÌÏæá %s ÏõİŞÊ';
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
$strUnselectAll = 'ÅáÛÇÁ ÊÍÏíÏ Çáßá';
$strUpdatePrivMessage = 'áŞÏ ÌÏÏÊ æÍÏËÊ ÇáÅãÊíÇÒÇÊ áÜ %s.';
$strUpdateProfile = 'ÊÌÏíÏ ÇáÚÑÖ ÇáÌÇäÈí:';
$strUpdateProfileMessage = 'áŞÏ Êã ÊÌÏíÏ ÇáÚÑÖ ÇáÌÇäÈí.';
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

$strWelcome = 'ÃåáÇğ İí %s';
$strWithChecked = 'ãÚ ÇáãÍÏÏ:';
$strWrongUser = 'ÎØÃ ÅÓã ÇáãÓÊÎÏã/ßáãÉ ÇáÓÑ. ÇáÏÎæá ããäæÚ.';

$strYes = 'äÚã';

$strZip = '"zipped" "ãÖÛæØ"';

// To translate
$strCardinality = 'Cardinality';
?>