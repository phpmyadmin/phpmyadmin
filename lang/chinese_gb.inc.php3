<?php
/* $Id$ */

$charset = 'gb2312';
$left_font_family = 'sans-serif';
$right_font_family = 'sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');


$strAccessDenied = '访问被拒绝';
$strAction = '执行操作';
$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate (tbl_qbe.php3)
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate (tbl_qbe.php3)
$strAddNewField = '添加新字段';
$strAddPriv = 'Add a new Privilege'; //to translate
$strAddPrivMessage = 'You have added a new privilege.'; //to translate
$strAddSearchConditions = '添加检索条件 ("where" 语句的主体)：';
$strAddUser = 'Add a new User'; //to translate
$strAddUserMessage = 'You have added a new user.'; //to translate
$strAfter = 'After'; //to translate
$strAll = 'All'; //to translate
$strAlterOrderBy = 'Alter table order by'; //to translate
$strAnalyzeTable = 'Analyze table'; //to translate
$strAnd = 'And'; //to translate (tbl_qbe.php3)
$strAnIndex = '已经添加了一个索引于 ';
$strAny = 'Any'; //to translate
$strAnyColumn = 'Any Column'; //to translate
$strAnyDatabase = 'Any database'; //to translate
$strAnyHost = 'Any host'; //to translate
$strAnyTable = 'Any table'; //to translate
$strAnyUser = 'Any user'; //to translate
$strAPrimaryKey = '已经建立了一个主键于 ';
$strAscending = 'Ascending'; //to translate (tbl_qbe.php3)
$strAtBeginningOfTable = 'At Beginning of Table'; //to translate
$strAtEndOfTable = 'At End of Table'; //to translate
$strAttr = '属性';

$strBack = '返回';
$strBookmarkLabel = 'Label'; //to translate
$strBookmarkQuery = 'Bookmarked SQL-query'; //to translate
$strBookmarkThis = 'Bookmark this SQL-query'; //to translate
$strBookmarkView = 'View only'; //to translate
$strBrowse = '浏览';

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.'; //to translate
$strCarriage = '回车: \\r';
$strChange = '改变';
$strCheckAll = 'Check All'; //to translate
$strCheckDbPriv = 'Check Database Privileges'; //to translate
$strCheckTable = 'Check table'; //to translate
$strColumn = 'Column'; //to translate
$strColumnEmpty = 'The columns names are empty!'; //to translate
$strColumnNames = '字段名';
$strCompleteInserts = 'Complete inserts'; //to translate
$strConfirm = 'Do you really want to do it?'; //to translate
$strCopyTable = '复制数据表为:';
$strCopyTableOK = '数据表 %s 已经成功复制为 %s。';
$strCreate = '建立';
$strCreateNewDatabase = '建立一个新的数据库';
$strCreateNewTable = '建立一个新的数据表与数据库 ';
$strCriteria = 'Criteria'; //to translate (tbl_qbe.php3)

$strData = 'Data'; //to translate
$strDatabase = '数据库 ';
$strDatabases = '数据库';
$strDataOnly = 'Data only'; //to translate
$strDbEmpty = 'The database name is empty!'; //to translate
$strDefault = '缺省值';
$strDelete = '删除';
$strDeleted = '该记录已经被删除。';
$strDeleteFailed = 'Deleted Failed!'; //to translate
$strDeletePassword = 'Delete Password'; //to translate
$strDeleteUserMessage = 'You have deleted the user'; //to translate
$strDelPassMessage = 'You have deleted the password for'; //to translate
$strDescending = 'Desending'; //to translate (tbl_qbe.php3)
$strDisableMagicQuotes = '<b>Warning:</b> You have enabled magic_quotes_gpc in your PHP configuration. This version of PhpMyAdmin cannot work properly with it. Please have a look at the configuration section of the PHP manual for information on how to disable it.'; //to translate
$strDisplay = 'Display'; //to translate
$strDoAQuery = '请执行 "查询示例" (通配符: "%")';
$strDocu = '文档';
$strDoYouReally = '请确认要 ';
$strDrop = '丢弃';
$strDropDB = '丢弃数据库 ';
$strDumpingData = '导出下面的数据库内容';
$strDynamic = 'dynamic'; //to translate

$strEdit = '编辑';
$strEditPrivileges = 'Edit Privileges'; //to translate
$strEffective = 'Effective'; //to translate
$strEmpty = '清空';
$strEmptyResultSet = 'MySQL 返回的查询结果为空。 (原因可能为：没有找到符合条件的记录。)';
$strEnableMagicQuotes = '<b>Warning:</b> You haven\'t enabled magic_quotes_gpc in your PHP configuration. PhpMyAdmin needs this to work properly. Please have a look at the configuration section of the PHP manual for information on how to enable it.'; //to translate
$strEnclosedBy = '由何字符括起来';
$strEnd = '结束';
$strError = '错误';
$strEscapedBy = '转义符';
$strExtra = '额外';

$strField = '字段';
$strFields = '字段';
$strFixed = 'fixed'; //to translate
$strFormat = 'Format'; //to translate
$strFunction = '功能';

$strGenTime = 'Generation Time'; //to translate
$strGo = '开始';
$strGrants = 'Grants'; //to translate

$strHasBeenAltered = '已经被修改。';
$strHasBeenCreated = '已经建立。';
$strHasBeenDropped = '已经被丢弃。';
$strHasBeenEmptied = '已经被清空。';
$strHome = '主目录';
$strHomepageOfficial = 'Official phpMyAdmin Homepage'; //to translate
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Page'; //to translate
$strHost = '主机';
$strHostEmpty = 'The host name is empty!'; //to translate

$strIfYouWish = '如果你要指定调入的字段，那么请给出用逗号隔开的字段列表。';
$strIndex = '索引';
$strIndexes = 'Indexes'; //to translate
$strInsert = '插入';
$strInsertAsNewRow = 'Insert as new row'; //to translate
$strInsertIntoTable = '插入于数据表';
$strInsertNewRow = '插入新记录';
$strInsertTextfiles = '从文本文件中提取数据，插入到数据表：';
$strInUse = 'in use'; //to translate

$strKeyname = '键名';
$strKill = 'Kill'; //to translate

$strLength = 'Length'; //to translate
$strLengthSet = '长度/Set';
$strLimitNumRows = 'records per page'; //to translate
$strLineFeed = '换行：\\n';
$strLines = '行数 ';
$strLocationTextfile = '文本文件的位置';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = '退出系统';

$strModifications = '修改后的数据已经存盘。';
$strModify = 'Modify'; //to translate (tbl_qbe.php3)
$strMySQLReloaded = 'MySQL 重新启动完成。';
$strMySQLSaid = 'MySQL 返回：';
$strMySQLShowProcess = '显示进程';
$strMySQLShowStatus = '显示 MySQL 的运行信息';
$strMySQLShowVars = '显示 MySQL 的系统变量';

$strName = '名字';
$strNext = '下一个';
$strNo = '否';
$strNoPassword = 'No Password'; //to translate
$strNoPrivileges = 'No Privileges'; //to translate
$strNoRights = 'You don\'t have enough rights to be here right now!'; //to translate
$strNoTablesFound = '数据库中没有数据表。';
$strNoUsersFound = 'No user(s) found.'; //to translate
$strNull = 'Null';
$strNumberIndexes = ' Number of advanced indexes '; //to translate

$strOftenQuotation = '通常为引号。 ”选中“ 表示使用引号。因为只有 char 和 varchar 类型的数据需要用引号括起来。';
$strOptimizeTable = 'Optimize table'; //to translate
$strOptionalControls = '可选。用于读取或写入特殊的字符。';
$strOptionally = '选中';
$strOr = 'Or'; //to translate
$strOverhead = 'Overhead'; //to translate

$strPassword = 'Password'; //to translate
$strPasswordEmpty = 'The password is empty!'; //to translate
$strPasswordNotSame = 'The passwords aren\'t the same!'; //to translate
$strPHPVersion = 'PHP Version'; //to translate
$strPos1 = '开始';
$strPrevious = '前一个';
$strPrimary = '键名';
$strPrimaryKey = '主键';
$strPrinterFriendly = 'Printer friendly version of above table'; //to translate
$strPrintView = 'Print view'; //to translate
$strPrivileges = 'Privileges'; //to translate
$strProducedAnError = '产生一个错误。';
$strProperties = '属性';

$strQBE = '查询模板';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)

$strReadTheDocs = '参照以下文档 ';
$strRecords = '记录';
$strReloadFailed = 'MySQL 重起失败。';
$strReloadMySQL = '重起 MySQL';
$strRememberReload = 'Remember reload the server.'; //to translate
$strRenameTable = '将数据表改名为';
$strRenameTableOK = '数据表 %s 名字已经被该成 %s。';
$strRepairTable = 'Repair table'; //to translate
$strReplace = '替换';
$strReplaceTable = '将数据表的数据用以下文本文件替换：';
$strReset = '重置';
$strReType = 'Re-type'; //to translate
$strRevoke = 'Revoke'; //to translate
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for'; //to translate
$strRevokeMessage = 'You have revoked the privileges for'; //to translate
$strRevokePriv = 'Revoke Privileges'; //to translate
$strRowLength = 'Row length'; //to translate
$strRows = 'Rows'; //to translate
$strRowsFrom = 'rows starting from'; //to translate
$strRowsStatistic = 'Row Statistic'; //to translate
$strRunning = '运行于 ';
$strRunQuery = 'Submit Query'; //to translate (tbl_qbe.php3)
$strRunSQLQuery = '在下面指定的数据库中运行 SQL 语句： ';

$strSave = '存储';
$strSelect = '选择';
$strSelectFields = '至少选择一个字段：';
$strSelectNumRows = 'in query'; //to translate
$strSend = '发送';
$strSequence = 'Seq.'; //to translate
$strServerVersion = 'Server version'; //to translate
$strShow = 'Show'; //to translate
$strShowingRecords = '显示记录 ';
$strSingly = '(singly)'; //to translate
$strSize = 'Size'; //to translate
$strSort = 'Sort'; //to translate (tbl_qbe.php3)
$strSpaceUsage = 'Space usage'; //to translate
$strSQLQuery = 'SQL 语句';
$strStatement = 'Statements'; //to translate
$strStrucCSV = 'CSV 数据';
$strStrucData = '结构和数据';
$strStrucDrop = '添加 \'drop table\'';
$strStrucOnly = '只选择结构';
$strSubmit = '发送';
$strSuccess = '你运行的 SQL 语句已经成功运行了。';
$strSum = 'Sum'; //to translate

$strTable = '数据表 ';
$strTableComments = 'Table comments'; //to translate
$strTableEmpty = 'The table name is empty!'; //to translate
$strTableMaintenance = 'Table maintenance'; //to translate
$strTableStructure = '数据表的结构';
$strTableType = 'Table type'; //to translate
$strTerminatedBy = '语句结束符：';
$strTextAreaLength = ' Because of its length,<br /> this field might not be editable '; //to translate
$strTheContent = '文件中的内容已经插入到数据表中。';
$strTheContents = '文件中的内容将会取代 所选定的数据表中具有 相同的主键或唯一键的 记录。';
$strTheTerminator = '这些字段的结束符';
$strTotal = '总计';
$strType = '类型';

$strUncheckAll = 'Uncheck All'; //to translate
$strUnique = '唯一';
$strUpdatePassMessage = 'You have updated the password for'; //to translate
$strUpdatePassword = 'Update Password'; //to translate
$strUpdatePrivMessage = 'You have updated the privileges for'; //to translate
$strUpdateQuery = 'Update Query'; //to translate (tbl_qbe.php3)
$strUsage = 'Usage'; //to translate
$strUser = 'User'; //to translate
$strUserEmpty = 'The user name is empty!'; //to translate
$strUserName = 'User name'; //to translate
$strUsers = 'Users'; //to translate
$strUseTables = 'Use Tables'; //to translate (tbl_qbe.php3)

$strValue = '值';
$strViewDump = '查看数据表的结构和摘要信息。';
$strViewDumpDB = '查看数据库的结构和摘要信息。';

$strWelcome = '欢迎使用 ';
$strWrongUser = '密码错误，访问被拒绝。';

$strYes = '是';

// automatic generated by langxlorer.php (June 27, 2001, 6:53 pm)
// V0.11 - experimental (Steve Alberty - alberty@neptunlabs.de)
$strBinary = ' Binary ';  //to translate
$strBinaryDoNotEdit = ' Binary - do not edit ';  //to translate
$strEnglishPrivileges = ' Note: MySQL privilege names are expressed in English ';  //to translate
$strNotNumber = 'This is not a number!';  //to translate
$strNotValidNumber = ' is not a valid row number!';  //to translate

// export Zip (July 07, 2001, 19:48am)
$strBzip = '"bzipped"';
$strGzip = '"gzipped"';  //to translate

// To translate
$strAffectedRows = 'Affected rows:';
$strDisplayOrder = 'Display order:';
$strFieldsEmpty = ' The field count is empty! ';  //to translate
$strFormEmpty = 'Missing value in the form !';
$strNbRecords = 'no. of records';
$strOffSet = 'offset';
$strQueryOnDb = 'SQL-query on database ';
$strRowSize = ' Row size ';  //to translate
$strShowPHPInfo = 'Show PHP information';  // To translate
$strShowThisQuery = ' Show this query here again ';
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strTables=" tables ";  //to translate
?>
