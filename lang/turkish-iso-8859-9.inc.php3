<?php

/* $Id$ */

//çeviride eksik veya hatalý olduðunu düþündüðünüz kýsýmlarý bora@ktu.edu.tr adresine gönderebilirsiniz...
//bora alioðlu 02.08.2002...tempus fugit...

$charset = 'iso-8859-9';
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
$byteUnits = array('Byte', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
//veritabanlarý terminolojisinde tercümeye pek müsait olmayan index ve unique sözcükleri aynen kullanýldý: uniqe=eþsiz,tek
$day_of_week = array('Pazar', 'Pazartesi', 'Salý', 'Çarþamba', 'Perþembe', 'Cuma', 'Cumartesi');
$month = array('Ocak', 'Þubat', 'Mart', 'Nisan', 'Mayýs', 'Haziran', 'Temmuz', 'Aðustos', 'Eylül', 'Ekim', 'Kasým', 'Aralýk');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Eriþim engellendi';
$strAction = 'Eylem';
$strAddDeleteColumn = 'Sütun alaný Ekle/Sil';
$strAddDeleteRow = 'Kriter satýrý Ekle/Sil';
$strAddNewField = 'Yeni alan ekle';
$strAddPrivMessage = 'Yeni ayrýcalýk eklediniz..';
$strAddPriv = 'Yeni ayrýcalýk ekle';
$strAddSearchConditions = 'Arama durumu ekle ("where" cümleciði için):';
$strAddToIndex = '%s sütununu(sütunlar&#305;na) index ekle';
$strAddUserMessage = 'Yeni bir kullanýcý eklediniz.';
$strAddUser = 'Yeni kullanýcý ekle';
$strAffectedRows = 'Etkilenen satýrlar:';
$strAfterInsertBack = 'Return';
$strAfterInsertNewInsert = 'Yeni kayit ekle';
$strAfter = 'Sonuna %s';
$strAllTableSameWidth = 'Bütün tablolarý ayný geniþlikte göster';
$strAll = 'Tümü';
$strAlterOrderBy = 'Tabloyu deðiþtir ve þuna göre sýrala:';
$strAnalyzeTable = 'Tabloyu analiz et';
$strAnd = 'Ve';
$strAnIndex = '%s üzerinde yeni bir index eklendi';
$strAnyColumn = 'Herhangi sütun';
$strAnyDatabase = 'Herhangi veritabaný';
$strAny = 'Herhangi';
$strAnyHost = 'Herhangi sunucu';
$strAnyTable = 'Herhangi tablo';
$strAnyUser = 'Herhangi kullanýcý';
$strAPrimaryKey = '%s üzerinde birincil index eklendi';
$strAscending = 'Artan';
$strAtBeginningOfTable = 'Tablonun baþýnda';
$strAtEndOfTable = 'Tablonun sonunda';
$strAttr = 'Özellikler';

$strBack = 'Geri';
$strBinary = 'Binari';
$strBinaryDoNotEdit = 'Binari - düzenlemeyiniz';
$strBookmarkDeleted = 'Bookmark silindi.';
$strBookmarkLabel = 'Etiket';
$strBookmarkQuery = ' SQL-sorgusu';
$strBookmarkThis = 'Bu SQL-sorgusunu iþaretle';
$strBookmarkView = 'Sadece gözat';
$strBrowse = 'Tara';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'mySQL uzantýsýný yükleyemiyor,<br />lütfen PHP ayarlarýný kontrol ediniz.';
$strCantLoadRecodeIconv = 'Karakter seti dönüþümü için gerekli olan Iconv veya recode uzantýlarýný yükleyenemiyor.  Php\'nin bu uzantýlara izin vermesini saðlayýn veya phpMyAdmin içinde karakter dönüþümünü devre dýþý býrakýnýz...';
$strCantRenameIdxToPrimary = 'Index\'i PRIMARY olarak adland&#305;r&#305;mazs&#305;n&#305;z!';
$strCantUseRecodeIconv = 'Uzantý raporlarý yüklenmiþken , ne iconv ne libinconv ne de recode_string fonksiyonu  kullanýlamaz.  Php ayarlarýnýzý kontrol ediniz.';
$strCardinality = 'En önemli';
$strCarriage = 'Enter Karakteri: \\r';
$strChange = 'Deðiþtir';
$strChangeDisplay = 'Görmek istediðiniz alaný seçiniz';
$strChangePassword = 'Þifre Deðiþtir';
$strCharsetOfFile = 'Dosyanýn karakter seti:';
$strCheckAll = 'Tümünü seç';
$strCheckDbPriv = 'Veritabaný önceliklerini kontrol et';
$strCheckTable = 'Tabloyu kontrol et';
$strChoosePage = 'Lütfen düzenlemek istediðiniz sayfayý seçin';
$strColComFeat = 'Sütun yorumlarý gösteriliyor';
$strColumnNames = 'Sütun adlarý';
$strColumn = 'Sütun';
$strComments = 'Yorumlar';
$strCompleteInserts = 'Tamamlanmýþ eklemeler';
$strConfigFileError ='phpMyAdmin konfigurasyon dosyanýzý okuyamadý....<br /> Bu php yorumlama hatasý bulduðu zaman veya dosyayý bulamadýðý zaman meydana gelebilir..<br /> Lütfen aþaðýdaki linki kullanarak dosyayý direkt olarak çaðýrýn ve aldýðýnýz php hata mesajlarýný okuyunuz.Çoðu durumda herhangi bir yerde týrnak veya noktalý virgül eksiktir<br /> Boþ bir sayfayla karþýlaþýrsanýz ,herþey yolunda demektir.';
$strConfigureTableCoord = ' Lütfen %s tablosu için koordinatlarý yapýlandýrýnýz';
$strConfirm = 'Aþaðýdaki komutu uygulamak istediðinizden emin misiniz?';
$strCookiesRequired = 'Cookieler aç&#305;k olmal&#305;d&#305;r.';
$strCopyTableOK = '%s tablosu %s üzerine kopyalandý.';
$strCopyTable = 'Tabloyu (veritabaný<b>.</b>tablo) kopyala:';
$strCreate = 'Git';
$strCreateIndex = '%s sütununda yeni bir index olu&#351;tur';
$strCreateIndexTopic = 'Yeni bir index olu&#351;tur';
$strCreateNewDatabase = 'Yeni veritabaný oluþtur';
$strCreateNewTable = '%s veritabaný üzerinde yeni bir tablo oluþtur';
$strCreatePage = 'Yeni sayfa oluþtur';
$strCreatePdfFeat = 'PDF\'lerin oluþturulmasý';
$strCriteria = 'Kriter';

$strDatabaseHasBeenDropped = '%s veritabaný kaldýrýldý.';
$strDatabasesStats = 'Veritabaný istatistikleri';
$strDatabases = 'veritabanlarý';
$strDatabase = 'Veritabaný ';
$strDatabaseWildcard = 'Veritabaný (* izin verili):';
$strDataOnly = 'Sadece veri';
$strData = 'Veri';
$strDefault = 'Varsayýlan';
$strDeletedRows = 'Silinen satýrlar:';
$strDeleted = 'Satýr silindi.';
$strDeleteFailed = 'Silme sýrasýnda hata oluþtu!';
$strDelete = 'Sil';
$strDeleteUserMessage = '%s kullanýcýsýný sildiniz.';
$strDescending = 'Azalan';
$strDisabled = 'Etkin deðil';
$strDisplayFeat = 'Özellikleri Göster';
$strDisplay = 'Görüntüle';
$strDisplayOrder = 'Görünüm düzeni:';
$strDisplayPDF = 'PDF þemasýný göster';
$strDoAQuery = '"Örnekle sorgu" yap (joker: "%")';
$strDocu = 'Yardým';
$strDoYouReally = 'Aþaðýdaki komutu uygulamak istediðinizden emin misiniz? ';
$strDropDB = 'Veritabaný\'ný kaldýr %s';
$strDrop = 'Kaldýr';
$strDropTable = 'Tablo\'yu kaldýr';
$strDumpingData = 'Tablo döküm verisi';
$strDumpXRows = ' %s satýrdan baþlayarak  %s a kadar çýktý üret.';
$strDynamic = 'dinamik';

$strEdit = 'Düzenle';
$strEditPDFPages = ' PDF Sayfalarýný düzenle';
$strEditPrivileges = 'Öncelikleri Düzenle';
$strEffective = 'Efektif';
$strEmpty = 'Boþalt';
$strEmptyResultSet = 'MySQL boþ bir sonuc kümesi döndürdü ( sýfýr satýr).';
$strEnabled = 'Etkin';
$strEnd = 'Son';
$strEnglishPrivileges = ' Not: mySQL  öncelik adlarý Ýngilizce olarak belirtilmiþtir ';
$strError = 'Hata';
$strExplain = 'SQL\'i açýkla';
$strExport = 'Dönüþtür';
$strExportToXML = 'XML formatýna dönüþtür';
$strExtendedInserts = 'Geniþletilmiþ eklemeler';
$strExtra = 'Ayrýca';

$strField = 'Alan';
$strFieldHasBeenDropped = '%s alaný kaldýrýlmýþtýr';
$strFields = 'Alanlar';
$strFieldsEmpty = ' Alan sayýsý boþ! ';
$strFieldsEnclosedBy = 'Alan ayýrýcý iþaret';//it does not seem well but just works
$strFieldsEscapedBy = 'Kaçýþ simgesi(özel iþaretler için)';//it does not seem well but just works
$strFieldsTerminatedBy = 'Alan bitirici iþaret';//it does not stand seem but just works
$strFixed = 'sabit';
$strFlushTable = 'Tabloyu kapat("FLUSH")';
$strFormat = 'Biçim';
$strFormEmpty = 'Form\'da eksik deðer !';
$strFullText = 'Tüm metinler';
$strFunction = 'Fonksiyon';

$strGenBy = 'Oluþturuldu->:';
$strGeneralRelationFeat = 'Genel iliþki özellikleri';
$strGenTime = 'Çýktý Tarihi';
$strGo = 'Git';
$strGrants = 'Haklar';
$strGzip = '"gziplenmiþ"';

$strHasBeenAltered = 'düzenlendi.';
$strHasBeenCreated = 'yaratýldý.';
$strHaveToShow = 'Görüntülemek için en az bir sütun seçmelisiniz';
$strHome = 'Ana Sayfa';
$strHomepageOfficial = 'phpMyAdmin Web Sayfasý';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin Yükleme Sayfasý';
$strHostEmpty = 'Sunucu ismi alaný doldurulmadý!';
$strHost = 'Sunucu:';

$strIdxFulltext = 'Tüm metinler';
$strIfYouWish = 'Eðer bir tablo\'nun sadece bazý sütunlarýný yüklemek istiyorsanýz,virgüllerle ayrýlmýþ bir alan listesi belirtiniz.';
$strIgnore = 'Yoksay';
$strIndexes = 'Index\'ler';
$strIndexHasBeenDropped = '%s index\'i silindi.';
$strIndex = 'Index';
$strIndexName = 'Index ismi :';
$strIndexType = 'Index tipi :';
$strInsertAsNewRow = 'Yeni bir satýr olarak ekle';
$strInsertedRows = 'Eklenen satýrlar:';
$strInsert = 'Ekle';
$strInsertNewRow = 'Yeni satýr ekle';
$strInsertTextfiles = 'Tablo içine metin dosyasý ekle';
$strInstructions = 'Talimatlar';
$strInUse = 'kullanýmda';
$strInvalidName = '"%s" sözcüðü kullanýlamayan sözcük.Veritabaný/tablo/alan ismi olarak kullanamassýnýz, you can\'t use it as a database/table/field name.';

$strKeepPass = 'Þifreyi deðiþtirme';
$strKeyname = 'Anahtar ismi';
$strKill = 'Kapat';

$strLength = 'Boyut';
$strLengthSet = 'Boyut/Deðerler*';
$strLimitNumRows = 'Sayfa ba&#351;&#305;na kay&#305;t say&#305;s&#305;';
$strLineFeed = 'Satýr: \\n';
$strLines = 'Satýrlar';
$strLinesTerminatedBy = 'Satýr sonu';
$strLinkNotFound = 'Link bulunamadý';
$strLinksTo = 'Linkler->';
$strLocationTextfile = 'Dosyadan yükle';
$strLogin = 'Login';
$strLogout = 'Çýkýþ';
$strLogPassword = '&#350;ifre:';
$strLogUsername = 'Kullan&#305;c&#305; Ad&#305;:';

$strMissingBracket = 'Parantez eksik';
$strModifications = 'Deðiþiklikler kaydedildi';
$strModify = 'Deðiþtir';
$strModifyIndexTopic = 'Index düzenle';
$strMoveTableOK = '%s tablosu %s üzerine taþýndý.';
$strMoveTable = 'Tabloyu (veritabaný<b>.</b>tablo) taþý:';
$strMySQLCharset = 'MySQL karakter seti';
$strMySQLReloaded = 'MySQL yeniden yüklendi.';
$strMySQLSaid = 'MySQL çýktýsý: ';
$strMySQLServerProcess = ' MySQL %pma_s1%   %pma_s2%  üzerinde  %pma_s3% olarak çalýþýyor';
$strMySQLShowProcess = 'Ýþlemleri göster';
$strMySQLShowStatus = 'MySQL çalýþma zamaný bilgisini göster';
$strMySQLShowVars = 'MySQL sistem deðiþkenlerini göster';

$strName = 'Ýsim';
$strNext = 'Sonraki';
$strNoDatabases = 'Veritabaný yok';
$strNoDescription = 'Tanýmlama yok';
$strNoDropDatabases = '"DROP DATABASE" cümlesi burada kullanýlamaz.';
$strNoExplain = 'SQL açýklamasýný yapma';
$strNoFrames = 'phpMyAdmin frame destekli bir taray&#305;c&#305; ile daha iyi çal&#305;&#351;maktad&#305;r...';
$strNo = 'Hayýr';
$strNoIndex = 'Index tan&#305;mlanmad&#305;!';
$strNoIndexPartsDefined = 'Index k&#305;sm&#305; tan&#305;mlanmad&#305;!';
$strNoModification = 'Deðiþiklik yok';
$strNone = 'Hiçbiri';
$strNoPassword = 'Þifre yok';
$strNoPhp = ' PHP kodsuz';
$strNoPrivileges = 'Ayrýcalýk yok';
$strNoQuery = 'SQL sorgusu yok!';
$strNoRights = 'Burada bulunmak için yeterli haklara sahip deðilsiniz!';
$strNoTablesFound = 'Veritabaný\'nda tablo bulunamadý.';
$strNotNumber = 'Bu bir sayý deðil!';
$strNotOK = 'Tamam deðil';
$strNotSet = '<b>%s</b> tablosu bulunamadý veya %s içinde tanýmlanmadý';
$strNotValidNumber = ' geçerli bir satýr sayýsý deðil!';
$strNoUsersFound = 'Kullanýcý(lar) bulunamadý.';
$strNoValidateSQL = 'SQL doðrulamasýný yapma';
$strNull = 'Boþ';
$strNumSearchResultsInTable = '%s eþleþim : %s tablosu içinde';
$strNumSearchResultsTotal = 'Toplam: %s eþleþim';

$strOftenQuotation = 'Sýk kullanýlan aktarma iþaretleri.SEÇÝME BAÐLI,sadece char ve varchar alanlarýnýn "enclosed-by" karakteri ile çevreneleceði anlamýna gelir..';
$strOK = 'Tamam';
$strOperations = 'Ýþlemler';
$strOptimizeTable = 'Tabloyu optimize et';
$strOptionalControls = 'Özel karakterleri yazmak ve okumak için kontroller.Opsiyonel';
$strOptionally = 'Seçime Baðlý';
$strOptions = 'Seçenekler';
$strOr = 'veya';
$strOverhead = 'Kullan&#305;lamayan Veri';

$strPageNumber = 'Sayfa numarasý:';
$strPartialText = 'Bölümsel Metinler';
$strPasswordEmpty = 'Þifre alaný doldurulmadý!';
$strPasswordNotSame = 'Girilen þifreler ayný deðil!';
$strPassword = 'Þifre';
$strPdfDbSchema = ' "%s" veritabanýnýn þemasý - Sayfa %s';
$strPdfInvalidPageNum = 'Tanýmlanmamýþ PDF sayfa numarasý!';
$strPdfInvalidTblName = ' "%s" tablosu bulunamýyor !';
$strPhp = 'PHP kodu oluþtur';
$strPHPVersion = 'PHP Sürümü';
$strPmaDocumentation = 'phpMyAdmin yardým';
$strPmaUriError = '<tt>$cfg[\'PmaAbsoluteUri\']</tt>\' nin deðeri  konfigurasyon dosyasýnýn içinde verilmelidir!';
$strPos1 = 'Baþlangýç';
$strPrevious = 'Önceki';
$strPrimary = 'Birincil';
$strPrimaryKey = 'Birincil anahtar';
$strPrimaryKeyHasBeenDropped = 'Birincil anahtar silindi';
$strPrimaryKeyName = 'PRIMARY KEY TEK olmal&#305;d&#305;r!';
$strPrimaryKeyWarning = '("PRIMARY" <b>sadece</b> bir anahtar&#305;n ismi <b>olmal&#305;d&#305;r!</b>)';
$strPrintView = 'Yazýcý görüntüsü';
$strPrivileges = 'Öncelikler';
$strProperties = 'Özellikler';

$strQBEDel = 'Del';
$strQBEIns = 'Ins';
$strQBE = ' Sorgula';
$strQueryOnDb = 'Veritabaný üzerinde SQL-sorgusu&nbsp;<b>%s</b>:';

$strRecords = 'Kayýtlar';
$strReferentialIntegrity = 'Referans bütünlüðünü kontrol et.';
$strRelationNotWorking = 'Baðlý tablolarla çalýþmada kullanýlan ekstra özellikler deaktif edildi...Niçin olduðunu öðrenmek için %sburaya%s týklayýnýz...';
$strRelationView = 'Ýliþki görünümü';
$strReloadFailed = 'MySQL yeniden yüklenmesi gerçekleþtirilemedi.';
$strReloadMySQL = 'MySQL\'i yeniden yükle';
$strRememberReload = 'Server\'ý yeniden yüklemeyi unutmayýnýz.';
$strRenameTableOK = '%s tablosu %s olarak yeniden adlandýrýldý';
$strRenameTable = 'Tablonun ismini þuna deðiþtir';
$strRepairTable = 'Tablo\'yu onar';
$strReplaceTable = 'Tablo verisini bir dosyadaki ile deðiþtir';
$strReplace = 'Yerdeðiþtir';
$strReset = 'Sýfýrla';
$strReType = 'Yeniden gir';
$strRevoke = 'Geçersiz kýl';
$strRevokeGrant = 'Hak geçersiz kýl';
$strRevokeGrantMessage = '%s için Grant önceli&#287;ini kald&#305;rd&#305;n&#305;z';
$strRevokeMessage = '%s\'in önceliklerini kald&#305;rd&#305;n&#305;z';
$strRevokePriv = 'Ayrýcalýklarý geçersiz kýl';
$strRowLength = 'Satýr boyu';
$strRowsFrom = '(kayýt)baþlayacaðý kayýt :';
$strRowSize = ' Satýr boyutu ';
$strRowsModeHorizontal= 'yatay';
$strRowsModeOptions= '%s modunda ve %s hücre sonra ba&#351;l&#305;&#287;&#305; tekrarla';
$strRowsModeVertical= 'dikey';
$strRows = 'Satýr Sayýsý';
$strRowsStatistic = 'Satýr istatistiði';
$strRunning = ': %s üzerinde çalýþýyor...';
$strRunQuery = 'Sorguyu çalýþtýr';
$strRunSQLQuery = '%s veritabaný üzerinde sorgu/sorgular çalýþtýr';

$strSave = 'Kaydet';
$strScaleFactorSmall = 'Çarpan faktörü sayfadaki þema için çok küçük';
$strSearch = 'Ara';
$strSearchFormTitle = 'Veritabanýnda ara';
$strSearchInTables = 'Tablo(lar) içinde:';
$strSearchNeedle = 'Aranacak kelime veya deðerler (joker: "%"):';
$strSearchOption1 = 'kelimelerin en azýndan biri';
$strSearchOption2 = 'bütün kelimeler';
$strSearchOption3 = 'tam eþleþim';
$strSearchOption4 = 'normal deyim gibi';
$strSearchResultsFor = ' "%s" %s için arama sonuçlarý:';
$strSearchType = 'Bul:';
$strSelectADb = ' Lütfen bir veritaban&#305; seçiniz';
$strSelectAll = 'Tümünü seç';
$strSelectFields = 'Alan seç (en az bir):';
$strSelectNumRows = 'sorgu içerisinde';
$strSelect = 'Seç';
$strSelectTables = 'Tablolarý seç';
$strSend = 'Dosya olarak kaydet';
$strServerChoice = 'Server seçimi';
$strServerVersion = 'Server sürümü';
$strSetEnumVal = 'Eðer alan tipi "enum" veya  "set" ise , lütfen verileri þu formata göre giriniz: \'a\',\'b\',\'c\'...<br>Eðer bu deðerler arasýna backslash ("\") veya tek týrnak koymanýz gerekirse ("\'"),bunun için backslash kullanýn (mesela \'\\\\xyz\' veya \'a\\\'b\').';
$strShowAll = 'Tümünü göster';
$strShowColor = 'Rengi göster';
$strShowCols = 'Bütün sütunlarý göster';
$strShow = 'Göster:';
$strShowGrid = 'Izgarayý göster';
$strShowingRecords = 'Kayýtlarý gösteriyor';
$strShowPHPInfo = 'PHP bilgisini göster';
$strShowTableDimension = 'Tablolarýn boyutlarýný göster';
$strShowTables = 'Tablolarý göster';
$strShowThisQuery = ' Bu sorguyu burda yine göster ';
$strSingly = '(birer birer)';
$strSize = 'Boyut';
$strSort = 'Sýrala';
$strSpaceUsage = 'Kullanýlan alan';
$strSplitWordsWithSpace = 'Kelimeler bir boþluk karakteriyle bölünmüþtür (" ").';
$strSQLQuery = 'SQL-sorgusu';
$strSQLResult = 'SQL sonucu';
$strSQL = 'SQL';
$strStatement = 'Ýfadeler';
$strStrucCSV = 'CSV verisi';
$strStrucData = 'Yapý ve Veri';
$strStrucDrop = '\'Drop table\' ekle';
$strStrucExcelCSV = 'MS Excel verisi için CSV';
$strStrucOnly = 'Sadece yapý';
$strStructPropose = 'Tablo yapýsýný ayarla(mysql,tablo yapýsýný optimize eder)';
$strStructure = 'Yapý';
$strSubmit = 'Onayla';
$strSuccess = 'SQL sorgunuz baþarýyla çalýþtýrýlmýþtýr';
$strSum = 'toplam';

$strTableComments = 'Tablo yorumlarý';
$strTableEmpty = 'Tablo ismi boþ!';
$strTableHasBeenDropped = '%s tablosu kaldýrýlmýþtýr';
$strTableHasBeenEmptied = '%s tablosu boþaltýlmýþtýr';
$strTableHasBeenFlushed = '%s tablosu ba&#351;ar&#305;yla kapat&#305;lm&#305;&#351;t&#305;r.';
$strTableMaintenance = 'Tablo bakýmý';
$strTables = '%s tablo';
$strTableStructure = 'Tablo için tablo yapýsý';
$strTable = 'tablo ';
$strTableType = 'Tablo tipi';
$strTextAreaLength = 'Boyutu nedeniyle,<br /> bu alan düzenlenmeyebilir ';
$strTheContent = 'Dosyanýzýn içeriði eklendi.';
$strTheContents = 'Dosyanýn içeriði tablonun içeriðini ayný birincil veya unique anahtar deðerli sütunlar için yer deðiþtirir..';
$strTheTerminator = 'Alan bitimini belirten iþaret.';
$strTotal = 'toplam';
$strType = 'Tip';

$strUncheckAll = 'Hiçbirisini Seçme';
$strUnique = 'Unique';
$strUnselectAll = 'Hiçbirisini seçme';
$strUpdatePrivMessage = '%s için olan ayrýcalýklarý güncellediniz.';
$strUpdateProfileMessage = 'Profil güncellendi.';
$strUpdateProfile = 'Profil güncelle:';
$strUpdateQuery = 'Sorguyu güncelle';
$strUsage = 'Kullaným';
$strUseBackquotes = 'Tablo ve alan isimleri için ters týrnak " ` " iþaretini kullan';
$strUserEmpty = 'Kullanýcý ismi alaný doldurulmadý!';
$strUser = 'Kullanýcý:';
$strUserName = 'Kullanýcý ismi';
$strUsers = 'Kullanýcýlar';
$strUseTables = 'Tablolarý kullan';

$strValidateSQL = 'SQL\'i doðrula'; 
$strValue = 'Deðer';
$strViewDumpDB = 'Veritabaný\'nýn döküm(þema)\'ünü göster';
$strViewDump = 'Tablo\'nun döküm(þema)\'ünü göster';

$strWelcome = '%s \'e HOÞGELDÝNÝZ....';
$strWithChecked = 'seçilileri:';
$strWrongUser = 'Hatalý kullanýcý/parola. Eriþim engellendi.';

$strYes = 'Evet';

$strZip = '"ziplenmiþ"';
// To translate


$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate

$strPdfNoTables = 'No tables';  //to translate

$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQLParserUserError = 'There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem';  //to translate
$strSQPBugInvalidIdentifer = 'Invalid Identifer';  //to translate
$strSQPBugUnclosedQuote = 'Unclosed quote';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate

?>
