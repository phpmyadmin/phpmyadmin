<?php
/* $Id$ */
// Peter Bakondy <bakondyp@freemail.hu>

$charset = 'iso-8859-2';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = '.';
$byteUnits = array('Bájt', 'KB', 'MB', 'GB');

$day_of_week = array('V', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo');
$month = array('Jan', 'Feb', 'Márc', 'Ápr', 'Máj', 'Jún', 'Júl', 'Aug', 'Szept', 'Okt', 'Nov', 'Dec');
// Leírás a $datefmt változó definiálásához:
// http://www.php.net/manual/en/function.strftime.php
$datefmt = '%Y. %B %d. %H:%M';


$strAccessDenied = 'Hozzáférés megtagadva';
$strAction = 'Parancs';
$strAddDeleteColumn = 'Mezõ Oszlopokat Hozzáad/Töröl';
$strAddDeleteRow = 'Kritérium Sort Hozzáad/Töröl';
$strAddNewField = 'Új mezõ hozzáadása';
$strAddPriv = 'Új privilégiumot ad';
$strAddPrivMessage = 'Az új privilégiumot hozzáadtam.';
$strAddSearchConditions = 'Keresési feltételek megadása (az \"ahol\" kikötések):';
$strAddToIndex = 'Adj az indexhez &nbsp;%s&nbsp;oszlopot';
$strAddUser = 'Új felhasználó hozzáadása';
$strAddUserMessage = 'Az új felhasználót felvettem.';
$strAffectedRows = 'Keresett sorok:';
$strAfter = '%s után';
$strAfterInsertBack = 'Vissza az elõzõ oldalra';
$strAfterInsertNewInsert = 'Új sor beszúrása';
$strAll = 'Mind';
$strAlterOrderBy = 'Tábla megváltozása rendezve e szerint:';
$strAnalyzeTable = 'Tábla vizsgálat';
$strAnd = 'És';
$strAnIndex = 'Indexet hozzáadtam: %s';
$strAny = 'Bármely';
$strAnyColumn = 'Bármely oszlop';
$strAnyDatabase = 'Bármely adatbázis';
$strAnyHost = 'Bármely hoszt';
$strAnyTable = 'Bármely tábla';
$strAnyUser = 'Bármely felhasználó';
$strAPrimaryKey = 'Elsõdleges kulcsot hozzáadtam: %s';
$strAscending = 'Növekvõ';
$strAtBeginningOfTable = 'A tábla elejénél';
$strAtEndOfTable = 'A tábla végénél';
$strAttr = 'Tulajdonságok';

$strBack = 'Vissza';
$strBinary = 'Bináris';
$strBinaryDoNotEdit = 'Bináris - nem szerkeszthetõ';
$strBookmarkDeleted = 'A könyvjelzõt töröltem.';
$strBookmarkLabel = 'Felirat';
$strBookmarkQuery = 'Feljegyzett SQL-kérés';
$strBookmarkThis = 'Jegyezd fel az SQL-kérés';
$strBookmarkView = 'Csak megnézhetõ';
$strBrowse = 'Tartalom';
$strBzip = '"bzip-pel tömörítve"';

$strCantLoadMySQL = 'nem tudom betölteni a MySQL bõvítményt,<br />ellenõrizd a PHP konfigurációt.';
$strCantRenameIdxToPrimary = 'Nem tudom átnevezni az indexet PRIMARY-vá!';
$strCardinality = 'Számosság';
$strCarriage = 'Kocsivissza: \\r';
$strChange = 'Változtat';
$strChangePassword = 'Jelszo megvaltoztatasa';
$strCheckAll = 'Összeset kijelöli';
$strCheckDbPriv = 'Adatbázis Privilégiumok Ellenõrzése';
$strCheckTable = 'Tábla ellenõrzés';
$strColumn = 'Oszlop';
$strColumnNames = 'Oszlop nevek';
$strCompleteInserts = 'Mezõneveket is hozzáadja';
$strConfirm = 'Biztos, hogy végre akarod hajtani?';
$strCookiesRequired = 'A Cookie-kat most engedélyeznek kell.';
$strCopyTable = 'Tábla másolása ide (adatbázis<b>.</b>tábla):';
$strCopyTableOK = '%s táblát ide másoltam: %s.';
$strCreate = 'Létrehoz';
$strCreateIndex = 'Készíts egy indexet a(z)&nbsp;%s&nbsp;. oszlopon';
$strCreateIndexTopic = 'Új index létrehozása';
$strCreateNewDatabase = 'Új adatbázis létrehozása';
$strCreateNewTable = 'Új tábla létrehozása az adatbázisban: ';
$strCriteria = 'Kritérium';

$strData = 'Adat';
$strDatabase = 'Adatbázis ';
$strDatabaseHasBeenDropped = '%s adatbázist eldobtam.';
$strDatabases = 'adatbázisok';
$strDatabasesStats = 'Adatbázis statisztika';
$strDatabaseWildcard = 'Adatbazis (joker-karakterek elfogadva):';
$strDataOnly = 'Csak adatok';
$strDefault = 'Alapértelmezett';
$strDelete = 'Töröl';
$strDeleted = 'A sort töröltem';
$strDeletedRows = 'Törölt sorok:';
$strDeleteFailed = 'Törlés meghiúsult!';
$strDeleteUserMessage = '%s felhasználót töröltem.';
$strDescending = 'Csökkenõ';
$strDisplay = 'Kijelzõ';
$strDisplayOrder = 'Kijelzõ rendezés:';
$strDoAQuery = 'Csinálj egy "példa lekérdezést" (helyettesítõ karakter: "%")';
$strDocu = 'Dokumentáció';
$strDoYouReally = 'Biztos ez akarod? ';
$strDrop = 'Eldob';
$strDropDB = 'Adatbázis eldobása %s';
$strDropTable = 'Tábla eldobása';
$strDumpingData = 'Tábla adatok:';
$strDynamic = 'dinamikus';

$strEdit = 'Szerkeszt';
$strEditPrivileges = 'Privilégiumok szerkesztése';
$strEffective = 'Hatályos';
$strEmpty = 'Kiürít';
$strEmptyResultSet = 'A MySQL üreset adott vissza (nincsenek sorok).';
$strEnd = 'Vége';
$strEnglishPrivileges = ' Megjegyzés: A MySQL privilégium nevek az angolból származnak ';
$strError = 'Hiba';
$strExtendedInserts = 'Kiterjesztett beszúrások';
$strExtra = 'Extra';

$strField = 'Mezõ';
$strFieldHasBeenDropped = '%s mezõt eldobtam';
$strFields = 'Mezõk száma';
$strFieldsEmpty = ' A mezõ számossága nulla! ';
$strFieldsEnclosedBy = 'Mezõ lezárás';
$strFieldsEscapedBy = 'Mezõ escape karakter';
$strFieldsTerminatedBy = 'Mezõ vége';
$strFixed = 'rögzített';
$strFlushTable = 'Tábla kiírása ("FLUSH")';
$strFormat = 'Formátum';
$strFormEmpty = 'Hiányzó adat a formban !';
$strFullText = 'Teljes Szövegek';
$strFunction = 'Funkció';

$strGenTime = 'Létrehozás ideje';
$strGo = 'Végrehajt';
$strGrants = 'Engedélyek';
$strGzip = '"gzip-pel tömörítve"';

$strHasBeenAltered = 'megváltozott.';
$strHasBeenCreated = 'megszületett.';
$strHome = 'Kezdõlap';
$strHomepageOfficial = 'Hivatalos phpMyAdmin Honlap';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Letöltés Oldal';
$strHost = 'Hoszt';
$strHostEmpty = 'A hosztnév üres!';

$strIdxFulltext = 'Fulltext';
$strIfYouWish = 'Ha csak a tábla néhány oszlopát akarod megjeleníteni, adj meg egy vesszõkkel elválasztott mezõlistát.';
$strIgnore = 'Elutasít';
$strIndex = 'Index';
$strIndexes = 'Indexek';
$strIndexHasBeenDropped = '%s indexet eldobtam';
$strIndexName = 'Index név&nbsp;:';
$strIndexType = 'Index tipus&nbsp;:';
$strInsert = 'Beszúr';
$strInsertAsNewRow = 'Beszúrás új sorként';
$strInsertedRows = 'Beszúrt sorok:';
$strInsertNewRow = 'Új sor beszúrása';
$strInsertTextfiles = 'Szövegfájl tartalmának beszúrása a táblába';
$strInstructions = 'Parancs';
$strInUse = 'használatban';
$strInvalidName = '"%s" egy fenntartott szó, nem használhatod adatbázis/tábla/mezõ neveként.';

$strKeepPass = 'Ne változtasd meg a jelszót';
$strKeyname = 'Kulcsnév';
$strKill = 'Leállít';

$strLength = 'Hossz';
$strLengthSet = 'Hossz/Érték*';
$strLimitNumRows = 'Sorok száma oldalanként';
$strLineFeed = 'Soremelés: \\n';
$strLines = 'Sor';
$strLinesTerminatedBy = 'Sorok vége';
$strLocationTextfile = 'A szövegfájlt helye';
$strLogin = 'Belépés';
$strLogout = 'Kilépés';
$strLogPassword = 'Jelszó:';
$strLogUsername = 'Felhasználói név:';

$strModifications = 'A változásokat elmentettem';
$strModify = 'Változás';
$strModifyIndexTopic = 'Index változása';
$strMoveTable = 'Tábla áthelyezése ide (adatbázis<b>.</b>tábla):';
$strMoveTableOK = '%s táblát áthelyeztem ide: %s.';
$strMySQLReloaded = 'MySQL újratöltve.';
$strMySQLSaid = 'MySQL jelzi: ';
$strMySQLServerProcess = 'MySQL %pma_s1%, szerver: %pma_s2%, felhasználó: %pma_s3%';
$strMySQLShowProcess = 'Mutasd meg a folyamatokat';
$strMySQLShowStatus = 'Mutasd meg a MySQL futási információkat';
$strMySQLShowVars = 'Mutasd meg a MySQL rendszer változókat';

$strName = 'Neve';
$strNbRecords = 'Sorok száma';
$strNext = 'Következõ';
$strNo = 'Nem';
$strNoDatabases = 'Nincs adatbázis';
$strNoDropDatabases = '"DROP DATABASE" utasítás le van tiltva.';
$strNoFrames = 'A phpMyAdmin használhatóbb egy <b>frame-kezelõ</b> böngészõben.';
$strNoIndex = 'Nincs index meghatározva!';
$strNoIndexPartsDefined = 'Nincs index darab meghatározva!';
$strNoModification = 'Nincs változás';
$strNone = 'Nincs';
$strNoPassword = 'Nincs jelszó';
$strNoPrivileges = 'Nincs privilégium';
$strNoQuery = 'Nincs SQL kérés!';  //to translate
$strNoRights = 'Nincs elég jogod ennek végrehajtására!';
$strNoTablesFound = 'Nincs tábla az adatbázisban.';
$strNotNumber = 'Ez nem egy szám!';
$strNotValidNumber = ' nem érvényes sorszám!';
$strNoUsersFound = 'Nem találtam felhasználó(ka)t.';
$strNull = 'Null';

$strOftenQuotation = 'Gyakran idézõjel. Opcionálisan a char és varchar mezõk lezárhatók a \"lezárás\"-karakterrel.';
$strOptimizeTable = 'Tábla optimalizálás';
$strOptionalControls = 'Opcionális. Vezérlõk, amelyekkel írhatsz és olvashatsz speciális karaktereket.';
$strOptionally = 'Opcionális';
$strOr = 'Vagy';
$strOverhead = 'Felülírás';

$strPartialText = 'Részleges Szövegek';
$strPassword = 'Jelszó';
$strPasswordEmpty = 'A jelszó mezõ üres!';
$strPasswordNotSame = 'A jelszavak nem azonosak!';
$strPHPVersion = 'PHP Verzió';
$strPmaDocumentation = 'phpMyAdmin dokumentáció';
$strPmaUriError = '<tt>$cfgPmaAbsoluteUri</tt> erteket a konfiguracios fajlban KELL beallitani!';
$strPos1 = 'Kezdet';
$strPrevious = 'ELõzõ';
$strPrimary = 'Elsõdleges';
$strPrimaryKey = 'Elsõdleges kulcs';
$strPrimaryKeyHasBeenDropped = 'Az elsõdleges kulcsot eldobtam';
$strPrimaryKeyName = 'Az elsõdleges kulcs nevének "PRIMARY"-nak kell lennie!';
$strPrimaryKeyWarning = '("PRIMARY"-nak <b>kell</b> lennie, és <b>csak annak</b> szabad lennie az elsõdleges kulcsnak!)';
$strPrintView = 'Nyomtatási nézet';
$strPrivileges = 'Privilégiumok';
$strProperties = 'Tulajdonságok';

$strQBE = 'Példa lekérdezés';
$strQBEDel = 'Töröl';
$strQBEIns = 'Beszúr';
$strQueryOnDb = 'SQL-kérés <b>%s</b> adatbázison:';

$strRecords = 'Sorok';
$strReloadFailed = 'MySQL újratöltése sikertelen.';
$strReloadMySQL = 'MySQL újratöltése';
$strRememberReload = 'Ne felejtd el újratölteni a szervert.';
$strRenameTable = 'Tábla átnevezése erre';
$strRenameTableOK = '%s táblát átneveztem erre: %s';
$strRepairTable = 'Tábla javítás';
$strReplace = 'Csere';
$strReplaceTable = 'Tábla adatok és fájl cseréje';
$strReset = 'Töröl';
$strReType = 'Újraírás';
$strRevoke = 'Visszavon';
$strRevokeGrant = 'Visszavonást engedélyez';
$strRevokeGrantMessage = 'Visszavontad %s privilégiumait';
$strRevokeMessage = 'Visszavontam a %s privilégiumokat';
$strRevokePriv = 'Privilégiumok visszavonása';
$strRowLength = 'Sorhossz';
$strRows = 'Sorok';
$strRowsFrom = 'sor kezdve ezzel:';
$strRowSize = ' Sorméret ';
$strRowsModeHorizontal = 'vízszintes';
$strRowsModeOptions = '%s módon, a fejlécet %s soronként megismételve';
$strRowsModeVertical = 'függõleges';
$strRowsStatistic = 'Sor-statisztika';
$strRunning = 'helye %s';
$strRunQuery = 'Kérés végrehajtása';
$strRunSQLQuery = 'SQL parancs(ok) futtatása a(z) %s adatbázison';

$strSave = 'Ment';
$strSelect = 'Kiválaszt';
$strSelectADb = 'Válassz egy adatbázist';
$strSelectAll = 'Mindet kijelöli';
$strSelectFields = 'Mezõk kiválasztása (legalább egyet):';
$strSelectNumRows = 'kérésben';
$strSend = 'Fájlnév megadása';
$strServerChoice = 'Szerver Választás';
$strServerVersion = 'Szerver verzió';
$strSetEnumVal = 'Ha a mezõ tipusa "enum" vagy "set", akkor az értékeket ilyen formában írd be: \'a\',\'b\',\'c\'...<br />Ha backslash-t ("\") vagy aposztrófot ("\'") akarsz ezen értékek között használni, használd a backslash escape karaktert (pl \'\\\\xyz\' vagy \'a\\\'b\').';
$strShow = 'Mutat';
$strShowAll = 'Mutasd mindet';
$strShowCols = 'Mutasd az oszlopokat';
$strShowingRecords = 'Sorok megjelenítése ';
$strShowPHPInfo = 'PHP információ';
$strShowTables = 'Mutasd a táblákat';
$strShowThisQuery = ' Mutasd a parancsot itt újra ';
$strSingly = '(egyenként)';
$strSize = 'Méret';
$strSort = 'Sorrendezés';
$strSpaceUsage = 'Helyfoglalás';
$strSQLQuery = 'SQL-kérés';
$strStartingRecord = 'Kezdõ sor';
$strStatement = 'Adatok';
$strStrucCSV = 'CSV adat';
$strStrucData = 'Szerkezet és adatok';
$strStrucDrop = '\'Tábla eldobás\' hozzáadása';
$strStrucExcelCSV = 'M$ Excel CSV adat';
$strStrucOnly = 'Csak szerkezet';
$strSubmit = 'Végrehajt';
$strSuccess = 'Az SQL-kérést sikeresen végrehajtottam';
$strSum = 'Összesen';

$strTable = 'tábla ';
$strTableComments = 'Tábla megjegyzések';
$strTableEmpty = 'A táblanév helye üres!';
$strTableHasBeenDropped = '%s táblát eldobtam';
$strTableHasBeenEmptied = '%s táblát kiürítettem';
$strTableHasBeenFlushed = '%s táblát kiírtam';
$strTableMaintenance = 'Tábla karbantartás';
$strTables = '%s tábla';
$strTableStructure = 'Tábla szerkezet:';
$strTableType = 'Tábla tipusa';
$strTextAreaLength = ' Mivel ez a hossz,<br /> ez a mezõ nem szerkeszthetõ ';
$strTheContent = 'A fájl tartalmát beillesztettem.';
$strTheContents = 'A fájl és a kiválasztott tábla sorainak tartalmát azonos elsõdleges vagy egyedi kulccsal cseréli ki.';
$strTheTerminator = 'A mezõk lezárója.';
$strTotal = 'Összes';
$strType = 'Tipus';

$strUncheckAll = 'Összeset törli';
$strUnique = 'Egyedi';
$strUnselectAll = 'Mindet törli';
$strUpdatePrivMessage = 'Frissítettem a(z) %s privilégiumokat.';
$strUpdateProfile = 'Profil frissítés:';
$strUpdateProfileMessage = 'A profilt frissítettem.';
$strUpdateQuery = 'Kérés frissítés';
$strUsage = 'Méret';
$strUseBackquotes = 'Idézõjelek használata a tábla- és mezõneveknél';
$strUser = 'Felhasználó';
$strUserEmpty = 'A felhasználói név mezõje üres!';
$strUserName = 'Felhasználói név';
$strUsers = 'Felhasználók';
$strUseTables = 'Táblák használata';

$strValue = 'Érték';
$strViewDump = 'Tábla kiírás (vázlat) megnézése';
$strViewDumpDB = 'Adatbázis kiírás (vázlat) megnézése';

$strWelcome = 'Üdvözöl a %s';
$strWithChecked = 'A kijelöltekkel végzendõ mûvelet:';
$strWrongUser = 'Rossz felhasználói név/jelszó. Hozzáférés megtagadva.';

$strYes = 'Igen';

$strZip = '"zippel tömörítve"';

// To translate
$strRelationalIntegrity = 'Check relational integrity:';  //to translate
?>
