<?php
/* $Id$ */

$charset = 'tis-620';
$text_dir = 'ltr';
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


$strAccessDenied = 'ไม่อนุญาตให้ใช้งาน';
$strAction = 'กระทำการ';
$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate (tbl_qbe.php3)
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate (tbl_qbe.php3)
$strAddNewField = 'เพิ่ม field ใหม่';
$strAddPriv = 'Add a new Privilege'; //to translate
$strAddPrivMessage = 'You have added a new privilege.'; //to translate
$strAddSearchConditions = 'เพิ่มเงื่อนไขค้นหา (ในส่วนของ where):';
$strAddUser = 'Add a new User'; //to translate
$strAddUserMessage = 'You have added a new user.'; //to translate
$strAfter = 'หลัง %s';
$strAll = 'All'; //to translate
$strAlterOrderBy = 'Alter table order by'; //to translate
$strAnalyzeTable = 'Analyze table'; //to translate
$strAnd = 'And'; //to translate (tbl_qbe.php3)
$strAny = 'Any'; //to translate
$strAnyColumn = 'Any Column'; //to translate
$strAnyDatabase = 'Any database'; //to translate
$strAnyHost = 'Any host'; //to translate
$strAnyTable = 'Any table'; //to translate
$strAnyUser = 'Any user'; //to translate
$strAscending = 'Ascending'; //to translate (tbl_qbe.php3)
$strAtBeginningOfTable = 'ที่จุดเริ่มต้นของตาราง';
$strAtEndOfTable = 'ที่จุดสุดท้ายของตาราง';
$strAttr = 'Attributes'; //to translate

$strBack = 'ย้อนกลับ';
$strBookmarkLabel = 'Label'; //to translate
$strBookmarkQuery = 'Bookmarked SQL-query'; //to translate
$strBookmarkThis = 'Bookmark this SQL-query'; //to translate
$strBookmarkView = 'View only'; //to translate
$strBrowse = 'เปิดดู';

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.'; //to translate
$strCarriage = 'Carriage return: \\r'; //to translate
$strChange = 'เปลี่ยน';
$strCheckAll = 'Check All'; //to translate
$strCheckDbPriv = 'Check Database Privileges'; //to translate
$strCheckTable = 'Check table'; //to translate
$strColumn = 'Column'; //to translate
$strColumnNames = 'ชื่อคอลัมน์';
$strCompleteInserts = 'Complete inserts';
$strConfirm = 'Do you really want to do it?'; //to translate
$strCopyTableOK = 'ตาราง %s ได้ทําการสําเนาไปเป็น %s เรียบร้อยแล้ว.';
$strCreate = 'สร้าง';
$strCreateNewDatabase = 'สร้างฐานข้อมูลใหม่';
$strCreateNewTable = 'สร้างตารางในฐานข้อมูลนี้ %s';
$strCriteria = 'Criteria'; //to translate (tbl_qbe.php3)

$strData = 'Data'; //to translate
$strDatabase = 'ฐานข้อมูล ';
$strDatabases = 'ฐานข้อมูล';
$strDataOnly = 'Data only'; //to translate
$strDefault = 'Default'; //to translate
$strDelete = 'ลบ';
$strDeleted = 'ลบแถวออกเรียบร้อยแล้ว';
$strDeleteFailed = 'Deleted Failed!'; //to translate
$strDescending = 'Desending'; //to translate (tbl_qbe.php3)
$strDisplay = 'แสดงผล';
$strDoAQuery = 'Do a "query by example" (wildcard: "%")'; //to translate
$strDocu = 'เอกสาร/คู่มืออ้างอิง';
$strDoYouReally = 'ต้องการที่จะ ';
$strDrop = 'Drop'; //to translate
$strDumpingData = 'Dump ตาราง';
$strDynamic = 'dynamic'; //to translate

$strEdit = 'แก้ไข';
$strEditPrivileges = 'Edit Privileges'; //to translate
$strEffective = 'Effective'; //to translate
$strEmpty = 'ลบข้อมูล';
$strEmptyResultSet = 'MySQL คืนผลว่างเปล่ากลับมา (0 แถว).';
$strEnd = 'ท้ายสุด';
$strError = 'ผิดพลาด';
$strExtra = 'Extra'; //to translate

$strField = 'Field'; //to translate
$strFields = 'จำนวน Fields';
$strFixed = 'fixed'; //to translate
$strFormat = 'Format'; //to translate
$strFunction = 'Function'; //to translate

$strGenTime = 'Generation Time'; //to translate
$strGo = 'ลงมือ';
$strGrants = 'Grants'; //to translate

$strHasBeenAltered = 'ได้เปลี่ยนแปลงแล้ว';
$strHasBeenCreated = 'ได้ถูกสร้างแล้ว';
$strHome = 'Home'; //to translate
$strHomepageOfficial = 'Official phpMyAdmin Homepage'; //to translate
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Page'; //to translate
$strHost = 'Host'; //to translate
$strHostEmpty = 'The host name is empty!'; //to translate

$strIfYouWish = 'If you wish to load only some of a table\'s columns, specify a comma separated field list.'; //to translate
$strIndex = 'Index'; //to translate
$strIndexes = 'Indexes'; //to translate
$strInsert = 'แทรก';
$strInsertAsNewRow = 'Insert as new row'; //to translate
$strInsertNewRow = 'แทรกแถวใหม่';
$strInsertTextfiles = 'แทรกข้อมูลจาก text file เข้าไปในตาราง';
$strInUse = 'in use'; //to translate

$strKeyname = 'ชื่อ key';
$strKill = 'Kill'; //to translate

$strLength = 'Length'; //to translate
$strLengthSet = 'ความยาว/เซต*';
$strLimitNumRows = 'ระเบียน ต่อหน้า';
$strLineFeed = 'Linefeed: \\n'; //to translate
$strLines = 'บรรทัด';
$strLocationTextfile = 'ตำแหน่งของ text file';
$strLogout = 'Log out'; //to translate

$strModifications = 'บันทึกการแก้ไขเรียบร้อยแล้ว';
$strModify = 'Modify'; //to translate (tbl_qbe.php3)
$strMySQLReloaded = 'MySQL reloaded.'; //to translate
$strMySQLSaid = 'MySQL said: '; //to translate
$strMySQLShowProcess = 'แสดง process ต่าง ๆ';
$strMySQLShowStatus = 'แสดงข้อมูล runtime ของ MySQL';
$strMySQLShowVars = 'แสดงตัวแปรระบบของ MySQL';

$strName = 'ชื่อ';
$strNext = 'ต่อไป';
$strNo = 'No'; //to translate
$strNoPassword = 'No Password'; //to translate
$strNoPrivileges = 'No Privileges'; //to translate
$strNoRights = 'You don\'t have enough rights to be here right now!'; //to translate
$strNoTablesFound = 'ไม่พบตารางใด ๆ ในฐานข้อมูล';
$strNoUsersFound = 'No user(s) found.'; //to translate
$strNull = 'Null'; //to translate

$strOftenQuotation = 'Often quotation marks. OPTIONALLY means that only char and varchar fields are enclosed by the "enclosed by"-character.'; //to translate
$strOptimizeTable = 'Optimize table'; //to translate
$strOptionalControls = 'Optional. Controls how to write or read special characters.'; //to translate
$strOptionally = 'OPTIONALLY'; //to translate
$strOr = 'หรือ';
$strOverhead = 'Overhead'; //to translate

$strPassword = 'Password'; //to translate
$strPasswordEmpty = 'The password is empty!'; //to translate
$strPasswordNotSame = 'The passwords aren\'t the same!'; //to translate
$strPHPVersion = 'PHP Version'; //to translate
$strPos1 = 'จุดเริ่มต้น';
$strPrevious = 'ก่อนหน้า';
$strPrimary = 'Primary'; //to translate
$strPrimaryKey = 'Primary key'; //to translate
$strPrintView = 'Print view'; //to translate
$strPrivileges = 'Privileges'; //to translate
$strProperties = 'คุณสมบัติ';

$strQBE = 'Query by Example'; //to translate
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)

$strRecords = 'ระเบียน';
$strReloadFailed = 'MySQL reload failed.'; //to translate
$strReloadMySQL = 'Reload MySQL'; //to translate
$strRememberReload = 'Remember reload the server.'; //to translate
$strRenameTable = 'เปลี่ยนชื่อตารางเป็น';
$strRenameTableOK = 'ตาราง %s ได้ถูกเปลี่ยนชื่อเป็น %s';
$strRepairTable = 'Repair table'; //to translate
$strReplace = 'แทนที่';
$strReplaceTable = 'แทนที่ข้อมูลด้วยไฟล์';
$strReset = 'Reset'; //to translate
$strReType = 'Re-type'; //to translate
$strRevoke = 'Revoke'; //to translate
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for %s'; //to translate
$strRevokeMessage = 'You have revoked the privileges for %s'; //to translate
$strRevokePriv = 'Revoke Privileges'; //to translate
$strRowLength = 'Row length'; //to translate
$strRows = 'Rows'; //to translate
$strRowsFrom = 'แถว เริ่มจากแถวที่';
$strRowsStatistic = 'Row Statistic'; //to translate
$strRunning = 'running on %s'; //to translate
$strRunQuery = 'Submit Query'; //to translate (tbl_qbe.php3)

$strSave = 'เก็บบันทึก';
$strSelect = 'เลือก';
$strSelectFields = 'เลือก field (อย่างน้อยหนึ่ง):';
$strSelectNumRows = 'in query'; //to translate
$strSend = 'Send'; //to translate
$strServerVersion = 'Server version'; //to translate
$strShow = 'แสดง';
$strShowingRecords = 'แสดงระเบียนที่ ';
$strSingly = '(singly)'; //to translate
$strSize = 'Size'; //to translate
$strSort = 'Sort'; //to translate (tbl_qbe.php3)
$strSpaceUsage = 'Space usage'; //to translate
$strSQLQuery = 'SQL-query'; //to translate
$strStatement = 'Statements'; //to translate
$strStrucCSV = 'ข้อมูล CSV';
$strStrucData = 'ทั้งโครงสร้างและข้อมูล';
$strStrucDrop = 'เพิ่ม \'drop table\'';
$strStrucOnly = 'เฉพาะโครงสร้าง';
$strSubmit = 'Submit'; //to translate
$strSuccess = 'ทำงาน SQL-query เสร็จเรียบร้อยแล้ว';
$strSum = 'Sum'; //to translate

$strTable = 'ตาราง ';
$strTableComments = 'หมายเหตุของตารางนี้';
$strTableEmpty = 'The table name is empty!'; //to translate
$strTableMaintenance = 'Table maintenance'; //to translate
$strTableStructure = 'โครงสร้างตาราง สำหรับตาราง';
$strTableType = 'Table type'; //to translate
$strTextAreaLength = ' Because of its length,<br /> this field might not be editable '; //to translate
$strTheContent = 'แทรกข้อมูลจากไฟล์ของคุณแล้ว';
$strTheContents = 'The contents of the file replaces the contents of the selected table for rows with identical primary or unique key.'; //to translate
$strTheTerminator = 'จุดสิ้นสุดของ field.';
$strTotal = 'ทั้งหมด';
$strType = 'Type'; //to translate

$strUncheckAll = 'Uncheck All'; //to translate
$strUnique = 'Unique'; //to translate
$strUpdateQuery = 'Update Query'; //to translate (tbl_qbe.php3)
$strUsage = 'Usage'; //to translate
$strUser = 'User'; //to translate
$strUserEmpty = 'The user name is empty!'; //to translate
$strUserName = 'User name'; //to translate
$strUsers = 'Users'; //to translate
$strUseTables = 'Use Tables'; //to translate (tbl_qbe.php3)

$strValue = 'Value'; //to translate
$strViewDump = 'ดูโครงสร้างของตาราง';
$strViewDumpDB = 'ดูโครงสร้างของฐานข้อมูล';

$strWelcome = 'ยินดีต้อนรับสู่ %s';
$strWrongUser = 'Wrong username/password. Access denied.'; //to translate

$strYes = 'Yes'; //to translate

// automatic generated by langxlorer.php3 (June 27, 2001, 6:53 pm)
// V0.11 - experimental (Steve Alberty - alberty@neptunlabs.de)
$strBinary = ' Binary ';  //to translate
$strBinaryDoNotEdit = ' Binary - do not edit ';  //to translate
$strEnglishPrivileges = ' Note: MySQL privilege names are expressed in English ';  //to translate
$strNotNumber = 'This is not a number!';  //to translate
$strNotValidNumber = ' is not a valid row number!';  //to translate

// export Zip (July 07, 2001, 19:48am)
$strBzip = '"bzipped"';
$strGzip = '"gzipped"';  //to translate
$strZip = '"zipped"';  //to translate

// To translate
$strAffectedRows = 'Affected rows:';
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Insert a new record';
$strAnIndex = 'An index has been added on %s';//to translate
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strBookmarkDeleted = 'The bookmark has been deleted.';
$strChangePassword = 'Change password';
$strCookiesRequired = 'Cookies must be enabled past this point.';
$strCopyTable = 'Copy table to (database<b>.</b>table):';
$strDatabaseHasBeenDropped = 'Database %s has been dropped.';  //to translate
$strDatabasesStats = 'Databases statistics';//to translate
$strDatabaseWildcard = 'Database (wildcards allowed):';  //to translate
$strDeletedRows = 'Deleted rows:';
$strDeleteUserMessage = 'You have deleted the user %s.';//to translate
$strDisplayOrder = 'Display order:';
$strDropDB = 'Drop database %s';
$strDropTable = 'Drop table';
$strExtendedInserts = 'Extended inserts';
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
$strLogin = 'Login';
$strLogPassword = 'Password:';
$strLogUsername = 'Username:';
$strMoveTable = 'Move table to (database<b>.</b>table):';
$strMoveTableOK = 'Table %s has been moved to %s.';
$strMySQLServerProcess = 'MySQL %pma_s1% running on %pma_s2% as %pma_s3%';
$strNbRecords = 'no. of records';
$strNoDatabases = 'No databases';
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strNoModification = 'No change'; // To translate
$strNoQuery = 'No SQL query!';  //to translate
$strPartialText = 'Partial Texts';//to translate
$strPmaDocumentation = 'phpMyAdmin Documentation';//to translate
$strPmaUriError = 'The <tt>$cfg[\'PmaAbsoluteUri\']</tt> directive MUST be set in your configuration file!';
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strQueryOnDb = 'SQL-query on database <b>%s</b>:';
$strRowSize = ' Row size ';  //to translate
$strRowsModeHorizontal = 'horizontal';  //to translate
$strRowsModeOptions = 'in %s mode and repeat headers after %s cells';  //to translate
$strRowsModeVertical = 'vertical';  //to translate
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strSelectADb = 'Please select a database';
$strSelectAll = 'Select All';  //to translate
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
$strUnselectAll = 'Unselect All';  //to translate
$strUpdatePrivMessage = 'You have updated the privileges for %s.';//to translate
$strUpdateProfile = 'Update profile:';//to translate
$strUpdateProfileMessage = 'The profile has been updated.';//to translate
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strWithChecked = 'With selected:';

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
$strReferentialIntegrity = 'Check referential integrity:';  //to translate
$strLinksTo = 'Links to';  //to translate
?>
