<?php
/* $Id: slovak-iso.inc.php3,v 1.59 2001/10/27 10:34:39 loic1 Exp */
/* By: lubos klokner <erkac@vault-tec.sk> */

$charset = 'iso-8859-2';
$left_font_family = '"verdana CE", "Arial CE", verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = '"verdana CE", "Arial CE", helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('Bajtov', 'KB', 'MB', 'GB');

$day_of_week = array('Ne', 'Po', 'Út', 'St', 'Št', 'Pi', 'So');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'Máj', 'Jún', 'Júl', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec');
$datefmt = '%d.%B, %Y - %H:%M';


$strAccessDenied = 'Prístup zamietnutý';
$strAction = 'Akcia';
$strAddDeleteColumn = 'Pridať/Odobrať polia stĺpcov';
$strAddDeleteRow = 'Pridať/Odobrať kritéria riadku';
$strAddNewField = 'Pridať nové pole';
$strAddPriv = 'Pridať nové privilégium';
$strAddPrivMessage = 'Privilégium bolo pridané.';
$strAddSearchConditions = 'Pridať vyhľadávacie parametre (obsah dotazu po "where" príkaze):';
$strAddUser = 'Pridať nového používateľa';
$strAddUserMessage = 'Používateľ bol pridaný.';
$strAffectedRows = ' Ovplyvnené riadky: ';
$strAfter = 'Po';
$strAfterInsertBack = 'Return'; //to translate
$strAfterInsertNewInsert = 'Insert a new record'; //to translate
$strAll = 'Všetko';
$strAlterOrderBy = 'Zmeniť poradie tabuľky podľa';
$strAnalyzeTable = 'Analyzovať tabuľku';
$strAnd = 'a';
$strAnIndex = 'Bol pridaný index pre %s';
$strAny = 'Akýkoľvek';
$strAnyColumn = 'Akýkoľvek stĺpec';
$strAnyDatabase = 'Akákoľvek databáza';
$strAnyHost = 'Akýkoľvek hostiteľ';
$strAnyTable = 'Akákoľvek tabuľka';
$strAnyUser = 'Akykoľvek používateľ';
$strAPrimaryKey = 'Bol pridaný primárny pre %s';
$strAscending = 'Vzostupne'; 
$strAtBeginningOfTable = 'Na začiatku tabuľky';
$strAtEndOfTable = 'Na konci tabuľky';
$strAttr = 'Atribúty';

$strBack = 'Späť';
$strBinary = 'Binárny';
$strBinaryDoNotEdit = 'Binárny - neupravujte ';
$strBookmarkDeleted = 'The bookmark has been deleted.';
$strBookmarkLabel = 'Názov';
$strBookmarkQuery = 'Obľúbený SQL dotaz';
$strBookmarkThis = 'Pridať tento SQL dotaz do obľúbených';
$strBookmarkView = 'Iba prezrieť';
$strBrowse = 'Prechádzať';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'nieje možné nahrať rozšírenie pre MySQL,<br />prosím skontrolujte konfiguráciu PHP.';
$strCarriage = 'Návrat vozíku (Carriage return): \\r';
$strChange = 'Zmeniť';
$strCheckAll = 'Označiť všetko';
$strCheckDbPriv = 'Skontrolovať privilégia databázy';
$strCheckTable = 'Skontrolovať tabuľku';
$strColumn = 'Stĺpec';
$strColumnNames = 'Názvy stĺpcov';
$strCompleteInserts = 'Úplné vloženie';
$strConfirm = 'Skutočne si želáte toto vykonať?';
$strCopyTable = 'Skopírovať tabuľku do (databáza<b>.</b>tabuľka):';
$strCopyTable = 'Skopírovať tabuľku do';
$strCopyTableOK = 'Tabuľka %s bola skorírovaná do %s.';
$strCreate = 'Vytvoriť';
$strCreateNewDatabase = 'Vytvoriť novú databázu';
$strCreateNewTable = 'Vytvoriť novú tabuľku v databáze ';
$strCriteria = 'Kritéria';

$strData = 'Dáta';
$strDatabase = 'Databáza ';
$strDatabaseHasBeenDropped = 'Databáza %s bola zmazaná.';
$strDatabases = 'databáz(y)';
$strDatabasesStats = 'Štatistiky databázy';
$strDataOnly = 'Iba dáta';
$strDefault = 'Predvolené';
$strDelete = 'Zmazať';
$strDeleted = 'Riadok bol zmazaný';
$strDeletedRows = 'Zmazané riadky:';
$strDeleteFailed = 'Mazanie bolo neúspešné!';
$strDeleteUserMessage = 'Používateľ %s bol zmazaný.';
$strDescending = 'Zostupne';
$strDisplay = 'Zobraziť';
$strDisplayOrder = 'Zobraziť zoradené:';
$strDoAQuery = 'Vykonať "dotaz podľa príkladu" (wildcard: "%")';
$strDocu = 'Dokumentácia';
$strDoYouReally = 'Skutočne chcete vykonať príkaz ';
$strDrop = 'Odstrániť';
$strDropDB = 'Odstrániť databázu ';
$strDropTable = 'Zrušiť tabuľku';
$strDumpingData = 'Dampujem dáta pre tabuľku';
$strDynamic = 'dynamic';

$strEdit = 'Upraviť';
$strEditPrivileges = 'Upraviť privilégia';
$strEffective = 'Efektívny';
$strEmpty = 'Vyprázdniť';
$strEmptyResultSet = 'MySQL vrátil prázdny výsledok (tj. nulový počet riadkov).';
$strEnd = 'Koniec';
$strEnglishPrivileges = ' Poznámka: názvy MySQL privilégií sú uvádzané v angličtine. ';
$strError = 'Chyba';
$strExtendedInserts = 'Rozšírené vkladanie';
$strExtra = 'Extra';

$strField = 'Pole';
$strFieldHasBeenDropped = 'Pole %s bolo odstránené';
$strFields = 'Polia';
$strFieldsEmpty = ' Počet polí je prázdny! ';
$strFieldsEnclosedBy = 'Polia uzatvorené';
$strFieldsEscapedBy = 'Polia uvedené pomocou';
$strFieldsTerminatedBy = 'Polia ukončené';
$strFixed = 'pevný';
$strFlushTable = 'Vyprázdniť tabuľku ("FLUSH")';
$strFormat = 'Formát';
$strFormEmpty = 'Chýbajúca položka vo formulári !';
$strFullText = 'Plné texty';
$strFunction = 'Funkcia';

$strGenTime = 'Vygenerované:';
$strGo = 'Vykonaj';
$strGrants = 'Privilégia';
$strGzip = '"gzip-ované"';

$strHasBeenAltered = 'bola zmenená.';
$strHasBeenCreated = 'bola vytvorená.';
$strHome = 'Domov';
$strHomepageOfficial = 'Oficiálne stránky phpMyAdmin-a';
$strHomepageSourceforge = 'Download stránka phpMyAdmin-a (Sourceforge)';
$strHost = 'Hostitel';
$strHostEmpty = 'Názov hostitela je prázdny!';

$strIdxFulltext = 'Celý text';
$strIfYouWish = 'Ak si zeláte nahrať iba určité stĺpce tabuľky, špecifikujte ich ako zoznam polí oddelený čiarkou.';
$strIndex = 'Index';
$strIndexes = 'Indexy';
$strIndexHasBeenDropped = 'Index pre %s bol odstránený';
$strInsert = 'Vložiť';
$strInsertAsNewRow = 'Vložiť ako nový riadok';
$strInsertedRows = 'Vložené riadky:';
$strInsertNewRow = 'Vložiť nový riadok';
$strInsertTextfiles = 'Vložiť textové súbory do tabuľky';
$strInstructions = 'Inštrukcie';
$strInUse = 'práve sa používa';
$strInvalidName = '"%s" je rezervované slovo, nemôže byť použité ako názov databázy/tabuľky/poľa.';

$strKeepPass = 'Nezmeniť heslo';
$strKeyname = 'Kľúčový názov';
$strKill = 'Zabiť';

$strLength = 'Dĺžka';
$strLengthSet = 'Dĺžka/Nastaviť*';
$strLimitNumRows = 'záznamov na stránku';
$strLineFeed = 'Ukončenie riadku (Linefeed): \\n';
$strLines = 'Riadky';
$strLinesTerminatedBy = 'Riadky ukončené';
$strLocationTextfile = 'Lokácia textového súboru';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Odhlásiť sa';

$strModifications = 'Zmeny boli uložené';
$strModify = 'Zmeniť';
$strMoveTable = 'Presunúť tabuľku do (databáza<b>.</b>tabuľka):';
$strMoveTableOK = 'Tabuľka %s bola presunutá do %s.';
$strMySQLReloaded = 'MySQL znovu-načítaná.';
$strMySQLSaid = 'MySQL hlási: ';
$strMySQLShowProcess = 'Zobraziť procesy';
$strMySQLShowStatus = 'Zobraziť MySQL informácie o behu';
$strMySQLShowVars = 'Zobraziť MySQL systémové premenné';

$strName = 'Názov';
$strNbRecords = 'Počet záznamov';
$strNext = 'Ďalší';
$strNo = 'Nie';
$strNoDatabases = 'Žiadne databázy';
$strNoDropDatabases = 'Možnosť "DROP DATABASE" vypnutá.';
$strNoFrames = 'phpMyAdmin funguje lepšie s prehliadačmi podporujúcimi <b>rámy</b>.';
$strNoModification = 'Žiadna zmena';
$strNoPassword = 'Žiadne heslo';
$strNoPrivileges = 'Žiadne privilégia';
$strNoQuery = 'Žiadny SQL dotaz!';
$strNoRights = 'Nemáte dostatočné práva na vykonanie tejto akcie!';
$strNoTablesFound = 'Neboli nájdené žiadne tabuľky v tejto datábaze.';
$strNotNumber = 'Toto nieje číslo!';
$strNotValidNumber = ' nieje platné číslo riadku!';
$strNoUsersFound = 'Nebol nájdený žiadny používateľ.';
$strNull = 'Nulový';
$strNumberIndexes = ' Počet rozšírených indexov ';

$strOftenQuotation = 'Často uvodzujúce znaky. Voliteľne znamena, že iba polia typu char a varchar sú uzatvorené do "uzatváracích" znakov.';
$strOptimizeTable = 'Optimalozovať tabuľku';
$strOptionalControls = 'Volitelné. Určuje ako zapisovať alebo čítať špeciálne znaky.';
$strOptionally = 'Voliteľne';
$strOr = 'alebo';
$strOverhead = 'Naviac';

$strPartialText = 'Čiastočné texty';
$strPassword = 'Heslo';
$strPasswordEmpty = 'Heslo je prázdne!';
$strPasswordNotSame = 'Heslá sa nezhodujú!';
$strPHPVersion = 'Verzia PHP';
$strPmaDocumentation = 'phpMyAdmin Dokumentácia';
$strPos1 = 'Začiatok';
$strPrevious = 'Predchádzajúci';
$strPrimary = 'Primárny';
$strPrimaryKey = 'Primárny kľúč';
$strPrimaryKeyHasBeenDropped = 'Primárny kľúč bol zrušený';
$strPrintView = 'Náhľad k tlači';
$strPrivileges = 'Privilégia';
$strProperties = 'Vlastnosti';

$strQBE = 'Dotaz podľa príkladu';
$strQBEDel = 'Zmazať';
$strQBEIns = 'Vložiť';
$strQueryOnDb = ' SQL dotaz v databáze ';

$strRecords = 'Záznamov';
$strReloadFailed = 'Znovu-načítanie MySQL bolo neúspešné.';
$strReloadMySQL = 'Znovu-načítať MySQL';
$strRememberReload = 'Nezabudnite znovu-načítať MySQL server.';
$strRenameTable = 'Premenovať tabuľku na';
$strRenameTableOK = 'Tabuľka %s bola premenovaná na %s';
$strRepairTable = 'Opraviť tabuľku';
$strReplace = 'Nahradiť';
$strReplaceTable = 'Nahradiť dáta v tabuľke súborom';
$strReset = 'Pôvodné (Reset)';
$strReType = 'Napísať znova';
$strRevoke = 'Zrusiť';
$strRevokeGrant = 'Zrušiť polovenie pridelovať privilégia';
$strRevokeGrantMessage = 'Bolo zrušené právo pridelovať privilégia pre';
$strRevokeMessage = 'Boli zrušené privilégia pre';
$strRevokePriv = 'Zrušiť privilégia';
$strRowLength = 'Dĺžka riadku';
$strRows = 'Riadkov';
$strRowsFrom = 'riadky začínajú od';
$strRowSize = ' Veľkosť riadku ';
$strRowsStatistic = 'Štatistika riadku';
$strRunning = 'beži na ';
$strRunningAs = 'ako';
$strRunQuery = 'Odošli dotaz';
$strRunSQLQuery = 'Spustiť SQL dotaz/dotazy na databázu %s';

$strSave = 'Uložiť';
$strSelect = 'Vybrať';
$strSelectFields = 'Zvoliť pole (najmenej jedno):';
$strSelectNumRows = 'v dotaze';
$strSend = 'Pošli';
$strSequence = 'Seq.';
$strServerChoice = 'Voľba serveru';
$strServerVersion = 'Verzia serveru';
$strSetEnumVal = 'Ak je pole typu "enum" alebo "set", prosím zadávajte hodnoty v tvare: \'a\',\'b\',\'c\'...<br />Ak dokonca porebujete zadať spätné lomítko ("\") alebo apostrof ("\'") pri týchto hodnotách, zadajte ich napríklad takto \'\\\\xyz\' alebo \'a\\\'b\'.';
$strShow = 'Ukázať';
$strShowAll = 'Zobraziť všetko';
$strShowCols = 'Zobraziť stĺpce';
$strShowingRecords = 'Ukázať záznamy ';
$strShowPHPInfo = 'Zobraziť informácie o PHP';
$strShowTables = 'Zobraziť tabuľky';
$strShowThisQuery = ' Zobraziť tento dotaz znovu ';
$strSingly = '(po jednom)';
$strSize = 'Veľkosť';
$strSort = 'Triediť';
$strSpaceUsage = 'Zabrané miesto';
$strSQLQuery = 'SQL dotaz';
$strStartingRecord = 'Začiatok záznamu';
$strStatement = 'Údaj';
$strStrucCSV = 'CSV dáta';
$strStrucData = 'Štruktúru a dáta';
$strStrucDrop = 'Pridaj \'vymaž tabuľku\'';
$strStrucExcelCSV = 'CSV pre Ms Excel dáta';
$strStrucOnly = 'Iba štruktúru';
$strSubmit = 'Odošli';
$strSuccess = 'SQL dotaz bol úspešne vykonaný';
$strSum = 'Celkom';

$strTable = 'tabuľka ';
$strTableComments = 'Komentár k tabuľke';
$strTableEmpty = 'Tabuľka je prázdna!';
$strTableHasBeenDropped = 'Tabuľka %s bola odstránená';
$strTableHasBeenEmptied = 'Tabuľka %s bola vyprázdená';
$strTableHasBeenFlushed = 'Tabuľka %s bola vyprázdnená';
$strTableMaintenance = 'Údržba tabuľky';
$strTables = '%s tabuľka(y)';
$strTableStructure = 'Štruktúra tabuľky pre tabuľku';
$strTableType = 'Typ tabuľky';
$strTextAreaLength = ' Toto možno nepojde upraviť,<br /> kôli svojej dĺžke ';
$strTheContent = 'Obsah Vášho súboru bol vložený.';
$strTheContents = 'Obsah súboru prepíše obsah vybranej tabuľky v riadkoch s identickým primárnym alebo unikátnym kľúčom.';
$strTheTerminator = 'Ukončenie polí.';
$strTotal = 'celkovo';
$strType = 'Typ';

$strUncheckAll = 'Odznačiť všetko';
$strUnique = 'Unikátny';
$strUpdatePrivMessage = 'Boli aktualizované privilégia pre %s.';
$strUpdateProfile = 'Aktualizovať profil:';
$strUpdateProfileMessage = 'Profil bol aktualizovaný.';
$strUpdateQuery = 'Aktualizovať dotaz';
$strUsage = 'Využitie';
$strUseBackquotes = ' Použiť opačný apostrof pri názvoch tabuliek a polí ';
$strUser = 'Používateľ';
$strUserEmpty = 'Meno používateľa je prázdne!';
$strUserName = 'Meno používateľa';
$strUsers = 'Používatelia';
$strUseTables = 'Použiť tabuľky';

$strValue = 'Hodnota';
$strViewDump = 'Zobraziť dump (schému) tabuľky';
$strViewDumpDB = 'Zobraziť dump (schému) databázy';

$strWelcome = 'Vitajte v ';
$strWithChecked = 'Výber:';
$strWrongUser = 'Zlé používateľské meno alebo heslo. Prístup zamietnutý.';

$strYes = 'Áno';

$strZip = '"zo zipované"';

?>
