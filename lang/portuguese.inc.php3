<?php
/* $Id$ */

$charset = 'iso-8859-1';
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


$strAccessDenied = 'Acesso Negado';
$strAction = 'Ações';
$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate (tbl_qbe.php3)
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate (tbl_qbe.php3)
$strAddNewField = 'Adiciona novo campo';
$strAddPriv = 'Add a new Privilege'; //to translate
$strAddPrivMessage = 'You have added a new privilege.'; //to translate
$strAddSearchConditions = 'Condição de Pesquisa (Complemento da clausula "where"):';
$strAddUser = 'Add a new User'; //to translate
$strAddUserMessage = 'You have added a new user.'; //to translate
$strAfter = 'After'; //to translate
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
$strAtBeginningOfTable = 'At Beginning of Table'; //to translate
$strAtEndOfTable = 'At End of Table'; //to translate
$strAttr = 'Atributos';

$strBack = 'Voltar';
$strBookmarkLabel = 'Label'; //to translate
$strBookmarkQuery = 'Bookmarked SQL-query'; //to translate
$strBookmarkThis = 'Bookmark this SQL-query'; //to translate
$strBookmarkView = 'View only'; //to translate
$strBrowse = 'Visualiza';

$strCantLoadMySQL = 'cannot load MySQL extension,<br />please check PHP Configuration.'; //to translate
$strCarriage = 'Carriage return: \\r';
$strChange = 'Muda';
$strCheckAll = 'Check All'; //to translate
$strCheckDbPriv = 'Check Database Privileges'; //to translate
$strCheckTable = 'Check table'; //to translate
$strColumn = 'Column'; //to translate
$strColumnNames = 'Nome da Colunas';
$strCompleteInserts = 'Complete inserts'; //to translate
$strConfirm = 'Do you really want to do it?'; //to translate
$strCopyTableOK = 'Tabela %s copiada para %s.';
$strCreate = 'Cria';
$strCreateNewDatabase = 'Cria novo banco de dados';
$strCreateNewTable = 'Cria nova tabela no banco de dados ';
$strCriteria = 'Criteria'; //to translate (tbl_qbe.php3)

$strData = 'Data'; //to translate
$strDatabase = 'Banco de Dados ';
$strDatabases = 'Banco de Dados';
$strDataOnly = 'Data only'; //to translate
$strDefault = 'Default';
$strDelete = 'Remove';
$strDeleted = 'Registro eliminado';
$strDeleteFailed = 'Deleted Failed!'; //to translate
$strDescending = 'Desending'; //to translate (tbl_qbe.php3)
$strDisplay = 'Display'; //to translate
$strDoAQuery = 'Faça uma "query by example" (wildcard: "%")';
$strDocu = 'Documentação';
$strDoYouReally = 'Confirma : ';
$strDrop = 'Elimina';
$strDropDB = 'Elimina o banco de dados: ';
$strDumpingData = 'Extraindo dados da tabela';
$strDynamic = 'dynamic'; //to translate

$strEdit = 'Edita';
$strEditPrivileges = 'Edit Privileges'; //to translate
$strEffective = 'Effective'; //to translate
$strEmpty = 'Limpa';
$strEmptyResultSet = 'MySQL retornou um set vazio (ex. zero regs).';
$strEnd = 'Fim';
$strError = 'Erro';
$strExtra = 'Extra'; //to translate

$strField = 'Campo';
$strFields = 'Qtd Campos';
$strFixed = 'fixed'; //to translate
$strFormat = 'Format'; //to translate
$strFunction = 'Funçoes';

$strGenTime = 'Generation Time'; //to translate
$strGo = 'Executa';
$strGrants = 'Grants'; //to translate

$strHasBeenAltered = 'foi alterado.';
$strHasBeenCreated = 'foi criado.';
$strHome = 'Home';
$strHomepageOfficial = 'Official phpMyAdmin Homepage'; //to translate
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download Page'; //to translate
$strHost = 'Host';
$strHostEmpty = 'The host name is empty!'; //to translate

$strIfYouWish = 'Para carregar apenas algumas colunas da tabela, faça uma lista separada por virgula.';
$strIndex = 'Index';
$strIndexes = 'Indexes'; //to translate
$strInsert = 'Insere';
$strInsertAsNewRow = 'Insert as new row'; //to translate
$strInsertNewRow = 'Insere novo registro';
$strInsertTextfiles = 'Insere arquivo texto na tabela';
$strInUse = 'in use'; //to translate

$strKeyname = 'Keyname';
$strKill = 'Kill'; //to translate

$strLength = 'Length'; //to translate
$strLengthSet = 'Tamanho/Set*';
$strLimitNumRows = 'records per page'; //to translate
$strLineFeed = 'Linefeed: \\n';
$strLines = 'Linhas';
$strLocationTextfile = 'Localização do arquivo textos';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Log out';

$strModifications = 'Modificações foram salvas';
$strModify = 'Modify'; //to translate (tbl_qbe.php3)
$strMySQLReloaded = 'MySQL reiniciado.';
$strMySQLSaid = 'Mensagens do MySQL : ';
$strMySQLShowProcess = 'Mostra os Processos';
$strMySQLShowStatus = 'Mostra informação de runtime do MySql';
$strMySQLShowVars = 'Mostra variáveis de sistema do MySQL';

$strName = 'Nome';
$strNext = 'Próximo';
$strNo = 'Não';
$strNoPassword = 'No Password'; //to translate
$strNoPrivileges = 'No Privileges'; //to translate
$strNoRights = 'You don\'t have enough rights to be here right now!'; //to translate
$strNoTablesFound = 'Nenhuma table encontrada no banco de dados';
$strNoUsersFound = 'No user(s) found.'; //to translate
$strNull = 'Null'; //to translate
$strNumberIndexes = ' Number of advanced indexes '; //to translate

$strOftenQuotation = 'Often quotation marks. OPTIONALLY means that only char and varchar fields are enclosed by the "enclosed by"-character.'; //to translate
$strOptimizeTable = 'Optimize table'; //to translate
$strOptionalControls = 'Optional. Controls how to write or read special characters.'; //to translate
$strOptionally = 'OPTIONALLY';
$strOr = 'Or'; //to translate
$strOverhead = 'Overhead'; //to translate

$strPassword = 'Password'; //to translate
$strPasswordEmpty = 'The password is empty!'; //to translate
$strPasswordNotSame = 'The passwords aren\'t the same!'; //to translate
$strPHPVersion = 'PHP Version'; //to translate
$strPos1 = 'Inicio';
$strPrevious = 'Anterior';
$strPrimary = 'Primary';
$strPrimaryKey = 'Chave Primária';
$strPrintView = 'Print view'; //to translate
$strPrivileges = 'Privileges'; //to translate
$strProperties = 'Propriedades';

$strQBE = 'Query by Example';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)

$strRecords = 'Registros';
$strReloadFailed = 'Reinicialização do MySQL falhou.';
$strReloadMySQL = 'Reinicializa o MySQL';
$strRememberReload = 'Remember reload the server.'; //to translate
$strRenameTable = 'Renomeia a tabela para ';
$strRenameTableOK = 'Tabela %s renomeada para %s';
$strRepairTable = 'Repair table'; //to translate
$strReplace = 'Substituir';
$strReplaceTable = 'Substituir os dados da tabela pelos do arquivo';
$strReset = 'Reset';
$strReType = 'Re-type'; //to translate
$strRevoke = 'Revoke'; //to translate
$strRevokeGrant = 'Revoke Grant'; //to translate
$strRevokeGrantMessage = 'You have revoked the Grant privilege for %s'; //to translate
$strRevokeMessage = 'You have revoked the privileges for %s'; //to translate
$strRevokePriv = 'Revoke Privileges'; //to translate
$strRowLength = 'Row length'; //to translate
$strRows = 'Rows'; //to translate
$strRowsFrom = 'rows starting from'; //to translate
$strRowsStatistic = 'Row Statistic'; //to translate
$strRunning = 'Rodando em ';
$strRunQuery = 'Submit Query'; //to translate (tbl_qbe.php3)

$strSave = 'Salva';
$strSelect = 'Seleciona';
$strSelectFields = 'Seleciones os campos (no mínimo 1)';
$strSelectNumRows = 'in query'; //to translate
$strSend = 'envia';
$strSequence = 'Seq.'; //to translate
$strServerVersion = 'Server version'; //to translate
$strShow = 'Show'; //to translate
$strShowingRecords = 'Mostrando registros ';
$strSingly = '(singly)'; //to translate
$strSize = 'Size'; //to translate
$strSort = 'Sort'; //to translate (tbl_qbe.php3)
$strSpaceUsage = 'Space usage'; //to translate
$strSQLQuery = 'SQL-query';
$strStatement = 'Statements'; //to translate
$strStrucCSV = 'Dados CSV';
$strStrucData = 'Estrutura e dados';
$strStrucDrop = 'Adiciona \'drop table\'';
$strStrucOnly = 'Somente estrutura';
$strSubmit = 'Submete';
$strSuccess = 'Sua SQL-query foi executada com sucesso';
$strSum = 'Sum'; //to translate

$strTable = 'tabela ';
$strTableComments = 'Comentários da tabela';
$strTableEmpty = 'The table name is empty!'; //to translate
$strTableMaintenance = 'Table maintenance'; //to translate
$strTableStructure = 'Estrutura da tabela';
$strTableType = 'Table type'; //to translate
$strTextAreaLength = ' Because of its length,<br /> this field might not be editable '; //to translate
$strTheContent = 'O conteúdo do seu arquivo foi inserido';
$strTheContents = 'O conteúdo do arquivo substituiu o conteúdo da tabela que tinha a mesma chave primária ou única';
$strTheTerminator = 'Terminador de campos.';
$strTotal = 'total';
$strType = 'Tipo';

$strUncheckAll = 'Uncheck All'; //to translate
$strUnique = 'Unique';
$strUpdateQuery = 'Update Query'; //to translate (tbl_qbe.php3)
$strUsage = 'Usage'; //to translate
$strUser = 'User'; //to translate
$strUserEmpty = 'The user name is empty!'; //to translate
$strUserName = 'User name'; //to translate
$strUsers = 'Users'; //to translate
$strUseTables = 'Use Tables'; //to translate (tbl_qbe.php3)

$strValue = 'Value';
$strViewDump = 'Ver o esquema da tabela';
$strViewDumpDB = 'Ver o esquema do banco de dados';

$strWelcome = 'Benvindo ao %s';
$strWrongUser = 'Usuário ou Senha errado. Acesso Negado.';

$strYes = 'Sim';

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
$strDeletedRows = 'Deleted rows:';
$strDeleteUserMessage = 'You have deleted the user %s.';//to translate
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
