<?php
/* $Id$ */

$charset = 'utf-8';
$allow_recoding = TRUE;
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
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
$strAddPriv = 'Tilføj nyt privilegium';
$strAddPrivMessage = 'Du har tilføjet et nyt privilegium.';
$strAddSearchConditions = 'Tilføj søgekriterier (kroppen af "WHERE" sætningen):';
$strAddToIndex = 'Føj til indeks &nbsp;%s&nbsp;kolonne(r)';
$strAddUser = 'Tilføj en ny bruger';
$strAddUserMessage = 'Du har tilføjet en ny bruger.';
$strAffectedRows = 'Berørte rækker:';
$strAfter = 'Efter %s';
$strAfterInsertBack = 'Retur';
$strAfterInsertNewInsert = 'Indsæt en ny record';
$strAll = 'Alle';
$strAlterOrderBy = 'Arranger rækkeorden efter';
$strAnalyzeTable = 'Analyser tabel';
$strAnd = 'Og';
$strAnIndex = 'Der er tilføjet et indeks til %s';
$strAny = 'Enhver';
$strAnyColumn = 'Enhver kolonne';
$strAnyDatabase = 'Enhver database';
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
$strCreate = 'Opret';
$strCreateIndex = 'Dan et indeks på&nbsp;%s&nbsp;kolonner';
$strCreateIndexTopic = 'Lav et nyt indeks';
$strCreateNewDatabase = 'Opret ny database';
$strCreateNewTable = 'Opret ny tabel i database %s';
$strCriteria = 'Kriterier';

$strData = 'Data';
$strDatabase = 'Database: ';
$strDatabaseHasBeenDropped = 'Database %s er slettet.';
$strDatabases = 'databaser';
$strDatabasesStats = 'Database statistik';
$strDatabaseWildcard = 'Database (jokertegn tilladt):';
$strDataOnly = 'Kun data';
$strDefault = 'Standardværdi';
$strDelete = 'Slet';
$strDeleted = 'Rækken er slettet!';
$strDeletedRows = 'Slettede rækker:';
$strDeleteFailed = 'Kan ikke slette!';
$strDeleteUserMessage = 'Du har slettet brugeren %s.';
$strDescending = 'Faldende';
$strDisplay = 'Vis';
$strDisplayOrder = 'Rækkefølge af visning:';
$strDoAQuery = 'Kør en forespørgsel på felter (wildcard: "%")';
$strDocu = 'Dokumentation';
$strDoYouReally = 'Er du sikker på at du vil ';
$strDrop = 'Slet';
$strDropDB = 'Slet database %s';
$strDropTable = 'Slet tabel';
$strDumpingData = 'Data dump for tabellen';
$strDynamic = 'dynamisk';

$strEdit = 'Ret';
$strEditPrivileges = 'Ret privilegier';
$strEffective = 'Effektiv';
$strEmpty = 'Tøm';
$strEmptyResultSet = 'MySQL returnerede ingen data (fx ingen rækker).';
$strEnd = 'Slut';
$strEnglishPrivileges = ' NB: Navne på MySQL privilegier er på engelsk ';
$strError = 'Fejl';
$strExtendedInserts = 'Udvidede inserts';
$strExtra = 'Ekstra';

$strField = 'Feltnavn';
$strFieldHasBeenDropped = 'Felt %s er slettet';
$strFields = 'Felter';
$strFieldsEmpty = ' Felttallet har ingen værdi! ';
$strFieldsEnclosedBy = 'Felter indrammet med';
$strFieldsEscapedBy = 'Felter escaped med';
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
$strHost = 'Vært';
$strHostEmpty = 'Der er intet værtsnavn!';

$strIdxFulltext = 'Fuldtekst';
$strIfYouWish = 'Hvis du kun ønsker at importere nogle af tabellens kolonner, angives de med en kommasepareret felt liste.';
$strIgnore = 'Ignorer';
$strIndex = 'Indeks';
$strIndexes = 'Indekser';
$strIndexHasBeenDropped = 'Indeks %s er blevet slettet';
$strIndexName = 'Indeks navn&nbsp;:';
$strIndexType = 'Indeks type&nbsp;:';
$strInsert = 'Indsæt';
$strInsertAsNewRow = 'Indsæt som ny række';
$strInsertedRows = 'Indsatte rækker:';
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
$strModify = 'Ret';
$strModifyIndexTopic = 'Ændring af et indeks';
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
$strNo = 'Nej';
$strNoDatabases = 'Ingen databaser';
$strNoDropDatabases = '"DROP DATABASE" erklæringer kan ikke bruges.';
$strNoFrames = 'phpMyAdmin er mere brugervenlig med en browser, der kan klare <b>frames</b>.';
$strNoIndex = 'Intet indeks defineret!';
$strNoIndexPartsDefined = 'Ingen dele af indeks er definerede!';
$strNoModification = 'Ingen ændring';
$strNone = 'Intet';
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
$strPassword = 'Password';
$strPasswordEmpty = 'Der er ikke angivet noget password!';
$strPasswordNotSame = 'De to passwords er ikke ens!';
$strPHPVersion = 'PHP version';
$strPmaDocumentation = 'phpMyAdmin dokumentation';
$strPmaUriError = '<tt>$cfg[\'PmaAbsoluteUri\']</tt> direktivet SKAL være sat i konfigurationsfilen!';
$strPos1 = 'Start';
$strPrevious = 'Forrige';
$strPrimary = 'Primær';
$strPrimaryKey = 'Primær nøgle';
$strPrimaryKeyHasBeenDropped = 'Primærnøglen er slettet';
$strPrimaryKeyName = 'Navnet på primærnøglen skal være... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>skal</b> være navnet på og <b>kun på</b> en primær nøgle!)';
$strPrintView = 'Vis (udskriftvenlig)';
$strPrivileges = 'Privilegier';
$strProperties = 'Egenskaber';

$strQBE = 'Query by Example';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'SQL-forespørgsel til database <b>%s</b>:';

$strRecords = 'Poster';
$strReferentialIntegrity = 'Check reference integriteten';
$strReloadFailed = 'Genstart af MySQL fejlede.';
$strReloadMySQL = 'Genstart MySQL';
$strRememberReload = 'Husk at indlæse serveren.';
$strRenameTable = 'Omdøb tabel til';
$strRenameTableOK = 'Tabellen %s er nu omdøbt til: %s';
$strRepairTable = 'Reparer tabel';
$strReplace = 'Erstat';
$strReplaceTable = 'Erstat data i tabellen med filens data';
$strReset = 'Nulstil';
$strReType = 'Skriv igen';
$strRevoke = 'Tilbagekald';
$strRevokeGrant = 'Tilbagekald tildeling';
$strRevokeGrantMessage = 'Du har tilbagekaldt det tildelte privilegium for %s';
$strRevokeMessage = 'Du har tilbagekaldt privilegierne for %s';
$strRevokePriv = 'Tilbagekald privilegier';
$strRowLength = 'Række længde';
$strRows = 'Rækker';
$strRowsFrom = 'rækker startende fra';
$strRowSize = ' Række størrelse ';
$strRowsModeHorizontal = 'vandret';
$strRowsModeOptions = '%s og gentag overskrifter efter %s celler';
$strRowsModeVertical = 'lodret';
$strRowsStatistic = 'Række statistik';
$strRunning = 'kører på %s';
$strRunQuery = 'Send forespørgsel';
$strRunSQLQuery = 'Kør SQL forspørgsel(er) på database %s';

$strSave = 'Gem';
$strSelect = 'Vælg';
$strSelectADb = 'Vælg en database';
$strSelectAll = 'Vælg alle';
$strSelectFields = 'Vælg mindst eet felt:';
$strSelectNumRows = 'i forespørgsel';
$strSend = 'Send';
$strServerChoice = 'Server valg';
$strServerVersion = 'Server version';
$strSetEnumVal = 'Hvis et felt er af typen "enum" eller "set", skal værdierne angives på formen: \'a\',\'b\',\'c\'...<br />Skulle du få brug for en backslash ("\") eller et enkelt anførselstegn ("\'") blandt disse værdier, så tilføj en ekstra backslash (fx \'\\\\xyz\' or \'a\\\'b\').';
$strShow = 'Vis';
$strShowAll = 'Vis alt';
$strShowCols = 'Vis kolonner';
$strShowingRecords = 'Viser poster ';
$strShowPHPInfo = 'Vis PHP information';
$strShowTables = 'Vis tabeller';
$strShowThisQuery = ' Vis forespørgslen her igen ';
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

$strTable = 'Tabel';
$strTableComments = 'Tabel kommentarer';
$strTableEmpty = 'Intet tabelnavn!';
$strTableHasBeenDropped = 'Tabel %s er slettet';
$strTableHasBeenEmptied = 'Tabel %s er tømt';
$strTableHasBeenFlushed = 'Tabel %s er blevet flushet';
$strTableMaintenance = 'Tabel vedligehold';
$strTables = '%s tabel(ler)';
$strTableStructure = 'Struktur dump for tabellen';
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
$strUpdateProfile = 'Opdater profil:';
$strUpdateProfileMessage = 'Profilen er blevet opdateret.';
$strUpdateQuery = 'Opdater forespørgsel';
$strUsage = 'Benyttelse';
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strUser = 'Bruger';
$strUserEmpty = 'Intet brugernavn!';
$strUserName = 'Brugernavn';
$strUsers = 'Brugere';
$strUseTables = 'Benyt tabeller';

$strValue = 'Værdi';
$strViewDump = 'Vis dump (skema) over tabel';
$strViewDumpDB = 'Vis dump (skema) af database';

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
$strServerTabVariables = 'Variables'; //to translate
$strServerTabProcesslist = 'Processes'; //to translate
$strServerTrafficNotes = '<b>Server traffic</b>: These tables show the network traffic statistics of this MySQL server since its startup.';
$strServerVars = 'Server variables and settings'; //to translate
$strSessionValue = 'Session value'; //to translate
$strTraffic = 'Traffic'; //to translate
$strVar = 'Variable'; //to translate

$strCommand = 'Command'; //to translate
$strCouldNotKill = 'phpMyAdmin was unable to kill thread %s. It probably has already been closed.'; //to translate
$strId = 'ID'; //to translate
$strProcesslist = 'Process list'; //to translate
$strStatus = 'Status'; //to translate
$strTime = 'Time'; //to translate
$strThreadSuccessfullyKilled = 'Thread %s was successfully killed.'; //to translate

$strBzError = 'phpMyAdmin was unable to compress the dump because of a broken Bz2 extension in this php version. It is strongly recommended to set the <code>$cfg[\'BZipDump\']</code> directive in your phpMyAdmin configuration file to <code>FALSE</code>. If you want to use the Bz2 compression features, you should upgrade to a later php version. See php bug report %s for details.'; //to translate
$strLaTeX = 'LaTeX';  //to translate

$strAdministration = 'Administration'; //to translate
$strFlushPrivilegesNote = 'Note: phpMyAdmin gets the users\' privileges directly from MySQL\'s privilege tables. The content of this tables may differ from the privileges the server uses if manual changes have made to it. In this case, you should %sreload the privileges%s before you continue.'; //to translate
$strGlobalPrivileges = 'Global privileges'; //to translate
$strGrantOption = 'Grant'; //to translate
$strPrivDescAllPrivileges = 'Includes all privileges except GRANT.'; //to translate
$strPrivDescAlter = 'Allows altering the structure of existing tables.'; //to translate
$strPrivDescCreateDb = 'Allows creating new databases and tables.'; //to translate
$strPrivDescCreateTbl = 'Allows creating new tables.'; //to translate
$strPrivDescCreateTmpTable = 'Allows creating temporary tables.'; //to translate
$strPrivDescDelete = 'Allows deleting data.'; //to translate
$strPrivDescDropDb = 'Allows dropping databases and tables.'; //to translate
$strPrivDescDropTbl = 'Allows dropping tables.'; //to translate
$strPrivDescExecute = 'Allows running stored procedures; Has no effect in this MySQL version.'; //to translate
$strPrivDescFile = 'Allows importing data from and exporting data into files.'; //to translate
$strPrivDescGrant = 'Allows adding users and privileges without reloading the privilege tables.'; //to translate
$strPrivDescIndex = 'Allows creating and dropping indexes.'; //to translate
$strPrivDescInsert = 'Allows inserting and replacing data.'; //to translate
$strPrivDescLockTables = 'Allows locking tables for the current thread.'; //to translate
$strPrivDescMaxConnections = 'Limits the number of new connections the user may open per hour.';
$strPrivDescMaxQuestions = 'Limits the number of queries the user may send to the server per hour.';
$strPrivDescMaxUpdates = 'Limits the number of commands that change any table or database the user may execute per hour.';
$strPrivDescProcess3 = 'Allows killing processes of other users.'; //to translate
$strPrivDescProcess4 = 'Allows viewing the complete queries in the process list.'; //to translate
$strPrivDescReferences = 'Has no effect in this MySQL version.'; //to translate
$strPrivDescReplClient = 'Gives the right to the user to ask where the slaves / masters are.'; //to translate
$strPrivDescReplSlave = 'Needed for the replication slaves.'; //to translate
$strPrivDescReload = 'Allows reloading server settings and flushing the server\'s caches.'; //to translate
$strPrivDescSelect = 'Allows reading data.'; //to translate
$strPrivDescShowDb = 'Gives access to the complete list of databases.'; //to translate
$strPrivDescShutdown = 'Allows shutting down the server.'; //to translate
$strPrivDescSuper = 'Allows connectiong, even if maximum number of connections is reached; Required for most administrative operations like setting global variables or killing threads of other users.'; //to translate
$strPrivDescUpdate = 'Allows changing data.'; //to translate
$strPrivDescUsage = 'No privileges.'; //to translate
$strPrivilegesReloaded = 'The privileges were reloaded successfully.'; //to translate
$strResourceLimits = 'Resource limits'; //to translate
$strUserOverview = 'User overview'; //to translate
$strZeroRemovesTheLimit = 'Note: Setting these options to 0 (zero) removes the limit.'; //to translate

$strPasswordChanged = 'The Password for %s was changed successfully.'; // to translate

$strDeleteAndFlush = 'Delete the users and reload the privileges afterwards.'; //to translate
$strDeleteAndFlushDescr = 'This is the cleanest way, but reloading the privileges may take a while.'; //to translate
$strDeleting = 'Deleting %s'; //to translate
$strJustDelete = 'Just delete the users from the privilege tables.'; //to translate
$strJustDeleteDescr = 'The &quot;deleted&quot; users will still be able to access the server as usual until the privileges are reloaded.'; //to translate
$strReloadingThePrivileges = 'Reloading the privileges'; //to translate
$strRemoveSelectedUsers = 'Remove selected users'; //to translate
$strRevokeAndDelete = 'Revoke all active privileges from the users and delete them afterwards.'; //to translate
$strRevokeAndDeleteDescr = 'The users will still have the USAGE privilege until the privileges are reloaded.'; //to translate
$strUsersDeleted = 'The selected users have been deleted successfully.'; //to translate
$strOriginalInterface = 'original interface';  //to translate

$strAddPrivilegesOnDb = 'Add privileges on the following database'; //to translate
$strAddPrivilegesOnTbl = 'Add privileges on the following table'; //to translate
$strColumnPrivileges = 'Column-specific privileges'; //to translate
$strDbPrivileges = 'Database-specific privileges'; //to translate
$strLocalhost = 'Local';
$strLoginInformation = 'Login Information'; //to translate
$strTblPrivileges = 'Table-specific privileges'; //to translate
$strThisHost = 'This Host'; //to translate
$strUserNotFound = 'The selected user was not found in the privilege table.'; //to translate
$strUserAlreadyExists = 'The user %s already exists!'; //to translate
$strUseTextField = 'Use text field'; //to translate
?>
