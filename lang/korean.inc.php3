<?php
/* $Id$ */

$charset = 'ks_c_5601-1987';
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


$strAccessDenied = '접근이 거부되었습니다.';
$strAction = '실행';
$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate (tbl_qbe.php3)
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate (tbl_qbe.php3)
$strAddNewField = '필드 추가하기';
$strAddPriv = 'Add a new Privilege'; //to translate
$strAddPrivMessage = 'You have added a new privilege.'; //to translate
$strAddSearchConditions = '검색 조건 문법을 추가하십시요. ("where" 쿼리문):';
$strAddUser = 'Add a new User'; //to translate
$strAddUserMessage = 'You have added a new user.'; //to translate
$strAfter = '다음에-->';
$strAll = 'All'; //to translate
$strAlterOrderBy = 'Alter table order by'; //to translate
$strAnalyzeTable = '테이블 분석';
$strAnd = 'And'; //to translate (tbl_qbe.php3)
$strAny = 'Any'; //to translate
$strAnyColumn = 'Any Column'; //to translate
$strAnyDatabase = 'Any database'; //to translate
$strAnyHost = 'Any host'; //to translate
$strAnyTable = 'Any table'; //to translate
$strAnyUser = 'Any user'; //to translate
$strAscending = 'Ascending'; //to translate (tbl_qbe.php3)
$strAtBeginningOfTable = '테이블의 처음';
$strAtEndOfTable = '테이블의 마지막';
$strAttr = '보기';

$strBack = '뒤로';
$strBookmarkLabel = 'Label'; //to translate
$strBookmarkQuery = 'Bookmarked SQL-query'; //to translate
$strBookmarkThis = 'Bookmark this SQL-query'; //to translate
$strBookmarkView = 'View only'; //to translate
$strBrowse = '보기';

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.'; //to translate
$strCarriage = '캐리지 리턴: \\r';
$strChange = '변경';
$strCheckAll = 'Check All'; //to translate
$strCheckDbPriv = 'Check Database Privileges'; //to translate
$strCheckTable = '테이블 체크';
$strColumn = 'Column'; //to translate
$strColumnEmpty = 'The columns names are empty!'; //to translate
$strColumnNames = '칼럼(列) 이름';
$strCompleteInserts = '완전한 INSERT문 작성';
$strConfirm = 'Do you really want to do it?'; //to translate
$strCopyTable = '테이블을 다음에 복사합니다. -->';
$strCopyTableOK = '%s 테이블이 %s 으로 복사되었습니다.';
$strCreate = ' 만들기 ';
$strCreateNewDatabase = '새로운 데이터 베이스를 만듭니다';
$strCreateNewTable = '현재의 데이터 베이스에 새로운 테이블을 만듭니다. -->';
$strCriteria = 'Criteria'; //to translate (tbl_qbe.php3)

$strData = 'Data'; //to translate
$strDatabase = '데이터 베이스 ';
$strDatabases = '데이터 베이스 ';
$strDataOnly = 'Data only'; //to translate
$strDbEmpty = 'The database name is empty!'; //to translate
$strDefault = '기본값';
$strDelete = '삭제';
$strDeleted = '선택한 열을 삭제 하였습니다.';
$strDeleteFailed = 'Deleted Failed!'; //to translate
$strDeletePassword = 'Delete Password'; //to translate
$strDeleteUserMessage = 'You have deleted the user'; //to translate
$strDelPassMessage = 'You have deleted the password for'; //to translate
$strDescending = 'Desending'; //to translate (tbl_qbe.php3)
$strDisableMagicQuotes = '<b>Warning:</b> PHP \'magic_quotes_gpc\'가 Enable로 설정되어 있습니다만, 이 버전의 phpMyAdmin에서는 작동하지 않습니다. PHP 매뉴얼을 참조하여 disable로 설정해 주십시요.';
$strDisplay = '보기';
$strDoAQuery = '"QUERY 예(例)"를 실행 (wildcard: "%")';
$strDocu = '도움말';
$strDoYouReally = '정말로 다음을 실행하시겠습니까? --> ';
$strDrop = '삭제';
$strDropDB = '데이터 베이스 삭제 -->';
$strDumpingData = '테이블의 덤프 데이터';
$strDynamic = 'dynamic'; //to translate

$strEdit = '수정';
$strEditPrivileges = 'Edit Privileges'; //to translate
$strEffective = 'Effective'; //to translate
$strEmpty = '비우기';
$strEmptyResultSet = 'MySQL 이 빈값을 리턴하였습니다.';
$strEnableMagicQuotes = '<b>Warning:</b> PHP \'magic_quotes_gpc\'이 Enable로 설정되어 있스지 않습니다. phpMyAdmin은 Enable상태에서 정상 작동합니다. PHP 매뉴얼을 참조하여 Enable로 설정해 주십시요.';
$strEnclosedBy = '인용 기호';
$strEnd = '마지막';
$strError = '오류';
$strEscapedBy = '에스케이프 기호';
$strExtra = '추가';

$strField = '필드';
$strFields = '필드';
$strFixed = 'fixed'; //to translate
$strFormat = 'Format'; //to translate
$strFunction = '함수';

$strGenTime = 'Generation Time'; //to translate
$strGo = '실행';
$strGrants = 'Grants'; //to translate

$strHasBeenAltered = '을(를) 변경하였습니다.';
$strHasBeenCreated = '을(를) 작성하였습니다.';
$strHasBeenDropped = '을(를) 삭제하였습니다.';
$strHasBeenEmptied = '을(를) 비웠습니다.';
$strHome = 'MainPage';
$strHomepageOfficial = 'phpMyAdmin 홈 페이지';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Page';
$strHost = '호스트';
$strHostEmpty = 'The host name is empty!'; //to translate

$strIfYouWish = '테이블 열(콜럼)에 데이터를 추가할 때는 필드 리스트를 콤마로 구분해 주십시요. ';
$strIndex = '인덱스';
$strIndexes = 'Indexes'; //to translate
$strInsert = '추가';
$strInsertAsNewRow = 'Insert as new row'; //to translate
$strInsertIntoTable = '테이블에 추가';
$strInsertNewRow = '새 열에 추가';
$strInsertTextfiles = '테이블에 텍스트 파일 추가';
$strInUse = 'in use'; //to translate

$strKeyname = '키 이름';
$strKill = 'Kill'; //to translate

$strLength = 'Length'; //to translate
$strLengthSet = '길이/세트*';
$strLimitNumRows = '페이지의 레코드 수';
$strLineFeed = '개행 문자: \\n';
$strLines = '줄(行)';
$strLocationTextfile = 'SQL 덤프 데이터 텍스트 파일';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = '로그 아웃';

$strModifications = '을 바르게 수정하였습니다.';
$strModify = 'Modify'; //to translate (tbl_qbe.php3)
$strMySQLReloaded = 'MySQL을 새로 읽어 들였습니다.';
$strMySQLSaid = 'MySQL 메세지: ';
$strMySQLShowProcess = 'MySQL 프로세스 보기';
$strMySQLShowStatus = 'MySQL 런타임 정보 보기';
$strMySQLShowVars = 'MySQL 시스템 변수 보기';

$strName = '이름';
$strNext = '다음으로';
$strNo = ' 아니오 ';
$strNoPassword = 'No Password'; //to translate
$strNoPrivileges = 'No Privileges'; //to translate
$strNoRights = 'You don\'t have enough rights to be here right now!'; //to translate
$strNoTablesFound = '현재의 DB에는 테이블이 없습니다.';
$strNoUsersFound = 'No user(s) found.'; //to translate
$strNull = 'Null';
$strNumberIndexes = ' Number of advanced indexes '; //to translate

$strOftenQuotation = '인용 기호입니다. 옵션은, char 혹은 varchar 필드만이 " "로 감싸여진 것을 의미합니다.';
$strOptimizeTable = '테이블 최적화';
$strOptionalControls = '특수문자 읽기/쓰기 옵션';
$strOptionally = '옵션입니다.';
$strOr = '혹은';
$strOverhead = 'Overhead'; //to translate

$strPassword = 'Password'; //to translate
$strPasswordEmpty = 'The password is empty!'; //to translate
$strPasswordNotSame = 'The passwords aren\'t the same!'; //to translate
$strPHPVersion = 'PHP Version'; //to translate
$strPos1 = '처음';
$strPrevious = '이전으로';
$strPrimary = 'Primary';
$strPrimaryKey = 'Primary 키';
$strPrinterFriendly = '위 테이블 인쇄용 보기';
$strPrintView = '인쇄용 보기';
$strPrivileges = 'Privileges'; //to translate
$strProducedAnError = '에러가 발생하였습니다';
$strProperties = '프로파티';

$strQBE = '예(例)에서 쿼리 실행';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)

$strReadTheDocs = '문서 읽어 들이기';
$strRecords = '기록수';
$strReloadFailed = 'MySQL이 읽어 들이기에 실패하였습니다.';
$strReloadMySQL = 'MySQL 다시 읽어 들이기';
$strRememberReload = 'Remember reload the server.'; //to translate
$strRenameTable = '테이블 이름 변경하기';
$strRenameTableOK = '%s을(를) %s(으)로 변경하였습니다.';
$strRepairTable = '테이블 복구';
$strReplace = '대치(代置)하기';
$strReplaceTable = '파일로 테이블 대치하기';
$strReset = '리세트';
$strReType = 'Re-type'; //to translate
$strRevoke = 'Revoke'; //to translate
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for'; //to translate
$strRevokeMessage = 'You have revoked the privileges for'; //to translate
$strRevokePriv = 'Revoke Privileges'; //to translate
$strRowLength = 'Row length'; //to translate
$strRows = 'Rows'; //to translate
$strRowsFrom = '개시행';
$strRowsStatistic = 'Row Statistic'; //to translate
$strRunning = '가 실행중입니다. ';
$strRunQuery = 'Submit Query'; //to translate (tbl_qbe.php3)

$strSave = '보존';
$strSelect = '선택';
$strSelectFields = '필드 선택 (하나 이상):';
$strSelectNumRows = '쿼리';
$strSend = '송신';
$strSequence = 'Seq.'; //to translate
$strServerVersion = 'Server version'; //to translate
$strShow = '보기';
$strShowingRecords = '기록 보기';
$strSingly = '(singly)'; //to translate
$strSize = 'Size'; //to translate
$strSort = 'Sort'; //to translate (tbl_qbe.php3)
$strSpaceUsage = 'Space usage'; //to translate
$strSQLQuery = '실행된 SQL쿼리';
$strStatement = 'Statements'; //to translate
$strStrucCSV = 'CSV 데이터';
$strStrucData = '구조와 데이터';
$strStrucDrop = '\'drop table\' 추가';
$strStrucOnly = '구조만';
$strSubmit = '실행';
$strSuccess = 'SQL-query가 바르게 실행되었습니다.';
$strSum = 'Sum'; //to translate

$strTable = '테이블 ';
$strTableComments = '테이블 설명';
$strTableEmpty = 'The table name is empty!'; //to translate
$strTableMaintenance = 'Table maintenance'; //to translate
$strTableStructure = '테이블 구조';
$strTableType = '테이블 타입';
$strTerminatedBy = '종료 기호';
$strTextAreaLength = ' Because of its length,<br /> this field might not be editable '; //to translate
$strTheContent = '파일의 데이터를 삽입하였습니다.';
$strTheContents = '파일의 데이터로 선택한 테이블의 primary 혹은 unique 키와 일치하는 열을 대치(代置)시키겠습니다.';
$strTheTerminator = '필드의 종료 기호 입니다.';
$strTotal = '합계';
$strType = '필드 타이프';

$strUncheckAll = 'Uncheck All'; //to translate
$strUnique = '유니크 타이프';
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

$strValue = '값';
$strViewDump = '테이블의 덤프(스키마) 데이터 보기';
$strViewDumpDB = 'DB의 덤프(스키마) 데이터 보기';

$strWelcome = 'Welcome to ';
$strWrongUser = '유저명 혹은 패스워드가 바르지 않습니다. <br /> 접근이 거부되었습니다.';

$strYes = ' 예 ';

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
$strDatabasesStats = 'Databases statistics';//to translate
$strDeletedRows = 'Deleted rows:';
$strDisplayOrder = 'Display order:';
$strDropTable = 'Drop table';
$strFieldsEmpty = ' The field count is empty! ';  //to translate
$strFormEmpty = 'Missing value in the form !';
$strFullText = 'Full Texts';//to translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strInsertedRows = 'Inserted rows:';
$strInstructions = 'Instructions';//to translate
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.'; //to translate
$strNbRecords = 'no. of records';
$strNoDatabases = 'No databases';
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.';
$strNoModification = 'No change'; // To translate
$strPartialText = 'Partial Texts';//to translate
$strPmaDocumentation = 'phpMyAdmin Documentation';//to translate 
$strQueryOnDb = 'SQL-query on database ';
$strRowSize = ' Row size ';  //to translate
$strRunningAs = 'as';
$strServerChoice = 'Server Choice';//to translate 
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').';
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowPHPInfo = 'Show PHP information';  // To translate
$strShowTables = 'Show tables';
$strShowThisQuery = ' Show this query here again ';  //to translate
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strTables = '%s table(s)';  //to translate
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strWithChecked = 'With checked:';
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strAnIndex = 'An index has been added on %s';//to translate
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strFieldsEnclosedBy = 'Fields enclosed by';//to translate
$strFieldsEscapedBy = 'Fields escaped by';//to translate
$strFieldsTerminatedBy = 'Fields terminated by';//to translate
$strIndexHasBeenDropped = 'Index %s has been dropped';//to translate
$strLinesTerminatedBy = 'Lines terminated by';//to translate
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strStartingRecord = 'Starting record';//to translate
$strTableHasBeenDropped = 'Table %s has been dropped';//to translate
$strTableHasBeenEmptied = 'Table %s has been emptied';//to translate
$strUpdateServer = 'Update server';//to translate
$strUpdateServMessage = 'You have updated the server for %s';//to translate
?>
