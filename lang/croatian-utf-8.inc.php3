<?php
/* $Id$ */

/**
 * Translation made by: Sime Essert <sime@nofrx.org>
 */

$charset = 'utf-8';
$allow_recoding = TRUE;
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('Byteova', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub');
$month = array('Sij', 'Vel', 'Ožu', 'Tra', 'Svi', 'Lip', 'Srp', 'Kol', 'Ruj', 'Lis', 'Stu', 'Pro');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d. %B %Y. u %H:%M';

$strAccessDenied = 'Pristup odbijen';
$strAction = 'Akcija';
$strAddDeleteColumn = 'Dodaj/izbriši stupac';
$strAddDeleteRow = 'Dodaj/izbriši polje za kriterij';
$strAddNewField = 'Dodaj novi stupac';
$strAddPriv = 'Dodaj novu privilegiju';
$strAddPrivMessage = 'Privilegija je dodana';
$strAddSearchConditions = 'Dodaj uvjete pretraživanja (dio "where" upita):';
$strAddToIndex = 'Dodaj ključ';
$strAddUser = 'Dodaj novog korisnika';
$strAddUserMessage = 'Korisnik dodan';
$strAffectedRows = 'Promijenjeno redaka:';
$strAfter = 'Nakon %s';
$strAfterInsertBack = 'Natrag na prethodnu stranicu';
$strAfterInsertNewInsert = 'Dodaj još jedan red';
$strAll = 'Sve';
$strAlterOrderBy = 'Promijeni redoslijed u tablici';
$strAnalyzeTable = 'Analiziraj tablicu';
$strAnd = 'i';
$strAnIndex = 'Ključ je upravo dodan %s';
$strAny = 'Bilo koji';
$strAnyColumn = 'Bilo koji stupac';
$strAnyDatabase = 'Bilo koja baza podataka';
$strAnyHost = 'Bilo koji server';
$strAnyTable = 'Bilo koja tablica';
$strAnyUser = 'Bilo koji korisnik';
$strAPrimaryKey = 'Primarni ključ je upravo dodan %s';
$strAscending = 'Rastući';
$strAtBeginningOfTable = 'Na početku tablice';
$strAtEndOfTable = 'Na kraju tablice';
$strAttr = 'Svojstva';

$strBack = 'Nazad';
$strBinary = 'Binarno';
$strBinaryDoNotEdit = 'Binarno - ne mijenjaj';
$strBookmarkDeleted = 'Oznaka je upravo izbrisana.';
$strBookmarkLabel = 'Naziv';
$strBookmarkQuery = 'Označeni SQL-upit';
$strBookmarkThis = 'Označi SQL-upit';
$strBookmarkView = 'Vidi samo';
$strBrowse = 'Pregled';
$strBzip = '"bzip-ano"';

$strCantLoadMySQL = 'Ne mogu učitati MySql ekstenziju,<br /> molim provjerite PHP konfiguraciju.';
$strCantRenameIdxToPrimary = 'Ne mogu promijeniti ključ u PRIMARY (primarni) !';
$strCardinality = 'Kardinalnost';
$strCarriage = 'Novi red (carriage return): \\r';
$strChange = 'Promijeni';
$strChangePassword = 'Promijeni šifru';
$strCheckAll = 'Označi sve';
$strCheckDbPriv = 'Provjeri privilegije baze podataka';
$strCheckTable = 'Provjeri tablicu';
$strColumn = 'Stupac';
$strColumnNames = 'Imena stupaca';
$strCompleteInserts = 'Kompletan INSERT (sa imenima polja)';
$strConfirm = 'Da li stvarno to želite učiniti?';
$strCookiesRequired = '<i>Cookies</i> moraju biti omogućeni.';
$strCopyTable = 'Kopiram tablicu u (baza<b>.</b>tablica):';
$strCopyTableOK = 'Tablica %s je upravo kopirana u %s.';
$strCreate = 'Napravi';
$strCreateIndex = 'Napravi ključ sa&nbsp;%s&nbsp;stupcem(aca)';
$strCreateIndexTopic = 'Napravi novi ključ';
$strCreateNewDatabase = 'Napravi bazu podataka';
$strCreateNewTable = 'Napravi novu tablicu u bazi ';
$strCriteria = 'Kriterij';

$strData = 'Podaci';
$strDatabase = 'Baza podataka ';
$strDatabaseHasBeenDropped = 'Baza %s je izbrisana.';
$strDatabases = 'baze';
$strDatabasesStats = 'Statistika baze';
$strDatabaseWildcard = 'Baza (<i>wildcard</i> znakovi dozvoljeni):';
$strDataOnly = 'Samo podaci';
$strDefault = 'Default';
$strDelete = 'Izbriši';
$strDeleted = 'Red je izbrisan';
$strDeletedRows = 'Izbrisani redovi:';
$strDeleteFailed = 'Brisanje nije uspjelo!';
$strDeleteUserMessage = 'Upravo ste izbrisali korisnika: %s.';
$strDescending = 'Opadajući';
$strDisplay = 'Prikaži';
$strDisplayOrder = 'Redoslijed prikaza:';
$strDoAQuery = 'Napravi "upit po primjeru" (<i>wildcard</i>: "%")';
$strDocu = 'Dokumentacija';
$strDoYouReally = 'Da li stvarno želite ';
$strDrop = 'Izbriši';
$strDropDB = 'Izbriši bazu %s';
$strDropTable = 'Izbriši tablicu';
$strDumpingData = 'Izvoz <i>(dump)</i> podataka tablice';
$strDynamic = 'dinamično';

$strEdit = 'Promijeni';
$strEditPrivileges = 'Promijeni privilegije';
$strEffective = 'Efektivno';
$strEmpty = 'Isprazni';
$strEmptyResultSet = 'MySQL je vratio prazan rezultat (nula redaka).';
$strEnd = 'Kraj';
$strEnglishPrivileges = 'Opaska: MySQL imena privilegija moraju biti engleskom ';
$strError = 'Greška';
$strExtendedInserts = 'Prošireni INSERT';
$strExtra = 'Dodatno';

$strField = 'Polje';
$strFieldHasBeenDropped = 'Polje %s izbrisano';
$strFields = 'Broj polja';
$strFieldsEmpty = ' Broj polja je nula! ';
$strFieldsEnclosedBy = 'Podaci ograđeni sa';
$strFieldsEscapedBy = '<i>Escape</i> znak &nbsp; &nbsp; &nbsp;';
$strFieldsTerminatedBy = 'Podaci razdvojeni sa';
$strFixed = 'sređeno';
$strFlushTable = 'Osvježi tablicu';
$strFormat = 'Format';
$strFormEmpty = 'Nedostaje vrijednost u formi !';
$strFullText = 'Pun tekst';
$strFunction = 'Funkcija';

$strGenTime = 'Vrijeme podizanja';
$strGo = 'Kreni';
$strGrants = 'Omogući';
$strGzip = '"gzip-ano"';

$strHasBeenAltered = 'je promijenjen.';
$strHasBeenCreated = 'je kreiran/a.';
$strHome = 'Početna stranica';
$strHomepageOfficial = 'phpMyAdmin WEB site';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Stranica';
$strHost = 'Host (domena)';
$strHostEmpty = 'Ime domene je prazno!';

$strIdxFulltext = 'Puni tekst';
$strIfYouWish = 'Ukoliko želite pregledati samo neke stupce u tablici, navedite ih razdvojene zarezom';
$strIgnore = 'Ignoriraj';
$strIndex = 'Ključ';
$strIndexes = 'Ključevi';
$strIndexHasBeenDropped = 'Ključ %s je izbrisan';
$strIndexName = 'Ime ključa :';
$strIndexType = 'Vrsta ključa :';
$strInsert = 'Novi redak';
$strInsertAsNewRow = 'Unesi kao novi redak';
$strInsertedRows = 'Uneseni reci:';
$strInsertNewRow = 'Unesi novi redak';
$strInsertTextfiles = 'Ubaci podatke iz tekstualne datoteke';
$strInstructions = 'Uputstva';
$strInUse = 'se koristi';
$strInvalidName = '"%s" je rezervirana riječ, \nne može se koristiti kao ime polja, tablice ili baze.';

$strKeepPass = 'Ne mijenjaj lozinku';
$strKeyname = 'Ime Ključa';
$strKill = 'Zaustavi';

$strLength = 'Dužina';
$strLengthSet = 'Dužina/Vrijednost*';
$strLimitNumRows = 'Broj redaka po stranici';
$strLineFeed = '<i>Linefeed</i>: \\n';
$strLines = 'Linije';
$strLinesTerminatedBy = 'Linije završavaju na';
$strLinksTo = 'Links to';
$strLocationTextfile = 'Lokacija tekstualne datoteke';
$strLogin = 'Prijava';
$strLogout = 'Odjava';
$strLogPassword = 'Lozinka:';
$strLogUsername = 'Korisničko ime:';

$strModifications = 'Izmjene su spremljene';
$strModify = 'Promijeni';
$strModifyIndexTopic = 'Promijeni ključ';
$strMoveTable = 'Preimenuj tablicu u (baza<b>.</b>tablica):';
$strMoveTableOK = 'Tablica %s se sada zove %s.';
$strMySQLReloaded = 'MySQL je ponovno pokrenut (<i>reload</i>).';
$strMySQLSaid = 'MySQL poruka: ';
$strMySQLServerProcess = 'MySQL %pma_s1% pokrenut na %pma_s2%, prijavljen kao %pma_s3%';
$strMySQLShowProcess = 'Prikaži listu procesa';
$strMySQLShowStatus = 'Prikaži MySQL runtime informacije';
$strMySQLShowVars = 'Prikaži MySQL sistemske varijable';

$strName = 'Ime';
$strNext = 'Sljedeći';
$strNo = 'Ne';
$strNoDatabases = 'Baza ne postoji';
$strNoDropDatabases = '"DROP DATABASE" naredba je onemogućena.';
$strNoFrames = 'phpMyAdmin preferira preglednike koji podržavaju frame-ove.';
$strNoIndex = 'Ključ nije definiran!';
$strNoIndexPartsDefined = 'Dijelovi ključa nisu definirani!';
$strNoModification = 'Nema nikakvih promjena';
$strNone = 'Ništa';
$strNoPassword = 'Nema lozinke';
$strNoPrivileges = 'Nema privilegija';
$strNoQuery = 'Nema SQL upita!';
$strNoRights = 'Nemate dovoljno prava za ovo područje!';
$strNoTablesFound = 'Tablica nije pronađena u bazi.';
$strNotNumber = 'To nije broj!';
$strNotValidNumber = ' nije odgovarajući broj redaka!';
$strNoUsersFound = 'Korisnik(ci) nije pronađen.';
$strNull = 'Null';

$strOftenQuotation = 'Navodnici koji se koriste. OPCIONO se misli da neka polja mogu, ali ne moraju biti pod navodnicima.';
$strOptimizeTable = 'Optimiziraj tablicu';
$strOptionalControls = 'Opciono. Znak koji prethodi specijalnim znakovima.';
$strOptionally = 'OPCIONO';
$strOr = 'ili';
$strOverhead = 'Prekoračenje';

$strPartialText = 'Dio teksta';
$strPassword = 'Lozinka';
$strPasswordEmpty = 'Lozinka je prazna!';
$strPasswordNotSame = 'Lozinka se ne podudara!';
$strPHPVersion = 'verzija PHP-a';
$strPmaDocumentation = 'phpMyAdmin dokumentacija';
$strPmaUriError = '<tt>$cfg[\'PmaAbsoluteUri\']</tt> dio mora biti namješten u konfiguracijskoj datoteci (config.inc.php)!';
$strPos1 = 'Početak';
$strPrevious = 'Prethodna';
$strPrimary = 'Primarni';
$strPrimaryKey = 'Primarni ključ';
$strPrimaryKeyHasBeenDropped = 'Primarni ključ je izbrisan';
$strPrimaryKeyName = 'Ime primarnog ključa mora biti... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>mora</b> biti ime i <b>samo</b> ime primarnog ključa!)';
$strPrintView = 'Sažetak';
$strPrivileges = 'Privilegije';
$strProperties = 'Svojstva';

$strQBE = 'Upit po primjeru';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'SQL upit na bazi <b>%s</b>:';

$strRecords = 'Reci';
$strReferentialIntegrity = 'Provjeri ispravnost veza:';
$strReloadFailed = 'ponovno pokretanje MySQL-a nije uspjelo.';
$strReloadMySQL = 'Ponovo pokreni MySQL (<i>reload</i>)';
$strRememberReload = 'Ne zaboravite ponovo pokrenuti (<i>reload</i>) server.';
$strRenameTable = 'Promijeni ime tablice u ';
$strRenameTableOK = 'Tablici %s promjenjeno ime u %s';
$strRepairTable = 'Popravi tablicu';
$strReplace = 'Zamijeni';
$strReplaceTable = 'Zamijeni podatke u tablici sa datotekom';
$strReset = 'Resetiraj';
$strReType = 'Ponovite unos';
$strRevoke = 'Opozovi';
$strRevokeGrant = 'Opozovi Grant';
$strRevokeGrantMessage = 'Opozvali ste Grant privilegije za  %s';
$strRevokeMessage = 'Opozvali ste privilegije za %s';
$strRevokePriv = 'Opozovi privilegije';
$strRowLength = 'Dužina retka';
$strRows = 'Redaka';
$strRowsFrom = ' redaka počevši od retka';
$strRowSize = ' Veličina retka ';
$strRowsModeHorizontal = 'horizontalnom';
$strRowsModeOptions = 'u %s načinu i ispiši zaglavlje poslije svakog %s retka';
$strRowsModeVertical = 'vertikalnom';
$strRowsStatistic = 'Statistika redaka';
$strRunning = 'pokrenuto na %s';
$strRunQuery = 'Izvrši SQL upit';
$strRunSQLQuery = 'Izvrši SQL upit(e) na bazi ';

$strSave = 'Spremi';
$strSelect = 'Označi';
$strSelectADb = 'Izaberite bazu';
$strSelectAll = 'Označi sve';
$strSelectFields = 'Izaberite polja (najmanje jedno)';
$strSelectNumRows = 'u upitu';
$strSend = 'Spremi u datoteku';
$strServerChoice = 'Izbor servera';
$strServerVersion = 'Verzija servera';
$strSetEnumVal = 'Ako je polje "enum" ili "set", unesite vrijednosti u formatu: \'a\',\'b\',\'c\'...<br />Ako vam zatreba <i>backslash</i> ("\") ili jednostruki navodnik ("\'") navedite ih koristeći <i>backslash</i> (npr. \'\\\\xyz\' ili \'a\\\'b\').';
$strShow = 'Prikaži';
$strShowAll = 'Prikaži sve';
$strShowCols = 'Prikaži stupce';
$strShowingRecords = 'Prikaz redaka';
$strShowPHPInfo = 'Prikaži informacije o PHP-u';
$strShowTables = 'Prikaži tablice';
$strShowThisQuery = ' Prikaži ovaj upit ponovo ';
$strSingly = '(po jednom polju)';
$strSize = 'Veličina';
$strSort = 'Sortiranje';
$strSpaceUsage = 'Zauzeće';
$strSQLQuery = 'SQL-upit';
$strStatement = 'Ime';
$strStrucCSV = 'CSV format';
$strStrucData = 'Struktura i podaci';
$strStrucDrop = 'Dodaj \'drop table\'';
$strStrucExcelCSV = 'CSV za Ms Excel';
$strStrucOnly = 'Samo struktura';
$strSubmit = 'Pokreni';
$strSuccess = 'Vaš SQL upit je uspješno izvršen';
$strSum = 'Ukupno';

$strTable = 'Tablica';
$strTableComments = 'Komentar tablice';
$strTableEmpty = 'Ime tablice je prazno!';
$strTableHasBeenDropped = 'Tablica %s je izbrisana';
$strTableHasBeenEmptied = 'Tablica %s je ispražnjena';
$strTableHasBeenFlushed = 'Tablica %s je osvježena';
$strTableMaintenance = 'Radnje na tablici';
$strTables = '%s tablica/e';
$strTableStructure = 'Struktura tablice';
$strTableType = 'Vrsta tablice';
$strTextAreaLength = ' Zbog veličine ovog polja,<br /> polje možda nećete moći mijenjati ';
$strTheContent = 'Sadržaj datoteke je stavljen u bazu.';
$strTheContents = 'Sadržaj tablice zamijeni sa sadržajem datoteke sa identičnim primarnim i jedinstvenim (unique) ključem.';
$strTheTerminator = 'Znak za odjeljivanje polja u datoteci.';
$strTotal = 'ukupno';
$strType = 'Vrsta';

$strUncheckAll = 'Makni oznake';
$strUnique = 'Jedinstveni ključ';
$strUnselectAll = 'Makni oznake';
$strUpdatePrivMessage = 'Promijenili ste privilegije za %s.';
$strUpdateProfile = 'Promijeni profil:';
$strUpdateProfileMessage = 'Profil je promijenjen.';
$strUpdateQuery = 'Promijeni SQL-upit';
$strUsage = 'Zauzeće';
$strUseBackquotes = 'Koristi \' za ograničavanje imena polja';
$strUser = 'Korisnik';
$strUserEmpty = 'Ime korisnika je prazno!';
$strUserName = 'Ime korisnika';
$strUsers = 'Korisnici';
$strUseTables = 'Koristi tablice';

$strValue = 'Vrijednost';
$strViewDump = 'Prikaži dump (shemu) tablice';
$strViewDumpDB = 'Prikaži dump (shemu) baze';

$strWelcome = 'Dobrodošli u %s';
$strWithChecked = 'Označeno:';
$strWrongUser = 'Pogrešno korisničko ime/lozinka. Pristup odbijen.';

$strYes = 'Da';

$strZip = '"zip-ano"';
// To translate

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
$strPrivDescMaxUpdates = 'Limits the number of commands that that change any table or database the user may execute per hour.';
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
?>
