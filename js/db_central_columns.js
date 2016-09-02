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
 * Multiple edit and delete option
 *
 */

AJAX.registerTeardown('db_central_columns.js', function () {
    $(".edit").unbind('click');
    $(".edit_save_form").unbind('click');
    $('.edit_cancel_form').unbind('click');
    $(".del_row").unbind('click');
    $(document).off("keyup", ".filter_rows");
    $('.edit_cancel_form').unbind('click');
    $('#table-select').unbind('change');
    $('#column-select').unbind('change');
    $("#add_col_div").find(">a").unbind('click');
    $('#add_new').unbind('submit');
    $('#multi_edit_central_columns').unbind('submit');
    $("select.default_type").unbind('change');
    $("button[name='delete_central_columns']").unbind('click');
    $("button[name='edit_central_columns']").unbind('click');
});

AJAX.registerOnload('db_central_columns.js', function () {
    $('#tableslistcontainer input,#tableslistcontainer select,#tableslistcontainer .default_value,#tableslistcontainer .open_enum_editor').hide();
    $('#tableslistcontainer').find('.checkall').show();
    $('#tableslistcontainer').find('.checkall_box').show();
    if ($('#table_columns').find('tbody tr').length > 0) {
        $("#table_columns").tablesorter({
            headers: {
                0: {sorter: false},
                1: {sorter: false}, // hidden column
                4: {sorter: "integer"}
            }
        });
    }
    $('#tableslistcontainer').find('button[name="delete_central_columns"]').click(function(event){
        event.preventDefault();
        var multi_delete_columns = $('.checkall:checkbox:checked').serialize();
        if(multi_delete_columns === ''){
            PMA_ajaxShowMessage(PMA_messages.strRadioUnchecked);
            return false;
        }
        PMA_ajaxShowMessage();
        $("#del_col_name").val(multi_delete_columns);
        $("#del_form").submit();
    });
    $('#tableslistcontainer').find('button[name="edit_central_columns"]').click(function(event){
        event.preventDefault();
        var editColumnList = $('.checkall:checkbox:checked').serialize();
        if(editColumnList === ''){
            PMA_ajaxShowMessage(PMA_messages.strRadioUnchecked);
            return false;
        }
        var editColumnData = editColumnList+ '&edit_central_columns_page=true&ajax_request=true&ajax_page_request=true&token='+PMA_commonParams.get('token')+'&db='+PMA_commonParams.get('db');
        PMA_ajaxShowMessage();
        AJAX.source = $(this);
        $.get('db_central_columns.php', editColumnData, AJAX.responseHandler);
    });
    $('#multi_edit_central_columns').submit(function(event){
        event.preventDefault();
        event.stopPropagation();
        var multi_column_edit_data = $("#multi_edit_central_columns").serialize()+'&multi_edit_central_column_save=true&ajax_request=true&ajax_page_request=true&token='+PMA_commonParams.get('token')+'&db='+PMA_commonParams.get('db');
        PMA_ajaxShowMessage();
        AJAX.source = $(this);
        $.post('db_central_columns.php', multi_column_edit_data, AJAX.responseHandler);
    });
    $('#add_new').find('td').each(function(){
        if ($(this).attr('name') !== 'undefined') {
            $(this).find('input,select:first').attr('name', $(this).attr('name'));
        }
    });
    $("#field_0_0").attr('required','required');
    $('#add_new input[type="text"], #add_new input[type="number"], #add_new select')
        .css({
            'width' : '10em',
            '-moz-box-sizing' : 'border-box'
        });
    window.scrollTo(0, 0);
    $(document).on("keyup", ".filter_rows", function () {
        // get the column names
        var cols = $('th.column_heading').map(function () {
            return $.trim($(this).text());
        }).get();
        $.uiTableFilter($("#table_columns"), $(this).val(), cols, null, "td span");
    });
    $('.edit').click(function() {
        var rownum = $(this).parent().data('rownum');
        $('#save_' + rownum).show();
        $(this).hide();
        $('#f_' + rownum + ' td span').hide();
        $('#f_' + rownum + ' input, #f_' + rownum + ' select, #f_' + rownum + ' .open_enum_editor').show();
        var attribute_val = $('#f_' + rownum + ' td[name=col_attribute] span').html();
        $('#f_' + rownum + ' select[name=field_attribute\\['+ rownum +'\\] ] option[value="' + attribute_val + '"]').attr("selected","selected");
        if($('#f_' + rownum + ' .default_type').val() === 'USER_DEFINED') {
            $('#f_' + rownum + ' .default_type').siblings('.default_value').show();
        } else {
            $('#f_' + rownum + ' .default_type').siblings('.default_value').hide();
        }
    });
    $(".del_row").click(function (event) {
        event.preventDefault();
        event.stopPropagation();
        var $td = $(this);
        var question = PMA_messages.strDeleteCentralColumnWarning;
        $td.PMA_confirm(question, null, function (url) {
            var rownum = $td.data('rownum');
            $("#del_col_name").val("selected_fld%5B%5D="+$('#checkbox_row_' + rownum ).val());
            $("#del_form").submit();
        });
    });
    $('.edit_cancel_form').click(function(event) {
        event.preventDefault();
        event.stopPropagation();
        var rownum = $(this).data('rownum');
        $('#save_' + rownum).hide();
        $('#edit_' + rownum).show();
        $('#f_' + rownum + ' td span').show();
        $('#f_' + rownum + ' input, #f_' + rownum + ' select,#f_'+rownum+' .default_value, #f_' + rownum + ' .open_enum_editor').hide();
        $('#tableslistcontainer').find('.checkall').show();
    });
    $('.edit_save_form').click(function(event) {
        event.preventDefault();
        event.stopPropagation();
        var rownum = $(this).data('rownum');
        $('#f_' + rownum + ' td').each(function() {
            if ($(this).attr('name') !== 'undefined') {
                $(this).find(':input[type!="hidden"],select:first')
                       .attr('name', $(this).attr('name'));
            }
        });

        if($('#f_' + rownum + ' .default_type').val() === 'USER_DEFINED') {
            $('#f_' + rownum + ' .default_type').attr('name','col_default_sel');
        } else {
            $('#f_' + rownum + ' .default_value').attr('name','col_default_val');
        }

        var datastring = $('#f_' + rownum + ' :input').serialize();
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
                    $('#f_' + rownum + ' td[name=col_name] span').text($('#f_' + rownum + ' input[name=col_name]').val()).html();
                    $('#f_' + rownum + ' td[name=col_type] span').text($('#f_' + rownum + ' select[name=col_type]').val()).html();
                    $('#f_' + rownum + ' td[name=col_length] span').text($('#f_' + rownum + ' input[name=col_length]').val()).html();
                    $('#f_' + rownum + ' td[name=collation] span').text($('#f_' + rownum + ' select[name=collation]').val()).html();
                    $('#f_' + rownum + ' td[name=col_attribute] span').text($('#f_' + rownum + ' select[name=col_attribute]').val()).html();
                    $('#f_' + rownum + ' td[name=col_isNull] span').text($('#f_' + rownum +' input[name=col_isNull]').is(":checked")?"Yes":"No").html();
                    $('#f_' + rownum + ' td[name=col_extra] span').text($('#f_' + rownum + ' input[name=col_extra]').is(":checked") ? "auto_increment" : "").html();
                    $('#f_' + rownum + ' td[name=col_default] span').text($('#f_' + rownum + ' :input[name=col_default]').val()).html();
                }
                $('#save_' + rownum).hide();
                $('#edit_' + rownum).show();
                $('#f_' + rownum + ' td span').show();
                $('#f_' + rownum + ' input, #f_' + rownum + ' select,#f_' + rownum + ' .default_value, #f_' + rownum + ' .open_enum_editor').hide();
                $('#tableslistcontainer').find('.checkall').show();
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
        var default_column_select = $('#column-select').find('option:first');
        var href = "db_central_columns.php";
        var params = {
            'ajax_request' : true,
            'token' : PMA_commonParams.get('token'),
            'server' : PMA_commonParams.get('server'),
            'db' : PMA_commonParams.get('db'),
            'selectedTable' : selectvalue,
            'populateColumns' : true
        };
        $('#column-select').html('<option value="">' + PMA_messages.strLoading + '</option>');
        if (selectvalue !== "") {
            $.post(href, params, function (data) {
                $('#column-select').empty().append(default_column_select);
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
    $("#add_col_div").find(">a").click(function(event){
        $('#add_new').slideToggle("slow");
        var $addColDivLinkSpan = $("#add_col_div").find(">a span");
        if($addColDivLinkSpan.html() === '+') {
            $addColDivLinkSpan.html('-');
        } else {
            $addColDivLinkSpan.html('+');
        }
    });
    $('#add_new').submit(function(event){
        $('#add_new').toggle();
    });
    $("#tableslistcontainer").find("select.default_type").change(function () {
        if ($(this).val() === 'USER_DEFINED') {
            $(this).siblings('.default_value').attr('name','col_default');
            $(this).attr('name','col_default_sel');
        } else {
            $(this).attr('name','col_default');
            $(this).siblings('.default_value').attr('name','col_default_val');
        }
    });
});
