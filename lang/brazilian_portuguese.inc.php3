<?php
/* $Id$ */

$charset = 'iso-8859-1';
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$day_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';


$strAccessDenied = 'Acesso Negado';
$strAction = 'Ações';
$strAddDeleteColumn = 'Adiciona/Remove Colunas';
$strAddDeleteRow = 'Adiciona/Remove Condições de busca';
$strAddNewField = 'Adiciona novo campo';
$strAddPriv = 'Adiciona um novo Privilégio';
$strAddPrivMessage = 'Privilégio adicionado.';
$strAddSearchConditions = 'Condição de Pesquisa (Complemento da clausula "onde"):';
$strAddUser = 'Adicionar um novo usuário';
$strAddUserMessage = 'Usuário adcionado.';
$strAffectedRows = 'Registro afetados:';
$strAfter = 'Depois';
$strAll = 'Todos';
$strAlterOrderBy = 'Alterar tabela ordenada por';
$strAnalyzeTable = 'Analizar tabela';
$strAnd = 'E';
$strAnIndex = 'Um indice foi adicionado na ';
$strAny = 'Qualquer';
$strAnyColumn = 'Qualquer coluna';
$strAnyDatabase = 'Qualquer banco de dados';
$strAnyHost = 'Qualquer servidor';
$strAnyTable = 'Qualquer tabela';
$strAnyUser = 'Qualquer usuário';
$strAPrimaryKey = 'Chave primária acrescentada na ';
$strAscending = 'Ascendente';
$strAtBeginningOfTable = 'No começo da tabela';
$strAtEndOfTable = 'Ao fim da tabela';
$strAttr = 'Atributos';

$strBack = 'Voltar';
$strBinary = ' Binário ';
$strBinaryDoNotEdit = ' Binário - não edite ';
$strBookmarkLabel = 'Nome';
$strBookmarkQuery = 'Procura de SQL salva';
$strBookmarkThis = 'Salvar essa procura de SQL';
$strBookmarkView = 'Apenas visualiza';
$strBrowse = 'Visualiza';
$strBzip = '"bzipped"';

$strCantLoadMySQL = 'não foi possível carregar extensão do MySQL,<br />por favor cheque a configuração do PHP.';
$strCarriage = 'Caracter de retorno: \\r';
$strChange = 'Muda';
$strCheckAll = 'Check All';
$strCheckDbPriv = 'Verifica Privilégios do Banco de Dados';
$strCheckTable = 'Verifica tabela';
$strColumn = 'Coluna';
$strColumnEmpty = 'Os nomes das colunas estão vazios!';
$strColumnNames = 'Nome da Colunas';
$strCompleteInserts = 'Inserções Completas';
$strConfirm = 'Você tem certeza?';
$strCopyTable = 'Copia tabela para';
$strCopyTableOK = 'Tabela %s copiada para %s.';
$strCreate = 'Cria';
$strCreateNewDatabase = 'Cria novo banco de dados';
$strCreateNewTable = 'Cria nova tabela no banco de dados ';
$strCriteria = 'Critério';

$strData = 'Dados';
$strDatabase = 'Banco de Dados ';
$strDatabases = 'Banco de Dados';
$strDatabasesStats = 'Estatisticas da base';
$strDataOnly = 'Dados apenas';
$strDbEmpty = 'O nome do banco de dados está vazio!';
$strDefault = 'Padrão';
$strDelete = 'Remove';
$strDeleted = 'Registro eliminado';
$strDeletedRows = 'Registro deletados:';
$strDeleteFailed = 'Não foi possível apagar!';
$strDeletePassword = 'Apagar Senha';
$strDeleteUserMessage = 'Você deletou o usuário';
$strDelPassMessage = 'Você deletou a senha de';
$strDescending = 'Descendente';
$strDisableMagicQuotes = '<b>Atenção:</b> You have enabled magic_quotes_gpc in your PHP configuration. This version of PhpMyAdmin cannot work properly with it. Please have a look at the configuration section of the PHP manual for information on how to disable it.'; //to translate
$strDisplay = 'Tela';
$strDisplayOrder = 'Ordenado por:';
$strDoAQuery = 'Faça uma "procura por exemplo" (coringa: "%")';
$strDocu = 'Documentação';
$strDoYouReally = 'Confirma : ';
$strDrop = 'Elimina';
$strDropDB = 'Elimina o banco de dados: ';
$strDropTable = 'Remove Tabela';
$strDumpingData = 'Extraindo dados da tabela';
$strDynamic = 'dinâmico';

$strEdit = 'Edita';
$strEditPrivileges = 'Edita Privilégios';
$strEffective = 'Efetivo';
$strEmpty = 'Limpa';
$strEmptyResultSet = 'MySQL retornou um conjunto vazio (ex. zero registros).';
$strEnableMagicQuotes = '<b>Aviso:</b> Seu PHP não esta configurado para usar magic_quotes_gpc . PhpMyAdmin precisa disso para funcionar corretamente. Por favor verifique a seção de configuração no manual do PHP para saber como habilitar esta opção.';
$strEnclosedBy = 'delimitados por';
$strEnd = 'Fim';
$strEnglishPrivileges = ' Nota: nomes de privilégios do MySQL são expressos em inglês ';
$strError = 'Erro';
$strEscapedBy = 'contornados por';
$strExtendedInserts = 'Extended inserts';
$strExtra = 'Extra';

$strField = 'Campo';
$strFields = 'Campos';
$strFieldsEmpty = ' O campo count esta vazio! ';
$strFixed = 'fixo';
$strFormat = 'Formato';
$strFormEmpty = 'Faltando valores do form !';
$strFullText = 'Textos completos';
$strFunction = 'Funçoes';

$strGenTime = 'Tempo de Generação';
$strGo = 'Executa';
$strGrants = 'Conceder';
$strGzip = '"gzipped"';

$strHasBeenAltered = 'foi alterado.';
$strHasBeenCreated = 'foi criado.';
$strHasBeenDropped = 'foi eliminado.';
$strHasBeenEmptied = 'foi esvaziado.';
$strHome = 'Principal';
$strHomepageOfficial = 'Página Oficial do phpMyAdmin';
$strHomepageSourceforge = 'Nova Página do phpMyAdmin';
$strHost = 'Servidor';
$strHostEmpty = 'O nome do servidor está vazio!';

$strIfYouWish = 'Para carregar apenas algumas colunas da tabela, faça uma lista separada por vírgula.';
$strIndex = 'Índice';
$strIndexes = 'Índices';
$strInsert = 'Insere';
$strInsertAsNewRow = 'Insere uma nova coluna';
$strInsertedRows = 'Linhas Inseridas:';
$strInsertIntoTable = 'Insere na tabela';
$strInsertNewRow = 'Insere novo registro';
$strInsertTextfiles = 'Insere arquivo texto na tabela';
$strInstructions = 'Instruções';
$strInUse = 'em uso';

$strKeyname = 'Nome chave';
$strKill = 'Matar';

$strLength = 'Tamanho';
$strLengthSet = 'Tamanho/Definir*';
$strLimitNumRows = 'records per page';
$strLineFeed = 'Caracter de Alimentação de Linha: \\n';
$strLines = 'Linhas';
$strLocationTextfile = 'Localização do arquivo texto';
$strLogin = ''; //to translate, but its not in use ...
$strLogout = 'Sair';

$strModifications = 'Modificações foram salvas';
$strModify = 'Modificar';
$strMySQLReloaded = 'MySQL reiniciado.';
$strMySQLSaid = 'Mensagens do MySQL : ';
$strMySQLShowProcess = 'Mostra os Processos';
$strMySQLShowStatus = 'Mostra informação de runtime do MySQL';
$strMySQLShowVars = 'Mostra variáveis de sistema do MySQL';

$strName = 'Nome';
$strNbRecords = 'no. de registros';
$strNext = 'Próximo';
$strNo = 'Não';
$strNoDatabases = 'Sem bases';
$strNoDropDatabases = 'O comando "DROP DATABASE" esta desabilitado.';
$strNoModification = 'Sem Mudança';
$strNoPassword = 'Sem Senha';
$strNoPrivileges = 'Sem Privilégios';
$strNoRights = 'Você não tem direitos suficientes para estar aqui agora!';
$strNoTablesFound = 'Nenhuma tabela encontrada no banco de dados';
$strNotNumber = 'Isto não é um número!';
$strNotValidNumber = ' não é um número de registro valido!';
$strNoUsersFound = 'Nenhum usuário(s) encontrado.';
$strNull = 'Nulo';
$strNumberIndexes = ' Número de índices avançados ';

$strOftenQuotation = 'Em geral aspas. OPCIONAL significa que apenas campos de caracteres são delimitados por caracteres "delimitadores"';
$strOptimizeTable = 'Optimizar tabela';
$strOptionalControls = 'Opcional. Controla como ler e escrever caracteres especiais.';
$strOptionally = 'OPCIONAL';
$strOr = 'Ou';
$strOverhead = 'Sobre Carga'; //to translate -> How is this word used in the program?

$strPartialText = 'Textos parciais';
$strQueryOnDb = 'SQL-query na base ';
$strPassword = 'Senha';
$strPasswordEmpty = 'A senhas está vazia!';
$strPasswordNotSame = 'As senhas não são a mesma!';
$strPHPVersion = 'Versão do PHP';
$strPmaDocumentation = 'Documentação do phpMyAdmin ';
$strPos1 = 'Início';
$strPrevious = 'Anterior';
$strPrimary = 'Primária';
$strPrimaryKey = 'Chave Primária';
$strPrinterFriendly = 'Printer friendly version of above table';
$strPrintView = 'Visualização para Impressão';
$strPrivileges = 'Privilégios';
$strProducedAnError = 'produziu um erro.';
$strProperties = 'Propriedades';

$strQBE = 'Procura por Exemplo';
$strQBEDel = 'Del';  //to translate (used in tbl_qbe.php3)
$strQBEIns = 'Ins';  //to translate (used in tbl_qbe.php3)

$strReadTheDocs = 'Leia a documentação';
$strRecords = 'Registros';
$strReloadFailed = 'Reinicialização do MySQL falhou.';
$strReloadMySQL = 'Reinicializa o MySQL';
$strRememberReload = 'Lembre-se recarregar o servidor.';
$strRenameTable = 'Renomeia a tabela para ';
$strRenameTableOK = 'Tabela %s renomeada para %s';
$strRepairTable = 'Reparar tabela';
$strReplace = 'Substituir';
$strReplaceTable = 'Substituir os dados da tabela pelos do arquivo';
$strReset = 'Resetar';
$strReType = 'Re-digite';
$strRevoke = 'Revogar';
$strRevokeGrant = 'Revogar Privilégio de Conceder';
$strRevokeGrantMessage = 'Você revogou o privilégio de conceder para';
$strRevokeMessage = 'Você revogou os privilégios para';
$strRevokePriv = 'Revogar Privilégios';
$strRowLength = 'Tamanho da Coluna';
$strRows = 'Colunas';
$strRowsFrom = 'colunas começando de';
$strRowSize = ' Tamanho do registro ';
$strRowsStatistic = 'Estatistícas da Coluna';
$strRunning = 'Rodando em ';
$strRunQuery = 'Envia Query';
$strRunSQLQuery = 'Executa comando SQL no banco de dados ';

$strSave = 'Salva';
$strSelect = 'Procura';
$strSelectFields = 'Selecione os campos (no mínimo 1)';
$strSelectNumRows = 'in query';
$strSend = 'Envia';
$strSequence = 'Sequência';
$strServerChoice = 'Seleção da Base';
$strServerVersion = 'Versão do Servidor';
$strShow = 'Mostrar';
$strShowingRecords = 'Mostrando registros ';
$strShowPHPInfo = 'Mostra informações do PHP';
$strShowThisQuery = ' Mostra esta query novamente ';
$strSingly = '(singly)'; //to translate
$strSize = 'Tamanho';
$strSort = 'Ordena';
$strSpaceUsage = 'Uso do espaço';
$strSQLQuery = 'comando SQL';
$strStatement = 'Comandos';
$strStrucCSV = 'Dados CSV';
$strStrucData = 'Estrutura e dados';
$strStrucDrop = 'Adiciona \'Sobrescrever\'';
$strStrucExcelCSV = 'CSV para dados Ms Excel';
$strStrucOnly = 'Somente estrutura';
$strSubmit = 'Submete';
$strSuccess = 'Seu comando SQL foi executado com sucesso';
$strSum = 'Soma';

$strTable = 'tabela ';
$strTableComments = 'Comentários da tabela';
$strTableEmpty = 'O Nome da Tabela está vazio!';
$strTableMaintenance = 'Tabela de Manutenção';
$strTables = '%s tabela(s)';
$strTableStructure = 'Estrutura da tabela';
$strTableType = 'Tipo da Tabela';
$strTerminatedBy = 'terminados por';
$strTextAreaLength = ' Por causa da sua largura,<br /> esse campo pode não ser editável ';
$strTheContent = 'O conteúdo do seu arquivo foi inserido';
$strTheContents = 'O conteúdo do arquivo substituiu o conteúdo da tabela que tinha a mesma chave primária ou única';
$strTheTerminator = 'Terminador de campos.';
$strTotal = 'total';
$strType = 'Tipo';

$strUncheckAll = 'Desmarca Todos';
$strUnique = 'Único';
$strUpdatePassMessage = 'Você mudou a senha para';
$strUpdatePassword = 'Mudar Senha';
$strUpdatePrivMessage = 'Você mudou os priviléios para';
$strUpdateQuery = 'Atualiza Query';
$strUsage = 'Uso';
$strUseBackquotes = 'Usa aspas simples nos nomes de tabelas e campos';
$strUser = 'Usuário';
$strUserEmpty = 'O nome do usuário está vazio!';
$strUserName = 'Nome do usuário';
$strUsers = 'Usuários';
$strUseTables = 'Usar Tabelas';

$strValue = 'Valor';
$strViewDump = 'Ver o esquema da tabela';
$strViewDumpDB = 'Ver o esquema do banco de dados';

$strWelcome = 'Bem vindo ao ';
$strWrongUser = 'Usuário ou Senha errado. Acesso Negado.';

$strYes = 'Sim';

// To translate
$strIdxFulltext = 'Fulltext';  //to translate 
$strInvalidName = '"%s" is a reserved word, you can\'t use it as a database/table/field name.'; //to translate
$strOffSet = 'offset'; //to translate
$strSetEnumVal = 'If field type is "enum" or "set", please enter the values using this format: \'a\',\'b\',\'c\'...<br />If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, backslashes it (for example \'\\\\xyz\' or \'a\\\'b\').'; //to translate
$strShowAll = 'Show all'; // to translate
$strShowCols = 'Show columns';
$strShowTables = 'Show tables';
$strWithChecked = 'With checked:'; //to translate
?>
