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
 * AJAX scripts for tbl_structure.php
 *
 * Actions ajaxified here:
 * Drop Column
 * Add Primary Key
 * Drop Primary Key/Index
 *
 */
$(document).ready(function() {

    /**
     * Attach Event Handler for 'Drop Column'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $(".drop_column_anchor").live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_table_name String containing the name of the current table
         */
        var curr_table_name = window.parent.table;
        /**
         * @var curr_row    Object reference to the currently selected row (i.e. field in the table)
         */
        var curr_row = $(this).parents('tr');
        /**
         * @var curr_column_name    String containing name of the field referred to by {@link curr_row}
         */
        var curr_column_name = $(curr_row).children('th').children('label').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strDoYouReally'] + ' :\n ALTER TABLE `' + curr_table_name + '` DROP `' + curr_column_name + '`';

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingColumn']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }); // end $.PMA_confirm()
    }) ; //end of Drop Column Anchor action

    /**
     * Ajax Event handler for 'Add Primary Key'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $(".action_primary a").live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_table_name String containing the name of the current table
         */
        var curr_table_name = window.parent.table;
        /**
         * @var curr_column_name    String containing name of the field referred to by {@link curr_row}
         */
        var curr_column_name = $(this).parents('tr').children('th').children('label').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strDoYouReally'] + ' :\n ALTER TABLE `' + curr_table_name + '` ADD PRIMARY KEY(`' + curr_column_name + '`)';

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strAddingPrimaryKey']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(this).remove();
                    if (typeof data.reload != 'undefined') {
                        window.parent.frame_content.location.reload();
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    })//end Add Primary Key

    /**
     * Ajax Event handler for 'Drop Primary Key/Index'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $('.drop_primary_key_index_anchor').live('click', function(event) {
        event.preventDefault();

        $anchor = $(this);

        /**
         * @var $curr_row    Object containing reference to the current field's row
         */
        var $curr_row = $anchor.parents('tr');
        /** @var    Number of columns in the key */
        var rows = $anchor.parents('td').attr('rowspan') || 1;
        /** @var    Rows that should be hidden */
        var $rows_to_hide = $curr_row;
        for (var i = 1, $last_row = $curr_row.next(); i < rows; i++, $last_row = $last_row.next()) {
            $rows_to_hide = $rows_to_hide.add($last_row);
        }

        var question = $curr_row.children('td').children('.drop_primary_key_index_msg').val();

        $anchor.PMA_confirm(question, $anchor.attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingPrimaryKeyIndex']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $rows_to_hide.hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }) //end Drop Primary Key/Index

    /**
     *Ajax event handler for muti column change
    **/
    $("#fieldsForm.ajax .mult_submit[value=change]").live('click', function(event){
        event.preventDefault();

        /*Check whether atleast one row is selected for change*/
        if ($("#tablestructure tbody tr").hasClass("marked")) {
            /*Define the action and $url variabls for the post method*/
            var $form = $("#fieldsForm");
            var action = $form.attr('action');
            var url = $form.serialize()+"&ajax_request=true&submit_mult=change";
            /*Calling for the changeColumns fucntion*/
            changeColumns(action,url);
        }  else {
            PMA_ajaxShowMessage(PMA_messages['strNoRowSelected']);
        }
    });

    /**
     *Ajax event handler for single column change
    **/
    $("#fieldsForm.ajax #tablestructure tbody tr td.edit a").live('click', function(event){
        event.preventDefault();
        /*Define the action and $url variabls for the post method*/
        var action = "tbl_alter.php";
        var url = $(this).attr('href');
        if (url.substring(0, 13) == "tbl_alter.php") {
            url = url.substring(14, url.length);
        }
        url = url + "&ajax_request=true";
        /*Calling for the changeColumns fucntion*/
        changeColumns(action,url);
     });

    /**
     *Ajax event handler for index edit
    **/
    $("#table_index tbody tr td.edit_index.ajax").live('click', function(event){
        event.preventDefault();
        var url = $(this).find("a").attr("href");
        if (url.substring(0, 16) == "tbl_indexes.php?") {
            url = url.substring(16, url.length );
        }
        url = url + "&ajax_request=true";

        /*Remove the hidden dialogs if there are*/
        if ($('#edit_index_dialog').length != 0) {
            $('#edit_index_dialog').remove();
        }
        var $div = $('<div id="edit_index_dialog"></div>');

        /**
         *  @var    button_options  Object that stores the options passed to jQueryUI
         *                          dialog
         */
        var button_options = {};
        // in the following function we need to use $(this)
        button_options[PMA_messages['strCancel']] = function() {$(this).dialog('close').remove();}

        var button_options_error = {};
        button_options_error[PMA_messages['strOK']] = function() {$(this).dialog('close').remove();}
        var $msgbox = PMA_ajaxShowMessage();

        $.get( "tbl_indexes.php" , url ,  function(data) {
            //in the case of an error, show the error message returned.
            if (data.success != undefined && data.success == false) {
                $div
                .append(data.error)
                .dialog({
                    title: PMA_messages['strEdit'],
                    height: 230,
                    width: 900,
                    open: PMA_verifyTypeOfAllColumns,
                    modal: true,
                    buttons : button_options_error
                })// end dialog options
            } else {
                $div
                .append(data)
                .dialog({
                    title: PMA_messages['strEdit'],
                    height: 600,
                    width: 900,
                    open: PMA_verifyTypeOfAllColumns,
                    modal: true,
                    buttons : button_options
                })
                //Remove the top menu container from the dialog
                .find("#topmenucontainer").hide()
                ; // end dialog options
                checkIndexType();
                checkIndexName("index_frm");
            }
            PMA_ajaxRemoveMessage($msgbox);
        }) // end $.get()
    });

    /**
     *Ajax action for submiting the index form
    **/
    $("#index_frm.ajax input[name=do_save_data]").live('click', function(event) {
        event.preventDefault();
        /**
         *  @var    the_form    object referring to the export form
         */
        var $form = $("#index_frm");

        PMA_prepareForAjaxRequest($form);
        //User wants to submit the form
        $.post($form.attr('action'), $form.serialize()+"&do_save_data=Save", function(data) {
            if ($("#sqlqueryresults").length != 0) {
                $("#sqlqueryresults").remove();
            }
            if (data.success == true) {
                PMA_ajaxShowMessage(data.message);
                $("<div id='sqlqueryresults'></div>").insertAfter("#topmenucontainer");
                $("#sqlqueryresults").html(data.sql_query);
                $("#result_query .notice").remove();
                $("#result_query").prepend((data.message));

                /*Reload the field form*/
                $("#table_index").remove();
                var $temp_div = $("<div id='temp_div'><div>").append(data.index_table);
                $($temp_div).find("#table_index").insertAfter("#index_header");
                if ($("#edit_index_dialog").length > 0) {
                    $("#edit_index_dialog").dialog("close").remove();
                }

            } else {
                if(data.error != undefined) {
                    var $temp_div = $("<div id='temp_div'><div>").append(data.error);
                    if($($temp_div).find(".error code").length != 0) {
                        var $error = $($temp_div).find(".error code").addClass("error");
                    } else {
                        var $error = $temp_div;
                    }
                }
                PMA_ajaxShowMessage($error);
            }

        }) // end $.post()
    }) // end insert table button "do_save_data"

    /**
     *Ajax action for submiting the index form for add more columns
    **/
    $("#index_frm.ajax input[name=add_fields]").live('click', function(event) {
        event.preventDefault();
        /**
         *  @var    the_form    object referring to the export form
         */
        var $form = $("#index_frm");

        PMA_prepareForAjaxRequest($form);
        //User wants to submit the form
        $.post($form.attr('action'), $form.serialize()+"&add_fields=Go", function(data) {
            $("#index_columns").remove();
            var $temp_div = $("<div id='temp_div'><div>").append(data);
            $($temp_div).find("#index_columns").appendTo("#index_edit_fields");
        }) // end $.post()
    }) // end insert table button "Go"

    /**Add the show/hide index table option if the index is available*/
    if ($("#index_div.ajax").find("#table_index").length != 0) {
        /**
         *Prepare a div containing a link for toggle the index table
         */
        $('<div id="toggletableindexdiv"><a id="toggletableindexlink"></a></div>')
        .insertAfter('#index_div')
        /** don't show it until we have index table on-screen */
        .show();

        /** Changing the displayed text according to the hide/show criteria in table index*/

        $('#toggletableindexlink')
        .html(PMA_messages['strHideIndexes'])
        .bind('click', function() {
             var $link = $(this);
             $('#index_div').slideToggle();
             if ($link.text() == PMA_messages['strHideIndexes']) {
                 $link.text(PMA_messages['strShowIndexes']);
             } else {
                 $link.text(PMA_messages['strHideIndexes']);
             }
             /** avoid default click action */
             return false;
        });
    } //end show/hide table index


}) // end $(document).ready()

/**
 * Loads the append_fields_form to the Change dialog allowing users
 * to change the columns
 * @param   string	action  Variable which parses the name of the
 * 							destination file
 * @param   string	$url    Variable which parses the data for the
 * 							post action
 */
function changeColumns(action,url) {
    /*Remove the hidden dialogs if there are*/
    if ($('#change_column_dialog').length != 0) {
        $('#change_column_dialog').remove();
    }
    var $div = $('<div id="change_column_dialog"></div>');

    /**
     *  @var    button_options  Object that stores the options passed to jQueryUI
     *                          dialog
     */
    var button_options = {};
    // in the following function we need to use $(this)
    button_options[PMA_messages['strCancel']] = function() {$(this).dialog('close').remove();}

    var button_options_error = {};
    button_options_error[PMA_messages['strOK']] = function() {$(this).dialog('close').remove();}
    var $msgbox = PMA_ajaxShowMessage();

    $.get( action , url ,  function(data) {
        //in the case of an error, show the error message returned.
        if (data.success != undefined && data.success == false) {
            $div
            .append(data.error)
            .dialog({
                title: PMA_messages['strChangeTbl'],
                height: 230,
                width: 900,
                modal: true,
                open: PMA_verifyTypeOfAllColumns,
                buttons : button_options_error
            })// end dialog options
        } else {
            $div
            .append(data)
            .dialog({
                title: PMA_messages['strChangeTbl'],
                height: 600,
                width: 900,
                modal: true,
                open: PMA_verifyTypeOfAllColumns,
                buttons : button_options
            })
            //Remove the top menu container from the dialog
            .find("#topmenucontainer").hide()
            ; // end dialog options
            $("#append_fields_form input[name=do_save_data]").addClass("ajax");
            /*changed the z-index of the enum editor to allow the edit*/
            $("#enum_editor").css("z-index", "1100");
        }
        PMA_ajaxRemoveMessage($msgbox);
    }) // end $.get()
}

