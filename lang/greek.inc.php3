<?php
/* $Id$ */

$charset = 'iso-8859-7';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Κυρ', 'Δευ', 'Τρι', 'Τετ', 'Πεμ', 'Παρ', 'Σαβ');
$month = array('Ιαν', 'Φεβ', 'Μάρ', 'Απρ', 'Μάι', 'Ιούν', 'Ιούλ', 'Αυγ', 'Σεπ', 'Οκτ', 'Νοε', 'Δεκ');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d %B %Y, στις %I:%M %p';


$strAccessDenied = 'Άρνηση Πρόσβασης';
$strAction = 'Ενέργεια';
$strAddDeleteColumn = 'Προσθήκη/Αφαίρεση Στήλης Πεδίου';
$strAddDeleteRow = 'Προσθήκη/Αφαίρεση Γραμμής Κριτηρίων';
$strAddNewField = 'Προσθήκη νέου Πεδίου';
$strAddPriv = 'Προσθήκη νέου Προνομίου';
$strAddPrivMessage = 'Προσθέσατε νέο Προνόμιο.';
$strAddSearchConditions = 'Προσθήκη νέου όρου (σώμα της "where" πρότασης):';
$strAddUser = 'Προσθήκη νέου Χρήστη';
$strAddUserMessage = 'Προσθέσατε ένα νέο χρήστη.';
$strAffectedRows = 'Επηρεαζόμενες γραμμές:';
$strAfter = 'Μετά το';
$strAll = 'Όλα';
$strAlterOrderBy = 'Αλλαγή ταξινόμησης Πίνακα κατά';
$strAnalyzeTable = 'Ανάλυση Πίνακα';
$strAnd = 'Και';
$strAny = 'Οποιοδήποτε';
$strAnyColumn = 'Οποιαδήποτε Στήλη';
$strAnyDatabase = 'Οποιαδήποτε Βάση';
$strAnyHost = 'Οποιοδήποτε Σύστημα';
$strAnyTable = 'Οποιοσδήποτε Πίνακας';
$strAnyUser = 'Οποιοσδήποτε Χρήστης';
$strAscending = 'Αύξουσα';
$strAtBeginningOfTable = 'Στην αρχή το Πίνακα';
$strAtEndOfTable = 'Στο τέλος του Πίνακα';
$strAttr = 'Χαρακτηριστικά';

$strBack = 'Πίσω';
$strBinary = 'Διαδικό';
$strBinaryDoNotEdit = 'Διαδικό - χωρίς δυνατότητα επεξεργασίας';
$strBookmarkLabel = 'Ετικέτα';
$strBookmarkQuery = 'Αποθηκευμένη επερώτηση SQL';
$strBookmarkThis = 'Αποθήκευσε αυτήν την επερώτηση SQL';
$strBookmarkView = 'Μόνο ανάγνωση';
$strBrowse = 'Περιήγηση';
$strBzip = 'συμπίεση «bzip»';

$strCantLoadMySQL = 'δεν μπορεί να φορτωθεί η επέκταση MySQL,<br />παρακαλώ ελέγξτε την ρύθμιση του PHP.';
$strCarriage = 'Χαρακτήρας επιστροφής: \\r';
$strChange = 'Αλλαγή';
$strCheckAll = 'Έλεγχος όλων';
$strCheckDbPriv = 'Έλεγχος προνομίων Βάσης';
$strCheckTable = 'Έλεγχος πίνακα';
$strColumn = 'Στήλη';
$strColumnNames = 'Ονόματα στηλών';
$strCompleteInserts = 'Ολοκληρωμένες εισαγωγές';
$strConfirm = 'Πραγματικά θέλετε να το εκελέσετε;';
$strCopyTableOK = 'Ο Πίνακας %s αντιγράφηκε στο %s.';
$strCreate = 'Δημιουργία';
$strCreateNewDatabase = 'Δημιουργία νέας βάσης';
$strCreateNewTable = 'Δημιουργία νέου πίνακα στη βάση ';
$strCriteria = 'Κριτήρια';

$strData = 'Δεδομένα';
$strDatabase = 'Βάση ';
$strDatabases = 'Βάσεις';
$strDatabasesStats = 'Στατιστικά βάσης';
$strDataOnly = 'Μόνο δεδομένα';
$strDefault = 'Προκαθορισμένο';
$strDelete = 'Διαγραφή';
$strDeleted = 'Η Γραμμή έχει διαγραφεί';
$strDeletedRows = 'Διαγραμμένες Γραμμές:';
$strDeleteFailed = 'Η διαγραφή απέτυχε';
$strDescending = 'Φθίνουσα';
$strDisplay = 'Εμφάνιση';
$strDisplayOrder = 'Σειρά εμφάνισης:';
$strDoAQuery = 'Εκτέλεσε μία «επερώτηση κατά παράδειγμα» (χαρακτήρας μπαλαντέρ "%")';
$strDocu = 'Τεκμηρίωση';
$strDoYouReally = 'Θέλετε πραγματικά να ';
$strDrop = 'Διαγραφή';
$strDropDB = 'Διαγραφή βάσης ';
$strDropTable = 'Διαγραφή πίνακα';
$strDumpingData = 'Άδειασμα δεδομένων του πίνακα';
$strDynamic = 'δυναμικά';

$strEdit = 'Επεξεργασία';
$strEditPrivileges = 'Επεξεργασία Προνομίων';
$strEffective = 'Αποτελεσματικός';
$strEmpty = 'Άδειασμα';
$strEmptyResultSet = 'Η MySQL επέστρεψε ένα άδειο σύνολο αποτελεσμάτων (π.χ. καμμία Γραμμή).';
$strEnd = 'Τέλος';
$strEnglishPrivileges = ' Σημείωση: Τα ονόματα Προνομίων της MySQL εκφράζονται στα Αγγλικά ';
$strError = 'λάθος';
$strExtendedInserts = 'Εκτεταμένες εισαγωγές';
$strExtra = 'Πρόσθετα';

$strField = 'Πεδίο';
$strFields = 'Πεδία';
$strFieldsEmpty = ' Η απαρίθμηση των πεδίων είναι κενή! ';
$strFixed = 'προκαθορισμνου μήκους';
$strFormat = 'Μορφοποίηση';
$strFormEmpty = 'Ελλειπής τιμή στο πεδίο !';
$strFullText = 'Πλήρη κείμενα';
$strFunction = 'Λειτουργία';

$strGenTime = 'Χρόνος δημιουργίας';
$strGo = 'Εκτέλεση';
$strGrants = 'Παραχωρεί';
$strGzip = 'συμπίεση «gzip»';

$strHasBeenAltered = 'έχει αλλαχθεί.';
$strHasBeenCreated = 'έχει δημιουργηθεί.';
$strHome = 'Κενρτική σελίδα';
$strHomepageOfficial = 'Επίσημη σελίδα του phpMyAdmin';
$strHomepageSourceforge = 'Σελίδα του Sourceforge για την απόκτηση του phpMyAdmin';
$strHost = 'Σύστημα';
$strHostEmpty = 'Το όνομα του Συστήματος είναι κενό!';

$strIdxFulltext = 'Πλήρες κείμενο';
$strIfYouWish = 'Αν ενδιαφέρεστε να φορτώσετε μόνο μερικές απο τις στήλες του πίνακα, καθορίστε μία λίστα πεδίων διαχωρισμένα με κόμμα.';
$strIndex = 'Ευρετήριο';
$strIndexes = 'Ευρετήρια';
$strInsert = 'Εισαγωγή';
$strInsertAsNewRow = 'Εισαγωγή ως νέα Γραμμή';
$strInsertedRows = 'Εισαγόμενες γραμμές:';
$strInsertNewRow = 'Εισαγωγή νέας Γραμμής';
$strInsertTextfiles = 'Εισαγωγή αρχείου κειμένου στον πίνακα';
$strInstructions = 'Οδηγίες';
$strInUse = 'σε χρήση';
$strInvalidName = 'Η «%s» είναι δεσμευμένη λέξη, δεν μπορείτε να την χρησιμοποιήσετε ως όνομα για Βάση, Πίνακα ή Πεδίο.';

$strKeyname = 'Όνομα κλειδιού';
$strKill = 'Τερμάτισε';

$strLength = 'Μήκος';
$strLengthSet = 'Μήκος/Τιμές*';
$strLimitNumRows = 'καταχώρηση ανά σελίδα';
$strLineFeed = 'Χαρακτήρας προώθησης γραμμής: \\n';
$strLines = 'Γραμμές';
$strLocationTextfile = 'Τοποθεσία του αρχείου κειμένου';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Αποσύνδεση';

$strModifications = 'Οι αλλαγές αποθηκεύτηκαν';
$strModify = 'Τροποποίηση';
$strMySQLReloaded = 'Η MySQL επαναφορτώθηκε.';
$strMySQLSaid = 'Η MySQL έδωσε: ';
$strMySQLShowProcess = 'Εμφάνιση διεργασιών';
$strMySQLShowStatus = 'Εμφάνιση πληροφορών εκτέλεσης της MySQL';
$strMySQLShowVars = 'Εμφάνιση μεταβλητών της MySQL';

$strName = 'Όνομα';
$strNbRecords = 'Αριθμός Εγγραφών';
$strNext = 'Επόμενο';
$strNo = 'Όχι';
$strNoDatabases = 'Χωρίς βάσεις';
$strNoDropDatabases = 'Οι επερωτήσεις «DROP DATABASE" έχουν απενεργοποιηθεί.';
$strNoModification = 'Χωρίς αλλαγή';
$strNoPassword = 'Χωρίς Κωδικό Πρόσβασης';
$strNoPrivileges = 'Χωρίς Προνόμια';
$strNoRights = 'Δεν έχετε αρκετά δικαιώματα να είσαστε εδώ τώρα!';
$strNoTablesFound = 'Δεν βρέθηκαν Πίνακες στη βάση.';
$strNotNumber = 'Αυτό δεν είναι αριθμός!';
$strNotValidNumber = ' δεν είναι υπαρκτός αριθμός Γραμμής!';
$strNoUsersFound = 'Δεν βρέθηκαν χρήστες.';
$strNull = 'Κενό';
$strNumberIndexes = ' Αριθμός των προηγμένων ευρετηρίων ';

$strOftenQuotation = 'Συχνά εισαγωγικά. Το OPTIONALLY σημαίνει ότι μόνο τα πεδία char και varchar εμπεριέχονται με τον χαρακτήρα «περιστοιχίζεται από».';
$strOptimizeTable = 'Βελτιστοποίηση Πίνακα';
$strOptionalControls = 'Προεραιτικό. Ρυθμίζει πώς να γίνεται η ανάγνωση και η εγγραφή ειδικών χαρακτήρων.';
$strOptionally = 'OPTIONALLY';
$strOr = 'Ή';
$strOverhead = 'Επιβάρυνση';

$strPartialText = 'Επιμέρους κείμενα';
$strPassword = 'Κωδικός Πρόσβασης';
$strPasswordEmpty = 'Ο Κωδικός Πρόσβασης είναι κενός!';
$strPasswordNotSame = 'Οι κωδικοί πρόσβασαη δεν είναι ίδιοι!';
$strPHPVersion = 'Έκδοση PHP';
$strPmaDocumentation = 'Τεκμηρίωση phpMyAdmin';
$strPos1 = 'Αρχή';
$strPrevious = 'Προηγούμενο';
$strPrimary = 'Πρωτεύον';
$strPrimaryKey = 'Πρωτεύον κλειδί';
$strPrintView = 'Εμφάνιση για εκτύπωση';
$strPrivileges = 'Προνόμια';
$strProperties = 'Ιδιότητες';

$strQBE = 'Επερώτηση κατά παράδειγμα';
$strQBEDel = 'Διαγραφή';
$strQBEIns = 'Εισαγωγή';
$strQueryOnDb = 'Επερώτηση SQL στη βάση ';

$strRecords = 'Εγγραφές';
$strReloadFailed = 'Η επαναφόρτωση της MySQL απέτυχε.';
$strReloadMySQL = 'Επαναφόρτωση της MySQL';
$strRememberReload = 'Ενθύμιση της επαναφόρτωσης του διακομιστή.';
$strRenameTable = 'Μετονομασία Πίνακα σε';
$strRenameTableOK = 'Ο Πίνακας %s μετονομάσθηκε σε %s';
$strRepairTable = 'Επιδιόρθωση πίνακα';
$strReplace = 'Αντικατάσταση';
$strReplaceTable = 'Αντικατάσταση δεδομένων Πίνακα με το αρχείο';
$strReset = 'Επαναφορά';
$strReType = 'Επαναεισαγωγή';
$strRevoke = 'Ανάκληση';
$strRevokeGrant = 'Ανάκληση Παραχώρισης';
$strRevokeGrantMessage = 'Ανακαλέσατε τα προνόμια Παραχώρισης του';
$strRevokeMessage = 'Ανακαλέσατε τα προνόμια για';
$strRevokePriv = 'Ανάκληση προνομοίων';
$strRowLength = 'Μέγεθος Γραμμής';
$strRows = 'Γραμμές';
$strRowsFrom = 'Γραμμές που αρχίζουν από';
$strRowSize = ' Μέγεθος Γραμμής ';
$strRowsStatistic = 'Στατιστικά Γραμμών';
$strRunning = 'που τρέχει στο ';
$strRunQuery = 'Υποβολή επερώτησης';

$strSave = 'Αποθήκευση';
$strSelect = 'Επιλογή';
$strSelectFields = 'Επιλογή πεδίων (τουλάχιστον ένα):';
$strSelectNumRows = 'στην επερώτηση';
$strSend = 'Αποστολή';
$strSequence = 'Ακολουθία';
$strServerChoice = 'Επιλογή Διακομιστή';
$strServerVersion = 'Έκδοση Διακομιστή';
$strSetEnumVal = 'Αν ο τύπος του πεδίου είναι «enum» ή «set», παρακαλώ εισάγετε τις τιμές χρησιμοποιώντας την εξής μορφοποίηση: \'α\',\'β\',\'γ\'...<br /> Αν χρειάζεται να εισάγετε την ανάποδη κάθετο ("\") ή απλά εισαγωγικά ("\'"), προθέστε τα με ανάποδη κάθετο στην αρχή (για παραειγμα \'\\\\χψω\' ή \'α\\\'β\').';
$strShow = 'Εμφάνιση';
$strShowingRecords = 'Εμφάνιση εγγραφής ';
$strShowPHPInfo = 'Εμφάνιση πληροφορίας PHP';
$strShowThisQuery = ' Εμφάνισε εδώ ξανά αυτήν την επερώτηση ';
$strSingly = '(μοναδικά)';
$strSize = 'Μέγεθος';
$strSort = 'Ταξινόμιση';
$strSpaceUsage = 'Χρήση χώρου';
$strSQLQuery = 'SQL επερώτηση';
$strStatement = 'Δηλώσεις';
$strStrucCSV = 'Δεδομένα CSV';
$strStrucData = 'Δομή και δεδομένα';
$strStrucDrop = 'Προσθήκη «drop table»';
$strStrucExcelCSV = 'Μορφή CSV για δεδομένα Ms Excel';
$strStrucOnly = 'Μόνο η δομή';
$strSubmit = 'Αποστολή';
$strSuccess = 'Η SQL επερώτησή σας εκτελέσθηκε επιτυχώς';
$strSum = 'Σύνολο';

$strTable = 'Πίνακας ';
$strTableComments = 'Σχόλια Πίνακα';
$strTableEmpty = 'Το όνομα του Πίνακα είναι κενό!';
$strTableMaintenance = 'Συντήρηση Πίνακα';
$strTables = '%s Πίνακας/Πίνακες';
$strTableStructure = 'Δομή Πίνακα για τον Πίνακα';
$strTableType = 'Τύπος Πίνακα';
$strTextAreaLength = ' Εξαιτίας του μεγέθος του,<br /> αυτό το πεδίο μπορεί να μη μπορεί να διορθωθεί ';
$strTheContent = 'Τα περιεχόμενα του αρχείου σας έχει εισαγχθεί.';
$strTheContents = 'Τα περιεχόμενα του αρχείου αντικαθιστά τα περιεχόμενα του επιλεγμένου πίνακα για Γραμμές με ίδιο πρωτεύον ή μοναδικό κλειδί.';
$strTheTerminator = 'Ο τερματικός χαρακτήρας των πεδίων.';
$strTotal = 'συνολικά';
$strType = 'Τύπος';

$strUncheckAll = 'Απεπιλογή όλων';
$strUnique = 'Μοναδικό';
$strUpdateQuery = 'Ενημέρωση της Επερώτησης';
$strUsage = 'Χρήση';
$strUseBackquotes = 'Χρησιμοποιήστε ανάποδα εισαγωγικά με τα ονόματα των Πινάκων και των Πεδίων';
$strUser = 'Χρήστης';
$strUserEmpty = 'Το όνομα του χρήστη είναι κενό!';
$strUserName = 'Όνομα χρήστη';
$strUsers = 'Χρήστες';
$strUseTables = 'Χρήση Πινάκων';

$strValue = 'Τιμή';
$strViewDump = 'Εμφάνιση (schema) του πίνακα';
$strViewDumpDB = 'Εμφάνιση (schema) της βάσης';

$strWelcome = 'Καλωσήρθατε στο ';
$strWithChecked = 'Με επιλογή:';
$strWrongUser = 'Λανθασμένος χρήστης/κωδικός πρόσβασης. Άρνηση πρόσβασης.';

$strYes = 'Ναι';

$strZip = 'συμπίεση «zip»';

// To translate
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Insert a new record';
$strAPrimaryKey = 'A primary key has been added on %s';//to translate
$strAnIndex = 'An index has been added on %s';//to translate
$strCopyTable = 'Copy table to (database<b>.</b>table):';
$strDatabaseHasBeenDropped = 'Database %s has been dropped.';  //to translate
$strDeleteUserMessage = 'You have deleted the user %s.';//to translate
$strFieldHasBeenDropped = 'Field %s has been dropped';//to translate
$strFieldsEnclosedBy = 'Fields enclosed by';//to translate
$strFieldsEscapedBy = 'Fields escaped by';//to translate
$strFieldsTerminatedBy = 'Fields terminated by';//to translate
$strFlushTable = 'Flush the table ("FLUSH")';
$strIndexHasBeenDropped = 'Index %s has been dropped';//to translate
$strKeepPass = 'Do not change the password';//to translate
$strLinesTerminatedBy = 'Lines terminated by';//to translate
$strMoveTable = 'Move table to (database<b>.</b>table):';
$strMoveTableOK = 'Table %s has been moved to %s.';
$strNoFrames = 'phpMyAdmin is more friendly with a <b>frames-capable</b> browser.';
$strNoQuery = 'No SQL query!';  //to translate
$strPrimaryKeyHasBeenDropped = 'The primary key has been dropped';//to translate
$strRunningAs = 'as';
$strRunSQLQuery = 'Run SQL query/queries on database %s';//to translate
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowTables = 'Show tables';
$strStartingRecord = 'Starting record';//to translate
$strTableHasBeenDropped = 'Table %s has been dropped';//to translate
$strTableHasBeenEmptied = 'Table %s has been emptied';//to translate
$strTableHasBeenFlushed = 'Table %s has been flushed';
$strUpdatePrivMessage = 'You have updated the privileges for %s.';//to translate
$strUpdateProfile = 'Update profile:';//to translate
$strUpdateProfileMessage = 'The profile has been updated.';//to translate
?>
