<?php
/* $Id$ */

$charset = 'windows-1250';
$text_dir = 'ltr';
$left_font_family = '"verdana CE", "Arial CE", verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = '"verdana CE", "Arial CE", helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = '.';
$byteUnits = array('Bajtù', 'KB', 'MB', 'GB');

$day_of_week = array('Nedìle', 'Pondìlí', 'Úterý', 'Støeda', 'Ètvrtek', 'Pátek', 'Sobota');
$month = array('ledna', 'února', 'bøezna', 'dubna', 'kvìtna', 'èervna', 'èervence', 'srpna', 'záøí', 'øíjna', 'listopadu', 'prosince');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%a %e. %b %Y, %H:%M';


$strAccessDenied = 'Pøístup odepøen';
$strAction = 'Akce';
$strAddDeleteColumn = 'Pøidat/Smazat sloupec polí';
$strAddDeleteRow = 'Pøidat/Smazat øádek s podmínkou';
$strAddNewField = 'Pøidat nové pole';
$strAddPriv = 'Pøidat nové privilegium';
$strAddPrivMessage = 'Oprávnìní bylo pøidáno.';
$strAddSearchConditions = 'Pøidat vyhledávací parametry (obsah dotazu po pøíkazu "WHERE"):';
$strAddToIndex = 'Pøidat do indexu  &nbsp;%s&nbsp;sloupcù';
$strAddUser = 'Pøidat nového uživatele';
$strAddUserMessage = 'Uživatel byl pøidán.';
$strAffectedRows = 'Ovlivnìné øádky:';
$strAfter = 'Po';
$strAfterInsertBack = 'Zpìt';
$strAfterInsertNewInsert = 'Vložit další øádek';
$strAll = 'Všechno';
$strAlterOrderBy = 'Zmìnit poøadí tabulky podle';
$strAnalyzeTable = 'Analyzovat tabulku';
$strAnd = 'a';
$strAnIndex = 'K tabulce %s byl pøidán index';
$strAny = 'Jakýkoliv';
$strAnyColumn = 'Jakýkoliv sloupec';
$strAnyDatabase = 'Jakákoliv databáze';
$strAnyHost = 'Jakýkoliv hostitel';
$strAnyTable = 'Jakákoliv tabulka';
$strAnyUser = 'Jakýkoliv uživatel';
$strAPrimaryKey = 'V tabulce %s byl vytvoøen primární klíè';
$strAscending = 'Vzestupnì';
$strAtBeginningOfTable = 'Na zaèátku tabulky';
$strAtEndOfTable = 'Na konci tabulky';
$strAttr = 'Atributy';

$strBack = 'Zpìt';
$strBinary = ' Binární ';
$strBinaryDoNotEdit = ' Binární - neupravujte ';
$strBookmarkDeleted = 'Položka byla smazána z oblíbených.';
$strBookmarkLabel = 'Název';
$strBookmarkQuery = 'Oblíbený SQL dotaz';
$strBookmarkThis = 'Pøidat tento SQL dotaz do oblíbených';
$strBookmarkView = 'Jen zobrazit';
$strBrowse = 'Projít';
$strBzip = '"zabzipováno"';

$strCantLoadMySQL = 'nelze nahrát rozšíøení pro MySQL,<br />prosím zkontrolujte nastavení PHP.';
$strCantRenameIdxToPrimary = 'Index nemùžete pøejmenovat na "PRIMARY"!';
$strCardinality = 'Mohutnost';
$strCarriage = 'Návrat vozíku (CR): \\r';
$strChange = 'Zmìnit';
$strCheckAll = 'Zaškrtnout vše';
$strCheckDbPriv = 'Zkontrolovat oprávnìní pro databázi';
$strCheckTable = 'Zkontrolovat tabulku';
$strColumn = 'Sloupec';
$strColumnNames = 'Názvy sloupcù';
$strCompleteInserts = 'Uplné inserty';
$strConfirm = 'Opravdu chcete toto provést?';
$strCopyTable = 'Kopírovat tabulku do (databáze<b>.</b>tabulka):';
$strCopyTableOK = 'Tabulka %s byla zkopírována do %s.';
$strCreate = 'Vytvoøit';
$strCreateIndex = 'Vytvoøit index na&nbsp;%s&nbsp;sloupcích';
$strCreateIndexTopic = 'Vytvoøit nový index';
$strCreateNewDatabase = 'Vytvoøit novou databázi';
$strCreateNewTable = 'Vytvoøit novou tabulku v databázi ';
$strCriteria = 'Podmínka';

$strData = 'Data';
$strDatabase = 'Databáze ';
$strDatabaseHasBeenDropped = 'Databáze %s byla zrušena.';
$strDatabases = 'databáze';
$strDatabasesStats = 'Statistiky databáze';
$strDataOnly = ' Jen data ';
$strDefault = 'Výchozí';
$strDelete = 'Smazat';
$strDeleted = 'Øádek byl smazán';
$strDeletedRows = 'Smazané øádky:';
$strDeleteFailed = 'Smazání selhalo!';
$strDeleteUserMessage = 'Byl smazán uživatel %s.';
$strDescending = 'Sestupnì';
$strDisplay = 'Zobrazit';
$strDisplayOrder = 'Seøadit podle:';
$strDoAQuery = 'Provést "dotaz podle pøíkladu" (žolík: "%")';
$strDocu = 'Dokumentace';
$strDoYouReally = 'Opravdu si pøeješ vykonat pøíkaz ';
$strDrop = 'Odstranit';
$strDropDB = 'Odstranit databázi ';
$strDropTable = 'Smazat tabulku';
$strDumpingData = 'Dumpuji data pro tabulku';
$strDynamic = 'dynamický';

$strEdit = 'Upravit';
$strEditPrivileges = 'Upravit oprávnìní';
$strEffective = 'Efektivní';
$strEmpty = 'Vyprázdnit';
$strEmptyResultSet = 'MySQL vrátil prázdný výsledek (tj. nulový poèet øádkù).';
$strEnd = 'Konec';
$strEnglishPrivileges = ' Poznámka: názvy oprávnìní v MySQL jsou uvádìna anglicky ';
$strError = 'Chyba';
$strExtendedInserts = 'Rozšíøené inserty';
$strExtra = 'Extra'; 

$strField = 'Sloupec';
$strFieldHasBeenDropped = 'Sloupec %s byl odstranìn';
$strFields = 'Poèet sloupcù';
$strFieldsEmpty = ' Nebyl zadán poèet sloupcù! ';
$strFieldsEnclosedBy = 'Názvy sloupcù uzavøené do';
$strFieldsEscapedBy = 'Názvy sloupcù escapovány';
$strFieldsTerminatedBy = 'Sloupce oddìlené';
$strFixed = 'pevný'; 
$strFlushTable = 'Vyprázdnit cache pro tabulku ("FLUSH")';
$strFormat = 'Formát'; 
$strFormEmpty = 'Chybìjící hodnota ve formuláøi !';
$strFullText = 'Celé texty';
$strFunction = 'Funkce';

$strGenTime = 'Vygenerováno:'; 
$strGo = 'Proveï';
$strGrants = 'Oprávnìní';
$strGzip = '"zagzipováno"';  

$strHasBeenAltered = 'byla zmìnìna.';
$strHasBeenCreated = 'byla vytvoøena.';
$strHome = 'Úvod';
$strHomepageOfficial = ' Oficiální stránka phpMyAdmina ';
$strHomepageSourceforge = ' nová stránka phpMyAdmina ';
$strHost = 'Hostitel';
$strHostEmpty = 'Jméno hostitele je prázdné!';

$strIdxFulltext = 'Fulltext';
$strIfYouWish = 'Pokud si pøeješ natáhnout jenom urèité sloupce z tabulky,
specifikuj je jako seznam polí oddìlených èárkou.';
$strIgnore = 'Ignorovat';
$strIndex = 'Index';
$strIndexes = 'Indexy'; 
$strIndexHasBeenDropped = 'Index %s byl odstranìn';
$strIndexName = 'Jméno indexu&nbsp;:';
$strIndexType = 'Typ indexu&nbsp;:';
$strInsert = 'Vložit';
$strInsertAsNewRow = ' Vložit jako nový øádek ';
$strInsertedRows = 'Vloženo øádkù:';
$strInsertNewRow = 'Vložit nový øádek';
$strInsertTextfiles = 'Vložit textové soubory do tabulky';
$strInstructions = 'Instrukce';
$strInUse = 'právì se používá'; 
$strInvalidName = '"%s" je rezervované slovo a proto ho nemùžete požít jako jméno databáze/tabulky/sloupce.';

$strKeepPass = 'Nemìnit heslo';
$strKeyname = 'Klíèovy název';
$strKill = ' Zabít ';

$strLength = 'Délka';
$strLengthSet = 'Délka/Set*';
$strLimitNumRows = 'záznamu na stránku';
$strLineFeed = 'Ukonèení øádku (Linefeed): \\n';
$strLines = 'Øádek';
$strLinesTerminatedBy = 'Øádky ukonèené';
$strLocationTextfile = 'Umístìní textového souboru';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Odhlásit se';

$strModifications = 'Zmìny byly uloženy';
$strModify = 'Úpravy';
$strModifyIndexTopic = 'Upravit index';
$strMoveTable = 'Pøesunout tabulku do (databáze<b>.</b>tabulka):';
$strMoveTableOK = 'Tabulka %s byla pøesunuta do %s.';
$strMySQLReloaded = 'MySQL znovu-naètena.';
$strMySQLSaid = 'MySQL hlásí: ';
$strMySQLServerProcess = 'MySQL %pma_s1% bìžící na %pma_s2% jako %pma_s3%';
$strMySQLShowProcess = 'Zobraz procesy';
$strMySQLShowStatus = 'Ukázat MySQL runtime informace';
$strMySQLShowVars = 'Ukázat MySQL systémové promìnné';

$strName = 'Název';
$strNbRecords = 'øádkù';
$strNext = 'Další';
$strNo = 'Ne';
$strNoDatabases = 'Žádné databáze';
$strNoDropDatabases = 'Pøíkaz "DROP DATABASE" je vypnutý.';
$strNoFrames = 'phpMyAdmin se lépe používá v prohlížeèi podporujícím rámy ("FRAME").';
$strNoIndex = 'Žádný index nebyl definován!';
$strNoIndexPartsDefined = 'Žádná èást indexu nebyla definována!';
$strNoModification = 'Žádná zmìna';
$strNone = 'Žádný';
$strNoPassword = 'Žádné heslo';
$strNoPrivileges = 'Žádná oprávnìní';
$strNoQuery = 'Žádný SQL dotaz!';
$strNoRights = 'Nemáte dostateèná práva na provedení této akce!';
$strNoTablesFound = 'V databázi nebyla nalezena ani jedna tabulka.';
$strNotNumber = 'Toto není èíslo!';  
$strNotValidNumber = ' není platné èíslo øádku!'; 
$strNoUsersFound = 'Žádný uživatel nenalezen.';
$strNull = 'Nulový';
$strNumberIndexes = ' Poèet rozšíøených indexù ';

$strOftenQuotation = 'Èasto uvozující znaky. OPTIONALLY znamená, že pouze pole
typu CHAR a VARCHAR jsou uzavøeny do "uzavíracích " znakù.';
$strOptimizeTable = 'Optimalizovat tabulku';
$strOptionalControls = 'Volitelné. Urèuje jak zapisovat nebo èíst speciální znaky.';
$strOptionally = 'Volitelnì';
$strOr = 'nebo';
$strOverhead = 'Navíc'; 

$strPartialText = 'Zkrácené texty';
$strPassword = 'Heslo';
$strPasswordEmpty = 'Heslo je prázdné!';
$strPasswordNotSame = 'Hesla nejsou stejná!';
$strPHPVersion = 'Verze PHP';
$strPmaDocumentation = 'Dokumentace phpMyAdmina';
$strPos1 = 'Zaèátek';
$strPrevious = 'Pøedchozí';
$strPrimary = 'Primární';
$strPrimaryKey = 'Primární klíè';
$strPrimaryKeyHasBeenDropped = 'Primární klíè byl odstranìn';
$strPrimaryKeyName = 'Jméno primárního klíèe musí být "PRIMARY"!';
$strPrimaryKeyWarning = '("PRIMARY" <b>musí</b> být jméno <b>pouze</b> primárního klíèe!)';
$strPrintView = 'Náhled k vytištìní';
$strPrivileges = 'Oprávnìní';
$strProperties = 'Vlastnosti';

$strQBE = 'Dotaz podle pøíkladu';
$strQBEDel = 'pøidat';
$strQBEIns = 'smazat';
$strQueryOnDb = 'SQL dotaz na databázi ';

$strRecords = 'Záznamù';
$strReloadFailed = 'Znovunaètení MySQL selhalo.';
$strReloadMySQL = 'Znovunaètení MySQL';
$strRememberReload = 'Nezapomeòte reloadovat server.';
$strRenameTable = 'Pøejmenovat tabulku na';
$strRenameTableOK = 'Tabulka %s byla pøejmenována na %s';
$strRepairTable = 'Opravit tabulku';
$strReplace = 'Pøepsat';
$strReplaceTable = 'Pøepsat data tabulky souborem';
$strReset = 'Pùvodní (reset)';
$strReType = 'Napsat znovu';
$strRevoke = 'Zrušit';
$strRevokeGrant = 'Zrušit povolení pøidìlovat práva';
$strRevokeGrantMessage = 'Bylo zrušeno oprávnìní pøidìlovat práva pro %s';
$strRevokeMessage = 'Byla zrušena práva pro %s';
$strRevokePriv = 'Zrušit práva';
$strRowLength = 'Délka øádku'; 
$strRows = 'Øádkù'; 
$strRowsFrom = 'øádkù zaèínající od';
$strRowSize = ' Velikost øádku '; 
$strRowsStatistic = 'Statistika øádkù'; 
$strRunning = 'bìžící na %s';
$strRunQuery = 'Provést dotaz';
$strRunSQLQuery = 'Spustit SQL dotaz(y) na databázi %s';

$strSave = 'Ulož';
$strSelect = 'Vybrat';
$strSelectFields = 'Zvol pole (alespoò jedno):';
$strSelectNumRows = 'v dotazu';
$strSend = 'Pošli';
$strSequence = 'Sekv.';
$strServerChoice = 'Výbìr serveru';
$strServerVersion = 'Verze serveru'; 
$strSetEnumVal = 'Pokud je pole typu "enum" nebo "set", zadávejte hodnoty v následujícím formátu: \'a\',\'b\',\'c\'...<br />Pokud potøebujete zadat zpìtné lomítko ("\") nebo jednoduché uvozovky ("\'") mezi tìmito hodnotami, napište pøed nì zpìtné lomítko (pøíklad: \'\\\\xyz\' nebo \'a\\\'b\').';
$strShow = 'Zobraz';
$strShowAll = 'Zobrazit vše';
$strShowCols = 'Zobrazit sloupce';
$strShowingRecords = 'Zobrazeny záznamy ';
$strShowPHPInfo = 'Zobrazit informace o PHP';
$strShowTables = 'Zobrazit tabulky';
$strShowThisQuery = ' Zobrazit zde tento dotaz znovu ';
$strSingly = '(po jednom)';
$strSize = 'Velikost'; 
$strSort = 'Øadit';
$strSpaceUsage = 'Využití místa'; 
$strSQLQuery = 'SQL-dotaz';
$strStartingRecord = 'Poèáteèní záznam';
$strStatement = 'Údaj'; 
$strStrucCSV = 'CSV data';
$strStrucData = 'Strukturu a data';
$strStrucDrop = 'Pøidej \'DROP TABLE\'';
$strStrucExcelCSV = 'CSV data pro Ms Excel';
$strStrucOnly = 'Pouze strukturu';
$strSubmit = 'Odešli';
$strSuccess = 'Tvùj SQL-dotaz byl úspìšnì vykonán';
$strSum = 'Celkem'; 

$strTable = 'Tabulka ';
$strTableComments = 'Komentáøe k tabulce';
$strTableEmpty = 'Jméno tabulky je prázdné!';
$strTableHasBeenDropped = 'Tabulka %s byla odstranìna';
$strTableHasBeenEmptied = 'Tabulka %s byla vyprázdnìna';
$strTableHasBeenFlushed = 'Cache pro tabulku %s bula vyprázdnìna';
$strTableMaintenance = ' Údržba tabulky ';
$strTables = '%s tabulek';
$strTableStructure = 'Struktura tabulky';
$strTableType = 'Typ tabulky';
$strTextAreaLength = ' Toto pole možná nepùjde <br />(kvùli délce) upravit ';
$strTheContent = 'Obsah tvého souboru byl vložen';
$strTheContents = 'Obsah souboru pøepíše obsah zvolené tabulky v tìch øádcích, kde je identický primární nebo unikátní klíè.';
$strTheTerminator = 'Ukonèení polí.';
$strTotal = 'celkem';
$strType = 'Typ';

$strUncheckAll = 'Odškrtnout vše';
$strUnique = 'Unikátní';
$strUpdatePrivMessage = 'Byla aktualizovana oprávnìní pro %s.';
$strUpdateProfile = 'Zmìny profilu:';
$strUpdateProfileMessage = 'Profil byl zmìnìn.';
$strUpdateQuery = 'Aktualizovat dotaz';
$strUsage = 'Používá'; 
$strUseBackquotes = 'Použít zpìtné uvozovky u jmen tabulek a polí';
$strUser = 'Uživatel';
$strUserEmpty = 'Jméno uživatele je prázdné!';
$strUserName = 'Jméno uživatele';
$strUsers = 'Uživatelé';
$strUseTables = 'Použít tabulky';

$strValue = 'Hodnota';
$strViewDump = 'Ukaž výpis (dump) tabulky';
$strViewDumpDB = 'Ukaž výpis (dump) databáze';

$strWelcome = 'Vítej v %s';
$strWithChecked = 'Zaškrtnuté:';
$strWrongUser = 'Špatné uživatelské jméno/heslo. Pøístup odepøen.';

$strYes = 'Ano';

$strZip = '"zazipováno"';

?>
