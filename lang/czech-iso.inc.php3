<?php
/* $Id$ */

$charset = 'iso-8859-2';
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
$strAddUser = 'Pøidat nového u¾ivatele';
$strAddUserMessage = 'U¾ivatel byl pøidán.';
$strAffectedRows = 'Ovlivnìné øádky:';
$strAfter = 'Po';
$strAll = 'V¹echno';
$strAlterOrderBy = 'Zmìnit poøadí tabulky podle';
$strAnalyzeTable = 'Analyzovat tabulku';
$strAnd = 'a';
$strAnIndex = 'K tabulce %s byl pøidán index';
$strAny = 'Jakýkoliv';
$strAnyColumn = 'Jakýkoliv sloupec';
$strAnyDatabase = 'Jakákoliv databáze';
$strAnyHost = 'Jakýkoliv hostitel';
$strAnyTable = 'Jakákoliv tabulka';
$strAnyUser = 'Jakýkoliv u¾ivatel';
$strAPrimaryKey = 'V tabulce %s byl vytvoøen primární klíè';
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
$strBookmarkView = 'Jen zobrazit';
$strBrowse = 'Projít';
$strBzip = '"zabzipováno"';

$strCantLoadMySQL = 'nelze nahrát roz¹íøení pro MySQL,<br />prosím zkontrolujte nastavení PHP.';
$strCarriage = 'Návrat vozíku (CR): \\r';
$strChange = 'Zmìnit';
$strCheckAll = 'Za¹krtnout v¹e';
$strCheckDbPriv = 'Zkontrolovat oprávnìní pro databázi';
$strCheckTable = 'Zkontrolovat tabulku';
$strColumn = 'Sloupec';
$strColumnNames = 'Názvy sloupcù';
$strCompleteInserts = 'Uplné inserty';
$strConfirm = 'Opravdu chcete toto provést?';
$strCopyTable = 'Kopírovat tabulku do (databáze<b>.</b>tabulka):';
$strCopyTableOK = 'Tabulka %s byla zkopírována do %s.';
$strCreate = 'Vytvoøit';
$strCreateNewDatabase = 'Vytvoøit novou databázi';
$strCreateNewTable = 'Vytvoøit novou tabulku v databázi ';
$strCriteria = 'Podmínka';

$strData = 'Data';
$strDatabase = 'Databáze ';
$strDatabaseHasBeenDropped = 'Databáze %s byla zru¹ena.';
$strDatabases = 'databáze';
$strDatabasesStats = 'Statistiky databáze';
$strDataOnly = ' Jen data ';
$strDefault = 'Výchozí';
$strDelete = 'Smazat';
$strDeleted = 'Øádek byl smazán';
$strDeletedRows = 'Smazané øádky:';
$strDeleteFailed = 'Smazání selhalo!';
$strDeleteUserMessage = 'Byl smazán u¾ivatel %s.';
$strDescending = 'Sestupnì';
$strDisplay = 'Zobrazit';
$strDisplayOrder = 'Seøadit podle:';
$strDoAQuery = 'Provést "dotaz podle pøíkladu" (¾olík: "%")';
$strDocu = 'Dokumentace';
$strDoYouReally = 'Opravdu si pøeje¹ vykonat pøíkaz ';
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
$strExtendedInserts = 'Roz¹íøené inserty';
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
$strIfYouWish = 'Pokud si pøeje¹ natáhnout jenom urèité sloupce z tabulky, specifikuj je jako seznam polí oddìlených èárkou.';
$strIndex = 'Index';
$strIndexes = 'Indexy'; 
$strIndexHasBeenDropped = 'Index %s byl odstranìn';
$strInsert = 'Vlo¾it';
$strInsertAsNewRow = ' Vlo¾it jako nový øádek ';
$strInsertedRows = 'Vlo¾eno øádkù:';
$strInsertNewRow = 'Vlo¾it nový øádek';
$strInsertTextfiles = 'Vlo¾it textové soubory do tabulky';
$strInstructions = 'Instrukce';
$strInUse = 'právì se pou¾ívá'; 
$strInvalidName = '"%s" je rezervované slovo a proto ho nemù¾ete po¾ít jako jméno databáze/tabulky/sloupce.';

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

$strModifications = 'Zmìny byly ulo¾eny';
$strModify = 'Úpravy';
$strMoveTable = 'Pøesunout tabulku do (databáze<b>.</b>tabulka):';
$strMoveTableOK = 'Tabulka %s byla pøesunuta do %s.';
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
$strNoFrames = 'phpMyAdmin se lépe pou¾ívá v prohlí¾eèi podporujícím rámy ("FRAME").';
$strNoModification = '®ádná zmìna';
$strNoPassword = '®ádné heslo';
$strNoPrivileges = '®ádná oprávnìní';
$strNoQuery = '®ádný SQL dotaz!';
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
$strPrimaryKeyHasBeenDropped = 'Primární klíè byl odstranìn';
$strPrintView = 'Náhled k vyti¹tìní';
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
$strRevoke = 'Zru¹it';
$strRevokeGrant = 'Zru¹it povolení pøidìlovat práva';
$strRevokeGrantMessage = 'Bylo zru¹eno oprávnìní pøidìlovat práva pro';
$strRevokeMessage = 'Byla zru¹ena práva pro';
$strRevokePriv = 'Zru¹it práva';
$strRowLength = 'Délka øádku'; 
$strRows = 'Øádkù'; 
$strRowsFrom = 'øádkù zaèínající od';
$strRowSize = ' Velikost øádku '; 
$strRowsStatistic = 'Statistika øádkù'; 
$strRunning = 'bì¾ící na ';
$strRunningAs = 'jako';
$strRunQuery = 'Provést dotaz';
$strRunSQLQuery = 'Spustit SQL dotaz(y) na databázi %s';

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
$strShowAll = 'Zobrazit v¹e';
$strShowCols = 'Zobrazit sloupce';
$strShowingRecords = 'Zobrazeny záznamy ';
$strShowPHPInfo = 'Zobrazit informace o PHP';
$strShowTables = 'Zobrazit tabulky';
$strShowThisQuery = ' Zobrazit zde tento dotaz znovu ';
$strSingly = '(po jednom)';
$strSize = 'Velikost'; 
$strSort = 'Øadit';
$strSpaceUsage = 'Vyu¾ití místa'; 
$strSQLQuery = 'SQL-dotaz';
$strStartingRecord = 'Poèáteèní záznam';
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
$strTableHasBeenDropped = 'Tabulka %s byla odstranìna';
$strTableHasBeenEmptied = 'Tabulka %s byla vyprázdnìna';
$strTableHasBeenFlushed = 'Cache pro tabulku %s bula vyprázdnìna';
$strTableMaintenance = ' Údr¾ba tabulky ';
$strTables = '%s tabulek';
$strTableStructure = 'Struktura tabulky';
$strTableType = 'Typ tabulky';
$strTextAreaLength = ' Toto pole mo¾ná nepùjde <br />(kvùli délce) upravit ';
$strTheContent = 'Obsah tvého souboru byl vlo¾en';
$strTheContents = 'Obsah souboru pøepí¹e obsah zvolené tabulky v tìch øádcích, kde je identický primární nebo unikátní klíè.';
$strTheTerminator = 'Ukonèení polí.';
$strTotal = 'celkem';
$strType = 'Typ';

$strUncheckAll = 'Od¹krtnout v¹e';
$strUnique = 'Unikátní';
$strUpdatePrivMessage = 'Byla aktualizovana oprávnìní pro %s.';
$strUpdateProfile = 'Zmìny profilu:';
$strUpdateProfileMessage = 'Profil byl zmìnìn.';
$strUpdateQuery = 'Aktualizovat dotaz';
$strUsage = 'Pou¾ívá'; 
$strUseBackquotes = 'Pou¾ít zpìtné uvozovky u jmen tabulek a polí';
$strUser = 'U¾ivatel';
$strUserEmpty = 'Jméno u¾ivatele je prázdné!';
$strUserName = 'Jméno u¾ivatele';
$strUsers = 'U¾ivatelé';
$strUseTables = 'Pou¾ít tabulky';

$strValue = 'Hodnota';
$strViewDump = 'Uka¾ výpis (dump) tabulky';
$strViewDumpDB = 'Uka¾ výpis (dump) databáze';

$strWelcome = 'Vítej v %s';
$strWithChecked = 'Za¹krtnuté:';
$strWrongUser = '©patné u¾ivatelské jméno/heslo. Pøístup odepøen.';

$strYes = 'Ano';

$strZip = '"zazipováno"';

// To translate
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Insert a new record';
$strBookmarkDeleted = 'The bookmark has been deleted.';
?>
