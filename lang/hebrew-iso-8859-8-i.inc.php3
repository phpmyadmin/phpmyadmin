<?php
/* $Id$ */

/* Translated by: Yuval "Etus" Sarna */

$charset = 'iso-8859-8-i';
$text_dir = 'rtl'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת');
$month = array('ינואר', 'פברואר', 'מרץ', 'אפריל', 'מאי', 'יוני', 'יולי', 'אוגוסט', 'ספטמבר', 'אוקטובר', 'נובמבר', 'דצמבר');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';

$strAccessDenied = 'הגישה נדחתה';
$strAction = 'פעולה';
$strAddDeleteColumn = 'הוסף/מחק שורות שדה';
$strAddDeleteRow = 'הוסף/מחק שורת קריטריון';
$strAddNewField = 'הוסף שדה חדש';
$strAddPriv = 'הוסף הרשאה חדשה';
$strAddPrivMessage = 'הוספת הרשאה חדשה.';
$strAddSearchConditions = 'הוסף תנאי חיפוש (הגוף של "where"):';
$strAddToIndex = 'הוסף לאינדקס &nbsp;%s&nbsp;שורה/שורות';
$strAddUser = 'הוסף משתמש חדש';
$strAddUserMessage = 'הוספת משתמש חדש.';
$strAffectedRows = 'שורות מושפעות:';
$strAfter = 'אחרי %s';
$strAfterInsertBack = 'חזור לעמוד הקודם';
$strAfterInsertNewInsert = 'הוסף שורה חדשה נוספת';
$strAll = 'הכל';
$strAlterOrderBy = 'שנה את סדר הטבלה על-ידי';
$strAnalyzeTable = 'נתח טבלה';
$strAnd = 'וגם';
$strAnIndex = 'אינדקס התווסף ב- %s';
$strAny = 'כל';
$strAnyColumn = 'כל עמודה';
$strAnyDatabase = 'כל מסד נתונים';
$strAnyHost = 'כל מארח';
$strAnyTable = 'כל טבלה';
$strAnyUser = 'כל משתמש';
$strAPrimaryKey = 'מפתח ראשי התווסף ב- %s';
$strAscending = 'עולה';
$strAtBeginningOfTable = 'בתחילת טבלה';
$strAtEndOfTable = 'בסוף טבלה';
$strAttr = 'תכונות';

$strBack = 'חזור';
$strBinary = 'בינארי';
$strBinaryDoNotEdit = 'בינארי - לא לערוך';
$strBookmarkDeleted = 'ה- Bookmark נמחק.';
$strBookmarkLabel = 'תווית';
$strBookmarkQuery = 'שאילת ה- SQL התווספה ל- Bookmark';
$strBookmarkThis = 'הוסף ל- Bookmark את שאילת ה- SQL הזו';
$strBookmarkView = 'לצפייה בלבד';
$strBrowse = 'עיון';
$strBzip = '"BZipped"';

$strCantLoadMySQL = 'לא יכול לטעון את סיומת ה- MySQL,<br />בבקשה בדוק את קונפיגורצית ה- PHP.';
$strCantRenameIdxToPrimary = 'לא יכול לשנות את האינדקס לעיקרי !';
$strCardinality = 'Cardinality';
$strCarriage = 'תו החזרת גררה: \\r';
$strChange = 'שנה';
$strChangeDisplay = 'בחר שדה להצגה';
$strChangePassword = 'שנה סיסמה';
$strCheckAll = 'סמן הכל';
$strCheckDbPriv = 'בדוק את הרשאות מסד הנתונים';
$strCheckTable = 'בדוק טבלה';
$strChoosePage = 'אנא בחר עמוד לעריכה';
$strColumn = 'עמודה';
$strColumnNames = 'שמות השורות';
$strComments = 'הערות';
$strCompleteInserts = 'השלם הכנסות';
$strConfigFileError = 'phpMyAdmin לא הצליח לקרוא את קובץ הקונפיגורציה שלך! מצב זה יתכן אם PHP מוצא טעות בקוד הקובץ או אם הוא לא מוצא את הקובץ.<br> אנא קרא לקובץ הקונפיגורציה ישירות בעזרת הקישור מתחת להודעה זו וקרא את הודעת PHP שהינך מקבל. ברוב המילים גרש או נקודה-פסיק חסרים במקום כלשהו.<br> אם הינך מקבל דף ריק, הכל בסדר.';
$strConfirm = 'אתה באמת רוצה לעשות את זה?';
$strCookiesRequired = 'אפשרות הקוקיס חייבת להיות מופעלת לאחר נקודה זו.';
$strCopyTable = 'העתק טבלה ל- (מסד נתונים<b>.</b>טבלה):';
$strCopyTableOK = 'הטבלה %s הועתקה ל- %s.';
$strCreate = 'צור';
$strCreateIndex = 'צור אינקס ב-&nbsp;%s&nbsp;שורות';
$strCreateIndexTopic = 'צור אינקס חדש';
$strCreateNewDatabase = 'צור מסד נתונים חדש';
$strCreateNewTable = 'צור טבלה חדשה על מסד הנתונים %s';
$strCreatePage = 'צור עמוד חדש';
$strCriteria = 'קריטריון';

$strData = 'מידע';
$strDatabase = 'מסד נתונים ';
$strDatabaseHasBeenDropped = 'מסד הנתונים %s נמחק.';
$strDatabases = 'מסדי הנתונים';
$strDatabasesStats = 'סטטיסטיקת מסד הנתונים';
$strDatabaseWildcard = 'מסד נתונים (תווים כלליים מורשים):';
$strDataOnly = 'מידע בלבד';
$strDefault = 'ברירת מחדל';
$strDelete = 'מחק';
$strDeleted = 'השורה נמחקה';
$strDeletedRows = 'שורות שנמחקו:';
$strDeleteFailed = 'מחיקה נכשלה !';
$strDeleteUserMessage = 'מחקת את המשתמש %s.';
$strDescending = 'יורד';
$strDisplay = 'הצג';
$strDisplayOrder = 'סדר הצגה:';
$strDisplayPDF = 'הצג סכמה בפורמט PDF';
$strDoAQuery = 'צור "שאילתה לדוגמה" (תו כללי: "%")';
$strDocu = 'תיעוד';
$strDoYouReally = 'האם אתה באמת רוצה לבצע ';
$strDrop = 'הסר';
$strDropDB = 'הסר מסד נתונים %s';
$strDropTable = 'הסר טבלה';
$strDumpingData = 'הזרם מידע לטבלה';
$strDynamic = 'דינאמי';

$strEdit = 'ערוך';
$strEditPDFPages = 'ערוך דפי PDF';
$strEditPrivileges = 'ערוך הרשאות';
$strEffective = 'אפקטיבי';
$strEmpty = 'רוקן';
$strEmptyResultSet = 'MySQL החזיר 0 תשובות מן מסד הנתונים(כלומר, 0 שורות).';
$strEnd = 'סוף';
$strEnglishPrivileges = ' הערה: הרשאות MySQL נכתבים באנגלית ';
$strError = 'תקלה';
$strExport = 'ייצא';
$strExportToXML = 'ייצא לפורמט XML';
$strExtendedInserts = 'הכנסות מורחבות';
$strExtra = 'נוסף';

$strField = 'שדה';
$strFieldHasBeenDropped = 'השדה %s נמחק';
$strFields = 'שדות';
$strFieldsEmpty = ' ספירת השדות ריקה ! ';
$strFieldsEnclosedBy = 'צרף שדות עם';
$strFieldsEscapedBy = 'הורד שדות עם';
$strFieldsTerminatedBy = 'סיים שדות עם';
$strFixed = 'תוקן';
$strFlushTable = 'שטוף את מסד הנתונים ("שטוף")';
$strFormat = 'פורמט';
$strFormEmpty = 'חסר מידע מן הטופס !';
$strFullText = 'טסקט מלא';
$strFunction = 'פונקציות';

$strGenBy = 'נוצר על-ידי';
$strGenTime = 'זמן יצירה';
$strGo = 'שלח';
$strGrants = 'הרשאות';
$strGzip = '"GZipped"';

$strHasBeenAltered = 'שונה.';
$strHasBeenCreated = 'נוצר.';
$strHome = 'עמוד ראשי';
$strHomepageOfficial = 'אתר phpMyAdmin הרשמי';
$strHomepageSourceforge = 'עמוד ההורדות של phpMyAdmin באתר Sourceforge';
$strHost = 'מארח';
$strHostEmpty = 'נתון המארח ריק !';

$strIdxFulltext = 'טקסט מלא';
$strIfYouWish = 'אם ברצונך לטעון רק חלק מן עמודות הטבלה, כתוב פסיק המפריד בין רשימת השדות.';
$strIgnore = 'התעלם';
$strIndex = 'אינדקס';
$strIndexes = 'אינדקסים';
$strIndexHasBeenDropped = 'האינקס %s נמחק';
$strIndexName = 'שם האינדקס&nbsp;:';
$strIndexType = 'סוג האינדקס&nbsp;:';
$strInsert = 'הכנס';
$strInsertAsNewRow = 'הוכנס כשורה חדשה';
$strInsertedRows = 'שורות שהוכנסו:';
$strInsertNewRow = 'הכנס שורה חדשה';
$strInsertTextfiles = 'הכנס מידע מתוך קובץ טסקט לטבלה';
$strInstructions = 'הוראות';
$strInUse = 'בשימוש';
$strInvalidName = '"%s" היא מילה שמורה, אינך יכול להשתמש בה כמסד נתונים/טבלה/שדה.';

$strKeepPass = 'אל תשנה את הסיסמה';
$strKeyname = 'שם מפתח';
$strKill = 'חסל';

$strLength = 'אורך';
$strLengthSet = 'אורך/ערכים*';
$strLimitNumRows = 'מספר עמודות בכל דף';
$strLineFeed = 'הזנת שורה: \\n';
$strLines = 'שורות';
$strLinesTerminatedBy = 'שורות נגמרות על-ידי';
$strLinkNotFound = 'קישור לא נמצא';
$strLinksTo = 'קישורים ל-';
$strLocationTextfile = 'מיקום קובץ הטקסט';
$strLogin = 'הכנס';
$strLogout = 'התנתק';
$strLogPassword = 'סיסמה:';
$strLogUsername = 'שם משתמש:';

$strMissingBracket = 'מרכאות חסרות';
$strModifications = 'השינויים נשמרו';
$strModify = 'שנה';
$strModifyIndexTopic = 'שנה אינדקס';
$strMoveTable = 'העבר טבלה ל- (מסד נתונים<b>.</b>טבלה):';
$strMoveTableOK = 'הטבלה %s הועברה ל- %s.';
$strMySQLReloaded = 'MySQL נטען מחדש.';
$strMySQLSaid = 'MySQL אמר: ';
$strMySQLServerProcess = 'MySQL %pma_s1% רץ על %pma_s2% כ- %pma_s3%';
$strMySQLShowProcess = 'הראה תהליכים';
$strMySQLShowStatus = 'הראה את מידע ההרצה של MySQL';
$strMySQLShowVars = 'הראה את משתני המערכת של MySQL';

$strName = 'שם';
$strNext = 'הבא';
$strNo = 'לא';
$strNoDatabases = 'אין מסדי נתונים';
$strNoDescription = 'אין תיאור';
$strNoDropDatabases = 'הביטוי "DROP DATABASE" מנוטרל.';
$strNoFrames = 'phpMyAdmin הוא יותר ידידותי עם דפדפן <b>התומך בפריימים</b>.';
$strNoIndex = 'אינדקס לא מוגדר !';
$strNoIndexPartsDefined = 'אין חלקי אינדקס מוגדרים !';
$strNoModification = 'אין שינוי';
$strNone = 'NULL';
$strNoPassword = 'אין סיסמה';
$strNoPhp = 'ללא קוד PHP';
$strNoPrivileges = 'אין הרשאות';
$strNoQuery = 'אין שאילתת SQL !';
$strNoRights = 'אין לך מספיק זכויות כדי להיות כאן עכשיו !';
$strNoTablesFound = 'טבלאות לא נמצאו במסד הנתונים.';
$strNotNumber = 'זהו לא מספר !';
$strNotSet = 'הטבלה <b>%s</b> לא נמצאה ב- %s';
$strNotValidNumber = ' הוא לא מספר שורה בר תוקף !';
$strNoUsersFound = 'אף משתמש/משתמשים נמצאו.';
$strNull = 'NULL';
$strNumSearchResultsInTable = '%s תוצאה/תוצאות בתוך הטבלה <i>%s</i>';

$strOftenQuotation = 'מרכאות נפוצות. בתור אופציה מתכוון שרק שדות char ו- varchar נסגרים על ידי מרכאות.';
$strOperations = 'פעולות';
$strOptimizeTable = 'ייעל טבלה';
$strOptionalControls = 'אופציה. בקרה על קריאה וכתיבה של סימנים מיוחדים.';
$strOptionally = 'בתור אופציה';
$strOptions = 'אפשרויות';
$strOr = 'או';
$strOverhead = 'תקורה';

$strPageNumber = 'מספר עמוד:';
$strPartialText = 'טקסטים חלקיים';
$strPassword = 'סיסמה';
$strPasswordEmpty = 'הסיסמה ריקה !';
$strPasswordNotSame = 'הסיסמאות אינן זהות !';
$strPdfDbSchema = 'סכמת מסד הנתונים "%s" - עמוד %s';
$strPdfInvalidPageNum = 'מספר עמוד של PDF לא מוגדר!';
$strPdfInvalidTblName = 'הטבלה "%s" לא קיימת!';
$strPhp = 'צור קוד PHP';
$strPHPVersion = 'גרסת PHP';
$strPmaDocumentation = 'דוקומנטצית phpMyAdmin';
$strPmaUriError = 'הנחיית ה- <tt>$cfg[\'PmaAbsoluteUri\']</tt> חייבת להיות ממוקמת בקובץ הקונפיגורציה שלך!';
$strPos1 = 'התחל';
$strPrevious = 'הקודם';
$strPrimary = 'ראשי';
$strPrimaryKey = 'מפתח ראשי';
$strPrimaryKeyHasBeenDropped = 'המפתח הראשי נמחק';
$strPrimaryKeyName = 'השם של המפתח הראשי חייב להיות... ראשי !';
$strPrimaryKeyWarning = '("מפתח ראשי" <b>חייב</b> להיקרות בשם של מפתח ראשי !)';
$strPrintView = 'הצגת הדפסה';
$strPrivileges = 'הרשאות';
$strProperties = 'מאפיינים';

$strQBE = 'שאילתה לדוגמה';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'שאילתת SQL על מסד הנתונים <b>%s</b>:';

$strRecords = 'רשומות';
$strReferentialIntegrity = 'בדוק את ה- Referential Integrity:';
$strRelationView = 'תצוגת יחס';
$strReloadFailed = 'טעינה מחדש של MySQL נכשלה.';
$strReloadMySQL = 'טען מחדש את MySQL';
$strRememberReload = 'זכור לטעון מחדש את השרת.';
$strRenameTable = 'שנה את שם הטבלה ל-';
$strRenameTableOK = 'שם הטבלה %s השתנה ל- %s';
$strRepairTable = 'תקן טבלה';
$strReplace = 'החלף';
$strReplaceTable = 'החלף את שם הטבלה עם קובץ';
$strReset = 'אפס';
$strReType = 'הכנס מחדש';
$strRevoke = 'פסול';
$strRevokeGrant = 'פסילה אושרה';
$strRevokeGrantMessage = 'פסלת את הרשאת ה- Grant מ- %s';
$strRevokeMessage = ' פסלת את ההרשמה מ- %s';
$strRevokePriv = 'פסול הרשאות';
$strRowLength = 'אורך שורה';
$strRows = 'שורות';
$strRowsFrom = 'שורות המתחילות מ-';
$strRowSize = ' גודל השורה ';
$strRowsModeHorizontal = 'אופקי';
$strRowsModeOptions = 'במצב %s חזור על הכותרת העליונה לאחר %s תאים';
$strRowsModeVertical = 'אורכי';
$strRowsStatistic = 'סטטיסטיקת השורה';
$strRunning = 'רץ על %s';
$strRunQuery = 'שלח שאילתה';
$strRunSQLQuery = 'הרץ את שאילתה/שאילתות על מסד הנתונים %s';

$strSave = 'שמור';
$strSearch = 'חפש';
$strSearchFormTitle = 'חפש במסד הנתונים';
$strSearchInTables = 'בתוך הטבלה/הטבלאות:';
$strSearchOption1 = 'לפחות אחת מן המילים';
$strSearchOption2 = 'כל המילים';
$strSearchOption3 = 'הביטוי המדוייק';
$strSearchOption4 = 'כביטוי רגיל';
$strSearchResultsFor = 'תוצאות חיפוש ל- "<i>%s</i>" %s:';
$strSearchType = 'מצא:';
$strSelect = 'בחר';
$strSelectADb = 'בחר בבקשה מסד נתונים';
$strSelectAll = 'בחר הכל';
$strSelectFields = 'בחר שדות (לפחות אחד):';
$strSelectNumRows = 'מבצע שאילתה';
$strSelectTables = 'בחר טבלאות';
$strSend = 'שמור כקובץ';
$strServerChoice = 'בחירת שרת';
$strServerVersion = 'גרסת שרת';
$strSetEnumVal = 'אם סוג השדה הוא enum או set, הכנס בבקשה ערכים המשתמשים בפורמט הבא: \'a\',\'b\',\'c\'...<br />אם תשים אי פעם סימן \ או מרכאה אחת יחד עם הערכים הללו, הוסף \ לפניו.';
$strShow = 'הראה';
$strShowAll = 'הראה הכל';
$strShowColor = 'הצג צבע';
$strShowCols = 'הראה טורים';
$strShowingRecords = 'מראה שורות';
$strShowPHPInfo = 'הראה מידע PHP';
$strShowTables = 'הראה טבלאות';
$strShowThisQuery = ' הראה את השאילתה הזו שנית ';
$strSingly = '(בנפרד)';
$strSize = 'גודל';
$strSort = 'סיווג';
$strSpaceUsage = 'נפח מקום';
$strSplitWordsWithSpace = 'המילים מופרדות על ידי תו רווח (" ").';
$strSQL = 'SQL';
$strSQLQuery = 'שאילתת SQL';
$strSQLResult = 'תוצאות SQL';
$strStatement = 'משפטים';
$strStrucCSV = 'מידע CSV';
$strStrucData = 'מבנים ומידע';
$strStrucDrop = 'הוסף \'מחק טבלה\'';
$strStrucExcelCSV = 'CVS למידע Ms Excel';
$strStrucOnly = 'מבנה בלבד';
$strStructPropose = 'הצע מבני טבלה';
$strStructure = 'מבנים';
$strSubmit = 'שלח';
$strSuccess = 'שאילתת ה- SQL שלך בוצעה בהצלחה';
$strSum = 'סיכום';

$strTable = 'טבלה';
$strTableComments = 'הערות טבלה';
$strTableEmpty = 'שם הטבלה ריק !';
$strTableHasBeenDropped = 'הטבלה %s נמחקה';
$strTableHasBeenEmptied = 'Table %s רוקנה';
$strTableHasBeenFlushed = 'Table %s נשטפה לאמצעי אחסון';
$strTableMaintenance = 'אחזקת טבלה';
$strTables = '%s טבלה/טבלאות';
$strTableStructure = 'מבנה טבלה לטבלה';
$strTableType = 'סוג טבלה';
$strTextAreaLength = ' בגלל אורכו,<br /> יתכן ושדה זה לא ינתן לעריכה ';
$strTheContent = 'התוכן של קבצך הוכנס.';
$strTheContents = 'התוכן של הקובץ הזה מחליף את התוכן של הטבלה הנבחרת לשורות עם מפתח ראשי או מפתח יחודי זהה.';
$strTheTerminator = 'הסוף של השדות.';
$strTotal = 'סך-הכל';
$strType = 'סוג';

$strUncheckAll = 'בטל סימון של הכל';
$strUnique = 'יחודי';
$strUnselectAll = 'בטל בחירה של הכל';
$strUpdatePrivMessage = 'עידכנת את ההרשאות ל- %s.';
$strUpdateProfile = 'עדכן פרופיל:';
$strUpdateProfileMessage = 'הפרופיל עודכן.';
$strUpdateQuery = 'עדכן שאילתה';
$strUsage = 'שימוש';
$strUseBackquotes = 'השתמש במרכאות אחוריות עם טבלאות ושמות שדות';
$strUser = 'משתמש';
$strUserEmpty = 'שם המשתמש ריק !';
$strUserName = 'שם משתמש';
$strUsers = 'משתמשים';
$strUseTables = 'השתמש בטבלאות';

$strValue = 'ערך';
$strViewDump = 'הראה את סכמת הטבלה';
$strViewDumpDB = 'הראה את סכמת מסד הנתונים';

$strWelcome = 'ברוך הבא ל- %s';
$strWithChecked = 'ביחד עם:';
$strWrongUser = 'שם משתמש/סיסמה שגויים. הגישה נדחתה.';

$strYes = 'כן';

$strZip = '"Zipped"';
//To translate:

$strAllTableSameWidth = 'display all Tables with same width?';  //to translate

$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCantLoadRecodeIconv = 'Can not load iconv or recode extension needed for charset conversion, configure php to allow using these extensions or disable charset conversion in phpMyAdmin.';  //to translate
$strCantUseRecodeIconv = 'Can not use iconv nor libiconv nor recode_string function while extension reports to be loaded. Check your php configuration.';  //to translate
$strCharsetOfFile = 'Character set of the file:'; //to translate
$strColComFeat = 'Displaying Column Comments';  //to translate
$strConfigureTableCoord = 'Please configure the coordinates for table %s';  //to translate
$strCreatePdfFeat = 'Creation of PDFs';  //to translate

$strDisabled = 'Disabled';  //to translate
$strDisplayFeat = 'Display Features';  //to translate
$strDumpXRows = 'Dump %s rows starting at row %s.'; //to translate

$strEnabled = 'Enabled';  //to translate
$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'Explain SQL';  //to translate

$strGeneralRelationFeat = 'General relation features';  //to translate

$strHaveToShow = 'You have to choose at least one Column to display';  //to translate

$strMySQLCharset = 'MySQL Charset';  //to translate

$strNoExplain = 'Skip Explain SQL';  //to translate
$strNotOK = 'not OK';  //to translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate
$strNumSearchResultsTotal = '<b>Total:</b> <i>%s</i> match(es)';//to translate

$strOK = 'OK';  //to translate

$strPdfNoTables = 'No tables';  //to translate

$strRelationNotWorking = 'The additional Features for working with linked Tables have been deactivated. To find out why click %shere%s.';  //to translate

$strScaleFactorSmall = 'The scale factor is too small to fit the schema on one page';  //to translate
$strSearchNeedle = 'Word(s) or value(s) to search for (wildcard: "%"):';//to translate
$strShowGrid = 'Show grid'; //to translate
$strShowTableDimension = 'Show dimension of tables';  //to translate
$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate

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
$strRelationalSchema = 'Relational schema';  //to translate
$strTableOfContents = 'Table of contents';  //to translate
$strCannotLogin = 'Cannot login to MySQL server';  //to translate
$strShowDatadictAs = 'Data Dictionary Format';  //to translate
$strLandscape = 'Landscape';  //to translate
$strPortrait = 'Portrait';  //to translate

$timespanfmt = '%s days, %s hours, %s minutes and %s seconds'; //to translate

$strAbortedClients = 'Aborted'; //to translate
$strConnections = 'Connections'; //to translate
$strFailedAttempts = 'Failed attempts'; //to translate
$strGlobalValue = 'Global value'; //to translate
$strMoreStatusVars = 'More status variables'; //to translate
$strPerHour = 'per hour'; //to translate
$strQueryStatistics = '<b>Query statistics</b>: Since its startup, %s queries have been sent to the server.';
$strQueryType = 'Query type'; //to translate
$strReceived = 'Received'; //to translate
$strSent = 'Sent'; //to translate
$strServerStatus = 'Runtime Information'; //to translate
$strServerStatusUptime = 'This MySQL server has been running for %s. It started up on %s.'; //to translate
$strServerTabVariables = 'Variables'; //to translate
$strServerTabProcesslist = 'Processes'; //to translate
$strServerTrafficNotes = '<b>Server traffic</b>: These tables show the network traffic statistics of this MySQL server since its startup.';
$strServerVars = 'Server variables and settings'; //to translate
$strSessionValue = 'Session value'; //to translate
$strTraffic = 'Traffic'; //to translate
$strVar = 'Variable'; //to translate

$strCommand = 'Command'; //to translate
$strCouldNotKill = 'phpMyAdmin was unable to kill thread %s. It probably has already been closed.'; //to translate
$strId = 'ID'; //to translate
$strProcesslist = 'Process list'; //to translate
$strStatus = 'Status'; //to translate
$strTime = 'Time'; //to translate
$strThreadSuccessfullyKilled = 'Thread %s was successfully killed.'; //to translate

$strBzError = 'phpMyAdmin was unable to compress the dump because of a broken Bz2 extension in this php version. It is strongly recommended to set the <code>$cfg[\'BZipDump\']</code> directive in your phpMyAdmin configuration file to <code>FALSE</code>. If you want to use the Bz2 compression features, you should upgrade to a later php version. See php bug report %s for details.'; //to translate
$strLaTeX = 'LaTeX';  //to translate

$strAdministration = 'Administration'; //to translate
$strFlushPrivilegesNote = 'Note: phpMyAdmin gets the users\' privileges directly from MySQL\'s privilege tables. The content of this tables may differ from the privileges the server uses if manual changes have made to it. In this case, you should %sreload the privileges%s before you continue.'; //to translate
$strGlobalPrivileges = 'Global privileges'; //to translate
$strGrantOption = 'Grant'; //to translate
$strPrivDescAllPrivileges = 'Includes all privileges except GRANT.'; //to translate
$strPrivDescAlter = 'Allows altering the structure of existing tables.'; //to translate
$strPrivDescCreateDb = 'Allows creating new databases and tables.'; //to translate
$strPrivDescCreateTbl = 'Allows creating new tables.'; //to translate
$strPrivDescCreateTmpTable = 'Allows creating temporary tables.'; //to translate
$strPrivDescDelete = 'Allows deleting data.'; //to translate
$strPrivDescDropDb = 'Allows dropping databases and tables.'; //to translate
$strPrivDescDropTbl = 'Allows dropping tables.'; //to translate
$strPrivDescExecute = 'Allows running stored procedures; Has no effect in this MySQL version.'; //to translate
$strPrivDescFile = 'Allows importing data from and exporting data into files.'; //to translate
$strPrivDescGrant = 'Allows adding users and privileges without reloading the privilege tables.'; //to translate
$strPrivDescIndex = 'Allows creating and dropping indexes.'; //to translate
$strPrivDescInsert = 'Allows inserting and replacing data.'; //to translate
$strPrivDescLockTables = 'Allows locking tables for the current thread.'; //to translate
$strPrivDescMaxConnections = 'Limits the number of new connections the user may open per hour.';
$strPrivDescMaxQuestions = 'Limits the number of queries the user may send to the server per hour.';
$strPrivDescMaxUpdates = 'Limits the number of commands that change any table or database the user may execute per hour.';
$strPrivDescProcess3 = 'Allows killing processes of other users.'; //to translate
$strPrivDescProcess4 = 'Allows viewing the complete queries in the process list.'; //to translate
$strPrivDescReferences = 'Has no effect in this MySQL version.'; //to translate
$strPrivDescReplClient = 'Gives the right to the user to ask where the slaves / masters are.'; //to translate
$strPrivDescReplSlave = 'Needed for the replication slaves.'; //to translate
$strPrivDescReload = 'Allows reloading server settings and flushing the server\'s caches.'; //to translate
$strPrivDescSelect = 'Allows reading data.'; //to translate
$strPrivDescShowDb = 'Gives access to the complete list of databases.'; //to translate
$strPrivDescShutdown = 'Allows shutting down the server.'; //to translate
$strPrivDescSuper = 'Allows connectiong, even if maximum number of connections is reached; Required for most administrative operations like setting global variables or killing threads of other users.'; //to translate
$strPrivDescUpdate = 'Allows changing data.'; //to translate
$strPrivDescUsage = 'No privileges.'; //to translate
$strPrivilegesReloaded = 'The privileges were reloaded successfully.'; //to translate
$strResourceLimits = 'Resource limits'; //to translate
$strUserOverview = 'User overview'; //to translate
$strZeroRemovesTheLimit = 'Note: Setting these options to 0 (zero) removes the limit.'; //to translate

$strPasswordChanged = 'The Password for %s was changed successfully.'; // to translate

$strDeleteAndFlush = 'Delete the users and reload the privileges afterwards.'; //to translate
$strDeleteAndFlushDescr = 'This is the cleanest way, but reloading the privileges may take a while.'; //to translate
$strDeleting = 'Deleting %s'; //to translate
$strJustDelete = 'Just delete the users from the privilege tables.'; //to translate
$strJustDeleteDescr = 'The &quot;deleted&quot; users will still be able to access the server as usual until the privileges are reloaded.'; //to translate
$strReloadingThePrivileges = 'Reloading the privileges'; //to translate
$strRemoveSelectedUsers = 'Remove selected users'; //to translate
$strRevokeAndDelete = 'Revoke all active privileges from the users and delete them afterwards.'; //to translate
$strRevokeAndDeleteDescr = 'The users will still have the USAGE privilege until the privileges are reloaded.'; //to translate
$strUsersDeleted = 'The selected users have been deleted successfully.'; //to translate
$strOriginalInterface = 'original interface';  //to translate

$strAddPrivilegesOnDb = 'Add privileges on the following database'; //to translate
$strAddPrivilegesOnTbl = 'Add privileges on the following table'; //to translate
$strColumnPrivileges = 'Column-specific privileges'; //to translate
$strDbPrivileges = 'Database-specific privileges'; //to translate
$strLocalhost = 'Local';
$strLoginInformation = 'Login Information'; //to translate
$strTblPrivileges = 'Table-specific privileges'; //to translate
$strThisHost = 'This Host'; //to translate
$strUserNotFound = 'The selected user was not found in the privilege table.'; //to translate
$strUserAlreadyExists = 'The user %s already exists!'; //to translate
$strUseTextField = 'Use text field'; //to translate
?>
