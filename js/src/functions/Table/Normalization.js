/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module Import
 */
import { PMA_Messages as PMA_messages } from '../../variables/export_variables';
import PMA_commonParams from '../../variables/common_params';
import { PMA_sprintf } from '../../utils/sprintf';
import { escapeHtml, escapeJsString } from '../../utils/Sanitise';
import { PMA_ajaxShowMessage } from '../../utils/show_ajax_messages';
import NormalizationEnum from '../../utils/NormalizationEnum';

export function appendHtmlColumnsList () {
    $.get(
        'normalization.php',
        {
            'ajax_request': true,
            'db': PMA_commonParams.get('db'),
            'table': PMA_commonParams.get('table'),
            'getColumns': true
        },
        function (data) {
            if (data.success === true) {
                $('select[name=makeAtomic]').html(data.message);
            }
        }
    );
}

function goTo3NFStep1 (newTables) {
    if (Object.keys(newTables).length === 1) {
        newTables = [PMA_commonParams.get('table')];
    }
    $.post(
        'normalization.php',
        {
            'ajax_request': true,
            'db': PMA_commonParams.get('db'),
            'tables': newTables,
            'step': '3.1'
        }, function (data) {
            $('#page_content').find('h3').html(PMA_messages.str3NFNormalization);
            $('#mainContent').find('legend').html(data.legendText);
            $('#mainContent').find('h4').html(data.headText);
            $('#mainContent').find('p').html(data.subText);
            $('#mainContent').find('#extra').html(data.extra);
            $('#extra').find('form').each(function () {
                var form_id = $(this).attr('id');
                var colname = $(this).data('colname');
                $('#' + form_id + ' input[value=\'' + colname + '\']').next().remove();
                $('#' + form_id + ' input[value=\'' + colname + '\']').remove();
            });
            $('#mainContent').find('#newCols').html('');
            $('.tblFooters').html('');

            if (data.subText !== '') {
                $('<input/>')
                    .attr({ type: 'button', value: PMA_messages.strDone })
                    .on('click', function () {
                        processDependencies('', true);
                    })
                    .appendTo('.tblFooters');
            }
        }
    );
}

function goTo2NFStep1 () {
    $.post(
        'normalization.php',
        {
            'ajax_request': true,
            'db': PMA_commonParams.get('db'),
            'table': PMA_commonParams.get('table'),
            'step': '2.1'
        }, function (data) {
            $('#page_content h3').html(PMA_messages.str2NFNormalization);
            $('#mainContent legend').html(data.legendText);
            $('#mainContent h4').html(data.headText);
            $('#mainContent p').html(data.subText);
            $('#mainContent #extra').html(data.extra);
            $('#mainContent #newCols').html('');
            if (data.subText !== '') {
                var doneButton = $('<input />')
                    .attr({ type: 'submit', value: PMA_messages.strDone, })
                    .on('click', function () {
                        processDependencies(data.primary_key);
                    })
                    .appendTo('.tblFooters');
            } else {
                if (NormalizationEnum.normalizeto === '3nf') {
                    $('#mainContent #newCols').html(PMA_messages.strToNextStep);
                    setTimeout(function () {
                        goTo3NFStep1([PMA_commonParams.get('table')]);
                    }, 3000);
                }
            }
        });
}

function goToFinish1NF () {
    if (NormalizationEnum.normalizeto !== '1nf') {
        goTo2NFStep1();
        return true;
    }
    $('#mainContent legend').html(PMA_messages.strEndStep);
    $('#mainContent h4').html(
        '<h3>' + PMA_sprintf(PMA_messages.strFinishMsg, escapeHtml(PMA_commonParams.get('table'))) + '</h3>'
    );
    $('#mainContent p').html('');
    $('#mainContent #extra').html('');
    $('#mainContent #newCols').html('');
    $('.tblFooters').html('');
}

function goToStep4 () {
    $.post(
        'normalization.php',
        {
            'ajax_request': true,
            'db': PMA_commonParams.get('db'),
            'table': PMA_commonParams.get('table'),
            'step4': true
        }, function (data) {
            $('#mainContent legend').html(data.legendText);
            $('#mainContent h4').html(data.headText);
            $('#mainContent p').html(data.subText);
            $('#mainContent #extra').html(data.extra);
            $('#mainContent #newCols').html('');
            $('.tblFooters').html('');
            for (var pk in NormalizationEnum.primary_key) {
                $('#extra input[value=\'' + escapeJsString(NormalizationEnum.primary_key[pk]) + '\']').attr('disabled','disabled');
            }
        }
    );
}

export function goToStep3 () {
    $.post(
        'normalization.php',
        {
            'ajax_request': true,
            'db': PMA_commonParams.get('db'),
            'table': PMA_commonParams.get('table'),
            'step3': true
        }, function (data) {
            $('#mainContent legend').html(data.legendText);
            $('#mainContent h4').html(data.headText);
            $('#mainContent p').html(data.subText);
            $('#mainContent #extra').html(data.extra);
            $('#mainContent #newCols').html('');
            $('.tblFooters').html('');
            NormalizationEnum.primary_key = JSON.parse(data.primary_key);
            for (var pk in NormalizationEnum.primary_key) {
                $('#extra input[value=\'' + escapeJsString(NormalizationEnum.primary_key[pk]) + '\']').attr('disabled','disabled');
            }
        }
    );
}

export function goToStep2 (extra) {
    $.post(
        'normalization.php',
        {
            'ajax_request': true,
            'db': PMA_commonParams.get('db'),
            'table': PMA_commonParams.get('table'),
            'step2': true
        }, function (data) {
            $('#mainContent legend').html(data.legendText);
            $('#mainContent h4').html(data.headText);
            $('#mainContent p').html(data.subText);
            $('#mainContent #extra,#mainContent #newCols').html('');
            $('.tblFooters').html('');
            if (data.hasPrimaryKey === '1') {
                if (extra === 'goToStep3') {
                    $('#mainContent h4').html(PMA_messages.strPrimaryKeyAdded);
                    $('#mainContent p').html(PMA_messages.strToNextStep);
                }
                if (extra === 'goToFinish1NF') {
                    goToFinish1NF();
                } else {
                    setTimeout(function () {
                        goToStep3();
                    }, 3000);
                }
            } else {
                // form to select columns to make primary
                $('#mainContent #extra').html(data.extra);
            }
        }
    );
}

function goTo2NFFinish (pd) {
    var tables = {};
    for (var dependson in pd) {
        tables[dependson] = $('#extra input[name="' + dependson + '"]').val();
    }
    var datastring = {
        'ajax_request': true,
        'db': PMA_commonParams.get('db'),
        'table': PMA_commonParams.get('table'),
        'pd': JSON.stringify(pd),
        'newTablesName':JSON.stringify(tables),
        'createNewTables2NF':1
    };
    $.ajax({
        type: 'POST',
        url: 'normalization.php',
        data: datastring,
        async:false,
        success: function (data) {
            if (data.success === true) {
                if (data.queryError === false) {
                    if (NormalizationEnum.normalizeto === '3nf') {
                        $('#pma_navigation_reload').trigger('click');
                        goTo3NFStep1(tables);
                        return true;
                    }
                    $('#mainContent legend').html(data.legendText);
                    $('#mainContent h4').html(data.headText);
                    $('#mainContent p').html('');
                    $('#mainContent #extra').html('');
                    $('.tblFooters').html('');
                } else {
                    PMA_ajaxShowMessage(data.extra, false);
                }
                $('#pma_navigation_reload').trigger('click');
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }
    });
}

function goTo3NFFinish (newTables) {
    for (var table in newTables) {
        for (var newtbl in newTables[table]) {
            var updatedname = $('#extra input[name="' + newtbl + '"]').val();
            newTables[table][updatedname] = newTables[table][newtbl];
            if (updatedname !== newtbl) {
                delete newTables[table][newtbl];
            }
        }
    }
    var datastring = {
        'ajax_request': true,
        'db': PMA_commonParams.get('db'),
        'newTables':JSON.stringify(newTables),
        'createNewTables3NF':1
    };
    $.ajax({
        type: 'POST',
        url: 'normalization.php',
        data: datastring,
        async:false,
        success: function (data) {
            if (data.success === true) {
                if (data.queryError === false) {
                    $('#mainContent legend').html(data.legendText);
                    $('#mainContent h4').html(data.headText);
                    $('#mainContent p').html('');
                    $('#mainContent #extra').html('');
                    $('.tblFooters').html('');
                } else {
                    PMA_ajaxShowMessage(data.extra, false);
                }
                $('#pma_navigation_reload').trigger('click');
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }
    });
}

function goTo2NFStep2 (pd, primary_key) {
    $('#newCols').html('');
    $('#mainContent legend').html(PMA_messages.strStep + ' 2.2 ' + PMA_messages.strConfirmPd);
    $('#mainContent h4').html(PMA_messages.strSelectedPd);
    $('#mainContent p').html(PMA_messages.strPdHintNote);
    var extra = '<div class="dependencies_box">';
    var pdFound = false;
    for (var dependson in pd) {
        if (dependson !== primary_key) {
            pdFound = true;
            extra += '<p class="displayblock desc">' + escapeHtml(dependson) + ' -> ' + escapeHtml(pd[dependson].toString()) + '</p>';
        }
    }
    if (!pdFound) {
        extra += '<p class="displayblock desc">' + PMA_messages.strNoPdSelected + '</p>';
        extra += '</div>';
    } else {
        extra += '</div>';
        var datastring = {
            'ajax_request': true,
            'db': PMA_commonParams.get('db'),
            'table': PMA_commonParams.get('table'),
            'pd': JSON.stringify(pd),
            'getNewTables2NF':1
        };
        $.ajax({
            type: 'POST',
            url: 'normalization.php',
            data: datastring,
            async:false,
            success: function (data) {
                if (data.success === true) {
                    extra += data.message;
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        });
    }
    $('#mainContent #extra').html(extra);
    $('.tblFooters').html('<input type="button" value="' + PMA_messages.strBack + '" id="backEditPd"/><input type="button" id="goTo2NFFinish" value="' + PMA_messages.strGo + '"/>');
    $('#goTo2NFFinish').on('click', function () {
        goTo2NFFinish(pd);
    });
}

function goTo3NFStep2 (pd, tablesTds) {
    $('#newCols').html('');
    $('#mainContent legend').html(PMA_messages.strStep + ' 3.2 ' + PMA_messages.strConfirmTd);
    $('#mainContent h4').html(PMA_messages.strSelectedTd);
    $('#mainContent p').html(PMA_messages.strPdHintNote);
    var extra = '<div class="dependencies_box">';
    var pdFound = false;
    for (var table in tablesTds) {
        for (var i in tablesTds[table]) {
            var dependson = tablesTds[table][i];
            if (dependson !== '' && dependson !== table) {
                pdFound = true;
                extra += '<p class="displayblock desc">' + escapeHtml(dependson) + ' -> ' + escapeHtml(pd[dependson].toString()) + '</p>';
            }
        }
    }
    if (!pdFound) {
        extra += '<p class="displayblock desc">' + PMA_messages.strNoTdSelected + '</p>';
        extra += '</div>';
    } else {
        extra += '</div>';
        var datastring = {
            'ajax_request': true,
            'db': PMA_commonParams.get('db'),
            'tables': JSON.stringify(tablesTds),
            'pd': JSON.stringify(pd),
            'getNewTables3NF':1
        };
        $.ajax({
            type: 'POST',
            url: 'normalization.php',
            data: datastring,
            async:false,
            success: function (data) {
                NormalizationEnum.data_parsed = data;
                if (data.success === true) {
                    extra += NormalizationEnum.data_parsed.html;
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        });
    }
    $('#mainContent #extra').html(extra);
    $('.tblFooters').html('<input type="button" value="' + PMA_messages.strBack + '" id="backEditPd"/><input type="button" id="goTo3NFFinish" value="' + PMA_messages.strGo + '"/>');
    $('#goTo3NFFinish').on('click', function () {
        if (!pdFound) {
            goTo3NFFinish([]);
        } else {
            goTo3NFFinish(NormalizationEnum.data_parsed.newTables);
        }
    });
}

function processDependencies (primary_key, isTransitive) {
    var pd = {};
    var tablesTds = {};
    var dependsOn;
    pd[primary_key] = [];
    $('#extra form').each(function () {
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
        $('#' + form_id + ' input[type=checkbox]:checked').each(function () {
            dependsOn += $(this).val() + ', ';
            $(this).attr('checked','checked');
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
    NormalizationEnum.backup = $('#mainContent').html();
    if (isTransitive === true) {
        goTo3NFStep2(pd, tablesTds);
    } else {
        goTo2NFStep2(pd, primary_key);
    }
    return false;
}

export function moveRepeatingGroup (repeatingCols) {
    var newTable = $('input[name=repeatGroupTable]').val();
    var newColumn = $('input[name=repeatGroupColumn]').val();
    if (!newTable) {
        $('input[name=repeatGroupTable]').focus();
        return false;
    }
    if (!newColumn) {
        $('input[name=repeatGroupColumn]').focus();
        return false;
    }
    var datastring = {
        'ajax_request': true,
        'db': PMA_commonParams.get('db'),
        'table': PMA_commonParams.get('table'),
        'repeatingColumns': repeatingCols,
        'newTable': newTable,
        'newColumn': newColumn,
        'primary_columns': NormalizationEnum.primary_key.toString()
    };
    $.ajax({
        type: 'POST',
        url: 'normalization.php',
        data: datastring,
        async:false,
        success: function (data) {
            if (data.success === true) {
                if (data.queryError === false) {
                    goToStep3();
                }
                PMA_ajaxShowMessage(data.message, false);
                $('#pma_navigation_reload').trigger('click');
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }
    });
}
