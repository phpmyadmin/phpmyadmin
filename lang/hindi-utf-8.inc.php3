<?php
/* $Id$ */

// Hindi translation
// 1st release   :   by Girish Nair <girishn@nagpur.dot.net.in>

$charset = 'utf-8';
$allow_recoding = TRUE;
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
//$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
$byteUnits = array(' बैट्स', ' केबी', ' एमबी', ' जीबी','टीबी','पीबी','ईबी');

$day_of_week = array('रवी', 'सोम', 'मन्गल', 'बुध', 'गुरु', 'शुक्र', 'शनि');
$month = array('जनवरी', 'फरवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', ' सितम्बर', 'अक्तूबर', 'नवम्बर', 'दिसमबर');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d %B, %Y को %I:%M %p';

$strAccessDenied = 'प्रवेश निषेध';
$strAction = ' कार्य';
$strAddNewField = 'नया फील्ड जोडो';
$strAddPriv = 'नया प्रिविलेज जोडो';
$strAddPrivMessage = 'आपने नया प्रिविलेज जोड लिया ।';
$strAddUser = 'नया यूसर बनाओ';
$strAddUserMessage = 'आपने नया यूसर बना लिया ।';
$strAfter = '%s के बाद में';
$strAfterInsertBack = 'पिछले पृष्ट पर वापस जाओ';
$strAfterInsertNewInsert = 'अगला नया रौ जोडे';
$strAll = 'सभी';
$strAnd = 'और';
$strAny = 'कोई';
$strAnyColumn = 'कोई भी कोलम';
$strAnyDatabase = 'कोई भी  डाटाबेस';
$strAnyHost = 'कोई भी  होस्ट';
$strAnyTable = 'कोई भी  टेबल';
$strAnyUser = 'कोई भी  यूसर';
$strAtBeginningOfTable = 'टेबल के शुरू  में';
$strAtEndOfTable = 'टेबल के आखिर में';
$strAttr = ' विशेषता';

$strBack = 'वापस';
$strBookmarkLabel = 'लेबल';

$strChange = 'बदलिये';
$strCheckAll = 'सभी को चेक करें';
$strCheckDbPriv = 'डाटाबेस   प्रिविलेजस  को चेक करें';
$strCheckTable = 'टेबल  को चेक करें';
$strColumn = 'कोलम';
$strColumnNames = 'कोलम के नाम';
$strComments = ' टिप्पणी';
$strCreate = 'बनाइये';
$strCreateNewDatabase = ' नया डाटाबेस बनाओ';
$strCreateNewTable = ' डाटाबेस मे नया टेबल बनाओ';

$strData = ' डाटा';
$strDatabase = ' डाटाबेस';
$strDatabaseHasBeenDropped = 'डाटाबेस %s को ड्रोप कर दिया ।';
$strDatabases = ' डाटाबेस';
$strDatabasesStats = ' डाटाबेसों के आँकडे';
$strDataOnly = 'केवल डाटा';
$strDelete = 'डिलीट';
$strDrop = ' ड्रोप';

$strEdit = 'एडिट';
$strEditPDFPages = 'PDF पेज एडिट करें';
$strEditPrivileges = ' प्रिविलेज एडिट करें';
$strEmpty = 'खाली';
$strExtra = ' अतिरिक्त';

$strGrants = 'ग्रान्टस';

$strHome = 'होम';
$strHomepageOfficial = 'phpMyAdmin का आधिकारिक होमपेज';
$strHost = 'होस्ट';

$strLogin = 'लोगिन';
$strLogout = 'लोग औट';
$strLogPassword = 'पासव्रड:';
$strLogUsername = 'यूसरनेम:';

$strMySQLShowProcess = 'प्रोसेस दिखाओ';
$strMySQLShowStatus = 'MySQL के runtime जानकारी  दिखाओ';
$strMySQLShowVars = 'MySQL के  system variables दिखाओ';

$strName = 'नाम';
$strNext = 'अगला';
$strNo = 'नहीं';

$strOr = 'अथवा';

$strShowPHPInfo = 'PHP कि जानकारी  दिखाओ';
$strSum = 'जोड';

$strTable = ' टेबल ';

$strUser = 'यूसर';
$strUserName = 'यूसर नेम';
$strUsers = 'यूसरस';
$strUseTables = 'टेबल का उपयोग करो';

$strValue = 'मूल्य';

$strWelcome = ' %s मे स्वागत है';

$strYes = 'हाँ';

// To translate

$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate
$strAddSearchConditions = 'Add search conditions (body of the "where" clause):'; //to translate
$strAddToIndex = 'Add to index &nbsp;%s&nbsp;column(s)'; //to translate
$strAffectedRows = 'Affected rows:'; //to translate
$strAllTableSameWidth = 'display all Tables with same width?'; //to translate
$strAlterOrderBy = 'Alter table order by'; //to translate
$strAnalyzeTable = 'Analyze table'; //to translate
$strAnIndex = 'An index has been added on %s'; //to translate
$strAPrimaryKey = 'A primary key has been added on %s'; //to translate
$strAscending = 'Ascending'; //to translate

$strBeginCut = 'BEGIN CUT'; //to translate
$strBeginRaw = 'BEGIN RAW'; //to translate
$strBinary = 'Binary'; //to translate
$strBinaryDoNotEdit = 'Binary - do not edit'; //to translate
$strBookmarkDeleted = 'The bookmark has been deleted.'; //to translate
$strBookmarkQuery = 'Bookmarked SQL-query'; //to translate
$strBookmarkThis = 'Bookmark this SQL-query'; //to translate
$strBookmarkView = 'View only'; //to translate
$strBrowse = 'Browse'; //to translate
$strBzip = '"bzipped"'; //to translate

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.'; //to translate
$strCantLoadRecodeIconv = 'Can not load iconv or recode extension needed for charset conversion, configure php to allow using these extensions or disable charset conversion in phpMyAdmin.'; //to translate
$strCantRenameIdxToPrimary = 'Can\'t rename index to PRIMARY!'; //to translate
$strCantUseRecodeIconv = 'Can not use iconv nor libiconv nor recode_string function while extension reports to be loaded. Check your php configuration.'; //to translate
$strCardinality = 'Cardinality'; //to translate
$strCarriage = 'Carriage return: \\r' //to translate;
$strChangeDisplay = 'Choose Field to display' //to translate;
$strChangePassword = 'Change password' //to translate;
$strCharsetOfFile = 'Character set of the file:' //to translate;
$strChoosePage = 'Please choose a Page to edit' //to translate;
$strColComFeat = 'Displaying Column Comments' //to translate;
$strCompleteInserts = 'Complete inserts' //to translate;
$strConfigFileError = 'phpMyAdmin was unable to read your configuration file!<br />This might happen if php finds a parse error in it or php cannot find the file.<br />Please call the configuration file directly using the link below and read the php error message(s) that you recieve. In most cases a quote or a semicolon is missing somewhere.<br />If you recieve a blank page, everything is fine.' //to translate;
$strConfigureTableCoord = 'Please configure the coordinates for table %s' //to translate;
$strConfirm = 'Do you really want to do it?' //to translate;
$strCookiesRequired = 'Cookies must be enabled past this point.' //to translate;
$strCopyTable = 'Copy table to (database<b>.</b>table):' //to translate;
$strCopyTableOK = 'Table %s has been copied to %s.' //to translate;
$strCreateIndex = 'Create an index on&nbsp;%s&nbsp;columns' //to translate;
$strCreateIndexTopic = 'Create a new index' //to translate;
$strCreatePage = 'Create a new Page' //to translate;
$strCreatePdfFeat = 'Creation of PDFs' //to translate;
$strCriteria = 'Criteria' //to translate;

$strDatabaseWildcard = 'Database (wildcards allowed):' //to translate;
$strDefault = 'Default' //to translate;
$strDeleted = 'The row has been deleted' //to translate;
$strDeletedRows = 'Deleted rows:' //to translate;
$strDeleteFailed = 'Deleted Failed!' //to translate;
$strDeleteUserMessage = 'You have deleted the user %s.' //to translate;
$strDescending = 'Descending' //to translate;
$strDisabled = 'Disabled' //to translate;
$strDisplay = 'Display' //to translate;
$strDisplayFeat = 'Display Features' //to translate;
$strDisplayOrder = 'Display order:' //to translate;
$strDisplayPDF = 'Display PDF schema' //to translate;
$strDoAQuery = 'Do a "query by example" (wildcard: "%")' //to translate;
$strDocu = 'Documentation' //to translate;
$strDoYouReally = 'Do you really want to ' //to translate;
$strDropDB = 'Drop database %s' //to translate;
$strDropTable = 'Drop table' //to translate;
$strDumpingData = 'Dumping data for table' //to translate;
$strDumpXRows = 'Dump %s row(s) starting at record # %s.' //to translate;
$strDynamic = 'dynamic' //to translate;

$strEffective = 'Effective' //to translate;
$strEmptyResultSet = 'MySQL returned an empty result set (i.e. zero rows).' //to translate;
$strEnabled = 'Enabled' //to translate;
$strEnd = 'End' //to translate;
$strEndCut = 'END CUT' //to translate;
$strEndRaw = 'END RAW' //to translate;
$strEnglishPrivileges = ' Note: MySQL privilege names are expressed in English ' //to translate;
$strError = 'Error' //to translate;
$strExplain = 'Explain SQL' //to translate;
$strExport = 'Export' //to translate;
$strExportToXML = 'Export to XML format' //to translate;
$strExtendedInserts = 'Extended inserts' //to translate;

$strField = 'Field' //to translate;
$strFieldHasBeenDropped = 'Field %s has been dropped' //to translate;
$strFields = 'Fields' //to translate;
$strFieldsEmpty = ' The field count is empty! ' //to translate;
$strFieldsEnclosedBy = 'Fields enclosed by' //to translate;
$strFieldsEscapedBy = 'Fields escaped by' //to translate;
$strFieldsTerminatedBy = 'Fields terminated by' //to translate;
$strFixed = 'fixed' //to translate;
$strFlushTable = 'Flush the table ("FLUSH")' //to translate;
$strFormat = 'Format' //to translate;
$strFormEmpty = 'Missing value in the form !' //to translate;
$strFullText = 'Full Texts' //to translate;
$strFunction = 'Function' //to translate;

$strGenBy = 'Generated by' //to translate;
$strGeneralRelationFeat = 'General relation features' //to translate;
$strGenTime = 'Generation Time' //to translate;
$strGo = 'Go' //to translate;
$strGzip = '"gzipped"' //to translate;

$strHasBeenAltered = 'has been altered.' //to translate;
$strHasBeenCreated = 'has been created.' //to translate;
$strHaveToShow = 'You have to choose at least one Column to display' //to translate;
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Page' //to translate;
$strHostEmpty = 'The host name is empty!' //to translate;

$strIdxFulltext = 'Fulltext' //to translate;
$strIfYouWish = 'If you wish to load only some of a table\'s columns, specify a comma separated field list.' //to translate;
$strIgnore = 'Ignore' //to translate;
$strIndex = 'Index' //to translate;
$strIndexes = 'Indexes' //to translate;
$strIndexHasBeenDropped = 'Index %s has been dropped' //to translate;
$strIndexName = 'Index name&nbsp;:' //to translate;
$strIndexType = 'Index type&nbsp;:' //to translate;
$strInsert = 'Insert' //to translate;
$strInsertAsNewRow = 'Insert as a new row' //to translate;
$strInsertedRows = 'Inserted rows:' //to translate;
$strInsertNewRow = 'Insert new row' //to translate;
$strInsertTextfiles = 'Insert data from a textfile into table' //to translate;
$strInstructions = 'Instructions' //to translate;
$strInUse = 'in use' //to translate;
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.' //to translate;

$strKeepPass = 'Do not change the password' //to translate;
$strKeyname = 'Keyname' //to translate;
$strKill = 'Kill' //to translate;

$strLength = 'Length' //to translate;
$strLengthSet = 'Length/Values*' //to translate;
$strLimitNumRows = 'Number of rows per page' //to translate;
$strLineFeed = 'Linefeed: \\n' //to translate;
$strLines = 'Lines' //to translate;
$strLinesTerminatedBy = 'Lines terminated by' //to translate;
$strLinkNotFound = 'Link not found' //to translate;
$strLinksTo = 'Links to' //to translate;
$strLocationTextfile = 'Location of the textfile' //to translate;

$strMissingBracket = 'Missing Bracket' //to translate;
$strModifications = 'Modifications have been saved' //to translate;
$strModify = 'Modify' //to translate;
$strModifyIndexTopic = 'Modify an index' //to translate;
$strMoveTable = 'Move table to (database<b>.</b>table):' //to translate;
$strMoveTableOK = 'Table %s has been moved to %s.' //to translate;
$strMySQLCharset = 'MySQL charset' //to translate;
$strMySQLReloaded = 'MySQL reloaded.' //to translate;
$strMySQLSaid = 'MySQL said: ' //to translate;
$strMySQLServerProcess = 'MySQL %pma_s1% running on %pma_s2% as %pma_s3%' //to translate;

$strNoDatabases = 'No databases' //to translate;
$strNoDescription = 'no Description' //to translate;
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.' //to translate;
$strNoExplain = 'Skip Explain SQL' //to translate;
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.' //to translate;
$strNoIndex = 'No index defined!' //to translate;
$strNoIndexPartsDefined = 'No index parts defined!' //to translate;
$strNoModification = 'No change' //to translate;
$strNone = 'None' //to translate;
$strNoPassword = 'No Password' //to translate;
$strNoPhp = 'Without PHP Code' //to translate;
$strNoPrivileges = 'No Privileges' //to translate;
$strNoQuery = 'No SQL query!' //to translate;
$strNoRights = 'You don\'t have enough rights to be here right now!' //to translate;
$strNoTablesFound = 'No tables found in database.' //to translate;
$strNotNumber = 'This is not a number!' //to translate;
$strNotOK = 'not OK' //to translate;
$strNotSet = '<b>%s</b> table not found or not set in %s' //to translate;
$strNotValidNumber = ' is not a valid row number!' //to translate;
$strNoUsersFound = 'No user(s) found.' //to translate;
$strNoValidateSQL = 'Skip Validate SQL' //to translate;
$strNull = 'Null' //to translate;
$strNumSearchResultsInTable = '%s match(es) inside table <i>%s</i>' //to translate;
$strNumSearchResultsTotal = '<b>Total:</b> <i>%s</i> match(es)' //to translate;

$strOftenQuotation = 'Often quotation marks. OPTIONALLY means that only char and varchar fields are enclosed by the "enclosed by"-character.' //to translate;
$strOK = 'OK' //to translate;
$strOperations = 'Operations' //to translate;
$strOptimizeTable = 'Optimize table' //to translate;
$strOptionalControls = 'Optional. Controls how to write or read special characters.' //to translate;
$strOptionally = 'OPTIONALLY' //to translate;
$strOptions = 'Options' //to translate;
$strOverhead = 'Overhead' //to translate;

$strPageNumber = 'Page number:' //to translate;
$strPartialText = 'Partial Texts' //to translate;
$strPassword = 'Password' //to translate;
$strPasswordEmpty = 'The password is empty!' //to translate;
$strPasswordNotSame = 'The passwords aren\'t the same!' //to translate;
$strPdfDbSchema = 'Schema of the the "%s" database - Page %s' //to translate;
$strPdfInvalidPageNum = 'Undefined PDF page number!' //to translate;
$strPdfInvalidTblName = 'The "%s" table doesn\'t exist!' //to translate;
$strPdfNoTables = 'No tables' //to translate;
$strPhp = 'Create PHP Code' //to translate;
$strPHPVersion = 'PHP Version' //to translate;
$strPmaDocumentation = 'phpMyAdmin documentation' //to translate;
$strPmaUriError = 'The <tt>$cfg[\'PmaAbsoluteUri\']</tt> directive MUST be set in your configuration file!' //to translate;
$strPos1 = 'Begin' //to translate;
$strPrevious = 'Previous' //to translate;
$strPrimary = 'Primary' //to translate;
$strPrimaryKey = 'Primary key' //to translate;
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped' //to translate;
$strPrimaryKeyName = 'The name of the primary key must be... PRIMARY!' //to translate;
$strPrimaryKeyWarning = '("PRIMARY" <b>must</b> be the name of and <b>only of</b> a primary key!)' //to translate;
$strPrintView = 'Print view' //to translate;
$strPrivileges = 'Privileges' //to translate;
$strProperties = 'Properties' //to translate;

$strQBE = 'Query' //to translate;
$strQBEDel = 'Del' //to translate;
$strQBEIns = 'Ins' //to translate;
$strQueryOnDb = 'SQL-query on database <b>%s</b>:' //to translate;

$strRecords = 'Records' //to translate;
$strReferentialIntegrity = 'Check referential integrity:' //to translate;
$strRelationNotWorking = 'The additional Features for working with linked Tables have been deactivated. To find out why click %shere%s.' //to translate;
$strRelationView = 'Relation view' //to translate;
$strReloadFailed = 'MySQL reload failed.' //to translate;
$strReloadMySQL = 'Reload MySQL' //to translate;
$strRememberReload = 'Remember reload the server.' //to translate;
$strRenameTable = 'Rename table to' //to translate;
$strRenameTableOK = 'Table %s has been renamed to %s' //to translate;
$strRepairTable = 'Repair table' //to translate;
$strReplace = 'Replace' //to translate;
$strReplaceTable = 'Replace table data with file' //to translate;
$strReset = 'Reset' //to translate;
$strReType = 'Re-type' //to translate;
$strRevoke = 'Revoke' //to translate;
$strRevokeGrant = 'Revoke Grant' //to translate;
$strRevokeGrantMessage = 'You have revoked the Grant privilege for %s' //to translate;
$strRevokeMessage = 'You have revoked the privileges for %s' //to translate;
$strRevokePriv = 'Revoke Privileges' //to translate;
$strRowLength = 'Row length' //to translate;
$strRows = 'Rows' //to translate;
$strRowsFrom = 'row(s) starting from record #' //to translate;
$strRowSize = ' Row size ' //to translate;
$strRowsModeHorizontal = 'horizontal' //to translate;
$strRowsModeOptions = 'in %s mode and repeat headers after %s cells' //to translate;
$strRowsModeVertical = 'vertical' //to translate;
$strRowsStatistic = 'Row Statistic' //to translate;
$strRunning = 'running on %s' //to translate;
$strRunQuery = 'Submit Query' //to translate;
$strRunSQLQuery = 'Run SQL query/queries on database %s' //to translate;

$strSave = 'Save' //to translate;
$strScaleFactorSmall = 'The scale factor is too small to fit the schema on one page' //to translate;
$strSearch = 'Search' //to translate;
$strSearchFormTitle = 'Search in database' //to translate;
$strSearchInTables = 'Inside table(s):' //to translate;
$strSearchNeedle = 'Word(s) or value(s) to search for (wildcard: "%"):' //to translate;
$strSearchOption1 = 'at least one of the words' //to translate;
$strSearchOption2 = 'all words' //to translate;
$strSearchOption3 = 'the exact phrase' //to translate;
$strSearchOption4 = 'as regular expression' //to translate;
$strSearchResultsFor = 'Search results for "<i>%s</i>" %s:' //to translate;
$strSearchType = 'Find:' //to translate;
$strSelect = 'Select' //to translate;
$strSelectADb = 'Please select a database' //to translate;
$strSelectAll = 'Select All' //to translate;
$strSelectFields = 'Select fields (at least one):' //to translate;
$strSelectNumRows = 'in query' //to translate;
$strSelectTables = 'Select Tables' //to translate;
$strSend = 'Save as file' //to translate;
$strServerChoice = 'Server Choice' //to translate;
$strServerVersion = 'Server version' //to translate;
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').' //to translate;
$strShow = 'Show' //to translate;
$strShowAll = 'Show all' //to translate;
$strShowColor = 'Show color' //to translate;
$strShowCols = 'Show columns' //to translate;
$strShowGrid = 'Show grid' //to translate;
$strShowingRecords = 'Showing rows' //to translate;
$strShowTableDimension = 'Show dimension of tables' //to translate;
$strShowTables = 'Show tables' //to translate;
$strShowThisQuery = ' Show this query here again ' //to translate;
$strSingly = '(singly)' //to translate;
$strSize = 'Size' //to translate;
$strSort = 'Sort' //to translate;
$strSpaceUsage = 'Space usage' //to translate;
$strSplitWordsWithSpace = 'Words are separated by a space character (" ").' //to translate;
$strSQL = 'SQL' //to translate;
$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:' //to translate;
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem' //to translate;
$strSQLQuery = 'SQL-query' //to translate;
$strSQLResult = 'SQL result' //to translate;
$strSQPBugInvalidIdentifer = 'Invalid Identifer' //to translate;
$strSQPBugUnclosedQuote = 'Unclosed quote' //to translate;
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String' //to translate;
$strStatement = 'Statements' //to translate;
$strStrucCSV = 'CSV data' //to translate;
$strStrucData = 'Structure and data' //to translate;
$strStrucDrop = 'Add \'drop table\'' //to translate;
$strStrucExcelCSV = 'CSV for Ms Excel data' //to translate;
$strStrucOnly = 'Structure only' //to translate;
$strStructPropose = 'Propose table structure' //to translate;
$strStructure = 'Structure' //to translate;
$strSubmit = 'Submit' //to translate;
$strSuccess = 'Your SQL-query has been executed successfully' //to translate;

$strTableComments = 'Table comments' //to translate;
$strTableEmpty = 'The table name is empty!' //to translate;
$strTableHasBeenDropped = 'Table %s has been dropped' //to translate;
$strTableHasBeenEmptied = 'Table %s has been emptied' //to translate;
$strTableHasBeenFlushed = 'Table %s has been flushed' //to translate;
$strTableMaintenance = 'Table maintenance' //to translate;
$strTables = '%s table(s)' //to translate;
$strTableStructure = 'Table structure for table' //to translate;
$strTableType = 'Table type' //to translate;
$strTextAreaLength = ' Because of its length,<br /> this field might not be editable ' //to translate;
$strTheContent = 'The content of your file has been inserted.' //to translate;
$strTheContents = 'The contents of the file replaces the contents of the selected table for rows with identical primary or unique key.' //to translate;
$strTheTerminator = 'The terminator of the fields.' //to translate;
$strTotal = 'total' //to translate;
$strType = 'Type' //to translate;

$strUncheckAll = 'Uncheck All' //to translate;
$strUnique = 'Unique' //to translate;
$strUnselectAll = 'Unselect All' //to translate;
$strUpdatePrivMessage = 'You have updated the privileges for %s.' //to translate;
$strUpdateProfile = 'Update profile:' //to translate;
$strUpdateProfileMessage = 'The profile has been updated.' //to translate;
$strUpdateQuery = 'Update Query' //to translate;
$strUsage = 'Usage' //to translate;
$strUseBackquotes = 'Enclose table and field names with backquotes' //to translate;
$strUserEmpty = 'The user name is empty!' //to translate;

$strValidateSQL = 'Validate SQL' //to translate;
$strViewDump = 'View dump (schema) of table' //to translate;
$strViewDumpDB = 'View dump (schema) of database' //to translate;

$strWithChecked = 'With selected:' //to translate;
$strWrongUser = 'Wrong username/password. Access denied.' //to translate;

$strZip = '"zipped"' //to translate;
?>
