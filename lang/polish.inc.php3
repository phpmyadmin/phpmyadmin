<?php
/* $Id$ */

$charset = 'iso-8859-2';
$left_font_family = 'verdana, helvetica, arial ce, arial, sans-serif';
$right_font_family = 'helvetica, arial ce, arial, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('bajtów', 'KB', 'MB', 'GB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Brak dostêpu';
$strAction = 'Dzia³anie';
$strAddDeleteColumn = 'Dodanie/usuniêcie pól';
$strAddDeleteRow = 'Dodanie/usuniêcie wierszów kryteriów';
$strAddNewField = 'Dodanie nowego pola';
$strAddPriv = 'Dodanie nowych uprawnien';
$strAddPrivMessage = 'Dodane zosta³y nowe uprawnienia.';
$strAddSearchConditions = 'Dodanie warunków przeszukiwania (warunek dla "where"):';
$strAddUser = 'Dodanie nowego u¿ytkownika';
$strAddUserMessage = 'Dodany zosta³ nowy uzytkownik.';
$strAffectedRows = 'Zmodyfikowanych rekordów:';
$strAfter = 'Po';
$strAll = 'Wszystko';
$strAlterOrderBy = 'Sortowanie tabeli wg';
$strAnalyzeTable = 'Analizowanie tabeli';
$strAnd = 'Oraz';
$strAnIndex = 'Dodany zosta³ indeks dla ';
$strAny = 'Dowolny';
$strAnyColumn = 'Dowolna kolumna';
$strAnyDatabase = 'Dowolna baza danych';
$strAnyHost = 'Dowolny host';
$strAnyTable = 'Dowolna tabela';
$strAnyUser = 'Dowolny u¿ytkownik';
$strAPrimaryKey = 'Dodany zosta³ podstawowy klucz dla ';
$strAscending = 'Rosnaco';
$strAtBeginningOfTable = 'Na pocz±tku tabeli';
$strAtEndOfTable = 'Na koñcu tabeli';
$strAttr = 'Atrybuty';

$strBack = 'Powrót';
$strBinary = ' Binarne ';
$strBinaryDoNotEdit = ' Binarne - nie do edycji ';
$strBookmarkLabel = 'Nazwa';
$strBookmarkQuery = 'Zapamiêtane zapytanie SQL';
$strBookmarkThis = 'Zapamiêtanie zapytania SQL';
$strBookmarkView = 'Tylko do pokazania';
$strBrowse = 'Przegl±danie';
$strBzip = '".bz2"';

$strCantLoadMySQL = 'nie mo¿na za³adowac modu³u MySQL,<br />proszê sprawdziæ konfiguracjê PHP.';
$strCarriage = 'Znak powrotu: \\r';
$strChange = 'Zmiana';
$strCheckAll = 'Zaznaczenie wszystkich';
$strCheckDbPriv = 'Sprawdzanie uprawnieñ dla bazy danych';
$strCheckTable = 'Sprawdzanie tabeli';
$strColumn = 'Kolumna';
$strColumnEmpty = 'Brak nazw kolumn!';
$strColumnNames = 'Nazwy kolumn';
$strCompleteInserts = 'Pe³ne dodania';
$strConfirm = 'Czy na pewno to zrobic?';
$strCopyTable = 'Kopiowanie tabeli do';
$strCopyTableOK = 'Tabela %s zosta³a przekopiowana do %s.';
$strCreate = 'Utworzenie';
$strCreateNewDatabase = 'Utworzenie nowej bazy danych';
$strCreateNewTable = 'Utworzenie nowej tabeli dla bazy danych ';
$strCriteria = 'Kryteria';

$strData = 'Dane';
$strDatabase = 'Baza danych ';
$strDatabases = 'bazy danych';
$strDatabasesStats = 'Statystyki baz danych';
$strDataOnly = 'Tylko dane';
$strDbEmpty = 'Brak nazwy bazy danych!';
$strDefault = 'Domy¶lnie';
$strDelete = 'Skasowanie';
$strDeleted = 'Rekord zosta³ skasowany';
$strDeletedRows = 'Skasowane rekordy:';
$strDeleteFailed = 'Kasowanie nie powiod³o sie!';
$strDeletePassword = 'Kasowanie has³a';
$strDeleteUserMessage = 'U¿ytkownik zosta³ skasowany';
$strDelPassMessage = 'Skasowane zosta³o has³o u¿ytkownika';
$strDescending = 'Malej±co';
$strDisableMagicQuotes = '<b>Ostrze¿enie:</b> W³±czono opcje magic_quotes_gpc w konfiguracji PHP. Ta wersja PhpMyAdmin ¼le dzia³a, gdy opcja ta jest wy³±czona. Proszê zaznajomiæ siê z dokumentacj± do PHP, aby znale¼æ informacje, w jaki sposób wy³±czyæ tê opcjê.';
$strDisplay = 'Poka¿';
$strDisplayOrder = 'Kolejno¶æ wy¶wietlania:';
$strDoAQuery = 'Wykonaj "zapytanie przez przyk³ad" (znak globalny: "%")';
$strDocu = 'Dokumentacja';
$strDoYouReally = 'Czy na pewno wykonaæ ';
$strDrop = 'Usuniêcie';
$strDropDB = 'Usuniêcie bazy danych ';
$strDropTable = 'Usuniêcie tabeli';
$strDumpingData = 'Zrzut danych dla tabeli';
$strDynamic = 'zmienny';

$strEdit = 'Edycja';
$strEditPrivileges = 'Edycja uprawnieñ';
$strEffective = 'Efektywne';
$strEmpty = 'Wyczyszczenie';
$strEmptyResultSet = 'MySQL zwróci³ pusty wynik (np. zero rekordów).';
$strEnableMagicQuotes = '<b>Ostrze¿enie:</b> Wy³±czono opcjê magic_quotes_gpc w konfiguracji PHP. Ta wersja PhpMyAdmin ¼le dzia³a, gdy opcja ta jest wy³±czona. Proszê zaznajomiæ siê z dokumentacj± do PHP, aby znale¼æ informacje, w jaki sposób w³±czyæ tê opcjê.';
$strEnclosedBy = 'zawarty w';
$strEnd = 'Koniec';
$strEnglishPrivileges = ' Uwaga: Uprawnienia MySQL s± oznaczone w jêz. angielskim ';
$strError = 'B³±d';
$strEscapedBy = 'poprzedzony przez';
$strExtra = 'Dodatkowy';

$strField = 'Pole';
$strFields = 'Pola';
$strFieldsEmpty = ' Pole count jest puste! ';
$strFixed = 'sta³y';
$strFormat = 'Format';
$strFormEmpty = 'Brakuj±ca warto¶æ w formularzu!';
$strFullText = 'Pe³ny tekst';
$strFunction = 'Funkcja';

$strGenTime = 'Czas wygenerowania';
$strGo = 'Wykonanie';
$strGrants = 'Nadanie';
$strGzip = '".gz"';

$strHasBeenAltered = 'zosta³o zamienione.';
$strHasBeenCreated = 'zosta³o utworzone.';
$strHasBeenDropped = 'zosta³o skasowane.';
$strHasBeenEmptied = 'zosta³o wyczyszczone.';
$strHome = 'Wej¶cie';
$strHomepageOfficial = 'Oficjalna strona phpMyAdmin';
$strHomepageSourceforge = 'Pobranie wersji Sourceforge phpMyAdmin';
$strHost = 'Host';
$strHostEmpty = 'Brak nazwy hosta!';

$strIfYouWish = 'Prosze podaæ listê kolumn rozdzielon± przecinkami aby za³adowaæ tylko wybrane kolumny.';
$strIndex = 'Indeks';
$strIndexes = 'Indeksy';
$strInsert = 'Dodanie';
$strInsertAsNewRow = 'Dodanie jako nowego rekordu';
$strInsertIntoTable = 'Dodanie do tabeli';
$strInsertNewRow = 'Dodanie nowego rekordu';
$strInsertTextfiles = 'Dodanie pliku tekstowego do tabeli';
$strInsertedRows = 'Wprowadzone rekordy:';
$strInstructions = 'Instrukcje';
$strInUse = 'w u¿yciu';

$strKeyname = 'Nazwa klucza';
$strKill = 'Zabicie';

$strLength = 'D³ugo¶æ';
$strLengthSet = 'D³ugo¶æ/Warto¶ci*';
$strLimitNumRows = 'rekordów na stronie';
$strLineFeed = 'Kod wysuniêcia linii: \\n';
$strLines = 'Linie';
$strLocationTextfile = 'Lokalizacja pliku tekstowego';
$strLogin = 'Zalogowanie';
$strLogout = 'Wylogowanie';

$strModifications = 'Modyfikacje zosta³y zapamiêtane';
$strModify = 'Modifikacja';
$strMySQLReloaded = 'MySQL prze³adowany.';
$strMySQLSaid = 'MySQL zwróci³ komunikat: ';
$strMySQLShowProcess = 'Pokazuj procesy';
$strMySQLShowStatus = 'Informacje o stanie serwera MySQL';
$strMySQLShowVars = 'Zmienne systemowe serwera MySQL';

$strName = 'Nazwa';
$strNbRecords = 'Ile';
$strNext = 'Nastêpne';
$strNo = 'Nie';
$strNoDatabases = 'Brak baz danych';
$strNoDropDatabases = 'Polecenie "DROP DATABASE" jest zablokowane.';
$strNoModification = 'Bez zmian';
$strNoPassword = 'Brak has³a';
$strNoPrivileges = 'Brak uprawnieñ';
$strNoRights = 'Brak wystarczajacych uprawnieñ!';
$strNoTablesFound = 'Nie znaleziono tabeli w bazie danych.';
$strNotNumber = 'To nie jest liczba!';
$strNotValidNumber = ' nie jest poprawnym numerem rekordu!';
$strNoUsersFound = 'Nie znaleziono u¿ytkownika(ów).';
$strNull = 'Null';
$strNumberIndexes = ' ilo¶æ zaawansowanych indeksów ';

$strOffSet = 'Od';
$strOftenQuotation = 'Znaki cudzys³owu. OPCJONALNIE oznacza, ¿e tylko pola char oraz varchar s± zawarte w "cudzys³owach".';
$strOptimizeTable = 'Optymalizacja tabeli';
$strOptionalControls = 'Opcjonalnie. Okre¶lenie w jaki sposób zapisaæ lub odczytaæ znaki specjalne.';
$strOptionally = 'OPCJONALNIE';
$strOr = 'Lub';
$strOverhead = 'Nadmiar';

$strPartialText = 'Skrócony tekst';
$strPassword = 'Has³o';
$strPasswordEmpty = 'Puste has³o!';
$strPasswordNotSame = 'Has³a nie s± identyczne!';
$strPHPVersion = 'Wersja PHP';
$strPmaDocumentation = 'Dokumentacja dla phpMyAdmin';
$strPos1 = 'Poczatek';
$strPrevious = 'Poprzednie';
$strPrimary = 'Podstawowy';
$strPrimaryKey = 'Podstawowy klucz';
$strPrinterFriendly = 'Tabela w wersji do wydruku';
$strPrintView = 'Widok do wydruku';
$strPrivileges = 'Uprawnienia';
$strProducedAnError = 'zg³osi³ b³±d.';
$strProperties = 'W³asciwo¶ci';

$strQBE = 'Zapytanie przez przyk³ad';
$strQBEDel = 'Usuñ'; 
$strQBEIns = 'Wstaw';
$strQueryOnDb = 'Zapytanie SQL dla bazy danych ';

$strReadTheDocs = 'Proszê przeczytaæ dokumentacje';
$strRecords = 'Rekordy';
$strReloadFailed = 'Nie powiod³o siê prze³adowanie MySQL.';
$strReloadMySQL = 'Prze³adowanie MySQL';
$strRememberReload = 'Proszê pamiêtaæ o prze³adowaniu serwera.';
$strRenameTable = 'Zmiana nazwy tabeli na';
$strRenameTableOK = 'Tabela %s ma zmienion± nazwê na %s';
$strRepairTable = 'Naprawienie tabeli';
$strReplace = 'Zamiana';
$strReplaceTable = 'Zamiana danych tabeli z plikiem';
$strReset = 'Reset';
$strReType = 'Ponownie';
$strRevoke = 'Cofniêcie';
$strRevokeGrant = 'Cofniêcie uprawnieñ';
$strRevokeGrantMessage = 'Cofniête zosta³y uprawnienia dla';
$strRevokeMessage = 'Cofniête zosta³y uprawnienia dla';
$strRevokePriv = 'Cofniêcie uprawnieñ';
$strRowLength = 'D³ugo¶æ rekordu';
$strRows = 'Ilo¶æ rekordów';
$strRowSize = ' Rozmiar rekordu ';
$strRowsFrom = 'rekordów pocz±wszy od';
$strRowsStatistic = 'Statystyka rekordów';
$strRunning = 'uruchomiony na ';
$strRunQuery = 'Wykonanie zapytania';
$strRunSQLQuery = 'Wykonanie zapytania SQL do bazy danych ';

$strSave = 'Zachowanie';
$strSelect = 'Wybór';
$strSelectFields = 'Wybór pól (co najmniej jedno):';
$strSelectNumRows = 'w zapytaniu';
$strSend = 'wys³anie';
$strServerChoice = 'Wybór serwera';
$strSequence = 'Sekwencja';
$strServerVersion = 'Wersja serwera';
$strSetEnumVal = 'Je¿eli pole jest typu "ENUM" lub "SET", warto¶ci wprowadza siê w formacie: \'a\',\'b\',\'c\'...<br />Je¿eli potrzeba wprowadziæ odwrotny uko¶nik ("\") lub apostrof ("\'"), nale¿y je poprzedziæ odwrotnym uko¶nikiem (np.: \'\\\\xyz\' lub \'a\\\'b\').';
$strShow = 'Pokazanie';
$strShowPHPInfo = 'Informacje o PHP';
$strShowThisQuery = ' Ponowne wywo³anie tego zapytania ';
$strShowingRecords = 'Pokazanie rekordów ';
$strSingly = '(pojedynczo)';
$strSize = 'Rozmiar';
$strSort = 'Sortuj';
$strSpaceUsage = 'Wykorzystanie przestrzeni';
$strSQLQuery = 'zapytanie SQL';
$strStatement = 'Cecha';
$strStrucCSV = 'dane CSV';
$strStrucData = 'Struktura i dane';
$strStrucDrop = 'Dodanie \'drop table\'';
$strStrucExcelCSV = 'CSV dla MS Excel';
$strStrucOnly = 'Tylko struktura';
$strSubmit = 'Wys³anie';
$strSuccess = 'Zapytanie SQL zosta³o pomy¶lnie wykonane';
$strSum = 'Suma';

$strTable = 'tabela ';
$strTableComments = 'Komentarze tabeli';
$strTableEmpty = 'Brak nazwy tabeli!';
$strTableMaintenance = 'Zarz±dzanie tabel±';
$strTableStructure = 'Struktura tabeli dla ';
$strTableType = 'Typ tabeli';
$strTables = '%s tabel(a)';
$strTerminatedBy = 'zakoñczone przez';
$strTextAreaLength = ' To pole mo¿e nie byæ edytowalne,<br /> z powodu swojej d³ugo¶ci ';
$strTheContent = 'Zawarto¶æ pliku zosta³a do³±czona.';
$strTheContents = 'Zawarto¶æ pliku zastapi³a dane wybranej tabeli, których podstawowy lub unikalny klucz by³ identyczny.';
$strTheTerminator = 'Znak rozdzielaj±cy pola.';
$strTotal = 'wszystkich';
$strType = 'Typ';

$strUncheckAll = 'Odznaczenie wszystkich';
$strUnique = 'Unikalny';
$strUpdatePassMessage = 'Zmienione zosta³o has³o dla';
$strUpdatePassword = 'Zmiana has³a';
$strUpdatePrivMessage = 'Zmienione zosta³y uprawnienia dla';
$strUpdateQuery = 'Zmiana zapytania';
$strUsage = 'Wykorzystanie';
$strUseBackquotes = 'U¿ycie cudzys³owów z nazwami tabel i pól';
$strUser = 'U¿ytkownik';
$strUserEmpty = 'Brak nazwy u¿ytkownika!';
$strUserName = 'Nazwa u¿ytkownika';
$strUsers = 'U¿ytkownicy';
$strUseTables = 'U¿ycie tabel';

$strValue = 'Warto¶æ';
$strViewDump = 'Zrzut tabeli';
$strViewDumpDB = 'Zrzut bazy danych';

$strWelcome = 'Witamy w ';
$strWrongUser = 'B³êdne pola u¿ytkownik/has³o. Brak dostêpu.';

$strYes = 'Tak';

// To translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.'; //to translate
$strWithChecked = 'With checked:';
?>
