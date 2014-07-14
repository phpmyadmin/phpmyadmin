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

var normalizeto = '1nf';

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

function goTo2NFStep1() {
    $.post(
        "normalization.php",
        {
            "token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "step": '2.1'
        }, function(data) {
            $("#page_content h3").html(PMA_messages.str2NFNormalization);
            $("#mainContent legend").html(data.legendText);
            $("#mainContent h4").html(data.headText);
            $("#mainContent p").html(data.subText);
            $("#mainContent #extra").html(data.extra);
            $("#mainContent #newCols").html('');
            if (data.subText !== '') {
                $('.tblFooters').html('<input type="submit" value="'+PMA_messages.strDone+'" onclick="processPartialDependancies(\''+data.primary_key+'\');">');
            }
        });
}

function goToFinish1NF()
{
    if (normalizeto !== '1nf') {
        goTo2NFStep1();
        return true;
    }
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
                if(extra === 'goToFinish1NF') {
                    goToFinish1NF();
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
function goTo2NFFinish(pd)
{
    var tables = {};
    for (var dependson in pd) {
        tables[dependson] = $('#extra input[name="'+dependson+'"]').val();
    }
    datastring = {"token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "pd": JSON.stringify(pd),
            "newTablesName":JSON.stringify(tables),
            "createNewTables2NF":1};
    $.ajax({
            type: "GET",
            url: "normalization.php",
            data: datastring,
            async:false,
            success: function(data) {
                if (data.success === true) {
                    if(data.queryError === false) {
                        $("#mainContent legend").html(data.legendText);
                        $("#mainContent h4").html(data.headText);
                        $("#mainContent p").html('');
                        $("#mainContent #extra").html('');
                        $('.tblFooters').html('');
                    } else {
                        PMA_ajaxShowMessage(data.extra, false);
                    }
                    $("#pma_navigation_reload").click();
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        });
}
var backup = '';
function goTo2NFStep2(pd, primary_key)
{
    $("#newCols").html('');
    $("#mainContent legend").html(PMA_messages.strStep+' 2.2 '+PMA_messages.strConfirmPd);
    $("#mainContent h4").html(PMA_messages.strSelectedPd);
    $("#mainContent p").html(PMA_messages.strPdHintNote);
    var extra = '<div class="dependencies_box">';
    var pdFound = false;
    for (var dependson in pd) {
        if (dependson !== primary_key) {
            pdFound = true;
            extra += '<p class="displayblock desc">'+dependson +" -> "+pd[dependson].toString()+'</p>';
        }
    }
    if(!pdFound) {
        extra += '<p class="displayblock desc">'+PMA_messages.strNoPdSelected+'</p>';
        extra += '</div>';
    } else {
        extra += '</div>';
        datastring = {"token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "pd": JSON.stringify(pd),
            "getNewTables2NF":1};
        $.ajax({
            type: "GET",
            url: "normalization.php",
            data: datastring,
            async:false,
            success: function(data) {
                if (data.success === true) {
                    extra += data.message;
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        });
    }
    $("#mainContent #extra").html(extra);
    $('.tblFooters').html('<input type="button" value="'+PMA_messages.strBack+'" id="backEditPd"/><input type="button" id="goTo2NFFinish" value="'+PMA_messages.strGo+'"/>');
    $("#goTo2NFFinish").click(function(){
        goTo2NFFinish(pd);
    });
}

function processPartialDependancies(primary_key)
{
    var pd = {};
    var dependsOn;
    pd[primary_key] = [];
    $("#extra form").each(function() {
        form_id = $(this).attr('id');
        $('#'+form_id+' input[type=checkbox]:not(:checked)').removeAttr('checked');
        dependsOn = '';
        $('#'+form_id+' input[type=checkbox]:checked').each(function(){
            dependsOn += $(this).val()+', ';
            $(this).attr("checked","checked");
        });
        if (dependsOn === '') {
            $('#'+form_id+' input[type=checkbox]').each(function(){
                dependsOn = primary_key;
            });
        } else {
            dependsOn = dependsOn.slice(0, -2);
        }
        if (! (dependsOn in pd)) {
            pd[dependsOn] = [];
        }
        pd[dependsOn].push($(this).data('colname'));
    });
    backup = $("#mainContent").html();
    goTo2NFStep2(pd, primary_key);
    return false;
}

AJAX.registerTeardown('normalization.js', function () {
    $("#extra").off("click", "#selectNonAtomicCol");
    $("#splitGo").unbind('click');
    $('.tblFooters').off("click", "#saveSplit");
    $("#extra").off("click", "#addNewPrimary");
    $(".tblFooters").off("click", "#saveNewPrimary");
    $("#extra").off("click", "#removeRedundant");
    $("#mainContent p").off("click", "#createPrimaryKey");
    $("#mainContent").off("click", "#backEditPd");
    $("#mainContent").off("click", "#showPossiblePd");
    $("#mainContent").off("click", ".pickPd");
});

AJAX.registerOnload('normalization.js', function() {
    var selectedCol;
    normalizeto = $("#mainContent").data('normalizeto');
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
                    goToStep2('goToFinish1NF');
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
    $("#mainContent").on("click", "#backEditPd", function(){
        $("#mainContent").html(backup);
    });
    $("#mainContent").on("click", "#showPossiblePd", function(){
        if($(this).hasClass('hideList')) {
            $(this).html('+ '+PMA_messages.strShowPossiblePd);
            $(this).removeClass('hideList');
            $("#newCols").slideToggle("slow");
            return false;
        }
        if($("#newCols").html() !== '') {
            $("#showPossiblePd").html('- '+PMA_messages.strHidePd);
            $("#showPossiblePd").addClass('hideList');
            $("#newCols").slideToggle("slow");
            return false;
        }
        $("#newCols").insertAfter("#mainContent h4");
        $("#newCols").html('<div class="center">'+PMA_messages.strLoading+'<br/>'+PMA_messages.strWaitForPd+'</div>');
        $.post(
        "normalization.php",
        {
            "token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "findPdl": true
        }, function(data) {
            $("#showPossiblePd").html('- '+PMA_messages.strHidePd);
            $("#showPossiblePd").addClass('hideList');
            $("#newCols").html(data.message);
        });
    });
    $("#mainContent").on("click", ".pickPd", function(){
        var strColsLeft = $(this).next('.determinants').html();
        var colsLeft = strColsLeft.split(',');
        var strColsRight = $(this).next().next().html();
        var colsRight = strColsRight.split(',');
        for (var i in colsRight) {
            $('form[data-colname="'+colsRight[i].trim()+'"] input[type="checkbox"]').removeAttr('checked');
            for (var j in colsLeft) {
                $('form[data-colname="'+colsRight[i].trim()+'"] input[value="'+colsLeft[j].trim()+'"]').attr('checked','checked');
            }
        }
    });
});