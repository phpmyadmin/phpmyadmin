<?php

/* $Id$ */

$charset = 'utf-8';
$allow_recoding = TRUE;
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Søn', 'Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php
// to define the variable below
$datefmt = '%d/%m %Y kl. %H:%M:%S';


$strAccessDenied = 'Adgang Nægtet';
$strAction = 'Handling';
$strAddDeleteColumn = 'Tilføj/Slet felt kolonne';
$strAddDeleteRow = 'Tilføj/Slet kriterie række';
$strAddNewField = 'Tilføj nyt felt';
$strAddPrivMessage = 'Du har tilføjet et nyt privilegium.';
$strAddPriv = 'Tilføj nyt privilegium';
$strAddSearchConditions = 'Tilføj søgekriterier (kroppen af "WHERE" sætningen):';
$strAddToIndex = 'Føj til indeks &nbsp;%s&nbsp;kolonne(r)';
$strAddUserMessage = 'Du har tilføjet en ny bruger.';
$strAddUser = 'Tilføj en ny bruger';
$strAffectedRows = 'Berørte rækker:';
$strAfter = 'Efter %s';
$strAfterInsertBack = 'Retur';
$strAfterInsertNewInsert = 'Indsæt en ny record';
$strAll = 'Alle';
$strAlterOrderBy = 'Arranger rækkeorden efter';
$strAnalyzeTable = 'Analyser tabel';
$strAnd = 'Og';
$strAnIndex = 'Der er tilføjet et indeks til %s';
$strAnyColumn = 'Enhver kolonne';
$strAnyDatabase = 'Enhver database';
$strAny = 'Enhver';
$strAnyHost = 'Enhver vært';
$strAnyTable = 'Enhver tabel';
$strAnyUser = 'Enhver bruger';
$strAPrimaryKey = 'Der er føjet en primær nøgle til %s';
$strAscending = 'Stigende';
$strAtBeginningOfTable = 'I begyndelsen af tabel';
$strAtEndOfTable = 'I slutningen af tabel';
$strAttr = 'Attributter';

$strBack = 'Tilbage';
$strBinary = ' Binært ';
$strBinaryDoNotEdit = ' Binært - må ikke ændres ';
$strBookmarkDeleted = 'Bogmærket er fjernet.';
$strBookmarkLabel = 'Label';
$strBookmarkQuery = 'SQL-forespørgsel med bogmærke';
$strBookmarkThis = 'Lav bogmærke til denne SQL-forespørgsel';
$strBookmarkView = 'Kun oversigt';
$strBrowse = 'Vis';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'MySQL udvidelser kan ikke loades,<br />check PHP konfigurationen.';
$strCantRenameIdxToPrimary = 'Kan ikke omdøbe indeks til PRIMARY!';
$strCardinality = 'Kardinalitet';
$strCarriage = 'Carriage return: \\r';
$strChange = 'Ændre';
$strChangePassword = 'Ændre password';
$strCheckAll = 'Afmærk alt';
$strCheckDbPriv = 'Tjek database privilegier';
$strCheckTable = 'Tjek tabel';
$strColumn = 'Kolonne';
$strColumnNames = 'Kolonne navne';
$strCompleteInserts = 'Lav komplette inserts';
$strConfirm = 'Ikke du sikker på at du vil gøre det?';
$strCookiesRequired = 'Herefter skal cookies være sat til.';
$strCopyTable = 'Kopier tabel til (database<b>.</b>tabel):';
$strCopyTableOK = 'Tabellen %s er nu kopieret til: %s.';
$strCreateIndex = 'Dan et indeks på&nbsp;%s&nbsp;kolonner';
$strCreateIndexTopic = 'Lav et nyt indeks';
$strCreateNewDatabase = 'Opret ny database';
$strCreateNewTable = 'Opret ny tabel i database %s';
$strCreate = 'Opret';
$strCriteria = 'Kriterier';

$strDatabase = 'Database: ';
$strDatabaseHasBeenDropped = 'Database %s er slettet.';
$strDatabases = 'databaser';
$strDatabasesStats = 'Database statistik';
$strDatabaseWildcard = 'Database (jokertegn tilladt):';
$strData = 'Data';
$strDataOnly = 'Kun data';
$strDefault = 'Standardværdi';
$strDeleted = 'Rækken er slettet!';
$strDeletedRows = 'Slettede rækker:';
$strDeleteFailed = 'Kan ikke slette!';
$strDelete = 'Slet';
$strDeleteUserMessage = 'Du har slettet brugeren %s.';
$strDescending = 'Faldende';
$strDisplayOrder = 'Rækkefølge af visning:';
$strDisplay = 'Vis';
$strDoAQuery = 'Kør en forespørgsel på felter (wildcard: "%")';
$strDocu = 'Dokumentation';
$strDoYouReally = 'Er du sikker på at du vil ';
$strDropDB = 'Slet database %s';
$strDrop = 'Slet';
$strDropTable = 'Slet tabel';
$strDumpingData = 'Data dump for tabellen';
$strDynamic = 'dynamisk';

$strEditPrivileges = 'Ret privilegier';
$strEdit = 'Ret';
$strEffective = 'Effektiv';
$strEmptyResultSet = 'MySQL returnerede ingen data (fx ingen rækker).';
$strEmpty = 'Tøm';
$strEnd = 'Slut';
$strEnglishPrivileges = ' NB: Navne på MySQL privilegier er på engelsk ';
$strError = 'Fejl';
$strExtendedInserts = 'Udvidede inserts';
$strExtra = 'Ekstra';

$strField = 'Feltnavn';
$strFieldHasBeenDropped = 'Felt %s er slettet';
$strFieldsEmpty = ' Felttallet har ingen værdi! ';
$strFieldsEnclosedBy = 'Felter indrammet med';
$strFieldsEscapedBy = 'Felter escaped med';
$strFields = 'Felter';
$strFieldsTerminatedBy = 'Felter afsluttet med';
$strFixed = 'ordnet';
$strFlushTable = 'Flush tabellen ("FLUSH")';
$strFormat = 'Format';
$strFormEmpty = 'Ingen værdi i formularen !';
$strFullText = 'Komplette tekster';
$strFunction = 'Funktion';

$strGenTime = 'Genereringstidspunkt';
$strGo = 'Udfør';
$strGrants = 'Tildelinger';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'er ændret.';
$strHasBeenCreated = 'er oprettet.';
$strHome = 'Hjem';
$strHomepageOfficial = 'Officiel phpMyAdmin hjemmeside ';
$strHomepageSourceforge = 'Ny phpMyAdmin hjemmeside ';
$strHostEmpty = 'Der er intet værtsnavn!';
$strHost = 'Vært';

$strIdxFulltext = 'Fuldtekst';
$strIfYouWish = 'Hvis du kun ønsker at importere nogle af tabellens kolonner, angives de med en kommasepareret felt liste.';
$strIgnore = 'Ignorer';
$strIndexes = 'Indekser';
$strIndexHasBeenDropped = 'Indeks %s er blevet slettet';
$strIndex = 'Indeks';
$strIndexName = 'Indeks navn&nbsp;:';
$strIndexType = 'Indeks type&nbsp;:';
$strInsertAsNewRow = 'Indsæt som ny række';
$strInsertedRows = 'Indsatte rækker:';
$strInsert = 'Indsæt';
$strInsertNewRow = 'Indsæt ny række';
$strInsertTextfiles = 'Importer tekstfil til tabellen';
$strInstructions = 'Instruktioner';
$strInUse = 'i brug';
$strInvalidName = '"%s" er et reserveret ord, du kan ikke bruge det som database-, tabel- eller feltnavn.';

$strKeepPass = 'Password må ikke ændres';
$strKeyname = 'Nøgle';
$strKill = 'Kill';

$strLength = 'Længde';
$strLengthSet = 'Længde/Værdi*';
$strLimitNumRows = 'poster pr. side';
$strLineFeed = 'Linefeed: \\n';
$strLines = 'Linier';
$strLinesTerminatedBy = 'Linier afsluttet med';
$strLocationTextfile = 'Tekstfilens placering';
$strLogin = 'Login';
$strLogout = 'Log af';
$strLogPassword = 'Password:';
$strLogUsername = 'Brugernavn:';

$strModifications = 'Rettelserne er gemt!';
$strModifyIndexTopic = 'Ændring af et indeks';
$strModify = 'Ret';
$strMoveTable = 'Flyt tabel til (database<b>.</b>tabel):';
$strMoveTableOK = 'Tabel %s er flyttet til %s.';
$strMySQLReloaded = 'MySQL genstartet.';
$strMySQLSaid = 'MySQL returnerede: ';
$strMySQLServerProcess = 'MySQL %pma_s1% kører på %pma_s2% som %pma_s3%';
$strMySQLShowProcess = 'Vis tråde';
$strMySQLShowStatus = 'Vis MySQL runtime information';
$strMySQLShowVars = 'Vis MySQL system variable';

$strName = 'Navn';
$strNext = 'Næste';
$strNoDatabases = 'Ingen databaser';
$strNoDropDatabases = '"DROP DATABASE" erklæringer kan ikke bruges.';
$strNoFrames = 'phpMyAdmin er mere brugervenlig med en browser, der kan klare <b>frames</b>.';
$strNoIndex = 'Intet indeks defineret!';
$strNoIndexPartsDefined = 'Ingen dele af indeks er definerede!';
$strNoModification = 'Ingen ændring';
$strNone = 'Intet';
$strNo = 'Nej';
$strNoPassword = 'Intet password';
$strNoPrivileges = 'Ingen privilegier';
$strNoQuery = 'Ingen SQL forespørgsel!';
$strNoRights = 'Du har ikke tilstrækkelige rettigheder til at være her!';
$strNoTablesFound = 'Ingen tabeller fundet i databasen.';
$strNotNumber = 'Dette er ikke et tal!';
$strNotValidNumber = ' er ikke et gyldigt rækkenummer!';
$strNoUsersFound = 'Ingen bruger(e) fundet.';
$strNull = 'Nulværdi';

$strOftenQuotation = 'Ofte anførselstegn. OPTIONALLY betyder at kun char og varchar felter er omsluttet med det valgte "tekstkvalifikator"-tegn.'; //skal muligvis ændres
$strOptimizeTable = 'Optimer tabel';
$strOptionalControls = 'Valgfrit. Kontrollerer hvordan specialtegn skrives eller læses.';
$strOptionally = 'OPTIONALLY';
$strOr = 'Eller';
$strOverhead = 'Overhead';

$strPartialText = 'Delvise tekster';
$strPasswordEmpty = 'Der er ikke angivet noget password!';
$strPasswordNotSame = 'De to passwords er ikke ens!';
$strPassword = 'Password';
$strPHPVersion = 'PHP version';
$strPmaDocumentation = 'phpMyAdmin dokumentation';
$strPmaUriError = '<tt>$cfg[\'PmaAbsoluteUri\']</tt> direktivet SKAL være sat i konfigurationsfilen!';
$strPos1 = 'Start';
$strPrevious = 'Forrige';
$strPrimaryKeyHasBeenDropped = 'Primærnøglen er slettet';
$strPrimaryKeyName = 'Navnet på primærnøglen skal være... PRIMARY!';
$strPrimaryKey = 'Primær nøgle';
$strPrimaryKeyWarning = '("PRIMARY" <b>skal</b> være navnet på og <b>kun på</b> en primær nøgle!)';
$strPrimary = 'Primær';
$strPrintView = 'Vis (udskriftvenlig)';
$strPrivileges = 'Privilegier';
$strProperties = 'Egenskaber';

$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQBE = 'Query by Example';
$strQueryOnDb = 'SQL-forespørgsel til database <b>%s</b>:';

$strRecords = 'Poster';
$strReferentialIntegrity = 'Check reference integriteten';
$strReloadFailed = 'Genstart af MySQL fejlede.';
$strReloadMySQL = 'Genstart MySQL';
$strRememberReload = 'Husk at indlæse serveren.';
$strRenameTableOK = 'Tabellen %s er nu omdøbt til: %s';
$strRenameTable = 'Omdøb tabel til';
$strRepairTable = 'Reparer tabel';
$strReplace = 'Erstat';
$strReplaceTable = 'Erstat data i tabellen med filens data';
$strReset = 'Nulstil';
$strReType = 'Skriv igen';
$strRevokeGrantMessage = 'Du har tilbagekaldt det tildelte privilegium for %s';
$strRevokeGrant = 'Tilbagekald tildeling';
$strRevokeMessage = 'Du har tilbagekaldt privilegierne for %s';
$strRevokePriv = 'Tilbagekald privilegier';
$strRevoke = 'Tilbagekald';
$strRowLength = 'Række længde';
$strRowsFrom = 'rækker startende fra';
$strRowSize = ' Række størrelse ';
$strRowsModeHorizontal = 'vandret';
$strRowsModeOptions = '%s og gentag overskrifter efter %s celler';
$strRowsModeVertical = 'lodret';
$strRows = 'Rækker';
$strRowsStatistic = 'Række statistik';
$strRunning = 'kører på %s';
$strRunQuery = 'Send forespørgsel';
$strRunSQLQuery = 'Kør SQL forspørgsel(er) på database %s';

$strSave = 'Gem';
$strSelectADb = 'Vælg en database';
$strSelectAll = 'Vælg alle';
$strSelectFields = 'Vælg mindst eet felt:';
$strSelectNumRows = 'i forespørgsel';
$strSelect = 'Vælg';
$strSend = 'Send';
$strServerChoice = 'Server valg';
$strServerVersion = 'Server version';
$strSetEnumVal = 'Hvis et felt er af typen "enum" eller "set", skal værdierne angives på formen: \'a\',\'b\',\'c\'...<br />Skulle du få brug for en backslash ("\") eller et enkelt anførselstegn ("\'") blandt disse værdier, så tilføj en ekstra backslash (fx \'\\\\xyz\' or \'a\\\'b\').';
$strShowAll = 'Vis alt';
$strShowCols = 'Vis kolonner';
$strShowingRecords = 'Viser poster ';
$strShowPHPInfo = 'Vis PHP information';
$strShowTables = 'Vis tabeller';
$strShowThisQuery = ' Vis forespørgslen her igen ';
$strShow = 'Vis';
$strSingly = '(enkeltvis)';
$strSize = 'Størrelse';
$strSort = 'Sorter';
$strSpaceUsage = 'Pladsforbrug';
$strSQLQuery = 'SQL-forespørgsel';
$strStatement = 'Erklæringer';
$strStrucCSV = 'CSV data';
$strStrucData = 'Struturen og data';
$strStrucDrop = 'Tilføj \'DROP TABLE\'';
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strStrucOnly = 'Kun strukturen';
$strSubmit = 'Send';
$strSuccess = 'Din SQL-forespørgsel blev udført korrekt';
$strSum = 'Sum';

$strTableComments = 'Tabel kommentarer';
$strTableEmpty = 'Intet tabelnavn!';
$strTableHasBeenDropped = 'Tabel %s er slettet';
$strTableHasBeenEmptied = 'Tabel %s er tømt';
$strTableHasBeenFlushed = 'Tabel %s er blevet flushet';
$strTableMaintenance = 'Tabel vedligehold';
$strTables = '%s tabel(ler)';
$strTableStructure = 'Struktur dump for tabellen';
$strTable = 'Tabel: ';
$strTableType = 'Tabel type';
$strTextAreaLength = ' På grund af feltets længde,<br /> kan det muligvis ikke ændres ';
$strTheContent = 'Filens indhold er importeret.';
$strTheContents = 'Filens indhold erstatter den valgte tabels rækker for rækker med identisk primær eller unik nøgle.';
$strTheTerminator = 'Felterne afgrænses af dette tegn.';
$strTotal = 'total';
$strType = 'Datatype';

$strUncheckAll = 'Fjern alle mærker';
$strUnique = 'Unik';
$strUnselectAll = 'Fravælg alle';
$strUpdatePrivMessage = 'Du har opdateret privilegierne for %s.';
$strUpdateProfileMessage = 'Profilen er blevet opdateret.';
$strUpdateProfile = 'Opdater profil:';
$strUpdateQuery = 'Opdater forespørgsel';
$strUsage = 'Benyttelse';
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strUser = 'Bruger';
$strUserEmpty = 'Intet brugernavn!';
$strUserName = 'Brugernavn';
$strUsers = 'Brugere';
$strUseTables = 'Benyt tabeller';

$strValue = 'Værdi';
$strViewDumpDB = 'Vis dump (skema) af database';
$strViewDump = 'Vis dump (skema) over tabel';

$strWelcome = 'Velkommen til %s';
$strWithChecked = 'Med det afmærkede:';
$strWrongUser = 'Forkert brugernavn/kodeord. Adgang nægtet.';

$strYes = 'Ja';

$strZip = '"zipped"';


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
