<?php
/* $Id$ */

/**
 * Portuguese language file by
 *   António Raposo <Antonio.Raposo at CFMsoft.JazzNet.pt>
 *   Lopo Pizarro <lopopizarro@users.sourceforge.net>
 */

$charset = 'iso-8859-1';
$text_dir = 'ltr';
$left_font_family = 'verdana, arial, helvetica, geneva, sans-serif';
$right_font_family = 'arial, helvetica, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab');
$month = array('Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%d-%B-%Y às %H:%M';


$strAPrimaryKey = 'Uma chave primária foi adicionada a %s';
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
$strAlterOrderBy = 'Alterar a ordem da tabela por';
$strAnIndex = 'Um índice foi adicionado a %s';
$strAnalyzeTable = 'Analizar tabela';
$strAnd = 'E';
$strAny = 'Todos';
$strAnyColumn = 'Qualquer coluna';
$strAnyDatabase = 'Qualquer base de dados';
$strAnyHost = 'Qualquer máquina';
$strAnyTable = 'Qualquer tabela';
$strAnyUser = 'Qualquer utilizador';
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
$strCantRenameIdxToPrimary = 'Impossível renomear índice para PRIMARY!';
$strCardinality = 'Quantidade';
$strCarriage = 'Fim de linha: \\r';
$strChange = 'Muda';
$strChangePassword = 'Alterar a senha';
$strCheckAll = 'Todos';
$strCheckDbPriv = 'Visualiza os Privilégios da Base de Dados';
$strCheckTable = 'Verificar tabela';
$strColumn = 'Campo';
$strColumnNames = 'Nome dos Campos';
$strCompleteInserts = 'Instrucções de inserção completas';
$strConfirm = 'Confirma a sua opção?';
$strCookiesRequired = 'O mecanismo de "Cookies" tem de estar ligado a partir deste ponto.';
$strCopyTable = 'Copia tabela para (base-de-dados<b>.</b>tabela):';
$strCopyTableOK = 'Tabela %s copiada para %s.';
$strCreate = 'Criar';
$strCreateIndex = 'Criar um índice com&nbsp;%s&nbsp;coluna(s)';
$strCreateIndexTopic = 'Criar um novo índice';
$strCreateNewDatabase = 'Criar nova base de dados';
$strCreateNewTable = 'Criar nova tabela na base de dados %s';
$strCriteria = 'Critérios';

$strData = 'Dados';
$strDataOnly = 'Apenas dados';
$strDatabase = 'Base de Dados ';
$strDatabaseHasBeenDropped = 'A base de dados %s foi eliminada.';
$strDatabases = 'Base de Dados';
$strDatabasesStats = 'Estatísticas das bases de dados';
$strDatabaseWildcard = 'Base de Dados (aceita caracteres universais):';
$strDefault = 'Defeito';
$strDelete = 'Apagar';
$strDeleteFailed = 'Erro ao apagar!';
$strDeleteUserMessage = 'Apagou o utilizador %s.';
$strDeleted = 'Registo eliminado';
$strDeletedRows = 'Linhas apagadas:';
$strDescending = 'Descendente';
$strDisplay = 'Mostra';
$strDisplayOrder = 'Ordem de visualização:';
$strDoAQuery = 'Faça uma "pesquisa por formulário" (caractere universal: "%")';
$strDoYouReally = 'Confirma : ';
$strDocu = 'Documentação';
$strDrop = 'Elimina';
$strDropDB = 'Elimina a base de dados %s';
$strDropTable = 'Elimina tabela';
$strDumpingData = 'Extraindo dados da tabela';
$strDynamic = 'dinâmico';

$strEdit = 'Edita';
$strEditPrivileges = 'Alterar Privilegios';
$strEffective = 'Em uso';
$strEmpty = 'Limpa';
$strEmptyResultSet = 'MySQL não retornou nenhum registo.';
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
$strFormEmpty = 'Nº de dados insuficiente!\nPreencha todas as opções!';
$strFormat = 'Formato';
$strFullText = 'Texto inteiro';
$strFunction = 'Funções';

$strGenTime = 'Data de Criação';
$strGo = 'Executa';
$strGrants = 'Autorizações';
$strGzip = '"Compressão gzip"';

$strHasBeenAltered = 'foi alterado(a).';
$strHasBeenCreated = 'foi criado(a).';
$strHome = 'Início';
$strHomepageOfficial = 'Página Oficial do phpMyAdmin';
$strHomepageSourceforge = 'Sourceforge phpMyAdmin - Página de Download';
$strHost = 'Máquina';
$strHostEmpty = 'O nome da máquina está vazio!';

$strIdxFulltext = 'Texto Completo';
$strIfYouWish = 'Para carregar apenas algumas colunas da tabela, faça uma lista separada por virgula.';
$strIgnore = 'Ignora';
$strInUse = 'em uso';
$strIndex = 'Índice';
$strIndexHasBeenDropped = 'O Índice %s foi eliminado';
$strIndexName = 'Nome do Índice&nbsp;:';
$strIndexType = 'Tipo de Índice&nbsp;:';
$strIndexes = 'Índices';
$strInsert = 'Insere';
$strInsertAsNewRow = 'Insere como novo registo';
$strInsertedRows = 'Registos inseridos :';
$strInsertNewRow = 'Insere novo registo';
$strInsertTextfiles = 'Insere arquivo texto na tabela';
$strInstructions = 'Instruções';
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
$strLinksTo = 'Links para';
$strLocationTextfile = 'Localização do arquivo de texto';
$strLogPassword = 'Senha&nbsp;:';
$strLogUsername = 'Utilizador&nbsp;:';
$strLogin = 'Entrada';
$strLogout = 'Sair';

$strModifications = 'Modificações foram guardadas';
$strModify = 'Modifica';
$strModifyIndexTopic = 'Modificar um índice';
$strMoveTable = 'Move tabela para (base de dados<b>.</b>tabela):';
$strMoveTableOK = 'A tabela %s foi movida para %s.';
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
$strNoDropDatabases = 'Os comandos "DROP DATABASE" estão inibidos.';
$strNoFrames = 'O phpMyAdmin torna-se mais agradável se usado num browser que suporte <b>frames</b>.';
$strNoIndex = 'Nenhum indíce definido!';
$strNoIndexPartsDefined = 'Nenhuma parte do índice definida!';
$strNoModification = 'Sem alterações';
$strNoPassword = 'Sem Senha';
$strNoPrivileges = 'Sem Privilégios';
$strNoQuery = 'Nenhum comando SQL encontrado!';
$strNoRights = 'Não tem permissões suficientes para aceder aqui, neste momento!';
$strNoTablesFound = 'Nenhuma tabela encontrada na base de dados';
$strNoUsersFound = 'Nenhum utilizador encontrado.';
$strNone = 'Nenhum';
$strNotNumber = 'Isto não é um número!';
$strNotValidNumber = ' não é um número de registo válido!';
$strNull = 'Nulo';

$strOftenQuotation = 'Normalmente aspas. OPTIONALLY significa que apenas os campos "char" e "varchar" são delimitados pelo caractere delimitador.';
$strOperations = 'Operações';
$strOptimizeTable = 'Optimizar tabela';
$strOptionalControls = 'Opcional. Comanda o modo de escrita e leitura dos caracteres especiais.';
$strOptionally = 'OPCIONAL';
$strOr = 'Ou';
$strOverhead = 'Suspenso';

$strPHPVersion = 'versão do PHP';
$strPartialText = 'Texto parcial';
$strPassword = 'Senha';
$strPasswordEmpty = 'Indique a Senha!';
$strPasswordNotSame = 'As senhas são diferentes!\nLembre-se de confirmar a senha!';
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

$strReType = 'Confirma';
$strRecords = 'Registos';
$strReferentialIntegrity = 'Verificar Integridade referencial:';
$strReloadFailed = 'Reiniciação do MySQL falhou.';
$strReloadMySQL = 'Reiniciar o MySQL';
$strRememberReload = 'Lembre-se de reiniciar o servidor.';
$strRenameTable = 'Renomeia a tabela para ';
$strRenameTableOK = 'Tabela %s renomeada para %s';
$strRepairTable = 'Reparar tabela';
$strReplace = 'Substituir';
$strReplaceTable = 'Substituir os dados da tabela pelos do arquivo';
$strReset = 'Limpa';
$strRevoke = 'Anula';
$strRevokeGrant = 'Anula Autorização';
$strRevokeGrantMessage = 'Anulou a autorização para %s';
$strRevokeMessage = 'Anulou os privilégios para %s';
$strRevokePriv = 'Anula Privilégios';
$strRowLength = 'Comprim. dos reg.';
$strRowSize = ' Tamanho dos reg.';
$strRows = 'Registos';
$strRowsFrom = 'registos começando em';
$strRowsModeHorizontal = 'horizontal';  // written the same in portuguese!
$strRowsModeOptions = 'em modo %s com cabeçalhos repetidos a cada %s células';
$strRowsModeVertical = 'vertical';  // written the same in portuguese!
$strRowsStatistic = 'Estatísticas dos registos';
$strRunQuery = 'Executa Comando SQL';
$strRunSQLQuery = 'Executa comando(s) SQL na base de dados %s';
$strRunning = 'a correr em %s';

$strSQLQuery = 'Comando SQL';
$strSave = 'Guarda';
$strSelect = 'Selecciona';
$strSelectADb = 'Por favor seleccione uma base de dados';
$strSelectAll = 'Selecciona Todas';
$strSelectFields = 'Seleccione os campos (no mínimo 1)';
$strSelectNumRows = 'na pesquisa';
$strSend = 'envia';
$strServerChoice = 'Escolha do Servidor';
$strServerVersion = 'Versão do servidor';
$strSetEnumVal = 'Se o tipo de campo é "enum" ou "set", por favor introduza os valores no seguinte formato: \'a\',\'b\',\'c\'...<br />Se precisar de colocar uma barra invertida ("\") ou um apóstrofe ("\'") entre esses valores, coloque uma barra invertida antes (por exemplo \'\\\\xyz\' ou \'a\\\'b\').';
$strShow = 'Mostra';
$strShowAll = 'Mostrar tudo';
$strShowCols = 'Mostra Colunas';
$strShowPHPInfo = 'Mostra informação do PHP';
$strShowTables = 'Mostra tabelas';
$strShowThisQuery = ' Mostrar de novo aqui este comando ';
$strShowingRecords = 'Mostrando registos ';
$strSingly = '(A refazer após inserir/eliminar)';
$strSize = 'Tamanho';
$strSort = 'Ordenação';
$strSpaceUsage = 'Espaço ocupado';
$strStatement = 'Itens';
$strStrucCSV = 'Dados CSV';
$strStrucData = 'Estrutura e dados';
$strStrucDrop = 'Adiciona \'drop table\'';
$strStrucExcelCSV = 'dados CSV para Ms Excel';
$strStrucOnly = 'Somente estrutura';
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
$strTableStructure = 'Estrutura da tabela';
$strTableType = 'Tipo de tabela';
$strTables = '%s tabela(s)';
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
$strUseTables = 'Usar Tabelas';
$strUser = 'Utilizador';
$strUserEmpty = 'O nome do utilizador está vazio!';
$strUserName = 'Nome do Utilizador';
$strUsers = 'Utilizadores';

$strValue = 'Valor';
$strViewDump = 'Ver o esquema da tabela';
$strViewDumpDB = 'Ver o esquema da base de dados';

$strWelcome = 'Bemvindo ao %s';
$strWithChecked = 'Com os seleccionados:';
$strWrongUser = 'Utilizador ou Senha errada. Acesso Negado.';

$strYes = 'Sim';

$strZip = '"Compressão zip"';


// To translate
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
?>
