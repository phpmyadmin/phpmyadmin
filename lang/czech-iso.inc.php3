<?php
/* $Id$ */

$charset = 'iso-8859-2';
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
$strAddUser = 'Pøidat nového u¾ivatele';
$strAddUserMessage = 'U¾ivatel byl pøidán.';
$strAffectedRows = 'Ovlivnìné øádky:';
$strAfter = 'Po';
$strAll = 'V¹echno';
$strAlterOrderBy = 'Zmìnit poøadí tabulky podle';
$strAnalyzeTable = 'Analyzovat tabulku';
$strAnd = 'a';
$strAny = 'Jakýkoliv';
$strAnyColumn = 'Jakýkoliv sloupec';
$strAnyDatabase = 'Jakákoliv databáze';
$strAnyHost = 'Jakýkoliv hostitel';
$strAnyTable = 'Any table';
$strAnyUser = 'Jakýkoliv u¾ivatel';
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

$strCantLoadMySQL = 'nelze nahrát roz¹íøení pro MySQL,<br />prosím zkontrolujte nastavení PHP.';
$strCarriage = 'Návrat vozíku (CR): \\r';
$strChange = 'Zmìnit';
$strCheckAll = 'Za¹krtnout v¹e';
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
$strDisableMagicQuotes = '<b>Varování:</b> Zapnul jste magic_quotes_gpc v nastavení PHP. Tato verze PhpMyAdmina nemù¾e s tímto nastavením korektnì pracovat. Prosím podívejte se do manuálu PHP (kapitola nastavení) jak toto nastavení zmìnit.';
$strDisplay = 'Zobrazit';
$strDisplayOrder = 'Seøadit podle:';
$strDoAQuery = 'ProvéstDo a "dotaz podle pøíkladu" (¾olík: "%")';
$strDocu = 'Dokumentace';
$strDoYouReally = 'Opravdu si pøeje¹ vykonat pøíkaz ';
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
$strEnableMagicQuotes = '<b>POZOR:</b> Nepovolil jsi \'magic_quotes_gpc\' v tvé konfiguraci PHP enginu. PhpMyAdmin toto potøebuje pro svoji korektní praci. Prosím, zkontroluj konfiguraci popø. v manuálu pro PHP vyhledej informaci jak toto povolit.';
$strEnclosedBy = 'uzavøen do';
$strEnd = 'Konec';
$strEnglishPrivileges = ' Poznámka: názvy MySQL privilegií jsou uvádìna v angliètinì ';
$strError = 'Chyba';
$strEscapedBy = 'uvozeno pomocí';
$strExtendedInserts = 'Roz¹íøené inserty';
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

$strIfYouWish = 'Pokud si pøeje¹ natáhnout jenom urèité sloupce z tabulky, specifikuj je jako seznam polí oddìlených èárkou.';
$strIndex = 'Index';
$strIndexes = 'Indexy'; 
$strInsert = 'Vlo¾it';
$strInsertAsNewRow = ' Vlo¾it jako nový øádek ';
$strInsertedRows = 'Vlo¾eno øádkù:';
$strInsertIntoTable = 'Vlo¾it do tabulky';
$strInsertNewRow = 'Vlo¾it nový øádek';
$strInsertTextfiles = 'Vlo¾it textové soubory do tabulky';
$strInstructions = 'Instrukce';
$strInUse = 'právì se pou¾ívá'; 
$strInvalidName = '"%s" je rezervované slovo a proto ho nemù¾ete po¾ít jako jméno databáze/tabulky/sloupce.';

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

$strModifications = 'Zmìny byly ulo¾eny';
$strModify = 'Úpravy';
$strMySQLReloaded = 'MySQL znovu-naètena.';
$strMySQLSaid = 'MySQL hlásí: ';
$strMySQLShowProcess = 'Zobraz procesy';
$strMySQLShowStatus = 'Ukázat MySQL runtime informace';
$strMySQLShowVars = 'Ukázat MySQL systémové promìnné';

$strName = 'Název';
$strNbRecords = 'øádkù';
$strNext = 'Dal¹í';
$strNo = 'Ne';
$strNoDatabases = '®ádné databáze';
$strNoDropDatabases = 'Pøíkaz "DROP DATABASE" je vypnutý.';
$strNoModification = '®ádná zmìna';
$strNoPassword = '®ádné heslo';
$strNoPrivileges = '®ádná privilegia';
$strNoRights = 'Nemáte dostateèná práva na provedení této akce!';
$strNoTablesFound = 'V databázi nebyla nalezena ani jedna tabulka.';
$strNotNumber = 'Toto není èíslo!';  
$strNotValidNumber = ' není platné èíslo øádku!'; 
$strNoUsersFound = '®ádný u¾ivatel nenalezen.';
$strNull = 'Nulový';
$strNumberIndexes = ' Poèet roz¹íøených indexù ';

$strOftenQuotation = 'Èasto uvozující znaky. OPTIONALLY znamená, ¾e pouze pole typu CHAR a VARCHAR jsou uzavøeny do "uzavíracích " znakù.';
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
$strPrintView = 'Náhled k vyti¹tìní';
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
$strRevoke = 'Zru¹it';
$strRevokeGrant = 'Zru¹it povolení pøidìlovat práva';
$strRevokeGrantMessage = 'Bylo zru¹eno privilegium pøidìlovat práva pro';
$strRevokeMessage = 'Byla zru¹ena práva pro';
$strRevokePriv = 'Zru¹it práva';
$strRowLength = 'Délka øádku'; 
$strRows = 'Øádkù'; 
$strRowsFrom = 'øádkù zaèínající od';
$strRowSize = ' Velikost øádku '; 
$strRowsStatistic = 'Statistika øádkù'; 
$strRunning = 'bì¾ící na ';
$strRunQuery = 'Provést dotaz';

$strSave = 'Ulo¾';
$strSelect = 'Vybrat';
$strSelectFields = 'Zvol pole (alespoò jedno):';
$strSelectNumRows = 'v dotazu';
$strSend = 'Po¹li';
$strSequence = 'Sekv.';
$strServerChoice = 'Výbìr serveru';
$strServerVersion = 'Verze serveru'; 
$strSetEnumVal = 'Pokud je pole typu "enum" nebo "set", zadávejte hodnoty v následujícím formátu: \'a\',\'b\',\'c\'...<br />Pokud potøebujete zadat zpìtné lomítko ("\") nebo jednoduché uvozovky ("\'") mezi tìmito hodnotami, napi¹te pøed nì zpìtné lomítko (pøíklad: \'\\\\xyz\' nebo \'a\\\'b\').';
$strShow = 'Zobraz';
$strShowingRecords = 'Ukazuji záznamy ';
$strShowPHPInfo = 'Zobrazit informace o PHP';
$strShowThisQuery = ' Zobrazit zde tento dotaz znovu ';
$strSingly = '(po jednom)';
$strSize = 'Velikost'; 
$strSort = 'Øadit';
$strSpaceUsage = 'Vyu¾ití místa'; 
$strSQLQuery = 'SQL-dotaz';
$strStatement = 'Údaj'; 
$strStrucCSV = 'CSV data';
$strStrucData = 'Strukturu a data';
$strStrucDrop = 'Pøidej \'DROP TABLE\'';
$strStrucExcelCSV = 'CSV data pro Ms Excel';
$strStrucOnly = 'Pouze strukturu';
$strSubmit = 'Ode¹li';
$strSuccess = 'Tvùj SQL-dotaz byl úspì¹nì vykonán';
$strSum = 'Celkem'; 

$strTable = 'Tabulka ';
$strTableComments = 'Komentáøe k tabulce';
$strTableEmpty = 'Jméno tabulky je prázdné!';
$strTableMaintenance = ' Údr¾ba tabulky ';
$strTables = '%s tabulek';
$strTableStructure = 'Struktura tabulky';
$strTableType = 'Typ tabulky';
$strTerminatedBy = 'ukonèen';
$strTextAreaLength = ' Toto pole mo¾ná nepùjde <br />(kvùli délce) upravit ';
$strTheContent = 'Obsah tvého souboru byl vlo¾en';
$strTheContents = 'Obsah souboru pøepí¹e obsah zvolené tabulky v tìch øádcích, kde je identický primární nebo unikátní klíè.';
$strTheTerminator = 'Ukonèení polí.';
$strTotal = 'celkem';
$strType = 'Typ';

$strUncheckAll = 'Od¹krtnout v¹e';
$strUnique = 'Unikátní';
$strUpdateQuery = 'Aktualizovat dotaz';
$strUsage = 'Pou¾ívá'; 
$strUseBackquotes = 'Pou¾ít zpìtné uvozovky u jmeno tabulek a polí';
$strUser = 'U¾ivatel';
$strUserEmpty = 'Jméno u¾ivatele je prázdné!';
$strUserName = 'Jméno u¾ivatele';
$strUsers = 'U¾ivatelé';
$strUseTables = 'Pou¾ít tabulky';

$strValue = 'Hodnota';
$strViewDump = 'Uka¾ dump (schema) tabulky';
$strViewDumpDB = 'Uka¾ dump (schema) databáze';

$strWelcome = 'Vítej v ';
$strWithChecked = 'Za¹krtnuté:';
$strWrongUser = '©patné u¾ivatelské jméno/heslo. Pøístup odepøen.';

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
?>
