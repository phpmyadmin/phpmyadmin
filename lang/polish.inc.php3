<?php
/* $Id$ */

$charset = 'iso-8859-2';
$text_dir = 'ltr';
$left_font_family = 'verdana, helvetica, arial ce, arial, sans-serif';
$right_font_family = 'helvetica, arial ce, arial, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('bajtów', 'KB', 'MB', 'GB');

$day_of_week = array('Nie', 'Pon', 'Wto', '¦ro', 'Czw', 'Pi±', 'Sob');
$month = array('Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Pa¼', 'Lis', 'Gru');
// See http://www.php3.net/manual/en/function.strftime.php3 to define the
// variable below
$datefmt = '%d %B %Y, %H:%M';


$strAccessDenied = 'Brak dostêpu';
$strAction = 'Dzia³anie';
$strAddDeleteColumn = 'Dodanie/usuniêcie pól';
$strAddDeleteRow = 'Dodanie/usuniêcie wierszów kryteriów';
$strAddNewField = 'Dodanie nowego pola';
$strAddPriv = 'Dodanie nowych uprawnien';
$strAddPrivMessage = 'Dodane zosta³y nowe uprawnienia.';
$strAddSearchConditions = 'Dodanie warunków przeszukiwania (warunek dla "where"):';
$strAddToIndex = 'Dodanie do indeksu &nbsp;%s&nbsp;kolumn';
$strAddUser = 'Dodanie nowego u¿ytkownika';
$strAddUserMessage = 'Dodany zosta³ nowy uzytkownik.';
$strAffectedRows = 'Zmodyfikowanych rekordów:';
$strAfter = 'Po %s';
$strAfterInsertBack = 'Powrót';
$strAfterInsertNewInsert = 'Wstawienie nowego rekordu';
$strAll = 'Wszystko';
$strAlterOrderBy = 'Sortowanie tabeli wg';
$strAnalyzeTable = 'Analizowanie tabeli';
$strAnd = 'Oraz';
$strAnIndex = 'Do %s dodany zosta³ indeks';
$strAny = 'Dowolny';
$strAnyColumn = 'Dowolna kolumna';
$strAnyDatabase = 'Dowolna baza danych';
$strAnyHost = 'Dowolny host';
$strAnyTable = 'Dowolna tabela';
$strAnyUser = 'Dowolny u¿ytkownik';
$strAPrimaryKey = 'Do %s dodany zosta³ klucz podstawowy';
$strAscending = 'Rosn±co';
$strAtBeginningOfTable = 'Na pocz±tku tabeli';
$strAtEndOfTable = 'Na koñcu tabeli';
$strAttr = 'Atrybuty';

$strBack = 'Powrót';
$strBinary = ' Binarne ';
$strBinaryDoNotEdit = ' Binarne - nie do edycji ';
$strBookmarkDeleted = 'Zapamiêtane zapytanie SQL zosta³o usuniête.';
$strBookmarkLabel = 'Nazwa';
$strBookmarkQuery = 'Zapamiêtane zapytanie SQL';
$strBookmarkThis = 'Zapamiêtanie zapytania SQL';
$strBookmarkView = 'Tylko do pokazania';
$strBrowse = 'Przegl±danie';
$strBzip = '".bz2"';

$strCantLoadMySQL = 'nie mo¿na za³adowac modu³u MySQL,<br />proszê sprawdziæ konfiguracjê PHP.';
$strCantRenameIdxToPrimary = 'Nie mo¿na zmieniæ nazwy indeksu na PRIMARY!';
$strCardinality = 'Moc';
$strCarriage = 'Znak powrotu: \\r';
$strChange = 'Zmiana';
$strCheckAll = 'Zaznaczenie wszystkich';
$strCheckDbPriv = 'Sprawdzanie uprawnieñ dla bazy danych';
$strCheckTable = 'Sprawdzanie tabeli';
$strColumn = 'Kolumna';
$strColumnNames = 'Nazwy kolumn';
$strCompleteInserts = 'Pe³ne dodania';
$strConfirm = 'Czy na pewno to zrobiæ?';
$strCookiesRequired = 'Odt±d musi byæ w³±czona obs³uga "cookies".';
$strCopyTable = 'Skopiowanie tabeli do (bazadanych<b>.</b>tabela):';
$strCopyTableOK = 'Tabela %s zosta³a skopiowana do %s.';
$strCreate = 'Utworzenie';
$strCreateIndex = 'Utworzenie indeksu dla %s kolumn';
$strCreateIndexTopic = 'Utworzenie nowego indeksu';
$strCreateNewDatabase = 'Utworzenie nowej bazy danych';
$strCreateNewTable = 'Utworzenie nowej tabeli dla bazy danych ';
$strCriteria = 'Kryteria';

$strData = 'Dane';
$strDatabase = 'Baza danych ';
$strDatabaseHasBeenDropped = 'Baza danych %s zosta³a usuniêta.';
$strDatabases = 'bazy danych';
$strDatabasesStats = 'Statystyki baz danych';
$strDataOnly = 'Tylko dane';
$strDefault = 'Domy¶lnie';
$strDelete = 'Skasowanie';
$strDeleted = 'Rekord zosta³ skasowany';
$strDeletedRows = 'Skasowane rekordy:';
$strDeleteFailed = 'Kasowanie nie powiod³o sie!';
$strDeleteUserMessage = 'Usunales uzytkownika  %s.';
$strDescending = 'Malej±co';
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
$strEnd = 'Koniec';
$strEnglishPrivileges = ' Uwaga: Uprawnienia MySQL s± oznaczone w jêz. angielskim ';
$strError = 'B³±d';
$strExtendedInserts = 'Rozszerzone dodania';
$strExtra = 'Dodatkowy';

$strField = 'Pole';
$strFieldHasBeenDropped = 'Pole %s zosta³o usuniête';
$strFields = 'Pola';
$strFieldsEmpty = ' Pole count jest puste! ';
$strFieldsEnclosedBy = 'Pola zawarte w';
$strFieldsEscapedBy = 'Pola poprzedzone przez';
$strFieldsTerminatedBy = 'Pola oddzielane przez';
$strFixed = 'sta³y';
$strFlushTable = 'Prze³adowanie tabeli ("FLUSH")';
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
$strHome = 'Wej¶cie';
$strHomepageOfficial = 'Oficjalna strona phpMyAdmin';
$strHomepageSourceforge = 'Pobranie wersji Sourceforge phpMyAdmin';
$strHost = 'Host';
$strHostEmpty = 'Brak nazwy hosta!';

$strIdxFulltext = 'Pe³ny tekst';
$strIfYouWish = 'Prosze podaæ listê kolumn rozdzielon± przecinkami aby za³adowaæ tylko wybrane kolumny.';
$strIgnore = 'Ignoruj';
$strIndex = 'Indeks';
$strIndexHasBeenDropped = 'Klucz %s zosta³ usuniêty';
$strIndexes = 'Indeksy';
$strIndexName = 'Nazwa indeksu :';
$strIndexType = 'Rodzaj indeksu :';
$strInsert = 'Dodanie';
$strInsertAsNewRow = 'Dodanie jako nowego rekordu';
$strInsertNewRow = 'Dodanie nowego rekordu';
$strInsertTextfiles = 'Dodanie pliku tekstowego do tabeli';
$strInsertedRows = 'Wprowadzone rekordy:';
$strInstructions = 'Instrukcje';
$strInUse = 'w u¿yciu';
$strInvalidName = '"%s" jest s³owem zarezerwowanym, nie mo¿na u¿yæ go jako nazwy bazy danych/tabeli/pola.';

$strKeepPass = 'Nie zmieniaj has³a';
$strKeyname = 'Nazwa klucza';
$strKill = 'Zabicie';

$strLength = 'D³ugo¶æ';
$strLengthSet = 'D³ugo¶æ/Warto¶ci*';
$strLimitNumRows = 'rekordów na stronie';
$strLineFeed = 'Kod wysuniêcia linii: \\n';
$strLines = 'Linie';
$strLinesTerminatedBy = 'Linie zakoñczone przez';
$strLocationTextfile = 'Lokalizacja pliku tekstowego';
$strLogin = 'Login';
$strLogout = 'Wylogowanie';
$strLogPassword = 'Has³o:';
$strLogUsername = 'U¿ytkownik:';$strRowsModeVertical=" vertical ";

$strModifications = 'Modyfikacje zosta³y zapamiêtane';
$strModify = 'Modifikacja';
$strModifyIndexTopic = 'Modyfikacja indeksu';
$strMoveTable = 'Przeniesienie tabeli do (bazadanych<b>.</b>tabela):';
$strMoveTableOK = 'Tabela %s zosta³a przeniosna do %s.';
$strMySQLReloaded = 'MySQL prze³adowany.';
$strMySQLSaid = 'MySQL zwróci³ komunikat: ';
$strMySQLServerProcess = 'MySQL %pma_s1% uruchomiony na %pma_s2% jako %pma_s3%';
$strMySQLShowProcess = 'Pokazuj procesy';
$strMySQLShowStatus = 'Informacje o stanie serwera MySQL';
$strMySQLShowVars = 'Zmienne systemowe serwera MySQL';

$strName = 'Nazwa';
$strNbRecords = 'Ile';
$strNext = 'Nastêpne';
$strNo = 'Nie';
$strNoDatabases = 'Brak baz danych';
$strNoDropDatabases = 'Polecenie "DROP DATABASE" jest zablokowane.';
$strNoFrames='phpMyAdmin jest bardziej przyjazny w przegl±darkach <b>obs³uguj±cych ramki</b>';
$strNoIndex = 'Brak zdefiniowanego indeksu!';
$strNoIndexPartsDefined = 'Brak zdefiniowanych czê¶ci indeksu!';
$strNoModification = 'Bez zmian';
$strNone = 'Brak';
$strNoPassword = 'Brak has³a';
$strNoPrivileges = 'Brak uprawnieñ';
$strNoQuery = 'Brak zapytania SQL!';
$strNoRights = 'Brak wystarczajacych uprawnieñ!';
$strNoTablesFound = 'Nie znaleziono tabeli w bazie danych.';
$strNotNumber = 'To nie jest liczba!';
$strNotValidNumber = ' nie jest poprawnym numerem rekordu!';
$strNoUsersFound = 'Nie znaleziono u¿ytkownika(ów).';
$strNull = 'Null';

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
$strPos1 = 'Pocz±tek';
$strPrevious = 'Poprzednie';
$strPrimary = 'Podstawowy';
$strPrimaryKey = 'Podstawowy klucz';
$strPrimaryKeyHasBeenDropped = 'Klucz podstawowy zosta³ usuniêty';
$strPrimaryKeyName = 'Nazw± podstawowego klucza musi byæ... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>musi</b> byæ nazw± <b>jedynie</b> klucza podstawowego!)';
$strPrintView = 'Widok do wydruku';
$strPrivileges = 'Uprawnienia';
$strProperties = 'W³asciwo¶ci';

$strQBE = 'Zapytanie przez przyk³ad';
$strQBEDel = 'Usuñ';
$strQBEIns = 'Wstaw';
$strQueryOnDb = 'Zapytanie SQL dla bazy danych <b>%s</b>:';

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
$strRevokeGrantMessage = 'Cofniête zosta³y uprawnienia dla %s';
$strRevokeMessage = 'Cofniête zosta³y uprawnienia dla %s';
$strRevokePriv = 'Cofniêcie uprawnieñ';
$strRowLength = 'D³ugo¶æ rekordu';
$strRows = 'Ilo¶æ rekordów';
$strRowSize = ' Rozmiar rekordu ';
$strRowsFrom = 'rekordów pocz±wszy od';
$strRowsStatistic = 'Statystyka rekordów';
$strRowsModeVertical= 'pionowo';
$strRowsModeHorizontal= 'poziomo';
$strRowsModeOptions= 'w trybie %s powtórz nag³ówki po %s komórkach';
$strRunning = 'uruchomiony na %s';
$strRunQuery = 'Wykonanie zapytania';
$strRunSQLQuery = 'Wykonanie zapytania/zapytañ SQL do bazy danych %s';

$strSave = 'Zachowanie';
$strSelect = 'Wybór';
$strSelectADb = 'Proszê wybraæ bazê danych';
$strSelectAll = 'Zaznaczenie wszystkich';
$strSelectFields = 'Wybór pól (co najmniej jedno):';
$strSelectNumRows = 'w zapytaniu';
$strSend = 'wys³anie';
$strServerChoice = 'Wybór serwera';
$strSequence = 'Sekwencja';
$strServerVersion = 'Wersja serwera';
$strSetEnumVal = 'Je¿eli pole jest typu "ENUM" lub "SET", warto¶ci wprowadza siê w formacie: \'a\',\'b\',\'c\'...<br />Je¿eli potrzeba wprowadziæ odwrotny uko¶nik ("\") lub apostrof ("\'"), nale¿y je poprzedziæ odwrotnym uko¶nikiem (np.: \'\\\\xyz\' lub \'a\\\'b\').';
$strShow = 'Pokazanie';
$strShowAll = 'Pokazanie wszystkiego';
$strShowCols = 'Pokazanie kolumn';
$strShowPHPInfo = 'Informacje o PHP';
$strShowTables = 'Pokazanie tabel';
$strShowThisQuery = ' Ponowne wywo³anie tego zapytania ';
$strShowingRecords = 'Pokazanie rekordów ';
$strSingly = '(pojedynczo)';
$strSize = 'Rozmiar';
$strSort = 'Sortuj';
$strSpaceUsage = 'Wykorzystanie przestrzeni';
$strSQLQuery = 'zapytanie SQL';
$strStartingRecord = 'Rozpoczêcie rekordu';
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
$strTableHasBeenDropped = 'Tabela %s zosta³a usuniêta';
$strTableHasBeenEmptied = 'Tabela %s zosta³a opró¿niona';
$strTableHasBeenFlushed = 'Tabela %s zosta³a prze³adowana';
$strTableMaintenance = 'Zarz±dzanie tabel±';
$strTableStructure = 'Struktura tabeli dla ';
$strTableType = 'Typ tabeli';
$strTables = '%s tabel(a)';
$strTextAreaLength = ' To pole mo¿e nie byæ edytowalne,<br /> z powodu swojej d³ugo¶ci ';
$strTheContent = 'Zawarto¶æ pliku zosta³a do³±czona.';
$strTheContents = 'Zawarto¶æ pliku zastapi³a dane wybranej tabeli, których podstawowy lub unikalny klucz by³ identyczny.';
$strTheTerminator = 'Znak rozdzielaj±cy pola.';
$strTotal = 'wszystkich';
$strType = 'Typ';

$strUncheckAll = 'Odznaczenie wszystkich';
$strUnique = 'Unikalny';
$strUnselectAll = 'Odznaczenie wszystkich';
$strUpdatePrivMessage = 'Uaktualni³e¶ uprawnienia dla %s.';
$strUpdateProfile = 'Uaktualnienie profilu:';
$strUpdateProfileMessage = 'Profil zosta³ uaktualniony.';
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

$strWelcome = 'Witamy w %s';
$strWithChecked = 'Zaznaczone:';
$strWrongUser = 'B³êdne pola u¿ytkownik/has³o. Brak dostêpu.';

$strYes = 'Tak';

$strZip = '".zip"';

// To translate
?>
