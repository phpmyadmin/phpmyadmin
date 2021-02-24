<?php
/**
 * Form templates
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use function array_flip;
use function array_merge;
use function array_shift;
use function defined;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function implode;
use function is_bool;
use function mb_strtolower;
use function sprintf;
use function is_string;

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
     * @param array      $attributes  optional extra attributes of fieldset
     */
    public function displayFieldsetTop(
        $title = '',
        $description = '',
        $errors = null,
        array $attributes = []
    ): string {
        $this->group = 0;

        $attributes = array_merge(['class' => 'optbox'], $attributes);

        return $this->template->render('config/form_display/fieldset_top', [
            'attributes' => $attributes,
            'title' => $title,
            'description' => $description,
            'errors' => $errors,
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
        static $icons;    // An array of IMG tags used further below in the function

        if (defined('TESTSUITE')) {
            $icons = null;
        }

        $isSetupScript = $this->config->get('is_setup');
        if ($icons === null) { // if the static variables have not been initialised
            $icons = [];
            // Icon definitions:
            // The same indexes will be used in the $icons array.
            // The first element contains the filename and the second
            // element is used for the "alt" and "title" attributes.
            $iconInit = [
                'edit'   => [
                    'b_edit',
                    '',
                ],
                'help'   => [
                    'b_help',
                    __('Documentation'),
                ],
                'reload' => [
                    's_reload',
                    '',
                ],
                'tblops' => [
                    'b_tblops',
                    '',
                ],
            ];
            if ($isSetupScript) {
                // When called from the setup script, we don't have access to the
                // sprite-aware getImage() function because the PMA_theme class
                // has not been loaded, so we generate the img tags manually.
                foreach ($iconInit as $k => $v) {
                    $title = '';
                    if (! empty($v[1])) {
                        $title = ' title="' . $v[1] . '"';
                    }
                    $icons[$k] = sprintf(
                        '<img alt="%s" src="%s"%s>',
                        $v[1],
                        '../themes/pmahomme/img/' . $v[0] . '.png',
                        $title
                    );
                }
            } else {
                // In this case we just use getImage() because it's available
                foreach ($iconInit as $k => $v) {
                    $icons[$k] = Generator::getImage(
                        $v[0],
                        $v[1]
                    );
                }
            }
        }
        $hasErrors = isset($opts['errors']) && ! empty($opts['errors']);
        $optionIsDisabled = ! $isSetupScript && isset($opts['userprefs_allow'])
            && ! $opts['userprefs_allow'];
        $nameId = 'name="' . htmlspecialchars($path) . '" id="'
            . htmlspecialchars($path) . '"';
        $fieldClass = $type === 'checkbox' ? 'checkbox' : '';
        if (! $valueIsDefault) {
            $fieldClass .= ($fieldClass == '' ? '' : ' ')
                . ($hasErrors ? 'custom field-error' : 'custom');
        }
        $fieldClass = $fieldClass ? ' class="' . $fieldClass . '"' : '';
        $trClass = $this->group > 0
            ? 'group-field group-field-' . $this->group
            : '';
        if (isset($opts['setvalue']) && $opts['setvalue'] === ':group') {
            unset($opts['setvalue']);
            $this->group++;
            $trClass = 'group-header-field group-header-' . $this->group;
        }
        if ($optionIsDisabled) {
            $trClass .= ($trClass ? ' ' : '') . 'disabled-field';
        }
        $trClass = $trClass ? ' class="' . $trClass . '"' : '';

        $htmlOutput = '<tr' . $trClass . '>';
        $htmlOutput .= '<th>';
        $htmlOutput .= '<label for="' . htmlspecialchars($path) . '">' . htmlspecialchars_decode($name)
            . '</label>';

        if (! empty($opts['doc'])) {
            $htmlOutput .= '<span class="doc">';
            $htmlOutput .= '<a href="' . $opts['doc']
                . '" target="documentation">' . $icons['help'] . '</a>';
            $htmlOutput .= "\n";
            $htmlOutput .= '</span>';
        }

        if ($optionIsDisabled) {
            $htmlOutput .= '<span class="disabled-notice" title="';
            $htmlOutput .= __(
                'This setting is disabled, it will not be applied to your configuration.'
            );
            $htmlOutput .= '">' . __('Disabled') . '</span>';
        }

        if (! empty($description)) {
            $htmlOutput .= '<small>' . $description . '</small>';
        }

        $htmlOutput .= '</th>';
        $htmlOutput .= '<td>';

        switch ($type) {
            case 'text':
                $htmlOutput .= '<input type="text" class="w-75" ' . $nameId . $fieldClass
                . ' value="' . htmlspecialchars($value) . '">';
                break;
            case 'password':
                $htmlOutput .= '<input type="password" class="w-75" ' . $nameId . $fieldClass
                . ' value="' . htmlspecialchars($value) . '">';
                break;
            case 'short_text':
                // As seen in the reporting server (#15042) we sometimes receive
                // an array here. No clue about its origin nor content, so let's avoid
                // a notice on htmlspecialchars().
                if (is_string($value)) {
                    $htmlOutput .= '<input type="text" size="25" ' . $nameId
                    . $fieldClass . ' value="' . htmlspecialchars($value)
                    . '">';
                }
                break;
            case 'number_text':
                $htmlOutput .= '<input type="number" ' . $nameId . $fieldClass
                . ' value="' . htmlspecialchars((string) $value) . '">';
                break;
            case 'checkbox':
                $htmlOutput .= '<span' . $fieldClass . '><input type="checkbox" ' . $nameId
                  . ($value ? ' checked="checked"' : '') . '></span>';
                break;
            case 'select':
                $htmlOutput .= '<select class="w-75" ' . $nameId . $fieldClass . '>';
                $escape = ! (isset($opts['values_escaped']) && $opts['values_escaped']);
                $valuesDisabled = isset($opts['values_disabled'])
                ? array_flip($opts['values_disabled']) : [];
                foreach ($opts['values'] as $optValueKey => $optValue) {
                    // set names for boolean values
                    if (is_bool($optValue)) {
                        $optValue = mb_strtolower(
                            $optValue ? __('Yes') : __('No')
                        );
                    }
                    // escape if necessary
                    if ($escape) {
                        $display = htmlspecialchars((string) $optValue);
                        $displayValue = htmlspecialchars((string) $optValueKey);
                    } else {
                        $display = $optValue;
                        $displayValue = $optValueKey;
                    }
                    // compare with selected value
                    // boolean values are cast to integers when used as array keys
                    $selected = is_bool($value)
                    ? (int) $value === $optValueKey
                    : $optValueKey === $value;
                    $htmlOutput .= '<option value="' . $displayValue . '"';
                    if ($selected) {
                        $htmlOutput .= ' selected="selected"';
                    }
                    if (isset($valuesDisabled[$optValueKey])) {
                        $htmlOutput .= ' disabled="disabled"';
                    }
                    $htmlOutput .= '>' . $display . '</option>';
                }
                $htmlOutput .= '</select>';
                break;
            case 'list':
                $val = $value;
                if (isset($val['wrapper_params'])) {
                    unset($val['wrapper_params']);
                }
                $htmlOutput .= '<textarea cols="35" rows="5" ' . $nameId . $fieldClass
                . '>' . htmlspecialchars(implode("\n", $val)) . '</textarea>';
                break;
        }
        if ($isSetupScript
            && isset($opts['userprefs_comment'])
            && $opts['userprefs_comment']
        ) {
            $htmlOutput .= '<a class="userprefs-comment" title="'
                . htmlspecialchars($opts['userprefs_comment']) . '">'
                . $icons['tblops'] . '</a>';
        }
        if (isset($opts['setvalue']) && $opts['setvalue']) {
            $htmlOutput .= '<a class="set-value hide" href="#'
                . htmlspecialchars($path . '=' . $opts['setvalue']) . '" title="'
                . sprintf(__('Set value: %s'), htmlspecialchars($opts['setvalue']))
                . '">' . $icons['edit'] . '</a>';
        }
        if (isset($opts['show_restore_default']) && $opts['show_restore_default']) {
            $htmlOutput .= '<a class="restore-default hide" href="#' . $path . '" title="'
                . __('Restore default value') . '">' . $icons['reload'] . '</a>';
        }
        // this must match with displayErrors() in scripts/config.js
        if ($hasErrors) {
            $htmlOutput .= "\n        <dl class=\"inline_errors\">";
            foreach ($opts['errors'] as $error) {
                $htmlOutput .= '<dd>' . htmlspecialchars($error) . '</dd>';
            }
            $htmlOutput .= '</dl>';
        }
        $htmlOutput .= '</td>';
        if ($isSetupScript && isset($opts['userprefs_allow'])) {
            $htmlOutput .= '<td class="userprefs-allow" title="' .
                __('Allow users to customize this value') . '">';
            $htmlOutput .= '<input type="checkbox" name="' . $path
                . '-userprefs-allow" ';
            if ($opts['userprefs_allow']) {
                $htmlOutput .= 'checked="checked"';
            }
            $htmlOutput .= '>';
            $htmlOutput .= '</td>';
        } elseif ($isSetupScript) {
            $htmlOutput .= '<td>&nbsp;</td>';
        }
        $htmlOutput .= '</tr>';

        return $htmlOutput;
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
        return $this->template->render('config/form_display/fieldset_bottom', [
            'show_buttons' => $showButtons,
            'is_setup' => $this->config->get('is_setup'),
        ]);
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
