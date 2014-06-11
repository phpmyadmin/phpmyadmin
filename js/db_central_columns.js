/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview   events handling from central columns page
 * @name            Central columns
 *
 * @requires    jQuery
 */

/**
 * AJAX scripts for db_central_columns.php
 *
 * Actions ajaxified here:
 * Inline Edit and save of a result row
 * Delete a row
 *
 */

AJAX.registerTeardown('db_central_columns.js', function () {
    $(".edit").unbind('click');
    $(".edit_save_form").unbind('click');
    $('.edit_cancel_form').unbind('click');
    $(".del_row").unbind('click');
    $(".filter_rows").die("keyup");
    $('.edit_cancel_form').unbind('click');
    $('.column_heading').unbind('hover');
    $('#table-select').unbind('change');
    $('#column-select').unbind('change');
});

AJAX.registerOnload('db_central_columns.js', function () {
    $('#tableslistcontainer input,#tableslistcontainer select').hide();
    if ($('#table_columns tbody tr').length > 0) {
        $("#table_columns").tablesorter();
    }
    $('.column_heading').hover(function(){
        $(this).css("cursor","move");
        PMA_tooltip(
            $(this),
            'th',
            PMA_messages.strSortHint
        );
    });
    $(".filter_rows").live("keyup", function () {
        var cols = ["Name", "Type", "Length/Values", "Collation", "Null", "Extra", "Default"];
        $.uiTableFilter($("#table_columns"), $(this).val(), cols, null, "td span");
    });
    $('.edit').click(function() {
        rownum = $(this).parent().data('rownum');
        $('#save_'+rownum).show();
        $(this).hide();
        $('#f_'+rownum+' td span').hide();
        $('#f_'+rownum +' input, #f_'+rownum+' select').show();
    });
    $(".del_row").click(function() {
        //alert('del')
        rownum = $(this).data('rownum');
        //alert($('#f_'+rownum +' input[name=col_name]').val());
        $("#del_col_name").val($('#f_'+rownum +' input[name=col_name]').val());
        $("#del_form").submit();
    });
    $('.edit_cancel_form').click(function(event) {
        event.preventDefault();
        event.stopPropagation();
        rownum = $(this).data('rownum');
        $('#save_'+rownum).hide();
        $('#edit_'+rownum).show();
        $('#f_'+rownum+' td span').show();
        $('#f_'+rownum +' input, #f_'+rownum+' select').hide();
    });
    $('.edit_save_form').click(function(event) {
        //alert(1);
        event.preventDefault();
        event.stopPropagation();
        rownum = $(this).data('rownum');
       // alert(rownum);
        var datastring = $('#f_'+rownum+' :input').serialize();
        //console.log(datastring);
        $.ajax({
            type: "POST",
            url: "db_central_columns.php",
            data: datastring+'&ajax_request=true',
            dataType: "json",
            success: function(data) {
                if (data.message !== '1') {
                    PMA_ajaxShowMessage(
                        '<div class="error">' +
                        data.message +
                        '</div>',
                        false
                    );
                } else {
                    $('#f_'+rownum +' td[name=col_name] span').text($('#f_'+rownum +' input[name=col_name]').val()).html();
                    $('#f_'+rownum +' td[name=col_type] span').text($('#f_'+rownum +' select[name=col_type]').val()).html();
                    $('#f_'+rownum +' td[name=col_length] span').text($('#f_'+rownum +' input[name=col_length]').val()).html();
                    $('#f_'+rownum +' td[name=collation] span').text($('#f_'+rownum +' select[name=collation]').val()).html();
                    $('#f_'+rownum +' td[name=col_isNull] span').text($('#f_'+rownum +' input[name=col_isNull]').val()).html();
                    $('#f_'+rownum +' td[name=col_extra] span').text($('#f_'+rownum +' input[name=col_extra]').val()).html();
                    $('#f_'+rownum +' td[name=col_default] span').text($('#f_'+rownum +' input[name=col_default]').val()).html();
                }

                $('#save_'+rownum).hide();
                $('#edit_'+rownum).show();
                $('#f_'+rownum+' td span').show();
                $('#f_'+rownum +' input, #f_'+rownum+' select').hide();
            },
            error: function() {
                    PMA_ajaxShowMessage(
                        '<div class="error">' +
                        PMA_messages.strErrorProcessingRequest +
                        '</div>',
                        false
                    );
                }
        });
    });
    $('#table-select').change(function(e) {
        var selectvalue = $(this).val();
        var default_column_select = $('#column-select').html();
        var href = "db_central_columns.php";
        var params = {
            'ajax_request' : true,
            'token' : PMA_commonParams.get('token'),
            'db' : PMA_commonParams.get('db'),
            'selectedTable' : selectvalue,
            'populateColumns' : true
        };
        $('#column-select').html('<option value="">'+PMA_messages.strLoading+'</option>');
        if (selectvalue !== "") {
            $.post(href, params, function (data) {
                $('#column-select').html(default_column_select);
                $('#column-select').append(data.message);
            });
        }
    });
    $('#column-select').change(function(e) {
        var selectvalue = $(this).val();
        if (selectvalue !== "") {
            $("#add_column").submit();
        }
    });
});
