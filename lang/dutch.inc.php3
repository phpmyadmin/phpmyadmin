<?php
/* $Id$ */

$charset = 'iso-8859-1';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Toegang geweigerd ';
$strAction = 'Actie';
$strAddDeleteColumn = 'Toevoegen/Verwijderen Veld Kolommen';
$strAddDeleteRow = 'Toevoegen/Verwijderen Criteria Rij';
$strAddNewField = 'Nieuw veld toevoegen';
$strAddPriv = 'Voeg nieuwe rechten toe';
$strAddPrivMessage = 'U heeft nieuwe rechten toegevoegd.';
$strAddSearchConditions = 'Zoekcondities toevoegen (het "where" gedeelte van de query):';
$strAddUser = 'Voeg een nieuwe gebruiker toe';
$strAddUserMessage = 'U heeft een nieuwe gebruiker toegevoegd.';
$strAfter = 'Na';
$strAll = 'All'; //to translate
$strAlterOrderBy = 'Wijzig de tabel order bij';
$strAnalyzeTable = 'Analyseer tabel';
$strAnd = 'En';
$strAny = 'Any'; //to translate
$strAnyColumn = 'Een willekeurige kolom';
$strAnyDatabase = 'Een willekeurige database';
$strAnyHost = 'Een willekeurige host';
$strAnyTable = 'Een willekeurige tabel';
$strAnyUser = 'Een willekeurige gebruiker';
$strAscending = 'Oplopend';
$strAtBeginningOfTable = 'Aan het begin van de tabel';
$strAtEndOfTable = 'Aan het eind van de tabel';
$strAttr = 'Attributen';

$strBack = 'Terug';
$strBinary = ' Binary ';
$strBinaryDoNotEdit = ' Binary - niet aanpassen ';
$strBookmarkLabel = 'Label';
$strBookmarkQuery = 'Opgeslagen SQL-query';
$strBookmarkThis = 'Sla deze SQL-query op';
$strBookmarkView = 'Alleen bekijken';
$strBrowse = 'Verkennen';

$strCantLoadMySQL = 'kan de MySQL extensie niet laden,<br />controleer de PHP configuratie.';
$strCarriage = 'Carriage return: \\r';
$strChange = 'Veranderen';
$strCheckAll = 'Selecteer alles';
$strCheckDbPriv = 'Controleer database rechten';
$strCheckTable = 'Controleer tabel';
$strColumn = 'Kolom';
$strColumnEmpty = 'De kolommen zijn leeg!';
$strColumnNames = 'Kolom namen';
$strCompleteInserts = 'Complete inserts'; //to translate
$strConfirm = 'Weet u zeker dat u dit wilt?';
$strCopyTable = 'Kopieer tabel naar';
$strCopyTableOK = 'Tabel %s is gekopieerd naar %s.';
$strCreate = 'Aanmaken';
$strCreateNewDatabase = 'Nieuwe database aanmaken';
$strCreateNewTable = 'Nieuwe tabel aanmaken in database ';
$strCriteria = 'Criteria';

$strData = 'Data';
$strDatabase = 'Database ';
$strDatabases = 'databases';
$strDatabasesStats = 'Database statistieken';
$strDataOnly = 'Alleen data';
$strDbEmpty = 'De database naam is leeg!';
$strDefault = 'Standaardwaarde';
$strDelete = 'Verwijderen';
$strDeleted = 'De rij is verwijderd';
$strDeletedRows = 'Verwijder rijen:';
$strDeleteFailed = 'Verwijderen mislukt!';
$strDeletePassword = 'Verwijder het wachtwoord';
$strDeleteUserMessage = 'U heeft de gebruiker verwijderd';
$strDelPassMessage = 'Het heeft het wachtwoord verwijderd voor';
$strDescending = 'Aflopend';
$strDisableMagicQuotes = '<b>Waarschuwing:</b> U heeft magic_quotes_gpc ingeschakeld staan in uw PHP configuratie. Deze versie van PhpMyAdmin kan daar niet goed mee werken. Kijk in de configuratie sectie van de PHP handleiding voor informatie om het uit te schakelen.';
$strDisplay = 'Laat zien';
$strDisplayOrder = 'Weergave volgorde:';
$strDoAQuery = 'Voer een query op basis van een vergelijking uit (wildcard: "%")';
$strDocu = 'Documentatie';
$strDoYouReally = 'Weet u zeker dat u wilt ';
$strDrop = 'Verwijderen';
$strDropDB = 'Verwijder database ';
$strDropTable = 'Verwijder tabel';
$strDumpingData = 'Gegevens worden uitgevoerd voor tabel';
$strDynamic = 'dynamisch';

$strEdit = 'Wijzigen';
$strEditPrivileges = 'Wijzig rechten';
$strEffective = 'Effectief'; 
$strEmpty = 'Legen';
$strEmptyResultSet = 'MySQL retourneerde een lege result set (0 rijen).';
$strEnableMagicQuotes = '<b>Waarschuwing:</b> U heeft enabled magic_quotes_gpc niet ingeschakeld in uw PHP configuratie. PhpMyAdmin heeft dit nodig om goed te werken. Kijk in de configuratie sectie van de PHP handleiding voor informatie om het in te schakelen.';
$strEnclosedBy = 'omsloten met';
$strEnd = 'Einde';
$strEnglishPrivileges = ' Aantekening: de MySQL rechten namen zijn uitgelegd in het Engels ';
$strError = 'Fout';
$strEscapedBy = 'escaped by';
$strExtra = 'Extra';

$strField = 'Veld';
$strFields = 'Velden';
$strFieldsEmpty = ' Het velden aantal is leeg! ';
$strFixed = 'fixed'; //to translate vast(staand)
$strFormat = 'Formatteren';
$strFormEmpty = 'Er mist een waarde in het formulier !';
$strFullText = 'Volledige teksten';
$strFunction = 'Functie';

$strGenTime = 'Generatie Tijd';
$strGo = 'Start';
$strGrants = 'Grants'; //to translate

$strHasBeenAltered = 'is veranderd.';
$strHasBeenCreated = 'is aangemaakt.';
$strHasBeenDropped = 'is verwijderd.';
$strHasBeenEmptied = 'is leeggemaakt.';
$strHome = 'Home';
$strHomepageOfficial = 'Officiële phpMyAdmin Website';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Pagina';
$strHost = 'Host';
$strHostEmpty = 'De hostnaam is leeg!';

$strIfYouWish = 'Indien u slechts enkele van de tabelkolommen wilt laden, voer dan een door komma\'s gescheiden veldlijst in.';
$strIndex = 'Index';
$strIndexes = 'Indices'; // Indexen
$strInsert = 'Invoegen';
$strInsertAsNewRow = 'Voeg toe als nieuwe rij';
$strInsertedRows = 'Ingevoegde rijen:';
$strInsertIntoTable = 'Invoegen in tabel';
$strInsertNewRow = 'Nieuwe rij invoegen';
$strInsertTextfiles = 'Invoegen tekstbestanden in tabel';
$strInstructions = 'Instructies';
$strInUse = 'in gebruik';
$strInvalidName = '"%s" is een gereserveerd woord, je kan het niet gebruiken voor een database/tabel/veld naam.';

$strKeyname = 'Sleutelnaam';
$strKill = 'Kill'; //to translate stop?

$strLength = 'Lengte';
$strLengthSet = 'Lengte/Waardes*';
$strLimitNumRows = 'records per pagina';
$strLineFeed = 'Linefeed: \\n';
$strLines = 'Regels';
$strLocationTextfile = 'Locatie van het tekstbestand';
$strLogin = 'Inloggen'; //to translate, but its not in use ...
$strLogout = 'Uitloggen';

$strModifications = 'Wijzigingen opgeslagen.';
$strModify = 'Pas aan';
$strMySQLReloaded = 'MySQL opnieuw geladen.';
$strMySQLSaid = 'MySQL retourneerde: ';
$strMySQLShowProcess = 'Laat processen zien';
$strMySQLShowStatus = 'MySQL runtime informatie';
$strMySQLShowVars = 'MySQL systeemvariabelen';

$strName = 'Naam';
$strNbRecords = 'aantal records';
$strNext = 'Volgende';
$strNo = 'Nee';
$strNoDatabases = 'Geen databases';
$strNoModification = 'Geen verandering';
$strNoPassword = 'Geen wachtwoord';
$strNoPrivileges = 'Geen rechten';
$strNoRights = 'U heeft niet genoeg rechten om hier te zijn!';
$strNoTablesFound = 'Geen tabellen gevonden in de database.';
$strNotNumber = 'Dit is geen cijfer!';
$strNotValidNumber = ' geen geldig rijnummer!';
$strNoUsersFound = 'Geen gebruiker(s) gevonden.';
$strNull = 'Null';
$strNumberIndexes = ' Aantal geavanceerde indices ';

$strOftenQuotation = 'Meestal aanhalingstekens. OPTIONEEL betekent dat alleen char en varchar velden omsloten worden met het "omsloten met"-karakter.';
$strOptimizeTable = 'Optimaliseer tabel';
$strOptionalControls = 'Optioneel. Geeft aan hoe speciale karakters geschreven of gelezen moeten worden.'; // 'Optional. Controls how to write or read special characters.';
$strOptionally = 'OPTIONEEL';
$strOr = 'Of';
$strOverhead = 'Overhead';

$strPartialText = 'Gedeeltelijke teksten';
$strPassword = 'Wachtwoord';
$strPasswordEmpty = 'Het wachtwoord is leeg!';
$strPasswordNotSame = 'De wachtwoorden zijn niet gelijk!';
$strPHPVersion = 'PHP Versie';
$strPmaDocumentation = 'phpMyAdmin Documentatie';
$strPos1 = 'Begin';
$strPrevious = 'Vorige';
$strPrimary = 'Primaire sleutel';
$strPrimaryKey = 'Primaire sleutel';
$strPrinterFriendly = 'Printer vriendelijke versie van de tabel hierboven';
$strPrintView = 'Print beeld'; //THIS IS A BAD TRANSLATION SINCE THERE AIN'T REALLY A TRANSLATION FOR THAT IN DUTCH
$strPrivileges = 'Rechten';
$strProducedAnError = 'leverde een fout op.';
$strProperties = 'Eigenschappen';

$strQBE = 'Query opbouwen';
$strQBEDel = 'Verwijder';
$strQBEIns = 'Toevoegen';
$strQueryOnDb = 'SQL-query op database ';

$strReadTheDocs = 'Lees de documentatie';
$strRecords = 'Records';
$strReloadFailed = 'Opnieuw laden van MySQL mislukt.';
$strReloadMySQL = 'MySQL opnieuw laden.';
$strRememberReload = 'Vergeet niet de server opnieuw te starten.';
$strRenameTable = 'Tabel hernoemen naar';
$strRenameTableOK = 'Tabel %s is hernoemd naar %s';
$strRepairTable = 'Repareer tabel';
$strReplace = 'Vervangen';
$strReplaceTable = 'Vervang tabelgegevens met bestand';
$strReset = 'Reset';
$strReType = 'Type opnieuw';
$strRevoke = 'Maak ongedaan';
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for'; //to translate
$strRevokeMessage = 'U heeft de rechten ingetrokken voor';
$strRevokePriv = 'Trek rechten in';
$strRowLength = 'Rij lengte';
$strRows = 'Rijen';
$strRowsFrom = 'rijen beginnend bij';
$strRowSize = ' Rij grootte ';
$strRowsStatistic = 'Rij statistiek';
$strRunning = 'wordt uitgevoerd op ';
$strRunQuery = 'Query uitvoeren';

$strSave = 'Opslaan';
$strSelect = 'Selecteren';
$strSelectFields = 'Selecteer velden (tenminste 1):';
$strSelectNumRows = 'in query';
$strSend = 'verzenden';
$strSequence = 'Seq.'; //to translate
$strServerChoice = 'Server keuze';
$strServerVersion = 'Server versie';
$strSetEnumVal = 'Als het veldtype "enum" of "set" is, voer dan de waardes in volgens dit formaat: \'a\',\'b\',\'c\'...<br />Als u ooit een backslash moet plaatsen ("\") of een enkel aanhalingsteken ("\'") bij deze waardes, backslash het (voorbeeld \'\\\\xyz\' of \'a\\\'b\').';
$strShow = 'Laat zien';
$strShowingRecords = 'Records laten zien ';
$strShowPHPInfo = 'Laat PHP informatie zien';
$strShowThisQuery = ' Laat deze query hier weer zien ';
$strSingly = '(singly)'; //to translate
$strSize = 'Grootte';
$strSort = 'Sorteren';
$strSpaceUsage = 'Ruimte gebruik';
$strSQLQuery = 'SQL-query';
$strStatement = 'Statements'; //to translate
$strStrucCSV = 'CSV gegevens';
$strStrucData = 'Structuur en gegevens';
$strStrucDrop = '\'drop table\' toevoegen';
$strStrucExcelCSV = 'CSV voor MS Excel data';
$strStrucOnly = 'Alleen structuur';
$strSubmit = 'Verzenden';
$strSuccess = 'Uw SQL-query is succesvol uitgevoerd.';
$strSum = 'Som';

$strTable = 'Tabel ';
$strTableComments = 'Tabel opmerkingen';
$strTableEmpty = 'De tabel naam is leeg!';
$strTableMaintenance = 'Tabel onderhoud';
$strTables = '%s tabel(len)';
$strTableStructure = 'Tabel structuur voor tabel';
$strTableType = 'Tabel type';
$strTerminatedBy = 'eindigend op';
$strTextAreaLength = ' Vanwege z\'n lengte,<br /> is dit veld misschien niet te wijzigen ';
$strTheContent = 'De inhoud van uw bestand is ingevoegd.';
$strTheContents = 'De inhoud van het bestand vervangt de inhoud van de geselecteerde tabel voor rijen met een identieke primaire of unieke sleutel.';
$strTheTerminator = 'De afsluiter van de velden.';
$strTotal = 'totaal';
$strType = 'Type';

$strUncheckAll = 'Deselecteer alles';
$strUnique = 'Unieke waarde';
$strUpdatePassMessage = 'U heeft het wachtwoord gewijzigd voor';
$strUpdatePassword = 'Wijzig wachtwoord';
$strUpdatePrivMessage = 'U heeft de rechten gewijzigd voor';
$strUpdateQuery = 'Wijzig Query';
$strUsage = 'Gebruik';
$strUser = 'Gebruiker';
$strUserEmpty = 'De gebruikersnaam is leeg!';
$strUserName = 'Gebruikersnaam';
$strUsers = 'Gebruikers';
$strUseTables = 'Gebruik tabellen';

$strValue = 'Waarde';
$strViewDump = 'Bekijk een dump (schema) van tabel';
$strViewDumpDB = 'Bekijk een dump (schema) van database';

$strWelcome = 'Welkom op ';
$strWrongUser = 'Verkeerde gebruikersnaam/wachtwoord. Toegang geweigerd.';

$strYes = 'Ja';

// To translate
$strAffectedRows = 'Affected rows:'; //to translate
$strBzip = '"bzipped"'; //to translate
$strExtendedInserts = 'Extended inserts'; //to translate
$strGzip = '"gzipped"'; //to translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strNoDropDatabases = '"DROP DATABASE" statements zijn niet mogelijk.'; //to translate
$strRunningAs = 'as';
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowTables = 'Show tables';
$strUseBackquotes = 'Gebruik backquotes bij tabellen en velden\' namen'; // what are backquotes?
$strWithChecked = 'With checked:';
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
$strUpdateServer = 'Update server';//to translate
$strUpdateServMessage = 'You have updated the server for %s';//to translate
?>
