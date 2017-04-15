/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    Javascript Handler for user_info.php
 * @name            User_info
 *
 * @requires    jQuery
 * @required    js/functions.js
 */

/**
 * AJAX scripts for user_info.php
 *
 * Actions ajaxified here:
 * Making edit form appear
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
