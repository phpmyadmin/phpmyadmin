/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used on the table structure page
 * @name            Table Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * AJAX scripts for user_info.php
 *
 * Actions ajaxified here:
 * Making edit form appear
 * Submission of edit form
 *
 */

AJAX.registerTeardown('user_info.js', function () {
    $('#buttonGo').die('click');
});

AJAX.registerOnload('user_info.js', function () {
	$('#user_info').hide();
	$('#buttonGo').live("click",function(event){
		$('#user_info').show();
    });
     		
});		
