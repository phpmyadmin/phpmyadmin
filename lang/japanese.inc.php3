<?php
/* $Id$ */

$charset = 'euc-jp';
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


$strAccessDenied = 'アクセスは拒否されました。';
$strAction = '実行';
$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate (tbl_qbe.php3)
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate (tbl_qbe.php3)
$strAddNewField = 'フィールドの追加';
$strAddPriv = 'Add a new Privilege'; //to translate
$strAddPrivMessage = 'You have added a new privilege.'; //to translate
$strAddSearchConditions = '検索条件文を追加してください。("where"の節文):';
$strAddUser = 'Add a new User'; //to translate
$strAddUserMessage = 'You have added a new user.'; //to translate
$strAfter = '後に-->';
$strAll = 'All'; //to translate
$strAlterOrderBy = 'Alter table order by'; //to translate
$strAnalyzeTable = 'テーブルを分析します。';
$strAnd = 'And'; //to translate (tbl_qbe.php3)
$strAny = 'Any'; //to translate
$strAnyColumn = 'Any Column'; //to translate
$strAnyDatabase = 'Any database'; //to translate
$strAnyHost = 'Any host'; //to translate
$strAnyTable = 'Any table'; //to translate
$strAnyUser = 'Any user'; //to translate
$strAscending = 'Ascending'; //to translate (tbl_qbe.php3)
$strAtBeginningOfTable = 'テーブルの最初';
$strAtEndOfTable = 'テーブルの最後';
$strAttr = '表示';

$strBack = '戻る';
$strBookmarkLabel = 'Label'; //to translate
$strBookmarkQuery = 'Bookmarked SQL-query'; //to translate
$strBookmarkThis = 'Bookmark this SQL-query'; //to translate
$strBookmarkView = 'View only'; //to translate
$strBrowse = '表示';

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.'; //to translate
$strCarriage = 'キャリッジリターン: \\r';
$strChange = '変更';
$strCheckAll = 'Check All'; //to translate
$strCheckDbPriv = 'Check Database Privileges'; //to translate
$strCheckTable = 'テーブルをチェックします。';
$strColumn = 'Column'; //to translate
$strColumnNames = '列(コラム)名';
$strCompleteInserts = '完全なINSERT文の作成';
$strConfirm = 'Do you really want to do it?'; //to translate
$strCopyTableOK = '%sテーブルを%sにコピーしました。';
$strCreate = '作成';
$strCreateNewDatabase = '新しいDBを作成します。';
$strCreateNewTable = '現在のDBに新しいテーブルを作成します。 --> ';
$strCriteria = 'Criteria'; //to translate (tbl_qbe.php3)

$strData = 'Data'; //to translate
$strDatabase = 'データベース';
$strDatabases = 'データベース';
$strDataOnly = 'Data only'; //to translate
$strDefault = '基本値';
$strDelete = '削除';
$strDeleted = '選択した列を削除しました。';
$strDeleteFailed = 'Deleted Failed!'; //to translate
$strDescending = 'Desending'; //to translate (tbl_qbe.php3)
$strDisplay = '表示';
$strDoAQuery = '"例のQUERY"を実行 (wildcard: "%")';
$strDocu = 'ヘルプ';
$strDoYouReally = '本当に次を実行しまか。 --> ';
$strDrop = '削除';
$strDropDB = 'DBの削除 -->';
$strDumpingData = 'テーブルのダンプデータ';
$strDynamic = 'dynamic'; //to translate

$strEdit = '修正';
$strEditPrivileges = 'Edit Privileges'; //to translate
$strEffective = 'Effective'; //to translate
$strEmpty = '空にする';
$strEmptyResultSet = 'MySQLが空の値をリターンしました。(i.e. zero rows).';
$strEnd = '最後';
$strError = 'エラー';
$strExtra = '追加';

$strField = 'フィールド';
$strFields = 'フィールド';
$strFixed = 'fixed'; //to translate
$strFormat = 'Format'; //to translate
$strFunction = '関数';

$strGenTime = 'Generation Time'; //to translate
$strGo = '実行';
$strGrants = 'Grants'; //to translate

$strHasBeenAltered = 'を変更しました。';
$strHasBeenCreated = 'を作成しました。';
$strHome = 'MainPage';
$strHomepageOfficial = 'phpMyAdminホーム';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Page';
$strHost = 'ホスト';
$strHostEmpty = 'The host name is empty!'; //to translate

$strIfYouWish = 'テーブルのコラム(列)にデータを追加する場合は、フィールドリストをカンマで区分してください。';
$strIndex = 'インデックス';
$strIndexes = 'Indexes'; //to translate
$strInsert = '追加';
$strInsertAsNewRow = 'Insert as new row'; //to translate
$strInsertNewRow = '新しい列の追加';
$strInsertTextfiles = 'テーブルにテキストファイルの追加';
$strInUse = 'in use'; //to translate

$strKeyname = 'Key名';
$strKill = 'Kill'; //to translate

$strLength = 'Length'; //to translate
$strLengthSet = '長さ/セット*';
$strLimitNumRows = 'ページのレコード数';
$strLineFeed = '改行文字: \\n';
$strLines = '行';
$strLocationTextfile = 'SQLのダンプデータが保存されているテキストファイル';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'ログアウト';

$strModifications = 'を正しく修正しました。';
$strModify = 'Modify'; //to translate (tbl_qbe.php3)
$strMySQLReloaded = 'MySQLを新しく読み込みました。';
$strMySQLSaid = 'MySQLのメッセージ --> ';
$strMySQLShowProcess = 'MySQLプロセスの表示';
$strMySQLShowStatus = 'MySQLのランタイム情報';
$strMySQLShowVars = 'MySQLのシステム変数';

$strName = '名前';
$strNext = '次へ';
$strNo = 'いいえ';
$strNoPassword = 'No Password'; //to translate
$strNoPrivileges = 'No Privileges'; //to translate
$strNoRights = 'You don\'t have enough rights to be here right now!'; //to translate
$strNoTablesFound = '現在のDBにテーブルはありません。';
$strNoUsersFound = 'No user(s) found.'; //to translate
$strNull = '空の値(Null)';
$strNumberIndexes = ' Number of advanced indexes '; //to translate

$strOftenQuotation = '引用符号です。オプションは、charまたはvarcharフィールドのみ" "で囲まれていることを意味します。';
$strOptimizeTable = 'テーブルを最適化します。';
$strOptionalControls = '特殊文字の読み込み/書き込みオプション';
$strOptionally = 'オプションです。';
$strOr = 'または';
$strOverhead = 'Overhead'; //to translate

$strPassword = 'Password'; //to translate
$strPasswordEmpty = 'The password is empty!'; //to translate
$strPasswordNotSame = 'The passwords aren\'t the same!'; //to translate
$strPHPVersion = 'PHP Version'; //to translate
$strPos1 = '最初';
$strPrevious = '以前';
$strPrimary = 'Primary';
$strPrimaryKey = 'Primaryキー';
$strPrintView = '印刷用表示';
$strPrivileges = 'Privileges'; //to translate
$strProperties = 'プロパティ';

$strQBE = '例からクエリー実行';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)

$strRecords = 'レコード数';
$strReloadFailed = 'MySQLのリロードに失敗しました。';
$strReloadMySQL = 'MySQLのリロード';
$strRememberReload = 'Remember reload the server.'; //to translate
$strRenameTable = 'テーブル名の変更';
$strRenameTableOK = '%sを%sに名前を変更しました。';
$strRepairTable = 'テーブルを復旧します。';
$strReplace = '置き換える';
$strReplaceTable = 'ファイルでテーブルを置き換える';
$strReset = 'リセット';
$strReType = 'Re-type'; //to translate
$strRevoke = 'Revoke'; //to translate
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for %s'; //to translate
$strRevokeMessage = 'You have revoked the privileges for %s'; //to translate
$strRevokePriv = 'Revoke Privileges'; //to translate
$strRowLength = 'Row length'; //to translate
$strRows = 'Rows'; //to translate
$strRowsFrom = '開始行';
$strRowsStatistic = 'Row Statistic'; //to translate
$strRunning = 'が実行中です。';
$strRunQuery = 'Submit Query'; //to translate (tbl_qbe.php3)

$strSave = '保存';
$strSelect = '選択';
$strSelectFields = 'フィールドの選択(一つ以上):';
$strSelectNumRows = 'クエリー';
$strSend = '送信';
$strSequence = 'Seq.'; //to translate
$strServerVersion = 'Server version'; //to translate
$strShow = '表示';
$strShowingRecords = 'レコード表示';
$strSingly = '(singly)'; //to translate
$strSize = 'Size'; //to translate
$strSort = 'Sort'; //to translate (tbl_qbe.php3)
$strSpaceUsage = 'Space usage'; //to translate
$strSQLQuery = '実行されたSQLクエリー';
$strStatement = 'Statements'; //to translate
$strStrucCSV = 'CSVデータ';
$strStrucData = '構造とデータ';
$strStrucDrop = '\'drop table\'の追加';
$strStrucOnly = '構造のみ';
$strSubmit = '実行';
$strSuccess = 'SQLクエリーが正常に実行されました。';
$strSum = 'Sum'; //to translate

$strTable = 'テーブル ';
$strTableComments = 'テーブルの説明';
$strTableEmpty = 'The table name is empty!'; //to translate
$strTableMaintenance = 'Table maintenance'; //to translate
$strTableStructure = 'テーブルの構造';
$strTableType = 'テーブルのタイプ';
$strTextAreaLength = ' Because of its length,<br /> this field might not be editable '; //to translate
$strTheContent = 'ファイルのデータを挿入しました。';
$strTheContents = 'ファイルのデータで、選択したテーブルのPrimaryまたはUniqueキーに一致する列を置き換えます。';
$strTheTerminator = 'フィールドの終了文字です。';
$strTotal = '合計';
$strType = 'フィールドタイプ';

$strUncheckAll = 'Uncheck All'; //to translate
$strUnique = 'ユニークタイプ';
$strUpdateQuery = 'Update Query'; //to translate (tbl_qbe.php3)
$strUsage = 'Usage'; //to translate
$strUser = 'User'; //to translate
$strUserEmpty = 'The user name is empty!'; //to translate
$strUserName = 'User name'; //to translate
$strUsers = 'Users'; //to translate
$strUseTables = 'Use Tables'; //to translate (tbl_qbe.php3)

$strValue = '値';
$strViewDump = 'テーブルのダンプ(スキーマ)表示';
$strViewDumpDB = 'DBのダンプ(スキーマ)表示';

$strWelcome = 'Welcome to %s';
$strWrongUser = 'ユーザ名またはパスワードが正しくありません。<br />アクセスは拒否されました。';

$strYes = '  はい  ';

// automatic generated by langxlorer.php (June 27, 2001, 6:53 pm)
// V0.11 - experimental (Steve Alberty - alberty@neptunlabs.de)
$strBinary = ' Binary ';  //to translate
$strBinaryDoNotEdit = ' Binary - do not edit ';  //to translate
$strEnglishPrivileges = ' Note: MySQL privilege names are expressed in English ';  //to translate
$strNotNumber = 'This is not a number!';  //to translate
$strNotValidNumber = ' is not a valid row number!';  //to translate

// export Zip (July 07, 2001, 19:48am)
$strBzip = '"bzipped"';  //to translate
$strGzip = '"gzipped"';  //to translate
$strZip = '"zipped"';  //to translate

// To translate
$strAffectedRows = 'Affected rows:';
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Insert a new record';
$strAnIndex = 'An index has been added on %s';//to translate
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strBookmarkDeleted = 'The bookmark has been deleted.';
$strCopyTable = 'Copy table to (database<b>.</b>table):';
$strDatabaseHasBeenDropped = 'Database %s has been dropped.';  //to translate
$strDatabasesStats = 'Databases statistics';//to translate
$strDeleteUserMessage = 'You have deleted the user %s.';//to translate
$strDeletedRows = 'Deleted rows:';
$strDisplayOrder = 'Display order:';
$strDropTable = 'Drop table';
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strFieldsEmpty = ' The field count is empty! ';  //to translate
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
$strNbRecords = 'no. of records';
$strNoDatabases = 'No databases';
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strNoModification = 'No change'; // To translate
$strNoQuery = 'No SQL query!';  //to translate
$strPartialText = 'Partial Texts';//to translate
$strPmaDocumentation = 'phpMyAdmin Documentation';//to translate 
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strQueryOnDb = 'SQL-query on database ';
$strRowSize = ' Row size ';  //to translate
$strRunningAs = 'as';
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strServerChoice = 'Server Choice';//to translate 
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').';
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowPHPInfo = 'Show PHP information';  // To translate
$strShowTables = 'Show tables';
$strShowThisQuery = ' Show this query here again ';  //to translate
$strStartingRecord = 'Starting record';//to translate
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strTableHasBeenDropped = 'Table %s has been dropped';//to translate
$strTableHasBeenEmptied = 'Table %s has been emptied';//to translate
$strTableHasBeenFlushed = 'Table %s has been flushed';
$strTables = '%s table(s)';  //to translate
$strUpdatePrivMessage = 'You have updated the privileges for %s.';//to translate
$strUpdateProfile = 'Update profile:';//to translate
$strUpdateProfileMessage = 'The profile has been updated.';//to translate
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strWithChecked = 'With selected:';
?>
