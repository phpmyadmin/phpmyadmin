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


$strAccessDenied = 'Access denegat';
$strAction = 'Acció';
$strAddDeleteColumn = 'Add/Delete Field Columns'; //to translate (tbl_qbe.php3)
$strAddDeleteRow = 'Add/Delete Criteria Row'; //to translate (tbl_qbe.php3)
$strAddNewField = 'Afegir un camp nou';
$strAddPriv = 'Afegir un privilegi nou'; 
$strAddPrivMessage = 'Has afegit un privilegi nou.'; 
$strAddSearchConditions = 'Afegeix condicions de recerca (cos de la clàusula "where"):';
$strAddUser = 'Afegir un usuari nou';
$strAddUserMessage = 'Has afegit un usuari nou.';
$strAfter = 'Després';
$strAll = 'Tot';
$strAlterOrderBy = 'Altera la taula i ordena per';
$strAnalyzeTable = 'Analitza la taula';
$strAnd = 'And'; //to translate (tbl_qbe.php3)
$strAny = 'Qualsevol';
$strAnyColumn = 'Qualsevol columna'; 
$strAnyDatabase = 'Qualsevol base de dades';
$strAnyHost = 'Qualsevol servidor';
$strAnyTable = 'Qualsevol taula';
$strAnyUser = 'Qualsevol usuari';
$strAscending = 'Ascending'; //to translate (tbl_qbe.php3)
$strAtBeginningOfTable = 'Al principi de la taula';
$strAtEndOfTable = 'Al final de la taula';
$strAttr = 'Atributs';

$strBack = 'Enrere';
$strBinary = ' Binari '; 
$strBinaryDoNotEdit = ' Binari - no editeu ';
$strBookmarkLabel = 'Etiqueta';
$strBookmarkQuery = 'Consulta SQL registrada';
$strBookmarkThis = 'Registra aquesta consulta SQL';
$strBookmarkView = 'Només mirar';
$strBrowse = 'Navega';
$strBzip = '"comprimit amb bzip"';

$strCantLoadMySQL = 'no s\'ha pogut carregar l\'extensió de MySQL,<br />bverifiqueu la configuració del PHP.';
$strCarriage = 'Retorn de línia: \\r';
$strChange = 'Canvi';
$strCheckAll = 'Verificar-ho tot';
$strCheckDbPriv = 'Verifica els privilegis de la base de dades';
$strCheckTable = 'Verifica la taula';
$strColumn = 'Columna';
$strColumnNames = 'Nom de les col&middot;lumnes';
$strCompleteInserts = 'Completar insercions';
$strConfirm = 'Ho vols fer realment ?';
$strCopyTableOK = 'La taula %s ha estat copiada a %s.';
$strCreate = 'Crear';
$strCreateNewDatabase = 'Crea una nova base de dades';
$strCreateNewTable = 'Crear una taula nova a la base de dades ';
$strCriteria = 'Criteria'; //to translate (tbl_qbe.php3)

$strData = 'Dades';
$strDatabase = 'Base de dades ';
$strDatabases = 'bases de dades';
$strDataOnly = 'Data only'; //to translate
$strDefault = 'Defecte';
$strDelete = 'Esborrar';
$strDeleted = 'La fila ha estat esborrada';
$strDeleteFailed = 'No s\'ha pogut esborrar!';
$strDeleteUserMessage = 'Has esborrat l\'usuari %s.';
$strDescending = 'Desending'; //to translate (tbl_qbe.php3)
$strDisplay = 'Mostrar'; 
$strDoAQuery = 'Fer un "petició segons exemple" (comodí: "%")';
$strDocu = 'Documentació';
$strDoYouReally = 'Realment vols ';
$strDrop = 'Eliminar';
$strDropDB = 'Eliminar base de dades ';
$strDumpingData = 'Volcant dades de la taula';
$strDynamic = 'dinàmic';

$strEdit = 'Editar';
$strEditPrivileges = 'Editar privilegis';
$strEffective = 'Efectiu';
$strEmpty = 'Buidar';
$strEmptyResultSet = 'MySQL ha retornat un conjunt buit (p.e. cap fila).';
$strEnd = 'Final';
$strEnglishPrivileges = ' Nota: Els noms dels privilegis del MySQL són en llengua anglesa '; 
$strError = 'Errada';
$strExtra = 'Extra';

$strField = 'Camp';
$strFields = 'Camps';
$strFixed = 'fixa';
$strFormat = 'Format';
$strFunction = 'Funció';

$strGenTime = 'Temps de generació';
$strGo = 'Executar';
$strGrants = 'Atorgar';
$strGzip = '"comprimit amb gzip"'; 

$strHasBeenAltered = 'ha estat alterada.';
$strHasBeenCreated = 'ha estat creada.';
$strHome = 'Inici';
$strHomepageOfficial = 'Plana oficial del phpMyAdmin';
$strHomepageSourceforge = 'Plana de descàrrega del phpMyAdmin a SourceForge';
$strHost = 'Servidor';
$strHostEmpty = 'El nom del servidor ès buit!';

$strIfYouWish = 'Si només vols carregar algunes col.lumnes de la taula, especifica-ho amb una llista separada per comes.';
$strIndex = 'Index';
$strIndexes = 'Indexos'; 
$strInsert = 'Inserta';
$strInsertAsNewRow = 'Insertar com a nova fila'; 
$strInsertNewRow = 'Inserir nova fila';
$strInsertTextfiles = 'Inserir fitxers de text a la taula';
$strInUse = 'en use';

$strKeyname = 'NomClau';
$strKill = 'Finalitzar'; 

$strLength = 'Longitut';
$strLimitNumRows = 'registres per plana'; 
$strLineFeed = 'Salt de línia: \\n';
$strLines = 'Línies';
$strLocationTextfile = 'Ubicació del fitxer de text';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Sortir';

$strModifications = 'Les modificacions han estat guardades';
$strModify = 'Modify'; //to translate (tbl_qbe.php3)
$strMySQLReloaded = 'MySQL reiniciat.';
$strMySQLSaid = 'MySQL diu: ';
$strMySQLShowProcess = 'Mostrar processos';
$strMySQLShowStatus = 'Mostra la informació de funcionament del MySQL';
$strMySQLShowVars = 'Mostra les variables de sistema del MySQL';

$strName = 'Nom';
$strNbRecords = 'Número de files ';
$strNext = 'Següent';
$strNo = 'No';
$strNoPassword = 'Sense contrassenya';
$strNoPrivileges = 'Sense privilegis'; 
$strNoRights = 'No tens prou privilegis per visualitzar aquesta informació!';
$strNoTablesFound = 'Base de dades sense taules.';
$strNotNumber = 'Aquest valor no és un número!';
$strNotValidNumber = ' no es un número de col.lumna vàlid!'; 
$strNoUsersFound = 'No s\'han trobat usuaris.';
$strNull = 'Nul';
$strNumberIndexes = ' Número d\'indexs avançats ';

$strOftenQuotation = 'Marques sintàctiques. OPCIONALMENT vol dir que només els camps de tipus char i varchar van "tancats dins" "-aquest caràcter.';
$strOptimizeTable = 'Optimitza la taula';
$strOptionalControls = 'Opcional. Controla com llegir o escriure caràcters especials.';
$strOptionally = 'OPCIONALMENT';
$strOr = 'O'; 
$strOverhead = 'Overhead';

$strPassword = 'Contrasenya'; 
$strPasswordEmpty = 'La contrasenya és buida!';
$strPasswordNotSame = 'Les contrasenyes no coincideixen!';
$strPHPVersion = 'PHP versió';
$strPos1 = 'Inici';
$strPrevious = 'Anterior';
$strPrimary = 'Primària';
$strPrimaryKey = 'Clau Primària';
$strPrintView = 'Imprimir vista'; 
$strPrivileges = 'Privilegis';
$strProperties = 'Propietats';

$strQBE = 'Consulta segons exemple'; 
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)

$strRecords = 'Registres';
$strReloadFailed = 'El reinici del MySQL ha fallat'; 
$strReloadMySQL = 'Rellegir el MySQL';
$strRememberReload = 'Recorda reiniciar el MySQL';
$strRenameTable = 'Renombrar les taules a';
$strRenameTableOK = 'La taula %s ha canviat de nom. Ara es diu %s';
$strRepairTable = 'Reparar taula'; 
$strReplace = 'Substituïr';
$strReplaceTable = 'Substituïr les dades de la taula pel fitxer ';
$strReset = 'Esborrar';
$strReType = 'Re-escriure';
$strRevoke = 'Revocar'; 
$strRevokeGrant = 'Revocar garantia'; 
$strRevokeGrantMessage = 'Has revocat la garantia de privilegis per a %s';
$strRevokeMessage = 'Has revocat els privilegis per';
$strRevokePriv = 'Revocar privilegis'; 
$strRowLength = 'Longitud de fila';
$strRows = 'Fila'; 
$strRowsFrom = 'Files començant des de';
$strRowSize = ' tamany de fila ';
$strRowsStatistic = 'Estadística de files';
$strRunning = 'funcionant a ';
$strRunQuery = 'Submit Query'; //to translate (tbl_qbe.php3)

$strSave = 'Guardar';
$strSelect = 'Sel&middot;lecciona';
$strSelectFields = 'Sel&middot;lecciona els camps (un com a mínim):';
$strSelectNumRows = 'en consulta';
$strSend = 'enviar';
$strSequence = 'Seq.';
$strServerVersion = 'Versió del servidor';
$strShow = 'Mostra';
$strShowingRecords = 'Mostrant registres: ';
$strSingly = '(singly)'; 
$strSize = 'Mida';
$strSort = 'Sort'; //to translate (tbl_qbe.php3)
$strSpaceUsage = 'Utilització d\'espai';
$strSQLQuery = 'crida SQL';
$strStatement = 'Sentències';
$strStrucCSV = 'dades CSV ';
$strStrucData = 'Estructura i dades';
$strStrucDrop = 'Afegir \'drop table\'';
$strStrucOnly = 'Només l\'estructura';
$strSubmit = 'Enviar';
$strSuccess = 'La vostra comanda SQL ha estat executada amb èxit';
$strSum = 'Suma';

$strTable = 'taula ';
$strTableComments = 'Comentaris de la taula'; 
$strTableEmpty = 'El nom de la taula és buit!'; 
$strTableMaintenance = 'Manteniment de la taula'; 
$strTableStructure = 'Estructura de la taula';
$strTableType = 'Tipus de taula';
$strTextAreaLength = ' A causa de la seva longitud,<br /> aquest camp pot no ser editable '; 
$strTheContent = 'El contingut del fitxer especificat ha estat inserit.';
$strTheContents = 'El contingut del fitxer substituïrà els continguts de les taules sel&middot;leccionades a les files que contenen la mateixa clau única o primària';
$strTheTerminator = 'El separador de camps.';
$strTotal = 'total';
$strType = 'Tipus';

$strUncheckAll = 'Descel.leccionar tot';
$strUnique = 'Única';
$strUpdatePrivMessage = 'Heu actualitzat els privilegis de %s.';
$strUpdateQuery = 'Update Query'; //to translate (tbl_qbe.php3)
$strUsage = 'Ús';
$strUser = 'Usuari';
$strUserEmpty = 'El nom d\'usuari és buit!';
$strUserName = 'Nom d\'usuari';
$strUsers = 'Usuaris';
$strUseTables = 'Use Tables'; //to translate (tbl_qbe.php3)

$strValue = 'Valor';
$strViewDump = 'Veure un esquema de la taula';
$strViewDumpDB = 'Veure l\'esquema de la base de dades';

$strWelcome = 'Benvingut a %s';
$strWrongUser = 'Usuari i/o clau erronis. Access denegat.';

$strYes = 'Si';

$strZip = '"comprimit amb zip"';

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
$strDisplayOrder = 'Display order:';
$strDropTable = 'Drop table';
$strExtendedInserts = 'Extended inserts';
$strFieldsEmpty = ' The field count is empty! ';  //to translate
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
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
$strLengthSet = 'Length/Values*'; //to translate
$strLinesTerminatedBy = 'Lines terminated by';//to translate
$strMoveTable = 'Move table to (database<b>.</b>table):';
$strMoveTableOK = 'Table %s has been moved to %s.';
$strNoDatabases = 'No databases';
$strNoDropDatabases = '"DROP DATABASE" statements are disabled.';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strNoModification = 'No change';
$strNoQuery = 'No SQL query!';  //to translate
$strPartialText = 'Partial Texts';//to translate
$strPmaDocumentation = 'phpMyAdmin Documentation';//to translate 
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strQueryOnDb = 'SQL-query on database ';
$strRunningAs = 'as';
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strServerChoice = 'Server Choice';//to translate 
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').';
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowPHPInfo = 'Show PHP information';  // To translate
$strShowTables = 'Show tables';
$strShowThisQuery = ' Show this query here again ';
$strStartingRecord = 'Starting record';//to translate
$strStrucExcelCSV = 'CSV for Ms Excel data';
$strTableHasBeenDropped = 'Table %s has been dropped';//to translate
$strTableHasBeenEmptied = 'Table %s has been emptied';//to translate
$strTableHasBeenFlushed = 'Table %s has been flushed';
$strTables = '%s table(s)';  //to translate
$strUpdateProfile = 'Update profile:';//to translate
$strUpdateProfileMessage = 'The profile has been updated.';//to translate
$strUseBackquotes = 'Use backquotes with tables and fields\' names';
$strWithChecked = 'With selected:';
?>
