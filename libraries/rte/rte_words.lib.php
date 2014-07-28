<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Helper functions for RTE
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This function is used to retreive some language strings that are used
 * in functionalities that are common to routines, triggers and events.
 *
 * @param string $index The index of the string to get
 *
 * @return string The requested string or an empty string, if not available
 */
function PMA_RTE_getWord($index)
{
    global $_PMA_RTE;

    switch ($_PMA_RTE) {
    case 'RTN':
        $words = array(
            'add'       => __('Add routine'),
            'docu'      => 'STORED_ROUTINES',
            'export'    => __('Export of routine %s'),
            'human'     => __('routine'),
            'no_create' => __(
                'You do not have the necessary privileges to create a routine'
            ),
            'not_found' => __('No routine with name %1$s found in database %2$s'),
            'nothing'   => __('There are no routines to display.'),
            'title'     => __('Routines'),
        );
        break;
    case 'TRI':
        $words = array(
            'add'       => __('Add trigger'),
            'docu'      => 'TRIGGERS',
            'export'    => __('Export of trigger %s'),
            'human'     => __('trigger'),
            'no_create' => __(
                'You do not have the necessary privileges to create a trigger'
            ),
            'not_found' => __('No trigger with name %1$s found in database %2$s'),
            'nothing'   => __('There are no triggers to display.'),
            'title'     => __('Triggers'),
        );
        break;
    case 'EVN':
        $words = array(
            'add'       => __('Add event'),
            'docu'      => 'EVENTS',
            'export'    => __('Export of event %s'),
            'human'     => __('event'),
            'no_create' => __(
                'You do not have the necessary privileges to create an event'
            ),
            'not_found' => __('No event with name %1$s found in database %2$s'),
            'nothing'   => __('There are no events to display.'),
            'title'     => __('Events'),
        );
        break;
    default:
        $words = array();
        break;
    }

    return isset($words[$index]) ? $words[$index] : '';
} // end PMA_RTE_getWord()

?>
