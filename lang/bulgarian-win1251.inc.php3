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
$byteUnits = array('байта', 'KB', 'MB', 'GB');

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
$strNbRecords = 'Брой записи';
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

$strOffSet = 'Отместване';
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
$strPmaUriError = 'На <tt>$cfgPmaAbsoluteUri</tt> ТРЯБВА да се зададе стойност в конфигурационния файл!';
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
$strStartingRecord = 'Начален запис';
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
?>
