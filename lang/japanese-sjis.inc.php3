<?php
/* $Id$ */

$charset = 'SHIFT_JIS';
$text_dir = 'ltr';
$left_font_family = 'sans-serif';
$right_font_family = 'sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('バイト', 'KB', 'MB', 'GB');

$day_of_week = array('日', '月', '火', '水', '木', '金', '土');
$month = array('1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%Y年%B%e日 %H:%M';


$strAccessDenied = 'アクセスは拒否されました。';
$strAction = '実行';
$strAddDeleteColumn = 'フィールド列の「追加／削除」';
$strAddDeleteRow = '条件行の「追加／削除」';
$strAddNewField = 'フィールドの追加';
$strAddPriv = '新しい特権の追加';
$strAddPrivMessage = '新しい特権を追加しました。';
$strAddSearchConditions = '検索条件文を追加してください。("where"の節文):';
$strAddToIndex = ' &nbsp;%s&nbsp;の列をインデックスに追加しました';
$strAddUser = 'ユーザーの追加';
$strAddUserMessage = 'ユーザーを追加しました。';
$strAffectedRows = '影響された行数:';
$strAfter = '後に %s';
$strAfterInsertBack = '戻る';
$strAfterInsertNewInsert = '新レコードの追加';
$strAll = '全部';
$strAlterOrderBy = 'テーブル順番の条件';
$strAnalyzeTable = 'テーブルを分析します。';
$strAnd = '及び';
$strAnIndex = 'インデックスが%sに追加されました。';
$strAny = '全て';
$strAnyColumn = '全コラム';
$strAnyDatabase = '全データベース';
$strAnyHost = '全てのホスト';
$strAnyTable = '全てのテーブル';
$strAnyUser = '全てのユーザー';
$strAPrimaryKey = '主要キーが%sに追加されました。';
$strAscending = '昇順';
$strAtBeginningOfTable = 'テーブルの最初';
$strAtEndOfTable = 'テーブルの最後';
$strAttr = '表示';

$strBack = '戻る';
$strBinary = ' バイナリ ';
$strBinaryDoNotEdit = ' バイナリ -  修正出来ません';
$strBookmarkDeleted = 'ブックマークを正常に削除しました。';
$strBookmarkLabel = 'ラベル';
$strBookmarkQuery = 'ブックマークされているSQLクエリー';
$strBookmarkThis = 'SQLクエリーをブックマークする';
$strBookmarkView = '発表だけ';
$strBrowse = '表示';
$strBzip = '"bzipされる"';

$strCantLoadMySQL = 'MySQLを実行できません。<br />PHPの設定を確認して下さい。';
$strCantRenameIdxToPrimary = 'インデックスの名前をPRIMARYに変更できません。';
$strCardinality = 'カーディナリティ';
$strCarriage = 'キャリッジリターン: \\r';
$strChange = '変更';
$strCheckAll = '全てをマーク';
$strCheckDbPriv = 'データベースの特権の確認';
$strCheckTable = 'テーブルをチェックします。';
$strColumn = '列';
$strColumnNames = '列(コラム)名';
$strCompleteInserts = '完全なINSERT文の作成';
$strConfirm = '実行しても良いですか？';
$strCookiesRequired = 'ここから先はクッキーが許可されている必要があります。';
$strCopyTable = 'テーブルを(database<b>.</b>table)にコピーする:';
$strCopyTableOK = '%sテーブルを%sにコピーしました。';
$strCreate = '作成';
$strCreateIndex = '&nbsp;%s&nbsp;の列のインデックスの作成';
$strCreateIndexTopic = '新しいインデックスの作成';
$strCreateNewDatabase = '新しいDBを作成します。';
$strCreateNewTable = '現在のDBに新しいテーブルを作成します。 --> ';
$strCriteria = '基準';

$strData = 'データ';
$strDatabase = 'データベース';
$strDatabaseHasBeenDropped = 'データベース%sを正常に削除しました。';
$strDatabases = 'データベース';
$strDatabasesStats = 'データベースの統計';
$strDataOnly = 'データのみ';
$strDefault = '基本値';
$strDelete = '削除';
$strDeleted = '選択した列を削除しました。';
$strDeleteFailed = '削除に失敗しました';
$strDeleteUserMessage = 'ユーザー%sを削除しました。';
$strDeletedRows = '削除された行数:';
$strDescending = '降順';
$strDisplay = '表示';
$strDisplayOrder = '発表順番:';
$strDoAQuery = '"例のQUERY"を実行 (wildcard: "%")';
$strDocu = 'ヘルプ';
$strDoYouReally = '本当に実行しても良いですか？ --> ';
$strDrop = '削除';
$strDropDB = 'データベース%sの削除 ';
$strDropTable = 'テーブルの削除';
$strDumpingData = 'テーブルのダンプデータ';
$strDynamic = 'ダイナミック';

$strEdit = '修正';
$strEditPrivileges = '特権を修正';
$strEffective = '事実上';
$strEmpty = '空にする';
$strEmptyResultSet = 'MySQLが空の値を返しました。(i.e. zero rows).';
$strEnd = '最後';
$strEnglishPrivileges = ' 注意: MySQLの特権の名前は英語で発表しています。';
$strError = 'エラー';
$strExtendedInserts = '長いINSERT文の作成';
$strExtra = '追加';

$strField = 'フィールド';
$strFieldHasBeenDropped = 'フィールド%sが正常に削除されました';
$strFields = 'フィールド';
$strFieldsEmpty = ' フィールド数は空です。 ';
$strFieldsEnclosedBy = 'フィールド囲み記号';
$strFieldsEscapedBy = 'フィールドのエスケープ記号';
$strFieldsTerminatedBy = 'フィールド区切り記号';
$strFixed = '固定';
$strFlushTable = 'テーブルのキャッシュを空にする("FLUSH")';
$strFormat = 'フォーマット';
$strFormEmpty = 'フォームでは値がありませんでした。';
$strFullText = '全文';
$strFunction = '関数';

$strGenTime = '作成の時間';
$strGo = '実行';
$strGrants = '付与';
$strGzip = '"gzipされる"';

$strHasBeenAltered = 'を変更しました。';
$strHasBeenCreated = 'を作成しました。';
$strHome = 'メーンページへ';
$strHomepageOfficial = 'phpMyAdminホーム';
$strHomepageSourceforge = 'SourceforgeのphpMyAdminダウンロードページ';
$strHost = 'ホスト';
$strHostEmpty = 'ホスト名は空です!';

$strIdxFulltext = '全文';
$strIfYouWish = 'テーブルのコラム(列)にデータを追加する場合は、フィールドリストをカンマで区分してください。';
$strIgnore = '無視';
$strIndex = 'インデックス';
$strIndexes = 'インデックス数';
$strIndexHasBeenDropped = 'インデックス%sが削除されました';
$strIndexName = 'インデックス名&nbsp;:';
$strIndexType = 'インデックスのタイプ&nbsp;:';
$strInsert = '追加';
$strInsertAsNewRow = '新しい行としての追加';
$strInsertedRows = '追加された行数:';
$strInsertNewRow = '新しい行の追加';
$strInsertTextfiles = 'テーブルにテキストファイルの追加';
$strInstructions = '教示';
$strInUse = '使用中';
$strInvalidName = '"%s"は予約語ですから「データベース／テーブル／フィールド」名には使えません。';

$strKeepPass = 'パスワードを変更しない';
$strKeyname = 'キー名';
$strKill = '停止';

$strLength = '長さ';
$strLengthSet = '長さ/セット*';
$strLimitNumRows = 'ページのレコード数';
$strLineFeed = '改行文字: \\n';
$strLines = '行';
$strLinesTerminatedBy = '行の終端記号';
$strLocationTextfile = 'テキストファイルの場所';
$strLogin = 'ログイン';
$strLogout = 'ログアウト';
$strLogPassword = 'パスワード:';
$strLogUsername = 'ユーザー名:';

$strModifications = 'を正しく修正しました。';
$strModify = '修正';
$strModifyIndexTopic = 'インデックスの変更';
$strMoveTable = 'テーブルを(database<b>.</b>table)に移動:';
$strMoveTableOK = 'テーブル%sが%s移動されました。';
$strMySQLReloaded = 'MySQLを新しく読み込みました。';
$strMySQLSaid = 'MySQLのメッセージ --> ';
$strMySQLServerProcess = 'MySQL %pma_s1%は%pma_s2%上%pma_s3%として実行しています。';
$strMySQLShowProcess = 'MySQLプロセスの表示';
$strMySQLShowStatus = 'MySQLのランタイム情報';
$strMySQLShowVars = 'MySQLのシステム変数';

$strName = '名前';
$strNbRecords = 'レコード数';
$strNext = '次へ';
$strNo = 'いいえ';
$strNoDatabases = 'データベース数';
$strNoDropDatabases = '"DROP DATABASE"ステートメントは禁止される。';
$strNoFrames = '<b>フレーム</b>可能なブラウザーの方がphpMyAdminは使いやすいです。';
$strNoIndex = 'インデックスは設定されていません。';
$strNoIndexPartsDefined = 'インデックスの部分は設定されていません。';
$strNoModification = '変更無';
$strNone = '無';
$strNoPassword = 'パスワード無し';
$strNoPrivileges = '特権無し';
$strNoQuery = 'SQLクエリー無し';
$strNoRights = '現在特権を持ってないのでここに入れません。';
$strNoTablesFound = '現在のDBにテーブルはありません。';
$strNotNumber = 'これは番号ではありません。';
$strNotValidNumber = ' は行の正しい番号ではありません ';
$strNoUsersFound = 'ユーザーは見つかりませんでした。';
$strNull = '空の値(Null)';

$strOftenQuotation = '引用符号です。オプションは、charまたはvarcharフィールドのみ" "で囲まれていることを意味します。';
$strOptimizeTable = 'テーブルを最適化します。';
$strOptionalControls = '特殊文字の読み込み/書き込みオプション';
$strOptionally = 'オプションです。';
$strOr = '又は';
$strOverhead = 'オーバーヘッド';

$strPartialText = '部分的な文書';
$strPassword = 'パスワード';
$strPasswordEmpty = 'パスワードは空です。';
$strPasswordNotSame = 'パスワードは空です。';
$strPHPVersion = 'PHP バージョン';
$strPmaDocumentation = 'phpMyAdminのドキュメント';
$strPos1 = '最初';
$strPrevious = '以前';
$strPrimary = '主要';
$strPrimaryKey = '主要キー';
$strPrimaryKeyHasBeenDropped = '主要キーを削除しました。';
$strPrimaryKeyName = '主要キーの名前は... PRIMARYではなければいけません。';
$strPrimaryKeyWarning = '("PRIMARY"はちょうど主要キーの名前ではなければいけません。';
$strPrintView = '印刷用表示';
$strPrivileges = '特権';
$strProperties = 'プロパティ';

$strQBE = '例からクエリー実行';
$strQBEDel = '削除';
$strQBEIns = '入';
$strQueryOnDb = 'データベースのSQLクエリー <b>%s</b>:';

$strRecords = 'レコード数';
$strReloadFailed = 'MySQLのリロードに失敗しました。';
$strReloadMySQL = 'MySQLのリロード';
$strRememberReload = 'サーバーのリロードを忘れないで下さい。';
$strRenameTable = 'テーブル名の変更';
$strRenameTableOK = '%sを%sに名前を変更しました。';
$strRepairTable = 'テーブルを復旧します。';
$strReplace = '置き換える';
$strReplaceTable = 'ファイルでテーブルを置き換える';
$strReset = 'リセット';
$strReType = '再記入';
$strRevoke = '廃止';
$strRevokeGrant = ' 付与の取消';
$strRevokeGrantMessage = '%sの付与特権を取消しました。';
$strRevokeMessage = '%sの特権を取消しました';
$strRevokePriv = '特権の取消';
$strRowLength = '行の長さ';
$strRows = '行';
$strRowsFrom = '開始行';
$strRowSize = ' 行のサイズ ';
$strRowsModeHorizontal = '水平';
$strRowsModeOptions = '方向: %s : %s 列ずつヘッダーを繰り返し発表する';
$strRowsModeVertical = '重直';
$strRowsStatistic = '行の統計';
$strRunning = 'が実行中です。 %s';
$strRunQuery = 'クエリーの実行';
$strRunSQLQuery = 'データベース%sにSQLクエリー実行';

$strSave = '保存';
$strSelect = '選択';
$strSelectADb = 'データベースを選択してください';
$strSelectAll = '全選択';
$strSelectFields = 'フィールドの選択(一つ以上):';
$strSelectNumRows = 'クエリー';
$strSend = 'ファイルに落とす';
$strSequence = '順.';
$strServerChoice = 'サーバーの選択';
$strServerVersion = 'サーバーのバージョン';
$strSetEnumVal = 'フィールドタイプが"enum"又は"set"の場合は値をこのフォーマットを使って入力して下さい: \'a\',\'b\',\'c\'...<br />バックスラッシュ「"\"」又はクオート「"\'"」を入力したいと、頭にバックスラッシュを付けて下さい「例: \'\\\\xyz\' or \'a\\\'b\'」。';
$strShow = '表示';
$strShowAll = '全の発' . "\x5c";
$strShowCols = '列の発' . "\x5c";
$strShowingRecords = 'レコード表示';
$strShowPHPInfo = 'PHP情報';
$strShowTables = 'テーブルの発' . "\x5c";
$strShowThisQuery = ' 実行したクエリーをここに表示する ';
$strSingly = '(一回)';
$strSize = 'サイズ';
$strSort = 'ソート';
$strSpaceUsage = 'ディスク使用量';
$strSQLQuery = '実行されたSQLクエリー';
$strStartingRecord = '最初のレコード';
$strStatement = 'ステートメント';
$strStrucCSV = 'CSVデータ';
$strStrucData = '構造とデータ';
$strStrucDrop = '\'drop table\'を追加';
$strStrucExcelCSV = 'Ms ExcelへのCSVデータ';
$strStrucOnly = '構造のみ';
$strSubmit = '実行';
$strSuccess = 'SQLクエリーが正常に実行されました。';
$strSum = '合計';

$strTable = 'テーブル ';
$strTableComments = 'テーブルの説明';
$strTableEmpty = 'テーブル名は空です。';
$strTableHasBeenDropped = 'テーブル%sを削除しました。';
$strTableHasBeenEmptied = 'テーブル%sを空にしました。';
$strTableHasBeenFlushed = 'テーブル%sのキャッシュを空にしました。';
$strTableMaintenance = 'テーブル管理';
$strTables = '%sテーブル';
$strTableStructure = 'テーブルの構造';
$strTableType = 'テーブルのタイプ';
$strTextAreaLength = ' 長さの所為でこのフィールドを<br /> 修正出来ない可能性があります ';
$strTheContent = 'ファイルのデータを挿入しました。';
$strTheContents = 'ファイルのデータで、選択したテーブルの主要キーまたは唯一なキーに一致する列を置き換えます。';
$strTheTerminator = 'フィールドの終端記号です。';
$strTotal = '合計';
$strType = 'フィールドタイプ';

$strUncheckAll = '全てのマークを削除';
$strUnique = '唯一';
$strUnselectAll = '全解放';
$strUpdatePrivMessage = '%sの特権をアップデートしました。';
$strUpdateProfile = 'プロファイルのアップデート:';
$strUpdateProfileMessage = 'プロファイルをアップデートしました。';
$strUpdateQuery = 'クエリーのアップデート';
$strUsage = '使用量';
$strUseBackquotes = '逆クオートでテーブル名やフィールド名を囲む';
$strUser = 'ユーザー';
$strUserEmpty = 'ユーザー名は空です。';
$strUserName = 'ユーザー名';
$strUsers = 'ユーザー';
$strUseTables = '使うテーブル';

$strValue = '値';
$strViewDump = 'テーブルのダンプ(スキーマ)表示';
$strViewDumpDB = 'DBのダンプ(スキーマ)表示';

$strWelcome = '%sへようこそ';
$strWithChecked = 'チェックしたものを:';
$strWrongUser = 'ユーザ名またはパスワードが正しくありません。<br />アクセスは拒否されました。';

$strYes = 'はい';

$strZip = '"zipされる"';

// japanese only
$strEncto = 'エンコーディングへ変換する'; // encoding convert
$strKanjiEncodConvert = '漢字コード変換'; // kanji code convert
$strXkana = '全角カナへ変換する'; // convert to X208-kana

// To translate
?>
