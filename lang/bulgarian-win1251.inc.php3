<?php
/* $Id$ */

$charset = 'windows-1251';
$left_font_family = 'sans-serif';
$right_font_family = 'sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Отказан достъп';
$strAction = 'Действие';
$strAddDeleteColumn = 'Добави/изтрий колона по критерий';
$strAddDeleteRow = 'Добави/изтрий ред по критерий';
$strAddNewField = 'Добави ново поле';
$strAddPriv = 'Добавяне на нова привилегия';
$strAddPrivMessage = 'Вие добавихте нова привилегия.';
$strAddSearchConditions = 'Добави условие за търсене (тяло за "where" условие):';
$strAddUser = 'Добавяне на нов потребител.';
$strAddUserMessage = 'Вие добавихте нов потребител.';
$strAffectedRows = 'Повлияни редове:';
$strAfter = 'След';
$strAll = 'All'; //to translate
$strAlterOrderBy = 'Alter table order by'; //to translate
$strAnalyzeTable = 'Анализиране на таблицата';
$strAnd = 'И';
$strAny = 'Всеки';
$strAnyColumn = 'Всяка колона';
$strAnyDatabase = 'Всяка база данни';
$strAnyHost = 'Всеки хост';
$strAnyTable = 'Всяка таблица';
$strAnyUser = 'Всеки потребител';
$strAPrimaryKey = 'Бeшe добавен първичeн ключ към ';
$strAscending = 'Възходящ';
$strAtBeginningOfTable = 'От началото на таблицата';
$strAtEndOfTable = 'От края на таблицата';
$strAttr = 'Атрибути';

$strBack = 'Назад';
$strBinary = ' Двоично ';
$strBinaryDoNotEdit = ' Двоично - не редактирай ';
$strBookmarkLabel = 'Етикет';
$strBookmarkQuery = 'Bookmarked SQL-query'; //to translate
$strBookmarkThis = 'Bookmark this SQL-query'; //to translate
$strBookmarkView = 'Само преглеждане';
$strBrowse = 'Прелисти';
$strBzip = '"формат bzip"';

$strCantLoadMySQL = 'не мога да заредя MySQL разширенията,<br />моля проверете конфигурацията на PHP.';
$strCarriage = 'Символ CR: \\r';
$strChange = 'Промени';
$strCheckAll = 'Маркирай всичко';
$strCheckDbPriv = 'Check Database Privileges'; //to translate
$strCheckTable = 'Проверка на таблицата';
$strColumn = 'Колона';
$strColumnEmpty = 'Имената на колоните са празни!';
$strColumnNames = 'Име на колона';
$strCompleteInserts = 'Complete inserts'; //to translate
$strConfirm = 'Действително ли желаете да го направите?';
$strCopyTableOK = 'Таблица %s беше копирана в %s.';
$strCreate = 'Създай';
$strCreateNewDatabase = 'Създай нова БД';
$strCreateNewTable = 'Създай нова таблица в БД ';
$strCriteria = 'Критерий';

$strData = 'Данни';
$strDatabase = 'БД ';
$strDatabases = 'База Данни';
$strDatabasesStats = 'Статистика за базите от данни';
$strDataOnly = 'Само данни';
$strDbEmpty = 'Името на базата данни е празно!';
$strDefault = 'По подразбиране';
$strDelete = 'Изтрий';
$strDeleted = 'Редът беше изтрит';
$strDeletedRows = 'Изтрити редове:';
$strDeleteFailed = 'Неуспешно изтриване!';
$strDescending = 'Низходящ';
$strDisplay = 'Покажи';
$strDisplayOrder = 'Покажи подредба:';
$strDoAQuery = 'Изпълни "запитване по заявка" (символ за  заместване: "%")';
$strDocu = 'Документация';
$strDoYouReally = 'Действително ли желаете ';
$strDrop = 'Унищожи';
$strDropDB = 'Унищожи БД ';
$strDropTable = 'Изтрии таблицата';
$strDumpingData = 'Дъмп (схема) на данните в таблицата';
$strDynamic = 'динамично';

$strEdit = 'Редактиране';
$strEditPrivileges = 'Редактиране на привилегиите';
$strEffective = 'Effective'; //to translate
$strEmpty = 'Изпразни';
$strEmptyResultSet = 'MySQL върна празен резултат (т.е. нула редове).';
$strEnd = 'Край';
$strError = 'Грешка';
$strExtra = 'Допълнително';

$strField = 'Поле';
$strFields = 'Полета';
$strFixed = 'fixed'; //to translate
$strFormat = 'Формат';
$strFormEmpty = 'Липсва стойност в формата !';
$strFunction = 'Функция';

$strGenTime = 'Време на генериране';
$strGo = 'Изпълни';
$strGrants = 'Grants'; //to translate
$strGzip = '"формат gzip"';

$strHasBeenAltered = 'беше променена.';
$strHasBeenCreated = 'беше създадена.';
$strHome = 'Начало';
$strHomepageOfficial = 'Официална phpMyAdmin уеб страница';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Page'; //to translate
$strHost = 'Хост';
$strHostEmpty = 'Името на хоста е празно!';

$strIfYouWish = 'Ако желаете да заредите само някои колони от таблицата, укажете списък на полетата разделени със запетаи.';
$strIndex = 'Индекс';
$strIndexes = 'Индекси';
$strInsert = 'Вмъкни';
$strInsertAsNewRow = 'Вмъкни като нов ред';
$strInsertedRows = 'Вмъкнати реда:';
$strInsertIntoTable = 'Вмъкни в таблицата';
$strInsertNewRow = 'Вмъкни нов ред';
$strInsertTextfiles = 'Вмъкни текстови файлове в таблицата';
$strInstructions = 'Инструкции';
$strInUse = 'заето';

$strKeyname = 'Име на ключа';
$strKill = 'Kill'; //to translate

$strLength = 'Дължина';
$strLengthSet = 'Дължина/Стойност*';
$strLimitNumRows = 'реда на страница';
$strLineFeed = 'Символ за край на ред: \\n';
$strLines = 'Редове';
$strLocationTextfile = 'Местоположение на текстовия файл';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Изход от системата';

$strModifications = 'Промените бяха съхранени';
$strModify = 'Измени';
$strMySQLReloaded = 'MySQL е презареден.';
$strMySQLSaid = 'MySQL отговори: ';
$strMySQLShowProcess = 'Покажи процесите';
$strMySQLShowStatus = 'Покажи състоянието на MySQL';
$strMySQLShowVars = 'Покажи системните променливи на MySQL';

$strName = 'Име';
$strNext = 'Следващ';
$strNo = 'Не';
$strNoDatabases = 'Няма бази от данни';
$strNoDropDatabases = '"DROP DATABASE" зявката е забранена.';
$strNoModification = 'Няма промяна';
$strNoPassword = 'Няма парола';
$strNoPrivileges = 'Няма привилегии';
$strNoRights = 'You don\'t have enough rights to be here right now!'; //to translate
$strNoTablesFound = 'В БД няма таблици.';
$strNotNumber = 'Това не е число!';
$strNotValidNumber = ' не е валиден номер на ред!';
$strNoUsersFound = 'Няма потребител(и).';
$strNull = 'Нула';
$strNumberIndexes = ' Number of advanced indexes '; //to translate

$strOffSet = 'отместване';
$strOftenQuotation = 'Обикновено кавички. ПО ИЗБОР означава, че само полета char и varchar се заграждат в кавички.';
$strOptimizeTable = 'Оптимизиране на таблицата';
$strOptionalControls = 'По избор. Контролира как да се четат или пишат специални символи.';
$strOptionally = 'ПО ИЗБОР';
$strOr = 'Или';
$strOverhead = 'Overhead'; //to translate

$strPassword = 'Парола';
$strPasswordEmpty = 'Паролата е празна!';
$strPasswordNotSame = 'Паролата не е същата!';
$strPHPVersion = 'Версия на PHP ';
$strPmaDocumentation = 'phpMyAdmin документация';
$strPos1 = 'Начало';
$strPrevious = 'Предишен';
$strPrimary = 'Първичен';
$strPrimaryKey = 'Първичен ключ';
$strPrinterFriendly = 'Printer friendly version of above table'; //to translate
$strPrintView = 'Изглед за печат';
$strPrivileges = 'Привилегии';
$strProducedAnError = 'доведе до грешка.';
$strProperties = 'Свойства';

$strQBE = 'Запитване по пример';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)
$strQueryOnDb = 'SQL-заявка към базата от данни ';

$strReadTheDocs = 'Прочети документацията';
$strRecords = 'Записи';
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
$strRevoke = 'Отмяни';
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for'; //to translate
$strRevokeMessage = 'Вие отменихте привилегиите за';
$strRevokePriv = 'Отмяна на привилегии';
$strRowLength = 'Дължина на реда';
$strRows = 'Rows'; //to translate
$strRowsFrom = 'реда започвайки от';
$strRowSize = ' Размер на ред ';
$strRowsStatistic = 'Статистика за реда';
$strRunning = 'е стартиран на ';
$strRunQuery = 'Изпълни Запитването';

$strSave = 'Запиши';
$strSelect = 'Избери';
$strSelectFields = 'Избери поле (минимум едно):';
$strSelectNumRows = 'в запитването';
$strSend = 'изпрати';
$strSequence = 'Seq.'; //to translate
$strServerChoice = 'Избор на сървър';
$strServerVersion = 'Версия на сървъра';
$strSetEnumVal = 'Ако типа на полето е "enum" или "set", моля въведете стойностите използвайки този формат: \'a\',\'b\',\'c\'...<br />Ако е необходимо да сложите обратна черта ("\") или апостроф ("\'") между тези стойности, сложите обратна черта пред тях (например:  \'\\\\xyz\' или \'a\\\'b\').';
$strShow = 'Покажи';
$strShowingRecords = 'Показва записи ';
$strShowPHPInfo = 'Покажи информация за PHP ';
$strShowThisQuery = ' Покажи тази заявка отново ';
$strSingly = '(singly)'; //to translate
$strSize = 'Размер';
$strSort = 'Сортирай';
$strSpaceUsage = 'Използвано място';
$strSQLQuery = 'SQL-запитване';
$strStatement = 'Statements'; //to translate
$strStrucCSV = 'CSV данни';
$strStrucData = 'Структура и данни';
$strStrucDrop = 'Добави \'изтрий таблицата\'';
$strStrucExcelCSV = 'CSV за Ms Excel данни';
$strStrucOnly = 'Само структурата';
$strSubmit = 'Изпълни';
$strSuccess = 'Вашето SQL-запитване беше изпълнено успешно';
$strSum = 'Сума';

$strTable = 'Таблица ';
$strTableComments = 'Коментари към таблицата';
$strTableEmpty = 'Името на таблицата е празно!';
$strTableMaintenance = 'Поддръжка на таблицата';
$strTables = '%s таблица(и)';
$strTableStructure = 'Структура на таблица';
$strTableType = 'Тип на таблицата';
$strTextAreaLength = ' Поради дължинат си,<br /> това поле може да не е редактируемо ';
$strTheContent = 'Съдържанието на файла беше импортирано.';
$strTheContents = 'Съдържанието на файла замества съдържанието на таблицата за редове с идентични първични или уникални ключове.';
$strTheTerminator = 'Символ за край на поле.';
$strTotal = 'всичко';
$strType = 'Тип';

$strUncheckAll = 'Размаркирай всичко';
$strUnique = 'Уникално';
$strUpdateQuery = 'Допълни Запитването';
$strUsage = 'Usage'; //to translate
$strUser = 'Потребител';
$strUserEmpty = 'Потребителското име е празно!';
$strUserName = 'Потребителско име';
$strUsers = 'Потребители';
$strUseTables = 'Използвай таблицата';

$strValue = 'Стойност';
$strViewDump = 'Покажи дъмп (схема) на таблицата';
$strViewDumpDB = 'Покажи дъмп (схема) на БД';

$strWelcome = 'Добре дошли в ';
$strWrongUser = 'Грешно име/парола. Отказан достъп.';

$strYes = 'Да';

$strZip = '"формат zip"';

// To translate
$strAffectedRows = 'Affected rows:';
$strAnIndex = 'An index has been added on %s';//to translate
$strCopyTable = 'Copy table to (database<b>.</b>table):';
$strDatabaseHasBeenDropped = 'Database %s has been dropped.';  //to translate
$strDeleteUserMessage = 'You have deleted the user %s.';//to translate
$strEnglishPrivileges = ' Note: MySQL privilege names are expressed in English ';  //to translate
$strExtendedInserts = 'Extended inserts';
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strFieldsEmpty = ' The field count is empty! ';  //to translate
$strFieldsEnclosedBy = 'Fields enclosed by';//to translate
$strFieldsEscapedBy = 'Fields escaped by';//to translate
$strFieldsTerminatedBy = 'Fields terminated by';//to translate
$strFullText = 'Full Texts';//to translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strIndexHasBeenDropped = 'Index %s has been dropped';//to translate
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.'; //to translate
$strKeepPass = 'Do not change the password';//to translate
$strLinesTerminatedBy = 'Lines terminated by';//to translate
$strMoveTable = 'Move table to (database<b>.</b>table):';
$strMoveTableOK = 'Table %s has been moved to %s.';
$strNbRecords = 'no. of records';
$strNoQuery = 'No SQL query!';  //to translate
$strPartialText = 'Partial Texts';//to translate
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
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strWithChecked = 'With checked:';
?>
