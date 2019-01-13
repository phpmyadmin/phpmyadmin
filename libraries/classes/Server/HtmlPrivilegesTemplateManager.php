<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Template manager for the Privileges section in pma
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Core;
use PhpMyAdmin\Display\ChangePassword;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Template manager for the Privileges section in pma
 *
 * @package PhpMyAdmin
 */
class HtmlPrivilegesTemplateManager implements PrivilegesTemplateManagerInterface
{
    /** @var Template */
    protected $template;

    /** @var Privileges */
    protected $privileges;

    /**
     * HtmlPrivilegesTemplateManager constructor.
     *
     * @param Privileges $privileges Privileges class
     * @param Template   $template   Template to use
     */
    public function __construct(Privileges $privileges, Template $template)
    {
        $this->privileges = $privileges;
        $this->template = $template;
    }

    /**
     * Get HTML for display Add userfieldset
     *
     * @param string $db    the database
     * @param string $table the table name
     *
     * @return string html output
     */
    public function getAddUserFieldset(string $db = '', string $table = ''): string
    {
        if (! $GLOBALS['is_createuser']) {
            return '';
        }
        $relParams = [];
        $urlParams = [
            'adduser' => 1,
        ];
        if (! empty($db)) {
            $urlParams['dbname']
                = $relParams['checkprivsdb']
                = $db;
        }
        if (! empty($table)) {
            $urlParams['tablename']
                = $relParams['checkprivstable']
                = $table;
        }

        return $this->template->render('server/privileges/add_user_fieldset', [
            'url_params' => $urlParams,
            'rel_params' => $relParams,
        ]);
    }

    /**
     * Displays on which column(s) a table-specific privilege is granted
     *
     * @param array  $columns        columns array
     * @param array  $row            first row from result or boolean false
     * @param string $nameForSelect  privilege types - Select_priv, Insert_priv
     *                               Update_priv, References_priv
     * @param string $privForHeader  privilege for header
     * @param string $name           privilege name: insert, select, update, references
     * @param string $nameForDfn     name for dfn
     * @param string $nameForCurrent name for current
     *
     * @return string html snippet
     */
    public function getColumnPrivileges(
        array $columns,
        array $row,
        string $nameForSelect,
        string $privForHeader,
        string $name,
        string $nameForDfn,
        string $nameForCurrent
    ): string {
        return $this->template->render('server/privileges/column_privileges', [
            'columns' => $columns,
            'row' => $row,
            'name_for_select' => $nameForSelect,
            'priv_for_header' => $privForHeader,
            'name' => $name,
            'name_for_dfn' => $nameForDfn,
            'name_for_current' => $nameForCurrent,
        ]);
    }

    /**
     * Get HTML snippet for privileges that are attached to a specific column
     *
     * @param array $columns columns array
     * @param array $row     first row from result or boolean false
     *
     * @return string
     */
    public function getAttachedPrivilegesToTableSpecificColumn(array $columns, array $row): string
    {
        $htmlOutput = $this->getColumnPrivileges(
            $columns,
            $row,
            'Select_priv',
            'SELECT',
            'select',
            __('Allows reading data.'),
            'Select'
        );

        $htmlOutput .= $this->getColumnPrivileges(
            $columns,
            $row,
            'Insert_priv',
            'INSERT',
            'insert',
            __('Allows inserting and replacing data.'),
            'Insert'
        );

        $htmlOutput .= $this->getColumnPrivileges(
            $columns,
            $row,
            'Update_priv',
            'UPDATE',
            'update',
            __('Allows changing data.'),
            'Update'
        );

        $htmlOutput .= $this->getColumnPrivileges(
            $columns,
            $row,
            'References_priv',
            'REFERENCES',
            'references',
            __('Has no effect in this MySQL version.'),
            'References'
        );
        return $htmlOutput;
    }

    /**
     * Get HTML for privileges that are not attached to a specific column
     *
     * @param array $row first row from result or boolean false
     *
     * @return string
     */
    public function getNotAttachedPrivilegesToTableSpecificColumn(array $row): string
    {
        $htmlOutput = '';

        foreach ($row as $currentGrant => $currentGrantValue) {
            $grantType = substr($currentGrant, 0, -5);
            if (in_array($grantType, ['Select', 'Insert', 'Update', 'References'])
            ) {
                continue;
            }
            // make a substitution to match the messages variables;
            // also we must substitute the grant we get, because we can't generate
            // a form variable containing blanks (those would get changed to
            // an underscore when receiving the POST)
            if ($currentGrant === 'Create View_priv') {
                $tmpCurrentGrant = 'CreateView_priv';
                $currentGrant = 'Create_view_priv';
            } elseif ($currentGrant === 'Show view_priv') {
                $tmpCurrentGrant = 'ShowView_priv';
                $currentGrant = 'Show_view_priv';
            } else {
                $tmpCurrentGrant = $currentGrant;
            }

            $htmlOutput .= '<div class="item">' . "\n"
               . '<input type="checkbox"'
               . ' name="' . $currentGrant . '" id="checkbox_' . $currentGrant
               . '" value="Y" '
               . ($currentGrantValue === 'Y' ? 'checked="checked" ' : '')
               . 'title="';

            $privGlobalName = 'strPrivDesc'
                . mb_substr(
                    $tmpCurrentGrant,
                    0,
                    -5
                );
            $htmlOutput .= ($GLOBALS[$privGlobalName] ?? $GLOBALS[$privGlobalName . 'Tbl']
                )
                . '">' . "\n";

            $privGlobalName1 = 'strPrivDesc'
                . mb_substr(
                    $tmpCurrentGrant,
                    0,
                    - 5
                );
            $htmlOutput .= '<label for="checkbox_' . $currentGrant
                . '"><code><dfn title="'
                . ($GLOBALS[$privGlobalName1] ?? $GLOBALS[$privGlobalName1 . 'Tbl']
                )
                . '">'
                . mb_strtoupper(
                    mb_substr(
                        $currentGrant,
                        0,
                        -5
                    )
                )
                . '</dfn></code></label>' . "\n"
                . '</div>' . "\n";
        }
        return $htmlOutput;
    }

    /**
     * Get content for global privileges table with check boxes
     *
     * @param array $privTable      privileges table array
     * @param array $privTableNames names of the privilege tables
     *                              (Data, Structure, Administration)
     * @param array $row            first row from result or boolean false
     *
     * @return string
     */
    public function getGlobalPrivTableWithCheckboxes(
        array $privTable,
        array $privTableNames,
        array $row
    ): string {
        return $this->template->render('server/privileges/global_priv_table', [
            'priv_table' => $privTable,
            'priv_table_names' => $privTableNames,
            'row' => $row,
        ]);
    }

    /**
     * Get HTML snippet for privileges table head
     *
     * @return string
     */
    public function getPrivsTableHead(): string
    {
        return '<thead>'
            . '<tr>'
            . '<th></th>'
            . '<th>' . __('User name') . '</th>'
            . '<th>' . __('Host name') . '</th>'
            . '<th>' . __('Type') . '</th>'
            . '<th>' . __('Privileges') . '</th>'
            . '<th>' . __('Grant') . '</th>'
            . '<th colspan="2">' . __('Action') . '</th>'
            . '</tr>'
            . '</thead>';
    }

    /**
     * Get HTML error for View Users form
     * For non superusers such as grant/create users
     *
     * @return string
     */
    public function getViewUsersError(): string
    {
        return Message::error(
            __('Not enough privilege to view users.')
        )->getDisplay();
    }

    /**
     * Get HTML for "Require"
     *
     * @param array $row privilege array
     *
     * @return string html snippet
     */
    public function getRequires(array $row): string
    {
        $specified = (isset($row['ssl_type']) && $row['ssl_type'] === 'SPECIFIED');
        $requireOptions = [
            [
                'name'        => 'ssl_type',
                'value'       => 'NONE',
                'description' => __(
                    'Does not require SSL-encrypted connections.'
                ),
                'label'       => 'REQUIRE NONE',
                'checked'     => isset($row['ssl_type'])
                    && ($row['ssl_type'] === 'NONE'
                        || $row['ssl_type'] === '')
                    ? 'checked="checked"'
                    : '',
                'disabled'    => false,
                'radio'       => true,
            ],
            [
                'name'        => 'ssl_type',
                'value'       => 'ANY',
                'description' => __(
                    'Requires SSL-encrypted connections.'
                ),
                'label'       => 'REQUIRE SSL',
                'checked'     => isset($row['ssl_type']) && $row['ssl_type'] === 'ANY'
                    ? 'checked="checked"'
                    : '',
                'disabled'    => false,
                'radio'       => true,
            ],
            [
                'name'        => 'ssl_type',
                'value'       => 'X509',
                'description' => __(
                    'Requires a valid X509 certificate.'
                ),
                'label'       => 'REQUIRE X509',
                'checked'     => isset($row['ssl_type']) && $row['ssl_type'] === 'X509'
                    ? 'checked="checked"'
                    : '',
                'disabled'    => false,
                'radio'       => true,
            ],
            [
                'name'        => 'ssl_type',
                'value'       => 'SPECIFIED',
                'description' => '',
                'label'       => 'SPECIFIED',
                'checked'     => $specified ? 'checked="checked"' : '',
                'disabled'    => false,
                'radio'       => true,
            ],
            [
                'name'        => 'ssl_cipher',
                'value'       => isset($row['ssl_cipher'])
                    ? htmlspecialchars($row['ssl_cipher']) : '',
                'description' => __(
                    'Requires that a specific cipher method be used for a connection.'
                ),
                'label'       => 'REQUIRE CIPHER',
                'checked'     => '',
                'disabled'    => ! $specified,
                'radio'       => false,
            ],
            [
                'name'        => 'x509_issuer',
                'value'       => isset($row['x509_issuer'])
                    ? htmlspecialchars($row['x509_issuer']) : '',
                'description' => __(
                    'Requires that a valid X509 certificate issued by this CA be presented.'
                ),
                'label'       => 'REQUIRE ISSUER',
                'checked'     => '',
                'disabled'    => ! $specified,
                'radio'       => false,
            ],
            [
                'name'        => 'x509_subject',
                'value'       => isset($row['x509_subject'])
                    ? htmlspecialchars($row['x509_subject']) : '',
                'description' => __(
                    'Requires that a valid X509 certificate with this subject be presented.'
                ),
                'label'       => 'REQUIRE SUBJECT',
                'checked'     => '',
                'disabled'    => ! $specified,
                'radio'       => false,
            ],
        ];

        return $this->template->render('server/privileges/require_options', [
            'require_options' => $requireOptions,
        ]);
    }

    /**
     * Get HTML for "Resource limits"
     *
     * @param array $row first row from result or boolean false
     *
     * @return string html snippet
     */
    public function getResourceLimits(array $row): string
    {
        $limits = [
            [
                'input_name'  => 'max_questions',
                'name_main'   => 'MAX QUERIES PER HOUR',
                'value'       => $row['max_questions'] ?? '0',
                'description' => __(
                    'Limits the number of queries the user may send to the server per hour.'
                ),
            ],
            [
                'input_name'  => 'max_updates',
                'name_main'   => 'MAX UPDATES PER HOUR',
                'value'       => $row['max_updates'] ?? '0',
                'description' => __(
                    'Limits the number of commands that change any table '
                    . 'or database the user may execute per hour.'
                ),
            ],
            [
                'input_name'  => 'max_connections',
                'name_main'   => 'MAX CONNECTIONS PER HOUR',
                'value'       => $row['max_connections'] ?? '0',
                'description' => __(
                    'Limits the number of new connections the user may open per hour.'
                ),
            ],
            [
                'input_name'  => 'max_user_connections',
                'name_main'   => 'MAX USER_CONNECTIONS',
                'value'       => $row['max_user_connections'] ?? '0',
                'description' => __(
                    'Limits the number of simultaneous connections '
                    . 'the user may have.'
                ),
            ],
        ];

        $htmlOutput = $this->template->render('server/privileges/resource_limits', [
            'limits' => $limits,
        ]);

        $htmlOutput .= '</fieldset>' . "\n";

        return $htmlOutput;
    }

    /**
     * Get HTML for global or database specific privileges
     *
     * @param string $db    the database
     * @param string $table the table
     * @param array  $row   first row from result or boolean false
     *
     * @return string
     */
    public function getGlobalOrDbSpecificPrivs($db, $table, array $row): string
    {
        $privTableKeys = [
            0 => __('Data'),
            1 => __('Structure'),
            2 => __('Administration'),
        ];
        $privTableValues = [];
        $privTableValues[0] = $this->privileges->getDataPrivilegeTable($db);
        $privTableValues[1] = $this->privileges->getStructurePrivilegeTable($table, $row);
        $privTableValues[2] = $this->privileges->getAdministrationPrivilegeTable($db);

        $htmlOutput = '<input type="hidden" name="grant_count" value="'
            . (count($privTableValues[0])
                + count($privTableValues[1])
                + count($privTableValues[2])
                - (isset($row['Grant_priv']) ? 1 : 0)
            )
            . '">';
        if ($db === '*') {
            $legend     = __('Global privileges');
            $menuLabel = __('Global');
        } elseif ($table === '*') {
            $legend     = __('Database-specific privileges');
            $menuLabel = __('Database');
        } else {
            $legend     = __('Table-specific privileges');
            $menuLabel = __('Table');
        }
        $htmlOutput .= '<fieldset id="fieldset_user_global_rights">'
            . '<legend data-submenu-label="' . $menuLabel . '">' . $legend
            . '<input type="checkbox" id="addUsersForm_checkall" '
            . 'class="checkall_box" title="' . __('Check all') . '"> '
            . '<label for="addUsersForm_checkall">' . __('Check all') . '</label> '
            . '</legend>'
            . '<p><small><i>'
            . __('Note: MySQL privilege names are expressed in English.')
            . '</i></small></p>';

        // Output the Global privilege tables with checkboxes
        $htmlOutput .= $this->getGlobalPrivTableWithCheckboxes(
            $privTableValues,
            $privTableKeys,
            $row
        );

        // The "Resource limits" box is not displayed for db-specific privs
        if ($db === '*') {
            $htmlOutput .= $this->getResourceLimits($row);
            $htmlOutput .= $this->getRequires($row);
        }
        // for Safari 2.0.2
        $htmlOutput .= '<div class="clearfloat"></div>';

        return $htmlOutput;
    }

    /**
     * Gets the currently active authentication plugins
     *
     * @param string $origAuthPlugin Default Authentication plugin
     * @param string $mode           are we creating a new user or are we just
     *                               changing  one?
     *                               (allowed values: 'new', 'edit', 'change_pw')
     * @param string $versions       Is MySQL version newer or older than 5.5.7
     *
     * @return string
     */
    public function getAuthPluginsDropdown($origAuthPlugin, $mode = 'new', $versions = 'new'): string
    {
        $selectId = 'select_authentication_plugin'
            . ($mode === 'change_pw' ? '_cp' : '');

        if ($versions === 'new') {
            $activeAuthPlugins = $this->privileges->getActiveAuthPlugins();

            if (isset($activeAuthPlugins['mysql_old_password'])) {
                unset($activeAuthPlugins['mysql_old_password']);
            }
        } else {
            $activeAuthPlugins = [
                'mysql_native_password' => __('Native MySQL authentication'),
            ];
        }

        $htmlOutput = Util::getDropdown(
            'authentication_plugin',
            $activeAuthPlugins,
            $origAuthPlugin,
            $selectId
        );

        return $htmlOutput;
    }

    /**
     * Get HTML header for display User's properties
     *
     * @param boolean      $dbnameIsWildcard whether database name is wildcard or not
     * @param string       $urlDbname        url database name that urlencode() string
     * @param array|string $dbname           database name
     * @param string       $username         username
     * @param string       $hostname         host name
     * @param string       $entityName       entity (table or routine) name
     * @param string       $entityType       optional, type of entity ('table' or 'routine')
     *
     * @return string
     */
    public function getHeaderForUserProperties(
        $dbnameIsWildcard,
        $urlDbname,
        $dbname,
        $username,
        $hostname,
        $entityName,
        $entityType = 'table'
    ): string {
        $htmlOutput = '<h2>' . "\n"
           . Util::getIcon('b_usredit')
           . __('Edit privileges:') . ' '
           . __('User account');

        if (! empty($dbname)) {
            $htmlOutput .= ' <i><a class="edit_user_anchor"'
                . ' href="server_privileges.php'
                . Url::getCommon(
                    [
                        'username' => $username,
                        'hostname' => $hostname,
                        'dbname' => '',
                        'tablename' => '',
                    ]
                )
                . '">\'' . htmlspecialchars($username)
                . '\'@\'' . htmlspecialchars($hostname)
                . '\'</a></i>' . "\n";

            $htmlOutput .= ' - ';
            $htmlOutput .= $dbnameIsWildcard
                || (is_array($dbname) && count($dbname) > 1)
                ? __('Databases') : __('Database');
            if (! empty($entityName) && $entityType === 'table') {
                $htmlOutput .= ' <i><a href="server_privileges.php'
                    . Url::getCommon(
                        [
                            'username' => $username,
                            'hostname' => $hostname,
                            'dbname' => $urlDbname,
                            'tablename' => '',
                        ]
                    )
                    . '">' . htmlspecialchars($dbname)
                    . '</a></i>';

                $htmlOutput .= ' - ' . __('Table')
                    . ' <i>' . htmlspecialchars($entityName) . '</i>';
            } elseif (! empty($entityName)) {
                $htmlOutput .= ' <i><a href="server_privileges.php'
                    . Url::getCommon(
                        [
                            'username' => $username,
                            'hostname' => $hostname,
                            'dbname' => $urlDbname,
                            'routinename' => '',
                        ]
                    )
                    . '">' . htmlspecialchars($dbname)
                    . '</a></i>';

                $htmlOutput .= ' - ' . __('Routine')
                    . ' <i>' . htmlspecialchars($entityName) . '</i>';
            } else {
                if (! is_array($dbname)) {
                    $dbname = [$dbname];
                }
                $htmlOutput .= ' <i>'
                    . htmlspecialchars(implode(', ', $dbname))
                    . '</i>';
            }
        } else {
            $htmlOutput .= ' <i>\'' . htmlspecialchars($username)
                . '\'@\'' . htmlspecialchars($hostname)
                . '\'</i>' . "\n";
        }
        $htmlOutput .= '</h2>' . "\n";
        $currentUser = $this->privileges->getCurrentUser();
        $user = $username . '@' . $hostname;
        // Add a short notice for the user
        // to remind him that he is editing his own privileges
        if ($user === $currentUser) {
            $htmlOutput .= Message::notice(
                __(
                    'Note: You are attempting to edit privileges of the '
                    . 'user with which you are currently logged in.'
                )
            )->getDisplay();
        }
        return $htmlOutput;
    }

    /**
     * Displays the fields used by the "new user" form as well as the
     * "change login information / copy user" form.
     *
     * @param string $mode     are we creating a new user or are we just
     *                         changing  one? (allowed values: 'new', 'change')
     * @param string $username User name
     * @param string $hostname Host name
     *
     * @global  array      $cfg     the phpMyAdmin configuration
     * @global  resource   $user_link the database connection
     *
     * @return string  a HTML snippet
     */
    public function getHtmlForLoginInformationFields(
        $mode = 'new',
        $username = null,
        $hostname = null
    ): string {
        list($usernameLength, $hostnameLength) = $this->privileges->getUsernameAndHostnameLength();

        if (isset($GLOBALS['username']) && '' === $GLOBALS['username']) {
            $GLOBALS['pred_username'] = 'any';
        }
        $htmlOutput = '<fieldset id="fieldset_add_user_login">' . "\n"
            . '<legend>' . __('Login Information') . '</legend>' . "\n"
            . '<div class="item">' . "\n"
            . '<label for="select_pred_username">' . "\n"
            . '    ' . __('User name:') . "\n"
            . '</label>' . "\n"
            . '<span class="options">' . "\n";

        $htmlOutput .= '<select name="pred_username" id="select_pred_username" '
            . 'title="' . __('User name') . '">' . "\n";

        $htmlOutput .= '<option value="any"'
            . (isset($GLOBALS['pred_username']) && $GLOBALS['pred_username'] === 'any'
                ? ' selected="selected"'
                : '') . '>'
            . __('Any user')
            . '</option>' . "\n";

        $htmlOutput .= '<option value="userdefined"'
            . (! isset($GLOBALS['pred_username'])
                    || $GLOBALS['pred_username'] === 'userdefined'
                ? ' selected="selected"'
                : '') . '>'
            . __('Use text field')
            . ':</option>' . "\n";

        $htmlOutput .= '</select>' . "\n"
            . '</span>' . "\n";

        $htmlOutput .= '<input type="text" name="username" id="pma_username" class="autofocus"'
            . ' maxlength="' . $usernameLength . '" title="' . __('User name') . '"'
            . (empty($GLOBALS['username'])
               ? ''
               : ' value="' . htmlspecialchars(
                   $GLOBALS['new_username'] ?? $GLOBALS['username']
               ) . '"'
            )
            . (! isset($GLOBALS['pred_username'])
                    || $GLOBALS['pred_username'] === 'userdefined'
                ? 'required="required"'
                : '') . '>' . "\n";

        $htmlOutput .= '<div id="user_exists_warning"'
            . ' name="user_exists_warning" class="hide">'
            . Message::notice(
                __(
                    'An account already exists with the same username '
                    . 'but possibly a different hostname.'
                )
            )->getDisplay()
            . '</div>';
        $htmlOutput .= '</div>';

        $htmlOutput .= '<div class="item">' . "\n"
            . '<label for="select_pred_hostname">' . "\n"
            . '    ' . __('Host name:') . "\n"
            . '</label>' . "\n";

        $htmlOutput .= '<span class="options">' . "\n"
            . '    <select name="pred_hostname" id="select_pred_hostname" '
            . 'title="' . __('Host name') . '"' . "\n";
        $_currentUser = $this->privileges->getUser();
        if (! empty($_currentUser)) {
            $thishost = str_replace(
                "'",
                '',
                mb_substr(
                    $_currentUser,
                    mb_strrpos($_currentUser, '@') + 1
                )
            );
            if ($thishost !== 'localhost' && $thishost !== '127.0.0.1') {
                $htmlOutput .= ' data-thishost="' . htmlspecialchars($thishost) . '" ';
            } else {
                unset($thishost);
            }
        }
        $htmlOutput .= '>' . "\n";
        unset($_currentUser);

        // when we start editing a user, $GLOBALS['pred_hostname'] is not defined
        if (! isset($GLOBALS['pred_hostname']) && isset($GLOBALS['hostname'])) {
            switch (mb_strtolower($GLOBALS['hostname'])) {
                case 'localhost':
                case '127.0.0.1':
                    $GLOBALS['pred_hostname'] = 'localhost';
                    break;
                case '%':
                    $GLOBALS['pred_hostname'] = 'any';
                    break;
                default:
                    $GLOBALS['pred_hostname'] = 'userdefined';
                    break;
            }
        }
        $htmlOutput .=  '<option value="any"'
            . (isset($GLOBALS['pred_hostname'])
                    && $GLOBALS['pred_hostname'] === 'any'
                ? ' selected="selected"'
                : '') . '>'
            . __('Any host')
            . '</option>' . "\n"
            . '<option value="localhost"'
            . (isset($GLOBALS['pred_hostname'])
                    && $GLOBALS['pred_hostname'] === 'localhost'
                ? ' selected="selected"'
                : '') . '>'
            . __('Local')
            . '</option>' . "\n";
        if (! empty($thishost)) {
            $htmlOutput .= '<option value="thishost"'
                . (isset($GLOBALS['pred_hostname'])
                        && $GLOBALS['pred_hostname'] === 'thishost'
                    ? ' selected="selected"'
                    : '') . '>'
                . __('This Host')
                . '</option>' . "\n";
        }
        unset($thishost);
        $htmlOutput .= '<option value="hosttable"'
            . (isset($GLOBALS['pred_hostname'])
                    && $GLOBALS['pred_hostname'] === 'hosttable'
                ? ' selected="selected"'
                : '') . '>'
            . __('Use Host Table')
            . '</option>' . "\n";

        $htmlOutput .= '<option value="userdefined"'
            . (isset($GLOBALS['pred_hostname'])
                    && $GLOBALS['pred_hostname'] === 'userdefined'
                ? ' selected="selected"'
                : '') . '>'
            . __('Use text field:') . '</option>' . "\n"
            . '</select>' . "\n"
            . '</span>' . "\n";

        $htmlOutput .= '<input type="text" name="hostname" id="pma_hostname" maxlength="'
            . $hostnameLength . '" value="'
            // use default value of '%' to match with the default 'Any host'
            . htmlspecialchars($GLOBALS['hostname'] ?? '%')
            . '" title="' . __('Host name') . '" '
            . (isset($GLOBALS['pred_hostname'])
                    && $GLOBALS['pred_hostname'] === 'userdefined'
                ? 'required="required"'
                : '')
            . '>' . "\n"
            . Util::showHint(
                __(
                    'When Host table is used, this field is ignored '
                    . 'and values stored in Host table are used instead.'
                )
            )
            . '</div>' . "\n";

        $htmlOutput .= '<div class="item">' . "\n"
            . '<label for="select_pred_password">' . "\n"
            . '    ' . __('Password:') . "\n"
            . '</label>' . "\n"
            . '<span class="options">' . "\n"
            . '<select name="pred_password" id="select_pred_password" title="'
            . __('Password') . '">' . "\n"
            . ($mode === 'change' ? '<option value="keep" selected="selected">'
                . __('Do not change the password')
                . '</option>' . "\n" : '')
            . '<option value="none"';

        if ($mode !== 'change' && isset($GLOBALS['username'])) {
            $htmlOutput .= '  selected="selected"';
        }
        $htmlOutput .= '>' . __('No Password') . '</option>' . "\n"
            . '<option value="userdefined"'
            . (isset($GLOBALS['username']) ? '' : ' selected="selected"') . '>'
            . __('Use text field')
            . ':</option>' . "\n"
            . '</select>' . "\n"
            . '</span>' . "\n"
            . '<input type="password" id="text_pma_pw" name="pma_pw" '
            . 'title="' . __('Password') . '" '
            . (isset($GLOBALS['username']) ? '' : 'required="required"')
            . '>' . "\n"
            . '<span>Strength:</span> '
            . '<meter max="4" id="password_strength_meter" name="pw_meter"></meter> '
            . '<span id="password_strength" name="pw_strength"></span>' . "\n"
            . '</div>' . "\n";

        $htmlOutput .= '<div class="item" '
            . 'id="div_element_before_generate_password">' . "\n"
            . '<label for="text_pma_pw2">' . "\n"
            . '    ' . __('Re-type:') . "\n"
            . '</label>' . "\n"
            . '<span class="options">&nbsp;</span>' . "\n"
            . '<input type="password" name="pma_pw2" id="text_pma_pw2" '
            . 'title="' . __('Re-type') . '" '
            . (isset($GLOBALS['username']) ? '' : 'required="required"')
            . '>' . "\n"
            . '</div>' . "\n"
            . '<div class="item" id="authentication_plugin_div">'
            . '<label for="select_authentication_plugin" >';

        $serverType = Util::getServerType();
        $serverVersion = $this->privileges->getServerVersion();
        $origAuthPlugin = $this->privileges->getCurrentAuthenticationPlugin(
            $mode,
            $username,
            $hostname
        );

        if (($serverType === 'MySQL'
            && $serverVersion >= 50507)
            || ($serverType === 'MariaDB'
            && $serverVersion >= 50200)
        ) {
            $htmlOutput .= __('Authentication Plugin')
            . '</label><span class="options">&nbsp;</span>' . "\n";

            $authPluginsDropdown = $this->getAuthPluginsDropdown(
                $origAuthPlugin,
                $mode,
                'new'
            );
        } else {
            $htmlOutput .= __('Password Hashing Method')
                . '</label><span class="options">&nbsp;</span>' . "\n";
            $authPluginsDropdown = $this->getAuthPluginsDropdown(
                $origAuthPlugin,
                $mode,
                'old'
            );
        }
        $htmlOutput .= $authPluginsDropdown;

        $htmlOutput .= '<div'
            . ($origAuthPlugin !== 'sha256_password' ? ' class="hide"' : '')
            . ' id="ssl_reqd_warning">'
            . Message::notice(
                __(
                    'This method requires using an \'<i>SSL connection</i>\' '
                    . 'or an \'<i>unencrypted connection that encrypts the password '
                    . 'using RSA</i>\'; while connecting to the server.'
                )
                . Util::showMySQLDocu('sha256-authentication-plugin')
            )
                ->getDisplay()
            . '</div>';

        $htmlOutput .= '</div>' . "\n"
            // Generate password added here via jQuery
           . '</fieldset>' . "\n";

        return $htmlOutput;
    }

    /**
     * Get a HTML table for display user's tabel specific or database specific rights
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $type     database, table or routine
     * @param string $dbname   database name
     *
     * @return string
     */
    public function getAllTableSpecificRights(
        $username,
        $hostname,
        $type,
        $dbname = ''
    ): string {
        $data = $this->privileges->getDataForAllTableSpecificRights($username, $hostname, $type, $dbname);

        return $this->template->render('server/privileges/privileges_summary', $data);
    }

    /**
     * Get HTML for Displays the initials
     *
     * @param array $arrayInitials array for all initials, even non A-Z
     *
     * @return string HTML snippet
     */
    public function getInitials(array $arrayInitials): string
    {
        $data = $this->privileges->getDataForInitials($arrayInitials);

        return $this->template->render('server/privileges/initials_row', $data);
    }

    /**
     * Get HTML fieldset for Add/Delete user
     *
     * @return string HTML snippet
     */
    public function getFieldsetForAddDeleteUser(): string
    {
        $htmlOutput = $this->getAddUserFieldset();

        $htmlOutput .= $this->template->render('server/privileges/delete_user_fieldset');

        return $htmlOutput;
    }

    /**
     * Get the HTML snippet for routine specific privileges
     *
     * @param string $username  username for database connection
     * @param string $hostname  hostname for database connection
     * @param string $db        the database
     * @param string $routine   the routine
     * @param string $urlDbname url encoded db name
     *
     * @return string
     */
    public function getRoutineSpecificPrivileges($username, $hostname, $db, $routine, $urlDbname): string
    {
        $header = $this->getHeaderForUserProperties(
            false,
            $urlDbname,
            $db,
            $username,
            $hostname,
            $routine,
            'routine'
        );

        $privs = $this->privileges->getDataForRoutineSpecificPrivileges($username, $hostname, $db, $routine);
        $routineArray   = [$this->privileges->getTriggerPrivilegeTable()];
        $privTableNames = [__('Routine')];
        $privCheckboxes = $this->getGlobalPrivTableWithCheckboxes(
            $routineArray,
            $privTableNames,
            $privs
        );

        return $this->template->render('server/privileges/edit_routine_privileges', [
            'username' => $username,
            'hostname' => $hostname,
            'database' => $db,
            'routine' => $routine,
            'grant_count' => count($privs),
            'priv_checkboxes' => $privCheckboxes,
            'header' => $header,
        ]);
    }

    /**
     * Displays a dropdown to select the user group
     * with menu items configured to each of them.
     *
     * @param string $username username
     *
     * @return string html to select the user group
     */
    public function getChooseUserGroup($username): string
    {
        $data = $this->privileges->getDataToChooseUserGroup($username);

        return $this->template->render('server/privileges/choose_user_group', $data);
    }

    /**
     * Get Html for User Group Dialog
     *
     * @param string $username    username
     * @param bool   $isMenuswork Is menuswork set in configuration
     *
     * @return string html
     */
    public function getUserGroupDialog(string $username, bool $isMenuswork): string
    {
        $html = '';
        if (! empty($_GET['edit_user_group_dialog']) && $isMenuswork) {
            $dialog = $this->getChooseUserGroup($username);
            $response = Response::getInstance();
            if ($response->isAjax()) {
                $response->addJSON('message', $dialog);
                exit;
            }

            $html .= $dialog;
        }

        return $html;
    }

    /**
     * Get the HTML snippet for table specific privileges
     *
     * @param string $username username for database connection
     * @param string $hostname hostname for database connection
     * @param string $db       the database
     * @param string $table    the table
     * @param array  $columns  columns array
     * @param array  $row      current privileges row
     *
     * @return string
     */
    public function getTableSpecificPrivileges(
        $username,
        $hostname,
        $db,
        $table,
        array $columns,
        array $row
    ): string {
        $columns = $this->privileges->getDataForTableSpecificPrivileges($username, $hostname, $db, $table, $columns);

        $htmlOutput = '<input type="hidden" name="grant_count" '
            . 'value="' . count($row) . '">' . "\n"
            . '<input type="hidden" name="column_count" '
            . 'value="' . count($columns) . '">' . "\n"
            . '<fieldset id="fieldset_user_priv">' . "\n"
            . '<legend data-submenu-label="' . __('Table') . '">' . __('Table-specific privileges')
            . '</legend>'
            . '<p><small><i>'
            . __('Note: MySQL privilege names are expressed in English.')
            . '</i></small></p>';

        // privs that are attached to a specific column
        $htmlOutput .= $this->getAttachedPrivilegesToTableSpecificColumn($columns, $row);

        // privs that are not attached to a specific column
        $htmlOutput .= '<div class="item">' . "\n"
            . $this->getNotAttachedPrivilegesToTableSpecificColumn($row)
            . '</div>' . "\n";

        // for Safari 2.0.2
        $htmlOutput .= '<div class="clearfloat"></div>' . "\n";

        return $htmlOutput;
    }

    /**
     * Get the HTML for routine based privileges
     *
     * @param string $db            database name
     * @param string $indexCheckbox starting index for rows to be added
     *
     * @return string
     */
    public function getTableBodyForSpecificDbRoutinePrivs($db, $indexCheckbox): string
    {
        $rows = $this->privileges->getDataTableBodyForSpecificDbRoutinePrivs($db);
        $htmlOutput = '';
        foreach ($rows as $row) {
            $htmlOutput .= '<tr>';

            $htmlOutput .= '<td';
            $value = htmlspecialchars($row['User'] . '&amp;#27;' . $row['Host']);
            $htmlOutput .= '>';
            $htmlOutput .= '<input type="checkbox" class="checkall" '
                . 'name="selected_usr[]" '
                . 'id="checkbox_sel_users_' . ($indexCheckbox++) . '" '
                . 'value="' . $value . '"></td>';

            $htmlOutput .= '<td>' . htmlspecialchars($row['User'])
                . '</td>'
                . '<td>' . htmlspecialchars($row['Host'])
                . '</td>'
                . '<td>' . 'routine'
                . '</td>'
                . '<td>' . '<code>' . htmlspecialchars($row['Routine_name']) . '</code>'
                . '</td>'
                . '<td>' . 'Yes'
                . '</td>';
            $currentUser = $row['User'];
            $currentHost = $row['Host'];
            $routine = $row['Routine_name'];
            $htmlOutput .= '<td>';
            $specificDb = '';
            $specificTable = '';
            if ($GLOBALS['is_grantuser']) {
                $specificDb = isset($row['Db']) && $row['Db'] !== '*'
                    ? $row['Db'] : '';
                $specificTable = isset($row['Table_name'])
                    && $row['Table_name'] !== '*'
                    ? $row['Table_name'] : '';
                $htmlOutput .= $this->privileges->getUserLink(
                    'edit',
                    $currentUser,
                    $currentHost,
                    $specificDb,
                    $specificTable,
                    $routine
                );
            }
            $htmlOutput .= '</td>';
            $htmlOutput .= '<td>';
            $htmlOutput .= $this->privileges->getUserLink(
                'export',
                $currentUser,
                $currentHost,
                $specificDb,
                $specificTable,
                $routine
            );
            $htmlOutput .= '</td>';

            $htmlOutput .= '</tr>';
        }
        return $htmlOutput;
    }

    /**
     * Returns user group edit link
     *
     * @param string $username User name
     *
     * @return string HTML code with link
     */
    public function getUserGroupEditLink($username): string
    {
         return '<a class="edit_user_group_anchor ajax"'
            . ' href="server_privileges.php'
            . Url::getCommon(['username' => $username])
            . '">'
            . Util::getIcon('b_usrlist', __('Edit user group'))
            . '</a>';
    }

    /**
     * Get the HTML snippet for change user login information
     *
     * @param string $username username
     * @param string $hostname host name
     *
     * @return string HTML snippet
     */
    public function getChangeLoginInformationForm(string $username, string $hostname): string
    {
        $choices = [
            '4' => __('… keep the old one.'),
            '1' => __('… delete the old one from the user tables.'),
            '2' => __(
                '… revoke all active privileges from '
                . 'the old one and delete it afterwards.'
            ),
            '3' => __(
                '… delete the old one from the user tables '
                . 'and reload the privileges afterwards.'
            ),
        ];

        $htmlOutput = '<form action="server_privileges.php" '
            . 'onsubmit="return checkAddUser(this);" '
            . 'method="post" class="copyUserForm submenu-item">' . "\n"
            . Url::getHiddenInputs('', '')
            . '<input type="hidden" name="old_username" '
            . 'value="' . htmlspecialchars($username) . '">' . "\n"
            . '<input type="hidden" name="old_hostname" '
            . 'value="' . htmlspecialchars($hostname) . '">' . "\n";

        $usergroup = $this->privileges->getUserGroupForUser($username);
        if ($usergroup !== null) {
            $htmlOutput .= '<input type="hidden" name="old_usergroup" '
            . 'value="' . htmlspecialchars($usergroup) . '">' . "\n";
        }

        $htmlOutput .= '<fieldset id="fieldset_change_copy_user">' . "\n"
            . '<legend data-submenu-label="' . __('Login Information') . '">' . "\n"
            . __('Change login information / Copy user account')
            . '</legend>' . "\n"
            . $this->getHtmlForLoginInformationFields('change', $username, $hostname);

        $htmlOutput .= '<fieldset id="fieldset_mode">' . "\n"
            . ' <legend>'
            . __('Create a new user account with the same privileges and …')
            . '</legend>' . "\n";
        $htmlOutput .= Util::getRadioFields(
            'mode',
            $choices,
            '4',
            true
        );
        $htmlOutput .= '</fieldset>' . "\n"
           . '</fieldset>' . "\n";

        $htmlOutput .= '<fieldset id="fieldset_change_copy_user_footer" '
            . 'class="tblFooters">' . "\n"
            . '<input type="hidden" name="change_copy" value="1">' . "\n"
            . '<input class="btn btn-primary" type="submit" value="' . __('Go') . '">' . "\n"
            . '</fieldset>' . "\n"
            . '</form>' . "\n";

        return $htmlOutput;
    }

    /**
     * Provide a line with links to the relevant database and table
     *
     * @param string $urlDbname url database name that urlencode() string
     * @param string $dbname    database name
     * @param string $tablename table name
     *
     * @return string HTML snippet
     */
    public function getLinkToDbAndTable(string $urlDbname, string $dbname, string $tablename): string
    {
        $htmlOutput = '[ ' . __('Database')
            . ' <a href="' . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            )
            . Url::getCommon(
                [
                    'db' => $urlDbname,
                    'reload' => 1,
                ]
            )
            . '">'
            . htmlspecialchars(Util::unescapeMysqlWildcards($dbname)) . ': '
            . Util::getTitleForTarget(
                $GLOBALS['cfg']['DefaultTabDatabase']
            )
            . "</a> ]\n";

        if ('' !== $tablename) {
            $htmlOutput .= ' [ ' . __('Table') . ' <a href="'
                . Util::getScriptNameForOption(
                    $GLOBALS['cfg']['DefaultTabTable'],
                    'table'
                )
                . Url::getCommon(
                    [
                        'db' => $urlDbname,
                        'table' => $tablename,
                        'reload' => 1,
                    ]
                )
                . '">' . htmlspecialchars($tablename) . ': '
                . Util::getTitleForTarget(
                    $GLOBALS['cfg']['DefaultTabTable']
                )
                . "</a> ]\n";
        }
        return $htmlOutput;
    }

    /**
     * Get table body for 'tableuserrights' table in userform
     *
     * @param array $dbRights user's database rights array
     *
     * @return string HTML snippet
     */
    public function getTableBodyForUserRights(array $dbRights): string
    {
        list($cfgRelation, $userGroupCount, $groupAssignment) = $this->privileges->getDataTableBodyForUserRights();

        $indexCheckbox = 0;
        $htmlOutput = '';
        foreach ($dbRights as $user) {
            ksort($user);
            foreach ($user as $host) {
                $indexCheckbox++;
                $htmlOutput .= '<tr>'
                    . "\n";
                $htmlOutput .= '<td>'
                    . '<input type="checkbox" class="checkall" name="selected_usr[]" '
                    . 'id="checkbox_sel_users_'
                    . $indexCheckbox . '" value="'
                    . htmlspecialchars($host['User'] . '&amp;#27;' . $host['Host'])
                    . '"'
                    . '></td>' . "\n";

                $htmlOutput .= '<td><label '
                    . 'for="checkbox_sel_users_' . $indexCheckbox . '">'
                    . (empty($host['User'])
                        ? '<span style="color: #FF0000">' . __('Any') . '</span>'
                        : htmlspecialchars($host['User'])) . '</label></td>' . "\n"
                    . '<td>' . htmlspecialchars($host['Host']) . '</td>' . "\n";

                $htmlOutput .= '<td>';

                $passwordColumn = 'Password';

                $res = $this->privileges->getCheckPluginQuery($host);

                if ((isset($res['authentication_string'])
                    && ! empty($res['authentication_string']))
                    || (isset($res['Password'])
                    && ! empty($res['Password']))
                ) {
                    $host[$passwordColumn] = 'Y';
                } else {
                    $host[$passwordColumn] = 'N';
                }

                switch ($host[$passwordColumn]) {
                    case 'Y':
                        $htmlOutput .= __('Yes');
                        break;
                    case 'N':
                        $htmlOutput .= '<span style="color: #FF0000">' . __('No')
                        . '</span>';
                        break;
                // this happens if this is a definition not coming from mysql.user
                    default:
                        $htmlOutput .= '--'; // in future version, replace by "not present"
                        break;
                }

                if (! isset($host['Select_priv'])) {
                    $htmlOutput .= Util::showHint(
                        __('The selected user was not found in the privilege table.')
                    );
                }

                $htmlOutput .= '</td>' . "\n";

                $htmlOutput .= '<td><code>' . "\n"
                    . '' . implode(',' . "\n" . '            ', $host['privs']) . "\n"
                    . '</code></td>' . "\n";
                if ($cfgRelation['menuswork']) {
                    $htmlOutput .= '<td class="usrGroup">' . "\n"
                        . (isset($groupAssignment[$host['User']])
                            ? htmlspecialchars($groupAssignment[$host['User']])
                            : ''
                        )
                        . '</td>' . "\n";
                }
                $htmlOutput .= '<td>'
                    . ($host['Grant_priv'] === 'Y' ? __('Yes') : __('No'))
                    . '</td>' . "\n";

                if ($GLOBALS['is_grantuser']) {
                    $htmlOutput .= '<td class="center">'
                        . $this->privileges->getUserLink(
                            'edit',
                            $host['User'],
                            $host['Host']
                        )
                        . '</td>';
                }
                if ($userGroupCount > 0 && $cfgRelation['menuswork']) {
                    if (empty($host['User'])) {
                        $htmlOutput .= '<td class="center"></td>';
                    } else {
                        $htmlOutput .= '<td class="center">'
                            . $this->getUserGroupEditLink($host['User'])
                            . '</td>';
                    }
                }
                $htmlOutput .= '<td class="center">'
                    . $this->privileges->getUserLink(
                        'export',
                        $host['User'],
                        $host['Host'],
                        '',
                        '',
                        '',
                        $_GET['initial'] ?? ''
                    )
                    . '</td>';
                $htmlOutput .= '</tr>';
            }
        }
        return $htmlOutput;
    }

    /**
     * Get title and textarea for export user definition in Privileges
     *
     * @param string $username username
     * @param string $hostname host name
     *
     * @return array ($title, $export)
     */
    public function getListForExportUserDefinition(string $username, string $hostname): array
    {
        $export = '<textarea class="export" cols="60" rows="15">';

        if (isset($_POST['selected_usr'])) {
            // export privileges for selected users
            $title = __('Privileges');

            //For removing duplicate entries of users
            $_POST['selected_usr'] = array_unique($_POST['selected_usr']);

            foreach ($_POST['selected_usr'] as $exportUser) {
                $exportUsername = mb_substr(
                    $exportUser,
                    0,
                    mb_strpos($exportUser, '&')
                );
                $exportHostname = mb_substr($exportUser, mb_strrpos($exportUser, ';') + 1);
                $export .= '# '
                    . sprintf(
                        __('Privileges for %s'),
                        '`' . htmlspecialchars($exportUsername)
                        . '`@`' . htmlspecialchars($exportHostname) . '`'
                    )
                    . "\n\n";
                $export .= $this->privileges->getGrants($exportUsername, $exportHostname) . "\n";
            }
        } else {
            // export privileges for a single user
            $title = __('User') . ' `' . htmlspecialchars($username)
                . '`@`' . htmlspecialchars($hostname) . '`';
            $export .= $this->privileges->getGrants($username, $hostname);
        }
        // remove trailing whitespace
        $export = trim($export);

        $export .= '</textarea>';

        return [
            $title,
            $export,
        ];
    }

    /**
     * Displays the privileges form table
     *
     * @param string  $db     the database
     * @param string  $table  the table
     * @param boolean $submit whether to display the submit button or not
     *
     * @global  array     $cfg         the phpMyAdmin configuration
     * @global  resource  $user_link   the database connection
     *
     * @return string html snippet
     */
    public function getPrivilegesTable(
        string $db = '*',
        string $table = '*',
        bool $submit = true
    ): string {
        list($table, $username, $hostname, $row, $columns) = $this->privileges->getDataToDisplayPrivilegesTable(
            $db,
            $table
        );

        $htmlOutput = '';
        // table-specific privileges
        if (! empty($columns)) {
            $htmlOutput .= $this->getTableSpecificPrivileges(
                $username,
                $hostname,
                $db,
                $table,
                $columns,
                $row
            );
        } else {
            // global or db-specific
            $htmlOutput .= $this->getGlobalOrDbSpecificPrivs($db, $table, $row);
        }
        $htmlOutput .= '</fieldset>' . "\n";
        if ($submit) {
            $htmlOutput .= '<fieldset id="fieldset_user_privtable_footer" '
                . 'class="tblFooters">' . "\n"
                . '<input type="hidden" name="update_privs" value="1">' . "\n"
                . '<input class="btn btn-primary" type="submit" value="' . __('Go') . '">' . "\n"
                . '</fieldset>' . "\n";
        }
        return $htmlOutput;
    }

    /**
     * Get HTML for display the users overview
     * (if less than 50 users, display them immediately)
     *
     * @param array  $usersInformation ran sql query
     * @param array  $dbRights         user's database rights array
     * @param string $pmaThemeImage    a image source link
     * @param string $textDir          text directory
     *
     * @return string HTML snippet
     */
    public function getUsersOverview(
        array $usersInformation,
        array $dbRights,
        string $pmaThemeImage,
        string $textDir
    ): string {
        list($dbRights, $userGroupCount) = $this->privileges->getDataForUsersOverview($usersInformation, $dbRights);

        $htmlOutput
            = '<form name="usersForm" id="usersForm" action="server_privileges.php" '
            . 'method="post">' . "\n"
            . Url::getHiddenInputs('', '')
            . '<div class="responsivetable">'
            . '<table id="tableuserrights" class="data">' . "\n"
            . '<thead>' . "\n"
            . '<tr><th></th>' . "\n"
            . '<th>' . __('User name') . '</th>' . "\n"
            . '<th>' . __('Host name') . '</th>' . "\n"
            . '<th>' . __('Password') . '</th>' . "\n"
            . '<th>' . __('Global privileges') . ' '
            . Util::showHint(
                __('Note: MySQL privilege names are expressed in English.')
            )
            . '</th>' . "\n";
        if ($GLOBALS['cfgRelation']['menuswork']) {
            $htmlOutput .= '<th>' . __('User group') . '</th>' . "\n";
        }
        $htmlOutput .= '<th>' . __('Grant') . '</th>' . "\n"
            . '<th colspan="' . ($userGroupCount > 0 ? '3' : '2') . '">'
            . __('Action') . '</th>' . "\n"
            . '</tr>' . "\n"
            . '</thead>' . "\n";

        $htmlOutput .= '<tbody>' . "\n";
        $htmlOutput .= $this->getTableBodyForUserRights($dbRights);
        $htmlOutput .= '</tbody>'
            . '</table></div>' . "\n";

        $htmlOutput .= '<div class="floatleft">'
            . $this->template->render('select_all', [
                'pma_theme_image' => $pmaThemeImage,
                'text_dir' => $textDir,
                'form_name' => 'usersForm',
            ]) . "\n";
        $htmlOutput .= Util::getButtonOrImage(
            'submit_mult',
            'mult_submit',
            __('Export'),
            'b_tblexport',
            'export'
        );
        $htmlOutput .= '<input type="hidden" name="initial" '
            . 'value="' . (isset($_GET['initial']) ? htmlspecialchars($_GET['initial']) : '') . '">';
        $htmlOutput .= '</div>'
            . '<div class="clearfloat"></div>';

        // add/delete user fieldset
        $htmlOutput .= $this->getFieldsetForAddDeleteUser();
        $htmlOutput .= '</form>' . "\n";

        return $htmlOutput;
    }

    /**
     * Get HTML snippet for display user properties
     *
     * @param boolean      $dbnameIsWildcard whether database name is wildcard or not
     * @param string       $urlDbname        url database name that urlencode() string
     * @param string       $username         username
     * @param string       $hostname         host name
     * @param array|string $dbname           database name
     * @param string       $tablename        table name
     *
     * @return string
     */
    public function getUserProperties(
        $dbnameIsWildcard,
        $urlDbname,
        $username,
        $hostname,
        $dbname,
        $tablename
    ): string {
        $htmlOutput  = '<div id="edit_user_dialog">';
        $htmlOutput .= $this->getHeaderForUserProperties(
            $dbnameIsWildcard,
            $urlDbname,
            $dbname,
            $username,
            $hostname,
            $tablename,
            'table'
        );

        $userDoesNotExist = ! $this->privileges->doesUserExist($username, $hostname);

        if ($userDoesNotExist) {
            $htmlOutput .= Message::error(
                __('The selected user was not found in the privilege table.')
            )->getDisplay();
            $htmlOutput .= $this->getHtmlForLoginInformationFields();
        }

        $_params = [
            'username' => $username,
            'hostname' => $hostname,
        ];
        if (! is_array($dbname) && '' !== $dbname) {
            $_params['dbname'] = $dbname;
            if ('' !== $tablename) {
                $_params['tablename'] = $tablename;
            }
        } else {
            $_params['dbname'] = $dbname;
        }

        $htmlOutput .= '<form class="submenu-item" name="usersForm" '
            . 'id="addUsersForm" action="server_privileges.php" method="post">' . "\n";
        $htmlOutput .= Url::getHiddenInputs($_params);
        $htmlOutput .= $this->getPrivilegesTable(
            // If $dbname is an array, pass any one db as all have same privs.
            Core::ifSetOr($dbname, is_array($dbname) ? $dbname[0] : '*', 'length'),
            Core::ifSetOr($tablename, '*', 'length')
        );

        $htmlOutput .= '</form>' . "\n";

        if (! is_array($dbname) && '' === $tablename
            && empty($dbnameIsWildcard)
        ) {
            // no table name was given, display all table specific rights
            // but only if $dbname contains no wildcards
            if ('' === $dbname) {
                $htmlOutput .= $this->getAllTableSpecificRights(
                    $username,
                    $hostname,
                    'database'
                );
            } else {
                // unescape wildcards in dbname at table level
                $unescapedDb = Util::unescapeMysqlWildcards($dbname);

                $htmlOutput .= $this->getAllTableSpecificRights(
                    $username,
                    $hostname,
                    'table',
                    $unescapedDb
                );
                $htmlOutput .= $this->getAllTableSpecificRights(
                    $username,
                    $hostname,
                    'routine',
                    $unescapedDb
                );
            }
        }

        // Provide a line with links to the relevant database and table
        if (! is_array($dbname) && '' !== $dbname && empty($dbnameIsWildcard)) {
            $htmlOutput .= $this->getLinkToDbAndTable($urlDbname, $dbname, $tablename);
        }

        if (! is_array($dbname) && '' === $dbname && ! $userDoesNotExist) {
            //change login information
            $htmlOutput .= ChangePassword::getHtml(
                'edit_other',
                $username,
                $hostname
            );
            $htmlOutput .= $this->getChangeLoginInformationForm($username, $hostname);
        }
        $htmlOutput .= '</div>';

        return $htmlOutput;
    }

    /**
     * Get HTML snippet for display user overview page
     *
     * @param string $pmaThemeImage a image source link
     * @param string $textDir       text directory
     *
     * @return string
     */
    public function getHtmlForUserOverview(string $pmaThemeImage, string $textDir): string
    {
        $htmlOutput = '<h2>' . "\n"
           . Util::getIcon('b_usrlist')
           . __('User accounts overview') . "\n"
           . '</h2>' . "\n";

        list($usersInformation, $numberAll) = $this->privileges->getDataForUserOverviewPart1();

        if (false === $usersInformation) {
            // the query failed! This may have two reasons:
            // - the user does not have enough privileges
            // - the privilege tables use a structure of an earlier version.
            // so let's try a more simple query

            $allUsersInformation = $this->privileges->getDataForUserOverviewPart2();

            if (! $allUsersInformation) {
                $htmlOutput .= $this->getViewUsersError();
                $htmlOutput .= $this->getAddUserFieldset();
            } else {
                // This message is hardcoded because I will replace it by
                // a automatic repair feature soon.
                $raw = 'Your privilege table structure seems to be older than'
                    . ' this MySQL version!<br>'
                    . 'Please run the <code>mysql_upgrade</code> command'
                    . ' that should be included in your MySQL server distribution'
                    . ' to solve this problem!';
                $htmlOutput .= Message::rawError($raw)->getDisplay();
            }
        } else {
            $dbRights = $this->privileges->getDbRightsForUserOverview();
            // for all initials, even non A-Z
            $arrayInitials = [];

            foreach ($dbRights as $right) {
                foreach ($right as $account) {
                    if (empty($account['User']) && $account['Host'] === 'localhost') {
                        $htmlOutput .= Message::notice(
                            __(
                                'A user account allowing any user from localhost to '
                                . 'connect is present. This will prevent other users '
                                . 'from connecting if the host part of their account '
                                . 'allows a connection from any (%) host.'
                            )
                            . Util::showMySQLDocu('problems-connecting')
                        )->getDisplay();
                        break 2;
                    }
                }
            }

            /**
             * Displays the initials
             * Also not necessary if there is less than 20 privileges
             */
            if ($numberAll > 20) {
                $htmlOutput .= $this->getInitials($arrayInitials);
            }

            /**
            * Display the user overview
            * (if less than 50 users, display them immediately)
            */
            if (isset($_GET['initial'])
                || isset($_GET['showall'])
                || count($usersInformation) < 50
            ) {
                $htmlOutput .= $this->getUsersOverview(
                    $usersInformation,
                    $dbRights,
                    $pmaThemeImage,
                    $textDir
                );
            } else {
                $htmlOutput .= $this->getAddUserFieldset();
            }

            $response = Response::getInstance();
            if (! empty($_REQUEST['ajax_page_request'])
                || ! $response->isAjax()
            ) {
                if ($GLOBALS['is_reload_priv']) {
                    $flushnote = new Message(
                        __(
                            'Note: phpMyAdmin gets the users’ privileges directly '
                            . 'from MySQL’s privilege tables. The content of these '
                            . 'tables may differ from the privileges the server uses, '
                            . 'if they have been changed manually. In this case, '
                            . 'you should %sreload the privileges%s before you continue.'
                        ),
                        Message::NOTICE
                    );
                    $flushnote->addParamHtml(
                        '<a href="server_privileges.php'
                        . Url::getCommon(['flush_privileges' => 1])
                        . '" id="reload_privileges_anchor">'
                    );
                    $flushnote->addParamHtml('</a>');
                } else {
                    $flushnote = new Message(
                        __(
                            'Note: phpMyAdmin gets the users’ privileges directly '
                            . 'from MySQL’s privilege tables. The content of these '
                            . 'tables may differ from the privileges the server uses, '
                            . 'if they have been changed manually. In this case, '
                            . 'the privileges have to be reloaded but currently, you '
                            . 'don\'t have the RELOAD privilege.'
                        )
                        . Util::showMySQLDocu(
                            'privileges-provided',
                            false,
                            null,
                            null,
                            'priv_reload'
                        ),
                        Message::NOTICE
                    );
                }
                $htmlOutput .= $flushnote->getDisplay();
            }
        }

        return $htmlOutput;
    }
}
