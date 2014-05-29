<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to table indexes
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Function to get html for displaying the index form
 *
 * @param array     $fields      fields
 * @param PMA_Index $index       index
 * @param array     $form_params form parameters
 * @param int       $add_fields  number of fields in the form
 *
 * @return string
 */
function PMA_getHtmlForEditPages()
{
        global $db,$table,$cfgRelation;
        $page_query = 'SELECT * FROM '
            . $GLOBALS['relation'] . '.'
            . $GLOBALS['table']
            . ' WHERE db_name = \'' . $GLOBALS['db'] . '\'';
        $page_rs    = PMA_queryAsControlUser(
            $page_query, false, PMA_DatabaseInterface::QUERY_STORE
        );
        

    $html = "";
    $choices = array(
                 '0' => __('Edit'),
                 '1' => __('Delete')
            );
    
    	$html .= '<form action="edit_pages.php" method="post" name="edit_pages" id="edit_pages" class="ajax">';
    	$html .= '<fieldset id="page_edit_options">';
    	$html .= PMA_URL_getHiddenInputs($db, $table);
    	$html .= '<input type="hidden" name="do" value="selectpage" />';
    	$html .= '<select name="chpage" id="chpage" class="autosubmit">';
    	$html .= '<option value="0">' . __('Select page').'</option>';

    	while ($curr_page = $GLOBALS['dbi']->fetchAssoc($page_rs)) 
    	{
        	$html .= '<option value="' . $curr_page['page_nr'] . '"';
                if(isset($this->chosenPage) && $this->chosenPage == $curr_page['page_nr'] ) 
                {
                    $html .= ' selected="selected"';
                }
                $html .= '>' . $curr_page['page_nr'] . ': ' . htmlspecialchars($curr_page['page_descr']) . '</option>';
        }
    	$html .= '</select>';
    
    	$html .= PMA_Util::getRadioFields( 'action_choose', $choices, '0', false );
    
        $html .= '</fieldset>';
        $html .= '</form>';
        $html .= '<span>'. $page_query .' db = '.$GLOBALS['dbi'].'</span></form>';

    return $html;
}

?>