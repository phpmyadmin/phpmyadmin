<?php
/* $Id$ */

/**
 * Updated by "CaliMonk" <calimonk at gmx.net> on 2002/07/22 11:36.
 */

$charset = 'iso-8859-1';
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = ' %e %B %Y om %H:%M';

$strAccessDenied = 'Toegang geweigerd ';
$strAction = 'Actie';
$strAddDeleteColumn = 'Toevoegen/Verwijderen Veld Kolommen';
$strAddDeleteRow = 'Toevoegen/Verwijderen Criteria Rij';
$strAddNewField = 'Nieuw veld toevoegen';
$strAddPriv = 'Voeg nieuwe rechten toe';
$strAddPrivMessage = 'U heeft nieuwe rechten toegevoegd.';
$strAddSearchConditions = 'Zoekcondities toevoegen (het "where" gedeelte van de query):';
$strAddToIndex = 'Voeg &nbsp;%s&nbsp; kolom(men) toe aan index';
$strAddUser = 'Voeg een nieuwe gebruiker toe';
$strAddUserMessage = 'U heeft een nieuwe gebruiker toegevoegd.';
$strAffectedRows = 'Getroffen rijen:';
$strAfter = 'Na %s';
$strAfterInsertBack = 'Terug';
$strAfterInsertNewInsert = 'Voeg een nieuw record toe';
$strAll = 'Alle';
$strAllTableSameWidth = 'Alle tabellen weergeven met de zelfde breedte?';
$strAlterOrderBy = 'Wijzig de tabel order bij';
$strAnalyzeTable = 'Analyseer tabel';
$strAnd = 'En';
$strAnIndex = 'Een index is toegevoegd aan %s';
$strAny = 'Elke'; //! Willekeurige?
$strAnyColumn = 'Een willekeurige kolom';
$strAnyDatabase = 'Een willekeurige database';
$strAnyHost = 'Een willekeurige host';
$strAnyTable = 'Een willekeurige tabel';
$strAnyUser = 'Een willekeurige gebruiker';
$strAPrimaryKey = 'Een primaire sleutel is toegevoegd aan %s';
$strAscending = 'Oplopend';
$strAtBeginningOfTable = 'Aan het begin van de tabel';
$strAtEndOfTable = 'Aan het eind van de tabel';
$strAttr = 'Attributen';

$strBack = 'Terug';
$strBinary = ' Binair ';
$strBinaryDoNotEdit = ' Binair - niet aanpassen ';
$strBookmarkDeleted = 'De boekenlegger is verwijderd.';
$strBookmarkLabel = 'Label';
$strBookmarkQuery = 'Opgeslagen SQL-query';
$strBookmarkThis = 'Sla deze SQL-query op';
$strBookmarkView = 'Alleen bekijken';
$strBrowse = 'Verkennen';
$strBzip = '"ge-bzipt"';

$strCantLoadMySQL = 'kan de MySQL extensie niet laden,<br />controleer de PHP configuratie.';
$strCantLoadRecodeIconv = 'Kan iconv of recode extenties niet laden die nodig zijn voor de Karakterset conversie, configureer php om deze extensies toe te laten of schakel Karakterset conversie uit in phpMyAdmin';
$strCantRenameIdxToPrimary = 'Kan index niet naar PRIMARY hernoemen';
$strCantUseRecodeIconv = 'Kan iconv, libiconv en recode_string functies niet gebruiken zolang de extensies geladen moeten worden. Controleer de php configuratie.';
$strCardinality = 'Kardinaliteit';
$strCarriage = 'Harde return: \\r';
$strChange = 'Veranderen';
$strChangeDisplay = 'Kies weer te geven veld';
$strChangePassword = 'Wijzig paswoord';
$strCheckAll = 'Selecteer alles';
$strCheckDbPriv = 'Controleer database rechten';
$strCheckTable = 'Controleer tabel';
$strChoosePage = 'Kies een pagina om aan te passen';
$strColComFeat = 'Toon kolom commentaar';
$strColumn = 'Kolom';
$strColumnNames = 'Kolom namen';
$strComments = 'Commentaar';
$strCompleteInserts = 'Invoegen voltooid';
$strConfigFileError = 'phpMyAdmin kon het configuratie bestand niet lezen! <br />Dit kan gebeuren als php een parse error in dit bestand aantreft of dit bestand helemaal niet gevonden kan worden.<br />Roep het configuratie bestand direct aan met de snelkoppeling hieronder en lees de php foutmelding(en). In de meeste gevallen ontbreekt er ergens bijvoorbeeld een quote.<br /> Wanneer er een blanco pagina wordt weergegeven zijn er geen problemen.';
$strConfigureTableCoord = 'Configureer de coördinaten voor de tabel %s';
$strConfirm = 'Weet u zeker dat u dit wilt?';
$strCookiesRequired = 'Cookies moeten aan staan voorbij dit punt.';
$strCopyTable = 'Kopieer tabel naar (database<b>.</b>tabel):';
$strCopyTableOK = 'Tabel %s is gekopieerd naar %s.';
$strCreate = 'Aanmaken';
$strCreateIndex = 'Creëer een index op kolommen&nbsp;%s&nbsp;';
$strCreateIndexTopic = 'Creëer een nieuwe index';
$strCreateNewDatabase = 'Nieuwe database aanmaken';
$strCreateNewTable = 'Nieuwe tabel aanmaken in database %s';
$strCreatePage = 'Creëer een nieuwe pagina';
$strCreatePdfFeat = 'Aanmaken van PDF bestanden';
$strCriteria = 'Criteria';

$strData = 'Data';
$strDatabase = 'Database ';
$strDatabaseHasBeenDropped = 'Database %s is vervallen.';
$strDatabases = 'databases';
$strDatabasesStats = 'Database statistieken';
$strDatabaseWildcard = 'Database (wildcards toegestaan):';
$strDataOnly = 'Alleen data';
$strDefault = 'Standaardwaarde';
$strDelete = 'Verwijderen';
$strDeleted = 'De rij is verwijderd';
$strDeletedRows = 'Verwijder rijen:';
$strDeleteFailed = 'Verwijderen mislukt!';
$strDeleteUserMessage = 'U heeft gebruiker %s verwijderd.';
$strDescending = 'Aflopend';
$strDisabled = 'Uitgeschakeld';
$strDisplay = 'Laat zien';
$strDisplayFeat = 'Toon Opties';
$strDisplayOrder = 'Weergave volgorde:';
$strDisplayPDF = 'Geef het PDF schema weer';
$strDoAQuery = 'Voer een query op basis van een vergelijking uit (wildcard: "%")';
$strDocu = 'Documentatie';
$strDoYouReally = 'Weet u zeker dat u dit wilt ';
$strDrop = 'Verwijderen';
$strDropDB = 'Verwijder database %s';
$strDropTable = 'Verwijder tabel';
$strDumpingData = 'Gegevens worden uitgevoerd voor tabel';
$strDumpXRows = '%s rijen aan het dumpen, start bij rij %s.';
$strDynamic = 'dynamisch';

$strEdit = 'Wijzigen';
$strEditPDFPages = 'PDF Pagina\'s aanpassen';
$strEditPrivileges = 'Wijzig rechten';
$strEffective = 'Effectief';
$strEmpty = 'Legen';
$strEmptyResultSet = 'MySQL retourneerde een lege resultaat set (0 rijen).';
$strEnabled = 'Ingeschakeld';
$strEnd = 'Einde';
$strEnglishPrivileges = ' Aantekening: de MySQL rechten namen zijn uitgelegd in het Engels ';
$strError = 'Fout';
$strExport = 'Exporteer';
$strExportToXML = 'Exporteer naar XML formaat';
$strExtendedInserts = 'Uitgebreide invoegingen';
$strExtra = 'Extra';

$strField = 'Veld';
$strFieldHasBeenDropped = 'Veld %s is vervallen';
$strFields = 'Velden';
$strFieldsEmpty = ' Het velden aantal is leeg! ';
$strFieldsEnclosedBy = 'Velden ingesloten door';
$strFieldsEscapedBy = 'Velden ontsnapt door';
$strFieldsTerminatedBy = 'Velden beëindigd door';
$strFixed = 'vast';
$strFormat = 'Formatteren';
$strFormEmpty = 'Er mist een waarde in het formulier!';
$strFullText = 'Volledige teksten';
$strFunction = 'Functie';

$strGenBy = 'Gegenereerd door';
$strGeneralRelationFeat = 'Basis relatie opties';
$strGenTime = 'Generatie Tijd';
$strGo = 'Start';
$strGrants = 'Toekennen';
$strGzip = '"ge-gzipt"';

$strHasBeenAltered = 'is veranderd.';
$strHasBeenCreated = 'is aangemaakt.';
$strHaveToShow = 'Er moet ten minste 1 weer te geven kolom worden gekozen';
$strHome = 'Home';
$strHomepageOfficial = 'Officiële phpMyAdmin Website';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Pagina';
$strHost = 'Host';
$strHostEmpty = 'De hostnaam is leeg!';

$strIdxFulltext = 'Voltekst';
$strIfYouWish = 'Indien u slechts enkele van de tabelkolommen wilt laden, voer dan een door komma\'s gescheiden veldlijst in.';
$strIgnore = 'Negeer';
$strIndex = 'Index';
$strIndexes = 'Indices';
$strIndexHasBeenDropped = 'Index %s is vervallen';
$strIndexName = 'Index naam&nbsp;:';
$strIndexType = 'Index type&nbsp;:';
$strInsert = 'Invoegen';
$strInsertAsNewRow = 'Voeg toe als nieuwe rij';
$strInsertedRows = 'Ingevoegde rijen:';
$strInsertNewRow = 'Nieuwe rij invoegen';
$strInsertTextfiles = 'Invoegen tekstbestanden in tabel';
$strInstructions = 'Instructies';
$strInUse = 'in gebruik';
$strInvalidName = '"%s" is een gereserveerd woord, je kunt het niet gebruiken voor een database/tabel/veld naam.';

$strKeepPass = 'Wijzig het paswoord niet';
$strKeyname = 'Sleutelnaam';
$strKill = 'stop proces';

$strLength = 'Lengte';
$strLengthSet = 'Lengte/Waardes*';
$strLimitNumRows = 'records per pagina';
$strLineFeed = 'Regelopschuiving: \\n';
$strLines = 'Regels';
$strLinesTerminatedBy = 'Regels beëindigd door';
$strLinkNotFound = 'Link niet gevonden';
$strLinksTo = 'Gelinked naar';
$strLocationTextfile = 'Locatie van het tekstbestand';
$strLogin = 'Inloggen';
$strLogout = 'Uitloggen';
$strLogPassword = 'Paswoord:';
$strLogUsername = 'Gebruikers naam:';

$strMissingBracket = 'Er ontbreekt een bracket';
$strModifications = 'Wijzigingen opgeslagen.';
$strModify = 'Pas aan';
$strModifyIndexTopic = 'Wijzig een index';
$strMoveTable = 'Verplaats tabel naar (database<b>.</b>tabel):';
$strMoveTableOK = 'Tabel %s is verplaatst naar %s.';
$strMySQLCharset = 'MySQL Karakterset';
$strMySQLReloaded = 'MySQL opnieuw geladen.';
$strMySQLSaid = 'MySQL retourneerde: ';
$strMySQLServerProcess = 'MySQL %pma_s1% draait op %pma_s2% als %pma_s3%';
$strMySQLShowProcess = 'Laat processen zien';
$strMySQLShowStatus = 'MySQL runtime informatie';
$strMySQLShowVars = 'MySQL systeemvariabelen';

$strName = 'Naam';
$strNext = 'Volgende';
$strNo = 'Nee';
$strNoDatabases = 'Geen databases';
$strNoDescription = 'Geen beschrijving aanwezig';
$strNoDropDatabases = '"DROP DATABASE" opdrachten zijn niet mogelijk.';
$strNoFrames = 'phpMyAdmin is vriendelijker met een browser die <b>frames</b> aan kan.';
$strNoIndex = 'Geen index gedefinieerd!';
$strNoIndexPartsDefined = 'Geen index delen gedefinieerd!';
$strNoModification = 'Geen verandering';
$strNone = 'Geen';
$strNoPassword = 'Geen wachtwoord';
$strNoPhp = 'zonder PHP Code';
$strNoPrivileges = 'Geen rechten';
$strNoQuery = 'Geen SQL query!';
$strNoRights = 'U heeft niet genoeg rechten om hier te zijn!';
$strNoTablesFound = 'Geen tabellen gevonden in de database.';
$strNotNumber = 'Dit is geen cijfer!';
$strNotOK = 'Niet Goed';
$strNotSet = '<b>%s</b> tabel niet gevonden of niet ingesteld in %s';
$strNotValidNumber = ' geen geldig rijnummer!';
$strNoUsersFound = 'Geen gebruiker(s) gevonden.';
$strNull = 'Null';
$strNumSearchResultsInTable = '%s overeenkomst(en) in de tabel<i>%s</i>';
$strNumSearchResultsTotal = '<b>Totaal:</b> <i>%s</i> overeenkomst(en)';

$strOftenQuotation = 'Meestal aanhalingstekens. OPTIONEEL betekent dat alleen char en varchar velden omsloten worden met het "omsloten met"-karakter.';
$strOK = 'Goed';
$strOperations = 'Handelingen';
$strOptimizeTable = 'Optimaliseer tabel';
$strOptionalControls = 'Optioneel. Geeft aan hoe speciale karakters geschreven of gelezen moeten worden.'; // 'Optional. Controls how to write or read special characters.';
$strOptionally = 'OPTIONEEL';
$strOptions = 'Opties';
$strOr = 'Of';
$strOverhead = 'Overhead';

$strPageNumber = 'Pagina nummer:';
$strPartialText = 'Gedeeltelijke teksten';
$strPassword = 'Wachtwoord';
$strPasswordEmpty = 'Het wachtwoord is leeg!';
$strPasswordNotSame = 'De wachtwoorden zijn niet gelijk!';
$strPdfDbSchema = 'Schema van de "%s" database - Pagina %s';
$strPdfInvalidPageNum = 'Ongedefinieerde PDF pagina nummer!';
$strPdfInvalidTblName = 'De tabel "%s" bestaat niet!';
$strPdfNoTables = 'Geen Tabellen';
$strPhp = 'Creëer PHP Code';
$strPHPVersion = 'PHP Versie';
$strPmaDocumentation = 'phpMyAdmin Documentatie';
$strPmaUriError = 'De <tt>$cfg[\'PmaAbsoluteUri\']</tt> richtlijn MOET gezet zijn in het configuratie bestand!';
$strPos1 = 'Begin';
$strPrevious = 'Vorige';
$strPrimary = 'Primaire sleutel';
$strPrimaryKey = 'Primaire sleutel';
$strPrimaryKeyHasBeenDropped = 'De primaire sleutel is vervallen';
$strPrimaryKeyName = 'De naam van de primaire sleutel moet PRIMARY zijn!';
$strPrimaryKeyWarning = '("PRIMARY" <b>moet</b> de naam van en <b>alleen van</b> een primaire sleutel zijn!)';
$strPrintView = 'Printopmaak';
$strPrivileges = 'Rechten';
$strProperties = 'Eigenschappen';

$strQBE = 'Query opbouwen';
$strQBEDel = 'Verwijder';
$strQBEIns = 'Toevoegen';
$strQueryOnDb = 'SQL-query op database <b>%s</b>:';

$strRecords = 'Records';
$strReferentialIntegrity = 'Controleer referentiële integriteit';
$strRelationNotWorking = 'Extra opties om met tabellen te werken die gelinked zijn, zijn uitgeschakeld. Om te weten te komen waarom klik %shier%s.';
$strRelationView = 'Relatie overzicht';
$strReloadFailed = 'Opnieuw laden van MySQL mislukt.';
$strReloadMySQL = 'MySQL opnieuw laden.';
$strRememberReload = 'Vergeet niet de server opnieuw te starten.';
$strRenameTable = 'Tabel hernoemen naar';
$strRenameTableOK = 'Tabel %s is hernoemt naar %s';
$strRepairTable = 'Repareer tabel';
$strReplace = 'Vervangen';
$strReplaceTable = 'Vervang tabelgegevens met bestand';
$strReset = 'Opnieuw';
$strReType = 'Type opnieuw';
$strRevoke = 'Ongedaan maken';
$strRevokeGrant = 'Trek Grant recht in';
$strRevokeGrantMessage = 'U heeft het Grant recht ingetrokken voor %s';
$strRevokeMessage = 'U heeft de rechten ingetrokken voor %s';
$strRevokePriv = 'Trek rechten in';
$strRowLength = 'Rij lengte';
$strRows = 'Rijen';
$strRowsFrom = 'rijen beginnend bij';
$strRowSize = ' Rij grootte ';
$strRowsModeHorizontal = 'horizontaal';
$strRowsModeOptions = 'in %s modus en herhaal kopregels na %s cellen';
$strRowsModeVertical = 'verticaal';
$strRowsStatistic = 'Rij statistiek';
$strRunning = 'wordt uitgevoerd op %s';
$strRunQuery = 'Query uitvoeren';
$strRunSQLQuery = 'Draai SQL query/queries op database %s';

$strSave = 'Opslaan';
$strScaleFactorSmall = 'De schaal factor is te klein om het schema op een pagina te zetten';
$strSearch = 'Zoeken';
$strSearchFormTitle = 'Zoeken in de database';
$strSearchInTables = 'In de tabel(len):';
$strSearchNeedle = 'Woord(en) of waarde(s) waarnaar gezocht moet worden (wildcard: "%"):';
$strSearchOption1 = 'ten minste een van de woorden';
$strSearchOption2 = 'alle woorden';
$strSearchOption3 = 'de exacte zin';
$strSearchOption4 = 'als een reguliere expressie';
$strSearchResultsFor = 'Zoek resultaten voor "<i>%s</i>" %s:';
$strSearchType = 'Vind:';
$strSelect = 'Selecteren';
$strSelectADb = 'Selecteer A.U.B. een database';
$strSelectAll = 'Selecteer alles';
$strSelectFields = 'Selecteer velden (tenminste 1):';
$strSelectNumRows = 'in query';
$strSelectTables = 'Selecteer tabellen';
$strSend = 'verzenden';
$strServerChoice = 'Server keuze';
$strServerVersion = 'Server versie';
$strSetEnumVal = 'Als het veldtype "enum" of "set" is, voer dan de waardes in volgens dit formaat: \'a\',\'b\',\'c\'...<br />Als u ooit een backslash moet plaatsen ("\") of een enkel aanhalingsteken ("\'") bij deze waardes, backslash het (voorbeeld \'\\\\xyz\' of \'a\\\'b\').';
$strShow = 'Toon';
$strShowAll = 'Toon alles';
$strShowColor = 'Toon kleur';
$strShowCols = 'Toon kolommen';
$strShowGrid = 'Toon grid';
$strShowingRecords = 'Toon Records';
$strShowPHPInfo = 'Laat PHP informatie zien';
$strShowTableDimension = 'Geef de dimensies weer van de tabellen';
$strShowTables = 'Toon tabellen';
$strShowThisQuery = ' Laat deze query hier weer zien ';
$strSingly = '(apart)';
$strSize = 'Grootte';
$strSort = 'Sorteren';
$strSpaceUsage = 'Ruimte gebruik';
$strSplitWordsWithSpace = 'Woorden worden gesplit door een spatie karakter (" ").';
$strSQL = 'SQL';
$strSQLQuery = 'SQL-query';
$strSQLResult = 'SQL resultaat';
$strStatement = 'Opdrachten';
$strStrucCSV = 'CSV gegevens';
$strStrucData = 'Structuur en gegevens';
$strStrucDrop = '\'drop table\' toevoegen';
$strStrucExcelCSV = 'CSV voor MS Excel data';
$strStrucOnly = 'Alleen structuur';
$strStructPropose = 'Tabel structuur voorstellen';
$strStructure = 'Structuur';
$strSubmit = 'Verzenden';
$strSuccess = 'Uw SQL-query is succesvol uitgevoerd.';
$strSum = 'Som';

$strTable = 'Schoon de tabel ("FLUSH")';
$strTable = 'Tabel ';
$strTableComments = 'Tabel opmerkingen';
$strTableEmpty = 'De tabel naam is leeg!';
$strTableHasBeenDropped = 'Tabel %s is vervallen';
$strTableHasBeenEmptied = 'Tabel %s is leeg gemaakt';
$strTableHasBeenFlushed = 'Tabel %s is geschoond';
$strTableMaintenance = 'Tabel onderhoud';
$strTables = '%s tabel(len)';
$strTableStructure = 'Tabel structuur voor tabel';
$strTableType = 'Tabel type';
$strTextAreaLength = ' Vanwege z\'n lengte,<br /> is dit veld misschien niet te wijzigen ';
$strTheContent = 'De inhoud van uw bestand is ingevoegd.';
$strTheContents = 'De inhoud van het bestand vervangt de inhoud van de geselecteerde tabel voor rijen met een identieke primaire of unieke sleutel.';
$strTheTerminator = 'De afsluiter van de velden.';
$strTotal = 'totaal';
$strType = 'Type';

$strUncheckAll = 'Deselecteer alles';
$strUnique = 'Unieke waarde';
$strUnselectAll = 'Deselecteer alles';
$strUpdatePrivMessage = 'U heeft de rechten aangepast voor %s.';
$strUpdateProfile = 'Pas profiel aan:';
$strUpdateProfileMessage = 'Het profiel is aangepast.';
$strUpdateQuery = 'Wijzig Query';
$strUsage = 'Gebruik';
$strUseBackquotes = 'Gebruik backquotes (`) bij tabellen en velden\' namen';
$strUser = 'Gebruiker';
$strUserEmpty = 'De gebruikersnaam is leeg!';
$strUserName = 'Gebruikersnaam';
$strUsers = 'Gebruikers';
$strUseTables = 'Gebruik tabellen';

$strValue = 'Waarde';
$strViewDump = 'Bekijk een dump (schema) van tabel';
$strViewDumpDB = 'Bekijk een dump (schema) van database';

$strWelcome = 'Welkom op %s';
$strWithChecked = 'Met geselecteerd:';
$strWrongUser = 'Verkeerde gebruikersnaam/wachtwoord. Toegang geweigerd.';

$strYes = 'Ja';

$strZip = '"Gezipt"';

$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCharsetOfFile = 'Character set of the file:'; //to translate

$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'Explain SQL';  //to translate

$strNoExplain = 'Skip Explain SQL';  //to translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate

$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate

$strValidateSQL = 'Validate SQL';  //to translate

?>
