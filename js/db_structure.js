/**
 * jQuery code for 'Drop Database', 'Truncate Table', 'Drop Table' action on
 * db_structure.php
 *
 */
$(document).ready(function() {

    //Drop Database
    $("#drop_db_anchor").live('click', function(event) {
        event.preventDefault();

        //context is top.frame_content, so we need to use window.parent.db to access the db var
        var question = PMA_messages['strDropDatabaseStrongWarning'] + '\n' + PMA_messages['strDoYouReally'] + ' :\n' + 'DROP DATABASE ' + window.parent.db;

        $(this).PMA_confirm(question, $(this).attr('href') ,function(url) {

            PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);
            $.get(url, {'is_js_confirmed': '1', 'ajax_request': true}, function(data) {
                //Database deleted successfully, refresh both the frames
                window.parent.refreshNavigation();
                window.parent.refreshMain();
            })
        });
    }); //end of Drop Database Ajax action

    //Truncate Table
    $(".truncate_table_anchor").live('click', function(event) {
        event.preventDefault();

        //extract current table name and build the question string
        var curr_table_name = $(this).parents('tr').children('th').children('a').text();
        var question = 'TRUNCATE ' + curr_table_name;

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    //need to find a better solution here.  The icon should be replaced
                    $(this).remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            })
        })
    }); //end of Truncate Table Ajax action

    //Drop Table
    $(".drop_table_anchor").live('click', function(event) {
        event.preventDefault();

        //extract current table name and build the question string
        var curr_row = $(this).parents('tr');
        var curr_table_name = $(curr_row).children('th').children('a').text();
        var question = 'DROP TABLE ' + curr_table_name;

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    //need to find a better solution here.  The icon should be replaced
                    $(curr_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            });
        });
    }); //end of Drop Table Ajax action

    //Drop Column
    $(".drop_column_anchor").live('click', function(event) {
        event.preventDefault();

        var curr_table_name = window.parent.table;
        var curr_row = $(this).parents('tr');
        var curr_column_name = $(curr_row).children('th').children('label').text();
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
            })
        }); //end of Drop Column Anchor action
    })

    //Add Primary Key
    $(".add_primary_key_anchor").live('click', function(event) {
        event.preventDefault();

        var curr_table_name = window.parent.table;
        var curr_column_name = $(this).parents('tr').children('th').children('label').text();
        var question = PMA_messages['strDoYouReally'] + ' :\n ALTER TABLE `' + curr_table_name + '` ADD PRIMARY KEY(`' + curr_column_name + '`)';

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strAddingPrimaryKey']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(this).remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            })
        })
    })//end Add Primary Key

    //Drop Event
    $('.drop_event_anchor').live('click', function(event) {
        event.preventDefault();

        var curr_event_row = $(this).parents('tr');
        var curr_event_name = $(curr_event_row).children('td:first').text();
        var question = 'DROP EVENT ' + curr_event_name;

        $(this).PMA_confirm(question, $(this).attr('href') , function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingEvent']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_event_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            })
        })
    })
    //end Drop Event

    //Drop Procedure
    $('.drop_procedure_anchor').live('click', function(event) {
        event.preventDefault();

        var curr_proc_row = $(this).parents('tr');
        var question = $(curr_proc_row).children('.drop_procedure_sql').val();

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingProcedure']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_event_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            })
        })
    })
    //end Drop Procedure

    //Drop Tracking
    $('.drop_tracking_anchor').live('click', function(event) {
        event.preventDefault();

        var curr_tracking_row = $(this).parents('tr');
        var question = PMA_messages['strDeleteTrackingData'];

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDeletingTrackingData']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_tracking_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            })
        })
    })
    //end Drop Tracking

    //Drop Primary Key/Index
    $('.drop_primary_key_index_anchor').live('click', function(event) {
        event.preventDefault();

        var curr_row = $(this).parents('tr');
        var question = $(curr_row).children('.drop_primary_key_index_msg').val();

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingPrimaryKeyIndex']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            })
        })
    })
    //end Drop Primary Key/Index

    //Calculate Real End for InnoDB
    $('#real_end_input').live('click', function(event) {
        event.preventDefault();

        var question = PMA_messages['strOperationTakesLongTime'];

        $(this).PMA_confirm(question, '', function() {
            return true;
        })
        return false;
    })
    //end Calculate Real End for InnoDB

}, 'top.frame_content');