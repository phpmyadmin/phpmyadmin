<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Form management class, displays and processes forms
 *
 * Explanation of used terms:
 * o work_path - original field path, eg. Servers/4/verbose
 * o system_path - work_path modified so that it points to the first server,
 *                 eg. Servers/1/verbose
 * o translated_path - work_path modified for HTML field name, a path with
 *                     slashes changed to hyphens, eg. Servers-4-verbose
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\config;

/**
 * Core libraries.
 */
use PMA\libraries\Util;

require_once './libraries/js_escape.lib.php';
require_once './libraries/config/FormDisplay.tpl.php';

/**
 * Form management class, displays and processes forms
 *
 * @package PhpMyAdmin
 */
class FormDisplay
{
    /**
     * ConfigFile instance
     * @var ConfigFile
     */
    private $_configFile;

    /**
     * Form list
     * @var Form[]
     */
    private $_forms = array();

    /**
     * Stores validation errors, indexed by paths
     * [ Form_name ] is an array of form errors
     * [path] is a string storing error associated with single field
     * @var array
     */
    private $_errors = array();

    /**
     * Paths changed so that they can be used as HTML ids, indexed by paths
     * @var array
     */
    private $_translatedPaths = array();

    /**
     * Server paths change indexes so we define maps from current server
     * path to the first one, indexed by work path
     * @var array
     */
    private $_systemPaths = array();

    /**
     * Language strings which will be sent to PMA_messages JS variable
     * Will be looked up in $GLOBALS: str{value} or strSetup{value}
     * @var array
     */
    private $_jsLangStrings = array();

    /**
     * Tells whether forms have been validated
     * @var bool
     */
    private $_isValidated = true;

    /**
     * Dictionary with user preferences keys
     * @var array|null
     */
    private $_userprefsKeys;

    /**
     * Dictionary with disallowed user preferences keys
     * @var array
     */
    private $_userprefsDisallow;

    /**
     * Constructor
     *
     * @param ConfigFile $cf Config file instance
     */
    public function __construct(ConfigFile $cf)
    {
        $this->_jsLangStrings = array(
            'error_nan_p' => __('Not a positive number!'),
            'error_nan_nneg' => __('Not a non-negative number!'),
            'error_incorrect_port' => __('Not a valid port number!'),
            'error_invalid_value' => __('Incorrect value!'),
            'error_value_lte' => __('Value must be equal or lower than %s!'));
        $this->_configFile = $cf;
        // initialize validators
        Validator::getValidators($this->_configFile);
    }

    /**
     * Returns {@link ConfigFile} associated with this instance
     *
     * @return ConfigFile
     */
    public function getConfigFile()
    {
        return $this->_configFile;
    }

    /**
     * Registers form in form manager
     *
     * @param string $form_name Form name
     * @param array  $form      Form data
     * @param int    $server_id 0 if new server, validation; >= 1 if editing a server
     *
     * @return void
     */
    public function registerForm($form_name, array $form, $server_id = null)
    {
        $this->_forms[$form_name] = new Form(
            $form_name, $form, $this->_configFile, $server_id
        );
        $this->_isValidated = false;
        foreach ($this->_forms[$form_name]->fields as $path) {
            $work_path = $server_id === null
                ? $path
                : str_replace('Servers/1/', "Servers/$server_id/", $path);
            $this->_systemPaths[$work_path] = $path;
            $this->_translatedPaths[$work_path] = str_replace('/', '-', $work_path);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param bool $allow_partial_save allows for partial form saving
     *                                 on failed validation
     * @param bool $check_form_submit  whether check for $_POST['submit_save']
     *
     * @return boolean whether processing was successful
     */
    public function process($allow_partial_save = true, $check_form_submit = true)
    {
        if ($check_form_submit && !isset($_POST['submit_save'])) {
            return false;
        }

        // save forms
        if (count($this->_forms) > 0) {
            return $this->save(array_keys($this->_forms), $allow_partial_save);
        }
        return false;
    }

    /**
     * Runs validation for all registered forms
     *
     * @return void
     */
    private function _validate()
    {
        if ($this->_isValidated) {
            return;
        }

        $paths = array();
        $values = array();
        foreach ($this->_forms as $form) {
            /* @var $form Form */
            $paths[] = $form->name;
            // collect values and paths
            foreach ($form->fields as $path) {
                $work_path = array_search($path, $this->_systemPaths);
                $values[$path] = $this->_configFile->getValue($work_path);
                $paths[] = $path;
            }
        }

        // run validation
        $errors = Validator::validate(
            $this->_configFile, $paths, $values, false
        );

        // change error keys from canonical paths to work paths
        if (is_array($errors) && count($errors) > 0) {
            $this->_errors = array();
            foreach ($errors as $path => $error_list) {
                $work_path = array_search($path, $this->_systemPaths);
                // field error
                if (! $work_path) {
                    // form error, fix path
                    $work_path = $path;
                }
                $this->_errors[$work_path] = $error_list;
            }
        }
        $this->_isValidated = true;
    }

    /**
     * Outputs HTML for the forms under the menu tab
     *
     * @param bool  $show_restore_default whether to show "restore default"
     *                                    button besides the input field
     * @param array &$js_default          stores JavaScript code
     *                                    to be displayed
     * @param array &$js                  will be updated with javascript code
     * @param bool  $show_buttons         whether show submit and reset button
     *
     * @return string $htmlOutput
     */
    private function _displayForms(
        $show_restore_default, array &$js_default, array &$js, $show_buttons
    ) {
        $htmlOutput = '';
        $validators = Validator::getValidators($this->_configFile);

        foreach ($this->_forms as $form) {
            /* @var $form Form */
            $form_desc = isset($GLOBALS["strConfigForm_{$form->name}_desc"])
                ? PMA_lang("Form_{$form->name}_desc")
                : '';
            $form_errors = isset($this->_errors[$form->name])
                ? $this->_errors[$form->name] : null;
            $htmlOutput .= PMA_displayFieldsetTop(
                PMA_lang("Form_$form->name"),
                $form_desc,
                $form_errors,
                array('id' => $form->name)
            );

            foreach ($form->fields as $field => $path) {
                $work_path = array_search($path, $this->_systemPaths);
                $translated_path = $this->_translatedPaths[$work_path];
                // always true/false for user preferences display
                // otherwise null
                $userprefs_allow = isset($this->_userprefsKeys[$path])
                    ? !isset($this->_userprefsDisallow[$path])
                    : null;
                // display input
                $htmlOutput .= $this->_displayFieldInput(
                    $form,
                    $field,
                    $path,
                    $work_path,
                    $translated_path,
                    $show_restore_default,
                    $userprefs_allow,
                    $js_default
                );
                // register JS validators for this field
                if (isset($validators[$path])) {
                    PMA_addJsValidate($translated_path, $validators[$path], $js);
                }
            }
            $htmlOutput .= PMA_displayFieldsetBottom($show_buttons);
        }
        return $htmlOutput;
    }

    /**
     * Outputs HTML for forms
     *
     * @param bool   $tabbed_form          if true, use a form with tabs
     * @param bool   $show_restore_default whether show "restore default" button
     *                                     besides the input field
     * @param bool   $show_buttons         whether show submit and reset button
     * @param string $form_action          action attribute for the form
     * @param array  $hidden_fields        array of form hidden fields (key: field
     *                                     name)
     *
     * @return string HTML for forms
     */
    public function getDisplay(
        $tabbed_form = false,
        $show_restore_default = false,
        $show_buttons = true,
        $form_action = null,
        $hidden_fields = null
    ) {
        static $js_lang_sent = false;

        $htmlOutput = '';

        $js = array();
        $js_default = array();

        $htmlOutput .= PMA_displayFormTop($form_action, 'post', $hidden_fields);

        if ($tabbed_form) {
            $tabs = array();
            foreach ($this->_forms as $form) {
                $tabs[$form->name] = PMA_lang("Form_$form->name");
            }
            $htmlOutput .= PMA_displayTabsTop($tabs);
        }

        // validate only when we aren't displaying a "new server" form
        $is_new_server = false;
        foreach ($this->_forms as $form) {
            /* @var $form Form */
            if ($form->index === 0) {
                $is_new_server = true;
                break;
            }
        }
        if (! $is_new_server) {
            $this->_validate();
        }

        // user preferences
        $this->_loadUserprefsInfo();

        // display forms
        $htmlOutput .= $this->_displayForms(
            $show_restore_default, $js_default, $js, $show_buttons
        );

        if ($tabbed_form) {
            $htmlOutput .= PMA_displayTabsBottom();
        }
        $htmlOutput .= PMA_displayFormBottom();

        // if not already done, send strings used for validation to JavaScript
        if (! $js_lang_sent) {
            $js_lang_sent = true;
            $js_lang = array();
            foreach ($this->_jsLangStrings as $strName => $strValue) {
                $js_lang[] = "'$strName': '" . PMA_jsFormat($strValue, false) . '\'';
            }
            $js[] = "$.extend(PMA_messages, {\n\t"
                . implode(",\n\t", $js_lang) . '})';
        }

        $js[] = "$.extend(defaultValues, {\n\t"
            . implode(",\n\t", $js_default) . '})';
        $htmlOutput .= PMA_displayJavascript($js);

        return $htmlOutput;
    }

    /**
     * Prepares data for input field display and outputs HTML code
     *
     * @param Form      $form                 Form object
     * @param string    $field                field name as it appears in $form
     * @param string    $system_path          field path, eg. Servers/1/verbose
     * @param string    $work_path            work path, eg. Servers/4/verbose
     * @param string    $translated_path      work path changed so that it can be
     *                                        used as XHTML id
     * @param bool      $show_restore_default whether show "restore default" button
     *                                        besides the input field
     * @param bool|null $userprefs_allow      whether user preferences are enabled
     *                                        for this field (null - no support,
     *                                        true/false - enabled/disabled)
     * @param array     &$js_default          array which stores JavaScript code
     *                                        to be displayed
     *
     * @return string HTML for input field
     */
    private function _displayFieldInput(
        Form $form, $field, $system_path, $work_path,
        $translated_path, $show_restore_default, $userprefs_allow, array &$js_default
    ) {
        $name = PMA_langName($system_path);
        $description = PMA_langName($system_path, 'desc', '');

        $value = $this->_configFile->get($work_path);
        $value_default = $this->_configFile->getDefault($system_path);
        $value_is_default = false;
        if ($value === null || $value === $value_default) {
            $value = $value_default;
            $value_is_default = true;
        }

        $opts = array(
            'doc' => $this->getDocLink($system_path),
            'show_restore_default' => $show_restore_default,
            'userprefs_allow' => $userprefs_allow,
            'userprefs_comment' => PMA_langName($system_path, 'cmt', ''));
        if (isset($form->default[$system_path])) {
            $opts['setvalue'] = $form->default[$system_path];
        }

        if (isset($this->_errors[$work_path])) {
            $opts['errors'] = $this->_errors[$work_path];
        }

        $type = '';
        switch ($form->getOptionType($field)) {
        case 'string':
            $type = 'text';
            break;
        case 'short_string':
            $type = 'short_text';
            break;
        case 'double':
        case 'integer':
            $type = 'number_text';
            break;
        case 'boolean':
            $type = 'checkbox';
            break;
        case 'select':
            $type = 'select';
            $opts['values'] = $form->getOptionValueList($form->fields[$field]);
            break;
        case 'array':
            $type = 'list';
            $value = (array) $value;
            $value_default = (array) $value_default;
            break;
        case 'group':
            // :group:end is changed to :group:end:{unique id} in Form class
            $htmlOutput = '';
            if (mb_substr($field, 7, 4) != 'end:') {
                $htmlOutput .= PMA_displayGroupHeader(
                    mb_substr($field, 7)
                );
            } else {
                PMA_displayGroupFooter();
            }
            return $htmlOutput;
        case 'NULL':
            trigger_error("Field $system_path has no type", E_USER_WARNING);
            return null;
        }

        // detect password fields
        if ($type === 'text'
            && (mb_substr($translated_path, -9) === '-password'
               || mb_substr($translated_path, -4) === 'pass'
               || mb_substr($translated_path, -4) === 'Pass')
        ) {
            $type = 'password';
        }

        // TrustedProxies requires changes before displaying
        if ($system_path == 'TrustedProxies') {
            foreach ($value as $ip => &$v) {
                if (!preg_match('/^-\d+$/', $ip)) {
                    $v = $ip . ': ' . $v;
                }
            }
        }
        $this->_setComments($system_path, $opts);

        // send default value to form's JS
        $js_line = '\'' . $translated_path . '\': ';
        switch ($type) {
        case 'text':
        case 'short_text':
        case 'number_text':
        case 'password':
            $js_line .= '\'' . PMA_escapeJsString($value_default) . '\'';
            break;
        case 'checkbox':
            $js_line .= $value_default ? 'true' : 'false';
            break;
        case 'select':
            $value_default_js = is_bool($value_default)
                ? (int) $value_default
                : $value_default;
            $js_line .= '[\'' . PMA_escapeJsString($value_default_js) . '\']';
            break;
        case 'list':
            $js_line .= '\'' . PMA_escapeJsString(implode("\n", $value_default))
                . '\'';
            break;
        }
        $js_default[] = $js_line;

        return PMA_displayInput(
            $translated_path, $name, $type, $value,
            $description, $value_is_default, $opts
        );
    }

    /**
     * Displays errors
     *
     * @return string HTML for errors
     */
    public function displayErrors()
    {
        $this->_validate();
        if (count($this->_errors) == 0) {
            return null;
        }

        $htmlOutput = '';

        foreach ($this->_errors as $system_path => $error_list) {
            if (isset($this->_systemPaths[$system_path])) {
                $path = $this->_systemPaths[$system_path];
                $name = PMA_langName($path);
            } else {
                $name = $GLOBALS["strConfigForm_$system_path"];
            }
            $htmlOutput .= PMA_displayErrors($name, $error_list);
        }

        return $htmlOutput;
    }

    /**
     * Reverts erroneous fields to their default values
     *
     * @return void
     */
    public function fixErrors()
    {
        $this->_validate();
        if (count($this->_errors) == 0) {
            return;
        }

        $cf = $this->_configFile;
        foreach (array_keys($this->_errors) as $work_path) {
            if (!isset($this->_systemPaths[$work_path])) {
                continue;
            }
            $canonical_path = $this->_systemPaths[$work_path];
            $cf->set($work_path, $cf->getDefault($canonical_path));
        }
    }

    /**
     * Validates select field and casts $value to correct type
     *
     * @param string &$value  Current value
     * @param array  $allowed List of allowed values
     *
     * @return bool
     */
    private function _validateSelect(&$value, array $allowed)
    {
        $value_cmp = is_bool($value)
            ? (int) $value
            : $value;
        foreach ($allowed as $vk => $v) {
            // equality comparison only if both values are numeric or not numeric
            // (allows to skip 0 == 'string' equalling to true)
            // or identity (for string-string)
            if (($vk == $value && !(is_numeric($value_cmp) xor is_numeric($vk)))
                || $vk === $value
            ) {
                // keep boolean value as boolean
                if (!is_bool($value)) {
                    settype($value, gettype($vk));
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Validates and saves form data to session
     *
     * @param array|string $forms              array of form names
     * @param bool         $allow_partial_save allows for partial form saving on
     *                                         failed validation
     *
     * @return boolean true on success (no errors and all saved)
     */
    public function save($forms, $allow_partial_save = true)
    {
        $result = true;
        $forms = (array) $forms;

        $values = array();
        $to_save = array();
        $is_setup_script = defined('PMA_SETUP');
        if ($is_setup_script) {
            $this->_loadUserprefsInfo();
        }

        $this->_errors = array();
        foreach ($forms as $form_name) {
            /* @var $form Form */
            if (isset($this->_forms[$form_name])) {
                $form = $this->_forms[$form_name];
            } else {
                continue;
            }
            // get current server id
            $change_index = $form->index === 0
                ? $this->_configFile->getServerCount() + 1
                : false;
            // grab POST values
            foreach ($form->fields as $field => $system_path) {
                $work_path = array_search($system_path, $this->_systemPaths);
                $key = $this->_translatedPaths[$work_path];
                $type = $form->getOptionType($field);

                // skip groups
                if ($type == 'group') {
                    continue;
                }

                // ensure the value is set
                if (!isset($_POST[$key])) {
                    // checkboxes aren't set by browsers if they're off
                    if ($type == 'boolean') {
                        $_POST[$key] = false;
                    } else {
                        $this->_errors[$form->name][] = sprintf(
                            __('Missing data for %s'),
                            '<i>' . PMA_langName($system_path) . '</i>'
                        );
                        $result = false;
                        continue;
                    }
                }

                // user preferences allow/disallow
                if ($is_setup_script
                    && isset($this->_userprefsKeys[$system_path])
                ) {
                    if (isset($this->_userprefsDisallow[$system_path])
                        && isset($_POST[$key . '-userprefs-allow'])
                    ) {
                        unset($this->_userprefsDisallow[$system_path]);
                    } else if (!isset($_POST[$key . '-userprefs-allow'])) {
                        $this->_userprefsDisallow[$system_path] = true;
                    }
                }

                // cast variables to correct type
                switch ($type) {
                case 'double':
                    $_POST[$key] = Util::requestString($_POST[$key]);
                    settype($_POST[$key], 'float');
                    break;
                case 'boolean':
                case 'integer':
                    if ($_POST[$key] !== '') {
                        $_POST[$key] = Util::requestString($_POST[$key]);
                        settype($_POST[$key], $type);
                    }
                    break;
                case 'select':
                    $successfully_validated = $this->_validateSelect(
                        $_POST[$key],
                        $form->getOptionValueList($system_path)
                    );
                    if (! $successfully_validated) {
                        $this->_errors[$work_path][] = __('Incorrect value!');
                        $result = false;
                        continue;
                    }
                    break;
                case 'string':
                case 'short_string':
                    $_POST[$key] = Util::requestString($_POST[$key]);
                    break;
                case 'array':
                    // eliminate empty values and ensure we have an array
                    $post_values = is_array($_POST[$key])
                        ? $_POST[$key]
                        : explode("\n", $_POST[$key]);
                    $_POST[$key] = array();
                    $this->_fillPostArrayParameters($post_values, $key);
                    break;
                }

                // now we have value with proper type
                $values[$system_path] = $_POST[$key];
                if ($change_index !== false) {
                    $work_path = str_replace(
                        "Servers/$form->index/",
                        "Servers/$change_index/", $work_path
                    );
                }
                $to_save[$work_path] = $system_path;
            }
        }

        // save forms
        if (!$allow_partial_save && !empty($this->_errors)) {
            // don't look for non-critical errors
            $this->_validate();
            return $result;
        }

        foreach ($to_save as $work_path => $path) {
            // TrustedProxies requires changes before saving
            if ($path == 'TrustedProxies') {
                $proxies = array();
                $i = 0;
                foreach ($values[$path] as $value) {
                    $matches = array();
                    $match = preg_match(
                        "/^(.+):(?:[ ]?)(\\w+)$/", $value, $matches
                    );
                    if ($match) {
                        // correct 'IP: HTTP header' pair
                        $ip = trim($matches[1]);
                        $proxies[$ip] = trim($matches[2]);
                    } else {
                        // save also incorrect values
                        $proxies["-$i"] = $value;
                        $i++;
                    }
                }
                $values[$path] = $proxies;
            }
            $this->_configFile->set($work_path, $values[$path], $path);
        }
        if ($is_setup_script) {
            $this->_configFile->set(
                'UserprefsDisallow',
                array_keys($this->_userprefsDisallow)
            );
        }

        // don't look for non-critical errors
        $this->_validate();

        return $result;
    }

    /**
     * Tells whether form validation failed
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return count($this->_errors) > 0;
    }


    /**
     * Returns link to documentation
     *
     * @param string $path Path to documentation
     *
     * @return string
     */
    public function getDocLink($path)
    {
        $test = mb_substr($path, 0, 6);
        if ($test == 'Import' || $test == 'Export') {
            return '';
        }
        return Util::getDocuLink(
            'config',
            'cfg_' .  $this->_getOptName($path)
        );
    }

    /**
     * Changes path so it can be used in URLs
     *
     * @param string $path Path
     *
     * @return string
     */
    private function _getOptName($path)
    {
        return str_replace(array('Servers/1/', '/'), array('Servers/', '_'), $path);
    }

    /**
     * Fills out {@link userprefs_keys} and {@link userprefs_disallow}
     *
     * @return void
     */
    private function _loadUserprefsInfo()
    {
        if ($this->_userprefsKeys !== null) {
            return;
        }

        $this->_userprefsKeys = array_flip(PMA_readUserprefsFieldNames());
        // read real config for user preferences display
        $userprefs_disallow = defined('PMA_SETUP')
            ? $this->_configFile->get('UserprefsDisallow', array())
            : $GLOBALS['cfg']['UserprefsDisallow'];
        $this->_userprefsDisallow = array_flip($userprefs_disallow);
    }

    /**
     * Sets field comments and warnings based on current environment
     *
     * @param string $system_path Path to settings
     * @param array  &$opts       Chosen options
     *
     * @return void
     */
    private function _setComments($system_path, array &$opts)
    {
        // RecodingEngine - mark unavailable types
        if ($system_path == 'RecodingEngine') {
            $comment = '';
            if (!function_exists('iconv')) {
                $opts['values']['iconv'] .= ' (' . __('unavailable') . ')';
                $comment = sprintf(
                    __('"%s" requires %s extension'), 'iconv', 'iconv'
                );
            }
            if (!function_exists('recode_string')) {
                $opts['values']['recode'] .= ' (' . __('unavailable') . ')';
                $comment .= ($comment ? ", " : '') . sprintf(
                    __('"%s" requires %s extension'),
                    'recode', 'recode'
                );
            }
            if (!function_exists('mb_convert_encoding')) {
                $opts['values']['mb'] .= ' (' . __('unavailable') . ')';
                $comment .= ($comment ? ", " : '') . sprintf(
                    __('"%s" requires %s extension'),
                    'mb', 'mbstring'
                );
            }
            $opts['comment'] = $comment;
            $opts['comment_warning'] = true;
        }
        // ZipDump, GZipDump, BZipDump - check function availability
        if ($system_path == 'ZipDump'
            || $system_path == 'GZipDump'
            || $system_path == 'BZipDump'
        ) {
            $comment = '';
            $funcs = array(
                'ZipDump'  => array('zip_open', 'gzcompress'),
                'GZipDump' => array('gzopen', 'gzencode'),
                'BZipDump' => array('bzopen', 'bzcompress'));
            if (!function_exists($funcs[$system_path][0])) {
                $comment = sprintf(
                    __(
                        'Compressed import will not work due to missing function %s.'
                    ),
                    $funcs[$system_path][0]
                );
            }
            if (!function_exists($funcs[$system_path][1])) {
                $comment .= ($comment ? '; ' : '') . sprintf(
                    __(
                        'Compressed export will not work due to missing function %s.'
                    ),
                    $funcs[$system_path][1]
                );
            }
            $opts['comment'] = $comment;
            $opts['comment_warning'] = true;
        }
        if (!defined('PMA_SETUP')) {
            if (($system_path == 'MaxDbList' || $system_path == 'MaxTableList'
                || $system_path == 'QueryHistoryMax')
            ) {
                $opts['comment'] = sprintf(
                    __('maximum %s'), $GLOBALS['cfg'][$system_path]
                );
            }
        }
    }

    /**
     * Copy items of an array to $_POST variable
     *
     * @param array  $post_values List of parameters
     * @param string $key         Array key
     *
     * @return void
     */
    private function _fillPostArrayParameters($post_values, $key)
    {
        foreach ($post_values as $v) {
            $v = Util::requestString($v);
            if ($v !== '') {
                $_POST[$key][] = $v;
            }
        }
    }
}
