<?php
/* $Id$ */

$charset = 'iso-8859-1';
$text_dir = 'ltr';
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
$strAddToIndex = '%s&nbsp;Spalten zum Index hinzufügen';
$strAddNewField = 'Neue Feld(er) hinzufügen';
$strAddPriv = 'Rechte hinzufügen';
$strAddPrivMessage = 'Rechte wurden hinzugefügt.';
$strAddSearchConditions = 'Suchkondition (Argumente für das WHERE-Statement):';
$strAddUser = 'Neuen Benutzer hinzufügen';
$strAddUserMessage = 'Der Benutzer wurde hinzugefügt.';
$strAffectedRows = ' Betroffene Datensätze: ';
$strAfter = 'Nach';
$strAfterInsertBack = 'zurück';
$strAfterInsertNewInsert = 'Neuen Datensatz einfügen';
$strAll = 'Alle';
$strAlterOrderBy = 'Tabelle sortieren nach';
$strAnalyzeTable = 'Analysiere Tabelle';
$strAnd = 'und';
$strAnIndex = 'Ein Index wurde in %s erzeugt';
$strAny = 'Jeder';
$strAnyColumn = 'Jede Spalte';
$strAnyDatabase = 'Jede Datenbank';
$strAnyHost = 'Jeder Host';
$strAnyTable = 'Jede Tabelle';
$strAnyUser = 'Jeder Benutzer';
$strAPrimaryKey = 'Ein Primärschlüssel wurde in %s erzeugt';
$strAscending = 'aufsteigend';
$strAtBeginningOfTable = 'An den Anfang der Tabelle';
$strAtEndOfTable = 'An das Ende der Tabelle';
$strAttr = 'Attribute';

$strBack = 'Zurück';
$strBinary = ' Binär ';
$strBinaryDoNotEdit = ' Binär - nicht editierbar !';
$strBookmarkDeleted = 'SQL-Abfrage wurde gelöscht.';
$strBookmarkLabel = 'Titel';
$strBookmarkQuery = 'Gespeicherte SQL-Abfrage';
$strBookmarkThis = 'SQL-Abfrage speichern';
$strBookmarkView = 'Nur zeigen';
$strBrowse = 'Anzeigen';
$strBzip = '"BZip komprimiert"';

$strCantLoadMySQL = 'MySQL Erweiterung konnte nicht geladen werden,<br />bitte PHP Konfiguration überprüfen.';
$strCantRenameIdxToPrimary = 'Kann Index nicht in PRIMARY umbenennen!';
$strCarriage = 'Wagenrücklauf \\r';
$strChange = 'Ändern';
$strCheckAll = 'Alle auswählen';
$strCheckDbPriv = 'Rechte einer Datenbank prüfen';
$strCheckTable = 'Überprüfe Tabelle';
$strColumn = 'Spalte';
$strColumnNames = 'Spaltennamen';
$strCompleteInserts = 'Vollständige \'INSERT\'s';
$strConfirm = 'Bist du dir wirklich sicher?';
$strCopyTable = 'Kopiere Tabelle nach (Datenbank<b>.</b>Tabellenname):';
$strCopyTableOK = 'Tabelle %s wurde kopiert nach %s.';
$strCreate = 'Erzeugen';
$strCreateIndex = 'Index über&nbsp;%s&nbsp;Spalten anlegen';
$strCreateIndexTopic = 'Neuen Index anlegen';
$strCreateNewDatabase = 'Neue Datenbank erzeugen';
$strCreateNewTable = 'Neue Tabelle erstellen in Datenbank ';
$strCriteria = 'Kriterium';

$strData = 'Daten';
$strDatabase = 'Datenbank ';
$strDatabaseHasBeenDropped = 'Datenbank %s wurde gelöscht.';
$strDatabases = 'Datenbanken';
$strDatabasesStats = 'Statistiken über alle Datenbanken';
$strDataOnly = 'Nur Daten';
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
$strFieldHasBeenDropped = 'Spalte %s wurde entfernt.';
$strFields = 'Felder';
$strFieldsEmpty = ' Sie müssen angeben wie viele Felder die Tabelle haben soll! ';
$strFieldsEnclosedBy = 'Felder eingeschlossen von';
$strFieldsEscapedBy = 'Felder escaped von';
$strFieldsTerminatedBy = 'Felder getrennt mit';
$strFixed = 'starr';
$strFlushTable = 'Verwerfen der Tabelle ("FLUSH")';
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
$strIndexHasBeenDropped = 'Index %s wurde entfernt.';
$strIndexName = 'Index Name&nbsp;:';
$strIndexType = 'Index Typ&nbsp;:';
$strIndexes = 'Indizes';
$strIgnore = 'Ignorieren';
$strInsert = 'Einfügen';
$strInsertAsNewRow = ' Als neuen Datensatz speichern ';
$strInsertedRows = 'Eingefügte Zeilen:';
$strInsertNewRow = 'Neue Zeile einfügen';
$strInsertTextfiles = 'Textdatei in Tabelle einfügen';
$strInstructions = 'Befehle';
$strInUse = 'in Benutzung';
$strInvalidName = '"%s" ist ein reserviertes Wort, welches nicht als Datenbank-, Feld- oder Tabellenname verwendet werden darf.';

$strKeepPass = 'Kennwort nicht verändert';
$strKeyname = 'Name';
$strKill = 'Beenden';

$strLength = ' Länge ';
$strLengthSet = 'Länge/Set*';
$strLimitNumRows = 'Einträge pro Seite';
$strLineFeed = 'Zeilenvorschub: \\n';
$strLines = 'Zeilen';
$strLinesTerminatedBy = 'Zeilen getrennt mit';
$strLocationTextfile = 'Datei';
$strLogout = 'Neu einloggen';
$strLogPassword = 'Kennwort:';

$strModifications = 'Änderungen gespeichert.';
$strModify = 'Verändern';
$strModifyIndexTopic = 'Index modifizieren';
$strMoveTable = 'Verschiebe Tabelle nach (Datenbank<b>.</b>Tabellenname):';
$strMoveTableOK = 'Tabelle %s wurde nach %s verschoben.';
$strMySQLReloaded = 'MySQL neu gestartet.';
$strMySQLSaid = 'MySQL meldet: ';
$strMySQLServerProcess = 'MySQL %pma_s1% auf %pma_s2% als %pma_s3%';
$strMySQLShowProcess = 'Prozesse anzeigen';
$strMySQLShowStatus = 'MySQL-Laufzeit-Informationen anzeigen';
$strMySQLShowVars = 'MySQL-System-Variablen anzeigen';

$strName = 'Name';
$strNbRecords = 'Datensätze';
$strNext = 'Nächste';
$strNo = 'Nein';
$strNoDatabases = 'Keine Datenbanken';
$strNoDropDatabases = '"DROP DATABASE" Anweisung wurde deaktiviert.';
$strNoFrames = 'phpMyAdmin arbeitet besser mit einem <b>Frame</b> fähigen Browser.';
$strNoIndex = 'Kein Index definiert!';
$strNoIndexPartsDefined = 'Keine Indizies definiert.';
$strNoModification = 'Keine Änderung';
$strNone = 'keine';
$strNoPassword = 'Kein Kennwort';
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
$strPassword = 'Kennwort';
$strPasswordEmpty = 'Es wurde kein Kennwort angegeben!';
$strPasswordNotSame = 'Die eingegebenen Kennwörter sind nicht identisch!';
$strPmaDocumentation = 'phpMyAdmin Dokumentation';
$strPHPVersion = 'PHP Version';
$strPos1 = 'Anfang';
$strPrevious = 'Vorherige';
$strPrimary = 'Primärschlüssel';
$strPrimaryKey = 'Primärschlüssel';
$strPrimaryKeyHasBeenDropped = 'Der Primärschlüssel wurde gelöscht';
$strPrimaryKeyName = 'Der Name des Primärschlüssels muss PRIMARY lauten!';
$strPrimaryKeyWarning = 'Der Name des Primärschlüssels darf <b>nur</b> "PRIMARY" lauten.';
$strPrintView = 'Druckansicht';
$strPrivileges = 'Rechte';
$strProperties = 'Eigenschaften';

$strQBE = 'Suche über Beispielwerte';
$strQBEDel = 'Entf.';
$strQBEIns = 'Einf.';
$strQueryOnDb = ' SQL-Befehl in der Datenbank <b>%s</b>:';

$strRecords = 'Einträge';
$strReloadFailed = 'MySQL Neuladen fehlgeschlagen.';
$strReloadMySQL = 'MySQL neu starten';
$strRememberReload = 'Der Server muss neugestartet werden.';
$strRenameTable = 'Tabelle umbenennen in';
$strRenameTableOK = 'Tabelle %s wurde umbenannt in %s.';
$strRepairTable = 'Repariere Tabelle';
$strReplace = 'Ersetzen';
$strReplaceTable = 'Tabelleninhalt ersetzen';
$strReset = 'Zurücksetzen';
$strReType = 'Wiederholen';
$strRevoke = 'Entfernen';
$strRevokeGrant = '\'Grant\' entfernen';
$strRevokeGrantMessage = 'Du hast das Recht \'Grant\' entfernt für %s';
$strRevokeMessage = 'Du hast die Rechte entfernt für %s';
$strRevokePriv = 'Rechte entfernen';
$strRowLength = 'Zeilenlänge';
$strRows = 'Zeilen';
$strRowsFrom = 'Datensätze, beginnend ab';
$strRowSize = 'Zeilengröße';
$strRowsModeHorizontal = 'untereinander';
$strRowsModeOptions = '%s angeordnet und wiederhole die Kopfzeilen nach %s Datensätzen.';
$strRowsModeVertical = 'nebeneinander';
$strRowsStatistic = 'Zeilenstatistik';
$strRunning = 'auf %s';
$strRunQuery = 'SQL Befehl ausführen';
$strRunSQLQuery = 'SQL-Befehl(e) ausführen in Datenbank %s';

$strSave = 'Speichern';
$strSelect = 'Teilw. anzeigen';
$strSelectAll = 'Alle auswählen';  
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
$strTableHasBeenFlushed = 'Tabelle %s wurde geschlossen und zwischengespeicherte Daten gespeichert.';
$strTableMaintenance = 'Hilfsmittel';
$strTables = '%s Tabellen';
$strTableStructure = 'Tabellenstruktur für Tabelle';
$strTableType = 'Tabellentyp';
$strTextAreaLength = 'Wegen der Länge ist dieses<br />Feld vielleicht nicht editierbar.';
$strTheContent = 'Der Inhalt Ihrer Datei wurde eingefügt.';
$strTheContents = 'Der Inhalt der CSV-Datei ersetzt die Einträge mit den gleichen Primär- oder Unique-Schlüsseln.';
$strTheTerminator = 'Der Trenner zwischen den Feldern.';
$strTotal = 'insgesamt';
$strType = 'Typ';

$strUncheckAll = 'Auswahl entfernen';
$strUnique = 'Unique';
$strUnselectAll = 'Auswahl entfernen'; 
$strUpdatePrivMessage = 'Die Rechte wurden geändert %s.';
$strUpdateProfile = 'Benutzer ändern:';
$strUpdateProfileMessage = 'Benutzer wurde geändert.';
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

$strWelcome = 'Willkommen bei %s';
$strWithChecked = 'markierte:';
$strWrongUser = 'Falscher Benutzername/Passwort. Zugriff verweigert.';

$strYes = 'Ja';

$strZip = '"Zip komprimiert"';

// To translate
$strCardinality = 'Cardinality';
$strCookiesRequired = 'Cookies must be enabled past this point.';
$strLogin = 'Login';
$strLogUsername = 'Username:';
$strShowCols = 'Show columns';
$strShowTables = 'Show tables';

?>
