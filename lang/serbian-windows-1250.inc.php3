<?php
/* $Id$ */

/**
 * Translated by:
 *     Igor Mladenovic <mligor@zimco.com>
 *     David Trajkovic <tdavid@ptt.yu>
 */

$charset = 'windows-1250';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('Bajtova', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Ned', 'Pon', 'Uto', 'Sre', 'Èet', 'Pet', 'Sub');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Avg', 'Sep', 'Okt', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d. %B %Y. u %H:%M';

$strAccessDenied = 'Pristup odbijen';
$strAction = 'Akcija';
$strAddDeleteColumn = 'Dodaj/Obriši Kolonu';
$strAddDeleteRow = 'Dodaj/Obriši polje za kriterujum';
$strAddNewField = 'Dodaj novo polje';
$strAddPriv = 'Dodaj novu privilegiju';
$strAddPrivMessage = 'Upravo ste dodali privilegiju.';
$strAddSearchConditions = 'Dodaj uslov pretraživanja (deo "where" upita):';
$strAddToIndex = 'Dodaj kljuè';
$strAddUser = 'Dodaj novog korisnika';
$strAddUserMessage = 'Upravo ste dodali korisnika.';
$strAffectedRows = 'Obuhvaæeni rekordi:';
$strAfter = 'Posle %s';
$strAfterInsertBack = 'Nazad na prethodnu stranu';
$strAfterInsertNewInsert = 'Dodaj jos jedan red';
$strAll = 'Sve';
$strAlterOrderBy = 'Promeni redosled u tabeli';
$strAnalyzeTable = 'Analiziraj tabelu';
$strAnd = 'i';
$strAnIndex = 'Kljuè je upravo dodat %s';
$strAny = 'Bilo koji';
$strAnyColumn = 'Bilo koja kolona';
$strAnyDatabase = 'Bilo koja baza podataka';
$strAnyHost = 'Bilo koji host';
$strAnyTable = 'Bilo koja tabela';
$strAnyUser = 'Bilo koji korisnik';
$strAPrimaryKey = 'Primarni kljuè je upravo dodat %s';
$strAscending = 'Rastuæi';
$strAtBeginningOfTable = 'Na poèetku tabele';
$strAtEndOfTable = 'Na kraju tabele';
$strAttr = 'Atributi';

$strBack = 'Nazad';
$strBinary = 'Binarni';
$strBinaryDoNotEdit = 'Binarni - ne memenjaj';
$strBookmarkDeleted = 'Obelezivaè je upravo obrisan.';
$strBookmarkLabel = 'Naziv';
$strBookmarkQuery = 'Obeležen SQL-upit';
$strBookmarkThis = 'Obeleži SQL-upit';
$strBookmarkView = 'Vidi samo';
$strBrowse = 'Pregled';
$strBzip = '"bzip-ovano"';

$strCantLoadMySQL = 'Ne mogu da uèitam MySql ekstenziju,<br /> molim pogledajte PHP koniguraciju.';
$strCantRenameIdxToPrimary = 'Ne mogu da promenim kljuè u PRIMARY (primarni) !';
$strCardinality = 'Kardinalnost';
$strCarriage = 'Prenos je vratio: \\r';
$strChange = 'Promeni';
$strChangePassword = 'Promeni šifru';
$strCheckAll = 'Markiraj sve';
$strCheckDbPriv = 'Proveri privilegije baze podataka';
$strCheckTable = 'Proveri tabelu';
$strColumn = 'Kolona';
$strColumnNames = 'Imena kolona';
$strCompleteInserts = 'Kompletan INSERT (sa imenima polja)';
$strConfirm = 'Da li stvarno želite da to uradite?';
$strCookiesRequired = 'Kukiji (Cookies) moraju u ovom sluèaju biti aktivni.';
$strCopyTable = 'Kopiram tabelu u (baza<b>.</b>tabela):';
$strCopyTableOK = 'Tabela %s je upravo kopirana u %s.';
$strCreate = 'Kreiraj';
$strCreateIndex = 'Kreiraj kljuè sa&nbsp;%s&nbsp;kolonom(e)';
$strCreateIndexTopic = 'Kreiraj novi kljuè';
$strCreateNewDatabase = 'Kreiraj bazu podataka';
$strCreateNewTable = 'Kreiraj novu tabelu u bazi %s';
$strCriteria = 'Kriterijum';

$strData = 'Podaci';
$strDatabase = 'Baza podataka ';
$strDatabaseHasBeenDropped = 'Baza %s je obrisana.';
$strDatabases = 'baze';
$strDatabasesStats = 'Statistika baze';
$strDatabaseWildcard = 'Baza (džoker karakteri dozvoljeni):';
$strDataOnly = 'Samo podaci';
$strDefault = 'Default';
$strDelete = 'Obriši';
$strDeleted = 'Red je obrisan';
$strDeletedRows = 'Obrisani redovi:';
$strDeleteFailed = 'Brisanje nije uspelo!';
$strDeleteUserMessage = 'Upravo ste obrisali korisnika: %s.';
$strDescending = 'Opadajuææi';
$strDisplay = 'Prikaži';
$strDisplayOrder = 'Redosled prikaza:';
$strDoAQuery = 'Napravi "upit po primeru" (džoker: "%")';
$strDocu = 'Dokumentacija';
$strDoYouReally = 'Da li stvarno hoæete da ';
$strDrop = 'Obriši';
$strDropDB = 'Obriši bazu %s';
$strDropTable = 'Obriši tabelu';
$strDumpingData = 'Backup podataka za tabelu';
$strDynamic = 'dynamic';

$strEdit = 'Promeni';
$strEditPrivileges = 'Promeni privilegije';
$strEffective = 'Efektivne';
$strEmpty = 'Izprazni';
$strEmptyResultSet = 'MySQL je vratio prazan rezultat (nula redova).';
$strEnd = 'Kraj';
$strEnglishPrivileges = ' Info: MySQL imena privilegija moraju da budu na engleskom ';
$strError = 'Greska';
$strExtendedInserts = 'Prošireni INSERT';
$strExtra = 'Dodatno';

$strField = 'Polje';
$strFieldHasBeenDropped = 'Polje %s obrisano';
$strFields = 'Broj Polja';
$strFieldsEmpty = ' Broj polja je nula! ';
$strFieldsEnclosedBy = 'Podaci ogranièeni sa';
$strFieldsEscapedBy = 'Escape karakter &nbsp; &nbsp; &nbsp;';
$strFieldsTerminatedBy = 'Podaci razdvojeni sa';
$strFixed = 'sredjeno';
$strFlushTable = 'Refrešuj tabelu ("FLUSH")';
$strFormat = 'Format';
$strFormEmpty = 'Nedostaje vrednost u formi !';
$strFullText = 'Pun tekst';
$strFunction = 'Funkcija';

$strGenTime = 'Vreme kreiranja';
$strGo = 'Start';
$strGrants = 'Omoguci';
$strGzip = '"gzip-ovano"';

$strHasBeenAltered = 'je promenjen.';
$strHasBeenCreated = 'je kreiran.';
$strHome = 'Poèetna strana';
$strHomepageOfficial = 'phpMyAdmin WEB sajt';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Stranica';
$strHost = 'Host (domen)';
$strHostEmpty = 'Ime domena je prazno!';

$strIdxFulltext = 'Tekst kljuè';
$strIfYouWish = 'Ako zelite da izlistate samo neke kolone\nu tebeli, navedite ih razdvojene zarezom';
$strIgnore = 'Ignoriši';
$strIndex = 'Kljuè';
$strIndexes = 'Kljuèevi';
$strIndexHasBeenDropped = 'Kljuè %s je obrisan';
$strIndexName = 'Ime kljuèa&nbsp;:';
$strIndexType = 'Tip kljuèa&nbsp;:';
$strInsert = 'Novi rekord';
$strInsertAsNewRow = 'Unesi kao novi rekord';
$strInsertedRows = 'Uneseni rekordi:';
$strInsertNewRow = 'Unesi novi rekord';
$strInsertTextfiles = 'Importuj podatke iz tekstualne datoteke';
$strInstructions = 'Uputstva';
$strInUse = 'se koristi';
$strInvalidName = '"%s" je rezervisana rec, \nne možete je koristiti kao ime polja, tabele ili baze.';

$strKeepPass = 'Nemoj da menjas sifru';
$strKeyname = 'Ime Kljuèa';
$strKill = 'Stopiraj';

$strLength = 'Duzina';
$strLengthSet = 'Duzina/Vrednost*';
$strLimitNumRows = 'Broj rekorda po strani';
$strLineFeed = 'Karakter za liniju: \\n';
$strLines = 'Linije';
$strLinesTerminatedBy = 'Linije se završavaju sa';
$strLocationTextfile = 'Lokacija tekstualnog fajla';
$strLogin = 'Logovanje';
$strLogout = 'Izlogivanje';
$strLogPassword = 'Password:';
$strLogUsername = 'Username:';

$strModifications = 'Izmene su snimljene';
$strModify = 'Promeni';
$strModifyIndexTopic = 'Izmeni kljuè';
$strMoveTable = 'Pomeri tabelu u (baza<b>.</b>tabela):';
$strMoveTableOK = 'Tabela %s je pomereno u %s.';
$strMySQLReloaded = 'MySQL resetovan (reload).';
$strMySQLSaid = 'MySQL rece: ';
$strMySQLServerProcess = 'MySQL %pma_s1% startovan na %pma_s2%, ulogovan kao %pma_s3%';
$strMySQLShowProcess = 'Prikaži listu procesa';
$strMySQLShowStatus = 'Prikaži MySQL runtime informacije';
$strMySQLShowVars = 'Prikaži MySQL sistemske promenljive';

$strName = 'Ime';
$strNext = 'Sledeæi';
$strNo = 'Ne';
$strNoDatabases = 'Baza ne postoji';
$strNoDropDatabases = '"DROP DATABASE" komanda je onemogucena.';
$strNoFrames = 'phpMyAdmin vise voli da radi za <b>frames-capable</b> browser-ima.';
$strNoIndex = 'Kljuè nije definisan!';
$strNoIndexPartsDefined = 'Deo za kljuè nije definisan!';
$strNoModification = 'Nema nikakvih promena';
$strNone = 'Ništa';
$strNoPassword = 'Nema sifre';
$strNoPrivileges = 'Nema privilegija';
$strNoRights = 'Vama nije dozvoljeno da budete ovde!';
$strNoTablesFound = 'Tabela nije pronadjena u bazi.';
$strNotNumber = 'Ovo nije broj!';
$strNotValidNumber = ' nije odgovarajuci broj rekorda!';
$strNoUsersFound = 'Korisnik(ci) nije nadjen.';
$strNull = 'Null';

$strOftenQuotation = 'Navodnici koji se koriste. OPCIONO se misli da neka polja mogu, ali ne moraju da budu pod znacima navoda.';
$strOptimizeTable = 'Optimizuj tabelu';
$strOptionalControls = 'Opciono. Karakter koji prethodi specijalnim karakterima.';
$strOptionally = 'OPCIONO';
$strOr = 'ili';
$strOverhead = 'Prekoraèenje';

$strPartialText = 'Deo teksta';
$strPassword = 'Sifra';
$strPasswordEmpty = 'Sifra je prazna!';
$strPasswordNotSame = 'Sifra nije identicna!';
$strPHPVersion = 'verzija PHP-a';
$strPmaDocumentation = 'phpMyAdmin dokumentacija';
$strPmaUriError = '<tt>$cfg[\'PmaAbsoluteUri\']</tt> deo MORA biti setovan u konfiguracijonom fajlu!';
$strPos1 = 'Pocetak';
$strPrevious = 'Prethodna';
$strPrimary = 'Primarni kljuè';
$strPrimaryKey = 'Primarni kljuè';
$strPrimaryKeyHasBeenDropped = 'Primarni kljuè je izbrisan';
$strPrimaryKeyName = 'Ime za primarni kljuè mora da bude... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>može i mora</b> da bude ime i <b>samo</b> ime primarnog kljuèa!)';
$strPrintView = 'Za štampu';
$strPrivileges = 'Privilegije';
$strProperties = 'Informacije';

$strQBE = 'Upit po primeru';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'SQL upit na bazi <b>%s</b>:';

$strRecords = 'Polja';
$strReferentialIntegrity = 'Proveri validnost linkova:';
$strReloadFailed = 'restartovanje MySQL-a neuspesno.';
$strReloadMySQL = 'resetuj MySQL (reload)';
$strRememberReload = 'Ne zaboravi da restartujes (reload) server.';
$strRenameTable = 'Promeni ime tabele u ';
$strRenameTableOK = 'Tabeli %s promenjeno ime u %s';
$strRepairTable = 'Popravi tabelu';
$strReplace = 'Zameni';
$strReplaceTable = 'Zameni podatke u tabeli sa fajlom';
$strReset = 'Resetuj';
$strReType = 'Ponovite unos';
$strRevoke = 'Zabrani';
$strRevokeGrant = 'Zabrani Grant';
$strRevokeGrantMessage = 'Zabranili ste Grant privilegije za  %s';
$strRevokeMessage = 'Zabranili ste privilegije za %s';
$strRevokePriv = 'Zabrani privilegije';
$strRowLength = 'Velièina rekorda';
$strRows = 'Rekordi';
$strRowsFrom = 'pocni od rekorda';
$strRowSize = ' Velièina Rekorda ';
$strRowsModeHorizontal = 'horinzontalnom';
$strRowsModeOptions = 'u %s modu i ponovi zaglavlje posle svakog %s reda';
$strRowsModeVertical = 'vertikalnom';
$strRowsStatistic = 'Statistika Rekorda';
$strRunning = 'startovana na %s';
$strRunQuery = 'Izvrši SQL Upit';
$strRunSQLQuery = 'Izvrši SQL upit/upite na bazi %s';

$strSave = 'Snimi';
$strSelect = 'Selektuj';
$strSelectADb = 'Izaberite bazu';
$strSelectAll = 'Selektuj sve';
$strSelectFields = 'Izaberi polja (najmanje jedno)';
$strSelectNumRows = 'u upitu';
$strSend = 'Snimi kao fajl';
$strServerChoice = 'Izbor servera';
$strServerVersion = 'Verzija server';
$strSetEnumVal = 'Ako je polje "enum" ili "set", unesite vrednosti u formatu: \'a\',\'b\',\'c\'...<br />Ako vam treba mozda obrnuta kosa crta ("\") ili jednostruki znak navoda ("\'") koristite ih u eskepovanom obliku (na primer \'\\\\xyz\' ili \'a\\\'b\').';
$strShow = 'Prikaži';
$strShowAll = 'Prikaži sve';
$strShowCols = 'Prikaži kolone';
$strShowingRecords = 'Prikaz rekorda';
$strShowPHPInfo = 'Prikaži informacije o PHP-u';
$strShowTables = 'Prikaži tabele';
$strShowThisQuery = ' Prikaži ovaj upit ponovo ';
$strSingly = '(po jednom polju)';
$strSize = 'Velièina';
$strSort = 'Sortiranje';
$strSpaceUsage = 'Zauzeæe';
$strSQLQuery = 'SQL upit';
$strStatement = 'Ime';
$strStrucCSV = 'CSV format';
$strStrucData = 'Struktura i podaci';
$strStrucDrop = 'Dodaj \'drop table\'';
$strStrucExcelCSV = 'CSV za MS Excel';
$strStrucOnly = 'Samo Struktura';
$strSubmit = 'Startuj';
$strSuccess = 'Vas SQL upit je uspesno izvrsen';
$strSum = 'Ukupno';

$strTable = 'Tabela';
$strTableComments = 'Komentar tabele';
$strTableEmpty = 'Ima tabele je prazno!';
$strTableHasBeenDropped = 'Tabela %s je obrisana';
$strTableHasBeenEmptied = 'Tabela %s je ispraznjena';
$strTableHasBeenFlushed = 'Tabela %s je refresovana';
$strTableMaintenance = 'Akcije na tabeli';
$strTables = '%s tabela/tabele';
$strTableStructure = 'Struktura tabele';
$strTableType = 'Tip tabele';
$strTextAreaLength = ' Zbog velièine ovog polja,<br /> polje necete moci da editujete ';
$strTheContent = 'Sadrzaj datoteke je dodat u vasu bazu.';
$strTheContents = 'Sadrzaj tabele zameni sa sadrzajem fajla sa identicnim primarnim i jedinstvenim (unique) kljuèem.';
$strTheTerminator = 'Separator polja u datoteci.';
$strTotal = 'ukupno';
$strType = 'Tip';

$strUncheckAll = 'Demarkiraj sve';
$strUnique = 'Jedinstveni kljuè';
$strUnselectAll = 'Deselektuj sve';
$strUpdatePrivMessage = 'Promenili ste privilegije za %s.';
$strUpdateProfile = 'Promeni profil:';
$strUpdateProfileMessage = 'Profil je promenjen.';
$strUpdateQuery = 'Update SQL Upit';
$strUsage = 'Zauzeæe';
$strUseBackquotes = 'Koristi \' za ogranièavanje imena polja';
$strUser = 'Korisnik';
$strUserEmpty = 'Ime korisnika je prazno!';
$strUserName = 'Ime korisnika';
$strUsers = 'Korisnici';
$strUseTables = 'Koristi tabele';

$strValue = 'Vrednost';
$strViewDump = 'Prikaži dump (shemu) tabele';
$strViewDumpDB = 'Prikaži dump (shemu) baze';

$strWelcome = 'Dobrodošli na %s';
$strWithChecked = 'Na selektovanim:';
$strWrongUser = 'Pogresno korisnicko ime/sifra. Pristup odbijen.';

$strYes = 'Da';

$strZip = '"zip-ovano"';
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
$strLinksTo = 'Links to';  //to translate

$strMissingBracket = 'Missing Bracket';  //to translate
$strMySQLCharset = 'MySQL Charset';  //to translate

$strNoDescription = 'no Description';  //to translate
$strNoExplain = 'Skip Explain SQL';  //to translate
$strNoPhp = 'without PHP Code';  //to translate
$strNoQuery = 'Nema SQL upita!';  //to translate
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
?>
