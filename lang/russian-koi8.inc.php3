<?php
/* $Id$ */

$charset = 'koi8-r';
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


$strAccessDenied = 'В доступе отказано';
$strAction = 'Действие';
$strAddDeleteColumn = 'Добавить/удалить столбец критерия';
$strAddDeleteRow = 'Добавить/удалить ряд критерия';
$strAddNewField = 'Добавить новое поле';
$strAddPriv = 'Добавить новые привилегии';
$strAddPrivMessage = 'Была добавлена новая привилегия';
$strAddSearchConditions = 'Добавить условия поиска (тело для "where" условия):';
$strAddUser = 'Добавить нового пользователя';
$strAddUserMessage = 'Была добавлен новый пользователь.';
$strAfter = 'После';
$strAll = 'Все';
$strAlterOrderBy = 'Изменить порядок таблицы';
$strAnalyzeTable = 'Анализ таблицы';
$strAnd = 'И';
$strAnIndex = 'Был добавлен индекс для ';
$strAny = 'Любой';
$strAnyColumn = 'Любая колонка';
$strAnyDatabase = 'Любая база данных';
$strAnyHost = 'Любой хост';
$strAnyTable = 'Любая таблица';
$strAnyUser = 'Любой пользователь';
$strAPrimaryKey = 'Был добавлен первичный ключ к ';
$strAscending = 'Восходящий';
$strAtBeginningOfTable = 'В начало таблицы';
$strAtEndOfTable = 'В конец таблицы';
$strAttr = 'Атрибуты';

$strBack = 'Назад';
$strBinary = ' Двоичный ';
$strBinaryDoNotEdit = ' Двоичные данные - не редактируются ';
$strBookmarkLabel = 'Метка';
$strBookmarkQuery = 'Закладка на SQL-запрос';
$strBookmarkThis = 'Закладка на данный SQL-запрос';
$strBookmarkView = 'Только просмотр';
$strBrowse = 'Пролистать';

$strCantLoadMySQL = 'расширение MySQL не загруженно,<br />проверьте конфигурацию PHP.';
$strCarriage = 'Возврат каретки: \\r';
$strChange = 'Изменить';
$strCheckAll = 'Проверить все';
$strCheckDbPriv = 'Проверить Привилегии Базы Данных';
$strCheckTable = 'Проверить таблицу';
$strColumn = 'Колонка';
$strColumnEmpty = 'Название колонки пусто!';
$strColumnNames = 'Названия колонок';
$strCompleteInserts = 'Полная вставка';
$strConfirm = 'Вы действительно хотите сделать это?';
$strCopyTable = 'Скопировать таблицу в';
$strCopyTableOK = 'Таблица %s была скопирована в %s.';
$strCreate = 'Создать';
$strCreateNewDatabase = 'Создать новую БД';
$strCreateNewTable = 'Создать новую таблицу в БД ';
$strCriteria = 'Критерий';

$strData = 'Данные';
$strDatabase = 'БД ';
$strDatabases = 'Базы Данных';
$strDataOnly = 'Только данные';
$strDbEmpty = 'Пустое название базы данных!';
$strDefault = 'По умолчанию';
$strDelete = 'Удалить';
$strDeleted = 'Ряд был удален';
$strDeleteFailed = 'Неудачное удаление!';
$strDeletePassword = 'Увалить пароль';
$strDeleteUserMessage = 'Был удален пользователь';
$strDelPassMessage = 'Был удален пароль для';
$strDescending = 'Нисходящий';
$strDisableMagicQuotes = '<b>Внимане:</b> В конфигурации PHP были разрешены magic_quotes_gpc. Эта версия PhpMyAdmin не поддерживает данную возможность . Посмотрите секцию конфигурации в руководстве по PHP, для информации об отключении данной возможности.';
$strDisplay = 'Показать';
$strDisplayOrder = 'Порядок просмотра:';
$strDoAQuery = 'Выполнить "запрос по примеру" (символ подставновки: "%")';
$strDocu = 'Документация';
$strDoYouReally = 'Вы действительно желаете ';
$strDrop = 'Уничтожить';
$strDropDB = 'Уничтожить БД ';
$strDumpingData = 'Сдампать данные таблицы';
$strDynamic = 'динамически';

$strEdit = 'Редактировать';
$strEditPrivileges = 'Редактирование привилегий';
$strEffective = 'Эффективность';
$strEmpty = 'Пустота';
$strEmptyResultSet = 'MySQL вернула пустой результат (т.е. ноль рядов).';
$strEnableMagicQuotes = '<b>Внимане:</b> В конфигурации PHP не были разрешены magic_quotes_gpc. Для работы PhpMyAdmin необходима данная возможность. Посмотрите секцию конфигурации в руководстве по PHP, для информации об включении данной возможности.';
$strEnclosedBy = 'заключенный в';
$strEnd = 'Конец';
$strEnglishPrivileges = ' Note: Привилегии MySQL необходимо задавать по английски ';
$strError = 'Ошибка';
$strEscapedBy = 'завершаемые через';
$strExtra = 'Дополнительно';

$strField = 'Поле';
$strFields = 'Поля';
$strFieldsEmpty = ' Пустой счетчик полей! ';
$strFixed = 'фиксированно';
$strFormat = 'Формат';
$strFormEmpty = 'Требуется значение для формы!';
$strFunction = 'Функция';

$strGenTime = 'Время Генерации';
$strGo = 'Пошел';
$strGrants = 'Предоставление прав';

$strHasBeenAltered = 'была изменена.';
$strHasBeenCreated = 'была создана.';
$strHasBeenDropped = 'была уничтожена.';
$strHasBeenEmptied = 'была опустошена.';
$strHome = 'К началу';
$strHomepageOfficial = 'Официальная страница phpMyAdmin';
$strHomepageSourceforge = 'Загрузка phpMyAdmin на Sourceforge';
$strHost = 'Хост';
$strHostEmpty = 'Пустое имя хоста!';

$strIfYouWish = 'Если Вы желаете загрузить только некоторые столбцы таблицы, укажите разделенный запятыми список полей.';
$strIndex = 'Индекс';
$strIndexes = 'Индекировать';
$strInsert = 'Вставить';
$strInsertAsNewRow = 'Вставить новый ряд';
$strInsertIntoTable = 'Вставить в таблицу';
$strInsertNewRow = 'Вставить новый ряд';
$strInsertTextfiles = 'Вставить текстовые файлы в таблицу';
$strInUse = 'используется';

$strKeyname = 'Имя ключа';
$strKill = 'Убить';

$strLength = 'Длинна';
$strLimitNumRows = 'записей на страницу';
$strLineFeed = 'Символ окончания линии: \\n';
$strLines = 'Линии';
$strLocationTextfile = 'Месторасположение текстового файла';
$strLogin = ''; // To translate, but its not in use ...
$strLogout = 'Выйти из системы';

$strModifications = 'Модификации были сохранены';
$strModify = 'Изменить';
$strMySQLReloaded = 'MySQL перезагружена.';
$strMySQLSaid = 'MySQL сказала: ';
$strMySQLShowProcess = 'Показать процессы';
$strMySQLShowStatus = 'Показать состояние MySQL';
$strMySQLShowVars = 'Показать системные переменные MySQL';

$strName = 'Имя';
$strNbRecords = 'число записей';
$strNext = 'Следующий';
$strNo = 'Нет';
$strNoPassword = 'Без пароля';
$strNoPrivileges = 'Без привилегий';
$strNoRights = 'Вы не имеете достаточно прав для этого!';
$strNoTablesFound = 'В БД не обнаружено таблиц.';
$strNotNumber = 'Это не число!';
$strNotValidNumber = ' недопустимое количество рядов!';
$strNoUsersFound = 'Не найден пользователь.';
$strNull = 'Ноль';
$strNumberIndexes = ' Количество расширенных индексов ';

$strOftenQuotation = 'Обычно кавычки. ПО ВЫБОРУ означает, что только поля char и varchar заключаются в кавычки.';
$strOptimizeTable = 'Оптимизировать таблицу';
$strOptionalControls = 'По выбору. Контролирует как читать или писать специальные символы.';
$strOptionally = 'ПО ВЫБОРУ';
$strOr = 'Или';
$strOverhead = 'Наверху';

$strPassword = 'Пароль';
$strPasswordEmpty = 'Пустой пароль!';
$strPasswordNotSame = 'Пароли не одинаковы!';
$strPHPVersion = 'Версия PHP';
$strPos1 = 'Начало';
$strPrevious = 'Предыдущий';
$strPrimary = 'Первичный';
$strPrimaryKey = 'Первичный ключ';
$strPrinterFriendly = 'Версия для печати этой таблицы';
$strPrintView = 'Версия для печати';
$strPrivileges = 'Привилегии';
$strProducedAnError = 'привела к ошибке.';
$strProperties = 'Свойства';

$strQBE = 'Запрос по примеру';
$strQBEDel = 'Del';  // To translate (used in tbl_qbe.php)
$strQBEIns = 'Ins';  // To translate (used in tbl_qbe.php)
$strQueryOnDb = 'SQL-запрос БД ';

$strReadTheDocs = 'Почитать документацию';
$strRecords = 'Записи';
$strReloadFailed = 'Не удалось перезагрузить MySQL.';
$strReloadMySQL = 'Перезагрузить MySQL';
$strRememberReload = 'Не забудьте перезагрузить сервер.';
$strRenameTable = 'Переименовать таблицу в';
$strRenameTableOK = 'Таблица %s была переименована в %s';
$strRepairTable = 'Починить таблицу';
$strReplace = 'Заместить';
$strReplaceTable = 'Заместить данные таблицы данными из файла';
$strReset = 'Переустановить';
$strReType = 'Изменение типа';
$strRevoke = 'Отменить';
$strRevokeGrant = 'Отменить предоставление прав';
$strRevokeGrantMessage = 'Было отменено предоставление прав для';
$strRevokeMessage = 'Были отменены привилегии для';
$strRevokePriv = 'Отменить привилегии';
$strRowLength = 'Длина ряда';
$strRows = 'Ряды';
$strRowsFrom = 'ряды начинающиеся с';
$strRowSize = ' Размер ряда ';
$strRowsStatistic = 'Статистика ряда';
$strRunning = 'запущено на ';
$strRunQuery = 'Выполнить Запрос';
$strRunSQLQuery = 'Выполнить SQL запрос(ы) на БД ';

$strSave = 'Сохранить';
$strSelect = 'Выбрать';
$strSelectFields = 'Выбрать поля (минимум одно):';
$strSelectNumRows = 'по запросу';
$strSend = 'послать';
$strSequence = 'Seq.'; // To translate
$strServerVersion = 'Версия сервера';
$strShow = 'Показать';
$strShowingRecords = 'Показывает записи ';
$strShowThisQuery = ' Показать данный запрос снова ';
$strSingly = '(отдельно)';
$strSize = 'Размер';
$strSort = 'Отсортировать';
$strSpaceUsage = 'Используемое пространство';
$strSQLQuery = 'SQL-запрос';
$strStatement = 'Statements'; // To translate
$strStrucCSV = 'CSV данные';
$strStrucData = 'Структура и данные';
$strStrucDrop = 'Добавить \'удалить таблицу\'';
$strStrucOnly = 'Только структуру';
$strSubmit = 'Выполнить';
$strSuccess = 'Ваш SQL-запрос был успешно выполнен';
$strSum = 'Sum'; // To translate

$strTable = 'таблица ';
$strTableComments = 'Коменатии таблицы';
$strTableEmpty = 'Пустое название таблицы!';
$strTableMaintenance = 'Table maintenance'; // To translate
$strTableStructure = 'Структура таблицы для таблицы';
$strTableType = 'Тип таблицы';
$strTerminatedBy = 'Завершается через';
$strTextAreaLength = ' Из-за большой длины,<br /> это поле не может быть отредактированно ';
$strTheContent = 'Содержимое файла было импортировано.';
$strTheContents = 'Содержимое файла замещает содержимое таблицы для рядов с идентичными первичными или уникальными ключами.';
$strTheTerminator = 'Символ окончания полей.';
$strTotal = 'всего';
$strType = 'Тип';

$strUncheckAll = 'Ничего не проверять';
$strUnique = 'Уникальное';
$strUpdatePassMessage = 'Был изменет пароль для';
$strUpdatePassword = 'Изменить пароль';
$strUpdatePrivMessage = 'Были изменены привилегии для';
$strUpdateQuery = 'Дополнить Запрос';
$strUsage = 'Использование';
$strUseBackquotes = 'Обратные кавычки в названиях таблиц и полей';
$strUser = 'Пользователь';
$strUserEmpty = 'Пустое имя пользователя!';
$strUserName = 'Имя пользователя';
$strUsers = 'Пользователи';
$strUseTables = 'Использовать таблицы';

$strValue = 'Значение';
$strViewDump = 'Просмотреть дамп (схему) таблицы';
$strViewDumpDB = 'Просмотреть дамп (схему) БД';

$strWelcome = 'Добро пожаловать в ';
$strWrongUser = 'Ошибочный логин/пароль. В доступе отказано.';

$strYes = 'Да';

// To translate
$strAffectedRows = 'Affected rows:'; // To translate
$strBzip = '"bzipped"'; // To translate
$strDatabasesStats = 'Databases statistics';//to translate
$strDeletedRows = 'Deleted rows:';
$strDropTable = 'Drop table';
$strExtendedInserts = 'Extended inserts';
$strFullText = 'Full Texts';//to translate
$strGzip = '"gzipped"'; // To translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strInsertedRows = 'Inserted rows:';
$strInstructions = 'Instructions';//to translate
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.'; //to translate
$strLengthSet = 'Length/Values*'; // To translate
$strOffSet = 'offset'; // To translate
$strNoDatabases = 'No databases';
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.';
$strNoModification = 'No change'; // To translate
$strPartialText = 'Partial Texts';//to translate
$strPmaDocumentation = 'phpMyAdmin Documentation';//to translate 
$strRunningAs = 'as';
$strServerChoice = 'Server Choice';//to translate 
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').';
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowPHPInfo = 'Show PHP information'; // To translate
$strShowTables = 'Show tables';
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strTables = '%s table(s)';  //to translate
$strWithChecked = 'With checked:';
?>
