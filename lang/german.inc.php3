<?php
/* $Id$ */

$charset = 'iso-8859-1';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
$month = array('Jan', 'Feb', 'März', 'April', 'Mai', 'Juni', 'Juli', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d. %B %Y um %H:%M';


$strAccessDenied = 'Zugriff verweigert.';
$strAction = 'Aktion';
$strAddDeleteColumn = 'Spalten hinzufügen/entfernen';
$strAddDeleteRow = 'Zeilen hinzufügen/entfernen';
$strAddNewField = 'Neue Feld(er) hinzufügen';
$strAddPriv = 'Rechte hinzufügen';
$strAddPrivMessage = 'Rechte wurden hinzugefügt.';
$strAddSearchConditions = 'Suchkondition (Argumente für das WHERE-Statement):';
$strAddUser = 'Neuen Benutzer hinzufügen';
$strAddUserMessage = 'Der Benutzer wurde hinzugefügt.';
$strAffectedRows = ' Betroffene Datensätze: ';
$strAfter = 'Nach';
$strAll = 'Alle';
$strAlterOrderBy = 'Tabelle sortieren nach';
$strAnalyzeTable = 'Analysiere Tabelle';
$strAnd = 'und';
$strAny = 'Jeder';
$strAnyColumn = 'Jede Spalte';
$strAnyDatabase = 'Jede Datenbank';
$strAnyHost = 'Jeder Host';
$strAnyTable = 'Jede Tabelle';
$strAnyUser = 'Jeder Benutzer';
$strAscending = 'aufsteigend';
$strAtBeginningOfTable = 'An den Anfang der Tabelle';
$strAtEndOfTable = 'An das Ende der Tabelle';
$strAttr = 'Attribute';

$strBack = 'Zurück';
$strBinary = ' Binär ';
$strBinaryDoNotEdit = ' Binär - nicht editierbar !';
$strBookmarkLabel = 'Titel';
$strBookmarkQuery = 'Gespeicherte SQL-Abfrage';
$strBookmarkThis = 'SQL-Abfrage speichern';
$strBookmarkView = 'Nur zeigen';
$strBrowse = 'Anzeigen';
$strBzip = '"BZip komprimiert"';

$strCantLoadMySQL = 'MySQL Erweiterung konnte nicht geladen werden,<br />bitte PHP Konfiguration überprüfen.';
$strCarriage = 'Wagenrücklauf \\r';
$strChange = 'Ändern';
$strCheckAll = 'Alle auswählen';
$strCheckDbPriv = 'Rechte einer Datenbank prüfen';
$strCheckTable = 'Überprüfe Tabelle';
$strColumn = 'Spalte';
$strColumnEmpty = 'Die Spalten-Titel sind leer!';
$strColumnNames = 'Spaltennamen';
$strCompleteInserts = 'Vollständige \'INSERT\'s';
$strConfirm = 'Bist du dir wirklich sicher?';
$strCopyTable = 'Kopiere Tabelle nach (Datenbank<b>.</b>Tabellenname):';
$strCopyTableOK = 'Tabelle %s wurde kopiert nach %s.';
$strCreate = 'Erzeugen';
$strCreateNewDatabase = 'Neue Datenbank erzeugen';
$strCreateNewTable = 'Neue Tabelle erstellen in Datenbank ';
$strCriteria = 'Kriterium';

$strData = 'Daten';
$strDatabase = 'Datenbank ';
$strDatabaseHasBeenDropped = 'Datenbank %s wurde gelöscht.';
$strDatabases = 'Datenbanken';
$strDatabasesStats = 'Statistiken über alle Datenbanken';
$strDataOnly = 'Nur Daten';
$strDbEmpty = 'Der Name der Datenbank ist leer!';
$strDefault = 'Standard';
$strDelete = 'Löschen';
$strDeleted = 'Die Zeile wurde gelöscht.';
$strDeletedRows = 'Gelöschte Zeilen:';
$strDeleteFailed = 'Löschen fehlgeschlagen!';
$strDeleteUserMessage = 'Der Benutzer wurde gelöscht %s.';
$strDescending = 'absteigend';
$strDisplay = 'Zeige';
$strDisplayOrder = 'Sortierung nach:';
$strDoAQuery = 'Suche über Beispielwerte ("query by example") (Platzhalter: "%")';
$strDocu = 'Dokumentation';
$strDoYouReally = 'Möchten Sie wirklich diese Abfrage ausführen: ';
$strDrop = 'Löschen';
$strDropDB = 'Datenbank löschen:';
$strDropTable = 'Tabelle löschen:';
$strDumpingData = 'Daten für Tabelle';
$strDynamic = 'dynamisch';

$strEdit = 'Ändern';
$strEditPrivileges = 'Rechte ändern';
$strEffective = 'Effektiv';
$strEmpty = 'Leeren';
$strEmptyResultSet = 'MySQL lieferte ein leeres Resultset zurück (d.h. null Zeilen).';
$strEnd = 'Ende';
$strEnglishPrivileges = ' Anmerkung: MySQL Rechtename werden in Englisch angegeben ';
$strError = 'Fehler';
$strExtendedInserts = 'Erweiterte \'INSERT\'s';
$strExtra = 'Extra';

$strField = 'Feld';
$strFields = 'Felder';
$strFieldsEmpty = ' Sie müssen angeben wieviele Felder die Tabelle haben soll! ';
$strFieldsEnclosedBy = 'Felder eingeschlossen von';
$strFieldsEscapedBy = 'Felder escaped von';
$strFieldsTerminatedBy = 'Felder getrennt mit';
$strFixed = 'starr';
$strFormat = 'Format';
$strFormEmpty = 'Das Formular ist leer !';
$strFullText = 'vollständige Textfelder';
$strFunction = 'Funktion';

$strGenTime = 'Erstellungszeit';
$strGo = 'OK';
$strGrants = 'Rechte';
$strGzip = '"GZip komprimiert"';

$strHasBeenAltered = 'wurde geändert.';
$strHasBeenCreated = 'wurde erzeugt.';
$strHome = 'Home';
$strHomepageOfficial = ' Offizielle phpMyAdmin Homepage ';
$strHomepageSourceforge = ' Sourceforge phpMyAdmin Download Homepage ';
$strHost = 'Host';
$strHostEmpty = 'Es wurde kein Host angegeben!';

$strIdxFulltext = 'Volltext';
$strIfYouWish = 'Wenn Sie nur bestimmte Spalten importieren möchten, geben Sie diese bitte hier an.';
$strIndex = 'Index';
$strIndexes = 'Indizes';
$strInsert = 'Einfügen';
$strInsertAsNewRow = ' Als neuen Datensatz speichern ';
$strInsertedRows = 'Eingefügte Zeilen:';
$strInsertIntoTable = 'In Tabelle einfügen';
$strInsertNewRow = 'Neue Zeile einfügen';
$strInsertTextfiles = 'Textdatei in Tabelle einfügen';
$strInstructions = 'Befehle';
$strInUse = 'in Benutzung';
$strInvalidName = '"%s" ist ein reserviertes Wort, welches nicht als Datenbank-, Feld- oder Tabellenname verwendet werden darf.';

$strKeyname = 'Name';
$strKill = 'Beenden';

$strLength = ' Länge ';
$strLengthSet = 'Länge/Set*';
$strLimitNumRows = 'Einträge pro Seite';
$strLineFeed = 'Zeilenvorschub: \\n';
$strLines = 'Zeilen';
$strLinesTerminatedBy = 'Zeilen getrennt mit';
$strLocationTextfile = 'Datei';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Neu einloggen';

$strModifications = 'Änderungen gespeichert.';
$strModify = 'Verändern';
$strMoveTable = 'Verschiebe Tabelle nach (Datenbank<b>.</b>Tabellenname):';
$strMoveTableOK = 'Tabelle %s wurde nach %s verschoben.';
$strMySQLReloaded = 'MySQL neu gestartet.';
$strMySQLSaid = 'MySQL meldet: ';
$strMySQLShowProcess = 'Prozesse anzeigen';
$strMySQLShowStatus = 'MySQL-Laufzeit-Informationen anzeigen';
$strMySQLShowVars = 'MySQL-System-Variablen anzeigen';

$strName = 'Name';
$strNbRecords = 'Datensätze';
$strNext = 'Nächste';
$strNo = 'Nein';
$strNoDatabases = 'Keine Datenbanken';
$strNoDropDatabases = '"DROP DATABASE" Anweisung wurde deaktiviert.';
$strNoModification = 'Keine Änderung';
$strNoPassword = 'Kein Password';
$strNoPrivileges = 'Keine Rechte';
$strNoQuery = 'Kein SQL-Befehl!';
$strNoRights = 'Du hast nicht genug Rechte um fortzufahren!';
$strNoTablesFound = 'Keine Tabellen in der Datenbank gefunden.';
$strNotNumber = 'Das ist keine Zahl!';
$strNotValidNumber = ' ist keine gültige Zeilennummer!';
$strNoUsersFound = 'Keine(n) Benutzer gefunden.';
$strNull = 'Null';
$strNumberIndexes = ' Anzahl der erweiterten Indizes ';

$strOftenQuotation = 'Häufig Anführungszeichen. Optional bedeutet, daß nur Textfelder von den angegeben Zeichen eingeschlossen sind.';
$strOptimizeTable = 'Optimiere Tabelle';
$strOptionalControls = 'Optional. Bestimmt, wie Sonderzeichen kenntlich gemacht werden.';
$strOptionally = 'optional';
$strOr = 'Oder';
$strOverhead = 'Überhang';

$strPartialText = 'gekürzte Textfelder';
$strPassword = 'Password';
$strPasswordEmpty = 'Es wurde kein Passwort angegeben!';
$strPasswordNotSame = 'Die eingegebenen Passwörter sind nicht identisch!';
$strPmaDocumentation = 'phpMyAdmin Dokumentation';
$strPHPVersion = 'PHP Version';
$strPos1 = 'Anfang';
$strPrevious = 'Vorherige';
$strPrimary = 'Primärschlüssel';
$strPrimaryKey = 'Primärschlüssel';
$strPrinterFriendly = 'Druckerfreundliche Version der Tabelle';
$strPrintView = 'Druckansicht';
$strPrivileges = 'Rechte';
$strProducedAnError = 'erzeugte einen Fehler.';
$strProperties = 'Eigenschaften';

$strQBE = 'Suche über Beispielwerte';
$strQBEDel = 'Entf.';
$strQBEIns = 'Einf.';
$strQueryOnDb = ' SQL-Befehl in der Datenbank ';

$strReadTheDocs = 'MySQL-Dokumentation zu LOAD DATA INFILE lesen';
$strRecords = 'Einträge';
$strReloadFailed = 'MySQL Neuladen fehlgeschlagen.';
$strReloadMySQL = 'MySQL neu starten';
$strRememberReload = 'Der Server muss neugestartet werden.';
$strRenameTable = 'Tabelle umbennen in';
$strRenameTableOK = 'Tabelle %s wurde umbenannt in %s.';
$strRepairTable = 'Repariere Tabelle';
$strReplace = 'Ersetzen';
$strReplaceTable = 'Tabelleninhalt ersetzen';
$strReset = 'Zurücksetzen';
$strReType = 'Wiederholen';
$strRevoke = 'Entfernen';
$strRevokeGrant = '\'Grant\' entfernen';
$strRevokeGrantMessage = 'Du hast das Recht \'Grant\' entfernt für';
$strRevokeMessage = 'Du hast die Rechte entfernt für';
$strRevokePriv = 'Rechte entfernen';
$strRowLength = 'Zeilenlänge';
$strRows = 'Zeilen';
$strRowsFrom = 'Datensätze, beginnend ab';
$strRowSize = 'Zeilengröße';
$strRowsStatistic = 'Zeilenstatistik';
$strRunning = 'auf ';
$strRunQuery = 'SQL Befehl ausführen';

$strSave = 'Speichern';
$strSelect = 'Teilw. anzeigen';
$strSelectFields = 'Felder auswählen (mind. eines):';
$strSelectNumRows = 'in der Abfrage';
$strSend = 'Senden';
$strSequence = ' Sequenz ';
$strServerChoice = 'Server Auswählen';
$strServerVersion = 'Server Version';
$strSetEnumVal = 'Wenn das Feld vom Type \'ENUM\' oder \'SET\' ist, benutzen Sie das Format: \'a\',\'b\',\'c\',....<br />Wann immer Sie ein Backslash ("\") oder ein einfaches Anführungszeichen ("\'") verwenden,<br \>setzen Sie bitte ein Backslash vor das Zeichen.  (z.B.: \'\\\\xyz\' or \'a\\\'b\').';
$strShow = 'Zeige';
$strShowAll = 'Alles anzeigen';
$strShowingRecords = 'Zeige Datensätze ';
$strShowPHPInfo = 'PHP Informationen anzeigen';
$strShowThisQuery = 'SQL Befehl hier wieder anzeigen';
$strSingly = '(einmalig)';
$strSize = 'Größe';
$strSort = 'Sortierung';
$strSpaceUsage = 'Speicherplatzverbrauch';
$strSQLQuery = 'SQL-Befehl';
$strStartingRecord = 'Anfangszeile';
$strStatement = 'Angaben';
$strStrucCSV = 'CSV-Daten';
$strStrucData = 'Struktur und Daten';
$strStrucDrop = 'Mit \'DROP TABLE\'';
$strStrucExcelCSV = 'CSV-Daten für MS Excel';
$strStrucOnly = 'Nur Struktur';
$strSubmit = 'Abschicken';
$strSuccess = 'Ihr SQL-Befehl wurde erfolgreich ausgeführt.';
$strSum = 'Summe';

$strTable = 'Tabelle ';
$strTableComments = 'Tabellen-Kommentar';
$strTableEmpty = 'Der Tabellenname ist leer!';
$strTableHasBeenDropped = 'Tabelle %s wurde gelöscht';
$strTableHasBeenEmptied = 'Tabelle %s wurde geleert';
$strTableMaintenance = 'Hilfsmittel';
$strTables = '%s Tabellen';
$strTableStructure = 'Tabellenstruktur für Tabelle';
$strTableType = 'Tabellentyp';
$strTextAreaLength = ' Wegen der Länge ist dieses<br />Feld vieleicht nicht editierbar.';
$strTheContent = 'Der Inhalt Ihrer Datei wurde eingefügt.';
$strTheContents = 'Der Inhalt der CSV-Datei ersetzt die Einträge mit den gleichen Primär- oder Unique-Schlüsseln.';
$strTheTerminator = 'Der Trenner zwischen den Feldern.';
$strTotal = 'insgesamt';
$strType = 'Typ';

$strUncheckAll = 'Auswahl entfernen';
$strUnique = 'Unique';
$strUpdatePrivMessage = 'Die Rechte wurden geändert %s.';
$strUpdateQuery = 'Aktualisieren';
$strUsage = 'Verbrauch';
$strUseBackquotes = ' Tabellen- und Feldnamen in einfachen Anführungszeichen ';
$strUser = 'Benutzer';
$strUserEmpty = 'Kein Benutzername eingegeben!';
$strUserName = 'Benutzername';
$strUsers = 'Benutzer';
$strUseTables = 'Verwendete Tabellen';

$strValue = 'Wert';
$strViewDump = 'Dump (Schema) der Tabelle anzeigen';
$strViewDumpDB = 'Dump (Schema) der Datenbank anzeigen';

$strWelcome = 'Willkommen bei ';
$strWithChecked = 'markierte:';
$strWrongUser = 'Falscher Benutzername/Passwort. Zugriff verweigert.';

$strYes = 'Ja';

$strZip = '"Zip komprimiert"';

// To translate
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strAnIndex = 'An index has been added on %s';//to translate
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strIndexHasBeenDropped = 'Index %s has been dropped';//to translate
$strKeepPass = 'Do not change the password';//to translate
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strRunningAs = 'as';
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strShowCols = 'Show columns';
$strShowTables = 'Show tables';
$strUpdateProfile = 'Update profile:';//to translate
$strUpdateProfileMessage = 'The profile has been updated.';//to translate
?>