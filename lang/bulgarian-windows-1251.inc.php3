<?php
/* $Id$ */

/**
 * Translated by Georgi Georgiev <chutz at chubaka.homeip.net>
 */

$charset = 'windows-1251';
$text_dir = 'ltr';
$left_font_family = 'sans-serif';
$right_font_family = 'sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('байта', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('нед', 'пон', 'вт', 'ср', 'чет', 'пет', 'съб');
$month = array('януари', 'февруари', 'март', 'април', 'май', 'юни', 'юли', 'август', 'септември', 'окомври', 'ноември', 'декември');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%e %B %Y в %H:%M';

$strAccessDenied = 'Отказан достъп';
$strAction = 'Действие';
$strAddDeleteColumn = 'Добави/изтрий колона по критерий';
$strAddDeleteRow = 'Добави/изтрий ред по критерий';
$strAddNewField = 'Добави ново поле';
$strAddPriv = 'Добавяне на нова привилегия';
$strAddPrivMessage = 'Вие добавихте нова привилегия.';
$strAddSearchConditions = 'Добави условие за търсене (тяло за "where" условие):';
$strAddToIndex = ' &nbsp;%s&nbsp;колона(и) бяха добавени към индекса ';
$strAddUser = 'Добавяне на нов потребител.';
$strAddUserMessage = 'Вие добавихте нов потребител.';
$strAffectedRows = 'Засегнати реда:';
$strAfter = 'След %s';
$strAfterInsertBack = 'се върни';
$strAfterInsertNewInsert = 'вмъкни нов запис';
$strAll = 'всички';
$strAlterOrderBy = 'Подреди таблицата по';
$strAnalyzeTable = 'Анализиране на таблицата';
$strAnd = 'и';
$strAnIndex = 'Беше добавен индекс на %s';
$strAny = 'всеки';
$strAnyColumn = 'Всяка колона';
$strAnyDatabase = 'Всяка база данни';
$strAnyHost = 'Всеки хост';
$strAnyTable = 'Всяка таблица';
$strAnyUser = 'Всеки потребител';
$strAPrimaryKey = 'Бeшe добавен главен ключ към ';
$strAscending = 'Възходящо';
$strAtBeginningOfTable = 'от началото на таблицата';
$strAtEndOfTable = 'от края на таблицата';
$strAttr = 'Атрибути';

$strBack = 'Назад';
$strBinary = ' Двоично ';
$strBinaryDoNotEdit = ' Двоично - не се редактира ';
$strBookmarkDeleted = 'Bookmark беше изтрит.';
$strBookmarkLabel = 'Етикет';
$strBookmarkQuery = 'Запазваме на SQL-запитване';
$strBookmarkThis = 'Запази това SQL-запитване';
$strBookmarkView = 'Само показване';
$strBrowse = 'Прелисти';
$strBzip = '"bzip-нато"';

$strCantLoadMySQL = 'Не мога да заредя MySQL разширенията,<br />моля проверете конфигурацията на PHP.';
$strCantRenameIdxToPrimary = 'Не мога да преименувам индекса на PRIMARY!';
$strCardinality = 'надеждност';
$strCarriage = 'Символ за край на ред: \\r';
$strChange = 'Промени';
$strChangePassword = 'Смяна на паролата';
$strCheckAll = 'Маркирай всичко';
$strCheckDbPriv = 'Провери привилегиите на БД';
$strCheckTable = 'Проверка на таблицата';
$strColumn = 'Колона';
$strColumnNames = 'Име на колона';
$strCompleteInserts = 'Пълни INSERT-и';
$strConfirm = 'Действително ли желаете да го направите?';
$strCookiesRequired = 'Оттук нататък са необходими "Cookies".';
$strCopyTable = 'Копиране на таблица (база от данни<b>.</b>таблица):';
$strCopyTableOK = 'Таблица %s беше копирана в %s.';
$strCreate = 'Създай';
$strCreateIndex = 'Създай индекс върху &nbsp;%s&nbsp;колони';
$strCreateIndexTopic = 'Създай нов индекс';
$strCreateNewDatabase = 'Създай нова БД';
$strCreateNewTable = 'Създай нова таблица в БД %s';
$strCriteria = 'Критерий';

$strData = 'Данни';
$strDatabase = ' БД ';
$strDatabaseHasBeenDropped = 'Базата данни %s беше изтрита.';
$strDatabases = 'Бази от Данни';
$strDatabasesStats = ' Статистика за базите данни';
$strDatabaseWildcard = 'База данни (може и с wildcard):';
$strDataOnly = 'Само данни';
$strDefault = 'По подразбиране';
$strDelete = 'Изтрий';
$strDeleted = 'Редът беше изтрит';
$strDeletedRows = 'Изтрити редове:';
$strDeleteFailed = 'Неуспешно изтриване!';
$strDeleteUserMessage = 'Вие изтрихте потребител %s.';
$strDescending = 'Низходящо';
$strDisplay = 'Покажи';
$strDisplayOrder = 'Покажи подредба:';
$strDoAQuery = 'Изпълни "запитване по заявка" (символ за  заместване: "%")';
$strDocu = 'Документация';
$strDoYouReally = 'Действително ли желаете да';
$strDrop = 'Унищожи';
$strDropDB = 'Унищожи БД %s';
$strDropTable = 'Изтрий таблицата';
$strDumpingData = 'Дъмп (схема) на данните в таблицата';
$strDynamic = 'динамичен';

$strEdit = 'Редактиране';
$strEditPrivileges = 'Редактиране на привилегиите';
$strEffective = 'Ефективни';
$strEmpty = 'Изпразни';
$strEmptyResultSet = 'MySQL върна празен резултат (т.е. нула редове).';
$strEnd = 'Край';
$strEnglishPrivileges = ' Забележка: Имената на привилегиите на MySQL са показани на английски. ';
$strError = 'Грешка';
$strExtendedInserts = 'Разширени INSERT-и';
$strExtra = 'Допълнително';

$strField = 'Поле';
$strFieldHasBeenDropped = 'Полето %s беше изтрито';
$strFields = 'Полета';
$strFieldsEmpty = ' Брояча на полетата е празен! ';
$strFieldsEnclosedBy = 'Полетата са оградени със';
$strFieldsEscapedBy = 'Представка пред специалните символи';
$strFieldsTerminatedBy = 'Полетата завършват със';
$strFixed = 'Фиксиран';
$strFlushTable = 'Изпразни кеша на таблицата ("FLUSH")';
$strFormat = 'Формат';
$strFormEmpty = 'Липсва стойност във формата!';
$strFullText = 'Пълни текстове';
$strFunction = 'Функция';

$strGenTime = 'Време на генериране';
$strGo = 'Изпълни';
$strGrants = 'Grant&nbsp;прив.';
$strGzip = '"gzip-нато"';

$strHasBeenAltered = 'беше променена.';
$strHasBeenCreated = 'беше създадена.';
$strHome = 'Начало';
$strHomepageOfficial = 'Официална phpMyAdmin уеб страница';
$strHomepageSourceforge = 'phpMyAdmin страница на Sourceforge';
$strHost = 'Хост';
$strHostEmpty = 'Името на хоста е празно!';

$strIdxFulltext = 'Пълнотекстово';
$strIfYouWish = 'Ако желаете да заредите само някои колони от таблицата, укажете списък на полетата разделени със запетаи.';
$strIgnore = 'Игнорирай';
$strIndex = 'Индекс';
$strIndexes = 'Индекси';
$strIndexHasBeenDropped = 'Индекса %s беше изтрит';
$strIndexName = 'Име на индекса&nbsp;:';
$strIndexType = 'Тип на индекса&nbsp;:';
$strInsert = 'Вмъкни';
$strInsertAsNewRow = 'Вмъкни като нов ред';
$strInsertedRows = 'Вмъкнати реда:';
$strInsertNewRow = 'Вмъкни нов ред';
$strInsertTextfiles = 'Вмъкни текстови файлове в таблицата';
$strInstructions = 'Инструкции';
$strInUse = 'Заето';
$strInvalidName = '"%s" е запазана дума и вие не можете да я използвате за име на база от данни,таблица или поле. ';

$strKeepPass = 'Да не се сменя паролата';
$strKeyname = 'Име на ключа';
$strKill = 'СТОП';

$strLength = 'Дължина';
$strLengthSet = 'Дължина/Стойност*';
$strLimitNumRows = 'реда на страница';
$strLineFeed = 'Символ за край на ред: \\n';
$strLines = 'Редове';
$strLinesTerminatedBy = 'Линиите завършват с';
$strLocationTextfile = 'Местоположение на текстовия файл';
$strLogin = 'Вход';
$strLogout = 'Изход от системата';
$strLogPassword = 'Парола:';
$strLogUsername = 'Име:';

$strModifications = 'Промените бяха съхранени';
$strModify = 'Промени';
$strModifyIndexTopic = 'Промяна на индекс';
$strMoveTable = 'Преместване на таблица към (база от данни<b>.</b>таблица):';
$strMoveTableOK = 'Таблицата %s беше преместена към %s.';
$strMySQLReloaded = 'MySQL е презареден.';
$strMySQLSaid = 'MySQL отговори: ';
$strMySQLServerProcess = 'MySQL %pma_s1% е стартиран на %pma_s2% като %pma_s3%';
$strMySQLShowProcess = 'Покажи процесите';
$strMySQLShowStatus = 'Покажи състоянието на MySQL';
$strMySQLShowVars = 'Покажи системните променливи на MySQL';

$strName = 'Име';
$strNext = 'Следващ';
$strNo = 'не';
$strNoDatabases = 'Няма бази от данни';
$strNoDropDatabases = '"DROP DATABASE" зявката е забранена.';
$strNoFrames = 'phpMyAdmin е по дружелюбен ако използвате браузър, който поддържа <b>frames</b>.';
$strNoIndex = 'Не е дефиниран индекс!';
$strNoIndexPartsDefined = 'Не са дефинирани части на индекс!';
$strNoModification = 'Няма промяна';
$strNone = 'Нищо';
$strNoPassword = 'Няма парола';
$strNoPrivileges = 'Няма привилегии';
$strNoQuery = 'Няма SQL заявка!';
$strNoRights = 'В момента не разполагате с достатъчно права за да се намирате тук!';
$strNoTablesFound = 'В базата данни няма таблици.';
$strNotNumber = 'Това не е число!';
$strNotValidNumber = ' не е валиден номер на ред!';
$strNoUsersFound = 'Няма потребител(и).';
$strNull = 'Празно';

$strOftenQuotation = 'Обикновено кавички. ПО ИЗБОР означава, че само полета char и varchar се заграждат в кавички.';
$strOptimizeTable = 'Оптимизиране на таблицата';
$strOptionalControls = 'По избор. Контролира как да се четат или пишат специални символи.';
$strOptionally = 'ПО ИЗБОР';
$strOr = 'или';
$strOverhead = 'Загубено място';

$strPartialText = 'Частични текстове';
$strPassword = 'Парола';
$strPasswordEmpty = 'Паролата е празна!';
$strPasswordNotSame = 'Паролата не е същата!';
$strPHPVersion = 'Версия на PHP ';
$strPmaDocumentation = 'phpMyAdmin документация';
$strPmaUriError = 'На <tt>$cfg[\'PmaAbsoluteUri\']</tt> ТРЯБВА да се зададе стойност в конфигурационния файл!';
$strPos1 = 'Начало';
$strPrevious = 'Предишен';
$strPrimary = 'PRIMARY';
$strPrimaryKey = 'Главен ключ';
$strPrimaryKeyHasBeenDropped = ' Главния ключ беше изтрит.';
$strPrimaryKeyName = 'Името на главния ключ трябва да е... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>трябва</b> да е името на <b>и единствено на</b> главния ключ!)';
$strPrintView = 'Изглед за печат';
$strPrivileges = 'Привилегии';
$strProperties = 'Свойства';

$strQBE = 'Запитване по пример';
$strQBEDel = 'Изтрий';
$strQBEIns = 'Вмъкни';
$strQueryOnDb = 'SQL-заявка към базата от данни <b>%s</b>:';

$strRecords = 'Записи';
$strReferentialIntegrity = 'Проверка на интегритета на връзките';
$strReloadFailed = 'Неуспешен опит за презареждане на MySQL.';
$strReloadMySQL = 'Презареди MySQL';
$strRememberReload = 'Не забравяйте да презаредите сървъра.';
$strRenameTable = 'Преименувай таблицата на';
$strRenameTableOK = 'Таблица %s беше преименувана на %s';
$strRepairTable = 'Поправяне на таблицата';
$strReplace = 'Замести';
$strReplaceTable = 'Замести данните от таблицата с данните от файла';
$strReset = 'Изчисти';
$strReType = 'Отново';
$strRevoke = 'Отмени';
$strRevokeGrant = 'Отнемане на Grant&nbsp;прив.';
$strRevokeGrantMessage = 'Вие премахнахте Grant привилегиите за %s';
$strRevokeMessage = 'Вие отменихте привилегиите за %s';
$strRevokePriv = 'Отмяна на привилегии';
$strRowLength = 'Дължина на реда';
$strRows = 'Редове';
$strRowsFrom = 'реда започвайки от';
$strRowSize = ' Размер на ред ';
$strRowsModeHorizontal = 'хоризонтален';
$strRowsModeOptions = 'в %s вид и повтаряй имената на колоните през всеки %s<br />';
$strRowsModeVertical = 'вертикален';
$strRowsStatistic = 'Статистика за редовете';
$strRunning = 'работи на %s';
$strRunQuery = 'Изпълни заявката';
$strRunSQLQuery = 'Стартиране SQL заявка/заявки към база от данни %s';

$strSave = 'Запиши';
$strSelect = 'Избери';
$strSelectADb = 'Моля изберете база данни';
$strSelectAll = 'Селектирай всичко';
$strSelectFields = 'Избери поле (минимум едно):';
$strSelectNumRows = 'в запитването';
$strSend = 'Изпрати';
$strServerChoice = 'Избор на сървър';
$strServerVersion = 'Версия на сървъра';
$strSetEnumVal = 'Ако типа на полето е "enum" или "set", моля въведете стойностите използвайки този формат: \'a\',\'b\',\'c\'...<br />Ако е необходимо да сложите обратна черта ("\") или апостроф ("\'") между тези стойности, сложите обратна черта пред тях (например:  \'\\\\xyz\' или \'a\\\'b\').';
$strShow = 'Покажи';
$strShowAll = 'Покажи всички';
$strShowCols = 'Покажи колоните';
$strShowingRecords = 'Показва записи ';
$strShowPHPInfo = 'Покажи информация за PHP ';
$strShowTables = 'Покажи таблиците';
$strShowThisQuery = ' Покажи тази заявка отново ';
$strSingly = '(еднократно)';
$strSize = 'Размер';
$strSort = 'Сортиране';
$strSpaceUsage = 'Използвано място';
$strSQLQuery = 'SQL-запитване';
$strStatement = 'Заявление';
$strStrucCSV = 'CSV данни';
$strStrucData = 'Структурата и данните';
$strStrucDrop = 'Добави \'изтрий таблицата\'';
$strStrucExcelCSV = 'CSV за Ms Excel данни';
$strStrucOnly = 'Само структурата';
$strSubmit = 'Изпълни';
$strSuccess = 'Вашето SQL-запитване беше изпълнено успешно';
$strSum = 'Сума';

$strTable = 'Таблица ';
$strTableComments = 'Коментари към таблицата';
$strTableEmpty = 'Името на таблицата е празно!';
$strTableHasBeenDropped = 'Таблицата %s беше изтрита';
$strTableHasBeenEmptied = 'Таблицата %s беше изпразнена';
$strTableHasBeenFlushed = 'Кеша на таблица %s беше изпразнен';
$strTableMaintenance = 'Поддръжка на таблицата';
$strTables = '%s таблица(и)';
$strTableStructure = 'Структура на таблица';
$strTableType = 'Тип на таблицата';
$strTextAreaLength = ' Поради дължината си,<br /> това поле може да не е редактируемо ';
$strTheContent = 'Съдържанието на файла беше импортирано.';
$strTheContents = 'Съдържанието на файла замества съдържанието на таблицата за редове с идентични първични или уникални ключове.';
$strTheTerminator = 'Символ за край на поле.';
$strTotal = 'Всичко';
$strType = 'Тип';

$strUncheckAll = 'Размаркирай всичко';
$strUnique = 'Уникално';
$strUnselectAll = 'Деселектирай всичко';
$strUpdatePrivMessage = 'Вие променихте привилегиите за %s.';
$strUpdateProfile = 'Обновяване на профил:';
$strUpdateProfileMessage = 'Профила беше обновен.';
$strUpdateQuery = 'Допълни Запитването';
$strUsage = 'Използвани';
$strUseBackquotes = 'Използвай обратни кавички около имена на таблици и полета';
$strUser = 'Потребител';
$strUserEmpty = 'Потребителското име е празно!';
$strUserName = 'Потребителско име';
$strUsers = 'Потребители';
$strUseTables = 'Използвай таблицата';

$strValue = 'Стойност';
$strViewDump = 'Покажи дъмп (схема) на таблицата';
$strViewDumpDB = 'Покажи дъмп (схема) на БД';

$strWelcome = 'Добре дошли в %s';
$strWithChecked = 'Когато има отметка:';
$strWrongUser = 'Грешно име/парола. Отказан достъп.';

$strYes = 'да';

$strZip = '"zip-нато"';
// To translate

$strAllTableSameWidth = 'display all Tables with same width?';  //to translate

$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCantLoadRecodeIconv = 'Can not load iconv or recode extension needed for charset conversion, configure php to allow using these extensions or disable charset conversion in phpMyAdmin.';  //to translate
$strCantUseRecodeIconv = 'Can not use iconv nor libiconv nor recode_string function while extension reports to be loaded. Check your php configuration.';  //to translate
$strChangeDisplay = 'Choose Field to display';  //to translate
$strCharsetOfFile = 'Character set of the file:'; //to translate
$strChoosePage = 'Please choose a Page to edit';  //to translate
$strColComFeat = 'Displaying Column Comments';  //to translate
$strComments = 'Comments';  //to translate
$strConfigFileError = 'phpMyAdmin was unable to read your configuration file!<br />This might happen if php finds a parse error in it or php cannot find the file.<br />Please call the configuration file directly using the link below and read the php error message(s) that you recieve. In most cases a quote or a semicolon is missing somewhere.<br />If you recieve a blank page, everything is fine.'; //to translate
$strConfigureTableCoord = 'Please configure the coordinates for table %s';  //to translate
$strCreatePage = 'Create a new Page';  //to translate
$strCreatePdfFeat = 'Creation of PDFs';  //to translate

$strDisabled = 'Disabled';  //to translate
$strDisplayFeat = 'Display Features';  //to translate
$strDisplayPDF = 'Display PDF schema';  //to translate
$strDumpXRows = 'Dump %s rows starting at row %s.'; //to translate

$strEditPDFPages = 'Edit PDF Pages';  //to translate
$strEnabled = 'Enabled';  //to translate
$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'Explain SQL';  //to translate
$strExport = 'Export';  //to translate
$strExportToXML = 'Export to XML format'; //to translate

$strGenBy = 'Generated by'; //to translate
$strGeneralRelationFeat = 'General relation features';  //to translate

$strHaveToShow = 'You have to choose at least one Column to display';  //to translate

$strLinkNotFound = 'Link not found';  //to translate
$strLinksTo = 'Links to';  //to translate

$strMissingBracket = 'Missing Bracket';  //to translate
$strMySQLCharset = 'MySQL Charset';  //to translate

$strNoDescription = 'no Description';  //to translate
$strNoExplain = 'Skip Explain SQL';  //to translate
$strNoPhp = 'without PHP Code';  //to translate
$strNotOK = 'not OK';  //to translate
$strNotSet = '<b>%s</b> table not found or not set in %s';  //to translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate
$strNumSearchResultsInTable = '%s match(es) inside table <i>%s</i>';//to translate
$strNumSearchResultsTotal = '<b>Total:</b> <i>%s</i> match(es)';//to translate

$strOK = 'OK';  //to translate
$strOperations = 'Operations';  //to translate
$strOptions = 'Options';  //to translate

$strPageNumber = 'Page number:';  //to translate
$strPdfDbSchema = 'Schema of the the "%s" database - Page %s';  //to translate
$strPdfInvalidPageNum = 'Undefined PDF page number!';  //to translate
$strPdfInvalidTblName = 'The "%s" table does not exist!';  //to translate
$strPdfNoTables = 'No tables';  //to translate
$strPhp = 'Create PHP Code';  //to translate

$strRelationNotWorking = 'The additional Features for working with linked Tables have been deactivated. To find out why click %shere%s.';  //to translate
$strRelationView = 'Relation view';  //to translate

$strScaleFactorSmall = 'The scale factor is too small to fit the schema on one page';  //to translate
$strSearch = 'Search';//to translate
$strSearchFormTitle = 'Search in database';//to translate
$strSearchInTables = 'Inside table(s):';//to translate
$strSearchNeedle = 'Word(s) or value(s) to search for (wildcard: "%"):';//to translate
$strSearchOption1 = 'at least one of the words';//to translate
$strSearchOption2 = 'all words';//to translate
$strSearchOption3 = 'the exact phrase';//to translate
$strSearchOption4 = 'as regular expression';//to translate
$strSearchResultsFor = 'Search results for "<i>%s</i>" %s:';//to translate
$strSearchType = 'Find:';//to translate
$strSelectTables = 'Select Tables';  //to translate
$strShowColor = 'Show color';  //to translate
$strShowGrid = 'Show grid';  //to translate
$strShowTableDimension = 'Show dimension of tables';  //to translate
$strSplitWordsWithSpace = 'Words are seperated by a space character (" ").';//to translate
$strSQL = 'SQL'; //to translate
$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQLResult = 'SQL result'; //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate
$strStructPropose = 'Propose table structure';  //to translate
$strStructure = 'Structure';  //to translate

$strValidateSQL = 'Validate SQL';  //to translate

$strInsecureMySQL = 'Your configuration file contains settings (root with no password) that correspond to the default MySQL privileged account. Your MySQL server is running with this default, is open to intrusion, and you really should fix this security hole.';  //to translate
$strWebServerUploadDirectory = 'web-server upload directory';  //to translate
$strWebServerUploadDirectoryError = 'The directory you set for upload work cannot be reached';  //to translate
$strValidatorError = 'The SQL validator could not be initialized. Please check if you have installed the necessary php extensions as described in the %sdocumentation%s.'; //to translate
$strServer = 'Server %s';  //to translate
$strPutColNames = 'Put fields names at first row';  //to translate
$strImportDocSQL = 'Import docSQL Files';  //to translate
$strDataDict = 'Data Dictionary';  //to translate
$strPrint = 'Print';  //to translate
$strPHP40203 = 'You are using PHP 4.2.3, which has a serious bug with multi-byte strings (mbstring). See PHP bug report 19404. This version of PHP is not recommended for use with phpMyAdmin.';  //to translate
$strCompression = 'Compression'; //to translate
$strNumTables = 'Tables'; //to translate
$strTotalUC = 'Total'; //to translate
$strRelationalSchema = 'Relational schema';  //to translate
$strTableOfContents = 'Table of contents';  //to translate
$strCannotLogin = 'Cannot login to MySQL server';  //to translate
$strShowDatadictAs = 'Data Dictionary Format';  //to translate
$strLandscape = 'Landscape';  //to translate
$strPortrait = 'Portrait';  //to translate

$timespanfmt = '%s days, %s hours, %s minutes and %s seconds'; //to translate

$strAbortedClients = 'Aborted'; //to translate
$strConnections = 'Connections'; //to translate
$strFailedAttempts = 'Failed attempts'; //to translate
$strGlobalValue = 'Global value'; //to translate
$strMoreStatusVars = 'More status variables'; //to translate
$strPerHour = 'per hour'; //to translate
$strQueryStatistics = '<b>Query statistics</b>: Since its startup, %s queries have been sent to the server.';
$strQueryType = 'Query type'; //to translate
$strReceived = 'Received'; //to translate
$strSent = 'Sent'; //to translate
$strServerStatus = 'Runtime Information'; //to translate
$strServerStatusUptime = 'This MySQL server has been running for %s. It started up on %s.'; //to translate
$strServerTabVariables = 'Variables'; //to translate
$strServerTabProcesslist = 'Processes'; //to translate
$strServerTrafficNotes = '<b>Server traffic</b>: These tables show the network traffic statistics of this MySQL server since its startup.';
$strServerVars = 'Server variables and settings'; //to translate
$strSessionValue = 'Session value'; //to translate
$strTraffic = 'Traffic'; //to translate
$strVar = 'Variable'; //to translate

$strCommand = 'Command'; //to translate
$strCouldNotKill = 'phpMyAdmin was unable to kill thread %s. It probably has already been closed.'; //to translate
$strId = 'ID'; //to translate
$strProcesslist = 'Process list'; //to translate
$strStatus = 'Status'; //to translate
$strTime = 'Time'; //to translate
$strThreadSuccessfullyKilled = 'Thread %s was successfully killed.'; //to translate

$strBzError = 'phpMyAdmin was unable to compress the dump because of a broken Bz2 extension in this php version. It is strongly recommended to set the <code>$cfg[\'BZipDump\']</code> directive in your phpMyAdmin configuration file to <code>FALSE</code>. If you want to use the Bz2 compression features, you should upgrade to a later php version. See php bug report %s for details.'; //to translate
$strLaTeX = 'LaTeX';  //to translate

$strAdministration = 'Administration'; //to translate
$strFlushPrivilegesNote = 'Note: phpMyAdmin gets the users\' privileges directly from MySQL\'s privilege tables. The content of this tables may differ from the privileges the server uses if manual changes have made to it. In this case, you should %sreload the privileges%s before you continue.'; //to translate
$strGlobalPrivileges = 'Global privileges'; //to translate
$strGrantOption = 'Grant'; //to translate
$strPrivDescAllPrivileges = 'Includes all privileges except GRANT.'; //to translate
$strPrivDescAlter = 'Allows altering the structure of existing tables.'; //to translate
$strPrivDescCreateDb = 'Allows creating new databases and tables.'; //to translate
$strPrivDescCreateTbl = 'Allows creating new tables.'; //to translate
$strPrivDescCreateTmpTable = 'Allows creating temporary tables.'; //to translate
$strPrivDescDelete = 'Allows deleting data.'; //to translate
$strPrivDescDropDb = 'Allows dropping databases and tables.'; //to translate
$strPrivDescDropTbl = 'Allows dropping tables.'; //to translate
$strPrivDescExecute = 'Allows running stored procedures; Has no effect in this MySQL version.'; //to translate
$strPrivDescFile = 'Allows importing data from and exporting data into files.'; //to translate
$strPrivDescGrant = 'Allows adding users and privileges without reloading the privilege tables.'; //to translate
$strPrivDescIndex = 'Allows creating and dropping indexes.'; //to translate
$strPrivDescInsert = 'Allows inserting and replacing data.'; //to translate
$strPrivDescLockTables = 'Allows locking tables for the current thread.'; //to translate
$strPrivDescMaxConnections = 'Limits the number of new connections the user may open per hour.';
$strPrivDescMaxQuestions = 'Limits the number of queries the user may send to the server per hour.';
$strPrivDescMaxUpdates = 'Limits the number of commands that change any table or database the user may execute per hour.';
$strPrivDescProcess3 = 'Allows killing processes of other users.'; //to translate
$strPrivDescProcess4 = 'Allows viewing the complete queries in the process list.'; //to translate
$strPrivDescReferences = 'Has no effect in this MySQL version.'; //to translate
$strPrivDescReplClient = 'Gives the right to the user to ask where the slaves / masters are.'; //to translate
$strPrivDescReplSlave = 'Needed for the replication slaves.'; //to translate
$strPrivDescReload = 'Allows reloading server settings and flushing the server\'s caches.'; //to translate
$strPrivDescSelect = 'Allows reading data.'; //to translate
$strPrivDescShowDb = 'Gives access to the complete list of databases.'; //to translate
$strPrivDescShutdown = 'Allows shutting down the server.'; //to translate
$strPrivDescSuper = 'Allows connectiong, even if maximum number of connections is reached; Required for most administrative operations like setting global variables or killing threads of other users.'; //to translate
$strPrivDescUpdate = 'Allows changing data.'; //to translate
$strPrivDescUsage = 'No privileges.'; //to translate
$strPrivilegesReloaded = 'The privileges were reloaded successfully.'; //to translate
$strResourceLimits = 'Resource limits'; //to translate
$strUserOverview = 'User overview'; //to translate
$strZeroRemovesTheLimit = 'Note: Setting these options to 0 (zero) removes the limit.'; //to translate

$strPasswordChanged = 'The Password for %s was changed successfully.'; // to translate

$strDeleteAndFlush = 'Delete the users and reload the privileges afterwards.'; //to translate
$strDeleteAndFlushDescr = 'This is the cleanest way, but reloading the privileges may take a while.'; //to translate
$strDeleting = 'Deleting %s'; //to translate
$strJustDelete = 'Just delete the users from the privilege tables.'; //to translate
$strJustDeleteDescr = 'The &quot;deleted&quot; users will still be able to access the server as usual until the privileges are reloaded.'; //to translate
$strReloadingThePrivileges = 'Reloading the privileges'; //to translate
$strRemoveSelectedUsers = 'Remove selected users'; //to translate
$strRevokeAndDelete = 'Revoke all active privileges from the users and delete them afterwards.'; //to translate
$strRevokeAndDeleteDescr = 'The users will still have the USAGE privilege until the privileges are reloaded.'; //to translate
$strUsersDeleted = 'The selected users have been deleted successfully.'; //to translate
$strOriginalInterface = 'original interface';  //to translate

$strAddPrivilegesOnDb = 'Add privileges on the following database'; //to translate
$strAddPrivilegesOnTbl = 'Add privileges on the following table'; //to translate
$strColumnPrivileges = 'Column-specific privileges'; //to translate
$strDbPrivileges = 'Database-specific privileges'; //to translate
$strLocalhost = 'Local';
$strLoginInformation = 'Login Information'; //to translate
$strTblPrivileges = 'Table-specific privileges'; //to translate
$strThisHost = 'This Host'; //to translate
$strUserNotFound = 'The selected user was not found in the privilege table.'; //to translate
$strUserAlreadyExists = 'The user %s already exists!'; //to translate
$strUseTextField = 'Use text field'; //to translate

$strNoUsersSelected = 'No users selected.'; //to translate
$strDropUsersDb = 'Drop the databases that have the same names as the users.'; //to translate
$strAddedColumnComment = 'Added comment for column';  //to translate
$strWritingCommentNotPossible = 'Writing of comment not possible';  //to translate
$strAddedColumnRelation = 'Added relation for column';  //to translate
$strWritingRelationNotPossible = 'Writing of relation not possible';  //to translate
$strImportFinished = 'Import finished';  //to translate
$strFileCouldNotBeRead = 'File could not be read';  //to translate
$strIgnoringFile = 'Ignoring file %s';  //to translate
$strThisNotDirectory = 'This was not a directory';  //to translate
$strAbsolutePathToDocSqlDir = 'Please enter the absolute path on webserver to docSQL directory';  //to translate
$strImportFiles = 'Import files';  //to translate
?>
