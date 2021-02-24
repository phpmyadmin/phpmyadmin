<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Utils\Routine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use const ENT_QUOTES;
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
use function mb_strpos;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_match;
use function sprintf;
use function stripos;
use function substr;
use function trim;

/**
 * Functions for routine management.
 */
class Routines
{
    /** @var array<int, string> */
    private $directions = ['IN', 'OUT', 'INOUT'];

    /** @var array<int, string> */
    private $sqlDataAccess = ['CONTAINS SQL', 'NO SQL', 'READS SQL DATA', 'MODIFIES SQL DATA'];

    /** @var array<int, string> */
    private $numericOptions = ['UNSIGNED', 'ZEROFILL', 'UNSIGNED ZEROFILL'];

    /** @var DatabaseInterface */
    private $dbi;

    /** @var Template */
    private $template;

    /** @var Response */
    private $response;

    /**
     * @param DatabaseInterface $dbi      DatabaseInterface instance.
     * @param Template          $template Template instance.
     * @param Response          $response Response instance.
     */
    public function __construct(DatabaseInterface $dbi, Template $template, $response)
    {
        $this->dbi = $dbi;
        $this->template = $template;
        $this->response = $response;
    }

    /**
     * Handles editor requests for adding or editing an item
     *
     * @return void
     */
    public function handleEditor()
    {
        global $db, $errors;

        $errors = $this->handleRequestCreateOrEdit($errors, $db);

        /**
         * Display a form used to add/edit a routine, if necessary
         */
        // FIXME: this must be simpler than that
        if (! count($errors)
            && ( ! empty($_POST['editor_process_add'])
            || ! empty($_POST['editor_process_edit'])
            || (empty($_REQUEST['add_item']) && empty($_REQUEST['edit_item'])
            && empty($_POST['routine_addparameter'])
            && empty($_POST['routine_removeparameter'])
            && empty($_POST['routine_changetype'])))
        ) {
            return;
        }

        // Handle requests to add/remove parameters and changing routine type
        // This is necessary when JS is disabled
        $operation = '';
        if (! empty($_POST['routine_addparameter'])) {
            $operation = 'add';
        } elseif (! empty($_POST['routine_removeparameter'])) {
            $operation = 'remove';
        } elseif (! empty($_POST['routine_changetype'])) {
            $operation = 'change';
        }
        // Get the data for the form (if any)
        $routine = null;
        $mode = null;
        $title = null;
        if (! empty($_REQUEST['add_item'])) {
            $title = __('Add routine');
            $routine = $this->getDataFromRequest();
            $mode = 'add';
        } elseif (! empty($_REQUEST['edit_item'])) {
            $title = __('Edit routine');
            if (! $operation && ! empty($_GET['item_name'])
                && empty($_POST['editor_process_edit'])
            ) {
                $routine = $this->getDataFromName(
                    $_GET['item_name'],
                    $_GET['item_type']
                );
                if ($routine !== null) {
                    $routine['item_original_name'] = $routine['item_name'];
                    $routine['item_original_type'] = $routine['item_type'];
                }
            } else {
                $routine = $this->getDataFromRequest();
            }
            $mode = 'edit';
        }
        if ($routine !== null) {
            // Show form
            $editor = $this->getEditorForm($mode, $operation, $routine);
            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $editor);
                $this->response->addJSON('title', $title);
                $this->response->addJSON('paramTemplate', $this->getParameterRow());
                $this->response->addJSON('type', $routine['item_type']);
            } else {
                echo "\n\n<h2>" . $title . "</h2>\n\n" . $editor;
            }
            exit;
        }

        $message  = __('Error in processing request:') . ' ';
        $message .= sprintf(
            __(
                'No routine with name %1$s found in database %2$s. '
                . 'You might be lacking the necessary privileges to edit this routine.'
            ),
            htmlspecialchars(
                Util::backquote($_REQUEST['item_name'])
            ),
            htmlspecialchars(Util::backquote($db))
        );

        $message = Message::error($message);
        if ($this->response->isAjax()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);
            exit;
        }

        echo $message->getDisplay();
    }

    /**
     * Handle request to create or edit a routine
     *
     * @param array  $errors Errors
     * @param string $db     DB name
     *
     * @return array
     */
    public function handleRequestCreateOrEdit(array $errors, $db)
    {
        global $message;

        if (empty($_POST['editor_process_add'])
            && empty($_POST['editor_process_edit'])
        ) {
            return $errors;
        }

        $sql_query = '';
        $routine_query = $this->getQueryFromRequest();

        // set by getQueryFromRequest()
        if (! count($errors)) {
            // Execute the created query
            if (! empty($_POST['editor_process_edit'])) {
                $isProcOrFunc = in_array(
                    $_POST['item_original_type'],
                    [
                        'PROCEDURE',
                        'FUNCTION',
                    ]
                );

                if (! $isProcOrFunc) {
                    $errors[] = sprintf(
                        __('Invalid routine type: "%s"'),
                        htmlspecialchars($_POST['item_original_type'])
                    );
                } else {
                    // Backup the old routine, in case something goes wrong
                    $create_routine = $this->dbi->getDefinition(
                        $db,
                        $_POST['item_original_type'],
                        $_POST['item_original_name']
                    );

                    $privilegesBackup = $this->backupPrivileges();

                    $drop_routine = 'DROP ' . $_POST['item_original_type'] . ' '
                        . Util::backquote($_POST['item_original_name'])
                        . ";\n";
                    $result = $this->dbi->tryQuery($drop_routine);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($drop_routine)
                        )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        [$newErrors, $message] = $this->create(
                            $routine_query,
                            $create_routine,
                            $privilegesBackup
                        );
                        if (empty($newErrors)) {
                            $sql_query = $drop_routine . $routine_query;
                        } else {
                            $errors = array_merge($errors, $newErrors);
                        }
                        unset($newErrors);
                    }
                }
            } else {
                // 'Add a new routine' mode
                $result = $this->dbi->tryQuery($routine_query);
                if (! $result) {
                    $errors[] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($routine_query)
                    )
                    . '<br><br>'
                    . __('MySQL said: ') . $this->dbi->getError();
                } else {
                    $message = Message::success(
                        __('Routine %1$s has been created.')
                    );
                    $message->addParam(
                        Util::backquote($_POST['item_name'])
                    );
                    $sql_query = $routine_query;
                }
            }
        }

        if (count($errors)) {
            $message = Message::error(
                __(
                    'One or more errors have occurred while'
                    . ' processing your request:'
                )
            );
            $message->addHtml('<ul>');
            foreach ($errors as $string) {
                $message->addHtml('<li>' . $string . '</li>');
            }
            $message->addHtml('</ul>');
        }

        $output = Generator::getMessage($message, $sql_query);

        if (! $this->response->isAjax()) {
            return $errors;
        }

        if (! $message->isSuccess()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $output);
            exit;
        }

        $routines = $this->dbi->getRoutines(
            $db,
            $_POST['item_type'],
            $_POST['item_name']
        );
        $routine = $routines[0];
        $this->response->addJSON(
            'name',
            htmlspecialchars(
                mb_strtoupper($_POST['item_name'])
            )
        );
        $this->response->addJSON('new_row', $this->getRow($routine));
        $this->response->addJSON('insert', ! empty($routine));
        $this->response->addJSON('message', $output);
        exit;
    }

    /**
     * Backup the privileges
     *
     * @return array
     */
    public function backupPrivileges()
    {
        if (! $GLOBALS['proc_priv'] || ! $GLOBALS['is_reload_priv']) {
            return [];
        }

        // Backup the Old Privileges before dropping
        // if $_POST['item_adjust_privileges'] set
        if (! isset($_POST['item_adjust_privileges'])
            || empty($_POST['item_adjust_privileges'])
        ) {
            return [];
        }

        $privilegesBackupQuery = 'SELECT * FROM ' . Util::backquote(
            'mysql'
        )
        . '.' . Util::backquote('procs_priv')
        . ' where Routine_name = "' . $_POST['item_original_name']
        . '" AND Routine_type = "' . $_POST['item_original_type']
        . '";';

        return $this->dbi->fetchResult(
            $privilegesBackupQuery,
            0
        );
    }

    /**
     * Create the routine
     *
     * @param string $routine_query    Query to create routine
     * @param string $create_routine   Query to restore routine
     * @param array  $privilegesBackup Privileges backup
     *
     * @return array
     */
    public function create(
        $routine_query,
        $create_routine,
        array $privilegesBackup
    ) {
        $result = $this->dbi->tryQuery($routine_query);
        if (! $result) {
            $errors = [];
            $errors[] = sprintf(
                __('The following query has failed: "%s"'),
                htmlspecialchars($routine_query)
            )
            . '<br>'
            . __('MySQL said: ') . $this->dbi->getError();
            // We dropped the old routine,
            // but were unable to create the new one
            // Try to restore the backup query
            $result = $this->dbi->tryQuery($create_routine);
            $errors = $this->checkResult($result, $create_routine, $errors);

            return [
                $errors,
                null,
            ];
        }

        // Default value
        $resultAdjust = false;

        if ($GLOBALS['proc_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            // Insert all the previous privileges
            // but with the new name and the new type
            foreach ($privilegesBackup as $priv) {
                $adjustProcPrivilege = 'INSERT INTO '
                    . Util::backquote('mysql') . '.'
                    . Util::backquote('procs_priv')
                    . ' VALUES("' . $priv[0] . '", "'
                    . $priv[1] . '", "' . $priv[2] . '", "'
                    . $_POST['item_name'] . '", "'
                    . $_POST['item_type'] . '", "'
                    . $priv[5] . '", "'
                    . $priv[6] . '", "'
                    . $priv[7] . '");';
                $resultAdjust = $this->dbi->query(
                    $adjustProcPrivilege
                );
            }
        }

        $message = $this->flushPrivileges($resultAdjust);

        return [
            [],
            $message,
        ];
    }

    /**
     * Flush privileges and get message
     *
     * @param bool $flushPrivileges Flush privileges
     *
     * @return Message
     */
    public function flushPrivileges($flushPrivileges)
    {
        if ($flushPrivileges) {
            // Flush the Privileges
            $flushPrivQuery = 'FLUSH PRIVILEGES;';
            $this->dbi->query($flushPrivQuery);

            $message = Message::success(
                __(
                    'Routine %1$s has been modified. Privileges have been adjusted.'
                )
            );
        } else {
            $message = Message::success(
                __('Routine %1$s has been modified.')
            );
        }
        $message->addParam(
            Util::backquote($_POST['item_name'])
        );

        return $message;
    }

    /**
     * This function will generate the values that are required to
     * complete the editor form. It is especially necessary to handle
     * the 'Add another parameter', 'Remove last parameter' and
     * 'Change routine type' functionalities when JS is disabled.
     *
     * @return array    Data necessary to create the routine editor.
     */
    public function getDataFromRequest()
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

        $retval['item_type']         = 'PROCEDURE';
        $retval['item_type_toggle']  = 'FUNCTION';
        if (isset($_REQUEST['item_type']) && $_REQUEST['item_type'] === 'FUNCTION') {
            $retval['item_type']         = 'FUNCTION';
            $retval['item_type_toggle']  = 'PROCEDURE';
        }
        $retval['item_original_type'] = 'PROCEDURE';
        if (isset($_POST['item_original_type'])
            && $_POST['item_original_type'] === 'FUNCTION'
        ) {
            $retval['item_original_type'] = 'FUNCTION';
        }
        $retval['item_num_params']      = 0;
        $retval['item_param_dir']       = [];
        $retval['item_param_name']      = [];
        $retval['item_param_type']      = [];
        $retval['item_param_length']    = [];
        $retval['item_param_opts_num']  = [];
        $retval['item_param_opts_text'] = [];
        if (isset($_POST['item_param_name'], $_POST['item_param_type'])
            && isset($_POST['item_param_length'])
            && isset($_POST['item_param_opts_num'])
            && isset($_POST['item_param_opts_text'])
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
            $retval['item_param_length']    = $_POST['item_param_length'];
            $retval['item_param_opts_num']  = $_POST['item_param_opts_num'];
            $retval['item_param_opts_text'] = $_POST['item_param_opts_text'];
            $retval['item_num_params'] = max(
                count($retval['item_param_name']),
                count($retval['item_param_type']),
                count($retval['item_param_length']),
                count($retval['item_param_opts_num']),
                count($retval['item_param_opts_text'])
            );
        }
        $retval['item_returntype'] = '';
        if (isset($_POST['item_returntype'])
            && in_array($_POST['item_returntype'], Util::getSupportedDatatypes())
        ) {
            $retval['item_returntype'] = $_POST['item_returntype'];
        }

        $retval['item_isdeterministic'] = '';
        if (isset($_POST['item_isdeterministic'])
            && mb_strtolower($_POST['item_isdeterministic']) === 'on'
        ) {
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
        if (isset($_POST['item_sqldataaccess'])
            && in_array($_POST['item_sqldataaccess'], $this->sqlDataAccess, true)
        ) {
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
     * @return array|null    Data necessary to create the routine editor.
     */
    public function getDataFromName($name, $type, $all = true): ?array
    {
        global $db;

        $retval  = [];

        // Build and execute the query
        $fields  = 'SPECIFIC_NAME, ROUTINE_TYPE, DTD_IDENTIFIER, '
                 . 'ROUTINE_DEFINITION, IS_DETERMINISTIC, SQL_DATA_ACCESS, '
                 . 'ROUTINE_COMMENT, SECURITY_TYPE';
        $where   = 'ROUTINE_SCHEMA ' . Util::getCollateForIS() . '='
                 . "'" . $this->dbi->escapeString($db) . "' "
                 . "AND SPECIFIC_NAME='" . $this->dbi->escapeString($name) . "'"
                 . "AND ROUTINE_TYPE='" . $this->dbi->escapeString($type) . "'";
        $query   = 'SELECT ' . $fields . ' FROM INFORMATION_SCHEMA.ROUTINES WHERE ' . $where . ';';

        $routine = $this->dbi->fetchSingleRow($query, 'ASSOC');

        if (! $routine) {
            return null;
        }

        // Get required data
        $retval['item_name'] = $routine['SPECIFIC_NAME'];
        $retval['item_type'] = $routine['ROUTINE_TYPE'];

        $definition
            = $this->dbi->getDefinition(
                $db,
                $routine['ROUTINE_TYPE'],
                $routine['SPECIFIC_NAME']
            );

        if ($definition === null) {
            return null;
        }

        $parser = new Parser($definition);

        /**
         * @var CreateStatement $stmt
         */
        $stmt = $parser->statements[0];

        $params = Routine::getParameters($stmt);
        $retval['item_num_params']       = $params['num'];
        $retval['item_param_dir']        = $params['dir'];
        $retval['item_param_name']       = $params['name'];
        $retval['item_param_type']       = $params['type'];
        $retval['item_param_length']     = $params['length'];
        $retval['item_param_length_arr'] = $params['length_arr'];
        $retval['item_param_opts_num']   = $params['opts'];
        $retval['item_param_opts_text']  = $params['opts'];

        // Get extra data
        if (! $all) {
            return $retval;
        }

        if ($retval['item_type'] === 'FUNCTION') {
            $retval['item_type_toggle'] = 'PROCEDURE';
        } else {
            $retval['item_type_toggle'] = 'FUNCTION';
        }
        $retval['item_returntype']      = '';
        $retval['item_returnlength']    = '';
        $retval['item_returnopts_num']  = '';
        $retval['item_returnopts_text'] = '';

        if (! empty($routine['DTD_IDENTIFIER'])) {
            $options = [];
            foreach ($stmt->return->options->options as $opt) {
                $options[] = is_string($opt) ? $opt : $opt['value'];
            }

            $retval['item_returntype']      = $stmt->return->name;
            $retval['item_returnlength']    = implode(',', $stmt->return->parameters);
            $retval['item_returnopts_num']  = implode(' ', $options);
            $retval['item_returnopts_text'] = implode(' ', $options);
        }

        $retval['item_definer'] = $stmt->options->has('DEFINER');
        $retval['item_definition'] = $routine['ROUTINE_DEFINITION'];
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
        $retval['item_comment']       = $routine['ROUTINE_COMMENT'];

        return $retval;
    }

    /**
     * Creates one row for the parameter table used in the routine editor.
     *
     * @param array  $routine Data for the routine returned by
     *                        getDataFromRequest() or getDataFromName()
     * @param mixed  $index   Either a numeric index of the row being processed
     *                        or NULL to create a template row for AJAX request
     * @param string $class   Class used to hide the direction column, if the
     *                        row is for a stored function.
     *
     * @return string    HTML code of one row of parameter table for the editor.
     */
    public function getParameterRow(array $routine = [], $index = null, $class = '')
    {
        if ($index === null) {
            // template row for AJAX request
            $i = 0;
            $index = '%s';
            $drop_class = '';
            $routine = [
                'item_param_dir'       => [0 => ''],
                'item_param_name'      => [0 => ''],
                'item_param_type'      => [0 => ''],
                'item_param_length'    => [0 => ''],
                'item_param_opts_num'  => [0 => ''],
                'item_param_opts_text' => [0 => ''],
            ];
        } elseif (! empty($routine)) {
            // regular row for routine editor
            $drop_class = ' hide';
            $i = $index;
        } else {
            // No input data. This shouldn't happen,
            // but better be safe than sorry.
            return '';
        }

        $allCharsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $charsets = [];
        /** @var Charset $charset */
        foreach ($allCharsets as $charset) {
            $charsets[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'is_selected' => $charset->getName() === $routine['item_param_opts_text'][$i],
            ];
        }

        return $this->template->render('database/routines/parameter_row', [
            'class' => $class,
            'index' => $index,
            'param_directions' => $this->directions,
            'param_opts_num' => $this->numericOptions,
            'item_param_dir' => $routine['item_param_dir'][$i] ?? '',
            'item_param_name' => $routine['item_param_name'][$i] ?? '',
            'item_param_length' => $routine['item_param_length'][$i] ?? '',
            'item_param_opts_num' => $routine['item_param_opts_num'][$i] ?? '',
            'supported_datatypes' => Util::getSupportedDatatypes(
                true,
                $routine['item_param_type'][$i]
            ),
            'charsets' => $charsets,
            'drop_class' => $drop_class,
        ]);
    }

    /**
     * Displays a form used to add/edit a routine
     *
     * @param string $mode      If the editor will be used to edit a routine
     *                          or add a new one: 'edit' or 'add'.
     * @param string $operation If the editor was previously invoked with
     *                          JS turned off, this will hold the name of
     *                          the current operation
     * @param array  $routine   Data for the routine returned by
     *                          getDataFromRequest() or getDataFromName()
     *
     * @return string   HTML code for the editor.
     */
    public function getEditorForm($mode, $operation, array $routine)
    {
        global $db, $errors;

        // Escape special characters
        $need_escape = [
            'item_original_name',
            'item_name',
            'item_returnlength',
            'item_definition',
            'item_definer',
            'item_comment',
        ];
        foreach ($need_escape as $key => $index) {
            $routine[$index] = htmlentities($routine[$index], ENT_QUOTES, 'UTF-8');
        }
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            $routine['item_param_name'][$i]   = htmlentities(
                $routine['item_param_name'][$i],
                ENT_QUOTES
            );
            $routine['item_param_length'][$i] = htmlentities(
                $routine['item_param_length'][$i],
                ENT_QUOTES
            );
        }

        // Handle some logic first
        if ($operation === 'change') {
            if ($routine['item_type'] === 'PROCEDURE') {
                $routine['item_type']        = 'FUNCTION';
                $routine['item_type_toggle'] = 'PROCEDURE';
            } else {
                $routine['item_type']        = 'PROCEDURE';
                $routine['item_type_toggle'] = 'FUNCTION';
            }
        } elseif ($operation === 'add'
            || ($routine['item_num_params'] == 0 && $mode === 'add' && ! $errors)
        ) {
            $routine['item_param_dir'][]       = '';
            $routine['item_param_name'][]      = '';
            $routine['item_param_type'][]      = '';
            $routine['item_param_length'][]    = '';
            $routine['item_param_opts_num'][]  = '';
            $routine['item_param_opts_text'][] = '';
            $routine['item_num_params']++;
        } elseif ($operation === 'remove') {
            unset(
                $routine['item_param_dir'][$routine['item_num_params'] - 1],
                $routine['item_param_name'][$routine['item_num_params'] - 1],
                $routine['item_param_type'][$routine['item_num_params'] - 1],
                $routine['item_param_length'][$routine['item_num_params'] - 1],
                $routine['item_param_opts_num'][$routine['item_num_params'] - 1],
                $routine['item_param_opts_text'][$routine['item_num_params'] - 1]
            );
            $routine['item_num_params']--;
        }
        $disableRemoveParam = '';
        if (! $routine['item_num_params']) {
            $disableRemoveParam = " class='isdisableremoveparam_class' disabled=disabled";
        }
        $original_routine = '';
        if ($mode === 'edit') {
            $original_routine = "<input name='item_original_name' "
                              . "type='hidden' "
                              . "value='" . $routine['item_original_name'] . "'>\n"
                              . "<input name='item_original_type' "
                              . "type='hidden' "
                              . "value='" . $routine['item_original_type'] . "'>\n";
        }
        $isfunction_class   = '';
        $isprocedure_class  = '';
        $isfunction_select  = '';
        $isprocedure_select = '';
        if ($routine['item_type'] === 'PROCEDURE') {
            $isfunction_class   = ' hide';
            $isprocedure_select = " selected='selected'";
        } else {
            $isprocedure_class = ' hide';
            $isfunction_select = " selected='selected'";
        }

        // Create the output
        $retval  = '';
        $retval .= '<!-- START ' . mb_strtoupper($mode)
            . " ROUTINE FORM -->\n\n";
        $retval .= '<form class="rte_form" action="' . Url::getFromRoute('/database/routines')
            . '" method="post">' . "\n";
        $retval .= "<input name='" . $mode . "_item' type='hidden' value='1'>\n";
        $retval .= $original_routine;
        $retval .= Url::getHiddenInputs($db) . "\n";
        $retval .= "<fieldset>\n";
        $retval .= '<legend>' . __('Details') . "</legend>\n";
        $retval .= '<table class="rte_table table table-borderless table-sm">' . "\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Routine name') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_name' maxlength='64'\n";
        $retval .= "               value='" . $routine['item_name'] . "'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Type') . "</td>\n";
        $retval .= "    <td>\n";
        if ($this->response->isAjax()) {
            $retval .= "        <select name='item_type'>\n"
                . "<option value='PROCEDURE'" . $isprocedure_select . ">PROCEDURE</option>\n"
                . "<option value='FUNCTION'" . $isfunction_select . ">FUNCTION</option>\n"
                . "</select>\n";
        } else {
            $retval .= "<input name='item_type' type='hidden'"
                . " value='" . $routine['item_type'] . "'>\n"
                . "<div class='font_weight_bold text-center w-50'>\n"
                . $routine['item_type'] . "\n"
                . "</div>\n"
                . "<input type='submit' name='routine_changetype'\n"
                . " value='" . sprintf(__('Change to %s'), $routine['item_type_toggle'])
                . "'>\n";
        }
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Parameters') . "</td>\n";
        $retval .= "    <td>\n";
        // parameter handling start
        $retval .= "        <table class='routine_params_table table table-borderless table-sm'>\n";
        $retval .= "        <thead>\n";
        $retval .= "        <tr>\n";
        $retval .= "            <td></td>\n";
        $retval .= "            <th class='routine_direction_cell" . $isprocedure_class . "'>"
            . __('Direction') . "</th>\n";
        $retval .= '            <th>' . __('Name') . "</th>\n";
        $retval .= '            <th>' . __('Type') . "</th>\n";
        $retval .= '            <th>' . __('Length/Values') . "</th>\n";
        $retval .= "            <th colspan='2'>" . __('Options') . "</th>\n";
        $retval .= "            <th class='routine_param_remove hide'>&nbsp;</th>\n";
        $retval .= '        </tr>';
        $retval .= "        </thead>\n";
        $retval .= "        <tbody>\n";
        for ($i = 0; $i < $routine['item_num_params']; $i++) { // each parameter
            $retval .= $this->getParameterRow($routine, $i, $isprocedure_class);
        }
        $retval .= "        </tbody>\n";
        $retval .= '        </table>';
        $retval .= '    </td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '    <td>&nbsp;</td>';
        $retval .= '    <td>';
        $retval .= '        <input type="button" class="btn btn-primary"';
        $retval .= "               name='routine_addparameter'";
        $retval .= "               value='" . __('Add parameter') . "'>";
        $retval .= '        <input ' . $disableRemoveParam . '';
        $retval .= "               type='submit' ";
        $retval .= "               name='routine_removeparameter'";
        $retval .= "               value='" . __('Remove last parameter') . "'>";
        $retval .= '    </td>';
        $retval .= '</tr>';
        // parameter handling end
        $retval .= "<tr class='routine_return_row" . $isfunction_class . "'>";
        $retval .= '    <td>' . __('Return type') . '</td>';
        $retval .= "    <td><select name='item_returntype'>";
        $retval .= Util::getSupportedDatatypes(true, $routine['item_returntype']);
        $retval .= '    </select></td>';
        $retval .= '</tr>';
        $retval .= "<tr class='routine_return_row" . $isfunction_class . "'>";
        $retval .= '    <td>' . __('Return length/values') . '</td>';
        $retval .= "    <td><input type='text' name='item_returnlength'";
        $retval .= "        value='" . $routine['item_returnlength'] . "'></td>";
        $retval .= "    <td class='hide no_len'>---</td>";
        $retval .= '</tr>';
        $retval .= "<tr class='routine_return_row" . $isfunction_class . "'>";
        $retval .= '    <td>' . __('Return options') . '</td>';
        $retval .= '    <td><div>';
        $retval .= '<select lang="en" dir="ltr" name="item_returnopts_text">' . "\n";
        $retval .= '<option value="">' . __('Charset') . '</option>' . "\n";
        $retval .= '<option value=""></option>' . "\n";

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        /** @var Charset $charset */
        foreach ($charsets as $charset) {
            $retval .= '<option value="' . $charset->getName()
                . '" title="' . $charset->getDescription() . '"'
                . ($routine['item_returnopts_text'] == $charset->getName() ? ' selected' : '') . '>'
                . $charset->getName() . '</option>' . "\n";
        }

        $retval .= '</select>' . "\n";
        $retval .= '    </div>';
        $retval .= "    <div><select name='item_returnopts_num'>";
        $retval .= "        <option value=''></option>";
        foreach ($this->numericOptions as $key => $value) {
            $selected = '';
            if (! empty($routine['item_returnopts_num'])
                && $routine['item_returnopts_num'] == $value
            ) {
                $selected = " selected='selected'";
            }
            $retval .= '<option' . $selected . '>' . $value . '</option>';
        }
        $retval .= '    </select></div>';
        $retval .= "    <div class='hide no_opts'>---</div>";
        $retval .= '</td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '    <td>' . __('Definition') . '</td>';
        $retval .= "    <td><textarea name='item_definition' rows='15' cols='40'>";
        $retval .= $routine['item_definition'];
        $retval .= '</textarea></td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '    <td>' . __('Is deterministic') . '</td>';
        $retval .= "    <td><input type='checkbox' name='item_isdeterministic'"
            . $routine['item_isdeterministic'] . '></td>';
        $retval .= '</tr>';
        if (isset($_REQUEST['edit_item'])
            && ! empty($_REQUEST['edit_item'])
        ) {
            $retval .= '<tr>';
            $retval .= '    <td>' . __('Adjust privileges');
            $retval .= MySQLDocumentation::showDocumentation('faq', 'faq6-39');
            $retval .= '</td>';
            if ($GLOBALS['proc_priv']
                && $GLOBALS['is_reload_priv']
            ) {
                $retval .= "    <td><input type='checkbox' "
                    . "name='item_adjust_privileges' value='1' checked></td>";
            } else {
                $retval .= "    <td><input type='checkbox' "
                    . "name='item_adjust_privileges' value='1' title='" . __(
                        'You do not have sufficient privileges to perform this '
                        . 'operation; Please refer to the documentation for more '
                        . 'details'
                    )
                    . "' disabled></td>";
            }
            $retval .= '</tr>';
        }

        $retval .= '<tr>';
        $retval .= '    <td>' . __('Definer') . '</td>';
        $retval .= "    <td><input type='text' name='item_definer'";
        $retval .= "               value='" . $routine['item_definer'] . "'></td>";
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '    <td>' . __('Security type') . '</td>';
        $retval .= "    <td><select name='item_securitytype'>";
        $retval .= "        <option value='DEFINER'"
            . $routine['item_securitytype_definer'] . '>DEFINER</option>';
        $retval .= "        <option value='INVOKER'"
            . $routine['item_securitytype_invoker'] . '>INVOKER</option>';
        $retval .= '    </select></td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '    <td>' . __('SQL data access') . '</td>';
        $retval .= "    <td><select name='item_sqldataaccess'>";
        foreach ($this->sqlDataAccess as $key => $value) {
            $selected = '';
            if ($routine['item_sqldataaccess'] == $value) {
                $selected = " selected='selected'";
            }
            $retval .= '        <option' . $selected . '>' . $value . '</option>';
        }
        $retval .= '    </select></td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '    <td>' . __('Comment') . '</td>';
        $retval .= "    <td><input type='text' name='item_comment' maxlength='64'";
        $retval .= "    value='" . $routine['item_comment'] . "'></td>";
        $retval .= '</tr>';
        $retval .= '</table>';
        $retval .= '</fieldset>';
        if ($this->response->isAjax()) {
            $retval .= "<input type='hidden' name='editor_process_" . $mode . "'";
            $retval .= "       value='true'>";
            $retval .= "<input type='hidden' name='ajax_request' value='true'>";
        } else {
            $retval .= "<fieldset class='tblFooters'>";
            $retval .= "    <input type='submit' name='editor_process_" . $mode . "'";
            $retval .= "           value='" . __('Go') . "'>";
            $retval .= '</fieldset>';
        }
        $retval .= '</form>';
        $retval .= '<!-- END ' . mb_strtoupper($mode) . ' ROUTINE FORM -->';

        return $retval;
    }

    /**
     * Set the found errors and build the params
     *
     * @param string[] $itemParamName     The parameter names
     * @param string[] $itemParamDir      The direction parameter (see $this->directions)
     * @param array    $itemParamType     The parameter type
     * @param array    $itemParamLength   A length or not for the parameter
     * @param array    $itemParamOpsText  An optional charset for the parameter
     * @param array    $itemParamOpsNum   An optional parameter for a $itemParamType NUMBER
     * @param string   $itemType          The item type (PROCEDURE/FUNCTION)
     * @param bool     $warnedAboutLength A boolean that will be switched if a the length warning is given
     */
    private function processParamsAndBuild(
        array $itemParamName,
        array $itemParamDir,
        array $itemParamType,
        array $itemParamLength,
        array $itemParamOpsText,
        array $itemParamOpsNum,
        string $itemType,
        bool &$warnedAboutLength
    ): string {
        global $errors, $dbi;

        $params = '';
        $warnedAboutDir = false;

        for ($i = 0, $nb = count($itemParamName); $i < $nb; $i++) {
            if (empty($itemParamName[$i])
                || empty($itemParamType[$i])
            ) {
                $errors[] = __(
                    'You must provide a name and a type for each routine parameter.'
                );
                break;
            }

            if ($itemType === 'PROCEDURE'
                && ! empty($itemParamDir[$i])
                && in_array($itemParamDir[$i], $this->directions)
            ) {
                $params .= $itemParamDir[$i] . ' '
                    . Util::backquote($itemParamName[$i])
                    . ' ' . $itemParamType[$i];
            } elseif ($itemType === 'FUNCTION') {
                $params .= Util::backquote($itemParamName[$i])
                    . ' ' . $itemParamType[$i];
            } elseif (! $warnedAboutDir) {
                $warnedAboutDir = true;
                $errors[] = sprintf(
                    __('Invalid direction "%s" given for parameter.'),
                    htmlspecialchars($itemParamDir[$i])
                );
            }
            if ($itemParamLength[$i] != ''
                && ! preg_match(
                    '@^(DATE|TINYBLOB|TINYTEXT|BLOB|TEXT|'
                    . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|'
                    . 'SERIAL|BOOLEAN)$@i',
                    $itemParamType[$i]
                )
            ) {
                $params .= '(' . $itemParamLength[$i] . ')';
            } elseif ($itemParamLength[$i] == ''
                && preg_match(
                    '@^(ENUM|SET|VARCHAR|VARBINARY)$@i',
                    $itemParamType[$i]
                )
            ) {
                if (! $warnedAboutLength) {
                    $warnedAboutLength = true;
                    $errors[] = __(
                        'You must provide length/values for routine parameters'
                        . ' of type ENUM, SET, VARCHAR and VARBINARY.'
                    );
                }
            }
            if (! empty($itemParamOpsText[$i])) {
                if ($dbi->types->getTypeClass($itemParamType[$i]) === 'CHAR') {
                    if (! in_array($itemParamType[$i], ['VARBINARY', 'BINARY'])) {
                        $params .= ' CHARSET '
                            . mb_strtolower(
                                $itemParamOpsText[$i]
                            );
                    }
                }
            }
            if (! empty($itemParamOpsNum[$i])) {
                if ($dbi->types->getTypeClass($itemParamType[$i]) === 'NUMBER') {
                    $params .= ' '
                        . mb_strtoupper(
                            $itemParamOpsNum[$i]
                        );
                }
            }
            if ($i == count($itemParamName) - 1) {
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
        bool $warnedAboutLength
    ): string {
        global $errors, $dbi;

        $itemReturnType = $_POST['item_returntype'] ?? null;

        if (! empty($itemReturnType)
            && in_array(
                $itemReturnType,
                Util::getSupportedDatatypes()
            )
        ) {
            $query .= 'RETURNS ' . $itemReturnType;
        } else {
            $errors[] = __('You must provide a valid return type for the routine.');
        }
        if (! empty($_POST['item_returnlength'])
            && ! preg_match(
                '@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|'
                . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN)$@i',
                $itemReturnType
            )
        ) {
            $query .= '(' . $_POST['item_returnlength'] . ')';
        } elseif (empty($_POST['item_returnlength'])
            && preg_match(
                '@^(ENUM|SET|VARCHAR|VARBINARY)$@i',
                $itemReturnType
            )
        ) {
            if (! $warnedAboutLength) {
                $errors[] = __(
                    'You must provide length/values for routine parameters'
                    . ' of type ENUM, SET, VARCHAR and VARBINARY.'
                );
            }
        }
        if (! empty($_POST['item_returnopts_text'])) {
            if ($dbi->types->getTypeClass($itemReturnType) === 'CHAR') {
                $query .= ' CHARSET '
                    . mb_strtolower($_POST['item_returnopts_text']);
            }
        }
        if (! empty($_POST['item_returnopts_num'])) {
            if ($dbi->types->getTypeClass($itemReturnType) === 'NUMBER') {
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
    public function getQueryFromRequest(): string
    {
        global $errors;

        $itemType = $_POST['item_type'] ?? '';
        $itemDefiner = $_POST['item_definer'] ?? '';
        $itemName = $_POST['item_name'] ?? '';

        $query = 'CREATE ';
        if (! empty($itemDefiner)) {
            if (mb_strpos($itemDefiner, '@') !== false) {
                $arr = explode('@', $itemDefiner);

                $do_backquote = true;
                if (substr($arr[0], 0, 1) === '`'
                    && substr($arr[0], -1) === '`'
                ) {
                    $do_backquote = false;
                }
                $query .= 'DEFINER=' . Util::backquote($arr[0], $do_backquote);

                $do_backquote = true;
                if (substr($arr[1], 0, 1) === '`'
                    && substr($arr[1], -1) === '`'
                ) {
                    $do_backquote = false;
                }
                $query .= '@' . Util::backquote($arr[1], $do_backquote) . ' ';
            } else {
                $errors[] = __('The definer must be in the "username@hostname" format!');
            }
        }
        if ($itemType === 'FUNCTION'
            || $itemType === 'PROCEDURE'
        ) {
            $query .= $itemType . ' ';
        } else {
            $errors[] = sprintf(
                __('Invalid routine type: "%s"'),
                htmlspecialchars($itemType)
            );
        }
        if (! empty($itemName)) {
            $query .= Util::backquote($itemName);
        } else {
            $errors[] = __('You must provide a routine name!');
        }
        $warnedAboutLength = false;

        $itemParamName = $_POST['item_param_name'] ?? '';
        $itemParamType = $_POST['item_param_type'] ?? '';
        $itemParamLength = $_POST['item_param_length'] ?? '';
        $itemParamDir = (array) ($_POST['item_param_dir'] ?? []);
        $itemParamOpsText = (array) ($_POST['item_param_opts_text'] ?? []);
        $itemParamOpsNum = (array) ($_POST['item_param_opts_num'] ?? []);

        $params = '';
        if (! empty($itemParamName)
            && ! empty($itemParamType)
            && ! empty($itemParamLength)
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
                $warnedAboutLength// Will possibly be modified by the function
            );
        }

        $query .= '(' . $params . ') ';
        if ($itemType === 'FUNCTION') {
            $query = $this->processFunctionSpecificParameters($query, $warnedAboutLength);
        }
        if (! empty($_POST['item_comment'])) {
            $query .= "COMMENT '" . $this->dbi->escapeString($_POST['item_comment'])
                . "' ";
        }
        if (isset($_POST['item_isdeterministic'])) {
            $query .= 'DETERMINISTIC ';
        } else {
            $query .= 'NOT DETERMINISTIC ';
        }

        $itemSqlDataAccess = $_POST['item_sqldataaccess'] ?? '';
        if (! empty($itemSqlDataAccess)
            && in_array($itemSqlDataAccess, $this->sqlDataAccess)
        ) {
            $query .= $itemSqlDataAccess . ' ';
        }

        $itemSecurityType = $_POST['item_securitytype'] ?? '';
        if (! empty($itemSecurityType)) {
            if ($itemSecurityType === 'DEFINER'
                || $itemSecurityType === 'INVOKER'
            ) {
                $query .= 'SQL SECURITY ' . $itemSecurityType . ' ';
            }
        }

        $itemDefinition = $_POST['item_definition'] ?? '';
        if (! empty($itemDefinition)) {
            $query .= $itemDefinition;
        } else {
            $errors[] = __('You must provide a routine definition.');
        }

        return $query;
    }

    /**
     * @see handleExecuteRoutine
     *
     * @param array $routine The routine params
     *
     * @return string[] The SQL queries / SQL query parts
     */
    private function getQueriesFromRoutineForm(array $routine): array
    {
        $queries   = [];
        $end_query = [];
        $args      = [];
        $all_functions = $this->dbi->types->getAllFunctions();
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            if (isset($_POST['params'][$routine['item_param_name'][$i]])) {
                $value = $_POST['params'][$routine['item_param_name'][$i]];
                if (is_array($value)) { // is SET type
                    $value = implode(',', $value);
                }
                $value = $this->dbi->escapeString($value);
                if (! empty($_POST['funcs'][$routine['item_param_name'][$i]])
                    && in_array(
                        $_POST['funcs'][$routine['item_param_name'][$i]],
                        $all_functions
                    )
                ) {
                    $queries[] = 'SET @p' . $i . '='
                        . $_POST['funcs'][$routine['item_param_name'][$i]]
                        . "('" . $value . "');\n";
                } else {
                    $queries[] = 'SET @p' . $i . "='" . $value . "';\n";
                }
                $args[] = '@p' . $i;
            } else {
                $args[] = '@p' . $i;
            }
            if ($routine['item_type'] !== 'PROCEDURE') {
                continue;
            }

            if ($routine['item_param_dir'][$i] !== 'OUT'
                && $routine['item_param_dir'][$i] !== 'INOUT'
            ) {
                continue;
            }

            $end_query[] = '@p' . $i . ' AS '
                . Util::backquote($routine['item_param_name'][$i]);
        }
        if ($routine['item_type'] === 'PROCEDURE') {
            $queries[] = 'CALL ' . Util::backquote($routine['item_name'])
                        . '(' . implode(', ', $args) . ");\n";
            if (count($end_query)) {
                $queries[] = 'SELECT ' . implode(', ', $end_query) . ";\n";
            }
        } else {
            $queries[] = 'SELECT ' . Util::backquote($routine['item_name'])
                        . '(' . implode(', ', $args) . ') '
                        . 'AS ' . Util::backquote($routine['item_name'])
                        . ";\n";
        }

        return $queries;
    }

    private function handleExecuteRoutine(): void
    {
        global $db;

        // Build the queries
        $routine = $this->getDataFromName(
            $_POST['item_name'],
            $_POST['item_type'],
            false
        );
        if ($routine === null) {
            $message  = __('Error in processing request:') . ' ';
            $message .= sprintf(
                __('No routine with name %1$s found in database %2$s.'),
                htmlspecialchars(Util::backquote($_POST['item_name'])),
                htmlspecialchars(Util::backquote($db))
            );
            $message = Message::error($message);
            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);
                exit;
            }

            echo $message->getDisplay();
            unset($_POST);
            //NOTE: Missing exit ?
        }

        $queries = is_array($routine) ? $this->getQueriesFromRoutineForm($routine) : [];

        // Get all the queries as one SQL statement
        $multiple_query = implode('', $queries);

        $outcome = true;
        $affected = 0;

        // Execute query
        if (! $this->dbi->tryMultiQuery($multiple_query)) {
            $outcome = false;
        }

        // Generate output
        $output = '';
        $nbResultsetToDisplay = 0;
        if ($outcome) {
            // Pass the SQL queries through the "pretty printer"
            $output  = Generator::formatSql(implode("\n", $queries));

            // Display results
            $output .= '<fieldset><legend>';
            $output .= sprintf(
                __('Execution results of routine %s'),
                Util::backquote(htmlspecialchars($routine['item_name']))
            );
            $output .= '</legend>';

            do {
                $result = $this->dbi->storeResult();
                $num_rows = $this->dbi->numRows($result);

                if (($result !== false) && ($num_rows > 0)) {
                    $output .= '<table class="pma-table"><tr>';
                    foreach ($this->dbi->getFieldsMeta($result) as $field) {
                        $output .= '<th>';
                        $output .= htmlspecialchars($field->name);
                        $output .= '</th>';
                    }
                    $output .= '</tr>';

                    while ($row = $this->dbi->fetchAssoc($result)) {
                        $output .= '<tr>' . $this->browseRow($row) . '</tr>';
                    }

                    $output .= '</table>';
                    $nbResultsetToDisplay++;
                    $affected = $num_rows;
                }

                if (! $this->dbi->moreResults()) {
                    break;
                }

                $output .= '<br>';

                $this->dbi->freeResult($result);

                $outcome = $this->dbi->nextResult();
            } while ($outcome);
        }

        if ($outcome) {
            $output .= '</fieldset>';

            $message = __('Your SQL query has been executed successfully.');
            if ($routine['item_type'] === 'PROCEDURE') {
                $message .= '<br>';

                // TODO : message need to be modified according to the
                // output from the routine
                $message .= sprintf(
                    _ngettext(
                        '%d row affected by the last statement inside the '
                        . 'procedure.',
                        '%d rows affected by the last statement inside the '
                        . 'procedure.',
                        (int) $affected
                    ),
                    $affected
                );
            }
            $message = Message::success($message);

            if ($nbResultsetToDisplay == 0) {
                $notice = __(
                    'MySQL returned an empty result set (i.e. zero rows).'
                );
                $output .= Message::notice($notice)->getDisplay();
            }
        } else {
            $output = '';
            $message = Message::error(
                sprintf(
                    __('The following query has failed: "%s"'),
                    htmlspecialchars($multiple_query)
                )
                . '<br><br>'
                . __('MySQL said: ') . $this->dbi->getError()
            );
        }

        // Print/send output
        if ($this->response->isAjax()) {
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON('message', $message->getDisplay() . $output);
            $this->response->addJSON('dialog', false);
            exit;
        }

        echo $message->getDisplay() , $output;
        if ($message->isError()) {
            // At least one query has failed, so shouldn't
            // execute any more queries, so we quit.
            exit;
        }
        unset($_POST);
        // Now deliberately fall through to displaying the routines list
    }

    /**
     * Handles requests for executing a routine
     *
     * @return void
     */
    public function handleExecute()
    {
        global $db;

        /**
         * Handle all user requests other than the default of listing routines
         */
        if (! empty($_POST['execute_routine']) && ! empty($_POST['item_name'])) {
            $this->handleExecuteRoutine();
        } elseif (! empty($_GET['execute_dialog']) && ! empty($_GET['item_name'])) {
            /**
             * Display the execute form for a routine.
             */
            $routine = $this->getDataFromName(
                $_GET['item_name'],
                $_GET['item_type'],
                true
            );
            if ($routine !== null) {
                $form = $this->getExecuteForm($routine);
                if ($this->response->isAjax()) {
                    $title = __('Execute routine') . ' ' . Util::backquote(
                        htmlentities($_GET['item_name'], ENT_QUOTES)
                    );
                    $this->response->addJSON('message', $form);
                    $this->response->addJSON('title', $title);
                    $this->response->addJSON('dialog', true);
                } else {
                    echo "\n\n<h2>" . __('Execute routine') . "</h2>\n\n";
                    echo $form;
                }
                exit;
            }

            if ($this->response->isAjax()) {
                $message  = __('Error in processing request:') . ' ';
                $message .= sprintf(
                    __('No routine with name %1$s found in database %2$s.'),
                    htmlspecialchars(Util::backquote($_GET['item_name'])),
                    htmlspecialchars(Util::backquote($db))
                );
                $message = Message::error($message);

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);
                exit;
            }
        }
    }

    /**
     * Browse row array
     *
     * @param array $row Columns
     */
    private function browseRow(array $row): ?string
    {
        $output = null;
        foreach ($row as $value) {
            if ($value === null) {
                $value = '<i>NULL</i>';
            } else {
                $value = htmlspecialchars($value);
            }
            $output .= '<td>' . $value . '</td>';
        }

        return $output;
    }

    /**
     * Creates the HTML code that shows the routine execution dialog.
     *
     * @param array $routine Data for the routine returned by
     *                       getDataFromName()
     *
     * @return string HTML code for the routine execution dialog.
     */
    public function getExecuteForm(array $routine): string
    {
        global $db, $cfg;

        // Escape special characters
        $routine['item_name'] = htmlentities($routine['item_name'], ENT_QUOTES);
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            $routine['item_param_name'][$i] = htmlentities(
                $routine['item_param_name'][$i],
                ENT_QUOTES
            );
        }

        // Create the output
        $retval  = '';
        $retval .= "<!-- START ROUTINE EXECUTE FORM -->\n\n";
        $retval .= '<form action="' . Url::getFromRoute('/database/routines') . '" method="post"' . "\n";
        $retval .= "       class='rte_form ajax' onsubmit='return false'>\n";
        $retval .= "<input type='hidden' name='item_name'\n";
        $retval .= "       value='" . $routine['item_name'] . "'>\n";
        $retval .= "<input type='hidden' name='item_type'\n";
        $retval .= "       value='" . $routine['item_type'] . "'>\n";
        $retval .= Url::getHiddenInputs($db) . "\n";
        $retval .= "<fieldset>\n";
        if (! $this->response->isAjax()) {
            $retval .= '<legend>' . $routine['item_name'] . "</legend>\n";
            $retval .= "<table class='pma-table rte_table'>\n";
            $retval .= "<caption class='tblHeaders'>\n";
            $retval .= __('Routine parameters');
            $retval .= "</caption>\n";
        } else {
            $retval .= '<legend>' . __('Routine parameters') . "</legend>\n";
            $retval .= "<table class='pma-table rte_table'>\n";
        }
        $retval .= "<tr>\n";
        $retval .= '<th>' . __('Name') . "</th>\n";
        $retval .= '<th>' . __('Type') . "</th>\n";
        if ($cfg['ShowFunctionFields']) {
            $retval .= '<th>' . __('Function') . "</th>\n";
        }
        $retval .= '<th>' . __('Value') . "</th>\n";
        $retval .= "</tr>\n";
        // Get a list of data types that are not yet supported.
        $no_support_types = Util::unsupportedDatatypes();
        for ($i = 0; $i < $routine['item_num_params']; $i++) { // Each parameter
            if ($routine['item_type'] === 'PROCEDURE'
                && $routine['item_param_dir'][$i] === 'OUT'
            ) {
                continue;
            }
            $retval .= "\n<tr>\n";
            $retval .= '<td>' . $routine['item_param_name'][$i] . "</td>\n";
            $retval .= '<td>' . $routine['item_param_type'][$i] . "</td>\n";
            if ($cfg['ShowFunctionFields']) {
                $retval .= "<td>\n";
                if (stripos($routine['item_param_type'][$i], 'enum') !== false
                    || stripos($routine['item_param_type'][$i], 'set') !== false
                    || in_array(
                        mb_strtolower($routine['item_param_type'][$i]),
                        $no_support_types
                    )
                ) {
                    $retval .= "--\n";
                } else {
                    $field = [
                        'True_Type'       => mb_strtolower(
                            $routine['item_param_type'][$i]
                        ),
                        'Type'            => '',
                        'Key'             => '',
                        'Field'           => '',
                        'Default'         => '',
                        'first_timestamp' => false,
                    ];
                    $retval .= "<select name='funcs["
                        . $routine['item_param_name'][$i] . "]'>";
                    $retval .= Generator::getFunctionsForField($field, false, []);
                    $retval .= '</select>';
                }
                $retval .= "</td>\n";
            }
            // Append a class to date/time fields so that
            // jQuery can attach a datepicker to them
            $class = '';
            if ($routine['item_param_type'][$i] === 'DATETIME'
                || $routine['item_param_type'][$i] === 'TIMESTAMP'
            ) {
                $class = 'datetimefield';
            } elseif ($routine['item_param_type'][$i] === 'DATE') {
                $class = 'datefield';
            }
            $retval .= "<td class='nowrap'>\n";
            if (in_array($routine['item_param_type'][$i], ['ENUM', 'SET'])) {
                if ($routine['item_param_type'][$i] === 'ENUM') {
                    $input_type = 'radio';
                } else {
                    $input_type = 'checkbox';
                }
                foreach ($routine['item_param_length_arr'][$i] as $value) {
                    $value = htmlentities(Util::unQuote($value), ENT_QUOTES);
                    $retval .= "<input name='params["
                        . $routine['item_param_name'][$i] . "][]' "
                        . "value='" . $value . "' type='"
                        . $input_type . "'>"
                        . $value . "<br>\n";
                }
            } elseif (in_array(
                mb_strtolower($routine['item_param_type'][$i]),
                $no_support_types
            )) {
                $retval .= "\n";
            } else {
                $retval .= "<input class='" . $class . "' type='text' name='params["
                    . $routine['item_param_name'][$i] . "]'>\n";
            }
            $retval .= "</td>\n";
            $retval .= "</tr>\n";
        }
        $retval .= "\n</table>\n";
        if (! $this->response->isAjax()) {
            $retval .= "</fieldset>\n\n";
            $retval .= "<fieldset class='tblFooters'>\n";
            $retval .= "    <input type='submit' name='execute_routine'\n";
            $retval .= "           value='" . __('Go') . "'>\n";
            $retval .= "</fieldset>\n";
        } else {
            $retval .= "<input type='hidden' name='execute_routine' value='true'>";
            $retval .= "<input type='hidden' name='ajax_request' value='true'>";
        }
        $retval .= "</form>\n\n";
        $retval .= "<!-- END ROUTINE EXECUTE FORM -->\n\n";

        return $retval;
    }

    /**
     * Creates the contents for a row in the list of routines
     *
     * @param array  $routine  An array of routine data
     * @param string $rowClass Additional class
     *
     * @return string HTML code of a row for the list of routines
     */
    public function getRow(array $routine, $rowClass = '')
    {
        global $db, $table;

        $sqlDrop = sprintf(
            'DROP %s IF EXISTS %s',
            $routine['type'],
            Util::backquote($routine['name'])
        );

        // this is for our purpose to decide whether to
        // show the edit link or not, so we need the DEFINER for the routine
        $where = 'ROUTINE_SCHEMA ' . Util::getCollateForIS() . '='
            . "'" . $this->dbi->escapeString($db) . "' "
            . "AND SPECIFIC_NAME='" . $this->dbi->escapeString($routine['name']) . "'"
            . "AND ROUTINE_TYPE='" . $this->dbi->escapeString($routine['type']) . "'";
        $query = 'SELECT `DEFINER` FROM INFORMATION_SCHEMA.ROUTINES WHERE ' . $where . ';';
        $routineDefiner = $this->dbi->fetchValue($query);

        $currentUser = $this->dbi->getCurrentUser();

        // Since editing a procedure involved dropping and recreating, check also for
        // CREATE ROUTINE privilege to avoid lost procedures.
        $hasEditPrivilege = (Util::currentUserHasPrivilege('CREATE ROUTINE', $db)
            && $currentUser == $routineDefiner) || $this->dbi->isSuperUser();

        // There is a problem with Util::currentUserHasPrivilege():
        // it does not detect all kinds of privileges, for example
        // a direct privilege on a specific routine. So, at this point,
        // we show the Execute link, hoping that the user has the correct rights.
        // Also, information_schema might be hiding the ROUTINE_DEFINITION
        // but a routine with no input parameters can be nonetheless executed.

        // Check if the routine has any input parameters. If it does,
        // we will show a dialog to get values for these parameters,
        // otherwise we can execute it directly.

        $definition = $this->dbi->getDefinition(
            $db,
            $routine['type'],
            $routine['name']
        );
        $hasExecutePrivilege = Util::currentUserHasPrivilege('EXECUTE', $db);
        $executeAction = '';

        if ($definition !== null) {
            $parser = new Parser($definition);

            /**
             * @var CreateStatement $stmt
             */
            $stmt = $parser->statements[0];

            $params = Routine::getParameters($stmt);

            if ($hasExecutePrivilege) {
                $executeAction = 'execute_routine';
                for ($i = 0; $i < $params['num']; $i++) {
                    if ($routine['type'] === 'PROCEDURE'
                        && $params['dir'][$i] === 'OUT'
                    ) {
                        continue;
                    }
                    $executeAction = 'execute_dialog';
                    break;
                }
            }
        }

        $hasExportPrivilege = (Util::currentUserHasPrivilege('CREATE ROUTINE', $db)
            && $currentUser == $routineDefiner) || $this->dbi->isSuperUser();

        return $this->template->render('database/routines/row', [
            'db' => $db,
            'table' => $table,
            'sql_drop' => $sqlDrop,
            'routine' => $routine,
            'row_class' => $rowClass,
            'has_edit_privilege' => $hasEditPrivilege,
            'has_export_privilege' => $hasExportPrivilege,
            'has_execute_privilege' => $hasExecutePrivilege,
            'execute_action' => $executeAction,
        ]);
    }

    /**
     * @param resource|bool $result          Query result
     * @param string        $createStatement Query
     * @param array         $errors          Errors
     *
     * @return array
     */
    private function checkResult($result, $createStatement, array $errors)
    {
        if ($result) {
            return $errors;
        }

        // OMG, this is really bad! We dropped the query,
        // failed to create a new one
        // and now even the backup query does not execute!
        // This should not happen, but we better handle
        // this just in case.
        $errors[] = __('Sorry, we failed to restore the dropped routine.') . '<br>'
            . __('The backed up query was:')
            . '"' . htmlspecialchars($createStatement) . '"<br>'
            . __('MySQL said: ') . $this->dbi->getError();

        return $errors;
    }

    public function export(): void
    {
        global $db;

        if (empty($_GET['export_item']) || empty($_GET['item_name']) || empty($_GET['item_type'])) {
            return;
        }

        if ($_GET['item_type'] !== 'FUNCTION' && $_GET['item_type'] !== 'PROCEDURE') {
            return;
        }

        $routineDefinition = $this->dbi->getDefinition($db, $_GET['item_type'], $_GET['item_name']);
        $exportData = false;

        if ($routineDefinition !== null) {
            $exportData = "DELIMITER $$\n" . $routineDefinition . "$$\nDELIMITER ;\n";
        }

        $itemName = htmlspecialchars(Util::backquote($_GET['item_name']));
        if ($exportData !== false) {
            $exportData = htmlspecialchars(trim($exportData));
            $title = sprintf(__('Export of routine %s'), $itemName);

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $exportData);
                $this->response->addJSON('title', $title);

                exit;
            }

            $exportData = '<textarea cols="40" rows="15" style="width: 100%;">'
                . $exportData . '</textarea>';
            echo "<fieldset>\n" . '<legend>' . $title . "</legend>\n"
                . $exportData . "</fieldset>\n";

            return;
        }

        $message = sprintf(
            __(
                'Error in processing request: No routine with name %1$s found in database %2$s.'
                . ' You might be lacking the necessary privileges to view/export this routine.'
            ),
            $itemName,
            htmlspecialchars(Util::backquote($db))
        );
        $message = Message::error($message);

        if ($this->response->isAjax()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);

            exit;
        }

        echo $message->getDisplay();
    }
}
