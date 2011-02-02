/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    JavaScript functions used on Database Search page
 * @name            Database Search
 *
 * @requires    jQuery
 * @requires    js/functions.js
 */

/**
 * AJAX script for the Database Search page.
 *
 * Actions ajaxified here:
 * Retrieve result of SQL query
 */

$(document).ready(function() {

    /**
     * Set a parameter for all Ajax queries made on this page.  Don't let the
     * web server serve cached pages
     */
    $.ajaxSetup({
        cache: 'false'
    });

    /**
     * Prepare a div containing a link, otherwise it's incorrectly displayed 
     * after a couple of clicks
     */
    $('<div id="togglesearchformdiv"><a id="togglesearchformlink"></a></div>')
    .insertAfter('#db_search_form')
    // don't show it until we have results on-screen
    .hide();

   $('#togglesearchformlink')
       .html(PMA_messages['strShowSearchCriteria'])
       .bind('click', function() {
    	   var $link = $(this);
    	   //prompt("'"+$link.text()+"'");
    	   //prompt("'"+PMA_messages['strShowSearchCriteria']+"'");
    	   $('#db_search_form').slideToggle();
    	   if ($link.text() == PMA_messages['strHideSearchCriteria']) {
               $link.text(PMA_messages['strShowSearchCriteria']);
           } else {
               $link.text(PMA_messages['strHideSearchCriteria']);
           }
           //var_dump("");
           // avoid default click action
           return false;
       });
    /**
     * Ajax Event handler for retrieving the result of an SQL Query
     * (see $GLOBALS['cfg']['AjaxEnable'])
     *
     * @uses    PMA_ajaxShowMessage()
     */
    $("#db_search_form.ajax").live('submit', function(event) {
  	
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strSearching']);
        // jQuery object to reuse
        $form = $(this);
        
        // add this hidden field just once 
        if (! $form.find('input:hidden').is('#ajax_request_hidden')) {
            $form.append('<input type="hidden" id="ajax_request_hidden" name="ajax_request" value="true" />');
        }

        $.post($form.attr('action'), $form.serialize() + "&submit_search=" + $("#buttonGo").val(),  function(response) {
        	if (typeof response == 'string') {
        		// found results
        		$("#searchresults").html(response);
        		$("#sqlqueryresults").trigger('appendAnchor');
        		$('#db_search_form').hide();
        		$('#togglesearchformlink')
        		// always start with the Show message
        		.text(PMA_messages['strShowSearchCriteria'])
        		$('#togglesearchformdiv')
        		// now it's time to show the div containing the link 
        		.show();
        	}
        	else {
                // error message (zero rows)
                $("#sqlqueryresults").html(response['message']);
            }            
        })
    })
}, 'top.frame_content'); // end $(document).ready()
