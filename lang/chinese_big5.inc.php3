<?php
/* $Id$ */
// Last translation by: siusun <siusun@best-view.net>
// Follow by the original translation of Taiyen Hung 洪泰元<yen789@pchome.com.tw>

$charset = 'big5';
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
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
$strAddDeleteColumn = '新增/減少 選擇欄';
$strAddDeleteRow = '新增/減少 篩選列';
$strAddNewField = '增加新欄位';
$strAddPriv = '增加新權限';
$strAddPrivMessage = '您已經為下面這位使用者增加了新權限.';
$strAddSearchConditions = '增加檢索條件 ("where" 子句的主體)';
$strAddToIndex = '新增 &nbsp;%s&nbsp; 組索引欄';
$strAddUser = '新增使用者';
$strAddUserMessage = '您已新增了一個新使用者.';
$strAffectedRows = '影響列數: ';
$strAfter = '在 %s 之後';
$strAfterInsertBack = '返回';
$strAfterInsertNewInsert = '新增一筆記錄';
$strAll = '全部';
$strAlterOrderBy = '根據欄位內容排序記錄：';
$strAnalyzeTable = '分析資料表';
$strAnd = '與';
$strAnIndex = '索引已經新增到 %s';
$strAny = '任何';
$strAnyColumn = '任何欄位';
$strAnyDatabase = '任何資料庫';
$strAnyHost = '任何主機';
$strAnyTable = '任何資料表';
$strAnyUser = '任何使用者';
$strAPrimaryKey = '主鍵已經新增到 %s';
$strAscending = '遞增';
$strAtBeginningOfTable = '於資料表開頭';
$strAtEndOfTable = '於資料表尾端';
$strAttr = '屬性';

$strBack = '回上一頁';
$strBinary = 'Binary'; //should expressed in English
$strBinaryDoNotEdit = 'Binary - 不能編輯';
$strBookmarkDeleted = '書簽已經刪除.';
$strBookmarkLabel = '書籤名稱';
$strBookmarkQuery = 'SQL 語法書籤';
$strBookmarkThis = '將此 SQL 語法加入書籤';
$strBookmarkView = '查看';
$strBrowse = '瀏覽';
$strBzip = '"bzipped"';

$strCantLoadMySQL = '不能載入 MySQL 模組,<br />請檢查 PHP 的組態設定';
$strCantRenameIdxToPrimary = '無法將索引更名為 PRIMARY!';
$strCardinality = '組別';
$strCarriage = '歸位: \\r';
$strChange = '修改';
$strCheckAll = '全選';
$strCheckDbPriv = '檢查資料庫權限';
$strCheckTable = '檢查資料表';
$strColumn = '欄位';
$strColumnNames = '欄位名稱';
$strCompleteInserts = '使用完整新增指令';
$strConfirm = '您確定要這樣做？';
$strCookiesRequired = 'Cookies 必須啟動才能登入.';
$strCopyTable = '複製資料表到： (格式為 資料庫名稱<b>.</b>資料表名稱):';
$strCopyTableOK = '已經將資料表 %s 複製為 %s.';
$strCreate = '建立';
$strCreateIndex = '新增 &nbsp;%s&nbsp; 組索引欄';
$strCreateIndexTopic = '新增一組索引';
$strCreateNewDatabase = '建立新資料庫';
$strCreateNewTable = '建立新資料表於資料庫 ';
$strCriteria = '篩選';

$strData = '資料';
$strDatabase = '資料庫';
$strDatabaseHasBeenDropped = '資料庫 %s 已被刪除';
$strDatabases = '資料庫';
$strDatabasesStats = '資料庫統計';
$strDataOnly = '只有資料';
$strDefault = '預設值';
$strDelete = '刪除';
$strDeleted = '記錄已被刪除';
$strDeletedRows = '已刪除欄數:';
$strDeleteFailed = '刪除失敗!';
$strDeleteUserMessage = '您已經將用戶 %s 刪除.';
$strDescending = '遞增';
$strDisplay = '顯示';
$strDisplayOrder = '顯示次序:';
$strDoAQuery = '以範例查詢 (萬用字元 : "%")';
$strDocu = '說明文件';
$strDoYouReally = '您確定要 ';
$strDrop = '刪除';
$strDropDB = '刪除資料庫';
$strDropTable = '刪除資料表';
$strDumpingData = '列出以下資料庫的數據：';
$strDynamic = '動態';

$strEdit = '編輯';
$strEditPrivileges = '編輯權限';
$strEffective = '實際';
$strEmpty = '清空';
$strEmptyResultSet = 'MySQL 傳回的查詢結果為空 (原因可能為：沒有找到符合條件的記錄)';
$strEnd = '最後一頁';
$strEnglishPrivileges = '注意: MySQL 權限名稱會被解釋成英文';
$strError = '錯誤';
$strExtendedInserts = '伸延新增模式';
$strExtra = '附加';

$strField = '欄位';
$strFieldHasBeenDropped = '資料表 %s 已被刪除';
$strFields = '欄位';
$strFieldsEmpty = ' 欄位是空的! ';
$strFieldsEnclosedBy = '「欄位」使用字元：';
$strFieldsEscapedBy = '「ESCAPE」使用字元：';
$strFieldsTerminatedBy = '「欄位分隔」使用字元：';
$strFixed = '固定';
$strFlushTable = '強迫關閉資料表 ("FLUSH")';
$strFormat = '格式';
$strFormEmpty = '表格內漏填一些資料 !';
$strFullText = '顯示完整文字';
$strFunction = '函數';

$strGenTime = '建立日期';
$strGo = '執行';
$strGrants = 'Grants'; //should expressed in English
$strGzip = '"gzipped"';

$strHasBeenAltered = '已經修改';
$strHasBeenCreated = '已經建立';
$strHome = '主目錄';
$strHomepageOfficial = 'phpMyAdmin 官方網站';
$strHomepageSourceforge = 'phpMyAdmin 下載網頁';
$strHost = '主機';
$strHostEmpty = '請輸入主機名稱!';

$strIdxFulltext = '全文檢索';
$strIfYouWish = '如果您要指定資料匯入的欄位，請輸入用逗號隔開的欄位名稱';
$strIgnore = '忽略';
$strIndex = '索引';
$strIndexes = '索引';
$strIndexHasBeenDropped = '索引 %s 已被刪除';
$strIndexName = '索引名稱&nbsp;:';
$strIndexType = '索引類型&nbsp;:';
$strInsert = '新增';
$strInsertAsNewRow = '儲存為新記錄';
$strInsertedRows = '新增列數:';
$strInsertNewRow = '新增一筆記錄';
$strInsertTextfiles = '將文字檔資料匯入資料表';
$strInstructions = '指令';
$strInUse = '使用中';
$strInvalidName = '"%s" 是一個保留字,您不能將保留字使用為 資料庫/資料表/欄位 名稱.';

$strKeepPass = '請不要更改密碼';
$strKeyname = '鍵名';
$strKill = 'Kill'; //should expressed in English

$strLength = '長度';
$strLengthSet = '長度/集合*';
$strLimitNumRows = '筆記錄/每頁';
$strLineFeed = '換行: \\n';
$strLines = '行數';
$strLinesTerminatedBy = '「下一行」使用字元：';
$strLocationTextfile = '文字檔案的位置';
$strLogin = '登入';
$strLogout = '登出系統';
$strLogPassword = '密碼:';
$strLogUsername = '登入名稱:';

$strModifications = '修改已儲存';
$strModify = '更改';
$strModifyIndexTopic = '更改索引';
$strMoveTable = '移動資料表到：(格式為 資料庫名稱<b>.</b>資料表名稱)';
$strMoveTableOK = '資料表 %s 已經移動到 %s.';
$strMySQLReloaded = 'MySQL 重新載入完成';
$strMySQLSaid = 'MySQL 傳回： ';
$strMySQLServerProcess = 'MySQL 版本 %pma_s1% 在 %pma_s2% 執行，登入者為 %pma_s3%';
$strMySQLShowProcess = '顯示程序 (Process)';
$strMySQLShowStatus = '顯示 MySQL 執行狀態';
$strMySQLShowVars = '顯示 MySQL 系統變數';

$strName = '名稱';
$strNbRecords = '筆開始；列出記錄筆數';
$strNext = '下一個';
$strNo = ' 否 ';
$strNoDatabases = '沒有資料庫';
$strNoDropDatabases = '"DROP DATABASE" 指令已經停用.';
$strNoFrames = 'phpMyAdmin 較為適合使用在支援<b>頁框</b>的瀏覽器.';
$strNoIndexPartsDefined = '部份索引資料還未定義!';
$strNoIndex = '沒有已定義的索引!';
$strNoModification = '沒有更改';
$strNone = '不適用';
$strNoPassword = '不用密碼';
$strNoPrivileges = '沒有權限';
$strNoQuery = '沒有 SQL 語法!';
$strNoRights = '您現在沒有足夠的權限在這裡!';
$strNoTablesFound = '資料庫中沒有資料表';
$strNotNumber = '請輸入數字!';
$strNotValidNumber = '不是有效的列數!';
$strNoUsersFound = '沒有找到使用者';
$strNull = 'Null'; //should expressed in English

$strOftenQuotation = '最常用的是引號，「非必須」表示只有 char 和 varchar 欄位會被包括起來';
$strOptimizeTable = '最佳化資料表';
$strOptionalControls = '非必要選項，用來讀寫特殊字元';
$strOptionally = '非必須';
$strOr = '或';
$strOverhead = '多餘';

$strPartialText = '顯示部份文字';
$strPassword = '密碼';
$strPasswordEmpty = '請輸入密碼!';
$strPasswordNotSame = '二次輸入的密碼不同!';
$strPHPVersion = 'PHP版本';
$strPmaDocumentation = 'phpMyAdmin 說明文件';
$strPos1 = '第一頁';
$strPrevious = '前一頁';
$strPrimary = '主鍵';
$strPrimaryKey = '主鍵';
$strPrimaryKeyHasBeenDropped = '主鍵已被刪除';
$strPrimaryKeyName = '主鍵的名稱必須稱為 PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>必須</b>是主鍵的名稱以及是<b>唯一</b>一組主鍵!)';
$strPrintView = '列印檢視';
$strPrivileges = '權限';
$strProperties = '屬性';

$strQBE = '依範例查詢 (QBE)';
$strQBEDel = '移除';
$strQBEIns = '新增';
$strQueryOnDb = '在資料庫 <b>%s</b> 執行 SQL 語法:';

$strRecords = '記錄';
$strReloadFailed = '重新載入MySQL失敗';
$strReloadMySQL = '重新載入 MySQL';
$strRememberReload = '請記著重新啟動伺服器.';
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
$strRowsModeHorizontal = '水平';
$strRowsModeOptions = '顯示為 %s 方式 及 每隔 %s 行顯示欄名';
$strRowsModeVertical = '垂直';
$strRowsStatistic = '資料列統計數值';
$strRunning = '在 %s 執行';
$strRunQuery = '執行語法';
$strRunSQLQuery = '在資料庫 %s 執行以下指令';

$strSave = '儲存';
$strSelect = '選擇';
$strSelectADb = '請選擇資料庫';
$strSelectAll = '全選';
$strSelectFields = '選擇欄位 (至少一個)';
$strSelectNumRows = '查詢中';
$strSend = '下載儲存';
$strSequence = '序列';
$strServerChoice = '選擇伺服器';
$strServerVersion = '資料庫版本';
$strSetEnumVal = '如欄位格式是 "enum" 或 "set", 請使用以下的格式輸入: \'a\',\'b\',\'c\'...<br />如在數值上需要輸入反斜線 (\) 或單引號 (\') , 請再加上反斜線 (例如 \'\\\\xyz\' or \'a\\\'b\').';
$strShow = '顯示';
$strShowAll = '顯示全部';
$strShowCols = '顯示欄';
$strShowingRecords = '顯示記錄';
$strShowPHPInfo = '顯示 PHP 資訊';
$strShowTables = '顯示資料表';
$strShowThisQuery = '在這裡重新顯示語法 ';
$strSingly = '(只會排序現時的記錄)';
$strSize = '大小';
$strSort = '排序';
$strSpaceUsage = '已使用空間';
$strSQLQuery = 'SQL 語法';
$strStartingRecord = '由記錄';
$strStatement = '敘述';
$strStrucCSV = 'CSV 資料';
$strStrucData = '結構與資料';
$strStrucDrop = '增加 \'drop table\'';
$strStrucExcelCSV = 'Ms Excel 的 CSV 格式';
$strStrucOnly = '只有結構';
$strSubmit = '送出';
$strSuccess = '您的SQL語法已順利執行';
$strSum = '總計';

$strTable = '資料表';
$strTableComments = '資料表註解文字';
$strTableEmpty = '請輸入資料表名稱!';
$strTableHasBeenDropped = '資料表 %s 已被刪除';
$strTableHasBeenEmptied = '資料表 %s 已被清空';
$strTableHasBeenFlushed = '資料表 %s 已被強迫關閉';
$strTableMaintenance = '資料表維護';
$strTables = '%s 資料表';
$strTableStructure = '資料表格式：';
$strTableType = '資料表類型';
$strTextAreaLength = ' 由於長度限制<br /> 此欄位不能編輯';
$strTheContent = '檔案內容已經匯入資料表';
$strTheContents = '檔案內容將會取代選定的資料表中具有相同主鍵或唯一鍵的記錄';
$strTheTerminator = '分隔欄位的字元';
$strTotal = '總計';
$strType = '型態';

$strUncheckAll = '全部取消';
$strUnique = '唯一';
$strUnselectAll = '全部取消';
$strUpdatePrivMessage = '你已經更新了 %s 的權限.';
$strUpdateProfile = '更新資料:';
$strUpdateProfileMessage = '資料己經更新.';
$strUpdateQuery = '更新語法';
$strUsage = '使用';
$strUseBackquotes = ' 將資料表及欄位加入引號';
$strUser = '使用者';
$strUserEmpty = '請輸入使用者名稱!';
$strUserName = '使用者名稱';
$strUsers = '使用者';
$strUseTables = '使用資料表';

$strValue = '值';
$strViewDump = '檢視資料表的備份概要 (dump schema)';
$strViewDumpDB = '檢視資料庫的備份概要 (dump schema)';

$strWelcome = '歡迎使用 %s';
$strWithChecked = '選擇的資料表：';
$strWrongUser = '錯誤的使用者名稱或密碼   拒絕存取';

$strYes = ' 是 ';

$strZip = '"zipped"';

// To translate
?>
