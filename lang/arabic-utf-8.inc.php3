<?php
/* $Id$ */

/**
 * Original translation to Arabic by Fisal <fisal77 at hotmail.com>
 * Update by Tarik kallida <kallida at caramail.com>
 */


$charset = 'utf-8';
$allow_recoding = TRUE;
$text_dir = 'rtl'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'Tahoma, verdana, arial, helvetica, sans-serif';
$right_font_family = '"Windows UI", Tahoma, verdana, arial, helvetica, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('بايت', 'كيلوبايت', 'ميجابايت', 'غيغابايت');

$day_of_week = array('الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت');
$month = array('يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d %B %Y الساعة %H:%M';


$strAccessDenied = 'غير مسموح';
$strAction = 'العملية';
$strAddDeleteColumn = 'إضافه/حذف عمود حقل';
$strAddDeleteRow = 'إضافه/حذف صف سجل';
$strAddNewField = 'إضافة حقل جديد';
$strAddPriv = 'إضافة إمتياز جديد';
$strAddPrivMessage = 'لقد أضفت إمتياز جديد.';
$strAddSearchConditions = 'أضف شروط البحث (جسم من الفقره "where" clause):';
$strAddToIndex = 'إضافه كفهرس &nbsp;%s&nbsp;صف(ـوف)';
$strAddUser = 'أضف مستخدم جديد';
$strAddUserMessage = 'لقد أضفت مستخدم جديد.';
$strAffectedRows = 'صفوف مؤثره:';
$strAfter = 'بعد %s';
$strAfterInsertBack = 'الرجوع إلى الصفحة السابقة';
$strAfterInsertNewInsert = 'إدخال تسجيل جديد';
$strAll = 'الكل';
$strAlterOrderBy = 'تعديل ترتيب الجدول بـ';
$strAnalyzeTable = 'تحليل الجدول';
$strAnd = 'و';
$strAnIndex = 'لقد أُضيف الفهرس في %s';
$strAny = 'أي';
$strAnyColumn = 'أي عمود';
$strAnyDatabase = 'أي قاعدة بيانات';
$strAnyHost = 'أي مزود';
$strAnyTable = 'أي جدول';
$strAnyUser = 'أي مستخدم';
$strAPrimaryKey = 'لقد أُضيف المفتاح الأساسي في %s';
$strAscending = 'تصاعدياً';
$strAtBeginningOfTable = 'في بداية الجدول';
$strAtEndOfTable = 'في نهاية الجدول';
$strAttr = 'الخواص';

$strBack = 'رجوع';
$strBinary = 'ثنائي';
$strBinaryDoNotEdit = 'ثنائي - لاتحرره';
$strBookmarkDeleted = 'لقد حُذفت العلامه المرجعيه.';
$strBookmarkLabel = 'علامه';
$strBookmarkQuery = 'علامه مرجعيه SQL-إستعلام';
$strBookmarkThis = 'إجعل علامه مرجعيه SQL-إستعلام';
$strBookmarkView = 'عرض فقط';
$strBrowse = 'إستعراض';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'لايمكن تحميل إمتداد MySQL,<br />الرجاء فحص إعدادات PHP.';
$strCantRenameIdxToPrimary = 'لايمكن تغيير إسم الفهرس إلى الأساسي!';
$strCarriage = 'إرجاع الحموله: \\r';
$strChange = 'تغيير';
$strChangePassword = 'تغيير كلمة السر';
$strCheckAll = 'إختر الكل';
$strCheckDbPriv = 'فحص إمتياز قاعدة البيانات';
$strCheckTable = 'التحقق من الجدول';
$strColumn = 'عمود';
$strColumnNames = 'إسم العمود';
$strCompleteInserts = 'الإدخال لقد إكتمل';
$strConfirm = 'هل تريد حقاً أن تفعل ذلك؟';
$strCookiesRequired = 'يجب تفعيل دعم الكوكيز في هذه المرحلة.';
$strCopyTable = 'نسخ الجدول إلى';
$strCopyTableOK = 'الجدول %s لقد تم نسخه إلى %s.';
$strCreate = 'تكوين';
$strCreateIndex = 'تصميم فهرسه على&nbsp;%s&nbsp;عمود';
$strCreateIndexTopic = 'تصميم فهرسه جديده';
$strCreateNewDatabase = 'تكوين قاعدة بيانات جديدة';
$strCreateNewTable = 'تكوين جدول جديد في قاعدة البيانات %s';
$strCriteria = 'المعايير';

$strData = 'بيانات';
$strDatabase = 'قاعدة البيانات ';
$strDatabaseHasBeenDropped = 'قاعدة بيانات %s محذوفه.';
$strDatabases = 'قاعدة بيانات';
$strDatabasesStats = 'إحصائيات قواعد البيانات';
$strDatabaseWildcard = 'قاعدة بيانات:';
$strDataOnly = 'بيانات فقط';
$strDefault = 'إفتراضي';
$strDelete = 'حذف';
$strDeleted = 'لقد تم حذف الصف';
$strDeletedRows = 'الصفوف المحذوفه:';
$strDeleteFailed = 'الحذف خاطئ!';
$strDeleteUserMessage = 'لقد حذفت المستخدم %s.';
$strDescending = 'تنازلياً';
$strDisplay = 'عرض';
$strDisplayOrder = 'ترتيب العرض:';
$strDoAQuery = 'تجعل "إستعلام بواسطة المثال" (wildcard: "%")';
$strDocu = 'مستندات وثائقيه';
$strDoYouReally = 'هل تريد حقاً تنفيذ';
$strDrop = 'حذف';
$strDropDB = 'حذف قاعدة بيانات %s';
$strDropTable = 'حذف جدول';
$strDumpingData = 'إرجاع أو إستيراد بيانات الجدول';
$strDynamic = 'ديناميكي';

$strEdit = 'تحرير';
$strEditPrivileges = 'تحرير الإمتيازات';
$strEffective = 'فعال';
$strEmpty = 'إفراغ محتوى';
$strEmptyResultSet = 'MySQL قام بإرجاع نتيجة إعداد فارغه (مثلاً. صف صفري).';
$strEnd = 'نهايه';
$strEnglishPrivileges = ' ملاحظه: إسم الإمتياز لـMySQL يظهر ويُقرأ باللغه الإنجليزيه فقط ';
$strError = 'خطأ';
$strExtendedInserts = 'إدخال مُدد';
$strExtra = 'إضافي';

$strField = 'الحقل';
$strFieldHasBeenDropped = 'حقل محذوف %s';
$strFields = ' عدد الحقول';
$strFieldsEmpty = ' تعداد الحقل فارغ! ';
$strFieldsEnclosedBy = 'حقل مضمن بـ';
$strFieldsEscapedBy = 'حقل مُتجاهل بـ';
$strFieldsTerminatedBy = 'حقل مفصول بـ';
$strFixed = 'مثبت';
$strFlushTable = 'إعادة تحميل الجدول ("FLUSH")';
$strFormat = 'صيغه';
$strFormEmpty = 'يوجد قيمه مفقوده بالنموذج !';
$strFullText = 'نصوص كامله';
$strFunction = 'دالة';

$strGenTime = 'أنشئ في';
$strGo = '&nbsp;تنفيــذ&nbsp;';
$strGrants = 'Grants';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'لقد عُدِل.';
$strHasBeenCreated = 'لقد تكون.';
$strHome = 'الصفحة الرئيسية';
$strHomepageOfficial = 'الصفحة الرئيسية الرسمية لـ phpMyAdmin';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin صفحة التنزيل';
$strHost = 'المزود';
$strHostEmpty = 'إسم المستضيف فارغ!';

$strIdxFulltext = 'النص كاملاً';
$strIfYouWish = 'إذا كنت ترغب في أن تحمل بعض أعمدة الجدول فقط, حدد بالفاصله التي تفصل قائمة الحقل.';
$strIgnore = 'تجاهل';
$strIndex = 'فهرست';
$strIndexHasBeenDropped = 'فهرسه محذوفه %s';
$strIndexName = 'إسم الفهرس&nbsp;:';
$strIndexType = 'نوع الفهرس&nbsp;:';
$strIndexes = 'فهارس';
$strInsert = 'إدخال';
$strInsertAsNewRow = 'إدخال كتسجيل جديد';
$strInsertedRows = 'صفوف مدخله:';
$strInsertNewRow = 'إضافة تسجيل جديد';
$strInsertTextfiles = 'إدخال ملف نصي في الجدول';
$strInstructions = 'الأوامر';
$strInUse = 'قيد الإستعمال';
$strInvalidName = '"%s" كلمه محجوزه, لايمكنك إستخدامها كإسم قاعدة بيانات/جدول/حقل.';

$strKeepPass = 'لاتغير كلمة السر';
$strKeyname = 'إسم المفتاح';
$strKill = 'إبطال';

$strLength = 'الطول';
$strLengthSet = 'الطول/القيمه*';
$strLimitNumRows = 'رقم السجلات لكل صفحه';
$strLineFeed = 'خطوط معرفه: \\n';
$strLines = 'خطوط';
$strLinesTerminatedBy = 'خطوط مفصوله بـ';
$strLocationTextfile = 'مكان ملف نصي';
$strLogin = 'دخول';
$strLogout = 'تسجيل خروج';
$strLogPassword = 'كلمة السر:';
$strLogUsername = 'إسم المُستخدم:';

$strModifications = 'تمت التعديلات';
$strModify = 'تعديل';
$strModifyIndexTopic = 'تعديل الفهرسه';
$strMoveTable = 'نقل جدول إلى (قاعدة بيانات<b>.</b>جدول):';
$strMoveTableOK = '%s جدول تم نقله إلى %s.';
$strMySQLReloaded = 'تم إعادة تحميل MySQL بنجاح.';
$strMySQLSaid = 'MySQL قال: ';
$strMySQLServerProcess = 'MySQL %pma_s1%  على المزود %pma_s2% -  المستخدم : %pma_s3%';
$strMySQLShowProcess = 'عرض العمليات';
$strMySQLShowStatus = 'عرض حالة المزود MySQL';
$strMySQLShowVars ='عرض متغيرات المزود MySQL';

$strName = 'الإسم';
$strNext = 'التالي';
$strNo = 'لا';
$strNoDatabases = 'لايوجد قواعد بيانات';
$strNoDropDatabases = 'معطل "حذف قاعدة بيانات"الأمر ';
$strNoFrames = 'phpMyAdmin أكثر تفهماً مع مستعرض <b>الإطارات</b>.';
$strNoIndex = 'فهرس غير معرف!';
$strNoIndexPartsDefined = 'إجزاء الفهرسه غير معرفه!';
$strNoModification = 'لا تغييرات';
$strNone = 'لاشئ';
$strNoPassword = 'لا كلمة سر';
$strNoPrivileges = 'إمتياز غير موجود';
$strNoQuery = 'ليست إستعلام SQL!';
$strNoRights = 'ليس لديك الحقوق الكافيه بأن تكون هنا الآن!';
$strNoTablesFound = 'لايوجد جداول متوفره في قاعدة البيانات هذه!.';
$strNotNumber = 'هذا ليس رقم!';
$strNotValidNumber = ' هذا ليس عدد صف صحيح!';
$strNoUsersFound = 'المستخدم(ـين) لم يتم إيجادهم.';
$strNull = 'خالي';

$strOftenQuotation = 'غالباً علامات الإقتباس. إختياري يعني بأن الحقول  char و varchar ترفق بـ " ".';
$strOptimizeTable = 'ضغط الجدول';
$strOptionalControls = 'إختياري. التحكم في كيفية كتابة أو قراءة الأحرف أو الجمل الخاصه.';
$strOptionally = 'إختياري';
$strOr = 'أو';
$strOverhead = 'الفوقي';

$strPartialText = 'نصوص جزئيه';
$strPassword = 'كلمة السر';
$strPasswordEmpty = 'كلمة السر فارغة !';
$strPasswordNotSame = 'كلمتا السر غير متشابهتان !';
$strPHPVersion = ' PHP إصدارة';
$strPmaDocumentation = 'مستندات وثائقيه لـ phpMyAdmin (بالإنجليزية)';
$strPmaUriError = 'المتغير <span dir="ltr"><tt>$cfg[\'PmaAbsoluteUri\']</tt></span> يجب تعديله في ملف الكوفيك !';
$strPos1 = 'بداية';
$strPrevious = 'سابق';
$strPrimary = 'أساسي';
$strPrimaryKey = 'مفتاح أساسي';
$strPrimaryKeyHasBeenDropped = 'لقد تم حذف المفتاح الأساسي';
$strPrimaryKeyName = 'إسم المفتاح الأساسي يجب أن يكون أساسي... PRIMARY!';
$strPrimaryKeyWarning = '("الأساسي" <b>يجب</b> يجب أن يكون الأسم <b>وأيضاً فقط</b> المفتاح الأساسي!)';
$strPrintView = 'عرض نسخة للطباعة';
$strPrivileges = 'الإمتيازات';
$strProperties = 'خصائص';

$strQBE = 'إستعلام بواسطة مثال';
$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQueryOnDb = 'في قاعدة البيانات SQL-إستعلام <b>%s</b>:';

$strRecords = 'التسجيلات';
$strReferentialIntegrity = 'تحديد referential integrity:';
$strReloadFailed = ' إعادة تحميل خاطئهMySQL.';
$strReloadMySQL = 'إعادة تحميل MySQL';
$strRememberReload = 'تذكير لإعادة تحميل الخادم.';
$strRenameTable = 'تغيير إسم جدول إلى';
$strRenameTableOK = 'تم تغيير إسمهم إلى %s  جدول%s';
$strRepairTable = 'إصلاح الجدول';
$strReplace = 'إستبدال';
$strReplaceTable = 'إستبدال بيانات الجدول بالملف';
$strReset = 'إلغاء';
$strReType = 'أعد كتابه';
$strRevoke = 'إبطال';
$strRevokeGrant = 'إبطال Grant';
$strRevokeGrantMessage = 'لقد أبطلت إمتياز Grant لـ %s';
$strRevokeMessage = 'لقد أبطلت الأمتيازات لـ %s';
$strRevokePriv = 'إبطال إمتيازات';
$strRowLength = 'طول الصف';
$strRows = 'صفوف';
$strRowsFrom = 'صفوف تبدأ من';
$strRowSize = ' مقاس الصف ';
$strRowsModeHorizontal = 'أفقي';
$strRowsModeOptions = ' %s و إعادة الرؤوس بعد %s حقل';
$strRowsModeVertical = 'عمودي';
$strRowsStatistic = 'إحصائيات';
$strRunning = ' على المزود %s';
$strRunQuery = 'إرسال الإستعلام';
$strRunSQLQuery = 'تنفيذ إستعلام/إستعلامات SQL على قاعدة بيانات %s';

$strSave = 'حفــظ';
$strSelect = 'إختيار';
$strSelectADb = 'إختر قاعدة بيانات من القائمة';
$strSelectAll = 'تحديد الكل';
$strSelectFields = 'إختيار حقول (على الأقل واحد):';
$strSelectNumRows = 'في الإستعلام';
$strSend = 'حفظ كملف';
$strServerChoice = 'إختيار الخادم';
$strServerVersion = 'إصدارة المزود';
$strSetEnumVal = 'إذا كان نوع الحقل هو "enum" أو "set", الرجاء إدخال القيم بإستخدام هذا التنسيق: \'a\',\'b\',\'c\'...<br />إذا كنت تحتاج بأن تضع علامة الشرطه المائله لليسار ("\") أو علامة الإقتباس الفرديه ("\'") فيما بين تلك القيم, إجعلها كشرطه مائله لليسار (مثلاً \'\\\\xyz\' أو \'a\\\'b\').';
$strShow = 'عرض';
$strShowAll = 'شاهد الكل';
$strShowCols = 'شاهد الأعمده';
$strShowingRecords = 'مشاهدة السجلات ';
$strShowPHPInfo = 'عرض المعلومات المتعلقة ب  PHP';
$strShowTables = 'شاهد الجدول';
$strShowThisQuery = ' عرض هذا الإستعلام هنا مرة أخرى ';
$strSingly = '(فردي)';
$strSize = 'الحجم';
$strSort = 'تصنيف';
$strSpaceUsage = 'المساحة المستغلة';
$strSQLQuery = 'إستعلام-SQL';
$strStatement = 'أوامر';
$strStrucCSV = 'بيانات CSV';
$strStrucData = 'البنية والبيانات';
$strStrucDrop = ' إضافة \'حذف جدول إذا كان موجودا\' في البداية';
$strStrucExcelCSV = 'بيانات CSV لبرنامج  Ms Excel';
$strStrucOnly = 'البنية فقط';
$strSubmit = 'إرسال';
$strSuccess = 'الخاص بك تم تنفيذه بنجاح SQL-إستعلام';
$strSum = 'المجموع';

$strTable = 'الجدول ';
$strTableComments = 'تعليقات على الجدول';
$strTableEmpty = 'إسم الجدول فارغ!';
$strTableHasBeenDropped = 'جدول %s حُذفت';
$strTableHasBeenEmptied = 'جدول %s أُفرغت محتوياتها';
$strTableHasBeenFlushed = 'لقد تم إعادة تحميل الجدول %s  بنجاح';
$strTableMaintenance = 'صيانة الجدول';
$strTables = '%s  جدول (جداول)';
$strTableStructure = 'بنية الجدول';
$strTableType = 'نوع الجدول';
$strTextAreaLength = ' بسبب طوله,<br /> فمن المحتمل أن هذا الحقل غير قابل للتحرير ';
$strTheContent = 'لقد تم إدخال محتويات ملفك.';
$strTheContents = 'لقد تم إستبدال محتويات الجدول المحدد للصفوف بالمفتاح المميز أو الأساسي المماثل لهما بمحتويات الملف.';
$strTheTerminator = 'فاصل الحقول.';
$strTotal = 'المجموع';
$strType = 'النوع';

$strUncheckAll = 'إلغاء تحديد الكل';
$strUnique = 'مميز';
$strUnselectAll = 'إلغاء تحديد الكل';
$strUpdatePrivMessage = 'لقد جددت وحدثت الإمتيازات لـ %s.';
$strUpdateProfile = 'تجديد العرض الجانبي:';
$strUpdateProfileMessage = 'لقد تم تجديد العرض الجانبي.';
$strUpdateQuery = 'تجديد إستعلام';
$strUsage = 'المساحة';
$strUseBackquotes = 'حماية أسماء الجداول و الحقول ب "`" ';
$strUser = 'المستخدم';
$strUserEmpty = 'إسم المستخدم فارغ!';
$strUserName = 'إسم المستخدم';
$strUsers = 'المستخدمين';
$strUseTables = 'إستخدم الجدول';

$strValue = 'القيمه';
$strViewDump = 'عرض بنية الجدول ';
$strViewDumpDB = 'عرض بنية قاعدة البيانات';

$strWelcome = 'أهلاً بك في %s';
$strWithChecked = ': على المحدد';
$strWrongUser = 'خطأ إسم المستخدم/كلمة السر. الدخول ممنوع.';

$strYes = 'نعم';

$strZip = '"zipped" "مضغوط"';

// To translate
$strCardinality = 'Cardinality';
$strLinksTo = 'Links to';  //to translate
$strExport = 'Export';  //to translate
$strOperations = 'Operations';  //to translate
$strExportToXML = 'Export to XML format'; //to translate
$strOptions = 'Options';  //to translate
$strStructure = 'Structure';  //to translate
$strRelationView = 'Relation view';  //to translate
$strDumpXRows = 'Dump %s rows starting at row %s.'; //to translate
$strSQL = 'SQL'; //to translate
$strLinkNotFound = 'Link not found';  //to translate
$strConfigureTableCoord = 'Please configure the coordinates for table %s';  //to translate
$strScaleFactorSmall = 'The scale factor is too small to fit the schema on one page';  //to translate
$strDisplayPDF = 'Display PDF schema';  //to translate
$strPageNumber = 'Page number:';  //to translate
$strShowGrid = 'Show grid';  //to translate
$strShowColor = 'Show color';  //to translate
$strShowTableDimension = 'Show dimension of tables';  //to translate
$strPdfInvalidPageNum = 'Undefined PDF page number!';  //to translate
$strPdfInvalidTblName = 'The "%s" table does not exist!';  //to translate
$strChangeDisplay = 'Choose Field to display';  //to translate
$strNumSearchResultsInTable = '%s match(es) inside table <i>%s</i>';//to translate
$strNumSearchResultsTotal = '<b>Total:</b> <i>%s</i> match(es)';//to translate
$strSearch = 'Search';//to translate
$strSearchFormTitle = 'Search in database';//to translate
$strSearchInTables = 'Inside table(s):';//to translate
$strSearchNeedle = 'Word(s) or value(s) to search for (wildcard: "%"):';//to translate
$strSearchOption1 = 'at least one of the words';//to translate
$strSearchOption2 = 'all words';//to translate
$strSearchOption3 = 'the exact phrase';//to translate
$strSearchOption4 = 'as regular expression';//to translate
$strSearchResultsFor = 'Search results for "<i>%s</i>" %s:';//to translate
$strSearchType = 'Find:';//to translate
$strSplitWordsWithSpace = 'Words are seperated by a space character (" ").';//to translate
$strStructPropose = 'Propose table structure';  //to translate
$strExplain = 'Explain SQL Code';  //to translate
$strPhp = 'Create PHP Code';  //to translate
$strNoPhp = 'without PHP Code';  //to translate
$strPdfDbSchema = 'Schema of the the "%s" database - Page %s';  //to translate
$strGenBy = 'Generated by'; //to translate
$strSQLResult = 'SQL result'; //to translate
$strEditPDFPages = 'Edit PDF Pages';  //to translate
$strNoDescription = 'no Description';  //to translate
$strChoosePage = 'Please choose a Page to edit';  //to translate
$strCreatePage = 'Create a new Page';  //to translate
$strSelectTables = 'Select Tables';  //to translate
$strConfigFileError = 'phpMyAdmin was unable to read your configuration file!<br />This might happen if php finds a parse error in it or php cannot find the file.<br />Please call the configuration file directly using the link below and read the php error message(s) that you recieve. In most cases a quote or a semicolon is missing somewhere.<br />If you recieve a blank page, everything is fine.'; //to translate
$strNotSet = '<b>%s</b> table not found or not set in %s';  //to translate
$strMissingBracket = 'Missing Bracket';  //to translate
$strHaveToShow = 'You have to choose at least one Column to display';  //to translate
$strCantLoadRecodeIconv = 'Can not load iconv or recode extension needed for charset conversion, configure php to allow using these extensions or disable charset conversion in phpMyAdmin.';  //to translate
$strCantUseRecodeIconv = 'Can not use iconv nor libiconv nor recode_string function while extension reports to be loaded. Check your php configuration.';  //to translate
$strMySQLCharset = 'MySQL Charset';  //to translate
$strComments = 'Comments';  //to translate
$strRelationNotWorking = 'The additional Features for working with linked Tables have been deactivated. To find out why click %shere%s.';  //to translate
$strAllTableSameWidth = 'display all Tables with same width?';  //to translate
?>
