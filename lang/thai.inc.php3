<?php
/* $Id$ */


/**
 * Translated on 2002/04/29 by: Arthit Suriyawongkul
 *                              Warit Wanasathian
 *
 * Revised on 2002/06/05 by: Arthit Suriyawongkul
 */


// note: Thai has 2 standard encodings (tis-620, iso-8859-11)
$charset = 'tis-620';
$text_dir = 'ltr';
$left_font_family = 'sans-serif';
$right_font_family = 'sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('ไบต์', 'กิโลไบต์', 'เมกกะไบต์', 'กิกะไบต์');

$day_of_week = array('อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.');
$month = array('ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%e %B %Y  %Rน.';


$strAccessDenied = 'ไม่อนุญาตให้ใช้งาน';
$strAction = 'กระทำการ';
$strAddDeleteColumn = 'เพิ่ม/ลบ คอลัมน์ (ฟิลด์)';
$strAddDeleteRow = 'เพิ่ม/ลบ แถว';
$strAddNewField = 'เพิ่มฟิลด์ใหม่';
$strAddPriv = 'เพิ่มสิทธิ';
$strAddPrivMessage = 'เพิ่มสิทธิเรียบร้อยแล้ว';
$strAddSearchConditions = 'เพิ่มเงื่อนไขในการค้นหา:';
$strAddToIndex = 'เพิ่มดัชนีคอลัมน์ %s';
$strAddUser = 'เพิ่มผู้ใช้ใหม่';
$strAddUserMessage = 'เพิ่มผู้ใช้ใหม่เรียบร้อยแล้ว';
$strAffectedRows = 'แถวที่ถูกกระทบ:';
$strAfter = 'หลัง %s';
$strAfterInsertBack = 'ส่งกลับ';
$strAfterInsertNewInsert = 'แทรกระเบียนใหม่';
$strAll = 'ทั้งหมด';
$strAlterOrderBy = 'เรียงค่าในตารางตาม';
$strAnalyzeTable = 'วิเคราะห์ตาราง';
$strAnd = 'และ';
$strAnIndex = 'ได้เพิ่มดัชนีแล้วใน %s';
$strAny = 'ใดๆ';
$strAnyColumn = 'คอลัมน์ใดๆ';
$strAnyDatabase = 'ฐานข้อมูลใดๆ';
$strAnyHost = 'โฮสต์ใดๆ';
$strAnyTable = 'ตารางใดๆ';
$strAnyUser = 'ผู้ใช้ใดๆ';
$strAPrimaryKey = 'ได้เพิ่ม primary key แล้วใน %s';
$strAscending = 'น้อยไปมาก';
$strAtBeginningOfTable = 'ที่จุดเริ่มต้นของตาราง';
$strAtEndOfTable = 'ที่จุดสุดท้ายของตาราง';
$strAttr = 'แอตทริบิวต์';

$strBack = 'ย้อนกลับ';
$strBinary = ' ข้อมูลไบนารี ';
$strBinaryDoNotEdit = ' ข้อมูลไบนารี - ห้ามแก้ไข ';
$strBookmarkDeleted = 'ลบคำค้นที่จดไว้เรียบร้อยแล้ว';
$strBookmarkLabel = 'ป้ายชื่อ';
$strBookmarkQuery = 'คำค้นนี้ถูกจดไว้';
$strBookmarkThis = 'จดคำค้นนี้ไว้';
$strBookmarkView = 'ดูอย่างเดียว';
$strBrowse = 'เปิดดู';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'ไม่สามารถเรียกใช้ MySQL extension,<br />กรุณาตรวจสอบการตั้งค่าของ PHP';
$strCantRenameIdxToPrimary = 'เปลี่ยนชื่อดัชนีเป็น PRIMARY ไม่ได้!';
$strCardinality = 'Cardinality';
$strCarriage = 'ปัดแคร่: \\r';
$strChange = 'เปลี่ยน';
$strChangeDisplay = 'เลือกฟิลด์ที่ต้องการแสดง';
$strChangePassword = 'เปลี่ยนรหัสผ่าน';
$strCheckAll = 'เลือกทั้งหมด';
$strCheckDbPriv = 'ตรวจสอบสิทธิในฐานข้อมูล';
$strCheckTable = 'ตรวจสอบตาราง';
$strColumn = 'คอลัมน์';
$strColumnNames = 'ชื่อคอลัมน์';
$strCompleteInserts = 'คำสั่ง INSERT เต็มรูปแบบ';
$strConfigureTableCoord = 'กรุณาตั้งค่าโคออร์ดิเนตของตาราง %s';
$strConfirm = 'คุณยืนยันที่จะทำสิ่งนี้?';
$strCookiesRequired = 'ต้องอนุญาตใช้ใช้ \'คุ๊กกี้\' เสียก่อน จึงจะผ่านจุดนี้ไปได้';
$strCopyTable = 'คัดลอกตารางไปยัง (database<b>.</b>table):';
$strCopyTableOK =  'คัดลอกตาราง %s ไปเก็บในชื่อ %s เรียบร้อยแล้ว.';
$strCreate = 'สร้าง';
$strCreateIndex = 'สร้างดัชนีโดยคอลัมน์ %s';
$strCreateIndexTopic = 'สร้างดัชนีใหม่';
$strCreateNewDatabase = 'สร้างฐานข้อมูลใหม่';
$strCreateNewTable = 'สร้างตารางในฐานข้อมูลนี้ %s';
$strCriteria = 'เงื่อนไข';

$strData = 'ข้อมูล';
$strDatabase = 'ฐานข้อมูล ';
$strDatabaseHasBeenDropped = 'โยนฐานข้อมูล %s ทิ้งไปเรียบร้อยแล้ว';
$strDatabases = 'ฐานข้อมูล';
$strDatabasesStats = 'สถิติฐานข้อมูล';
$strDatabaseWildcard = 'ฐานข้อมูล (ใช้ wildcards ได้):';
$strDataOnly = 'เฉพาะข้อมูล';
$strDefault = 'ค่าปริยาย';
$strDelete = 'ลบ';
$strDeleted = 'ลบเรียบร้อยแล้ว';
$strDeletedRows = 'แถวที่ถูกลบ:';
$strDeleteFailed = 'ลบไม่สำเร็จ!';
$strDeleteUserMessage = 'คุณได้ลบผู้ใช้ %s ไปแล้ว';
$strDescending = 'มากไปน้อย';
$strDisplay = 'แสดงผล';
$strDisplayPDF = 'แสดง PDF schema';
$strDisplayOrder = 'ลำดับการแสดง:';
$strDoAQuery = 'ทำ "คำค้นจากตัวอย่าง" (wildcard: "%")';
$strDocu = 'เอกสารอ้างอิง';
$strDoYouReally = 'ต้องการจะ ';
$strDrop = 'โยนทิ้ง';
$strDropDB = 'โยนฐานข้อมูล %s ทิ้ง';
$strDropTable = 'โยนตารางทิ้ง';
$strDumpingData = 'dump ตาราง';
$strDumpXRows = 'ดัมพ์แถว %s แถว เริ่มที่แถว %s';
$strDynamic = 'ไม่คงที่';

$strEdit = 'แก้ไข';
$strEditPrivileges = 'แก้ไขสิทธิ';
$strEffective = 'มีผล';
$strEmpty = 'ลบข้อมูล';
$strEmptyResultSet = 'MySQL คืนผลลัพธ์ว่างเปล่า (null) กลับมา (0 แถว).';
$strEnd = 'ท้ายสุด';
$strEnglishPrivileges = ' โปรดทราบ: ชื่อของสิทธิใน MySQL จะแสดงเป็นภาษาอังกฤษ ';
$strError = 'ผิดพลาด';
$strExplain = 'อธิบายโค้ด SQL';
$strExport = 'ส่งออก';
$strExportToXML = 'ส่งออกเป็นรูปแบบ XML';
$strExtendedInserts = 'แทรกหลายระเบียนในคราวเดียว';
$strExtra = 'เพิ่มเติม';

$strField = 'ฟิลด์';
$strFieldHasBeenDropped = 'โยนฟิลด์ %s ทิ้งไปเรียบร้อยแล้ว';
$strFields = 'จำนวนฟิลด์';
$strFieldsEmpty = ' จำนวนฟิลด์คือ ว่างเปล่า! ';
$strFieldsEnclosedBy = 'คร่อมฟิลด์ด้วย';
$strFieldsEscapedBy = 'เครื่องหมายสำหรับ escape char';
$strFieldsTerminatedBy = 'จบฟิลด์ด้วย';
$strFixed = 'คงที่';
$strFlushTable = 'ล้างตาราง ("FLUSH")';
$strFormat = 'รูปแบบ';
$strFormEmpty = 'ค่าในแบบฟอร์มหายไป !';
$strFullText = 'ทั้งข้อความ';
$strFunction = 'ฟังก์ชั่น';

$strGenBy = 'สร้างโดย';
$strGenTime = 'เวลาในการสร้าง';
$strGo = 'ลงมือ';
$strGrants = 'อนุญาต';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'เปลี่ยนเสร็จแล้ว';
$strHasBeenCreated = 'สร้างเสร็จแล้ว';
$strHome = 'หน้าบ้าน';
$strHomepageOfficial = 'โฮมเพจอย่างเป็นทางการของ phpMyAdmin';
$strHomepageSourceforge = 'หน้าดาวน์โหลด phpMyAdmin ที่ Sourceforge';
$strHost = 'โฮสต์';
$strHostEmpty = 'ชื่อโฮสต์ยังว่างอยู่!';

$strIdxFulltext = 'Fulltext';
$strIfYouWish = 'ถ้าต้องการเรียกดูเฉพาะบางคอลัมน์ ให้ระบุรายชื่อฟิลด์มาด้วย (คั่นแต่ละชื่อด้วยเครื่องหมายลูกน้ำ)';
$strIgnore = 'ไม่สนใจ';
$strIndex = 'ดัชนี';
$strIndexes = 'ดัชนี';
$strIndexHasBeenDropped = 'โยนดัชนี %s ทิ้งไปเรียบร้อยแล้ว';
$strIndexName = 'ชื่อดัชนี :';
$strIndexType = 'ชนิดของดัชนี :';
$strInsert = 'แทรก';
$strInsertAsNewRow = 'แทรกเป็นแถวใหม่';
$strInsertedRows = 'แถวที่ถูกแทรก:';
$strInsertNewRow = 'แทรกแถวใหม่';
$strInsertTextfiles = 'แทรกข้อมูลจากไฟล์ข้อความเข้าไปในตาราง';
$strInstructions = 'วิธีใช้';
$strInUse = 'ใช้อยู่';
$strInvalidName = '"%s" เป็นคำสงวน นำมาใช้ตั้งชื่อ ฐานข้อมูล/ ตาราง/ฟิลด์ ไม่ได้';

$strKeepPass = 'กรุณาอย่าเปลี่ยนรหัสผ่าน';
$strKeyname = 'ชื่อคีย์';
$strKill = 'ฆ่าทิ้ง';

$strLength = 'ความยาว';
$strLengthSet = 'ความยาว/เซต*';
$strLimitNumRows = 'ระเบียนต่อหน้า';
$strLineFeed = 'ขึ้นบรรทัดใหม่: \\n';
$strLines = 'บรรทัด';
$strLinesTerminatedBy = 'จบแถวด้วย';
$strLinkNotFound = 'ไม่พบลิงก์';
$strLinksTo = 'เชื่อมไปยัง';
$strLocationTextfile = 'เลือกไฟล์ข้อความจาก';
$strLogin = 'เข้าสู่ระบบ';
$strLogout = 'ออกจากระบบ';
$strLogPassword = 'รหัสผ่าน:';
$strLogUsername = 'ชื่อผู้ใช้:';

$strModifications = 'บันทึกการแก้ไขเรียบร้อยแล้ว';
$strModify = 'แก้ไข';
$strModifyIndexTopic = 'แก้ไขดัชนี';
$strMoveTable = 'ย้ายตารางไป (database<b>.</b>table):';
$strMoveTableOK = 'ตาราง %s ถูกย้ายไป %s แล้ว';
$strMySQLReloaded = 'เรียก MySQL ขึ้นมาใหม่แล้ว';
$strMySQLSaid = 'MySQL แสดง: ';
$strMySQLServerProcess = 'MySQL %pma_s1% ทำงานอยู่บน %pma_s2% ในชื่อ %pma_s3%';
$strMySQLShowProcess = 'แสดงงานที่ทำอยู่ของ MySQL';
$strMySQLShowStatus = 'แสดงสถานะของ MySQL';
$strMySQLShowVars = 'แสดงตัวแปรระบบของ MySQL';

$strName = 'ชื่อ';
$strNext = 'ต่อไป';
$strNo = 'ไม่';
$strNoDatabases = 'ไม่มีฐานข้อมูล';
$strNoDropDatabases = 'คำสั่ง "DROP DATABASE" ถูกปิดไว้';
$strNoFrames = 'เบราเซอร์ที่<b>ใช้เฟรมได้</b> จะช่วยให้ใช้ phpMyAdmin ได้ง่ายขึ้น';
$strNoIndex = 'ยังไม่ได้กำหนดดัชนีใดๆ!';
$strNoIndexPartsDefined = 'ไม่ได้กำหนดส่วนใดๆ ของดัชนี!';
$strNoModification = 'ไม่มีการเปลี่ยนแปลง';
$strNone = 'ไม่มี';
$strNoPassword = 'ไม่มีรหัสผ่าน';
$strNoPhp = 'ไม่เอาโค้ด PHP';
$strNoPrivileges = 'ไม่มีสิทธิ';
$strNoQuery = 'ไม่มีคำค้น SQL!';
$strNoRights = 'คุณไม่มีสิทธิที่จะเข้ามาตรงนี้!';
$strNoTablesFound = 'ไม่พบตารางใด ๆ ในฐานข้อมูล';
$strNotNumber = 'ค่านี้ไม่ใช่ตัวเลข!';
$strNotValidNumber = ' ไม่ใช่หมายเลขแถวที่ถูกต้อง!';
$strNoUsersFound = 'ไม่พบผู้ใช้ใดๆ.';
$strNull = 'ว่างเปล่า (null)';
$strNumSearchResultsInTable = 'พบ %s ผลลัพธ์ที่ตรงในตาราง <i>%s</i>';
$strNumSearchResultsTotal = '<b>รวม:</b> <i>%s</i> ผลลัพธ์ที่ตรง';

$strOftenQuotation = 'โดยปกติจะเป็นเครื่องหมายอัญประกาศ (เครื่องหมายคำพูด)<br />"เท่าที่จำเป็น" หมายถึงให้ใส่เครื่องหมายคร่อมเฉพาะกับฟิลด์ชนิด char และ varchar เท่านั้น';
$strOperations = 'กระบวนการ';
$strOptimizeTable = 'ปรับแต่งตาราง';
$strOptionalControls = 'กำหนดว่าจะเขียนหรืออ่านตัวอักขระพิเศษ อย่างไร';
$strOptionally = 'เท่าที่จำเป็น';
$strOptions = 'ตัวเลือก';
$strOr = 'หรือ';
$strOverhead = 'เกินความจำเป็น';

$strPageNumber = 'หมายเลขหน้า:';
$strPartialText = 'ข้อความบางส่วน';
$strPassword = 'รหัสผ่าน';
$strPasswordEmpty = 'รหัสผ่านยังว่างอยู่!';
$strPasswordNotSame = 'รหัสผ่านไม่ตรงกัน!';
$strPdfDbSchema = 'schema ของฐานข้อมูล "%s" - หน้า %s';
$strPdfInvalidPageNum = 'ยังไม่ได้กำหนดเลขหน้าของ PDF!';
$strPdfInvalidTblName = 'ไม่มีตาราง "%s"!';
$strPhp = 'สร้างโค้ด PHP';
$strPHPVersion = 'รุ่นของ PHP';
$strPmaDocumentation = 'เอกสารการใช้ phpMyAdmin';
$strPmaUriError = '<b>ต้อง</b>กำหนดค่า <tt>$cfg[\'PmaAbsoluteUri\']</tt> ในไฟล์คอนฟิกูเรชั่นเสียก่อน';
$strPos1 = 'จุดเริ่มต้น';
$strPrevious = 'ก่อนหน้า';
$strPrimary = 'Primary';
$strPrimaryKey = 'Primary key';
$strPrimaryKeyHasBeenDropped = 'โยน primary key ทิ้งไปเรียบ ร้อยแล้ว';
$strPrimaryKeyName = 'ชื่อของ primary key จะต้องเป็น... PRIMARY!';
$strPrimaryKeyWarning = '(ชื่อของ primary key <b>จะต้องเป็น </b>"PRIMARY" เท่านั้น!)';
$strPrintView = 'แสดง';
$strPrivileges = 'สิทธิ';
$strProperties = 'คุณสมบัติ';

$strQBE = 'คำค้นจากตัวอย่าง';
$strQBEDel = 'ลบ';
$strQBEIns = 'เพิ่ม';
$strQueryOnDb = 'คำค้นบนฐานข้อมูล <b>%s</b>:';

$strRecords = 'ระเบียน';
$strReferentialIntegrity = 'ตรวจสอบความสมบูรณ์ของการอ้างถึง:';
$strRelationView = 'Relation view';
$strReloadFailed = 'รีโหลด MySQL ใหม่ไม่สำเร็จ';
$strReloadMySQL = 'รีโหลด MySQL ใหม่';
$strRememberReload = 'อย่าลืมรีโหลดเซิร์ฟเวอร์ใหม่อีกครั้ง'; // can be better translated
$strRenameTable = 'เปลี่ยนชื่อตารางเป็น';
$strRenameTableOK = 'ตาราง %s ได้ถูกเปลี่ยนชื่อเป็น %s';
$strRepairTable = 'ซ่อมแซมตาราง';
$strReplace = 'เขียนทับ';
$strReplaceTable = 'เขียนทับด้วยข้อมูลจากไฟล์';
$strReset = 'เริ่มใหม่';
$strReType = 'พิมพ์ใหม่';
$strRevoke = 'เพิกถอน';
$strRevokeGrant = 'เพิกถอนการอนุญาต';
$strRevokeGrantMessage = 'คุณได้เพิกถอนการอนุญาตของ %s';
$strRevokeMessage = 'คุณได้เพิกถอนสิทธิของ %s';
$strRevokePriv = 'เพิกถอนสิทธิ';
$strRowLength = 'ความยาวแถว';
$strRows = 'แถว';
$strRowsFrom = 'แถว เริ่มจากแถวที่';
$strRowSize = ' ขนาดแถว ';
$strRowsModeHorizontal = 'แนวนอน';
$strRowsModeOptions = 'อยู่ใน %s และซ้ำหัวแถวทุกๆ %s เซลล์';
$strRowsModeVertical = 'แนวตั้ง';
$strRowsStatistic = 'สถิติของแถว';
$strRunning = 'ทำงานอยู่ใน %s';
$strRunQuery = 'ส่งคำค้น';
$strRunSQLQuery = 'ทำคำค้นบนฐานข้อมูล %s';

$strSave = 'บันทึก';
$strScaleFactorSmall = 'อัตราขยายน้อยเกินไปที่จะจัดให้ schema อยู่ในหน้าเดียว';
$strSearch = 'ค้นหา';
$strSearchFormTitle = 'ค้นหาในฐานข้อมูล';
$strSearchInTables = 'ในตาราง:';
$strSearchNeedle = 'คำหรือค่าที่ต้องการค้นหา (wildcard: "%"):';
$strSearchOption1 = 'อย่างน้อยหนึ่งคำ';
$strSearchOption2 = 'ทุกคำ';
$strSearchOption3 = 'เหมือนทั้งวลี';
$strSearchOption4 = 'ค้นแบบ regular expression';
$strSearchResultsFor = 'ผลการค้นหา "<i>%s</i>" %s:';
$strSearchType = 'ค้น:';
$strSelect = 'เลือก';
$strSelectADb = 'โปรดเลือกฐานข้อมูล';
$strSelectAll = 'เลือกทั้งหมด';
$strSelectFields = 'เลือกฟิลด์ (อย่างน้อยหนึ่งฟิลด์):';
$strSelectNumRows = 'ในคำค้น';
$strSend = 'ส่งมาเป็นไฟล์';
$strServerChoice = 'ตัวเลือกเซิร์ฟเวอร์';
$strServerVersion = 'รุ่นของเซิร์ฟเวอร์';
$strSetEnumVal = 'ถ้าชนิดของฟิลด์เป็น "enum" หรือ "set" โปรดใส่ค่าตามรูปแบบ: \'a\',\'b\',\'c\'...<br />ถ้าต้องการใส่เครื่องหมาย backslash ("\\") หรือ อัญประกาศเดี่ยว ("\'") เข้าไปในค่าเหล่านั้น ให้ใส่เครื่องหมาย backslash นำหน้า (ตัวอย่าง: \'\\\\xyz\' or \'a\\\'b\')';
$strShow = 'แสดง';
$strShowAll = 'แสดงทั้งหมด';
$strShowColor = 'แสดงสี';
$strShowCols = 'แสดงคอลัมน์';
$strShowGrid = 'แสดงกริด';
$strShowTableDimension = 'แสดงมิติของตาราง';
$strShowingRecords = 'แสดงระเบียนที่ ';
$strShowPHPInfo = 'แสดงข้อมูลของ PHP';
$strShowTables = 'แสดงตาราง';
$strShowThisQuery = ' แสดงคำค้นนี้อีกที ';
$strSingly = '(เดี่ยว)';
$strSize = 'ขนาด';
$strSort = 'เรียง';
$strSpaceUsage = 'เนื้อที่ที่ใช้';
$strSplitWordsWithSpace = 'คำถูกแบ่งด้วยช่องว่าง (" ").';
$strSQL = 'SQL';
$strSQLQuery = 'คำค้น SQL';
$strSQLResult = 'ผลลัพธ์ SQL';
$strStatement = 'คำสั่ง';
$strStrucExcelCSV = 'ข้อมูล CSV สำหรับไมโครซอฟต์เอ็กเซล';
$strStrucCSV = 'ข้อมูล CSV';
$strStrucData = 'ทั้งโครงสร้างและข้อมูล';
$strStrucDrop = 'เพิ่มคำสั่ง \'drop table\'';
$strStrucOnly = 'เฉพาะโครงสร้าง';
$strStructPropose = 'เสนอโครงสร้างตาราง';
$strStructure = 'โครงสร้าง';
$strSubmit = 'ส่ง';
$strSuccess = 'ทำคำค้นเสร็จเรียบร้อยแล้ว';
$strSum = 'ผลรวม';

$strTable = 'ตาราง ';
$strTableComments = 'หมายเหตุของตาราง';
$strTableEmpty = 'ชื่อตารางยังว่างอยู่!';
$strTableHasBeenDropped = 'โยนตาราง %s ทิ้งไปเรียบร้อย แล้ว';
$strTableHasBeenEmptied = 'ลบข้อมูลในตาราง %s เรียบร้อย แล้ว';
$strTableHasBeenFlushed = 'ล้างตาราง %s เรียบร้อยแล้ว';
$strTableMaintenance = 'การดูแลรักษาตาราง';
$strTables = '%s ตาราง';
$strTableStructure = 'โครงสร้างตาราง';
$strTableType = 'ชนิดตาราง';
$strTextAreaLength = ' เนื่องจากความยาวของมัน <br />ฟิลด์นี้ ไม่อาจแก้ไขได้ ';
$strTheContent = 'ได้แทรกข้อมูลจากไฟล์ของคุณเรียบร้อยแล้ว';
$strTheContents = 'สำหรับแถวที่มี primary key หรือ unique key เหมือนกัน เนื้อหาจากไฟล์จะแทนที่เนื้อหาเดิมในตาราง';
$strTheTerminator = 'จุดสิ้นสุดของฟิลด์';
$strTotal = 'ทั้งหมด';
$strType = 'ชนิด';

$strUncheckAll = 'ไม่เลือกเลย';
$strUnique = 'เอกลักษณ์';
$strUnselectAll = 'ไม่เลือกเลย';
$strUpdatePrivMessage = 'คุณได้ปรับปรุงสิทธิสำหรับ %s แล้ว';
$strUpdateProfile = 'ปรับปรุงโพรไฟล์:';
$strUpdateProfileMessage = 'ปรับปรุงโพรไฟล์เรียบร้อยแล้ว';
$strUpdateQuery = 'ปรับปรุงคำค้น';
$strUsage = 'ใช้งาน';
$strUseBackquotes = 'ใส่ \'backqoute\' ให้กับชื่อตารางและฟิลด์';
$strUser = 'ผู้ใช้';
$strUserEmpty = 'ชื่อผู้ใช้ยังว่างอยู่!';
$strUserName = 'ชื่อผู้ใช้';
$strUsers = 'ผู้ใช้';
$strUseTables = 'ใช้ตาราง';

$strValue = 'ค่า';
$strViewDump = 'ดูโครงสร้างของตาราง';
$strViewDumpDB = 'ดูโครงสร้างของฐานข้อมูล';

$strWelcome = '%s ยินดีต้อนรับ';
$strWithChecked = 'ทำกับที่เลือก:';
$strWrongUser = 'อนุญาตให้เข้าใช้ไม่ได้ ชื่อผู้ใช้หรือรหัสผ่านผิด';

$strYes = 'ใช่';

$strZip = '"zipped"';

// To translate
$strEditPDFPages = 'Edit PDF Pages';  //to translate
$strNoDescription = 'no Description';  //to translate
$strChoosePage = 'Please choose a Page to edit';  //to translate
$strCreatePage = 'Create a new Page';  //to translate
$strSelectTables = 'Select Tables';  //to translate
$strConfigFileError = 'phpMyAdmin was unable to read your configuration file!<br />This might happen if php finds a parse error in it or php cannot find the file.<br />Please call the configuration file directly using the link below and read the php error message(s) that you recieve. In most cases a quote or a semicolon is missing somewhere.<br />If you recieve a blank page, everything is fine.'; //to translate
$strNotSet = '<b>%s</b> table not found or not set in config.inc.php(3)';  //to translate
?>
