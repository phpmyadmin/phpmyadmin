<?php
/* $Id$ */

/* Translated by WooSuhan <kjh@unews.co.kr> */

$charset = 'ks_c_5601-1987';
$text_dir = 'ltr';
$left_font_family = '"굴림", sans-serif';
$right_font_family = '"굴림", sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Giga, Tera, Peta, Exa
$byteUnits = array('바이트', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('일', '월', '화', '수', '목', '금', '토');
$month = array('해오름달', '시샘달', '물오름달', '잎새달', '푸른달', '누리달', '견우직녀달', '타오름달', '열매달', '하늘연달', '미틈달', '매듭달');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%Y년 %B %d일 %p %I:%M ';

$strAPrimaryKey = ' %s에 기본(프라이머리)키가 추가되었습니다';
$strAccessDenied = '접근이 거부되었습니다.';
$strAction = '실행';
$strAddDeleteColumn = '필드 칼럼 추가/삭제';
$strAddDeleteRow = 'Criteria 레코드(줄) 추가/삭제';
$strAddNewField = '필드 추가하기';
$strAddPriv = '권한 추가하기';
$strAddPrivMessage = '새 권한을 추가했습니다';
$strAddSearchConditions = '검색 조건 추가 ("where" 조건):';
$strAddToIndex = '%s칼럼에 인덱스 추가';
$strAddUser = '새 사용자 추가';
$strAddUserMessage = '새 사용자를 추가했습니다.';
$strAffectedRows = '적용된 레코드(줄):';
$strAfter = '%s 다음에';
$strAfterInsertBack = '되돌아가기';
$strAfterInsertNewInsert = '새 레코드(줄) 삽입하기';
$strAlterOrderBy = '다음 순서대로 테이블 정렬(변경)';
$strAnIndex = '%s 에 인덱스가 걸렸습니다';
$strAnalyzeTable = '테이블 분석';
$strAnd = '그리고';
$strAnyColumn = '모든 칼럼';
$strAnyDatabase = '아무 데이터베이스';
$strAnyHost = '아무데서나';
$strAnyTable = '모든 테이블';
$strAnyUser = '아무나';
$strAscending = '오름차순';
$strAtBeginningOfTable = '테이블의 처음';
$strAtEndOfTable = '테이블의 마지막';
$strAttr = '보기';

$strBack = '뒤로';
$strBinary = '바이너리';
$strBinaryDoNotEdit = ' 바이너리 - 편집 금지 ';
$strBookmarkDeleted = '북마크를 제거했습니다.';
$strBookmarkQuery = '북마크된 SQL 쿼리';
$strBookmarkThis = '이 SQL 쿼리를 북마크함';
$strBrowse = '보기';
$strBzip = '"bz 압축"';

$strCantLoadMySQL = 'MySQL 확장모듈을 불러올 수 없습니다.<br />PHP 설정을 검사하십시오..';
$strCantRenameIdxToPrimary = '인덱스 이름을 기본(프라이머리)키로 바꿀 수 없습니다!';
$strCarriage = '캐리지 리턴: \\r';
$strChange = '변경';
$strChangePassword = '암호 변경';
$strCheckAll = '모두 체크';
$strCheckDbPriv = '데이터베이스 권한 검사';
$strCheckTable = '테이블 검사';
$strColumn = '칼럼';
$strColumnNames = '칼럼(칸) 이름';
$strCompleteInserts = '완전한 INSERT문 작성';
$strConfirm = '정말로 이 작업을 하시겠습니까?';
$strCopyTable = '테이블 복사하기 (데이터베이스명<b>.</b>테이블명):';
$strCopyTableOK = '%s 테이블이 %s 으로 복사되었습니다.';
$strCreate = ' 만들기 ';
$strCreateIndex = '%s 칼럼에 인덱스 만들기 ';
$strCreateIndexTopic = '새 인덱스 만들기';
$strCreateNewTable = '데이터베이스 %s에 새로운 테이블을 만듭니다.';

$strData = '데이터';
$strDataOnly = '데이터만';
$strDatabase = '데이터베이스 ';
$strDatabaseHasBeenDropped = '데이터베이스 %s 를 제거했습니다.';
$strDatabaseWildcard = '데이터베이스 (와일드카드문자 사용 가능):';
$strDatabases = '데이터베이스 ';
$strDatabasesStats = '데이터베이스 사용량 통계';
$strDefault = '기본값';
$strDelete = '삭제';
$strDeleteUserMessage = '사용자 %s 를 삭제했습니다.';
$strDeleted = '선택한 줄(레코드)을 삭제 하였습니다.';
$strDeletedRows = '지워진 줄(레코드):';
$strDescending = '내림차순(역순)';
$strDisplay = '보기';
$strDisplayOrder = '출력 순서:';
$strDoAQuery = '다음으로 쿼리를 만들기 (와일드카드: "%")';
$strDoYouReally = '정말로 다음을 실행하시겠습니까? ';
$strDocu = '도움말';
$strDrop = '삭제';
$strDropDB = '데이터베이스 %s 제거';
$strDropTable = '테이블 제거';
$strDumpXRows = '%s개의 레코드(줄)을 덤프 (%s번째 레코드부터).';
$strDumpingData = '테이블의 덤프 데이터';
$strDynamic = '동적(다이내믹)';

$strEdit = '수정';
$strEditPrivileges = '권한 수정';
$strEffective = '실제량';
$strEmpty = '비우기';
$strEmptyResultSet = '결과값이 없습니다. (빈 레코드 리턴.)';
$strEnd = '마지막';
$strEnglishPrivileges = ' 주의: MySQL 권한 이름은 영어로 표기되어야 합니다. ';
$strError = '오류';
$strExtendedInserts = '확장된 inserts';
$strExtra = '추가';

$strField = '필드';
$strFieldHasBeenDropped = '필드 %s 를 제거했습니다';
$strFields = '필드';
$strFieldsEmpty = ' 필드 갯수가 없습니다! ';
$strFieldsEnclosedBy = '필드 감싸기';
$strFieldsEscapedBy = '필드 특수문자(escape) 처리';
$strFieldsTerminatedBy = '필드 구분자 ';
$strFlushTable = '테이블 닫기(캐시 삭제)';
$strFunction = '함수';

$strGenTime = '처리한 시간';
$strGo = '실행';
$strGrants = '승인권한';
$strGzip = 'gz 압축';

$strHasBeenAltered = '을(를) 변경하였습니다.';
$strHasBeenCreated = '을(를) 작성하였습니다.';
$strHaveToShow = '출력하려면 적어도 1개 이상의 칼럼을 선택해야 합니다.';
$strHome = '시작페이지';
$strHomepageOfficial = 'phpMyAdmin 공식 홈';
$strHomepageSourceforge = '소스포지 phpMyAdmin 다운로드';
$strHost = '호스트';
$strHostEmpty = '호스트명이 없습니다!';

$strIfYouWish = '테이블 칼럼(칸)에 데이터를 추가할 때는 필드 목록을 콤마로 구분해 주십시요. ';
$strIgnore = 'Ignore';
$strInUse = '사용중';
$strIndex = '인덱스';
$strIndexHasBeenDropped = '인덱스 %s 를 제거했습니다';
$strIndexName = '인덱스 이름:';
$strIndexType = '인덱스 종류:';
$strIndexes = '인덱스';
$strInsecureMySQL = '여러분의 환경설정파일은 MySQL 서버의 기본적인 권한 설정에 대응합니다(관리자 암호 없음). 이 기본설정으로 MySQL 서버가 작동한다면 누구나 침입할 수 있으므로, 이 보안상 구멍을 고치시기 바랍니다.';
$strInsert = '삽입';
$strInsertAsNewRow = '새 열을 삽입합니다';
$strInsertNewRow = '새 열을 삽입';
$strInsertTextfiles = '텍스트파일을 읽어서 테이블에 데이터 삽입';
$strInsertedRows = '삽입된 열:';
$strInstructions = '설명서';
$strInvalidName = '"%s" 는 예약된 단어이므로 데이터베이스, 테이블, 필드명에 사용할 수 없습니다.';

$strKeepPass = '암호를 변경하지 않음';
$strKeyname = '키 이름';
$strKill = 'Kill';

$strLength = '길이';
$strLengthSet = '길이/값*';
$strLimitNumRows = '페이지당 레코드 수';
$strLineFeed = '줄(열)바꿈 문자: \\n';
$strLines = '줄(열)';
$strLinesTerminatedBy = '줄(열) 구분자';
$strLocationTextfile = 'SQL 텍스트파일의 위치';
$strLogPassword = '암호:';
$strLogUsername = '사용자명:';
$strLogin = '로그인';
$strLogout = '로그아웃';

$strModifications = '수정된 내용이 저장되었습니다.';
$strModify = '수정';
$strModifyIndexTopic = '인덱스 수정';
$strMoveTable = '테이블 옮기기 (데이터베이스명<b>.</b>테이블명):';
$strMoveTableOK = '테이블 %s 을 %s 로 옮겼습니다.';
$strMySQLCharset = 'MySQL 문자셋';
$strMySQLReloaded = 'MySQL을 재시동했습니다.';
$strMySQLSaid = 'MySQL 메시지: ';
$strMySQLServerProcess = '%pma_s2% (MySQL %pma_s1%)에 %pma_s3% 계정으로 들어왔습니다.';
$strMySQLShowProcess = 'MySQL 프로세스 보기';
$strMySQLShowStatus = 'MySQL 런타임 상태 보기';
$strMySQLShowVars = 'MySQL 시스템 환경변수 보기';

$strName = '이름';
$strNext = '다음';
$strNo = ' 아니오 ';
$strNoDatabases = '데이타베이스가 없습니다';
$strNoDescription = '설명이 없습니다';
$strNoDropDatabases = '"DROP DATABASE" 구문은 허락되지 않습니다.';
$strNoExplain = '해석(EXPLAIN) 생략';
$strNoFrames = 'phpMyAdmin 은 <b>프레임을 지원하는</b> 브라우저에서 잘 보입니다.';
$strNoIndex = '인덱스가 설정되지 않았습니다!';
$strNoModification = '변화 없음';
$strNoPassword = '암호 없음';
$strNoPhp = 'PHP 코드 없이 보기';
$strNoPrivileges = '권한 없음';
$strNoQuery = 'SQL 쿼리 없음!';
$strNoRights = '어떻게 들어오셨어요? 지금 여기 있을 권한이 없습니다!';
$strNoTablesFound = '데이터베이스에 테이블이 없습니다.';
$strNoUsersFound = '사용자가 없습니다.';
$strNone = 'None';
$strNotNumber = '은 숫자(번호)가 아닙니다!';
$strNotValidNumber = '은 올바른 열 번호가 아닙니다!';

$strOptimizeTable = '테이블 최적화';
$strOptionalControls = '특수문자 읽기/쓰기 옵션';
$strOptionally = '옵션입니다.';
$strOptions = '테이블 옵션';
$strOr = '또는';
$strOverhead = '부담';

$strPHPVersion = 'PHP 버전';
$strPageNumber = '페이지 번호:';
$strPassword = '암호';
$strPasswordEmpty = '암호가 비었습니다!';
$strPasswordNotSame = '암호가 동일하지 않습니다!';
$strPdfDbSchema = '"%s" 데이타베이스의 스킴(윤곽) - 페이지 %s';
$strPdfInvalidPageNum = 'PDF 페이지 번호가 설정되지 않았습니다!';
$strPdfInvalidTblName = '"%s" 테이블이 존재하지 않습니다!';
$strPdfNoTables = '테이블이 없습니다';
$strPhp = 'PHP 코드 보기';
$strPmaDocumentation = 'phpMyAdmin 설명서';
$strPmaUriError = '환경설정 파일에서 <tt>$cfg[\'PmaAbsoluteUri\']</tt> 주소를 기입하십시오!';
$strPos1 = '처음';
$strPrevious = '이전';
$strPrimary = '기본';
$strPrimaryKey = '기본(프라이머리) 키';
$strPrimaryKeyHasBeenDropped = '기본(프라이머리)키를 제거했습니다';
$strPrimaryKeyName = '기본(프라이머리)키의 이름은 반드시 PRIMARY여야 합니다!';
$strPrimaryKeyWarning = '("PRIMARY"는 <b>반드시</b> 기본(프라이머리)키의 <b>유일한</b> 이름이어야 합니다!)';
$strPrintView = '인쇄용 보기';
$strPrivileges = '권한';
$strProperties = '속성';

$strQBE = '보기에서 쿼리 만들기';
$strQBEDel = '삭제';
$strQBEIns = '삽입';
$strQueryOnDb = '데이터베이스 <b>%s</b>에 SQL 쿼리:';

$strReType = '재입력';
$strRecords = '레코드수';
$strReferentialIntegrity = 'referential 무결성 검사:';
$strReloadFailed = 'MySQL 재시동에 실패하였습니다.';
$strReloadMySQL = 'MySQL 재시동';
$strRememberReload = '서버를 재시동하는 것을 잊지마세요.';
$strRenameTable = '테이블 이름 변경하기';
$strRenameTableOK = '테이블 %s을(를) %s(으)로 변경하였습니다.';
$strRepairTable = '테이블 복구';
$strReplace = '대치(Replace)';
$strReplaceTable = '파일로 테이블 대치하기';
$strReset = '리세트';
$strRevoke = '제거';
$strRevokeGrant = '승인 제거';
$strRevokeGrantMessage = '%s의 승인 권한을 제거했습니다.';
$strRevokeMessage = '%s의 권한을 제거했습니다.';
$strRevokePriv = '권한 제거';
$strRowLength = '열 길이';
$strRowSize = ' Row size ';
$strRows = '열';
$strRowsFrom = '열. 시작(열)위치';
$strRowsModeHorizontal = '수평(가로)';
$strRowsModeOptions = ' %s 정렬 (%s 칸이 넘으면 헤더 반복)';
$strRowsModeVertical = '수직(세로)';
$strRowsStatistic = '레코드(줄) 통계';
$strRunQuery = '쿼리 실행';
$strRunSQLQuery = '데이터베이스 %s에 SQL 쿼리를 실행';
$strRunning = '입니다. (%s)';

$strSQL = 'SQL';
$strSQLParserUserError = 'SQL 쿼리문에 에러가 있습니다. MySQL 서버가 다음과 같은 에러를 출력했습니다. 이것이 문제를 진단하는데 도움이 될 것입니다.';
$strSQLQuery = 'SQL 쿼리';
$strSQLResult = 'SQL 결과';
$strSQPBugInvalidIdentifer = '잘못된 식별자(Identifer)';
$strSave = '보존';
$strSearch = '검색';
$strSearchFormTitle = '데이타베이스 검색';
$strSearchInTables = '찾을 테이블:';
$strSearchNeedle = '찾을 단어, 값 (와일드카드: "%"):';
$strSearchOption1 = '아무 단어나';
$strSearchOption2 = '모든 단어';
$strSearchOption3 = '정확한 문구';
$strSearchOption4 = '정규표현식';
$strSearchType = '찾는 방식:';
$strSelect = '선택';
$strSelectADb = '데이터베이스를 선택하세요';
$strSelectAll = '모두 선택';
$strSelectFields = '필드 선택 (하나 이상):';
$strSelectNumRows = '쿼리(in query)';
$strSend = '파일로 저장';
$strServerChoice = '서버 선택';
$strServerVersion = '서버 버전';
$strSetEnumVal = '필드 종류가 "enum"이나 "set"이면, 다음과 같은 형식으로 값을 입력하십시오: \'a\',\'b\',\'c\'...<br />이 값에 역슬래시("\")나 작은따옴표("\'")가 넣어야 한다면, 역슬래시를 사용하십시오. (예: \'\\\\xyz\' 또는 \'a\\\'b\').';
$strShow = '보기';
$strShowAll = '모두 보기';
$strShowCols = '칼럼(칸) 보기';
$strShowPHPInfo = 'PHP 정보 보기';
$strShowTables = '테이블 보기';
$strShowThisQuery = ' 이 쿼리를 다시 보여줌 ';
$strShowingRecords = '레코드(줄) 보기';
$strSingly = '(단독으로)';
$strSize = '크기';
$strSort = '정렬';
$strSpaceUsage = '공간 사용량';
$strSplitWordsWithSpace = '단어는 스페이스(" ")로 구분됩니다.';
$strStatement = '명세';
$strStrucCSV = 'CSV 데이터';
$strStrucData = '구조와 데이터 모두';
$strStrucDrop = '\'DROP TABLE\'문 추가';
$strStrucExcelCSV = 'MS엑셀 CSV 데이터';
$strStrucOnly = '구조만';
$strStructPropose = '제안하는 테이블 구조';
$strStructure = '구조';
$strSubmit = '확인';
$strSuccess = 'SQL 쿼리가 바르게 실행되었습니다.';
$strSum = '계';

$strTable = '테이블 ';
$strTableComments = '테이블 설명';
$strTableEmpty = '테이블명이 없습니다!';
$strTableHasBeenDropped = '테이블 %s 을 제거했습니다.';
$strTableHasBeenEmptied = '테이블 %s 을 비웠습니다';
$strTableHasBeenFlushed = '테이블 %s 을 닫았습니다(캐시 삭제)';
$strTableMaintenance = '테이블 유지보수';
$strTableStructure = '테이블 구조';
$strTableType = '테이블 종류';
$strTables = '테이블 %s 개';
$strTextAreaLength = ' 필드의 길이 때문에,<br />이 필드를 편집할 수 없습니다 ';
$strTheContent = '파일 내용을 삽입하였습니다.';
$strTheContents = '파일 내용이 선택한 테이블의 프라이머리 혹은 고유값 키와 일치하는 열을 대치(代置)시키겠습니다.';
$strTheTerminator = '필드 종료 기호.';
$strTotal = '합계';
$strType = '종류';

$strUncheckAll = '모두 체크안함';
$strUnique = '고유값';
$strUnselectAll = '모두 선택안함';
$strUpdatePrivMessage = '%s 의 권한을 업데이트했습니다.';
$strUpdateProfile = '프로파일 업데이트:';
$strUpdateProfileMessage = '프로파일을 업데이트했습니다.';
$strUpdateQuery = '쿼리 업데이트';
$strUsage = '사용법(량)';
$strUseBackquotes = '테이블, 필드명에 백쿼터(`) 사용';
$strUseTables = '사용할 테이블';
$strUser = '사용자';
$strUserEmpty = '사용자명이 없습니다!';
$strUserName = '사용자명';
$strUsers = '사용자들';

$strValue = '값';
$strViewDump = '테이블의 덤프(스키마) 데이터 보기';
$strViewDumpDB = '데이터베이스의 덤프(스키마) 데이터 보기';

$strWelcome = '%s에 오셨습니다';
$strWithChecked = '선택한 것을:';
$strWrongUser = '사용자명/암호가 틀렸습니다. 접근이 거부되었습니다.';

$strYes = ' 예 ';

$strZip = 'zip 압축';

$strAll = 'All'; // To translate
$strAllTableSameWidth = '모든 테이블을 같은 너비로 출력할까요?';  //to translate
$strAny = 'Any'; // To translate

$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate
$strBookmarkLabel = 'Label'; // To translate
$strBookmarkView = 'View only'; // To translate

$strCantLoadRecodeIconv = 'Can not load iconv or recode extension needed for charset conversion, configure php to allow using these extensions or disable charset conversion in phpMyAdmin.';  //to translate
$strCantUseRecodeIconv = 'Can not use iconv nor libiconv nor recode_string function while extension reports to be loaded. Check your php configuration.';  //to translate
$strCardinality = 'Cardinality'; // To translate
$strChangeDisplay = '출력할 필드 선택';  //to translate
$strCharsetOfFile = '파일 문자셋:'; //to translate
$strChoosePage = '편집할 페이지를 선택하세요';  //to translate
$strColComFeat = '칼럼 코멘트 출력하기';  //to translate
$strComments = 'Comments';  //to translate
$strCompression = 'Compression'; //to translate
$strConfigFileError = 'phpMyAdmin이 환경설정 파일을 읽을 수 없습니다!<br />This might happen if php finds a parse error in it or php cannot find the file.<br />Please call the configuration file directly using the link below and read the php error message(s) that you recieve. In most cases a quote or a semicolon is missing somewhere.<br />If you recieve a blank page, everything is fine.'; //to translate
$strConfigureTableCoord = 'Please configure the coordinates for table %s';  //to translate
$strCookiesRequired = '쿠키 사용이 가능해야 합니다 past this point.'; // To translate
$strCreateNewDatabase = '새 데이터베이스 만들기'; // To translate
$strCreatePage = '새 페이지 만들기';  //to translate
$strCreatePdfFeat = 'Creation of PDFs';  //to translate
$strCriteria = 'Criteria'; // To translate

$strDataDict = 'Data Dictionary';  //to translate
$strDeleteFailed = 'Deleted Failed!'; // To translate
$strDisabled = 'Disabled';  //to translate
$strDisplayFeat = 'Display Features';  //to translate
$strDisplayPDF = 'Display PDF schema';  //to translate

$strEditPDFPages = 'PDF 페이지 편집';  //to translate
$strEnabled = 'Enabled';  //to translate
$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'SQL 해석';  //to translate
$strExport = '내보내기';  //to translate
$strExportToXML = 'XML 형식으로 내보내기'; //to translate

$strFixed = 'fixed'; // To translate
$strFormEmpty = 'Missing value in the form !'; // To translate
$strFormat = 'Format'; // To translate
$strFullText = 'Full Texts'; // To translate

$strGenBy = 'Generated by'; //to translate
$strGeneralRelationFeat = 'General relation features';  //to translate

$strIdxFulltext = 'Fulltext'; // To translate
$strImportDocSQL = 'Import docSQL Files';  //to translate

$strLinkNotFound = 'Link not found';  //to translate
$strLinksTo = 'Links to';  //to translate

$strMissingBracket = 'Missing Bracket';  //to translate

$strNoIndexPartsDefined = 'No index parts defined!'; // To translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate
$strNotOK = 'not OK';  //to translate
$strNotSet = '<b>%s</b> table not found or not set in %s';  //to translate
$strNull = 'Null'; // To translate
$strNumSearchResultsInTable = '%s match(es) inside table <i>%s</i>';//to translate
$strNumSearchResultsTotal = '<b>Total:</b> <i>%s</i> match(es)';//to translate

$strOK = 'OK';  //to translate
$strOftenQuotation = 'Often quotation marks. 옵션(OPTIONALLY)은 char 및 varchar 필드값을 따옴표(")문자로 닫는다는 것을 뜻합니다.';  // To translate
$strOperations = '테이블 작업';  //to translate

$strPHP40203 = 'You are using PHP 4.2.3, which has a serious bug with multi-byte strings (mbstring). See PHP bug report 19404. This version of PHP is not recommended for use with phpMyAdmin.';  //to translate
$strPartialText = 'Partial Texts'; // To translate
$strPrint = 'Print';  //to translate
$strPutColNames = '맨처음에 필드 이름을 출력';  //to translate

$strRelationNotWorking = 'linked Tables 에서 작동하는 부가기능이 사용중지되었습니다. 이유를 알려면 %s여기를 클릭%s하십시오.';  //to translate
$strRelationView = 'Relation view';  //to translate

$strSQLParserBugMessage = 'There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:';  //to translate
$strSQPBugUnclosedQuote = '따옴표(quote)가 닫히지 않았음';  //to translate
$strSQPBugUnknownPunctuation = 'Unknown Punctuation String';  //to translate
$strScaleFactorSmall = 'The scale factor is too small to fit the schema on one page';  //to translate
$strSearchResultsFor = 'Search results for "<i>%s</i>" %s:';//to translate
$strSelectTables = 'Select Tables';  //to translate
$strServer = '서버 %s';  //to translate
$strShowColor = 'Show color';  //to translate
$strShowGrid = 'Show grid';  //to translate
$strShowTableDimension = 'Show dimension of tables';  //to translate

$strValidateSQL = 'Validate SQL'; // To Translate
$strValidatorError = 'The SQL validator could not be initialized. Please check if you have installed the necessary php extensions as described in the %sdocumentation%s.'; //to translate

$strWebServerUploadDirectory = '웹서버 업로드 디렉토리';  //to translate
$strWebServerUploadDirectoryError = '업로드 디렉토리에 접근할 수 없습니다';  //to translate

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
$strPrivDescMaxUpdates = 'Limits the number of commands that that change any table or database the user may execute per hour.';
$strPrivDescProcess3 = 'Allows killing processes of other users.'; //to translate
$strPrivDescProcess4 = 'Allows viewing the complete queries in the process list.'; //to translate
$strPrivDescReferences = 'Has no effect in this MySQL version.'; //to translate
$strPrivDescReplClient = 'Gives the right tp the user to ask where the slaves / masters are.'; //to translate
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
?>
