<?php
/* $Id$ */

/**
 * Original translation to Arabic by Fisal <fisal77 at hotmail.com>
 * Update by Tarik kallida <kallida at caramail.com>
 */

$charset = 'windows-1256';
$text_dir = 'rtl'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'Tahoma, verdana, arial, helvetica, sans-serif';
$right_font_family = '"Windows UI", Tahoma, verdana, arial, helvetica, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('ÈÇíÊ', 'ßíáæÈÇíÊ', 'ãíÌÇÈÇíÊ', 'ÛíÛÇÈÇíÊ');

$day_of_week = array('ÇáÃÍÏ', 'ÇáÅËäíä', 'ÇáËáÇËÇÁ', 'ÇáÃÑÈÚÇÁ', 'ÇáÎãíÓ', 'ÇáÌãÚÉ', 'ÇáÓÈÊ');
$month = array('íäÇíÑ', 'İÈÑÇíÑ', 'ãÇÑÓ', 'ÃÈÑíá', 'ãÇíæ', 'íæäíæ', 'íæáíæ', 'ÃÛÓØÓ', 'ÓÈÊãÈÑ', 'ÃßÊæÈÑ', 'äæİãÈÑ', 'ÏíÓãÈÑ');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d %B %Y ÇáÓÇÚÉ %H:%M';

$strAccessDenied = 'ÛíÑ ãÓãæÍ';
$strAction = 'ÇáÚãáíÉ';
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
$strAfterInsertBack = 'ÇáÑÌæÚ Åáì ÇáÕİÍÉ ÇáÓÇÈŞÉ';
$strAfterInsertNewInsert = 'ÅÏÎÇá ÊÓÌíá ÌÏíÏ';
$strAll = 'Çáßá';
$strAlterOrderBy = 'ÊÚÏíá ÊÑÊíÈ ÇáÌÏæá ÈÜ';
$strAnalyzeTable = 'ÊÍáíá ÇáÌÏæá';
$strAnd = 'æ';
$strAnIndex = 'áŞÏ ÃõÖíİ ÇáİåÑÓ İí %s';
$strAny = 'Ãí';
$strAnyColumn = 'Ãí ÚãæÏ';
$strAnyDatabase = 'Ãí ŞÇÚÏÉ ÈíÇäÇÊ';
$strAnyHost = 'Ãí ãÒæÏ';
$strAnyTable = 'Ãí ÌÏæá';
$strAnyUser = 'Ãí ãÓÊÎÏã';
$strAPrimaryKey = 'áŞÏ ÃõÖíİ ÇáãİÊÇÍ ÇáÃÓÇÓí İí %s';
$strAscending = 'ÊÕÇÚÏíÇğ';
$strAtBeginningOfTable = 'İí ÈÏÇíÉ ÇáÌÏæá';
$strAtEndOfTable = 'İí äåÇíÉ ÇáÌÏæá';
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
$strCardinality = 'Cardinality';
$strCarriage = 'ÅÑÌÇÚ ÇáÍãæáå: \\r';
$strChange = 'ÊÛííÑ';
$strChangePassword = 'ÊÛííÑ ßáãÉ ÇáÓÑ';
$strCheckAll = 'ÅÎÊÑ Çáßá';
$strCheckDbPriv = 'İÍÕ ÅãÊíÇÒ ŞÇÚÏÉ ÇáÈíÇäÇÊ';
$strCheckTable = 'ÇáÊÍŞŞ ãä ÇáÌÏæá';
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
$strCreateNewDatabase = 'Êßæíä ŞÇÚÏÉ ÈíÇäÇÊ ÌÏíÏÉ';
$strCreateNewTable = 'Êßæíä ÌÏæá ÌÏíÏ İí ŞÇÚÏÉ ÇáÈíÇäÇÊ %s';
$strCriteria = 'ÇáãÚÇííÑ';

$strData = 'ÈíÇäÇÊ';
$strDatabase = 'ŞÇÚÏÉ ÇáÈíÇäÇÊ ';
$strDatabaseHasBeenDropped = 'ŞÇÚÏÉ ÈíÇäÇÊ %s ãÍĞæİå.';
$strDatabases = 'ŞÇÚÏÉ ÈíÇäÇÊ';
$strDatabasesStats = 'ÅÍÕÇÆíÇÊ ŞæÇÚÏ ÇáÈíÇäÇÊ';
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
$strDoYouReally = 'åá ÊÑíÏ ÍŞÇğ ÊäİíĞ';
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

$strField = 'ÇáÍŞá';
$strFieldHasBeenDropped = 'ÍŞá ãÍĞæİ %s';
$strFields = ' ÚÏÏ ÇáÍŞæá';
$strFieldsEmpty = ' ÊÚÏÇÏ ÇáÍŞá İÇÑÛ! ';
$strFieldsEnclosedBy = 'ÍŞá ãÖãä ÈÜ';
$strFieldsEscapedBy = 'ÍŞá ãõÊÌÇåá ÈÜ';
$strFieldsTerminatedBy = 'ÍŞá ãİÕæá ÈÜ';
$strFixed = 'ãËÈÊ';
$strFlushTable = 'ÅÚÇÏÉ ÊÍãíá ÇáÌÏæá ("FLUSH")';
$strFormat = 'ÕíÛå';
$strFormEmpty = 'íæÌÏ Şíãå ãİŞæÏå ÈÇáäãæĞÌ !';
$strFullText = 'äÕæÕ ßÇãáå';
$strFunction = 'ÏÇáÉ';

$strGenTime = 'ÃäÔÆ İí';
$strGo = '&nbsp;ÊäİíÜÜĞ&nbsp;';
$strGrants = 'Grants';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'áŞÏ ÚõÏöá.';
$strHasBeenCreated = 'áŞÏ Êßæä.';
$strHome = 'ÇáÕİÍÉ ÇáÑÆíÓíÉ';
$strHomepageOfficial = 'ÇáÕİÍÉ ÇáÑÆíÓíÉ ÇáÑÓãíÉ áÜ phpMyAdmin';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin ÕİÍÉ ÇáÊäÒíá';
$strHost = 'ÇáãÒæÏ';
$strHostEmpty = 'ÅÓã ÇáãÓÊÖíİ İÇÑÛ!';

$strIdxFulltext = 'ÇáäÕ ßÇãáÇğ';
$strIfYouWish = 'ÅĞÇ ßäÊ ÊÑÛÈ İí Ãä ÊÍãá ÈÚÖ ÃÚãÏÉ ÇáÌÏæá İŞØ, ÍÏÏ ÈÇáİÇÕáå ÇáÊí ÊİÕá ŞÇÆãÉ ÇáÍŞá.';
$strIgnore = 'ÊÌÇåá';
$strIndex = 'İåÑÓÊ';
$strIndexes = 'İåÇÑÓ';
$strIndexHasBeenDropped = 'İåÑÓå ãÍĞæİå %s';
$strIndexName = 'ÅÓã ÇáİåÑÓ&nbsp;:';
$strIndexType = 'äæÚ ÇáİåÑÓ&nbsp;:';
$strInsert = 'ÅÏÎÇá';
$strInsertAsNewRow = 'ÅÏÎÇá ßÊÓÌíá ÌÏíÏ';
$strInsertedRows = 'Õİæİ ãÏÎáå:';
$strInsertNewRow = 'ÅÖÇİÉ ÊÓÌíá ÌÏíÏ';
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
$strLocationTextfile = 'ãßÇä ãáİ äÕí';
$strLogin = 'ÏÎæá';
$strLogout = 'ÊÓÌíá ÎÑæÌ';
$strLogPassword = 'ßáãÉ ÇáÓÑ:';
$strLogUsername = 'ÅÓã ÇáãõÓÊÎÏã:';

$strModifications = 'ÊãÊ ÇáÊÚÏíáÇÊ';
$strModify = 'ÊÚÏíá';
$strModifyIndexTopic = 'ÊÚÏíá ÇáİåÑÓå';
$strMoveTable = 'äŞá ÌÏæá Åáì (ŞÇÚÏÉ ÈíÇäÇÊ<b>.</b>ÌÏæá):';
$strMoveTableOK = '%s ÌÏæá Êã äŞáå Åáì %s.';
$strMySQLReloaded = 'Êã ÅÚÇÏÉ ÊÍãíá MySQL ÈäÌÇÍ.';
$strMySQLSaid = 'MySQL ŞÇá: ';
$strMySQLServerProcess = 'MySQL %pma_s1%  Úáì ÇáãÒæÏ %pma_s2% -  ÇáãÓÊÎÏã : %pma_s3%';
$strMySQLShowProcess = 'ÚÑÖ ÇáÚãáíÇÊ';
$strMySQLShowStatus = 'ÚÑÖ ÍÇáÉ ÇáãÒæÏ MySQL';
$strMySQLShowVars ='ÚÑÖ ãÊÛíÑÇÊ ÇáãÒæÏ MySQL';

$strName = 'ÇáÅÓã';
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
$strNoUsersFound = 'ÇáãÓÊÎÏã(Üíä) áã íÊã ÅíÌÇÏåã.';
$strNull = 'ÎÇáí';

$strOftenQuotation = 'ÛÇáÈÇğ ÚáÇãÇÊ ÇáÅŞÊÈÇÓ. ÅÎÊíÇÑí íÚäí ÈÃä ÇáÍŞæá  char æ varchar ÊÑİŞ ÈÜ " ".';
$strOptimizeTable = 'ÖÛØ ÇáÌÏæá';
$strOptionalControls = 'ÅÎÊíÇÑí. ÇáÊÍßã İí ßíİíÉ ßÊÇÈÉ Ãæ ŞÑÇÁÉ ÇáÃÍÑİ Ãæ ÇáÌãá ÇáÎÇÕå.';
$strOptionally = 'ÅÎÊíÇÑí';
$strOr = 'Ãæ';
$strOverhead = 'ÇáİæŞí';

$strPartialText = 'äÕæÕ ÌÒÆíå';
$strPassword = 'ßáãÉ ÇáÓÑ';
$strPasswordEmpty = 'ßáãÉ ÇáÓÑ İÇÑÛÉ !';
$strPasswordNotSame = 'ßáãÊÇ ÇáÓÑ ÛíÑ ãÊÔÇÈåÊÇä !';
$strPHPVersion = ' PHP ÅÕÏÇÑÉ';
$strPmaDocumentation = 'ãÓÊäÏÇÊ æËÇÆŞíå áÜ phpMyAdmin (ÈÇáÅäÌáíÒíÉ)';
$strPmaUriError = 'ÇáãÊÛíÑ <span dir="ltr"><tt>$cfg[\'PmaAbsoluteUri\']</tt></span> íÌÈ ÊÚÏíáå İí ãáİ Çáßæİíß !';
$strPos1 = 'ÈÏÇíÉ';
$strPrevious = 'ÓÇÈŞ';
$strPrimary = 'ÃÓÇÓí';
$strPrimaryKey = 'ãİÊÇÍ ÃÓÇÓí';
$strPrimaryKeyHasBeenDropped = 'áŞÏ Êã ÍĞİ ÇáãİÊÇÍ ÇáÃÓÇÓí';
$strPrimaryKeyName = 'ÅÓã ÇáãİÊÇÍ ÇáÃÓÇÓí íÌÈ Ãä íßæä ÃÓÇÓí... PRIMARY!';
$strPrimaryKeyWarning = '("ÇáÃÓÇÓí" <b>íÌÈ</b> íÌÈ Ãä íßæä ÇáÃÓã <b>æÃíÖÇğ İŞØ</b> ÇáãİÊÇÍ ÇáÃÓÇÓí!)';
$strPrintView = 'ÚÑÖ äÓÎÉ ááØÈÇÚÉ';
$strPrivileges = 'ÇáÅãÊíÇÒÇÊ';
$strProperties = 'ÎÕÇÆÕ';

$strQBE = 'ÅÓÊÚáÇã ÈæÇÓØÉ ãËÇá';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'İí ŞÇÚÏÉ ÇáÈíÇäÇÊ SQL-ÅÓÊÚáÇã <b>%s</b>:';

$strRecords = 'ÇáÊÓÌíáÇÊ';
$strReferentialIntegrity = 'ÊÍÏíÏ referential integrity:';
$strReloadFailed = ' ÅÚÇÏÉ ÊÍãíá ÎÇØÆåMySQL.';
$strReloadMySQL = 'ÅÚÇÏÉ ÊÍãíá MySQL';
$strRememberReload = 'ÊĞßíÑ áÅÚÇÏÉ ÊÍãíá ÇáÎÇÏã.';
$strRenameTable = 'ÊÛííÑ ÅÓã ÌÏæá Åáì';
$strRenameTableOK = 'Êã ÊÛííÑ ÅÓãåã Åáì %s  ÌÏæá%s';
$strRepairTable = 'ÅÕáÇÍ ÇáÌÏæá';
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
$strRowsStatistic = 'ÅÍÕÇÆíÇÊ';
$strRunning = ' Úáì ÇáãÒæÏ %s';
$strRunQuery = 'ÅÑÓÇá ÇáÅÓÊÚáÇã';
$strRunSQLQuery = 'ÊäİíĞ ÅÓÊÚáÇã/ÅÓÊÚáÇãÇÊ SQL Úáì ŞÇÚÏÉ ÈíÇäÇÊ %s';

$strSave = 'ÍİÜÜÙ';
$strSelect = 'ÅÎÊíÇÑ';
$strSelectADb = 'ÅÎÊÑ ŞÇÚÏÉ ÈíÇäÇÊ ãä ÇáŞÇÆãÉ';
$strSelectAll = 'ÊÍÏíÏ Çáßá';
$strSelectFields = 'ÅÎÊíÇÑ ÍŞæá (Úáì ÇáÃŞá æÇÍÏ):';
$strSelectNumRows = 'İí ÇáÅÓÊÚáÇã';
$strSend = 'ÍİÙ ßãáİ';
$strServerChoice = 'ÅÎÊíÇÑ ÇáÎÇÏã';
$strServerVersion = 'ÅÕÏÇÑÉ ÇáãÒæÏ';
$strSetEnumVal = 'ÅĞÇ ßÇä äæÚ ÇáÍŞá åæ "enum" Ãæ "set", ÇáÑÌÇÁ ÅÏÎÇá ÇáŞíã ÈÅÓÊÎÏÇã åĞÇ ÇáÊäÓíŞ: \'a\',\'b\',\'c\'...<br />ÅĞÇ ßäÊ ÊÍÊÇÌ ÈÃä ÊÖÚ ÚáÇãÉ ÇáÔÑØå ÇáãÇÆáå ááíÓÇÑ ("\") Ãæ ÚáÇãÉ ÇáÅŞÊÈÇÓ ÇáİÑÏíå ("\'") İíãÇ Èíä Êáß ÇáŞíã, ÅÌÚáåÇ ßÔÑØå ãÇÆáå ááíÓÇÑ (ãËáÇğ \'\\\\xyz\' Ãæ \'a\\\'b\').';
$strShow = 'ÚÑÖ';
$strShowAll = 'ÔÇåÏ Çáßá';
$strShowCols = 'ÔÇåÏ ÇáÃÚãÏå';
$strShowingRecords = 'ãÔÇåÏÉ ÇáÓÌáÇÊ ';
$strShowPHPInfo = 'ÚÑÖ ÇáãÚáæãÇÊ ÇáãÊÚáŞÉ È  PHP';
$strShowTables = 'ÔÇåÏ ÇáÌÏæá';
$strShowThisQuery = ' ÚÑÖ åĞÇ ÇáÅÓÊÚáÇã åäÇ ãÑÉ ÃÎÑì ';
$strSingly = '(İÑÏí)';
$strSize = 'ÇáÍÌã';
$strSort = 'ÊÕäíİ';
$strSpaceUsage = 'ÇáãÓÇÍÉ ÇáãÓÊÛáÉ';
$strSQLQuery = 'ÅÓÊÚáÇã-SQL';
$strStatement = 'ÃæÇãÑ';
$strStrucCSV = 'ÈíÇäÇÊ CSV';
$strStrucData = 'ÇáÈäíÉ æÇáÈíÇäÇÊ';
$strStrucDrop = ' ÅÖÇİÉ \'ÍĞİ ÌÏæá ÅĞÇ ßÇä ãæÌæÏÇ\' İí ÇáÈÏÇíÉ';
$strStrucExcelCSV = 'ÈíÇäÇÊ CSV áÈÑäÇãÌ  Ms Excel';
$strStrucOnly = 'ÇáÈäíÉ İŞØ';
$strSubmit = 'ÅÑÓÇá';
$strSuccess = 'ÇáÎÇÕ Èß Êã ÊäİíĞå ÈäÌÇÍ SQL-ÅÓÊÚáÇã';
$strSum = 'ÇáãÌãæÚ';

$strTable = 'ÇáÌÏæá ';
$strTableComments = 'ÊÚáíŞÇÊ Úáì ÇáÌÏæá';
$strTableEmpty = 'ÅÓã ÇáÌÏæá İÇÑÛ!';
$strTableHasBeenDropped = 'ÌÏæá %s ÍõĞİÊ';
$strTableHasBeenEmptied = 'ÌÏæá %s ÃõİÑÛÊ ãÍÊæíÇÊåÇ';
$strTableHasBeenFlushed = 'áŞÏ Êã ÅÚÇÏÉ ÊÍãíá ÇáÌÏæá %s  ÈäÌÇÍ';
$strTableMaintenance = 'ÕíÇäÉ ÇáÌÏæá';
$strTables = '%s  ÌÏæá (ÌÏÇæá)';
$strTableStructure = 'ÈäíÉ ÇáÌÏæá';
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
$strUsage = 'ÇáãÓÇÍÉ';
$strUseBackquotes = 'ÍãÇíÉ ÃÓãÇÁ ÇáÌÏÇæá æ ÇáÍŞæá È "`" ';
$strUser = 'ÇáãÓÊÎÏã';
$strUserEmpty = 'ÅÓã ÇáãÓÊÎÏã İÇÑÛ!';
$strUserName = 'ÅÓã ÇáãÓÊÎÏã';
$strUsers = 'ÇáãÓÊÎÏãíä';
$strUseTables = 'ÅÓÊÎÏã ÇáÌÏæá';

$strValue = 'ÇáŞíãå';
$strViewDump = 'ÚÑÖ ÈäíÉ ÇáÌÏæá ';
$strViewDumpDB = 'ÚÑÖ ÈäíÉ ŞÇÚÏÉ ÇáÈíÇäÇÊ';

$strWelcome = 'ÃåáÇğ Èß İí %s';
$strWithChecked = ': Úáì ÇáãÍÏÏ';
$strWrongUser = 'ÎØÃ ÅÓã ÇáãÓÊÎÏã/ßáãÉ ÇáÓÑ. ÇáÏÎæá ããäæÚ.';

$strYes = 'äÚã';

$strZip = '"zipped" "ãÖÛæØ"';
// To translate

$strAllTableSameWidth = 'display all Tables with same width?';  //to translate

$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCantLoadRecodeIconv = 'Can not load iconv or recode extension needed for charset conversion, configure php to allow using these extensions or disable charset conversion in phpMyAdmin.';  //to translate
$strCantUseRecodeIconv = 'Can not use iconv nor libiconv nor recode_string function while extension reports to be loaded. Check your php configuration.';  //to translate
$strChangeDisplay = 'Choose Field to display';  //to translate
$strCharsetOfFile = 'Character set of the file:'; //to translate
$strChoosePage = 'Please choose a Page to edit';  //to translate
$strColComFeat = 'Displaying Column Comments';  //to translate
$strComments = 'Comments';  //to translate
$strConfigFileError = 'phpMyAdmin was unable to read your configuration file!<br />This might happen if php finds a parse error in it or php cannot find the file.<br />Please call the configuration file directly using the link below and read the php error message(s) that you recieve. In most cases a quote or a semicolon is missing somewhere.<br />If you recieve a blank page, everything is fine.'; //to translate
$strConfigureTableCoord = 'Please configure the coordinates for table %s';  //to translate
$strCreatePage = 'Create a new Page';  //to translate
$strCreatePdfFeat = 'Creation of PDFs';  //to translate

$strDisabled = 'Disabled';  //to translate
$strDisplayFeat = 'Display Features';  //to translate
$strDisplayPDF = 'Display PDF schema';  //to translate
$strDumpXRows = 'Dump %s rows starting at row %s.'; //to translate

$strEditPDFPages = 'Edit PDF Pages';  //to translate
$strEnabled = 'Enabled';  //to translate
$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'Explain SQL';  //to translate
$strExport = 'Export';  //to translate
$strExportToXML = 'Export to XML format'; //to translate

$strGenBy = 'Generated by'; //to translate
$strGeneralRelationFeat = 'General relation features';  //to translate

$strHaveToShow = 'You have to choose at least one Column to display';  //to translate

$strLinkNotFound = 'Link not found';  //to translate
$strLinksTo = 'Links to';  //to translate

$strMissingBracket = 'Missing Bracket';  //to translate
$strMySQLCharset = 'MySQL Charset';  //to translate

$strNoDescription = 'no Description';  //to translate
$strNoExplain = 'Skip Explain SQL';  //to translate
$strNoPhp = 'without PHP Code';  //to translate
$strNotOK = 'not OK';  //to translate
$strNotSet = '<b>%s</b> table not found or not set in %s';  //to translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate
$strNumSearchResultsInTable = '%s match(es) inside table <i>%s</i>';//to translate
$strNumSearchResultsTotal = '<b>Total:</b> <i>%s</i> match(es)';//to translate

$strOK = 'OK';  //to translate
$strOperations = 'Operations';  //to translate
$strOptions = 'Options';  //to translate

$strPageNumber = 'Page number:';  //to translate
$strPdfDbSchema = 'Schema of the the "%s" database - Page %s';  //to translate
$strPdfInvalidPageNum = 'Undefined PDF page number!';  //to translate
$strPdfInvalidTblName = 'The "%s" table does not exist!';  //to translate
$strPdfNoTables = 'No tables';  //to translate
$strPhp = 'Create PHP Code';  //to translate

$strRelationNotWorking = 'The additional Features for working with linked Tables have been deactivated. To find out why click %shere%s.';  //to translate
$strRelationView = 'Relation view';  //to translate

$strScaleFactorSmall = 'The scale factor is too small to fit the schema on one page';  //to translate
$strSearch = 'Search';//to translate
$strSearchFormTitle = 'Search in database';//to translate
$strSearchInTables = 'Inside table(s):';//to translate
$strSearchNeedle = 'Word(s) or value(s) to search for (wildcard: "%"):';//to translate
$strSearchOption1 = 'at least one of the words';//to translate
$strSearchOption2 = 'all words';//to translate
$strSearchOption3 = 'the exact phrase';//to translate
$strSearchOption4 = 'as regular expression';//to translate
$strSearchResultsFor = 'Search results for "<i>%s</i>" %s:';//to translate
$strSearchType = 'Find:';//to translate
$strSelectTables = 'Select Tables';  //to translate
$strShowColor = 'Show color';  //to translate
$strShowGrid = 'Show grid';  //to translate
$strShowTableDimension = 'Show dimension of tables';  //to translate
$strSplitWordsWithSpace = 'Words are seperated by a space character (" ").';//to translate
$strSQL = 'SQL'; //to translate
$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQLResult = 'SQL result'; //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate
$strStructPropose = 'Propose table structure';  //to translate
$strStructure = 'Structure';  //to translate

$strValidateSQL = 'Validate SQL';  //to translate

$strInsecureMySQL = 'Your configuration file contains settings (root with no password) that correspond to the default MySQL privileged account. Your MySQL server is running with this default, is open to intrusion, and you really should fix this security hole.';  //to translate
$strWebServerUploadDirectory = 'web-server upload directory';  //to translate
$strWebServerUploadDirectoryError = 'The directory you set for upload work cannot be reached';  //to translate
$strValidatorError = 'The SQL validator could not be initialized. Please check if you have installed the necessary php extensions as described in the %sdocumentation%s.'; //to translate
$strServer = 'Server %s';  //to translate
$strPutColNames = 'Put fields names at first row';  //to translate
$strImportDocSQL = 'Import docSQL Files';  //to translate
$strDataDict = 'Data Dictionary';  //to translate
$strPrint = 'Print';  //to translate
$strPHP40203 = 'You are using PHP 4.2.3, which has a serious bug with multi-byte strings (mbstring). See PHP bug report 19404. This version of PHP is not recommended for use with phpMyAdmin.';  //to translate
$strCompression = 'Compression'; //to translate
$strNumTables = 'Tables'; //to translate
$strTotalUC = 'Total'; //to translate
$strRelationalSchema = 'Relational schema';  //to translate
$strTableOfContents = 'Table of contents';  //to translate
$strCannotLogin = 'Cannot login to MySQL server';  //to translate
$strShowDatadictAs = 'Data Dictionary Format';  //to translate
$strLandscape = 'Landscape';  //to translate
$strPortrait = 'Portrait';  //to translate

$timespanfmt = '%s days, %s hours, %s minutes and %s seconds'; //to translate

$strAbortedClients = 'Aborted'; //to translate
$strConnections = 'Connections'; //to translate
$strFailedAttempts = 'Failed attempts'; //to translate
$strGlobalValue = 'Global value'; //to translate
$strMoreStatusVars = 'More status variables'; //to translate
$strPerHour = 'per hour'; //to translate
$strQueryStatistics = '<b>Query statistics</b>: Since its startup, %s queries have been sent to the server.';
$strQueryType = 'Query type'; //to translate
$strReceived = 'Received'; //to translate
$strSent = 'Sent'; //to translate
$strServerStatus = 'Runtime Information'; //to translate
$strServerStatusUptime = 'This MySQL server has been running for %s. It started up on %s.'; //to translate
$strServerTabStatus = 'Status'; //to translate
$strServerTabVariables = 'Variables'; //to translate
$strServerTabProcesslist = 'Processes'; //to translate
$strServerTrafficNotes = '<b>Server traffic</b>: These tables show the network traffic statistics of this MySQL server since its startup.';
$strServerVars = 'Server variables and settings'; //to translate
$strSessionValue = 'Session value'; //to translate
$strTraffic = 'Traffic'; //to translate
$strVar = 'Variable'; //to translate
?>
