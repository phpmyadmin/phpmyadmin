<?php
/* $Id$ */

$charset = 'iso-8859-1';
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


$strAccessDenied = 'Adgang N&aelig;gtet';
$strAction = 'Handling';
$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate (tbl_qbe.php3)
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate (tbl_qbe.php3)
$strAddNewField = 'Tilf&oslash;j nyt felt';
$strAddPriv = 'Add a new Privilege'; //to translate
$strAddPrivMessage = 'You have added a new privilege.'; //to translate
$strAddSearchConditions = 'Tilf&oslash;j s&oslash;ge kriterier (body of the "where" clause):';
$strAddUser = 'Add a new User'; //to translate
$strAddUserMessage = 'You have added a new user.'; //to translate
$strAffectedRows = 'Affected rows:';  //to translate
$strAfter = 'Efter';
$strAll = 'All'; //to translate
$strAlterOrderBy = 'Alter table order by'; //to translate
$strAnalyzeTable = 'Analyser tabel';
$strAnd = 'And'; //to translate (tbl_qbe.php3)
$strAnIndex = 'An index has been added on %s';//to translate
$strAny = 'Any'; //to translate
$strAnyColumn = 'Any Column'; //to translate
$strAnyDatabase = 'Any database'; //to translate
$strAnyHost = 'Any host'; //to translate
$strAnyTable = 'Any table'; //to translate
$strAnyUser = 'Any user'; //to translate
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strAscending = 'Ascending'; //to translate (tbl_qbe.php3)
$strAtBeginningOfTable = 'I begyndelsen af tabel';
$strAtEndOfTable = 'I slutning af tabel';
$strAttr = 'Attributer';

$strBack = 'Tilbage';
$strBinary = ' Binary ';  //to translate
$strBinaryDoNotEdit = ' Binary - do not edit ';  //to translate
$strBookmarkLabel = 'Label'; //to translate
$strBookmarkQuery = 'Bookmarked SQL-query'; //to translate
$strBookmarkThis = 'Bookmark this SQL-query'; //to translate
$strBookmarkView = 'View only'; //to translate
$strBrowse = 'Vis';
$strBzip = '"bzipped"'; //to translate

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.'; //to translate
$strCarriage = 'Carriage return: \\r';
$strChange = '&AElig;ndre';
$strCheckAll = 'Check All'; //to translate
$strCheckDbPriv = 'Check Database Privileges'; //to translate
$strCheckTable = 'Tjek tabel';
$strColumn = 'Column'; //to translate
$strColumnEmpty = 'The columns names are empty!'; //to translate
$strColumnNames = 'Kolonne navne';
$strCompleteInserts = 'Lav komplette inserts';
$strConfirm = 'Do you really want to do it?'; //to translate
$strCopyTable = 'Copy table to (database<b>.</b>table):';  //to translate
$strCopyTableOK = 'Tabellen %s er nu kopieret til: %s.';
$strCreate = 'Opret';
$strCreateNewDatabase = 'Opret ny database';
$strCreateNewTable = 'Opret ny tabel i database ';
$strCriteria = 'Criteria'; //to translate (tbl_qbe.php3)

$strData = 'Data'; //to translate
$strDatabase = 'Database: ';
$strDatabaseHasBeenDropped = 'Database %s has been dropped.';  //to translate
$strDatabases = 'databaser';
$strDatabasesStats = 'Databases statistics';//to translate
$strDataOnly = 'Data only'; //to translate
$strDbEmpty = 'The database name is empty!'; //to translate
$strDefault = 'Standardv&aelig;rdi';
$strDelete = 'Slet';
$strDeleted = 'R&aelig;kken er slettet!';
$strDeletedRows = 'Deleted rows:';  //to translate
$strDeleteFailed = 'Deleted Failed!'; //to translate
$strDeleteUserMessage = 'You have deleted the user %s.';//to translate
$strDescending = 'Desending'; //to translate (tbl_qbe.php3)
$strDisplay = 'Vis';
$strDisplayOrder = 'Display order:';  //to translate
$strDoAQuery = 'K&oslash;r en foresp&oslash;rgsel p&aring; felter (wildcard: "%")';
$strDocu = 'Dokumentation';
$strDoYouReally = 'Er du sikker p&aring; at du vil ';
$strDrop = 'Slet';
$strDropDB = 'Slet database ';
$strDropTable = 'Drop table';  //to translate
$strDumpingData = 'Data dump for tabellen';
$strDynamic = 'dynamic'; //to translate

$strEdit = 'Ret';
$strEditPrivileges = 'Edit Privileges'; //to translate
$strEffective = 'Effective'; //to translate
$strEmpty = 'T&oslash;m';
$strEmptyResultSet = 'MySQL returnerede ingen data (fx ingen r&aelig;kker).';
$strEnd = 'Slut';
$strEnglishPrivileges = ' Note: MySQL privilege names are expressed in English ';  //to translate
$strError = 'Fejl';
$strExtendedInserts = 'Extended inserts';  //to translate
$strExtra = 'Ekstra';

$strField = 'Feltnavn';
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strFields = 'Felter';
$strFieldsEmpty = ' The field count is empty! ';  //to translate
$strFieldsEnclosedBy = 'Fields enclosed by';//to translate
$strFieldsEscapedBy = 'Fields escaped by';//to translate
$strFieldsTerminatedBy = 'Fields terminated by';//to translate
$strFixed = 'fixed'; //to translate
$strFormat = 'Format'; //to translate
$strFormEmpty = 'Missing value in the form !';  //to translate
$strFullText = 'Full Texts';//to translate
$strFunction = 'Funktion';

$strGenTime = 'Generation Time'; //to translate
$strGo = 'Udf&oslash;r';
$strGrants = 'Grants'; //to translate
$strGzip = '"gzipped"';  //to translate

$strHasBeenAltered = 'er &aelig;ndret.';
$strHasBeenCreated = 'er oprettet.';
$strHome = 'Hjem';
$strHomepageOfficial = 'Officiel phpMyAdmin hjemmeside ';
$strHomepageSourceforge = 'Ny phpMyAdmin hjemmeside ';
$strHost = 'Host';
$strHostEmpty = 'The host name is empty!'; //to translate

$strIdxFulltext = 'Fulltext';  //to translate 
$strIfYouWish = 'Hvis du kun &oslash;nsker at importere nogle af tabellens kolonner, angives de med en kommasepereret felt liste.';
$strIndex = 'Indekseret';
$strIndexes = 'Indexes'; //to translate
$strIndexHasBeenDropped = 'Index %s has been dropped';//to translate
$strInsert = 'Inds&aelig;t';
$strInsertAsNewRow = 'Insert as new row'; //to translate
$strInsertedRows = 'Inserted rows:';  //to translate
$strInsertIntoTable = 'Importer til tabel';
$strInsertNewRow = 'Inds&aelig;t ny r&aelig;kke';
$strInsertTextfiles = 'Importer tekstfil til tabellen';
$strInstructions = 'Instructions';//to translate
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.'; //to translate
$strInUse = 'in use'; //to translate

$strKeepPass = 'Do not change the password';//to translate
$strKeyname = 'N&oslash;gle';
$strKill = 'Kill'; //to translate

$strLength = 'Length'; //to translate
$strLengthSet = 'L&aelig;ngde/V&aelig;rdi*';
$strLimitNumRows = 'poster pr. side';
$strLineFeed = 'Linefeed: \\n';
$strLines = 'Linier';
$strLinesTerminatedBy = 'Lines terminated by';//to translate
$strLocationTextfile = 'Tekstfilens placering';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Log af';

$strModifications = 'Rettelserne er gemt!';
$strModify = 'Modify'; //to translate (tbl_qbe.php3)
$strMoveTable = 'Move table to (database<b>.</b>table):';  //to translate
$strMoveTableOK = 'Table %s has been moved to %s.';  //to translate
$strMySQLReloaded = 'MySQL genstartet.';
$strMySQLSaid = 'MySQL returnerede: ';
$strMySQLShowProcess = 'Vis tr&aring;de';
$strMySQLShowStatus = 'Vis MySQL runtime information';
$strMySQLShowVars = 'Vis MySQL system variable';

$strName = 'Navn';
$strNbRecords = 'No. Of records';
$strNext = 'N&aelig;ste';
$strNo = 'Nej';
$strNoDatabases = 'No databases';  //to translate
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.';  //to translate
$strNoModification = 'No change';  //to translate
$strNoPassword = 'No Password'; //to translate
$strNoPrivileges = 'No Privileges'; //to translate
$strNoQuery = 'No SQL query!';  //to translate
$strNoRights = 'You don\'t have enough rights to be here right now!'; //to translate
$strNoTablesFound = 'Ingen tabeller fundet i databasen.';
$strNotNumber = 'This is not a number!';  //to translate
$strNotValidNumber = ' is not a valid row number!';  //to translate
$strNoUsersFound = 'No user(s) found.'; //to translate
$strNull = 'Nulv&aelig;rdi';
$strNumberIndexes = ' Number of advanced indexes '; //to translate

$strOftenQuotation = 'Ofte anf&oslash;relsestegn. OPTIONALLY betyder at kun char og varchar felter er omsluttet med det valgte "tekstkvalifikator"-tegn.';
$strOptimizeTable = 'Optimer tabel';
$strOptionalControls = 'Optional. Kontrollerer hvordan specialtegn skrives eller l&aelig;ses.';
$strOptionally = 'OPTIONALLY';
$strOr = 'Eller';
$strOverhead = 'Overhead'; //to translate

$strPartialText = 'Partial Texts';//to translate
$strPassword = 'Password'; //to translate
$strPasswordEmpty = 'The password is empty!'; //to translate
$strPasswordNotSame = 'The passwords aren\'t the same!'; //to translate
$strPHPVersion = 'PHP Version'; //to translate
$strPmaDocumentation = 'phpMyAdmin Documentation';//to translate 
$strPos1 = 'Start';
$strPrevious = 'Forrige';
$strPrimary = 'Prim&aelig;r';
$strPrimaryKey = 'Prim&aelig;r n&oslash;gle';
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strPrinterFriendly = 'Udskrift venlig version af ovenst&aring;ende tabel';
$strPrintView = 'Vis (udskriftvenlig)';
$strPrivileges = 'Privileges'; //to translate
$strProducedAnError = 'forsagede en fejl.';
$strProperties = 'Egenskaber';

$strQBE = 'Query by Example';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)
$strQueryOnDb = 'SQL-query on database ';  //to translate

$strReadTheDocs = 'L&aelig;s dokumentationen';
$strRecords = 'Poster';
$strReloadFailed = 'Genstart af MySQL fejlede.';
$strReloadMySQL = 'Genstart MySQL';
$strRememberReload = 'Remember reload the server.'; //to translate
$strRenameTable = 'Omd&oslash;b tabel til';
$strRenameTableOK = 'Tabellen %s er nu omd&oslash;bt til: %s';
$strRepairTable = 'Reparer tabel';
$strReplace = 'Erstat';
$strReplaceTable = 'Erstat data i tabellen med filens data';
$strReset = 'Nulstil';
$strReType = 'Re-type'; //to translate
$strRevoke = 'Revoke'; //to translate
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for'; //to translate
$strRevokeMessage = 'You have revoked the privileges for'; //to translate
$strRevokePriv = 'Revoke Privileges'; //to translate
$strRowLength = 'Row length'; //to translate
$strRows = 'Rows'; //to translate
$strRowsFrom = 'r&aelig;kker startende fra';
$strRowSize = ' Row size ';  //to translate
$strRowsStatistic = 'Row Statistic'; //to translate
$strRunning = 'k&oslash;rer p&aring; ';
$strRunningAs = 'as';  //to translate
$strRunQuery = 'Submit Query'; //to translate (tbl_qbe.php3)
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate

$strSave = 'Gem';
$strSelect = 'V&aelig;lg';
$strSelectFields = 'V&aelig;lg mindst eet felt:';
$strSelectNumRows = 'i foresp&oslash;rgsel';
$strSend = 'send';
$strSequence = 'Seq.'; //to translate
$strServerChoice = 'Server Choice';//to translate 
$strServerVersion = 'Server version'; //to translate
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').';  //to translate
$strShow = 'Vis';
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';  //to translate
$strShowingRecords = 'Viser poster ';
$strShowPHPInfo = 'Show PHP information';  // To translate
$strShowTables = 'Show tables';  //to translate
$strShowThisQuery = ' Show this query here again ';  //to translate
$strSingly = '(singly)'; //to translate
$strSize = 'Size'; //to translate
$strSort = 'Sort'; //to translate (tbl_qbe.php3)
$strSpaceUsage = 'Space usage'; //to translate
$strSQLQuery = 'SQL-foresp&oslash;rgsel';
$strStartingRecord = 'Starting record';//to translate
$strStatement = 'Statements'; //to translate
$strStrucCSV = 'CSV data';
$strStrucData = 'Struturen og data';
$strStrucDrop = 'Tilf&oslash;j \'drop table\'';
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strStrucOnly = 'Kun strukturen';
$strSubmit = 'Send';
$strSuccess = 'Din SQL-foresp&oslash;rgsel blev eksekveret korrekt';
$strSum = 'Sum'; //to translate

$strTable = 'Tabel: ';
$strTableComments = 'Tabel kommentarer';
$strTableEmpty = 'The table name is empty!'; //to translate
$strTableHasBeenDropped = 'Table %s has been dropped';//to translate
$strTableHasBeenEmptied = 'Table %s has been emptied';//to translate
$strTableMaintenance = 'Table maintenance'; //to translate
$strTables = '%s table(s)';  //to translate
$strTableStructure = 'Struktur dump for tabellen';
$strTableType = 'Tabel type';
$strTextAreaLength = ' Because of its length,<br /> this field might not be editable '; //to translate
$strTheContent = 'Filens indhold er importeret.';
$strTheContents = 'Filens indhold erstatter den valgte tabels r&aelig;kker for r&aelig;kker med identisk prim&aelig;r eller unik n&oslash;gle.';
$strTheTerminator = 'Felterne afgr&aelig;nses af dette tegn.';
$strTotal = 'total';
$strType = 'Datatype';

$strUncheckAll = 'Uncheck All'; //to translate
$strUnique = 'Unik';
$strUpdatePrivMessage = 'You have updated the privileges for %s.';//to translate
$strUpdateProfile = 'Update profile:';//to translate
$strUpdateProfileMessage = 'The profile has been updated.';//to translate
$strUpdateQuery = 'Update Query'; //to translate (tbl_qbe.php3)
$strUsage = 'Usage'; //to translate
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strUser = 'User'; //to translate
$strUserEmpty = 'The user name is empty!'; //to translate
$strUserName = 'User name'; //to translate
$strUsers = 'Users'; //to translate
$strUseTables = 'Use Tables'; //to translate (tbl_qbe.php3)

$strValue = 'V&aelig;rdi';
$strViewDump = 'Vis dump (skema) over tabel';
$strViewDumpDB = 'Vis dump (skema) af database';

$strWelcome = 'Velkommen til ';
$strWithChecked = 'With checked:';
$strWrongUser = 'Forkert brugernavn/kodeord. Adgang N&aelig;gtet.';

$strYes = 'Ja';

$strZip = '"zipped"'; //to translate

?>
