<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\TypeClass;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function array_merge;
use function count;
use function explode;
use function htmlentities;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function max;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function stripos;

use const ENT_QUOTES;

/**
 * Functions for routine management.
 */
class Routines
{
    /** @var array<int, string> */
    public readonly array $directions;

    /** @var array<int, string> */
    public readonly array $sqlDataAccess;

    /** @var array<int, string> */
    public readonly array $numericOptions;

    /** @var array<string> */
    private array $errors = [];

    public function __construct(private DatabaseInterface $dbi)
    {
        $this->directions = ['IN', 'OUT', 'INOUT'];
        $this->sqlDataAccess = ['CONTAINS SQL', 'NO SQL', 'READS SQL DATA', 'MODIFIES SQL DATA'];
        $this->numericOptions = ['UNSIGNED', 'ZEROFILL', 'UNSIGNED ZEROFILL'];
    }

    /**
     * Handle request to create or edit a routine
     */
    public function handleRequestCreateOrEdit(
        UserPrivileges $userPrivileges,
        string $db,
        ServerRequest $request,
    ): string {
        $sqlQuery = '';
        $routineQuery = $this->getQueryFromRequest($request);

        // set by getQueryFromRequest()
        if ($this->errors === []) {
            // Execute the created query
            if (! empty($_POST['editor_process_edit'])) {
                if (! in_array($_POST['item_original_type'], ['PROCEDURE', 'FUNCTION'], true)) {
                    $this->errors[] = sprintf(
                        __('Invalid routine type: "%s"'),
                        htmlspecialchars($_POST['item_original_type']),
                    );
                } else {
                    // Backup the old routine, in case something goes wrong
                    if ($_POST['item_original_type'] === 'FUNCTION') {
                        $createRoutine = self::getFunctionDefinition($this->dbi, $db, $_POST['item_original_name']);
                    } else {
                        $createRoutine = self::getProcedureDefinition($this->dbi, $db, $_POST['item_original_name']);
                    }

                    $privilegesBackup = $this->backupPrivileges($userPrivileges);

                    $dropRoutine = 'DROP ' . $_POST['item_original_type'] . ' '
                        . Util::backquote($_POST['item_original_name'])
                        . ";\n";
                    $result = $this->dbi->tryQuery($dropRoutine);
                    if (! $result) {
                        $this->errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($dropRoutine),
                        )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        [$newErrors, Current::$message] = $this->create(
                            $userPrivileges,
                            $routineQuery,
                            $createRoutine,
                            $privilegesBackup,
                        );
                        if ($newErrors === []) {
                            $sqlQuery = $dropRoutine . $routineQuery;
                        } else {
                            $this->errors = array_merge($this->errors, $newErrors);
                        }

                        unset($newErrors);
                    }
                }
            } else {
                // 'Add a new routine' mode
                $result = $this->dbi->tryQuery($routineQuery);
                if (! $result) {
                    $this->errors[] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($routineQuery),
                    )
                    . '<br><br>'
                    . __('MySQL said: ') . $this->dbi->getError();
                } else {
                    Current::$message = Message::success(
                        __('Routine %1$s has been created.'),
                    );
                    Current::$message->addParam(
                        Util::backquote($_POST['item_name']),
                    );
                    $sqlQuery = $routineQuery;
                }
            }
        }

        if ($this->errors !== []) {
            Current::$message = Message::error(
                __(
                    'One or more errors have occurred while processing your request:',
                ),
            );
            Current::$message->addHtml('<ul>');
            foreach ($this->errors as $string) {
                Current::$message->addHtml('<li>' . $string . '</li>');
            }

            Current::$message->addHtml('</ul>');
        }

        return Generator::getMessage(Current::$message ?? Message::success(), $sqlQuery);
    }

    /**
     * Backup the privileges
     *
     * @return string[][]
     */
    public function backupPrivileges(UserPrivileges $userPrivileges): array
    {
        if (! $userPrivileges->routines || ! $userPrivileges->isReload) {
            return [];
        }

        // Backup the Old Privileges before dropping
        // if $_POST['item_adjust_privileges'] set
        if (empty($_POST['item_adjust_privileges'])) {
            return [];
        }

        $privilegesBackupQuery = 'SELECT * FROM ' . Util::backquote('mysql')
        . '.' . Util::backquote('procs_priv')
        . ' WHERE Routine_name = ' . $this->dbi->quoteString($_POST['item_original_name'])
        . ' AND Routine_type = ' . $this->dbi->quoteString($_POST['item_original_type']);

        return $this->dbi->fetchResult($privilegesBackupQuery, 0);
    }

    /**
     * Create the routine
     *
     * @param string     $routineQuery     Query to create routine
     * @param string     $createRoutine    Query to restore routine
     * @param string[][] $privilegesBackup Privileges backup
     *
     * @return array{string[], Message|null}
     */
    public function create(
        UserPrivileges $userPrivileges,
        string $routineQuery,
        string $createRoutine,
        array $privilegesBackup,
    ): array {
        $result = $this->dbi->tryQuery($routineQuery);
        if (! $result) {
            $errors = [];
            $errors[] = sprintf(
                __('The following query has failed: "%s"'),
                htmlspecialchars($routineQuery),
            )
            . '<br>'
            . __('MySQL said: ') . $this->dbi->getError();
            // We dropped the old routine,
            // but were unable to create the new one
            // Try to restore the backup query
            $result = $this->dbi->tryQuery($createRoutine);
            if (! $result) {
                // OMG, this is really bad! We dropped the query,
                // failed to create a new one
                // and now even the backup query does not execute!
                // This should not happen, but we better handle
                // this just in case.
                $errors[] = __('Sorry, we failed to restore the dropped routine.') . '<br>'
                . __('The backed up query was:')
                . '"' . htmlspecialchars($createRoutine) . '"<br>'
                . __('MySQL said: ') . $this->dbi->getError();
            }

            return [$errors, null];
        }

        // Default value
        $resultAdjust = false;

        if ($userPrivileges->routines && $userPrivileges->isReload) {
            // Insert all the previous privileges
            // but with the new name and the new type
            foreach ($privilegesBackup as $priv) {
                $adjustProcPrivilege = 'INSERT INTO '
                    . Util::backquote('mysql') . '.'
                    . Util::backquote('procs_priv')
                    . ' VALUES(' . $this->dbi->quoteString($priv[0]) . ', '
                    . $this->dbi->quoteString($priv[1]) . ', ' . $this->dbi->quoteString($priv[2]) . ', '
                    . $this->dbi->quoteString($_POST['item_name']) . ', '
                    . $this->dbi->quoteString($_POST['item_type']) . ', '
                    . $this->dbi->quoteString($priv[5]) . ', '
                    . $this->dbi->quoteString($priv[6]) . ', '
                    . $this->dbi->quoteString($priv[7]) . ');';
                $this->dbi->query($adjustProcPrivilege);
                $resultAdjust = true;
            }
        }

        $message = $this->flushPrivileges($resultAdjust);

        return [[], $message];
    }

    /**
     * Flush privileges and get message
     *
     * @param bool $flushPrivileges Flush privileges
     */
    public function flushPrivileges(bool $flushPrivileges): Message
    {
        if ($flushPrivileges) {
            // Flush the Privileges
            $this->dbi->tryQuery('FLUSH PRIVILEGES;');

            $message = Message::success(
                __(
                    'Routine %1$s has been modified. Privileges have been adjusted.',
                ),
            );
        } else {
            $message = Message::success(
                __('Routine %1$s has been modified.'),
            );
        }

        $message->addParam(
            Util::backquote($_POST['item_name']),
        );

        return $message;
    }

    /**
     * This function will generate the values that are required to
     * complete the editor form. It is especially necessary to handle
     * the 'Add another parameter', 'Remove last parameter' and
     * 'Change routine type' functionalities when JS is disabled.
     *
     * @return mixed[]    Data necessary to create the routine editor.
     */
    public function getDataFromRequest(): array
    {
        $retval = [];
        $indices = [
            'item_name',
            'item_original_name',
            'item_returnlength',
            'item_returnopts_num',
            'item_returnopts_text',
            'item_definition',
            'item_comment',
            'item_definer',
        ];
        foreach ($indices as $index) {
            $retval[$index] = $_POST[$index] ?? '';
        }

        $retval['item_type'] = 'PROCEDURE';
        $retval['item_type_toggle'] = 'FUNCTION';
        if (isset($_POST['item_type']) && $_POST['item_type'] === 'FUNCTION') {
            $retval['item_type'] = 'FUNCTION';
            $retval['item_type_toggle'] = 'PROCEDURE';
        }

        $retval['item_original_type'] = 'PROCEDURE';
        if (isset($_POST['item_original_type']) && $_POST['item_original_type'] === 'FUNCTION') {
            $retval['item_original_type'] = 'FUNCTION';
        }

        $retval['item_num_params'] = 0;
        $retval['item_param_dir'] = [];
        $retval['item_param_name'] = [];
        $retval['item_param_type'] = [];
        $retval['item_param_length'] = [];
        $retval['item_param_opts_num'] = [];
        $retval['item_param_opts_text'] = [];
        if (
            isset(
                $_POST['item_param_name'],
                $_POST['item_param_type'],
                $_POST['item_param_length'],
                $_POST['item_param_opts_num'],
                $_POST['item_param_opts_text'],
            )
            && is_array($_POST['item_param_name'])
            && is_array($_POST['item_param_type'])
            && is_array($_POST['item_param_length'])
            && is_array($_POST['item_param_opts_num'])
            && is_array($_POST['item_param_opts_text'])
        ) {
            if ($_POST['item_type'] === 'PROCEDURE') {
                $retval['item_param_dir'] = $_POST['item_param_dir'];
                foreach ($retval['item_param_dir'] as $key => $value) {
                    if (in_array($value, $this->directions, true)) {
                        continue;
                    }

                    $retval['item_param_dir'][$key] = '';
                }
            }

            $retval['item_param_name'] = $_POST['item_param_name'];
            $retval['item_param_type'] = $_POST['item_param_type'];
            foreach ($retval['item_param_type'] as $key => $value) {
                if (in_array($value, Util::getSupportedDatatypes(), true)) {
                    continue;
                }

                $retval['item_param_type'][$key] = '';
            }

            $retval['item_param_length'] = $_POST['item_param_length'];
            $retval['item_param_opts_num'] = $_POST['item_param_opts_num'];
            $retval['item_param_opts_text'] = $_POST['item_param_opts_text'];
            $retval['item_num_params'] = max(
                count($retval['item_param_name']),
                count($retval['item_param_type']),
                count($retval['item_param_length']),
                count($retval['item_param_opts_num']),
                count($retval['item_param_opts_text']),
            );
        }

        $retval['item_returntype'] = '';
        if (
            isset($_POST['item_returntype'])
            && in_array($_POST['item_returntype'], Util::getSupportedDatatypes(), true)
        ) {
            $retval['item_returntype'] = $_POST['item_returntype'];
        }

        $retval['item_isdeterministic'] = '';
        if (isset($_POST['item_isdeterministic']) && mb_strtolower($_POST['item_isdeterministic']) === 'on') {
            $retval['item_isdeterministic'] = " checked='checked'";
        }

        $retval['item_securitytype_definer'] = '';
        $retval['item_securitytype_invoker'] = '';
        if (isset($_POST['item_securitytype'])) {
            if ($_POST['item_securitytype'] === 'DEFINER') {
                $retval['item_securitytype_definer'] = " selected='selected'";
            } elseif ($_POST['item_securitytype'] === 'INVOKER') {
                $retval['item_securitytype_invoker'] = " selected='selected'";
            }
        }

        $retval['item_sqldataaccess'] = '';
        if (isset($_POST['item_sqldataaccess']) && in_array($_POST['item_sqldataaccess'], $this->sqlDataAccess, true)) {
            $retval['item_sqldataaccess'] = $_POST['item_sqldataaccess'];
        }

        return $retval;
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit routine" form given the name of a routine.
     *
     * @param string $name The name of the routine.
     * @param string $type Type of routine (ROUTINE|PROCEDURE)
     * @param bool   $all  Whether to return all data or just the info about parameters.
     *
     * @return mixed[]|null    Data necessary to create the routine editor.
     */
    public function getDataFromName(string $name, string $type, bool $all = true): array|null
    {
        $retval = [];

        // Build and execute the query
        $fields = 'SPECIFIC_NAME, ROUTINE_TYPE, DTD_IDENTIFIER, '
                 . 'ROUTINE_DEFINITION, IS_DETERMINISTIC, SQL_DATA_ACCESS, '
                 . 'ROUTINE_COMMENT, SECURITY_TYPE';
        $where = 'ROUTINE_SCHEMA ' . Util::getCollateForIS() . '=' . $this->dbi->quoteString(Current::$database)
                 . ' AND SPECIFIC_NAME=' . $this->dbi->quoteString($name)
                 . ' AND ROUTINE_TYPE=' . $this->dbi->quoteString($type);
        $query = 'SELECT ' . $fields . ' FROM INFORMATION_SCHEMA.ROUTINES WHERE ' . $where . ';';

        $routine = $this->dbi->fetchSingleRow($query);

        if ($routine === []) {
            return null;
        }

        // Get required data
        $retval['item_name'] = $routine['SPECIFIC_NAME'];
        $retval['item_type'] = $routine['ROUTINE_TYPE'];

        if ($routine['ROUTINE_TYPE'] === 'FUNCTION') {
            $definition = self::getFunctionDefinition($this->dbi, Current::$database, $routine['SPECIFIC_NAME']);
        } else {
            $definition = self::getProcedureDefinition($this->dbi, Current::$database, $routine['SPECIFIC_NAME']);
        }

        if ($definition === null) {
            return null;
        }

        $parser = new Parser('DELIMITER $$' . "\n" . $definition);

        /** @var CreateStatement $stmt */
        $stmt = $parser->statements[0];

        // Do not use $routine['ROUTINE_DEFINITION'] because of a MySQL escaping issue: #15370
        $body = TokensList::buildFromArray($stmt->body);
        if ($body === '') {
            // Fallback just in case the parser fails
            $body = (string) $routine['ROUTINE_DEFINITION'];
        }

        $retval = array_merge($retval, $this->getParameters($stmt));
        $retval['item_param_opts_text'] = $retval['item_param_opts_num'];

        // Get extra data
        if (! $all) {
            return $retval;
        }

        $retval['item_type_toggle'] = $retval['item_type'] === 'FUNCTION' ? 'PROCEDURE' : 'FUNCTION';

        $retval['item_returntype'] = '';
        $retval['item_returnlength'] = '';
        $retval['item_returnopts_num'] = '';
        $retval['item_returnopts_text'] = '';

        if (! empty($routine['DTD_IDENTIFIER'])) {
            $options = [];
            foreach ($stmt->return->options->options as $opt) {
                $options[] = is_string($opt) ? $opt : $opt['value'];
            }

            $retval['item_returntype'] = $stmt->return->name;
            $retval['item_returnlength'] = implode(',', $stmt->return->parameters);
            $retval['item_returnopts_num'] = implode(' ', $options);
            $retval['item_returnopts_text'] = implode(' ', $options);
        }

        $retval['item_definer'] = $stmt->options->get('DEFINER');
        $retval['item_definition'] = $body;
        $retval['item_isdeterministic'] = '';
        if ($routine['IS_DETERMINISTIC'] === 'YES') {
            $retval['item_isdeterministic'] = " checked='checked'";
        }

        $retval['item_securitytype_definer'] = '';
        $retval['item_securitytype_invoker'] = '';
        if ($routine['SECURITY_TYPE'] === 'DEFINER') {
            $retval['item_securitytype_definer'] = " selected='selected'";
        } elseif ($routine['SECURITY_TYPE'] === 'INVOKER') {
            $retval['item_securitytype_invoker'] = " selected='selected'";
        }

        $retval['item_sqldataaccess'] = $routine['SQL_DATA_ACCESS'];
        $retval['item_comment'] = $routine['ROUTINE_COMMENT'];

        return $retval;
    }

    /**
     * Gets the parameters of a routine from the parse tree.
     *
     * @param CreateStatement $statement the statement to be processed
     *
     * @return array<string, int|array<int, mixed[]|string|null>>
     */
    private function getParameters(CreateStatement $statement): array
    {
        $retval = [
            'item_num_params' => 0,
            'item_param_dir' => [],
            'item_param_name' => [],
            'item_param_type' => [],
            'item_param_length' => [],
            'item_param_length_arr' => [],
            'item_param_opts_num' => [],
        ];

        if ($statement->parameters !== null) {
            $idx = 0;
            foreach ($statement->parameters as $param) {
                $retval['item_param_dir'][$idx] = $param->inOut;
                $retval['item_param_name'][$idx] = $param->name;
                $retval['item_param_type'][$idx] = $param->type->name;
                $retval['item_param_length'][$idx] = implode(',', $param->type->parameters);
                $retval['item_param_length_arr'][$idx] = $param->type->parameters;
                $retval['item_param_opts_num'][$idx] = [];
                foreach ($param->type->options->options as $opt) {
                    $retval['item_param_opts_num'][$idx][] = is_string($opt) ?
                        $opt : $opt['value'];
                }

                $retval['item_param_opts_num'][$idx] = implode(' ', $retval['item_param_opts_num'][$idx]);
                ++$idx;
            }

            $retval['item_num_params'] = $idx;
        }

        return $retval;
    }

    /**
     * Creates one row for the parameter table used in the routine editor.
     *
     * @param mixed[] $routine Data for the routine returned by
     *                       getDataFromRequest() or getDataFromName()
     * @param mixed   $index   Either a numeric index of the row being processed
     *                         or NULL to create a template row for AJAX request
     * @param string  $class   Class used to hide the direction column, if the
     *                         row is for a stored function.
     *
     * @return mixed[]
     */
    public function getParameterRow(array $routine = [], mixed $index = null, string $class = ''): array
    {
        if ($index === null) {
            // template row for AJAX request
            $i = 0;
            $index = '%s';
            $dropClass = '';
            $routine = [
                'item_param_dir' => [''],
                'item_param_name' => [''],
                'item_param_type' => [''],
                'item_param_length' => [''],
                'item_param_opts_num' => [''],
                'item_param_opts_text' => [''],
            ];
        } elseif ($routine !== []) {
            // regular row for routine editor
            $dropClass = ' hide';
            $i = $index;
        } else {
            // No input data. This shouldn't happen,
            // but better be safe than sorry.
            return [];
        }

        $allCharsets = Charsets::getCharsets($this->dbi, Config::getInstance()->selectedServer['DisableIS']);
        $charsets = [];
        foreach ($allCharsets as $charset) {
            $charsets[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'is_selected' => $charset->getName() === mb_strtolower($routine['item_param_opts_text'][$i]),
            ];
        }

        return [
            'class' => $class,
            'index' => $index,
            'param_directions' => $this->directions,
            'param_opts_num' => $this->numericOptions,
            'item_param_dir' => $routine['item_param_dir'][$i] ?? '',
            'item_param_name' => $routine['item_param_name'][$i] ?? '',
            'item_param_length' => $routine['item_param_length'][$i] ?? '',
            'item_param_opts_num' => $routine['item_param_opts_num'][$i] ?? '',
            'supported_datatypes' => Generator::getSupportedDatatypes(
                $this->dbi->types->mapAliasToMysqlType($routine['item_param_type'][$i]),
            ),
            'charsets' => $charsets,
            'drop_class' => $dropClass,
        ];
    }

    /**
     * Set the found errors and build the params
     *
     * @param string[] $itemParamName     The parameter names
     * @param string[] $itemParamDir      The direction parameter (see $this->directions)
     * @param mixed[]  $itemParamType     The parameter type
     * @param mixed[]  $itemParamLength   A length or not for the parameter
     * @param mixed[]  $itemParamOpsText  An optional charset for the parameter
     * @param mixed[]  $itemParamOpsNum   An optional parameter for a $itemParamType NUMBER
     * @param bool     $warnedAboutLength A boolean that will be switched if a the length warning is given
     */
    private function processParamsAndBuild(
        array $itemParamName,
        array $itemParamDir,
        array $itemParamType,
        array $itemParamLength,
        array $itemParamOpsText,
        array $itemParamOpsNum,
        RoutineType $itemType,
        bool &$warnedAboutLength,
    ): string {
        $params = '';
        $warnedAboutDir = false;

        for ($i = 0, $nb = count($itemParamName); $i < $nb; $i++) {
            if (empty($itemParamName[$i]) || empty($itemParamType[$i])) {
                $this->errors[] = __('You must provide a name and a type for each routine parameter.');
                break;
            }

            if (
                $itemType === RoutineType::Procedure
                && ! empty($itemParamDir[$i])
                && in_array($itemParamDir[$i], $this->directions, true)
            ) {
                $params .= $itemParamDir[$i] . ' '
                    . Util::backquote($itemParamName[$i])
                    . ' ' . $itemParamType[$i];
            } elseif ($itemType === RoutineType::Function) {
                $params .= Util::backquote($itemParamName[$i])
                    . ' ' . $itemParamType[$i];
            } elseif (! $warnedAboutDir) {
                $warnedAboutDir = true;
                $this->errors[] = sprintf(
                    __('Invalid direction "%s" given for parameter.'),
                    htmlspecialchars($itemParamDir[$i]),
                );
            }

            if (
                $itemParamLength[$i] != ''
                && preg_match(
                    '@^(DATE|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN)$@i',
                    $itemParamType[$i],
                ) !== 1
            ) {
                $params .= '(' . $itemParamLength[$i] . ')';
            } elseif (
                $itemParamLength[$i] == ''
                && preg_match('@^(ENUM|SET|VARCHAR|VARBINARY)$@i', $itemParamType[$i]) === 1
            ) {
                if (! $warnedAboutLength) {
                    $warnedAboutLength = true;
                    $this->errors[] = __(
                        'You must provide length/values for routine parameters'
                        . ' of type ENUM, SET, VARCHAR and VARBINARY.',
                    );
                }
            }

            if (! empty($itemParamOpsText[$i])) {
                if ($this->dbi->types->getTypeClass($itemParamType[$i]) === TypeClass::Char) {
                    if (! in_array($itemParamType[$i], ['VARBINARY', 'BINARY'], true)) {
                        $params .= ' CHARSET '
                            . mb_strtolower($itemParamOpsText[$i]);
                    }
                }
            }

            if (! empty($itemParamOpsNum[$i])) {
                if ($this->dbi->types->getTypeClass($itemParamType[$i]) === TypeClass::Number) {
                    $params .= ' '
                        . mb_strtoupper($itemParamOpsNum[$i]);
                }
            }

            if ($i === count($itemParamName) - 1) {
                continue;
            }

            $params .= ', ';
        }

        return $params;
    }

    /**
     * Set the found errors and build the query
     *
     * @param string $query             The existing query
     * @param bool   $warnedAboutLength If the length warning was given
     */
    private function processFunctionSpecificParameters(
        string $query,
        bool $warnedAboutLength,
    ): string {
        $itemReturnType = $_POST['item_returntype'] ?? null;

        if ($itemReturnType !== '' && in_array($itemReturnType, Util::getSupportedDatatypes(), true)) {
            $query .= 'RETURNS ' . $itemReturnType;
        } else {
            $this->errors[] = __('You must provide a valid return type for the routine.');
        }

        if (
            ! empty($_POST['item_returnlength'])
            && preg_match(
                '@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|'
                . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN)$@i',
                $itemReturnType,
            ) !== 1
        ) {
            $query .= '(' . $_POST['item_returnlength'] . ')';
        } elseif (
            empty($_POST['item_returnlength'])
            && preg_match('@^(ENUM|SET|VARCHAR|VARBINARY)$@i', $itemReturnType) === 1
        ) {
            if (! $warnedAboutLength) {
                $this->errors[] = __(
                    'You must provide length/values for routine parameters of type ENUM, SET, VARCHAR and VARBINARY.',
                );
            }
        }

        if (! empty($_POST['item_returnopts_text'])) {
            if ($this->dbi->types->getTypeClass($itemReturnType) === TypeClass::Char) {
                $query .= ' CHARSET '
                    . mb_strtolower($_POST['item_returnopts_text']);
            }
        }

        if (! empty($_POST['item_returnopts_num'])) {
            if ($this->dbi->types->getTypeClass($itemReturnType) === TypeClass::Number) {
                $query .= ' '
                    . mb_strtoupper($_POST['item_returnopts_num']);
            }
        }

        return $query . ' ';
    }

    /**
     * Composes the query necessary to create a routine from an HTTP request.
     *
     * @return string  The CREATE [ROUTINE | PROCEDURE] query.
     */
    public function getQueryFromRequest(ServerRequest $request): string
    {
        $itemType = RoutineType::tryFrom($request->getParsedBodyParamAsString('item_type', ''));
        $itemDefiner = $request->getParsedBodyParamAsString('item_definer', '');
        $itemName = $request->getParsedBodyParamAsString('item_name', '');

        $query = 'CREATE ';
        if ($itemDefiner !== '') {
            if (str_contains($itemDefiner, '@')) {
                $arr = explode('@', $itemDefiner);

                $doBackquote = true;
                if (str_starts_with($arr[0], '`') && str_ends_with($arr[0], '`')) {
                    $doBackquote = false;
                }

                $query .= 'DEFINER=' . Util::backquoteCompat($arr[0], 'NONE', $doBackquote);

                $doBackquote = true;
                if (str_starts_with($arr[1], '`') && str_ends_with($arr[1], '`')) {
                    $doBackquote = false;
                }

                $query .= '@' . Util::backquoteCompat($arr[1], 'NONE', $doBackquote) . ' ';
            } else {
                $this->errors[] = __('The definer must be in the "username@hostname" format!');
            }
        }

        if ($itemType !== null) {
            $query .= $itemType->value . ' ';
        } else {
            $this->errors[] = __('Invalid routine type!');
        }

        if ($itemName !== '') {
            $query .= Util::backquote($itemName);
        } else {
            $this->errors[] = __('You must provide a routine name!');
        }

        $warnedAboutLength = false;

        $itemParamName = $request->getParsedBodyParam('item_param_name', '');
        $itemParamType = $request->getParsedBodyParam('item_param_type', '');
        $itemParamLength = $request->getParsedBodyParam('item_param_length', '');
        $itemParamDir = (array) $request->getParsedBodyParam('item_param_dir', []);
        $itemParamOpsText = (array) $request->getParsedBodyParam('item_param_opts_text', []);
        $itemParamOpsNum = (array) $request->getParsedBodyParam('item_param_opts_num', []);

        $params = '';
        if (
            $itemParamName !== []
            && $itemParamType !== []
            && $itemParamLength !== []
            && is_array($itemParamName)
            && is_array($itemParamType)
            && is_array($itemParamLength)
        ) {
            $params = $this->processParamsAndBuild(
                $itemParamName,
                $itemParamDir,
                $itemParamType,
                $itemParamLength,
                $itemParamOpsText,
                $itemParamOpsNum,
                $itemType,
                $warnedAboutLength, // Will possibly be modified by the function
            );
        }

        $query .= '(' . $params . ') ';
        if ($itemType === RoutineType::Function) {
            $query = $this->processFunctionSpecificParameters($query, $warnedAboutLength);
        }

        $itemComment = $request->getParsedBodyParamAsString('item_comment', '');
        if ($itemComment !== '') {
            $query .= 'COMMENT ' . $this->dbi->quoteString($itemComment) . ' ';
        }

        if ($request->hasBodyParam('item_isdeterministic')) {
            $query .= 'DETERMINISTIC ';
        } else {
            $query .= 'NOT DETERMINISTIC ';
        }

        $itemSqlDataAccess = $request->getParsedBodyParamAsString('item_sqldataaccess', '');
        if (in_array($itemSqlDataAccess, $this->sqlDataAccess, true)) {
            $query .= $itemSqlDataAccess . ' ';
        }

        $itemSecurityType = $request->getParsedBodyParamAsString('item_securitytype', '');
        if ($itemSecurityType === 'DEFINER' || $itemSecurityType === 'INVOKER') {
            $query .= 'SQL SECURITY ' . $itemSecurityType . ' ';
        }

        $itemDefinition = $request->getParsedBodyParamAsString('item_definition', '');
        if ($itemDefinition !== '') {
            $query .= $itemDefinition;
        } else {
            $this->errors[] = __('You must provide a routine definition.');
        }

        return $query;
    }

    /**
     * @param mixed[] $routine The routine params
     *
     * @return string[] The SQL queries / SQL query parts
     */
    private function getQueriesFromRoutineForm(array $routine): array
    {
        $queries = [];
        $outParams = [];
        $args = [];
        $allFunctions = $this->dbi->types->getAllFunctions();
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            if (isset($_POST['params'][$routine['item_param_name'][$i]])) {
                $value = $_POST['params'][$routine['item_param_name'][$i]];
                if (is_array($value)) { // is SET type
                    $value = implode(',', $value);
                }

                if (
                    ! empty($_POST['funcs'][$routine['item_param_name'][$i]])
                    && in_array($_POST['funcs'][$routine['item_param_name'][$i]], $allFunctions, true)
                ) {
                    $queries[] = sprintf(
                        'SET @p%d=%s(%s);',
                        $i,
                        $_POST['funcs'][$routine['item_param_name'][$i]],
                        $this->dbi->quoteString($value),
                    );
                } else {
                    $queries[] = 'SET @p' . $i . '=' . $this->dbi->quoteString($value) . ';';
                }
            }

            $args[] = '@p' . $i;

            if ($routine['item_type'] !== 'PROCEDURE') {
                continue;
            }

            if ($routine['item_param_dir'][$i] !== 'OUT' && $routine['item_param_dir'][$i] !== 'INOUT') {
                continue;
            }

            $outParams[] = '@p' . $i . ' AS ' . Util::backquote($routine['item_param_name'][$i]);
        }

        if ($routine['item_type'] === 'PROCEDURE') {
            $queries[] = sprintf(
                'CALL %s(%s);',
                Util::backquote($routine['item_name']),
                implode(', ', $args),
            );
            if ($outParams !== []) {
                $queries[] = 'SELECT ' . implode(', ', $outParams) . ';';
            }
        } else {
            $queries[] = sprintf(
                'SELECT %s(%s) AS %s;',
                Util::backquote($routine['item_name']),
                implode(', ', $args),
                Util::backquote($routine['item_name']),
            );
        }

        return $queries;
    }

    /**
     * @param mixed[] $routine
     *
     * @psalm-return array{string, Message}
     */
    public function handleExecuteRoutine(array $routine): array
    {
        $queries = $this->getQueriesFromRoutineForm($routine);

        $affected = 0;
        $resultHtmlTables = '';
        $nbResultsetToDisplay = 0;

        foreach ($queries as $query) {
            $result = $this->dbi->tryQuery($query);

            // Generate output
            while ($result !== false) {
                if ($result->numRows() > 0) {
                    $resultHtmlTables .= '<table class="table table-striped w-auto"><tr>';
                    foreach ($result->getFieldNames() as $field) {
                        $resultHtmlTables .= '<th>';
                        $resultHtmlTables .= htmlspecialchars($field);
                        $resultHtmlTables .= '</th>';
                    }

                    $resultHtmlTables .= '</tr>';

                    foreach ($result as $row) {
                        $resultHtmlTables .= '<tr>' . $this->browseRow($row) . '</tr>';
                    }

                    $resultHtmlTables .= '</table>';
                    $nbResultsetToDisplay++;
                    $affected = $result->numRows();
                }

                $result = $this->dbi->nextResult();
            }

            // We must check for an error after fetching the results because
            // either tryQuery might have produced an error or any of nextResult calls.
            if ($this->dbi->getError() !== '') {
                $message = Message::error(
                    sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($query),
                    )
                    . '<br><br>'
                    . __('MySQL said: ') . $this->dbi->getError(),
                );

                return ['', $message];
            }
        }

        // Pass the SQL queries through the "pretty printer"
        $output = Generator::formatSql(implode("\n", $queries));
        // Display results
        $output .= '<div class="card my-3"><div class="card-header">';
        $output .= sprintf(
            __('Execution results of routine %s'),
            htmlspecialchars(Util::backquote($routine['item_name'])),
        );
        $output .= '</div><div class="card-body">';
        $output .= $resultHtmlTables;
        $output .= '</div></div>';

        $message = __('Your SQL query has been executed successfully.');
        if ($routine['item_type'] === 'PROCEDURE') {
            $message .= '<br>';

            // TODO : message need to be modified according to the
            // output from the routine
            $message .= sprintf(
                _ngettext(
                    '%d row affected by the last statement inside the procedure.',
                    '%d rows affected by the last statement inside the procedure.',
                    (int) $affected,
                ),
                $affected,
            );
        }

        if ($nbResultsetToDisplay === 0) {
            $notice = __('MySQL returned an empty result set (i.e. zero rows).');
            $output .= Message::notice($notice)->getDisplay();
        }

        return [$output, Message::success($message)];
    }

    /**
     * Browse row array
     *
     * @param (string|null)[] $row Columns
     */
    private function browseRow(array $row): string
    {
        $output = '';
        foreach ($row as $value) {
            $value = $value === null ? '<i>NULL</i>' : htmlspecialchars($value);

            $output .= '<td>' . $value . '</td>';
        }

        return $output;
    }

    /**
     * Creates the HTML code that shows the routine execution dialog.
     *
     * @param mixed[] $routine Data for the routine returned by getDataFromName()
     *
     * @psalm-return array{mixed[], mixed[]}
     */
    public function getExecuteForm(array $routine): array
    {
        // Escape special characters
        $routine['item_name'] = htmlentities($routine['item_name'], ENT_QUOTES);
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            $routine['item_param_name'][$i] = htmlentities($routine['item_param_name'][$i], ENT_QUOTES);
        }

        $params = [];

        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            if ($routine['item_type'] === 'PROCEDURE' && $routine['item_param_dir'][$i] === 'OUT') {
                continue;
            }

            if (Config::getInstance()->settings['ShowFunctionFields']) {
                if (
                    stripos($routine['item_param_type'][$i], 'enum') !== false
                    || stripos($routine['item_param_type'][$i], 'set') !== false
                ) {
                    $params[$i]['generator'] = null;
                } else {
                    $defaultFunction = Generator::getDefaultFunctionForField(
                        mb_strtolower($routine['item_param_type'][$i]),
                        false,
                        '',
                        '',
                        false,
                        '',
                        '',
                        false,
                    );
                    $params[$i]['generator'] = Generator::getFunctionsForField($defaultFunction);
                }
            }

            if ($routine['item_param_type'][$i] === 'DATETIME' || $routine['item_param_type'][$i] === 'TIMESTAMP') {
                $params[$i]['class'] = 'datetimefield';
            } elseif ($routine['item_param_type'][$i] === 'DATE') {
                $params[$i]['class'] = 'datefield';
            }

            if (in_array($routine['item_param_type'][$i], ['ENUM', 'SET'], true)) {
                $params[$i]['input_type'] = $routine['item_param_type'][$i] === 'ENUM' ? 'radio' : 'checkbox';
                foreach ($routine['item_param_length_arr'][$i] as $value) {
                    $value = htmlentities(Util::unQuote($value), ENT_QUOTES);
                    $params[$i]['htmlentities'][] = $value;
                }
            } else {
                $params[$i]['input_type'] = 'text';
            }
        }

        return [$routine, $params];
    }

    /**
     * Creates the contents for a row in the list of routines
     *
     * @param string $rowClass Additional class
     *
     * @return mixed[]
     */
    public function getRow(Routine $routine, string $rowClass = ''): array
    {
        $sqlDrop = sprintf(
            'DROP %s IF EXISTS %s',
            $routine->type,
            Util::backquote($routine->name),
        );

        $currentUser = $this->dbi->getCurrentUser();
        $currentUserIsRoutineDefiner = $currentUser === $routine->definer;

        // Since editing a procedure involved dropping and recreating, check also for
        // CREATE ROUTINE privilege to avoid lost procedures.
        $hasCreateRoutine = Util::currentUserHasPrivilege('CREATE ROUTINE', Current::$database);
        $hasEditPrivilege = ($hasCreateRoutine && $currentUserIsRoutineDefiner)
                            || $this->dbi->isSuperUser();
        $hasExportPrivilege = ($hasCreateRoutine && $currentUserIsRoutineDefiner)
                            || $this->dbi->isSuperUser();
        $hasExecutePrivilege = Util::currentUserHasPrivilege('EXECUTE', Current::$database)
                            || $currentUserIsRoutineDefiner;

        // There is a problem with Util::currentUserHasPrivilege():
        // it does not detect all kinds of privileges, for example
        // a direct privilege on a specific routine. So, at this point,
        // we show the Execute link, hoping that the user has the correct rights.
        // Also, information_schema might be hiding the ROUTINE_DEFINITION
        // but a routine with no input parameters can be nonetheless executed.

        // Check if the routine has any input parameters. If it does,
        // we will show a dialog to get values for these parameters,
        // otherwise we can execute it directly.

        return [
            'db' => Current::$database,
            'table' => Current::$table,
            'sql_drop' => $sqlDrop,
            'routine' => $routine,
            'row_class' => $rowClass,
            'has_edit_privilege' => $hasEditPrivilege,
            'has_export_privilege' => $hasExportPrivilege,
            'has_execute_privilege' => $hasExecutePrivilege,
        ];
    }

    /**
     * returns details about the PROCEDUREs or FUNCTIONs for a specific database
     * or details about a specific routine
     *
     * @param string $name name of the routine (to fetch a specific routine)
     *
     * @return Routine[]
     */
    public static function getDetails(
        DatabaseInterface $dbi,
        string $db,
        RoutineType|null $which = null,
        string $name = '',
        int $limit = 0,
        int $offset = 0,
    ): array {
        $query = QueryGenerator::getInformationSchemaRoutinesRequest(
            $dbi->quoteString($db),
            $which,
            $name === '' ? null : $dbi->quoteString($name),
            $limit,
            $offset,
        );
        $routines = $dbi->fetchResultSimple($query);

        $ret = [];
        /** @var array{Name:string, Type:string, Definer:string, DTD_IDENTIFIER:string|null} $routine */
        foreach ($routines as $routine) {
            $ret[] = new Routine(
                $routine['Name'],
                $routine['Type'],
                $routine['DTD_IDENTIFIER'] ?? '',
                $routine['Definer'],
            );
        }

        return $ret;
    }

    public static function getRoutineCount(DatabaseInterface $dbi, string $db, RoutineType|null $which = null): int
    {
        $query = QueryGenerator::getInformationSchemaRoutinesCountRequest(
            $dbi->quoteString($db),
            $which,
        );

        return (int) $dbi->fetchValue($query);
    }

    public static function getFunctionDefinition(DatabaseInterface $dbi, string $db, string $name): string|null
    {
        $result = $dbi->fetchValue(
            'SHOW CREATE FUNCTION ' . Util::backquote($db) . '.' . Util::backquote($name),
            'Create Function',
        );

        return is_string($result) ? $result : null;
    }

    public static function getProcedureDefinition(DatabaseInterface $dbi, string $db, string $name): string|null
    {
        $result = $dbi->fetchValue(
            'SHOW CREATE PROCEDURE ' . Util::backquote($db) . '.' . Util::backquote($name),
            'Create Procedure',
        );

        return is_string($result) ? $result : null;
    }

    /**
     * @return array<int, string>
     * @psalm-return list<non-empty-string>
     */
    public static function getNames(DatabaseInterface $dbi, string $db, RoutineType $type): array
    {
        /** @var list<non-empty-string> $names */
        $names = $dbi->fetchSingleColumn(
            'SELECT SPECIFIC_NAME FROM information_schema.ROUTINES'
            . ' WHERE ROUTINE_SCHEMA = ' . $dbi->quoteString($db)
            . " AND ROUTINE_TYPE = '" . $type->value . "' AND SPECIFIC_NAME != ''",
        );

        return $names;
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }
}
