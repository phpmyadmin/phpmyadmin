<?php
/* $Id$ */
// Last translation by: Taiyen Hung 洪泰元<yen789@pchome.com.tw>

$charset = 'big5';
$text_dir = 'ltr';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = '拒絕存取';
$strAction = '執行';
$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate (tbl_qbe.php3)
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate (tbl_qbe.php3)
$strAddNewField = '增加新欄位';
$strAddPriv = '增加新權限';
$strAddPrivMessage = 'You have added a new privilege.'; //您已經為下面這位使用者增加了新權限(this variable can't show Chinese correctly)
$strAddSearchConditions = '增加檢索條件 ("where" 子句的主體)';
$strAddUser = '新增使用者';
$strAddUserMessage = 'You have added a new user.';//您已新增了一個新使用者(this variable can't show Chinese correctly)
$strAfter = 'After'; //should expressed in English
$strAll = '全部';
$strAlterOrderBy = 'Alter table order by';
$strAnalyzeTable = '分析資料表';
$strAnd = 'And'; //to translate (tbl_qbe.php3)
$strAny = '任何';
$strAnyColumn = '任何欄位';
$strAnyDatabase = '任何資料庫';
$strAnyHost = '任何主機';
$strAnyTable = '任何資料表';
$strAnyUser = '任何使用者';
$strAscending = 'Ascending'; //to translate (tbl_qbe.php3)
$strAtBeginningOfTable = '於資料表開頭';
$strAtEndOfTable = '於資料表尾端';
$strAttr = '屬性';

$strBack = '回上一頁';
$strBinary = 'Binary'; //should expressed in English
$strBinaryDoNotEdit = 'Binary - 不能編輯';
$strBookmarkLabel = '書籤名稱';
$strBookmarkQuery = 'SQL 語法書籤';
$strBookmarkThis = '將此 SQL 語法加入書籤';
$strBookmarkView = '查看';
$strBrowse = '瀏覽';
$strBzip = '"bzipped"';

$strCantLoadMySQL = '不能載入 MySQL extension,<br />請檢查 PHP 的組態設定';
$strCarriage = '歸位: \\r';
$strChange = '修改';
$strCheckAll = '全選';
$strCheckDbPriv = '檢查資料庫權限';
$strCheckTable = '檢查資料表';
$strColumn = '欄位';
$strColumnNames = '欄位名稱';
$strCompleteInserts = 'Complete inserts'; 
$strConfirm = '您確定要這樣做?';
$strCopyTableOK = '已經將資料表 %s 複製為 %s.'; 
$strCreate = '建立';
$strCreateNewDatabase = '建立新資料庫';
$strCreateNewTable = '建立新資料表於資料庫 ';
$strCriteria = 'Criteria'; //to translate (tbl_qbe.php3)

$strData = '資料';
$strDatabase = '資料庫';
$strDatabases = '資料庫';
$strDataOnly = '只有資料';
$strDefault = '預設值';
$strDelete = '刪除';
$strDeleted = '記錄已被刪除';
$strDeleteFailed = '刪除失敗!';
$strDescending = 'Desending'; //to translate (tbl_qbe.php3)
$strDisplay = '顯示';
$strDoAQuery = '以範例查詢 (萬用字元 : "%")';
$strDocu = '說明文件';
$strDoYouReally = '您確定要 ';
$strDrop = '刪除';
$strDropDB = '刪除資料庫';
$strDumpingData = 'Dumping data for table'; //should expressed in English
$strDynamic = '動態';

$strEdit = '編輯';
$strEditPrivileges = '編輯權限';
$strEffective = '實際';
$strEmpty = '清空';
$strEmptyResultSet = 'MySQL 傳回的查詢結果為空 (原因可能為：沒有找到符合條件的記錄)';
$strEnd = '最後一頁';
$strEnglishPrivileges = '注意: MySQL 權限名稱會被解釋成英文';
$strError = '錯誤';
$strExtra = '附加';

$strField = '欄位';
$strFields = '欄位';
$strFixed = '固定';
$strFormat = '格式';
$strFunction = '函數';

$strGenTime = '建立日期';
$strGo = 'Go';
$strGrants = 'Grants'; //should expressed in English
$strGzip = '"gzipped"';

$strHasBeenAltered = '已經修改';
$strHasBeenCreated = '已經建立';
$strHome = '主目錄';
$strHomepageOfficial = 'phpMyAdmin 官方網站';
$strHomepageSourceforge = 'phpMyAdmin 下載網頁';
$strHost = '主機';
$strHostEmpty = '請輸入主機名稱!';

$strIfYouWish = '如果您要指定資料匯入的欄位，請輸入用逗號隔開的欄位名稱';
$strIndex = '索引';
$strIndexes = '索引';
$strInsert = '新增';
$strInsertAsNewRow = '儲存為新記錄';
$strInsertNewRow = '新增一筆記錄';
$strInsertTextfiles = '將文字檔資料匯入資料表';
$strInUse = '使用中';

$strKeyname = '鍵名';
$strKill = 'Kill'; //系統指令不翻

$strLength = '長度';
$strLengthSet = '長度/集合*';
$strLimitNumRows = '筆記錄/每頁';
$strLineFeed = '換行: \\n';
$strLines = '行數';
$strLocationTextfile = '文字檔案的位置';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = '登出系統';

$strModifications = '修改已儲存';
$strModify = 'Modify'; //to translate (tbl_qbe.php3)
$strMySQLReloaded = 'MySQL 重新載入完成';
$strMySQLSaid = 'MySQL 傳回： ';
$strMySQLShowProcess = '顯示程序 (Process)';
$strMySQLShowStatus = '顯示 MySQL 執行狀態';
$strMySQLShowVars = '顯示 MySQL 系統變數';

$strName = '名稱';
$strNbRecords = '筆開始；列出記錄筆數';
$strNext = 'Next';
$strNo = ' 否 ';
$strNoPassword = '不用密碼';
$strNoPrivileges = '沒有權限';
$strNoRights = '您現在沒有足夠的權限在這裡!';
$strNoTablesFound = '資料庫中沒有資料表';
$strNotNumber = '請輸入數字!';
$strNotValidNumber = '不是有效的列數!';
$strNoUsersFound = '沒有找到使用者';
$strNull = 'Null'; //should expressed in English
$strNumberIndexes = ' Number of advanced indexes '; //should expressed in English

$strOftenQuotation = '最常用的是引號，「非必須」表示只有 char 和 varchar 欄位會被包括起來';
$strOptimizeTable = '最佳化資料表';
$strOptionalControls = '非必要選項，用來讀寫特殊字元';
$strOptionally = '非必須';
$strOr = '或';
$strOverhead = 'Overhead';

$strPassword = '密碼';
$strPasswordEmpty = '請輸入密碼!';
$strPasswordNotSame = '二次輸入的密碼不同!';
$strPHPVersion = 'PHP版本';
$strPos1 = '第一頁';
$strPrevious = '前一頁';
$strPrimary = '主鍵';
$strPrimaryKey = '主鍵';
$strPrintView = 'Print view';
$strPrivileges = '權限';
$strProperties = '屬性';

$strQBE = '依範例查詢 (QBE)';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)

$strRecords = '記錄';
$strReloadFailed = '重新載入MySQL失敗';
$strReloadMySQL = '重新載入 MySQL';
$strRememberReload = 'Remember reload the server.'; //記得重載主機(this variable can't show Chinese correctly)
$strRenameTable = '將資料表改名為';
$strRenameTableOK = '已經將資料表 %s 改名成 %s';
$strRepairTable = '修復資料表';
$strReplace = '取代';
$strReplaceTable = '以檔案取代資料表資料';
$strReset = '重置';
$strReType = '確認密碼';
$strRevoke = '移除';
$strRevokeGrant = '移除 Grant 權限';
$strRevokeGrantMessage = '您已移除下面這位使用者的 Grant 權限: %s';
$strRevokeMessage = '您已移除下面這位使用者的權限: %s';
$strRevokePriv = '移除權限';
$strRowLength = '資料列長度';
$strRows = '資料列列數';
$strRowsFrom = '筆記錄，開始列數:';
$strRowSize = '資料列大小';
$strRowsStatistic = '資料列統計數值';
$strRunning = '執行於 %s';
$strRunQuery = 'Submit Query'; //to translate (tbl_qbe.php3)

$strSave = '儲存';
$strSelect = '選擇';
$strSelectFields = '選擇欄位 (至少一個)';
$strSelectNumRows = '查詢中';
$strSend = '發送';
$strSequence = '序列';
$strServerVersion = '資料庫版本';
$strShow = '顯示';
$strShowingRecords = '顯示記錄';
$strShowThisQuery = '在這裡重新顯示語法 ';
$strSingly = '(singly)';
$strSize = '大小';
$strSort = 'Sort'; //to translate (tbl_qbe.php3)
$strSpaceUsage = '已使用空間';
$strSQLQuery = 'SQL 語法';
$strStatement = '敘述';
$strStrucCSV = 'CSV 資料';
$strStrucData = '結構與資料';
$strStrucDrop = '增加 \'drop table\'';
$strStrucOnly = '只有結構';
$strSubmit = '送出';
$strSuccess = '您的SQL語法已順利執行';
$strSum = '總計';

$strTable = '資料表';
$strTableComments = '資料表註解文字';
$strTableEmpty = '請輸入資料表名稱!';
$strTableMaintenance = '資料表維護';
$strTableStructure = 'Table structure for table'; //should expressed in English
$strTableType = '資料表類型';
$strTextAreaLength = ' 由於長度限制<br /> 此欄位不能編輯';
$strTheContent = '檔案內容已經匯入資料表';
$strTheContents = '檔案內容將會取代選定的資料表中具有相同主鍵或唯一鍵的記錄';
$strTheTerminator = '分隔欄位的字元';
$strTotal = '總計';
$strType = '型態';

$strUncheckAll = '全部取消';
$strUnique = '唯一';
$strUpdateQuery = 'Update Query'; //to translate (tbl_qbe.php3)
$strUsage = '使用';
$strUser = '使用者';
$strUserEmpty = '請輸入使用者名稱!';
$strUserName = '使用者名稱';
$strUsers = '使用者';
$strUseTables = 'Use Tables'; //to translate (tbl_qbe.php3)

$strValue = '值';
$strViewDump = '檢視資料表的備份概要 (dump schema)';
$strViewDumpDB = '檢視資料庫的備份概要 (dump schema)';

$strWelcome = '歡迎使用 %s';
$strWrongUser = '錯誤的使用者名稱或密碼   拒絕存取';

$strYes = ' 是 ';

// To translate
$strAffectedRows = ' Rows affected: ';  //to translate
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Insert a new record';
$strAnIndex = 'An index has been added on %s';//to translate
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strBookmarkDeleted = 'The bookmark has been deleted.';
$strCopyTable = 'Copy table to (database<b>.</b>table):';
$strDatabaseHasBeenDropped = 'Database %s has been dropped.';  //to translate
$strDatabasesStats = 'Databases statistics';//to translate
$strDeletedRows = 'Deleted rows:';
$strDeleteUserMessage = 'You have deleted the user %s.';//to translate
$strDisplayOrder = 'Display order:';
$strDropTable = 'Drop table';
$strExtendedInserts = 'Extended inserts';
$strFieldsEmpty = ' The field count is empty! ';  //to translate
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strFieldsEnclosedBy = 'Fields enclosed by';//to translate
$strFieldsEscapedBy = 'Fields escaped by';//to translate
$strFieldsTerminatedBy = 'Fields terminated by';//to translate
$strFlushTable = 'Flush the table ("FLUSH")';
$strFormEmpty = 'Missing value in the form !';
$strFullText = 'Full Texts';//to translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strIndexHasBeenDropped = 'Index %s has been dropped';//to translate
$strInsertedRows = 'Inserted rows:';
$strInstructions = 'Instructions';//to translate
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.'; //to translate
$strKeepPass = 'Do not change the password';//to translate
$strLinesTerminatedBy = 'Lines terminated by';//to translate
$strMoveTable = 'Move table to (database<b>.</b>table):';
$strMoveTableOK = 'Table %s has been moved to %s.';
$strMySQLServerProcess = 'MySQL %pma_s1% running on %pma_s2% as %pma_s3%';
$strNoDatabases = 'No databases';
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strNoModification = 'No change';
$strNoQuery = 'No SQL query!';  //to translate
$strPartialText = 'Partial Texts';//to translate
$strPmaDocumentation = 'phpMyAdmin Documentation';//to translate 
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strQueryOnDb = ' SQL-query on database ';  //to translate
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strServerChoice = 'Server Choice';//to translate 
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').';
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowPHPInfo = 'Show PHP information';  // To translate
$strShowTables = 'Show tables';
$strStartingRecord = 'Starting record';//to translate
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strTableHasBeenDropped = 'Table %s has been dropped';//to translate
$strTableHasBeenEmptied = 'Table %s has been emptied';//to translate
$strTableHasBeenFlushed = 'Table %s has been flushed';
$strTables = '%s table(s)';  //to translate
$strUpdatePrivMessage = 'You have updated the privileges for %s.';//to translate
$strUpdateProfile = 'Update profile:';//to translate
$strUpdateProfileMessage = 'The profile has been updated.';//to translate
$strUseBackquotes = ' Use backquotes with tables and fields names ';  //to translate
$strWithChecked = 'With selected:';
$strZip = '"zipped"';  //to translate

// Indexes
$strAddToIndex = 'Add to index &nbsp;%s&nbsp;column(s)';
$strCantRenameIdxToPrimary = 'Can\'t rename index to PRIMARY!';
$strCardinality = 'Cardinality';
$strCreateIndex = 'Create an index on&nbsp;%s&nbsp;columns';
$strCreateIndexTopic = 'Create a new index';
$strIgnore = 'Ignore';
$strIndexName = 'Index name&nbsp;:';
$strIndexType = 'Index type&nbsp;:';
$strModifyIndexTopic = 'Modify an index';
$strNone = 'None';
$strNoIndexPartsDefined = 'No index parts defined!';
$strNoIndex = 'No index defined!';
$strPrimaryKeyName = 'The name of the primary key must be... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>must</b> be the name of and <b>only of</b> a primary key!)';
?>
