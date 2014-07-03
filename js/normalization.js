/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview   events handling from normalization page
 * @name            normalization
 *
 * @requires    jQuery
 */

/**
 * AJAX scripts for normalization.php
 *
 */

function appendHtmlColumnsList()
{
    $.get(
        "normalization.php",
        {
            "token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "getColumns": true
        },
        function(data) {
            if (data.success === true) {
                $('select[name=makeAtomic]').html(data.message);
            }
        }
    );
}

function goToFinish()
{
    $("#mainContent legend").html(PMA_messages.strEndStep);
    $("#mainContent h4").html(
        "<h3>"+$.sprintf(PMA_messages.strFinishMsg, PMA_commonParams.get('table'))+"</h3>"
    );
    $("#mainContent p").html('');
    $("#mainContent #extra").html('');
    $("#mainContent #newCols").html('');
    $('.tblFooters').html('');
}

function goToStep3()
{
    $.post(
        "normalization.php",
        {
            "token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "step3": true
        }, function(data) {
            $("#mainContent legend").html(data.legendText);
            $("#mainContent h4").html(data.headText);
            $("#mainContent p").html(data.subText);
            $("#mainContent #extra").html(data.extra);
            $("#mainContent #newCols").html('');
            $('.tblFooters').html('');
        }
    );
}

function goToStep2(extra)
{
    $.post(
        "normalization.php",
        {
            "token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "step2": true
        }, function(data) {
            $("#mainContent legend").html(data.legendText);
            $("#mainContent h4").html(data.headText);
            $("#mainContent p").html(data.subText);
            $("#mainContent #extra,#mainContent #newCols").html('');
            $('.tblFooters').html('');
            if (data.hasPrimaryKey === "1") {
                if(extra === 'goToStep3') {
                    $("#mainContent h4").html(PMA_messages.strPrimaryKeyAdded);
                    $("#mainContent p").html(PMA_messages.strToNextStep);
                }
                if(extra === 'gotoFinish') {
                    goToFinish();
                } else {
                    setTimeout(function() {
                        goToStep3();
                    }, 3000);
                }
            } else {
                //form to select columns to make primary
                $("#mainContent #extra").html(data.extra);
            }
        }
    );
}

AJAX.registerTeardown('normalization.js', function () {
    $("#extra").off("click", "#selectNonAtomicCol");
    $("#splitGo").unbind('click');
    $('.tblFooters').off("click", "#saveSplit");
    $("#extra").off("click", "#addNewPrimary");
    $(".tblFooters").off("click", "#saveNewPrimary");
    $("#extra").off("click", "#removeRedundant");
    $("#mainContent p").off("click", "#createPrimaryKey");
});

AJAX.registerOnload('normalization.js', function() {
    var selectedCol;
    $("#extra").on("click", "#selectNonAtomicCol", function() {
        if ($(this).val() === 'no_such_col') {
            goToStep2();
        } else {
            selectedCol = $(this).val();
        }
    });

    $("#splitGo").click(function() {
        if(!selectedCol || selectedCol === '') {
            return false;
        }
        var numField = $("#numField").val();
        $.get(
            "normalization.php",
            {
                "token": PMA_commonParams.get('token'),
                "ajax_request": true,
                "db": PMA_commonParams.get('db'),
                "table": PMA_commonParams.get('table'),
                "splitColumn": true,
                "numFields": numField
            },
        function(data) {
                if (data.success === true) {
                    $('#newCols').html(data.message);
                    $('.default_value').hide();
                    $('.enum_notice').hide();
                    $('.tblFooters').html("<input type='submit' id='saveSplit' value='"+PMA_messages.strSave+"'/>" +
                        "<input type='submit' id='cancelSplit' value='"+PMA_messages.strCancel+"' "+
                        "onclick=\"$('#newCols').html('');$(this).parent().html('')\"/>");
                }
            }
        );
        return false;
    });
    $('.tblFooters').on("click","#saveSplit", function() {
        central_column_list = [];
        if ($("#newCols #field_0_1").val() === '') {
            $("#newCols #field_0_1").focus();
            return false;
        }
        datastring = $('#newCols :input').serialize();
        datastring += "&ajax_request=1&do_save_data=1&field_where=last";
        $.post("tbl_addfield.php", datastring, function(data) {
            if (data.success) {
                $.get(
                    "sql.php",
                    {
                        "token": PMA_commonParams.get('token'),
                        "ajax_request": true,
                        "db": PMA_commonParams.get('db'),
                        "table": PMA_commonParams.get('table'),
                        "dropped_column": selectedCol,
                        "sql_query": 'ALTER TABLE `' + PMA_commonParams.get('table') + '` DROP `' + selectedCol + '`;',
                        "is_js_confirmed": 1
                    },
                function(data) {
                        if (data.success === true) {
                            appendHtmlColumnsList();
                            $('#newCols').html('');
                            $('.tblFooters').html('');
                        } else {
                            PMA_ajaxShowMessage(data.error, false);
                        }
                        selectedCol = '';
                    }
                );
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    });

    $("#extra").on("click", "#addNewPrimary", function() {
        $.get(
            "normalization.php",
            {
                "token": PMA_commonParams.get('token'),
                "ajax_request": true,
                "db": PMA_commonParams.get('db'),
                "table": PMA_commonParams.get('table'),
                "addNewPrimary": true
            },
        function(data) {
                if (data.success === true) {
                    $('#newCols').html(data.message);
                    $('.default_value').hide();
                    $('.enum_notice').hide();
                    $('.tblFooters').html("<input type='submit' id='saveNewPrimary' value='"+PMA_messages.strSave+"'/>" +
                        "<input type='submit' id='cancelSplit' value='"+PMA_messages.strCancel+"' "+
                        "onclick=\"$('#newCols').html('');$(this).parent().html('')\"/>");
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        );
        return false;
    });
    $(".tblFooters").on("click", "#saveNewPrimary", function() {
        datastring = $('#newCols :input').serialize();
        datastring += "&field_key[0]=primary_0&ajax_request=1&do_save_data=1&field_where=last";
        $.post("tbl_addfield.php", datastring, function(data) {
            if (data.success === true) {
                $("#mainContent h4").html(PMA_messages.strPrimaryKeyAdded);
                $("#mainContent p").html(PMA_messages.strToNextStep);
                $("#mainContent #extra").html('');
                $("#mainContent #newCols").html('');
                $('.tblFooters').html('');
                setTimeout(function() {
                    goToStep3();
                }, 2000);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    });
    $("#extra").on("click", "#removeRedundant", function() {
        var dropQuery = 'ALTER TABLE `' + PMA_commonParams.get('table') + '` ';
        $("#extra input[type=checkbox]:checked").each(function() {
            dropQuery += 'DROP `' + $(this).val() + '`, ';
        });
        dropQuery = dropQuery.slice(0, -2);
        $.get(
            "sql.php",
            {
                "token": PMA_commonParams.get('token'),
                "ajax_request": true,
                "db": PMA_commonParams.get('db'),
                "table": PMA_commonParams.get('table'),
                "sql_query": dropQuery,
                "is_js_confirmed": 1
            },
        function(data) {
                if (data.success === true) {
                    goToStep2('gotoFinish');
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        );
    });

    $("#mainContent p").on("click", "#createPrimaryKey", function(event) {
        event.preventDefault();
        var url = { create_index: 1,
            server:  PMA_commonParams.get('server'),
            db: PMA_commonParams.get('db'),
            table: PMA_commonParams.get('table'),
            token: PMA_commonParams.get('token'),
            added_fields: 1,
            add_fields:1,
            index: {Key_name:'PRIMARY'},
            ajax_request: true
        };
        var title = PMA_messages.strAddPrimaryKey;
        indexEditorDialog(url, title, function(){
            //on success
            $("#sqlqueryresults").remove();
            $('#result_query').remove();
            $('.tblFooters').html('');
            goToStep2('goToStep3');
        });
        return false;
    });
});