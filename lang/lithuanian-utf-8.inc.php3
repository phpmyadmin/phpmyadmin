<?php

/* $Id$ */

$charset = 'utf-8';
$allow_recoding = TRUE;
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
$byteUnits = array('baitų', 'Kilobaitų', 'Megabaitų', 'Gigabaitų');

$day_of_week = array('Sekmadienis', 'Pirmadienis', 'Antradienis', 'Trečiadienis', 'Ketvirtadienis', 'Penktadienis', 'Šeštadienis');
$month = array('Sausio', 'Vasario', 'Kovo', 'Balandžio', 'Gegužio', 'Birželio', 'Liepos', 'Rugpjūčio', 'Rugsėjo', 'Spalio', 'Lapkričio', 'Gruodžio');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = ' %Y m. %B %d d.  %H:%M';


$strAccessDenied = 'Priėjimas uždraustas';
$strAction = 'Valdymo veiksmai';
$strAddDeleteColumn = 'Įterpti/Trinti stulpelius';
$strAddDeleteRow = 'Įterpti/Trinti požymio eilutę(es)';
$strAddNewField = 'Įterpti naują laukelį(ius)';
$strAddPriv = 'Įterpti privilegiją(as)';
$strAddPrivMessage = 'Jūs įterpėte privilegijas.';
$strAddSearchConditions = 'Įterpkite paieškos sąlygas į "where" sakinį:';
$strAddToIndex = 'Įterpti indeksui papildomus &nbsp;%s&nbsp;stulpelį(ius)';
$strAddUserMessage = 'Jūs sukūrėte naują vartotoją.';
$strAddUser = 'Sukurti naują vartotoją';
$strAffectedRows = 'Pakeista eilučių:';
$strAfterInsertBack = 'Sugrįžti į buvusį puslapį';
$strAfterInsertNewInsert = 'Įterpti sekančią naują eilutę';
$strAfter = 'Prieš %s';
$strAllTableSameWidth = 'rodyti visas lenteles vienodo pločio?';
$strAll = 'Viską';
$strAlterOrderBy = 'Pakeisti lentelės rūšiavimą pagal:';
$strAnalyzeTable = 'Analizuoti lentelę';
$strAnd = 'IR';
$strAnIndex = 'Indeksas sukurtas %s stulpeliui';
$strAny = 'Bet kurį(ią)';
$strAnyColumn = 'Bet kurį stulpelį';
$strAnyDatabase = 'Bet kurią duomenų bazę';
$strAnyHost = 'Bet kurį prisijungimo adresą';
$strAnyTable = 'Bet kurią lentelę';
$strAnyUser = 'Bet kurį vartotoją';
$strAPrimaryKey = 'Stulpeliui %s sukurtas PIRMINIS raktas';
$strAscending = 'Didėjimo tvarka';
$strAtBeginningOfTable = 'Lentelės pradžioje';
$strAtEndOfTable = 'Lentelės pabaigoje';
$strAttr = 'Atributai';

$strBack = 'Atgal';
$strBinaryDoNotEdit = 'Dvejetainis - nekeisti';
$strBinary = 'Dvejetainis';
$strBookmarkDeleted = 'Nuoroda ištrinta.';
$strBookmarkLabel = 'Nuorodos Antraštė';
$strBookmarkQuery = 'Sukurti nuoroda SQL-užklausai';
$strBookmarkThis = 'Sukurti nuorodą';
$strBookmarkView = 'Peržiūra';
$strBrowse = 'Peržiūrėti';
$strBzip = '"bzip"';

$strCantLoadMySQL = 'negali užkrauti MySQL proceso,<br />patikrinkite PHP nustatymus.';
$strCantLoadRecodeIconv = 'Nepavyko užkrauti <em>iconv</em> arba <em>recode</em> plėtinių, reikalingų koduotės kovertavimui. Suteikite PHP teises naudotis šiais išplėtimais arba išjunkite phpMyAdmin koduotės konvertavimą. ';
$strCantRenameIdxToPrimary = 'Indeksą pervadinti PIRMINIU nepavyko!';
$strCantUseRecodeIconv = 'Kraunant plėtinių pranešimus, nepavyko pasinaudoti <em>iconv</em> arba <em>libiconv</em> arba <em>recode_string</em> funkcijomis. Pasitkrinkite PHP konfigūraciją. ';
$strCardinality = 'Elementų skaičius';
$strCarriage = 'Grįžimo į eilutės pradžią simbolis(CR): \\r';
$strChangeDisplay = 'Pasirinkite lauką, kurį norite peržiūrėti';
$strChange = 'Keisti';
$strChangePassword = 'Pakeisti slaptažodį';
$strCheckAll = 'Pažymėti visus(as)';
$strCheckDbPriv = 'Pažymėti duomenų bazės privilegijas';
$strCheckTable = 'Patikrinti lentelę';
$strChoosePage = 'Pasirinkite puslapį redagavimui';
$strColComFeat = 'Stulpelių komentarų išvedimas';
$strColumnNames = 'Stulpelių vardai';
$strColumn = 'Stulpelis';
$strComments = 'Komentarai';
$strCompleteInserts = 'Visiškas įterpimas';
$strConfigFileError = 'phpMyAdmin negalėjo perskaityti konfigūracinės bylos!<br />Tai galėjo nutikti jeigu <u>php</u> rado byloje vykdymo klaidą arba visai nerando bylos.<br />Prašome kreiptis į konfigūracinę bylą tiesiogiai (naudojantis nuoroda žemiau) ir perskaityti gautus <u>php</u> klaidų pranešimą(us). Daugeliu atveju vienoje/keletoje eilučių truksta kabučių ir/arba kabliataškio.<br />Jeigu išvedamas tuščias naršyklės langas - viskas tvarkoje (klaidų nepastebėta).';
$strConfigureTableCoord = 'Nustatykite lentelės %s koordinates';
$strConfirm = 'Ar TIKRAI norite atlikti šį veiksmą?';
$strCookiesRequired = 'Sausainėliai(Cookies) turi būti priimami.';
$strCopyTable = 'Kopijuoti lentelė į (duomenų bazė<b>.</b>lentelė):';
$strCopyTableOK = 'Letelė %s nukopijuota į %s.';
$strCreateIndex = 'Sukurti indeksą &nbsp;%s&nbsp;stulpeliui(iams)';
$strCreateIndexTopic = 'Sukurti naują indeksą';
$strCreateNewDatabase = 'Sukurti naują duomenų bazę';
$strCreateNewTable = 'Sukurti naują lentelę duomenų bazėje %s';
$strCreatePage = 'Sukurti naują puslapį';
$strCreatePdfFeat = 'PDF bylos generavimas';
$strCreate = 'Sukurti';
$strCriteria = 'Kriterijai';

$strDatabase = 'Duomenų bazė ';
$strDatabaseHasBeenDropped = 'Duomenų bazė %s ištrinta.';
$strDatabases = 'duomenų bazės';
$strDatabasesStats = 'Duomenų bazių statistika';
$strDatabaseWildcard = 'Duomenų bazė (galima naudoti pakaitos simbolius):';
$strData = 'Duomenys';
$strDataOnly = 'Tik duomenys';
$strDefault = 'Nutylint';
$strDeleted = 'Eilutė ištrinta';
$strDeletedRows = 'Eilutės ištrintos:';
$strDeleteFailed = 'Trynimo klaida!';
$strDelete = 'Trinti';
$strDeleteUserMessage = 'Jūs ištynėte vartotoją %s.';
$strDescending = 'Mažėjimo tvarka';
$strDisabled = 'Išjungta';
$strDisplay = 'Atvaizduoti';
$strDisplayFeat = 'Išvedimo sąvybės';
$strDisplayOrder = 'Atvaizdavimo tvarka:';
$strDisplayPDF = 'Rodyti PDF vaizdą';
$strDoAQuery = 'Vykdyti "užklausą pagal pavyzdį" (pakaitos simbolis: "%")';
$strDocu = 'Dokumentacija';
$strDoYouReally = 'Ar TIKRAI norite ';
$strDropDB = 'Panaikinti duomenų bazę %s';
$strDrop = 'Panaikinti';
$strDropTable = 'Panaikinti lentelę';
$strDumpingData = 'Sukurta duomenų kopija lentelei';
$strDumpXRows = 'Išvesti %s eilučių pradedant nuo %s eilutės.';
$strDynamic = 'dinaminis';

$strEditPDFPages = 'Taisyti PDF puslapius';
$strEditPrivileges = 'Taisyti privilegijas';
$strEdit = 'Taisyti';
$strEffective = 'Efektyvus';
$strEmpty = 'Panaikinti reikšmes';
$strEmptyResultSet = 'MySQL gražino tuščią rezultatų rinkinį (nėra eilučių).';
$strEnabled = 'Įjungta';
$strEnd = 'Pabaiga';
$strEnglishPrivileges = ' Pastaba: MySQL privilegijų pavadinimai pateikiami anglų kalba';
$strError = 'Klaida';
$strExport = 'Eksportuoti';
$strExportToXML = 'Išvesti XML formatu';
$strExtendedInserts = 'Išplėstinis įterpimas';
$strExtra = 'Papildomai';

$strFieldHasBeenDropped = 'Laukas %s išmestas';
$strField = 'Laukas';
$strFieldsEmpty = ' Tuščia laukų skaičiaus reikšmė! ';
$strFieldsEnclosedBy = 'Laukų reikšmės apskliaustos  simboliais';
$strFieldsEscapedBy = 'Laukų reikšmės baigiasi simboliu';
$strFields = 'Kiek laukų';
$strFieldsTerminatedBy = 'Laukų pabaigos žymė';
$strFixed = 'fiksuotas';
$strFlushTable = 'Išvalyti lentelę ("FLUSH")';
$strFormat = 'Formatas';
$strFormEmpty = 'Trūksta reikšmės formoje !';
$strFullText = 'Visus tekstus';
$strFunction = 'Funkcija';

$strGenBy = 'Generavo:';
$strGeneralRelationFeat = 'Pagrindinės sąryšių sąvybės';
$strGenTime = 'Atlikimo laikas';
$strGo = 'Vykdyti';
$strGrants = 'Leisti';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'išplėsta.';
$strHasBeenCreated = 'sukurta.';
$strHaveToShow = 'Pasirinkite bent vieną stulpelį išvedimui';
$strHomepageOfficial = 'Oficialus phpMyAdmin tinklapis';
$strHomepageSourceforge = 'Parsisiųsti phpMyAdmin iš Sourceforge tinklapio';
$strHome = 'Pirmas puslapis';
$strHostEmpty = 'Tuščias prisijungimo adresas!';
$strHost = 'Prisijungimo adresas';

$strIdxFulltext = '"Fulltext" indeksas';
$strIfYouWish = 'Jei pageidaujate pakrauti keletą lentelės stulpelių, sudarykite laukų sąrašą atskirtą kabliataškiais.';
$strIgnore = 'Ignoruoti';
$strIndexes = 'Indeksai';
$strIndexHasBeenDropped = 'Indeksas %s panaikintas';
$strIndex = 'Indeksas';
$strIndexName = 'Indekso vardas&nbsp;:';
$strIndexType = 'Indekso tipas&nbsp;:';
$strInsertAsNewRow = 'Įterpti naują įrašą';
$strInsert = 'Įterpti';
$strInsertedRows = 'Įterptą eilučių:';
$strInsertNewRow = 'Įterpti naują eilutę';
$strInsertTextfiles = 'Įterpti duomenis iš tekstinio failo į lentelę';
$strInstructions = 'Instrukcijos';
$strInUse = 'šiuo metu naudojama';
$strInvalidName = '"%s" rezervuotas žodis, Jūs negalite šio žodžio naudoti kaip duomenų bazės, lentelės arba laukelio vardo.';

$strKeepPass = 'Nekeisti slaptažodžio';
$strKeyname = 'Raktinis žodis';
$strKill = 'Stabdyti procesą';

$strLength = 'Ilgis';
$strLengthSet = 'Ilgis/reikšmės*';
$strLimitNumRows = 'Eilučių skaičius puslapyje';
$strLineFeed = 'Eilutės: \\n';
$strLines = 'Eilučių';
$strLinesTerminatedBy = 'Eilutės pabaigos žymė';
$strLinkNotFound = 'Sąryšis nerastas';
$strLinksTo = 'Sąryšis su';
$strLocationTextfile = 'Tekstiniai SQL užklausų failai';
$strLogin = 'Įsiregistruoti';
$strLogout = 'Išsiregistruoti';
$strLogPassword = 'Slaptažodis:';
$strLogUsername = 'Vartotojo vardas:';

$strMissingBracket = 'Trūksta skliausto(ų)';
$strModifications = 'Pakeitimai išsaugoti';
$strModifyIndexTopic = 'Keisti indeksą';
$strModify = 'Keisti';
$strMoveTableOK = 'Lentelė %s perkelta į %s.';
$strMoveTable = 'Perkelti lentelė į (duomenų bazė<b>.</b>lentelė):';
$strMySQLCharset = 'MySQL koduotė';
$strMySQLReloaded = 'MySQL procesas perkrautas.';
$strMySQLSaid = 'MySQL atsakymas: ';
$strMySQLServerProcess = 'MySQL %pma_s1% procesas serveryje %pma_s2%. Įregistruotas vartotojas %pma_s3%';
$strMySQLShowProcess = 'Rodyti procesus';
$strMySQLShowStatus = 'Rodyti MySQL aplinkos būseną';
$strMySQLShowVars = 'Rodyti MySQL sistemos kintamuosius';

$strName = 'Pavadinimas';
$strNext = 'Sekantis';
$strNoDatabases = 'Nėra duomenų bazių';
$strNoDescription = 'Aprašymo nėra';
$strNoDropDatabases = '"DROP DATABASE" komandos įvykdyti negalima.';
$strNoFrames = 'phpMyAdmin draugiškesnis su <b>rėmelius</b> palaikančiomis naršyklėmis.';
$strNoIndex = 'Neaprašyti indeksai!';
$strNoIndexPartsDefined = 'Neaprašytos indekso dalys!';
$strNoModification = 'Nėra pakeitimų';
$strNo = 'Ne';
$strNone = 'Nėra';
$strNoPassword = 'Nėra slaptažodžio';
$strNoPhp = 'be PHP kodo';
$strNoPrivileges = 'Nėra privilegijų';
$strNoQuery = 'Nėra SQL užklausos!';
$strNoRights = 'Neturite pakankamai teisių';
$strNoTablesFound = 'Duomenų bazėje nerasta lentelių.';
$strNotNumber = 'Įveskite skaičių!';
$strNotOK = 'Negerai';
$strNotSet = 'Lentelė <b>%s</b> nerasta arba nenurodyta %s byloje';
$strNotValidNumber = ' netinkamas eilutės numeris!';
$strNoUsersFound = 'Nerasta vartotojo(ų).';
$strNull = 'Null';
$strNumSearchResultsInTable = '%s atitikmuo(enys) lentelėje <i>%s</i>';
$strNumSearchResultsTotal = '<b>Viso:</b> <i>%s</i> atitikmuo(enys)';

$strOftenQuotation = 'Dažnai kartojasi kabutės. Pasirinktinai reiškia, kad tik  char ir varchar laukai yra uždaryti kabutėmis.';
$strOK = 'Gerai';
$strOperations = 'Veiksmai';
$strOptimizeTable = 'Optimizuoti lentelę';
$strOptionalControls = 'Pasirinktinai. Kontroliuojama kaip skaitoma, rašoma specialiuosius simbolius.';
$strOptionally = 'Pasirinktinai';
$strOptions = 'Parinktys';
$strOr = 'Arba';
$strOverhead = 'Perteklinis';

$strPageNumber = 'Puslapis:';
$strPartialText = 'Daliniai tekstai';
$strPasswordEmpty = 'Tuščias slaptažodis!';
$strPasswordNotSame = 'Slaptažodžiai nesutampa!';
$strPassword = 'Slaptažodis';
$strPdfDbSchema = 'Duomenų bazės "%s" schema - %s puslapis';
$strPdfInvalidPageNum = 'Nenurodytas PDF puslapio numeris!';
$strPdfInvalidTblName = 'Lentelė "%s" neegzistuoja!';
$strPdfNoTables = 'No tables';
$strPhp = 'Sukurti PHP kodą';
$strPHPVersion = 'PHP versija';
$strPmaDocumentation = 'phpMyAdmin\'o dokumentacija';
$strPmaUriError = 'Reikia konfigūraciniame faile įrašyti <tt>$cfg[\'PmaAbsoluteUri\']</tt> reikšmę!';
$strPos1 = 'Pradžia';
$strPrevious = 'Praėjęs';
$strPrimaryKeyHasBeenDropped = 'Panaikintas pirminis raktas';
$strPrimaryKeyName = 'Pirminio rakto pavadinimas turi būti "PRIMARY"!';
$strPrimaryKey = 'Pirminis raktas';
$strPrimaryKeyWarning = '("PRIMARY" <b>yra vienintelis</b> pirminio rakto tipas!)';
$strPrimary = 'Pirminis';
$strPrintView = 'Atspausdinti struktūra';
$strPrivileges = 'Privilegijos';
$strProperties = 'Nustatymai';

$strQBEDel = 'Pakeičiant';
$strQBEIns = 'Įterpiant';
$strQBE = 'Užklausa pagal pavyzdį';
$strQueryOnDb = 'SQL-užklausa duomenų bazėje <b>%s</b>:';

$strRecords = 'Viso įrašų:';
$strReferentialIntegrity = 'Patikrinti sąryšių vientisumą:';
$strRelationNotWorking = 'Atsisakyta papildomų nustatymų, leidžiančių dirbti su jungtinėmis lentelėmis. %sPaaiškinimas%s.';
$strRelationView = 'Peržiūrėti sąryšius';
$strReloadFailed = 'MySQL procesą perkrauti nepavyko.';
$strReloadMySQL = 'Perkrauti MySQL procesą';
$strRememberReload = 'Neužmirškite perkrauti serverį.';
$strRenameTableOK = 'Lentelė %s pervadinta į %s';
$strRenameTable = 'Pervadinti lentelę į';
$strRepairTable = 'Taisyti lentelę';
$strReplace = 'Pakeisti';
$strReplaceTable = 'Pakeisti lentelės turinį failo duomenimis ';
$strReset = 'Nustatyti į pradinę būseną';
$strReType = 'Įveskite dar kartą';
$strRevokeGrantMessage = 'Jūs panaikinote GRANT privilegiją %s';
$strRevokeGrant = 'Panaikinti GRANT privilegiją';
$strRevokeMessage = 'Jūs panaikinote privilegijas %s';
$strRevoke = 'Panaikinti';
$strRevokePriv = 'Panaikinti privilegijas';
$strRowLength = 'Eilutės ilgis';
$strRows = 'Eilutės';
$strRowsFrom = 'eilučių pradedant nuo';
$strRowSize = 'Eilutės dydis';
$strRowsModeHorizontal = 'horizontaliai';
$strRowsModeOptions = 'išdėstant  %s pakartoti antraštes kas %s įrašų';
$strRowsModeVertical = 'vertikaliai';
$strRowsStatistic = 'Eilučių statistika';
$strRunning = 'adresu %s';
$strRunQuery = 'Vykdyti užklausą';
$strRunSQLQuery = 'Vykdyti SQL užklausą(as) duomenų bazėje %s';

$strSave = 'Išsaugoti';
$strScaleFactorSmall = 'Dydžio matas yra per mažas norint sutalpinti vaizdą viename lape.';
$strSearchFormTitle = 'Paieška duomenų bazėje';
$strSearchInTables = 'Lentelės(ių) viduje:';
$strSearchNeedle = 'Paieškos žodis(iai) arba reikšmė(ės) (pakaitos simbolis: "%"):';
$strSearchOption1 = 'bent vienas iš žodžių';
$strSearchOption2 = 'visi žodžiai';
$strSearchOption3 = 'ištisa frazė';
$strSearchOption4 = 'kaip reguliarųjį išsireiškimą';
$strSearch = 'Paieška';
$strSearchResultsFor = 'Paieškos rezultatai frazei "<i>%s</i>" %s:';
$strSearchType = 'Rasti:';
$strSelectADb = 'Pasirinkti duomenų bazę';
$strSelectAll = 'Išrinkti visas(us)';
$strSelectFields = 'Pasirinkti laukus (nors vieną)';
$strSelect = 'Išrinkti';
$strSelectNumRows = 'užklausą vykdoma';
$strSelectTables = 'Pasirinkite lenteles';
$strSend = 'Išsaugoti į failą';
$strServerChoice = 'Pasirinkti serverį';
$strServerVersion = 'Serverio versija';
$strSetEnumVal = 'Jeigu laukelio tipas yra "enum" arba "set", tuomet duomenų reikšmes reikia įvesti naudojant šį formatą: \'a\',\'b\',\'c\'...<br />. Jeigu jums reikia įrašyti dešininį įžambųjį brūkšnį ("\") arba kabutes("\'"), tuomet prieš šios simbolius reikia papildomo dešininio įžambaus brūkšnio (pavyzdžiui: \'\\\\xyz\' or \'a\\\'b\').';
$strShowAll = 'Rodyti viską';
$strShowColor = 'Rodyti spalvą';
$strShowCols = 'Rodyti stulpelius';
$strShowGrid = 'Rodyti tinklelį';
$strShowingRecords = 'Rodomi įrašai';
$strShowPHPInfo = 'Rodyti PHP informaciją';
$strShow = 'Rodyti';
$strShowTableDimension = 'Rodyti lentelių dydžius';
$strShowTables = 'Rodyti lentelės';
$strShowThisQuery = ' Rodyti šią užklausą vėl ';
$strSingly = '(pavieniui)';
$strSize = 'Dydis';
$strSort = 'Rūšiuoti';
$strSpaceUsage = 'Vietos naudojimas';
$strSplitWordsWithSpace = 'Žodžiai atskirti tarpo simboliu (" ").';
$strSQLQuery = 'SQL- užklausa';
$strSQLResult = 'SQL rezultatas';
$strSQL = 'SQL';
$strStatement = 'Parametrai';
$strStrucCSV = 'Duomenys CSV formatu';
$strStrucData = 'Struktūra ir duomenys';
$strStrucDrop = 'Panaikinti-įterpti lentelę';
$strStrucExcelCSV = 'Duomenys ekselio CSV formatu';
$strStrucOnly = 'Tik struktūrą';
$strStructPropose = 'Propose table structure';
$strStructure = 'Struktūra';
$strSubmit = 'Siųsti';
$strSuccess = 'Jūsų SQL užklausa sėkmingai įvykdyta';
$strSum = 'Sumos';

$strTableComments = 'Lentelės komentarai';
$strTableEmpty = 'Tuščias lentelės vardas!';
$strTableHasBeenDropped = 'Lentelė %s panaikinta';
$strTableHasBeenEmptied = 'Lentelės reikšmės %s ištuštintos';
$strTableHasBeenFlushed = 'Lentelės buferis  %s išvalytas';
$strTable = 'lentelė ';
$strTableMaintenance = 'Lentelės diagnostika';
$strTables = '%s lentelė(s)';
$strTableStructure = 'Sukurta duomenų struktūra lentelei';
$strTableType = 'Lentelės tipas';
$strTextAreaLength = ' Tai yra jo ilgis,<br /> šis laukelis neredaguojamas ';
$strTheContent = 'Jūsų failo turinys įterptas.';
$strTheContents = 'Failo turinys įterpus panaikina išrinktos lentelės ar stulpelio turinį, bet išlieka unikalūs ir pirminiai indeksai.';
$strTheTerminator = 'Laukų pabaigos žymė.';
$strTotal = ' iš viso ';
$strType = 'Tipas';

$strUncheckAll = 'Nepažymėti visus(as)';
$strUnique = 'Unikalus';
$strUnselectAll = 'Nepažymėti visus(as)';
$strUpdatePrivMessage = 'Jūs pakeitėte privilegijas  %s.';
$strUpdateProfileMessage = 'Profilis papildytas.';
$strUpdateProfile = 'Papildyti profilį:';
$strUpdateQuery = 'Atnaujinti užklausą';
$strUsage = 'Išnaudota';
$strUseBackquotes = 'Lentelių ir laukų vardams naudoti šias kabutes ` `';
$strUserEmpty = 'Tuščias vartotojo vardas!';
$strUserName = 'Vartotojo vardas';
$strUsers = 'Vartotojai';
$strUser = 'Vartotojas';
$strUseTables = 'Naudoti lenteles';

$strValue = 'Reikšmė';
$strViewDumpDB = 'Sukurti, peržiūrėti duomenų bazės atvaizdį';
$strViewDump = 'Peržiūrėti lentelės struktūros atvaizdį';

$strWelcome = 'Sveiki atvykę į %s';
$strWithChecked = 'Pasirinktas lenteles:';
$strWrongUser = 'Neteisingas vartotojo vardas arba slaptažodis. Priėjimas uždraustas.';

$strYes = 'Taip';

$strZip = '"zip"';
//to translate:


$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCharsetOfFile = 'Character set of the file:'; //to translate

$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'Explain SQL';  //to translate

$strNoExplain = 'Skip Explain SQL';  //to translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate

$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate

$strValidateSQL = 'Validate SQL';  //to translate

?>
