<?php
/* $Id$ */

$charset = "iso-8859-1";
$left_font_family = "verdana, helvetica, arial, geneva, sans-serif";
$right_font_family = "helvetica, arial, geneva, sans-serif";
$number_thousands_separator = ".";
$number_decimal_separator = ",";
$byteUnits = array("Bytes", "KB", "MB", "GB");

$strAccessDenied = "Zugriff verweigert.";
$strAction = "Aktion";
$strAddDeleteColumn = "Add/Delete Field Columns"; //to translate (tbl_qbe.php3)
$strAddDeleteRow = "Add/Delete Criteria Row"; //to translate (tbl_qbe.php3)
$strAddNewField = "Neue Feld(er) hinzuf&uuml;gen";
$strAddPriv = "Rechte hinzuf&uuml;gen";
$strAddPrivMessage = "Rechte wurden hinzugef&uuml;gt.";
$strAddSearchConditions = "Suchkondition (Argumente f&uuml;r das WHERE-Statement):";
$strAddUser = "Neuen Benutzer hinzuf&uuml;gen";
$strAddUserMessage = "Der Benutzer wurde hinzugef&uuml;gt.";
$strAfter = "Nach";
$strAll = "Alle";
$strAlterOrderBy = "Tabelle sortieren nach";
$strAnalyzeTable = "Analysiere Tabelle";
$strAnd = "And"; //to translate (tbl_qbe.php3)
$strAnIndex = "Ein Index wurde f&uuml;r folgendes Feld hinzugef&uuml;gt: ";
$strAny = "Jeder";
$strAnyColumn = "Jede Spalte";
$strAnyDatabase = "Jede Datenbank";
$strAnyHost = "Jeder Host";
$strAnyTable = "Jede Tabelle";
$strAnyUser = "Jeder Benutzer";
$strAPrimaryKey = "Ein Primarschl&uuml;ssel wurde f&uuml;r folgendes Feld hinzugef&uuml;gt: ";
$strAscending = "Ascending"; //to translate (tbl_qbe.php3)
$strAtBeginningOfTable = "An den Anfang der Tabelle";
$strAtEndOfTable = "An das Ende der Tabelle";
$strAttr = "Attribute";

$strBack = "Zur&uuml;ck";
$strBookmarkLabel = "Titel";
$strBookmarkQuery = "Gespeicherte SQL-Abfrage";
$strBookmarkThis = "SQL-Abfrage speichern";
$strBookmarkView = "Nur zeigen";
$strBrowse = "Anzeigen";

$strCantLoadMySQL = "MySQL Erweiterung konnte nicht geladen werden,<br>bitte PHP Konfiguration &uuml;berpr&uuml;fen.";
$strCarriage = "Wagenr&uuml;cklauf \\r";
$strChange = "&Auml;ndern";
$strCheckAll = "Alle ausw&auml;hlen";
$strCheckDbPriv = "Rechte einer Datenbank pr&uuml;fen";
$strCheckTable = "&Uuml;berpr&uuml;fe Tabelle";
$strColumn = "Spalte";
$strColumnEmpty = "Die Spalten-Titel sind leer!";
$strColumnNames = "Spaltennamen";
$strCompleteInserts = "Vollst&auml;ndige 'inserts'";
$strConfirm = "Bist du dir wirklich sicher?";
$strCopyTable = "Tabelle kopieren nach";
$strCopyTableOK = "Tabelle %s wurde kopiert nach %s.";
$strCreate = "Erzeugen";
$strCreateNewDatabase = "Neue Datenbank erzeugen";
$strCreateNewTable = "Neue Tabelle erstellen in Datenbank ";
$strCriteria = "Criteria"; //to translate (tbl_qbe.php3)

$strData = "Daten";
$strDatabase = "Datenbank ";
$strDatabases = "Datenbanken";
$strDataOnly = "Nur Daten";
$strDbEmpty = "Der Name der Datenbank ist leer!";
$strDefault = "Standard";
$strDelete = "L&ouml;schen";
$strDeleted = "Die Zeile wurde gel&ouml;scht.";
$strDeleteFailed = "L&ouml;schen fehlgeschlagen!";
$strDeletePassword = "Password l&ouml;schen";
$strDeleteUserMessage = "Der Benutzer wurde gel&ouml;scht";
$strDelPassMessage = "Password entfernt f&uuml;r";
$strDescending = "Desending"; //to translate (tbl_qbe.php3)
$strDisableMagicQuotes = "<b>Achtung:</b> Sie haben magic_quotes_gpc in Ihrer PHP-Konfigration aktiviert. Dieses Version von PhpMyAdmin braucht dies nicht aktiviert sein, um korrekt zu funktionieren. Bitte lesen Sie im PHP-Manual (Installation & Configuration) nach, um Informationen dar&uuml;ber zu erhalten, wie man magic_quotes_gpc nicht aktivieren kann.";
$strDisplay = "Zeige";
$strDoAQuery = "Suche &uuml;ber Beispielwerte (\"query by example\") (Platzhalter: \"%\")";
$strDocu = "Dokumentation";
$strDoYouReally = "M&ouml;chten Sie wirklich diese Abfrage ausf&uuml;hren: ";
$strDrop = "L&ouml;schen";
$strDropDB = "Datenbank l&ouml;schen:";
$strDumpingData = "Daten f&uuml;r Tabelle";
$strDynamic = "dynamisch";

$strEdit = "&Auml;ndern";
$strEditPrivileges = "Rechte &auml;ndern";
$strEffective = "Effektiv";
$strEmpty = "Leeren";
$strEmptyResultSet = "MySQL lieferte ein leeres Resultset zur&uuml;ck (d.h. null Zeilen).";
$strEnableMagicQuotes = "<b>Achtung:</b> Sie haben magic_quotes_gpc in Ihrer PHP-Konfigration nicht aktiviert. PhpMyAdmin ben&ouml;tigt dies aber, um korrekt zu funktionieren. Bitte lesen Sie im PHP-Manual (Installation & Configuration) nach, um Informationen dar&uuml;ber z erhalten, wie man magic_quotes_gpc aktiviert.";
$strEnclosedBy = "eingeschlossen von";
$strEnd = "Ende";
$strError = "Fehler";
$strEscapedBy = "escaped von";
$strExtra = "Extra";

$strField = "Feld";
$strFields = "Felder";
$strFixed = "starr";
$strFormat = "Format";
$strFunction = "Funktion";

$strGenTime = "Erstellungszeit";
$strGo = "OK";
$strGrants = "Rechte";

$strHasBeenAltered = "wurde ge&auml;ndert.";
$strHasBeenCreated = "wurde erzeugt.";
$strHasBeenDropped = "wurde gel&ouml;scht.";
$strHasBeenEmptied = "wurde geleert.";
$strHome = "Home";
$strHomepageOfficial = " Offizielle phpMyAdmin Homepage ";
$strHomepageSourceforge = " Sourceforge phpMyAdmin Download Homepage ";
$strHost = "Host";
$strHostEmpty = "Es wurde kein Host angegeben!";

$strIfYouWish = "Wenn Sie nur bestimmte Spalten importieren m&ouml;chten, geben Sie diese bitte hier an.";
$strIndex = "Index";
$strIndexes = "Indizes";
$strInsert = "Einf&uuml;gen";
$strInsertAsNewRow = " Als neuen Datensatz speichern ";
$strInsertIntoTable = "In Tabelle einf&uuml;gen";
$strInsertNewRow = "Neue Zeile einf&uuml;gen";
$strInsertTextfiles = "Textdatei in Tabelle einf&uuml;gen";
$strInUse = "in Benutzung";

$strKeyname = "Name";
$strKill = "Beenden";

$strLength = " L&auml;nge ";
$strLengthSet = "L&auml;nge/Set";
$strLimitNumRows = "Eintr&auml;ge pro Seite";
$strLineFeed = "Zeilenvorschub: \\n";
$strLines = "Zeilen";
$strLocationTextfile = "Datei";
$strLogin = ""; //to translate, but its not in use ...
$strLogout = "Neu einloggen";

$strModifications = "&Auml;nderungen gespeichert.";
$strModify = "Modify"; //to translate (tbl_qbe.php3)
$strMySQLReloaded = "MySQL neu gestartet.";
$strMySQLSaid = "MySQL meldet: ";
$strMySQLShowProcess = "Prozesse anzeigen";
$strMySQLShowStatus = "MySQL-Laufzeit-Informationen anzeigen";
$strMySQLShowVars = "MySQL-System-Variablen anzeigen";

$strName = "Name";
$strNext = "N&auml;chste";
$strNo = "Nein";
$strNoPassword = "Kein Password";
$strNoPrivileges = "Keine Rechte";
$strNoRights = "Du hast nicht genug Rechte um fortzufahren!";
$strNoTablesFound = "Keine Tabellen in der Datenbank gefunden.";
$strNoUsersFound = "Keine(n) Benutzer gefunden.";
$strNull = "Null";
$strNumberIndexes = " Number of advanced indexes "; //to translate

$strOftenQuotation = "H&auml;ufig Anf&uuml;hrungszeichen. Optional bedeutet, da&szlig; nur Textfelder von den angegeben Zeichen eingeschlossen sind.";
$strOptimizeTable = "Optimiere Tabelle";
$strOptionalControls = "Optional. Bestimmt, wie Sonderzeichen kenntlich gemacht werden.";
$strOptionally = "optional";
$strOr = "Oder";
$strOverhead = "&Uuml;berhang";

$strPassword = "Password";
$strPasswordEmpty = "Es wurde kein Passwort angegeben!";
$strPasswordNotSame = "Die eingegebenen Passw&ouml;rter sind nicht identisch!";
$strPHPVersion = "PHP Version";
$strPos1 = "Anfang";
$strPrevious = "Vorherige";
$strPrimary = "Prim&auml;rschl&uuml;ssel";
$strPrimaryKey = "Prim&auml;rschl&uuml;ssel";
$strPrinterFriendly = "Druckerfreundliche Version der Tabelle";
$strPrintView = "Druckansicht";
$strPrivileges = "Rechte";
$strProducedAnError = "erzeugte einen Fehler.";
$strProperties = "Eigenschaften";

$strQBE = "Suche &uuml;ber Beispielwerte";
$strQBEDel = "Del";  //to translate (used in tbl_qbe.php3)
$strQBEIns = "Ins";  //to translate (used in tbl_qbe.php3)

$strReadTheDocs = "MySQL-Dokumentation zu LOAD DATA INFILE lesen";
$strRecords = "Eintr&auml;ge";
$strReloadFailed = "MySQL Neuladen fehlgeschlagen.";
$strReloadMySQL = "MySQL neu starten";
$strRememberReload = "Der Server muss neugestartet werden.";
$strRenameTable = "Tabelle umbennen in";
$strRenameTableOK = "Tabelle %s wurde umbenannt in %s.";
$strRepairTable = "Repariere Tabelle";
$strReplace = "Ersetzen";
$strReplaceTable = "Tabelleninhalt ersetzen";
$strReset = "Zur&uuml;cksetzen";
$strReType = "Wiederholen";
$strRevoke = "Entfernen";
$strRevokeGrant = "'Grant' entfernen";
$strRevokeGrantMessage = "Du hast das Recht 'Grant' entfernt f&uuml;r";
$strRevokeMessage = "Du hast die Rechte entfernt f&uuml;r";
$strRevokePriv = "Rechte entfernen";
$strRowLength = "Zeilenl&auml;nge";
$strRows = "Zeilen";
$strRowsFrom = "Datens&auml;tze, beginnend ab";
$strRowsStatistic = "Zeilenstatistik";
$strRunning = "auf ";
$strRunQuery = "Submit Query"; //to translate (tbl_qbe.php3)
$strRunSQLQuery = "SQL-Befehl(e) ausf&uuml;hren in Datenbank ";

$strSave = "Speichern";
$strSelect = "Teilw. anzeigen";
$strSelectFields = "Felder ausw&auml;hlen (mind. eines):";
$strSelectNumRows = "in der Abfrage";
$strSend = "Senden";
$strSequence = " Sequenz ";
$strServerVersion = "Server Version";
$strShow = "Zeige";
$strShowingRecords = "Zeige Datens&auml;tze ";
$strSingly = "(einmalig)";
$strSize = "Gr&ouml;&szlig;e";
$strSort = "Sort"; //to translate (tbl_qbe.php3)
$strSpaceUsage = "Speicherplatzverbrauch";
$strSQLQuery = "SQL-Befehl";
$strStatement = "Angaben";
$strStrucCSV = "CSV-Daten";
$strStrucData = "Struktur und Daten";
$strStrucDrop = "Mit 'drop table'";
$strStrucOnly = "Nur Struktur";
$strSubmit = "Abschicken";
$strSuccess = "Ihr SQL-Befehl wurde erfolgreich ausgef&uuml;hrt.";
$strSum = "Summe";

$strTable = "Tabelle ";
$strTableComments = "Tabellen-Kommentar";
$strTableEmpty = "Der Tabellenname ist leer!";
$strTableMaintenance = "Hilfsmittel";
$strTableStructure = "Tabellenstruktur f&uuml;r Tabelle";
$strTableType = "Tabellentyp";
$strTerminatedBy = "getrennt mit";
$strTextAreaLength = " Because of its length,<br> this field might not be editable "; //to translate
$strTheContent = "Der Inhalt Ihrer Datei wurde eingef&uuml;gt.";
$strTheContents = "Der Inhalt der CSV-Datei ersetzt die Eintr&auml;ge mit den gleichen Prim&auml;r- oder Unique-Schl&uuml;sseln.";
$strTheTerminator = "Der Trenner zwischen den Feldern.";
$strTotal = "insgesamt";
$strType = "Typ";

$strUncheckAll = "Auswahl entfernen";
$strUnique = "Unique";
$strUpdatePassMessage = "Das Password wurde ge&auml;ndert:";
$strUpdatePassword = "Password &auml;ndern";
$strUpdatePrivMessage = "Die Rechte wurden ge&auml;ndert:";
$strUpdateQuery = "Update Query"; //to translate (tbl_qbe.php3)
$strUsage = "Verbrauch";
$strUser = "Benutzer";
$strUserEmpty = "Kein Benutzername eingegeben!";
$strUserName = "Benutzername";
$strUsers = "Benutzer";
$strUseTables = "Use Tables"; //to translate (tbl_qbe.php3)

$strValue = "Wert";
$strViewDump = "Dump (Schema) der Tabelle anzeigen";
$strViewDumpDB = "Dump (Schema) der Datenbank anzeigen";

$strWelcome = "Willkommen bei ";
$strWrongUser = "Falscher Benutzername/Passwort. Zugriff verweigert.";

$strYes = "Ja";

// automatic generated by langxlorer.php (June 27, 2001, 6:53 pm)
// V0.11 - experimental (Steve Alberty - alberty@neptunlabs.de)
$strBinary=" Bin&auml;r ";
$strBinaryDoNotEdit=" Bin&auml;r - nicht editierbar !";
$strEnglishPrivileges=" Anmerkung: MySQL Rechtename werden in Englisch angegeben ";
$strNotNumber = "Das ist keine Zahl!";
$strNotValidNumber = " ist keine gültige Zeilennummer!"; // do not html quote

// export Zip (July 07, 2001, 19:48am)
$strBzip = "\"BZip komprimiert\"";
$strGzip = "\"GZip komprimiert\"";
$strOffSet = "Offset";
$strNbRecords = "Datens&auml;tze";
$strRowSize = "Zeilengr&ouml;&szlig;e";
$strShowThisQuery = "SQL Befehl hier wieder anzeigen";
$strUseBackquotes=" Tabellen- und Feldnamen in einfachen Anführungszeichen  ";
$strQueryOnDb=" SQL-query on database ";  //to translate (tbl_qbe.php3)
$strFieldsEmpty=" Sie müssen angeben wieviele Felder die Tabelle haben soll! ";
$strAffectedRows=" Betroffene Datens&auml;tze: ";
?>
