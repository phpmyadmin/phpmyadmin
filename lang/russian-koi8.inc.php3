<?php
/* $Id$ */

$charset = 'koi8-r';
$text_dir = 'ltr';
$left_font_family = 'sans-serif';
$right_font_family = 'sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Байт', 'кБ', 'МБ', 'ГБ');

$day_of_week = array('Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб');
$month = array('Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d %Y г., %H:%M';


$strAccessDenied = 'В доступе отказано';
$strAction = 'Действие';
$strAddDeleteColumn = 'Добавить/удалить столбец критерия';
$strAddDeleteRow = 'Добавить/удалить ряд критерия';
$strAddNewField = 'Добавить новое поле';
$strAddPriv = 'Добавить новые привилегии';
$strAddPrivMessage = 'Была добавлена новая привилегия';
$strAddSearchConditions = 'Добавить условия поиска (тело для условия "where"):';
$strAddToIndex = 'Добавить к индексу&nbsp;%s&nbsp;колоноку(и)';
$strAddUser = 'Добавить нового пользователя';
$strAddUserMessage = 'Была добавлен новый пользователь.';
$strAffectedRows = 'Затронутые ряды:';
$strAfter = 'После';
$strAfterInsertBack = 'Возврат';
$strAfterInsertNewInsert = 'Вставить новую запись';
$strAll = 'Все';
$strAlterOrderBy = 'Изменить порядок таблицы';
$strAnalyzeTable = 'Анализ таблицы';
$strAnd = 'И';
$strAnIndex = 'Был добавлен индекс для %s';
$strAny = 'Любой';
$strAnyColumn = 'Любая колонка';
$strAnyDatabase = 'Любая база данных';
$strAnyHost = 'Любой хост';
$strAnyTable = 'Любая таблица';
$strAnyUser = 'Любой пользователь';
$strAPrimaryKey = 'Был добавлен первичный ключ к %s';
$strAscending = 'Восходящий';
$strAtBeginningOfTable = 'В начало таблицы';
$strAtEndOfTable = 'В конец таблицы';
$strAttr = 'Атрибуты';

$strBack = 'Назад';
$strBinary = ' Двоичный ';
$strBinaryDoNotEdit = ' Двоичные данные - не редактируются ';
$strBookmarkDeleted = 'Закладка была удалена.';
$strBookmarkLabel = 'Метка';
$strBookmarkQuery = 'Закладка на SQL-запрос';
$strBookmarkThis = 'Закладка на данный SQL-запрос';
$strBookmarkView = 'Только просмотр';
$strBrowse = 'Обзор';
$strBzip = 'паковать в "bzip"';

$strCantLoadMySQL = 'расширение MySQL не загруженно,<br />проверьте конфигурацию PHP.';
$strCantRenameIdxToPrimary = 'Невозмозможно переименовать индекс в PRIMARY!';
$strCarriage = 'Возврат каретки: \\r';
$strCardinality = 'Количество элементов';
$strChange = 'Изменить';
$strCheckAll = 'Отметить все';
$strCheckDbPriv = 'Проверить Привилегии Базы Данных';
$strCheckTable = 'Проверить таблицу';
$strColumn = 'Колонка';
$strColumnNames = 'Названия колонок';
$strCompleteInserts = 'Полная вставка';
$strConfirm = 'Вы действительно хотите сделать это?';
$strCopyTable = 'Скопировать таблицу в (база данных<b>.</b>таблица):';
$strCopyTableOK = 'Таблица %s была скопирована в %s.';
$strCreate = 'Создать';
$strCreateNewDatabase = 'Создать новую БД';
$strCreateNewTable = 'Создать новую таблицу в БД ';
$strCreateIndex = 'Создать индекс на&nbsp;%s&nbsp;колонках';
$strCreateIndexTopic = 'Создать новый индекс';
$strCriteria = 'Критерий';

$strData = 'Данные';
$strDatabase = 'БД ';
$strDatabaseHasBeenDropped = 'База данных %s была удалена.';
$strDatabases = 'Базы Данных';
$strDatabasesStats = 'Статистика баз данных';
$strDataOnly = 'Только данные';
$strDefault = 'По умолчанию';
$strDelete = 'Удалить';
$strDeleted = 'Ряд был удален';
$strDeletedRows = 'Следующие ряды были удалены:';
$strDeleteFailed = 'Неудачное удаление!';
$strDeleteUserMessage = 'Был удален пользователь %s.';
$strDescending = 'Нисходящий';
$strDisplay = 'Показать';
$strDisplayOrder = 'Порядок просмотра:';
$strDoAQuery = 'Выполнить "запрос по примеру" (символ подставновки: "%")';
$strDocu = 'Документация';
$strDoYouReally = 'Вы действительно желаете ';
$strDrop = 'Уничтожить';
$strDropDB = 'Уничтожить БД ';
$strDropTable = 'Удалить таблицу';
$strDumpingData = 'Дамп данных таблицы';
$strDynamic = 'динамический';

$strEdit = 'Правка';
$strEditPrivileges = 'Редактирование привилегий';
$strEffective = 'Эффективность';
$strEmpty = 'Очистить';
$strEmptyResultSet = 'MySQL вернула пустой результат (т.е. ноль рядов).';
$strEnd = 'Конец';
$strEnglishPrivileges = ' Примечание: привилегии MySQL задаются по английски ';
$strError = 'Ошибка';
$strExtendedInserts = 'Расширенные вставки';
$strExtra = 'Дополнительно';

$strField = 'Поле';
$strFieldHasBeenDropped = 'Поле %s было удалено';
$strFields = 'Поля';
$strFieldsEmpty = ' Пустой счетчик полей! ';
$strFieldsEnclosedBy = 'Поля заключены в';
$strFieldsEscapedBy = 'Поля экранируются';
$strFieldsTerminatedBy = 'Поля разделены';
$strFixed = 'фиксированный';
$strFlushTable = 'Сбросить кэш таблицы ("FLUSH")';
$strFormat = 'Формат';
$strFormEmpty = 'Требуется значение для формы!';
$strFullText = 'Полные тексты';
$strFunction = 'Функция';

$strGenTime = 'Время создания';
$strGo = 'Пошел';
$strGrants = 'Права';
$strGzip = 'паковать в "gzip"';

$strHasBeenAltered = 'была изменена.';
$strHasBeenCreated = 'была создана.';
$strHome = 'К началу';
$strHomepageOfficial = 'Официальная страница phpMyAdmin';
$strHomepageSourceforge = 'Загрузка phpMyAdmin на Sourceforge';
$strHost = 'Хост';
$strHostEmpty = 'Пустое имя хоста!';

$strIdxFulltext = 'ПолнТекст';
$strIfYouWish = 'Если Вы желаете загрузить только некоторые столбцы таблицы, укажите разделенный запятыми список полей.';
$strIgnore = 'Игнорировать';
$strIndex = 'Индекс';
$strIndexes = 'Индексы';
$strIndexHasBeenDropped = 'Индекс %s был удален';
$strIndexName = 'Имя индекса&nbsp;:';
$strIndexType = 'Тип индекса&nbsp;:';
$strInsert = 'Вставить';
$strInsertAsNewRow = 'Вставить новый ряд';
$strInsertedRows = 'Добавленны ряды:';
$strInsertNewRow = 'Вставить новый ряд';
$strInsertTextfiles = 'Вставить текстовые файлы в таблицу';
$strInstructions = 'Инструкции';
$strInUse = 'используется';
$strInvalidName = '"%s" - является зарезервированным словом, вы не можете использовать его в качестви имени базы данных/таблицы/поля.';

$strKeepPass = 'Не менять пароль';
$strKeyname = 'Имя ключа';
$strKill = 'Убить';

$strLength = 'Длинна';
$strLengthSet = 'Длины/Значения*';
$strLimitNumRows = 'записей на страницу';
$strLineFeed = 'Символ окончания линии: \\n';
$strLines = 'Линии';
$strLinesTerminatedBy = 'Строки разделены';
$strLocationTextfile = 'Месторасположение текстового файла';
$strLogin = 'Вход в систему';
$strLogout = 'Выйти из системы';

$strModifications = 'Модификации были сохранены';
$strModify = 'Изменить';
$strModifyIndexTopic = 'Изменить индекс';
$strMoveTable = 'Переместить таблицы в (база данных<b>.</b>таблица):';
$strMoveTableOK = 'Таблица %s была перемещена в %s.';
$strMySQLReloaded = 'MySQL перезагружена.';
$strMySQLSaid = 'Ответ MySQL: ';
$strMySQLServerProcess = 'MySQL %pma_s1% на %pma_s2% как %pma_s3%';
$strMySQLShowProcess = 'Показать процессы';
$strMySQLShowStatus = 'Показать состояние MySQL';
$strMySQLShowVars = 'Показать системные переменные MySQL';

$strName = 'Имя';
$strNbRecords = 'число записей';
$strNext = 'Далее';
$strNo = 'Нет';
$strNoDatabases = 'БД отсутствуют';
$strNoDropDatabases = 'Операторы "DROP DATABASE" отключены.';
$strNoFrames = 'Для работы phpMyAdmin нужен браузер с поддержкой <b>фреймов</b>.';
$strNoIndexPartsDefined = 'Частей индекса не определено!';
$strNoIndex = 'Индекс не определен!';
$strNoModification = 'Нет изменений';
$strNone = 'Нет';
$strNoPassword = 'Без пароля';
$strNoPrivileges = 'Без привилегий';
$strNoQuery = 'Нет SQL-запроса!';
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
$strOverhead = 'Накладные расходы';

$strPartialText = 'Частичные тексты';
$strPassword = 'Пароль';
$strPasswordEmpty = 'Пустой пароль!';
$strPasswordNotSame = 'Пароли не одинаковы!';
$strPHPVersion = 'Версия PHP';
$strPmaDocumentation = 'Документация по phpMyAdmin';
$strPos1 = 'Начало';
$strPrevious = 'Назад';
$strPrimary = 'Первичный';
$strPrimaryKey = 'Первичный ключ';
$strPrimaryKeyName = 'Имя первичного ключа должно быть PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>должно</b> быть именем <b>только</b> первичного ключа!)';
$strPrimaryKeyHasBeenDropped = 'Первичный ключ был удален';
$strPrintView = 'Версия для печати';
$strPrivileges = 'Привилегии';
$strProperties = 'Свойства';

$strQBE = 'Запрос по примеру';
$strQBEDel = 'Удалить';
$strQBEIns = 'Вставить';
$strQueryOnDb = 'SQL-запрос БД <b>%s</b>:';

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
$strReType = 'Подтверждение';
$strRevoke = 'Отменить';
$strRevokeGrant = 'Отменить предоставление прав';
$strRevokeGrantMessage = 'Было отменено предоставление прав для %s';
$strRevokeMessage = 'Вы изменили привелегии для %s';
$strRevokePriv = 'Отменить привилегии';
$strRowLength = 'Длина ряда';
$strRows = 'Ряды';
$strRowsFrom = 'рядов от';
$strRowSize = ' Размер ряда ';
$strRowsStatistic = 'Статистика ряда';
$strRunning = 'на %s';
$strRunQuery = 'Выполнить Запрос';
$strRunSQLQuery = 'Выполнить SQL запрос(ы) на БД %ы';

$strSave = 'Сохранить';
$strSelect = 'Выбрать';
$strSelectFields = 'Выбрать поля (минимум одно):';
$strSelectNumRows = 'по запросу';
$strSend = 'послать';
$strSequence = 'Посл.';
$strServerChoice = 'Выбор сервера';
$strServerVersion = 'Версия сервера';
$strSetEnumVal = 'Для типов поля "enum" и "set", введите значения по этому формату: \'a\',\'b\',\'c\'...<br />Если вам понадобиться ввести обратную косую черту ("\"") или одиночную кавычку ("\'") среди этих значений, поставьте перед ними обратную косую черту (например, \'\\\\xyz\' или \'a\\\'b\').';
$strShow = 'Показать';
$strShowAll = 'Показать все';
$strShowCols = 'Показать колонки';
$strShowingRecords = 'Показывает записи ';
$strShowPHPInfo = 'Показать информацию о PHP';
$strShowTables = 'Показать таблицы';
$strShowThisQuery = ' Показать данный запрос снова ';
$strSingly = '(отдельно)';
$strSize = 'Размер';
$strSort = 'Отсортировать';
$strSpaceUsage = 'Используемое пространство';
$strSQLQuery = 'SQL-запрос';
$strStartingRecord = 'Начинать с записи';
$strStatement = 'Параметр'; // ???To translate
$strStrucCSV = 'CSV данные';
$strStrucData = 'Структура и данные';
$strStrucDrop = 'Добавить удаление таблицы';
$strStrucExcelCSV = 'CSV для данных Ms Excel';
$strStrucOnly = 'Только структуру';
$strSubmit = 'Выполнить';
$strSuccess = 'Ваш SQL-запрос был успешно выполнен';
$strSum = 'Всего';

$strTable = 'таблица ';
$strTableComments = 'Комментарий к таблице';
$strTableEmpty = 'Пустое название таблицы!';
$strTableHasBeenDropped = 'Таблица %s была удалена';
$strTableHasBeenEmptied = 'Таблица %s была опустошена';
$strTableHasBeenFlushed = 'Был сброшен кэш таблицы %s';
$strTableMaintenance = 'Обслуживание таблицы';
$strTables = '%s таблиц(ы)';
$strTableStructure = 'Структура таблицы';
$strTableType = 'Тип таблицы';
$strTextAreaLength = ' Из-за большой длины,<br /> это поле не может быть отредактированно ';
$strTheContent = 'Содержимое файла было импортировано.';
$strTheContents = 'Содержимое файла замещает содержимое таблицы для рядов с идентичными первичными или уникальными ключами.';
$strTheTerminator = 'Символ окончания полей.';
$strTotal = 'всего';
$strType = 'Тип';

$strUncheckAll = 'Снять отметку со всех';
$strUnique = 'Уникальное';
$strUpdatePrivMessage = 'Были изменены привилегии для';
$strUpdateProfile = 'Обновить профиль:';
$strUpdateProfileMessage = 'Профиль был обновлен.';
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

$strWelcome = 'Добро пожаловать в %s';
$strWithChecked = 'С отмеченными:';
$strWrongUser = 'Ошибочный логин/пароль. В доступе отказано.';

$strYes = 'Да';

$strZip = 'упаковать в "zip"';

// To translate
$strCookiesRequired = 'Cookies must be enabled past this point.';
$strLogPassword = 'Password:';
$strLogUsername = 'Username:';
$strRowsModeVertical=" vertical ";  //to translate
$strRowsModeHorizontal=" horizontal ";  //to translate
$strRowsModeOptions=" in %s mode and repeat headers after %s cells ";  //to translate
$strSelectAll = 'Select All';  //to translate
$strUnselectAll = 'Unselect All';  //to translate
?>
