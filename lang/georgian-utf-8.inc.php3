<?php
/* $Id$ */

/**
 * Translation by Kakha Mchedlidze <kakha at qartuli.com>
 *
 * It requires some special Unicode font faces that can downloaded at
 * http://www.main.osgf.ge/eng/dounen.htm
 * http://www.osgf.ge/resources/fonts/sylfaen.zip
 */

$charset = "utf-8";
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = "Sylfaen";
$right_font_family = "Sylfaen";
$number_thousands_separator = " ";
$number_decimal_separator = ",";
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
$byteUnits = array("ბაიტი", "KB", "MB", "GB");

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';

$strAccessDenied = 'აკრძალულია';
$strAction = 'მოქმედება';
$strAddDeleteColumn = 'დაამატე/წაშალე სვეტის ველები';
$strAddDeleteRow = 'დაამატე/წაშალე სტრიქონის კრიტერია';
$strAddNewField = 'ახალი ველის დამატება.';
$strAddPriv = 'ახალი პრივილეგიის დამატება.';
$strAddPrivMessage = 'თქვენ დაამატეთ ახალი პრივილეგია.';
$strAddSearchConditions = 'ძებნის პარამეტრების დამატება ("where" ნაწილის ტანი):';
$strAddToIndex = '&nbsp;%s&nbsp;ამ ინდექსში სვეტის(სვეტების) დამატება';
$strAddUser = 'ახალი მომხმარებლის დამატება.';
$strAddUserMessage = 'თქვენ დაამატეთ ახალი მომხმარებელი.';
$strAffectedRows = 'გააქტიურებული რიგები:';
$strAfter = '%s შემდეგ';
$strAfterInsertBack = 'წინა გვერდზე დაბრუნება';
$strAfterInsertNewInsert = 'ახალი სვეტის ჩამატება';
$strAll = 'ყველა';
$strAlterOrderBy = 'შეცვლილი ცხრილი სორტირებული';
$strAnalyzeTable = 'ცხრილის ანალიზი';
$strAnd = 'და';
$strAnIndex = 'ინდექსი დამატებულია ველზე %s';
$strAny = 'ნებისმიერი.';
$strAnyColumn = 'ნებისმიერი სვეტი';
$strAnyDatabase = 'ნებისმიერი მონაცემთა ბაზა';
$strAnyHost = 'ნებისმიერი ჰოსტი';
$strAnyTable = 'ნებისმიერი ცხრილი';
$strAnyUser = 'ნებისმიერი მომხმარებელი';
$strAPrimaryKey = 'პირველადი გასაღები დამატებულია ველზე %s';
$strAscending = 'ამომავალი';
$strAtBeginningOfTable = 'ცხრილის დასაწყისში';
$strAtEndOfTable = 'ცხრილის დასასრულში';
$strAttr = 'ატრიბუტები';

$strBack = 'უკან';
$strBinary = 'ბინარული';
$strBinaryDoNotEdit = 'ბინარული - არ რედაქტირდება';
$strBookmarkDeleted = 'სანიშნი წაიშალა.';
$strBookmarkLabel = 'ჭდე';
$strBookmarkQuery = 'SQL-შეკითხვის(მოთხოვნის) სანიშნი';
$strBookmarkThis = 'მოცემული SQL-შეკითხვის(მოთხოვნის) სანიშნი';
$strBookmarkView = 'მხოლოდ დათვალიერება';
$strBrowse = 'ნახვა';
$strBzip = '"bzip შეკუმშვა"';

$strCantLoadMySQL = 'MySQL გაფართოება არ ჩაიტვირტა,<br />გთხოვთ შეამოწმეთ PHP კონფიგურაცია.';
$strCantLoadRecodeIconv = 'ვერ ჩაიტვირთა iconv,რაც საჭიროა charset-ის ასამუშავებლად, შეცვალეთ php-ს კონფიგურირება თუ გინდათ ამ ფუნქციის გამოყენება, ან გამორთეთ charset ფუნქცია phpMyAdmin-ში';
$strCantRenameIdxToPrimary = 'PRIMARY-ში ინდექსის სახელის შეცვლა შეუძლებელია!';
$strCantUseRecodeIconv = 'iconv-ს ან libiconv-ს და recode_string-ს ვერ იყენებს, მაშინ როდესაც ფუნქცია ჩატვირთულია. შეამოწმეთ php კონფიგურაცია.';
$strCardinality = 'ელემენტების რაოდენობა';
$strCarriage = 'კურსორის გადატანა: \\r';
$strChange = 'შეცვლა';
$strChangeDisplay = 'აირჩიეთ მონაცემი გვერდზე გამოსაჩენად';
$strChangePassword = 'შეცვალე პაროლი';
$strCheckAll = 'მონიშნე ყველა';
$strCheckDbPriv = 'შეამოწმეთ მონაცემთა ბაზის პრივილეგიები';
$strCheckTable = 'ცხრილის შემოწმება';
$strChoosePage = 'აირჩიეთ გვერდი რედაქტირებისთვის';
$strColumn = 'სვეტი';
$strColumnNames = 'სვეტის სახელები';
$strComments = 'კომენტარი';
$strCompleteInserts = 'სრულყოფილი ჩამატება';
$strConfigFileError = 'phpMyAdmin-მა ვერ შეძლო კონფიგურაციის ფაილის წაკითხვა!<br/>ეს მაშინ ხდება თუ php-მ იპოვა parse შეცდომა, ან php-მ ვერ იპოვა ფაილი.<br />გამოიძახეთ კონფიგურაციის ფაილი და ქვევით ჩამოწერილი შეცდომები გაასწორეთ. უმეტეს შემთხვევაში წერტილ-მძიმე აკლია ხოლმე.<br />თუ ცარიელი გვერდი ჩამოიტვირთა, ესეიგი ყველაფერი რიგზეა.';
$strConfigureTableCoord = 'საჭიროა %s ცხრილის კოორდინატების კონფიგურირება';
$strConfirm = 'თქვენ დარწმუნებული ხართ რომ გინდათ ამის გაკეთება?';
$strCookiesRequired = 'ამ ადგილის შემდეგ Cookies უნდა ჩართოთ.';
$strCopyTable = 'ცხრილის კოპირება (ბაზა<b>.</b>ცხრილი):';
$strCopyTableOK = 'ცხრილი %s კოპირებულია %s ცხრილში.';
$strCreate = 'შექმნა';
$strCreateIndex = '&nbsp;%s&nbsp;ინდექსის შექმნა სვეტებზე';
$strCreateIndexTopic = 'ახალი ინდექსის შექმნა';
$strCreateNewDatabase = 'ახალი მონაცემთა ბაზის შექმნა';
$strCreateNewTable = 'მონაცემთა ბაზაში ახალი ცხრილის შექმნა %s';
$strCreatePage = 'შექმენი ახალი გვერდი';
$strCriteria = 'კრიტერია';

$strData = 'მონაცემები';
$strDatabase = 'მონაცემთა ბაზა ';
$strDatabaseHasBeenDropped = 'მონაცემთა ბაზა %s წაიშალა.';
$strDatabases = 'ბაზები';
$strDatabasesStats = 'მონაცემთა ბაზის სტატისტიკა';
$strDatabaseWildcard = 'მონაცემთა ბაზა (wildcards allowed):';
$strDataOnly = 'მხოლოდ მონაცემები';
$strDefault = 'ავტო მნიშვნელობა';
$strDelete = 'წაშლა';
$strDeleted = 'სტრიქონი წაიშალა';
$strDeletedRows = 'სტრიქონები წაიშალა:';
$strDeleteFailed = 'წაშლილი ველი!';
$strDeleteUserMessage = 'თქვენ წაშალეთ მომხმარებელი %s.';
$strDescending = 'შუთავსებელი';
$strDisplay = 'აჩვენე';
$strDisplayOrder = 'დათვალიერების წესი:';
$strDisplayPDF = 'PDF სქემის ჩვენება';
$strDoAQuery = 'შეასრულე "მოთხოვნა მაგალითის მოხედვით" (ნებისმიერი სიმბოლოს აღმნიშვნელია: "%")';
$strDocu = 'დოკუმენტაცია';
$strDoYouReally = 'დარწმუნებული ხართ, რომ გინდათ ';
$strDrop = 'წაშლა';
$strDropDB = 'წაშალე მონაცემთა ბაზა %s';
$strDropTable = 'სვეტის წაშლა';
$strDumpingData = 'მონაცემები ცხრილიდან ';
$strDynamic = 'დინამიური';

$strEdit = 'შესწორება';
$strEditPDFPages = 'PDF გვერდების რედაქტირება';
$strEditPrivileges = 'პრივილეგიების რედაქტირება';
$strEffective = 'ეფექტური';
$strEmpty = 'ცარიელი';
$strEmptyResultSet = 'MySQL-ის მიერ დააბრუნებული ჩანაწერების რაოდენობაა 0.';
$strEnd = 'დასასრული';
$strEnglishPrivileges = ' შენიშვნა: MySQL-ის პრივილეგიები ენიჭება ინგლისურად ';
$strError = 'შეცდომა';
$strExport = 'ექსპორტი';
$strExtendedInserts = 'ჩამატების გაფართოება';
$strExtra = 'სხვა';

$strField = 'ველი';
$strFieldHasBeenDropped = 'ველი %s წაიშალა';
$strFields = 'ველები';
$strFieldsEmpty = ' ველების მთვლელი ცარიელია! ';
$strFieldsEnclosedBy = 'ველები ჩაკეტილია by';
$strFieldsEscapedBy = 'ველები გახსნილია by';
$strFieldsTerminatedBy = 'ველები განცალკავებულია by';
$strFixed = 'გამართულია';
$strFlushTable = 'კეში გადატანა ("FLUSH") ცხრილში';
$strFormat = 'ფორმატი';
$strFormEmpty = 'საჭიროა ფორმის აღმნიშვნელები!';
$strFullText = 'სრული ტექსტი';
$strFunction = 'ფუნქცია';

$strGenBy = 'შექმნილია by';
$strGenTime = 'შექმნის დრო';
$strGo = 'შესრულება';
$strGrants = 'უფლებები';
$strGzip = '"gzip-ში შეკუმშვა"';

$strHasBeenAltered = 'შეიცვალა.';
$strHasBeenCreated = 'შეიქმნა.';
$strHaveToShow = 'თქვენ ერთი ცხრილი მაინც უნდა აირჩიოთ';
$strHome = 'დასაწყისი';
$strHomepageOfficial = 'phpMyAdmin ოფიციალური ვებგვერდი';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Download გვერდი';
$strHost = 'ჰოსტი';
$strHostEmpty = 'ჰოსტის სახელი ცარიელია!';

$strIdxFulltext = 'სრული ტექსტი';
$strIfYouWish = 'თუ თქვენ მხოლოდ რამოდენიმე სვეტის მონაცემების ჩატვირთვა, მიუთითეთ მძიმეებით გამოყოფილი ველების ჩამონათვალი.';
$strIgnore = 'იგნორირება';
$strIndex = 'ინდექსირება';
$strIndexes = 'ინდექსები';
$strIndexHasBeenDropped = 'ინდექსი %s წაიშალა';
$strIndexName = 'ინდექსის სახელი&nbsp;:';
$strIndexType = 'ინდექსის ტიპი&nbsp;:';
$strInsert = 'დამატება';
$strInsertAsNewRow = 'დამატება ახალ ჩანაწერად';
$strInsertedRows = 'სტრიქონების დამატება:';
$strInsertNewRow = 'დაამატე ახალი სტრიქონი';
$strInsertTextfiles = 'ჩაამატე ტექსტური ფაილები ცხრილში';
$strInstructions = 'ინსტრუქცია';
$strInUse = 'გამოყენებულია';
$strInvalidName = '"%s" ეს რეგისტირებული სიტყვაა, შენ არ შეგიძლიათ ის გამოიყენო მონაცემთა ბაზის/ცხრილის/ველის სახელად.';

$strKeepPass = 'არ შეცვალო ეს პაროლი';
$strKeyname = 'Keyname';
$strKill = 'Kill';

$strLength = 'სიგრძე';
$strLengthSet = 'სიგრძე/მნიშვნელობა*';
$strLimitNumRows = 'სტრიქონის რაოდენობა თითოეულ გვერდზე';
$strLineFeed = 'ახალი ხაზი: \\n';
$strLines = 'სტრიქონები(ჩანაწერები) ';
$strLinesTerminatedBy = 'სტრიქონები დაყოფილია by';
$strLinkNotFound = 'ლინკი ვერ ვიპოვე';
$strLinksTo = 'ლინკები';
$strLocationTextfile = 'მიუთითეთ ტექსტური ფაილის მდებარეობა';
$strLogin = 'ლოგინი';
$strLogout = 'გასვლა';
$strLogPassword = 'პაროლი:';
$strLogUsername = 'სახელი:';

$strMissingBracket = 'ბრჭყალები არ არსებობს';
$strModifications = 'ცვლილებები შენახულია';
$strModify = 'შეცვალე';
$strModifyIndexTopic = 'ინდექსის შეცვლა';
$strMoveTable = 'გადაიტანე ცხრილები (მონაცემთა ბაზა<b>.</b>ცხრილი):';
$strMoveTableOK = 'ცხრილი %s გადატანილია %s ში.';
$strMySQLCharset = 'MySQL Charset-ი';
$strMySQLReloaded = 'MySQL გადაიტვირთა.';
$strMySQLSaid = 'MySQL-მა თქვა: ';
$strMySQLServerProcess = 'MySQL %pma_s1% მუშაობს on %pma_s2% როგორც %pma_s3%';
$strMySQLShowProcess = 'პროცესების შვენება';
$strMySQLShowStatus = 'MySQL მონაცემთა ბაზის მდგომარეობის ჩვენება';
$strMySQLShowVars = 'MySQL მონაცემთა ბაზის სისტემური ცვლადები';

$strName = 'სახელი';
$strNbRecords = 'სტრიქონების რაოდენობა';
$strNext = 'შემდეგი';
$strNo = 'არა';
$strNoDatabases = 'ცარიელია';
$strNoDescription = 'შინაარსი არ არის';
$strNoDropDatabases = '"DROP DATABASE" ოპერატორები გათიშულია.';
$strNoFrames = 'phpMyAdmin-თან სამუშაოდ საჭიროა ისეთი ბროუზერი რომელიც <b>ფრეიმებთან</b> მუშაობს.';
$strNoIndex = 'ინდექსი არ არსებობს!';
$strNoIndexPartsDefined = 'ინდექსის ნაწილები არ არსებობს!';
$strNoModification = 'ცვლილებები არ მომხდარა';
$strNone = 'არა';
$strNoPassword = 'არ არის პარილი';
$strNoPhp = 'PHP კოდის გარეშე';
$strNoPrivileges = 'არ არის პრივილეგიები';
$strNoQuery = 'SQL შეკითხვა არ არსებობს!';
$strNoRights = 'თქვენ არაგაქვთ ამის უფლება!';
$strNoTablesFound = 'მონაცემთა ბაზა არ შეიცავს ცხრილებს.';
$strNotNumber = 'ეს რიცხვი არაა!';
$strNotSet = '<b>%s</b> ცხრილი ვერ ვიპვე ან უწესრიგობაა %s-ში';
$strNotValidNumber = ' სტრიქონების მიუწვდომელი რაოდენობა!';
$strNoUsersFound = 'მომხმარებელი არ არის ნაპოვნი.';
$strNull = 'ნული';
$strNumSearchResultsInTable = '%s შესაბამისობა ცხრილის შიგნით<i>%s</i>';
$strNumSearchResultsTotal = '<b>სულ:</b> <i>%s</i> შესაბამისობა';

$strOftenQuotation = 'ველების მნიშვნელობები მოთავსდება ამ სიმბოლოებში OPTIONALLY ნიშნავს რომ მხოლოდ char და varchar ტიპის ველების მნიშვნელობები მოთავსდება მითითებულ სიმბოლოებში.';
$strOperations = 'ოპერაციები';
$strOptimizeTable = 'ცხრილის ოპტიმიზაცია';
$strOptionalControls = 'არააუცილებელია. განსაზღვრავს როგორ უნდა იქნას ჩაწერილი და წაკითხული სპეციალური სიმბოლოები.';
$strOptionally = 'აქრჩევანის მიხედვით';
$strOptions = 'ოფციები';
$strOr = 'ან';
$strOverhead = 'ზედმეტი';

$strPageNumber = 'გვერდის ნომერი:';
$strPartialText = 'ტექსტების ნაწილი';
$strPassword = 'პაროლი';
$strPasswordEmpty = 'პაროლი ცარიელია!';
$strPasswordNotSame = 'პაროლები განსხვავდება!';
$strPdfDbSchema = '"%s"-ს სქემა %s მონაცემთა ბაზაში';
$strPdfInvalidPageNum = 'PDF გვერდების რაოდენობა გაურკვეველია!';
$strPdfInvalidTblName = 'The "%s" table does not exist!';
$strPhp = 'PHP კოდის შექმნა';
$strPHPVersion = 'PHP ვერსია';
$strPmaDocumentation = 'phpMyAdmin-ის დოკუმენტაცია';
$strPmaUriError = 'დირექტივა <tt>$cfgPmaAbsoluteUri</tt> უნდა დაყენდეს კონფიგურაციის ფაილში!';
$strPos1 = 'დასაწყისი';
$strPrevious = 'წინა';
$strPrimary = 'პირველადი';
$strPrimaryKey = 'პირველადი ველი';
$strPrimaryKeyHasBeenDropped = 'პირველი გასაღები წაშლილია';
$strPrimaryKeyName = 'პირველი გასაღების სახელი უნდა იყოს PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>უნდა იყოს მხოლოდ</b> პირველი გასაღების სახელი!)';
$strPrintView = 'ბეჭდვისთვის';
$strPrivileges = 'პრივილეგიები';
$strProperties = 'თვისებები';

$strQBE = 'ამორჩევა მაგალითის მიხედვით';
$strQBEDel = 'წაშლა';
$strQBEIns = 'დამატება';
$strQueryOnDb = 'SQL-შეკითხვა <b>%s</b> მონაცემთა ბაზაში:';

$strRecords = 'ჩანაწერები';
$strReferentialIntegrity = 'მონაცემთა შემოწმება:';
$strRelationView = 'ურთიერთობათა სახე';
$strReloadFailed = 'MySQL წარუმატებლად გადაიტვირთა.';
$strReloadMySQL = 'MySQL-ის გადატვირთვა';
$strRememberReload = 'არ დაგავიწყდეთ სერვერის გადატვირთვა.';
$strRenameTable = 'სახელის შეცვლა';
$strRenameTableOK = 'ცხრილი %s გადაკეთდა %s-დ';
$strRepairTable = 'ცხრილის აღდგენა';
$strReplace = 'შეცვლა';
$strReplaceTable = 'შეცვალე ცხრილი მონაცემებით შემდეგი ფაილიდან';
$strReset = 'საწყისი მნიშვნელობები';
$strReType = 'დამოწმება';
$strRevoke = 'გაუქმება';
$strRevokeGrant = 'უფლებების გაუქმება';
$strRevokeGrantMessage = 'უფლებების პრივილეგია გაუუქმდა %s-ს';
$strRevokeMessage = 'თქვენ შეცვალეთ პრივიკებიები %s-სთვის';
$strRevokePriv = 'პრივილეგიების შეცვლა';
$strRowLength = 'სტრიქონის სიგრძე ';
$strRows = 'ჩანაწერები';
$strRowsFrom = 'სტრიქონი. საწყისი სტრიქონი:';
$strRowSize = ' სტრიქონის ზომა ';
$strRowsModeHorizontal = 'ჰორიზონტალური';
$strRowsModeOptions = '%s-ს რეჟიმში, სათაურები %s სვეტების სემდეგ';
$strRowsModeVertical = 'ვერტიკალური';
$strRowsStatistic = 'სტრიქონის სტატისტიკა';
$strRunning = 'გაშვებულია ჰოსტზე %s';
$strRunQuery = 'სესრულება';
$strRunSQLQuery = 'შეასრულე SQL მოთხოვნა/მოთხოვნები მონაცემთა ბაზაზე %s';

$strSave = 'შენახვა';
$strScaleFactorSmall = 'მაშტაბის ფაქტორი ძალიან პატარაა იმისთვის, რომ გვერდის სქემაში აისახოს';
$strSearch = 'ძებნა';
$strSearchFormTitle = 'ძებნა მონაცემთა ბაზაში';
$strSearchInTables = 'Inside ცხრილი:';
$strSearchNeedle = 'საძიებელი სიტყვები ან მნიშვნელობები (wildcard: "%"):';
$strSearchOption1 = 'ერთი სიტყვა მაინც';
$strSearchOption2 = 'ყველა სიტყვა';
$strSearchOption3 = 'ზუსტი ფრაზა';
$strSearchOption4 = 'როგორც სწორი ფრაზა';
$strSearchResultsFor = 'ძებნის შედეგი "<i>%s</i>" %s:';
$strSearchType = 'ძიება:';
$strSelect = 'ამორჩევა';
$strSelectADb = 'გთხოვთ მონიშნეთ მონაცემთა ბაზა';
$strSelectAll = 'ყველას მონიშვნა';
$strSelectFields = 'აირჩიეთ ველები (მინიმუმ ერთი მაინც):';
$strSelectNumRows = 'მოთხოვნაში';
$strSelectTables = 'ცხრილის მონიშვნა';
$strSend = 'ფაილად შენახვა';
$strServerChoice = 'სერვერის არჩევა';
$strServerVersion = 'სერვერის ვერსია';
$strSetEnumVal = '"enum" ან "set" ტიპის ველებისათვის მონაცემები შეიყვანეთ შემდეგი ფორმატის მიხედვით: \'a\',\'b\',\'c\'...<br />თუ თქვენ დაგჭირდებათ დახრილი ხაზის ("\") ან დახრილი ხაზისა და აპოსტროფის ("\'") შეყვანა, ამ სიმბოლოების წინ და შორის ჩასვით დახრილი ხაზი ისე როგორც აქაა (for example \'\\\\xyz\' or \'a\\\'b\').';
$strShow = 'გამოიტანე';
$strShowAll = 'ყველას დათვალიერება';
$strShowColor = 'ფერების ჩვენება';
$strShowCols = 'სვეტების დათვალიერება';
$strShowGrid = 'ჩვენების ბადე';
$strShowingRecords = 'ნაჩვენებია ჩანაწერები ';
$strShowPHPInfo = 'PHP ინფორმაცია';
$strShowTableDimension = 'ცხრილის ჩვენების ცვლილება';
$strShowTables = 'ცხრილების დათვალიერება';
$strShowThisQuery = ' მოცემული შეკითხვის ხელახლა ჩვენება ';
$strSingly = '(ცალკე)';
$strSize = 'ზომა';
$strSort = 'სორტირება';
$strSpaceUsage = 'გამოყენებული სივრცე';
$strSplitWordsWithSpace = 'სიტყვები არის დაშლილია ცალკეულ სიმბოლოენად (" ").';
$strSQL = 'SQL-ი';
$strSQLQuery = 'SQL-ის ამორჩევა';
$strSQLResult = 'SQL შედეგი';
$strStartingRecord = 'სტრიქონის ჩაწერის დაწყება';
$strStatement = 'აღწერა';
$strStrucCSV = 'CSV მონაცემები';
$strStrucData = 'სტრუქტურა და მონაცემები';
$strStrucDrop = 'არსებულის წაშლა და დამატება';
$strStrucExcelCSV = 'CSV Ms Excel-ის მონაცემებისთვის ';
$strStrucOnly = 'მხოლოდ სტრუქტურა';
$strStructPropose = 'ცხრილის სტრუქტურის შეთავაზება';
$strStructure = 'სტრუქტურა';
$strSubmit = 'თანხმობა';
$strSuccess = 'თქვენი SQL მოთხოვნა წარმატებით შესრულდა';
$strSum = 'ჯამი';

$strTable = 'ცხრილი ';
$strTableComments = 'კომენტარი ცხრილზე';
$strTableEmpty = 'ცხრილის სახელი არა არის მითითებული!';
$strTableHasBeenDropped = 'ცხრილი %s წაიშალა';
$strTableHasBeenEmptied = 'ცხრილი %s დაცარიელდა';
$strTableHasBeenFlushed = 'ცხრილი %s კეშირებულია';
$strTableMaintenance = 'ცხრილის მომსახურება';
$strTables = '%s ცხრილი';
$strTableStructure = 'ცხრილის სტრუქტურა. ცხრილი:';
$strTableType = 'ცხრილის ტიპი';
$strTextAreaLength = ' მისი სიგრძის გამო,<br /> ეს ველი შეიძლება არ არის რედაქტირებადი ';
$strTheContent = 'ფაილის შემცველობა დამატებულ იქნა.';
$strTheContents = 'ცხრილის ის ჩანაწერები, რომლებსაც ჰქონდათ იდენტური პირველადი ან უნიკალური გასაღები შეცვლილია ფაილის შემცველობით.';
$strTheTerminator = 'ველების ტერმინატორი.';
$strTotal = 'სულ ცხრილში';
$strType = 'ტიპი';

$strUncheckAll = 'Uncheck All';
$strUnique = 'უნიკალური';
$strUnselectAll = 'მონიშვნის გაუქმება';
$strUpdatePrivMessage = 'პრივილეგიები განახლდა %s-სთვის.';
$strUpdateProfile = 'პროფაილის განახლება:';
$strUpdateProfileMessage = 'პროფაილი განახლდა.';
$strUpdateQuery = 'შეკითხვის (მოთხოვნის) განახლება';
$strUsage = 'მოცულობა';
$strUseBackquotes = 'შებრუნებული ბრჭყალები';
$strUser = 'მომხმარებელი';
$strUserEmpty = 'მომხმარებლის სახელი ცარიელია!';
$strUserName = 'მომხმარებლის სახელი';
$strUsers = 'მომხმარებლები';
$strUseTables = 'მომხმარებლის ცხრილები';

$strValue = 'მნიშვნელობა';
$strViewDump = 'ცხრილისი სქემა';
$strViewDumpDB = 'მონაცემთა ბაზის სქემა';

$strWelcome = 'კეთილი იყოს თქვენი მობრძანება %s';
$strWithChecked = 'მონიშნულებთან:';
$strWrongUser = 'არასწორი სახელი/პაროლი. მიმართვა ბლოკირებულია';

$strYes = 'კი';

$strZip = '"zip-ში შეკუმშვა"';

$strAllTableSameWidth = 'display all Tables with same width?';  //to translate

$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCharsetOfFile = 'Character set of the file:'; //to translate
$strColComFeat = 'Displaying Column Comments';  //to translate
$strCreatePdfFeat = 'Creation of PDFs';  //to translate

$strDisabled = 'Disabled';  //to translate
$strDisplayFeat = 'Display Features';  //to translate

$strEnabled = 'Enabled';  //to translate
$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'Explain SQL';  //to translate

$strGeneralRelationFeat = 'General relation features';  //to translate

$strNoExplain = 'Skip Explain SQL';  //to translate
$strNotOK = 'not OK';  //to translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate

$strOK = 'OK';  //to translate

$strPdfNoTables = 'No tables';  //to translate

$strRelationNotWorking = 'The additional Features for working with linked Tables have been deactivated. To find out why click %shere%s.';  //to translate

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
?>
