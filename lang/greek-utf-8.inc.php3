<?php
/* $Id$ */

/* Translated by Kyriakos Xagoraris <theremon at users.sourceforge.net> */

$charset = 'utf-8';
$allow_recoding = TRUE;
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'tahoma, verdana, helvetica, geneva, sans-serif';
$number_thousands_separator = '.';
$number_decimal_separator = ',';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Κυρ', 'Δευ', 'Τρι', 'Τετ', 'Πεμ', 'Παρ', 'Σαβ');
$month = array('Ιαν', 'Φεβ', 'Μάρ', 'Απρ', 'Μάι', 'Ιούν', 'Ιούλ', 'Αυγ', 'Σεπ', 'Οκτ', 'Νοε', 'Δεκ');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d %B %Y, στις %I:%M %p';

// To Arrange

$strAccessDenied = '\'Αρνηση Πρόσβασης';
$strAction = 'Ενέργεια';
$strAddDeleteColumn = 'Προσθήκη/Αφαίρεση Στήλης Πεδίου';
$strAddDeleteRow = 'Προσθήκη/Αφαίρεση Γραμμής Κριτηρίων';
$strAddNewField = 'Προσθήκη νέου Πεδίου';
$strAddPriv = 'Προσθήκη νέου Προνομίου';
$strAddPrivMessage = 'Προσθέσατε νέο Προνόμιο.';
$strAddSearchConditions = 'Προσθήκη νέου όρου (σώμα της "where" πρότασης):';
$strAddToIndex = 'Προσθήκη στο ευρετήριο &nbsp;%s&nbsp;κολώνας(ων)';
$strAddUser = 'Προσθήκη νέου Χρήστη';
$strAddUserMessage = 'Προσθέσατε ένα νέο χρήστη.';
$strAffectedRows = 'Επηρεαζόμενες εγγραφές:';
$strAfter = 'Μετά το %s';
$strAfterInsertBack = 'Επιστροφή';
$strAfterInsertNewInsert = 'Εισαγωγή νέας εγγραφής';
$strAll = 'Όλα';
$strAllTableSameWidth = 'εμφάνιση όλων των πινάκων με το ίδιο πλάτος;';
$strAlterOrderBy = 'Αλλαγή ταξινόμησης Πίνακα κατά';
$strAnalyzeTable = 'Ανάλυση Πίνακα';
$strAnd = 'Και';
$strAnIndex = 'Ένα ευρετήριο προστέθηκε στο %s';
$strAny = 'Οποιοδήποτε';
$strAnyColumn = 'Οποιαδήποτε Στήλη';
$strAnyDatabase = 'Οποιαδήποτε Βάση';
$strAnyHost = 'Οποιοδήποτε Σύστημα';
$strAnyTable = 'Οποιοσδήποτε Πίνακας';
$strAnyUser = 'Οποιοσδήποτε Χρήστης';
$strAPrimaryKey = 'Ένα πρωτεύον κλειδί προστέθηκε στο %s';
$strAscending = 'Αύξουσα';
$strAtBeginningOfTable = 'Στην αρχή του Πίνακα';
$strAtEndOfTable = 'Στο τέλος του Πίνακα';
$strAttr = 'Χαρακτηριστικά';

$strBack = 'Επιστροφή';
$strBinary = 'Δυαδικό';
$strBinaryDoNotEdit = 'Δυαδικό - χωρίς δυνατότητα επεξεργασίας';
$strBookmarkDeleted = 'Η ετικέτα διεγράφη.';
$strBookmarkLabel = 'Ετικέτα';
$strBookmarkQuery = 'Αποθηκευμένη εντολή SQL';
$strBookmarkThis = 'Αποθήκευσε αυτήν την εντολή SQL';
$strBookmarkView = 'Μόνο ανάγνωση';
$strBrowse = 'Περιήγηση';
$strBzip = 'συμπίεση «bzip»';

$strCantLoadMySQL = 'δεν μπορεί να φορτωθεί η επέκταση MySQL,<br />παρακαλώ ελέγξτε τις ρυθμίσεις της PHP.';
$strCantRenameIdxToPrimary = 'Η μετονομασία του ευρετηρίου σε PRIMARY σε είναι εφικτή!';
$strCardinality = 'Μοναδικότητα';
$strCarriage = 'Χαρακτήρας επιστροφής: \\r';
$strChange = 'Αλλαγή';
$strChangePassword = 'Αλλαγή κωδικού πρόσβασης';
$strCheckAll = 'Επιλογή όλων';
$strCheckDbPriv = 'Έλεγχος προνομίων Βάσης';
$strCheckTable = 'Έλεγχος πίνακα';
$strColComFeat = 'Εμφάνιση σχολίων πεδίων';
$strColumn = 'Στήλη';
$strColumnNames = 'Ονόματα στηλών';
$strCompleteInserts = 'Ολοκληρωμένες εντολές «Insert»';
$strConfirm = 'Πραγματικά θέλετε να το εκτελέσετε;';
$strCookiesRequired = 'Από αυτό το σημείο πρέπει να έχετε ενεργοποιημένα cookies.';
$strCopyTable = 'Αντιγραφή πίνακα σε (βάση<b>.</b>πίνακας):';
$strCopyTableOK = 'Ο Πίνακας %s αντιγράφηκε στο %s.';
$strCreate = 'Δημιουργία';
$strCreateIndex = 'Δημιουργία ευρετηρίου σε &nbsp;%s&nbsp;πεδία';
$strCreateIndexTopic = 'Δημιουργία νέου ευρετηρίου';
$strCreateNewDatabase = 'Δημιουργία νέας βάσης';
$strCreateNewTable = 'Δημιουργία νέου πίνακα στη βάση %s';
$strCreatePdfFeat = 'Δημιουργία αρχείων PDF';
$strCriteria = 'Κριτήρια';

$strData = 'Δεδομένα';
$strDatabase = 'Βάση ';
$strDatabaseHasBeenDropped = 'Η βάση δεδομένων %s διεγράφη.';
$strDatabases = 'Βάσεις';
$strDatabasesStats = 'Στατιστικά βάσης';
$strDatabaseWildcard = 'Βάση δεδομένων (επιτρέπονται wildcards):';
$strDataOnly = 'Μόνο τα δεδομένα';
$strDefault = 'Προκαθορισμένο';
$strDelete = 'Διαγραφή';
$strDeleted = 'Η Εγγραφή έχει διαγραφεί';
$strDeletedRows = 'Διαγραμμένες Εγγραφές:';
$strDeleteFailed = 'Η διαγραφή απέτυχε';
$strDeleteUserMessage = 'Διαγράψατε τον χρήστη %s.';
$strDescending = 'Φθίνουσα';
$strDisabled = 'Απενεργοποιημένο';
$strDisplay = 'Εμφάνιση';
$strDisplayFeat = 'Λειτουργίες εμφάνισης';
$strDisplayOrder = 'Σειρά εμφάνισης:';
$strDoAQuery = 'Εκτέλεσε μία «επερώτηση κατά παράδειγμα» (χαρακτήρας μπαλαντέρ "%")';
$strDocu = 'Τεκμηρίωση';
$strDoYouReally = 'Θέλετε να εκτελέσετε την εντολή';
$strDrop = 'Διαγραφή';
$strDropDB = 'Διαγραφή βάσης %s';
$strDropTable = 'Διαγραφή πίνακα';
$strDumpingData = '\'Αδειασμα δεδομένων του πίνακα';
$strDynamic = 'δυναμικά';

$strEdit = 'Επεξεργασία';
$strEditPrivileges = 'Επεξεργασία Προνομίων';
$strEffective = 'Αποτελεσματικός';
$strEmpty = '\'Αδειασμα';
$strEmptyResultSet = 'Η MySQL επέστρεψε ένα άδειο σύνολο αποτελεσμάτων (π.χ. καμμία εγγραφή).';
$strEnabled = 'Ενεργοποιημένο';
$strEnd = 'Τέλος';
$strEnglishPrivileges = ' Σημείωση: Τα ονόματα προνομίων της MySQL εκφράζονται στα Αγγλικά ';
$strError = 'λάθος';
$strExtendedInserts = 'Εκτεταμένες εντολές «Insert»';
$strExtra = 'Πρόσθετα';

$strField = 'Πεδίο';
$strFieldHasBeenDropped = 'Το πεδίο %s διεγράφη';
$strFields = 'Πεδία';
$strFieldsEmpty = ' Η απαρίθμηση των πεδίων είναι κενή! ';
$strFieldsEnclosedBy = 'Πεδία που περικλείονται σε';
$strFieldsEscapedBy = 'Τα πεδία χρησιμοποιούν το χαρακτήρα διαφυγής ';
$strFieldsTerminatedBy = 'Πεδία που τελειώνουν σε';
$strFixed = 'προκαθορισμένου μήκους';
$strFlushTable = 'Εκκαθάριση ("FLUSH") πίνακα';
$strFormat = 'Μορφοποίηση';
$strFormEmpty = 'Ελλειπής τιμή στο πεδίο !';
$strFullText = 'Πλήρη κείμενα';
$strFunction = 'Έλεγχος';

$strGeneralRelationFeat = 'Γενικές λειτουργίες συσχέτισης';
$strGenTime = 'Χρόνος δημιουργίας';
$strGo = 'Εκτέλεση';
$strGrants = 'Παραχωρήσεις';
$strGzip = 'συμπίεση «gzip»';

$strHasBeenAltered = 'έχει αλλαχθεί.';
$strHasBeenCreated = 'έχει δημιουργηθεί.';
$strHome = 'Κεντρική σελίδα';
$strHomepageOfficial = 'Επίσημη σελίδα του phpMyAdmin';
$strHomepageSourceforge = 'Σελίδα του Sourceforge για την απόκτηση του phpMyAdmin';
$strHost = 'Σύστημα';
$strHostEmpty = 'Το όνομα του Συστήματος είναι κενό!';

$strIdxFulltext = 'Πλήρες κείμενο';
$strIfYouWish = 'Αν ενδιαφέρεστε να φορτώσετε μόνο μερικές απο τις στήλες του πίνακα, καθορίστε μία λίστα πεδίων διαχωρισμένα με κόμμα.';
$strIgnore = 'Παράληψη';
$strIndex = 'Ευρετήριο';
$strIndexes = 'Ευρετήρια';
$strIndexHasBeenDropped = 'Το ευρετήριο %s διεγράφη';
$strIndexName = 'Όνομα ευρετηρίου&nbsp;:';
$strIndexType = 'Τύπος ευρετηρίου&nbsp;:';
$strInsert = 'Εισαγωγή';
$strInsertAsNewRow = 'Εισαγωγή ως νέα εγγραφές';
$strInsertedRows = 'Εισαγόμενες εγγραφές:';
$strInsertNewRow = 'Εισαγωγή νέας εγγραφής';
$strInsertTextfiles = 'Εισαγωγή αρχείου κειμένου στον πίνακα';
$strInstructions = 'Οδηγίες';
$strInUse = 'σε χρήση';
$strInvalidName = 'Η «%s» είναι δεσμευμένη λέξη, δεν μπορείτε να την χρησιμοποιήσετε ως όνομα για Βάση, Πίνακα ή Πεδίο.';

$strKeepPass = 'Διατήρηση κωδικού πρόσβασης';
$strKeyname = 'Όνομα κλειδιού';
$strKill = 'Τερματισμός';

$strLength = 'Μήκος';
$strLengthSet = 'Μήκος/Τιμές*';
$strLimitNumRows = 'Εγγραφές ανά σελίδα';
$strLineFeed = 'Χαρακτήρας προώθησης γραμμής: \\n';
$strLines = 'Γραμμές';
$strLinesTerminatedBy = 'Γραμμές που τελειώνουν σε';
$strLocationTextfile = 'Τοποθεσία του αρχείου κειμένου';
$strLogin = 'Σύνδεση';
$strLogout = 'Αποσύνδεση';
$strLogPassword = 'Κωδικός πρόσβασης:';
$strLogUsername = 'Όνομα χρήστη:';

$strModifications = 'Οι αλλαγές αποθηκεύτηκαν';
$strModify = 'Τροποποίηση';
$strModifyIndexTopic = 'Αλλαγή ενός ευρετηρίου';
$strMoveTable = 'Μεταφορά πίνακα σε (βάση<b>.</b>πίνακας):';
$strMoveTableOK = 'Ο πίνακας %s μεταφέρθηκε στο %s.';
$strMySQLReloaded = 'Η MySQL επαναφορτώθηκε.';
$strMySQLSaid = 'Η MySQL επέστρεψε το μύνημα: ';
$strMySQLServerProcess = 'Η MySQL %pma_s1% εκτελείται στον %pma_s2% ως %pma_s3%';
$strMySQLShowProcess = 'Εμφάνιση διεργασιών';
$strMySQLShowStatus = 'Εμφάνιση πληροφορών εκτέλεσης της MySQL';
$strMySQLShowVars = 'Εμφάνιση μεταβλητών της MySQL';

$strName = 'Όνομα';
$strNext = 'Επόμενο';
$strNo = 'Όχι';
$strNoDatabases = 'Δεν υπάρχουν βάσεις δεδομένων';
$strNoDropDatabases = 'Οι εντολές «DROP DATABASE» έχουν απενεργοποιηθεί.';
$strNoFrames = 'Το phpMyAdmin είναι πιο φιλικό με έναν browser <b>που υποστηρίζει frames</b>.';
$strNoIndex = 'Δεν ορίστηκε ευρετήριο!';
$strNoIndexPartsDefined = 'Δεν ορίστηκαν τα στοιχεία του ευρετηρίου!';
$strNoModification = 'Χωρίς αλλαγή';
$strNone = 'Κανένα';
$strNoPassword = 'Χωρίς Κωδικό Πρόσβασης';
$strNoPrivileges = 'Χωρίς Προνόμια';
$strNoQuery = 'Δεν υπάρχει εντολή SQL!';
$strNoRights = 'Δεν έχετε αρκετά δικαιώματα να είσαστε εδώ τώρα!';
$strNoTablesFound = 'Δεν βρέθηκαν Πίνακες στη βάση.';
$strNotNumber = 'Αυτό δεν είναι αριθμός!';
$strNotOK = 'ΛΑΘΟΣ';
$strNotValidNumber = ' δεν είναι υπαρκτός αριθμός Εγγραφής!';
$strNoUsersFound = 'Δεν βρέθηκαν χρήστες.';
$strNull = 'Κενό';

$strOftenQuotation = 'Συχνά εισαγωγικά. Το OPTIONALLY σημαίνει ότι μόνο τα πεδία char και varchar εμπεριέχονται με τον χαρακτήρα «περιστοιχίζεται από».';
$strOK = 'OK';
$strOptimizeTable = 'Βελτιστοποίηση Πίνακα';
$strOptionalControls = 'Προεραιτικό. Ρυθμίζει πώς να γίνεται η ανάγνωση και η εγγραφή ειδικών χαρακτήρων.';
$strOptionally = 'ΠΡΟΑΙΡΕΤΙΚΑ';
$strOr = 'Ή';
$strOverhead = 'Επιβάρυνση';

$strPartialText = 'Επιμέρους κείμενα';
$strPassword = 'Κωδικός Πρόσβασης';
$strPasswordEmpty = 'Ο Κωδικός Πρόσβασης είναι κενός!';
$strPasswordNotSame = 'Οι κωδικοί πρόσβασης δεν είναι ίδιοι!';
$strPdfNoTables = 'Δεν υπάρχουν πίνακες';
$strPHPVersion = 'Έκδοση PHP';
$strPmaDocumentation = 'Τεκμηρίωση phpMyAdmin';
$strPmaUriError = 'Η εντολή <tt>$cfg[\'PmaAbsoluteUri\']</tt> ΠΡΕΠΕΙ να οριστεί στο αρχείο προεπιλογών!';
$strPos1 = 'Αρχή';
$strPrevious = 'Προηγούμενο';
$strPrimary = 'Πρωτεύον';
$strPrimaryKey = 'Πρωτεύον κλειδί';
$strPrimaryKeyHasBeenDropped = 'Το πρωτεύον κλειδί διεγράφη';
$strPrimaryKeyName = 'Το όνομα του πρωτεύοντος κλειδιού πρέπει να είναι... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>πρέπει</b> να είναι το όνομα του πρωτεύοντος κλειδιού και <b>μόνο αυτού</b> !)';
$strPrintView = 'Εμφάνιση για εκτύπωση';
$strPrivileges = 'Προνόμια';
$strProperties = 'Ιδιότητες';

$strQBE = 'Επερώτηση κατά παράδειγμα';
$strQBEDel = 'Διαγραφή';
$strQBEIns = 'Εισαγωγή';
$strQueryOnDb = 'Εντολή SQL στη βάση <b>%s</b>:';

$strRecords = 'Εγγραφές';
$strReferentialIntegrity = 'Έλεγχος ακεραιότητας σχέσεων:';
$strRelationNotWorking = 'Οι επιπρόσθετες λειτουργίες για εργασία με συσχετισμένους πίνακες έχουν απενεργοποιηθεί. Για να μάθετε γιατί, πατήστε %sεδώ%s.';
$strReloadFailed = 'Η επανεκκίνηση της MySQL απέτυχε.';
$strReloadMySQL = 'Επανεκκίνηση της MySQL';
$strRememberReload = 'Ενθύμιση της επανεκκίνησης του διακομιστή.';
$strRenameTable = 'Μετονομασία πίνακα σε';
$strRenameTableOK = 'Ο Πίνακας %s μετονομάσθηκε σε %s';
$strRepairTable = 'Επιδιόρθωση πίνακα';
$strReplace = 'Αντικατάσταση';
$strReplaceTable = 'Αντικατάσταση δεδομένων Πίνακα με το αρχείο';
$strReset = 'Επαναφορά';
$strReType = 'Επαναεισαγωγή';
$strRevoke = 'Ανάκληση';
$strRevokeGrant = 'Ανάκληση Παραχώρισης';
$strRevokeGrantMessage = 'Ανακαλέσατε τα προνόμια Παραχώρισης του %s';
$strRevokeMessage = 'Ανακαλέσατε τα προνόμια για %s';
$strRevokePriv = 'Ανάκληση προνομοίων';
$strRowLength = 'Μέγεθος Γραμμής';
$strRows = 'Εγγραφές';
$strRowsFrom = 'Εγγραφές αρχίζοντας από την εγγραφή';
$strRowSize = ' Μέγεθος Εγγραφής ';
$strRowsModeHorizontal = 'οριζόντια';
$strRowsModeOptions = 'σε %s μορφή με επανάληψη επικεφαλίδων ανά %s κελιά';
$strRowsModeVertical = 'κάθετη';
$strRowsStatistic = 'Στατιστικά Εγγραφών';
$strRunning = 'που εκτελείται στο %s';
$strRunQuery = 'Υποβολή επερώτησης';
$strRunSQLQuery = 'Εκτέλεση εντολής/εντολών SQL στη βάση δεδομένων %s';

$strSave = 'Αποθήκευση';
$strSelect = 'Επιλογή';
$strSelectADb = 'Παρακαλώ επιλέξτε μία βάση δεδομένων';
$strSelectAll = 'Επιλογή όλων';
$strSelectFields = 'Επιλογή πεδίων (τουλάχιστον ένα)';
$strSelectNumRows = 'στην εντολή';
$strSend = 'Αποστολή';
$strServerChoice = 'Επιλογή Διακομιστή';
$strServerVersion = 'Έκδοση Διακομιστή';
$strSetEnumVal = 'Αν ο τύπος του πεδίου είναι «enum» ή «set», παρακαλώ εισάγετε τις τιμές χρησιμοποιώντας την εξής μορφοποίηση: \'α\',\'β\',\'γ\'...<br /> Αν χρειάζεται να εισάγετε την ανάποδη κάθετο ("\") ή απλά εισαγωγικά ("\'"), προθέστε τα με ανάποδη κάθετο στην αρχή (για παράδειγμα \'\\\\χψω\' ή \'α\\\'β\').';
$strShow = 'Εμφάνιση';
$strShowAll = 'Εμφάνιση όλων';
$strShowCols = 'Εμφάνιση στηλών';
$strShowingRecords = 'Εμφάνιση εγγραφής ';
$strShowPHPInfo = 'Εμφάνιση πληροφοριών της PHP';
$strShowTables = 'Εμφάνιση πινάκων';
$strShowThisQuery = ' Εμφάνισε εδώ ξανά αυτήν την εντολή ';
$strSingly = '(μοναδικά)';
$strSize = 'Μέγεθος';
$strSort = 'Ταξινόμιση';
$strSpaceUsage = 'Χρήση χώρου';
$strSQLQuery = 'Εντολή SQL';
$strStatement = 'Δηλώσεις';
$strStrucCSV = 'Δεδομένα CSV';
$strStrucData = 'Δομή και δεδομένα';
$strStrucDrop = 'Προσθήκη «Drop Table»';
$strStrucExcelCSV = 'Μορφή CSV για δεδομένα Ms Excel';
$strStrucOnly = 'Μόνο η δομή';
$strSubmit = 'Αποστολή';
$strSuccess = 'Η SQL εντολή σας εκτελέσθηκε επιτυχώς';
$strSum = 'Σύνολο';

$strTable = 'Πίνακας ';
$strTableComments = 'Σχόλια Πίνακα';
$strTableEmpty = 'Το όνομα του Πίνακα είναι κενό!';
$strTableHasBeenDropped = 'Ο Πίνακας %s διεγράφη';
$strTableHasBeenEmptied = 'Ο Πίνακας %s άδειασε';
$strTableHasBeenFlushed = 'Ο Πίνακας %s εκκαθαρίστικε ("FLUSH")';
$strTableMaintenance = 'Συντήρηση Πίνακα';
$strTables = '%s Πίνακας/Πίνακες';
$strTableStructure = 'Δομή Πίνακα για τον Πίνακα';
$strTableType = 'Τύπος Πίνακα';
$strTextAreaLength = ' Εξαιτίας του μεγέθος του,<br /> αυτό το πεδίο μπορεί να μη μπορεί να διορθωθεί ';
$strTheContent = 'Τα περιεχόμενα του αρχείου σας έχουν εισαγχθεί.';
$strTheContents = 'Τα περιεχόμενα του αρχείου αντικαθιστούν τα περιεχόμενα του επιλεγμένου πίνακα για Γραμμές με ίδιο πρωτεύον ή μοναδικό κλειδί.';
$strTheTerminator = 'Ο τερματικός χαρακτήρας των πεδίων.';
$strTotal = 'συνολικά';
$strType = 'Τύπος';

$strUncheckAll = 'Απεπιλογή όλων';
$strUnique = 'Μοναδικό';
$strUnselectAll = 'Απεπιλογή όλων';
$strUpdatePrivMessage = 'Τα προνόμια του χρήστη %s ενημερώθηκαν.';
$strUpdateProfile = 'Ενημέρωση στοιχείων:';
$strUpdateProfileMessage = 'Τα στοιχεία ανανεώθηκαν.';
$strUpdateQuery = 'Ενημέρωση της εντολής';
$strUsage = 'Χρήση';
$strUseBackquotes = 'Χρήση ανάποδων εισαγωγικών στα ονόματα των Πινάκων και των Πεδίων';
$strUser = 'Χρήστης';
$strUserEmpty = 'Το όνομα του χρήστη είναι κενό!';
$strUserName = 'Όνομα χρήστη';
$strUsers = 'Χρήστες';
$strUseTables = 'Χρήση Πινάκων';

$strValue = 'Τιμή';
$strViewDump = 'Εμφάνιση σχήματος του πίνακα';
$strViewDumpDB = 'Εμφάνιση σχήματος της βάσης';

$strWelcome = 'Καλωσήρθατε στο %s';
$strWithChecked = 'Με τους επιλεγμένους:';
$strWrongUser = 'Λανθασμένο όνομα χρήστη/κωδικός πρόσβασης. \'Αρνηση πρόσβασης.';

$strYes = 'Ναι';

$strZip = 'συμπίεση «zip»';
// To Translate

$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCantLoadRecodeIconv = 'Δεν είναι δυνατή η φόρτωση της επέκτασης iconv ή recode που χρειάζεται για την μετατροπή του σετ χαρακτήρων. Ρυθμίστε την php να επιτρέπει την χρήση αυτών των επεκτάσεων ή απανεργοποιήστε την μετατροπή χαρακτήρων στο phpMyAdmin.';  //to translate
$strCantUseRecodeIconv = 'Δεν είναι δυνατή η χρήση της επέκτασης iconv ούτε της libiconv ούτε της ρουτίνας recode_string, ενώ η επέκταση έχει φορτωθεί. Ελέξτε τις ρυθμίσεις της php.';  //to translate
$strChangeDisplay = 'Επιλέξτε πεδίο για εμφάνιση';  //to translate
$strCharsetOfFile = 'Character set of the file:'; //to translate
$strChoosePage = 'Παρακαλώ επιλέξτε σελίδα για αλλαγή';  //to translate
$strComments = 'Σχόλια';  //to translate
$strConfigFileError = 'Το phpMyAdmin δεν μπόρεσε να διαβάσει το αρχείο ρυθμίσεων!<br />Αυτό μπορεί να συμβεί εάν η php βρει κάποιο λάθος στο αρχείο ή εάν η php δεν μπορεί να βρει το αρχείο.<br />Παρακαλώ καλέστε το αρχείο ρυθμίσεων απ\' ευθείας χρησιμοποιώντας το ακόλουθο link και διαβάστε τα μυνήματα λάθους που θα επιστρέψει η php. Στις περισσότερες περιπτώσεις κάπου λείπουν εισαγωγικά (") ή ερωτιματικά (;).<br />Εάν η php επιστρέψει μια λευκή σελίδα, όλα είναι σωστά.'; //to translate
$strConfigureTableCoord = 'Παρακαλώ ορίστε τις συντεταγμένες για τον πίνακα %s';  //to translate
$strCreatePage = 'Δημιουργία νέας σελίδας';  //to translate

$strDisplayPDF = 'Εμφάνιση σχήματος PDF';  //to translate
$strDumpXRows = 'Εμφάνιση %s εγγραφών ξεκινώντας από την εγγραφή %s.'; //to translate

$strEditPDFPages = 'Αλλαγή σελίδων PDF';  //to translate
$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'Explain SQL';  //to translate
$strExport = 'Εξαγωγή';  //to translate
$strExportToXML = 'Export to XML format'; //to translate

$strGenBy = 'Δημιουργήθηκε από:'; //to translate

$strHaveToShow = 'Πρέπει να επιλέξετε τουλάχιστον μία στήλη για εμφάνιση';  //to translate

$strLinkNotFound = 'Δεν βρέθηκε η σύνδεση';  //to translate
$strLinksTo = 'Σύνδεση με';  //to translate

$strMissingBracket = 'Λείπει μία αγκύλη';  //to translate
$strMySQLCharset = 'Σετ χαρακτήρων της MySQL';  //to translate

$strNoDescription = 'χωρίς περιγραφή';  //to translate
$strNoExplain = 'Skip Explain SQL';  //to translate
$strNoPhp = 'χωρίς κώδικα PHP';  //to translate
$strNotSet = 'Ο πίνακας <b>%s</b> δεν βρέθηκε ή δεν ορίστηκε στη %s';  //to translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate
$strNumSearchResultsInTable = '%s αποτελέσματα στον πίνακα <i>%s</i>';//to translate
$strNumSearchResultsTotal = '<b>Σύνολο:</b> <i>%s</i> αποτελέσματα';//to translate

$strOperations = 'Λειτουργίες';  //to translate
$strOptions = 'Επιλογές';  //to translate

$strPageNumber = 'Σελίδα:';  //to translate
$strPdfDbSchema = 'Σχήμα της βάσης "%s" - Σελίδα %s';  //to translate
$strPdfInvalidPageNum = 'Δεν ορίστηκε αριθμός σελίδας PDF!';  //to translate
$strPdfInvalidTblName = 'Ο πίνακας "%s" δεν υπάρχει!';  //to translate
$strPhp = 'Δημιουργία κώδικα PHP';  //to translate

$strRelationView = 'Εμφάνιση σχέσεων';  //to translate

$strScaleFactorSmall = 'Η κλίμακα είναι πολύ μικρή για να εμφανιστεί το σχήμα σε μία σελίδα';  //to translate
$strSearch = 'Αναζήτηση';//to translate
$strSearchFormTitle = 'Αναζήτηση στη βάση';//to translate
$strSearchInTables = 'Μέσα στους πίνακες:';//to translate
$strSearchNeedle = 'Όροι ή τιμές για αναζήτηση (μπαλαντέρ: "%"):';//to translate
$strSearchOption1 = 'τουλάχιστον έναν από τους όρους';//to translate
$strSearchOption2 = 'όλους τους όρους';//to translate
$strSearchOption3 = 'την ακριβή φράση';//to translate
$strSearchOption4 = 'ως regular expression';//to translate
$strSearchResultsFor = 'Αποτελέσματα αναζήτησης για "<i>%s</i>" %s:';//to translate
$strSearchType = 'Έυρεση:';//to translate
$strSelectTables = 'Επιλογή Πινάκων';  //to translate
$strShowColor = 'Εμφάνιση χρωμάτων';  //to translate
$strShowGrid = 'Εμφάνιση πλέγματος';  //to translate
$strShowTableDimension = 'Εμφάνιση διαστάσεων πινάκων';  //to translate
$strSplitWordsWithSpace = 'Οι λέξεις χωρίζονται από τον χαρακτήρα διαστήματος (" ").';//to translate
$strSQL = 'SQL'; //to translate
$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQLResult = 'αποτέλεσμα SQL'; //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate
$strStructPropose = 'Προτεινόμενη δομή πίνακα';  //to translate
$strStructure = 'Δομή';  //to translate

$strValidateSQL = 'Validate SQL';  //to translate

$strInsecureMySQL = 'Your configuration file contains settings (root with no password) that correspond to the default MySQL privileged account. Your MySQL server is running with this default, is open to intrusion, and you really should fix this security hole.';  //to translate
$strWebServerUploadDirectory = 'web-server upload directory';  //to translate
$strWebServerUploadDirectoryError = 'The directory you set for upload work cannot be reached';  //to translate
$strValidatorError = 'The SQL validator could not be initialized. Please check if you have installed the necessary php extensions as described in the %sdocumentation%s.'; //to translate
$strServer = 'Server %s';  //to translate
$strPutColNames = 'Put fields names at first row';  //to translate
$strImportDocSQL = 'Import docSQL Files';  //to translate
$strDataDict = 'Data Dictionary';  //to translate
$strPrint = 'Print';  //to translate
$strPHP40203 = 'You are using PHP 4.2.3, which has a serious bug with multi-byte strings (mbstring). See PHP bug report 19404. This version of PHP is not recommended for use with phpMyAdmin.';  //to translate
$strCompression = 'Compression'; //to translate
$strNumTables = 'Tables'; //to translate
$strTotalUC = 'Total'; //to translate
?>
