<?php
/* $Id$ */

/**
 * Translation made by: Sime Essert <sime@nofrx.org>
 */

$charset = 'windows-1250';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('Byteova', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Ned', 'Pon', 'Uto', 'Sri', 'Èet', 'Pet', 'Sub');
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
$strAddToIndex = 'Dodaj kljuè';
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
$strAnIndex = 'Kljuè je upravo dodan %s';
$strAny = 'Bilo koji';
$strAnyColumn = 'Bilo koji stupac';
$strAnyDatabase = 'Bilo koja baza podataka';
$strAnyHost = 'Bilo koji server';
$strAnyTable = 'Bilo koja tablica';
$strAnyUser = 'Bilo koji korisnik';
$strAPrimaryKey = 'Primarni kljuè je upravo dodan %s';
$strAscending = 'Rastuæi';
$strAtBeginningOfTable = 'Na poèetku tablice';
$strAtEndOfTable = 'Na kraju tablice';
$strAttr = 'Svojstva';

$strBack = 'Nazad';
$strBinary = 'Binarno';
$strBinaryDoNotEdit = 'Binarno - ne mijenjaj';
$strBookmarkDeleted = 'Oznaka je upravo izbrisana.';
$strBookmarkLabel = 'Naziv';
$strBookmarkQuery = 'Oznaèeni SQL-upit';
$strBookmarkThis = 'Oznaèi SQL-upit';
$strBookmarkView = 'Vidi samo';
$strBrowse = 'Pregled';
$strBzip = '"bzip-ano"';

$strCantLoadMySQL = 'Ne mogu uèitati MySql ekstenziju,<br /> molim provjerite PHP konfiguraciju.';
$strCantRenameIdxToPrimary = 'Ne mogu promijeniti kljuè u PRIMARY (primarni) !';
$strCardinality = 'Kardinalnost';
$strCarriage = 'Novi red (carriage return): \\r';
$strChange = 'Promijeni';
$strChangePassword = 'Promijeni šifru';
$strCheckAll = 'Oznaèi sve';
$strCheckDbPriv = 'Provjeri privilegije baze podataka';
$strCheckTable = 'Provjeri tablicu';
$strColumn = 'Stupac';
$strColumnNames = 'Imena stupaca';
$strCompleteInserts = 'Kompletan INSERT (sa imenima polja)';
$strConfirm = 'Da li stvarno to želite uèiniti?';
$strCookiesRequired = '<i>Cookies</i> moraju biti omoguæeni.';
$strCopyTable = 'Kopiram tablicu u (baza<b>.</b>tablica):';
$strCopyTableOK = 'Tablica %s je upravo kopirana u %s.';
$strCreate = 'Napravi';
$strCreateIndex = 'Napravi kljuè sa&nbsp;%s&nbsp;stupcem(aca)';
$strCreateIndexTopic = 'Napravi novi kljuè';
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
$strDescending = 'Opadajuæi';
$strDisplay = 'Prikaži';
$strDisplayOrder = 'Redoslijed prikaza:';
$strDoAQuery = 'Napravi "upit po primjeru" (<i>wildcard</i>: "%")';
$strDocu = 'Dokumentacija';
$strDoYouReally = 'Da li stvarno želite ';
$strDrop = 'Izbriši';
$strDropDB = 'Izbriši bazu %s';
$strDropTable = 'Izbriši tablicu';
$strDumpingData = 'Izvoz <i>(dump)</i> podataka tablice';
$strDynamic = 'dinamièno';

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
$strFieldsEnclosedBy = 'Podaci ograðeni sa';
$strFieldsEscapedBy = '<i>Escape</i> znak &nbsp; &nbsp; &nbsp;';
$strFieldsTerminatedBy = 'Podaci razdvojeni sa';
$strFixed = 'sreðeno';
$strFlushTable = 'Osvježi tablicu';
$strFormat = 'Format';
$strFormEmpty = 'Nedostaje vrijednost u formi !';
$strFullText = 'Pun tekst';
$strFunction = 'Funkcija';

$strGenTime = 'Vrijeme podizanja';
$strGo = 'Kreni';
$strGrants = 'Omoguæi';
$strGzip = '"gzip-ano"';

$strHasBeenAltered = 'je promijenjen.';
$strHasBeenCreated = 'je kreiran/a.';
$strHome = 'Poèetna stranica';
$strHomepageOfficial = 'phpMyAdmin WEB site';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Stranica';
$strHost = 'Host (domena)';
$strHostEmpty = 'Ime domene je prazno!';

$strIdxFulltext = 'Puni tekst';
$strIfYouWish = 'Ukoliko želite pregledati samo neke stupce u tablici, navedite ih razdvojene zarezom';
$strIgnore = 'Ignoriraj';
$strIndex = 'Kljuè';
$strIndexes = 'Kljuèevi';
$strIndexHasBeenDropped = 'Kljuè %s je izbrisan';
$strIndexName = 'Ime kljuèa :';
$strIndexType = 'Vrsta kljuèa :';
$strInsert = 'Novi redak';
$strInsertAsNewRow = 'Unesi kao novi redak';
$strInsertedRows = 'Uneseni reci:';
$strInsertNewRow = 'Unesi novi redak';
$strInsertTextfiles = 'Ubaci podatke iz tekstualne datoteke';
$strInstructions = 'Uputstva';
$strInUse = 'se koristi';
$strInvalidName = '"%s" je rezervirana rijeè, \nne može se koristiti kao ime polja, tablice ili baze.';

$strKeepPass = 'Ne mijenjaj lozinku';
$strKeyname = 'Ime Kljuèa';
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
$strLogUsername = 'Korisnièko ime:';

$strModifications = 'Izmjene su spremljene';
$strModify = 'Promijeni';
$strModifyIndexTopic = 'Promijeni kljuè';
$strMoveTable = 'Preimenuj tablicu u (baza<b>.</b>tablica):';
$strMoveTableOK = 'Tablica %s se sada zove %s.';
$strMySQLReloaded = 'MySQL je ponovno pokrenut (<i>reload</i>).';
$strMySQLSaid = 'MySQL poruka: ';
$strMySQLServerProcess = 'MySQL %pma_s1% pokrenut na %pma_s2%, prijavljen kao %pma_s3%';
$strMySQLShowProcess = 'Prikaži listu procesa';
$strMySQLShowStatus = 'Prikaži MySQL runtime informacije';
$strMySQLShowVars = 'Prikaži MySQL sistemske varijable';

$strName = 'Ime';
$strNext = 'Sljedeæi';
$strNo = 'Ne';
$strNoDatabases = 'Baza ne postoji';
$strNoDropDatabases = '"DROP DATABASE" naredba je onemoguæena.';
$strNoFrames = 'phpMyAdmin preferira preglednike koji podržavaju frame-ove.';
$strNoIndex = 'Kljuè nije definiran!';
$strNoIndexPartsDefined = 'Dijelovi kljuèa nisu definirani!';
$strNoModification = 'Nema nikakvih promjena';
$strNone = 'Ništa';
$strNoPassword = 'Nema lozinke';
$strNoPrivileges = 'Nema privilegija';
$strNoQuery = 'Nema SQL upita!';
$strNoRights = 'Nemate dovoljno prava za ovo podruèje!';
$strNoTablesFound = 'Tablica nije pronaðena u bazi.';
$strNotNumber = 'To nije broj!';
$strNotValidNumber = ' nije odgovarajuæi broj redaka!';
$strNoUsersFound = 'Korisnik(ci) nije pronaðen.';
$strNull = 'Null';

$strOftenQuotation = 'Navodnici koji se koriste. OPCIONO se misli da neka polja mogu, ali ne moraju biti pod navodnicima.';
$strOptimizeTable = 'Optimiziraj tablicu';
$strOptionalControls = 'Opciono. Znak koji prethodi specijalnim znakovima.';
$strOptionally = 'OPCIONO';
$strOr = 'ili';
$strOverhead = 'Prekoraèenje';

$strPartialText = 'Dio teksta';
$strPassword = 'Lozinka';
$strPasswordEmpty = 'Lozinka je prazna!';
$strPasswordNotSame = 'Lozinka se ne podudara!';
$strPHPVersion = 'verzija PHP-a';
$strPmaDocumentation = 'phpMyAdmin dokumentacija';
$strPmaUriError = '<tt>$cfg[\'PmaAbsoluteUri\']</tt> dio mora biti namješten u konfiguracijskoj datoteci (config.inc.php)!';
$strPos1 = 'Poèetak';
$strPrevious = 'Prethodna';
$strPrimary = 'Primarni';
$strPrimaryKey = 'Primarni kljuè';
$strPrimaryKeyHasBeenDropped = 'Primarni kljuè je izbrisan';
$strPrimaryKeyName = 'Ime primarnog kljuèa mora biti... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>mora</b> biti ime i <b>samo</b> ime primarnog kljuèa!)';
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
$strRowsFrom = ' redaka poèevši od retka';
$strRowSize = ' Velièina retka ';
$strRowsModeHorizontal = 'horizontalnom';
$strRowsModeOptions = 'u %s naèinu i ispiši zaglavlje poslije svakog %s retka';
$strRowsModeVertical = 'vertikalnom';
$strRowsStatistic = 'Statistika redaka';
$strRunning = 'pokrenuto na %s';
$strRunQuery = 'Izvrši SQL upit';
$strRunSQLQuery = 'Izvrši SQL upit(e) na bazi ';

$strSave = 'Spremi';
$strSelect = 'Oznaèi';
$strSelectADb = 'Izaberite bazu';
$strSelectAll = 'Oznaèi sve';
$strSelectFields = 'Izaberite polja (najmanje jedno)';
$strSelectNumRows = 'u upitu';
$strSend = 'Spremi u datoteku';
$strServerChoice = 'Izbor servera';
$strServerVersion = 'Verzija servera';
$strSetEnumVal = 'Ako je polje "enum" ili "set", unesite vrijednosti u formatu: \'a\',\'b\',\'c\'...<br />Ako vam zatreba <i>backslash</i> ("\") ili jednostruki navodnik ("\'") navedite ih koristeæi <i>backslash</i> (npr. \'\\\\xyz\' ili \'a\\\'b\').';
$strShow = 'Prikaži';
$strShowAll = 'Prikaži sve';
$strShowCols = 'Prikaži stupce';
$strShowingRecords = 'Prikaz redaka';
$strShowPHPInfo = 'Prikaži informacije o PHP-u';
$strShowTables = 'Prikaži tablice';
$strShowThisQuery = ' Prikaži ovaj upit ponovo ';
$strSingly = '(po jednom polju)';
$strSize = 'Velièina';
$strSort = 'Sortiranje';
$strSpaceUsage = 'Zauzeæe';
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
$strTextAreaLength = ' Zbog velièine ovog polja,<br /> polje možda neæete moæi mijenjati ';
$strTheContent = 'Sadržaj datoteke je stavljen u bazu.';
$strTheContents = 'Sadržaj tablice zamijeni sa sadržajem datoteke sa identiènim primarnim i jedinstvenim (unique) kljuèem.';
$strTheTerminator = 'Znak za odjeljivanje polja u datoteci.';
$strTotal = 'ukupno';
$strType = 'Vrsta';

$strUncheckAll = 'Makni oznake';
$strUnique = 'Jedinstveni kljuè';
$strUnselectAll = 'Makni oznake';
$strUpdatePrivMessage = 'Promijenili ste privilegije za %s.';
$strUpdateProfile = 'Promijeni profil:';
$strUpdateProfileMessage = 'Profil je promijenjen.';
$strUpdateQuery = 'Promijeni SQL-upit';
$strUsage = 'Zauzeæe';
$strUseBackquotes = 'Koristi \' za ogranièavanje imena polja';
$strUser = 'Korisnik';
$strUserEmpty = 'Ime korisnika je prazno!';
$strUserName = 'Ime korisnika';
$strUsers = 'Korisnici';
$strUseTables = 'Koristi tablice';

$strValue = 'Vrijednost';
$strViewDump = 'Prikaži dump (shemu) tablice';
$strViewDumpDB = 'Prikaži dump (shemu) baze';

$strWelcome = 'Dobrodošli u %s';
$strWithChecked = 'Oznaèeno:';
$strWrongUser = 'Pogrešno korisnièko ime/lozinka. Pristup odbijen.';

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
?>
