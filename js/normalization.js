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
var primary_key;
var data_parsed = null;
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
function goTo3NFStep1(newTables)
{
    if (Object.keys(newTables).length === 1) {
        newTables = [PMA_commonParams.get('table')];
    }
    $.post(
        "normalization.php",
        {
            "token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "tables": newTables,
            "step": '3.1'
        }, function(data) {
            $("#page_content").find("h3").html(PMA_messages.str3NFNormalization);
            $("#mainContent").find("legend").html(data.legendText);
            $("#mainContent").find("h4").html(data.headText);
            $("#mainContent").find("p").html(data.subText);
            $("#mainContent").find("#extra").html(data.extra);
            $("#extra").find("form").each(function() {
                var form_id = $(this).attr('id');
                var colname = $(this).data('colname');
                $("#" + form_id + " input[value='" + colname + "']").next().remove();
                $("#" + form_id + " input[value='" + colname + "']").remove();
            });
            $("#mainContent").find("#newCols").html('');
            $('.tblFooters').html('');
            if (data.subText !== "") {
                $('.tblFooters').html('<input type="button" onClick="processDependencies(\'\', true);" value="' + PMA_messages.strDone + '"/>');
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
                $('.tblFooters').html('<input type="submit" value="' + PMA_messages.strDone + '" onclick="processDependencies(\'' + escapeJsString(escapeHtml(data.primary_key)) + '\');">');
            } else {
                if (normalizeto === '3nf') {
                    $("#mainContent #newCols").html(PMA_messages.strToNextStep);
                    setTimeout(function() {
                        goTo3NFStep1([PMA_commonParams.get('table')]);
                    }, 3000);
                }
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
        "<h3>" + PMA_sprintf(PMA_messages.strFinishMsg, escapeHtml(PMA_commonParams.get('table'))) + "</h3>"
    );
    $("#mainContent p").html('');
    $("#mainContent #extra").html('');
    $("#mainContent #newCols").html('');
    $('.tblFooters').html('');
}

function goToStep4()
{
    $.post(
        "normalization.php",
        {
            "token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "step4": true
        }, function(data) {
            $("#mainContent legend").html(data.legendText);
            $("#mainContent h4").html(data.headText);
            $("#mainContent p").html(data.subText);
            $("#mainContent #extra").html(data.extra);
            $("#mainContent #newCols").html('');
            $('.tblFooters').html('');
            for(var pk in primary_key) {
                $("#extra input[value='" + escapeJsString(primary_key[pk]) + "']").attr("disabled","disabled");
            }
        }
    );
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
            primary_key = JSON.parse(data.primary_key);
            for(var pk in primary_key) {
                $("#extra input[value='" + escapeJsString(primary_key[pk]) + "']").attr("disabled","disabled");
            }
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
        tables[dependson] = $('#extra input[name="' + dependson + '"]').val();
    }
    datastring = {"token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "pd": JSON.stringify(pd),
            "newTablesName":JSON.stringify(tables),
            "createNewTables2NF":1};
    $.ajax({
            type: "POST",
            url: "normalization.php",
            data: datastring,
            async:false,
            success: function(data) {
                if (data.success === true) {
                    if(data.queryError === false) {
                        if (normalizeto === '3nf') {
                            $("#pma_navigation_reload").click();
                            goTo3NFStep1(tables);
                            return true;
                        }
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

function goTo3NFFinish(newTables)
{
    for (var table in newTables) {
        for (var newtbl in newTables[table]) {
            var updatedname = $('#extra input[name="' + newtbl + '"]').val();
            newTables[table][updatedname] = newTables[table][newtbl];
            if (updatedname !== newtbl) {
                delete newTables[table][newtbl];
            }
        }
    }
    datastring = {"token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "newTables":JSON.stringify(newTables),
            "createNewTables3NF":1};
    $.ajax({
            type: "POST",
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
    $("#mainContent legend").html(PMA_messages.strStep + ' 2.2 ' + PMA_messages.strConfirmPd);
    $("#mainContent h4").html(PMA_messages.strSelectedPd);
    $("#mainContent p").html(PMA_messages.strPdHintNote);
    var extra = '<div class="dependencies_box">';
    var pdFound = false;
    for (var dependson in pd) {
        if (dependson !== primary_key) {
            pdFound = true;
            extra += '<p class="displayblock desc">' + escapeHtml(dependson) + " -> " + escapeHtml(pd[dependson].toString()) + '</p>';
        }
    }
    if(!pdFound) {
        extra += '<p class="displayblock desc">' + PMA_messages.strNoPdSelected + '</p>';
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
            type: "POST",
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
    $('.tblFooters').html('<input type="button" value="' + PMA_messages.strBack + '" id="backEditPd"/><input type="button" id="goTo2NFFinish" value="' + PMA_messages.strGo + '"/>');
    $("#goTo2NFFinish").click(function(){
        goTo2NFFinish(pd);
    });
}

function goTo3NFStep2(pd, tablesTds)
{
    $("#newCols").html('');
    $("#mainContent legend").html(PMA_messages.strStep + ' 3.2 ' + PMA_messages.strConfirmTd);
    $("#mainContent h4").html(PMA_messages.strSelectedTd);
    $("#mainContent p").html(PMA_messages.strPdHintNote);
    var extra = '<div class="dependencies_box">';
    var pdFound = false;
    for (var table in tablesTds) {
        for (var i in tablesTds[table]) {
            dependson = tablesTds[table][i];
            if (dependson !== '' && dependson !== table) {
                pdFound = true;
                extra += '<p class="displayblock desc">' + escapeHtml(dependson) + " -> " + escapeHtml(pd[dependson].toString()) + '</p>';
            }
        }
    }
    if(!pdFound) {
        extra += '<p class="displayblock desc">' + PMA_messages.strNoTdSelected + '</p>';
        extra += '</div>';
    } else {
        extra += '</div>';
        datastring = {"token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "tables": JSON.stringify(tablesTds),
            "pd": JSON.stringify(pd),
            "getNewTables3NF":1};
        $.ajax({
            type: "POST",
            url: "normalization.php",
            data: datastring,
            async:false,
            success: function(data) {
                data_parsed = data;
                if (data.success === true) {
                    extra += data_parsed.html;
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        });
    }
    $("#mainContent #extra").html(extra);
    $('.tblFooters').html('<input type="button" value="' + PMA_messages.strBack + '" id="backEditPd"/><input type="button" id="goTo3NFFinish" value="' + PMA_messages.strGo + '"/>');
    $("#goTo3NFFinish").click(function(){
        if (!pdFound) {
            goTo3NFFinish([]);
        } else {
            goTo3NFFinish(data_parsed.newTables);
        }
    });
}
function processDependencies(primary_key, isTransitive)
{
    var pd = {};
    var tablesTds = {};
    var dependsOn;
    pd[primary_key] = [];
    $("#extra form").each(function() {
        var tblname;
        if (isTransitive === true) {
            tblname = $(this).data('tablename');
            primary_key = tblname;
            if (!(tblname in tablesTds)) {
                tablesTds[tblname] = [];
            }
            tablesTds[tblname].push(primary_key);
        }
        var form_id = $(this).attr('id');
        $('#' + form_id + ' input[type=checkbox]:not(:checked)').prop('checked', false);
        dependsOn = '';
        $('#' + form_id + ' input[type=checkbox]:checked').each(function(){
            dependsOn += $(this).val() + ', ';
            $(this).attr("checked","checked");
        });
        if (dependsOn === '') {
            dependsOn = primary_key;
        } else {
            dependsOn = dependsOn.slice(0, -2);
        }
        if (! (dependsOn in pd)) {
            pd[dependsOn] = [];
        }
        pd[dependsOn].push($(this).data('colname'));
        if (isTransitive === true) {
            if (!(tblname in tablesTds)) {
                tablesTds[tblname] = [];
            }
            if ($.inArray(dependsOn, tablesTds[tblname]) === -1) {
                tablesTds[tblname].push(dependsOn);
            }
        }
    });
    backup = $("#mainContent").html();
    if (isTransitive === true) {
        goTo3NFStep2(pd, tablesTds);
    } else {
        goTo2NFStep2(pd, primary_key);
    }
    return false;
}

function moveRepeatingGroup(repeatingCols) {
    var newTable = $("input[name=repeatGroupTable]").val();
    var newColumn = $("input[name=repeatGroupColumn]").val();
    if (!newTable) {
        $("input[name=repeatGroupTable]").focus();
        return false;
    }
    if (!newColumn) {
        $("input[name=repeatGroupColumn]").focus();
        return false;
    }
    datastring = {"token": PMA_commonParams.get('token'),
        "ajax_request": true,
        "db": PMA_commonParams.get('db'),
        "table": PMA_commonParams.get('table'),
        "repeatingColumns": repeatingCols,
        "newTable":newTable,
        "newColumn":newColumn,
        "primary_columns":primary_key.toString()
    };
    $.ajax({
        type: "POST",
        url: "normalization.php",
        data: datastring,
        async:false,
        success: function(data) {
            if (data.success === true) {
                if(data.queryError === false) {
                    goToStep3();
                }
                PMA_ajaxShowMessage(data.message, false);
                $("#pma_navigation_reload").click();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }
    });
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
                    $('.tblFooters').html("<input type='submit' id='saveSplit' value='" + PMA_messages.strSave + "'/>" +
                        "<input type='submit' id='cancelSplit' value='" + PMA_messages.strCancel + "' " +
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
                $.post(
                    "sql.php",
                    {
                        "token": PMA_commonParams.get('token'),
                        "ajax_request": true,
                        "db": PMA_commonParams.get('db'),
                        "table": PMA_commonParams.get('table'),
                        "dropped_column": selectedCol,
                        "purge" : 1,
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
                    $('.tblFooters').html("<input type='submit' id='saveNewPrimary' value='" + PMA_messages.strSave + "'/>" +
                        "<input type='submit' id='cancelSplit' value='" + PMA_messages.strCancel + "' " +
                        "onclick=\"$('#newCols').html('');$(this).parent().html('')\"/>");
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        );
        return false;
    });
    $(".tblFooters").on("click", "#saveNewPrimary", function() {
        var datastring = $('#newCols :input').serialize();
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
        $.post(
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
    $("#extra").on("click", "#moveRepeatingGroup", function() {
        var repeatingCols = '';
        $("#extra input[type=checkbox]:checked").each(function() {
            repeatingCols += $(this).val() + ', ';
        });

        if (repeatingCols !== '') {
            var newColName = $("#extra input[type=checkbox]:checked:first").val();
            repeatingCols = repeatingCols.slice(0, -2);
            var confirmStr = PMA_sprintf(PMA_messages.strMoveRepeatingGroup, escapeHtml(repeatingCols), escapeHtml(PMA_commonParams.get('table')));
            confirmStr += '<input type="text" name="repeatGroupTable" placeholder="' + PMA_messages.strNewTablePlaceholder + '"/>' +
                '( ' + escapeHtml(primary_key.toString()) + ', <input type="text" name="repeatGroupColumn" placeholder="' + PMA_messages.strNewColumnPlaceholder + '" value="' + escapeHtml(newColName) + '">)' +
                '</ol>';
            $("#newCols").html(confirmStr);
            $('.tblFooters').html('<input type="submit" value="' + PMA_messages.strCancel + '" onclick="$(\'#newCols\').html(\'\');$(\'#extra input[type=checkbox]\').prop(\'checked\', false)"/>' +
                '<input type="submit" value="' + PMA_messages.strGo + '" onclick="moveRepeatingGroup(\'' + escapeJsString(escapeHtml(repeatingCols)) + '\')"/>');
        }
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
            $(".sqlqueryresults").remove();
            $('.result_query').remove();
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
            $(this).html('+ ' + PMA_messages.strShowPossiblePd);
            $(this).removeClass('hideList');
            $("#newCols").slideToggle("slow");
            return false;
        }
        if($("#newCols").html() !== '') {
            $("#showPossiblePd").html('- ' + PMA_messages.strHidePd);
            $("#showPossiblePd").addClass('hideList');
            $("#newCols").slideToggle("slow");
            return false;
        }
        $("#newCols").insertAfter("#mainContent h4");
        $("#newCols").html('<div class="center">' + PMA_messages.strLoading + '<br/>' + PMA_messages.strWaitForPd + '</div>');
        $.post(
        "normalization.php",
        {
            "token": PMA_commonParams.get('token'),
            "ajax_request": true,
            "db": PMA_commonParams.get('db'),
            "table": PMA_commonParams.get('table'),
            "findPdl": true
        }, function(data) {
            $("#showPossiblePd").html('- ' + PMA_messages.strHidePd);
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
            $('form[data-colname="' + colsRight[i].trim() + '"] input[type="checkbox"]').prop('checked', false);
            for (var j in colsLeft) {
                $('form[data-colname="' + colsRight[i].trim() + '"] input[value="' + colsLeft[j].trim() + '"]').prop('checked', true);
            }
        }
    });
});
