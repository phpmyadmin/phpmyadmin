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

/**
 * Core libraries.
 */
require_once './libraries/config/FormDisplay.tpl.php';
require_once './libraries/config/validate.lib.php';
require_once './libraries/js_escape.lib.php';

/**
 * Form management class, displays and processes forms
 */
class FormDisplay
{
    /**
     * Form list
     * @var Form[]
     */
    private $forms = array();

    /**
     * Stores validation errors, indexed by paths
     * [ Form_name ] is an array of form errors
     * [path] is a string storing error associated with single field
     * @var array
     */
    private $errors = array();

    /**
     * Paths changed so that they can be used as HTML ids, indexed by paths
     * @var array
     */
    private $translated_paths = array();

    /**
     * Server paths change indexes so we define maps from current server
     * path to the first one, indexed by work path
     * @var array
     */
    private $system_paths = array();

    /**
     * Language strings which will be sent to PMA_messages JS variable
     * Will be looked up in $GLOBALS: str{value} or strSetup{value}
     * @var array
     */
    private $js_lang_strings = array();

    /**
     * Tells whether forms have been validated
     * @var bool
     */
    private $is_validated = true;

    /**
     * Dictionary with user preferences keys
     * @var array
     */
    private $userprefs_keys;

    /**
     * Dictionary with disallowed user preferences keys
     * @var array
     */
    private $userprefs_disallow;

    public function __construct()
    {
        $this->js_lang_strings = array(
            'error_nan_p' => __('Not a positive number'),
            'error_nan_nneg' => __('Not a non-negative number'),
            'error_incorrect_port' => __('Not a valid port number'),
            'error_invalid_value' => __('Incorrect value'),
            'error_value_lte' => __('Value must be equal or lower than %s'));
        // initialize validators
        PMA_config_get_validators();
    }

    /**
     * Registers form in form manager
     *
     * @param string $form_name
     * @param array  $form
     * @param int    $server_id 0 if new server, validation; >= 1 if editing a server
     */
    public function registerForm($form_name, array $form, $server_id = null)
    {
        $this->forms[$form_name] = new Form($form_name, $form, $server_id);
        $this->is_validated = false;
        foreach ($this->forms[$form_name]->fields as $path) {
            $work_path = $server_id === null
                ? $path
                : str_replace('Servers/1/', "Servers/$server_id/", $path);
            $this->system_paths[$work_path] = $path;
            $this->translated_paths[$work_path] = str_replace('/', '-', $work_path);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param  bool  $allow_partial_save  allows for partial form saving on failed validation
     * @param  bool  $check_form_submit   whether check for $_POST['submit_save']
     * @return boolean
     */
    public function process($allow_partial_save = true, $check_form_submit = true)
    {
        if ($check_form_submit && !isset($_POST['submit_save'])) {
            return false;
        }

        // save forms
        if (count($this->forms) > 0) {
            return $this->save(array_keys($this->forms), $allow_partial_save);
        }
        return false;
    }

    /**
     * Runs validation for all registered forms
     *
     */
    private function _validate()
    {
        if ($this->is_validated) {
            return;
        }

        $cf = ConfigFile::getInstance();
        $paths = array();
        $values = array();
        foreach ($this->forms as $form) {
            /* @var $form Form */
            $paths[] = $form->name;
            // collect values and paths
            foreach ($form->fields as $path) {
                $work_path = array_search($path, $this->system_paths);
                $values[$path] = $cf->getValue($work_path);
                $paths[] = $path;
            }
        }

        // run validation
        $errors = PMA_config_validate($paths, $values, false);

        // change error keys from canonical paths to work paths
        if (is_array($errors) && count($errors) > 0) {
            $this->errors = array();
            foreach ($errors as $path => $error_list) {
                $work_path = array_search($path, $this->system_paths);
                // field error
                if (!$work_path) {
                    // form error, fix path
                    $work_path = $path;
                }
                $this->errors[$work_path] = $error_list;
            }
        }
        $this->is_validated = true;
    }

    /**
     * Outputs HTML for forms
     *
     * @param bool $tabbed_form
     * @param bool   $show_restore_default  whether show "restore default" button besides the input field
     */
    public function display($tabbed_form = false, $show_restore_default = false)
    {
        static $js_lang_sent = false;

        $js = array();
        $js_default = array();
        $tabbed_form = $tabbed_form && (count($this->forms) > 1);
        $validators = PMA_config_get_validators();

        display_form_top();

        if ($tabbed_form) {
            $tabs = array();
            foreach ($this->forms as $form) {
                $tabs[$form->name] = PMA_lang("Form_$form->name");
            }
            display_tabs_top($tabs);
        }

        // valdiate only when we aren't displaying a "new server" form
        $is_new_server = false;
        foreach ($this->forms as $form) {
            /* @var $form Form */
            if ($form->index === 0) {
                $is_new_server = true;
                break;
            }
        }
        if (!$is_new_server) {
            $this->_validate();
        }

        // user preferences
        $this->_loadUserprefsInfo();

        // display forms
        foreach ($this->forms as $form) {
            /* @var $form Form */
            $form_desc = isset($GLOBALS["strConfigForm_{$form->name}_desc"])
                ? PMA_lang("Form_{$form->name}_desc")
                : '';
            $form_errors = isset($this->errors[$form->name])
                ? $this->errors[$form->name] : null;
            display_fieldset_top(
                PMA_lang("Form_$form->name"),
                $form_desc,
                $form_errors,
                array('id' => $form->name)
            );

            foreach ($form->fields as $field => $path) {
                $work_path = array_search($path, $this->system_paths);
                $translated_path = $this->translated_paths[$work_path];
                // always true/false for user preferences display
                // otherwise null
                $userprefs_allow = isset($this->userprefs_keys[$path])
                    ? !isset($this->userprefs_disallow[$path])
                    : null;
                // display input
                $this->_displayFieldInput(
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
                    js_validate($translated_path, $validators[$path], $js);
                }
            }
            display_fieldset_bottom();
        }

        if ($tabbed_form) {
            display_tabs_bottom();
        }
        display_form_bottom();

        // if not already done, send strings used for valdiation to JavaScript
        if (!$js_lang_sent) {
            $js_lang_sent = true;
            $js_lang = array();
            foreach ($this->js_lang_strings as $strName => $strValue) {
                $js_lang[] = "'$strName': '" . PMA_jsFormat($strValue, false) . '\'';
            }
            $js[] = "$.extend(PMA_messages, {\n\t" . implode(",\n\t", $js_lang) . '})';
        }

        $js[] = "$.extend(defaultValues, {\n\t" . implode(",\n\t", $js_default) . '})';
        display_js($js);
    }

    /**
     * Prepares data for input field display and outputs HTML code
     *
     * @param Form   $form
     * @param string $field                 field name as it appears in $form
     * @param string $system_path           field path, eg. Servers/1/verbose
     * @param string $work_path             work path, eg. Servers/4/verbose
     * @param string $translated_path       work path changed so that it can be used as XHTML id
     * @param bool   $show_restore_default  whether show "restore default" button besides the input field
     * @param mixed  $userprefs_allow       whether user preferences are enabled for this field
     *                                      (null - no support, true/false - enabled/disabled)
     * @param array  &$js_default           array which stores JavaScript code to be displayed
     */
    private function _displayFieldInput(Form $form, $field, $system_path, $work_path,
            $translated_path, $show_restore_default, $userprefs_allow, array &$js_default)
    {
        $name = PMA_lang_name($system_path);
        $description = PMA_lang_name($system_path, 'desc', '');

        $cf = ConfigFile::getInstance();
        $value = $cf->get($work_path);
        $value_default = $cf->getDefault($system_path);
        $value_is_default = false;
        if ($value === null || $value === $value_default) {
            $value = $value_default;
            $value_is_default = true;
        }

        $opts = array(
            'doc' => $this->getDocLink($system_path),
            'wiki' =>  $this->getWikiLink($system_path),
            'show_restore_default' => $show_restore_default,
            'userprefs_allow' => $userprefs_allow,
            'userprefs_comment' => PMA_lang_name($system_path, 'cmt', ''));
        if (isset($form->default[$system_path])) {
            $opts['setvalue'] = $form->default[$system_path];
        }

        if (isset($this->errors[$work_path])) {
            $opts['errors'] = $this->errors[$work_path];
        }
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
                if (substr($field, 7, 4) != 'end:') { // :group:end is changed to :group:end:{unique id} in Form class
                    display_group_header(substr($field, 7));
                } else {
                    display_group_footer();
                }
                return;
            case 'NULL':
                trigger_error("Field $system_path has no type", E_USER_WARNING);
                return;
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
                $js_line .= '\'' . PMA_escapeJsString(implode("\n", $value_default)) . '\'';
                break;
        }
        $js_default[] = $js_line;

        display_input($translated_path, $name, $description, $type,
            $value, $value_is_default, $opts);
    }

    /**
     * Displays errors
     *
     */
    public function displayErrors()
    {
        $this->_validate();
        if (count($this->errors) == 0) {
            return;
        }

        foreach ($this->errors as $system_path => $error_list) {
            if (isset($this->system_paths[$system_path])) {
                $path = $this->system_paths[$system_path];
                $name = PMA_lang_name($path);
            } else {
                $name = $GLOBALS["strConfigForm_$system_path"];
            }
            display_errors($name, $error_list);
        }
    }

    /**
     * Reverts erroneous fields to their default values
     *
     *
     */
    public function fixErrors()
    {
        $this->_validate();
        if (count($this->errors) == 0) {
            return;
        }

        $cf = ConfigFile::getInstance();
        foreach (array_keys($this->errors) as $work_path) {
            if (!isset($this->system_paths[$work_path])) {
                continue;
            }
            $canonical_path = $this->system_paths[$work_path];
            $cf->set($work_path, $cf->getDefault($canonical_path));
        }
    }

    /**
     * Validates select field and casts $value to correct type
     *
     * @param  string  $value
     * @param  array   $allowed
     * @return bool
     */
    private function _validateSelect(&$value, array $allowed)
    {
        $value_cmp = is_bool($value)
            ? (int) $value
            : $value;
        foreach ($allowed as $vk => $v) {
            // equality comparison only if both values are numeric or not numeric
            // (allows to skip 0 == 'string' equalling to true) or identity (for string-string)
            if (($vk == $value && !(is_numeric($value_cmp) xor is_numeric($vk)))
                    || $vk === $value) {
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
     * @param  array|string  $forms               array of form names
     * @param  bool          $allow_partial_save  allows for partial form saving on failed validation
     * @return boolean  true on success (no errors and all saved)
     */
    public function save($forms, $allow_partial_save = true)
    {
        $result = true;
        $cf = ConfigFile::getInstance();
        $forms = (array) $forms;

        $values = array();
        $to_save = array();
        $is_setup_script = defined('PMA_SETUP');
        if ($is_setup_script) {
            $this->_loadUserprefsInfo();
        }

        $this->errors = array();
        foreach ($forms as $form_name) {
            /* @var $form Form */
            if (isset($this->forms[$form_name])) {
                $form = $this->forms[$form_name];
            } else {
                continue;
            }
            // get current server id
            $change_index = $form->index === 0
                ? $cf->getServerCount() + 1
                : false;
            // grab POST values
            foreach ($form->fields as $field => $system_path) {
                $work_path = array_search($system_path, $this->system_paths);
                $key = $this->translated_paths[$work_path];
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
                        $this->errors[$form->name][] = sprintf(
                            __('Missing data for %s'),
                            '<i>' . PMA_lang_name($system_path) . '</i>');
                        $result = false;
                        continue;
                    }
                }

                // user preferences allow/disallow
                if ($is_setup_script && isset($this->userprefs_keys[$system_path])) {
                    if (isset($this->userprefs_disallow[$system_path])
                            && isset($_POST[$key . '-userprefs-allow'])) {
                        unset($this->userprefs_disallow[$system_path]);
                    } else if (!isset($_POST[$key . '-userprefs-allow'])) {
                        $this->userprefs_disallow[$system_path] = true;
                    }
                }

                // cast variables to correct type
                switch ($type) {
                    case 'double':
                        settype($_POST[$key], 'float');
                        break;
                    case 'boolean':
                    case 'integer':
                        if ($_POST[$key] !== '') {
                            settype($_POST[$key], $type);
                        }
                        break;
                    case 'select':
                        // special treatment for NavigationBarIconic and PropertiesIconic
                        if ($key === 'NavigationBarIconic' || $key === 'PropertiesIconic') {
                            if ($_POST[$key] !== 'both') {
                                settype($_POST[$key], 'boolean');
                            }
                        }
                        if (!$this->_validateSelect($_POST[$key], $form->getOptionValueList($system_path))) {
                            $this->errors[$work_path][] = __('Incorrect value');
                            $result = false;
                            continue;
                        }
                        break;
                    case 'string':
                    case 'short_string':
                        $_POST[$key] = trim($_POST[$key]);
                        break;
                    case 'array':
                        // eliminate empty values and ensure we have an array
                        $post_values = is_array($_POST[$key])
                            ? $_POST[$key]
                            : explode("\n", $_POST[$key]);
                        $_POST[$key] = array();
                        foreach ($post_values as $v) {
                            $v = trim($v);
                            if ($v !== '') {
                                $_POST[$key][] = $v;
                            }
                        }
                        break;
                }

                // now we have value with proper type
                $values[$system_path] = $_POST[$key];
                if ($change_index !== false) {
                    $work_path = str_replace("Servers/$form->index/",
                      "Servers/$change_index/", $work_path);
                }
                $to_save[$work_path] = $system_path;
            }
        }

        // save forms
        if ($allow_partial_save || empty($this->errors)) {
            foreach ($to_save as $work_path => $path) {
                // TrustedProxies requires changes before saving
                if ($path == 'TrustedProxies') {
                    $proxies = array();
                    $i = 0;
                    foreach ($values[$path] as $value) {
                        $matches = array();
                        if (preg_match("/^(.+):(?:[ ]?)(\\w+)$/", $value, $matches)) {
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
                $cf->set($work_path, $values[$path], $path);
            }
            if ($is_setup_script) {
                $cf->set('UserprefsDisallow', array_keys($this->userprefs_disallow));
            }
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
        return count($this->errors) > 0;
    }


    /**
     * Returns link to documentation
     *
     * @param string $path
     * @return string
     */
    public function getDocLink($path)
    {
        $test = substr($path, 0, 6);
        if ($test == 'Import' || $test == 'Export') {
            return '';
        }
        return 'Documentation.html#cfg_' . $this->_getOptName($path);
    }

    /**
     * Returns link to wiki
     *
     * @param string $path
     * @return string
     */
    public function getWikiLink($path)
    {
        $opt_name = $this->_getOptName($path);
        if (substr($opt_name, 0, 7) == 'Servers') {
            $opt_name = substr($opt_name, 8);
            if (strpos($opt_name, 'AllowDeny') === 0) {
                $opt_name = str_replace('_', '_.28', $opt_name) . '.29';
            }
        }
        $test = substr($path, 0, 6);
        if ($test == 'Import') {
            $opt_name = substr($opt_name, 7);
            if ($opt_name == 'format') {
                $opt_name = 'format_2';
            }
        }
        if ($test == 'Export') {
            $opt_name = substr($opt_name, 7);
        }
        return PMA_linkURL('http://wiki.phpmyadmin.net/pma/Config#' . $opt_name);
    }

    /**
     * Changes path so it can be used in URLs
     *
     * @param string $path
     * @return string
     */
    private function _getOptName($path)
    {
        return str_replace(array('Servers/1/', '/'), array('Servers/', '_'), $path);
    }

    /**
     * Fills out {@link userprefs_keys} and {@link userprefs_disallow}
     *
     */
    private function _loadUserprefsInfo()
    {
        if ($this->userprefs_keys === null) {
            $this->userprefs_keys = array_flip(PMA_read_userprefs_fieldnames());
            // read real config for user preferences display
            $userprefs_disallow = defined('PMA_SETUP')
                ? ConfigFile::getInstance()->get('UserprefsDisallow', array())
                : $GLOBALS['cfg']['UserprefsDisallow'];
            $this->userprefs_disallow = array_flip($userprefs_disallow);
        }
    }

    /**
     * Sets field comments and warnings based on current environment
     *
     * @param string $system_path
     * @param array  $opts
     */
    private function _setComments($system_path, array &$opts)
    {
        // RecodingEngine - mark unavailable types
        if ($system_path == 'RecodingEngine') {
            $comment = '';
            if (!function_exists('iconv')) {
                $opts['values']['iconv'] .= ' (' . __('unavailable') . ')';
                $comment = sprintf(__('"%s" requires %s extension'), 'iconv', 'iconv');
            }
            if (!function_exists('recode_string')) {
                $opts['values']['recode'] .= ' (' . __('unavailable') . ')';
                $comment .= ($comment ? ", " : '') . sprintf(__('"%s" requires %s extension'),
                    'recode', 'recode');
            }
            $opts['comment'] = $comment;
            $opts['comment_warning'] = true;
        }
        // ZipDump, GZipDump, BZipDump - check function availability
        if ($system_path == 'ZipDump' || $system_path == 'GZipDump' || $system_path == 'BZipDump') {
            $comment = '';
            $funcs = array(
                'ZipDump'  => array('zip_open', 'gzcompress'),
                'GZipDump' => array('gzopen', 'gzencode'),
                'BZipDump' => array('bzopen', 'bzcompress'));
            if (!function_exists($funcs[$system_path][0])) {
                $comment = sprintf(__('import will not work, missing function (%s)'),
                    $funcs[$system_path][0]);
            }
            if (!function_exists($funcs[$system_path][1])) {
                $comment .= ($comment ? '; ' : '') . sprintf(__('export will not work, missing function (%s)'),
                    $funcs[$system_path][1]);
            }
            $opts['comment'] = $comment;
            $opts['comment_warning'] = true;
        }
        if ($system_path == 'SQLQuery/Validate' && !$GLOBALS['cfg']['SQLValidator']['use']) {
            $opts['comment'] = __('SQL Validator is disabled');
            $opts['comment_warning'] = true;
        }
        if ($system_path == 'SQLValidator/use') {
            if (!class_exists('SOAPClient')) {
                @include_once 'SOAP/Client.php';
                if (!class_exists('SOAP_Client')) {
                    $opts['comment'] = __('SOAP extension not found');
                    $opts['comment_warning'] = true;
                }
            }
        }
        if (!defined('PMA_SETUP')) {
            if (($system_path == 'MaxDbList' || $system_path == 'MaxTableList'
                    || $system_path == 'QueryHistoryMax')) {
                $opts['comment'] = sprintf(__('maximum %s'), $GLOBALS['cfg'][$system_path]);
            }
        }
    }
}
?>
