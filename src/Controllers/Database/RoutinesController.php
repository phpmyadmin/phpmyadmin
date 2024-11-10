<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;

use function __;
use function htmlentities;
use function htmlspecialchars;
use function in_array;
use function mb_strtoupper;
use function sprintf;
use function trim;

use const ENT_QUOTES;

/**
 * Routines management.
 */
final class RoutinesController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
        private readonly DatabaseInterface $dbi,
        private readonly Routines $routines,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['errors'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->response->addScriptFiles(['database/routines.js', 'sql.js']);

        $type = $_REQUEST['type'] ?? null;

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $config = Config::getInstance();
        if (! $request->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (Current::$table !== '' && in_array(Current::$table, $this->dbi->getTables(Current::$database), true)) {
                if (! $this->response->checkParameters(['db', 'table'])) {
                    return $this->response->response();
                }

                UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];
                $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
                $GLOBALS['errorUrl'] .= Url::getCommon(UrlParams::$params, '&');

                $databaseName = DatabaseName::tryFrom($request->getParam('db'));
                if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                    $this->response->redirectToRoute(
                        '/',
                        ['reload' => true, 'message' => __('No databases selected.')],
                    );

                    return $this->response->response();
                }

                $tableName = TableName::tryFrom($request->getParam('table'));
                if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                    $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);

                    return $this->response->response();
                }
            } else {
                Current::$table = '';

                if (! $this->response->checkParameters(['db'])) {
                    return $this->response->response();
                }

                $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
                    $config->settings['DefaultTabDatabase'],
                    'database',
                );
                $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

                $databaseName = DatabaseName::tryFrom($request->getParam('db'));
                if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                    $this->response->redirectToRoute(
                        '/',
                        ['reload' => true, 'message' => __('No databases selected.')],
                    );

                    return $this->response->response();
                }
            }
        } elseif (Current::$database !== '') {
            $this->dbi->selectDb(Current::$database);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $GLOBALS['errors'] = [];
        $GLOBALS['message'] ??= null;

        if (! empty($_POST['editor_process_add']) || ! empty($_POST['editor_process_edit'])) {
            $output = $this->routines->handleRequestCreateOrEdit($userPrivileges, Current::$database);
            if ($request->isAjax()) {
                if (! $GLOBALS['message']->isSuccess()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $output);

                    return $this->response->response();
                }

                $routines = Routines::getDetails(
                    $this->dbi,
                    Current::$database,
                    $_POST['item_type'],
                    $_POST['item_name'],
                );
                $routine = $routines[0];
                $this->response->addJSON(
                    'name',
                    htmlspecialchars(
                        mb_strtoupper($_POST['item_name']),
                    ),
                );
                $this->response->addJSON(
                    'new_row',
                    $this->template->render('database/routines/row', $this->routines->getRow($routine)),
                );
                $this->response->addJSON('insert', true);
                $this->response->addJSON('message', $output);
                $this->response->addJSON('tableType', 'routines');

                return $this->response->response();
            }
        }

        /**
         * Display a form used to add/edit a routine, if necessary
         */
        // FIXME: this must be simpler than that
        if (
            $GLOBALS['errors'] !== []
            || empty($_POST['editor_process_add'])
            && empty($_POST['editor_process_edit'])
            && (
                ! empty($_REQUEST['add_item'])
                || ! empty($_REQUEST['edit_item'])
                || ! empty($_POST['routine_addparameter'])
                || ! empty($_POST['routine_removeparameter'])
                || ! empty($_POST['routine_changetype'])
            )
        ) {
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
                $routine = $this->routines->getDataFromRequest();
                $mode = 'add';
            } elseif (! empty($_REQUEST['edit_item'])) {
                $title = __('Edit routine');
                if ($operation === '' && ! empty($_GET['item_name']) && empty($_POST['editor_process_edit'])) {
                    $routine = $this->routines->getDataFromName($_GET['item_name'], $_GET['item_type']);
                    if ($routine !== null) {
                        $routine['item_original_name'] = $routine['item_name'];
                        $routine['item_original_type'] = $routine['item_type'];
                    }
                } else {
                    $routine = $this->routines->getDataFromRequest();
                }

                $mode = 'edit';
            }

            if ($routine !== null) {
                // Show form
                for ($i = 0; $i < $routine['item_num_params']; $i++) {
                    $routine['item_param_name'][$i] = htmlentities($routine['item_param_name'][$i], ENT_QUOTES);
                    $routine['item_param_length'][$i] = htmlentities($routine['item_param_length'][$i], ENT_QUOTES);
                }

                // Handle some logic first
                if ($operation === 'change') {
                    if ($routine['item_type'] === 'PROCEDURE') {
                        $routine['item_type'] = 'FUNCTION';
                        $routine['item_type_toggle'] = 'PROCEDURE';
                    } else {
                        $routine['item_type'] = 'PROCEDURE';
                        $routine['item_type_toggle'] = 'FUNCTION';
                    }
                } elseif (
                    $operation === 'add'
                    || ($routine['item_num_params'] == 0 && $mode === 'add' && ! $GLOBALS['errors'])
                ) {
                    $routine['item_param_dir'][] = '';
                    $routine['item_param_name'][] = '';
                    $routine['item_param_type'][] = '';
                    $routine['item_param_length'][] = '';
                    $routine['item_param_opts_num'][] = '';
                    $routine['item_param_opts_text'][] = '';
                    $routine['item_num_params']++;
                } elseif ($operation === 'remove') {
                    unset(
                        $routine['item_param_dir'][$routine['item_num_params'] - 1],
                        $routine['item_param_name'][$routine['item_num_params'] - 1],
                        $routine['item_param_type'][$routine['item_num_params'] - 1],
                        $routine['item_param_length'][$routine['item_num_params'] - 1],
                        $routine['item_param_opts_num'][$routine['item_num_params'] - 1],
                        $routine['item_param_opts_text'][$routine['item_num_params'] - 1],
                    );
                    $routine['item_num_params']--;
                }

                $parameterRows = '';
                for ($i = 0; $i < $routine['item_num_params']; $i++) {
                    $parameterRows .= $this->template->render(
                        'database/routines/parameter_row',
                        $this->routines->getParameterRow(
                            $routine,
                            $i,
                            $routine['item_type'] === 'FUNCTION' ? ' hide' : '',
                        ),
                    );
                }

                $charsets = Charsets::getCharsets($this->dbi, $config->selectedServer['DisableIS']);

                $editor = $this->template->render('database/routines/editor_form', [
                    'db' => Current::$database,
                    'routine' => $routine,
                    'is_edit_mode' => $mode === 'edit',
                    'is_ajax' => $request->isAjax(),
                    'parameter_rows' => $parameterRows,
                    'charsets' => $charsets,
                    'numeric_options' => $this->routines->numericOptions,
                    'has_privileges' => $userPrivileges->routines && $userPrivileges->isReload,
                    'sql_data_access' => $this->routines->sqlDataAccess,
                ]);

                if ($request->isAjax()) {
                    $this->response->addJSON('message', $editor);
                    $this->response->addJSON('title', $title);
                    $this->response->addJSON(
                        'paramTemplate',
                        $this->template->render('database/routines/parameter_row', $this->routines->getParameterRow()),
                    );
                    $this->response->addJSON('type', $routine['item_type']);

                    return $this->response->response();
                }

                $this->response->addHTML("\n\n<h2>" . $title . "</h2>\n\n" . $editor);

                return $this->response->response();
            }

            $message = __('Error in processing request:') . ' ';
            $message .= sprintf(
                __(
                    'No routine with name %1$s found in database %2$s. '
                    . 'You might be lacking the necessary privileges to edit this routine.',
                ),
                htmlspecialchars(
                    Util::backquote($_REQUEST['item_name']),
                ),
                htmlspecialchars(Util::backquote(Current::$database)),
            );

            $message = Message::error($message);
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);

                return $this->response->response();
            }

            $this->response->addHTML($message->getDisplay());
        }

        /**
         * Handle all user requests other than the default of listing routines
         */
        if (! empty($_POST['execute_routine']) && ! empty($_POST['item_name'])) {
            // Build the queries
            $routine = $this->routines->getDataFromName($_POST['item_name'], $_POST['item_type'], false);
            if ($routine === null) {
                $message = __('Error in processing request:') . ' ';
                $message .= sprintf(
                    __('No routine with name %1$s found in database %2$s.'),
                    htmlspecialchars(Util::backquote($_POST['item_name'])),
                    htmlspecialchars(Util::backquote(Current::$database)),
                );
                $message = Message::error($message);
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $message);

                    return $this->response->response();
                }

                $this->response->addHTML($message->getDisplay());

                return $this->response->response();
            }

            [$output, $message] = $this->routines->handleExecuteRoutine($routine);

            // Print/send output
            if ($request->isAjax()) {
                $this->response->setRequestStatus($message->isSuccess());
                $this->response->addJSON('message', $message->getDisplay() . $output);
                $this->response->addJSON('dialog', false);

                return $this->response->response();
            }

            $this->response->addHTML($message->getDisplay() . $output);
            if ($message->isError()) {
                // At least one query has failed, so shouldn't
                // execute any more queries, so we quit.
                return $this->response->response();
            }
        } elseif (! empty($_GET['execute_dialog']) && ! empty($_GET['item_name'])) {
            /**
             * Display the execute form for a routine.
             */
            $routine = $this->routines->getDataFromName($_GET['item_name'], $_GET['item_type']);
            if ($routine !== null) {
                [$routine, $params] = $this->routines->getExecuteForm($routine);
                $form = $this->template->render('database/routines/execute_form', [
                    'db' => Current::$database,
                    'routine' => $routine,
                    'ajax' => $request->isAjax(),
                    'show_function_fields' => $config->settings['ShowFunctionFields'],
                    'params' => $params,
                ]);
                if ($request->isAjax()) {
                    $title = __('Execute routine') . ' ' . Util::backquote(
                        htmlentities($_GET['item_name'], ENT_QUOTES),
                    );
                    $this->response->addJSON('message', $form);
                    $this->response->addJSON('title', $title);
                    $this->response->addJSON('dialog', true);

                    return $this->response->response();
                }

                $this->response->addHTML("\n\n<h2>" . __('Execute routine') . "</h2>\n\n");
                $this->response->addHTML($form);

                return $this->response->response();
            }

            if ($request->isAjax()) {
                $message = __('Error in processing request:') . ' ';
                $message .= sprintf(
                    __('No routine with name %1$s found in database %2$s.'),
                    htmlspecialchars(Util::backquote($_GET['item_name'])),
                    htmlspecialchars(Util::backquote(Current::$database)),
                );
                $message = Message::error($message);

                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);

                return $this->response->response();
            }
        }

        /** @var mixed $routineType */
        $routineType = $request->getQueryParam('item_type');
        if (
            ! empty($_GET['export_item'])
            && ! empty($_GET['item_name'])
            && in_array($routineType, ['FUNCTION', 'PROCEDURE'], true)
        ) {
            if ($routineType === 'FUNCTION') {
                $routineDefinition = Routines::getFunctionDefinition(
                    $this->dbi,
                    Current::$database,
                    $_GET['item_name'],
                );
            } else {
                $routineDefinition = Routines::getProcedureDefinition(
                    $this->dbi,
                    Current::$database,
                    $_GET['item_name'],
                );
            }

            $exportData = false;

            if ($routineDefinition !== null) {
                $exportData = "DELIMITER $$\n" . $routineDefinition . "$$\nDELIMITER ;\n";
            }

            $itemName = htmlspecialchars(Util::backquote($_GET['item_name']));
            if ($exportData !== false) {
                $exportData = htmlspecialchars(trim($exportData));
                $title = sprintf(__('Export of routine %s'), $itemName);

                if ($request->isAjax()) {
                    $this->response->addJSON('message', $exportData);
                    $this->response->addJSON('title', $title);

                    return $this->response->response();
                }

                $output = '<div class="container">';
                $output .= '<h2>' . $title . '</h2>';
                $output .= '<div class="card"><div class="card-body">';
                $output .= '<textarea rows="15" class="form-control">' . $exportData . '</textarea>';
                $output .= '</div></div></div>';

                $this->response->addHTML($output);
            } else {
                $message = sprintf(
                    __(
                        'Error in processing request: No routine with name %1$s found in database %2$s.'
                        . ' You might be lacking the necessary privileges to view/export this routine.',
                    ),
                    $itemName,
                    htmlspecialchars(Util::backquote(Current::$database)),
                );
                $message = Message::error($message);

                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $message);

                    return $this->response->response();
                }

                $this->response->addHTML($message->getDisplay());
            }
        }

        if (! isset($type) || ! in_array($type, ['FUNCTION', 'PROCEDURE'], true)) {
            $type = null;
        }

        $items = Routines::getDetails($this->dbi, Current::$database, $type);
        $isAjax = $request->isAjax() && empty($_REQUEST['ajax_page_request']);

        $rows = '';
        foreach ($items as $item) {
            $rows .= $this->template->render(
                'database/routines/row',
                $this->routines->getRow($item, $isAjax ? 'ajaxInsert hide' : ''),
            );
        }

        $this->response->render('database/routines/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'has_any_routines' => $items !== [],
            'rows' => $rows,
            'has_privilege' => Util::currentUserHasPrivilege('CREATE ROUTINE', Current::$database, Current::$table),
        ]);

        return $this->response->response();
    }
}
