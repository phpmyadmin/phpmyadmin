<?php

/* $Id$ */

/*
     translated by Andreas Pauley <pauley@buitegroep.org.za>

Dit lyk nogal snaaks in Afrikaans ;-).
Laat weet my asb. as jy aan beter taalgebruik kan dink.
*/

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('So', 'Ma', 'Di', 'Wo', 'Do', 'Fr', 'Sa');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Toegang Geweier';
$strAction = 'Aksie';
$strAddDeleteColumn = 'Voeg By/Verwyder Veld Kolomme';
$strAddDeleteRow = 'Voeg By/Verwyder Kriteria Ry';
$strAddNewField = 'Voeg \'n nuwe veld by';
$strAddPrivMessage = 'Jy het nuwe regte bygevoeg';
$strAddPriv = 'Voeg nuwe regte by';
$strAddSearchConditions = 'Voeg soek kriteria by (laaste deel van die "where" in SQL SELECT):';
$strAddToIndex = 'Voeg by indeks &nbsp;%s&nbsp;kolom(me)';
$strAddUserMessage = 'Jy het \'n nuwe gebruiker bygevoeg.';
$strAddUser = 'Voeg \'n nuwe gebruiker by';
$strAffectedRows = 'Geaffekteerde rye:';
$strAfterInsertBack = 'Terug na vorige bladsy';
$strAfterInsertNewInsert = 'Voeg \'n nuwe ry by';
$strAfter = 'Na %s';
$strAll = 'Alle';
$strAllTableSameWidth = 'vertoon alle tabelle met dieselfde wydte?';
$strAlterOrderBy = 'Verander tabel sorteer volgens';
$strAnalyzeTable = 'Analiseer tabel';
$strAnd = 'En';
$strAnIndex = '\'n Indeks is bygevoeg op %s';
$strAnyColumn = 'Enige Kolom';
$strAnyDatabase = 'Enige databasis';
$strAny = 'Enige';
$strAnyHost = 'Enige gasheer (host)';
$strAnyTable = 'Enige tabel';
$strAnyUser = 'Enige gebruiker';
$strAPrimaryKey = '\'n primere sleutel is bygevoeg op %s';
$strAscending = 'Dalend';
$strAtBeginningOfTable = 'By Begin van Tabel';
$strAtEndOfTable = 'By Einde van Tabel';
$strAttr = 'Kenmerke';

$strBack = 'Terug';
$strBinary = 'Biner';
$strBinaryDoNotEdit = 'Biner - moenie verander nie';
$strBookmarkDeleted = 'Die boekmerk is verwyder.';
$strBookmarkLabel = 'Etiket';
$strBookmarkQuery = 'Geboekmerkde SQL-stelling';
$strBookmarkThis = 'Boekmerk hierdie SQL-stelling';
$strBookmarkView = 'Kyk slegs';
$strBrowse = 'Beloer Data';
$strBzip = '"ge-bzip"';

$strCantLoadMySQL = 'kan ongelukkig nie die MySQL module laai nie, <br />kyk asb. na die PHP opstelling.';
$strCantLoadRecodeIconv = 'Kan nie iconv laai nie, of "recode" ekstensie word benodig vir die karakterstel omskakeling, stel PHP op om hierdie ekstensies toe te laat of verwyder karakterstel omskakeling in phpMyAdmin.';
$strCantRenameIdxToPrimary = 'Kannie die indeks hernoem na PRIMARY!';
$strCantUseRecodeIconv = 'Kan nie iconv, libiconv of recode_string funksie gebruik terwyl die extensie homself as gelaai rapporteer nie. Kyk na jou PHP opstelling.';
$strCardinality = 'Cardinality';
$strCarriage = 'Carriage return: \\r';
$strChangeDisplay = 'Kies \'n Veld om te vertoon';
$strChangePassword = 'Verander wagwoord';
$strChange = 'Verander';
$strCheckAll = 'Kies Alles';
$strCheckDbPriv = 'Kontroleer Databasis Regte';
$strCheckTable = 'Kontroleer tabel';
$strChoosePage = 'Kies asb. \'n bladsy om te verander';
$strColComFeat = 'Kolom Kommentaar word vertoon';
$strColumn = 'Kolom';
$strColumnNames = 'Kolom name';
$strComments = 'Kommentaar';
$strCompleteInserts = 'Voltooi invoegings';
$strConfigFileError = 'phpMyAdmin was nie in staat om jou konfigurasie leer te lees nie!<br />Dit kan moontlik gebeur wanneer PHP \'n fout in die leer vind of die leer sommer glad nie vind nie.<br />Volg asb. die skakel hieronder om die leer direk te roep, en lees dan enige foutboodskappe. In die meeste gevalle is daar net \'n quote of \'n kommapunt weg erens.<br />Indien jy \'n bladsy kry wat leeg is, is alles klopdisselboom.';
$strConfigureTableCoord = 'Stel asb. die koordinate op van tabel %s';
$strConfirm = 'Wil jy dit regtig doen?';
$strCookiesRequired = 'HTTP Koekies moet van nou af geaktifeer wees.';
$strCopyTable = 'Kopieer tabel na (databasis<b>.</b>tabel):';
$strCopyTableOK = 'Tabel %s is gekopieer na %s.';
$strCreateIndex = 'Skep \'n indeks op&nbsp;%s&nbsp;kolomme';
$strCreateIndexTopic = 'Skep \'n nuwe indeks';
$strCreateNewDatabase = 'Skep \'n nuwe databasis';
$strCreateNewTable = 'Skep \'n nuwe tabel op databasis %s';
$strCreatePage = 'Skep \'n nuwe bladsy';
$strCreatePdfFeat = 'Skepping van PDF\'s';
$strCreate = 'Skep';
$strCriteria = 'Kriteria';

$strDatabase = 'Databasis ';
$strDatabaseHasBeenDropped = 'Databasis %s is verwyder.';
$strDatabases = 'databasisse';
$strDatabasesStats = 'Databasis statistieke';
$strDatabaseWildcard = 'Databasis (wildcards toegelaat):';
$strData = 'Data';
$strDataOnly = 'Slegs Data';
$strDefault = 'Verstekwaarde (default)';
$strDeleted = 'Die ry is verwyder';
$strDeletedRows = 'Verwyderde rye:';
$strDeleteFailed = 'Verwyder aksie het misluk!';
$strDeleteUserMessage = 'Jy het die gebruiker %s verwyder.';
$strDelete = 'Verwyder';
$strDescending = 'Dalend';
$strDisabled = 'Onbeskikbaar';
$strDisplayFeat = 'Vertoon Funksies';
$strDisplayOrder = 'Vertoon volgorde:';
$strDisplayPDF = 'Vertoon PDF skema';
$strDisplay = 'Vertoon';
$strDoAQuery = 'Doen \'n "Navraag dmv Voorbeeld" (wildcard: "%")';
$strDocu = 'Dokumentasie';
$strDoYouReally = 'Wil jy regtig ';
$strDropDB = 'Verwyder databasis %s';
$strDropTable = 'Verwyder tabel';
$strDrop = 'Verwyder';
$strDumpingData = 'Stort data vir tabel';
$strDumpXRows = 'Stort %s rye beginnende by rekord # %s.';
$strDynamic = 'dinamies';

$strEditPDFPages = 'Verander PDF Bladsye';
$strEditPrivileges = 'Verander Regte';
$strEdit = 'Verander';
$strEffective = 'Effektief';
$strEmpty = 'Maak Leeg';
$strEmptyResultSet = 'MySQL het niks teruggegee nie (dus nul rye).';
$strEnabled = 'Beskikbaar';
$strEnd = 'Einde';
$strEnglishPrivileges = ' Nota: MySQL regte name word in Engels vertoon ';
$strError = 'Fout';
$strExplain = 'Verduidelik SQL';
$strExport = 'Export';
$strExportToXML = 'Export na XML formaat';
$strExtendedInserts = 'Uitgebreide toevoegings';
$strExtra = 'Ekstra';

$strFieldHasBeenDropped = 'Veld %s is verwyder';
$strFieldsEmpty = ' Die veld telling is leeg! ';
$strFieldsEnclosedBy = 'Velde omring met';
$strFieldsEscapedBy = 'Velde ontsnap (escaped) deur';
$strFieldsTerminatedBy = 'Velde beeindig deur';
$strFields = 'Velde';
$strField = 'Veld';
$strFixed = 'vaste (fixed)';
$strFlushTable = 'Spoel die tabel ("FLUSH")';
$strFormat = 'Formaat';
$strFormEmpty = 'Daar ontbreek \'n waarde in die vorm !';
$strFullText = 'Volle Tekste';
$strFunction = 'Funksie';

$strGenBy = 'Voortgebring deur';
$strGeneralRelationFeat = 'Algemene verwantskap funksies';
$strGenTime = 'Generasie Tyd';
$strGo = 'Gaan';
$strGrants = 'Vergunnings';
$strGzip = '"ge-gzip"';

$strHasBeenAltered = 'is verander.';
$strHasBeenCreated = 'is geskep.';
$strHaveToShow = 'Jy moet ten minste een Kolom kies om te vertoon';
$strHomepageOfficial = 'Amptelike phpMyAdmin Tuisblad';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Aflaai bladsy';
$strHome = 'Tuis';
$strHostEmpty = 'Die gasheer naam is leeg!';
$strHost = 'Gasheer (host)';

$strIdxFulltext = 'Volteks';
$strIfYouWish = 'Indien jy slegs sommige van \'n tabel se kolomme wil laai, spesifiseer \'n komma-geskeide veldlys.';
$strIgnore = 'Ignoreer';
$strIndexes = 'Indekse';
$strIndexHasBeenDropped = 'Indeks %s is verwyder';
$strIndex = 'Indeks';
$strIndexName = 'Indeks naam&nbsp;:';
$strIndexType = 'Indeks tipe&nbsp;:';
$strInsertAsNewRow = 'Voeg by as \'n nuwe ry';
$strInsertedRows = 'Toegevoegde rye:';
$strInsertNewRow = 'Voeg nuwe ry by';
$strInsertTextfiles = 'Voeg data vanaf \'n teks leer in die tabel in';
$strInsert = 'Voeg by';
$strInstructions = 'Instruksies';
$strInUse = 'in gebruik';
$strInvalidName = '"%s" is \'n gereserveerde woord, jy kan dit nie as \'n databasis/tabel/veld naam gebruik nie.';

$strKeepPass = 'Moenie die wagwoord verander nie';
$strKeyname = 'Sleutelnaam';
$strKill = 'Vermoor';

$strLength = 'Lengte';
$strLengthSet = 'Lengte/Waardes*';
$strLimitNumRows = 'Hoeveelheid rye per bladsy';
$strLineFeed = 'Linefeed: \\n';
$strLines = 'Lyne';
$strLinesTerminatedBy = 'Lyne beeindig deur';
$strLinkNotFound = 'Skakel nie gevind nie';
$strLinksTo = 'Skakels na';
$strLocationTextfile = 'Soek die teksleer';
$strLogin = 'Teken aan';
$strLogout = 'Teken uit';
$strLogPassword = 'Wagwoord:';
$strLogUsername = 'Gebruiker Naam:';

$strMissingBracket = 'Hakie Ontbreek';
$strModifications = 'Veranderinge is gestoor';
$strModifyIndexTopic = 'Verander \'n indeks';
$strModify = 'Verander';
$strMoveTableOK = 'Tabel %s is geskuif na %s.';
$strMoveTable = 'Skuif tabel na (databasis<b>.</b>tabel):';
$strMySQLCharset = 'MySQL Karakterstel';
$strMySQLReloaded = 'MySQL is herlaai.';
$strMySQLSaid = 'MySQL het gepraat: ';
$strMySQLServerProcess = 'MySQL %pma_s1% hardloop op %pma_s2% as %pma_s3%';
$strMySQLShowProcess = 'Wys prosesse';
$strMySQLShowStatus = 'Wys MySQL in-proses informasie';
$strMySQLShowVars = 'Wys MySQL stelsel veranderlikes';

$strName = 'Naam';
$strNext = 'Volgende';
$strNoDatabases = 'Geen databasisse';
$strNoDescription = 'geen Beskrywing';
$strNoDropDatabases = '"DROP DATABASE" stellings word nie toegelaat nie.';
$strNoExplain = 'Ignoreer SQL Verduideliking';
$strNoFrames = 'phpMyAdmin verkies \'n <b>frames-kapabele</b> blaaier.';
$strNoIndex = 'Geen indeks gedefinieer!';
$strNoIndexPartsDefined = 'Geen indeks dele gedefinieer!';
$strNoModification = 'Geen verandering';
$strNo = 'Nee';
$strNone = 'Geen';
$strNoPassword = 'Geen Wagwoord';
$strNoPhp = 'Sonder PHP Kode';
$strNoPrivileges = 'Geen Regte';
$strNoQuery = 'Geen SQL stelling!';
$strNoRights = 'Jy het nie genoeg regte om nou hier te wees nie!';
$strNoTablesFound = 'Geen tabelle in databasis gevind nie.';
$strNotNumber = 'Hierdie is nie \'n nommer nie';
$strNotOK = 'nie OK';
$strNotSet = '<b>%s</b> tabel nie gevind nie of nie gesetel in %s';
$strNotValidNumber = ' is nie \'n geldige ry-nommer nie!';
$strNoUsersFound = 'Geen gebruiker(s) gevind nie.';
$strNoValidateSQL = 'Ignoreer SQL Validasie';
$strNull = 'Null';
$strNumSearchResultsInTable = '%s resultate binne tabel <i>%s</i>';
$strNumSearchResultsTotal = '<b>Totaal:</b> <i>%s</i> ooreenkomste';

$strOftenQuotation = 'Dikwels kwotasie-karakters. OPSIONEEL beteken dat slegs char en varchar velde ingeslote is binne die "enclosed by"-character.';
$strOK = 'OK';
$strOperations = 'Operasies';
$strOptimizeTable = 'Optimaliseer tabel';
$strOptionalControls = 'Opsioneel. Kontroleer hoe om spesiale karakters te lees en skryf.';
$strOptionally = 'OPSIONEEL';
$strOptions = 'Opsies';
$strOr = 'Of';
$strOverhead = 'Overhead';

$strPageNumber = 'Bladsy nommer:';
$strPartialText = 'Gedeeltelike Tekste';
$strPasswordEmpty = 'Die wagwoord is leeg!';
$strPasswordNotSame = 'Die wagwoorde is verskillend!';
$strPassword = 'Wagwoord';
$strPdfDbSchema = 'Skema van die "%s" databasis - Bladsy %s';
$strPdfInvalidPageNum = 'Ongedefinieerde PDF bladsy nommer!';
$strPdfInvalidTblName = 'Die "%s" databasis bestaan nie!';
$strPdfNoTables = 'Geen tabelle';
$strPhp = 'Skep PHP Kode';
$strPHPVersion = 'PHP Version';
$strPmaDocumentation = 'phpMyAdmin dokumentasie';
$strPmaUriError = 'Die <tt>$cfg[\'PmaAbsoluteUri\']</tt> veranderlike MOET gestel wees in jou konfigurasie leer!';
$strPos1 = 'Begin';
$strPrevious = 'Vorige';
$strPrimaryKeyHasBeenDropped = 'Die primere sleutel is verwyder';
$strPrimaryKeyName = 'Die naam van die primere sleutel moet PRIMARY wees!';
$strPrimaryKey = 'Primere sleutel';
$strPrimaryKeyWarning = '("PRIMARY" <b>moet</b> die naam wees van die primere sleutel, en <b>slegs</b> van die primere sleutel!)';
$strPrimary = 'Primere';
$strPrintView = 'Drukker mooi (print view)';
$strPrivileges = 'Regte';
$strProperties = 'Eienskappe';

$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQBE = 'Navraag dmv Voorbeeld';
$strQueryOnDb = 'SQL-navraag op databasis <b>%s</b>:';

$strRecords = 'Rekords';
$strReferentialIntegrity = 'Toets referential integrity:';
$strRelationNotWorking = 'Die addisionele funksies om met geskakelde tabelle te werk is ge deaktiveer. Om uit te vind hoekom kliek %shier%s.';
$strRelationView = 'Relasie uitsig';
$strReloadFailed = 'MySQL herlaai het misluk.';
$strReloadMySQL = 'Herlaai MySQL';
$strRememberReload = 'Onthou om die bediener (server) te herlaai.';
$strRenameTable = 'Hernoem tabel na';
$strRenameTableOK = 'Tabel %s is vernoem na %s';
$strRepairTable = 'Herstel tabel';
$strReplaceTable = 'Vervang tabel data met leer (file)';
$strReplace = 'Vervang';
$strReset = 'Herstel';
$strReType = 'Tik weer';
$strRevokeGrant = 'Herroep Vergunning';
$strRevokeGrantMessage = 'Jy het die Vergunnings-reg herroep vir %s';
$strRevoke = 'Herroep';
$strRevokeMessage = 'Jy het die regte herroep vir %s';
$strRevokePriv = 'Herroep Regte';
$strRowLength = 'Ry lengte';
$strRowsFrom = 'ry(e) beginnende vanaf rekord #';
$strRowSize = ' Ry grootte ';
$strRowsModeHorizontal = 'horisontale';
$strRowsModeOptions = 'in %s formaat en herhaal opskrifte na %s selle';
$strRowsModeVertical = 'vertikale';
$strRows = 'Rye';
$strRowsStatistic = 'Ry Statistiek';
$strRunning = 'op bediener %s';
$strRunQuery = 'Doen Navraag';
$strRunSQLQuery = 'Hardloop SQL stellings op databasis %s';

$strSave = 'Stoor';
$strScaleFactorSmall = 'Die skaal faktor is te klein om die skema op een bladsy te pas';
$strSearchFormTitle = 'Soek in databasis';
$strSearchInTables = 'Binne tabel(le):';
$strSearchNeedle = 'Woord(e) of waarde(s) om voor te soek (wildcard: "%"):';
$strSearchOption1 = 'ten minste een van die woorde';
$strSearchOption2 = 'alle woorde';
$strSearchOption3 = 'die presiese frase';
$strSearchOption4 = 'as \'n regular expression';
$strSearchResultsFor = 'Soek resultate vir "<i>%s</i>" %s:';
$strSearch = 'Soek';
$strSearchType = 'Vind:';
$strSelectADb = 'Kies asb. \'n databasis';
$strSelectAll = 'Kies Alles';
$strSelectFields = 'Kies Velde (ten minste een):';
$strSelect = 'Kies';
$strSelectNumRows = 'in navraag';
$strSelectTables = 'Kies Tabelle';
$strSend = 'Stoor as leer (file)';
$strServerChoice = 'Bediener Keuse';
$strServerVersion = 'Bediener weergawe';
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').';
$strShowAll = 'Wys alles';
$strShowColor = 'Wys kleur';
$strShowCols = 'Wys kolomme';
$strShowGrid = 'Wys ruitgebied';
$strShowingRecords = 'Vertoon rye';
$strShowPHPInfo = 'Wys PHP informasie';
$strShowTableDimension = 'Wys dimensie van tabelle';
$strShowTables = 'Wys tabelle';
$strShowThisQuery = ' Wys hierdie navraag weer hier ';
$strShow = 'Wys';
$strSingly = '(afsonderlik)';
$strSize = 'Grootte';
$strSort = 'Sorteer';
$strSpaceUsage = 'Spasie verbruik';
$strSplitWordsWithSpace = 'Woorde is geskei dmv \'n spasie karakter (" ").';
$strSQLQuery = 'SQL-stelling';
$strSQLResult = 'SQL resultaat';
$strSQL = 'SQL';
$strStatement = 'Stellings';
$strStrucCSV = 'CSV data';
$strStrucData = 'Struktuur en data';
$strStrucDrop = 'Voeg \'drop table\' by';
$strStrucExcelCSV = 'CSV vir M$ Excel data';
$strStrucOnly = 'Slegs struktuur';
$strStructPropose = 'Stel tabel struktuur voor';
$strStructure = 'Struktuur';
$strSubmit = 'Stuur';
$strSuccess = 'Jou SQL-navraag is suksesvol uitgevoer';
$strSum = 'Som';

$strTableComments = 'Tabel kommentaar';
$strTableEmpty = 'Die tabel naam is leeg!';
$strTableHasBeenDropped = 'Tabel %s is verwyder';
$strTableHasBeenEmptied = 'Tabel %s is leeg gemaak';
$strTableHasBeenFlushed = 'Tabel %s is geflush';
$strTableMaintenance = 'Tabel instandhouding';
$strTables = '%s tabel(le)';
$strTableStructure = 'Tabel struktuur vir tabel';
$strTable = 'tabel ';
$strTableType = 'Tabel tipe';
$strTextAreaLength = ' Omrede sy lengte,<br /> is hierdie veld moontlik nie veranderbaar nie ';
$strTheContent = 'Die inhoud van jou leer is ingevoeg.';
$strTheContents = 'Die inhoud van die leer vervang die inhoud van die geselekteerde tabel vir rye met \'n identiese primere of unieke sleutel.';
$strTheTerminator = 'Die beeindiger (terminator) van die velde.';
$strTotal = 'totaal';
$strType = 'Tipe';

$strUncheckAll = 'Kies Niks';
$strUnique = 'Uniek';
$strUnselectAll = 'Selekteer Niks';
$strUpdatePrivMessage = 'Jy het die regte opgedateer vir %s.';
$strUpdateProfileMessage = 'Die profiel is opgedateer.';
$strUpdateProfile = 'Verander profiel:';
$strUpdateQuery = 'Verander Navraag';
$strUsage = 'Gebruik';
$strUseBackquotes = 'Omring tabel en veldname met backquotes';
$strUserEmpty = 'Die gebruiker naam ontbreek!';
$strUser = 'Gebruiker';
$strUserName = 'Gebruiker naam';
$strUsers = 'Gebruikers';
$strUseTables = 'Gebruik Tabelle';

$strValidateSQL = 'Valideer SQL';
$strValue = 'Waarde';
$strViewDumpDB = 'Sien die storting (skema) van die databasis';
$strViewDump = 'Sien die storting (skema) van die tabel';

$strWelcome = 'Welkom by %s';
$strWithChecked = 'Met gekose:';
$strWrongUser = 'Verkeerde gebruikernaam/wagwoord. Toegang geweier.';

$strYes = 'Ja';

$strZip = '"ge-zip"';
// To translate


$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCharsetOfFile = 'Character set of the file:'; //to translate

$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate

$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate

?>
