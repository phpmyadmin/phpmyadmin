<?php

/* $Id$ */

/**
 * Latvian language file by Sandis Jçrics <sandisj at parks.lv>
 */

$charset = 'windows-1257';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
$byteUnits = array('baiti', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Sv', 'Pr', 'Ot', 'Tr', 'Ce', 'Pt', 'Se');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'Mai', 'Jûn', 'Jûl', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d%m.%Y %H:%M';


$strAccessDenied = 'Pieeja aizliegta';
$strAction = 'Darbîba';
$strAddDeleteColumn = 'Pievienot/Dzçst laukus (kolonnas)';
$strAddDeleteRow = 'Pievienot/Dzçst ierakstu';
$strAddNewField = 'Pievienot jaunu lauku';
$strAddPrivMessage = 'Jûs pievienojât jaunu privilçìiju.';
$strAddPriv = 'Pievienot jaunu privilçìiju';
$strAddSearchConditions = 'Pievienot meklçðanas nosacîjumus ("where" izteiksmes íermenis):';
$strAddToIndex = 'Pievienot indeksam &nbsp;%s&nbsp;kolonn(u/as)';
$strAddUserMessage = 'Jûs pievienojât jaunu lietotâju.';
$strAddUser = 'Pievienot jaunu lietotâju';
$strAffectedRows = 'Ietekmçto rindu skaits:';
$strAfterInsertBack = 'Atgriezties iepriekðçjâ lapâ atpakaï';
$strAfterInsertNewInsert = 'Ievietot vçl vienu rindu';
$strAfter = 'Pçc %s';
$strAll = 'Visi';
$strAlterOrderBy = 'Mainît datu kârtoðanas laukus';
$strAnalyzeTable = 'Analizçt tabulu';
$strAnd = 'Un';
$strAnIndex = 'Indekss tieka pievienots uz %s';
$strAnyColumn = 'Jebkura kolonna';
$strAnyDatabase = 'Jebkura datubâze';
$strAnyHost = 'Jebkurð hosts';
$strAny = 'Jebkurð';
$strAnyTable = 'Jebkura tabula';
$strAnyUser = 'Jebkurð lietotâjs';
$strAPrimaryKey = 'Primârâ atslçga pievienota uz lauka %s';
$strAscending = 'Augoðâ secîbâ';
$strAtBeginningOfTable = 'Tabulas sâkumâ';
$strAtEndOfTable = 'Tabulas beigâs';
$strAttr = 'Atribûti';

$strBack = 'Atpakaï';
$strBinary = 'Binârais';
$strBinaryDoNotEdit = 'Binârais - netiek labots';
$strBookmarkDeleted = 'Ieraksts tika dzçsts.';
$strBookmarkLabel = 'Nosaukums';
$strBookmarkQuery = 'Saglabâtie SQL-vaicâjumi';
$strBookmarkThis = 'Saglabât ðo SQL-vaicâjumu';
$strBookmarkView = 'Tikai apskatît';
$strBrowse = 'Apskatît';
$strBzip = 'saarhivçts ar bzip';

$strCantLoadMySQL = 'nevar ielâdçt MySQL paplaðinâjumu,<br />lûdzu pârbaudiet PHP konfigurâciju.';
$strCantRenameIdxToPrimary = 'Nevar pârsaukt indeksu par PRIMARY!';
$strCardinality = 'Kardinalitâte';
$strCarriage = 'Rindas nobeiguma simbols: \\r';
$strChange = 'Labot';
$strChangePassword = 'Mainît paroli';
$strCheckAll = 'Iezîmçt visu';
$strCheckDbPriv = 'Pârbaudît privilçìijas uz datubâzi';
$strCheckTable = 'Pârbaudît tabulu';
$strColumn = 'Kolonna';
$strColumnNames = 'Kolonnu nosaukumi';
$strCompleteInserts = 'Pilnas INSERT izteiksmes';
$strConfirm = 'Vai Jûs tieðâm gribat to darît?';
$strCookiesRequired = 'Ðî lapa nestrâdâs korekti, ja \'Cookies\' ir atslçgtas jûsu pârlûkprogrammas konfigurâcijâ.';
$strCopyTable = 'Kopçt tabulu uz (datubâze<b>.</b>tabula):';
$strCopyTableOK = 'Tabula %s tika pârkopçta uz %s.';
$strCreateIndex = 'Izveidot indeksu uz&nbsp;%s&nbsp;laukiem';
$strCreateIndexTopic = 'Izveidot jaunu indeksu';
$strCreate = 'Izveidot';
$strCreateNewDatabase = 'Izveidot jaunu datubâzi';
$strCreateNewTable = 'Izveidot jaunu tabulu datubâzç %s';
$strCriteria = 'Kritçrijs';

$strDatabase = 'Datubâze ';
$strDatabaseHasBeenDropped = 'Datubâze %s tika izdzçsta.';
$strDatabases = 'datubâzes';
$strDatabasesStats = 'Datubâzu statistika';
$strDatabaseWildcard = 'Datubâze (var lietot aizstâjçjzîmes):';
$strData = 'Dati';
$strDataOnly = 'Tikai dati';
$strDefault = 'Noklusçts';
$strDeleted = 'Ieraksts tika dzçsts';
$strDeletedRows = 'Ieraksti dzçsti:';
$strDelete = 'Dzçst';
$strDeleteFailed = 'Dzçðana nenotika!';
$strDeleteUserMessage = 'Jûs nodzçsât lietotâju %s.';
$strDescending = 'Dilstoðâ secîbâ';
$strDisplay = 'Attçlot';
$strDisplayOrder = 'Attçloðanas secîba:';
$strDoAQuery = 'Izpildît "vaicâjumu pçc parauga" (aizstâjçjzîme: "%")';
$strDocu = 'Dokumentâcija';
$strDoYouReally = 'Vai Jûs tieðâm gribat ';
$strDropDB = 'Likvidçt datubâzi %s';
$strDrop = 'Likvidçt';
$strDropTable = 'Likvidçt tabulu';
$strDumpingData = 'Dati tabulai';
$strDynamic = 'dinamisks';

$strEdit = 'Labot';
$strEditPrivileges = 'Mainît privilçìijas';
$strEffective = 'Efektîvs';
$strEmpty = 'Iztukðot';
$strEmptyResultSet = 'MySQL atgrieza tukðo rezultâtu (0 rindas).';
$strEnd = 'Beigas';
$strEnglishPrivileges = ' Piezîme: MySQL privilçìiju apzîmçjumi tiek rakstîti angïu valodâ ';
$strError = 'Kïûda';
$strExtendedInserts = 'Paplaðinâtâs INSERT izteiksmes';
$strExtra = 'Ekstras';

$strFieldHasBeenDropped = 'Lauks %s tika izdzçsts';
$strField = 'Lauks';
$strFieldsEmpty = ' Lauku skaits ir nulle! ';
$strFieldsEnclosedBy = 'Lauki iekïauti iekð';
$strFieldsEscapedBy = 'Glâbjoðâ (escape) rakstzîme ir';
$strFields = 'Lauku skaits';
$strFieldsTerminatedBy = 'Lauki atdalîti ar';
$strFixed = 'fiksçts';
$strFlushTable = 'Atsvaidzinât tabulu ("FLUSH")';
$strFormat = 'Formats';
$strFormEmpty = 'Formâ trûkst vçrtîbu!';
$strFullText = 'Pilni teksti';
$strFunction = 'Funkcija';

$strGenTime = 'Izveidoðanas laiks';
$strGo = 'Aiziet!';
$strGrants = 'Tiesîbas';
$strGzip = 'saarhivçts ar gzip';

$strHasBeenAltered = 'tika modificçta.';
$strHasBeenCreated = 'tika izveidota.';
$strHomepageOfficial = 'Oficiâlâ phpMyAdmin mâjaslapa';
$strHomepageSourceforge = 'phpMyAdmin lejuplâdes lapa iekð Sourceforge';
$strHome = 'Sâkumlapa';
$strHostEmpty = 'Hosts nav norâdîts!';
$strHost = 'Hosts';

$strIdxFulltext = 'Pilni teksti';
$strIfYouWish = 'Ja Jûs vçlaties ielâdçt tikai daþas tabulas kolonnas, norâdiet to nosaukumus, atdalot tos ar komatu.';
$strIgnore = 'Ignorçt';
$strIndexes = 'Indeksi';
$strIndexHasBeenDropped = 'Indekss %s tika izdzçsts';
$strIndex = 'Indekss';
$strIndexName = 'Indeksa nosaukums&nbsp;:';
$strIndexType = 'Indeksa tips&nbsp;:';
$strInsertAsNewRow = 'Ievietot kâ jaunu rindu';
$strInsertedRows = 'Rindas pievienotas:';
$strInsertNewRow = 'Pievienot jaunu rindu';
$strInsert = 'Pievienot';
$strInsertTextfiles = 'Ievietot tabulâ datus no teksta faila';
$strInstructions = 'Instrukcijas';
$strInUse = 'lietoðanâ';
$strInvalidName = '"%s" ir rezervçts vârds, Jûs nevarat lietot to kâ datubâzes/tabulas/lauka nosaukumu.';

$strKeepPass = 'Nemainît paroli';
$strKeyname = 'Atslçgas nosaukums';
$strKill = 'Nogalinât';

$strLength = 'Garums';
$strLengthSet = 'Garums/Vçrtîbas*';
$strLimitNumRows = 'Rindu skaits vienâ lapâ';
$strLineFeed = 'Rindas beigu simbols: \\n';
$strLines = 'Rindas';
$strLinesTerminatedBy = 'Rindas atdalîtas ar';
$strLocationTextfile = 'Teksta faila atraðanâs vieta';
$strLogin = 'Ieiet';
$strLogout = 'Iziet';
$strLogPassword = 'Parole:';
$strLogUsername = 'Lietotâjvârds:';

$strModifications = 'Grozîjumi tika saglabâti';
$strModifyIndexTopic = 'Modificçt indeksu';
$strModify = 'Modificçt';
$strMoveTableOK = 'Tabula %s tika pârvietota uz %s.';
$strMoveTable = 'Pârvietot tabulu uz (datubâze<b>.</b>tabula):';
$strMySQLReloaded = 'MySQL serveris tika pârlâdçts.';
$strMySQLSaid = 'MySQL teica: ';
$strMySQLServerProcess = 'MySQL %pma_s1% strâdâ uz %pma_s2% kâ %pma_s3%';
$strMySQLShowProcess = 'Parâdît procesus';
$strMySQLShowStatus = 'Parâdît MySQL izpildes laika informâciju';
$strMySQLShowVars = 'Parâdît MySQL sistçmas mainîgos';

$strName = 'Nosaukums';
$strNext = 'Nâkamais';
$strNoDatabases = 'Nav datubâzu';
$strNoDropDatabases = '"DROP DATABASE" komanda ir aizliegta.';
$strNoFrames = 'phpMyAdmin ir vairâk draudzîgs <b>freimu atbalstoðu</b> pârlûkprogrammu.';
$strNoIndex = 'Nav definçti indeksi!';
$strNoIndexPartsDefined = 'Nav definçto indeksa daïu!';
$strNoModification = 'Netika labots';
$strNo = 'Nç';
$strNone = 'Nekas';
$strNoPassword = 'Nav paroles';
$strNoPrivileges = 'Nav privilçìiju';
$strNoQuery = 'Nav SQL vaicâjuma!';
$strNoRights = 'Jums nav pietiekoði tiesîbu, lai atrastos ðeit tagad!';
$strNoTablesFound = 'Tabulas nav atrastas ðajâ datubâzç.';
$strNotNumber = 'Tas nav numurs!';
$strNotValidNumber = ' nav derîgs lauku skaits!';
$strNoUsersFound = 'Lietotâji netika atrasti.';
$strNull = 'Nulle';

$strOftenQuotation = 'Parasti pçdiòas. NEOBLIGÂTS nozîmç, ka tikai \'char\' un \'varchar\' tipa lauki tiek norobeþoti ar ðo simbolu.';
$strOptimizeTable = 'Optimizçt tabulu';
$strOptionalControls = 'Neobligâts. Nosaka, kâ rakstît vai lasît speciâlâs rakstzîmes.';
$strOptionally = 'NEOBLIGÂTS';
$strOr = 'Vai';
$strOverhead = 'Pârtçriòð';

$strPartialText = 'Daïçji teksti';
$strPasswordEmpty = 'Parole nav norâdîta!';
$strPasswordNotSame = 'Paroles nesakrît!';
$strPassword = 'Parole';
$strPHPVersion = 'PHP Versija';
$strPmaDocumentation = 'phpMyAdmin dokumentâcija';
$strPmaUriError = '<tt>$cfg[\'PmaAbsoluteUri\']</tt> direktîvai ir JÂBÛT nodefinçtai Jûsu konfigurâcijas failâ!';
$strPos1 = 'Sâkums';
$strPrevious = 'Iepriekðçjie';
$strPrimaryKeyHasBeenDropped = 'Primârâ atslçga tika izdzçsta';
$strPrimaryKeyName = 'Primârâs atslçgas nosaukumam jâbût... PRIMARY!';
$strPrimaryKey = 'Primârâ atslçga';
$strPrimaryKeyWarning = '("PRIMARY" <b>jâbût</b> tikai un <b>vienîgi</b> primârâs atslçgas indeksa nosaukumam!)';
$strPrimary = 'Primârâ';
$strPrintView = 'Izdrukas versija';
$strPrivileges = 'Privilçìijas';
$strProperties = 'Îpaðîbas';

$strQBEDel = 'Dzçst';
$strQBEIns = 'Ielikt';
$strQBE = 'Vaicâjums pçc parauga';
$strQueryOnDb = 'SQL-vaicâjums uz datubâzes <b>%s</b>:';

$strRecords = 'Ieraksti';
$strReferentialIntegrity = 'Pârbaudît referenciâlo integritâti:';
$strReloadFailed = 'Nesanâca pârlâdçt MySQL serveri.';
$strReloadMySQL = 'Pârlâdçt MySQL serveri';
$strRememberReload = 'Neaizmirstiet pârlâdçt serveri.';
$strRenameTableOK = 'Tabula %s tika pârsaukta par %s';
$strRenameTable = 'Pârsaukt tabulu uz';
$strRepairTable = 'Restaurçt tabulu';
$strReplace = 'Aizvietot';
$strReplaceTable = 'Aizvietot tabulas datus ar datiem no faila';
$strReset = 'Atcelt';
$strReType = 'Atkârtojiet';
$strRevoke = 'Atsaukt';
$strRevokeGrant = 'Atòemt \'Grant\' tiesîbas';
$strRevokeGrantMessage = 'Jûs atòçmât \'Grant\' tiesîbas lietotâjam %s';
$strRevokeMessage = 'Jûs atòçmât privilçgijas lietotâjam %s';
$strRevokePriv = 'Atòemt privilçìijas';
$strRowLength = 'Rindas garums';
$strRowsFrom = 'rindas sâkot no';
$strRowSize = ' Rindas izmçrs ';
$strRowsModeHorizontal = 'horizontâlâ';
$strRowsModeOptions = '%s skatâ un atkârtot virsrakstus ik pçc %s rindâm';
$strRowsModeVertical = 'vertikâlâ';
$strRows = 'Rindas';
$strRowsStatistic = 'Rindas statistika';
$strRunning = 'atrodas uz %s';
$strRunQuery = 'Izpildît vaicâjumu';
$strRunSQLQuery = 'Izpildît SQL-vaicâjumu(s) uz datubâzes %s';

$strSave = 'Saglabât';
$strSelectADb = 'Lûdzu izvçlieties datubâzi';
$strSelectAll = 'Iezîmçt visu';
$strSelect = 'Atlasît';
$strSelectFields = 'Izvçlieties laukus (kaut vienu):';
$strSelectNumRows = 'vaicâjumâ';
$strSend = 'Saglabât kâ failu';
$strServerChoice = 'Servera izvçle';
$strServerVersion = 'Servera versija';
$strSetEnumVal = 'Ja lauka tips ir "enum" vai "set", lûdzu ievadiet vçrtîbas atbilstoði ðim formatam: \'a\',\'b\',\'c\'...<br />Ja Jums vajag ielikt atpakaïçjo slîpsvîtru (\) vai vienkârðo pçdiòu (\') kâdâ no ðîm vçrtîbâm, lieciet tâs priekðâ atpakaïçjo slîpsvîtru (piemçram, \'\\\\xyz\' vai \'a\\\'b\').';
$strShowAll = 'Râdît visu';
$strShowCols = 'Râdît kolonnas';
$strShowingRecords = 'Parâdu rindas';
$strShowPHPInfo = 'Parâdît PHP informâciju';
$strShow = 'Râdît';
$strShowTables = 'Râdît tabulas';
$strShowThisQuery = ' Râdît ðo vaicâjumu ðeit atkal ';
$strSingly = '(vienkârði)';
$strSize = 'Izmçrs';
$strSort = 'Kârtoðana';
$strSpaceUsage = 'Diska vietas lietoðana';
$strSQLQuery = 'SQL-vaicâjums';
$strStatement = 'Parametrs';
$strStrucCSV = 'CSV dati';
$strStrucData = 'Struktûra un dati';
$strStrucDrop = 'Pievienot tabulu dzçðanas komandas';
$strStrucExcelCSV = 'CSV dati MS Excel formâtâ';
$strStrucOnly = 'Tikai struktûra';
$strSubmit = 'Nosûtît';
$strSuccess = 'Jûsu SQL-vaicâjums tika veiksmîgi izpildîts';
$strSum = 'Kopumâ';

$strTableComments = 'Komentârs tabulai';
$strTableEmpty = 'Tabulas nosaukums nav norâdîts!';
$strTableHasBeenDropped = 'Tabula %s tika izdzçsta';
$strTableHasBeenEmptied = 'Tabula %s tika iztukðota';
$strTableHasBeenFlushed = 'Tabula %s tika atsvaidzinâta';
$strTableMaintenance = 'Tabulas apkalpoðana';
$strTables = '%s tabula(s)';
$strTableStructure = 'Tabulas struktûra tabulai';
$strTable = 'Tabula ';
$strTableType = 'Tabulas tips';
$strTextAreaLength = ' Sava garuma dçï,<br /> ðis lauks var bût nerediìçjams ';
$strTheContent = 'Jûsu faila saturs tika ievietots.';
$strTheContents = 'Faila saturs aizvieto izvçlçtâs tabulas saturu rindiòâm ar identisko primârâs vai unikâlâs atslçgas vçrtîbu.';
$strTheTerminator = 'Lauku atdalîtâjs.';
$strTotal = 'kopâ';
$strType = 'Tips';

$strUncheckAll = 'Neiezîmçt neko';
$strUnique = 'Unikâlais';
$strUnselectAll = 'Neiezîmçt neko';
$strUpdatePrivMessage = 'Jûs modificçjât privilçìijas objektam %s.';
$strUpdateProfileMessage = 'Profils tika modificçts.';
$strUpdateProfile = 'Modificçt profilu:';
$strUpdateQuery = 'Modificçðanas vaicâjums';
$strUsage = 'Aizòem';
$strUseBackquotes = 'Lietot apostrofa simbolu [`] tabulu un lauku nosaukumiem';
$strUserEmpty = 'Lietotâja vârds nav norâdîts!';
$strUser = 'Lietotâjs';
$strUserName = 'Lietotâjvârds';
$strUsers = 'Lietotâji';
$strUseTables = 'Lietot tabulas';

$strValue = 'Vçrtîba';
$strViewDump = 'Apskatît tabulas dampu (shçmu)';
$strViewDumpDB = 'Apskatît datubâzes dampu (shçmu)';

$strWelcome = 'Laipni lûgti %s';
$strWithChecked = 'Ar iezîmçto:';
$strWrongUser = 'Kïûdains lietotâjvârds/parole. Pieeja aizliegta.';

$strYes = 'Jâ';

$strZip = 'arhivçts ar zip';
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
$strSearchFormTitle = 'Search in database';//to translate
$strSearchInTables = 'Inside table(s):';//to translate
$strSearchNeedle = 'Word(s) or value(s) to search for (wildcard: "%"):';//to translate
$strSearchOption1 = 'at least one of the words';//to translate
$strSearchOption2 = 'all words';//to translate
$strSearchOption3 = 'the exact phrase';//to translate
$strSearchOption4 = 'as regular expression';//to translate
$strSearchResultsFor = 'Search results for "<i>%s</i>" %s:';//to translate
$strSearch = 'Search';//to translate
$strSearchType = 'Find:';//to translate
$strSelectTables = 'Select Tables';  //to translate
$strShowColor = 'Show color';  //to translate
$strShowGrid = 'Show grid';  //to translate
$strShowTableDimension = 'Show dimension of tables';  //to translate
$strSplitWordsWithSpace = 'Words are seperated by a space character (" ").';//to translate
$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQLResult = 'SQL result'; //to translate
$strSQL = 'SQL'; //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate
$strStructPropose = 'Propose table structure';  //to translate
$strStructure = 'Structure';  //to translate

$strValidateSQL = 'Validate SQL';  //to translate

?>
