<?php
/* $Id$ */

$charset = 'iso-8859-1';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Søn', 'Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d. %B, %Y klokka %H:%M %p';


$strAccessDenied = 'Ingen tilgang';
$strAction = 'Handling';
$strAddDeleteColumn = 'Legg til/Slett kolonne';
$strAddDeleteRow = 'Legg til/Slett kriterie rad';
$strAddNewField = 'Legg til felt';
$strAddPriv = 'Legg til nytt privilegie';
$strAddPrivMessage = 'Du har lagt til ett nytt privilegie.';
$strAddSearchConditions = 'Legg til søke-kriterier (innhold i "where"-setningen):';
$strAddUser = 'Legg til en ny bruker';
$strAddUserMessage = 'Du har lagt til en ny bruker.';
$strAffectedRows = 'Berørte rader:';
$strAfter = 'Etter';
$strAll = 'Alle';
$strAlterOrderBy = 'Endre tabell rekkefølge ved';
$strAnalyzeTable = 'Analyser tabell';
$strAnd = 'Og';
$strAnIndex = 'En indeks har blitt lagt til %s';
$strAny = 'Alle';
$strAnyColumn = 'Alle kolonner';
$strAnyDatabase = 'Alle databaser';
$strAnyHost = 'Alle verter';
$strAnyTable = 'Alle tabeller';
$strAnyUser = 'Alle brukere';
$strAPrimaryKey = 'En primær-nøkkel har blitt lagt til %s';
$strAscending = 'Stigende';
$strAtBeginningOfTable = 'Ved begynnelsen av tabellen';
$strAtEndOfTable = 'Ved slutten av tabellen';
$strAttr = 'Attributter';

$strBack = 'Tilbake';
$strBinary = ' Binær ';
$strBinaryDoNotEdit = ' Binær - må ikke redigeres ';
$strBookmarkLabel = 'Navn';
$strBookmarkQuery = 'Lagret SQL-spørring';
$strBookmarkThis = 'Lagre denne SQL-spørringen';
$strBookmarkView = 'Bare se';
$strBrowse = 'Se på';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'kan ikke starte MySQL modulen,<br />vennligst kontroller PHP konfigureringen.';
$strCarriage = 'Linjeskift (Mac): \\r';
$strChange = 'Endre';
$strCheckAll = 'Kontroller alle';
$strCheckDbPriv = 'Kontroller database privilegier';
$strCheckTable = 'Kontroller tabell';
$strColumn = 'Kolonne';
$strColumnNames = 'Kolonnenavn';
$strCompleteInserts = 'Komplette innlegg';
$strConfirm = 'Ønsker du virkelig å gjøre det?';
$strCopyTable = 'Kopier tabell til (database<b>.</b>tabell):';
$strCopyTableOK = 'Tabellen %s er kopiert til %s.';
$strCreate = 'Opprett';
$strCreateNewDatabase = 'Opprett ny database';
$strCreateNewTable = 'Opprett ny tabell i database ';
$strCriteria = 'Kriterier';

$strData = 'Data';
$strDatabase = 'Database ';
$strDatabaseHasBeenDropped = 'Databasen %s har blitt slettet';
$strDatabases = 'databaser';
$strDatabasesStats = 'Statistikk for databaser';
$strDataOnly = 'Bare data';
$strDefault = 'Standard';
$strDelete = 'Slett';
$strDeleted = 'Raden er slettet';
$strDeletedRows = 'Slettede rader:';
$strDeleteFailed = 'Slettingen misslyktes!';
$strDeleteUserMessage = 'Du har slettet brukeren %s.';
$strDescending = 'Synkende';
$strDisplay = 'Vis';
$strDisplayOrder = 'Visningsrekkefølge:';
$strDoAQuery = 'Utfør en "query by example" (wildcard: "%")';
$strDocu = 'Dokumentasjon';
$strDoYouReally = 'Vil du virkelig ';
$strDrop = 'Slett';
$strDropDB = 'Slett databasen ';
$strDropTable = 'Slett tabell';
$strDumpingData = 'Data-ark for tabell';
$strDynamic = 'dynamisk';

$strEdit = 'Endre';
$strEditPrivileges = 'Rediger privilegier';
$strEffective = 'Effektiv';
$strEmpty = 'Tøm';
$strEmptyResultSet = 'MySQL returnerte tomt resultat (m.a.o. ingen rader).';
$strEnd = 'Slutt';
$strEnglishPrivileges = ' OBS: MySQL privilegie navn er på engelsk ';
$strError = 'Feil';
$strExtra = 'Ekstra';

$strField = 'Felt';
$strFieldHasBeenDropped = 'Feltet %s har blitt slettet';
$strFields = 'Felter';
$strFieldsEmpty = ' Antall felter er tommt! ';
$strFieldsEnclosedBy = 'Felter omsluttet av';
$strFieldsEscapedBy = 'Felter "escaped" med';
$strFieldsTerminatedBy = 'Felter avsluttet med';
$strFixed = 'statisk';
$strFormat = 'Format';
$strFormEmpty = 'Manglende verdi i skjemaet!';
$strFullText = 'Hele strenger';
$strFunction = 'Funksjon';

$strGenTime = 'Generert den';
$strGo = 'Utfør';
$strGrants = 'Rettigheter';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'er endret.';
$strHasBeenCreated = 'er opprettet.';
$strHome = 'Hjem';
$strHomepageOfficial = 'Offisiell phpMyAdmin Hjemmeside';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin nedlastingsside';
$strHost = 'Vert';
$strHostEmpty = 'Vertsnavnet er tomt!';

$strIdxFulltext = 'Fulltekst';
$strIfYouWish = 'Hvis du ønsker å kun lese inn enkelte av tabellens kolonner, angi en komme-separert felt-liste.';
$strIndex = 'Indeks';
$strIndexHasBeenDropped = 'Indeksen %s har blitt slettet';
$strIndexes = 'Indekser';
$strInsert = 'Sett inn';
$strInsertAsNewRow = 'Sett inn som ny rad';
$strInsertedRows = 'Innsatte rader:';
$strInsertNewRow = 'Sett inn ny rad';
$strInsertTextfiles = 'Les tekstfil inn i tabell';
$strInstructions = 'Instruksjoner';
$strInUse = 'i bruk';
$strInvalidName = '"%s" er ett reservert ord, du kan ikke bruke det som et database/tabell/felt navn.';

$strKeepPass = 'Ikke endre passordet';
$strKeyname = 'Nøkkel';
$strKill = 'Avslutt';

$strLength = 'Lengde';
$strLengthSet = 'Lengde/Sett*';
$strLimitNumRows = 'rader per side';
$strLineFeed = 'Linjeskift: \\n';
$strLines = 'Linjer';
$strLinesTerminatedBy = 'Linker avsluttet med';
$strLocationTextfile = 'Plassering av filen';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Logg ut';

$strModifications = 'Endringene er lagret';
$strModify = 'Endre';
$strMoveTable = 'Flytt tabell til (database<b>.</b>tabell):';
$strMoveTableOK = 'Tabellen %s har blitt flyttet til %s.';
$strMySQLReloaded = 'MySQL omstartet.';
$strMySQLSaid = 'MySQL sa: ';
$strMySQLShowProcess = 'Vis prosesser';
$strMySQLShowStatus = 'Vis MySQL runtime informasjon';
$strMySQLShowVars = 'Vis MySQL systemvariabler';

$strName = 'Navn';
$strNbRecords = 'antall poster';
$strNext = 'Neste';
$strNo = 'Nei';
$strNoDatabases = 'Ingen databaser';
$strNoDropDatabases = '"DROP DATABASE" uttrykk er avslått.';
$strNoModification = 'Ingen endring';
$strNoPassword = 'Intet passord';
$strNoPrivileges = 'Ingen privilegier';
$strNoQuery = 'Ingen SQL spxrring!';
$strNoRights = 'Du har ikke nok rettigheter til å være her nå!';
$strNoTablesFound = 'Ingen tabeller i databasen.';
$strNotNumber = 'Dette er ikke ett tall!';
$strNotValidNumber = ' er ikke et gyldig rad nummer!';
$strNoUsersFound = 'Ingen bruker(e) funnet.';
$strNull = 'Null';
$strNumberIndexes = ' Antall avanserte indekser ';

$strOftenQuotation = 'Ofte anførselstegn. Valgfritt innebærer at kun tekstfelter ("char" og "varchar") felter er omfattet av tegnet.';
$strOptimizeTable = 'Optimiser tabell';
$strOptionalControls = 'Valgfritt. Angir hvordan spesialtegn skrives eller leses.';
$strOptionally = 'Valgfritt';
$strOr = 'Eller';
$strOverhead = 'Overheng';

$strPartialText = 'Delvis tekst';
$strPassword = 'Passord';
$strPasswordEmpty = 'Passordet er blankt!';
$strPasswordNotSame = 'Passordene er ikke like!';
$strPHPVersion = 'PHP Versjon';
$strPmaDocumentation = 'phpMyAdmin Dokumentasjon';
$strPos1 = 'Start';
$strPrevious = 'Forrige';
$strPrimary = 'Primær';
$strPrimaryKey = 'Primærnøkkel';
$strPrimaryKeyHasBeenDropped = 'Primær-nøkkelen har blitt slettet';
$strPrintView = 'Forhåndsvisning';
$strPrivileges = 'Privilegier';
$strProperties = 'Egenskaper';

$strQBE = 'Spørring ved eksempel';
$strQBEDel = 'Slett';
$strQBEIns = 'Sett inn';
$strQueryOnDb = 'SQL-spørring i database ';

$strRecords = 'Rader';
$strReloadFailed = 'Omstart av MySQL feilet.';
$strReloadMySQL = 'Omstart av MySQL';
$strRememberReload = 'Husk å re-starte serveren.';
$strRenameTable = 'Endre tabellens navn';
$strRenameTableOK = 'Tabellen %s har fått nytt navn %s';
$strRepairTable = 'Reparer tabell';
$strReplace = 'Erstatt';
$strReplaceTable = 'Erstatt tabell med filen';
$strReset = 'Tøm skjema';
$strReType = 'Gjenta';
$strRevoke = 'Tilbakekall';
$strRevokeGrant = 'Tilbakekall Grant';
$strRevokeGrantMessage = 'Du har tilbakekalt Grant privileget til';
$strRevokeMessage = 'Du har tilbakekalt privilegiene til';
$strRevokePriv = 'Tibakekall privilegier';
$strRowLength = 'Rad lengde';
$strRows = 'Rader';
$strRowsFrom = 'rader fra';
$strRowSize = ' Rad størelse ';
$strRowsStatistic = 'Rad statistikk';
$strRunning = 'som kjører på ';
$strRunningAs = 'som';
$strRunQuery = 'Kjør spørring';
$strRunSQLQuery = 'Kjør SQL spørring/spørringer mot databasen %s';

$strSave = 'Lagre';
$strSelect = 'Spørring';
$strSelectFields = 'Velg felt (minst ett):';
$strSelectNumRows = 'i spørring';
$strSend = 'Kjør';
$strSequence = 'Rekkefølge';
$strServerChoice = 'Server valg';
$strServerVersion = 'Server versjon';
$strSetEnumVal = 'Hvis felttypen er "enum" eller "set", skriv inn verdien med dette formatet: \'a\',\'b\',\'c\'...<br />Hvis du skulle trenge å ha en skråstrek ("\") eller en enkel apostrof ("\'") blant disse verdiene, skriv en skråstrek foran (eks. \'\\\\xyz\' eller \'a\\\'b\').';
$strShow = 'Vis';
$strShowAll = 'Vis alle';
$strShowCols = 'Vis kolonner';
$strShowingRecords = 'Viser rader ';
$strShowPHPInfo = 'Vis PHP informasjon';
$strShowTables = 'Vis tabeller';
$strShowThisQuery = ' Vis denne spørring her igjen ';
$strSingly = '(enkeltvis)';
$strSize = 'Størelse';
$strSort = 'Sorter';
$strSpaceUsage = 'Plass bruk';
$strSQLQuery = 'SQL-spørring';
$strStartingRecord = 'Start post';
$strStatement = 'Oversikt';
$strStrucCSV = 'CSV data';
$strStrucData = 'Struktur og data';
$strStrucDrop = 'Legg til \'drop table\'';
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strStrucOnly = 'Kun struktur';
$strSubmit = 'Send';
$strSuccess = 'Kommandoen/spørringen er utført';
$strSum = 'Sum';

$strTable = 'tabell ';
$strTableComments = 'Tabell kommentarer';
$strTableEmpty = 'Tabellnavnet er tomt!';
$strTableHasBeenDropped = 'Tabellen %s har blitt slettet';
$strTableHasBeenEmptied = 'Tabellen %s har blitt tømt';
$strTableMaintenance = 'Tabell vedlikehold';
$strTables = '%s tabell(er)';
$strTableStructure = 'Tabell-struktur for tabell';
$strTableType = 'Tabell type';
$strTextAreaLength = ' På grunn av sin lengde,<br /> så vil muligens dette feltet ikke være redigerbar ';
$strTheContent = 'Innholdet av filen er lagt inn.';
$strTheContents = 'Innholdet av filen erstatter valgt tabell for rader med lik identifikator eller unikt felt';
$strTheTerminator = 'Tegn som angir slutt på felter.';
$strTotal = 'totalt';
$strType = 'Type';

$strUncheckAll = 'Fjern avmerking';
$strUnique = 'Unik';
$strUpdatePrivMessage = 'Du har oppdatert privilegiene til %s.';
$strUpdateProfile = 'Oppdater profil:';
$strUpdateProfileMessage = 'Profilen har blitt oppdatert.';
$strUpdateQuery = 'Oppdater spørring';
$strUsage = 'Bruk';
$strUseBackquotes = 'Bruk venstre anførselstegn med tabell og felt navn';
$strUser = 'Bruker';
$strUserEmpty = 'Brukernavnet er tomt!';
$strUserName = 'Brukernavn';
$strUsers = 'Brukere';
$strUseTables = 'Bruk tabeller';

$strValue = 'Verdi';
$strViewDump = 'Vis dump (skjema) av tabell';
$strViewDumpDB = 'Vis dump (skjema) av database';

$strWelcome = 'Velkommen til ';
$strWithChecked = 'Med avkrysset:';
$strWrongUser = 'Ugyldig brukernavn/passord. Ingen tilgang.';

$strYes = 'Ja';

$strZip = '"komprimert (zip)"';

// To translate
$strFlush = 'Flush';
$strTableHasBeenFlushed = 'Table %s has been flushed';
?>
