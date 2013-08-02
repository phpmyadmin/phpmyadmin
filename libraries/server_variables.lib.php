<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server variables
 *
 * @usedby  server_variables.php
 *  
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Get Ajax return when $_REQUEST['type'] === 'getval'
 *
 * @return null
 */
function PMA_getAjaxReturnForGetVal()
{
    global $VARIABLE_DOC_LINKS;    
    $response = PMA_Response::getInstance();
    
    // Send with correct charset
    header('Content-Type: text/html; charset=UTF-8');
    $varValue = $GLOBALS['dbi']->fetchSingleRow(
        'SHOW GLOBAL VARIABLES WHERE Variable_name="'
        . PMA_Util::sqlAddSlashes($_REQUEST['varName']) . '";',
        'NUM'
    );
    if (isset($VARIABLE_DOC_LINKS[$_REQUEST['varName']][3])
        && $VARIABLE_DOC_LINKS[$_REQUEST['varName']][3] == 'byte'
    ) {
        $response->addJSON(
            'message',
            implode(
                ' ', PMA_Util::formatByteDown($varValue[1], 3, 3)
            )
        );
    } else {
        $response->addJSON(
            'message',
            $varValue[1]
        );
    }
}
/**
 * Get Ajax return when $_REQUEST['type'] === 'setval'
 *
 * @return null
 */
function PMA_getAjaxReturnForSetVal()
{
    global $VARIABLE_DOC_LINKS;   
    $response = PMA_Response::getInstance();
    
    $value = $_REQUEST['varValue'];
    $matches = array();

    if (isset($VARIABLE_DOC_LINKS[$_REQUEST['varName']][3])
        && $VARIABLE_DOC_LINKS[$_REQUEST['varName']][3] == 'byte'
        && preg_match(
            '/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i',
            $value,
            $matches
        )
    ) {
        $exp = array(
            'kb' => 1,
            'kib' => 1,
            'mb' => 2,
            'mib' => 2,
            'gb' => 3,
            'gib' => 3
        );
        $value = floatval($matches[1]) * PMA_Util::pow(
            1024,
            $exp[strtolower($matches[3])]
        );
    } else {
        $value = PMA_Util::sqlAddSlashes($value);
    }

    if (! is_numeric($value)) {
        $value="'" . $value . "'";
    }

    if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName'])
        && $GLOBALS['dbi']->query(
            'SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value
        )
    ) {
        // Some values are rounded down etc.
        $varValue = $GLOBALS['dbi']->fetchSingleRow(
            'SHOW GLOBAL VARIABLES WHERE Variable_name="'
            . PMA_Util::sqlAddSlashes($_REQUEST['varName'])
            . '";', 'NUM'
        );
        $response->addJSON(
            'variable',
            PMA_formatVariable($_REQUEST['varName'], $varValue[1])
        );
    } else {
        $response->isSuccess(false);
        $response->addJSON(
            'error',
            __('Setting variable failed')
        );
    }
}

/**
 * Format Variable
 *
 * @param string  $name  variable name
 * @param numeric $value variable value
 *
 * @return formatted string
 */
function PMA_formatVariable($name, $value)
{
    global $VARIABLE_DOC_LINKS;

    if (is_numeric($value)) {
        if (isset($VARIABLE_DOC_LINKS[$name][3])
            && $VARIABLE_DOC_LINKS[$name][3]=='byte'
        ) {
            return '<abbr title="'
                . PMA_Util::formatNumber($value, 0) . '">'
                . implode(' ', PMA_Util::formatByteDown($value, 3, 3))
                . '</abbr>';
        } else {
            return PMA_Util::formatNumber($value, 0);
        }
    }
    return htmlspecialchars($value);
}

/**
 * Prints link templates
 *
 * @return string
 */
function PMA_getHtmlForLinkTemplates()
{
    $url = htmlspecialchars('server_variables.php?' . PMA_generate_common_url());
    $output  = '<a style="display: none;" href="#" class="editLink">';
    $output .= PMA_Util::getIcon('b_edit.png', __('Edit')) . '</a>';
    $output .= '<a style="display: none;" href="' 
        . $url . '" class="ajax saveLink">';
    $output .= PMA_Util::getIcon('b_save.png', __('Save')) . '</a> ';
    $output .= '<a style="display: none;" href="#" class="cancelLink">';
    $output .= PMA_Util::getIcon('b_close.png', __('Cancel')) . '</a> ';
    $output .= PMA_Util::getImage(
        'b_help.png',
        __('Documentation'),
        array(
            'style' => 'display:none',
            'id' => 'docImage'
        )
    );

    return $output;
}

/**
 * Prints Html for Server Variables
 *
 * @return string
 */
function PMA_getHtmlForServerVariables()
{   
    $value = ! empty($_REQUEST['filter']) 
        ? htmlspecialchars($_REQUEST['filter']) 
        : '';
    $output = '<fieldset id="tableFilter">'
        . '<legend>' . __('Filters') . '</legend>'
        . '<div class="formelement">'
        . '<label for="filterText">' .  __('Containing the word:') . '</label>'
        . '<input name="filterText" type="text" id="filterText"'
        . ' style="vertical-align: baseline;" value="' . $value . '" />'
        . '</div>'
        . '</fieldset>';
    
    $output .= '<div id="serverVariables" class="data filteredData noclick">'
        . '<div class="var-header var-row">'
        . '<div class="var-name">' .  __('Variable') . '</div>'
        . '<div class="var-value valueHeader">'
        . __('Session value') . ' / ' . __('Global value')
        . '</div>'
        . '<div style="clear:both"></div>'
        . '</div>';
 
    $output .= PMA_getHtmlForServerVariablesItems();
 
    $output .= '</div>';

    return $output;
}


/**
 * Prints Html for Server Variables Items
 *
 * @return string
 */
function PMA_getHtmlForServerVariablesItems()
{
    global $VARIABLE_DOC_LINKS;    
    /**
     * Sends the queries and buffers the results
     */
    $serverVarsSession 
        = $GLOBALS['dbi']->fetchResult('SHOW SESSION VARIABLES;', 0, 1);
    $serverVars = $GLOBALS['dbi']->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);
  
    $output = '';
    $odd_row = true;
    foreach ($serverVars as $name => $value) {
        $has_session_value = isset($serverVarsSession[$name])
            && $serverVarsSession[$name] != $value;
        $row_class = ($odd_row ? ' odd' : ' even')
            . ($has_session_value ? ' diffSession' : '');
    
        $output .= '<div class="var-row' . $row_class . '">'
            . '<div class="var-name">';
    
        // To display variable documentation link
        if (isset($VARIABLE_DOC_LINKS[$name])) {
            $output .= '<span title="' 
                . htmlspecialchars(str_replace('_', ' ', $name)) . '">';
            $output .= PMA_Util::showMySQLDocu(
                $VARIABLE_DOC_LINKS[$name][1],
                $VARIABLE_DOC_LINKS[$name][1],
                false,
                $VARIABLE_DOC_LINKS[$name][2] . '_' . $VARIABLE_DOC_LINKS[$name][0],
                true
            );
            $output .= htmlspecialchars(str_replace('_', ' ', $name));
            $output .= '</a>';
            $output .= '</span>';
        } else {
            $output .= htmlspecialchars(str_replace('_', ' ', $name));
        }
        $output .= '</div>'
            . '<div class="var-value value' 
            . ($GLOBALS['dbi']->isSuperuser() ? ' editable' : '') . '">&nbsp;'
            . PMA_formatVariable($name, $value)
            . '</div>'
            . '<div style="clear:both"></div>'
            . '</div>';
    
        if ($has_session_value) {
            $output .= '<div class="var-row' . ($odd_row ? ' odd' : ' even') . '">'
                . '<div class="var-name session">(' . __('Session value') . ')</div>'
                . '<div class="var-value value">&nbsp;' 
                . PMA_formatVariable($name, $serverVarsSession[$name]) . '</div>'
                . '<div style="clear:both"></div>'
                . '</div>';
        }
    
        $odd_row = ! $odd_row;
    }
    
    return $output;
}
?>


