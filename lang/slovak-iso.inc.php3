<?php
/* $Id: slovak-iso.inc.php,v 1.1 2001/07/24 17:15:00 loic1 Exp */
/* By: lubos klokner <erkac@vault-tec.sk> */

$charset = 'iso-8859-2';
$left_font_family = '"verdana CE", "Arial CE", verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = '"verdana CE", "Arial CE", helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ' ';
$number_decimal_separator = ',';
$byteUnits = array('Bajtov', 'KB', 'MB', 'GB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Prístup zamietnutý';
$strAction = 'Akcia';
$strAddDeleteColumn = 'Prida»/Odobra» polia ståpcov';
$strAddDeleteRow = 'Prida»/Odobra» kritéria riadku';
$strAddNewField = 'Prida» nové pole';
$strAddPriv = 'Prida» nové privilégium';
$strAddPrivMessage = 'Privilégium bolo pridané.';
$strAddSearchConditions = 'Prida» vyhµadávacie parametre (obsah dotazu po "where" príkaze):';
$strAddUser = 'Prida» nového pou¾ívateµa';
$strAddUserMessage = 'Pou¾ívateµ bol pridaný.';
$strAffectedRows = ' Ovplyvnené riadky: ';
$strAfter = 'Po';
$strAll = 'V¹etko';
$strAlterOrderBy = 'Zmeni» poradie tabuµky podµa';
$strAnalyzeTable = 'Analyzova» tabuµku';
$strAnd = 'a';
$strAnIndex = 'Bol pridaný index pre %s';
$strAny = 'Akýkoµvek';
$strAnyColumn = 'Akýkoµvek ståpec';
$strAnyDatabase = 'Akákoµvek databáza';
$strAnyHost = 'Akýkoµvek hostiteµ';
$strAnyTable = 'Akákoµvek tabuµka';
$strAnyUser = 'Akykoµvek pou¾ívateµ';
$strAPrimaryKey = 'Bol pridaný primárny pre %s';
$strAscending = 'Vzostupne'; 
$strAtBeginningOfTable = 'Na zaèiatku tabuµky';
$strAtEndOfTable = 'Na konci tabuµky';
$strAttr = 'Atribúty';

$strBack = 'Spä»';
$strBinary = 'Binárny';
$strBinaryDoNotEdit = 'Binárny - neupravujte ';
$strBookmarkLabel = 'Názov';
$strBookmarkQuery = 'Obµúbený SQL dotaz';
$strBookmarkThis = 'Prida» tento SQL dotaz do obµúbených';
$strBookmarkView = 'Iba prezrie»';
$strBrowse = 'Prechádza»';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'nieje mo¾né nahra» roz¹írenie pre MySQL,<br />prosím skontrolujte konfiguráciu PHP.';
$strCarriage = 'Návrat vozíku (Carriage return): \\r';
$strChange = 'Zmeni»';
$strCheckAll = 'Oznaèi» v¹etko';
$strCheckDbPriv = 'Skontrolova» privilégia databázy';
$strCheckTable = 'Skontrolova» tabuµku';
$strColumn = 'Ståpec';
$strColumnNames = 'Názvy ståpcov';
$strCompleteInserts = 'Úplné vlo¾enie';
$strConfirm = 'Skutoène si ¾eláte toto vykona»?';
$strCopyTable = 'Skopírova» tabuµku do (databáza<b>.</b>tabuµka):';
$strCopyTable = 'Skopírova» tabuµku do';
$strCopyTableOK = 'Tabuµka %s bola skorírovaná do %s.';
$strCreate = 'Vytvori»';
$strCreateNewDatabase = 'Vytvori» novú databázu';
$strCreateNewTable = 'Vytvori» novú tabuµku v databáze ';
$strCriteria = 'Kritéria';

$strData = 'Dáta';
$strDatabase = 'Databáza ';
$strDatabaseHasBeenDropped = 'Databáza %s bola zmazaná.';
$strDatabases = 'databáz(y)';
$strDatabasesStats = '©tatistiky databázy';
$strDataOnly = 'Iba dáta';
$strDefault = 'Predvolené';
$strDelete = 'Zmaza»';
$strDeleted = 'Riadok bol zmazaný';
$strDeletedRows = 'Zmazané riadky:';
$strDeleteFailed = 'Mazanie bolo neúspe¹né!';
$strDeleteUserMessage = 'Pou¾ívateµ %s bol zmazaný.';
$strDescending = 'Zostupne';
$strDisplay = 'Zobrazi»';
$strDisplayOrder = 'Zobrazi» zoradené:';
$strDoAQuery = 'Vykona» "dotaz podµa príkladu" (wildcard: "%")';
$strDocu = 'Dokumentácia';
$strDoYouReally = 'Skutoène chcete vykona» príkaz ';
$strDrop = 'Odstráni»';
$strDropDB = 'Odstráni» databázu ';
$strDropTable = 'Zru¹i» tabuµku';
$strDumpingData = 'Dampujem dáta pre tabuµku';
$strDynamic = 'dynamic';

$strEdit = 'Upravi»';
$strEditPrivileges = 'Upravi» privilégia';
$strEffective = 'Efektívny';
$strEmpty = 'Vyprázdni»';
$strEmptyResultSet = 'MySQL vrátil prázdny výsledok (tj. nulový poèet riadkov).';
$strEnd = 'Koniec';
$strEnglishPrivileges = ' Poznámka: názvy MySQL privilégií sú uvádzané v angliètine. ';
$strError = 'Chyba';
$strExtendedInserts = 'Roz¹írené vkladanie';
$strExtra = 'Extra';

$strField = 'Pole';
$strFieldHasBeenDropped = 'Pole %s bolo odstránené';
$strFields = 'Polia';
$strFieldsEmpty = ' Poèet polí je prázdny! ';
$strFieldsEnclosedBy = 'Polia uzatvorené';
$strFieldsEscapedBy = 'Polia uvedené pomocou;
$strFieldsTerminatedBy = 'Polia ukonèené';
$strFixed = 'pevný';
$strFormat = 'Formát';
$strFormEmpty = 'Chýbajúca polo¾ka vo formulári !';
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
$strIfYouWish = 'Ak si zeláte nahra» iba urèité ståpce tabuµky, ¹pecifikujte ich ako zoznam polí oddelený èiarkou.';
$strIndex = 'Index';
$strIndexes = 'Indexy';
$strIndexHasBeenDropped = 'Index pre %s bol odstránený';
$strInsert = 'Vlo¾i»';
$strInsertAsNewRow = 'Vlo¾i» ako nový riadok';
$strInsertedRows = 'Vlo¾ené riadky:';
$strInsertNewRow = 'Vlo¾i» nový riadok';
$strInsertTextfiles = 'Vlo¾i» textové súbory do tabuµky';
$strInstructions = 'In¹trukcie';
$strInUse = 'práve sa pou¾íva';
$strInvalidName = '"%s" je rezervované slovo, nemô¾e by» pou¾ité ako názov databázy/tabuµky/poµa.';

$strKeepPass = 'Nezmeni» heslo';
$strKeyname = 'Kµúèový názov';
$strKill = 'Zabi»';

$strLength = 'Då¾ka';
$strLengthSet = 'Då¾ka/Nastavi»*';
$strLimitNumRows = 'záznamov na stránku';
$strLineFeed = 'Ukonèenie riadku (Linefeed): \\n';
$strLines = 'Riadky';
$strLinesTerminatedBy = 'Riadky ukonèené';
$strLocationTextfile = 'Lokácia textového súboru';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Odhlási» sa';

$strModifications = 'Zmeny boli ulo¾ené';
$strModify = 'Zmeni»';
$strMoveTable = 'Presunú» tabuµku do (databáza<b>.</b>tabuµka):';
$strMoveTableOK = 'Tabuµka %s bola presunutá do %s.';
$strMySQLReloaded = 'MySQL znovu-naèítaná.';
$strMySQLSaid = 'MySQL hlási: ';
$strMySQLShowProcess = 'Zobrazi» procesy';
$strMySQLShowStatus = 'Zobrazi» MySQL informácie o behu';
$strMySQLShowVars = 'Zobrazi» MySQL systémové premenné';

$strName = 'Názov';
$strNbRecords = 'Poèet záznamov';
$strNext = 'Ïal¹í';
$strNo = 'Nie';
$strNoDatabases = '®iadne databázy';
$strNoDropDatabases = 'Mo¾nos» "DROP DATABASE" vypnutá.';
$strNoModification = '®iadna zmena';
$strNoPassword = '®iadne heslo';
$strNoPrivileges = '®iadne privilégia';
$strNoQuery = '®iadny SQL dotaz!';
$strNoRights = 'Nemáte dostatoèné práva na vykonanie tejto akcie!';
$strNoTablesFound = 'Neboli nájdené ¾iadne tabuµky v tejto datábaze.';
$strNotNumber = 'Toto nieje èíslo!';
$strNotValidNumber = ' nieje platné èíslo riadku!';
$strNoUsersFound = 'Nebol nájdený ¾iadny pou¾ívateµ.';
$strNull = 'Nulový';
$strNumberIndexes = ' Poèet roz¹írených indexov ';

$strOftenQuotation = 'Èasto uvodzujúce znaky. Voliteµne znamena, ¾e iba polia typu char a varchar sú uzatvorené do "uzatváracích" znakov.';
$strOptimizeTable = 'Optimalozova» tabuµku';
$strOptionalControls = 'Volitelné. Urèuje ako zapisova» alebo èíta» ¹peciálne znaky.';
$strOptionally = 'Voliteµne';
$strOr = 'alebo';
$strOverhead = 'Naviac';

$strPartialText = 'Èiastoèné texty';
$strPassword = 'Heslo';
$strPasswordEmpty = 'Heslo je prázdne!';
$strPasswordNotSame = 'Heslá sa nezhodujú!';
$strPHPVersion = 'Verzia PHP';
$strPmaDocumentation = 'phpMyAdmin Dokumentácia';
$strPos1 = 'Zaèiatok';
$strPrevious = 'Predchádzajúci';
$strPrimary = 'Primárny';
$strPrimaryKey = 'Primárny kµúè';
$strPrimaryKeyHasBeenDropped = 'Primárny kµúè bol zru¹ený';
$strPrintView = 'Náhµad k tlaèi';
$strPrivileges = 'Privilégia';
$strProperties = 'Vlastnosti';

$strQBE = 'Dotaz podµa príkladu';
$strQBEDel = 'Zmaza»';
$strQBEIns = 'Vlo¾i»';
$strQueryOnDb = ' SQL dotaz v databáze ';

$strRecords = 'Záznamov';
$strReloadFailed = 'Znovu-naèítanie MySQL bolo neúspe¹né.';
$strReloadMySQL = 'Znovu-naèíta» MySQL';
$strRememberReload = 'Nezabudnite znovu-naèíta» MySQL server.';
$strRenameTable = 'Premenova» tabuµku na';
$strRenameTableOK = 'Tabuµka %s bola premenovaná na %s';
$strRepairTable = 'Opravi» tabuµku';
$strReplace = 'Nahradi»';
$strReplaceTable = 'Nahradi» dáta v tabuµke súborom';
$strReset = 'Pôvodné (Reset)';
$strReType = 'Napísa» znova';
$strRevoke = 'Zrusi»';
$strRevokeGrant = 'Zru¹i» polovenie pridelova» privilégia';
$strRevokeGrantMessage = 'Bolo zru¹ené právo pridelova» privilégia pre';
$strRevokeMessage = 'Boli zru¹ené privilégia pre';
$strRevokePriv = 'Zru¹i» privilégia';
$strRowLength = 'Då¾ka riadku';
$strRows = 'Riadkov';
$strRowsFrom = 'riadky zaèínajú od';
$strRowSize = ' Veµkos» riadku ';
$strRowsStatistic = '©tatistika riadku';
$strRunning = 'be¾i na ';
$strRunningAs = 'ako';
$strRunQuery = 'Odo¹li dotaz';
$strRunSQLQuery = 'Spusti» SQL dotaz/dotazy na databázu %s';

$strSave = 'Ulo¾i»';
$strSelect = 'Vybra»';
$strSelectFields = 'Zvoli» pole (najmenej jedno):';
$strSelectNumRows = 'v dotaze';
$strSend = 'Po¹li';
$strSequence = 'Seq.';
$strServerChoice = 'Voµba serveru';
$strServerVersion = 'Verzia serveru';
$strSetEnumVal = 'Ak je pole typu "enum" alebo "set", prosím zadávajte hodnoty v tvare: \'a\',\'b\',\'c\'...<br />Ak dokonca porebujete zada» spätné lomítko ("\") alebo apostrof ("\'") pri týchto hodnotách, zadajte ich napríklad takto \'\\\\xyz\' alebo \'a\\\'b\'.';
$strShow = 'Ukáza»';
$strShowAll = 'Zobrazi» v¹etko';
$strShowCols = 'Zobrazi» ståpce';
$strShowingRecords = 'Ukáza» záznamy ';
$strShowPHPInfo = 'Zobrazi» informácie o PHP';
$strShowTables = 'Zobrazi» tabuµky';
$strShowThisQuery = ' Zobrazi» tento dotaz znovu ';
$strSingly = '(po jednom)';
$strSize = 'Veµkos»';
$strSort = 'Triedi»';
$strSpaceUsage = 'Zabrané miesto';
$strSQLQuery = 'SQL dotaz';
$strStartingRecord = 'Zaèiatok záznamu';
$strStatement = 'Údaj';
$strStrucCSV = 'CSV dáta';
$strStrucData = '©truktúru a dáta';
$strStrucDrop = 'Pridaj \'vyma¾ tabuµku\'';
$strStrucExcelCSV = 'CSV pre Ms Excel dáta';
$strStrucOnly = 'Iba ¹truktúru';
$strSubmit = 'Odo¹li';
$strSuccess = 'SQL dotaz bol úspe¹ne vykonaný';
$strSum = 'Celkom';

$strTable = 'tabuµka ';
$strTableComments = 'Komentár k tabuµke';
$strTableEmpty = 'Tabuµka je prázdna!';
$strTableHasBeenDropped = 'Tabuµka %s bola odstránená';
$strTableHasBeenEmptied = 'Tabuµka %s bola vyprázdená';
$strTableMaintenance = 'Údr¾ba tabuµky';
$strTables = '%s tabuµka(y)';
$strTableStructure = '©truktúra tabuµky pre tabuµku';
$strTableType = 'Typ tabuµky';
$strTextAreaLength = ' Toto mo¾no nepojde upravi»,<br /> kôli svojej då¾ke ';
$strTheContent = 'Obsah Vá¹ho súboru bol vlo¾ený.';
$strTheContents = 'Obsah súboru prepí¹e obsah vybranej tabuµky v riadkoch s identickým primárnym alebo unikátnym kµúèom.';
$strTheTerminator = 'Ukonèenie polí.';
$strTotal = 'celkovo';
$strType = 'Typ';

$strUncheckAll = 'Odznaèi» v¹etko';
$strUnique = 'Unikátny';
$strUpdatePrivMessage = 'Boli aktualizované privilégia pre %s.';
$strUpdateProfile = 'Aktualizova» profil:';
$strUpdateProfileMessage = 'Profil bol aktualizovaný.';
$strUpdateQuery = 'Aktualizova» dotaz';
$strUsage = 'Vyu¾itie';
$strUseBackquotes = ' Pou¾i» opaèný apostrof pri názvoch tabuliek a polí ';
$strUser = 'Pou¾ívateµ';
$strUserEmpty = 'Meno pou¾ívateµa je prázdne!';
$strUserName = 'Meno pou¾ívateµa';
$strUsers = 'Pou¾ívatelia';
$strUseTables = 'Pou¾i» tabuµky';

$strValue = 'Hodnota';
$strViewDump = 'Zobrazi» dump (schému) tabuµky';
$strViewDumpDB = 'Zobrazi» dump (schému) databázy';

$strWelcome = 'Vitajte v ';
$strWithChecked = 'Výber:';
$strWrongUser = 'Zlé pou¾ívateµské meno alebo heslo. Prístup zamietnutý.';

$strYes = 'Áno';

$strZip = '"zo zipované"';

// To translate
$strFlushTable = 'Flush the table ("FLUSH")';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strTableHasBeenFlushed = 'Table %s has been flushed';
?>
