<?php
/* $Id$ */

$charset = 'iso-8859-1';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('bytes', 'kB', 'MB', 'GB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Åtkomst nekad';
$strAction = 'Handling';
$strAddDeleteColumn = 'Lägg till/ta bort fältkolumner';
$strAddDeleteRow = 'Lägg till/ta bort kriteriumrader';
$strAddNewField = 'Lägg till nytt fält';
$strAddPriv = 'Lägg till ett nytt privilegie';
$strAddPrivMessage = 'Du har lagt till ett nytt privilegie.';
$strAddSearchConditions = 'Lägg till sökkriterier (uttryck i "where"-sats):';
$strAddUser = 'Lägg till ny användare';
$strAddUserMessage = 'Du har lagt till en ny användare.';
$strAffectedRows = 'Påverkade rader:';
$strAfter = 'Efter';
$strAll = 'Alla';
$strAlterOrderBy = 'Sortera om tabellen efter';
$strAnalyzeTable = 'Analysera tabell';
$strAnd = 'Och';
$strAny = 'Any'; //to translate
$strAnyColumn = 'Any Column'; //to translate
$strAnyDatabase = 'Any database'; //to translate
$strAnyHost = 'Any host'; //to translate
$strAnyTable = 'Any table'; //to translate
$strAnyUser = 'Any user'; //to translate
$strAscending = 'Stigande';
$strAtBeginningOfTable = 'I början av tabellen';
$strAtEndOfTable = 'I slutet av tabellen';
$strAttr = 'Attribut';

$strBack = 'Bakåt';
$strBinary = 'Binär';
$strBinaryDoNotEdit = 'Binär - ändra inte';
$strBookmarkLabel = 'Etikett';
$strBookmarkQuery = 'Bokmärkt SQL-fråga';
$strBookmarkThis = 'Skapa bokmärke för den här SQL-frågan';
$strBookmarkView = 'View only'; //to translate
$strBrowse = 'Visa';
$strBzip = '"bzippad"';

$strCantLoadMySQL = 'kan inte ladda MySQL-tillägg,<br />var god och kontrollera PHP-konfigurationen.';
$strCarriage = 'Vagnretur: \\r';
$strChange = 'Ändra';
$strCheckAll = 'Markera alla';
$strCheckDbPriv = 'Check Database Privileges'; //to translate
$strCheckTable = 'Kontrollera tabell';
$strColumn = 'Kolumn';
$strColumnEmpty = 'Kolumn-namnen är tomma!';
$strColumnNames = 'Kolumn-namn';
$strCompleteInserts = 'Kompletta infogningar';
$strConfirm = 'Vill du verkligen göra det?';
$strCopyTable = 'Kopiera tabellen till';
$strCopyTableOK = 'Tabellen %s har kopierats till %s.';
$strCreate = 'Skapa';
$strCreateNewDatabase = 'Skapa ny databas';
$strCreateNewTable = 'Skapa ny tabell i databas ';
$strCriteria = 'Kriterium';

$strData = 'Data';
$strDatabase = 'Databas ';
$strDatabases = 'databaser';
$strDatabasesStats = 'Databas-statistik';
$strDataOnly = 'Enbart data';
$strDbEmpty = 'Databasen är tom!';
$strDefault = 'Standard';
$strDelete = 'Radera';
$strDeleted = 'Raden har raderats';
$strDeletedRows = 'Raderade rader';
$strDeleteFailed = 'Raderingen misslyckades!';
$strDeletePassword = 'Radera lösenord';
$strDeleteUserMessage = 'Du har raderat användaren';
$strDelPassMessage = 'Du har raderat lösenordet för';
$strDescending = 'Fallande';
$strDisableMagicQuotes = '<b>Varning:</b> Du har aktiverat \'magic_quotes_gpc\' i din PHP-konfiguration. Den här versionen av PhpMyAdmin fungerar inte korrekt med det tillvalet. Läs i PHP-dokumentationen för att se hur du avaktiverar det.';
$strDisplay = 'Visa';
$strDisplayOrder = 'Visningsordning:';
$strDoAQuery = 'Utför en "query by example" (wildcard: "%")';
$strDocu = 'Dokumentation';
$strDoYouReally = 'Vill du verkligen ';
$strDrop = 'Radera';
$strDropDB = 'Radera databas ';
$strDropTable = 'Radera tabell';
$strDumpingData = 'Raderar data i tabell';
$strDynamic = 'dynamisk';

$strEdit = 'Ändra';
$strEditPrivileges = 'Ändra privilegier';
$strEffective = 'Effektivt';
$strEmpty = 'Töm';
$strEmptyResultSet = 'MySQL skickade tillbaka ett tomt resultat (dvs inga rader).';
$strEnableMagicQuotes = '<b>Varning:</b> Du har inte aktiverat \'magic_quotes_gpc\' i din PHP konfiguration. Den här versionen av PhpMyAdmin fungerar inte korrekt utan det. Läs i PHP-dokumentationen hur du aktiverar det.';
$strEnclosedBy = 'omges av';
$strEnd = 'Slut';
$strEnglishPrivileges = ' Viktigt: MySQL-privilegienamn anges på engelska ';
$strError = 'Fel';
$strEscapedBy = 'med specialtecken';
$strExtendedInserts = 'Utökade infogningar';
$strExtra = 'Extra';

$strField = 'Fält';
$strFields = 'Fält';
$strFieldsEmpty = ' Antalet fält är noll! ';
$strFixed = 'fast';
$strFormat = 'Format';
$strFormEmpty = 'Värde saknas i formuläret!';
$strFullText = 'Fullständiga texter';
$strFunction = 'Funktion';

$strGenTime = 'Skapad';
$strGo = 'Kör';
$strGrants = 'Grants'; //to translate
$strGzip = '"gzippad"';

$strHasBeenAltered = 'har ändrats.';
$strHasBeenCreated = 'har skapats.';
$strHasBeenDropped = 'har raderats.';
$strHasBeenEmptied = 'har tömts.';
$strHome = 'Hem';
$strHomepageOfficial = 'phpMyAdmin:s officiella hemsida';
$strHomepageSourceforge = 'phpMyAdmin Sourceforge-nedladdningssida';
$strHost = 'Värd';
$strHostEmpty = 'Värdnamnet är ej satt!';

$strIfYouWish = 'Om du vill ladda enbart några av tabellens kolumner, ange en kommaseparerad fältlista.';
$strIndex = 'Index';
$strIndexes = 'Index';
$strInsert = 'Infoga';
$strInsertAsNewRow = 'Lägg till som ny rad';
$strInsertedRows = 'Tillagda rader:';
$strInsertIntoTable = 'Infoga i tabell';
$strInsertNewRow = 'Infoga ny rad';
$strInsertTextfiles = 'Importera textfil till tabellen';
$strInstructions = 'Instruktioner';
$strInUse = 'används';
$strInvalidName = '"%s" är ett reserverat ord, du kan inte använda det som ett namn på en databas/tabell/fält.';

$strKeyname = 'Nyckel';;
$strKill = 'Döda';

$strLength = 'Längd';
$strLengthSet = 'Längd/Värden*';
$strLimitNumRows = 'rader per sida';
$strLineFeed = 'Radframmatning: \\n';
$strLines = 'Rader';
$strLocationTextfile = 'Textfilens plats';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Logga ut';

$strModifications = 'Ändringarna har sparats';
$strModify = 'Ändra';
$strMySQLReloaded = 'MySQL har laddats om.';
$strMySQLSaid = 'MySQL sa: ';
$strMySQLShowProcess = 'Visa processer';
$strMySQLShowStatus = 'Visa MySQL-körningsinformation';
$strMySQLShowVars = 'Visa MySQL:s systemvariabler';

$strName = 'Namn';
$strNbRecords = 'Antal rader';
$strNext = 'Nästa';
$strNo = 'Nej';
$strNoDatabases = 'Inga databaser';
$strNoDropDatabases = '"DROP DATABASE"-instruktioner är avstängda.';
$strNoModification = 'Ingen förändring';
$strNoPassword = 'Inget lösenord';
$strNoPrivileges = 'Inga privilegier';
$strNoRights = 'Du har inte nog med behörighet för att vara här!';
$strNoTablesFound = 'Fann inga tabeller i databasen.';
$strNotNumber = 'Det är inte ett nummer!';
$strNotValidNumber = ' är inte ett giltigt radnummer!';
$strNoUsersFound = 'Fann ingen användare.';
$strNull = 'Null';
$strNumberIndexes = ' Antal avancerade index ';

$strOftenQuotation = 'Ofta citattecken. Frivilligt betyder att bara \'char\' och \'varchar\' fälten omgivs av det angivna tecken.';
$strOptimizeTable = 'Optimera tabell';
$strOptionalControls = 'Frivilligt. Styr hur läsning och skrivning av specialtecken utförs.';
$strOptionally = 'Frivilligt';
$strOr = 'Eller';
$strOverhead = 'Overhead'; //to translate

$strPartialText = 'Avkortade texter';
$strPassword = 'Lösenord';
$strPasswordEmpty = 'Lösenordet är tomt!';
$strPasswordNotSame = 'Lösenorden är inte lika!';
$strPHPVersion = 'PHP-version';
$strPmaDocumentation = 'phpMyAdmin dokumentation';
$strPos1 = 'Börja';
$strPrevious = 'Föregående';
$strPrimary = 'Primär';
$strPrimaryKey = 'Primärnyckel';
$strPrinterFriendly = 'Utskriftsvänlig visning av tabellen ovan';
$strPrintView = 'Skriv ut ovanstående';
$strPrivileges = 'Privilegier';
$strProducedAnError = 'återgav ett fel.';
$strProperties = 'Inställningar';

$strQBE = 'Skapa fråga mha formulär (Query by Example)';
$strQBEDel = 'Ta bort';
$strQBEIns = 'Infoga';
$strQueryOnDb = 'SQL-query on database ';

$strReadTheDocs = 'Läs manualen';
$strRecords = 'Rader';
$strReloadFailed = 'Omladdning av MySQL misslyckades.';
$strReloadMySQL = 'Ladda om MySQL';
$strRememberReload = 'Kom ihåg att ladda om servern.';
$strRenameTable = 'Döp om tabellen till';
$strRenameTableOK = 'Tabell %s har döpts om till %s';
$strRepairTable = 'Reparera tabell';
$strReplace = 'Ersätt';
$strReplaceTable = 'Ersätt tabelldata med fil';
$strReset = 'Nollställ';
$strReType = 'Skriv om';
$strRevoke = 'Revoke'; //to translate
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for'; //to translate
$strRevokeMessage = 'You have revoked the privileges for'; //to translate
$strRevokePriv = 'Revoke Privileges'; //to translate
$strRowLength = 'Radlängd';
$strRows = 'Rader';
$strRowsFrom = 'rader med början från';
$strRowSize = ' Radstorlek ';
$strRowsStatistic = 'Radstatistik';
$strRunning = 'körs på ';
$strRunQuery = 'Kör fråga';

$strSave = 'Spara';
$strSelect = 'Välj';
$strSelectFields = 'Välj fält (minst ett):';
$strSelectNumRows = 'i fråga';
$strSend = 'Skicka';
$strSequence = 'Sekv.';
$strServerChoice = 'Serverval';
$strServerVersion = 'Serverversion';
$strSetEnumVal = 'Om en fälttyp är "enum" eller "set", var god ange värden enligt följande format: \'a\',\'b\',\'c\'...<br />Om du behöver lägga till ett bakåtstreck ("\") eller ett enkelcitat ("\'") i värdena, skriv ett bakåtstreck före tecknet (till exempel \'\\\\xyz\' eller \'a\\\'b\').';
$strShow = 'Visa';
$strShowingRecords = 'Visar rader ';
$strShowPHPInfo = 'Visa PHP-information';
$strShowThisQuery = ' Visa den här frågan igen ';
$strSingly = '(singly)'; //to translate
$strSize = 'Storlek';
$strSort = 'Sortering';
$strSpaceUsage = 'Utrymmesanvändning';
$strSQLQuery = 'SQL-fråga';
$strStatement = 'Uppgift';
$strStrucCSV = 'CSV-data';
$strStrucData = 'Struktur och data';
$strStrucDrop = 'Lägg till \'radera tabell\'';
$strStrucExcelCSV = 'CSV för Ms Excel-data';
$strStrucOnly = 'Enbart struktur';
$strSubmit = 'Sänd';
$strSuccess = 'Din SQL-fråga utfördes korrekt';
$strSum = 'Summa';

$strTable = 'tabell ';
$strTableComments = 'Tabellkommentarer';
$strTableEmpty = 'Tabellnamnet är tomt!';
$strTableMaintenance = 'Tabellunderhåll';
$strTables = '%s tabell(er)';
$strTableStructure = 'Tabellstruktur för tabell';
$strTableType = 'Tabelltyp';
$strTerminatedBy = 'avslutas med';
$strTextAreaLength = ' På grund av dess längd,<br /> kanske detta fält inte kan redigeras ';
$strTheContent = 'Filens innehåll har importerats.';
$strTheContents = 'Filens innehåll ersätter den valda tabellens rader som har identiska primära eller unika nycklar.';
$strTheTerminator = 'Fältavslutare.';
$strTotal = 'totalt';
$strType = 'Typ';

$strUncheckAll = 'Avmarkera alla';
$strUnique = 'Unik';
$strUpdatePassMessage = 'Du har uppdaterat lösenordet för';
$strUpdatePassword = 'Uppdatera lösenord';
$strUpdatePrivMessage = 'Du har uppdaterat privilegierna för';
$strUpdateQuery = 'Uppdatera fråga';
$strUsage = 'Användning';
$strUseBackquotes = 'Använd bakåtcitat i tabeller och fältnamn';
$strUser = 'Användare';
$strUserEmpty = 'Användarnamnet är tomt!';
$strUserName = 'Användarnamn';
$strUsers = 'Användare';
$strUseTables = 'Använd tabeller';

$strValue = 'Värde';
$strViewDump = 'Visa dump(-schema) av tabellen';
$strViewDumpDB = 'Visa dump(-schema) av databas';

$strWelcome = 'Välkommen till ';
$strWithChecked = 'Med markerade:';
$strWrongUser = 'Fel användarnamn/lösenord. Åtkomst nekad.';

$strYes = 'Ja';

// To translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strRunningAs = 'as';
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowTables = 'Show tables';
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strAnIndex = 'An index has been added on %s';//to translate
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strFieldsEnclosedBy = 'Fields enclosed by';//to translate
$strFieldsEscapedBy = 'Fields escaped by';//to translate
$strFieldsTerminatedBy = 'Fields terminated by';//to translate
$strIndexHasBeenDropped = 'Index %s has been dropped';//to translate
$strLinesTerminatedBy = 'Lines terminated by';//to translate
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strStartingRecord = 'Starting record';//to translate
$strTableHasBeenDropped = 'Table %s has been dropped';//to translate
$strTableHasBeenEmptied = 'Table %s has been emptied';//to translate
?>
