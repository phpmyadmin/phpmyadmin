<?php
/* $Id$ */

$charset = 'windows-1250';
$left_font_family = 'verdana CE, Arial CE, verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'verdana CE, Arial CE, helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = '.';
$byteUnits = array('Bajtù', 'KB', 'MB', 'GB');

$day_of_week = array('Nedìle', 'Pondìlí', 'Úterı', 'Støeda', 'Ètvrtek', 'Pátek', 'Sobota');
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
$strAddUser = 'Pøidat nového uivatele';
$strAddUserMessage = 'Uivatel byl pøidán.';
$strAffectedRows = 'Ovlivnìné øádky:';
$strAfter = 'Po';
$strAll = 'Všechno';
$strAlterOrderBy = 'Zmìnit poøadí tabulky podle';
$strAnalyzeTable = 'Analyzovat tabulku';
$strAnd = 'a';
$strAnIndex = 'Byl pøidán index na ';
$strAny = 'Jakıkoliv';
$strAnyColumn = 'Jakıkoliv sloupec';
$strAnyDatabase = 'Jakákoliv databáze';
$strAnyHost = 'Jakıkoliv hostitel';
$strAnyTable = 'Any table';
$strAnyUser = 'Jakıkoliv uivatel';
$strAPrimaryKey = 'Byl pøidán primární klíè na ';
$strAscending = 'Vzestupnì';
$strAtBeginningOfTable = 'Na zaèátku tabulky';
$strAtEndOfTable = 'Na konci tabulky';
$strAttr = 'Atributy';

$strBack = 'Zpìt';
$strBinary = ' Binární ';  
$strBinaryDoNotEdit = ' Binární - neupravujte ';  
$strBookmarkLabel = 'Název'; 
$strBookmarkQuery = 'Oblíbenı SQL dotaz';
$strBookmarkThis = 'Pøidat tento SQL dotaz do oblíbenıch';
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
$strDefault = 'Vıchozí';
$strDelete = 'Smazat';
$strDeleted = 'Øádek byl smazán';
$strDeletedRows = 'Smazané øádky:';
$strDeleteFailed = 'Smazání selhalo!';
$strDeletePassword = 'Smazat heslo';
$strDeleteUserMessage = 'Byl smazán uivatel';
$strDelPassMessage = 'Bylo smazáno heslo pro';
$strDescending = 'Sestupnì';
$strDisableMagicQuotes = '<b>Varování:</b> Zapnul jste magic_quotes_gpc v nastavení PHP. Tato verze PhpMyAdmina nemùe s tímto nastavením korektnì pracovat. Prosím podívejte se do manuálu PHP (kapitola nastavení) jak toto nastavení zmìnit.';
$strDisplay = 'Zobrazit';
$strDisplayOrder = 'Seøadit podle:';
$strDoAQuery = 'ProvéstDo a "dotaz podle pøíkladu" (olík: "%")';
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
$strEmptyResultSet = 'MySQL vrátil prázdnı vısledek (tj. nulovı poèet øádkù).';
$strEnableMagicQuotes = '<b>POZOR:</b> Nepovolil jsi \'magic_quotes_gpc\' v tvé konfiguraci PHP enginu. PhpMyAdmin toto potøebuje pro svoji korektní praci. Prosím, zkontroluj konfiguraci popø. v manuálu pro PHP vyhledej informaci jak toto povolit.';
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
$strFixed = 'pevnı'; 
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

$strIfYouWish = 'Pokud si pøeješ natáhnout jenom urèité sloupce z tabulky, specifikuj je jako seznam polí oddìlenıch èárkou.';
$strIndex = 'Index';
$strIndexes = 'Indexy'; 
$strInsert = 'Vloit';
$strInsertAsNewRow = ' Vloit jako novı øádek ';
$strInsertedRows = 'Vloeno øádkù:';
$strInsertIntoTable = 'Vloit do tabulky';
$strInsertNewRow = 'Vloit novı øádek';
$strInsertTextfiles = 'Vloit textové soubory do tabulky';
$strInstructions = 'Instrukce';
$strInUse = 'právì se pouívá'; 
$strInvalidName = '"%s" je rezervované slovo a proto ho nemùete poít jako jméno databáze/tabulky/sloupce.';

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

$strModifications = 'Zmìny byly uloeny';
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
$strNoDatabases = 'ádné databáze';
$strNoDropDatabases = 'Pøíkaz "DROP DATABASE" je vypnutı.';
$strNoModification = 'ádná zmìna';
$strNoPassword = 'ádné heslo';
$strNoPrivileges = 'ádná privilegia';
$strNoRights = 'Nemáte dostateèná práva na provedení této akce!';
$strNoTablesFound = 'V databázi nebyla nalezena ani jedna tabulka.';
$strNotNumber = 'Toto není èíslo!';  
$strNotValidNumber = ' není platné èíslo øádku!'; 
$strNoUsersFound = 'ádnı uivatel nenalezen.';
$strNull = 'Nulovı';
$strNumberIndexes = ' Poèet rozšíøenıch indexù ';

$strOffSet = 'Zaèátek';
$strOftenQuotation = 'Èasto uvozující znaky. OPTIONALLY znamená, e pouze pole typu CHAR a VARCHAR jsou uzavøeny do "uzavíracích " znakù.';
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
$strRunning = 'bìící na ';
$strRunQuery = 'Provést dotaz';
$strRunSQLQuery = 'Spus SQL dotaz(y) na databázi ';

$strSave = 'Ulo';
$strSelect = 'Vybrat';
$strSelectFields = 'Zvol pole (alespoò jedno):';
$strSelectNumRows = 'v dotazu';
$strSend = 'Pošli';
$strSequence = 'Sekv.';
$strServerChoice = 'Vıbìr serveru';
$strServerVersion = 'Verze serveru'; 
$strSetEnumVal = 'Pokud je pole typu "enum" nebo "set", zadávejte hodnoty v následujícím formátu: \'a\',\'b\',\'c\'...<br />Pokud potøebujete zadat zpìtné lomítko ("\") nebo jednoduché uvozovky ("\'") mezi tìmito hodnotami, napište pøed nì zpìtné lomítko (pøíklad: \'\\\\xyz\' nebo \'a\\\'b\').';
$strShow = 'Zobraz';
$strShowingRecords = 'Ukazuji záznamy ';
$strShowPHPInfo = 'Zobrazit informace o PHP';
$strShowThisQuery = ' Zobrazit zde tento dotaz znovu ';
$strSingly = '(po jednom)';
$strSize = 'Velikost'; 
$strSort = 'Øadit';
$strSpaceUsage = 'Vyuití místa'; 
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
$strTableMaintenance = ' Údrba tabulky ';
$strTables = '%s tabulek';
$strTableStructure = 'Struktura tabulky';
$strTableType = 'Typ tabulky';
$strTerminatedBy = 'ukonèen';
$strTextAreaLength = ' Toto pole moná nepùjde <br />(kvùli délce) upravit ';
$strTheContent = 'Obsah tvého souboru byl vloen';
$strTheContents = 'Obsah souboru pøepíše obsah zvolené tabulky v tìch øádcích, kde je identickı primární nebo unikátní klíè.';
$strTheTerminator = 'Ukonèení polí.';
$strTotal = 'celkem';
$strType = 'Typ';

$strUncheckAll = 'Odškrtnout vše';
$strUnique = 'Unikátní';
$strUpdatePassMessage = 'Bylo zmìnìno heslo pro';
$strUpdatePassword = 'Zmìnit heslo';
$strUpdatePrivMessage = 'Byla zmìnìna privilegia pro';
$strUpdateQuery = 'Aktualizovat dotaz';
$strUsage = 'Pouívá'; 
$strUseBackquotes = 'Pouít zpìtné uvozovky u jmeno tabulek a polí';
$strUser = 'Uivatel';
$strUserEmpty = 'Jméno uivatele je prázdné!';
$strUserName = 'Jméno uivatele';
$strUsers = 'Uivatelé';
$strUseTables = 'Pouít tabulky';

$strValue = 'Hodnota';
$strViewDump = 'Uka dump (schema) tabulky';
$strViewDumpDB = 'Uka dump (schema) databáze';

$strWelcome = 'Vítej v ';
$strWithChecked = 'Zaškrtnuté:';
$strWrongUser = 'Špatné uivatelské jméno/heslo. Pøístup odepøen.';

$strYes = 'Ano';

?>
