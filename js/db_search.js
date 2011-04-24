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

/** Loads the database search results */
function loadResult(result_path , table_name , link , ajaxEnable){
    $(document).ready(function() {
        if(ajaxEnable)
        {
            /**   Hides the results shown by the delete criteria */
            //PMA_ajaxShowMessage(PMA_messages['strBrowsing']);
            $('#sqlqueryform').hide();
            $('#togglequerybox').hide();
            /**  Load the browse results to the page */
            $("#table-info").show();
            $('#table-link').attr({"href" : 'sql.php?'+link }).text(table_name);
            $('#browse-results').load(result_path + " '"+'#sqlqueryresults' + "'").show();
        }
        else
        {
            event.preventDefault();
        }
    });
}

/**  Delete the selected search results */
function deleteResult(result_path , msg , ajaxEnable){
    $(document).ready(function() {
        /**  Hides the results shown by the browse criteria */
        $("#table-info").hide();
        $('#browse-results').hide();
        $('#sqlqueryform').hide();
        $('#togglequerybox').hide();
        /** Conformation message for deletion */
        if(confirm(msg))
        {
            if(ajaxEnable)
            {
                /** Load the deleted option to the page*/
                $('#browse-results').load(result_path + " '"+'#result_query' + "'");
                $('#sqlqueryform').load(result_path + " '"+'#sqlqueryform' + "'");
                $('#togglequerybox').html(PMA_messages['strHideQueryBox']);

                /** Refresh the search results after the deletion */
                document.getElementById('buttonGo'). click();
                //PMA_ajaxShowMessage(PMA_messages['strDeleting']);
                /** Show the results of the deletion option */
                $('#browse-results').show();
                $('#sqlqueryform').show();
                $('#togglequerybox').show();
            }
            else
            {
                event.preventDefault();
            }
       }
    });
}

$(document).ready(function() {

    /**
     * Set a parameter for all Ajax queries made on this page.  Don't let the
     * web server serve cached pagesshow
     */
    $.ajaxSetup({
        cache: 'false'
    });

    /** Hide the table link in the initial search result */
    $("#table-info").prepend('<img id="table-image" src="./themes/original/img/s_tbl.png" />').hide();

    /** Hide the browse and deleted results in the new search criteria */
    $('#buttonGo').click(function(){
        $("#table-info").hide();
        $('#browse-results').hide();
        $('#sqlqueryform').hide();
        $('#togglequerybox').hide();
    });
    /**
     * Prepare a div containing a link for toggle the search form, otherwise it's incorrectly displayed
     * after a couple of clicks
     */
    $('<div id="togglesearchformdiv"><a id="togglesearchformlink"></a></div>')
    .insertAfter('#db_search_form')
    /** don't show it until we have results on-screen */
    .hide();

    /** Changing the displayed text according to the hide/show criteria in search form*/
    $("#togglequerybox").hide();
    $("#togglequerybox").bind('click', function() {
        var $link = $(this)
        $('#sqlqueryform').slideToggle("medium");
        if ($link.text() == PMA_messages['strHideQueryBox']) {
            $link.text(PMA_messages['strShowQueryBox']);
        } else {
            $link.text(PMA_messages['strHideQueryBox']);
        }
        /** avoid default click action */
        return false;
    })

    /** don't show it until we have results on-screen */

   /** Changing the displayed text according to the hide/show criteria in search criteria form*/
   $('#togglesearchformlink')
       .html(PMA_messages['strShowSearchCriteria'])
       .bind('click', function() {
            var $link = $(this);
            $('#db_search_form').slideToggle();
            if ($link.text() == PMA_messages['strHideSearchCriteria']) {
                $link.text(PMA_messages['strShowSearchCriteria']);
            } else {
                $link.text(PMA_messages['strHideSearchCriteria']);
            }
            /** avoid default click action */
            return false;
       });
    /**
     * Ajax Event handler for retrieving the result of an SQL Query
     * (see $GLOBALS['cfg']['AjaxEnable'])
     *
     * @uses    PMA_ajaxShowMessage()
     * @see     $GLOBALS['cfg']['AjaxEnable']
     */
    $("#db_search_form.ajax").live('submit', function(event) {
        event.preventDefault();

        var $msgbox = PMA_ajaxShowMessage(PMA_messages['strSearching']);
        // jQuery object to reuse
        $form = $(this);

        PMA_prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize() + "&submit_search=" + $("#buttonGo").val(),  function(response) {
            if (typeof response == 'string') {
                // found results
                $("#searchresults").html(response);
                $("#sqlqueryresults").trigger('appendAnchor');
                $('#db_search_form')
                    // workaround for Chrome problem (bug #3168569)
                    .slideToggle()
                    .hide();
                $('#togglesearchformlink')
                    // always start with the Show message
                    .text(PMA_messages['strShowSearchCriteria'])
                $('#togglesearchformdiv')
                    // now it's time to show the div containing the link
                    .show();
            } else {
                // error message (zero rows)
                $("#sqlqueryresults").html(response['message']);
            }

            PMA_ajaxRemoveMessage($msgbox);
        })
    })
}, 'top.frame_content'); // end $(document).ready()
