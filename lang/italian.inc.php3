<?php
/* $Id$ */
/* Pietro Danesi <danone@aruba.it>  07.09.2001 */

$charset = 'iso-8859-1';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'); //italian days
$month = array('Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'); //italian months
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d %B, %Y at %I:%M %p'; //italian time


$strAccessDenied = 'Accesso negato';
$strAction = 'Azione';
$strAddDeleteColumn = 'Aggiungi/Cancella campo';
$strAddDeleteRow = 'Aggiungi/Cancella criterio';
$strAddNewField = 'Aggiungi un nuovo campo';
$strAddPriv = 'Aggiungi un nuovo privilegio';
$strAddPrivMessage = 'Hai aggiunto un nuovo privilegio.';
$strAddSearchConditions = 'Aggiungi condizioni di ricerca (corpo della clausola "where"):';
$strAddUser = 'Aggiungi un nuovo utente';
$strAddUserMessage = 'Hai aggiunto un nuovo utente.';
$strAffectedRows = 'Righe affette:';
$strAfter = 'Dopo';
$strAll = 'Tutti';
$strAlterOrderBy = 'Altera tabella ordinata per';
$strAnalyzeTable = 'Analizza tabella';
$strAnd = 'E';
$strAnIndex = 'E\' stato aggiunto un indice per ';
$strAny = 'Qualsiasi';
$strAnyColumn = 'Qualsiasi colonna';
$strAnyDatabase = 'Qualsiasi database';
$strAnyHost = 'Qualsiasi host';
$strAnyTable = 'Qualsiasi tabella';
$strAnyUser = 'Qualsiasi utente';
$strAPrimaryKey = 'E\' stata creata una chiave primaria per ';
$strAscending = 'Crescente';
$strAtBeginningOfTable = 'All\'inizio della tabella';
$strAtEndOfTable = 'Alla fine della tabella';
$strAttr = 'Attributi';

$strBack = 'Indietro';
$strBinary = 'Binario';
$strBinaryDoNotEdit = 'Tipo di dato Binario - non modificare';
$strBookmarkLabel = 'Etichetta';
$strBookmarkQuery = 'Query SQL aggiunte ai preferiti';
$strBookmarkThis = 'Aggiungi ai preferiti questa query SQL';
$strBookmarkView = 'Visualizza solo';
$strBrowse = 'Mostra';
$strBzip = '"compresso con bzip2"';

$strCantLoadMySQL = 'impossibile caricare l\'estensione MySQL,<br />controlla la configurazione di PHP.';
$strCarriage = 'Ritorno carrello: \\r';
$strChange = 'Modifica';
$strCheckAll = 'Seleziona tutti';
$strCheckDbPriv = 'Controlla i privilegi del database';
$strCheckTable = 'Controlla tabella';
$strColumn = 'Colonna';
$strColumnEmpty = 'Il nome della colonna è vuoto!';
$strColumnNames = 'Nomi delle colonne';
$strCompleteInserts = 'Inserimenti completi';
$strConfirm = 'Sicuro di volerlo fare?';
$strCopyTable = 'Copia la tabella su';
$strCopyTableOK = 'La tabella %s è stata copiata su %s.';
$strCreate = 'Crea';
$strCreateNewDatabase = 'Crea un nuovo database';
$strCreateNewTable = 'Crea una nuova tabella nel database ';
$strCriteria = 'Criterio';

$strData = 'Dati';
$strDatabase = 'Database ';
$strDatabases = 'database';
$strDatabasesStats = 'Statistiche dei databases';
$strDataOnly = 'Solo dati';
$strDbEmpty = 'Il nome del database è vuoto!';
$strDefault = 'Predefinito';
$strDelete = 'Cancella';
$strDeleted = 'La riga è stata cancellata';
$strDeletedRows = 'Righe cancellate:';
$strDeleteFailed = 'Cancellazione fallita!';
$strDeletePassword = 'Cancella Password';
$strDeleteUserMessage = 'Hai cancellato l\'utente';
$strDelPassMessage = 'Hai cancellato la password per';
$strDescending = 'Decrescente';
$strDisableMagicQuotes = '<b>Attenzione:</b> Hai l\'opzione \'magic_quotes_gpc\' abilitata nel file di configurazione di PHP. Questa versione di PhpMyAdmin non funziona correttamente con essa. Fai riferimento alla sezione relativa alla configurazione del manuale PHP per informazioni su come disabilitarla.';
$strDisplay = 'Visualizza';
$strDisplayOrder = 'Ordine di visualizzazione:';
$strDoAQuery = 'Esegui "query da esempio" (carattere jolly: "%")';
$strDocu = 'Documentazione';
$strDoYouReally = 'Confermi: ';
$strDrop = 'Elimina';
$strDropDB = 'Elimina database ';
$strDropTable = 'Elimina table';
$strDumpingData = 'Dump dei dati per la tabella';
$strDynamic = 'dinamico';

$strEdit = 'Modifica';
$strEditPrivileges = 'Modifica Privilegi';
$strEffective = 'Effettivo';
$strEmpty = 'Svuota';
$strEmptyResultSet = 'MySQL ha restituito un insieme vuoto (i.e. zero righe).';
$strEnableMagicQuotes = '<b>Attenzione:</b> Non hai abilitato l\'opzione \'magic_quotes_gpc\' nella configurazione del PHP. PhpMyAdmin ne ha bisogno per funzionare correttamente. Fai riferimento alla sezione relativa alla configurazione del manuale PHP per informazioni su come abilitala.';
$strEnclosedBy = 'delimitati da';
$strEnd = 'Fine';
$strEnglishPrivileges = 'Nota: i nomi dei privilegi di MySQL sono in Inglese';
$strError = 'Errore';
$strEscapedBy = 'con escape';
$strExtendedInserts = 'Inserimenti estesi';
$strExtra = 'Extra';

$strField = 'Campo';
$strFields = 'Campi';
$strFieldsEmpty = ' Il contatore dei campi è vuoto! ';
$strFixed = 'fisso';
$strFormat = 'Formato';
$strFormEmpty = 'Valore mancante nel form!';
$strFullText = 'Testo completo';
$strFunction = 'Funzione';

$strGenTime = 'Generato il';
$strGo = 'Esegui';
$strGrants = 'Permetti';
$strGzip = '"compresso con gzip"';

$strHasBeenAltered = 'è stato modificato.';
$strHasBeenCreated = 'è stato creato.';
$strHasBeenDropped = 'è stato eliminato.';
$strHasBeenEmptied = 'è stato svuotato.';
$strHome = 'Home';
$strHomepageOfficial = 'Home page ufficiale di phpMyAdmin';
$strHomepageSourceforge = 'Home page di phpMyAdmin su sourceforge.net';
$strHost = 'Host';
$strHostEmpty = 'Il nome di host è vuoto!';

$strIdxFulltext = 'Testo completo';
$strIfYouWish = 'Per caricare i dati solo per alcune colonne della tabella, specificare la lista dei campi (separati da virgole).';
$strIndex = 'Indice';
$strIndexes = 'Indici';
$strInsert = 'Inserisci';
$strInsertAsNewRow = 'Inserisci come nuova riga';
$strInsertedRows = 'Righe inserite:';
$strInsertIntoTable = 'Inserisci nella tabella';
$strInsertNewRow = 'Inserisci una nuova riga';
$strInsertTextfiles = 'Inserisci un file di testo nella tabella';
$strInstructions = 'Istruzioni';
$strInUse = 'in uso';
$strInvalidName = '"%s" &egrave; una parola riservata; non &egrave; possibile utilizzarla come nome di database/tabella/campo.';

$strKeyname = 'Nome chiave';
$strKill = 'Uccidi';

$strLength = 'Lunghezza';
$strLengthSet = 'Lunghezza/Set*';
$strLimitNumRows = 'record per pagina';
$strLineFeed = 'Fine riga: \\n';
$strLines = 'Record';
$strLocationTextfile = 'Percorso del file';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Disconnetti';

$strModifications = 'Le modifiche sono state salvate';
$strModify = 'Modifica';
$strMySQLReloaded = 'MySQL riavviato.';
$strMySQLSaid = 'Messaggio di MySQL: ';
$strMySQLShowProcess = 'Visualizza processi in esecuzione';
$strMySQLShowStatus = 'Visualizza informazioni di runtime di MySQL';
$strMySQLShowVars = 'Visualizza variabili di sistema di MySQL';

$strName = 'Nome';
$strNbRecords = 'n. di record';
$strNext = 'Prossimo';
$strNo = ' No ';
$strNoDatabases = 'Nessun database';
$strNoDropDatabases = 'I comandi "DROP DATABASE" sono disabilitati.';
$strNoModification = 'Nessun cambiamento';
$strNoPassword = 'Nessuna Password';
$strNoPrivileges = 'Nessun Privilegio';
$strNoRights = 'Non hai i permessi per effettuare questa operazione!';
$strNoTablesFound = 'Non ci sono tabelle nel database.';
$strNotNumber = 'Questo non è un numero!';
$strNotValidNumber = ' non è una riga valida!';
$strNoUsersFound = 'Nessun utente trovato.';
$strNull = 'Null';
$strNumberIndexes = 'Numero di indici avanzati';

$strOffSet = 'Spiazzamento';
$strOftenQuotation = 'In genere da doppi apici (virgolette). OPZIONALE indica che solo i campi <I>char</I> e <I>varchar</I> devono essere delimitati dal carattere indicato.';
$strOptimizeTable = 'Ottimizza tabella';
$strOptionalControls = 'Opzionale. Questo carattere controlla come scrivere o leggere i caratteri speciali.';
$strOptionally = 'OPZIONALE';
$strOr = 'O';
$strOverhead = 'In eccesso';

$strPartialText = 'Testo parziale';
$strPassword = 'Password';
$strPasswordEmpty = 'La password è vuota!';
$strPasswordNotSame = 'La password non coincide!';
$strPHPVersion = 'Versione PHP';
$strPmaDocumentation = 'Documentazione di phpMyAdmin';
$strPos1 = 'Inizio';
$strPrevious = 'Precedente';
$strPrimary = 'Primaria';
$strPrimaryKey = 'Chiave primaria';
$strPrinterFriendly = 'Visulizzazione <I>per stampa</I> della tabella precedente';
$strPrintView = 'Visualizza per stampa';
$strPrivileges = 'Privilegi';
$strProducedAnError = 'ha causato un errore.';
$strProperties = 'Proprietà';

$strQBE = 'Query da esempio';
$strQBEDel = 'Cancella';
$strQBEIns = 'Aggiungi';
$strQueryOnDb = 'SQL-query sul database ';

$strReadTheDocs = 'Leggere la docomentazione';
$strRecords = 'Record';
$strReloadFailed = 'Riavvio di MySQL fallito.';
$strReloadMySQL = 'Riavvia MySQL';
$strRememberReload = 'Ricorda di riavviare MySQL.';
$strRenameTable = 'Rinomina la tabella in';
$strRenameTableOK = 'La tabella %s è stata rinominata %s';
$strRepairTable = 'Ripara tabella';
$strReplace = 'Sostituisci';
$strReplaceTable = 'Sostituisci i dati della tabella col file';
$strReset = 'Riavvia';
$strReType = 'Reinserisci';
$strRevoke = 'Revoca';
$strRevokeGrant = 'Revoca permessi';
$strRevokeGrantMessage = 'Hai revocato i privilegi di permesso per';
$strRevokeMessage = 'Hai revocato i privilegi per';
$strRevokePriv = 'Revoca privilegi';
$strRowLength = 'Lunghezza riga';
$strRows = 'Righe';
$strRowsFrom = 'righe a partire da';
$strRowSize = 'Dimensione riga';
$strRowsStatistic = 'Statistiche righe';
$strRunning = 'in esecuzione su ';
$strRunQuery = 'Invia Query';
$strRunSQLQuery = 'Esegui una/più query SQL sul database ';

$strSave = 'Salva';
$strSelect = 'Seleziona';
$strSelectFields = 'Seleziona campi (almeno uno):';
$strSelectNumRows = 'nella query';
$strSend = 'Invia';
$strSequence = 'Sequenza';
$strServerChoice = 'Scelta del server';
$strServerVersion = 'Versione MySQL';
$strSetEnumVal = 'Se il tipo di campo è "enum" o "set", immettere i valori usando il formato: \'a\',\'b\',\'c\'...<br />Se comunque dovete mettere dei backslashes ("\") o dei single quote ("\'") davanti a questi valori, backslashateli (per esempio \'\\\\xyz\' o \'a\\\'b\').';
$strShow = 'Mostra';
$strShowingRecords = 'Visualizzazione record ';
$strShowPHPInfo = 'Mostra le info sul PHP';
$strShowThisQuery = 'Mostra questa query di nuovo';
$strSingly = '(singolarmente)';
$strSize = 'Dimensione';
$strSort = 'Ordinamento';
$strSpaceUsage = 'Spazio utilizzato';
$strSQLQuery = 'query SQL';
$strStatement = 'Istruzioni';
$strStrucCSV = 'dati CSV';
$strStrucData = 'Struttura e dati';
$strStrucDrop = 'Aggiungi \'drop table\'';
$strStrucExcelCSV = 'CSV per dati Ms Excel';
$strStrucOnly = 'Solo struttura';
$strSubmit = 'Invia';
$strSuccess = 'La query è stata eseguita con successo';
$strSum = 'Totali';

$strTable = 'tabella ';
$strTableComments = 'Commenti sulla tabella';
$strTableEmpty = 'Il nome della tabella è vuoto!';
$strTableMaintenance = 'Amministrazione tabella';
$strTables = '%s tabella(e)';
$strTableStructure = 'Struttura della tabella';
$strTableType = 'Tipo tabella';
$strTerminatedBy = 'terminati da';
$strTextAreaLength = ' A causa della sua lunghezza,<br /> questo campo non può essere modificato ';
$strTheContent = 'Il contenuto del file è stato inserito.';
$strTheContents = 'Il contenuto del file sostituisce le righe della tabella con la stessa chiave primaria o chiave unica.';
$strTheTerminator = 'Il carattere terminatore dei campi.';
$strTotal = 'Totali';
$strType = 'Tipo';

$strUncheckAll = 'Deseleziona tutti';
$strUnique = 'Unica';
$strUpdatePassMessage = 'Hai aggiornato la password per';
$strUpdatePassword = 'Aggiorna Password';
$strUpdatePrivMessage = 'Hai aggiornato i permessi per';
$strUpdateQuery = 'Aggiorna Query';
$strUsage = 'Utilizzo';
$strUseBackquotes = 'Usa i backquotes con i nomi delle tabelle e dei campi';
$strUser = 'Utente';
$strUserEmpty = 'Il nome utente è vuoto!';
$strUserName = 'Nome utente';
$strUsers = 'Utenti';
$strUseTables = 'Utilizza tabelle';

$strValue = 'Valore';
$strViewDump = 'Visualizza dump (schema) della tabella';
$strViewDumpDB = 'Visualizza dump (schema) del database';

$strWelcome = 'Benvenuto in ';
$strWithChecked = 'Se selezionati:';
$strWrongUser = 'Nome utente o password errati. Accesso negato.';

$strYes = ' Si ';
?>
