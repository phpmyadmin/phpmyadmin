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
declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;

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
    private $_forms = [];

    /**
     * Stores validation errors, indexed by paths
     * [ Form_name ] is an array of form errors
     * [path] is a string storing error associated with single field
     * @var array
     */
    private $_errors = [];

    /**
     * Paths changed so that they can be used as HTML ids, indexed by paths
     * @var array
     */
    private $_translatedPaths = [];

    /**
     * Server paths change indexes so we define maps from current server
     * path to the first one, indexed by work path
     * @var array
     */
    private $_systemPaths = [];

    /**
     * Language strings which will be sent to Messages JS variable
     * Will be looked up in $GLOBALS: str{value} or strSetup{value}
     * @var array
     */
    private $_jsLangStrings = [];

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
     * @var FormDisplayTemplate
     */
    private $formDisplayTemplate;

    /**
     * Constructor
     *
     * @param ConfigFile $cf Config file instance
     */
    public function __construct(ConfigFile $cf)
    {
        $this->formDisplayTemplate = new FormDisplayTemplate($GLOBALS['PMA_Config']);
        $this->_jsLangStrings = [
            'error_nan_p' => __('Not a positive number!'),
            'error_nan_nneg' => __('Not a non-negative number!'),
            'error_incorrect_port' => __('Not a valid port number!'),
            'error_invalid_value' => __('Incorrect value!'),
            'error_value_lte' => __('Value must be less than or equal to %s!'),
        ];
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
     * @param string $formName Form name
     * @param array  $form     Form data
     * @param int    $serverId 0 if new server, validation; >= 1 if editing a server
     *
     * @return void
     */
    public function registerForm($formName, array $form, $serverId = null)
    {
        $this->_forms[$formName] = new Form(
            $formName,
            $form,
            $this->_configFile,
            $serverId
        );
        $this->_isValidated = false;
        foreach ($this->_forms[$formName]->fields as $path) {
            $workPath = $serverId === null
                ? $path
                : str_replace('Servers/1/', "Servers/$serverId/", $path);
            $this->_systemPaths[$workPath] = $path;
            $this->_translatedPaths[$workPath] = str_replace('/', '-', $workPath);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param bool $allowPartialSave allows for partial form saving
     *                               on failed validation
     * @param bool $checkFormSubmit  whether check for $_POST['submit_save']
     *
     * @return boolean whether processing was successful
     */
    public function process($allowPartialSave = true, $checkFormSubmit = true)
    {
        if ($checkFormSubmit && ! isset($_POST['submit_save'])) {
            return false;
        }

        // save forms
        if (count($this->_forms) > 0) {
            return $this->save(array_keys($this->_forms), $allowPartialSave);
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

        $paths = [];
        $values = [];
        foreach ($this->_forms as $form) {
            /** @var Form $form */
            $paths[] = $form->name;
            // collect values and paths
            foreach ($form->fields as $path) {
                $workPath = array_search($path, $this->_systemPaths);
                $values[$path] = $this->_configFile->getValue($workPath);
                $paths[] = $path;
            }
        }

        // run validation
        $errors = Validator::validate(
            $this->_configFile,
            $paths,
            $values,
            false
        );

        // change error keys from canonical paths to work paths
        if (is_array($errors) && count($errors) > 0) {
            $this->_errors = [];
            foreach ($errors as $path => $errorList) {
                $workPath = array_search($path, $this->_systemPaths);
                // field error
                if (! $workPath) {
                    // form error, fix path
                    $workPath = $path;
                }
                $this->_errors[$workPath] = $errorList;
            }
        }
        $this->_isValidated = true;
    }

    /**
     * Outputs HTML for the forms under the menu tab
     *
     * @param bool  $showRestoreDefault whether to show "restore default"
     *                                  button besides the input field
     * @param array $jsDefault          stores JavaScript code
     *                                  to be displayed
     * @param array $js                 will be updated with javascript code
     * @param bool  $showButtons        whether show submit and reset button
     *
     * @return string
     */
    private function _displayForms(
        $showRestoreDefault,
        array &$jsDefault,
        array &$js,
        $showButtons
    ) {
        $htmlOutput = '';
        $validators = Validator::getValidators($this->_configFile);

        foreach ($this->_forms as $form) {
            /** @var Form $form */
            $formErrors = isset($this->_errors[$form->name])
                ? $this->_errors[$form->name] : null;
            $htmlOutput .= $this->formDisplayTemplate->displayFieldsetTop(
                Descriptions::get("Form_{$form->name}"),
                Descriptions::get("Form_{$form->name}", 'desc'),
                $formErrors,
                ['id' => $form->name]
            );

            foreach ($form->fields as $field => $path) {
                $workPath = array_search($path, $this->_systemPaths);
                $translatedPath = $this->_translatedPaths[$workPath];
                // always true/false for user preferences display
                // otherwise null
                $userPrefsAllow = isset($this->_userprefsKeys[$path])
                    ? ! isset($this->_userprefsDisallow[$path])
                    : null;
                // display input
                $htmlOutput .= $this->_displayFieldInput(
                    $form,
                    $field,
                    $path,
                    $workPath,
                    $translatedPath,
                    $showRestoreDefault,
                    $userPrefsAllow,
                    $jsDefault
                );
                // register JS validators for this field
                if (isset($validators[$path])) {
                    $this->formDisplayTemplate->addJsValidate($translatedPath, $validators[$path], $js);
                }
            }
            $htmlOutput .= $this->formDisplayTemplate->displayFieldsetBottom($showButtons);
        }
        return $htmlOutput;
    }

    /**
     * Outputs HTML for forms
     *
     * @param bool       $tabbedForm         if true, use a form with tabs
     * @param bool       $showRestoreDefault whether show "restore default" button
     *                                       besides the input field
     * @param bool       $showButtons        whether show submit and reset button
     * @param string     $formAction         action attribute for the form
     * @param array|null $hiddenFields       array of form hidden fields (key: field
     *                                       name)
     *
     * @return string HTML for forms
     */
    public function getDisplay(
        $tabbedForm = false,
        $showRestoreDefault = false,
        $showButtons = true,
        $formAction = null,
        $hiddenFields = null
    ) {
        static $jsLangSent = false;

        $htmlOutput = '';

        $js = [];
        $jsDefault = [];

        $htmlOutput .= $this->formDisplayTemplate->displayFormTop($formAction, 'post', $hiddenFields);

        if ($tabbedForm) {
            $tabs = [];
            foreach ($this->_forms as $form) {
                $tabs[$form->name] = Descriptions::get("Form_$form->name");
            }
            $htmlOutput .= $this->formDisplayTemplate->displayTabsTop($tabs);
        }

        // validate only when we aren't displaying a "new server" form
        $isNewServer = false;
        foreach ($this->_forms as $form) {
            /** @var Form $form */
            if ($form->index === 0) {
                $isNewServer = true;
                break;
            }
        }
        if (! $isNewServer) {
            $this->_validate();
        }

        // user preferences
        $this->_loadUserprefsInfo();

        // display forms
        $htmlOutput .= $this->_displayForms(
            $showRestoreDefault,
            $jsDefault,
            $js,
            $showButtons
        );

        if ($tabbedForm) {
            $htmlOutput .= $this->formDisplayTemplate->displayTabsBottom();
        }
        $htmlOutput .= $this->formDisplayTemplate->displayFormBottom();

        // if not already done, send strings used for validation to JavaScript
        if (! $jsLangSent) {
            $jsLangSent = true;
            $jsLang = [];
            foreach ($this->_jsLangStrings as $strName => $strValue) {
                $jsLang[] = "'$strName': '" . Sanitize::jsFormat($strValue, false) . '\'';
            }
            $js[] = "$.extend(Messages, {\n\t"
                . implode(",\n\t", $jsLang) . '})';
        }

        $js[] = "$.extend(defaultValues, {\n\t"
            . implode(",\n\t", $jsDefault) . '})';
        $htmlOutput .= $this->formDisplayTemplate->displayJavascript($js);

        return $htmlOutput;
    }

    /**
     * Prepares data for input field display and outputs HTML code
     *
     * @param Form      $form               Form object
     * @param string    $field              field name as it appears in $form
     * @param string    $systemPath         field path, eg. Servers/1/verbose
     * @param string    $workPath           work path, eg. Servers/4/verbose
     * @param string    $translatedPath     work path changed so that it can be
     *                                      used as XHTML id
     * @param bool      $showRestoreDefault whether show "restore default" button
     *                                      besides the input field
     * @param bool|null $userPrefsAllow     whether user preferences are enabled
     *                                      for this field (null - no support,
     *                                      true/false - enabled/disabled)
     * @param array     $jsDefault          array which stores JavaScript code
     *                                      to be displayed
     *
     * @return string|null HTML for input field
     */
    private function _displayFieldInput(
        Form $form,
        $field,
        $systemPath,
        $workPath,
        $translatedPath,
        $showRestoreDefault,
        $userPrefsAllow,
        array &$jsDefault
    ) {
        $name = Descriptions::get($systemPath);
        $description = Descriptions::get($systemPath, 'desc');

        $value = $this->_configFile->get($workPath);
        $valueDefault = $this->_configFile->getDefault($systemPath);
        $valueIsDefault = false;
        if ($value === null || $value === $valueDefault) {
            $value = $valueDefault;
            $valueIsDefault = true;
        }

        $opts = [
            'doc' => $this->getDocLink($systemPath),
            'show_restore_default' => $showRestoreDefault,
            'userprefs_allow' => $userPrefsAllow,
            'userprefs_comment' => Descriptions::get($systemPath, 'cmt'),
        ];
        if (isset($form->default[$systemPath])) {
            $opts['setvalue'] = (string) $form->default[$systemPath];
        }

        if (isset($this->_errors[$workPath])) {
            $opts['errors'] = $this->_errors[$workPath];
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
                $valueDefault = (array) $valueDefault;
                break;
            case 'group':
                // :group:end is changed to :group:end:{unique id} in Form class
                $htmlOutput = '';
                if (mb_substr($field, 7, 4) != 'end:') {
                    $htmlOutput .= $this->formDisplayTemplate->displayGroupHeader(
                        mb_substr($field, 7)
                    );
                } else {
                    $this->formDisplayTemplate->displayGroupFooter();
                }
                return $htmlOutput;
            case 'NULL':
                trigger_error("Field $systemPath has no type", E_USER_WARNING);
                return null;
        }

        // detect password fields
        if ($type === 'text'
            && (mb_substr($translatedPath, -9) === '-password'
               || mb_substr($translatedPath, -4) === 'pass'
               || mb_substr($translatedPath, -4) === 'Pass')
        ) {
            $type = 'password';
        }

        // TrustedProxies requires changes before displaying
        if ($systemPath == 'TrustedProxies') {
            foreach ($value as $ip => &$v) {
                if (! preg_match('/^-\d+$/', $ip)) {
                    $v = $ip . ': ' . $v;
                }
            }
        }
        $this->_setComments($systemPath, $opts);

        // send default value to form's JS
        $jsLine = '\'' . $translatedPath . '\': ';
        switch ($type) {
            case 'text':
            case 'short_text':
            case 'number_text':
            case 'password':
                $jsLine .= '\'' . Sanitize::escapeJsString($valueDefault) . '\'';
                break;
            case 'checkbox':
                $jsLine .= $valueDefault ? 'true' : 'false';
                break;
            case 'select':
                $valueDefaultJs = is_bool($valueDefault)
                ? (int) $valueDefault
                : $valueDefault;
                $jsLine .= '[\'' . Sanitize::escapeJsString($valueDefaultJs) . '\']';
                break;
            case 'list':
                $jsLine .= '\'' . Sanitize::escapeJsString(implode("\n", $valueDefault))
                . '\'';
                break;
        }
        $jsDefault[] = $jsLine;

        return $this->formDisplayTemplate->displayInput(
            $translatedPath,
            $name,
            $type,
            $value,
            $description,
            $valueIsDefault,
            $opts
        );
    }

    /**
     * Displays errors
     *
     * @return string|null HTML for errors
     */
    public function displayErrors()
    {
        $this->_validate();
        if (count($this->_errors) === 0) {
            return null;
        }

        $htmlOutput = '';

        foreach ($this->_errors as $systemPath => $errorList) {
            if (isset($this->_systemPaths[$systemPath])) {
                $name = Descriptions::get($this->_systemPaths[$systemPath]);
            } else {
                $name = Descriptions::get('Form_' . $systemPath);
            }
            $htmlOutput .= $this->formDisplayTemplate->displayErrors($name, $errorList);
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
        if (count($this->_errors) === 0) {
            return;
        }

        $cf = $this->_configFile;
        foreach (array_keys($this->_errors) as $workPath) {
            if (! isset($this->_systemPaths[$workPath])) {
                continue;
            }
            $canonicalPath = $this->_systemPaths[$workPath];
            $cf->set($workPath, $cf->getDefault($canonicalPath));
        }
    }

    /**
     * Validates select field and casts $value to correct type
     *
     * @param string $value   Current value
     * @param array  $allowed List of allowed values
     *
     * @return bool
     */
    private function _validateSelect(&$value, array $allowed)
    {
        $valueCmp = is_bool($value)
            ? (int) $value
            : $value;
        foreach ($allowed as $vk => $v) {
            // equality comparison only if both values are numeric or not numeric
            // (allows to skip 0 == 'string' equalling to true)
            // or identity (for string-string)
            if (($vk == $value && ! (is_numeric($valueCmp) xor is_numeric($vk)))
                || $vk === $value
            ) {
                // keep boolean value as boolean
                if (! is_bool($value)) {
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
     * @param array|string $forms            array of form names
     * @param bool         $allowPartialSave allows for partial form saving on
     *                                       failed validation
     *
     * @return boolean true on success (no errors and all saved)
     */
    public function save($forms, $allowPartialSave = true)
    {
        $result = true;
        $forms = (array) $forms;

        $values = [];
        $toSave = [];
        $isSetupScript = $GLOBALS['PMA_Config']->get('is_setup');
        if ($isSetupScript) {
            $this->_loadUserprefsInfo();
        }

        $this->_errors = [];
        foreach ($forms as $formName) {
            /** @var Form $form */
            if (isset($this->_forms[$formName])) {
                $form = $this->_forms[$formName];
            } else {
                continue;
            }
            // get current server id
            $changeIndex = $form->index === 0
                ? $this->_configFile->getServerCount() + 1
                : false;
            // grab POST values
            foreach ($form->fields as $field => $systemPath) {
                $workPath = array_search($systemPath, $this->_systemPaths);
                $key = $this->_translatedPaths[$workPath];
                $type = $form->getOptionType($field);

                // skip groups
                if ($type == 'group') {
                    continue;
                }

                // ensure the value is set
                if (! isset($_POST[$key])) {
                    // checkboxes aren't set by browsers if they're off
                    if ($type == 'boolean') {
                        $_POST[$key] = false;
                    } else {
                        $this->_errors[$form->name][] = sprintf(
                            __('Missing data for %s'),
                            '<i>' . Descriptions::get($systemPath) . '</i>'
                        );
                        $result = false;
                        continue;
                    }
                }

                // user preferences allow/disallow
                if ($isSetupScript
                    && isset($this->_userprefsKeys[$systemPath])
                ) {
                    if (isset($this->_userprefsDisallow[$systemPath])
                        && isset($_POST[$key . '-userprefs-allow'])
                    ) {
                        unset($this->_userprefsDisallow[$systemPath]);
                    } elseif (! isset($_POST[$key . '-userprefs-allow'])) {
                        $this->_userprefsDisallow[$systemPath] = true;
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
                        $successfullyValidated = $this->_validateSelect(
                            $_POST[$key],
                            $form->getOptionValueList($systemPath)
                        );
                        if (! $successfullyValidated) {
                            $this->_errors[$workPath][] = __('Incorrect value!');
                            $result = false;
                            // "continue" for the $form->fields foreach-loop
                            continue 2;
                        }
                        break;
                    case 'string':
                    case 'short_string':
                        $_POST[$key] = Util::requestString($_POST[$key]);
                        break;
                    case 'array':
                        // eliminate empty values and ensure we have an array
                        $postValues = is_array($_POST[$key])
                        ? $_POST[$key]
                        : explode("\n", $_POST[$key]);
                        $_POST[$key] = [];
                        $this->_fillPostArrayParameters($postValues, $key);
                        break;
                }

                // now we have value with proper type
                $values[$systemPath] = $_POST[$key];
                if ($changeIndex !== false) {
                    $workPath = str_replace(
                        "Servers/$form->index/",
                        "Servers/$changeIndex/",
                        $workPath
                    );
                }
                $toSave[$workPath] = $systemPath;
            }
        }

        // save forms
        if (! $allowPartialSave && ! empty($this->_errors)) {
            // don't look for non-critical errors
            $this->_validate();
            return $result;
        }

        foreach ($toSave as $workPath => $path) {
            // TrustedProxies requires changes before saving
            if ($path == 'TrustedProxies') {
                $proxies = [];
                $i = 0;
                foreach ($values[$path] as $value) {
                    $matches = [];
                    $match = preg_match(
                        "/^(.+):(?:[ ]?)(\\w+)$/",
                        $value,
                        $matches
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
            $this->_configFile->set($workPath, $values[$path], $path);
        }
        if ($isSetupScript) {
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
            'cfg_' . $this->_getOptName($path)
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
        return str_replace(['Servers/1/', '/'], ['Servers/', '_'], $path);
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

        $this->_userprefsKeys = array_flip(UserFormList::getFields());
        // read real config for user preferences display
        $userPrefsDisallow = $GLOBALS['PMA_Config']->get('is_setup')
            ? $this->_configFile->get('UserprefsDisallow', [])
            : $GLOBALS['cfg']['UserprefsDisallow'];
        $this->_userprefsDisallow = array_flip($userPrefsDisallow ?? []);
    }

    /**
     * Sets field comments and warnings based on current environment
     *
     * @param string $systemPath Path to settings
     * @param array  $opts       Chosen options
     *
     * @return void
     */
    private function _setComments($systemPath, array &$opts)
    {
        // RecodingEngine - mark unavailable types
        if ($systemPath == 'RecodingEngine') {
            $comment = '';
            if (! function_exists('iconv')) {
                $opts['values']['iconv'] .= ' (' . __('unavailable') . ')';
                $comment = sprintf(
                    __('"%s" requires %s extension'),
                    'iconv',
                    'iconv'
                );
            }
            if (! function_exists('recode_string')) {
                $opts['values']['recode'] .= ' (' . __('unavailable') . ')';
                $comment .= ($comment ? ", " : '') . sprintf(
                    __('"%s" requires %s extension'),
                    'recode',
                    'recode'
                );
            }
            /* mbstring is always there thanks to polyfill */
            $opts['comment'] = $comment;
            $opts['comment_warning'] = true;
        }
        // ZipDump, GZipDump, BZipDump - check function availability
        if ($systemPath == 'ZipDump'
            || $systemPath == 'GZipDump'
            || $systemPath == 'BZipDump'
        ) {
            $comment = '';
            $funcs = [
                'ZipDump'  => [
                    'zip_open',
                    'gzcompress',
                ],
                'GZipDump' => [
                    'gzopen',
                    'gzencode',
                ],
                'BZipDump' => [
                    'bzopen',
                    'bzcompress',
                ],
            ];
            if (! function_exists($funcs[$systemPath][0])) {
                $comment = sprintf(
                    __(
                        'Compressed import will not work due to missing function %s.'
                    ),
                    $funcs[$systemPath][0]
                );
            }
            if (! function_exists($funcs[$systemPath][1])) {
                $comment .= ($comment ? '; ' : '') . sprintf(
                    __(
                        'Compressed export will not work due to missing function %s.'
                    ),
                    $funcs[$systemPath][1]
                );
            }
            $opts['comment'] = $comment;
            $opts['comment_warning'] = true;
        }
        if (! $GLOBALS['PMA_Config']->get('is_setup')) {
            if ($systemPath == 'MaxDbList' || $systemPath == 'MaxTableList'
                || $systemPath == 'QueryHistoryMax'
            ) {
                $opts['comment'] = sprintf(
                    __('maximum %s'),
                    $GLOBALS['cfg'][$systemPath]
                );
            }
        }
    }

    /**
     * Copy items of an array to $_POST variable
     *
     * @param array  $postValues List of parameters
     * @param string $key        Array key
     *
     * @return void
     */
    private function _fillPostArrayParameters(array $postValues, $key)
    {
        foreach ($postValues as $v) {
            $v = Util::requestString($v);
            if ($v !== '') {
                $_POST[$key][] = $v;
            }
        }
    }
}
