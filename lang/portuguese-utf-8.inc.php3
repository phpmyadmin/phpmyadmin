<?php
/* $Id$ */

/**
 * Portuguese language file by
 *   Lopo Pizarro <lopopizarro@users.sourceforge.net>
 *   António Raposo <cfmsoft@users.sourceforge.net>
 */

$charset = 'utf-8';
$allow_recoding = TRUE;
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
// shortcuts for Byte, Kilo, Mega, Tera, Peta, Exa
$byteUnits = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

$day_of_week = array('Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab');
$month = array('Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d-%B-%Y às %H:%M';

$strAccessDenied = 'Acesso Negado';
$strAction = 'Acções';
$strAddDeleteColumn = 'Adicionar/Remover Campos';
$strAddDeleteRow = 'Adicionar/Remover Critérios';
$strAddNewField = 'Adiciona novo campo';
$strAddPriv = 'Acrescenta um novo Privilégio';
$strAddPrivMessage = 'Acrescentou um novo privilégio.';
$strAddSearchConditions = 'Condição de Pesquisa (Complemento da clausula "where"):';
$strAddToIndex = 'Adicionar ao índice &nbsp;%s&nbsp;coluna(s)';
$strAddUser = 'Acrescenta um utilizador';
$strAddUserMessage = 'Acrescentou um novo utilizador.';
$strAffectedRows = 'Linhas afectadas:';
$strAfter = 'Depois %s';
$strAfterInsertBack = 'Voltar atrás';
$strAfterInsertNewInsert = 'Inserir novo registo';
$strAll = 'Todas';
$strAllTableSameWidth = 'mostrar todas as tabelas com a mesma altura?';
$strAlterOrderBy = 'Alterar a ordem da tabela por';
$strAnalyzeTable = 'Analizar tabela';
$strAnd = 'E';
$strAnIndex = 'Um índice foi adicionado a %s';
$strAny = 'Todos';
$strAnyColumn = 'Qualquer coluna';
$strAnyDatabase = 'Qualquer base de dados';
$strAnyHost = 'Qualquer máquina';
$strAnyTable = 'Qualquer tabela';
$strAnyUser = 'Qualquer utilizador';
$strAPrimaryKey = 'Uma chave primária foi adicionada a %s';
$strAscending = 'Ascendente';
$strAtBeginningOfTable = 'No Início da Tabela';
$strAtEndOfTable = 'No Fim da Tabela';
$strAttr = 'Atributos';

$strBack = 'Voltar';
$strBinary = ' Binário ';
$strBinaryDoNotEdit = ' Binário - não editar ';
$strBookmarkDeleted = 'Marcador apagado com sucesso.';
$strBookmarkLabel = 'Etiqueta';
$strBookmarkQuery = 'Comandos SQL marcados';
$strBookmarkThis = 'Marcar este comando SQL';
$strBookmarkView = 'Ver apenas';
$strBrowse = 'Visualiza';
$strBzip = '"Compressão bzip"';

$strCantLoadMySQL = 'não foi possível carregar a extensão MySQL,<br />por favor verifique a configuração do PHP.';
$strCantLoadRecodeIconv = 'Não é possível carregar <i>iconv</i> ou recodificar a extensão necessária para a conversão do Mapa de Caracteres, configure o php de modo a permitir utilizar estas extensões ou desligue a conversão do mapa de caracteres no phpmyadmin.';
$strCantRenameIdxToPrimary = 'Impossível renomear índice para PRIMARY!';
$strCantUseRecodeIconv = 'Não é possível usar <i>iconv</i> nem <i>libiconv</i> nem a função <i>recode_string</i> enquanto a extensão reportar que está ligada. Confira a configuração do seu php.';
$strCardinality = 'Quantidade';
$strCarriage = 'Fim de linha: \\r';
$strChange = 'Muda';
$strChangeDisplay = 'Escolha campo para mostrar';
$strChangePassword = 'Alterar a senha';
$strCheckAll = 'Todos';
$strCheckDbPriv = 'Visualiza os Privilégios da Base de Dados';
$strCheckTable = 'Verificar tabela';
$strChoosePage = 'Escolha uma Página para editar';
$strColComFeat = 'Mostrando comentários das Colunas';
$strColumn = 'Campo';
$strColumnNames = 'Nome dos Campos';
$strComments = 'Comentários';
$strCompleteInserts = 'Instrucções de inserção completas';
$strConfigFileError = 'O phpMyAdmin não foi capaz de ler o ficheiro de configuração!<br />Isto pode acontecer se o php encontrar um erro no <i>parsing</i>  ou se não conseguir encontrar o ficheiro.<br />Chame o ficheiro de configuração directamente usando o <i>link</i> a baixo e leia a(s) mensagem(ns) de erro do php. Na maior parte dos casos, trata-se de uma falta de aspas ou de um ponto e vírgula algures.<br />Se receber uma página em branco, está tudo correcto.';
$strConfigureTableCoord = 'Configure as cordenadas para a tabela %s';
$strConfirm = 'Confirma a sua opção?';
$strCookiesRequired = 'O mecanismo de "Cookies" tem de estar ligado a partir deste ponto.';
$strCopyTable = 'Copia tabela para (base-de-dados<b>.</b>tabela):';
$strCopyTableOK = 'Tabela %s copiada para %s.';
$strCreate = 'Criar';
$strCreateIndex = 'Criar um índice com&nbsp;%s&nbsp;coluna(s)';
$strCreateIndexTopic = 'Criar um novo índice';
$strCreateNewDatabase = 'Criar nova base de dados';
$strCreateNewTable = 'Criar nova tabela na base de dados %s';
$strCreatePage = 'Criar uma Página nova';
$strCreatePdfFeat = 'Criação de PDFs';
$strCriteria = 'Critérios';

$strData = 'Dados';
$strDatabase = 'Base de Dados ';
$strDatabaseHasBeenDropped = 'A base de dados %s foi eliminada.';
$strDatabases = 'Base de Dados';
$strDatabasesStats = 'Estatísticas das bases de dados';
$strDatabaseWildcard = 'Base de Dados (aceita caracteres universais):';
$strDataOnly = 'Apenas dados';
$strDefault = 'Defeito';
$strDelete = 'Apagar';
$strDeleted = 'Registo eliminado';
$strDeletedRows = 'Linhas apagadas:';
$strDeleteFailed = 'Erro ao apagar!';
$strDeleteUserMessage = 'Apagou o utilizador %s.';
$strDescending = 'Descendente';
$strDisabled = 'Desactidado';
$strDisplay = 'Mostra';
$strDisplayFeat = 'Mostrar Características';
$strDisplayOrder = 'Ordem de visualização:';
$strDisplayPDF = 'Mostrar o esquema de PDF';
$strDoAQuery = 'Faça uma "pesquisa por formulário" (caractere universal: "%")';
$strDocu = 'Documentação';
$strDoYouReally = 'Confirma : ';
$strDrop = 'Elimina';
$strDropDB = 'Elimina a base de dados %s';
$strDropTable = 'Elimina tabela';
$strDumpingData = 'Extraindo dados da tabela';
$strDumpXRows = 'Exporta %s registos começando em %s.';
$strDynamic = 'dinâmico';

$strEdit = 'Edita';
$strEditPDFPages = 'Editar páginas PDF';
$strEditPrivileges = 'Alterar Privilegios';
$strEffective = 'Em uso';
$strEmpty = 'Limpa';
$strEmptyResultSet = 'MySQL não retornou nenhum registo.';
$strEnabled = 'Activado';
$strEnd = 'Fim';
$strEnglishPrivileges = ' Nota: os nomes dos privilégios do MySQL são em Inglês ';
$strError = 'Erro';
$strExport = 'Exportar';
$strExportToXML = 'Exportar para o formato XML';
$strExtendedInserts = 'Instrucções de inserção múltiplas';
$strExtra = 'Extra'; // written the same in portuguese

$strField = 'Campo';
$strFieldHasBeenDropped = 'O campo %s foi eliminado';
$strFields = 'Qtd Campos';
$strFieldsEmpty = ' Número de campos inválido! ';
$strFieldsEnclosedBy = 'Campos delimitados por';
$strFieldsEscapedBy = 'Campos precedidos por';
$strFieldsTerminatedBy = 'Campos terminados por';
$strFixed = 'fixo';
$strFlushTable = 'Fecha a tabela ("FLUSH")';
$strFormat = 'Formato';
$strFormEmpty = 'Nº de dados insuficiente!\nPreencha todas as opções!';
$strFullText = 'Texto inteiro';
$strFunction = 'Funções';

$strGenBy = 'Gerado por';
$strGeneralRelationFeat = 'Características gerais de Relação';
$strGenTime = 'Data de Criação';
$strGo = 'Executa';
$strGrants = 'Autorizações';
$strGzip = '"Compressão gzip"';

$strHasBeenAltered = 'foi alterado(a).';
$strHasBeenCreated = 'foi criado(a).';
$strHaveToShow = 'Tem que escolher pelo menos uma coluna para mostrar';
$strHome = 'Início';
$strHomepageOfficial = 'Página Oficial do phpMyAdmin';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin - Página de Download';
$strHost = 'Máquina';
$strHostEmpty = 'O nome da máquina está vazio!';

$strIdxFulltext = 'Texto Completo';
$strIfYouWish = 'Para carregar apenas algumas colunas da tabela, faça uma lista separada por virgula.';
$strIgnore = 'Ignora';
$strIndex = 'Índice';
$strIndexes = 'Índices';
$strIndexHasBeenDropped = 'O Índice %s foi eliminado';
$strIndexName = 'Nome do Índice&nbsp;:';
$strIndexType = 'Tipo de Índice&nbsp;:';
$strInsert = 'Insere';
$strInsertAsNewRow = 'Insere como novo registo';
$strInsertedRows = 'Registos inseridos :';
$strInsertNewRow = 'Insere novo registo';
$strInsertTextfiles = 'Insere arquivo texto na tabela';
$strInstructions = 'Instruções';
$strInUse = 'em uso';
$strInvalidName = '"%s" é uma palavra reservada, não pode usar como nome de base de dados/tabela/campo.';

$strKeepPass = 'Sem alterar senha';
$strKeyname = 'Nome do Índice';
$strKill = 'Termina';

$strLength = 'Comprimento';
$strLengthSet = 'Tamanho/Valores*';
$strLimitNumRows = 'Número de registos por página';
$strLineFeed = 'Mudança de linha: \\n';
$strLines = 'Linhas';
$strLinesTerminatedBy = 'Linhas terminadas por';
$strLinkNotFound = 'Link não encontrado';
$strLinksTo = 'Links para';
$strLocationTextfile = 'Localização do arquivo de texto';
$strLogin = 'Entrada';
$strLogout = 'Sair';
$strLogPassword = 'Senha&nbsp;:';
$strLogUsername = 'Utilizador&nbsp;:';

$strMissingBracket = 'Falta de parêntesis recto';
$strModifications = 'Modificações foram guardadas';
$strModify = 'Modifica';
$strModifyIndexTopic = 'Modificar um índice';
$strMoveTable = 'Move tabela para (base de dados<b>.</b>tabela):';
$strMoveTableOK = 'A tabela %s foi movida para %s.';
$strMySQLCharset = 'Mapa de Caracteres do mySQL';
$strMySQLReloaded = 'MySQL reiniciado.';
$strMySQLSaid = 'Mensagens do MySQL : ';
$strMySQLServerProcess = 'MySQL %pma_s1% a correr em %pma_s2% como %pma_s3%';
$strMySQLShowProcess = 'Mostra os Processos';
$strMySQLShowStatus = 'Mostra informação do estado do MySQL';
$strMySQLShowVars = 'Mostra as variáveis de sistema do MySQL';

$strName = 'Nome';
$strNext = 'Próximo';
$strNo = 'Não';
$strNoDatabases = 'Sem bases de dados';
$strNoDescription = 'sem Descrição';
$strNoDropDatabases = 'Os comandos "DROP DATABASE" estão inibidos.';
$strNoFrames = 'O phpMyAdmin torna-se mais agradável se usado num browser que suporte <b>frames</b>.';
$strNoIndex = 'Nenhum indíce definido!';
$strNoIndexPartsDefined = 'Nenhuma parte do índice definida!';
$strNoModification = 'Sem alterações';
$strNone = 'Nenhum';
$strNoPassword = 'Sem Senha';
$strNoPhp = 'sem código PHP';
$strNoPrivileges = 'Sem Privilégios';
$strNoQuery = 'Nenhum comando SQL encontrado!';
$strNoRights = 'Não tem permissões suficientes para aceder aqui, neste momento!';
$strNoTablesFound = 'Nenhuma tabela encontrada na base de dados';
$strNotNumber = 'Isto não é um número!';
$strNotOK = 'não está OK';
$strNotSet = 'A Tabela <b>%s</b> não foi encontrada ou não foi definida em %s';
$strNotValidNumber = ' não é um número de registo válido!';
$strNoUsersFound = 'Nenhum utilizador encontrado.';
$strNull = 'Nulo';
$strNumSearchResultsInTable = '%s resultado(s) na tabela <i>%s</i>';
$strNumSearchResultsTotal = '<b>Total:</b> <i>%s</i> resultado(s)';

$strOftenQuotation = 'Normalmente aspas. OPTIONALLY significa que apenas os campos "char" e "varchar" são delimitados pelo caractere delimitador.';
$strOK = 'OK';  //Same in portuguese
$strOperations = 'Operações';
$strOptimizeTable = 'Optimizar tabela';
$strOptionalControls = 'Opcional. Comanda o modo de escrita e leitura dos caracteres especiais.';
$strOptionally = 'OPCIONAL';
$strOptions = 'Opções';
$strOr = 'Ou';
$strOverhead = 'Suspenso';

$strPageNumber = 'Página número:';
$strPartialText = 'Texto parcial';
$strPassword = 'Senha';
$strPasswordEmpty = 'Indique a Senha!';
$strPasswordNotSame = 'As senhas são diferentes!\nLembre-se de confirmar a senha!';
$strPdfDbSchema = 'Esquema da base de dados "%s" - Página %s';
$strPdfInvalidPageNum = 'Numero da página do PDF indefinido!';
$strPdfInvalidTblName = 'A tabela "%s" não existe!';
$strPdfNoTables = 'Sem tablelas';
$strPhp = 'Criar código PHP';
$strPHPVersion = 'versão do PHP';
$strPmaDocumentation = 'Documentação do phpMyAdmin';
$strPmaUriError = 'A directiva <tt>$cfg[\'PmaAbsoluteUri\']</tt> TEM que ser definida no ficheiro de configuração!';
$strPos1 = 'Inicio';
$strPrevious = 'Anterior';
$strPrimary = 'Primária';
$strPrimaryKey = 'Chave Primária';
$strPrimaryKeyHasBeenDropped = 'A chave primária foi eliminada';
$strPrimaryKeyName = 'O nome da chave primária tem de ser... PRIMARY!';
$strPrimaryKeyWarning = '("PRIMARY" <b>tem</b> de ser o nome de e <b>apenas de</b> uma chave primária!)';
$strPrintView = 'Vista de impressão';
$strPrivileges = 'Privilégios';
$strProperties = 'Propriedades';

$strQBE = 'Pesquisa por formulário';
$strQBEDel = 'Elim.';
$strQBEIns = 'Ins.';
$strQueryOnDb = 'Comando SQL na base de dados <b>%s</b>:';

$strRecords = 'Registos';
$strReferentialIntegrity = 'Verificar Integridade referencial:';
$strRelationNotWorking = 'As Características adicionais para trabalhar com ligações entre Tabelas foram desactivadas. Para saber porquê carregue %saqui%s.';
$strRelationView = 'Vista de Relação';
$strReloadFailed = 'Reiniciação do MySQL falhou.';
$strReloadMySQL = 'Reiniciar o MySQL';
$strRememberReload = 'Lembre-se de reiniciar o servidor.';
$strRenameTable = 'Renomeia a tabela para ';
$strRenameTableOK = 'Tabela %s renomeada para %s';
$strRepairTable = 'Reparar tabela';
$strReplace = 'Substituir';
$strReplaceTable = 'Substituir os dados da tabela pelos do arquivo';
$strReset = 'Limpa';
$strReType = 'Confirma';
$strRevoke = 'Anula';
$strRevokeGrant = 'Anula Autorização';
$strRevokeGrantMessage = 'Anulou a autorização para %s';
$strRevokeMessage = 'Anulou os privilégios para %s';
$strRevokePriv = 'Anula Privilégios';
$strRowLength = 'Comprim. dos reg.';
$strRows = 'Registos';
$strRowsFrom = 'registos começando em';
$strRowSize = ' Tamanho dos reg.';
$strRowsModeHorizontal = 'horizontal';  // written the same in portuguese!
$strRowsModeOptions = 'em modo %s com cabeçalhos repetidos a cada %s células';
$strRowsModeVertical = 'vertical';  // written the same in portuguese!
$strRowsStatistic = 'Estatísticas dos registos';
$strRunning = 'a correr em %s';
$strRunQuery = 'Executa Comando SQL';
$strRunSQLQuery = 'Executa comando(s) SQL na base de dados %s';

$strSave = 'Guarda';
$strScaleFactorSmall = 'O factor escala é muito pequeno para encaixar o esquema numa página';
$strSearch = 'Pesquisar';
$strSearchFormTitle = 'Pesquisar na Base de Dados';
$strSearchInTables = 'Dentro de Tabela(s):';
$strSearchNeedle = 'Palavra(s) ou valor(es) para pesquisar para (wildcard: "%"):';
$strSearchOption1 = 'pelo menos uma das palavras';
$strSearchOption2 = 'todas as palavras';
$strSearchOption3 = 'a frase exacta';
$strSearchOption4 = 'as regular expression';
$strSearchResultsFor = 'Procurar resultados para "<i>%s</i>" %s:';
$strSearchType = 'Procurar:';
$strSelect = 'Selecciona';
$strSelectADb = 'Por favor seleccione uma base de dados';
$strSelectAll = 'Selecciona Todas';
$strSelectFields = 'Seleccione os campos (no mínimo 1)';
$strSelectNumRows = 'na pesquisa';
$strSelectTables = 'Seleccionar Tabelas';
$strSend = 'envia';
$strServerChoice = 'Escolha do Servidor';
$strServerVersion = 'Versão do servidor';
$strSetEnumVal = 'Se o tipo de campo é "enum" ou "set", por favor introduza os valores no seguinte formato: \'a\',\'b\',\'c\'...<br />Se precisar de colocar uma barra invertida ("\") ou um apóstrofe ("\'") entre esses valores, coloque uma barra invertida antes (por exemplo \'\\\\xyz\' ou \'a\\\'b\').';
$strShow = 'Mostra';
$strShowAll = 'Mostrar tudo';
$strShowColor = 'Mostrar côr';
$strShowCols = 'Mostra Colunas';
$strShowGrid = 'Mostrar grelha';
$strShowingRecords = 'Mostrando registos ';
$strShowPHPInfo = 'Mostra informação do PHP';
$strShowTableDimension = 'Mostrar dimensão das tabelas';
$strShowTables = 'Mostra tabelas';
$strShowThisQuery = ' Mostrar de novo aqui este comando ';
$strSingly = '(A refazer após inserir/eliminar)';
$strSize = 'Tamanho';
$strSort = 'Ordenação';
$strSpaceUsage = 'Espaço ocupado';
$strSplitWordsWithSpace = 'As palavras são separadas pelo caracter espaço (" ").';
$strSQL = 'SQL';
$strSQLQuery = 'Comando SQL';
$strSQLResult = 'Resultado SQL';
$strStatement = 'Itens';
$strStrucCSV = 'Dados CSV';
$strStrucData = 'Estrutura e dados';
$strStrucDrop = 'Adiciona \'drop table\'';
$strStrucExcelCSV = 'dados CSV para Ms Excel';
$strStrucOnly = 'Somente estrutura';
$strStructPropose = 'Propor uma estrutura de tabela';
$strStructure = 'Estrutura';
$strSubmit = 'Submete';
$strSuccess = 'O seu comando SQL foi executado com sucesso';
$strSum = 'Soma';

$strTable = 'tabela ';
$strTableComments = 'Comentários da tabela';
$strTableEmpty = 'O nome da tabela está vazio!';
$strTableHasBeenDropped = 'A tabela %s foi eliminada';
$strTableHasBeenEmptied = 'A tabela %s foi limpa';
$strTableHasBeenFlushed = 'A tabela %s foi fechada';
$strTableMaintenance = 'Manutenção da tabela';
$strTables = '%s tabela(s)';
$strTableStructure = 'Estrutura da tabela';
$strTableType = 'Tipo de tabela';
$strTextAreaLength = ' Devido ao seu tamanho,<br /> este campo pode não ser editável ';
$strTheContent = 'O conteúdo do seu arquivo foi inserido';
$strTheContents = 'O conteúdo do arquivo substituiu o conteúdo da tabela que tinha a mesma chave primária ou única';
$strTheTerminator = 'Terminador de campos.';
$strTotal = 'total';
$strType = 'Tipo';

$strUncheckAll = 'Nenhum';
$strUnique = 'Único';
$strUnselectAll = 'Limpa Todas as Selecções';
$strUpdatePrivMessage = 'Actualizou os privilégios de %s.';
$strUpdateProfile = 'Actualiza o prefil:';
$strUpdateProfileMessage = 'O prefil foi actualizado.';
$strUpdateQuery = 'Actualiza Comando SQL';
$strUsage = 'Utilização';
$strUseBackquotes = 'Usar apóstrofes com os nomes das tabelas e campos';
$strUser = 'Utilizador';
$strUserEmpty = 'O nome do utilizador está vazio!';
$strUserName = 'Nome do Utilizador';
$strUsers = 'Utilizadores';
$strUseTables = 'Usar Tabelas';

$strValue = 'Valor';
$strViewDump = 'Ver o esquema da tabela';
$strViewDumpDB = 'Ver o esquema da base de dados';

$strWelcome = 'Bemvindo ao %s';
$strWithChecked = 'Com os seleccionados:';
$strWrongUser = 'Utilizador ou Senha errada. Acesso Negado.';

$strYes = 'Sim';

$strZip = '"Compressão zip"';

$strBeginCut = 'BEGIN CUT';  //to translate
$strBeginRaw = 'BEGIN RAW';  //to translate

$strCharsetOfFile = 'Character set of the file:'; //to translate

$strEndCut = 'END CUT';  //to translate
$strEndRaw = 'END RAW';  //to translate
$strExplain = 'Explain SQL';  //to translate

$strNoExplain = 'Skip Explain SQL';  //to translate
$strNoValidateSQL = 'Skip Validate SQL';  //to translate

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
?>
