<?php
/* $Id$ */

$charset = 'iso-8859-1';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Søn', 'Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d/%m %Y kl. %H:%M:%S';


$strAccessDenied = 'Adgang Nægtet';
$strAction = 'Handling';
$strAddDeleteColumn = 'Tilføj/Slet felt kolonne';
$strAddDeleteRow = 'Tilføj/Slet kriterie række';
$strAddNewField = 'Tilføj nyt felt';
$strAddPriv = 'Tilføj nyt privilegium';
$strAddPrivMessage = 'Du har tilføjet et nyt privilegium.'; 
$strAddSearchConditions = 'Tilføj søgekriterier (kroppen af "WHERE" sætningen):';
$strAddUser = 'Tilføj en ny bruger';
$strAddUserMessage = 'Du har tilføjet en ny bruger.';
$strAffectedRows = 'Berørte rækker:';
$strAfter = 'Efter';
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
$strBookmarkLabel = 'Label'; //to translate
$strBookmarkQuery = 'SQL-forespørgsel med bogmærke';
$strBookmarkThis = 'Lav bogmærke til denne SQL-forespørgsel';
$strBookmarkView = 'Kun oversigt';
$strBrowse = 'Vis';
$strBzip = '"bzipped"'; //to translate

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.'; //to translate
$strCarriage = 'Carriage return: \\r';
$strChange = 'Ændre';
$strCheckAll = 'Afmærk alt'; 
$strCheckDbPriv = 'Tjek database privilegier';
$strCheckTable = 'Tjek tabel';
$strColumn = 'Kolonne';
$strColumnNames = 'Kolonne navne';
$strCompleteInserts = 'Lav komplette inserts';
$strConfirm = 'Ikke du sikker på at du vil gøre det?';
$strCopyTable = 'Kopier tabel til (database<b>.</b>tabel):';
$strCopyTableOK = 'Tabellen %s er nu kopieret til: %s.';
$strCreate = 'Opret';
$strCreateNewDatabase = 'Opret ny database';
$strCreateNewTable = 'Opret ny tabel i database ';
$strCriteria = 'Kriterier';

$strData = 'Data'; 
$strDatabase = 'Database: ';
$strDatabaseHasBeenDropped = 'Database %s er slettet.';
$strDatabases = 'databaser';
$strDatabasesStats = 'Database statistik';
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
$strDropDB = 'Slet database ';
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
$strExtendedInserts = 'Extended inserts';  //to translate
$strExtra = 'Ekstra';

$strField = 'Feltnavn';
$strFieldHasBeenDropped = 'Felt %s er slettet';
$strFields = 'Felter';
$strFieldsEmpty = ' Felttallet har ingen værdi! '; 
$strFieldsEnclosedBy = 'Felter indrammet med';
$strFieldsEscapedBy = 'Felter escaped med';
$strFieldsTerminatedBy = 'Felter afsluttet med';
$strFixed = 'ordnet'; 
$strFormat = 'Format'; //to translate
$strFormEmpty = 'Ingen værdi i formularen !'; 
$strFullText = 'Komplette tekster';
$strFunction = 'Funktion';

$strGenTime = 'Genereringstidspunkt'; 
$strGo = 'Udfør';
$strGrants = 'Tildelinger';
$strGzip = '"gzipped"';  //to translate

$strHasBeenAltered = 'er ændret.';
$strHasBeenCreated = 'er oprettet.';
$strHome = 'Hjem';
$strHomepageOfficial = 'Officiel phpMyAdmin hjemmeside ';
$strHomepageSourceforge = 'Ny phpMyAdmin hjemmeside ';
$strHost = 'Vært';
$strHostEmpty = 'Der er intet værtsnavn!'; 
$strIdxFulltext = 'Fuldtekst'; 
$strIfYouWish = 'Hvis du kun ønsker at importere nogle af tabellens kolonner, angives de med en kommasepareret felt liste.';
$strIndex = 'Indeks';
$strIndexes = 'Indekser';
$strIndexHasBeenDropped = 'Index %s er blevet slettet';
$strInsert = 'Indsæt';
$strInsertAsNewRow = 'Indsæt som ny række';
$strInsertedRows = 'Indsatte rækker:';
$strInsertNewRow = 'Indsæt ny række';
$strInsertTextfiles = 'Importer tekstfil til tabellen';
$strInstructions = 'Instruktioner';
$strInvalidName = '"%s" er et reserveret ord, du kan ikke bruge det som database-, tabel- eller feltnavn.';
$strInUse = 'i brug';

$strKeepPass = 'Password må ikke ændres';
$strKeyname = 'Nøgle';
$strKill = 'Kill'; //to translate

$strLength = 'Længde';
$strLengthSet = 'Længde/Værdi*';
$strLimitNumRows = 'poster pr. side';
$strLineFeed = 'Linefeed: \\n';
$strLines = 'Linier';
$strLinesTerminatedBy = 'Linier afsluttet med';
$strLocationTextfile = 'Tekstfilens placering';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Log af';

$strModifications = 'Rettelserne er gemt!';
$strModify = 'Ret'; 
$strMoveTable = 'Flyt tabel til (database<b>.</b>tabel):';
$strMoveTableOK = 'Tabel %s er flyttet til %s.';
$strMySQLReloaded = 'MySQL genstartet.';
$strMySQLSaid = 'MySQL returnerede: ';
$strMySQLShowProcess = 'Vis tråde';
$strMySQLShowStatus = 'Vis MySQL runtime information';
$strMySQLShowVars = 'Vis MySQL system variable';

$strName = 'Navn';
$strNbRecords = 'Antal records';
$strNext = 'Næste';
$strNo = 'Nej';
$strNoDatabases = 'Ingen databaser'; 
$strNoDropDatabases = '"DROP DATABASE" erklæringer kan ikke bruges.';
$strNoModification = 'Ingen ændring';
$strNoPassword = 'Intet password';
$strNoPrivileges = 'Ingen privilegier';
$strNoQuery = 'Ingen SQL forespørgsel!';
$strNoRights = 'Du har ikke tilstrækkelige rettigheder til at være her!'; 
$strNoTablesFound = 'Ingen tabeller fundet i databasen.';
$strNotNumber = 'Dette er ikke et tal!';
$strNotValidNumber = ' er ikke et gyldigt rækkenummer!';
$strNoUsersFound = 'Ingen bruger(e) fundet.'; 
$strNull = 'Nulværdi';
$strNumberIndexes = ' Antal udvidede indeks ';

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
$strPos1 = 'Start';
$strPrevious = 'Forrige';
$strPrimary = 'Primær';
$strPrimaryKey = 'Primær nøgle';
$strPrimaryKeyHasBeenDropped = 'Primærnøglen er slettet';
$strPrintView = 'Vis (udskriftvenlig)';
$strPrivileges = 'Privilegier';
$strProperties = 'Egenskaber';

$strQBE = 'Query by Example';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php)
$strQueryOnDb = 'SQL-forespørgsel til database ';

$strRecords = 'Poster';
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
$strRevokeGrantMessage = 'Du har tilbagekaldt det tildelte privilegium for';
$strRevokeMessage = 'Du har tilbagekaldt privilegierne for';
$strRevokePriv = 'Tilbagekald privilegier';
$strRowLength = 'Række længde';
$strRows = 'Rækker';
$strRowsFrom = 'rækker startende fra';
$strRowSize = ' Række størrelse ';
$strRowsStatistic = 'Række statistik';
$strRunning = 'kører på ';
$strRunningAs = 'som';  
$strRunQuery = 'Send forespørgsel';
$strRunSQLQuery = 'Kør SQL forspørgsel(er) på database %s';

$strSave = 'Gem';
$strSelect = 'Vælg';
$strSelectFields = 'Vælg mindst eet felt:';
$strSelectNumRows = 'i forespørgsel';
$strSend = 'Send';
$strSequence = 'Seq.'; //to translate
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
$strStartingRecord = 'Begyndende post';
$strStatement = 'Erklæringer'; 
$strStrucCSV = 'CSV data';
$strStrucData = 'Struturen og data';
$strStrucDrop = 'Tilføj \'DROP TABLE\'';
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strStrucOnly = 'Kun strukturen';
$strSubmit = 'Send';
$strSuccess = 'Din SQL-forespørgsel blev udført korrekt';
$strSum = 'Sum'; 

$strTable = 'Tabel: ';
$strTableComments = 'Tabel kommentarer';
$strTableEmpty = 'Intet tabelnavn!';
$strTableHasBeenDropped = 'Tabel %s er slettet';
$strTableHasBeenEmptied = 'Tabel %s er tømt';
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

$strZip = '"zipped"'; //to translate

// To translate
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Insert a new record';
$strBookmarkDeleted = 'The bookmark has been deleted.';
$strFlushTable = 'Flush the table ("FLUSH")';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strTableHasBeenFlushed = 'Table %s has been flushed';
?>
