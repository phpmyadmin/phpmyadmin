<?php
/* $Id$ */

$charset = 'windows-1250';
$left_font_family = 'verdana CE, Arial CE, verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'verdana CE, Arial CE, helvetica, arial, geneva, sans-serif';
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
$strAddPrivMessage = 'Privilegium bylo pøidáno.';
$strAddSearchConditions = 'Pøidat vyhledávací parametry (obsah dotazu po "where" pøíkazu):';
$strAddUser = 'Pøidat nového uživatele';
$strAddUserMessage = 'Uživatel byl pøidán.';
$strAffectedRows = 'Ovlivnìné øádky:';
$strAfter = 'Po';
$strAll = 'Všechno';
$strAlterOrderBy = 'Zmìnit poøadí tabulky podle';
$strAnalyzeTable = 'Analyzovat tabulku';
$strAnd = 'a';
$strAny = 'Jakýkoliv';
$strAnyColumn = 'Jakýkoliv sloupec';
$strAnyDatabase = 'Jakákoliv databáze';
$strAnyHost = 'Jakýkoliv hostitel';
$strAnyTable = 'Any table';
$strAnyUser = 'Jakýkoliv uživatel';
$strAscending = 'Vzestupnì';
$strAtBeginningOfTable = 'Na zaèátku tabulky';
$strAtEndOfTable = 'Na konci tabulky';
$strAttr = 'Atributy';

$strBack = 'Zpìt';
$strBinary = ' Binární ';  
$strBinaryDoNotEdit = ' Binární - neupravujte ';  
$strBookmarkLabel = 'Název'; 
$strBookmarkQuery = 'Oblíbený SQL dotaz';
$strBookmarkThis = 'Pøidat tento SQL dotaz do oblíbených';
$strBookmarkView = 'Jen prohlédnout';
$strBrowse = 'Projít';
$strBzip = '"zabzipováno"';

$strCantLoadMySQL = 'nelze nahrát rozšíøení pro MySQL,<br />prosím zkontrolujte nastavení PHP.';
$strCarriage = 'Návrat vozíku (CR): \\r';
$strChange = 'Zmìnit';
$strCheckAll = 'Zaškrtnout vše';
$strCheckDbPriv = 'Zkontrolovat privilegia databáze';
$strCheckTable = 'Zkontrolovat tabulku';
$strColumn = 'Sloupec';
$strColumnEmpty = 'Jména sloupcù jsou prázdná!';
$strColumnNames = 'Názvy sloupcù';
$strCompleteInserts = 'Uplné inserty';
$strConfirm = 'Opravdu chcete toto provést?';
$strCopyTable = 'Zkopírovat tabulku do';
$strCopyTableOK = 'Tabulka %s byla zkopírována do %s.';
$strCreate = 'Vytvoøit';
$strCreateNewDatabase = 'Vytvoøit novou databázi';
$strCreateNewTable = 'Vytvoøit novou tabulku v databázi ';
$strCriteria = 'Podmínka';

$strData = 'Data'; 
$strDatabase = 'Databáze ';
$strDatabases = 'databáze';
$strDatabasesStats = 'Statistiky databáze';
$strDataOnly = ' Jen data ';
$strDbEmpty = 'Jméno databáze je prázdné!';
$strDefault = 'Výchozí';
$strDelete = 'Smazat';
$strDeleted = 'Øádek byl smazán';
$strDeletedRows = 'Smazané øádky:';
$strDeleteFailed = 'Smazání selhalo!';
$strDeletePassword = 'Smazat heslo';
$strDelPassMessage = 'Bylo smazáno heslo pro';
$strDescending = 'Sestupnì';
$strDisplay = 'Zobrazit';
$strDisplayOrder = 'Seøadit podle:';
$strDoAQuery = 'ProvéstDo a "dotaz podle pøíkladu" (žolík: "%")';
$strDocu = 'Dokumentace';
$strDoYouReally = 'Opravdu si pøeješ vykonat pøíkaz ';
$strDrop = 'Odstranit';
$strDropDB = 'Odstranit databázi ';
$strDropTable = 'Smazat tabulku';
$strDumpingData = 'Dumpuji data pro tabulku';
$strDynamic = 'dynamic';

$strEdit = 'Editovat';
$strEditPrivileges = 'Upravit práva';
$strEffective = 'Efektivní';
$strEmpty = 'Vyprázdnit';
$strEmptyResultSet = 'MySQL vrátil prázdný výsledek (tj. nulový poèet øádkù).';
$strEnclosedBy = 'uzavøen do';
$strEnd = 'Konec';
$strEnglishPrivileges = ' Poznámka: názvy MySQL privilegií jsou uvádìna v angliètinì ';
$strError = 'Chyba';
$strEscapedBy = 'uvozeno pomocí';
$strExtendedInserts = 'Rozšíøené inserty';
$strExtra = 'Extra'; 

$strField = 'Pole';
$strFields = 'Poèet polí';
$strFieldsEmpty = ' Nebyl zadán poèet polí! ';
$strFixed = 'pevný'; 
$strFormat = 'Formát'; 
$strFormEmpty = 'Chybìjící hodnota ve formuláøi !';
$strFullText = 'Celé texty';
$strFunction = 'Funkce';

$strGenTime = 'Vygenerováno:'; 
$strGo = 'Proveï';
$strGrants = 'Privilegia';
$strGzip = '"zagzipováno"';

$strHasBeenAltered = 'byla zmìnìna.';
$strHasBeenCreated = 'byla vytvoøena.';
$strHasBeenDropped = 'byla odstranìna.';
$strHasBeenEmptied = 'byla vyprázdnìna.';
$strHome = 'Úvod';
$strHomepageOfficial = ' Oficiální stránka phpMyAdmina ';
$strHomepageSourceforge = ' nová stránka phpMyAdmina ';
$strHost = 'Hostitel';
$strHostEmpty = 'Jméno hostitele je prázdné!';

$strIfYouWish = 'Pokud si pøeješ natáhnout jenom urèité sloupce z tabulky, specifikuj je jako seznam polí oddìlených èárkou.';
$strIndex = 'Index';
$strIndexes = 'Indexy'; 
$strInsert = 'Vložit';
$strInsertAsNewRow = ' Vložit jako nový øádek ';
$strInsertedRows = 'Vloženo øádkù:';
$strInsertIntoTable = 'Vložit do tabulky';
$strInsertNewRow = 'Vložit nový øádek';
$strInsertTextfiles = 'Vložit textové soubory do tabulky';
$strInstructions = 'Instrukce';
$strInUse = 'právì se používá'; 
$strInvalidName = '"%s" je rezervované slovo a proto ho nemùžete požít jako jméno databáze/tabulky/sloupce.';

$strKeyname = 'Klíèovy název';
$strKill = ' Zabít ';

$strLength = 'Délka';
$strLengthSet = 'Délka/Set*';
$strLimitNumRows = 'záznamu na stránku';
$strLineFeed = 'Ukonèení øádku (Linefeed): \\n';
$strLines = 'Øádek';
$strLocationTextfile = 'Umístìní textového souboru';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Odhlásit se';

$strModifications = 'Zmìny byly uloženy';
$strModify = 'Úpravy';
$strMySQLReloaded = 'MySQL znovu-naètena.';
$strMySQLSaid = 'MySQL hlásí: ';
$strMySQLShowProcess = 'Zobraz procesy';
$strMySQLShowStatus = 'Ukázat MySQL runtime informace';
$strMySQLShowVars = 'Ukázat MySQL systémové promìnné';

$strName = 'Název';
$strNbRecords = 'øádkù';
$strNext = 'Další';
$strNo = 'Ne';
$strNoDatabases = 'Žádné databáze';
$strNoDropDatabases = 'Pøíkaz "DROP DATABASE" je vypnutý.';
$strNoModification = 'Žádná zmìna';
$strNoPassword = 'Žádné heslo';
$strNoPrivileges = 'Žádná privilegia';
$strNoRights = 'Nemáte dostateèná práva na provedení této akce!';
$strNoTablesFound = 'V databázi nebyla nalezena ani jedna tabulka.';
$strNotNumber = 'Toto není èíslo!';  
$strNotValidNumber = ' není platné èíslo øádku!'; 
$strNoUsersFound = 'Žádný uživatel nenalezen.';
$strNull = 'Nulový';
$strNumberIndexes = ' Poèet rozšíøených indexù ';

$strOftenQuotation = 'Èasto uvozující znaky. OPTIONALLY znamená, že pouze pole typu CHAR a VARCHAR jsou uzavøeny do "uzavíracích " znakù.';
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
$strPrinterFriendly = 'Verze urèená pro tisk';
$strPrintView = 'Náhled k vytištìní';
$strPrivileges = 'Privilegia';
$strProducedAnError = 'vytvoøil chybu.';
$strProperties = 'Vlastnosti';

$strQBE = 'Dotaz podle pøíkladu';
$strQBEDel = 'pøidat';
$strQBEIns = 'smazat';
$strQueryOnDb = 'SQL dotaz na databázi ';

$strReadTheDocs = 'Pøeèti dokumentaci';
$strRecords = 'Záznamù';
$strReloadFailed = 'Znovunaètení MySQL selhalo.';
$strReloadMySQL = 'Znovunaètení MySQL';
$strRememberReload = 'Nezapomeòte reloadovat server.';
$strRenameTable = 'Pøejmenovat tabulku na';
$strRenameTableOK = 'Tabulka %s byla pøejmenována na %s';
$strRepairTable = 'Opravit tabulku';
$strReplace = 'Pøepsat';
$strReplaceTable = 'Pøepsat data tabulky souborem';
$strReset = 'P§vodní (reset)';
$strReType = 'Napsat znovu';
$strRevoke = 'Zrušit';
$strRevokeGrant = 'Zrušit povolení pøidìlovat práva';
$strRevokeGrantMessage = 'Bylo zrušeno privilegium pøidìlovat práva pro';
$strRevokeMessage = 'Byla zrušena práva pro';
$strRevokePriv = 'Zrušit práva';
$strRowLength = 'Délka øádku'; 
$strRows = 'Øádkù'; 
$strRowsFrom = 'øádkù zaèínající od';
$strRowSize = ' Velikost øádku ';
$strRowsStatistic = 'Statistika øádkù'; 
$strRunning = 'bìžící na ';
$strRunQuery = 'Provést dotaz';

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
$strShowingRecords = 'Ukazuji záznamy ';
$strShowPHPInfo = 'Zobrazit informace o PHP';
$strShowThisQuery = ' Zobrazit zde tento dotaz znovu ';
$strSingly = '(po jednom)';
$strSize = 'Velikost'; 
$strSort = 'Øadit';
$strSpaceUsage = 'Využití místa'; 
$strSQLQuery = 'SQL-dotaz';
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
$strTableMaintenance = ' Údržba tabulky ';
$strTables = '%s tabulek';
$strTableStructure = 'Struktura tabulky';
$strTableType = 'Typ tabulky';
$strTerminatedBy = 'ukonèen';
$strTextAreaLength = ' Toto pole možná nepùjde <br />(kvùli délce) upravit ';
$strTheContent = 'Obsah tvého souboru byl vložen';
$strTheContents = 'Obsah souboru pøepíše obsah zvolené tabulky v tìch øádcích, kde je identický primární nebo unikátní klíè.';
$strTheTerminator = 'Ukonèení polí.';
$strTotal = 'celkem';
$strType = 'Typ';

$strUncheckAll = 'Odškrtnout vše';
$strUnique = 'Unikátní';
$strUpdateQuery = 'Aktualizovat dotaz';
$strUsage = 'Používá'; 
$strUseBackquotes = 'Použít zpìtné uvozovky u jmeno tabulek a polí';
$strUser = 'Uživatel';
$strUserEmpty = 'Jméno uživatele je prázdné!';
$strUserName = 'Jméno uživatele';
$strUsers = 'Uživatelé';
$strUseTables = 'Použít tabulky';

$strValue = 'Hodnota';
$strViewDump = 'Ukaž dump (schema) tabulky';
$strViewDumpDB = 'Ukaž dump (schema) databáze';

$strWelcome = 'Vítej v ';
$strWithChecked = 'Zaškrtnuté:';
$strWrongUser = 'Špatné uživatelské jméno/heslo. Pøístup odepøen.';

$strYes = 'Ano';

// To translate
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strAnIndex = 'An index has been added on %s';//to translate
$strDeleteUserMessage = 'You have deleted the user %s.';//to translate
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strFieldsEnclosedBy = 'Fields enclosed by';//to translate
$strFieldsEscapedBy = 'Fields escaped by';//to translate
$strFieldsTerminatedBy = 'Fields terminated by';//to translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strIndexHasBeenDropped = 'Index %s has been dropped';//to translate
$strKeepPass = 'Do not change the password';//to translate
$strLinesTerminatedBy = 'Lines terminated by';//to translate
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strRunningAs = 'as';
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowTables = 'Show tables';
$strStartingRecord = 'Starting record';//to translate
$strTableHasBeenDropped = 'Table %s has been dropped';//to translate
$strTableHasBeenEmptied = 'Table %s has been emptied';//to translate
$strUpdatePrivMessage = 'You have updated the privileges for %s.';//to translate
$strUpdateProfile = 'Update profile:';//to translate
$strUpdateProfileMessage = 'The profile has been updated.';//to translate
$strDatabaseHasBeenDropped=" Database %s has been dropped ";  //to translate
?>
