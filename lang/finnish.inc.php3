<?php
/* $Id$ */

/*
 * Finnish language file by Visa Kopu, visa@visakopu.net
 */

$charset = 'iso-8859-1';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('tavua', 'kt', 'Mt', 'Gt');

$day_of_week = array('Su', 'Ma', 'Ti', 'Ke', 'To', 'Pe', 'La');
$month = array('Tammi', 'Helmi', 'Maalis', 'Huhti', 'Touko', 'Kesä', 'Heinä', 'Elo', 'Syys', 'Loka', 'Marras', 'Joulu');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d.%m.%Y klo %H:%M';


$strAccessDenied = 'Pääsy kielletty';
$strAction = 'Toiminto';
$strAddDeleteColumn = 'Lisää/poista sarakkeita';
$strAddDeleteRow = 'Lisää/poista hakuehtoja';
$strAddNewField = 'Lisää sarake';
$strAddPriv = 'Lisää käyttöoikeus';
$strAddPrivMessage = 'Olet lisännyt uuden käyttöoikeuden.';
$strAddSearchConditions = 'Lisää hakuehtoja ("where"-lauseen sisältö):';
$strAddUser = 'Lisää uusi käyttäjä';
$strAddUserMessage = 'Olet lisännyt uuden käyttäjän.';
$strAffectedRows = 'Rivejä:';
$strAfter = 'Jälkeen sarakkeen:';
$strAll = 'Kaikki';
$strAlterOrderBy = 'Järjestä taulu';
$strAnalyzeTable = 'Analysoi taulu';
$strAnd = 'Ja';
$strAnIndex = 'Sarakkeelle %s on lisätty indeksi';
$strAny = 'Mikä tahansa';
$strAnyColumn = 'Mikä tahansa sarake';
$strAnyDatabase = 'Mikä tahansa tietokanta';
$strAnyHost = 'Mikä tahansa palvelin';
$strAnyTable = 'Mikä tahansa taulu';
$strAnyUser = 'Mikä tahansa käyttäjä';
$strAPrimaryKey = 'Sarakkeelle %s on lisätty ensisijainen avain';
$strAscending = 'Nouseva';
$strAtBeginningOfTable = 'Taulun alkuun';
$strAtEndOfTable = 'Taulun loppuun';
$strAttr = 'Attribuutit';

$strBack = 'Takaisin';
$strBinary = 'Binääridata';
$strBinaryDoNotEdit = 'Binääridataa - älä muokkaa';
$strBookmarkLabel = 'Tunniste';
$strBookmarkQuery = 'Tallennettu SQL-lause';
$strBookmarkThis = 'Tallenna SQL-lause';
$strBookmarkView = 'Näytä';
$strBrowse = 'Selaa';
$strBzip = '"bzip-pakattu"';

$strCantLoadMySQL = 'MySQL-laajennusta ei voitu ladata,<br />tarkista PHP:n asetukset.';
$strCarriage = 'CR-rivinvaihto: \\r';
$strChange = 'Muokkaa';
$strCheckAll = 'Tarkista kaikki';
$strCheckDbPriv = 'Tarkista tietokannan käyttöoikeudet';
$strCheckTable = 'Tarkista taulu';
$strColumn = 'Sarake';
$strColumnNames = 'Sarakkeiden nimet';
$strCompleteInserts = 'Täydelliset insert-lauseet';
$strConfirm = 'Oletko varma, että haluat tehdä tämän?';
$strCopyTable = 'Kopioi taulu (tietokanta<b>.</b>taulu):';
$strCopyTableOK = 'Taulu %s on kopioitu nimelle %s.';
$strCreate = 'Luo';
$strCreateNewDatabase = 'Luo uusi tietokanta';
$strCreateNewTable = 'Luo uusi taulu tietokantaan ';
$strCriteria = 'Hakuehdot';

$strData = 'Data';
$strDatabase = 'Tietokanta ';
$strDatabases = 'tietokantaa';
$strDatabaseHasBeenDropped = 'Tietokanta %s on pudotettu.';
$strDatabasesStats = 'Tietokantastatistiikka';
$strDataOnly = 'Vain data';
$strDefault = 'Oletusarvo';
$strDelete = 'Poista';
$strDeleted = 'Rivi on poistettu';
$strDeletedRows = 'Poistetut rivit:';
$strDeleteFailed = 'Poistaminen epäonnistui!';
$strDeleteUserMessage = 'Käyttäjä %s on poistettu.';
$strDescending = 'Laskeva';
$strDisplay = 'Näytä';
$strDisplayOrder = 'Lajittelujärjestys:';
$strDoAQuery = 'Suorita "esimerkin mukainen haku" (jokerimerkki: "%")';
$strDocu = 'Ohjeet';
$strDoYouReally = 'Oletko varma että haluat ';
$strDrop = 'Pudota';
$strDropDB = 'Pudota tietokanta ';
$strDropTable = 'Pudota taulu';
$strDumpingData = 'Vedostan dataa taulusta';
$strDynamic = 'dynaaminen';

$strEdit = 'Muokkaa';
$strEditPrivileges = 'Muokkaa käyttöoikeuksia';
$strEffective = 'Varsinainen';
$strEmpty = 'Tyhjennä';
$strEmptyResultSet = 'MySQL palautti tyhjän vastauksen (ts. nolla riviä).';
$strEnd = 'Loppu';
$strEnglishPrivileges = ' Huom. MySQL-käyttöoikeuksien nimet ovat englanniksi ';
$strError = 'Virhe';
$strExtendedInserts = 'Yhteinen insert-lause';
$strExtra = 'Lisätiedot';

$strField = 'Sarake';
$strFieldHasBeenDropped = 'Sarake %s on pudotettu';
$strFields = 'Sarakkeet';
$strFieldsEmpty = ' Sarakkeiden lukumäärä on nolla! ';
$strFieldsEnclosedBy = 'Sarakkeiden ympäröintimerkki';
$strFieldsEscapedBy = 'Koodinvaihtomerkki (escape)';
$strFieldsTerminatedBy = 'Sarakkeiden erotinmerkki';
$strFixed = 'kiinteä';
$strFormat = 'Muoto';
$strFormEmpty = 'Tarvittava tieto puuttuu lomakkeesta!';
$strFullText = 'Koko tekstit';
$strFunction = 'Funktio';

$strGenTime = 'Luontiaika';
$strGo = 'Suorita';
$strGrants = 'Luvat';
$strGzip = '"gzip-pakattu"';

$strHasBeenAltered = 'on muutettu.';
$strHasBeenCreated = 'on luotu.';
$strHome = 'Etusivu';
$strHomepageOfficial = 'phpMyAdminin viralliset sivut';
$strHomepageSourceforge = 'phpMyAdminin sivut SourceForge-palvelussa';
$strHost = 'Palvelin';
$strHostEmpty = 'Palvelimen nimi puuttuu!';

$strIdxFulltext = 'Koko teksti';
$strIfYouWish = 'Jos haluat hakea vain osan taulun sarakkeista, syötä pilkuilla erotettu lista sarakkeiden nimistä.';
$strIndex = 'Indeksi';
$strIndexHasBeenDropped = 'Indeksi %s on pudotettu';
$strIndexes = 'Indeksit';
$strInsert = 'Lisää rivi';
$strInsertAsNewRow = 'Lisää uutena rivinä';
$strInsertedRows = 'Lisätyt rivit:';
$strInsertNewRow = 'Lisää uusi rivi';
$strInsertTextfiles = 'Syötä tekstitiedosto tauluun';
$strInstructions = 'komentoa';
$strInUse = 'käytössä';
$strInvalidName = '"%s" on varattu sana eikä sitä voi käyttää tietokannan, taulun tai sarakkeen nimenä.';

$strKeepPass = 'Älä vaihda salasanaa';
$strKeyname = 'Avaimen nimi';
$strKill = 'Tapa';

$strLength = 'Pituus';
$strLengthSet = 'Pituus/Arvot*';
$strLimitNumRows = 'riviä/sivu';
$strLineFeed = 'LF-rivinvaihto: \\n';
$strLines = 'Rivit';
$strLinesTerminatedBy = 'Rivien erotinmerkki';
$strLocationTextfile = 'Tiedoston sijainti';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Poistu';

$strModifications = 'Muutokset tallennettu';
$strModify = 'Muokkaa';
$strMoveTable = 'Siirra taulu (tietokanta<b>.</b>taulu):';
$strMoveTableOK = 'Taulu %s on siirretty %s.';
$strMySQLReloaded = 'MySQL uudelleenladattu.';
$strMySQLSaid = 'MySQL ilmoittaa: ';
$strMySQLShowProcess = 'Näytä prosessit';
$strMySQLShowStatus = 'Näytä MySQL:n ajonaikaiset tiedot';
$strMySQLShowVars = 'Näytä MySQL:n järjestelmämuuttujat';

$strName = 'Nimi';
$strNbRecords = 'Rivien lukumäärä';
$strNext = 'Seuraava';
$strNo = 'Ei';
$strNoDatabases = 'Ei tietokantoja';
$strNoDropDatabases = '"DROP DATABASE"-lauseiden käyttö on estetty.';
$strNoModification = 'Ei muutoksia';
$strNoPassword = 'Ei salasanaa';
$strNoPrivileges = 'Ei käyttöoikeuksia';
$strNoRights = 'Sinulla ei ole tarpeeksi oikeuksia!';
$strNoQuery = 'Ei SQL lausetta!';
$strNoTablesFound = 'Tietokannasta ei löytynyt yhtään taulua.';
$strNotNumber = 'Tämä ei ole numero!';
$strNotValidNumber = ' ei ole hyväksyttävä rivin numero!';
$strNoUsersFound = 'Käyttäjiä ei löytynyt.';
$strNull = 'Tyhjä';
$strNumberIndexes = ' Kehittyneiden indeksien määrä ';

$strOftenQuotation = 'Yleensä lainausmerkki. "Valinnaisesti" tarkoittaa, että vain char- ja varchar-tyyppiset sarakkeet ympäröidään annetulla ympäröintimerkillä.';
$strOptimizeTable = 'Optimoi taulu';
$strOptionalControls = 'Valinnainen. Ohjaa erikoismerkkien lukua ja kirjoitusta.';
$strOptionally = 'Valinnaisesti';
$strOr = 'Tai';

$strPartialText = 'Osittaiset tekstit';
$strPassword = 'Salasana';
$strPasswordEmpty = 'Salasana puuttuu!';
$strPasswordNotSame = 'Salasanat eivät ole samat!';
$strPHPVersion = 'PHP:n versio';
$strPmaDocumentation = 'phpMyAdminin dokumentaatio';
$strPos1 = 'Alku';
$strPrevious = 'Edellinen';
$strPrimary = 'Ensisijainen';
$strPrimaryKey = 'Ensisijainen avain';
$strPrimaryKeyHasBeenDropped = 'Ensisijainen avain on pudotettu';
$strPrintView = 'Tulostusversio';
$strPrivileges = 'Käyttöoikeudet';
$strProperties = 'Asetukset';

$strQBE = 'Esimerkin mukainen haku';
$strQBEDel = 'Poista';
$strQBEIns = 'Lisää';
$strQueryOnDb = 'Suorita SQL-lause tietokannassa ';

$strRecords = 'riviä';
$strReloadFailed = 'MySQL:n uudelleenlataus epäonnistui.';
$strReloadMySQL = 'Lataa MySQL uudelleen';
$strRememberReload = 'Muista käynnistää palvelin uudestaan.';
$strRenameTable = 'Nimeä taulu uudelleen';
$strRenameTableOK = 'Taulun %s nimi on nyt %s';
$strRepairTable = 'Korjaa taulu';
$strReplace = 'Korvaa';
$strReplaceTable = 'Korvaa taulun nykyiset rivit tiedostolla';
$strReset = 'Tyhjennä';
$strReType = 'Kirjoita uudelleen';
$strRevoke = 'Mitätöi';
$strRevokeGrant = 'Mitätöi lupa';
$strRevokeGrantMessage = 'Olet mitätöinyt luvan käyttöoikeuteen';
$strRevokeMessage = 'Olet mitätöinyt käyttöoikeudet tauluun';
$strRevokePriv = 'Mitätöi käyttöoikeudet';
$strRowLength = 'Rivin pituus';
$strRows = 'riviä';
$strRowsFrom = 'riviä alkaen rivistä';
$strRowSize = ' Rivin koko ';
$strRowsStatistic = 'Rivistatistiikka';
$strRunning = 'palvelimella ';
$strRunningAs = 'käyttäjänä';
$strRunQuery = 'Suorita';
$strRunSQLQuery = 'Suorita SQL-lauseita tietokannassa %s';
$strSave = 'Tallenna';
$strSelect = 'Hae';
$strSelectFields = 'Valitse sarakkeet (vähintään yksi):';
$strSelectNumRows = 'lauseessa';
$strSend = 'Tallenna tiedostoksi';
$strSequence = 'Jakso';
$strServerChoice = 'Valitse palvelin';
$strServerVersion = 'Palvelimen versio';
$strSetEnumVal = 'Jos sarakkeen tietotyyppi on "enum" tai "set", syötä vaaditut arvot tässä muodossa: \'a\',\'b\',\'c\'...<br />Jos tarvitset arvoissa kenoviivaa ("\") tai heittomerkkiä ("\'"), laita merkin eteen kenoviiva (esim. \'\\\\xyz\' tai \'a\\\'b\').';
$strShow = 'Näytä';
$strShowAll = 'Näytä kaikki'; 
$strShowCols = 'Näytä sarakkeet';
$strShowingRecords = 'Näkyvillä rivit ';
$strShowPHPInfo = 'Näytä tietoja PHP:n asetuksista';
$strShowTables = 'Näytä taulut';
$strShowThisQuery = ' Näytä lause uudelleen ';
$strSingly = '(yksitellen)';
$strSize = 'Koko';
$strSort = 'Järjestys';
$strSpaceUsage = 'Levytilan käyttö';
$strSQLQuery = 'SQL-lause';
$strStartingRecord = 'Aloitusrivi';
$strStatement = 'Tieto';
$strStrucCSV = 'CSV-muotoinen data';
$strStrucData = 'Rakenne ja data';
$strStrucDrop = 'Lisää \'DROP TABLE\'-rivit';
$strStrucExcelCSV = 'CSV-muoto MS Exceliä varten';
$strStrucOnly = 'Vain rakenne';
$strSubmit = 'Lähetä';
$strSuccess = 'SQL-lause on suoritettu';
$strSum = 'Summa';

$strTable = 'taulu ';
$strTableComments = 'Kommentoi taulua';
$strTableEmpty = 'Taulun nimi puuttuu!';
$strTableHasBeenDropped = 'Taulu %s on pudotettu';
$strTableHasBeenEmptied = 'Taulu %s on tyhjennetty';
$strTableMaintenance = 'Taulun huolto';
$strTables = '%s taulu(a)';
$strTableStructure = 'Rakenne taululle';
$strTableType = 'Taulun muoto';
$strTextAreaLength = ' Pituudestaan johtuen<br /> tätä saraketta ei ehkä voi muokata ';
$strTheContent = 'Tiedoston sisältö on syötetty.';
$strTheContents = 'Tiedoston sisältö korvaa valitusta taulusta ne rivit, joissa on sama ensisijainen (primary) tai yksikäsitteinen (unique) avain kuin tiedoston riveissä.';
$strTheTerminator = 'Sarakkeiden erotin.';
$strTotal = 'yhteensä';
$strType = 'Tyyppi';

$strUncheckAll = 'Poista valinta kaikista';
$strUnique = 'Uniikki';
$strUpdatePrivMessage = 'Käyttäjän %s käyttöoikeudet on päivitetty.';
$strUpdateProfile = 'Päivitä profiili:';
$strUpdateProfileMessage = 'Profiili on päivitetty.';
$strUpdateQuery = 'Päivitä lause';
$strUsage = 'Tila';
$strUseBackquotes = 'Laita taulujen ja sarakkeiden nimet lainausmerkkeihin';
$strUser = 'Käyttäjä';
$strUserEmpty = 'Käyttäjän nimi puuttuu!';
$strUserName = 'Käyttäjänimi';
$strUsers = 'käyttäjää';
$strUseTables = 'Käytä tauluja';

$strValue = 'Arvo';
$strViewDump = 'Tee vedos (dump) taulusta';
$strViewDumpDB = 'Tee vedos (dump) tietokannasta';

$strWelcome = 'Tervetuloa! - ';
$strWithChecked = 'Valitut:';
$strWrongUser = 'Väärä käyttäjätunnus tai salasana. Pääsy kielletty.';

$strYes = 'Kyllä';

$strZip = '"zip-pakattu"';

// To translate

$strOverhead = 'Overhead'; //to translate

?>
