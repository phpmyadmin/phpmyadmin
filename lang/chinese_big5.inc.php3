<?php
/* $Id$ */

/**
 * Last translation by: Siu Sun <siusun@best-view.net>
 * Follow by the original translation of Taiyen Hung 洪泰元<yen789@pchome.com.tw>
 */

$charset = 'big5';
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'helvetica, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';

$strAPrimaryKey = '主鍵已經新增到 %s';
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
$strAllTableSameWidth = '以相同寬度顯示所有資料表?';
$strAlterOrderBy = '根據欄位內容排序記錄';
$strAnIndex = '索引已經新增到 %s';
$strAnalyzeTable = '分析資料表';
$strAnd = '與';
$strAny = '任何';
$strAnyColumn = '任何欄位';
$strAnyDatabase = '任何資料庫';
$strAnyHost = '任何主機';
$strAnyTable = '任何資料表';
$strAnyUser = '任何使用者';
$strAscending = '遞增';
$strAtBeginningOfTable = '於資料表開頭';
$strAtEndOfTable = '於資料表尾端';
$strAttr = '屬性';

$strBack = '回上一頁';
$strBeginCut = '開始 剪取';
$strBeginRaw = '開始 原始資料';
$strBinary = '二進制碼';
$strBinaryDoNotEdit = '二進制碼 - 不能編輯';
$strBookmarkDeleted = '書籤已經刪除.';
$strBookmarkLabel = '書籤名稱';
$strBookmarkQuery = 'SQL 語法書籤';
$strBookmarkThis = '將此 SQL 語法加入書籤';
$strBookmarkView = '查看';
$strBrowse = '瀏覽';
$strBzip = '"bzipped"';

$strCantLoadMySQL = '不能載入 MySQL 模組,<br />請檢查 PHP 的組態設定';
$strCantLoadRecodeIconv = '未能讀取 iconv 或重新編碼程式來作文字編碼轉換, 請設定 php 來啟動這些模組或取消 phpMyAdmin 使用文字編碼轉換功能.';
$strCantRenameIdxToPrimary = '無法將索引更名為 PRIMARY!';
$strCantUseRecodeIconv = '當文編碼模組讀取後,未能使用 iconv 、 libiconv 或 recode_string 功能. 請檢查您的 php 設定.';
$strCardinality = '組別';
$strCarriage = '歸位: \\r';
$strChange = '修改';
$strChangeDisplay = '選擇顯示之欄位';
$strChangePassword = '更改密碼';
$strCharsetOfFile = '字元表檔案:';
$strCheckAll = '全選';
$strCheckDbPriv = '檢查資料庫權限';
$strCheckTable = '檢查資料表';
$strChoosePage = '請選擇需要編輯的頁碼';
$strColComFeat = '顯示欄位註解';
$strColumn = '欄位';
$strColumnNames = '欄位名稱';
$strComments = '註解';
$strCompleteInserts = '使用完整新增指令';
$strCompression = '壓縮';
$strConfigFileError = 'phpMyAdmin 未能讀取您的設定檔! 這可能是因為 php 找到語法上的錯誤或 php 未能找到檔案而成.<br />請嘗試直接按下下方的連結開啟並查看 php 的錯誤信息. 通常的錯誤都來自某處漏了引號或分別.<br />如果按下連結後出現空白頁, 即代表沒有任何問題.';
$strConfigureTableCoord = '請設定表格 %s 內的坐標';
$strConfirm = '您確定要這樣做？';
$strCookiesRequired = 'Cookies 必須啟動才能登入.';
$strCopyTable = '複製資料表到： (格式為 資料庫名稱<b>.</b>資料表名稱):';
$strCopyTableOK = '已經將資料表 %s 複製為 %s.';
$strCreate = '建立';
$strCreateIndex = '新增 &nbsp;%s&nbsp; 組索引欄';
$strCreateIndexTopic = '新增一組索引';
$strCreateNewDatabase = '建立新資料庫';
$strCreateNewTable = '建立新資料表於資料庫 %s';
$strCreatePage = '建立新一頁';
$strCreatePdfFeat = '建立 PDF';
$strCriteria = '篩選';

$strData = '資料';
$strDataDict = '數據字典';
$strDataOnly = '只有資料';
$strDatabase = '資料庫';
$strDatabaseHasBeenDropped = '資料庫 %s 已被刪除';
$strDatabaseWildcard = '資料庫 (允許使用萬用字元):';
$strDatabases = '資料庫';
$strDatabasesStats = '資料庫統計';
$strDefault = '預設值';
$strDelete = '刪除';
$strDeleteFailed = '刪除失敗!';
$strDeleteUserMessage = '您已經將用戶 %s 刪除.';
$strDeleted = '記錄已被刪除';
$strDeletedRows = '已刪除欄數:';
$strDescending = '遞減';
$strDisabled = '未啟動';
$strDisplay = '顯示';
$strDisplayFeat = '功能顯示';
$strDisplayOrder = '顯示次序';
$strDisplayPDF = '顯示 PDF 概要';
$strDoAQuery = '以範例查詢 (萬用字元 : "%")';
$strDoYouReally = '您確定要 ';
$strDocu = '說明文件';
$strDrop = '刪除';
$strDropDB = '刪除資料庫 %s';
$strDropTable = '刪除資料表';
$strDumpXRows = '備份 %s 行, 由 %s 行開始.';
$strDumpingData = '列出以下資料庫的數據：';
$strDynamic = '動態';

$strEdit = '編輯';
$strEditPDFPages = '編輯 PDF 頁碼';
$strEditPrivileges = '編輯權限';
$strEffective = '實際';
$strEmpty = '清空';
$strEmptyResultSet = 'MySQL 傳回的查詢結果為空 (原因可能為：沒有找到符合條件的記錄)';
$strEnabled = '啟動';
$strEnd = '最後一頁';
$strEndCut = '結束 剪取';
$strEndRaw = '結束 原始資料';
$strEnglishPrivileges = '注意: MySQL 權限名稱會以英語顯示';
$strError = '錯誤';
$strExplain = '說明 SQL';
$strExport = '輸出';
$strExportToXML = '輸出為 XML 格式';
$strExtendedInserts = '伸延新增模式';
$strExtra = '附加';

$strField = '欄位';
$strFieldHasBeenDropped = '資料表 %s 已被刪除';
$strFields = '欄位';
$strFieldsEmpty = ' 欄位總數是空的! ';
$strFieldsEnclosedBy = '「欄位」使用字元：';
$strFieldsEscapedBy = '「ESCAPE」使用字元：';
$strFieldsTerminatedBy = '「欄位分隔」使用字元：';
$strFixed = '固定';
$strFlushTable = '強迫更新資料表 ("FLUSH")';
$strFormEmpty = '表格內漏填一些資料!';
$strFormat = '格式';
$strFullText = '顯示完整文字';
$strFunction = '函數';

$strGenBy = '建立';
$strGenTime = '建立日期';
$strGeneralRelationFeat = '一般關聯功能';
$strGo = '執行';
$strGrants = 'Grants'; //should expressed in English
$strGzip = '"gzipped"';

$strHasBeenAltered = '已經修改';
$strHasBeenCreated = '已經建立';
$strHaveToShow = '您需要選擇最少顯示一行欄位';
$strHome = '主目錄';
$strHomepageOfficial = 'phpMyAdmin 官方網站';
$strHomepageSourceforge = 'phpMyAdmin 下載網頁';
$strHost = '主機';
$strHostEmpty = '請輸入主機名稱!';

$strIdxFulltext = '全文檢索';
$strIfYouWish = '如果您要指定資料匯入的欄位，請輸入用逗號隔開的欄位名稱';
$strIgnore = '忽略';
$strInUse = '使用中';
$strIndex = '索引';
$strIndexHasBeenDropped = '索引 %s 已被刪除';
$strIndexName = '索引名稱&nbsp;:';
$strIndexType = '索引類型&nbsp;:';
$strIndexes = '索引';
$strInsecureMySQL = '設定檔內有關設定 (root登入及沒有密碼) 與預設的 MySQL 權限戶口相同。 MySQL 伺服器在這預設的設定運行的話會很容易被入侵，您應更改有關設定去防止安全漏洞。';
$strInsert = '新增';
$strInsertAsNewRow = '儲存為新記錄';
$strInsertNewRow = '新增一筆記錄';
$strInsertTextfiles = '將文字檔資料匯入資料表';
$strInsertedRows = '新增列數:';
$strInstructions = '指令';
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
$strLinkNotFound = '找不到連結';
$strLinksTo = '連結到';
$strLocationTextfile = '文字檔案的位置';
$strLogPassword = '密碼:';
$strLogUsername = '登入名稱:';
$strLogin = '登入';
$strLogout = '登出系統';

$strMissingBracket = '找不到括號';
$strModifications = '修改已儲存';
$strModify = '修改';
$strModifyIndexTopic = '修改索引';
$strMoveTable = '移動資料表到：(格式為 資料庫名稱<b>.</b>資料表名稱)';
$strMoveTableOK = '資料表 %s 已經移動到 %s.';
$strMySQLCharset = 'MySQL 文字編碼';
$strMySQLReloaded = 'MySQL 重新載入完成';
$strMySQLSaid = 'MySQL 傳回： ';
$strMySQLServerProcess = 'MySQL 版本 %pma_s1% 在 %pma_s2% 執行，登入者為 %pma_s3%';
$strMySQLShowProcess = '顯示程序 (Process)';
$strMySQLShowStatus = '顯示 MySQL 執行狀態';
$strMySQLShowVars = '顯示 MySQL 系統變數';

$strName = '名稱';
$strNext = '下一個';
$strNo = ' 否 ';
$strNoDatabases = '沒有資料庫';
$strNoDescription = '沒有說明';
$strNoDropDatabases = '"DROP DATABASE" 指令已經停用.';
$strNoExplain = '略過說明 SQL';
$strNoFrames = 'phpMyAdmin 較為適合使用在支援<b>頁框</b>的瀏覽器.';
$strNoIndex = '沒有已定義的索引!';
$strNoIndexPartsDefined = '部份索引資料還未定義!';
$strNoModification = '沒有變更';
$strNoPassword = '不用密碼';
$strNoPhp = '移除 PHP 程式碼';
$strNoPrivileges = '沒有權限';
$strNoQuery = '沒有 SQL 語法!';
$strNoRights = '您現在沒有足夠的權限!';
$strNoTablesFound = '資料庫中沒有資料表';
$strNoUsersFound = '找不到使用者';
$strNoValidateSQL = '略過檢查 SQL';
$strNone = '不適用';
$strNotNumber = '這不是一個數字!';
$strNotOK = '未能確定';
$strNotSet = '<b>%s</b> 資料表找不到或還未在 %s 設定';
$strNotValidNumber = '不是有效的列數!';
$strNull = 'Null'; //should expressed in English
$strNumSearchResultsInTable = '%s 項資料符合 - 於資料表 <i>%s</i>';
$strNumSearchResultsTotal = '<b>總計:</b> <i>%s</i> 項資料符合';

$strOK = '確定';
$strOftenQuotation = '最常用的是引號，「非必須」表示只有 char 和 varchar 欄位會被包括起來';
$strOperations = '管理';
$strOptimizeTable = '最佳化資料表';
$strOptionalControls = '非必要選項，用來讀寫特殊字元';
$strOptionally = '非必須';
$strOptions = '選項';
$strOr = '或';
$strOverhead = '多餘';

$strPHP40203 = '您正使用 PHP 版本 4.2.3, 這版本有一個雙字節字元的嚴重錯誤(mbstring). 請參閱 PHP 臭蟲報告編號 19404. phpMyAdmin 並不建議使用這個版本的 PHP .';
$strPHPVersion = 'PHP 版本';
$strPageNumber = '頁碼:';
$strPartialText = '顯示部份文字';
$strPassword = '密碼';
$strPasswordEmpty = '請輸入密碼!';
$strPasswordNotSame = '第二次輸入的密碼不同!';
$strPdfDbSchema = '"%s" 資料庫概要 - 第 %s 頁';
$strPdfInvalidPageNum = 'PDF 頁碼沒有設定!';
$strPdfInvalidTblName = '資料表 "%s" 不存在!';
$strPdfNoTables = '沒有資料表';
$strPhp = '建立 PHP 程式碼';
$strPmaDocumentation = 'phpMyAdmin 說明文件';
$strPmaUriError = ' 必須設定 <tt>$cfg[\'PmaAbsoluteUri\']</tt> 在設定檔內!';
$strPos1 = '第一頁';
$strPrevious = '前一頁';
$strPrimary = '主鍵';
$strPrimaryKey = '主鍵';
$strPrimaryKeyHasBeenDropped = '主鍵已被刪除';
$strPrimaryKeyName = '主鍵的名稱必須稱為 PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>必須</b>是主鍵的名稱以及是<b>唯一</b>一組主鍵!)';
$strPrint = '列印';
$strPrintView = '列印檢視';
$strPrivDescMaxConnections = 'Limits the number of new connections the user may open per hour.';
$strPrivDescMaxQuestions = 'Limits the number of queries the user may send to the server per hour.';
$strPrivDescMaxUpdates = 'Limits the number of commands that change any table or database the user may execute per hour.';
$strPrivileges = '權限';
$strProperties = '屬性';
$strPutColNames = '將欄位名稱放在首列';

$strQBE = '依範例查詢 (QBE)';
$strQBEDel = '移除';
$strQBEIns = '新增';
$strQueryOnDb = '在資料庫 <b>%s</b> 執行 SQL 語法:';
$strQueryStatistics = '<b>Query statistics</b>: Since its startup, %s queries have been sent to the server.';

$strReType = '確認密碼';
$strRecords = '記錄';
$strReferentialIntegrity = '檢查指示完整性:';
$strRelationNotWorking = '關聯資料表的附加功能未能啟動, %s請按此%s 查出問題原因.';
$strRelationView = '關聯檢視';
$strReloadFailed = '重新載入MySQL失敗';
$strReloadMySQL = '重新載入 MySQL';
$strRememberReload = '請記著重新啟動伺服器.';
$strRenameTable = '將資料表改名為';
$strRenameTableOK = '已經將資料表 %s 改名成 %s';
$strRepairTable = '修復資料表';
$strReplace = '取代';
$strReplaceTable = '以檔案取代資料表資料';
$strReset = '重置';
$strRevoke = '移除';
$strRevokeGrant = '移除 Grant 權限';
$strRevokeGrantMessage = '您已移除這位使用者的 Grant 權限: %s';
$strRevokeMessage = '您已移除這位使用者的權限: %s';
$strRevokePriv = '移除權限';
$strRowLength = '資料列長度';
$strRowSize = '資料列大小';
$strRows = '資料列列數';
$strRowsFrom = '筆記錄，開始列數:';
$strRowsModeHorizontal = '水平';
$strRowsModeOptions = '顯示為 %s 方式 及 每隔 %s 行顯示欄名';
$strRowsModeVertical = '垂直';
$strRowsStatistic = '資料列統計數值';
$strRunQuery = '執行語法';
$strRunSQLQuery = '在資料庫 %s 執行以下指令';
$strRunning = '在 %s 執行';

$strSQL = 'SQL'; // should express in english
$strSQLParserBugMessage = '這可能是您找到了 SQL 分析程式的一些程式錯誤，請細心查看您的語法，檢查一下引號是正確及沒有遺漏，其他可能出錯的原因可能來自您上載檔案時在引號外的地方使用了二進制碼。您可以嘗試在 MySQL 命令列介面執行該語法。如 MySQL 伺服器發出錯誤信息，這可能幫助您去找出問題所在。如您仍然未能解決問題，或在分析程式出現錯誤，但在命令列模式能正常執行，請將該句出現錯誤的 SQL 語法抽出，並將以下的"剪取"部份一同提交到臭虫區:';
$strSQLParserUserError = '可能是您的 SQL 語法出現錯誤，如 MySQL 伺服器發出錯誤信息，這可能幫助您去找出問題所在。';
$strSQLQuery = 'SQL 語法';
$strSQLResult = 'SQL 查詢結果';
$strSQPBugInvalidIdentifer = '無效的識別碼 (Invalid Identifer)';
$strSQPBugUnclosedQuote = '未完結的引號 (Unclosed quote)';
$strSQPBugUnknownPunctuation = '不知明的標點符號 (Unknown Punctuation String)';
$strSave = '儲存';
$strScaleFactorSmall = '比例倍數太細, 無法將圖表放在一頁內';
$strSearch = '搜索';
$strSearchFormTitle = '搜索資料庫';
$strSearchInTables = '於以下資料表:';
$strSearchNeedle = '尋找之文字或數值 (萬用字元: "%"):';
$strSearchOption1 = '任何一組文字';
$strSearchOption2 = '所有文字';
$strSearchOption3 = '完整詞語';
$strSearchOption4 = '以規則表示法 (regular expression) 搜索';
$strSearchResultsFor = '搜索 "<i>%s</i>" 的結果 %s:';
$strSearchType = '尋找:';
$strSelect = '選擇';
$strSelectADb = '請選擇資料庫';
$strSelectAll = '全選';
$strSelectFields = '選擇欄位 (至少一個)';
$strSelectNumRows = '查詢中';
$strSelectTables = '選擇資料表';
$strSend = '下載儲存';
$strServer = '伺服器 %s';
$strServerChoice = '選擇伺服器';
$strServerTrafficNotes = '<b>Server traffic</b>: These tables show the network traffic statistics of this MySQL server since its startup.';
$strServerVersion = '伺服器版本';
$strSetEnumVal = '如欄位格式是 "enum" 或 "set", 請使用以下的格式輸入: \'a\',\'b\',\'c\'...<br />如在數值上需要輸入反斜線 (\) 或單引號 (\') , 請再加上反斜線 (例如 \'\\\\xyz\' or \'a\\\'b\').';
$strShow = '顯示';
$strShowAll = '顯示全部';
$strShowColor = '顯示顏色';
$strShowCols = '顯示欄';
$strShowGrid = '顯示框格';
$strShowPHPInfo = '顯示 PHP 資訊';
$strShowTableDimension = '顯示表格大小';
$strShowTables = '顯示資料表';
$strShowThisQuery = '重新顯示 SQL 語法 ';
$strShowingRecords = '顯示記錄';
$strSingly = '(只會排序現時的記錄)';
$strSize = '大小';
$strSort = '排序';
$strSpaceUsage = '已使用空間';
$strSplitWordsWithSpace = '每組文字以空格 (" ") 分隔.';
$strStatement = '敘述';
$strStrucCSV = 'CSV 資料';
$strStrucData = '結構與資料';
$strStrucDrop = '增加 \'drop table\'';
$strStrucExcelCSV = 'Ms Excel 的 CSV 格式';
$strStrucOnly = '只有結構';
$strStructPropose = '分析資料表結構';
$strStructure = '結構';
$strSubmit = '送出';
$strSuccess = '您的SQL語法已順利執行';
$strSum = '總計';

$strTable = '資料表';
$strTableComments = '資料表註解文字';
$strTableEmpty = '請輸入資料表名稱!';
$strTableHasBeenDropped = '資料表 %s 已被刪除';
$strTableHasBeenEmptied = '資料表 %s 已被清空';
$strTableHasBeenFlushed = '資料表 %s 已被強迫更新';
$strTableMaintenance = '資料表維護';
$strTableStructure = '資料表格式：';
$strTableType = '資料表類型';
$strTables = '%s 資料表';
$strTextAreaLength = ' 由於長度限制<br /> 此欄位不能編輯 ';
$strTheContent = '檔案內容已經匯入資料表';
$strTheContents = '檔案內容將會取代選定的資料表中具有相同主鍵或唯一鍵的記錄';
$strTheTerminator = '分隔欄位的字元';
$strTotal = '總計';
$strType = '型態';

$strUncheckAll = '全部取消';
$strUnique = '唯一';
$strUnselectAll = '全部取消';
$strUpdatePrivMessage = '您已經更新了 %s 的權限.';
$strUpdateProfile = '更新資料:';
$strUpdateProfileMessage = '資料己經更新.';
$strUpdateQuery = '更新語法';
$strUsage = '使用';
$strUseBackquotes = '請在資料表及欄位使用引號';
$strUseTables = '使用資料表';
$strUser = '使用者';
$strUserEmpty = '請輸入使用者名稱!';
$strUserName = '使用者名稱';
$strUsers = '使用者';

$strValidateSQL = '檢查 SQL';
$strValidatorError = 'SQL 分析程式未能啟動，請檢查是否已將 %s文件%s 內的 PHP 檔案安裝。';
$strValue = '值';
$strViewDump = '檢視資料表的備份概要 (dump schema)';
$strViewDumpDB = '檢視資料庫的備份概要 (dump schema)';

$strWebServerUploadDirectory = 'Web 伺服器上載目錄';
$strWebServerUploadDirectoryError = '設定之上載目錄錯誤，未能使用';
$strWelcome = '歡迎使用 %s';
$strWithChecked = '選擇的資料表：';
$strWrongUser = '錯誤的使用者名稱或密碼，拒絕存取';

$strYes = ' 是 ';

$strZip = '"zipped"';

// To translate
$timespanfmt = '%s days, %s hours, %s minutes and %s seconds'; //to translate

$strAbortedClients = 'Aborted'; //to translate
$strAdministration = 'Administration'; //to translate

$strBzError = 'phpMyAdmin was unable to compress the dump because of a broken Bz2 extension in this php version. It is strongly recommended to set the <code>$cfg[\'BZipDump\']</code> directive in your phpMyAdmin configuration file to <code>FALSE</code>. If you want to use the Bz2 compression features, you should upgrade to a later php version. See php bug report %s for details.'; //to translate

$strCannotLogin = 'Cannot login to MySQL server';  //to translate
$strCommand = 'Command'; //to translate
$strConnections = 'Connections'; //to translate
$strCouldNotKill = 'phpMyAdmin was unable to kill thread %s. It probably has already been closed.'; //to translate

$strDeleteAndFlush = 'Delete the users and reload the privileges afterwards.'; //to translate
$strDeleteAndFlushDescr = 'This is the cleanest way, but reloading the privileges may take a while.'; //to translate
$strDeleting = 'Deleting %s'; //to translate

$strFailedAttempts = 'Failed attempts'; //to translate
$strFlushPrivilegesNote = 'Note: phpMyAdmin gets the users\' privileges directly from MySQL\'s privilege tables. The content of this tables may differ from the privileges the server uses if manual changes have made to it. In this case, you should %sreload the privileges%s before you continue.'; //to translate

$strGlobalPrivileges = 'Global privileges'; //to translate
$strGlobalValue = 'Global value'; //to translate
$strGrantOption = 'Grant'; //to translate

$strId = 'ID'; //to translate
$strImportDocSQL = 'Import docSQL Files';  //to translate

$strJustDelete = 'Just delete the users from the privilege tables.'; //to translate
$strJustDeleteDescr = 'The &quot;deleted&quot; users will still be able to access the server as usual until the privileges are reloaded.'; //to translate

$strLaTeX = 'LaTeX';  //to translate
$strLandscape = 'Landscape';  //to translate

$strMoreStatusVars = 'More status variables'; //to translate

$strNumTables = 'Tables'; //to translate


$strPasswordChanged = 'The Password for %s was changed successfully.'; // to translate
$strPerHour = 'per hour'; //to translate
$strPortrait = 'Portrait';  //to translate
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
$strPrivDescProcess3 = 'Allows killing processes of other users.'; //to translate
$strPrivDescProcess4 = 'Allows viewing the complete queries in the process list.'; //to translate
$strPrivDescReferences = 'Has no effect in this MySQL version.'; //to translate
$strPrivDescReload = 'Allows reloading server settings and flushing the server\'s caches.'; //to translate
$strPrivDescReplClient = 'Gives the right to the user to ask where the slaves / masters are.'; //to translate
$strPrivDescReplSlave = 'Needed for the replication slaves.'; //to translate
$strPrivDescSelect = 'Allows reading data.'; //to translate
$strPrivDescShowDb = 'Gives access to the complete list of databases.'; //to translate
$strPrivDescShutdown = 'Allows shutting down the server.'; //to translate
$strPrivDescSuper = 'Allows connectiong, even if maximum number of connections is reached; Required for most administrative operations like setting global variables or killing threads of other users.'; //to translate
$strPrivDescUpdate = 'Allows changing data.'; //to translate
$strPrivDescUsage = 'No privileges.'; //to translate
$strPrivilegesReloaded = 'The privileges were reloaded successfully.'; //to translate
$strProcesslist = 'Process list'; //to translate

$strQueryType = 'Query type'; //to translate

$strReceived = 'Received'; //to translate
$strRelationalSchema = 'Relational schema';  //to translate
$strReloadingThePrivileges = 'Reloading the privileges'; //to translate
$strRemoveSelectedUsers = 'Remove selected users'; //to translate
$strResourceLimits = 'Resource limits'; //to translate
$strRevokeAndDelete = 'Revoke all active privileges from the users and delete them afterwards.'; //to translate
$strRevokeAndDeleteDescr = 'The users will still have the USAGE privilege until the privileges are reloaded.'; //to translate

$strSent = 'Sent'; //to translate
$strServerStatus = 'Runtime Information'; //to translate
$strServerStatusUptime = 'This MySQL server has been running for %s. It started up on %s.'; //to translate
$strServerTabProcesslist = 'Processes'; //to translate
$strServerTabVariables = 'Variables'; //to translate
$strServerVars = 'Server variables and settings'; //to translate
$strSessionValue = 'Session value'; //to translate
$strShowDatadictAs = 'Data Dictionary Format';  //to translate
$strStatus = 'Status'; //to translate

$strTableOfContents = 'Table of contents';  //to translate
$strThreadSuccessfullyKilled = 'Thread %s was successfully killed.'; //to translate
$strTime = 'Time'; //to translate
$strTotalUC = 'Total'; //to translate
$strTraffic = 'Traffic'; //to translate

$strUserOverview = 'User overview'; //to translate
$strUsersDeleted = 'The selected users have been deleted successfully.'; //to translate

$strVar = 'Variable'; //to translate

$strZeroRemovesTheLimit = 'Note: Setting these options to 0 (zero) removes the limit.'; //to translate

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
$strDBGModule = 'Module';  //to translate
$strDBGLine = 'Line';  //to translate
$strDBGHits = 'Hits';  //to translate
$strDBGTimePerHitMs = 'Time/Hit, ms';  //to translate
$strDBGTotalTimeMs = 'Total time, ms';  //to translate
$strDBGMinTimeMs = 'Min time, ms';  //to translate
$strDBGMinTimeMs = 'Max time, ms';  //to translate
$strDBGContextID = 'Context ID';  //to translate
$strDBGContext = 'Context';  //to translate
$strCantLoad = 'cannot load %s extension,<br />please check PHP Configuration';  //to translate
?>
