<?php
/* $Id$ */

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Access denied';
$strAction = 'Action';
$strAddDeleteColumn = 'Add/Delete Field Columns';
$strAddDeleteRow = 'Add/Delete Criteria Row';
$strAddNewField = 'Add new field';
$strAddPriv = 'Add a new Privilege';
$strAddPrivMessage = 'You have added a new privilege.';
$strAddSearchConditions = 'Add search conditions (body of the "where" clause):';
$strAddToIndex = 'Add to index &nbsp;%s&nbsp;column(s)';
$strAddUser = 'Add a new User';
$strAddUserMessage = 'You have added a new user.';
$strAffectedRows = 'Affected rows:';
$strAfter = 'After';
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Insert a new record';
$strAll = 'All';
$strAlterOrderBy = 'Alter table order by';
$strAnalyzeTable = 'Analyze table';
$strAnd = 'And';
$strAnIndex = 'An index has been added on %s';
$strAny = 'Any';
$strAnyColumn = 'Any Column';
$strAnyDatabase = 'Any database';
$strAnyHost = 'Any host';
$strAnyTable = 'Any table';
$strAnyUser = 'Any user';
$strAPrimaryKey = 'A primary key has been added on %s';
$strAscending = 'Ascending';
$strAtBeginningOfTable = 'At Beginning of Table';
$strAtEndOfTable = 'At End of Table';
$strAttr = 'Attributes';

$strBack = 'Back';
$strBinary = 'Binary';
$strBinaryDoNotEdit = 'Binary - do not edit';
$strBookmarkDeleted = 'The bookmark has been deleted.';
$strBookmarkLabel = 'Label';
$strBookmarkQuery = 'Bookmarked SQL-query';
$strBookmarkThis = 'Bookmark this SQL-query';
$strBookmarkView = 'View only';
$strBrowse = 'Browse';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.';
$strCantRenameIdxToPrimary = 'Can\'t rename index to PRIMARY!';
$strCardinality = 'Cardinality';
$strCarriage = 'Carriage return: \\r';
$strChange = 'Change';
$strCheckAll = 'Check All';
$strCheckDbPriv = 'Check Database Privileges';
$strCheckTable = 'Check table';
$strColumn = 'Column';
$strColumnNames = 'Column names';
$strCompleteInserts = 'Complete inserts';
$strConfirm = 'Do you really want to do it?';
$strCookiesRequired = 'Cookies must be enabled past this point.';
$strCopyTable = 'Copy table to (database<b>.</b>table):';
$strCopyTableOK = 'Table %s has been copied to %s.';
$strCreate = 'Create';
$strCreateIndex = 'Create an index on&nbsp;%s&nbsp;columns';
$strCreateIndexTopic = 'Create a new index';
$strCreateNewDatabase = 'Create new database';
$strCreateNewTable = 'Create new table on database ';
$strCriteria = 'Criteria';

$strData = 'Data';
$strDatabase = 'Database ';
$strDatabaseHasBeenDropped = 'Database %s has been dropped.';
$strDatabases = 'databases';
$strDatabasesStats = 'Databases statistics';
$strDataOnly = 'Data only';
$strDefault = 'Default';
$strDelete = 'Delete';
$strDeleted = 'The row has been deleted';
$strDeletedRows = 'Deleted rows:';
$strDeleteFailed = 'Deleted Failed!';
$strDeleteUserMessage = 'You have deleted the user %s.';
$strDescending = 'Descending';
$strDisplay = 'Display';
$strDisplayOrder = 'Display order:';
$strDoAQuery = 'Do a "query by example" (wildcard: "%")';
$strDocu = 'Documentation';
$strDoYouReally = 'Do you really want to ';
$strDrop = 'Drop';
$strDropDB = 'Drop database ';
$strDropTable = 'Drop table';
$strDumpingData = 'Dumping data for table';
$strDynamic = 'dynamic';

$strEdit = 'Edit';
$strEditPrivileges = 'Edit Privileges';
$strEffective = 'Effective';
$strEmpty = 'Empty';
$strEmptyResultSet = 'MySQL returned an empty result set (i.e. zero rows).';
$strEnd = 'End';
$strEnglishPrivileges = ' Note: MySQL privilege names are expressed in English ';
$strError = 'Error';
$strExtendedInserts = 'Extended inserts';
$strExtra = 'Extra';

$strField = 'Field';
$strFieldHasBeenDropped = 'Field %s has been dropped';
$strFields = 'Fields';
$strFieldsEmpty = ' The field count is empty! ';
$strFieldsEnclosedBy = 'Fields enclosed by';
$strFieldsEscapedBy = 'Fields escaped by';
$strFieldsTerminatedBy = 'Fields terminated by';
$strFixed = 'fixed';
$strFlushTable = 'Flush the table ("FLUSH")';
$strFormat = 'Format';
$strFormEmpty = 'Missing value in the form !';
$strFullText = 'Full Texts';
$strFunction = 'Function';

$strGenTime = 'Generation Time';
$strGo = 'Go';
$strGrants = 'Grants';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'has been altered.';
$strHasBeenCreated = 'has been created.';
$strHome = 'Home';
$strHomepageOfficial = 'Official phpMyAdmin Homepage';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Page';
$strHost = 'Host';
$strHostEmpty = 'The host name is empty!';

$strIdxFulltext = 'Fulltext';
$strIfYouWish = 'If you wish to load only some of a table\'s columns, specify a comma separated field list.';
$strIgnore = 'Ignore';
$strIndex = 'Index';
$strIndexes = 'Indexes';
$strIndexHasBeenDropped = 'Index %s has been dropped';
$strIndexName = 'Index name&nbsp;:';
$strIndexType = 'Index type&nbsp;:';
$strInsert = 'Insert';
$strInsertAsNewRow = 'Insert as new row';
$strInsertedRows = 'Inserted rows:';
$strInsertNewRow = 'Insert new row';
$strInsertTextfiles = 'Insert data from a textfile into table';
$strInstructions = 'Instructions';
$strInUse = 'in use';
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.';

$strKeepPass = 'Do not change the password';
$strKeyname = 'Keyname';
$strKill = 'Kill';

$strLength = 'Length';
$strLengthSet = 'Length/Values*';
$strLimitNumRows = 'Number of records per page';
$strLineFeed = 'Linefeed: \\n';
$strLines = 'Lines';
$strLinesTerminatedBy = 'Lines terminated by';
$strLocationTextfile = 'Location of the textfile';
$strLogin = 'Login';
$strLogout = 'Log out';
$strLogPassword = 'Password:';
$strLogUsername = 'Username:';

$strModifications = 'Modifications have been saved';
$strModify = 'Modify';
$strModifyIndexTopic = 'Modify an index';
$strMoveTable = 'Move table to (database<b>.</b>table):';
$strMoveTableOK = 'Table %s has been moved to %s.';
$strMySQLReloaded = 'MySQL reloaded.';
$strMySQLSaid = 'MySQL said: ';
$strMySQLServerProcess = 'MySQL %pma_s1% running on %pma_s2% as %pma_s3%';
$strMySQLShowProcess = 'Show processes';
$strMySQLShowStatus = 'Show MySQL runtime information';
$strMySQLShowVars = 'Show MySQL system variables';

$strName = 'Name';
$strNbRecords = 'No. Of records';
$strNext = 'Next';
$strNo = 'No';
$strNoDatabases = 'No databases';
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strNoIndex = 'No index defined!';
$strNoIndexPartsDefined = 'No index parts defined!';
$strNoModification = 'No change';
$strNone = 'None';
$strNoPassword = 'No Password';
$strNoPrivileges = 'No Privileges';
$strNoQuery = 'No SQL query!';  //to translate
$strNoRights = 'You don\'t have enough rights to be here right now!';
$strNoTablesFound = 'No tables found in database.';
$strNotNumber = 'This is not a number!';
$strNotValidNumber = ' is not a valid row number!';
$strNoUsersFound = 'No user(s) found.';
$strNull = 'Null';
$strNumberIndexes = ' Number of advanced indexes ';

$strOftenQuotation = 'Often quotation marks. OPTIONALLY means that only char and varchar fields are enclosed by the "enclosed by"-character.';
$strOptimizeTable = 'Optimize table';
$strOptionalControls = 'Optional. Controls how to write or read special characters.';
$strOptionally = 'OPTIONALLY';
$strOr = 'Or';
$strOverhead = 'Overhead';

$strPartialText = 'Partial Texts';
$strPassword = 'Password';
$strPasswordEmpty = 'The password is empty!';
$strPasswordNotSame = 'The passwords aren\'t the same!';
$strPHPVersion = 'PHP Version';
$strPmaDocumentation = 'phpMyAdmin documentation';
$strPos1 = 'Begin';
$strPrevious = 'Previous';
$strPrimary = 'Primary';
$strPrimaryKey = 'Primary key';
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';
$strPrimaryKeyName = 'The name of the primary key must be... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>must</b> be the name of and <b>only of</b> a primary key!)';
$strPrintView = 'Print view';
$strPrivileges = 'Privileges';
$strProperties = 'Properties';

$strQBE = 'Query by Example';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'SQL-query on database <b>%s</b>:';

$strRecords = 'Records';
$strReloadFailed = 'MySQL reload failed.';
$strReloadMySQL = 'Reload MySQL';
$strRememberReload = 'Remember reload the server.';
$strRenameTable = 'Rename table to';
$strRenameTableOK = 'Table %s has been renamed to %s';
$strRepairTable = 'Repair table';
$strReplace = 'Replace';
$strReplaceTable = 'Replace table data with file';
$strReset = 'Reset';
$strReType = 'Re-type';
$strRevoke = 'Revoke';
$strRevokeGrant = 'Revoke Grant';
$strRevokeGrantMessage = 'You have revoked the Grant privilege for %s';
$strRevokeMessage = 'You have revoked the privileges for %s';
$strRevokePriv = 'Revoke Privileges';
$strRowLength = 'Row length';
$strRows = 'Rows';
$strRowsFrom = 'rows starting from';
$strRowSize = ' Row size ';
$strRowsStatistic = 'Row Statistic';
$strRunning = 'running on %s';
$strRunQuery = 'Submit Query';
$strRunSQLQuery = 'Run SQL query/queries on database %s';

$strSave = 'Save';
$strSelect = 'Select';
$strSelectFields = 'Select fields (at least one):';
$strSelectNumRows = 'in query';
$strSend = 'Save as file';
$strSequence = 'Seq.';
$strServerChoice = 'Server Choice';
$strServerVersion = 'Server version';
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').';
$strShow = 'Show';
$strShowAll = 'Show all';
$strShowCols = 'Show columns';
$strShowingRecords = 'Showing records ';
$strShowPHPInfo = 'Show PHP information';
$strShowTables = 'Show tables';
$strShowThisQuery = ' Show this query here again ';
$strSingly = '(singly)';
$strSize = 'Size';
$strSort = 'Sort';
$strSpaceUsage = 'Space usage';
$strSQLQuery = 'SQL-query';
$strStartingRecord = 'Starting record';
$strStatement = 'Statements';
$strStrucCSV = 'CSV data';
$strStrucData = 'Structure and data';
$strStrucDrop = 'Add \'drop table\'';
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strStrucOnly = 'Structure only';
$strSubmit = 'Submit';
$strSuccess = 'Your SQL-query has been executed successfully';
$strSum = 'Sum';

$strTable = 'table ';
$strTableComments = 'Table comments';
$strTableEmpty = 'The table name is empty!';
$strTableHasBeenDropped = 'Table %s has been dropped';
$strTableHasBeenEmptied = 'Table %s has been emptied';
$strTableHasBeenFlushed = 'Table %s has been flushed';
$strTableMaintenance = 'Table maintenance';
$strTables = '%s table(s)';
$strTableStructure = 'Table structure for table';
$strTableType = 'Table type';
$strTextAreaLength = ' Because of its length,<br /> this field might not be editable ';
$strTheContent = 'The content of your file has been inserted.';
$strTheContents = 'The contents of the file replaces the contents of the selected table for rows with identical primary or unique key.';
$strTheTerminator = 'The terminator of the fields.';
$strTotal = 'total';
$strType = 'Type';

$strUncheckAll = 'Uncheck All';
$strUnique = 'Unique';
$strUpdatePrivMessage = 'You have updated the privileges for %s.';
$strUpdateProfile = 'Update profile:';
$strUpdateProfileMessage = 'The profile has been updated.';
$strUpdateQuery = 'Update Query';
$strUsage = 'Usage';
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strUser = 'User';
$strUserEmpty = 'The user name is empty!';
$strUserName = 'User name';
$strUsers = 'Users';
$strUseTables = 'Use Tables';

$strValue = 'Value';
$strViewDump = 'View dump (schema) of table';
$strViewDumpDB = 'View dump (schema) of database';

$strWelcome = 'Welcome to %s';
$strWithChecked = 'With selected:';
$strWrongUser = 'Wrong username/password. Access denied.';

$strYes = 'Yes';

$strZip = '"zipped"';

// please don't touch these, will sort after:
$strRowsModeVertical = 'vertical'; 
$strRowsModeHorizontal = 'horizontal'; 
$strRowsModeOptions = 'in %s mode and repeat headers after %s cells';

?>
