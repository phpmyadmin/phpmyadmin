<?php
/**
 * Form templates
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use function array_shift;
use function implode;

/**
 * PhpMyAdmin\Config\FormDisplayTemplate class
 */
class FormDisplayTemplate
{
    /** @var int */
    public $group;

    /** @var Config */
    protected $config;

    /** @var Template */
    public $template;

    /**
     * @param Config $config Config instance
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->template = new Template();
    }

    /**
     * Displays top part of the form
     *
     * @param string     $action       default: $_SERVER['REQUEST_URI']
     * @param string     $method       'post' or 'get'
     * @param array|null $hiddenFields array of form hidden fields (key: field name)
     */
    public function displayFormTop(
        $action = null,
        $method = 'post',
        $hiddenFields = null
    ): string {
        static $hasCheckPageRefresh = false;

        if ($action === null) {
            $action = $_SERVER['REQUEST_URI'];
        }
        if ($method !== 'post') {
            $method = 'get';
        }

        /**
         * We do validation on page refresh when browser remembers field values,
         * add a field with known value which will be used for checks.
         */
        if (! $hasCheckPageRefresh) {
            $hasCheckPageRefresh = true;
        }

        return $this->template->render('config/form_display/form_top', [
            'method' => $method,
            'action' => $action,
            'has_check_page_refresh' => $hasCheckPageRefresh,
            'hidden_fields' => (array) $hiddenFields,
        ]);
    }

    /**
     * Displays form tabs which are given by an array indexed by fieldset id
     * ({@link self::displayFieldsetTop}), with values being tab titles.
     *
     * @param array $tabs tab names
     */
    public function displayTabsTop(array $tabs): string
    {
        return $this->template->render('config/form_display/tabs_top', ['tabs' => $tabs]);
    }

    /**
     * Displays top part of a fieldset
     *
     * @param string     $title       title of fieldset
     * @param string     $description description shown on top of fieldset
     * @param array|null $errors      error messages to display
     */
    public function displayFieldsetTop(
        $title = '',
        $description = '',
        $errors = null,
        string $name = '',
        bool $firstTab = false
    ): string {
        $this->group = 0;

        return $this->template->render('config/form_display/fieldset_top', [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'errors' => $errors,
            'first_tab' => $firstTab,
        ]);
    }

    /**
     * Displays input field
     *
     * $opts keys:
     * o doc - (string) documentation link
     * o errors - error array
     * o setvalue - (string) shows button allowing to set predefined value
     * o show_restore_default - (boolean) whether show "restore default" button
     * o userprefs_allow - whether user preferences are enabled for this field
     *                    (null - no support, true/false - enabled/disabled)
     * o userprefs_comment - (string) field comment
     * o values - key - value pairs for <select> fields
     * o values_escaped - (boolean) tells whether values array is already escaped
     *                    (defaults to false)
     * o values_disabled -  (array)list of disabled values (keys from values)
     * o comment - (string) tooltip comment
     * o comment_warning - (bool) whether this comments warns about something
     *
     * @param string     $path           config option path
     * @param string     $name           config option name
     * @param string     $type           type of config option
     * @param mixed      $value          current value
     * @param string     $description    verbose description
     * @param bool       $valueIsDefault whether value is default
     * @param array|null $opts           see above description
     */
    public function displayInput(
        $path,
        $name,
        $type,
        $value,
        $description = '',
        $valueIsDefault = true,
        $opts = null
    ): string {
        $isSetupScript = $this->config->get('is_setup');
        $optionIsDisabled = ! $isSetupScript && isset($opts['userprefs_allow']) && ! $opts['userprefs_allow'];
        $trClass = $this->group > 0 ? 'group-field group-field-' . $this->group : '';
        if (isset($opts['setvalue']) && $opts['setvalue'] === ':group') {
            unset($opts['setvalue']);
            $this->group++;
            $trClass = 'group-header-field group-header-' . $this->group;
        }

        return $this->template->render('config/form_display/input', [
            'is_setup' => $isSetupScript,
            'allows_customization' => $opts['userprefs_allow'] ?? null,
            'path' => $path,
            'has_errors' => isset($opts['errors']) && ! empty($opts['errors']),
            'errors' => $opts['errors'] ?? [],
            'show_restore_default' => $opts['show_restore_default'] ?? null,
            'set_value' => $opts['setvalue'] ?? null,
            'tr_class' => $trClass,
            'name' => $name,
            'doc' => $opts['doc'] ?? '',
            'option_is_disabled' => $optionIsDisabled,
            'description' => $description,
            'comment' => $opts['userprefs_comment'] ?? null,
            'type' => $type,
            'value' => $value,
            'value_is_default' => $valueIsDefault,
            'select_values' => $opts['values'] ?? [],
            'select_values_disabled' => $opts['values_disabled'] ?? [],
        ]);
    }

    /**
     * Display group header
     *
     * @param string $headerText Text of header
     */
    public function displayGroupHeader(string $headerText): string
    {
        $this->group++;
        if ($headerText === '') {
            return '';
        }
        $colspan = $this->config->get('is_setup') ? 3 : 2;

        return $this->template->render('config/form_display/group_header', [
            'group' => $this->group,
            'colspan' => $colspan,
            'header_text' => $headerText,
        ]);
    }

    /**
     * Display group footer
     */
    public function displayGroupFooter(): void
    {
        $this->group--;
    }

    /**
     * Displays bottom part of a fieldset
     *
     * @param bool $showButtons Whether show submit and reset button
     */
    public function displayFieldsetBottom(bool $showButtons = true): string
    {
        return $this->template->render('config/form_display/fieldset_bottom', ['show_buttons' => $showButtons]);
    }

    /**
     * Closes form tabs
     */
    public function displayTabsBottom(): string
    {
        return $this->template->render('config/form_display/tabs_bottom');
    }

    /**
     * Displays bottom part of the form
     */
    public function displayFormBottom(): string
    {
        return $this->template->render('config/form_display/form_bottom');
    }

    /**
     * Appends JS validation code to $js_array
     *
     * @param string       $fieldId    ID of field to validate
     * @param string|array $validators validators callback
     * @param array        $jsArray    will be updated with javascript code
     */
    public function addJsValidate($fieldId, $validators, array &$jsArray): void
    {
        foreach ((array) $validators as $validator) {
            $validator = (array) $validator;
            $vName = array_shift($validator);
            $vArgs = [];
            foreach ($validator as $arg) {
                $vArgs[] = Sanitize::escapeJsString($arg);
            }
            $vArgs = $vArgs ? ", ['" . implode("', '", $vArgs) . "']" : '';
            $jsArray[] = "registerFieldValidator('" . $fieldId . "', '" . $vName . "', true" . $vArgs . ')';
        }
    }

    /**
     * Displays JavaScript code
     *
     * @param array $jsArray lines of javascript code
     */
    public function displayJavascript(array $jsArray): string
    {
        if (empty($jsArray)) {
            return '';
        }

        return $this->template->render('javascript/display', ['js_array' => $jsArray]);
    }

    /**
     * Displays error list
     *
     * @param string $name      Name of item with errors
     * @param array  $errorList List of errors to show
     *
     * @return string HTML for errors
     */
    public function displayErrors($name, array $errorList): string
    {
        return $this->template->render('config/form_display/errors', [
            'name' => $name,
            'error_list' => $errorList,
        ]);
    }
}
